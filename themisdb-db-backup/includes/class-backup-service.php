<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_DB_Backup_Service {
    const INDEX_FILE = 'backup-index.jsonl';

    public static function ensure_storage() {
        $dir = self::get_storage_dir();
        $tmp = self::get_tmp_dir();

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        if (!is_dir($tmp)) {
            wp_mkdir_p($tmp);
        }

        self::protect_directory($dir);
    }

    public static function get_storage_dir() {
        $upload = wp_upload_dir();
        return trailingslashit($upload['basedir']) . 'themisdb-db-backups';
    }

    public static function get_tmp_dir() {
        return trailingslashit(self::get_storage_dir()) . 'tmp';
    }

    public static function get_index_path() {
        return trailingslashit(self::get_storage_dir()) . self::INDEX_FILE;
    }

    public static function list_backups() {
        self::ensure_storage();
        $index = self::get_index_path();

        if (!file_exists($index)) {
            return array();
        }

        $rows = array();
        $lines = @file($index, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return array();
        }

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }

        usort($rows, function($a, $b) {
            return strcmp((string) $b['created_at_utc'], (string) $a['created_at_utc']);
        });

        return $rows;
    }

    public static function verify_chain() {
        $entries = self::list_backups();
        $prev_chain = '';
        $issues = array();

        usort($entries, function($a, $b) {
            return strcmp((string) $a['created_at_utc'], (string) $b['created_at_utc']);
        });

        foreach ($entries as $entry) {
            $file = trailingslashit(self::get_storage_dir()) . $entry['zip_filename'];
            if (!file_exists($file)) {
                $issues[] = 'Fehlende Datei: ' . $entry['zip_filename'];
                continue;
            }

            $current_sha = hash_file('sha256', $file);
            if (!hash_equals((string) $entry['zip_sha256'], $current_sha)) {
                $issues[] = 'SHA256-Mismatch: ' . $entry['zip_filename'];
            }

            $calc_chain = self::build_chain_hash(
                $prev_chain,
                (string) $entry['zip_sha256'],
                (string) $entry['created_at_utc'],
                (string) $entry['zip_filename']
            );

            if (!hash_equals((string) $entry['chain_hash'], $calc_chain)) {
                $issues[] = 'Chain-Mismatch: ' . $entry['zip_filename'];
            }

            $calc_sig = self::build_signature($calc_chain);
            if (!hash_equals((string) $entry['signature'], $calc_sig)) {
                $issues[] = 'Signatur-Mismatch: ' . $entry['zip_filename'];
            }

            $prev_chain = (string) $entry['chain_hash'];
        }

        return array(
            'ok' => empty($issues),
            'issues' => $issues,
            'count' => count($entries),
        );
    }

    public static function create_backup($trigger = 'manual') {
        global $wpdb;

        self::ensure_storage();

        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_missing', 'ZipArchive ist auf diesem Server nicht verfuegbar.');
        }

        $tmp_dir = self::get_tmp_dir();
        $storage = self::get_storage_dir();

        $timestamp = gmdate('Ymd-His');
        $random = wp_generate_password(8, false, false);
        $base_name = 'db-backup-' . $timestamp . '-' . strtolower($random);

        $sql_path = trailingslashit($tmp_dir) . $base_name . '.sql';
        $meta_path = trailingslashit($tmp_dir) . $base_name . '-meta.json';
        $zip_name = $base_name . '.zip';
        $zip_path = trailingslashit($storage) . $zip_name;

        $written = self::write_sql_dump($sql_path);
        if (is_wp_error($written)) {
            return $written;
        }

        $meta = array(
            'plugin' => 'themisdb-db-backup',
            'plugin_version' => THEMISDB_DB_BACKUP_VERSION,
            'created_at_utc' => gmdate('c'),
            'trigger' => $trigger,
            'site_url' => site_url(),
            'home_url' => home_url(),
            'db_name' => DB_NAME,
            'db_server' => DB_HOST,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => (string) $wpdb->db_version(),
            'table_count' => self::count_tables(),
        );

        file_put_contents($meta_path, wp_json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sql_path);
            @unlink($meta_path);
            return new WP_Error('zip_create_failed', 'ZIP-Datei konnte nicht erstellt werden.');
        }

        $zip->addFile($sql_path, 'dump.sql');
        $zip->addFile($meta_path, 'backup-meta.json');
        $zip->close();

        @unlink($sql_path);
        @unlink($meta_path);

        $sha256 = hash_file('sha256', $zip_path);
        $entries = self::list_backups();

        $prev_chain = '';
        if (!empty($entries)) {
            usort($entries, function($a, $b) {
                return strcmp((string) $a['created_at_utc'], (string) $b['created_at_utc']);
            });
            $last = end($entries);
            $prev_chain = isset($last['chain_hash']) ? (string) $last['chain_hash'] : '';
        }

        $created_at = gmdate('c');
        $chain_hash = self::build_chain_hash($prev_chain, $sha256, $created_at, $zip_name);
        $signature = self::build_signature($chain_hash);

        $entry = array(
            'zip_filename' => $zip_name,
            'zip_sha256' => $sha256,
            'zip_size_bytes' => filesize($zip_path),
            'created_at_utc' => $created_at,
            'trigger' => $trigger,
            'prev_chain_hash' => $prev_chain,
            'chain_hash' => $chain_hash,
            'signature' => $signature,
        );

        self::append_index_entry($entry);
        self::write_manifest_sidecar($zip_path . '.manifest.json', $entry);

        @chmod($zip_path, 0440);
        @chmod($zip_path . '.manifest.json', 0440);

        self::enforce_retention();

        return $entry;
    }

    private static function append_index_entry($entry) {
        $line = wp_json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents(self::get_index_path(), $line, FILE_APPEND | LOCK_EX);
    }

    private static function write_manifest_sidecar($path, $entry) {
        file_put_contents($path, wp_json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private static function count_tables() {
        global $wpdb;
        $tables = $wpdb->get_col('SHOW TABLES');
        return is_array($tables) ? count($tables) : 0;
    }

    private static function build_chain_hash($prev_chain_hash, $zip_sha256, $created_at, $filename) {
        $payload = $prev_chain_hash . '|' . $zip_sha256 . '|' . $created_at . '|' . $filename;
        return hash('sha256', $payload);
    }

    private static function build_signature($chain_hash) {
        $key = wp_salt('auth');
        return hash_hmac('sha256', $chain_hash, $key);
    }

    private static function enforce_retention() {
        $retention = (int) get_option('themisdb_db_backup_retention', 120);
        if ($retention <= 0) {
            return;
        }

        $entries = self::list_backups();
        if (count($entries) <= $retention) {
            return;
        }

        usort($entries, function($a, $b) {
            return strcmp((string) $a['created_at_utc'], (string) $b['created_at_utc']);
        });

        $to_remove = array_slice($entries, 0, count($entries) - $retention);
        foreach ($to_remove as $entry) {
            $zip = trailingslashit(self::get_storage_dir()) . $entry['zip_filename'];
            $manifest = $zip . '.manifest.json';

            if (file_exists($zip)) {
                @unlink($zip);
            }
            if (file_exists($manifest)) {
                @unlink($manifest);
            }
        }

        $kept = array_slice($entries, count($entries) - $retention);
        self::rebuild_chain_and_index($kept);
    }

    private static function rebuild_index($entries) {
        $index = self::get_index_path();
        $buffer = '';

        foreach ($entries as $entry) {
            $buffer .= wp_json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }

        file_put_contents($index, $buffer, LOCK_EX);
    }

    private static function rebuild_chain_and_index($entries) {
        $prev_chain = '';
        $recomputed = array();

        foreach ($entries as $entry) {
            $entry['prev_chain_hash'] = $prev_chain;
            $entry['chain_hash'] = self::build_chain_hash(
                $prev_chain,
                (string) $entry['zip_sha256'],
                (string) $entry['created_at_utc'],
                (string) $entry['zip_filename']
            );
            $entry['signature'] = self::build_signature((string) $entry['chain_hash']);

            $zip = trailingslashit(self::get_storage_dir()) . $entry['zip_filename'];
            self::write_manifest_sidecar($zip . '.manifest.json', $entry);

            $recomputed[] = $entry;
            $prev_chain = (string) $entry['chain_hash'];
        }

        self::rebuild_index($recomputed);
    }

    private static function write_sql_dump($sql_path) {
        global $wpdb;

        $tables = $wpdb->get_col('SHOW TABLES');
        if (!is_array($tables) || empty($tables)) {
            return new WP_Error('no_tables', 'Keine Tabellen fuer Backup gefunden.');
        }

        $fh = fopen($sql_path, 'wb');
        if (!$fh) {
            return new WP_Error('dump_open_failed', 'SQL-Datei konnte nicht geschrieben werden.');
        }

        fwrite($fh, "-- ThemisDB DB Backup\n");
        fwrite($fh, '-- Created at UTC: ' . gmdate('c') . "\n");
        fwrite($fh, "SET NAMES utf8mb4;\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $table = (string) $table;
            $create = $wpdb->get_row('SHOW CREATE TABLE `' . esc_sql($table) . '`', ARRAY_N);

            fwrite($fh, '-- --------------------------------------------------------' . "\n");
            fwrite($fh, '-- Table: ' . $table . "\n\n");

            fwrite($fh, 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n");
            if (is_array($create) && isset($create[1])) {
                fwrite($fh, $create[1] . ';' . "\n\n");
            }

            self::write_table_rows($fh, $table);
            fwrite($fh, "\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);

        return true;
    }

    private static function write_table_rows($fh, $table) {
        global $wpdb;

        $table_sql = self::escape_identifier($table);
        $offset = 0;
        $limit = 400;

        while (true) {
            $rows = $wpdb->get_results('SELECT * FROM `' . $table_sql . '` LIMIT ' . (int) $offset . ', ' . (int) $limit, ARRAY_A);
            if (empty($rows)) {
                break;
            }

            $columns = array_keys($rows[0]);
            $column_sql = '`' . implode('`, `', array_map(array(__CLASS__, 'escape_identifier'), $columns)) . '`';

            foreach ($rows as $row) {
                $values = array();
                foreach ($columns as $col) {
                    $val = $row[$col];
                    if (is_null($val)) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . self::sql_escape((string) $val) . "'";
                    }
                }

                fwrite(
                    $fh,
                    'INSERT INTO `' . $table_sql . '` (' . $column_sql . ') VALUES (' . implode(', ', $values) . ');' . "\n"
                );
            }

            if (count($rows) < $limit) {
                break;
            }

            $offset += $limit;
        }
    }

    private static function sql_escape($value) {
        $map = array(
            "\\" => "\\\\",
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "'"  => "\\'",
            '"'  => '\\"',
            "\x1a" => "\\Z",
        );

        return strtr($value, $map);
    }

    private static function escape_identifier($identifier) {
        return str_replace('`', '``', (string) $identifier);
    }

    private static function protect_directory($dir) {
        $htaccess = trailingslashit($dir) . '.htaccess';
        $index_php = trailingslashit($dir) . 'index.php';

        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        }

        if (!file_exists($index_php)) {
            file_put_contents($index_php, "<?php\n// Silence is golden.\n", LOCK_EX);
        }
    }
}
