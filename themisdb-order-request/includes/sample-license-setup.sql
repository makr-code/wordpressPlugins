-- =====================================================================
-- ThemisDB License Pricing - Standard Setup
-- Default License Types with Pricing Tiers
-- =====================================================================

-- =====================================================================
-- Standard License Types for Different Editions
-- =====================================================================
-- Community Edition - Free / Community
INSERT INTO {prefix}themisdb_license_prices (
    license_id, license_type, product_edition, base_price, currency, 
    max_nodes, max_cores, max_storage_gb, valid_from, notes, created_by
) VALUES 
    (1, 'community', 'community', 0.00, 'EUR', 1, NULL, 10, CURDATE(), 'Community Edition - kostenlos', 1),
    (2, 'community', 'community', 0.00, 'EUR', 1, NULL, 10, CURDATE(), 'Community Edition - kostenlos', 1);

-- Standard Edition - Single Node
INSERT INTO {prefix}themisdb_license_prices (
    license_id, license_type, product_edition, base_price, currency, 
    max_nodes, max_cores, max_storage_gb, valid_from, notes, created_by
) VALUES 
    (3, 'standard-single', 'standard', 999.00, 'EUR', 1, 8, 100, CURDATE(), 'Standard Edition Single Node', 1),
    (4, 'standard-single', 'standard', 999.00, 'EUR', 1, 8, 100, CURDATE(), 'Standard Edition Single Node', 1);

-- Standard Edition - Cluster (3 Nodes)
INSERT INTO {prefix}themisdb_license_prices (
    license_id, license_type, product_edition, base_price, currency, 
    max_nodes, max_cores, max_storage_gb, valid_from, notes, created_by
) VALUES 
    (5, 'standard-cluster', 'standard', 2499.00, 'EUR', 3, 8, 500, CURDATE(), 'Standard Edition 3-Node Cluster', 1),
    (6, 'standard-cluster', 'standard', 2499.00, 'EUR', 3, 8, 500, CURDATE(), 'Standard Edition 3-Node Cluster', 1);

-- Professional Edition
INSERT INTO {prefix}themisdb_license_prices (
    license_id, license_type, product_edition, base_price, currency, 
    max_nodes, max_cores, max_storage_gb, valid_from, notes, created_by
) VALUES 
    (7, 'professional', 'professional', 4999.00, 'EUR', 5, 16, 2000, CURDATE(), 'Professional Edition', 1),
    (8, 'professional', 'professional', 4999.00, 'EUR', 5, 16, 2000, CURDATE(), 'Professional Edition', 1);

-- Enterprise Edition (Custom)
INSERT INTO {prefix}themisdb_license_prices (
    license_id, license_type, product_edition, base_price, currency, 
    max_nodes, max_cores, max_storage_gb, valid_from, notes, created_by
) VALUES 
    (9, 'enterprise', 'enterprise', 9999.00, 'EUR', NULL, NULL, NULL, CURDATE(), 'Enterprise Edition - Custom', 1),
    (10, 'enterprise', 'enterprise', 9999.00, 'EUR', NULL, NULL, NULL, CURDATE(), 'Enterprise Edition - Custom', 1);

-- =====================================================================
-- Standard Features by Edition
-- =====================================================================

-- Community Features
INSERT INTO {prefix}themisdb_license_features (
    license_id, feature_code, feature_name, feature_value, 
    is_active, valid_from, notes
) VALUES 
    (1, 'api_access', 'REST API Access', 'basic', 1, CURDATE(), 'Read-only API'),
    (1, 'replication', 'Data Replication', 'disabled', 1, CURDATE(), ''),
    (1, 'backup', 'Automated Backups', 'disabled', 1, CURDATE(), ''),
    (1, 'support', 'Support Level', 'community', 1, CURDATE(), 'Community Forum only'),
    (2, 'api_access', 'REST API Access', 'basic', 1, CURDATE(), 'Read-only API'),
    (2, 'replication', 'Data Replication', 'disabled', 1, CURDATE(), ''),
    (2, 'backup', 'Automated Backups', 'disabled', 1, CURDATE(), ''),
    (2, 'support', 'Support Level', 'community', 1, CURDATE(), 'Community Forum only');

