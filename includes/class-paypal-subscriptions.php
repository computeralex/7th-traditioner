<?php
/**
 * PayPal Subscriptions Handler
 *
 * Creates products/plans and manages subscription API calls.
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_PayPal_Subscriptions {

    /**
     * Option key for stored product ID per mode
     */
    private static function get_product_option_key() {
        return 'seventh_trad_paypal_product_id_' . Seventh_Trad_PayPal_Handler::get_mode();
    }

    /**
     * Make an authenticated PayPal API request
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param array|null $body Request body
     * @return array|WP_Error
     */
    private static function api_request($method, $path, $body = null) {
        $access_token = Seventh_Trad_PayPal_Handler::get_access_token();

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(Seventh_Trad_PayPal_Handler::get_api_base_url() . $path, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code < 200 || $status_code >= 300) {
            $message = isset($decoded['message']) ? $decoded['message'] : 'PayPal API request failed';
            error_log('7th Traditioner PayPal Subscriptions Error: ' . print_r($decoded, true));
            return new WP_Error('paypal_subscription_error', $message, $decoded);
        }

        return is_array($decoded) ? $decoded : array();
    }

    /**
     * Ensure the shared PayPal product exists
     *
     * @return string|WP_Error Product ID
     */
    public static function ensure_product() {
        $option_key = self::get_product_option_key();
        $product_id = get_option($option_key);

        if (!empty($product_id)) {
            return $product_id;
        }

        $fellowship_name = seventh_trad_get_fellowship_name();

        $result = self::api_request('POST', '/v1/catalogs/products', array(
            'name' => sprintf('%s - 7th Tradition', $fellowship_name),
            'description' => __('Voluntary monthly member contribution (7th Tradition)', '7th-traditioner'),
            'type' => 'SERVICE',
            'category' => 'CHARITY',
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (empty($result['id'])) {
            return new WP_Error('product_creation_failed', __('Failed to create PayPal product.', '7th-traditioner'));
        }

        update_option($option_key, $result['id']);

        return $result['id'];
    }

    /**
     * Create a monthly billing plan for a specific amount and currency
     *
     * @param float $amount Amount
     * @param string $currency Currency code
     * @return array|WP_Error Plan details with plan_id
     */
    public static function create_monthly_plan($amount, $currency) {
        $currency = strtoupper(sanitize_text_field($currency));
        $amount = floatval($amount);

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', __('Invalid subscription amount.', '7th-traditioner'));
        }

        $supported = seventh_trad_get_enabled_currencies();
        if (!isset($supported[$currency])) {
            return new WP_Error('invalid_currency', __('Invalid currency.', '7th-traditioner'));
        }

        $product_id = self::ensure_product();
        if (is_wp_error($product_id)) {
            return $product_id;
        }

        $decimals = seventh_trad_get_currency_decimals($currency);
        $formatted_amount = number_format($amount, $decimals, '.', '');

        $fellowship_name = seventh_trad_get_fellowship_name();

        $result = self::api_request('POST', '/v1/billing/plans', array(
            'product_id' => $product_id,
            'name' => sprintf(
                /* translators: 1: fellowship name, 2: amount, 3: currency */
                __('%1$s Monthly - %2$s %3$s', '7th-traditioner'),
                $fellowship_name,
                $formatted_amount,
                $currency
            ),
            'description' => __('Voluntary monthly member contribution (7th Tradition)', '7th-traditioner'),
            'status' => 'ACTIVE',
            'billing_cycles' => array(
                array(
                    'frequency' => array(
                        'interval_unit' => 'MONTH',
                        'interval_count' => 1,
                    ),
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0,
                    'pricing_scheme' => array(
                        'fixed_price' => array(
                            'value' => $formatted_amount,
                            'currency_code' => $currency,
                        ),
                    ),
                ),
            ),
            'payment_preferences' => array(
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 3,
            ),
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (empty($result['id'])) {
            return new WP_Error('plan_creation_failed', __('Failed to create PayPal billing plan.', '7th-traditioner'));
        }

        return array(
            'plan_id' => $result['id'],
            'status' => isset($result['status']) ? $result['status'] : 'ACTIVE',
        );
    }

    /**
     * Get subscription details from PayPal
     *
     * @param string $subscription_id Subscription ID
     * @return array|WP_Error
     */
    public static function get_subscription($subscription_id) {
        $subscription_id = sanitize_text_field($subscription_id);

        if (empty($subscription_id)) {
            return new WP_Error('invalid_subscription', __('Invalid subscription ID.', '7th-traditioner'));
        }

        return self::api_request('GET', '/v1/billing/subscriptions/' . rawurlencode($subscription_id));
    }

    /**
     * Verify a PayPal webhook signature
     *
     * @param array $headers Request headers
     * @param string $body Raw request body
     * @return bool
     */
    public static function verify_webhook_signature($headers, $body) {
        $webhook_id = get_option('seventh_trad_paypal_webhook_id');
        if (empty($webhook_id)) {
            return false;
        }

        $access_token = Seventh_Trad_PayPal_Handler::get_access_token();
        if (is_wp_error($access_token)) {
            return false;
        }

        $payload = array(
            'auth_algo' => isset($headers['paypal-auth-algo']) ? $headers['paypal-auth-algo'] : '',
            'cert_url' => isset($headers['paypal-cert-url']) ? $headers['paypal-cert-url'] : '',
            'transmission_id' => isset($headers['paypal-transmission-id']) ? $headers['paypal-transmission-id'] : '',
            'transmission_sig' => isset($headers['paypal-transmission-sig']) ? $headers['paypal-transmission-sig'] : '',
            'transmission_time' => isset($headers['paypal-transmission-time']) ? $headers['paypal-transmission-time'] : '',
            'webhook_id' => $webhook_id,
            'webhook_event' => json_decode($body, true),
        );

        $response = wp_remote_post(
            Seventh_Trad_PayPal_Handler::get_api_base_url() . '/v1/notifications/verify-webhook-signature',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($payload),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            error_log('7th Traditioner: Webhook verification error - ' . $response->get_error_message());
            return false;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        return isset($decoded['verification_status']) && $decoded['verification_status'] === 'SUCCESS';
    }
}