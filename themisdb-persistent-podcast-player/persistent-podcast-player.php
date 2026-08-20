<?php
/**
 * Plugin Name: Persistent Podcast Player
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Plugin for ThemisDB.
 * Version: 1.0.1
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: persistent-podcast-player
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            persistent-podcast-player.php                      ║
  Version:         0.0.34                                             ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          makr-code                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     361                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */
/**
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PPP_VERSION', '1.0.1');
define('PPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PPP_PLUGIN_FILE', __FILE__);

// Load updater class (prefer local copy for standalone ZIP distribution)
$themisdb_updater_local = PPP_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(PPP_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        PPP_PLUGIN_FILE,
        'themisdb-persistent-podcast-player',
        PPP_VERSION
    );
}

/**
 * Main plugin class.
 */
class Persistent_Podcast_Player {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'register_post_type'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_body_open', array($this, 'render_player'));
        
        // Admin: media library integration
        add_action('add_meta_boxes', array($this, 'add_audio_meta_box'));
        add_action('add_meta_boxes_post', array($this, 'maybe_add_audio_meta_box_for_post'));
        add_action('save_post_pod_episode', array($this, 'save_audio_meta'), 10, 2);
        add_action('save_post_post', array($this, 'save_audio_meta_for_post'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_ppp_search_related_posts', array($this, 'ajax_search_related_posts'));
        add_filter('redirect_post_location', array($this, 'add_audio_notice_redirect_arg'), 10, 2);
        add_action('admin_notices', array($this, 'render_audio_admin_notice'));
        // Admin settings page
        add_action('admin_menu',  array($this, 'add_admin_menu'));
        add_action('admin_init',  array($this, 'register_settings'));
    }

    /**
     * Build normalized payload for theme adapter hooks.
     */
    private function get_player_payload() {
        $payload = array(
            'player_id' => 'ppp-player',
            'audio_id' => 'ppp-audio',
            'playlist_id' => 'ppp-playlist',
            'rest_url' => rest_url('persistent-player/v1/episodes'),
            'nonce' => wp_create_nonce('wp_rest'),
        );

        return apply_filters('themisdb_persistent_podcast_player_payload', $payload);
    }

    /**
     * Resolve optional full HTML override from theme.
     */
    private function resolve_player_html_override($payload) {
        $html = apply_filters('themisdb_persistent_podcast_player_html', null, $payload);
        return (null !== $html) ? (string) $html : null;
    }

    /**
     * Final output hook for post-processing player HTML.
     */
    private function finalize_player_html($html, $payload) {
        return apply_filters('themisdb_persistent_podcast_player_html_output', (string) $html, $payload);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Register custom post type
     */
    public function register_post_type() {
        $args = array(
            'label' => __('Podcast Episodes', 'persistent-podcast-player'),
            'public' => true,
            'show_in_rest' => true,
            'rest_base' => 'pod_episodes',
            'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
            'menu_icon' => 'dashicons-microphone',
            'has_archive' => true,
            'capability_type' => 'post',
            'labels' => array(
                'name' => __('Podcast Episodes', 'persistent-podcast-player'),
                'singular_name' => __('Podcast Episode', 'persistent-podcast-player'),
                'add_new' => __('Add New Episode', 'persistent-podcast-player'),
                'add_new_item' => __('Add New Episode', 'persistent-podcast-player'),
                'edit_item' => __('Edit Episode', 'persistent-podcast-player'),
                'new_item' => __('New Episode', 'persistent-podcast-player'),
                'view_item' => __('View Episode', 'persistent-podcast-player'),
                'search_items' => __('Search Episodes', 'persistent-podcast-player'),
                'not_found' => __('No episodes found', 'persistent-podcast-player'),
                'not_found_in_trash' => __('No episodes found in Trash', 'persistent-podcast-player'),
            ),
        );
        
        register_post_type('pod_episode', $args);
        
        // Register meta fields
        register_post_meta('pod_episode', 'audio_url', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_post_meta('pod_episode', 'audio_attachment_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_post_meta('pod_episode', 'related_post_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route('persistent-player/v1', '/episodes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_episodes'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Get episodes endpoint callback
     */
    public function get_episodes($request) {
        $args = array(
            'post_type' => 'pod_episode',
            'post_status' => 'publish',
            'posts_per_page' => max(1, (int) get_option('ppp_episodes_limit', 50)),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        $episodes = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get custom fields
                $audio_data = $this->resolve_episode_audio($post_id);
                $related_post_id = get_post_meta($post_id, 'related_post_id', true);
                
                // Get excerpt and permalink from related post
                $excerpt = '';
                $permalink = '';
                
                if ($related_post_id && get_post_status($related_post_id) === 'publish') {
                    $related_post = get_post($related_post_id);
                    if ($related_post) {
                        $excerpt = $related_post->post_excerpt 
                            ? $related_post->post_excerpt 
                            : wp_trim_words(strip_tags($related_post->post_content), apply_filters('ppp_excerpt_length', 30));
                        $permalink = get_permalink($related_post_id);
                    }
                }
                
                // Get thumbnail
                $thumbnail = array(
                    'full' => '',
                    'medium' => '',
                    'thumbnail' => '',
                );
                
                if (has_post_thumbnail($post_id)) {
                    $thumbnail_id = get_post_thumbnail_id($post_id);
                    $thumbnail['full'] = wp_get_attachment_image_url($thumbnail_id, 'full');
                    $thumbnail['medium'] = wp_get_attachment_image_url($thumbnail_id, 'medium');
                    $thumbnail['thumbnail'] = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                }
                
                $episodes[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'audio' => $audio_data['url'],
                    'audio_attachment_id' => $audio_data['attachment_id'],
                    'audio_source' => $audio_data['source'],
                    'desc' => strip_tags(get_the_content()),
                    'excerpt' => $excerpt,
                    'permalink' => $permalink,
                    'thumbnail' => $thumbnail,
                );
            }
            wp_reset_postdata();
        }
        
        return rest_ensure_response($episodes);
    }
    
    /**
     * Add meta box for audio file selection
     */
    public function add_audio_meta_box() {
        add_meta_box(
            'ppp-audio-meta-box',
            __('Audio-Datei', 'persistent-podcast-player'),
            array($this, 'render_audio_meta_box'),
            'pod_episode',
            'normal',
            'high'
        );
    }

    /**
     * Conditionally add audio meta box for regular posts in the Podcast category.
     *
     * @param WP_Post $post Current post object.
     */
    public function maybe_add_audio_meta_box_for_post( $post ) {
        $podcast_cat = get_option('ppp_podcast_category', 'podcast');
        if ( ! has_category( $podcast_cat, $post ) ) {
            return;
        }
        add_meta_box(
            'ppp-audio-meta-box',
            __('Audio-Datei', 'persistent-podcast-player'),
            array($this, 'render_audio_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    /**
     * Save audio meta for regular posts in the Podcast category.
     * No publish-blocking applied — only pod_episode requires mandatory audio.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_audio_meta_for_post( $post_id, $post ) {
        if ( ! isset( $_POST['ppp_audio_meta_box_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ppp_audio_meta_box_nonce'] ) ), 'ppp_audio_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST['ppp_audio_attachment_id'] ) ) {
            return;
        }

        $attachment_id = absint( $_POST['ppp_audio_attachment_id'] );
        if ( $attachment_id > 0 && $this->is_audio_attachment( $attachment_id ) ) {
            update_post_meta( $post_id, 'audio_attachment_id', $attachment_id );
            $attachment_url = wp_get_attachment_url( $attachment_id );
            if ( $attachment_url ) {
                update_post_meta( $post_id, 'audio_url', esc_url_raw( $attachment_url ) );
            }
            delete_post_meta( $post_id, '_ppp_audio_notice' );
            delete_post_meta( $post_id, '_ppp_audio_notice_mime' );
        } elseif ( $attachment_id === 0 ) {
            delete_post_meta( $post_id, 'audio_attachment_id' );
            delete_post_meta( $post_id, 'audio_url' );
        }
    }
    
    /**
     * Render audio meta box
     */
    public function render_audio_meta_box($post) {
        wp_nonce_field('ppp_audio_meta_box', 'ppp_audio_meta_box_nonce');
        
        $attachment_id = (int) get_post_meta($post->ID, 'audio_attachment_id', true);
        $audio_url     = get_post_meta($post->ID, 'audio_url', true);
        
        $attachment_url = '';
        if ($attachment_id) {
            $resolved = wp_get_attachment_url($attachment_id);
            $attachment_url = $resolved ? $resolved : '';
        }
        ?>
        <p>
            <strong><?php esc_html_e('Audio-Datei aus der Mediathek auswählen:', 'persistent-podcast-player'); ?></strong>
        </p>
        <div class="ppp-media-selector">
            <input type="hidden"
                   id="ppp_audio_attachment_id"
                   name="ppp_audio_attachment_id"
                   value="<?php echo esc_attr($attachment_id ?: ''); ?>">
            <input type="text"
                   id="ppp_audio_attachment_url_display"
                   class="large-text"
                   readonly
                   value="<?php echo esc_attr($attachment_url); ?>"
                   placeholder="<?php esc_attr_e('Keine Datei ausgewählt', 'persistent-podcast-player'); ?>">
            <button type="button" id="ppp_select_audio_btn" class="button button-secondary">
                <?php esc_html_e('Audio-Datei auswählen', 'persistent-podcast-player'); ?>
            </button>
            <button type="button" id="ppp_remove_audio_btn" class="button button-link-delete" style="<?php echo $attachment_url ? '' : 'display:none;'; ?>">
                <?php esc_html_e('Entfernen', 'persistent-podcast-player'); ?>
            </button>
        </div>
        <p class="description">
            <?php esc_html_e('Audio-Datei aus der WordPress-Mediathek auswählen oder hochladen. Nur Audio-Anhänge (mp3, m4a, wav, ogg) werden akzeptiert.', 'persistent-podcast-player'); ?>
        </p>
        <p id="ppp_publish_guard_message" class="description" style="color:#b91c1c; display:none;">
            <?php esc_html_e('Veröffentlichen ist erst möglich, wenn eine gültige Audio-Datei aus der Mediathek ausgewählt wurde.', 'persistent-podcast-player'); ?>
        </p>
        <?php if (!empty($audio_url) && empty($attachment_url)) : ?>
            <p class="description" style="color:#a16207;">
                <?php esc_html_e('Legacy audio_url gefunden. Bitte Datei in die Mediathek übernehmen und neu auswählen, um strikt mediathekbasiert zu arbeiten.', 'persistent-podcast-player'); ?>
            </p>
        <?php endif; ?>

        <?php if ( 'pod_episode' === get_post_type( $post ) ) : ?>
            <?php
            $related_post_id = (int) get_post_meta( $post->ID, 'related_post_id', true );
            $related_post_title = '';
            if ( $related_post_id > 0 ) {
                $related_post_title = (string) get_the_title( $related_post_id );
            }
            ?>
            <hr style="margin:16px 0;" />
            <p>
                <strong><?php esc_html_e('Zugehoeriger Artikel (fuer Hero-CTA):', 'persistent-podcast-player'); ?></strong>
            </p>
            <p>
                <input type="hidden" name="ppp_related_post_id" id="ppp_related_post_id" value="<?php echo esc_attr( $related_post_id ); ?>" />
                <input
                    type="text"
                    id="ppp_related_post_search"
                    class="widefat"
                    value="<?php echo esc_attr( $related_post_title ); ?>"
                    placeholder="<?php esc_attr_e('Titel oder ID suchen…', 'persistent-podcast-player'); ?>"
                    autocomplete="off"
                />
                <div id="ppp_related_post_results" class="ppp-related-search-results" style="display:none; margin-top:8px;"></div>
            </p>
            <p>
                <button type="button" id="ppp_related_post_clear" class="button button-secondary"><?php esc_html_e('Verknuepfung entfernen', 'persistent-podcast-player'); ?></button>
            </p>
            <p id="ppp_related_post_state" class="description">
                <?php
                if ( $related_post_id > 0 && '' !== $related_post_title ) {
                    echo esc_html( sprintf( 'Aktuell verknuepft: #%d - %s', $related_post_id, $related_post_title ) );
                } else {
                    esc_html_e( 'Aktuell ist kein Artikel verknuepft.', 'persistent-podcast-player' );
                }
                ?>
            </p>
            <p class="description">
                <?php esc_html_e('Wenn ein Artikel verknuepft ist, erscheint im Hero-Slider automatisch ein Podcast-Button auf diesem Artikel-Slide.', 'persistent-podcast-player'); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * AJAX: Search published posts/pages for related episode binding.
     */
    public function ajax_search_related_posts() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ppp_search_related_posts' ) ) {
            wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
        }

        $raw_query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        $query_text = trim( $raw_query );
        if ( '' === $query_text || strlen( $query_text ) < 2 ) {
            wp_send_json_success( array() );
        }

        $items = array();
        if ( ctype_digit( $query_text ) ) {
            $candidate = get_post( (int) $query_text );
            if ( $candidate instanceof WP_Post && 'publish' === $candidate->post_status && in_array( $candidate->post_type, array( 'post', 'page' ), true ) ) {
                $items[] = array(
                    'id'    => (int) $candidate->ID,
                    'title' => (string) get_the_title( $candidate->ID ),
                    'type'  => (string) $candidate->post_type,
                    'date'  => (string) get_the_date( 'Y-m-d', $candidate->ID ),
                );
            }
        }

        $search_query = new WP_Query(
            array(
                'post_type'              => array( 'post', 'page' ),
                'post_status'            => 'publish',
                'posts_per_page'         => 15,
                's'                      => $query_text,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        if ( $search_query->have_posts() ) {
            foreach ( $search_query->posts as $candidate ) {
                $candidate_id = (int) $candidate->ID;
                $items[] = array(
                    'id'    => $candidate_id,
                    'title' => (string) get_the_title( $candidate_id ),
                    'type'  => (string) $candidate->post_type,
                    'date'  => (string) get_the_date( 'Y-m-d', $candidate_id ),
                );
            }
        }

        $items = array_values(
            array_map(
                'unserialize',
                array_unique(
                    array_map(
                        'serialize',
                        $items
                    )
                )
            )
        );

        wp_send_json_success( $items );
    }
    
    /**
     * Save audio meta fields
     */
    public function save_audio_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['ppp_audio_meta_box_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ppp_audio_meta_box_nonce'])), 'ppp_audio_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ( isset( $_POST['ppp_related_post_id'] ) ) {
            $related_post_id = absint( wp_unslash( $_POST['ppp_related_post_id'] ) );
            if ( $related_post_id > 0 ) {
                $related_post = get_post( $related_post_id );
                if ( $related_post instanceof WP_Post && 'publish' === $related_post->post_status && in_array( $related_post->post_type, array( 'post', 'page' ), true ) ) {
                    update_post_meta( $post_id, 'related_post_id', $related_post_id );
                } else {
                    delete_post_meta( $post_id, 'related_post_id' );
                }
            } else {
                delete_post_meta( $post_id, 'related_post_id' );
            }
        }
        
        $attachment_saved = false;

        // Save attachment ID (media-library only)
        if (isset($_POST['ppp_audio_attachment_id'])) {
            $attachment_id = absint($_POST['ppp_audio_attachment_id']);
            if ($attachment_id > 0 && $this->is_audio_attachment($attachment_id)) {
                update_post_meta($post_id, 'audio_attachment_id', $attachment_id);
                $attachment_url = wp_get_attachment_url($attachment_id);
                if ($attachment_url) {
                    update_post_meta($post_id, 'audio_url', esc_url_raw($attachment_url));
                }
                delete_post_meta($post_id, '_ppp_audio_notice');
                delete_post_meta($post_id, '_ppp_audio_notice_mime');
                $attachment_saved = true;
            } else {
                delete_post_meta($post_id, 'audio_attachment_id');

                // Keep UX transparent in strict mode.
                if ($attachment_id > 0) {
                    update_post_meta($post_id, '_ppp_audio_notice', 'invalid_attachment');
                    $invalid_mime = (string) get_post_mime_type($attachment_id);
                    if ($invalid_mime !== '') {
                        update_post_meta($post_id, '_ppp_audio_notice_mime', sanitize_text_field($invalid_mime));
                    } else {
                        delete_post_meta($post_id, '_ppp_audio_notice_mime');
                    }
                }
            }
        }

        // Strict mode: no manual URL writes from admin UI.
        if ($attachment_saved) {
            return;
        }

        $existing_attachment_id = (int) get_post_meta($post_id, 'audio_attachment_id', true);
        if ($existing_attachment_id > 0) {
            $resolved_audio_url = wp_get_attachment_url($existing_attachment_id);
            if ($resolved_audio_url) {
                update_post_meta($post_id, 'audio_url', esc_url_raw($resolved_audio_url));
            }
        }

        // Hard block: publishing requires a valid media-library audio file.
        if ($post instanceof WP_Post && $post->post_status === 'publish') {
            $current_notice = (string) get_post_meta($post_id, '_ppp_audio_notice', true);
            if ($existing_attachment_id <= 0 && $current_notice === '') {
                update_post_meta($post_id, '_ppp_audio_notice', 'publish_blocked_missing_audio');

                remove_action('save_post_pod_episode', array($this, 'save_audio_meta'), 10);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => 'draft',
                ));
                add_action('save_post_pod_episode', array($this, 'save_audio_meta'), 10, 2);
            }
        }
    }

    /**
     * Add admin notice state to post-save redirect.
     */
    public function add_audio_notice_redirect_arg($location, $post_id) {
        if (get_post_type($post_id) !== 'pod_episode') {
            return $location;
        }

        $notice = get_post_meta($post_id, '_ppp_audio_notice', true);
        if (!$notice) {
            return $location;
        }

        $notice_mime = (string) get_post_meta($post_id, '_ppp_audio_notice_mime', true);

        delete_post_meta($post_id, '_ppp_audio_notice');
        delete_post_meta($post_id, '_ppp_audio_notice_mime');

        $query_args = array(
            'ppp_audio_notice' => sanitize_key((string) $notice),
        );
        if ($notice_mime !== '') {
            $query_args['ppp_audio_notice_mime'] = sanitize_text_field($notice_mime);
        }

        return add_query_arg($query_args, $location);
    }

    /**
     * Render admin notice for audio validation issues.
     */
    public function render_audio_admin_notice() {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'pod_episode') {
            return;
        }

        $notice = isset($_GET['ppp_audio_notice']) ? sanitize_key(wp_unslash($_GET['ppp_audio_notice'])) : '';
        if ($notice !== 'invalid_attachment' && $notice !== 'missing_audio' && $notice !== 'publish_blocked_missing_audio') {
            return;
        }

        $invalid_mime = isset($_GET['ppp_audio_notice_mime']) ? sanitize_text_field(wp_unslash($_GET['ppp_audio_notice_mime'])) : '';
        if ($notice === 'publish_blocked_missing_audio') {
            $message = __('Veröffentlichen wurde blockiert: Für Podcast-Episoden ist eine gültige Audio-Datei aus der Mediathek erforderlich. Der Beitrag wurde als Entwurf gespeichert.', 'persistent-podcast-player');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            return;
        }

        if ($notice === 'missing_audio') {
            $message = __('Diese Episode wurde ohne Audio-Datei veröffentlicht. Bitte eine gültige Audio-Datei aus der Mediathek auswählen.', 'persistent-podcast-player');
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($message) . '</p></div>';
            return;
        }

        $allowed_mimes = implode(', ', $this->get_allowed_audio_mimes());
        $message = __('Die ausgewählte Datei ist kein gültiger Audio-Anhang. Bitte eine Audio-Datei aus der Mediathek auswählen.', 'persistent-podcast-player');
        if ($invalid_mime !== '') {
            $message .= ' ' . sprintf(__('Erkannter MIME-Type: %s.', 'persistent-podcast-player'), $invalid_mime);
        }
        $message .= ' ' . sprintf(__('Erlaubte MIME-Types: %s.', 'persistent-podcast-player'), $allowed_mimes);

        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Resolve episode audio by preferring WordPress media attachments.
     */
    private function resolve_episode_audio($post_id) {
        $audio_url = get_post_meta($post_id, 'audio_url', true);
        $audio_attachment_id = (int) get_post_meta($post_id, 'audio_attachment_id', true);

        if ($audio_attachment_id > 0 && $this->is_audio_attachment($audio_attachment_id)) {
            $resolved_audio_url = wp_get_attachment_url($audio_attachment_id);
            if ($resolved_audio_url) {
                return array(
                    'url' => $resolved_audio_url,
                    'attachment_id' => $audio_attachment_id,
                    'source' => 'media_library',
                );
            }
        }

        if ($audio_url) {
            $resolved_id = attachment_url_to_postid($audio_url);
            if ($resolved_id > 0 && $this->is_audio_attachment($resolved_id)) {
                update_post_meta($post_id, 'audio_attachment_id', $resolved_id);
                $resolved_audio_url = wp_get_attachment_url($resolved_id);
                if ($resolved_audio_url) {
                    update_post_meta($post_id, 'audio_url', esc_url_raw($resolved_audio_url));
                    return array(
                        'url' => $resolved_audio_url,
                        'attachment_id' => $resolved_id,
                        'source' => 'media_library',
                    );
                }
            }

            return array(
                'url' => $audio_url,
                'attachment_id' => 0,
                'source' => 'manual_url',
            );
        }

        return array(
            'url' => '',
            'attachment_id' => 0,
            'source' => 'none',
        );
    }

    /**
     * Check whether an attachment is an audio file.
     */
    private function is_audio_attachment($attachment_id) {
        if ($attachment_id <= 0) {
            return false;
        }

        if (get_post_type($attachment_id) !== 'attachment') {
            return false;
        }

        $mime_type = (string) get_post_mime_type($attachment_id);
        return in_array($mime_type, $this->get_allowed_audio_mimes(), true);
    }

    /**
     * Allowed MIME types for strict media-only mode.
     */
    private function get_allowed_audio_mimes() {
        return array(
            'audio/mpeg',
            'audio/mp3',
            'audio/mp4',
            'audio/x-m4a',
            'audio/wav',
            'audio/x-wav',
            'audio/ogg',
        );
    }
    
    /**
     * Enqueue admin assets for media library integration
     */
    public function enqueue_admin_assets($hook) {
        global $post;
        
        // Only on the pod_episode edit screen
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }
        
        if (!$post || $post->post_type !== 'pod_episode') {
            return;
        }
        
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        
        // Register a thin handle that depends on jquery and media-editor,
        // then attach the inline script to it so wp.media is guaranteed available.
        wp_register_script(
            'ppp-admin-media',
            false,
            array('jquery', 'media-editor'),
            PPP_VERSION,
            true
        );
        wp_enqueue_script('ppp-admin-media');
        wp_localize_script('ppp-admin-media', 'pppAdminMedia', $this->get_admin_media_i18n());
        wp_add_inline_script('ppp-admin-media', $this->get_admin_media_js());
    }

    /**
     * Localized strings for admin media picker script.
     */
    private function get_admin_media_i18n() {
        return array(
            'pickerTitle' => __('Audio-Datei auswählen', 'persistent-podcast-player'),
            'pickerButtonText' => __('Diese Datei verwenden', 'persistent-podcast-player'),
            'publishBlockedReason' => __('Veröffentlichen ist blockiert, bis eine gültige Audio-Datei aus der Mediathek ausgewählt wurde.', 'persistent-podcast-player'),
            'allowedFilesAlert' => __('Es sind nur Audio-Dateien der Typen mp3, m4a, wav oder ogg erlaubt.', 'persistent-podcast-player'),
            'relatedSearchMinChars' => __('Bitte mindestens 2 Zeichen eingeben.', 'persistent-podcast-player'),
            'relatedSearchNoResults' => __('Keine Treffer gefunden.', 'persistent-podcast-player'),
            'relatedSearchLoading' => __('Suche laeuft…', 'persistent-podcast-player'),
            'relatedSearchRemoveState' => __('Aktuell ist kein Artikel verknuepft.', 'persistent-podcast-player'),
            'relatedSearchSelectedState' => __('Aktuell verknuepft:', 'persistent-podcast-player'),
            'relatedSearchTypePost' => __('Beitrag', 'persistent-podcast-player'),
            'relatedSearchTypePage' => __('Seite', 'persistent-podcast-player'),
            'relatedSearchError' => __('Suche fehlgeschlagen. Bitte erneut versuchen.', 'persistent-podcast-player'),
            'relatedSearchAction' => 'ppp_search_related_posts',
            'relatedSearchNonce' => wp_create_nonce('ppp_search_related_posts'),
            'relatedSearchUrl' => admin_url('admin-ajax.php'),
        );
    }
    
    /**
     * Return inline JS for media picker (avoids a separate file)
     */
    private function get_admin_media_js() {
        return <<<'JS'
jQuery(document).ready(function($) {
    var i18n = window.pppAdminMedia || {};
    var mediaUploader;
    var allowedMime = {
        'audio/mpeg': true,
        'audio/mp3': true,
        'audio/mp4': true,
        'audio/x-m4a': true,
        'audio/wav': true,
        'audio/x-wav': true,
        'audio/ogg': true
    };
    var allowedExt = {
        'mp3': true,
        'm4a': true,
        'wav': true,
        'ogg': true
    };
    var relatedSearchTimer = null;

    function getExtension(filename) {
        var normalized = String(filename || '').toLowerCase();
        var idx = normalized.lastIndexOf('.');
        if (idx < 0) {
            return '';
        }
        return normalized.substring(idx + 1);
    }

    function hasAudioAttachment() {
        var raw = $('#ppp_audio_attachment_id').val();
        var parsed = parseInt(raw, 10);
        return !isNaN(parsed) && parsed > 0;
    }

    function setButtonDisabledState($button, disabled, reason) {
        if (!$button || !$button.length) {
            return;
        }

        $button.prop('disabled', disabled);
        if (disabled) {
            $button.attr('aria-disabled', 'true');
            if (reason) {
                $button.attr('title', reason);
            }
        } else {
            $button.removeAttr('aria-disabled');
            $button.removeAttr('title');
        }
    }

    function updatePublishGuard() {
        var hasAudio = hasAudioAttachment();
        var reason = i18n.publishBlockedReason || 'Publishing is blocked until a valid Media Library audio file is selected.';

        setButtonDisabledState($('#publish'), !hasAudio, reason);
        setButtonDisabledState($('.editor-post-publish-panel__toggle'), !hasAudio, reason);
        setButtonDisabledState($('.editor-post-publish-button'), !hasAudio, reason);
        setButtonDisabledState($('.editor-post-publish-button__button'), !hasAudio, reason);

        var $message = $('#ppp_publish_guard_message');
        if ($message.length) {
            if (hasAudio) {
                $message.hide();
            } else {
                $message.show();
            }
        }
    }

    function relatedTypeLabel(type) {
        if (type === 'page') {
            return i18n.relatedSearchTypePage || 'Seite';
        }
        return i18n.relatedSearchTypePost || 'Beitrag';
    }

    function setRelatedState(id, title) {
        var $state = $('#ppp_related_post_state');
        if (!$state.length) {
            return;
        }

        if (!id) {
            $state.text(i18n.relatedSearchRemoveState || 'Aktuell ist kein Artikel verknuepft.');
            return;
        }

        var prefix = i18n.relatedSearchSelectedState || 'Aktuell verknuepft:';
        $state.text(prefix + ' #' + id + ' - ' + title);
    }

    function renderRelatedResults(items) {
        var $results = $('#ppp_related_post_results');
        if (!$results.length) {
            return;
        }

        if (!items || !items.length) {
            $results.html('<p class="description" style="margin:0;">' + (i18n.relatedSearchNoResults || 'Keine Treffer gefunden.') + '</p>').show();
            return;
        }

        var html = '<ul style="margin:0; padding:0; list-style:none; border:1px solid #d0d7de; border-radius:4px; background:#fff; max-height:220px; overflow:auto;">';
        items.forEach(function(item) {
            var id = Number(item.id || 0);
            if (!id) {
                return;
            }
            var title = String(item.title || '');
            var date = String(item.date || '');
            var type = String(item.type || 'post');
            var label = '#' + id + ' - ' + title + ' (' + relatedTypeLabel(type) + ', ' + date + ')';
            html += '<li><button type="button" class="button-link ppp-related-result" data-id="' + id + '" data-title="' + $('<div/>').text(title).html() + '" style="display:block; width:100%; text-align:left; padding:8px 10px; border:0; background:transparent; cursor:pointer;">' + $('<div/>').text(label).html() + '</button></li>';
        });
        html += '</ul>';
        $results.html(html).show();
    }

    function runRelatedSearch(term) {
        var $results = $('#ppp_related_post_results');
        if (!$results.length) {
            return;
        }

        var minChars = 2;
        if (term.length < minChars) {
            $results.html('<p class="description" style="margin:0;">' + (i18n.relatedSearchMinChars || 'Bitte mindestens 2 Zeichen eingeben.') + '</p>').show();
            return;
        }

        $results.html('<p class="description" style="margin:0;">' + (i18n.relatedSearchLoading || 'Suche laeuft…') + '</p>').show();

        $.ajax({
            url: i18n.relatedSearchUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: i18n.relatedSearchAction || 'ppp_search_related_posts',
                nonce: i18n.relatedSearchNonce || '',
                query: term
            }
        }).done(function(resp) {
            if (!resp || !resp.success) {
                $results.html('<p class="description" style="margin:0;">' + (i18n.relatedSearchError || 'Suche fehlgeschlagen. Bitte erneut versuchen.') + '</p>').show();
                return;
            }
            renderRelatedResults(resp.data || []);
        }).fail(function() {
            $results.html('<p class="description" style="margin:0;">' + (i18n.relatedSearchError || 'Suche fehlgeschlagen. Bitte erneut versuchen.') + '</p>').show();
        });
    }

    $('#ppp_select_audio_btn').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: i18n.pickerTitle || 'Select Audio File',
            button: { text: i18n.pickerButtonText || 'Use this file' },
            library: { type: 'audio' },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var mime = String(attachment.mime || '').toLowerCase();
            var ext = getExtension(attachment.filename || attachment.url || '');
            if (!allowedMime[mime] && !allowedExt[ext]) {
                window.alert(i18n.allowedFilesAlert || 'Only mp3, m4a, wav, or ogg audio files are allowed.');
                $('#ppp_audio_attachment_id').val('');
                $('#ppp_audio_attachment_url_display').val('');
                $('#ppp_remove_audio_btn').hide();
                updatePublishGuard();
                return;
            }
            $('#ppp_audio_attachment_id').val(attachment.id);
            $('#ppp_audio_attachment_url_display').val(attachment.url);
            $('#ppp_remove_audio_btn').show();
            updatePublishGuard();
        });

        mediaUploader.open();
    });

    $('#ppp_remove_audio_btn').on('click', function(e) {
        e.preventDefault();
        $('#ppp_audio_attachment_id').val('');
        $('#ppp_audio_attachment_url_display').val('');
        $(this).hide();
        updatePublishGuard();
    });

    $('#ppp_related_post_search').on('input', function() {
        var term = String($(this).val() || '').trim();
        clearTimeout(relatedSearchTimer);
        relatedSearchTimer = setTimeout(function() {
            runRelatedSearch(term);
        }, 220);
    });

    $(document).on('click', '.ppp-related-result', function(e) {
        e.preventDefault();
        var id = parseInt($(this).attr('data-id'), 10);
        var title = String($(this).attr('data-title') || '');
        if (isNaN(id) || id <= 0) {
            return;
        }

        $('#ppp_related_post_id').val(String(id));
        $('#ppp_related_post_search').val(title);
        $('#ppp_related_post_results').hide().empty();
        setRelatedState(id, title);
    });

    $('#ppp_related_post_clear').on('click', function(e) {
        e.preventDefault();
        $('#ppp_related_post_id').val('0');
        $('#ppp_related_post_search').val('');
        $('#ppp_related_post_results').hide().empty();
        setRelatedState(0, '');
    });

    $(document).on('click', function(e) {
        var $target = $(e.target);
        if ($target.closest('#ppp_related_post_results').length || $target.is('#ppp_related_post_search')) {
            return;
        }
        $('#ppp_related_post_results').hide();
    });

    updatePublishGuard();

    if (window.MutationObserver) {
        var observer = new MutationObserver(function() {
            updatePublishGuard();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
});
JS;
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        $theme_controls_presentation =
            wp_style_is('themisdb-style', 'enqueued') ||
            wp_style_is('themisdb-style', 'registered') ||
            wp_style_is('lis-a-style', 'enqueued') ||
            wp_style_is('lis-a-style', 'registered');

        $should_enqueue_frontend_style = apply_filters(
            'themisdb_persistent_podcast_player_enqueue_frontend_style',
            !$theme_controls_presentation
        );

        if ($should_enqueue_frontend_style) {
            wp_enqueue_style(
                'ppp-player-style',
                PPP_PLUGIN_URL . 'assets/css/player.css',
                array(),
                PPP_VERSION
            );
        }
        
        // Plugin JS
        wp_enqueue_script(
            'ppp-player-script',
            PPP_PLUGIN_URL . 'assets/js/player.js',
            array('jquery'),
            PPP_VERSION,
            true
        );
        
        // Localize script with REST URL
        wp_localize_script(
            'ppp-player-script',
            'pppData',
            array(
                'restUrl' => rest_url('persistent-player/v1/episodes'),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );
    }
    
    /**
     * Render player HTML
     */
    public function render_player() {
        $payload = $this->get_player_payload();
        $override_html = $this->resolve_player_html_override($payload);
        if (null !== $override_html) {
            echo $override_html;
            return;
        }

        ob_start();
        ?>
        <div id="ppp-player" class="ppp-player">
            <div class="ppp-player-container">
                <div class="ppp-controls">
                    <button id="ppp-prev" class="ppp-btn" title="Previous Episode" aria-label="Previous Episode">
                        <span>&#9664;</span>
                    </button>
                    <button id="ppp-skip-backward" class="ppp-btn ppp-btn-skip" title="Skip Backward 15s" aria-label="Skip Backward 15 seconds">
                        <span>&#8634; 15</span>
                    </button>
                    <button id="ppp-play-pause" class="ppp-btn ppp-btn-play" title="Play/Pause" aria-label="Play">
                        <span class="ppp-play-icon">&#9654;</span>
                        <span class="ppp-pause-icon">&#10074;&#10074;</span>
                    </button>
                    <button id="ppp-skip-forward" class="ppp-btn ppp-btn-skip" title="Skip Forward 30s" aria-label="Skip Forward 30 seconds">
                        <span>30 &#8635;</span>
                    </button>
                    <button id="ppp-next" class="ppp-btn" title="Next Episode" aria-label="Next Episode">
                        <span>&#9654;</span>
                    </button>
                </div>
                
                <div class="ppp-time-display">
                    <span id="ppp-current-time">0:00</span>
                </div>
                
                <div class="ppp-progress-container">
                    <div class="ppp-progress-bar" id="ppp-progress-bar" role="slider" aria-label="Seek" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
                        <div class="ppp-progress-fill" id="ppp-progress-fill"></div>
                        <div class="ppp-progress-buffer" id="ppp-progress-buffer"></div>
                    </div>
                </div>
                
                <div class="ppp-time-display">
                    <span id="ppp-total-time">0:00</span>
                </div>
                
                <div class="ppp-volume-container">
                    <button id="ppp-volume-btn" class="ppp-btn ppp-btn-small" title="Mute/Unmute" aria-label="Volume">
                        <span class="ppp-volume-icon ppp-volume-on">&#128266;</span>
                        <span class="ppp-volume-icon ppp-volume-off">&#128263;</span>
                    </button>
                    <div class="ppp-volume-slider-container">
                        <input type="range" id="ppp-volume-slider" class="ppp-volume-slider" min="0" max="100" value="100" aria-label="Volume Level">
                    </div>
                </div>
                
                <div class="ppp-speed-container">
                    <button id="ppp-speed-btn" class="ppp-btn ppp-btn-small" title="Playback Speed" aria-label="Playback Speed">
                        <span id="ppp-speed-label">1x</span>
                    </button>
                    <div id="ppp-speed-menu" class="ppp-speed-menu" style="display: none;">
                        <button class="ppp-speed-option" data-speed="0.5">0.5x</button>
                        <button class="ppp-speed-option" data-speed="0.75">0.75x</button>
                        <button class="ppp-speed-option ppp-speed-active" data-speed="1">1x</button>
                        <button class="ppp-speed-option" data-speed="1.25">1.25x</button>
                        <button class="ppp-speed-option" data-speed="1.5">1.5x</button>
                        <button class="ppp-speed-option" data-speed="1.75">1.75x</button>
                        <button class="ppp-speed-option" data-speed="2">2x</button>
                    </div>
                </div>
                
                <div class="ppp-info">
                    <div class="ppp-title" id="ppp-title">Select an episode</div>
                    <div class="ppp-excerpt" id="ppp-excerpt"></div>
                </div>
                
                <div class="ppp-link-container">
                    <a href="#" id="ppp-link" class="ppp-link" target="_blank" rel="noopener noreferrer" aria-label="Zum Artikel (öffnet in neuem Tab)" style="display: none;">Zum Artikel</a>
                </div>
                
                <div class="ppp-options">
                    <label class="ppp-checkbox-label" title="Automatically play next episode">
                        <input type="checkbox" id="ppp-continuous-play" checked>
                        <span>Continuous Play</span>
                    </label>
                    <label class="ppp-checkbox-label" title="Automatically minimize player while scrolling down">
                        <input type="checkbox" id="ppp-auto-minimize" checked>
                        <span>Auto-Minimize</span>
                    </label>
                </div>
                
                <div class="ppp-playlist-toggle">
                    <button id="ppp-toggle-playlist" class="ppp-btn-toggle" aria-label="Toggle Playlist">Playlist</button>
                </div>

                <div class="ppp-minimize-toggle">
                    <button id="ppp-toggle-minimize" class="ppp-btn-toggle ppp-btn-minimize" aria-label="Player minimieren" title="Player minimieren">▾</button>
                </div>
            </div>
            
            <div id="ppp-loading" class="ppp-loading" style="display: none;">
                <div class="ppp-spinner"></div>
            </div>
            
            <div id="ppp-error" class="ppp-error" style="display: none;">
                <span class="ppp-error-icon">⚠</span>
                <span class="ppp-error-message" id="ppp-error-message">Error loading audio</span>
                <button id="ppp-error-retry" class="ppp-btn-retry">Retry</button>
            </div>
            
            <div id="ppp-playlist" class="ppp-playlist" style="display: none;">
                <div class="ppp-playlist-items" id="ppp-playlist-items">
                    <!-- Playlist items will be populated by JS -->
                </div>
            </div>
            
            <audio id="ppp-audio" preload="metadata"></audio>
        </div>
        <?php
        echo $this->finalize_player_html(ob_get_clean(), $payload);
    }

    /* -----------------------------------------------------------------
       Admin – Einstellungsseite
       ----------------------------------------------------------------- */

    /**
     * Optionsseite unter Einstellungen registrieren.
     */
    public function add_admin_menu() {
        add_options_page(
            __('Podcast Player Einstellungen', 'persistent-podcast-player'),
            __('Podcast Player', 'persistent-podcast-player'),
            'manage_options',
            'persistent-podcast-player',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Einstellungen registrieren.
     */
    public function register_settings() {
        register_setting('ppp_options', 'ppp_episodes_limit', array(
            'type' => 'integer', 'default' => 50, 'sanitize_callback' => 'absint',
        ));
        register_setting('ppp_options', 'ppp_autoplay', array(
            'type' => 'boolean', 'default' => 0, 'sanitize_callback' => 'absint',
        ));
        register_setting('ppp_options', 'ppp_show_on_all_pages', array(
            'type' => 'boolean', 'default' => 1, 'sanitize_callback' => 'absint',
        ));
        register_setting('ppp_options', 'ppp_player_position', array(
            'type' => 'string', 'default' => 'bottom',
            'sanitize_callback' => function($v) { return in_array($v, array('bottom','top'), true) ? $v : 'bottom'; },
        ));
        register_setting('ppp_options', 'ppp_podcast_category', array(
            'type' => 'string', 'default' => 'podcast', 'sanitize_callback' => 'sanitize_text_field',
        ));
    }

    /**
     * Einstellungsseite rendern.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'settings';
        if (!in_array($tab, array('settings', 'episodes', 'integration'), true)) {
            $tab = 'settings';
        }

        $tab_url = function($target_tab) {
            return esc_url(add_query_arg(
                array('page' => 'persistent-podcast-player', 'tab' => $target_tab),
                admin_url('options-general.php')
            ));
        };

        $episodes_url = admin_url('edit.php?post_type=pod_episode');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Podcast Player Einstellungen', 'persistent-podcast-player'); ?></h1>

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Podcast Player Tabs', 'persistent-podcast-player'); ?>">
                <a href="<?php echo $tab_url('settings'); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Einstellungen', 'persistent-podcast-player'); ?>
                </a>
                <a href="<?php echo $tab_url('episodes'); ?>" class="nav-tab <?php echo $tab === 'episodes' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Episoden', 'persistent-podcast-player'); ?>
                </a>
                <a href="<?php echo $tab_url('integration'); ?>" class="nav-tab <?php echo $tab === 'integration' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Integration', 'persistent-podcast-player'); ?>
                </a>
            </nav>

            <div class="themisdb-tab-content">
            <?php if ($tab === 'settings') : ?>
            <div class="themisdb-admin-modules">
                <div class="card">
                    <h2><?php esc_html_e('Schnellaktionen', 'persistent-podcast-player'); ?></h2>
                    <p><?php esc_html_e('Konfiguriere Verhalten des Players und wechsle direkt zur Episodenverwaltung.', 'persistent-podcast-player'); ?></p>
                    <p>
                        <a href="<?php echo esc_url($episodes_url); ?>" class="button button-secondary"><?php esc_html_e('Episoden öffnen', 'persistent-podcast-player'); ?></a>
                        <a href="<?php echo $tab_url('integration'); ?>" class="button button-secondary"><?php esc_html_e('REST & Hooks', 'persistent-podcast-player'); ?></a>
                    </p>
                </div>
                <div class="card">
                    <h2><?php esc_html_e('Aktive Defaults', 'persistent-podcast-player'); ?></h2>
                    <table class="widefat striped"><tbody>
                        <tr><th><?php esc_html_e('Max. Episoden', 'persistent-podcast-player'); ?></th><td><?php echo esc_html((string) get_option('ppp_episodes_limit', 50)); ?></td></tr>
                        <tr><th><?php esc_html_e('Kategorie', 'persistent-podcast-player'); ?></th><td><code><?php echo esc_html(get_option('ppp_podcast_category', 'podcast')); ?></code></td></tr>
                        <tr><th><?php esc_html_e('Position', 'persistent-podcast-player'); ?></th><td><?php echo esc_html(get_option('ppp_player_position', 'bottom')); ?></td></tr>
                    </tbody></table>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('ppp_options'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Max. Episoden', 'persistent-podcast-player'); ?></th>
                        <td>
                            <input type="number" name="ppp_episodes_limit" min="1" max="500"
                                   value="<?php echo esc_attr(get_option('ppp_episodes_limit', 50)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Wie viele Episoden in der Playlist angezeigt werden (Standard: 50).', 'persistent-podcast-player'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Podcast-Kategorie', 'persistent-podcast-player'); ?></th>
                        <td>
                            <input type="text" name="ppp_podcast_category"
                                   value="<?php echo esc_attr(get_option('ppp_podcast_category', 'podcast')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Slug der WordPress-Kategorie, aus der reguläre Beiträge als Podcast-Episoden gelten (für Audio-Metabox).', 'persistent-podcast-player'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Player-Position', 'persistent-podcast-player'); ?></th>
                        <td>
                            <select name="ppp_player_position">
                                <option value="bottom" <?php selected('bottom', get_option('ppp_player_position', 'bottom')); ?>>
                                    <?php esc_html_e('Unten (fixed)', 'persistent-podcast-player'); ?>
                                </option>
                                <option value="top" <?php selected('top', get_option('ppp_player_position', 'bottom')); ?>>
                                    <?php esc_html_e('Oben (fixed)', 'persistent-podcast-player'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Autoplay', 'persistent-podcast-player'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ppp_autoplay" value="1"
                                       <?php checked(1, get_option('ppp_autoplay', 0)); ?>>
                                <?php esc_html_e('Erste Episode automatisch starten', 'persistent-podcast-player'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Player anzeigen', 'persistent-podcast-player'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ppp_show_on_all_pages" value="1"
                                       <?php checked(1, get_option('ppp_show_on_all_pages', 1)); ?>>
                                <?php esc_html_e('Player auf allen Frontend-Seiten einblenden', 'persistent-podcast-player'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Wenn deaktiviert, erscheint der Player nur auf Einzelbeitrags-Seiten mit Audio.', 'persistent-podcast-player'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
            <?php elseif ($tab === 'episodes') : ?>
            <div class="card" style="max-width:860px;">
                <h2><?php esc_html_e('Podcast-Episoden verwalten', 'persistent-podcast-player'); ?></h2>
                <p><?php esc_html_e('Episoden können als eigener Beitragstyp oder über reguläre Beiträge in der Podcast-Kategorie geführt werden.', 'persistent-podcast-player'); ?></p>
                <p>
                <a href="<?php echo esc_url($episodes_url); ?>" class="button button-secondary">
                    <?php esc_html_e('Alle Episoden anzeigen', 'persistent-podcast-player'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=pod_episode')); ?>" class="button button-primary">
                    <?php esc_html_e('Neue Episode erstellen', 'persistent-podcast-player'); ?>
                </a>
                </p>
            </div>
            <?php else : ?>
            <div class="card" style="max-width:860px;">
                <h2><?php esc_html_e('REST-API Endpunkt', 'persistent-podcast-player'); ?></h2>
                <p><code><?php echo esc_url(rest_url('persistent-player/v1/episodes')); ?></code></p>
                <p><strong><?php esc_html_e('Wichtige Filter-Hooks', 'persistent-podcast-player'); ?></strong></p>
                <ul>
                    <li><code>themisdb_persistent_podcast_player_payload</code></li>
                    <li><code>themisdb_persistent_podcast_player_html</code></li>
                    <li><code>themisdb_persistent_podcast_player_html_output</code></li>
                </ul>
            </div>
            <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
Persistent_Podcast_Player::get_instance();


