<?php
/**
 * Shortcodes Handler
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Shortcodes {

    /**
     * Register shortcodes
     */
    public static function register_shortcodes() {
        add_shortcode('seventh_traditioner', array(__CLASS__, 'contribution_form_shortcode'));
    }

    /**
     * Contribution form shortcode
     *
     * Usage: [seventh_traditioner]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function contribution_form_shortcode($atts) {
        // Check if TSML is active
        if (!function_exists('tsml_get_groups')) {
            return '<div class="seventh-trad-error">' .
                   __('7th Traditioner requires the 12 Step Meeting List plugin to be installed and activated.', '7th-traditioner') .
                   '</div>';
        }

        // Check if PayPal is configured
        $paypal_mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        $paypal_client_id = ($paypal_mode === 'live')
            ? get_option('seventh_trad_paypal_live_client_id')
            : get_option('seventh_trad_paypal_sandbox_client_id');

        if (empty($paypal_client_id)) {
            return '<div class="seventh-trad-error">' .
                   __('PayPal is not configured. Please configure PayPal in the plugin settings.', '7th-traditioner') .
                   '</div>';
        }

        $atts = shortcode_atts(array(
            'title' => '',
            'description' => '',
        ), $atts, 'seventh_traditioner');

        ob_start();
        include SEVENTH_TRAD_PLUGIN_DIR . 'templates/contribution-form.php';
        return ob_get_clean();
    }
}
