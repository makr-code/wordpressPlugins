<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-database.php                                 ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     419                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Database handler for ThemisDB Order Request Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Order_Database {
    
    /**
     * Initialize database
     */
    public static function init() {
        // Nothing to do on init
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Orders table
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        $sql_orders = "CREATE TABLE IF NOT EXISTS $table_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL UNIQUE,
            customer_id bigint(20) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_company varchar(255) DEFAULT NULL,
            customer_type varchar(20) NOT NULL DEFAULT 'consumer',
            vat_id varchar(50) DEFAULT NULL,
            billing_name varchar(255) DEFAULT NULL,
            billing_address_line1 varchar(255) DEFAULT NULL,
            billing_address_line2 varchar(255) DEFAULT NULL,
            billing_postal_code varchar(20) DEFAULT NULL,
            billing_city varchar(100) DEFAULT NULL,
            billing_country varchar(2) DEFAULT 'DE',
            product_type varchar(50) NOT NULL,
            product_edition varchar(50) NOT NULL,
            modules longtext DEFAULT NULL,
            training_modules longtext DEFAULT NULL,
            legal_terms_accepted tinyint(1) NOT NULL DEFAULT 0,
            legal_privacy_accepted tinyint(1) NOT NULL DEFAULT 0,
            legal_withdrawal_acknowledged tinyint(1) NOT NULL DEFAULT 0,
            legal_withdrawal_waiver tinyint(1) NOT NULL DEFAULT 0,
            legal_acceptance_version varchar(50) DEFAULT 'de-v1',
            legal_accepted_at datetime DEFAULT NULL,
            legal_accepted_ip varchar(45) DEFAULT NULL,
            legal_accepted_user_agent text DEFAULT NULL,
            shipping_name varchar(255) DEFAULT NULL,
            shipping_address_line1 varchar(255) DEFAULT NULL,
            shipping_address_line2 varchar(255) DEFAULT NULL,
            shipping_postal_code varchar(20) DEFAULT NULL,
            shipping_city varchar(100) DEFAULT NULL,
            shipping_country varchar(2) DEFAULT 'DE',
            shipping_method varchar(50) DEFAULT NULL,
            shipping_cost decimal(10,2) NOT NULL DEFAULT 0.00,
            tracking_number varchar(100) DEFAULT NULL,
            fulfillment_status varchar(50) NOT NULL DEFAULT 'not_required',
            fulfilled_at datetime DEFAULT NULL,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            status varchar(50) NOT NULL DEFAULT 'draft',
            step int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY fulfillment_status (fulfillment_status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Order items table (multi-line positions, quantities, SKU/variant)
        $table_order_items = $wpdb->prefix . 'themisdb_order_items';
        $sql_order_items = "CREATE TABLE IF NOT EXISTS $table_order_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            item_type varchar(50) NOT NULL DEFAULT 'product',
            product_id bigint(20) DEFAULT NULL,
            sku varchar(100) DEFAULT NULL,
            item_name varchar(255) NOT NULL,
            variant_data longtext DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY item_type (item_type),
            KEY product_id (product_id),
            KEY sku (sku)
        ) $charset_collate;";

        // Inventory stock table (current stock by SKU)
        $table_inventory_stock = $wpdb->prefix . 'themisdb_inventory_stock';
        $sql_inventory_stock = "CREATE TABLE IF NOT EXISTS $table_inventory_stock (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            product_id bigint(20) DEFAULT NULL,
            product_name varchar(255) NOT NULL,
            stock_on_hand int(11) NOT NULL DEFAULT 0,
            reserved_stock int(11) NOT NULL DEFAULT 0,
            reorder_level int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY sku (sku),
            KEY product_id (product_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Inventory movements table (audit trail for stock changes)
        $table_inventory_movements = $wpdb->prefix . 'themisdb_inventory_movements';
        $sql_inventory_movements = "CREATE TABLE IF NOT EXISTS $table_inventory_movements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            order_id bigint(20) DEFAULT NULL,
            movement_type varchar(50) NOT NULL,
            quantity_delta int(11) NOT NULL,
            reason varchar(255) DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sku (sku),
            KEY order_id (order_id),
            KEY movement_type (movement_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Contracts table
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        $sql_contracts = "CREATE TABLE IF NOT EXISTS $table_contracts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            contract_number varchar(50) NOT NULL UNIQUE,
            order_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            contract_type varchar(50) NOT NULL,
            contract_data longtext NOT NULL,
            pdf_file_id bigint(20) DEFAULT NULL,
            pdf_data longblob DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'draft',
            signed_at datetime DEFAULT NULL,
            valid_from date DEFAULT NULL,
            valid_until date DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY valid_from (valid_from),
            KEY valid_until (valid_until)
        ) $charset_collate;";
        
        // Contract revisions table (for legal compliance)
        $table_revisions = $wpdb->prefix . 'themisdb_contract_revisions';
        $sql_revisions = "CREATE TABLE IF NOT EXISTS $table_revisions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            contract_id bigint(20) NOT NULL,
            revision_number int(11) NOT NULL,
            contract_data longtext NOT NULL,
            pdf_data longblob DEFAULT NULL,
            changed_by bigint(20) NOT NULL,
            change_reason text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contract_id (contract_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Products master data (synced with epServer)
        $table_products = $wpdb->prefix . 'themisdb_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_code varchar(50) NOT NULL UNIQUE,
            product_name varchar(255) NOT NULL,
            product_type varchar(50) NOT NULL,
            edition varchar(50) NOT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            epserver_id varchar(100) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_type (product_type),
            KEY edition (edition),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Modules master data
        $table_modules = $wpdb->prefix . 'themisdb_modules';
        $sql_modules = "CREATE TABLE IF NOT EXISTS $table_modules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            module_code varchar(50) NOT NULL UNIQUE,
            module_name varchar(255) NOT NULL,
            module_category varchar(50) NOT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            epserver_id varchar(100) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY module_category (module_category),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Training modules master data
        $table_training = $wpdb->prefix . 'themisdb_training_modules';
        $sql_training = "CREATE TABLE IF NOT EXISTS $table_training (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            training_code varchar(50) NOT NULL UNIQUE,
            training_name varchar(255) NOT NULL,
            training_type varchar(50) NOT NULL,
            duration_hours int(11) DEFAULT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            epserver_id varchar(100) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY training_type (training_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Email log table
        $table_email_log = $wpdb->prefix . 'themisdb_email_log';
        $sql_email_log = "CREATE TABLE IF NOT EXISTS $table_email_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) DEFAULT NULL,
            contract_id bigint(20) DEFAULT NULL,
            recipient varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            body longtext NOT NULL,
            attachments longtext DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY contract_id (contract_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Payments table
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        $sql_payments = "CREATE TABLE IF NOT EXISTS $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            payment_number varchar(50) NOT NULL UNIQUE,
            order_id bigint(20) NOT NULL,
            contract_id bigint(20) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            payment_method varchar(50) NOT NULL,
            payment_status varchar(50) NOT NULL DEFAULT 'pending',
            transaction_id varchar(255) DEFAULT NULL,
            payment_date datetime DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            verified_by bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            epserver_payment_id varchar(100) DEFAULT NULL,
            bank_reference varchar(500) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY contract_id (contract_id),
            KEY payment_status (payment_status),
            KEY payment_date (payment_date),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Bank import sessions table
        $table_bank_imports = $wpdb->prefix . 'themisdb_bank_imports';
        $sql_bank_imports = "CREATE TABLE IF NOT EXISTS $table_bank_imports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            import_uuid varchar(36) NOT NULL UNIQUE,
            filename varchar(255) NOT NULL,
            bank_format varchar(50) NOT NULL DEFAULT 'auto',
            rows_total int(11) NOT NULL DEFAULT 0,
            rows_matched int(11) NOT NULL DEFAULT 0,
            rows_unmatched int(11) NOT NULL DEFAULT 0,
            rows_duplicate int(11) NOT NULL DEFAULT 0,
            rows_skipped int(11) NOT NULL DEFAULT 0,
            imported_by bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY bank_format (bank_format),
            KEY imported_by (imported_by),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Bank transactions table (individual CSV rows)
        $table_bank_transactions = $wpdb->prefix . 'themisdb_bank_transactions';
        $sql_bank_transactions = "CREATE TABLE IF NOT EXISTS $table_bank_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            import_id bigint(20) NOT NULL,
            booking_date date DEFAULT NULL,
            value_date date DEFAULT NULL,
            payer_name varchar(255) DEFAULT NULL,
            payer_iban varchar(50) DEFAULT NULL,
            payer_bic varchar(20) DEFAULT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            purpose text DEFAULT NULL,
            matched_payment_id bigint(20) DEFAULT NULL,
            match_status varchar(20) NOT NULL DEFAULT 'unmatched',
            match_confidence varchar(20) DEFAULT NULL,
            raw_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY import_id (import_id),
            KEY matched_payment_id (matched_payment_id),
            KEY match_status (match_status),
            KEY booking_date (booking_date),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Licenses table
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL UNIQUE,
            order_id bigint(20) NOT NULL,
            contract_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            product_edition varchar(50) NOT NULL,
            license_type varchar(50) NOT NULL DEFAULT 'standard',
            max_nodes int(11) DEFAULT 1,
            max_cores int(11) DEFAULT NULL,
            max_storage_gb int(11) DEFAULT NULL,
            license_status varchar(50) NOT NULL DEFAULT 'pending',
            activation_date datetime DEFAULT NULL,
            expiry_date datetime DEFAULT NULL,
            last_check datetime DEFAULT NULL,
            usage_data longtext DEFAULT NULL,
            license_file_path varchar(255) DEFAULT NULL,
            license_file_data longtext DEFAULT NULL,
            epserver_subscription_id varchar(100) DEFAULT NULL,
            cancellation_date datetime DEFAULT NULL,
            cancellation_reason text DEFAULT NULL,
            cancelled_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY contract_id (contract_id),
            KEY customer_id (customer_id),
            KEY license_status (license_status),
            KEY expiry_date (expiry_date),
            KEY cancellation_date (cancellation_date),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // License authentication log
        $table_license_auth = $wpdb->prefix . 'themisdb_license_auth_log';
        $sql_license_auth = "CREATE TABLE IF NOT EXISTS $table_license_auth (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            auth_method varchar(50) NOT NULL,
            auth_status varchar(50) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            auth_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY auth_status (auth_status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // License pricing table (versioniert - für Preisunterschiede pro Vertrag)
        $table_license_prices = $wpdb->prefix . 'themisdb_license_prices';
        $sql_license_prices = "CREATE TABLE IF NOT EXISTS $table_license_prices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            contract_id bigint(20) DEFAULT NULL,
            license_type varchar(50) NOT NULL,
            product_edition varchar(50) NOT NULL,
            base_price decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            max_nodes int(11) DEFAULT 1,
            max_cores int(11) DEFAULT NULL,
            max_storage_gb int(11) DEFAULT NULL,
            valid_from date NOT NULL,
            valid_until date DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY contract_id (contract_id),
            KEY product_edition (product_edition),
            KEY license_type (license_type),
            KEY valid_from (valid_from),
            UNIQUE KEY unique_license_version (license_id, valid_from, license_type)
        ) $charset_collate;";
        
        // License upgrade paths and costs
        $table_license_upgrades = $wpdb->prefix . 'themisdb_license_upgrades';
        $sql_license_upgrades = "CREATE TABLE IF NOT EXISTS $table_license_upgrades (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            contract_id bigint(20) NOT NULL,
            upgrade_from varchar(50) NOT NULL,
            upgrade_to varchar(50) NOT NULL,
            upgrade_type varchar(50) NOT NULL,
            upgrade_cost decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'EUR',
            upgrade_date datetime NOT NULL,
            effective_date date NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            approved_by bigint(20) DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY contract_id (contract_id),
            KEY upgrade_date (upgrade_date),
            KEY status (status),
            KEY effective_date (effective_date)
        ) $charset_collate;";
        
        // License history/changelog (detaillierte Änderungen)
        $table_license_history = $wpdb->prefix . 'themisdb_license_history';
        $sql_license_history = "CREATE TABLE IF NOT EXISTS $table_license_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            contract_id bigint(20) DEFAULT NULL,
            change_type varchar(50) NOT NULL,
            old_value longtext DEFAULT NULL,
            new_value longtext DEFAULT NULL,
            changed_field varchar(100) NOT NULL,
            change_reason text DEFAULT NULL,
            changed_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY contract_id (contract_id),
            KEY change_type (change_type),
            KEY created_at (created_at),
            KEY changed_field (changed_field)
        ) $charset_collate;";
        
        // License features (was kann diese Lizenz?)
        $table_license_features = $wpdb->prefix . 'themisdb_license_features';
        $sql_license_features = "CREATE TABLE IF NOT EXISTS $table_license_features (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            contract_id bigint(20) DEFAULT NULL,
            feature_code varchar(100) NOT NULL,
            feature_name varchar(255) NOT NULL,
            feature_value longtext DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            valid_from date NOT NULL,
            valid_until date DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY contract_id (contract_id),
            KEY feature_code (feature_code),
            KEY is_active (is_active),
            UNIQUE KEY unique_feature (license_id, feature_code, valid_from)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_inventory_stock);
        dbDelta($sql_inventory_movements);
        dbDelta($sql_contracts);
        dbDelta($sql_revisions);
        dbDelta($sql_products);
        dbDelta($sql_modules);
        dbDelta($sql_training);
        dbDelta($sql_email_log);
        dbDelta($sql_payments);
        dbDelta($sql_licenses);
        dbDelta($sql_license_auth);
        dbDelta($sql_license_prices);
        dbDelta($sql_license_upgrades);
        dbDelta($sql_license_history);
        dbDelta($sql_license_features);
        dbDelta($sql_bank_imports);
        dbDelta($sql_bank_transactions);
        
        // Insert default product data
        self::insert_default_data();
    }
    
    /**
     * Insert default product data
     */
    private static function insert_default_data() {
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'themisdb_products';
        
        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_products");
        if ($count > 0) {
            return;
        }
        
        // Default ThemisDB products
        $products = array(
            array(
                'product_code' => 'THEMIS-COMMUNITY',
                'product_name' => 'ThemisDB Community Edition',
                'product_type' => 'database',
                'edition' => 'community',
                'description' => 'Kostenlose Single-Node Edition',
                'price' => 0.00,
                'currency' => 'EUR'
            ),
            array(
                'product_code' => 'THEMIS-ENTERPRISE',
                'product_name' => 'ThemisDB Enterprise Edition',
                'product_type' => 'database',
                'edition' => 'enterprise',
                'description' => 'Enterprise Edition bis 100 Nodes',
                'price' => 5000.00,
                'currency' => 'EUR'
            ),
            array(
                'product_code' => 'THEMIS-HYPERSCALER',
                'product_name' => 'ThemisDB Hyperscaler Edition',
                'product_type' => 'database',
                'edition' => 'hyperscaler',
                'description' => 'Hyperscaler Edition unbegrenzte Nodes',
                'price' => 25000.00,
                'currency' => 'EUR'
            )
        );
        
        foreach ($products as $product) {
            $wpdb->insert($table_products, $product);
        }
        
        // Default modules
        $table_modules = $wpdb->prefix . 'themisdb_modules';
        $modules = array(
            array(
                'module_code' => 'MOD-VECTOR-SEARCH',
                'module_name' => 'Vector Search & HNSW',
                'module_category' => 'ai-ml',
                'description' => 'Hochleistungs-Vektorsuche mit HNSW-Index',
                'price' => 0.00,
                'currency' => 'EUR'
            ),
            array(
                'module_code' => 'MOD-LLM-INTEGRATION',
                'module_name' => 'LLM Integration (llama.cpp)',
                'module_category' => 'ai-ml',
                'description' => 'Embedded LLM-Integration ohne API-Kosten',
                'price' => 0.00,
                'currency' => 'EUR'
            ),
            array(
                'module_code' => 'MOD-GRAPH-DB',
                'module_name' => 'Graph Database',
                'module_category' => 'storage',
                'description' => 'Native Graph-Datenbank-Funktionalität',
                'price' => 0.00,
                'currency' => 'EUR'
            ),
            array(
                'module_code' => 'MOD-SHARDING',
                'module_name' => 'Sharding & RAID',
                'module_category' => 'scaling',
                'description' => 'Horizontale Skalierung mit RAID 0/1/5',
                'price' => 1000.00,
                'currency' => 'EUR'
            )
        );
        
        foreach ($modules as $module) {
            $wpdb->insert($table_modules, $module);
        }
        
        // Default training modules
        $table_training = $wpdb->prefix . 'themisdb_training_modules';
        $trainings = array(
            array(
                'training_code' => 'TRAIN-BASIC',
                'training_name' => 'ThemisDB Grundlagen',
                'training_type' => 'online',
                'duration_hours' => 4,
                'description' => 'Einführung in ThemisDB und grundlegende Konzepte',
                'price' => 500.00,
                'currency' => 'EUR'
            ),
            array(
                'training_code' => 'TRAIN-ADMIN',
                'training_name' => 'ThemisDB Administration',
                'training_type' => 'onsite',
                'duration_hours' => 8,
                'description' => 'Administration und Wartung von ThemisDB',
                'price' => 2000.00,
                'currency' => 'EUR'
            ),
            array(
                'training_code' => 'TRAIN-DEVELOPER',
                'training_name' => 'ThemisDB für Entwickler',
                'training_type' => 'online',
                'duration_hours' => 6,
                'description' => 'API-Nutzung und Entwicklung mit ThemisDB',
                'price' => 1500.00,
                'currency' => 'EUR'
            )
        );
        
        foreach ($trainings as $training) {
            $wpdb->insert($table_training, $training);
        }
    }
}
