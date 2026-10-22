<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ThemisDB_Graph_Data {
    public function get_graph_data() {
        $cache_key = 'themisdb_graph_v2_payload';
        $cached = ThemisDB_Graph_Cache::get($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $graph = $this->build_graph();
        $normalized = ThemisDB_Graph_Normalizer::normalize($graph);

        $ttl = (int) ThemisDB_Graph_Settings::get('cache_seconds', HOUR_IN_SECONDS);
        if ($ttl <= 0) {
            $ttl = HOUR_IN_SECONDS;
        }

        ThemisDB_Graph_Cache::set($cache_key, $normalized, $ttl);

        return $normalized;
    }

    private function get_data_mode() {
        $mode = ThemisDB_Graph_Settings::get('data_mode', 'all');
        $allowed = array('all', 'content', 'taxonomy', 'pages', 'posts');
        $mode = is_string($mode) ? strtolower(trim($mode)) : 'all';
        return in_array($mode, $allowed, true) ? $mode : 'all';
    }

    private function get_selected_post_types() {
        $raw = ThemisDB_Graph_Settings::get('post_types', 'post,page');
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = preg_split('/[,\s]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        }

        $items = array_filter(array_map('trim', (array) $items));
        if (empty($items)) {
            return array('post', 'page');
        }

        return array_values(array_unique($items));
    }

    private function get_selected_taxonomies() {
        $raw = ThemisDB_Graph_Settings::get('taxonomies', 'category,post_tag');
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = preg_split('/[,\s]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        }

        $items = array_filter(array_map('trim', (array) $items));
        if (empty($items)) {
            return array('category', 'post_tag');
        }

        return array_values(array_unique($items));
    }

    private function get_limit($name, $fallback) {
        $option_key = preg_replace('/^themisdb_graph_nav_v2_/', '', (string) $name);
        $value = (int) ThemisDB_Graph_Settings::get($option_key, $fallback);
        return max(5, $value);
    }

    private function build_graph() {
        $nodes = array();
        $links = array();
        $link_index = array();
        $mode = $this->get_data_mode();
        $selected_post_types = $this->get_selected_post_types();
        $selected_taxonomies = $this->get_selected_taxonomies();

        $add_node = function ($id, $label, $type, $url = '', $level = 0, $metadata = array()) use (&$nodes) {
            $node_id = (string) $id;
            foreach ($nodes as $node) {
                if ((string) $node['id'] === $node_id) {
                    return;
                }
            }

            $nodes[] = array(
                'id' => $node_id,
                'label' => $label,
                'type' => $type,
                'url' => $url,
                'level' => $level,
                'sourceType' => $type,
                'visible' => true,
                'metadata' => $metadata,
            );
        };

        $add_link = function ($source, $target, $type, $weight = 1.0, $provenance = 'graph') use (&$links, &$link_index) {
            $key = ((string) $source) . '::' . ((string) $target) . '::' . ((string) $type);
            if (isset($link_index[$key])) {
                return;
            }

            $link_index[$key] = true;
            $links[] = array(
                'source' => (string) $source,
                'target' => (string) $target,
                'type' => (string) $type,
                'weight' => (float) $weight,
                'strength' => (float) $weight,
                'provenance' => $provenance,
            );
        };

        $home_id = 'home';
        $home_label = get_bloginfo('name');
        $add_node($home_id, $home_label, 'home', home_url('/'), 0, array('kind' => 'root'));

        $include_taxonomy = in_array($mode, array('all', 'taxonomy', 'content'), true);
        $include_posts = in_array($mode, array('all', 'content', 'posts'), true);
        $include_pages = in_array($mode, array('all', 'content', 'pages'), true);

        $categories = get_categories(array('hide_empty' => false));
        if ($include_taxonomy) {
            foreach ($categories as $category) {
                $cat_id = 'cat_' . $category->term_id;
                $add_node($cat_id, $category->name, 'category', get_category_link($category->term_id), 1, array('count' => $category->count));
                $add_link($home_id, $cat_id, 'contains', 0.35, 'taxonomy');
            }
        }

        $tags = get_tags(array('hide_empty' => false));
        if ($include_taxonomy) {
            foreach ($tags as $tag) {
                $tag_id = 'tag_' . $tag->term_id;
                $add_node($tag_id, $tag->name, 'tag', get_tag_link($tag->term_id), 1, array('count' => $tag->count));
                $add_link($home_id, $tag_id, 'tagged', 0.25, 'taxonomy');
            }
        }

        if ($include_taxonomy) {
            $taxonomies = get_taxonomies(array('public' => true), 'objects');
            foreach ($taxonomies as $taxonomy) {
                if (in_array($taxonomy->name, array('category', 'post_tag', 'nav_menu', 'link_category'), true)) {
                    continue;
                }

                if (!empty($selected_taxonomies) && !in_array($taxonomy->name, $selected_taxonomies, true)) {
                    continue;
                }

                $terms = get_terms(array(
                    'taxonomy' => $taxonomy->name,
                    'hide_empty' => false,
                ));
                if (is_wp_error($terms)) {
                    continue;
                }

                foreach ($terms as $term) {
                    $term_id = $taxonomy->name . '_' . $term->term_id;
                    $add_node($term_id, $term->name, 'taxonomy_term', get_term_link($term), 1, array('taxonomy' => $taxonomy->name));
                    $add_link($home_id, $term_id, 'taxonomy_relates', 0.2, 'taxonomy');
                }
            }
        }

        $posts = array();
        if ($include_posts) {
            $posts = get_posts(array(
                'numberposts' => $this->get_limit('themisdb_graph_nav_v2_post_limit', 30),
                'post_type' => in_array('posts', $selected_post_types, true) ? 'post' : $selected_post_types,
                'post_status' => 'publish',
            ));
        }

        $pages = array();
        if ($include_pages) {
            $pages = get_posts(array(
                'numberposts' => $this->get_limit('themisdb_graph_nav_v2_page_limit', 20),
                'post_type' => 'page',
                'post_status' => 'publish',
            ));
        }

        foreach ($posts as $post) {
            $post_id = 'post_' . $post->ID;
            $add_node($post_id, $post->post_title, 'post', get_permalink($post->ID), 2, array('date' => $post->post_date));

            $categories_for_post = get_the_category($post->ID);
            foreach ($categories_for_post as $category) {
                $cat_id = 'cat_' . $category->term_id;
                $add_link($cat_id, $post_id, 'has_post', 0.65, 'taxonomy');
            }

            $tags_for_post = get_the_tags($post->ID);
            if (!empty($tags_for_post)) {
                foreach ($tags_for_post as $tag) {
                    $tag_id = 'tag_' . $tag->term_id;
                    $add_link($tag_id, $post_id, 'has_tag', 0.55, 'taxonomy');
                }
            }

            $this->connect_internal_content_links($post_id, $post->post_content, $add_link);
        }

        foreach ($pages as $page) {
            $page_id = 'page_' . $page->ID;
            $add_node($page_id, $page->post_title, 'page', get_permalink($page->ID), 1, array('date' => $page->post_date));
            $add_link($home_id, $page_id, 'page_of', 0.30, 'taxonomy');
            $this->connect_internal_content_links($page_id, $page->post_content, $add_link);
        }

        if ($mode !== 'posts' && $mode !== 'pages') {
            $post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
            foreach ($post_types as $post_type) {
                $type_name = $post_type->name;
                if (!empty($selected_post_types) && !in_array($type_name, $selected_post_types, true) && !in_array($type_name, array('post', 'page'), true)) {
                    continue;
                }

                $items = get_posts(array(
                    'numberposts' => $this->get_limit('themisdb_graph_nav_v2_post_limit', 30),
                    'post_type' => $type_name,
                    'post_status' => 'publish',
                ));

                if (empty($items)) {
                    continue;
                }

                $type_node_id = 'post_type_' . $type_name;
                $add_node($type_node_id, $post_type->labels->name ?: ucfirst($type_name), 'post_type', '', 1, array('postType' => $type_name));
                $add_link($home_id, $type_node_id, 'contains_type', 0.20, 'content');

                foreach ($items as $item) {
                    $custom_id = $type_name . '_' . $item->ID;
                    $add_node($custom_id, $item->post_title, $type_name, get_permalink($item->ID), 2, array('date' => $item->post_date));
                    $add_link($type_node_id, $custom_id, 'has_item', 0.5, 'content');
                    $this->connect_internal_content_links($custom_id, $item->post_content, $add_link);
                }
            }
        }

        $menus = wp_get_nav_menus();
        if (!empty($menus)) {
            foreach ($menus as $menu) {
                $menu_id = 'menu_' . $menu->term_id;
                $add_node($menu_id, $menu->name, 'menu', '', 1, array('menuSlug' => $menu->slug));
                $add_link($home_id, $menu_id, 'contains_menu', 0.25, 'navigation');

                $items = wp_get_nav_menu_items($menu->term_id);
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $item) {
                    if (empty($item->url)) {
                        continue;
                    }

                    $item_id = 'menu_item_' . $item->ID;
                    $add_node($item_id, $item->title ?: 'Menu item', 'menu_item', $item->url, 2, array('menuId' => $menu_id));
                    $add_link($menu_id, $item_id, 'has_item', 0.4, 'navigation');

                    if (false !== strpos($item->url, home_url())) {
                        $path = str_replace(home_url(), '', $item->url);
                        $path = trim($path, '/');
                        if ($path !== '') {
                            $linked_node = $this->resolve_link_target($path, $add_node, $add_link);
                            if ($linked_node) {
                                $add_link($item_id, $linked_node, 'links_to', 0.4, 'navigation');
                            }
                        }
                    }
                }
            }
        }

        return array(
            'nodes' => $nodes,
            'links' => $links,
        );
    }

    private function connect_internal_content_links($node_id, $content, $add_link) {
        if (empty($content)) {
            return;
        }

        preg_match_all('/https?:\/\/[^\s"\')>]+|\/[^\s"\')>]+/i', (string) $content, $matches);
        if (empty($matches[0])) {
            return;
        }

        foreach ($matches[0] as $match) {
            $normalized = trim($match, " \n\r\t\"'()[]{}");
            if ($normalized === '' || false !== strpos($normalized, '://') && false === strpos($normalized, home_url())) {
                continue;
            }

            $path = preg_replace('#^https?:\/\/' . preg_quote(home_url(), '#') . '#i', '', $normalized);
            $path = preg_replace('#^https?:\/\/[^\/]+#i', '', $normalized);
            $path = trim($path, '/');
            if ($path === '') {
                continue;
            }

            $target = $this->resolve_link_target($path, function ($id, $label, $type, $url = '', $level = 0, $metadata = array()) {}, $add_link);
            if ($target) {
                $add_link($node_id, $target, 'references', 0.3, 'content');
            }
        }
    }

    private function resolve_link_target($path, $add_node, $add_link) {
        if ($path === '' || false !== strpos($path, '?')) {
            $path = strtok($path, '?');
        }

        $normalized = trim((string) $path, '/');
        if ($normalized === '') {
            return null;
        }

        $lookup = array(
            'home' => 'home',
            'page' => 'page',
            'category' => 'category',
            'tag' => 'tag',
        );

        $post = get_page_by_path($normalized, OBJECT, array('page', 'post'));
        if ($post) {
            $prefix = $post->post_type === 'page' ? 'page_' : 'post_';
            $id = $prefix . $post->ID;
            $add_node($id, $post->post_title, $post->post_type, get_permalink($post->ID), 1, array('date' => $post->post_date));
            return $id;
        }

        $term = get_term_by('slug', $normalized, 'category');
        if ($term) {
            $id = 'cat_' . $term->term_id;
            $add_node($id, $term->name, 'category', get_category_link($term->term_id), 1, array('count' => $term->count));
            return $id;
        }

        $term_tag = get_term_by('slug', $normalized, 'post_tag');
        if ($term_tag) {
            $id = 'tag_' . $term_tag->term_id;
            $add_node($id, $term_tag->name, 'tag', get_tag_link($term_tag->term_id), 1, array('count' => $term_tag->count));
            return $id;
        }

        $home_path = trim(parse_url(home_url('/'), PHP_URL_PATH) ?: '/', '/');
        if ($home_path !== '' && $normalized === $home_path) {
            return 'home';
        }

        return null;
    }
}
