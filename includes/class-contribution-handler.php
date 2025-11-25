<?php
/**
 * Contribution Handler
 *
 * Handles AJAX requests for saving contributions
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Contribution_Handler {

    /**
     * Save contribution via AJAX
     */
    public static function save_contribution() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seventh_trad_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed. Please refresh the page and try again.', '7th-traditioner')
            ));
        }

        // Verify reCAPTCHA
        if (get_option('seventh_trad_recaptcha_site_key')) {
            $recaptcha_token = isset($_POST['recaptcha_token']) ? sanitize_text_field($_POST['recaptcha_token']) : '';

            if (!seventh_trad_verify_recaptcha($recaptcha_token)) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed. Please try again.', '7th-traditioner')
                ));
            }
        }

        // Validate required fields
        $required_fields = array('transaction_id', 'member_name', 'member_email', 'amount', 'currency', 'contributor_type');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: field name */
                        __('Required field missing: %s', '7th-traditioner'),
                        $field
                    )
                ));
            }
        }

        // Validate group-specific fields if contributing on behalf of group
        if ($_POST['contributor_type'] === 'group') {
            error_log('7th Traditioner: Group validation - meeting_day value: ' . print_r($_POST['meeting_day'], true));
            error_log('7th Traditioner: Group validation - meeting_id value: ' . print_r($_POST['meeting_id'], true));
            error_log('7th Traditioner: Group validation - meeting_name value: ' . print_r($_POST['meeting_name'], true));

            if (!isset($_POST['meeting_day']) || $_POST['meeting_day'] === '') {
                wp_send_json_error(array(
                    'message' => __('Meeting day is required when contributing on behalf of a group.', '7th-traditioner')
                ));
            }
            if (empty($_POST['meeting_id']) && empty($_POST['meeting_name'])) {
                wp_send_json_error(array(
                    'message' => __('Meeting information is required when contributing on behalf of a group.', '7th-traditioner')
                ));
            }
        }

        // Sanitize data
        $data = seventh_trad_sanitize_contribution_data($_POST);

        // Debug log
        error_log('7th Traditioner: Sanitized data - ' . json_encode($data));

        // Validate name
        if (empty($data['member_name']) || strlen(trim($data['member_name'])) < 2) {
            wp_send_json_error(array(
                'message' => __('Please provide your name.', '7th-traditioner')
            ));
        }

        // Validate email
        if (!is_email($data['member_email'])) {
            wp_send_json_error(array(
                'message' => __('Please provide a valid email address.', '7th-traditioner')
            ));
        }

        // Validate amount
        if ($data['amount'] <= 0) {
            wp_send_json_error(array(
                'message' => __('Contribution amount must be greater than zero.', '7th-traditioner')
            ));
        }

        // Note: Min/max validation is handled in frontend JavaScript only
        // We don't enforce server-side to avoid rejecting payments that PayPal already captured

        // Check if transaction already exists
        $existing = Seventh_Trad_Database::get_contribution_by_transaction($data['transaction_id']);
        if ($existing) {
            wp_send_json_error(array(
                'message' => __('This transaction has already been processed.', '7th-traditioner')
            ));
        }

        // Get group name from ID only if not already set (from manual entry or dropdown)
        if (empty($data['group_name']) && function_exists('tsml_get_groups')) {
            $groups = tsml_get_groups();
            if (isset($groups[$data['group_id']])) {
                $data['group_name'] = $groups[$data['group_id']]['group'];
            }
        }

        // Insert contribution
        $contribution_id = Seventh_Trad_Database::insert_contribution($data);

        if (!$contribution_id) {
            error_log('7th Traditioner: Failed to save contribution - ' . json_encode($data));
            wp_send_json_error(array(
                'message' => __('Failed to save contribution. Please contact support.', '7th-traditioner')
            ));
        }

        // Send receipt email
        $email_sent = Seventh_Trad_Email_Handler::send_receipt($contribution_id);

        if (!$email_sent) {
            error_log('7th Traditioner: Failed to send receipt email for contribution ID: ' . $contribution_id);
        }

        // Success response
        wp_send_json_success(array(
            'message' => __('Thank you for your contribution! A receipt has been sent to your email.', '7th-traditioner'),
            'contribution_id' => $contribution_id,
        ));
    }
}
