<?php
/**
 * PayPal Webhook Handler
 *
 * Records subscription renewals and status changes.
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_PayPal_Webhook {

    /**
     * Register REST route
     */
    public static function register_routes() {
        register_rest_route('seventh-traditioner/v1', '/paypal-webhook', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle incoming PayPal webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function handle_webhook($request) {
        if (!seventh_trad_recurring_enabled()) {
            return new WP_REST_Response(array('message' => 'Recurring disabled'), 200);
        }

        $body = $request->get_body();
        $headers = self::normalize_headers($request->get_headers());

        if (!Seventh_Trad_PayPal_Subscriptions::verify_webhook_signature($headers, $body)) {
            error_log('7th Traditioner: PayPal webhook signature verification failed');
            return new WP_REST_Response(array('message' => 'Invalid signature'), 400);
        }

        $event = json_decode($body, true);
        if (empty($event['event_type'])) {
            return new WP_REST_Response(array('message' => 'No event type'), 400);
        }

        switch ($event['event_type']) {
            case 'PAYMENT.SALE.COMPLETED':
                self::handle_payment_completed($event);
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                self::handle_subscription_status_change($event);
                break;
        }

        return new WP_REST_Response(array('message' => 'OK'), 200);
    }

    /**
     * Normalize REST headers for PayPal verification
     *
     * @param array $headers Request headers
     * @return array
     */
    private static function normalize_headers($headers) {
        $normalized = array();

        $map = array(
            'paypal-auth-algo' => 'paypal-auth-algo',
            'paypal-cert-url' => 'paypal-cert-url',
            'paypal-transmission-id' => 'paypal-transmission-id',
            'paypal-transmission-sig' => 'paypal-transmission-sig',
            'paypal-transmission-time' => 'paypal-transmission-time',
        );

        foreach ($map as $key => $paypal_key) {
            if (!empty($headers[$key])) {
                $normalized[$paypal_key] = is_array($headers[$key]) ? $headers[$key][0] : $headers[$key];
            }
        }

        return $normalized;
    }

    /**
     * Record a completed subscription payment (renewals)
     *
     * @param array $event Webhook event
     */
    private static function handle_payment_completed($event) {
        $resource = isset($event['resource']) ? $event['resource'] : array();
        $sale_id = isset($resource['id']) ? $resource['id'] : '';

        if (empty($sale_id)) {
            return;
        }

        if (Seventh_Trad_Database::get_contribution_by_transaction($sale_id)) {
            return;
        }

        $subscription_id = '';
        if (!empty($resource['billing_agreement_id'])) {
            $subscription_id = $resource['billing_agreement_id'];
        } elseif (!empty($resource['custom'])) {
            $subscription_id = $resource['custom'];
        }

        if (empty($subscription_id)) {
            return;
        }

        $initial = Seventh_Trad_Database::get_initial_subscription_contribution($subscription_id);
        if (!$initial) {
            return;
        }

        $amount = isset($resource['amount']['total']) ? floatval($resource['amount']['total']) : $initial->amount;
        $currency = isset($resource['amount']['currency']) ? $resource['amount']['currency'] : $initial->currency;

        $data = array(
            'transaction_id' => $sale_id,
            'paypal_order_id' => $sale_id,
            'member_name' => $initial->member_name,
            'member_email' => $initial->member_email,
            'member_phone' => $initial->member_phone,
            'contribution_type' => $initial->contribution_type,
            'meeting_day' => $initial->meeting_day,
            'group_name' => $initial->group_name,
            'group_id' => $initial->group_id,
            'amount' => $amount,
            'currency' => $currency,
            'paypal_status' => isset($resource['state']) ? $resource['state'] : 'completed',
            'is_recurring' => 1,
            'is_renewal' => 1,
            'subscription_id' => $subscription_id,
            'subscription_status' => $initial->subscription_status,
            'plan_id' => $initial->plan_id,
            'custom_notes' => $initial->custom_notes,
            'ip_address' => 'WEBHOOK',
            'user_agent' => 'PayPal Webhook',
        );

        $contribution_id = Seventh_Trad_Database::insert_contribution($data);
        if ($contribution_id) {
            Seventh_Trad_Email_Handler::send_receipt($contribution_id);
        }
    }

    /**
     * Update subscription status from lifecycle events
     *
     * @param array $event Webhook event
     */
    private static function handle_subscription_status_change($event) {
        $resource = isset($event['resource']) ? $event['resource'] : array();
        $subscription_id = isset($resource['id']) ? $resource['id'] : '';

        if (empty($subscription_id)) {
            return;
        }

        $status = isset($resource['status']) ? $resource['status'] : '';
        if (empty($status) && !empty($event['event_type'])) {
            $status = str_replace('BILLING.SUBSCRIPTION.', '', $event['event_type']);
        }

        Seventh_Trad_Database::update_subscription_status($subscription_id, $status);
    }
}