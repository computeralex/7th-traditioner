<?php
/**
 * Settings Handler
 *
 * Manages admin settings page
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Settings {

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('7th Traditioner', '7th-traditioner'),
            __('7th Traditioner', '7th-traditioner'),
            'manage_options',
            'seventh-traditioner',
            array(__CLASS__, 'render_settings_page'),
            'dashicons-heart',
            100
        );
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        // Save settings if form submitted
        if (isset($_POST['seventh_trad_save_settings'])) {
            self::save_settings();
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap seventh-trad-settings">
            <h1><?php echo esc_html__('7th Traditioner Settings', '7th-traditioner'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=seventh-traditioner&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner&tab=paypal" class="nav-tab <?php echo $active_tab === 'paypal' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('PayPal', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner&tab=recaptcha" class="nav-tab <?php echo $active_tab === 'recaptcha' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('reCAPTCHA', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Email', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner&tab=contributions" class="nav-tab <?php echo $active_tab === 'contributions' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Contributions', '7th-traditioner'); ?>
                </a>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('seventh_trad_settings', 'seventh_trad_nonce'); ?>

                <?php
                switch ($active_tab) {
                    case 'paypal':
                        self::render_paypal_tab();
                        break;
                    case 'recaptcha':
                        self::render_recaptcha_tab();
                        break;
                    case 'email':
                        self::render_email_tab();
                        break;
                    case 'contributions':
                        self::render_contributions_tab();
                        break;
                    default:
                        self::render_general_tab();
                        break;
                }
                ?>

                <?php if ($active_tab !== 'contributions') : ?>
                    <p class="submit">
                        <input type="submit" name="seventh_trad_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', '7th-traditioner'); ?>" />
                    </p>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render General tab
     */
    private static function render_general_tab() {
        $service_body_name = get_option('seventh_trad_service_body_name', get_bloginfo('name'));
        $default_currency = get_option('seventh_trad_default_currency', 'USD');
        $currencies = seventh_trad_get_supported_currencies();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="service_body_name"><?php esc_html_e('Fellowship / Service Body Name', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="service_body_name" name="service_body_name" value="<?php echo esc_attr($service_body_name); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('This name appears in emails and on the contribution form', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_currency"><?php esc_html_e('Default Currency', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <select id="default_currency" name="default_currency">
                        <?php foreach ($currencies as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($default_currency, $code); ?>>
                                <?php echo esc_html($code . ' - ' . $name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('The default currency pre-selected on the contribution form', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Shortcode Usage', '7th-traditioner'); ?></h2>
        <p><?php esc_html_e('Add the contribution form to any page or post using this shortcode:', '7th-traditioner'); ?></p>
        <code style="display: block; padding: 15px; background: #f5f5f5; border-radius: 4px; margin: 10px 0;">[seventh_traditioner]</code>

        <p><?php esc_html_e('You can also customize the title and description:', '7th-traditioner'); ?></p>
        <code style="display: block; padding: 15px; background: #f5f5f5; border-radius: 4px; margin: 10px 0;">[seventh_traditioner title="Support Our Fellowship" description="Your contribution helps keep our meetings running."]</code>
        <?php
    }

    /**
     * Render PayPal tab
     */
    private static function render_paypal_tab() {
        $paypal_mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        $paypal_client_id = get_option('seventh_trad_paypal_client_id');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="paypal_mode"><?php esc_html_e('PayPal Mode', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <select id="paypal_mode" name="paypal_mode">
                        <option value="sandbox" <?php selected($paypal_mode, 'sandbox'); ?>><?php esc_html_e('Sandbox (Testing)', '7th-traditioner'); ?></option>
                        <option value="live" <?php selected($paypal_mode, 'live'); ?>><?php esc_html_e('Live (Production)', '7th-traditioner'); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Use "Sandbox" for testing, "Live" when ready to accept real contributions', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="paypal_client_id"><?php esc_html_e('PayPal Client ID', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo esc_attr($paypal_client_id); ?>" class="large-text code" />
                    <p class="description">
                        <?php
                        printf(
                            /* translators: 1: opening link tag, 2: closing link tag */
                            esc_html__('Get your Client ID from your %1$sPayPal Developer Dashboard%2$s', '7th-traditioner'),
                            '<a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener">',
                            '</a>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="notice notice-info inline">
            <p>
                <strong><?php esc_html_e('PayPal Setup Instructions:', '7th-traditioner'); ?></strong>
            </p>
            <ol>
                <li><?php esc_html_e('Create a PayPal Business account if you don\'t have one', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Log in to the PayPal Developer Dashboard', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Create a new app or use an existing one', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Copy your Client ID and paste it above', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('For testing, use Sandbox credentials. For production, use Live credentials', '7th-traditioner'); ?></li>
            </ol>
        </div>

        <div class="notice notice-warning inline">
            <p>
                <strong><?php esc_html_e('Security Note:', '7th-traditioner'); ?></strong>
                <?php esc_html_e('Your Client ID is safe to use publicly. Never share your Secret Key with anyone or store it in your database.', '7th-traditioner'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render reCAPTCHA tab
     */
    private static function render_recaptcha_tab() {
        $recaptcha_site_key = get_option('seventh_trad_recaptcha_site_key');
        $recaptcha_secret_key = get_option('seventh_trad_recaptcha_secret_key');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="recaptcha_site_key"><?php esc_html_e('reCAPTCHA Site Key', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($recaptcha_site_key); ?>" class="large-text code" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="recaptcha_secret_key"><?php esc_html_e('reCAPTCHA Secret Key', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($recaptcha_secret_key); ?>" class="large-text code" />
                </td>
            </tr>
        </table>

        <div class="notice notice-info inline">
            <p>
                <strong><?php esc_html_e('reCAPTCHA v3 Setup:', '7th-traditioner'); ?></strong>
            </p>
            <ol>
                <li>
                    <?php
                    printf(
                        /* translators: 1: opening link tag, 2: closing link tag */
                        esc_html__('Visit the %1$sGoogle reCAPTCHA Admin Console%2$s', '7th-traditioner'),
                        '<a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">',
                        '</a>'
                    );
                    ?>
                </li>
                <li><?php esc_html_e('Register a new site', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Select "reCAPTCHA v3"', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Add your domain(s)', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Copy the Site Key and Secret Key and paste them above', '7th-traditioner'); ?></li>
            </ol>
        </div>

        <div class="notice notice-info inline">
            <p>
                <strong><?php esc_html_e('Why reCAPTCHA?', '7th-traditioner'); ?></strong>
                <?php esc_html_e('reCAPTCHA v3 protects your contribution form from card testing attacks and spam without requiring users to solve challenges. It runs invisibly in the background.', '7th-traditioner'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render Email tab
     */
    private static function render_email_tab() {
        ?>
        <p><?php esc_html_e('Email receipts are automatically sent to contributors after a successful contribution.', '7th-traditioner'); ?></p>

        <div class="notice notice-info inline">
            <p>
                <strong><?php esc_html_e('Email Configuration:', '7th-traditioner'); ?></strong>
            </p>
            <ul>
                <li><?php esc_html_e('Emails are sent using WordPress\'s built-in wp_mail() function', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('The "From" address is your site\'s admin email', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('To improve deliverability, consider using an SMTP plugin like WP Mail SMTP', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Receipts include the contribution amount, group name, transaction ID, and 7th Tradition message', '7th-traditioner'); ?></li>
            </ul>
        </div>

        <h3><?php esc_html_e('Test Email', '7th-traditioner'); ?></h3>
        <p><?php esc_html_e('Send a test receipt email to verify your email configuration:', '7th-traditioner'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="test_email"><?php esc_html_e('Send Test Email To', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="email" id="test_email" name="test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" />
                    <button type="button" id="send-test-email" class="button"><?php esc_html_e('Send Test Email', '7th-traditioner'); ?></button>
                    <div id="test-email-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('#send-test-email').on('click', function() {
                var email = $('#test_email').val();
                var button = $(this);
                var result = $('#test-email-result');

                button.prop('disabled', true).text('<?php esc_html_e('Sending...', '7th-traditioner'); ?>');
                result.html('');

                $.post(ajaxurl, {
                    action: 'seventh_trad_send_test_email',
                    email: email,
                    nonce: '<?php echo wp_create_nonce('seventh_trad_test_email'); ?>'
                }, function(response) {
                    if (response.success) {
                        result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    } else {
                        result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('<?php esc_html_e('Send Test Email', '7th-traditioner'); ?>');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render Contributions tab
     */
    private static function render_contributions_tab() {
        $contributions = Seventh_Trad_Database::get_contributions(array('limit' => 50));
        ?>
        <h2><?php esc_html_e('Recent Contributions', '7th-traditioner'); ?></h2>

        <?php if (empty($contributions)) : ?>
            <p><?php esc_html_e('No contributions recorded yet.', '7th-traditioner'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', '7th-traditioner'); ?></th>
                        <th><?php esc_html_e('Member', '7th-traditioner'); ?></th>
                        <th><?php esc_html_e('Group', '7th-traditioner'); ?></th>
                        <th><?php esc_html_e('Amount', '7th-traditioner'); ?></th>
                        <th><?php esc_html_e('Transaction ID', '7th-traditioner'); ?></th>
                        <th><?php esc_html_e('Status', '7th-traditioner'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contributions as $contribution) : ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($contribution->contribution_date))); ?></td>
                            <td>
                                <?php echo esc_html($contribution->member_name ?: __('Anonymous', '7th-traditioner')); ?>
                                <?php if ($contribution->member_email) : ?>
                                    <br><small><?php echo esc_html($contribution->member_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($contribution->group_name); ?></td>
                            <td><strong><?php echo esc_html(seventh_trad_format_amount($contribution->amount, $contribution->currency)); ?></strong></td>
                            <td><code><?php echo esc_html($contribution->transaction_id); ?></code></td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php echo esc_html($contribution->paypal_status); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }

    /**
     * Save settings
     */
    private static function save_settings() {
        // Verify nonce
        if (!isset($_POST['seventh_trad_nonce']) || !wp_verify_nonce($_POST['seventh_trad_nonce'], 'seventh_trad_settings')) {
            wp_die(__('Security check failed', '7th-traditioner'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page', '7th-traditioner'));
        }

        // Save settings
        if (isset($_POST['service_body_name'])) {
            update_option('seventh_trad_service_body_name', sanitize_text_field($_POST['service_body_name']));
        }

        if (isset($_POST['default_currency'])) {
            update_option('seventh_trad_default_currency', sanitize_text_field($_POST['default_currency']));
        }

        if (isset($_POST['paypal_mode'])) {
            update_option('seventh_trad_paypal_mode', sanitize_text_field($_POST['paypal_mode']));
        }

        if (isset($_POST['paypal_client_id'])) {
            update_option('seventh_trad_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
        }

        if (isset($_POST['recaptcha_site_key'])) {
            update_option('seventh_trad_recaptcha_site_key', sanitize_text_field($_POST['recaptcha_site_key']));
        }

        if (isset($_POST['recaptcha_secret_key'])) {
            update_option('seventh_trad_recaptcha_secret_key', sanitize_text_field($_POST['recaptcha_secret_key']));
        }

        // Show success message
        add_settings_error(
            'seventh_trad_messages',
            'seventh_trad_message',
            __('Settings saved successfully', '7th-traditioner'),
            'success'
        );

        settings_errors('seventh_trad_messages');
    }
}
