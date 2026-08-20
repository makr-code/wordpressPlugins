<?php
/**
 * ThemisDB Plain theme setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_editor_style('style.css');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'themisdb-plain-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );
});

add_action('init', function () {
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'themisdb-plain',
            array('label' => __('ThemisDB Plain', 'themisdb-plain'))
        );
    }
});

/* =====================================================================
   GITHUB UPDATE CHECK
   ===================================================================== */

add_action('after_setup_theme', function () {
    $updater_local  = get_template_directory() . '/includes/class-themisdb-theme-updater.php';
    $updater_shared = WP_PLUGIN_DIR . '/includes/class-themisdb-theme-updater.php';

    if (file_exists($updater_local)) {
        require_once $updater_local;
    } elseif (file_exists($updater_shared)) {
        require_once $updater_shared;
    }

    if (class_exists('ThemisDB_Theme_Updater')) {
        new ThemisDB_Theme_Updater('themisdb-theme-plain', wp_get_theme()->get('Version'));
    }
}, 100);
