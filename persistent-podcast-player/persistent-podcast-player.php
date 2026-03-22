<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            persistent-podcast-player.php                      ║
  Version:         0.0.34                                             ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          unknown                                            ║
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
 * Plugin Name: Persistent Podcast Player
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: A persistent podcast player with episode excerpts and related post links
 * Version: 1.0.0
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: persistent-podcast-player
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PPP_VERSION', '1.0.0');
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
        'persistent-podcast-player',
        PPP_VERSION
    );
}

/**
 * Main Plugin Class
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
        add_action('save_post_pod_episode', array($this, 'save_audio_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('redirect_post_location', array($this, 'add_audio_notice_redirect_arg'), 10, 2);
        add_action('admin_notices', array($this, 'render_audio_admin_notice'));
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
            'posts_per_page' => 50,
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
        <?php
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
        // Plugin CSS
        wp_enqueue_style(
            'ppp-player-style',
            PPP_PLUGIN_URL . 'assets/css/player.css',
            array(),
            PPP_VERSION
        );
        
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
                </div>
                
                <div class="ppp-playlist-toggle">
                    <button id="ppp-toggle-playlist" class="ppp-btn-toggle" aria-label="Toggle Playlist">Playlist</button>
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
    }
}

// Initialize plugin
Persistent_Podcast_Player::get_instance();
