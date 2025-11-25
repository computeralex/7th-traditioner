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
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-paypal-handler.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-email-handler.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-exchange-rates.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-settings.php';
        require_once SEVENTH_TRAD_PLUGIN_DIR . 'includes/class-contributions.php';
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

        add_action('wp_ajax_seventh_trad_get_meetings_by_day', array($this, 'ajax_get_meetings_by_day'));
        add_action('wp_ajax_nopriv_seventh_trad_get_meetings_by_day', array($this, 'ajax_get_meetings_by_day'));

        add_action('wp_ajax_seventh_trad_create_paypal_order', array($this, 'ajax_create_paypal_order'));
        add_action('wp_ajax_nopriv_seventh_trad_create_paypal_order', array($this, 'ajax_create_paypal_order'));

        add_action('wp_ajax_seventh_trad_send_test_email', array($this, 'ajax_send_test_email'));

        add_action('wp_ajax_seventh_trad_get_contribution_details', array($this, 'ajax_get_contribution_details'));

        add_action('wp_ajax_seventh_trad_get_exchange_rate', array('Seventh_Trad_Exchange_Rates', 'ajax_get_rate'));
        add_action('wp_ajax_nopriv_seventh_trad_get_exchange_rate', array('Seventh_Trad_Exchange_Rates', 'ajax_get_rate'));

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

        // PayPal SDK (client-side only, NO SECRETS NEEDED)
        $paypal_mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        $paypal_client_id = ($paypal_mode === 'live')
            ? get_option('seventh_trad_paypal_live_client_id')
            : get_option('seventh_trad_paypal_sandbox_client_id');

        if ($paypal_client_id) {
            // Load PayPal SDK WITHOUT currency parameter
            // Currency will be specified dynamically in createOrder() based on user selection
            $sdk_url = 'https://www.paypal.com/sdk/js?client-id=' . esc_attr($paypal_client_id)
                     . '&disable-funding=paylater';

            wp_enqueue_script(
                'paypal-sdk',
                $sdk_url,
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
            'paypal_client_id' => $paypal_client_id,
            'paypal_mode' => get_option('seventh_trad_paypal_mode', 'sandbox'),
            'minAmount' => get_option('seventh_trad_min_contribution_amount', ''),
            'maxAmount' => get_option('seventh_trad_max_contribution_amount', ''),
            'roundingMethod' => get_option('seventh_trad_amount_rounding_method', 'smart'),
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
        // Only on our plugin pages
        if ('toplevel_page_seventh-traditioner' !== $hook && '7th-traditioner_page_seventh-traditioner-settings' !== $hook) {
            return;
        }

        // Enqueue jQuery UI dialog for modal popups
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_enqueue_style(
            'seventh-trad-admin',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SEVENTH_TRAD_VERSION
        );

        wp_enqueue_script(
            'seventh-trad-admin',
            SEVENTH_TRAD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-dialog'),
            SEVENTH_TRAD_VERSION,
            true
        );
    }

    /**
     * AJAX handler for getting meetings by day
     */
    public function ajax_get_meetings_by_day() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seventh_trad_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        $day = isset($_POST['day']) ? intval($_POST['day']) : -1;

        if ($day < 0 || $day > 6) {
            wp_send_json_error(array('message' => 'Invalid day'));
        }

        // Get meetings for this day
        $meetings = seventh_trad_get_meetings_by_day($day);

        if (empty($meetings)) {
            wp_send_json_success(array());
        }

        // Format meetings for dropdown
        $formatted_meetings = array();
        foreach ($meetings as $meeting) {
            $formatted_meetings[] = array(
                'id' => $meeting['id'],
                'name' => $meeting['name'],
                'time' => isset($meeting['time']) ? $meeting['time'] : '',
                'time_formatted' => isset($meeting['time_formatted']) ? $meeting['time_formatted'] : '',
                'group' => isset($meeting['group']) ? $meeting['group'] : '',
                'group_id' => isset($meeting['group_id']) ? $meeting['group_id'] : '',
            );
        }

        wp_send_json_success($formatted_meetings);
    }

    /**
     * AJAX handler for creating PayPal order
     */
    public function ajax_create_paypal_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seventh_trad_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Validate required fields
        if (empty($_POST['amount']) || empty($_POST['currency'])) {
            wp_send_json_error(array('message' => 'Amount and currency are required'));
        }

        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '7th Tradition Contribution';

        // Validate amount
        if ($amount <= 0) {
            wp_send_json_error(array('message' => 'Invalid amount'));
        }

        // Validate currency
        $supported_currencies = seventh_trad_get_supported_currencies();
        if (!isset($supported_currencies[$currency])) {
            wp_send_json_error(array('message' => 'Invalid currency'));
        }

        // Get return and cancel URLs
        $return_url = home_url('/contribution-success');
        $cancel_url = home_url('/contribution-cancelled');

        // If we have a referrer, use it for better UX
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referrer = esc_url_raw($_SERVER['HTTP_REFERER']);
            $return_url = add_query_arg('contribution', 'success', $referrer);
            $cancel_url = add_query_arg('contribution', 'cancelled', $referrer);
        }

        // Prepare order data
        $order_data = array(
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'return_url' => $return_url,
            'cancel_url' => $cancel_url,
        );

        // Create PayPal order
        $result = Seventh_Trad_PayPal_Handler::create_order($order_data);

        if (is_wp_error($result)) {
            error_log('7th Traditioner PayPal Order Creation Error: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => 'Failed to create PayPal order: ' . $result->get_error_message()
            ));
        }

        // Return order details
        wp_send_json_success(array(
            'order_id' => $result['order_id'],
            'approve_url' => $result['approve_url'],
        ));
    }

    /**
     * AJAX handler for sending test email
     */
    public function ajax_send_test_email() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seventh_trad_test_email')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        // Get email address
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }

        // Create a mock contribution for testing
        $fellowship_name = seventh_trad_get_fellowship_name();
        $subject = sprintf(
            __('Test Receipt - %s', '7th-traditioner'),
            $fellowship_name
        );

        // Build test email content
        $message = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html__('Test Contribution Receipt', '7th-traditioner') . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background-color: #4a5568; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                ' . esc_html__('Test Contribution Receipt', '7th-traditioner') . '
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #2d3748; font-size: 16px; line-height: 1.6;">
                                <strong>' . esc_html__('This is a test email.', '7th-traditioner') . '</strong>
                            </p>

                            <p style="margin: 0 0 30px; color: #2d3748; font-size: 16px; line-height: 1.6;">
                                If you receive this email, your email configuration for <strong>' . esc_html($fellowship_name) . '</strong> is working correctly!
                            </p>

                            <!-- Contribution Details -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f7fafc; border-radius: 6px; padding: 20px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 10px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                    ' . esc_html__('Amount:', '7th-traditioner') . '
                                                </td>
                                                <td style="color: #2d3748; font-size: 18px; font-weight: bold; text-align: right; padding: 8px 0;">
                                                    $25.00
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                    ' . esc_html__('Group:', '7th-traditioner') . '
                                                </td>
                                                <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0;">
                                                    ' . esc_html__('Test Group', '7th-traditioner') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                    ' . esc_html__('Date:', '7th-traditioner') . '
                                                </td>
                                                <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0;">
                                                    ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format')) . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                    ' . esc_html__('Transaction ID:', '7th-traditioner') . '
                                                </td>
                                                <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0; font-family: monospace;">
                                                    TEST-' . time() . '
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- 7th Tradition Note -->
                            <div style="background-color: #edf2f7; border-left: 4px solid #4299e1; padding: 16px; margin-bottom: 30px; border-radius: 4px;">
                                <p style="margin: 0; color: #2d3748; font-size: 14px; line-height: 1.6;">
                                    <strong>' . esc_html__('7th Tradition:', '7th-traditioner') . '</strong>
                                    ' . esc_html__('Every group ought to be fully self-supporting, declining outside contributions.', '7th-traditioner') . '
                                </p>
                            </div>

                            <p style="margin: 0; color: #718096; font-size: 14px; line-height: 1.6;">
                                ' . esc_html__('This is a test email to verify your email configuration is working correctly.', '7th-traditioner') . '
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f7fafc; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0; color: #718096; font-size: 14px;">
                                <a href="' . esc_url(home_url('/')) . '" style="color: #4299e1; text-decoration: none;">
                                    ' . esc_html($fellowship_name) . '
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fellowship_name . ' <' . get_option('admin_email') . '>',
        );

        // Send email
        $sent = wp_mail($email, $subject, $message, $headers);

        if ($sent) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Test email sent successfully to %s!', '7th-traditioner'),
                    $email
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to send test email. Please check your email server configuration.', '7th-traditioner')
            ));
        }
    }

    /**
     * AJAX handler for getting contribution details
     */
    public function ajax_get_contribution_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seventh_trad_contribution_details')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        // Get contribution ID
        $contribution_id = isset($_POST['contribution_id']) ? intval($_POST['contribution_id']) : 0;
        if (!$contribution_id) {
            wp_send_json_error(array('message' => 'Invalid contribution ID'));
        }

        // Get contribution
        $contribution = Seventh_Trad_Database::get_contribution($contribution_id);
        if (!$contribution) {
            wp_send_json_error(array('message' => 'Contribution not found'));
        }

        // Build HTML output
        ob_start();
        ?>
        <table class="widefat">
            <tr>
                <th><?php esc_html_e('Transaction ID:', '7th-traditioner'); ?></th>
                <td><code><?php echo esc_html($contribution->transaction_id); ?></code></td>
            </tr>
            <tr>
                <th><?php esc_html_e('PayPal Order ID:', '7th-traditioner'); ?></th>
                <td><code><?php echo esc_html($contribution->paypal_order_id); ?></code></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Name:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->member_name); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Email:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->member_email); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Phone:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->member_phone ?: '—'); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Contribution Type:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->contribution_type === 'group' ? __('Group', '7th-traditioner') : __('Individual', '7th-traditioner')); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Group:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->group_name ?: '—'); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Amount:', '7th-traditioner'); ?></th>
                <td><strong><?php echo esc_html(seventh_trad_format_amount($contribution->amount, $contribution->currency)); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Currency:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->currency); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Date:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contribution->contribution_date))); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('PayPal Status:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->paypal_status); ?></td>
            </tr>
            <?php if ($contribution->custom_notes) : ?>
            <tr>
                <th><?php esc_html_e('Notes:', '7th-traditioner'); ?></th>
                <td><?php echo esc_html($contribution->custom_notes); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><?php esc_html_e('IP Address:', '7th-traditioner'); ?></th>
                <td><code><?php echo esc_html($contribution->ip_address); ?></code></td>
            </tr>
        </table>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
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
