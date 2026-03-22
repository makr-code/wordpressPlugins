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
