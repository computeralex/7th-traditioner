<?php
/**
 * Exchange Rates Handler
 *
 * Fetches and caches currency exchange rates using fawazahmed0/currency-api
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Exchange_Rates {

    /**
     * API endpoint for exchange rates
     */
    const API_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json';

    /**
     * Cache duration (24 hours)
     */
    const CACHE_DURATION = 86400; // 24 hours in seconds

    /**
     * Get exchange rate from USD to target currency
     *
     * @param string $to_currency Target currency code (e.g., 'eur', 'gbp')
     * @return float|false Exchange rate or false on failure
     */
    public static function get_rate($to_currency) {
        // Convert to lowercase for API
        $to_currency = strtolower($to_currency);

        // USD to USD is always 1
        if ($to_currency === 'usd') {
            return 1.0;
        }

        // Try to get from cache
        $rates = self::get_cached_rates();

        if ($rates && isset($rates[$to_currency])) {
            return floatval($rates[$to_currency]);
        }

        // Cache miss - fetch new rates
        $rates = self::fetch_rates();

        if ($rates && isset($rates[$to_currency])) {
            return floatval($rates[$to_currency]);
        }

        return false;
    }

    /**
     * Convert amount from USD to target currency
     *
     * @param float $amount Amount in USD
     * @param string $to_currency Target currency code
     * @param string $rounding_method 'simple' or 'smart'
     * @return float|false Converted amount or false on failure
     */
    public static function convert_amount($amount, $to_currency, $rounding_method = 'simple') {
        $rate = self::get_rate($to_currency);

        if ($rate === false) {
            return false;
        }

        $converted = $amount * $rate;

        // Apply rounding
        if ($rounding_method === 'smart') {
            return self::smart_round($converted, strtoupper($to_currency));
        }

        // Simple rounding to currency's decimal places
        $decimals = seventh_trad_get_currency_decimals(strtoupper($to_currency));
        return round($converted, $decimals);
    }

    /**
     * Smart rounding based on currency conventions
     *
     * @param float $amount Amount to round
     * @param string $currency Currency code
     * @return float Rounded amount
     */
    private static function smart_round($amount, $currency) {
        switch ($currency) {
            case 'JPY': // Japanese Yen - round to nearest 50
            case 'KRW': // Korean Won - round to nearest 50
                return round($amount / 50) * 50;

            case 'INR': // Indian Rupee - round to nearest 5
            case 'THB': // Thai Baht - round to nearest 5
                return round($amount / 5) * 5;

            case 'VND': // Vietnamese Dong - round to nearest 1000
                return round($amount / 1000) * 1000;

            case 'CLP': // Chilean Peso - round to nearest 100
            case 'IDR': // Indonesian Rupiah - round to nearest 100
                return round($amount / 100) * 100;

            default:
                // Most currencies - round to whole number
                $decimals = seventh_trad_get_currency_decimals($currency);
                if ($decimals === 0) {
                    return round($amount);
                }
                // For decimal currencies, round to nearest whole number for cleaner UX
                return round($amount);
        }
    }

    /**
     * Fetch rates from API
     *
     * @return array|false Array of rates or false on failure
     */
    private static function fetch_rates() {
        $response = wp_remote_get(self::API_URL, array(
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            error_log('7th Traditioner: Failed to fetch exchange rates - ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['usd'])) {
            error_log('7th Traditioner: Invalid exchange rate response');
            return false;
        }

        // Cache the rates
        set_transient('seventh_trad_exchange_rates', $data['usd'], self::CACHE_DURATION);

        return $data['usd'];
    }

    /**
     * Get cached rates
     *
     * @return array|false Cached rates or false if not cached
     */
    private static function get_cached_rates() {
        return get_transient('seventh_trad_exchange_rates');
    }

    /**
     * Clear rate cache (useful for testing)
     */
    public static function clear_cache() {
        delete_transient('seventh_trad_exchange_rates');
    }

    /**
     * Get rates for AJAX endpoint (used by frontend)
     *
     * @return void Outputs JSON
     */
    public static function ajax_get_rate() {
        $currency = isset($_GET['currency']) ? sanitize_text_field($_GET['currency']) : '';

        if (empty($currency)) {
            wp_send_json_error(array('message' => 'Currency required'));
        }

        $rate = self::get_rate(strtolower($currency));

        if ($rate === false) {
            wp_send_json_error(array('message' => 'Failed to get exchange rate'));
        }

        wp_send_json_success(array(
            'rate' => $rate,
            'currency' => strtoupper($currency)
        ));
    }
}
