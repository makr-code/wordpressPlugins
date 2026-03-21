<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-ticket-manager.php                           ║
  Plugin:          themisdb-support-portal                            ║
  Version:         1.0.0                                              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages support tickets and their messages.
 */
class ThemisDB_Support_Ticket_Manager {

    const STATUS_OPEN        = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED    = 'resolved';
    const STATUS_CLOSED      = 'closed';

    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    // -------------------------------------------------------------------------
    // Tickets
    // -------------------------------------------------------------------------

    /**
     * Create a new support ticket and its first message.
     *
     * @param array $data {
     *     @type string $subject
     *     @type string $message        First message body
     *     @type string $customer_name
     *     @type string $customer_email
     *     @type string $customer_company (optional)
     *     @type string $priority        (optional, default 'normal')
     *     @type string $license_key     (optional)
     *     @type int    $user_id         (optional)
     * }
     * @return int|false  New ticket ID on success, false on failure.
     */
    public static function create_ticket($data) {
        global $wpdb;

        $table_tickets  = $wpdb->prefix . 'themisdb_support_tickets';
        $table_messages = $wpdb->prefix . 'themisdb_support_messages';

        // Validate support benefits limits if license is provided
        if (!empty($data['license_key']) && class_exists('ThemisDB_Support_Benefits_Manager')) {
            // Get license from support portal's license auth system
            $license_table = $wpdb->prefix . 'themisdb_licenses';
            $license = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM $license_table WHERE license_key = %s", $data['license_key']),
                ARRAY_A
            );
            
            if ($license) {
                $license_id = $license['id'];
                $benefit = ThemisDB_Support_Benefits_Manager::get_by_license($license_id);
                
                if ($benefit && $benefit['benefit_status'] === 'active') {
                    $priority = isset($data['priority']) ? $data['priority'] : 'normal';
                    $limits_check = ThemisDB_Support_Benefits_Manager::check_limits($benefit['id'], $priority);
                    
                    if (!$limits_check['allowed']) {
                        // Log the limit violation
                        error_log("Support ticket creation blocked for license $license_id: " . $limits_check['reason']);
                        return false;
                    }
                } elseif ($benefit && $benefit['benefit_status'] !== 'active') {
                    // Benefit exists but is not active (pending, suspended, expired)
                    error_log("Support ticket creation blocked for license $license_id: Benefit status is " . $benefit['benefit_status']);
                    return false;
                }
            }
        }

        $ticket_data = array(
            'ticket_number'    => self::generate_ticket_number(),
            'subject'          => sanitize_text_field($data['subject']),
            'status'           => self::STATUS_OPEN,
            'priority'         => isset($data['priority']) && in_array($data['priority'], array('low', 'normal', 'high', 'urgent'), true)
                                    ? $data['priority']
                                    : self::PRIORITY_NORMAL,
            'customer_name'    => sanitize_text_field($data['customer_name']),
            'customer_email'   => sanitize_email($data['customer_email']),
            'customer_company' => isset($data['customer_company']) ? sanitize_text_field($data['customer_company']) : null,
            'license_key'      => isset($data['license_key'])  ? sanitize_text_field($data['license_key'])  : null,
            'user_id'          => isset($data['user_id'])      ? intval($data['user_id'])                   : null,
        );

        $result = $wpdb->insert($table_tickets, $ticket_data);

        if (!$result) {
            return false;
        }

        $ticket_id = $wpdb->insert_id;

        // Insert the first message
        if (!empty($data['message'])) {
            $wpdb->insert($table_messages, array(
                'ticket_id'     => $ticket_id,
                'author_name'   => $ticket_data['customer_name'],
                'author_email'  => $ticket_data['customer_email'],
                'message'       => wp_kses_post($data['message']),
                'is_admin_reply' => 0,
            ));
        }

        // Increment support benefit usage counter if applicable
        if (!empty($data['license_key']) && class_exists('ThemisDB_Support_Benefits_Manager')) {
            $license_table = $wpdb->prefix . 'themisdb_licenses';
            $license = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM $license_table WHERE license_key = %s", $data['license_key']),
                ARRAY_A
            );
            
