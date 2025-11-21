<?php
/**
 * PayPal Handler Class
 *
 * Handles PayPal API interactions for creating and capturing orders
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_PayPal_Handler {

    /**
     * Get PayPal API base URL based on mode
     */
    private static function get_api_base_url() {
        $mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        return ($mode === 'live')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get PayPal client ID based on mode
     */
    private static function get_client_id() {
        $mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        return ($mode === 'live')
            ? get_option('seventh_trad_paypal_live_client_id')
            : get_option('seventh_trad_paypal_sandbox_client_id');
    }

    /**
     * Get PayPal secret based on mode
     */
    private static function get_secret() {
        $mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        return ($mode === 'live')
            ? get_option('seventh_trad_paypal_live_secret')
            : get_option('seventh_trad_paypal_sandbox_secret');
    }

    /**
     * Get PayPal access token
     */
    private static function get_access_token() {
        $client_id = self::get_client_id();
        $secret = self::get_secret();

        if (empty($client_id) || empty($secret)) {
            return new WP_Error('missing_credentials', 'PayPal credentials not configured');
        }

        $api_base = self::get_api_base_url();

        $response = wp_remote_post($api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token'])) {
            return new WP_Error('token_error', 'Failed to get PayPal access token');
        }

        return $body['access_token'];
    }

    /**
     * Create PayPal order
     *
     * @param array $order_data Order data including amount, currency, description
     * @return array|WP_Error Order details or error
     */
    public static function create_order($order_data) {
        $access_token = self::get_access_token();

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $api_base = self::get_api_base_url();

        // Prepare order payload
        $payload = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => $order_data['currency'],
                        'value' => number_format((float)$order_data['amount'], 2, '.', ''),
                    ),
                    'description' => $order_data['description'],
                )
            ),
            'application_context' => array(
                'brand_name' => get_option('seventh_trad_service_body_name', get_bloginfo('name')),
                'locale' => 'en-US',
                'landing_page' => 'NO_PREFERENCE',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => !empty($order_data['return_url']) ? $order_data['return_url'] : home_url('/contribution-success'),
                'cancel_url' => !empty($order_data['cancel_url']) ? $order_data['cancel_url'] : home_url('/contribution-cancelled'),
            ),
        );

        $response = wp_remote_post($api_base . '/v2/checkout/orders', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 201 || empty($body['id'])) {
            error_log('PayPal Order Creation Error: ' . print_r($body, true));
            return new WP_Error('order_creation_failed',
                isset($body['message']) ? $body['message'] : 'Failed to create PayPal order'
            );
        }

        // Extract approval URL
        $approve_url = '';
        if (!empty($body['links'])) {
            foreach ($body['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approve_url = $link['href'];
                    break;
                }
            }
        }

        return array(
            'order_id' => $body['id'],
            'status' => $body['status'],
            'approve_url' => $approve_url,
        );
    }

    /**
     * Capture PayPal order (after user approves)
     *
     * @param string $order_id PayPal order ID
     * @return array|WP_Error Capture details or error
     */
    public static function capture_order($order_id) {
        $access_token = self::get_access_token();

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $api_base = self::get_api_base_url();

        $response = wp_remote_post($api_base . '/v2/checkout/orders/' . $order_id . '/capture', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 201 || empty($body['id'])) {
            error_log('PayPal Order Capture Error: ' . print_r($body, true));
            return new WP_Error('capture_failed',
                isset($body['message']) ? $body['message'] : 'Failed to capture PayPal order'
            );
        }

        return $body;
    }

    /**
     * Get order details
     *
     * @param string $order_id PayPal order ID
     * @return array|WP_Error Order details or error
     */
    public static function get_order($order_id) {
        $access_token = self::get_access_token();

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $api_base = self::get_api_base_url();

        $response = wp_remote_get($api_base . '/v2/checkout/orders/' . $order_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['id'])) {
            return new WP_Error('order_not_found', 'PayPal order not found');
        }

        return $body;
    }
}
