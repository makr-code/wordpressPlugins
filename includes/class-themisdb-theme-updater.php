<?php
/**
 * ThemisDB Theme Updater
 *
 * Handles automatic updates from GitHub repository for ThemisDB themes.
 * Integrates with the WordPress theme update system (pre_set_site_transient_update_themes).
 *
 * @package ThemisDB
 * @version 1.0.0
 * @link https://github.com/makr-code/wordpressPlugins
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'ThemisDB_Theme_Updater' ) ) {

class ThemisDB_Theme_Updater {

    /** @var string GitHub username */
    private $username;

    /** @var string GitHub repository name */
    private $repository;

    /** @var string GitHub repository branch */
    private $repository_branch = 'main';

    /** @var string Optional sub-path inside the repository where the theme resides */
    private $repository_theme_path = '';

    /** @var string Theme slug (directory name inside wp-content/themes/) */
    private $theme_slug;

    /** @var string Currently installed version */
    private $version;

    /** @var string GitHub API base URL */
    private $github_api_url = 'https://api.github.com';

    /** @var int Transient cache duration in seconds (12 h) */
    private $cache_duration = 43200;

    /**
     * Initialise the updater and register WordPress hooks.
     *
     * @param string $theme_slug  Theme directory name (e.g. "themisdb-theme-v3").
     * @param string $version     Currently installed version string.
     * @param string $username    GitHub account (default: makr-code).
     * @param string $repository  GitHub repository (default: wordpressPlugins).
     */
    public function __construct( $theme_slug, $version, $username = 'makr-code', $repository = 'wordpressPlugins' ) {
        $this->theme_slug  = $theme_slug;
        $this->version     = $version;
        $this->username    = apply_filters( 'themisdb_theme_updater_repo_owner',  $username,   $theme_slug );
        $this->repository  = apply_filters( 'themisdb_theme_updater_repo_name',   $repository, $theme_slug );
        $this->repository_branch = apply_filters( 'themisdb_theme_updater_repo_branch', 'main', $theme_slug );

        $path = apply_filters( 'themisdb_theme_updater_repo_theme_path', '', $theme_slug );
        $this->repository_theme_path = trim( (string) $path, '/' );

        add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_for_update' ) );
        add_filter( 'themes_api',                           array( $this, 'theme_info' ), 10, 3 );
        add_filter( 'upgrader_post_install',                array( $this, 'after_install' ), 10, 3 );
        add_action( 'admin_init',                           array( $this, 'maybe_clear_cache' ) );
    }

    // -------------------------------------------------------------------------
    // WordPress update-system hooks
    // -------------------------------------------------------------------------

    /**
     * Inject update information into the WordPress themes transient.
     *
     * @param object $transient
     * @return object
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = array();
        }

        if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
            $transient->no_update = array();
        }

        $remote = $this->get_remote_version();

        if ( ! $remote ) {
            unset( $transient->response[ $this->theme_slug ] );
            return $transient;
        }

        $local_version  = $this->normalize_version( $this->version );
        $remote_version = $this->normalize_version( $remote->version );

        if ( version_compare( $local_version, $remote_version, '<' ) ) {
            $transient->response[ $this->theme_slug ] = array(
                'theme'       => $this->theme_slug,
                'new_version' => $remote_version,
                'url'         => $remote->homepage,
                'package'     => $remote->download_url,
                'requires'    => $remote->requires,
                'requires_php'=> $remote->requires_php,
            );
            unset( $transient->no_update[ $this->theme_slug ] );
        } else {
            // Tell WordPress the theme is current so it does not disappear from the list.
            unset( $transient->response[ $this->theme_slug ] );
            $transient->no_update[ $this->theme_slug ] = array(
                'theme'       => $this->theme_slug,
                'new_version' => $remote_version,
                'url'         => $remote->homepage,
                'package'     => $remote->download_url,
            );
        }

        return $transient;
    }

    /**
     * Provide theme information for the "View version details" modal.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object
     */
    public function theme_info( $result, $action, $args ) {
        if ( $action !== 'theme_information' ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== $this->theme_slug ) {
            return $result;
        }

        $remote = $this->get_remote_version();

        if ( ! $remote ) {
            return $result;
        }

        return (object) array(
            'name'          => $remote->name,
            'slug'          => $this->theme_slug,
            'version'       => $remote->version,
            'author'        => $remote->author,
            'author_profile'=> $remote->author_profile,
            'requires'      => $remote->requires,
            'tested'        => $remote->tested,
            'requires_php'  => $remote->requires_php,
            'download_link' => $remote->download_url,
            'last_updated'  => $remote->last_updated,
            'sections'      => array(
                'description' => $remote->description,
                'changelog'   => $remote->changelog,
            ),
        );
    }

    /**
     * Rename the extracted theme folder to the correct slug after installation.
     *
     * @param bool  $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function after_install( $response, $hook_extra, $result ) {
        // Only act for our theme.
        if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== $this->theme_slug ) {
            return $result;
        }

        global $wp_filesystem;

        $themes_dir  = trailingslashit( get_theme_root() );
        $destination = $themes_dir . $this->theme_slug;

        $wp_filesystem->move( $result['destination'], $destination );
        $result['destination'] = $destination;

        return $result;
    }

    /**
     * Clear the cached update data when the user clicks "Check Again".
     */
    public function maybe_clear_cache() {
        global $pagenow;

        if (
            $pagenow === 'update-core.php' &&
            isset( $_GET['force-check'] ) &&
            '1' === $_GET['force-check'] &&
            current_user_can( 'update_themes' ) &&
            check_admin_referer( 'update-core' )
        ) {
            delete_transient( 'themisdb_theme_update_' . $this->theme_slug );
        }
    }

    // -------------------------------------------------------------------------
    // Remote version resolution
    // -------------------------------------------------------------------------

    /**
     * Return cached or freshly fetched version information.
     *
     * @return object|false
     */
    private function get_remote_version() {
        $cache_key    = 'themisdb_theme_update_' . $this->theme_slug;
        $cached       = get_transient( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $metadata = $this->fetch_theme_metadata();

        if ( ! $metadata ) {
            return false;
        }

        $metadata_version = isset( $metadata['version'] ) ? (string) $metadata['version'] : '';
        $release          = $this->fetch_release_for_theme();

        if ( ! $release && ! empty( $metadata_version ) ) {
            $download_url = isset( $metadata['download_url'] )
                ? (string) $metadata['download_url']
                : $this->build_download_url_from_version( $metadata_version );

            $remote = $this->build_remote_object( $metadata, $download_url, gmdate( 'c' ), '' );
            set_transient( $cache_key, $remote, $this->cache_duration );
            return $remote;
        }

        if ( ! $release ) {
            return false;
        }

        $download_url    = $this->get_download_url( $release );
        $release_version = $this->extract_version_from_tag( $release->tag_name );
        $remote          = $this->build_remote_object( $metadata, $download_url, $release->published_at, isset( $release->body ) ? $release->body : '' );

        $remote->version = $this->resolve_remote_version(
            isset( $metadata['version'] ) ? (string) $metadata['version'] : '',
            $release_version
        );

        set_transient( $cache_key, $remote, $this->cache_duration );
        return $remote;
    }

    /**
     * Assemble a remote version object from metadata + release data.
     *
     * @param array  $metadata
     * @param string $download_url
     * @param string $last_updated  ISO 8601 timestamp
     * @param string $changelog
     * @return object
     */
    private function build_remote_object( $metadata, $download_url, $last_updated, $changelog ) {
        return (object) array(
            'version'       => isset( $metadata['version'] )     ? $metadata['version']     : '',
            'name'          => isset( $metadata['name'] )        ? $metadata['name']        : $this->theme_slug,
            'slug'          => $this->theme_slug,
            'homepage'      => isset( $metadata['homepage'] )    ? $metadata['homepage']    : "https://github.com/{$this->username}/{$this->repository}",
            'description'   => isset( $metadata['description'] ) ? $metadata['description'] : '',
            'author'        => isset( $metadata['author'] )      ? $metadata['author']      : 'makr-code',
            'author_profile'=> isset( $metadata['author_uri'] )  ? $metadata['author_uri']  : "https://github.com/{$this->username}",
            'requires'      => isset( $metadata['requires'] )    ? $metadata['requires']    : '6.3',
            'tested'        => isset( $metadata['tested'] )      ? $metadata['tested']      : '6.7',
            'requires_php'  => isset( $metadata['requires_php'] )? $metadata['requires_php']: '7.4',
            'download_url'  => $download_url,
            'last_updated'  => $last_updated,
            'changelog'     => ! empty( $changelog ) ? $changelog : ( isset( $metadata['changelog'] ) ? $metadata['changelog'] : '' ),
        );
    }

    // -------------------------------------------------------------------------
    // GitHub data fetching
    // -------------------------------------------------------------------------

    /**
     * Fetch theme metadata from an `update-info.json` file in the repository.
     *
     * @return array|false
     */
    private function fetch_theme_metadata() {
        $branches = array_values( array_unique( array_filter( array(
            $this->repository_branch,
            'main',
            'develop',
            'master',
        ) ) ) );

        $paths = array();

        if ( ! empty( $this->repository_theme_path ) ) {
            $paths[] = $this->repository_theme_path;
        }

        $paths[] = '';

        $paths = array_values( array_unique( $paths ) );

        foreach ( $branches as $branch ) {
            foreach ( $paths as $path ) {
                $prefix       = empty( $path ) ? '' : $path . '/';
                $metadata_url = "https://raw.githubusercontent.com/{$this->username}/{$this->repository}/{$branch}/{$prefix}{$this->theme_slug}/update-info.json";

                $response = wp_remote_get( $metadata_url, array(
                    'timeout' => 10,
                    'headers' => $this->build_request_headers( array( 'Accept' => 'application/json' ) ),
                ) );

                if ( is_wp_error( $response ) ) {
                    continue;
                }

                if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                    continue;
                }

                $metadata = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( is_array( $metadata ) ) {
                    return $metadata;
                }
            }
        }

        return false;
    }

    /**
     * Fetch the most relevant GitHub release for this theme.
     *
     * @return object|false
     */
    private function fetch_release_for_theme() {
        $api_url = "{$this->github_api_url}/repos/{$this->username}/{$this->repository}/releases?per_page=30";

        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'headers' => $this->build_request_headers( array( 'Accept' => 'application/vnd.github.v3+json' ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $this->fetch_latest_release();
        }

        $releases = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_array( $releases ) ) {
            return $this->fetch_latest_release();
        }

        foreach ( $releases as $release ) {
            if ( ! isset( $release->tag_name ) || ! empty( $release->draft ) ) {
                continue;
            }

            if ( $this->release_has_theme_asset( $release ) || $this->tag_matches_theme( $release->tag_name ) ) {
                return $release;
            }
        }

        return $this->fetch_latest_release();
    }

    /**
     * Fetch the single latest GitHub release.
     *
     * @return object|false
     */
    private function fetch_latest_release() {
        $api_url = "{$this->github_api_url}/repos/{$this->username}/{$this->repository}/releases/latest";

        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'headers' => $this->build_request_headers( array( 'Accept' => 'application/vnd.github.v3+json' ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ) );

        return isset( $release->tag_name ) ? $release : false;
    }

    /**
     * Check whether a release contains a zip asset for this theme.
     *
     * @param object $release
     * @return bool
     */
    private function release_has_theme_asset( $release ) {
        if ( ! isset( $release->assets ) || ! is_array( $release->assets ) ) {
            return false;
        }

        $expected = strtolower( $this->theme_slug . '.zip' );

        foreach ( $release->assets as $asset ) {
            if ( isset( $asset->name ) && strtolower( $asset->name ) === $expected ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a release tag belongs to this theme.
     *
     * Supported formats:
     *   themisdb-theme-v3/v3.1.0
     *   themisdb-theme-v3-v3.1.0
     *
     * @param string $tag_name
     * @return bool
     */
    private function tag_matches_theme( $tag_name ) {
        $tag  = strtolower( (string) $tag_name );
        $slug = strtolower( $this->theme_slug );

        return ( strpos( $tag, $slug . '/v' ) === 0 ) ||
               ( strpos( $tag, $slug . '-v'  ) === 0 ) ||
               ( strpos( $tag, $slug . '_v'  ) === 0 );
    }

    /**
     * Determine the download URL from a release object.
     *
     * @param object $release
     * @return string
     */
    private function get_download_url( $release ) {
        if ( isset( $release->assets ) && is_array( $release->assets ) ) {
            $exact = strtolower( $this->theme_slug . '.zip' );

            foreach ( $release->assets as $asset ) {
                if ( isset( $asset->name, $asset->browser_download_url ) &&
                     strtolower( $asset->name ) === $exact ) {
                    return $asset->browser_download_url;
                }
            }

            foreach ( $release->assets as $asset ) {
                if ( isset( $asset->name, $asset->browser_download_url ) &&
                     strpos( $asset->name, $this->theme_slug ) !== false &&
                     strpos( $asset->name, '.zip' )            !== false ) {
                    return $asset->browser_download_url;
                }
            }
        }

        return "https://github.com/{$this->username}/{$this->repository}/releases/download/{$release->tag_name}/{$this->theme_slug}.zip";
    }

    /**
     * Build a versioned download URL when no release asset is available.
     *
     * @param string $version
     * @return string
     */
    private function build_download_url_from_version( $version ) {
        $version = ltrim( (string) $version, 'v' );
        $tag     = $this->theme_slug . '/v' . $version;

        return "https://github.com/{$this->username}/{$this->repository}/releases/download/{$tag}/{$this->theme_slug}.zip";
    }

    /**
     * Extract a clean version number from a tag string.
     *
     * @param string $tag_name
     * @return string
     */
    private function extract_version_from_tag( $tag_name ) {
        $tag = (string) $tag_name;

        $patterns = array(
            '/^' . preg_quote( $this->theme_slug, '/' ) . '\/v/i',
            '/^' . preg_quote( $this->theme_slug, '/' ) . '-v/i',
            '/^' . preg_quote( $this->theme_slug, '/' ) . '_v/i',
            '/^v/i',
        );

        foreach ( $patterns as $pattern ) {
            $normalized = preg_replace( $pattern, '', $tag );
            if ( $normalized !== $tag ) {
                return $normalized;
            }
        }

        return $tag;
    }

    /**
     * Normalize version strings for reliable comparisons.
     *
     * @param string $version
     * @return string
     */
    private function normalize_version( $version ) {
        $normalized = ltrim( trim( (string) $version ), 'vV' );

        return $normalized === '' ? '0.0.0' : $normalized;
    }

    /**
     * Resolve remote version using metadata and release tag.
     *
     * If values diverge, prefer the release version so WordPress compares
     * against the actually downloadable artifact.
     *
     * @param string $metadata_version
     * @param string $release_version
     * @return string
     */
    private function resolve_remote_version( $metadata_version, $release_version ) {
        $metadata_normalized = $this->normalize_version( $metadata_version );
        $release_normalized  = $this->normalize_version( $release_version );

        if ( $metadata_normalized === '0.0.0' ) {
            return $release_normalized;
        }

        if ( $metadata_normalized === $release_normalized ) {
            return $metadata_normalized;
        }

        return $release_normalized;
    }

    // -------------------------------------------------------------------------
    // Request helpers
    // -------------------------------------------------------------------------

    /**
     * Build HTTP request headers, optionally adding a GitHub token.
     *
     * @param array $headers
     * @return array
     */
    private function build_request_headers( $headers = array() ) {
        $defaults = array(
            'User-Agent' => 'ThemisDB-Theme-Updater/' . $this->theme_slug,
        );

        $token = apply_filters( 'themisdb_theme_updater_github_token', '', $this->theme_slug );
        if ( empty( $token ) && defined( 'THEMISDB_GITHUB_TOKEN' ) ) {
            $token = THEMISDB_GITHUB_TOKEN;
        }

        if ( ! empty( $token ) ) {
            $defaults['Authorization'] = 'Bearer ' . trim( (string) $token );
        }

        return array_merge( $defaults, $headers );
    }
}

} // end class_exists guard

