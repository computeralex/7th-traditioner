<?php
/**
 * Helper Functions
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get supported currencies
 *
 * @return array Array of currency codes => currency names
 */
function seventh_trad_get_supported_currencies() {
    return array(
        'AUD' => __('Australian Dollar', '7th-traditioner'),
        'BRL' => __('Brazilian Real', '7th-traditioner'),
        'CAD' => __('Canadian Dollar', '7th-traditioner'),
        'CNY' => __('Chinese Renminbi', '7th-traditioner'),
        'CZK' => __('Czech Koruna', '7th-traditioner'),
        'DKK' => __('Danish Krone', '7th-traditioner'),
        'EUR' => __('Euro', '7th-traditioner'),
        'HKD' => __('Hong Kong Dollar', '7th-traditioner'),
        'HUF' => __('Hungarian Forint', '7th-traditioner'),
        'ILS' => __('Israeli New Shekel', '7th-traditioner'),
        'JPY' => __('Japanese Yen', '7th-traditioner'),
        'MYR' => __('Malaysian Ringgit', '7th-traditioner'),
        'MXN' => __('Mexican Peso', '7th-traditioner'),
        'TWD' => __('New Taiwan Dollar', '7th-traditioner'),
        'NZD' => __('New Zealand Dollar', '7th-traditioner'),
        'NOK' => __('Norwegian Krone', '7th-traditioner'),
        'PHP' => __('Philippine Peso', '7th-traditioner'),
        'PLN' => __('Polish Złoty', '7th-traditioner'),
        'GBP' => __('Pound Sterling', '7th-traditioner'),
        'SGD' => __('Singapore Dollar', '7th-traditioner'),
        'SEK' => __('Swedish Krona', '7th-traditioner'),
        'CHF' => __('Swiss Franc', '7th-traditioner'),
        'THB' => __('Thai Baht', '7th-traditioner'),
        'USD' => __('United States Dollar', '7th-traditioner'),
    );
}

/**
 * Get currency symbol
 *
 * @param string $currency_code Currency code
 * @return string
 */
function seventh_trad_get_currency_symbol($currency_code) {
    $symbols = array(
        'AUD' => '$',
        'BRL' => 'R$',
        'CAD' => '$',
        'CNY' => '¥',
        'CZK' => 'Kč',
        'DKK' => 'kr',
        'EUR' => '€',
        'HKD' => '$',
        'HUF' => 'Ft',
        'ILS' => '₪',
        'JPY' => '¥',
        'MYR' => 'RM',
        'MXN' => '$',
        'TWD' => 'NT$',
        'NZD' => '$',
        'NOK' => 'kr',
        'PHP' => '₱',
        'PLN' => 'zł',
        'GBP' => '£',
        'SGD' => '$',
        'SEK' => 'kr',
        'CHF' => 'CHF',
        'THB' => '฿',
        'USD' => '$',
    );

    return isset($symbols[$currency_code]) ? $symbols[$currency_code] : $currency_code;
}

/**
 * Convert day number to day name
 * Compatible with TSML's day numbering (0 = Sunday)
 *
 * @param int $day_number Day number (0-6)
 * @return string Day name
 */
function seventh_trad_get_day_name($day_number) {
    $days = array(
        0 => __('Sunday', '7th-traditioner'),
        1 => __('Monday', '7th-traditioner'),
        2 => __('Tuesday', '7th-traditioner'),
        3 => __('Wednesday', '7th-traditioner'),
        4 => __('Thursday', '7th-traditioner'),
        5 => __('Friday', '7th-traditioner'),
        6 => __('Saturday', '7th-traditioner'),
    );

    return isset($days[$day_number]) ? $days[$day_number] : '';
}

/**
 * Get groups from TSML
 * Uses the hybrid approach - TSML's own function for direct database query
 *
 * @return array Array of groups with group_id => group_name
 */
function seventh_trad_get_groups() {
    // Check if TSML is active
    if (!function_exists('tsml_get_groups')) {
        return array();
    }

    $tsml_groups = tsml_get_groups();
    $groups = array();

    foreach ($tsml_groups as $group_id => $group_data) {
        if (!empty($group_data['group'])) {
            $groups[$group_id] = $group_data['group'];
        }
    }

    // Sort alphabetically
    asort($groups);

    return $groups;
}

/**
 * Get meetings from TSML by day
 *
 * @param int $day Day number (0-6)
 * @return array Array of meetings
 */
function seventh_trad_get_meetings_by_day($day) {
    // Check if TSML is active
    if (!function_exists('tsml_get_meetings')) {
        return array();
    }

    $meetings = tsml_get_meetings(array('day' => $day));

    return $meetings;
}

/**
 * Format contribution amount with currency
 *
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function seventh_trad_format_amount($amount, $currency) {
    $symbol = seventh_trad_get_currency_symbol($currency);

    // Format based on currency (JPY and others don't use decimals)
    $no_decimal_currencies = array('JPY', 'HUF', 'TWD');

    if (in_array($currency, $no_decimal_currencies)) {
        return $symbol . number_format($amount, 0, '.', ',');
    }

    return $symbol . number_format($amount, 2, '.', ',');
}

/**
 * Verify reCAPTCHA token
 *
 * @param string $token reCAPTCHA token
 * @return bool True if valid, false otherwise
 */
function seventh_trad_verify_recaptcha($token) {
    $secret_key = get_option('seventh_trad_recaptcha_secret_key');

    if (empty($secret_key) || empty($token)) {
        return false;
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret' => $secret_key,
            'response' => $token,
        )
    ));

    if (is_wp_error($response)) {
        error_log('7th Traditioner: reCAPTCHA verification error - ' . $response->get_error_message());
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Check if successful and score is above threshold (0.5)
    if (isset($response_body['success']) && $response_body['success'] === true) {
        if (isset($response_body['score']) && $response_body['score'] >= 0.5) {
            return true;
        }
    }

    error_log('7th Traditioner: reCAPTCHA verification failed - ' . json_encode($response_body));
    return false;
}

/**
 * Get fellowship name (from TSML settings if available)
 *
 * @return string Fellowship name
 */
function seventh_trad_get_fellowship_name() {
    // Try to get from TSML
    if (function_exists('tsml_get_option_array')) {
        $entity = tsml_get_option_array('tsml_entity');
        if (!empty($entity['entity'])) {
            return $entity['entity'];
        }
    }

    // Fallback to our setting or site name
    $fellowship = get_option('seventh_trad_service_body_name');
    return !empty($fellowship) ? $fellowship : get_bloginfo('name');
}

/**
 * Sanitize contribution data
 *
 * @param array $data Raw data
 * @return array Sanitized data
 */
function seventh_trad_sanitize_contribution_data($data) {
    return array(
        'transaction_id' => sanitize_text_field($data['transaction_id'] ?? ''),
        'paypal_order_id' => sanitize_text_field($data['paypal_order_id'] ?? ''),
        'member_name' => sanitize_text_field($data['member_name'] ?? ''),
        'member_email' => sanitize_email($data['member_email'] ?? ''),
        'group_name' => sanitize_text_field($data['group_name'] ?? ''),
        'group_id' => absint($data['group_id'] ?? 0),
        'amount' => floatval($data['amount'] ?? 0),
        'currency' => sanitize_text_field($data['currency'] ?? 'USD'),
        'paypal_status' => sanitize_text_field($data['paypal_status'] ?? ''),
        'custom_notes' => sanitize_textarea_field($data['custom_notes'] ?? ''),
    );
}
