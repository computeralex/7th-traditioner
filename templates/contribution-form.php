<?php
/**
 * Contribution Form Template
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

$groups = seventh_trad_get_groups();
$all_currencies = seventh_trad_get_supported_currencies();
$enabled_currency_codes = get_option('seventh_trad_enabled_currencies', array_keys($all_currencies));
// Filter to only show enabled currencies
$currencies = array_intersect_key($all_currencies, array_flip($enabled_currency_codes));
$default_currency = get_option('seventh_trad_default_currency', 'USD');
$fellowship_name = seventh_trad_get_fellowship_name();
$color_mode = get_option('seventh_trad_color_mode', 'auto');
?>

<div class="seventh-trad-container" data-color-mode="<?php echo esc_attr($color_mode); ?>">
    <div class="seventh-trad-form-wrapper">
        <?php if (!empty($atts['title'])) : ?>
            <h2 class="seventh-trad-title"><?php echo esc_html($atts['title']); ?></h2>
        <?php endif; ?>

        <?php if (!empty($atts['description'])) : ?>
            <div class="seventh-trad-description">
                <?php echo wp_kses_post($atts['description']); ?>
            </div>
        <?php endif; ?>

        <form id="seventh-trad-form" class="seventh-trad-form">
            <div class="seventh-trad-messages">
                <div class="seventh-trad-success" style="display: none;"></div>
                <div class="seventh-trad-error" style="display: none;"></div>
            </div>

            <!-- Name Fields - First and Last side by side -->
            <div class="seventh-trad-field-row">
                <div class="seventh-trad-field seventh-trad-field-half">
                    <label for="seventh-trad-first-name">
                        <?php esc_html_e('First Name', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="seventh-trad-first-name"
                        name="first_name"
                        class="seventh-trad-input"
                        required
                        placeholder="<?php esc_attr_e('First Name', '7th-traditioner'); ?>"
                    />
                </div>
                <div class="seventh-trad-field seventh-trad-field-half">
                    <label for="seventh-trad-last-name">
                        <?php esc_html_e('Last Name', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="seventh-trad-last-name"
                        name="last_name"
                        class="seventh-trad-input"
                        required
                        placeholder="<?php esc_attr_e('Last Name', '7th-traditioner'); ?>"
                    />
                </div>
            </div>

            <!-- Email (Always Required) -->
            <div class="seventh-trad-field">
                <label for="seventh-trad-email">
                    <?php esc_html_e('Email', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="email"
                    id="seventh-trad-email"
                    name="email"
                    class="seventh-trad-input"
                    required
                    placeholder="<?php esc_attr_e('your.email@example.com', '7th-traditioner'); ?>"
                />
            </div>

            <!-- Phone (Required) -->
            <div class="seventh-trad-field">
                <label for="seventh-trad-phone">
                    <?php esc_html_e('Phone', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="tel"
                    id="seventh-trad-phone"
                    name="phone"
                    class="seventh-trad-input"
                    required
                    pattern="[\d\s\-\(\)\.\+]*"
                    placeholder="<?php esc_attr_e('+1 (555) 123-4567', '7th-traditioner'); ?>"
                    title="<?php esc_attr_e('Please enter a valid phone number', '7th-traditioner'); ?>"
                />
            </div>

            <!-- Contributor Type -->
            <div class="seventh-trad-field">
                <label for="seventh-trad-contributor-type">
                    <?php esc_html_e('I am contributing', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <select
                    id="seventh-trad-contributor-type"
                    name="contributor_type"
                    class="seventh-trad-select"
                    required
                >
                    <option value=""><?php esc_html_e('-- Select --', '7th-traditioner'); ?></option>
                    <option value="individual"><?php esc_html_e('As an Individual', '7th-traditioner'); ?></option>
                    <option value="group"><?php esc_html_e('On Behalf of a Group', '7th-traditioner'); ?></option>
                </select>
            </div>

            <!-- Group Fields (shown when "On Behalf of a Group" selected) -->
            <div id="group-fields" style="display: none;">
                <div class="seventh-trad-field">
                    <label for="seventh-trad-meeting-day">
                        <?php esc_html_e('Meeting Day', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="seventh-trad-meeting-day"
                        name="meeting_day"
                        class="seventh-trad-select"
                    >
                        <option value=""><?php esc_html_e('-- Select Day --', '7th-traditioner'); ?></option>
                        <option value="0"><?php esc_html_e('Sunday', '7th-traditioner'); ?></option>
                        <option value="1"><?php esc_html_e('Monday', '7th-traditioner'); ?></option>
                        <option value="2"><?php esc_html_e('Tuesday', '7th-traditioner'); ?></option>
                        <option value="3"><?php esc_html_e('Wednesday', '7th-traditioner'); ?></option>
                        <option value="4"><?php esc_html_e('Thursday', '7th-traditioner'); ?></option>
                        <option value="5"><?php esc_html_e('Friday', '7th-traditioner'); ?></option>
                        <option value="6"><?php esc_html_e('Saturday', '7th-traditioner'); ?></option>
                    </select>
                </div>

                <div class="seventh-trad-field">
                    <label for="seventh-trad-meeting">
                        <?php esc_html_e('Meeting Name', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="seventh-trad-meeting"
                        name="meeting_id"
                        class="seventh-trad-select"
                        disabled
                    >
                        <option value=""><?php esc_html_e('-- Select Day First --', '7th-traditioner'); ?></option>
                    </select>
                    <small class="seventh-trad-help">
                        <?php esc_html_e('Don\'t see your meeting?', '7th-traditioner'); ?>
                        <a href="#" id="seventh-trad-add-other-meeting"><?php esc_html_e('Enter manually', '7th-traditioner'); ?></a>
                    </small>
                </div>

                <!-- Other meeting text input (shown when "Enter manually" clicked) -->
                <div id="other-meeting-field" class="seventh-trad-field" style="display: none;">
                    <label for="seventh-trad-other-meeting">
                        <?php esc_html_e('Meeting Name', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="seventh-trad-other-meeting"
                        name="other_meeting"
                        class="seventh-trad-input"
                        placeholder="<?php esc_attr_e('Enter your meeting name', '7th-traditioner'); ?>"
                    />
                    <small class="seventh-trad-help">
                        <?php esc_html_e('Is your meeting listed?', '7th-traditioner'); ?>
                        <a href="#" id="seventh-trad-select-from-list"><?php esc_html_e('Select from list instead', '7th-traditioner'); ?></a>
                    </small>
                </div>

                <!-- Group ID -->
                <?php if (get_option('seventh_trad_show_group_id', true)) : ?>
                <div class="seventh-trad-field">
                    <label for="seventh-trad-group-id">
                        <?php esc_html_e('Group ID', '7th-traditioner'); ?>
                        <span class="optional"><?php esc_html_e('(Optional)', '7th-traditioner'); ?></span>
                    </label>
                    <input
                        type="number"
                        id="seventh-trad-group-id"
                        name="group_id"
                        class="seventh-trad-input"
                        placeholder="<?php esc_attr_e('Enter your group ID if known', '7th-traditioner'); ?>"
                        min="0"
                        step="1"
                    />
                    <small class="seventh-trad-help">
                        <?php esc_html_e('Your group\'s unique identifier if different from the meeting selection', '7th-traditioner'); ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Currency and Amount Row -->
            <div class="seventh-trad-field-row">
                <div class="seventh-trad-field seventh-trad-field-60">
                    <label for="seventh-trad-currency">
                        <?php esc_html_e('Currency', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="seventh-trad-currency"
                        name="currency"
                        class="seventh-trad-select"
                        data-decimals="2"
                        data-symbol="$"
                        data-position="before"
                        required
                    >
                        <?php foreach ($currencies as $code => $currency_data) : ?>
                            <option
                                value="<?php echo esc_attr($code); ?>"
                                data-symbol="<?php echo esc_attr($currency_data['symbol']); ?>"
                                data-decimals="<?php echo esc_attr($currency_data['decimals']); ?>"
                                data-position="<?php echo esc_attr($currency_data['position']); ?>"
                                <?php selected($code, $default_currency); ?>
                            >
                                <?php echo esc_html($currency_data['name'] . ' (' . $code . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="seventh-trad-field seventh-trad-field-40">
                    <label for="seventh-trad-amount">
                        <?php esc_html_e('Amount', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="seventh-trad-amount-wrapper">
                        <span id="seventh-trad-currency-symbol" class="seventh-trad-currency-prefix">$</span>
                        <input
                            type="text"
                            id="seventh-trad-amount"
                            name="amount"
                            class="seventh-trad-input seventh-trad-amount-input"
                            min="1"
                            required
                            placeholder="0.00"
                        />
                    </div>
                </div>
            </div>
            <!-- Hidden fields -->
            <input type="hidden" name="action" value="seventh_trad_save_contribution" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('seventh_trad_nonce')); ?>" />
            <input type="hidden" name="recaptcha_token" id="seventh-trad-recaptcha-token" />
        </form>

        <!-- PayPal Button Container (outside form to prevent conflicts) -->
        <div class="seventh-trad-submit-container">
            <div id="seventh-trad-paypal-button-container"></div>
            <small class="seventh-trad-help" style="text-align: center; display: block; margin-top: 10px;">
                <?php esc_html_e('All payments are securely processed by PayPal', '7th-traditioner'); ?>
            </small>
        </div>

        <!-- Loading indicator -->
        <div id="seventh-trad-loading" class="seventh-trad-loading" style="display: none;">
            <div class="seventh-trad-spinner"></div>
            <p><?php esc_html_e('Processing your contribution...', '7th-traditioner'); ?></p>
        </div>

        <div class="seventh-trad-footer">
            <p class="seventh-trad-secure">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 1l5.5 2v4.5c0 3.313-2.281 5.781-5.5 6.5-3.219-.719-5.5-3.187-5.5-6.5V3L8 1z"/>
                </svg>
                <?php esc_html_e('Secure payment processing by PayPal', '7th-traditioner'); ?>
            </p>
            <p class="seventh-trad-privacy">
                <?php esc_html_e('Your payment information is never stored on our servers. All transactions are processed securely by PayPal.', '7th-traditioner'); ?>
            </p>
        </div>
    </div>
</div>