-- Standard Features
INSERT INTO {prefix}themisdb_license_features (
    license_id, feature_code, feature_name, feature_value, 
    is_active, valid_from, notes
) VALUES 
    (3, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (3, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (3, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Daily'),
    (3, 'support', 'Support Level', 'email', 1, CURDATE(), 'Email Support - 24h response'),
    (3, 'ha', 'High Availability', 'disabled', 1, CURDATE(), ''),
    (4, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (4, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (4, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Daily'),
    (4, 'support', 'Support Level', 'email', 1, CURDATE(), 'Email Support - 24h response'),
    (4, 'ha', 'High Availability', 'disabled', 1, CURDATE(), ''),
    (5, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (5, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (5, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Daily'),
    (5, 'support', 'Support Level', 'email', 1, CURDATE(), 'Email Support - 24h response'),
    (5, 'ha', 'High Availability', 'enabled', 1, CURDATE(), ''),
    (6, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (6, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (6, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Daily'),
    (6, 'support', 'Support Level', 'email', 1, CURDATE(), 'Email Support - 24h response'),
    (6, 'ha', 'High Availability', 'enabled', 1, CURDATE(), '');

-- Professional Features
INSERT INTO {prefix}themisdb_license_features (
    license_id, feature_code, feature_name, feature_value, 
    is_active, valid_from, notes
) VALUES 
    (7, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (7, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (7, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Hourly'),
    (7, 'support', 'Support Level', 'phone', 1, CURDATE(), 'Phone + Email - 4h response'),
    (7, 'ha', 'High Availability', 'enabled', 1, CURDATE(), ''),
    (7, 'monitoring', 'Advanced Monitoring', 'enabled', 1, CURDATE(), ''),
    (7, 'sso', 'SSO/SAML', 'enabled', 1, CURDATE(), ''),
    (8, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (8, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (8, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Hourly'),
    (8, 'support', 'Support Level', 'phone', 1, CURDATE(), 'Phone + Email - 4h response'),
    (8, 'ha', 'High Availability', 'enabled', 1, CURDATE(), ''),
    (8, 'monitoring', 'Advanced Monitoring', 'enabled', 1, CURDATE(), ''),
    (8, 'sso', 'SSO/SAML', 'enabled', 1, CURDATE(), '');

-- Enterprise Features
INSERT INTO {prefix}themisdb_license_features (
    license_id, feature_code, feature_name, feature_value, 
    is_active, valid_from, notes
) VALUES 
    (9, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (9, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (9, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Real-time'),
    (9, 'support', 'Support Level', 'dedicated', 1, CURDATE(), 'Dedicated Account Manager'),
    (9, 'ha', 'High Availability', 'enabled', 1, CURDATE(), ''),
    (9, 'monitoring', 'Advanced Monitoring', 'enabled', 1, CURDATE(), ''),
    (9, 'sso', 'SSO/SAML', 'enabled', 1, CURDATE(), ''),
    (9, 'custom_integration', 'Custom Integration', 'enabled', 1, CURDATE(), ''),
    (9, 'audit_logging', 'Audit Logging', 'enabled', 1, CURDATE(), ''),
    (10, 'api_access', 'REST API Access', 'full', 1, CURDATE(), ''),
    (10, 'replication', 'Data Replication', 'enabled', 1, CURDATE(), ''),
    (10, 'backup', 'Automated Backups', 'enabled', 1, CURDATE(), 'Real-time'),
    (10, 'support', 'Support Level', 'dedicated', 1, CURDATE(), 'Dedicated Account Manager'),
    (10, 'ha', 'High Availability', 'enabled', 1, CURDATE(), ''),
    (10, 'monitoring', 'Advanced Monitoring', 'enabled', 1, CURDATE(), ''),
    (10, 'sso', 'SSO/SAML', 'enabled', 1, CURDATE(), ''),
    (10, 'custom_integration', 'Custom Integration', 'enabled', 1, CURDATE(), ''),
    (10, 'audit_logging', 'Audit Logging', 'enabled', 1, CURDATE(), '');

-- =====================================================================
-- Standard Upgrade Paths (Examples)
-- =====================================================================

-- Community → Standard Single Node
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (1, 1, 'community', 'standard-single', 'edition', 999.00, 'EUR', CURDATE(), 'pending', 'Upgrade vom Community zur Standard Single Node Edition');

-- Standard Single → Standard Cluster
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (3, 3, 'standard-single', 'standard-cluster', 'edition', 1500.00, 'EUR', CURDATE(), 'pending', 'Upgrade auf 3-Node Cluster (pro-rata Berechnung möglich)');

-- Standard Cluster → Professional
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (5, 5, 'standard-cluster', 'professional', 'edition', 2500.00, 'EUR', CURDATE(), 'pending', 'Upgrade auf Professional Edition');

-- Professional → Enterprise
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (7, 7, 'professional', 'enterprise', 'edition', 5000.00, 'EUR', CURDATE(), 'pending', 'Upgrade auf Enterprise Edition (Custom-Angebot zu prüfen)');

-- Storage Upgrade Examples (within same edition)
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (3, 3, '100GB', '500GB', 'storage', 500.00, 'EUR', CURDATE(), 'pending', 'Storage Erweiterung 100GB → 500GB'),
    (5, 5, '500GB', '2000GB', 'storage', 1000.00, 'EUR', CURDATE(), 'pending', 'Storage Erweiterung 500GB → 2000GB');

-- Node Expansion (for cluster editions)
INSERT INTO {prefix}themisdb_license_upgrades (
    license_id, contract_id, upgrade_from, upgrade_to, upgrade_type, 
    upgrade_cost, currency, effective_date, status, notes
) VALUES 
    (5, 5, '3-Nodes', '5-Nodes', 'nodes', 1200.00, 'EUR', CURDATE(), 'pending', 'Cluster Erweiterung 3 → 5 Knoten (kostet pro zusätzlichem Node)');
