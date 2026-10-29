<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ThemisDB_Graph_Data {
    public function get_graph_data() {
        $cache_key = 'themisdb_graph_v2_payload_v6';
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
        $public_types = get_post_types(array('public' => true), 'names');
        $items = array_intersect(array_values(array_unique($items)), $public_types);
        $items = array_values(array_diff($items, array('attachment', 'revision', 'nav_menu_item')));
        if (empty($items)) {
            return array('post', 'page');
        }

        return $items;
    }

    private function get_selected_taxonomies() {
        $raw = ThemisDB_Graph_Settings::get('taxonomies', 'category,post_tag');
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = preg_split('/[,\s]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        }

        $items = array_filter(array_map('trim', (array) $items));
        $public_taxonomies = get_taxonomies(array('public' => true), 'names');
        $items = array_values(array_intersect(array_unique($items), $public_taxonomies));
        if (empty($items)) {
            return array('category', 'post_tag');
        }

        return $items;
    }

    private function get_limit($name, $fallback) {
        $option_key = preg_replace('/^themisdb_graph_nav_v2_/', '', (string) $name);
        $value = (int) ThemisDB_Graph_Settings::get($option_key, $fallback);
        return min(200, max(5, $value));
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
            if (isset($nodes[$node_id])) {
                return;
            }

            $nodes[$node_id] = array(
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
        if ($include_taxonomy) {
            $taxonomy_objects = get_taxonomies(array('public' => true), 'objects');
            foreach ($selected_taxonomies as $taxonomy_name) {
                if (!isset($taxonomy_objects[$taxonomy_name]) || in_array($taxonomy_name, array('nav_menu', 'link_category'), true)) {
                    continue;
                }

                $taxonomy = $taxonomy_objects[$taxonomy_name];
                $taxonomy_node_id = 'taxonomy_' . sanitize_key($taxonomy_name);
                $taxonomy_label = !empty($taxonomy->labels->name) ? $taxonomy->labels->name : $taxonomy->label;
                $add_node($taxonomy_node_id, $taxonomy_label ?: ucfirst($taxonomy_name), 'taxonomy', '', 1, array(
                    'taxonomy' => $taxonomy_name,
                ));
                $add_link($home_id, $taxonomy_node_id, 'contains_taxonomy', 0.35, 'taxonomy');
                $terms = get_terms(array('taxonomy' => $taxonomy_name, 'hide_empty' => false));
                if (is_wp_error($terms)) {
                    continue;
                }

                foreach ($terms as $term) {
                    $term_id = $this->get_term_node_id($taxonomy_name, $term->term_id);
                    $term_type = 'category' === $taxonomy_name ? 'category' : ('post_tag' === $taxonomy_name ? 'tag' : 'taxonomy_term');
                    $term_url = get_term_link($term);
                    $add_node($term_id, $term->name, $term_type, is_wp_error($term_url) ? '' : $term_url, 1, array(
                        'count' => (int) $term->count,
                        'taxonomy' => $taxonomy_name,
                        'parent' => (int) $term->parent,
                    ));
                    $add_link($taxonomy_node_id, $term_id, 'has_term', 0.45, 'taxonomy');

                    if (!empty($term->parent)) {
                        $parent_id = $this->get_term_node_id($taxonomy_name, $term->parent);
                        $parent_term = get_term((int) $term->parent, $taxonomy_name);
                        if ($parent_term && !is_wp_error($parent_term)) {
                            $parent_url = get_term_link($parent_term);
                            $add_node($parent_id, $parent_term->name, $term_type, is_wp_error($parent_url) ? '' : $parent_url, 1, array(
                                'count' => (int) $parent_term->count,
                                'taxonomy' => $taxonomy_name,
                                'parent' => (int) $parent_term->parent,
                            ));
                            $add_link($parent_id, $term_id, 'parent_term', 0.75, 'taxonomy');
                        }
                    }
                }
            }
        }

        $content_types = $selected_post_types;
        if ('taxonomy' === $mode) {
            $content_types = array();
        } elseif ('pages' === $mode) {
            $content_types = in_array('page', $selected_post_types, true) ? array('page') : array();
        } elseif ('posts' === $mode) {
            $content_types = array_values(array_diff($selected_post_types, array('page')));
        }

        $content_items = array();
        $registered_post_types = get_post_types(array('public' => true), 'objects');
        foreach ($content_types as $type_name) {
            if (!isset($registered_post_types[$type_name])) {
                continue;
            }

            $post_type = $registered_post_types[$type_name];
            $is_page = 'page' === $type_name;
            $items = get_posts(array(
                'numberposts' => $this->get_limit($is_page ? 'page_limit' : 'post_limit', $is_page ? 20 : 30),
                'post_type' => $type_name,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
            ));
            $taxonomies_for_type = $include_taxonomy
                ? array_values(array_intersect(get_object_taxonomies($type_name, 'names'), $selected_taxonomies))
                : array();
            $terms_by_post = array();
            if ($items && $taxonomies_for_type) {
                $terms = wp_get_object_terms(wp_list_pluck($items, 'ID'), $taxonomies_for_type, array('fields' => 'all_with_object_id'));
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $object_id = (int) ($term->object_id ?? 0);
                        if ($object_id) {
                            $terms_by_post[$object_id][$term->taxonomy][] = $term;
                        }
                    }
                }
            }

            $type_node_id = 'post_type_' . $type_name;
            if ($items) {
                $add_node($type_node_id, $post_type->labels->name ?: ucfirst($type_name), 'post_type', '', 1, array('postType' => $type_name));
                $add_link($home_id, $type_node_id, 'contains_type', 0.35, 'content');
            }

            foreach ($items as $item) {
                $node_id = $this->get_post_node_id($item);
                $content_items[$node_id] = $item;
                $add_node($node_id, $item->post_title, $type_name, get_permalink($item->ID), $is_page ? 1 : 2, array(
                    'date' => $item->post_date,
                    'excerpt' => wp_trim_words(wp_strip_all_tags($item->post_excerpt ?: $item->post_content), 28),
                    'postType' => $type_name,
                ));

                if ($is_page) {
                    $ancestor_node_id = null;
                    foreach (array_reverse(get_post_ancestors($item->ID)) as $ancestor_id) {
                        $ancestor = get_post($ancestor_id);
                        if (!$ancestor || 'publish' !== $ancestor->post_status) {
                            continue;
                        }
                        $current_ancestor_id = $this->get_post_node_id($ancestor);
                        $add_node($current_ancestor_id, $ancestor->post_title, 'page', get_permalink($ancestor->ID), 1, array(
                            'date' => $ancestor->post_date,
                            'postType' => 'page',
                        ));
                        if ($ancestor_node_id) {
                            $add_link($ancestor_node_id, $current_ancestor_id, 'parent_page', 0.75, 'hierarchy');
                        } else {
                            $add_link($type_node_id, $current_ancestor_id, 'page_of', 0.45, 'content');
                        }
                        $ancestor_node_id = $current_ancestor_id;
                    }
                    $parent_node_id = $this->get_post_node_id($item);
                    if ($ancestor_node_id && $ancestor_node_id !== $parent_node_id) {
                        $add_link($ancestor_node_id, $parent_node_id, 'parent_page', 0.75, 'hierarchy');
                    } elseif (!$ancestor_node_id) {
                        $add_link($type_node_id, $parent_node_id, 'page_of', 0.45, 'content');
                    }
                } else {
                    $add_link($type_node_id, $node_id, 'has_item', 0.5, 'content');
                }

                foreach ($taxonomies_for_type as $taxonomy_name) {
                    foreach ($terms_by_post[(int) $item->ID][$taxonomy_name] ?? array() as $term) {
                        $term_node_id = $this->get_term_node_id($taxonomy_name, $term->term_id);
                        $term_type = 'category' === $taxonomy_name ? 'category' : ('post_tag' === $taxonomy_name ? 'tag' : 'taxonomy_term');
                        $term_url = get_term_link($term);
                        $add_node($term_node_id, $term->name, $term_type, is_wp_error($term_url) ? '' : $term_url, 1, array(
                            'count' => (int) $term->count,
                            'taxonomy' => $taxonomy_name,
                        ));
                        $add_link($term_node_id, $this->get_post_node_id($item), 'has_term', 0.65, 'taxonomy');
                    }
                }
            }
        }

        $include_external = (bool) ThemisDB_Graph_Settings::get('include_external', true);
        foreach ($content_items as $node_id => $item) {
            $this->connect_content_links(
                $node_id,
                $item->post_content,
                $add_node,
                $add_link,
                $selected_post_types,
                $selected_taxonomies,
                $include_external
            );
        }

        if (in_array($mode, array('all', 'content'), true)) {
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
                        $item_url = esc_url_raw($item->url);
                        $add_node($item_id, $item->title ?: 'Menu item', 'menu_item', $item_url, 2, array(
                            'menuId' => $menu_id,
                            'parentMenuItem' => (int) $item->menu_item_parent,
                        ));
                        $parent_item_id = (int) $item->menu_item_parent;
                        if ($parent_item_id) {
                            $add_link('menu_item_' . $parent_item_id, $item_id, 'parent_menu_item', 0.7, 'navigation');
                        } else {
                            $add_link($menu_id, $item_id, 'has_item', 0.4, 'navigation');
                        }

                        $linked_node = $this->resolve_link_target(
                            $item_url,
                            $add_node,
                            $selected_post_types,
                            $selected_taxonomies,
                            $include_external
                        );
                        if ($linked_node && $linked_node !== $item_id) {
                            $add_link($item_id, $linked_node, 'links_to', 0.4, 'navigation');
                        }
                    }
                }
            }
        }

        return array(
            'nodes' => array_values($nodes),
            'links' => $links,
        );
    }

    private function connect_content_links($node_id, $content, $add_node, $add_link, $post_types, $taxonomies, $include_external) {
        if (empty($content)) {
            return;
        }

        preg_match_all('/<a\b[^>]*\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', (string) $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return;
        }

        foreach ($matches as $match) {
            $url = html_entity_decode($match[1] ?: ($match[2] ?: $match[3]), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
            $url = trim($url);
            if ($url === '' || preg_match('/^(?:#|mailto:|tel:|javascript:|data:)/i', $url)) {
                continue;
            }

            $target = $this->resolve_link_target($url, $add_node, $post_types, $taxonomies, $include_external);
            if ($target) {
                $add_link($node_id, $target, 'references', 0.3, 'content');
            }
        }
    }

    private function resolve_link_target($url, $add_node, $post_types, $taxonomies, $include_external) {
        $url = esc_url_raw(html_entity_decode((string) $url, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8'));
        if ($url === '') {
            return null;
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return null;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? (is_ssl() ? 'https' : 'http'));
        if (!in_array($scheme, array('http', 'https'), true)) {
            return null;
        }

        $site = wp_parse_url(home_url('/'));
        $host = strtolower($parts['host'] ?? ($site['host'] ?? ''));
        $site_host = strtolower($site['host'] ?? '');
        $default_port = 'https' === $scheme ? 443 : 80;
        $port = (int) ($parts['port'] ?? $default_port);
        $site_port = (int) ($site['port'] ?? ('https' === ($site['scheme'] ?? $scheme) ? 443 : 80));
        $path = rawurldecode($parts['path'] ?? '/');
        $site_path = untrailingslashit(rawurldecode($site['path'] ?? ''));
        $same_site = $host === $site_host && (
            $port === $site_port
            || (in_array($port, array(80, 443), true) && in_array($site_port, array(80, 443), true))
        );

        if ($same_site) {
            if ($site_path !== '' && $path !== $site_path && 0 !== strpos($path, $site_path . '/')) {
                return null;
            }

            $relative_path = trim($path, '/');
            if ($site_path !== '' && 0 === strpos($path, $site_path . '/')) {
                $relative_path = trim(substr($path, strlen($site_path)), '/');
            } elseif ($site_path !== '' && untrailingslashit($path) === $site_path) {
                $relative_path = '';
            }

            $post_id = url_to_postid($url);
            $post = $post_id ? get_post($post_id) : null;
            if (!$post && $relative_path !== '') {
                $post = get_page_by_path($relative_path, OBJECT, $post_types);
            }
            if ($post && 'publish' === $post->post_status && in_array($post->post_type, $post_types, true)) {
                $id = $this->get_post_node_id($post);
                $add_node($id, $post->post_title, $post->post_type, get_permalink($post->ID), 1, array(
                    'date' => $post->post_date,
                    'postType' => $post->post_type,
                ));
                return $id;
            }

            if ($relative_path === '') {
                return 'home';
            }

            $segments = array_reverse(array_filter(explode('/', $relative_path)));
            foreach ($segments as $segment) {
                foreach ($taxonomies as $taxonomy_name) {
                    $term = get_term_by('slug', sanitize_title($segment), $taxonomy_name);
                    if (!$term || is_wp_error($term)) {
                        continue;
                    }
                    $term_id = $this->get_term_node_id($taxonomy_name, $term->term_id);
                    $term_type = 'category' === $taxonomy_name ? 'category' : ('post_tag' === $taxonomy_name ? 'tag' : 'taxonomy_term');
                    $term_url = get_term_link($term);
                    $add_node($term_id, $term->name, $term_type, is_wp_error($term_url) ? '' : $term_url, 1, array(
                        'count' => (int) $term->count,
                        'taxonomy' => $taxonomy_name,
                    ));
                    return $term_id;
                }
            }

            return null;
        }

        if (!$include_external || empty($parts['host'])) {
            return null;
        }

        $authority = $host . ($port !== $default_port ? ':' . $port : '');
        $external_url = esc_url_raw($scheme . '://' . $authority . ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''));
        $external_id = 'external_' . substr(sha1($authority . ($parts['path'] ?? '/')), 0, 16);
        $label = $host;
        $path_segments = array_filter(explode('/', trim($parts['path'] ?? '', '/')));
        if ($path_segments) {
            $label .= ' / ' . sanitize_text_field(rawurldecode(end($path_segments)));
        }
        $add_node($external_id, $label, 'external_resource', $external_url, 2, array(
            'host' => $host,
            'path' => $parts['path'] ?? '/',
        ));

        return $external_id;
    }

    private function get_post_node_id($post) {
        $prefix = 'page' === $post->post_type ? 'page' : ('post' === $post->post_type ? 'post' : sanitize_key($post->post_type));
        return $prefix . '_' . (int) $post->ID;
    }

    private function get_term_node_id($taxonomy, $term_id) {
        $prefix = 'category' === $taxonomy
            ? 'cat'
            : ('post_tag' === $taxonomy ? 'tag' : 'term_' . sanitize_key($taxonomy));
        return $prefix . '_' . (int) $term_id;
    }
}