            if ($license) {
                $benefit = ThemisDB_Support_Benefits_Manager::get_by_license($license['id']);
                if ($benefit) {
                    ThemisDB_Support_Benefits_Manager::increment_ticket_usage($benefit['id']);
                }
            }
        }

        // Send admin notification
        self::notify_admin_new_ticket($ticket_id, $ticket_data);

        return $ticket_id;
    }

    /**
     * Retrieve a single ticket by ID.
     *
     * @param int $ticket_id
     * @return array|null
     */
    public static function get_ticket($ticket_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE id = %d", $ticket_id),
            ARRAY_A
        );
    }

    /**
     * Retrieve a single ticket by ticket number.
     *
     * @param string $ticket_number
     * @return array|null
     */
    public static function get_ticket_by_number($ticket_number) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE ticket_number = %s", $ticket_number),
            ARRAY_A
        );
    }

    /**
     * List tickets with optional filters.
     *
     * @param array $args {
     *     @type string $status    Filter by status
     *     @type string $priority  Filter by priority
     *     @type int    $user_id   Filter by WordPress user ID
     *     @type int    $per_page  Default 20
     *     @type int    $page      Default 1
     *     @type string $orderby   Default 'created_at'
     *     @type string $order     'ASC' or 'DESC' (default 'DESC')
     * }
     * @return array { tickets: array, total: int }
     */
    public static function get_tickets($args = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        $defaults = array(
            'status'   => '',
            'priority' => '',
            'user_id'  => 0,
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);

        $where  = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if (!empty($args['priority'])) {
            $where[]  = 'priority = %s';
            $values[] = $args['priority'];
        }
        if (!empty($args['user_id'])) {
            $where[]  = 'user_id = %d';
            $values[] = intval($args['user_id']);
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = array('created_at', 'updated_at', 'status', 'priority', 'ticket_number');
        $orderby         = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order           = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $per_page = max(1, intval($args['per_page']));
        $offset   = max(0, (intval($args['page']) - 1) * $per_page);

        if (!empty($values)) {
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM `$table` WHERE $where_sql",
                $values
            );
            $rows_query = $wpdb->prepare(
                "SELECT * FROM `$table` WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                array_merge($values, array($per_page, $offset))
            );
        } else {
            $count_query = "SELECT COUNT(*) FROM `$table` WHERE $where_sql";
            $rows_query  = $wpdb->prepare(
                "SELECT * FROM `$table` WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
        }

        $total   = (int) $wpdb->get_var($count_query);
        $tickets = $wpdb->get_results($rows_query, ARRAY_A);

        return array(
            'tickets' => $tickets ?: array(),
            'total'   => $total,
        );
    }

    /**
     * Retrieve all tickets belonging to a specific WordPress user.
     *
     * @param int $user_id
     * @return array
     */
    public static function get_user_tickets($user_id) {
        $result = self::get_tickets(array(
            'user_id'  => $user_id,
            'per_page' => 100,
            'order'    => 'DESC',
        ));
        return $result['tickets'];
    }

    /**
     * Update the status of a ticket.
     *
     * @param int    $ticket_id
     * @param string $status
     * @return bool
     */
    public static function update_ticket_status($ticket_id, $status) {
        global $wpdb;

        $allowed = array(self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED);
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $table  = $wpdb->prefix . 'themisdb_support_tickets';
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id'     => intval($ticket_id))
        );

        return $result !== false;
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    /**
     * Add a message (reply) to a ticket.
     *
     * @param int   $ticket_id
     * @param array $message_data {
     *     @type string $author_name
     *     @type string $author_email
     *     @type string $message
     *     @type bool   $is_admin_reply
     * }
     * @return int|false  New message ID on success, false on failure.
     */
    public static function add_message($ticket_id, $message_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_messages';

        $result = $wpdb->insert($table, array(
            'ticket_id'      => intval($ticket_id),
            'author_name'    => sanitize_text_field($message_data['author_name']),
            'author_email'   => sanitize_email($message_data['author_email']),
            'message'        => wp_kses_post($message_data['message']),
            'is_admin_reply' => !empty($message_data['is_admin_reply']) ? 1 : 0,
        ));

        if (!$result) {
            return false;
        }

        $message_id = $wpdb->insert_id;

        // Update ticket updated_at timestamp
        $ticket_table = $wpdb->prefix . 'themisdb_support_tickets';
        $wpdb->update(
            $ticket_table,
            array('updated_at' => current_time('mysql')),
            array('id' => intval($ticket_id))
        );

        // If it is an admin reply, notify the customer
        if (!empty($message_data['is_admin_reply'])) {
            $ticket = self::get_ticket($ticket_id);
            if ($ticket) {
                self::notify_customer_reply($ticket, $message_data['message']);
            }
        }

        return $message_id;
    }

    /**
     * Retrieve all messages for a ticket, ordered chronologically.
     *
     * @param int $ticket_id
     * @return array
     */
    public static function get_messages($ticket_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_messages';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `$table` WHERE ticket_id = %d ORDER BY created_at ASC",
                $ticket_id
            ),
            ARRAY_A
        );

        return $results ?: array();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique, human-readable ticket number.
     * Format: TKT-YYYYMMDD-XXXX (e.g. TKT-20260315-A3F9)
     *
     * @return string
     */
    private static function generate_ticket_number() {
        $prefix = 'TKT-' . gmdate('Ymd') . '-';

        do {
            $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $number = $prefix . $suffix;
        } while (self::ticket_number_exists($number));

        return $number;
    }

    /**
     * Check whether a ticket number is already taken.
     *
     * @param string $ticket_number
     * @return bool
     */
    private static function ticket_number_exists($ticket_number) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        return (bool) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `$table` WHERE ticket_number = %s", $ticket_number)
        );
    }

    /**
     * Return human-readable status labels.
     *
     * @return array
     */
    public static function get_status_labels() {
        return array(
            self::STATUS_OPEN        => __('Offen', 'themisdb-support-portal'),
            self::STATUS_IN_PROGRESS => __('In Bearbeitung', 'themisdb-support-portal'),
            self::STATUS_RESOLVED    => __('Gelöst', 'themisdb-support-portal'),
            self::STATUS_CLOSED      => __('Geschlossen', 'themisdb-support-portal'),
        );
    }

    /**
     * Return human-readable priority labels.
     *
     * @return array
     */
    public static function get_priority_labels() {
        return array(
            self::PRIORITY_LOW    => __('Niedrig', 'themisdb-support-portal'),
            self::PRIORITY_NORMAL => __('Normal', 'themisdb-support-portal'),
            self::PRIORITY_HIGH   => __('Hoch', 'themisdb-support-portal'),
            self::PRIORITY_URGENT => __('Dringend', 'themisdb-support-portal'),
        );
    }

    // -------------------------------------------------------------------------
    // Email notifications
    // -------------------------------------------------------------------------

    /**
     * Notify the site admin when a new ticket is created.
     *
     * @param int   $ticket_id
     * @param array $ticket_data
     */
    private static function notify_admin_new_ticket($ticket_id, $ticket_data) {
        if (!get_option('themisdb_support_email_notifications', '1')) {
            return;
        }

        $admin_email = get_option('themisdb_support_admin_email', get_option('admin_email'));
        $from_name   = get_option('themisdb_support_email_from_name', get_option('blogname'));
        $from_email  = get_option('themisdb_support_email_from', get_option('admin_email'));

        $ticket = self::get_ticket($ticket_id);
        if (!$ticket) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: ticket number */
            __('[Support] Neues Ticket %s: %s', 'themisdb-support-portal'),
            esc_html($ticket['ticket_number']),
            esc_html($ticket['subject'])
        );

        $admin_url = admin_url('admin.php?page=themisdb-support-view&ticket_id=' . $ticket_id);

        $body = sprintf(
            __("Ein neues Support-Ticket wurde erstellt.\n\nTicket: %s\nBetreff: %s\nKunde: %s (%s)\nPriorität: %s\n\nTicket ansehen: %s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            $ticket['customer_name'],
            $ticket['customer_email'],
            $ticket['priority'],
            $admin_url
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail($admin_email, $subject, $body, $headers);
    }

    /**
     * Notify the customer when an admin replies to their ticket.
     *
     * @param array  $ticket
     * @param string $reply_message
     */
    private static function notify_customer_reply($ticket, $reply_message) {
        if (!get_option('themisdb_support_email_notifications', '1')) {
            return;
        }

        $from_name  = get_option('themisdb_support_email_from_name', get_option('blogname'));
        $from_email = get_option('themisdb_support_email_from', get_option('admin_email'));

        $subject = sprintf(
            /* translators: %s: ticket number */
            __('[Support] Antwort auf Ihr Ticket %s', 'themisdb-support-portal'),
            esc_html($ticket['ticket_number'])
        );

        $body = sprintf(
            __("Ihr Support-Ticket hat eine neue Antwort erhalten.\n\nTicket: %s\nBetreff: %s\n\nAntwort:\n%s\n\nMit freundlichen Grüßen,\n%s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            wp_strip_all_tags($reply_message),
            $from_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail(sanitize_email($ticket['customer_email']), $subject, $body, $headers);
    }
}
