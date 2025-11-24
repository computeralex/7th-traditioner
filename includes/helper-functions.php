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
 * Get supported currencies with detailed formatting info
 *
 * @return array Array of currency data
 */
function seventh_trad_get_supported_currencies() {
    $currencies = array(
        'USD' => array(
            'name' => __('United States Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'AUD' => array(
            'name' => __('Australian Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'BRL' => array(
            'name' => __('Brazilian Real', '7th-traditioner'),
            'symbol' => 'R$',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'R$ X.XX'
        ),
        'CAD' => array(
            'name' => __('Canadian Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'CNY' => array(
            'name' => __('Chinese Renminbi', '7th-traditioner'),
            'symbol' => '¥',
            'position' => 'before',
            'decimals' => 2,
            'format' => '¥X.XX'
        ),
        'CZK' => array(
            'name' => __('Czech Koruna', '7th-traditioner'),
            'symbol' => 'Kč',
            'position' => 'after',
            'decimals' => 2,
            'format' => 'X.XX Kč'
        ),
        'DKK' => array(
            'name' => __('Danish Krone', '7th-traditioner'),
            'symbol' => 'kr.',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'kr. X.XX'
        ),
        'EUR' => array(
            'name' => __('Euro', '7th-traditioner'),
            'symbol' => '€',
            'position' => 'before',
            'decimals' => 2,
            'format' => '€X.XX'
        ),
        'GBP' => array(
            'name' => __('Pound Sterling', '7th-traditioner'),
            'symbol' => '£',
            'position' => 'before',
            'decimals' => 2,
            'format' => '£X.XX'
        ),
        'HKD' => array(
            'name' => __('Hong Kong Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'HUF' => array(
            'name' => __('Hungarian Forint', '7th-traditioner'),
            'symbol' => 'Ft',
            'position' => 'after',
            'decimals' => 0,
            'format' => 'X Ft'
        ),
        'ILS' => array(
            'name' => __('Israeli New Shekel', '7th-traditioner'),
            'symbol' => '₪',
            'position' => 'before',
            'decimals' => 2,
            'format' => '₪X.XX'
        ),
        'JPY' => array(
            'name' => __('Japanese Yen', '7th-traditioner'),
            'symbol' => '¥',
            'position' => 'before',
            'decimals' => 0,
            'format' => '¥X'
        ),
        'MYR' => array(
            'name' => __('Malaysian Ringgit', '7th-traditioner'),
            'symbol' => 'RM',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'RM X.XX'
        ),
        'MXN' => array(
            'name' => __('Mexican Peso', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'NOK' => array(
            'name' => __('Norwegian Krone', '7th-traditioner'),
            'symbol' => 'kr',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'kr X.XX'
        ),
        'NZD' => array(
            'name' => __('New Zealand Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'PHP' => array(
            'name' => __('Philippine Peso', '7th-traditioner'),
            'symbol' => '₱',
            'position' => 'before',
            'decimals' => 2,
            'format' => '₱X.XX'
        ),
        'PLN' => array(
            'name' => __('Polish Złoty', '7th-traditioner'),
            'symbol' => 'zł',
            'position' => 'after',
            'decimals' => 2,
            'format' => 'X.XX zł'
        ),
        'SEK' => array(
            'name' => __('Swedish Krona', '7th-traditioner'),
            'symbol' => 'kr',
            'position' => 'after',
            'decimals' => 2,
            'format' => 'X.XX kr'
        ),
        'SGD' => array(
            'name' => __('Singapore Dollar', '7th-traditioner'),
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'format' => '$X.XX'
        ),
        'CHF' => array(
            'name' => __('Swiss Franc', '7th-traditioner'),
            'symbol' => 'CHF',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'CHF X.XX'
        ),
        'THB' => array(
            'name' => __('Thai Baht', '7th-traditioner'),
            'symbol' => '฿',
            'position' => 'before',
            'decimals' => 2,
            'format' => '฿X.XX'
        ),
        'TWD' => array(
            'name' => __('New Taiwan Dollar', '7th-traditioner'),
            'symbol' => 'NT$',
            'position' => 'before',
            'decimals' => 2,
            'format' => 'NT$X.XX'
        ),
    );

    // Sort by currency name alphabetically
    uasort($currencies, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    return $currencies;
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
    if (!function_exists('tsml_get_meetings')) {
        return array();
    }

    // Get all meetings and extract unique groups
    $meetings = tsml_get_meetings();
    $groups = array();

    foreach ($meetings as $meeting) {
        if (!empty($meeting['group_id']) && !empty($meeting['group'])) {
            $groups[$meeting['group_id']] = $meeting['group'];
        }
    }

    // Remove duplicates and sort alphabetically
    $groups = array_unique($groups);
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
        'member_phone' => sanitize_text_field($data['phone'] ?? ''),
        'contribution_type' => sanitize_text_field($data['contributor_type'] ?? 'individual'),
        'meeting_day' => sanitize_text_field($data['meeting_day'] ?? ''),
        'group_name' => sanitize_text_field($data['meeting_name'] ?? $data['group_name'] ?? ''),
        'group_id' => absint($data['group_id'] ?? 0),
        'amount' => floatval($data['amount'] ?? 0),
        'currency' => sanitize_text_field($data['currency'] ?? 'USD'),
        'paypal_status' => sanitize_text_field($data['paypal_status'] ?? ''),
        'custom_notes' => sanitize_textarea_field($data['custom_notes'] ?? ''),
    );
}
