<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-database.php                                 ║
  Plugin:          themisdb-support-portal                            ║
  Version:         1.0.0                                              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler for ThemisDB Support Portal.
 * Creates and manages the tickets and messages tables.
 */
class ThemisDB_Support_Database {

    const DB_VERSION = '1.0.2';

    /**
     * Called on plugins_loaded – runs a schema upgrade if needed.
     */
    public static function init() {
        $installed_version = get_option('themisdb_support_db_version', '0');
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            self::create_tables();
            update_option('themisdb_support_db_version', self::DB_VERSION);
        }
    }

    /**
     * Create or update the support portal database tables.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate   = $wpdb->get_charset_collate();
        $table_tickets     = $wpdb->prefix . 'themisdb_support_tickets';
        $table_messages    = $wpdb->prefix . 'themisdb_support_messages';

        $sql = "CREATE TABLE $table_tickets (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ticket_number varchar(20) NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'open',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            customer_name varchar(255) NOT NULL DEFAULT '',
            customer_email varchar(255) NOT NULL DEFAULT '',
            customer_company varchar(255) DEFAULT NULL,
            license_key varchar(100) DEFAULT NULL,
            benefit_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            assignee_user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY ticket_number (ticket_number),
            KEY status (status),
            KEY user_id (user_id),
            KEY assignee_user_id (assignee_user_id),
            KEY benefit_id (benefit_id),
            KEY license_key (license_key)
        ) $charset_collate;

        CREATE TABLE $table_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) unsigned NOT NULL,
            author_name varchar(255) NOT NULL DEFAULT '',
            author_email varchar(255) NOT NULL DEFAULT '',
            message longtext NOT NULL,
            is_admin_reply tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop all plugin tables (used by uninstall.php).
     */
    public static function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_messages");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_tickets");
    }
}
