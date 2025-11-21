<?php
/**
 * Plugin Name: 7th Traditioner
 * Plugin URI: https://github.com/computeralex/7th-traditioner
 * Description: A 7th Tradition contribution system for 12-step fellowships. Integrates with TSML (12 Step Meeting List) and PayPal for secure, PCI-compliant contributions.
 * Version: 1.0.0
 * Author: Alex M and Claude (Code)
 * Author URI: https://github.com/computeralex/7th-traditioner
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 7th-traditioner
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SEVENTH_TRAD_VERSION', '1.0.0');
define('SEVENTH_TRAD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEVENTH_TRAD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEVENTH_TRAD_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class Seventh_Traditioner {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core functionality
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-database.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-contribution-handler.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-email-handler.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-settings.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/helper-functions.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(SEVENTH_TRAD_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SEVENTH_TRAD_PLUGIN_FILE, array($this, 'deactivate'));

        // Init hook
        add_action('init', array($this, 'init'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_seventh_trad_save_contribution', array('Seventh_Trad_Contribution_Handler', 'save_contribution'));
        add_action('wp_ajax_nopriv_seventh_trad_save_contribution', array('Seventh_Trad_Contribution_Handler', 'save_contribution'));

        // Shortcodes
        add_action('init', array('Seventh_Trad_Shortcodes', 'register_shortcodes'));

        // Admin menu
        add_action('admin_menu', array('Seventh_Trad_Settings', 'add_admin_menu'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database table
        Seventh_Trad_Database::create_table();

        // Set default options
        add_option('seventh_trad_version', SEVENTH_TRAD_VERSION);
        add_option('seventh_trad_service_body_name', get_bloginfo('name'));
        add_option('seventh_trad_default_currency', 'USD');
        add_option('seventh_trad_paypal_mode', 'sandbox'); // sandbox or live

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('7th-traditioner', false, dirname(plugin_basename(SEVENTH_TRAD_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on pages with our shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'seventh_traditioner')) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'seventh-trad-frontend',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SEVENTH_TRAD_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'seventh-trad-frontend',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SEVENTH_TRAD_VERSION,
            true
        );

        // PayPal SDK
        $paypal_client_id = get_option('seventh_trad_paypal_client_id');
        $currencies = seventh_trad_get_supported_currencies();
        $currency_codes = implode(',', array_keys($currencies));

        if ($paypal_client_id) {
            wp_enqueue_script(
                'paypal-sdk',
                'https://www.paypal.com/sdk/js?client-id=' . esc_attr($paypal_client_id) . '&currency=' . esc_attr($currency_codes),
                array(),
                null,
                true
            );
        }

        // reCAPTCHA v3
        $recaptcha_site_key = get_option('seventh_trad_recaptcha_site_key');
        if ($recaptcha_site_key) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr($recaptcha_site_key),
                array(),
                null,
                true
            );
        }

        // Localize script with data
        wp_localize_script('seventh-trad-frontend', 'seventhTradData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seventh_trad_nonce'),
            'recaptcha_site_key' => $recaptcha_site_key,
            'strings' => array(
                'processing' => __('Processing contribution...', '7th-traditioner'),
                'success' => __('Thank you for your contribution!', '7th-traditioner'),
                'error' => __('There was an error processing your contribution. Please try again.', '7th-traditioner'),
                'select_group' => __('Please select a group', '7th-traditioner'),
                'enter_amount' => __('Please enter a contribution amount', '7th-traditioner'),
            )
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on our settings page
        if ('toplevel_page_seventh-traditioner' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'seventh-trad-admin',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SEVENTH_TRAD_VERSION
        );

        wp_enqueue_script(
            'seventh-trad-admin',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SEVENTH_TRAD_VERSION,
            true
        );
    }
}

/**
 * Initialize the plugin
 */
function seventh_traditioner() {
    return Seventh_Traditioner::get_instance();
}

// Start the plugin
seventh_traditioner();
