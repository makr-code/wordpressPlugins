<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ThemisDB_Graph_Cache {
    public static function get($key) {
        return get_transient($key);
    }

    public static function set($key, $value, $ttl = HOUR_IN_SECONDS) {
        return set_transient($key, $value, $ttl);
    }

    public static function clear($key) {
        return delete_transient($key);
    }
}
