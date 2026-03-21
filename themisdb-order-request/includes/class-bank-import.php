<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-bank-import.php                              ║
  Version:         0.0.1                                              ║
  Last Modified:   2026-03-15                                         ║
  Author:          ThemisDB Team                                      ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */

/**
 * Bank CSV Import Handler for ThemisDB Order Request Plugin
 *
 * Imports bank account CSV exports and matches rows to existing payment
 * records via the Verwendungszweck (transfer purpose) field.
 *
 * Supported formats: Sparkasse, DKB, Deutsche Bank, Commerzbank, ING, Generic.
 * File encoding is auto-detected and converted to UTF-8 if necessary.
 *
 * === Verwendungszweck / Überweisungsträger ===
 * Customers must put ONE of the following references in the Verwendungszweck:
 *   PAY-YYYYMMDD-XXXXXX  (primary  – payment number, e.g. PAY-20261201-A3B2C1)
 *   ORD-YYYYMMDD-XXXXXX  (fallback – order  number,  e.g. ORD-20261130-F9E8D7)
 * The import matches these patterns automatically.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Bank_Import {

    // Supported bank format identifiers
    const FORMAT_AUTO        = 'auto';
    const FORMAT_SPARKASSE   = 'sparkasse';
    const FORMAT_DKB         = 'dkb';
    const FORMAT_DEUTSCHE    = 'deutsche_bank';
    const FORMAT_COMMERZBANK = 'commerzbank';
    const FORMAT_ING         = 'ing';
    const FORMAT_GENERIC     = 'generic';

    // Transaction match status
    const STATUS_MATCHED   = 'matched';
    const STATUS_UNMATCHED = 'unmatched';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_SKIPPED   = 'skipped';
    const STATUS_MANUAL    = 'manual';

    // Match confidence
    const CONF_AUTO   = 'auto';
    const CONF_MANUAL = 'manual';

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Return human-readable labels for all supported bank formats.
     *
     * @return array  format_key => label
     */
    public static function get_supported_formats() {
        return array(
            self::FORMAT_AUTO        => __( 'Automatisch erkennen', 'themisdb-order-request' ),
            self::FORMAT_SPARKASSE   => __( 'Sparkasse', 'themisdb-order-request' ),
            self::FORMAT_DKB         => __( 'DKB Deutsche Kreditbank', 'themisdb-order-request' ),
            self::FORMAT_DEUTSCHE    => __( 'Deutsche Bank', 'themisdb-order-request' ),
            self::FORMAT_COMMERZBANK => __( 'Commerzbank', 'themisdb-order-request' ),
            self::FORMAT_ING         => __( 'ING', 'themisdb-order-request' ),
            self::FORMAT_GENERIC     => __( 'Generisch (eigene Spaltenzuordnung)', 'themisdb-order-request' ),
        );
    }

    /**
     * Parse a bank CSV file into an array of normalised transaction rows.
     *
     * @param  string      $file_path  Absolute path to the uploaded CSV file.
     * @param  string      $format     One of the FORMAT_* constants (default: auto).
     * @return array|WP_Error  Normalised rows or WP_Error on failure.
     */
    public static function parse_csv_file( $file_path, $format = self::FORMAT_AUTO ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return new WP_Error(
                'file_not_found',
                __( 'CSV-Datei nicht gefunden oder nicht lesbar.', 'themisdb-order-request' )
            );
        }

        $raw = file_get_contents( $file_path );
        if ( $raw === false ) {
            return new WP_Error(
                'file_read_error',
                __( 'CSV-Datei konnte nicht gelesen werden.', 'themisdb-order-request' )
            );
        }

        // Detect encoding and convert to UTF-8 (German banks often use ISO-8859-1)
        $encoding = mb_detect_encoding( $raw, array( 'UTF-8', 'ISO-8859-1', 'Windows-1252' ), true );
        if ( $encoding && $encoding !== 'UTF-8' ) {
            $raw = mb_convert_encoding( $raw, 'UTF-8', $encoding );
        }

        // Normalise line endings
        $raw = str_replace( "\r\n", "\n", str_replace( "\r", "\n", $raw ) );

        $delimiter = self::detect_delimiter( $raw );

        $lines = array_values( array_filter( explode( "\n", $raw ), function ( $l ) {
            return trim( $l ) !== '';
        } ) );

        if ( empty( $lines ) ) {
            return new WP_Error(
                'empty_file',
                __( 'CSV-Datei ist leer.', 'themisdb-order-request' )
            );
        }

        // Find the header row (skip bank-specific metadata lines at the top)
        $header_idx = self::find_header_row( $lines, $delimiter );

        $headers = array_map( function ( $h ) {
            return strtolower( trim( $h, " \t\n\r\0\x0B\"" ) );
        }, self::parse_csv_line( $lines[ $header_idx ], $delimiter ) );

        // Auto-detect format from header keywords and raw content
        if ( $format === self::FORMAT_AUTO ) {
            $format = self::detect_format( $headers, $raw );
        }

        // Parse data rows
        $rows = array();
        for ( $i = $header_idx + 1; $i < count( $lines ); $i++ ) {
            $line = trim( $lines[ $i ] );
            if ( empty( $line ) ) {
                continue;
            }
            $cells = self::parse_csv_line( $line, $delimiter );
            if ( count( $cells ) < 2 ) {
                continue;
            }
            // Zip headers with cells (truncate to shorter of the two)
            $len = min( count( $headers ), count( $cells ) );
            $row = array_combine(
                array_slice( $headers, 0, $len ),
                array_slice( $cells, 0, $len )
            );
            $rows[] = $row;
        }

        if ( empty( $rows ) ) {
            return new WP_Error(
                'no_data',
                __( 'Keine Transaktionsdaten in der CSV-Datei gefunden.', 'themisdb-order-request' )
            );
        }

        switch ( $format ) {
            case self::FORMAT_SPARKASSE:
                return self::normalize_sparkasse( $rows );
            case self::FORMAT_DKB:
                return self::normalize_dkb( $rows );
            case self::FORMAT_DEUTSCHE:
                return self::normalize_deutsche_bank( $rows );
            case self::FORMAT_COMMERZBANK:
                return self::normalize_commerzbank( $rows );
            case self::FORMAT_ING:
                return self::normalize_ing( $rows );
            default:
                return self::normalize_generic( $rows );
        }
    }

    /**
     * Match a list of normalised transactions against existing payment records.
     *
     * Sets `match_status`, `matched_payment_id`, `match_confidence`, and
     * `match_note` on each transaction row in-place.
     *
     * @param  array  $transactions  Output of parse_csv_file().
     * @return array  Same array with match fields populated.
     */
    public static function match_transactions( array $transactions ) {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';
        $table_orders   = $wpdb->prefix . 'themisdb_orders';
        $table_tx       = $wpdb->prefix . 'themisdb_bank_transactions';

        foreach ( $transactions as &$tx ) {
            $tx['match_status']       = self::STATUS_UNMATCHED;
            $tx['matched_payment_id'] = null;
            $tx['match_confidence']   = null;
            $tx['match_note']         = '';

            $purpose = isset( $tx['purpose'] ) ? $tx['purpose'] : '';
            $amount  = isset( $tx['amount'] )  ? floatval( $tx['amount'] ) : 0.0;

            // Skip outgoing / negative transactions (we only care about incoming funds)
            if ( $amount < 0 ) {
                $tx['match_status'] = self::STATUS_SKIPPED;
                $tx['match_note']   = __( 'Ausgehende Transaktion – übersprungen.', 'themisdb-order-request' );
                continue;
            }

            // 1. Match by PAY-YYYYMMDD-XXXXXX in Verwendungszweck
            if ( preg_match( '/PAY-\d{8}-[A-Z0-9]{6}/i', $purpose, $m ) ) {
                $payment_number = strtoupper( $m[0] );
                $payment = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM $table_payments WHERE payment_number = %s",
                    $payment_number
                ), ARRAY_A );

                if ( $payment ) {
                    self::apply_match( $tx, $payment, $table_tx, $amount );
                    continue;
                }
            }

            // 2. Match by ORD-YYYYMMDD-XXXXXX in Verwendungszweck (fallback)
            if ( preg_match( '/ORD-\d{8}-[A-Z0-9]{6}/i', $purpose, $m ) ) {
                $order_number = strtoupper( $m[0] );
                $order = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM $table_orders WHERE order_number = %s",
                    $order_number
                ), ARRAY_A );

                if ( $order ) {
                    $payment = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM $table_payments
                         WHERE order_id = %d AND payment_status = 'pending'
                         ORDER BY created_at DESC LIMIT 1",
                        $order['id']
                    ), ARRAY_A );

                    if ( $payment ) {
                        self::apply_match( $tx, $payment, $table_tx, $amount );
                        continue;
                    }
                }
            }
        }
        unset( $tx );

        return $transactions;
    }

    /**
     * Persist a completed import: save the session record, individual
     * transactions, and auto-verify all matched payments.
     *
     * @param  array      $import_meta  Keys: filename, bank_format, notes.
     * @param  array      $transactions Output of match_transactions().
     * @return int|false  New import ID, or false on DB error.
     */
    public static function save_import( array $import_meta, array $transactions ) {
        global $wpdb;

        $table_imports = $wpdb->prefix . 'themisdb_bank_imports';
        $table_tx      = $wpdb->prefix . 'themisdb_bank_transactions';

        $counts = array(
            self::STATUS_MATCHED   => 0,
            self::STATUS_UNMATCHED => 0,
            self::STATUS_DUPLICATE => 0,
            self::STATUS_SKIPPED   => 0,
        );
        foreach ( $transactions as $tx ) {
            $s = isset( $counts[ $tx['match_status'] ] ) ? $tx['match_status'] : self::STATUS_UNMATCHED;
            $counts[ $s ]++;
        }

        $result = $wpdb->insert( $table_imports, array(
            'import_uuid'    => wp_generate_uuid4(),
            'filename'       => sanitize_file_name( $import_meta['filename'] ),
            'bank_format'    => sanitize_text_field( $import_meta['bank_format'] ),
            'rows_total'     => count( $transactions ),
            'rows_matched'   => $counts[ self::STATUS_MATCHED ],
            'rows_unmatched' => $counts[ self::STATUS_UNMATCHED ],
            'rows_duplicate' => $counts[ self::STATUS_DUPLICATE ],
            'rows_skipped'   => $counts[ self::STATUS_SKIPPED ],
            'imported_by'    => get_current_user_id(),
            'notes'          => isset( $import_meta['notes'] )
                                    ? sanitize_textarea_field( $import_meta['notes'] )
                                    : null,
        ) );

        if ( ! $result ) {
            return false;
        }

        $import_id = $wpdb->insert_id;

        foreach ( $transactions as $tx ) {
            $wpdb->insert( $table_tx, array(
                'import_id'          => $import_id,
                'booking_date'       => $tx['booking_date'] ?: null,
                'value_date'         => $tx['value_date']   ?: null,
                'payer_name'         => isset( $tx['payer_name'] )
                                            ? sanitize_text_field( $tx['payer_name'] )
                                            : null,
                'payer_iban'         => isset( $tx['payer_iban'] )
                                            ? sanitize_text_field( $tx['payer_iban'] )
                                            : null,
                'payer_bic'          => isset( $tx['payer_bic'] )
                                            ? sanitize_text_field( $tx['payer_bic'] )
                                            : null,
                'amount'             => floatval( $tx['amount'] ),
                'currency'           => isset( $tx['currency'] ) && $tx['currency'] !== ''
                                            ? sanitize_text_field( $tx['currency'] )
                                            : 'EUR',
                'purpose'            => isset( $tx['purpose'] )
                                            ? sanitize_textarea_field( $tx['purpose'] )
                                            : null,
                'matched_payment_id' => $tx['matched_payment_id'] ?: null,
                'match_status'       => $tx['match_status'],
                'match_confidence'   => $tx['match_confidence'] ?: null,
                'raw_data'           => wp_json_encode( $tx['raw'] ?? array() ),
            ) );

            // Auto-verify matched payments
            if ( $tx['match_status'] === self::STATUS_MATCHED
                && ! empty( $tx['matched_payment_id'] ) ) {
                self::apply_matched_payment( $tx['matched_payment_id'], $tx );
            }
        }

        return $import_id;
    }

    /**
     * Manually assign an unmatched bank transaction to a payment.
     *
     * @param  int      $transaction_id  Bank-transaction row ID.
     * @param  int      $payment_id      Payment record ID.
     * @return bool
     */
    public static function assign_transaction( $transaction_id, $payment_id ) {
        global $wpdb;

        $table_tx = $wpdb->prefix . 'themisdb_bank_transactions';

        $tx = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_tx WHERE id = %d",
            $transaction_id
        ), ARRAY_A );

        if ( ! $tx ) {
            return false;
        }

        $wpdb->update(
            $table_tx,
            array(
                'matched_payment_id' => $payment_id,
                'match_status'       => self::STATUS_MANUAL,
                'match_confidence'   => self::CONF_MANUAL,
            ),
            array( 'id' => $transaction_id ),
            null,
            array( '%d' )
        );

        self::apply_matched_payment( $payment_id, array(
            'purpose'      => $tx['purpose'],
            'booking_date' => $tx['booking_date'],
        ) );

        return true;
    }

    /**
     * Return paginated import history rows.
     *
     * @param  array  $args  Optional: limit, offset.
     * @return array
     */
    public static function get_imports( array $args = array() ) {
        global $wpdb;

        $args    = wp_parse_args( $args, array( 'limit' => 20, 'offset' => 0 ) );
        $table   = $wpdb->prefix . 'themisdb_bank_imports';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, u.display_name AS imported_by_name
               FROM $table i
          LEFT JOIN {$wpdb->users} u ON u.ID = i.imported_by
           ORDER BY i.created_at DESC
              LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        ), ARRAY_A );
    }

    /**
     * Return all transaction rows for a given import session.
     *
     * @param  int    $import_id
     * @return array
     */
    public static function get_transactions( $import_id ) {
        global $wpdb;

        $table_tx       = $wpdb->prefix . 'themisdb_bank_transactions';
        $table_payments = $wpdb->prefix . 'themisdb_payments';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*,
                    p.payment_number,
                    p.payment_status,
                    p.amount AS payment_amount
               FROM $table_tx t
          LEFT JOIN $table_payments p ON p.id = t.matched_payment_id
              WHERE t.import_id = %d
           ORDER BY t.booking_date ASC, t.id ASC",
            $import_id
        ), ARRAY_A );
    }

    /**
     * Count total import sessions.
     *
     * @return int
     */
    public static function get_import_count() {
        global $wpdb;

        $table_imports = $wpdb->prefix . 'themisdb_bank_imports';
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table_imports)) {
            return 0;
        }

        $table_imports_sql = '`' . $table_imports . '`';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_imports_sql}");
    }

    // -------------------------------------------------------------------------
    // Internal helpers – matching
    // -------------------------------------------------------------------------

    /**
     * Set match fields on a transaction after finding a candidate payment.
     *
     * @param  array  $tx          Transaction row (by reference, already a &$tx in loop).
     * @param  array  $payment     Payment DB row.
     * @param  string $table_tx    Full table name of bank_transactions.
     * @param  float  $tx_amount   Amount from CSV.
     */
    private static function apply_match( array &$tx, array $payment, $table_tx, $tx_amount ) {
        global $wpdb;

        // Duplicate check: was this payment already matched in a previous import?
        $already = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_tx
              WHERE matched_payment_id = %d
                AND match_status IN (%s, %s)",
            $payment['id'],
            self::STATUS_MATCHED,
            self::STATUS_MANUAL
        ) );

        if ( $already > 0 ) {
            $tx['match_status']       = self::STATUS_DUPLICATE;
            $tx['matched_payment_id'] = $payment['id'];
            $tx['match_note']         = __( 'Bereits in einem früheren Import verarbeitet.', 'themisdb-order-request' );
            return;
        }

        // Amount check (tolerance ±0.01 €)
        if ( abs( floatval( $payment['amount'] ) - $tx_amount ) > 0.01 ) {
            $tx['match_status'] = self::STATUS_UNMATCHED;
            $tx['match_note']   = sprintf(
                /* translators: 1: expected amount, 2: received amount */
                __( 'Betrag weicht ab: erwartet %s €, erhalten %s €. Bitte manuell prüfen.', 'themisdb-order-request' ),
                number_format( floatval( $payment['amount'] ), 2, ',', '.' ),
                number_format( $tx_amount, 2, ',', '.' )
            );
            $tx['matched_payment_id'] = $payment['id']; // pre-fill for manual review
            return;
        }

        $tx['match_status']       = self::STATUS_MATCHED;
        $tx['matched_payment_id'] = $payment['id'];
        $tx['match_confidence']   = self::CONF_AUTO;
    }

    /**
     * Update the payment record with bank reference data and auto-verify it.
     *
     * @param  int    $payment_id
     * @param  array  $tx  Keys used: purpose, booking_date.
     */
    private static function apply_matched_payment( $payment_id, array $tx ) {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';

        $update = array();
        if ( ! empty( $tx['purpose'] ) ) {
            $update['bank_reference'] = substr( sanitize_textarea_field( $tx['purpose'] ), 0, 500 );
        }
        if ( ! empty( $tx['booking_date'] ) ) {
            $update['payment_date'] = $tx['booking_date'] . ' 00:00:00';
        }

        if ( ! empty( $update ) ) {
            $wpdb->update( $table_payments, $update, array( 'id' => $payment_id ), null, array( '%d' ) );
        }

        // Auto-verify payment (triggers order-status update + license activation)
        ThemisDB_Payment_Manager::verify_payment( $payment_id, get_current_user_id() );
    }

    // -------------------------------------------------------------------------
    // Internal helpers – CSV parsing
    // -------------------------------------------------------------------------

    /**
     * Detect the field delimiter used in a CSV string.
     *
     * @param  string  $raw  First portion of the file.
     * @return string  One of ';', ',', "\t".
     */
    private static function detect_delimiter( $raw ) {
        $sample   = substr( $raw, 0, 4096 );
        $counts   = array(
            ';'  => substr_count( $sample, ';' ),
            ','  => substr_count( $sample, ',' ),
            "\t" => substr_count( $sample, "\t" ),
        );
        arsort( $counts );
        return (string) key( $counts );
    }

    /**
     * Find the index of the header row by looking for common German field names.
     *
     * @param  array   $lines
     * @param  string  $delimiter
     * @return int  Row index (0 as fallback when no keyword-matching row is found).
     */
    private static function find_header_row( array $lines, $delimiter ) {
        $keywords = array( 'buchung', 'datum', 'betrag', 'verwendung', 'auftraggeber',
                           'beguenstigter', 'wertstellung', 'kontonummer', 'iban' );

        foreach ( $lines as $i => $line ) {
            $lower   = strtolower( $line );
            $matches = 0;
            foreach ( $keywords as $kw ) {
                if ( strpos( $lower, $kw ) !== false ) {
                    $matches++;
                }
            }
            if ( $matches >= 2 ) {
                return $i;
            }
        }
        return 0; // Fallback: treat first line as header for generic formats
    }

    /**
     * Parse a single CSV line respecting quoted fields.
     *
     * @param  string  $line
     * @param  string  $delimiter
     * @return array
     */
    private static function parse_csv_line( $line, $delimiter ) {
        return str_getcsv( $line, $delimiter, '"' );
    }

    /**
     * Detect the bank format from header keywords and raw file content.
     *
     * @param  array   $headers  Lower-cased header values.
     * @param  string  $raw      Raw file content.
     * @return string  One of the FORMAT_* constants.
     */
    private static function detect_format( array $headers, $raw ) {
        $header_str = implode( ' ', $headers );

        if ( strpos( $raw, 'Sparkasse' ) !== false
             || strpos( $header_str, 'auftragskonto' ) !== false ) {
            return self::FORMAT_SPARKASSE;
        }
        if ( strpos( $raw, 'DKB' ) !== false
             || strpos( $header_str, 'glaeubigerkennung' ) !== false
             || strpos( $header_str, 'gläubiger-id' ) !== false ) {
            return self::FORMAT_DKB;
        }
        if ( strpos( $raw, 'Deutsche Bank' ) !== false ) {
            return self::FORMAT_DEUTSCHE;
        }
        if ( strpos( $raw, 'Commerzbank' ) !== false ) {
            return self::FORMAT_COMMERZBANK;
        }
        if ( strpos( $raw, 'ING-DiBa' ) !== false
             || strpos( $raw, 'ING Bank' ) !== false ) {
            return self::FORMAT_ING;
        }
        return self::FORMAT_GENERIC;
    }

    // -------------------------------------------------------------------------
    // Internal helpers – bank-specific normalisation
    // -------------------------------------------------------------------------

    /**
     * Sparkasse CSV field mapping.
     * Header examples (case-insensitive, quoted):
     *   Auftragskonto;Buchungstag;Valutadatum;Auftraggeber / Beguenstigter;Kontonummer;BLZ;Betrag;Verwendungszweck
     */
    private static function normalize_sparkasse( array $rows ) {
        return self::normalize_rows( $rows, array(
            'booking_date' => array( 'buchungstag', 'buchungsdatum', 'buchungs' ),
            'value_date'   => array( 'valutadatum', 'wertstellung' ),
            'payer_name'   => array( 'auftraggeber / beguenstigter', 'auftraggeber/beguenstigter',
                                     'beguenstigter/auftraggeber', 'auftraggeber', 'beguenstigter' ),
            'payer_iban'   => array( 'kontonummer / iban', 'kontonummer/iban', 'iban', 'kontonummer' ),
            'payer_bic'    => array( 'blz / bic', 'blz/bic', 'blz', 'bic' ),
            'amount'       => array( 'betrag', 'umsatz' ),
            'currency'     => array( 'währung', 'waehrung' ),
            'purpose'      => array( 'verwendungszweck', 'verwendungs' ),
        ) );
    }

    /**
     * DKB CSV field mapping.
     */
    private static function normalize_dkb( array $rows ) {
        return self::normalize_rows( $rows, array(
            'booking_date' => array( 'buchungsdatum', 'buchungstag' ),
            'value_date'   => array( 'wertstellung' ),
            'payer_name'   => array( 'gläubiger / zahlungspflichtiger', 'glaeubiger / zahlungspflichtiger',
                                     'auftraggeber / beguenstigter', 'beguenstigter/zahlungspflichtiger' ),
            'payer_iban'   => array( 'iban', 'kontonummer' ),
            'payer_bic'    => array( 'bic' ),
            'amount'       => array( 'betrag (eur)', 'betrag', 'umsatz in eur' ),
            'currency'     => array( 'währung', 'waehrung' ),
            'purpose'      => array( 'verwendungszweck' ),
        ) );
    }

    /**
     * Deutsche Bank CSV field mapping.
     */
    private static function normalize_deutsche_bank( array $rows ) {
        return self::normalize_rows( $rows, array(
            'booking_date' => array( 'buchungstag', 'buchungsdatum' ),
            'value_date'   => array( 'wert', 'valutadatum', 'wertstellung' ),
            'payer_name'   => array( 'auftraggeber / begünstigter', 'auftraggeber', 'begünstigter' ),
            'payer_iban'   => array( 'iban', 'kontonummer' ),
            'payer_bic'    => array( 'bic', 'blz' ),
            'amount'       => array( 'umsatz', 'betrag' ),
            'currency'     => array( 'währung' ),
            'purpose'      => array( 'verwendungszweck', 'buchungstext' ),
        ) );
    }

    /**
     * Commerzbank CSV field mapping.
     */
    private static function normalize_commerzbank( array $rows ) {
        return self::normalize_rows( $rows, array(
            'booking_date' => array( 'buchungstag', 'buchungsdatum', 'datum' ),
            'value_date'   => array( 'wertstellung' ),
            'payer_name'   => array( 'auftraggeber/begünstigter', 'auftraggeber / begünstigter', 'auftraggeber' ),
            'payer_iban'   => array( 'iban (auftraggeber)', 'iban', 'kontonummer' ),
            'payer_bic'    => array( 'bic (auftraggeber)', 'bic' ),
            'amount'       => array( 'betrag', 'umsatz' ),
            'currency'     => array( 'währung' ),
            'purpose'      => array( 'buchungstext', 'verwendungszweck' ),
        ) );
    }

    /**
     * ING CSV field mapping.
     */
    private static function normalize_ing( array $rows ) {
        return self::normalize_rows( $rows, array(
            'booking_date' => array( 'buchung', 'buchungsdatum', 'buchungstag' ),
            'value_date'   => array( 'valuta', 'wertstellung' ),
            'payer_name'   => array( 'auftraggeber/begünstigter', 'auftraggeber', 'begünstigter' ),
            'payer_iban'   => array( 'iban', 'kontonummer' ),
            'payer_bic'    => array( 'bic' ),
            'amount'       => array( 'betrag', 'umsatz' ),
            'currency'     => array( 'währung' ),
            'purpose'      => array( 'verwendungszweck' ),
        ) );
    }

    /**
     * Generic / fallback field mapping.
     */
    private static function normalize_generic( array $rows ) {
        // Reuse Sparkasse mapping as the broadest default
        return self::normalize_sparkasse( $rows );
    }

    /**
     * Core normalisation loop: apply a field map to every raw CSV row.
     *
     * @param  array  $rows      Raw CSV rows (associative, lowercase keys).
     * @param  array  $field_map Normalised-field => [ possible-csv-header, … ]
     * @return array  Normalised transaction rows.
     */
    private static function normalize_rows( array $rows, array $field_map ) {
        $normalized = array();
        foreach ( $rows as $row ) {
            $n = self::extract_fields( $row, $field_map );
            $n['raw']          = $row;
            $n['amount']       = self::parse_german_amount( $n['amount'] );
            $n['booking_date'] = self::parse_german_date( $n['booking_date'] );
            $n['value_date']   = self::parse_german_date( $n['value_date'] );
            if ( empty( $n['currency'] ) ) {
                $n['currency'] = 'EUR';
            }
            $normalized[] = $n;
        }
        return $normalized;
    }

    /**
     * Extract fields from a raw row using a list of possible header names.
     *
     * @param  array  $row        Associative row with lowercase keys.
     * @param  array  $field_map  target_field => [ candidate_header, … ]
     * @return array
     */
    private static function extract_fields( array $row, array $field_map ) {
        // Normalise row keys: lowercase, strip surrounding quotes/spaces
        $norm = array();
        foreach ( $row as $k => $v ) {
            $norm[ strtolower( trim( $k, " \t\"" ) ) ] = trim( $v, " \t\"" );
        }

        $result = array();
        foreach ( $field_map as $field => $candidates ) {
            $result[ $field ] = '';
            foreach ( $candidates as $candidate ) {
                if ( isset( $norm[ $candidate ] ) && $norm[ $candidate ] !== '' ) {
                    $result[ $field ] = $norm[ $candidate ];
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Parse a German-formatted amount string to float.
     * Handles: "1.234,56", "1234,56", "-1.234,56", "1 234,56".
     *
     * @param  string  $value
     * @return float
     */
    private static function parse_german_amount( $value ) {
        if ( $value === '' || $value === null ) {
            return 0.0;
        }
        $value = trim( $value, " \t\"" );
        $value = str_replace( array( '.', ' ' ), '', $value ); // remove thousands separator
        $value = str_replace( ',', '.', $value );              // decimal comma → dot
        return floatval( $value );
    }

    /**
     * Parse a German date (TT.MM.JJJJ) to ISO 8601 (YYYY-MM-DD).
     * Also accepts ISO dates and YYYY/MM/DD.
     *
     * @param  string  $value
     * @return string|null
     */
    private static function parse_german_date( $value ) {
        if ( empty( $value ) ) {
            return null;
        }
        $value = trim( $value, " \t\"" );

        // DD.MM.YYYY
        if ( preg_match( '/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $m ) ) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        // YYYY-MM-DD (already ISO)
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return $value;
        }
        // YYYY/MM/DD
        if ( preg_match( '/^(\d{4})\/(\d{2})\/(\d{2})$/', $value, $m ) ) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        return null;
    }
}
