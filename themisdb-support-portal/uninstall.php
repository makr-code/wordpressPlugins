<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            uninstall.php                                      ║
  Plugin:          themisdb-support-portal                            ║
╚═════════════════════════════════════════════════════════════════════╝
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Drop plugin tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_messages");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}themisdb_support_tickets");

// Delete plugin options
delete_option('themisdb_support_redirect_url');
delete_option('themisdb_support_email_notifications');
delete_option('themisdb_support_email_from');
delete_option('themisdb_support_email_from_name');
delete_option('themisdb_support_admin_email');
delete_option('themisdb_support_db_version');
