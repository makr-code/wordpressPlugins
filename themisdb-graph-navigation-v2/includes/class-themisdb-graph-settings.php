<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ThemisDB_Graph_Settings {
    public static function boot() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function register_settings() {
        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_enable_gpu',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_max_visible_nodes',
            array(
                'type' => 'integer',
                'default' => 250,
                'sanitize_callback' => 'absint',
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_include_external',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_cache_seconds',
            array(
                'type' => 'integer',
                'default' => HOUR_IN_SECONDS,
                'sanitize_callback' => 'absint',
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_data_mode',
            array(
                'type' => 'string',
                'default' => 'all',
                'sanitize_callback' => function ($value) {
                    $allowed = array('all', 'content', 'taxonomy', 'pages', 'posts');
                    $value = is_string($value) ? strtolower(trim($value)) : 'all';
                    return in_array($value, $allowed, true) ? $value : 'all';
                },
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_post_types',
            array(
                'type' => 'string',
                'default' => 'post,page',
                'sanitize_callback' => function ($value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $clean = array_map('trim', preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY));
                    return implode(',', array_values(array_unique($clean)));
                },
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_taxonomies',
            array(
                'type' => 'string',
                'default' => 'category,post_tag',
                'sanitize_callback' => function ($value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $clean = array_map('trim', preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY));
                    return implode(',', array_values(array_unique($clean)));
                },
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_post_limit',
            array(
                'type' => 'integer',
                'default' => 30,
                'sanitize_callback' => 'absint',
            )
        );

        register_setting(
            'themisdb_graph_nav_v2_group',
            'themisdb_graph_nav_v2_page_limit',
            array(
                'type' => 'integer',
                'default' => 20,
                'sanitize_callback' => 'absint',
            )
        );
    }

    public static function register_admin_menu() {
        add_options_page(
            __('ThemisDB Graph Navigation V2', 'themisdb-graph-navigation-v2'),
            __('Graph Navigation V2', 'themisdb-graph-navigation-v2'),
            'manage_options',
            'themisdb-graph-navigation-v2',
            array(__CLASS__, 'render_admin_page')
        );
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ThemisDB Graph Navigation V2', 'themisdb-graph-navigation-v2'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('themisdb_graph_nav_v2_group');
                do_settings_sections('themisdb_graph_nav_v2_group');
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Prefer WebGPU', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_graph_nav_v2_enable_gpu" value="1" <?php checked((bool) get_option('themisdb_graph_nav_v2_enable_gpu', true), true); ?> />
                                <?php esc_html_e('Enable WebGPU when available in the browser.', 'themisdb-graph-navigation-v2'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max visible nodes', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="number" min="20" max="1000" step="10" name="themisdb_graph_nav_v2_max_visible_nodes" value="<?php echo esc_attr((int) get_option('themisdb_graph_nav_v2_max_visible_nodes', 250)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Include external references', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_graph_nav_v2_include_external" value="1" <?php checked((bool) get_option('themisdb_graph_nav_v2_include_external', true), true); ?> />
                                <?php esc_html_e('Keep external resource nodes visible in the graph.', 'themisdb-graph-navigation-v2'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Graph dataset mode', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <select name="themisdb_graph_nav_v2_data_mode">
                                <option value="all" <?php selected(get_option('themisdb_graph_nav_v2_data_mode', 'all'), 'all'); ?>><?php esc_html_e('All content', 'themisdb-graph-navigation-v2'); ?></option>
                                <option value="content" <?php selected(get_option('themisdb_graph_nav_v2_data_mode', 'all'), 'content'); ?>><?php esc_html_e('Content only', 'themisdb-graph-navigation-v2'); ?></option>
                                <option value="taxonomy" <?php selected(get_option('themisdb_graph_nav_v2_data_mode', 'all'), 'taxonomy'); ?>><?php esc_html_e('Taxonomy only', 'themisdb-graph-navigation-v2'); ?></option>
                                <option value="pages" <?php selected(get_option('themisdb_graph_nav_v2_data_mode', 'all'), 'pages'); ?>><?php esc_html_e('Pages + navigation', 'themisdb-graph-navigation-v2'); ?></option>
                                <option value="posts" <?php selected(get_option('themisdb_graph_nav_v2_data_mode', 'all'), 'posts'); ?>><?php esc_html_e('Posts only', 'themisdb-graph-navigation-v2'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Choose the data profile used to assemble the graph.', 'themisdb-graph-navigation-v2'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Included post types', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="themisdb_graph_nav_v2_post_types" value="<?php echo esc_attr(get_option('themisdb_graph_nav_v2_post_types', 'post,page')); ?>" />
                            <p class="description"><?php esc_html_e('Comma-separated post types, such as post,page,product.', 'themisdb-graph-navigation-v2'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Included taxonomies', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="themisdb_graph_nav_v2_taxonomies" value="<?php echo esc_attr(get_option('themisdb_graph_nav_v2_taxonomies', 'category,post_tag')); ?>" />
                            <p class="description"><?php esc_html_e('Comma-separated taxonomy names, such as category,post_tag,portfolio_type.', 'themisdb-graph-navigation-v2'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max post items', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="number" min="5" max="200" step="5" name="themisdb_graph_nav_v2_post_limit" value="<?php echo esc_attr((int) get_option('themisdb_graph_nav_v2_post_limit', 30)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max page items', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="number" min="5" max="200" step="5" name="themisdb_graph_nav_v2_page_limit" value="<?php echo esc_attr((int) get_option('themisdb_graph_nav_v2_page_limit', 20)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Cache lifetime', 'themisdb-graph-navigation-v2'); ?></th>
                        <td>
                            <input type="number" min="60" step="60" name="themisdb_graph_nav_v2_cache_seconds" value="<?php echo esc_attr((int) get_option('themisdb_graph_nav_v2_cache_seconds', HOUR_IN_SECONDS)); ?>" />
                            <p class="description"><?php esc_html_e('Seconds for the graph JSON cache. Higher values reduce database load but delay content updates.', 'themisdb-graph-navigation-v2'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
