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

    const DB_VERSION = '1.0.5';

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

        // Legacy installations may keep a foreign key on benefit_id.
        // dbDelta can fail when changing a constrained column definition.
        self::drop_legacy_benefit_foreign_key($table_tickets);

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
            sla_due_at datetime DEFAULT NULL,
            sla_warned_at datetime DEFAULT NULL,
            sla_breached_at datetime DEFAULT NULL,
            ticket_type varchar(20) NOT NULL DEFAULT 'request',
            queue varchar(20) NOT NULL DEFAULT 'triage',
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

        $table_incident_log = $wpdb->prefix . 'themisdb_incident_log';

        $sql .= "\nCREATE TABLE $table_incident_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            domain varchar(50) NOT NULL DEFAULT 'system',
            severity varchar(20) NOT NULL DEFAULT 'medium',
            status varchar(20) NOT NULL DEFAULT 'open',
            last_error varchar(1000) NOT NULL DEFAULT '',
            retry_count int(10) unsigned NOT NULL DEFAULT 0,
            context longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY domain (domain),
            KEY status (status),
            KEY severity (severity)
        ) $charset_collate;";

        $table_mail_log = $wpdb->prefix . 'themisdb_mail_log';

        $sql .= "\nCREATE TABLE $table_mail_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL DEFAULT '',
            recipient varchar(255) NOT NULL DEFAULT '',
            subject varchar(500) NOT NULL DEFAULT '',
            status varchar(10) NOT NULL DEFAULT 'sent',
            error_msg varchar(500) DEFAULT NULL,
            context_json longtext DEFAULT NULL,
            sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop legacy FK `fk_themisdb_ticket_benefit` if it exists.
     */
    private static function drop_legacy_benefit_foreign_key($table_tickets) {
        global $wpdb;

        $constraint_name = 'fk_themisdb_ticket_benefit';
        $table_schema = defined('DB_NAME') ? DB_NAME : '';
        if ($table_schema === '') {
            return;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = %s
                   AND TABLE_NAME = %s
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = %s
                 LIMIT 1",
                $table_schema,
                $table_tickets,
                $constraint_name
            )
        );

        if (!empty($exists)) {
            $wpdb->query("ALTER TABLE `$table_tickets` DROP FOREIGN KEY `$constraint_name`");
        }
    }

    /**
     * Drop all plugin tables (used by uninstall.php).
     */
    public static function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_messages");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_tickets");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_mail_log");
    }
}
