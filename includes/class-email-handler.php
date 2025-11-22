<?php
/**
 * Email Handler
 *
 * Handles sending contribution receipt emails
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Email_Handler {

    /**
     * Send contribution receipt email
     *
     * @param int $contribution_id Contribution ID
     * @return bool True on success, false on failure
     */
    public static function send_receipt($contribution_id) {
        $contribution = Seventh_Trad_Database::get_contribution($contribution_id);

        if (!$contribution) {
            return false;
        }

        $to = $contribution->member_email;
        $fellowship_name = seventh_trad_get_fellowship_name();
        $subject = sprintf(
            /* translators: %s: Fellowship name */
            __('Contribution Receipt - %s', '7th-traditioner'),
            $fellowship_name
        );

        // Build email content
        $message = self::get_receipt_template($contribution);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fellowship_name . ' <' . get_option('admin_email') . '>',
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log if failed
        if (!$sent) {
            error_log('7th Traditioner: Failed to send receipt to ' . $to . ' for contribution ID: ' . $contribution_id);
        }

        return $sent;
    }

    /**
     * Get receipt email template
     *
     * @param object $contribution Contribution data
     * @return string HTML email content
     */
    private static function get_receipt_template($contribution) {
        $fellowship_name = seventh_trad_get_fellowship_name();

        $formatted_amount = seventh_trad_format_amount($contribution->amount, $contribution->currency);
        $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contribution->contribution_date));

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html__('Contribution Receipt', '7th-traditioner'); ?></title>
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
                                        <?php echo esc_html__('Contribution Receipt', '7th-traditioner'); ?>
                                    </h1>
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px; color: #2d3748; font-size: 16px; line-height: 1.6;">
                                        <?php
                                        if (!empty($contribution->member_name)) {
                                            printf(
                                                /* translators: %s: Member name */
                                                esc_html__('Dear %s,', '7th-traditioner'),
                                                '<strong>' . esc_html($contribution->member_name) . '</strong>'
                                            );
                                        } else {
                                            echo esc_html__('Dear Member,', '7th-traditioner');
                                        }
                                        ?>
                                    </p>

                                    <p style="margin: 0 0 30px; color: #2d3748; font-size: 16px; line-height: 1.6;">
                                        <?php
                                        printf(
                                            /* translators: %s: Fellowship name */
                                            esc_html__('Thank you for your contribution to %s. Your support helps us maintain our commitment to the 7th Tradition of being fully self-supporting through member contributions.', '7th-traditioner'),
                                            '<strong>' . esc_html($fellowship_name) . '</strong>'
                                        );
                                        ?>
                                    </p>

                                    <!-- Contribution Details -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f7fafc; border-radius: 6px; padding: 20px; margin-bottom: 30px;">
                                        <tr>
                                            <td style="padding: 10px 0;">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                    <tr>
                                                        <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                            <?php echo esc_html__('Amount:', '7th-traditioner'); ?>
                                                        </td>
                                                        <td style="color: #2d3748; font-size: 18px; font-weight: bold; text-align: right; padding: 8px 0;">
                                                            <?php echo esc_html($formatted_amount); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                            <?php echo esc_html__('Group:', '7th-traditioner'); ?>
                                                        </td>
                                                        <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0;">
                                                            <?php echo esc_html($contribution->group_name); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                            <?php echo esc_html__('Date:', '7th-traditioner'); ?>
                                                        </td>
                                                        <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0;">
                                                            <?php echo esc_html($date); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color: #718096; font-size: 14px; padding: 8px 0;">
                                                            <?php echo esc_html__('Transaction ID:', '7th-traditioner'); ?>
                                                        </td>
                                                        <td style="color: #2d3748; font-size: 14px; text-align: right; padding: 8px 0; font-family: monospace;">
                                                            <?php echo esc_html($contribution->transaction_id); ?>
                                                        </td>
                                                    </tr>
                                                    <?php if (!empty($contribution->custom_notes)) : ?>
                                                    <tr>
                                                        <td colspan="2" style="color: #718096; font-size: 14px; padding: 16px 0 8px;">
                                                            <?php echo esc_html__('Notes:', '7th-traditioner'); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" style="color: #2d3748; font-size: 14px; padding: 0 0 8px; font-style: italic;">
                                                            <?php echo esc_html($contribution->custom_notes); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- 7th Tradition Note -->
                                    <div style="background-color: #edf2f7; border-left: 4px solid #4299e1; padding: 16px; margin-bottom: 30px; border-radius: 4px;">
                                        <p style="margin: 0; color: #2d3748; font-size: 14px; line-height: 1.6;">
                                            <strong><?php echo esc_html__('7th Tradition:', '7th-traditioner'); ?></strong>
                                            <?php echo esc_html__('Every group ought to be fully self-supporting, declining outside contributions.', '7th-traditioner'); ?>
                                        </p>
                                    </div>

                                    <p style="margin: 0; color: #718096; font-size: 14px; line-height: 1.6;">
                                        <?php echo esc_html__('Please retain this receipt for your records. If you have any questions about your contribution, please contact us.', '7th-traditioner'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding: 30px 40px; background-color: #f7fafc; border-radius: 0 0 8px 8px; text-align: center;">
                                    <p style="margin: 0; color: #718096; font-size: 14px;">
                                        <a href="<?php echo esc_url(home_url('/')); ?>" style="color: #4299e1; text-decoration: none;">
                                            <?php echo esc_html($fellowship_name); ?>
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
