<?php
/**
 * Database Handler
 *
 * Manages the contributions database table
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Database {

    /**
     * Table name (without prefix)
     */
    const TABLE_NAME = 'seventh_trad_contributions';

    /**
     * Get full table name with prefix
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create contributions table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            paypal_order_id varchar(255) DEFAULT NULL,
            member_name varchar(255) DEFAULT NULL,
            member_email varchar(255) DEFAULT NULL,
            group_name varchar(255) DEFAULT NULL,
            group_id mediumint(9) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL,
            contribution_date datetime DEFAULT CURRENT_TIMESTAMP,
            paypal_status varchar(50) DEFAULT NULL,
            custom_notes text DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY transaction_id (transaction_id),
            KEY paypal_order_id (paypal_order_id),
            KEY member_email (member_email),
            KEY group_id (group_id),
            KEY contribution_date (contribution_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Store the database version
        add_option('seventh_trad_db_version', '1.0');
    }

    /**
     * Insert a contribution record
     *
     * @param array $data Contribution data
     * @return int|false Insert ID on success, false on failure
     */
    public static function insert_contribution($data) {
        global $wpdb;

        $defaults = array(
            'transaction_id' => '',
            'paypal_order_id' => '',
            'member_name' => '',
            'member_email' => '',
            'group_name' => '',
            'group_id' => null,
            'amount' => 0,
            'currency' => 'USD',
            'paypal_status' => '',
            'custom_notes' => '',
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            self::get_table_name(),
            array(
                'transaction_id' => sanitize_text_field($data['transaction_id']),
                'paypal_order_id' => sanitize_text_field($data['paypal_order_id']),
                'member_name' => sanitize_text_field($data['member_name']),
                'member_email' => sanitize_email($data['member_email']),
                'group_name' => sanitize_text_field($data['group_name']),
                'group_id' => absint($data['group_id']),
                'amount' => floatval($data['amount']),
                'currency' => sanitize_text_field($data['currency']),
                'paypal_status' => sanitize_text_field($data['paypal_status']),
                'custom_notes' => sanitize_textarea_field($data['custom_notes']),
                'ip_address' => sanitize_text_field($data['ip_address']),
                'user_agent' => sanitize_text_field($data['user_agent']),
            ),
            array(
                '%s', // transaction_id
                '%s', // paypal_order_id
                '%s', // member_name
                '%s', // member_email
                '%s', // group_name
                '%d', // group_id
                '%f', // amount
                '%s', // currency
                '%s', // paypal_status
                '%s', // custom_notes
                '%s', // ip_address
                '%s', // user_agent
            )
        );

        if (false === $result) {
            error_log('7th Traditioner: Failed to insert contribution - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get contribution by ID
     *
     * @param int $id Contribution ID
     * @return object|null
     */
    public static function get_contribution($id) {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }

    /**
     * Get contribution by transaction ID
     *
     * @param string $transaction_id Transaction ID
     * @return object|null
     */
    public static function get_contribution_by_transaction($transaction_id) {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE transaction_id = %s OR paypal_order_id = %s",
            $transaction_id,
            $transaction_id
        ));
    }

    /**
     * Get all contributions with optional filters
     *
     * @param array $args Query arguments
     * @return array
     */
    public static function get_contributions($args = array()) {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'contribution_date',
            'order' => 'DESC',
            'group_id' => null,
            'date_from' => null,
            'date_to' => null,
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['group_id']) {
            $where[] = 'group_id = %d';
            $values[] = $args['group_id'];
        }

        if ($args['date_from']) {
            $where[] = 'contribution_date >= %s';
            $values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'contribution_date <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }

        return 'UNKNOWN';
    }
}
