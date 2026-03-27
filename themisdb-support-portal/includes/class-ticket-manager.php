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
if (!class_exists('ThemisDB_SupportPortal_Ticket_Manager')) {
class ThemisDB_SupportPortal_Ticket_Manager {

    private static $last_error = '';
    const SYSTEM_AUTHOR_NAME  = 'ThemisDB System';
    const SYSTEM_AUTHOR_EMAIL = 'system@themisdb.local';

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
    *     @type int    $assignee_user_id (optional)
     * }
     * @return int|false  New ticket ID on success, false on failure.
     */
    public static function create_ticket($data) {
        global $wpdb;

        self::$last_error = '';

        $table_tickets  = $wpdb->prefix . 'themisdb_support_tickets';
        $table_messages = $wpdb->prefix . 'themisdb_support_messages';
        $benefit_id = null;
        $default_assignee_user_id = intval(get_option('themisdb_support_default_assignee_user_id', 0));

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
                        self::$last_error = $limits_check['reason'];
                        return false;
                    }

                    $benefit_id = intval($benefit['id']);
                } elseif ($benefit && $benefit['benefit_status'] !== 'active') {
                    // Benefit exists but is not active (pending, suspended, expired)
                    error_log("Support ticket creation blocked for license $license_id: Benefit status is " . $benefit['benefit_status']);
                    self::$last_error = sprintf('Support benefits are %s', $benefit['benefit_status']);
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
            'benefit_id'       => $benefit_id,
            'user_id'          => isset($data['user_id'])      ? intval($data['user_id'])                   : null,
            'assignee_user_id' => self::sanitize_assignee_user_id(isset($data['assignee_user_id']) ? $data['assignee_user_id'] : $default_assignee_user_id),
        );

        $result = $wpdb->insert($table_tickets, $ticket_data);

        if (!$result) {
            self::$last_error = __('Ticket konnte nicht gespeichert werden.', 'themisdb-support-portal');
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
        if (!empty($benefit_id) && class_exists('ThemisDB_Support_Benefits_Manager')) {
            ThemisDB_Support_Benefits_Manager::increment_ticket_usage($benefit_id);
        }

        // Send admin notification
        self::notify_admin_new_ticket($ticket_id, $ticket_data);

        /**
         * Fires after a support ticket is created in support portal.
         *
         * @param int   $ticket_id   New ticket ID.
         * @param array $ticket_data Persisted ticket fields.
         */
        do_action('themisdb_support_portal_ticket_created', $ticket_id, $ticket_data, $data);

        return $ticket_id;
    }

    /**
     * Return last human-readable creation error for UI feedback.
     *
     * @return string
     */
    public static function get_last_error() {
        return self::$last_error;
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
    *     @type int    $assignee_user_id Filter by assigned agent
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
            'assignee_user_id' => 0,
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
        if (!empty($args['assignee_user_id'])) {
            $where[]  = 'assignee_user_id = %d';
            $values[] = intval($args['assignee_user_id']);
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

        $ticket_id = intval($ticket_id);
        if ($ticket_id <= 0) {
            return false;
        }

        $current_ticket = self::get_ticket($ticket_id);
        if (!$current_ticket || !is_array($current_ticket)) {
            return false;
        }

        $old_status = isset($current_ticket['status']) ? sanitize_key($current_ticket['status']) : '';
        if ($old_status === $status) {
            return true;
        }

        $table  = $wpdb->prefix . 'themisdb_support_tickets';
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id'     => $ticket_id)
        );

        if ($result === false) {
            return false;
        }

        $updated_ticket = self::get_ticket($ticket_id);
        if ($updated_ticket && is_array($updated_ticket)) {
            self::log_status_change_history($updated_ticket, $old_status, $status);
            self::notify_admin_status_change($updated_ticket, $old_status, $status);
        }

        /**
         * Fires after ticket status has changed.
         *
         * @param int   $ticket_id
         * @param string $old_status
         * @param string $new_status
         * @param array|null $ticket
         */
        do_action('themisdb_support_portal_ticket_status_changed', $ticket_id, $old_status, $status, $updated_ticket);

        return true;
    }

    /**
     * Update editable ticket fields.
     *
     * @param int   $ticket_id
     * @param array $data
     * @return bool
     */
    public static function update_ticket($ticket_id, $data) {
        global $wpdb;

        $ticket_id = intval($ticket_id);
        if ($ticket_id <= 0 || !is_array($data)) {
            return false;
        }

        $current_ticket = self::get_ticket($ticket_id);
        if (!$current_ticket || !is_array($current_ticket)) {
            return false;
        }

        $old_assignee_user_id = isset($current_ticket['assignee_user_id']) ? intval($current_ticket['assignee_user_id']) : 0;

        $table = $wpdb->prefix . 'themisdb_support_tickets';
        $fields = array();

        if (isset($data['subject'])) {
            $subject = sanitize_text_field($data['subject']);
            if ($subject === '') {
                return false;
            }
            $fields['subject'] = $subject;
        }

        if (isset($data['priority'])) {
            $priority = sanitize_key($data['priority']);
            $allowed_priority = array(self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT);
            if (!in_array($priority, $allowed_priority, true)) {
                return false;
            }
            $fields['priority'] = $priority;
        }

        $status_to_apply = null;
        if (isset($data['status'])) {
            $status = sanitize_key($data['status']);
            $allowed_status = array(self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED);
            if (!in_array($status, $allowed_status, true)) {
                return false;
            }
            $status_to_apply = $status;
        }

        if (isset($data['customer_name'])) {
            $fields['customer_name'] = sanitize_text_field($data['customer_name']);
        }

        if (isset($data['customer_email'])) {
            $email = sanitize_email($data['customer_email']);
            if ($email === '' || !is_email($email)) {
                return false;
            }
            $fields['customer_email'] = $email;
        }

        if (isset($data['customer_company'])) {
            $fields['customer_company'] = sanitize_text_field($data['customer_company']);
        }

        if (isset($data['license_key'])) {
            $fields['license_key'] = sanitize_text_field($data['license_key']);
        }

        if (isset($data['assignee_user_id'])) {
            $fields['assignee_user_id'] = self::sanitize_assignee_user_id($data['assignee_user_id']);
        }

        if (empty($fields) && $status_to_apply === null) {
            return false;
        }

        $result = true;
        if (!empty($fields)) {
            $result = $wpdb->update($table, $fields, array('id' => $ticket_id));
            if ($result === false) {
                return false;
            }
        }

        if ($status_to_apply !== null) {
            if (!self::update_ticket_status($ticket_id, $status_to_apply)) {
                return false;
            }
        }

        if (isset($data['assignee_user_id'])) {
            $updated_ticket = self::get_ticket($ticket_id);
            if ($updated_ticket && is_array($updated_ticket)) {
                $new_assignee_user_id = isset($updated_ticket['assignee_user_id']) ? intval($updated_ticket['assignee_user_id']) : 0;
                if ($new_assignee_user_id !== $old_assignee_user_id) {
                    self::log_assignee_change_history($updated_ticket, $old_assignee_user_id, $new_assignee_user_id);
                    self::notify_assignee_assignment_change($updated_ticket, $old_assignee_user_id, $new_assignee_user_id);

                    /**
                     * Fires after assignee has changed for a ticket.
                     *
                     * @param int   $ticket_id
                     * @param int   $old_assignee_user_id
                     * @param int   $new_assignee_user_id
                     * @param array $ticket
                     */
                    do_action('themisdb_support_portal_ticket_assignee_changed', $ticket_id, $old_assignee_user_id, $new_assignee_user_id, $updated_ticket);
                }
            }
        }

        return true;
    }

    /**
     * Delete ticket and all related messages.
     *
     * @param int $ticket_id
     * @return bool
     */
    public static function delete_ticket($ticket_id) {
        global $wpdb;

        $ticket_id = intval($ticket_id);
        if ($ticket_id <= 0) {
            return false;
        }

        $table_tickets = $wpdb->prefix . 'themisdb_support_tickets';
        $table_messages = $wpdb->prefix . 'themisdb_support_messages';

        $wpdb->query('START TRANSACTION');

        $wpdb->delete($table_messages, array('ticket_id' => $ticket_id), array('%d'));
        $deleted = $wpdb->delete($table_tickets, array('id' => $ticket_id), array('%d'));

        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Bulk update status for multiple tickets.
     *
     * @param array  $ticket_ids
     * @param string $status
     * @return int Number of updated tickets
     */
    public static function bulk_update_status($ticket_ids, $status) {
        $allowed = array(self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED);
        if (!in_array($status, $allowed, true)) {
            return 0;
        }

        $ids = self::sanitize_ticket_ids($ticket_ids);
        if (empty($ids)) {
            return 0;
        }

        $updated = 0;
        foreach ($ids as $ticket_id) {
            if (self::update_ticket_status($ticket_id, $status)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Bulk assign tickets to a support agent or remove assignment.
     *
     * @param array    $ticket_ids
     * @param int|null $assignee_user_id
     * @return int Number of updated tickets
     */
    public static function bulk_update_assignee($ticket_ids, $assignee_user_id) {
        $ids = self::sanitize_ticket_ids($ticket_ids);
        if (empty($ids)) {
            return 0;
        }

        $sanitized_assignee_user_id = self::sanitize_assignee_user_id($assignee_user_id);
        $updated = 0;

        foreach ($ids as $ticket_id) {
            if (self::update_ticket($ticket_id, array('assignee_user_id' => $sanitized_assignee_user_id))) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Bulk delete tickets.
     *
     * @param array $ticket_ids
     * @return int Number of deleted tickets
     */
    public static function bulk_delete_tickets($ticket_ids) {
        $ids = self::sanitize_ticket_ids($ticket_ids);
        if (empty($ids)) {
            return 0;
        }

        $deleted = 0;
        foreach ($ids as $ticket_id) {
            if (self::delete_ticket($ticket_id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @param array $ticket_ids
     * @return array
     */
    private static function sanitize_ticket_ids($ticket_ids) {
        if (!is_array($ticket_ids)) {
            return array();
        }

        $ids = array_map('intval', $ticket_ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
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

        $ticket = self::get_ticket($ticket_id);

        // If it is an admin reply, notify the customer.
        // If it is a customer message, notify the assigned support agent.
        if ($ticket) {
            if (!empty($message_data['is_admin_reply'])) {
                self::notify_customer_reply($ticket, $message_data['message']);
            } else {
                self::notify_assignee_customer_message($ticket, $message_data['message']);
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

    /**
     * Detect whether a message row is a system history entry.
     *
     * @param array $message
     * @return bool
     */
    public static function is_system_message($message) {
        return is_array($message)
            && isset($message['author_email'])
            && sanitize_email($message['author_email']) === self::SYSTEM_AUTHOR_EMAIL;
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

    /**
     * Validate assignee user id against users that can edit content.
     *
     * @param mixed $assignee_user_id
     * @return int|null
     */
    private static function sanitize_assignee_user_id($assignee_user_id) {
        $assignee_user_id = intval($assignee_user_id);
        if ($assignee_user_id <= 0) {
            return null;
        }

        $user = get_user_by('id', $assignee_user_id);
        if (!$user || !($user instanceof WP_User) || !user_can($user, 'edit_posts')) {
            return null;
        }

        return $assignee_user_id;
    }

    /**
     * Resolve notification recipient: assigned editor first, fallback to admin email.
     *
     * @param array  $ticket
     * @param string $fallback_email
     * @return string
     */
    private static function resolve_notification_recipient_email($ticket, $fallback_email) {
        $assignee_user_id = isset($ticket['assignee_user_id']) ? intval($ticket['assignee_user_id']) : 0;
        if ($assignee_user_id > 0) {
            $assignee = get_user_by('id', $assignee_user_id);
            if ($assignee instanceof WP_User && is_email($assignee->user_email)) {
                return sanitize_email($assignee->user_email);
            }
        }

        return sanitize_email($fallback_email);
    }

    /**
     * Return assignee display name for mail output.
     *
     * @param array $ticket
     * @return string
     */
    private static function get_assignee_label($ticket) {
        $assignee_user_id = isset($ticket['assignee_user_id']) ? intval($ticket['assignee_user_id']) : 0;
        return self::get_assignee_label_by_user_id($assignee_user_id);
    }

    /**
     * Return assignee label for a user ID.
     *
     * @param int $assignee_user_id
     * @return string
     */
    private static function get_assignee_label_by_user_id($assignee_user_id) {
        $assignee_user_id = intval($assignee_user_id);
        if ($assignee_user_id <= 0) {
            return __('Nicht zugewiesen', 'themisdb-support-portal');
        }

        $assignee = get_user_by('id', $assignee_user_id);
        if (!($assignee instanceof WP_User)) {
            return __('Nicht zugewiesen', 'themisdb-support-portal');
        }

        return $assignee->display_name;
    }

    /**
     * Add an internal system message without triggering customer notifications.
     *
     * @param int    $ticket_id
     * @param string $message
     * @return int|false
     */
    private static function add_system_message($ticket_id, $message) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_messages';
        $result = $wpdb->insert($table, array(
            'ticket_id' => intval($ticket_id),
            'author_name' => self::SYSTEM_AUTHOR_NAME,
            'author_email' => self::SYSTEM_AUTHOR_EMAIL,
            'message' => wp_kses_post($message),
            'is_admin_reply' => 1,
        ));

        if (!$result) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Add a history entry for status changes.
     *
     * @param array  $ticket
     * @param string $old_status
     * @param string $new_status
     */
    private static function log_status_change_history($ticket, $old_status, $new_status) {
        if (empty($ticket['id'])) {
            return;
        }

        $status_labels = self::get_status_labels();
        $old_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;

        $message = sprintf(
            __('Status geändert: %1$s -> %2$s', 'themisdb-support-portal'),
            $old_label,
            $new_label
        );

        self::add_system_message(intval($ticket['id']), $message);
    }

    /**
     * Add a history entry for assignee changes.
     *
     * @param array $ticket
     * @param int   $old_assignee_user_id
     * @param int   $new_assignee_user_id
     */
    private static function log_assignee_change_history($ticket, $old_assignee_user_id, $new_assignee_user_id) {
        if (empty($ticket['id'])) {
            return;
        }

        $message = sprintf(
            __('Bearbeiter geändert: %1$s -> %2$s', 'themisdb-support-portal'),
            self::get_assignee_label_by_user_id($old_assignee_user_id),
            self::get_assignee_label_by_user_id($new_assignee_user_id)
        );

        self::add_system_message(intval($ticket['id']), $message);
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
        $status_labels = self::get_status_labels();
        $priority_labels = self::get_priority_labels();
        $status_label = isset($status_labels[$ticket['status']]) ? $status_labels[$ticket['status']] : $ticket['status'];
        $priority_label = isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority'];
        $assignee_label = self::get_assignee_label($ticket);
        $recipient_email = self::resolve_notification_recipient_email($ticket, $admin_email);

        $body = sprintf(
            __("Ein neues Support-Ticket wurde erstellt.\n\nTicket: %s\nBetreff: %s\nKunde: %s (%s)\nStatus: %s\nPriorität: %s\nZugewiesen an: %s\n\nTicket ansehen: %s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            $ticket['customer_name'],
            $ticket['customer_email'],
            $status_label,
            $priority_label,
            $assignee_label,
            $admin_url
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail($recipient_email, $subject, $body, $headers);
    }

    /**
     * Notify the support admin when a ticket status changes.
     *
     * @param array  $ticket
     * @param string $old_status
     * @param string $new_status
     */
    private static function notify_admin_status_change($ticket, $old_status, $new_status) {
        if (!get_option('themisdb_support_email_notifications', '1')) {
            return;
        }

        if (!get_option('themisdb_support_status_email_notifications', '1')) {
            return;
        }

        $admin_email = get_option('themisdb_support_admin_email', get_option('admin_email'));
        $from_name   = get_option('themisdb_support_email_from_name', get_option('blogname'));
        $from_email  = get_option('themisdb_support_email_from', get_option('admin_email'));

        if (empty($ticket['id']) || empty($ticket['ticket_number'])) {
            return;
        }

        $status_labels  = self::get_status_labels();
        $priority_labels = self::get_priority_labels();
        $old_label      = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_label      = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
        $priority_label = isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority'];
        $assignee_label = self::get_assignee_label($ticket);
        $recipient_email = self::resolve_notification_recipient_email($ticket, $admin_email);

        $subject = sprintf(
            /* translators: 1: ticket number, 2: new status */
            __('[Support] Ticket %1$s Status: %2$s', 'themisdb-support-portal'),
            esc_html($ticket['ticket_number']),
            esc_html($new_label)
        );

        $admin_url = admin_url('admin.php?page=themisdb-support-view&ticket_id=' . intval($ticket['id']));

        $body = sprintf(
            __("Der Status eines Support-Tickets wurde geändert.\n\nTicket: %s\nBetreff: %s\nKunde: %s (%s)\nAlter Status: %s\nNeuer Status: %s\nPriorität: %s\nZugewiesen an: %s\n\nTicket ansehen: %s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            $ticket['customer_name'],
            $ticket['customer_email'],
            $old_label,
            $new_label,
            $priority_label,
            $assignee_label,
            $admin_url
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail($recipient_email, $subject, $body, $headers);
    }

    /**
     * Notify newly assigned support editor about assignment.
     *
     * @param array $ticket
     * @param int   $old_assignee_user_id
     * @param int   $new_assignee_user_id
     */
    private static function notify_assignee_assignment_change($ticket, $old_assignee_user_id, $new_assignee_user_id) {
        if (!get_option('themisdb_support_email_notifications', '1')) {
            return;
        }

        if (!get_option('themisdb_support_assignee_email_notifications', '1')) {
            return;
        }

        if ($new_assignee_user_id <= 0) {
            return;
        }

        $assignee = get_user_by('id', $new_assignee_user_id);
        if (!($assignee instanceof WP_User) || !is_email($assignee->user_email)) {
            return;
        }

        $from_name  = get_option('themisdb_support_email_from_name', get_option('blogname'));
        $from_email = get_option('themisdb_support_email_from', get_option('admin_email'));

        $old_assignee_label = __('Nicht zugewiesen', 'themisdb-support-portal');
        if ($old_assignee_user_id > 0) {
            $old_assignee = get_user_by('id', $old_assignee_user_id);
            if ($old_assignee instanceof WP_User) {
                $old_assignee_label = $old_assignee->display_name;
            }
        }

        $subject = sprintf(
            /* translators: %s: ticket number */
            __('[Support] Ticket %s wurde Ihnen zugewiesen', 'themisdb-support-portal'),
            esc_html($ticket['ticket_number'])
        );

        $admin_url = admin_url('admin.php?page=themisdb-support&ticket_id=' . intval($ticket['id']));

        $body = sprintf(
            __("Ein Support-Ticket wurde Ihnen zugewiesen.\n\nTicket: %s\nBetreff: %s\nKunde: %s (%s)\nVorheriger Bearbeiter: %s\n\nTicket ansehen: %s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            $ticket['customer_name'],
            $ticket['customer_email'],
            $old_assignee_label,
            $admin_url
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail(sanitize_email($assignee->user_email), $subject, $body, $headers);
    }

    /**
     * Notify assigned support editor when customer posts a new message.
     *
     * @param array  $ticket
     * @param string $customer_message
     */
    private static function notify_assignee_customer_message($ticket, $customer_message) {
        if (!get_option('themisdb_support_email_notifications', '1')) {
            return;
        }

        if (!get_option('themisdb_support_assignee_email_notifications', '1')) {
            return;
        }

        $assignee_user_id = isset($ticket['assignee_user_id']) ? intval($ticket['assignee_user_id']) : 0;
        if ($assignee_user_id <= 0) {
            return;
        }

        $assignee = get_user_by('id', $assignee_user_id);
        if (!($assignee instanceof WP_User) || !is_email($assignee->user_email)) {
            return;
        }

        $from_name  = get_option('themisdb_support_email_from_name', get_option('blogname'));
        $from_email = get_option('themisdb_support_email_from', get_option('admin_email'));

        $subject = sprintf(
            /* translators: %s: ticket number */
            __('[Support] Neue Kunden-Nachricht zu Ticket %s', 'themisdb-support-portal'),
            esc_html($ticket['ticket_number'])
        );

        $admin_url = admin_url('admin.php?page=themisdb-support&ticket_id=' . intval($ticket['id']));

        $body = sprintf(
            __("Es gibt eine neue Kunden-Nachricht zu einem Ihnen zugewiesenen Ticket.\n\nTicket: %s\nBetreff: %s\nKunde: %s (%s)\n\nNachricht:\n%s\n\nTicket ansehen: %s", 'themisdb-support-portal'),
            $ticket['ticket_number'],
            $ticket['subject'],
            $ticket['customer_name'],
            $ticket['customer_email'],
            wp_strip_all_tags($customer_message),
            $admin_url
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>',
        );

        wp_mail(sanitize_email($assignee->user_email), $subject, $body, $headers);
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
}

// Backward compatibility for older integrations that still reference
// ThemisDB_Support_Ticket_Manager. Do not alias when another plugin already
// provides that class name.
if (!class_exists('ThemisDB_Support_Ticket_Manager') && class_exists('ThemisDB_SupportPortal_Ticket_Manager')) {
    class_alias('ThemisDB_SupportPortal_Ticket_Manager', 'ThemisDB_Support_Ticket_Manager');
}
