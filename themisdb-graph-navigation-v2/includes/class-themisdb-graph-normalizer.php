<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ThemisDB_Graph_Normalizer {
    public static function normalize($graph) {
        $node_map = array();
        foreach ((array) ($graph['nodes'] ?? array()) as $node) {
            if (!is_array($node) || empty($node['id'])) {
                continue;
            }

            $id = (string) $node['id'];
            if (!isset($node_map[$id])) {
                $node_map[$id] = self::normalize_node($node);
            }
        }

        $degree_counts = array_fill_keys(array_keys($node_map), 0);
        $link_map = array();
        foreach ((array) ($graph['links'] ?? array()) as $link) {
            if (!is_array($link)) {
                continue;
            }

            $source = isset($link['source']) ? (string) $link['source'] : '';
            $target = isset($link['target']) ? (string) $link['target'] : '';
            $type = isset($link['type']) ? (string) $link['type'] : 'related';

            if ($source === '' || $target === '' || !isset($node_map[$source]) || !isset($node_map[$target])) {
                continue;
            }

            $key = $source . '::' . $target . '::' . $type;
            if (isset($link_map[$key])) {
                continue;
            }

            $link_map[$key] = array(
                'id' => 'edge_' . md5($key),
                'source' => $source,
                'target' => $target,
                'type' => $type,
                'weight' => isset($link['weight']) ? (float) $link['weight'] : 1.0,
                'strength' => isset($link['strength']) ? (float) $link['strength'] : 1.0,
                'visible' => true,
                'provenance' => isset($link['provenance']) ? $link['provenance'] : 'graph',
            );

            $degree_counts[$source] = (int) ($degree_counts[$source] ?? 0) + 1;
            $degree_counts[$target] = (int) ($degree_counts[$target] ?? 0) + 1;
        }

        foreach ($node_map as $id => &$node) {
            $node['degree'] = (int) ($degree_counts[$id] ?? 0);
            $node['size'] = self::get_node_size($node['type'], $node['degree']);
            $node['clusterId'] = self::resolve_cluster_id($node['type']);
        }
        unset($node);

        $clusters = self::build_clusters($node_map);

        return array(
            'nodes' => array_values($node_map),
            'links' => array_values($link_map),
            'clusters' => $clusters,
            'stats' => array(
                'nodeCount' => count($node_map),
                'linkCount' => count($link_map),
                'clusterCount' => count($clusters),
                'maxDegree' => max(array_merge(array(0), array_map(static function ($node) {
                    return (int) ($node['degree'] ?? 0);
                }, $node_map))),
            ),
        );
    }

    protected static function normalize_node($node) {
        $type = isset($node['type']) ? (string) $node['type'] : 'generic';

        return array(
            'id' => (string) $node['id'],
            'sourceType' => isset($node['sourceType']) ? $node['sourceType'] : $type,
            'type' => $type,
            'label' => isset($node['label']) ? (string) $node['label'] : 'Untitled',
            'url' => isset($node['url']) ? $node['url'] : '',
            'level' => isset($node['level']) ? (int) $node['level'] : 0,
            'color' => isset($node['color']) ? $node['color'] : self::resolve_color_for_type($type),
            'visible' => isset($node['visible']) ? (bool) $node['visible'] : true,
            'degree' => 0,
            'size' => self::get_node_size($type, 0),
            'clusterId' => self::resolve_cluster_id($type),
            'metadata' => isset($node['metadata']) ? $node['metadata'] : array(),
        );
    }

    protected static function resolve_cluster_id($type) {
        $map = array(
            'home' => 'root',
            'category' => 'taxonomy',
            'tag' => 'taxonomy',
            'post' => 'content',
            'page' => 'content',
            'external_resource' => 'external',
        );

        return isset($map[$type]) ? $map[$type] : 'general';
    }

    protected static function resolve_color_for_type($type) {
        $map = array(
            'home' => '#7c4dff',
            'category' => '#4ecdc4',
            'tag' => '#ff9f43',
            'post' => '#4cd964',
            'page' => '#00c2ff',
            'external_resource' => '#f0ad4e',
        );

        return isset($map[$type]) ? $map[$type] : '#9aa5ff';
    }

    protected static function get_node_size($type, $degree) {
        $base = array(
            'home' => 24,
            'category' => 18,
            'tag' => 16,
            'post' => 14,
            'page' => 15,
            'external_resource' => 12,
        );

        $size = isset($base[$type]) ? $base[$type] : 14;
        return max(10, $size + (int) min(10, $degree / 2));
    }

    protected static function build_clusters($nodes) {
        $clusters = array();
        foreach ($nodes as $node) {
            $cluster_id = isset($node['clusterId']) ? (string) $node['clusterId'] : 'general';
            if (!isset($clusters[$cluster_id])) {
                $clusters[$cluster_id] = array(
                    'id' => $cluster_id,
                    'label' => $cluster_id,
                    'nodeIds' => array(),
                    'aggregateWeight' => 0,
                );
            }

            $clusters[$cluster_id]['nodeIds'][] = $node['id'];
            $clusters[$cluster_id]['aggregateWeight'] += (int) ($node['degree'] ?? 0);
        }

        foreach ($clusters as &$cluster) {
            $cluster['label'] = ucfirst($cluster['id']);
        }
        unset($cluster);

        return array_values($clusters);
    }
}
