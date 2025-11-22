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
        // Main menu item
        add_menu_page(
            __('7th Traditioner', '7th-traditioner'),
            __('7th Traditioner', '7th-traditioner'),
            'manage_options',
            'seventh-traditioner',
            array('Seventh_Trad_Contributions', 'render_page'),
            'dashicons-heart',
            100
        );

        // Contributions submenu (same as parent - will replace parent in menu)
        add_submenu_page(
            'seventh-traditioner',
            __('Contributions', '7th-traditioner'),
            __('Contributions', '7th-traditioner'),
            'manage_options',
            'seventh-traditioner',
            array('Seventh_Trad_Contributions', 'render_page')
        );

        // Settings submenu
        add_submenu_page(
            'seventh-traditioner',
            __('Settings', '7th-traditioner'),
            __('Settings', '7th-traditioner'),
            'manage_options',
            'seventh-traditioner-settings',
            array(__CLASS__, 'render_settings_page')
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
                <a href="?page=seventh-traditioner-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner-settings&tab=paypal" class="nav-tab <?php echo $active_tab === 'paypal' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('PayPal', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner-settings&tab=recaptcha" class="nav-tab <?php echo $active_tab === 'recaptcha' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('reCAPTCHA', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner-settings&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Email', '7th-traditioner'); ?>
                </a>
                <a href="?page=seventh-traditioner-settings&tab=data" class="nav-tab <?php echo $active_tab === 'data' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Data', '7th-traditioner'); ?>
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
                    case 'data':
                        self::render_data_tab();
                        break;
                    default:
                        self::render_general_tab();
                        break;
                }
                ?>

                <?php if ($active_tab !== 'data') : ?>
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
        $show_group_id = get_option('seventh_trad_show_group_id', true);
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
                    <label><?php esc_html_e('Currencies', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <?php
                    $enabled_currencies = get_option('seventh_trad_enabled_currencies', array_keys($currencies));
                    if (!is_array($enabled_currencies)) {
                        $enabled_currencies = array_keys($currencies);
                    }
                    ?>
                    <div style="margin-bottom: 10px;">
                        <button type="button" id="select-all-currencies" class="button"><?php esc_html_e('Enable All', '7th-traditioner'); ?></button>
                        <button type="button" id="deselect-all-currencies" class="button"><?php esc_html_e('Disable All', '7th-traditioner'); ?></button>
                    </div>
                    <div style="border: 1px solid #ddd; padding: 10px; background: #fff;">
                        <table class="widefat" style="border: none;">
                            <thead>
                                <tr>
                                    <th style="width: 60px; padding: 8px;"><?php esc_html_e('Enable', '7th-traditioner'); ?></th>
                                    <th style="width: 80px; padding: 8px;"><?php esc_html_e('Default', '7th-traditioner'); ?></th>
                                    <th style="padding: 8px;"><?php esc_html_e('Currency', '7th-traditioner'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currencies as $code => $currency_data) : ?>
                                    <tr>
                                        <td style="text-align: center; padding: 4px 8px;">
                                            <input type="checkbox" name="enabled_currencies[]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $enabled_currencies)); ?> class="currency-checkbox" id="currency_<?php echo esc_attr($code); ?>" />
                                        </td>
                                        <td style="text-align: center; padding: 4px 8px;">
                                            <input type="radio" name="default_currency" value="<?php echo esc_attr($code); ?>" <?php checked($default_currency, $code); ?> class="currency-default-radio" />
                                        </td>
                                        <td style="padding: 4px 8px;">
                                            <label for="currency_<?php echo esc_attr($code); ?>" style="margin: 0; font-weight: normal;">
                                                <strong><?php echo esc_html($code); ?></strong> - <?php echo esc_html($currency_data['name']); ?> (<?php echo esc_html($currency_data['symbol']); ?>)
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Enable currencies to make them available on the contribution form. Set one as default to pre-select it.', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="show_group_id"><?php esc_html_e('Show Group ID Field', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="show_group_id" name="show_group_id" value="1" <?php checked($show_group_id, true); ?> />
                        <?php esc_html_e('Display the Group ID field on the contribution form', '7th-traditioner'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Uncheck to hide the Group ID field if you don\'t need it', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="color_mode"><?php esc_html_e('Form Color Mode', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <?php $color_mode = get_option('seventh_trad_color_mode', 'auto'); ?>
                    <select id="color_mode" name="color_mode" class="regular-text">
                        <option value="auto" <?php selected($color_mode, 'auto'); ?>><?php esc_html_e('Auto (Follow System Preference)', '7th-traditioner'); ?></option>
                        <option value="light" <?php selected($color_mode, 'light'); ?>><?php esc_html_e('Light Mode', '7th-traditioner'); ?></option>
                        <option value="dark" <?php selected($color_mode, 'dark'); ?>><?php esc_html_e('Dark Mode', '7th-traditioner'); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Choose the color scheme for the contribution form. Auto will use the visitor\'s system preference.', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Shortcode Usage', '7th-traditioner'); ?></h2>
        <p><?php esc_html_e('Add the contribution form to any page or post using this shortcode:', '7th-traditioner'); ?></p>
        <code style="display: block; padding: 15px; background: #f5f5f5; border-radius: 4px; margin: 10px 0;">[seventh_traditioner]</code>

        <p><?php esc_html_e('You can also customize the title and description:', '7th-traditioner'); ?></p>
        <code style="display: block; padding: 15px; background: #f5f5f5; border-radius: 4px; margin: 10px 0;">[seventh_traditioner title="Support Our Fellowship" description="Contributions may be made by members of the fellowship, and are 100% voluntary"]</code>

        <script>
        jQuery(document).ready(function($) {
            // Select/Deselect all currencies
            $('#select-all-currencies').on('click', function(e) {
                e.preventDefault();
                $('.currency-checkbox').prop('checked', true);
            });

            $('#deselect-all-currencies').on('click', function(e) {
                e.preventDefault();
                $('.currency-checkbox').prop('checked', false);
            });

            // When a currency is set as default, automatically enable it
            $('.currency-default-radio').on('change', function() {
                var currencyCode = $(this).val();
                $('#currency_' + currencyCode).prop('checked', true);
            });

            // Warn if trying to disable the default currency
            $('.currency-checkbox').on('change', function() {
                var currencyCode = $(this).val();
                var isChecked = $(this).is(':checked');
                var isDefault = $('input[name="default_currency"]:checked').val() === currencyCode;

                if (!isChecked && isDefault) {
                    if (!confirm('<?php esc_html_e('This is the default currency. Are you sure you want to disable it? You should select a different default currency first.', '7th-traditioner'); ?>')) {
                        $(this).prop('checked', true);
                    }
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render PayPal tab
     */
    private static function render_paypal_tab() {
        $paypal_mode = get_option('seventh_trad_paypal_mode', 'sandbox');
        $paypal_sandbox_client_id = get_option('seventh_trad_paypal_sandbox_client_id');
        $paypal_live_client_id = get_option('seventh_trad_paypal_live_client_id');
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
                    <label for="paypal_sandbox_client_id"><?php esc_html_e('Sandbox Client ID', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="paypal_sandbox_client_id" name="paypal_sandbox_client_id" value="<?php echo esc_attr($paypal_sandbox_client_id); ?>" class="large-text code" />
                    <p class="description">
                        <?php esc_html_e('Your PayPal Sandbox Client ID for testing', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="paypal_live_client_id"><?php esc_html_e('Live Client ID', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="paypal_live_client_id" name="paypal_live_client_id" value="<?php echo esc_attr($paypal_live_client_id); ?>" class="large-text code" />
                    <p class="description">
                        <?php esc_html_e('Your PayPal Live Client ID for production', '7th-traditioner'); ?>
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
                <li>
                    <?php
                    printf(
                        /* translators: 1: opening link tag, 2: closing link tag */
                        esc_html__('Log in to the %1$sPayPal Developer Dashboard%2$s', '7th-traditioner'),
                        '<a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener">',
                        '</a>'
                    );
                    ?>
                </li>
                <li><?php esc_html_e('Create a new app (or use existing)', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Copy the Sandbox Client ID and paste above', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Switch to "Live" in PayPal Dashboard and copy the Live Client ID', '7th-traditioner'); ?></li>
                <li><?php esc_html_e('Test with Sandbox mode first, then switch to Live when ready', '7th-traditioner'); ?></li>
            </ol>
        </div>

        <div class="notice notice-success inline">
            <p>
                <strong><?php esc_html_e('Client-Side Only Integration:', '7th-traditioner'); ?></strong>
                <?php esc_html_e('This plugin uses PayPal\'s client-side JavaScript SDK with ONLY your Client ID (no secret keys required). Your Client ID is safe to use publicly and all transactions are processed securely by PayPal without storing sensitive payment data.', '7th-traditioner'); ?>
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
        $email_subject = get_option('seventh_trad_email_subject', 'Contribution Receipt');
        $email_title = get_option('seventh_trad_email_title', 'Contribution Receipt');
        $email_from_address = get_option('seventh_trad_email_from_address', get_option('admin_email'));
        $email_from_name = get_option('seventh_trad_email_from_name', seventh_trad_get_fellowship_name());
        ?>
        <p><?php esc_html_e('Email receipts are automatically sent to contributors after a successful contribution.', '7th-traditioner'); ?></p>

        <h3><?php esc_html_e('Email Customization', '7th-traditioner'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="email_from_name"><?php esc_html_e('From Name', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($email_from_name); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('The name that appears as the email sender.', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="email_from_address"><?php esc_html_e('From Email Address', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr($email_from_address); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('The email address that receipts will be sent from.', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="email_subject"><?php esc_html_e('Email Subject Line', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="email_subject" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('This appears in the recipient\'s inbox. Consider using discrete wording if needed.', '7th-traditioner'); ?>
                        <br>
                        <?php esc_html_e('Default: "Contribution Receipt"', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="email_title"><?php esc_html_e('Email Header Title', '7th-traditioner'); ?></label>
                </th>
                <td>
                    <input type="text" id="email_title" name="email_title" value="<?php echo esc_attr($email_title); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('This appears at the top of the email body. Can be the same as subject or different.', '7th-traditioner'); ?>
                        <br>
                        <?php esc_html_e('Default: "Contribution Receipt"', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="notice notice-info inline">
            <p>
                <?php esc_html_e('Receipts include the contribution amount, group name (for group contributions), transaction ID, and 7th Tradition message.', '7th-traditioner'); ?>
            </p>
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
            // Test email
            $('#send-test-email').on('click', function(e) {
                e.preventDefault();

                var email = $('#test_email').val();
                var button = $(this);
                var result = $('#test-email-result');

                if (!email) {
                    result.html('<div class="notice notice-error inline"><p><?php esc_html_e('Please enter an email address.', '7th-traditioner'); ?></p></div>');
                    return;
                }

                button.prop('disabled', true).text('<?php esc_html_e('Sending...', '7th-traditioner'); ?>');
                result.html('');

                console.log('Sending test email to:', email);
                console.log('AJAX URL:', ajaxurl);
                console.log('Nonce:', '<?php echo wp_create_nonce('seventh_trad_test_email'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'seventh_trad_send_test_email',
                        email: email,
                        nonce: '<?php echo wp_create_nonce('seventh_trad_test_email'); ?>'
                    },
                    success: function(response) {
                        console.log('Success response:', response);
                        if (response.success) {
                            result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            var errorMsg = response.data && response.data.message ? response.data.message : '<?php esc_html_e('Unknown error occurred.', '7th-traditioner'); ?>';
                            result.html('<div class="notice notice-error inline"><p>' + errorMsg + '</p></div>');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                        console.error('Response text:', jqXHR.responseText);
                        console.error('Status code:', jqXHR.status);
                        result.html('<div class="notice notice-error inline"><p><?php esc_html_e('Network error. Please check console for details.', '7th-traditioner'); ?></p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php esc_html_e('Send Test Email', '7th-traditioner'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render Data tab
     */
    private static function render_data_tab() {
        // Handle clear history
        if (isset($_POST['seventh_trad_clear_history'])) {
            check_admin_referer('seventh_trad_settings', 'seventh_trad_nonce');

            if (isset($_POST['seventh_trad_clear_confirm']) && $_POST['seventh_trad_clear_confirm'] === 'DELETE') {
                global $wpdb;
                $table_name = $wpdb->prefix . 'seventh_trad_contributions';
                $deleted = $wpdb->query("TRUNCATE TABLE $table_name");

                if ($deleted !== false) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All contribution records have been deleted.', '7th-traditioner') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to delete contribution records.', '7th-traditioner') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Confirmation text did not match. No data was deleted.', '7th-traditioner') . '</p></div>';
            }
        }

        $contribution_count = Seventh_Trad_Database::get_contributions_count(array());
        ?>
        <h2><?php esc_html_e('Contribution Records', '7th-traditioner'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e('Total Contributions', '7th-traditioner'); ?>
                </th>
                <td>
                    <strong><?php echo esc_html(number_format_i18n($contribution_count)); ?></strong>
                    <p class="description">
                        <?php esc_html_e('Total number of contribution records in the database.', '7th-traditioner'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h3 style="color: #dc3232;"><?php esc_html_e('Danger Zone', '7th-traditioner'); ?></h3>

        <div style="border: 2px solid #dc3232; padding: 20px; border-radius: 4px; background: #fff;">
            <h4><?php esc_html_e('Clear Contribution History', '7th-traditioner'); ?></h4>
            <p style="color: #dc3232; font-weight: bold;">
                <?php esc_html_e('WARNING: This will delete ALL contribution records! This action cannot be undone.', '7th-traditioner'); ?>
            </p>
            <p>
                <?php esc_html_e('To confirm deletion, type DELETE in the box below and click the button.', '7th-traditioner'); ?>
            </p>

            <form method="post" action="" onsubmit="return confirmDelete();">
                <?php wp_nonce_field('seventh_trad_settings', 'seventh_trad_nonce'); ?>

                <p>
                    <input type="text" id="seventh_trad_clear_confirm" name="seventh_trad_clear_confirm" value="" placeholder="<?php esc_attr_e('Type DELETE to confirm', '7th-traditioner'); ?>" style="width: 300px;" />
                </p>

                <p>
                    <input type="submit" name="seventh_trad_clear_history" class="button button-primary" value="<?php esc_attr_e('Clear All Contribution Records', '7th-traditioner'); ?>" style="background: #dc3232; border-color: #dc3232;" />
                </p>
            </form>
        </div>

        <script>
        function confirmDelete() {
            var confirmText = document.getElementById('seventh_trad_clear_confirm').value;
            if (confirmText !== 'DELETE') {
                alert('<?php esc_html_e('Please type DELETE to confirm.', '7th-traditioner'); ?>');
                return false;
            }
            return confirm('<?php esc_html_e('Are you absolutely sure? This will permanently delete ALL contribution records and cannot be undone!', '7th-traditioner'); ?>');
        }
        </script>
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

        // Save enabled currencies (checkboxes)
        $enabled_currencies = array();
        if (isset($_POST['enabled_currencies']) && is_array($_POST['enabled_currencies'])) {
            $enabled_currencies = array_map('sanitize_text_field', $_POST['enabled_currencies']);
        } else {
            // If no currencies selected, enable all
            $currencies = seventh_trad_get_supported_currencies();
            $enabled_currencies = array_keys($currencies);
        }

        // Save default currency and ensure it's enabled
        if (isset($_POST['default_currency'])) {
            $default_currency = sanitize_text_field($_POST['default_currency']);

            // Ensure default currency is in the enabled list
            if (!in_array($default_currency, $enabled_currencies)) {
                $enabled_currencies[] = $default_currency;
            }

            update_option('seventh_trad_default_currency', $default_currency);
        }

        update_option('seventh_trad_enabled_currencies', $enabled_currencies);

        // Save show_group_id (checkbox)
        update_option('seventh_trad_show_group_id', isset($_POST['show_group_id']) ? '1' : '0');

        // Save color mode
        if (isset($_POST['color_mode'])) {
            $color_mode = sanitize_text_field($_POST['color_mode']);
            if (in_array($color_mode, array('auto', 'light', 'dark'))) {
                update_option('seventh_trad_color_mode', $color_mode);
            }
        }

        if (isset($_POST['paypal_mode'])) {
            update_option('seventh_trad_paypal_mode', sanitize_text_field($_POST['paypal_mode']));
        }

        if (isset($_POST['paypal_sandbox_client_id'])) {
            update_option('seventh_trad_paypal_sandbox_client_id', sanitize_text_field($_POST['paypal_sandbox_client_id']));
        }

        if (isset($_POST['paypal_live_client_id'])) {
            update_option('seventh_trad_paypal_live_client_id', sanitize_text_field($_POST['paypal_live_client_id']));
        }

        if (isset($_POST['recaptcha_site_key'])) {
            update_option('seventh_trad_recaptcha_site_key', sanitize_text_field($_POST['recaptcha_site_key']));
        }

        if (isset($_POST['recaptcha_secret_key'])) {
            update_option('seventh_trad_recaptcha_secret_key', sanitize_text_field($_POST['recaptcha_secret_key']));
        }

        // Save email customization settings
        if (isset($_POST['email_from_name'])) {
            update_option('seventh_trad_email_from_name', sanitize_text_field($_POST['email_from_name']));
        }

        if (isset($_POST['email_from_address'])) {
            update_option('seventh_trad_email_from_address', sanitize_email($_POST['email_from_address']));
        }

        if (isset($_POST['email_subject'])) {
            update_option('seventh_trad_email_subject', sanitize_text_field($_POST['email_subject']));
        }

        if (isset($_POST['email_title'])) {
            update_option('seventh_trad_email_title', sanitize_text_field($_POST['email_title']));
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
