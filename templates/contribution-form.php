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
$currencies = seventh_trad_get_supported_currencies();
$default_currency = get_option('seventh_trad_default_currency', 'USD');
$fellowship_name = seventh_trad_get_fellowship_name();
?>

<div class="seventh-trad-container">
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

            <div class="seventh-trad-field">
                <label for="seventh-trad-member-name">
                    <?php esc_html_e('Your Name', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="seventh-trad-member-name"
                    name="member_name"
                    class="seventh-trad-input"
                    required
                    placeholder="<?php esc_attr_e('First Name, First & Last Initial', '7th-traditioner'); ?>"
                />
                <small class="seventh-trad-help">
                    <?php esc_html_e('Your name will appear in contribution records (e.g., "John D.")','7th-traditioner'); ?>
                </small>
            </div>

            <div class="seventh-trad-field">
                <label for="seventh-trad-member-email">
                    <?php esc_html_e('Email Address', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="email"
                    id="seventh-trad-member-email"
                    name="member_email"
                    class="seventh-trad-input"
                    required
                    placeholder="<?php esc_attr_e('your.email@example.com', '7th-traditioner'); ?>"
                />
                <small class="seventh-trad-help">
                    <?php esc_html_e('Required for receipt delivery', '7th-traditioner'); ?>
                </small>
            </div>

            <div class="seventh-trad-field">
                <label for="seventh-trad-group">
                    <?php esc_html_e('Select Group', '7th-traditioner'); ?>
                    <span class="required">*</span>
                </label>
                <select
                    id="seventh-trad-group"
                    name="group_id"
                    class="seventh-trad-select"
                    required
                >
                    <option value="">
                        <?php esc_html_e('-- Choose a Group --', '7th-traditioner'); ?>
                    </option>
                    <?php foreach ($groups as $group_id => $group_name) : ?>
                        <option value="<?php echo esc_attr($group_id); ?>">
                            <?php echo esc_html($group_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="seventh-trad-field-row">
                <div class="seventh-trad-field seventh-trad-field-half">
                    <label for="seventh-trad-amount">
                        <?php esc_html_e('Contribution Amount', '7th-traditioner'); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="seventh-trad-amount"
                        name="amount"
                        class="seventh-trad-input"
                        min="1"
                        step="0.01"
                        required
                        placeholder="0.00"
                    />
                </div>

                <div class="seventh-trad-field seventh-trad-field-half">
                    <label for="seventh-trad-currency">
                        <?php esc_html_e('Currency', '7th-traditioner'); ?>
                    </label>
                    <select
                        id="seventh-trad-currency"
                        name="currency"
                        class="seventh-trad-select"
                    >
                        <?php foreach ($currencies as $code => $name) : ?>
                            <option
                                value="<?php echo esc_attr($code); ?>"
                                <?php selected($code, $default_currency); ?>
                            >
                                <?php echo esc_html($code . ' - ' . $name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="seventh-trad-field">
                <label for="seventh-trad-notes">
                    <?php esc_html_e('Notes', '7th-traditioner'); ?>
                    <span class="optional"><?php esc_html_e('(Optional)', '7th-traditioner'); ?></span>
                </label>
                <textarea
                    id="seventh-trad-notes"
                    name="custom_notes"
                    class="seventh-trad-textarea"
                    rows="3"
                    placeholder="<?php esc_attr_e('In memory of...', '7th-traditioner'); ?>"
                ></textarea>
                <small class="seventh-trad-help">
                    <?php esc_html_e('Add a personal note if desired', '7th-traditioner'); ?>
                </small>
            </div>

            <!-- PayPal Button Container -->
            <div id="seventh-trad-paypal-button" class="seventh-trad-paypal-container"></div>

            <!-- Loading indicator -->
            <div id="seventh-trad-loading" class="seventh-trad-loading" style="display: none;">
                <div class="seventh-trad-spinner"></div>
                <p><?php esc_html_e('Processing your contribution...', '7th-traditioner'); ?></p>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="action" value="seventh_trad_save_contribution" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('seventh_trad_nonce')); ?>" />
            <input type="hidden" name="recaptcha_token" id="seventh-trad-recaptcha-token" />
        </form>

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
