<?php
/**
 * Central error logging helper for ThemisDB Order Request.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Error_Handler {

    const LOG_LEVELS = array('info', 'warning', 'error', 'critical');

    /**
     * Bootstrap hook for compatibility with plugin init flow.
     *
     * Kept intentionally lightweight because the logger lazily creates
     * resources only when log() is called.
     *
     * @return void
     */
    public static function init() {
        // No eager setup required.
    }

    /**
     * Write a structured log entry to PHP error log and optionally to DB.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public static function log($level, $message, $context = array()) {
        $level = sanitize_key((string) $level);
        if (!in_array($level, self::LOG_LEVELS, true)) {
            $level = 'error';
        }

        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => sanitize_text_field((string) $message),
            'context' => self::sanitize_context($context),
            'user_id' => get_current_user_id(),
            'trace' => function_exists('wp_debug_backtrace_summary')
                ? wp_debug_backtrace_summary(null, 2, false)
                : '',
        );

        error_log('ThemisDB [' . strtoupper($entry['level']) . '] ' . wp_json_encode($entry));

        if (in_array($level, array('error', 'critical'), true)) {
            self::store_in_db($entry);
        }
    }

    /**
     * Persist error/critical logs to DB table for admin reporting.
     *
     * @param array $entry
     * @return void
     */
    private static function store_in_db($entry) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_error_log';
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return;
        }

        self::ensure_table($table);

        $wpdb->insert(
            $table,
            array(
                'level' => $entry['level'],
                'message' => $entry['message'],
                'context' => wp_json_encode($entry['context']),
                'user_id' => intval($entry['user_id']),
                'trace' => sanitize_textarea_field((string) $entry['trace']),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Create log table lazily.
     *
     * @param string $table
     */
    private static function ensure_table($table) {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists === $table) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            level VARCHAR(16) NOT NULL,
            message TEXT NOT NULL,
            context LONGTEXT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            trace TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_level (level),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Keep context values serializable and size-limited.
     *
     * @param mixed $context
     * @return array
     */
    private static function sanitize_context($context) {
        if (!is_array($context)) {
            return array('value' => self::sanitize_scalar($context));
        }

        $result = array();
        foreach ($context as $k => $v) {
            $key = sanitize_key((string) $k);
            if ($key === '') {
                continue;
            }

            if (is_array($v)) {
                $result[$key] = self::sanitize_context($v);
            } else {
                $result[$key] = self::sanitize_scalar($v);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function sanitize_scalar($value) {
        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = wp_strip_all_tags($value);
            if (strlen($value) > 2000) {
                $value = substr($value, 0, 2000) . '...';
            }
            return $value;
        }

        return sanitize_text_field(wp_json_encode($value));
    }
}
