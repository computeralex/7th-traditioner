<?php
/**
 * Contributions Page Handler
 *
 * Manages the contributions admin page
 *
 * @package Seventh_Traditioner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seventh_Trad_Contributions {

    /**
     * Export action query arg value.
     */
    const EXPORT_ACTION = 'seventh_trad_export_csv';

    /**
     * Handle CSV export requests on the contributions admin screen.
     */
    public static function maybe_export_csv() {
        if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'seventh-traditioner') {
            return;
        }

        if (!isset($_GET['seventh_trad_export']) || $_GET['seventh_trad_export'] !== 'csv') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to export contributions.', '7th-traditioner'));
        }

        check_admin_referer(self::EXPORT_ACTION);

        $args = self::get_filter_args_from_request();
        $args['limit'] = -1;
        $args['offset'] = 0;

        $contributions = Seventh_Trad_Database::get_contributions($args);
        self::stream_csv($contributions);
        exit;
    }

    /**
     * Build query args from the current admin list filters.
     *
     * @return array
     */
    private static function get_filter_args_from_request() {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
        $sort_by = isset($_GET['sort_by']) ? sanitize_text_field(wp_unslash($_GET['sort_by'])) : 'date';
        $sort_order = isset($_GET['sort_order']) ? sanitize_text_field(wp_unslash($_GET['sort_order'])) : 'DESC';

        $args = array(
            'order_by' => $sort_by,
            'order' => $sort_order,
        );

        if ($search !== '') {
            $args['search'] = $search;
        }
        if ($date_from !== '') {
            $args['date_from'] = $date_from;
        }
        if ($date_to !== '') {
            $args['date_to'] = $date_to;
        }

        return $args;
    }

    /**
     * Stream a UTF-8 CSV download for the given contributions.
     *
     * @param array $contributions Contribution rows from the database.
     */
    private static function stream_csv($contributions) {
        $filename = 'seventh-trad-contributions-' . gmdate('Y-m-d-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Could not create export file.', '7th-traditioner'));
        }

        // Excel-friendly UTF-8 BOM.
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, array(
            __('Date', '7th-traditioner'),
            __('Name', '7th-traditioner'),
            __('Email', '7th-traditioner'),
            __('Phone', '7th-traditioner'),
            __('Type', '7th-traditioner'),
            __('Meeting Day', '7th-traditioner'),
            __('Group Name', '7th-traditioner'),
            __('Group ID', '7th-traditioner'),
            __('Amount', '7th-traditioner'),
            __('Currency', '7th-traditioner'),
            __('Schedule', '7th-traditioner'),
            __('Transaction ID', '7th-traditioner'),
            __('PayPal Order ID', '7th-traditioner'),
            __('PayPal Status', '7th-traditioner'),
            __('Subscription ID', '7th-traditioner'),
            __('Subscription Status', '7th-traditioner'),
            __('Notes', '7th-traditioner'),
        ));

        foreach ($contributions as $contribution) {
            $meeting_day = '';
            if (isset($contribution->meeting_day) && $contribution->meeting_day !== '' && $contribution->meeting_day !== null) {
                $meeting_day = seventh_trad_get_day_name((int) $contribution->meeting_day);
            }

            $schedule = __('One-time', '7th-traditioner');
            if (!empty($contribution->is_recurring)) {
                $schedule = !empty($contribution->is_renewal)
                    ? __('Recurring renewal', '7th-traditioner')
                    : __('Recurring initial', '7th-traditioner');
            }

            fputcsv($output, array(
                $contribution->contribution_date,
                $contribution->member_name,
                $contribution->member_email,
                $contribution->member_phone,
                ($contribution->contribution_type === 'group')
                    ? __('Group', '7th-traditioner')
                    : __('Individual', '7th-traditioner'),
                $meeting_day,
                $contribution->group_name,
                $contribution->group_id,
                number_format((float) $contribution->amount, 2, '.', ''),
                $contribution->currency,
                $schedule,
                $contribution->transaction_id,
                $contribution->paypal_order_id,
                $contribution->paypal_status,
                $contribution->subscription_id,
                $contribution->subscription_status,
                $contribution->custom_notes,
            ));
        }

        fclose($output);
    }

    /**
     * Build the export URL preserving current list filters.
     *
     * @param array $filter_args Current filter values.
     * @return string
     */
    private static function get_export_url($filter_args) {
        $query_args = array(
            'page' => 'seventh-traditioner',
            'seventh_trad_export' => 'csv',
        );

        foreach (array('search', 'date_from', 'date_to', 'sort_by', 'sort_order') as $key) {
            if (!empty($filter_args[$key])) {
                $query_args[$key === 'search' ? 's' : $key] = $filter_args[$key];
            }
        }

        return wp_nonce_url(
            add_query_arg($query_args, admin_url('admin.php')),
            self::EXPORT_ACTION
        );
    }

    /**
     * Render contributions page
     */
    public static function render_page() {
        // Get filter parameters
        $filter_args = self::get_filter_args_from_request();
        $search = isset($filter_args['search']) ? $filter_args['search'] : '';
        $date_from = isset($filter_args['date_from']) ? $filter_args['date_from'] : '';
        $date_to = isset($filter_args['date_to']) ? $filter_args['date_to'] : '';
        $sort_by = $filter_args['order_by'];
        $sort_order = $filter_args['order'];
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

        // Build query args
        $args = array_merge($filter_args, array(
            'limit' => $per_page,
            'offset' => ($paged - 1) * $per_page,
            'order_by' => $sort_by,
            'order' => $sort_order,
        ));

        // Get contributions
        $contributions = Seventh_Trad_Database::get_contributions($args);
        $total_count = Seventh_Trad_Database::get_contributions_count($args);
        $total_amount = Seventh_Trad_Database::get_total_amount($args);
        $total_pages = ceil($total_count / $per_page);

        ?>
        <div class="wrap seventh-trad-contributions">
            <h1><?php echo esc_html__('Contributions', '7th-traditioner'); ?></h1>

            <!-- Filters -->
            <div class="tablenav top" style="margin-bottom: 40px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="seventh-traditioner" />

                    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label for="search"><?php esc_html_e('Search:', '7th-traditioner'); ?></label><br>
                            <input type="text" id="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Name, email, phone...', '7th-traditioner'); ?>" style="width: 200px;" />
                        </div>

                        <div>
                            <label for="date_from"><?php esc_html_e('From Date:', '7th-traditioner'); ?></label><br>
                            <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
                        </div>

                        <div>
                            <label for="date_to"><?php esc_html_e('To Date:', '7th-traditioner'); ?></label><br>
                            <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
                        </div>

                        <div>
                            <label for="per_page"><?php esc_html_e('Show:', '7th-traditioner'); ?></label><br>
                            <select id="per_page" name="per_page">
                                <option value="25" <?php selected($per_page, 25); ?>>25</option>
                                <option value="50" <?php selected($per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($per_page, 100); ?>>100</option>
                                <option value="200" <?php selected($per_page, 200); ?>>200</option>
                            </select>
                        </div>

                        <div>
                            <input type="submit" class="button" value="<?php esc_attr_e('Filter', '7th-traditioner'); ?>" />
                            <a href="?page=seventh-traditioner" class="button"><?php esc_html_e('Reset', '7th-traditioner'); ?></a>
                            <a href="<?php echo esc_url(self::get_export_url(array(
                                'search' => $search,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                                'sort_by' => $sort_by,
                                'sort_order' => $sort_order,
                            ))); ?>" class="button button-secondary">
                                <?php esc_html_e('Export CSV', '7th-traditioner'); ?>
                            </a>
                        </div>
                    </div>
                </form>
                <?php if ($total_count > 0) : ?>
                    <p class="description" style="margin: 0 0 10px;">
                        <?php
                        printf(
                            /* translators: %s: number of contributions */
                            esc_html__('Export CSV includes all %s contributions matching the current filters.', '7th-traditioner'),
                            number_format_i18n($total_count)
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Contributions Table -->
            <?php if (empty($contributions)) : ?>
                <p style="margin-top: 20px;"><?php esc_html_e('No contributions found.', '7th-traditioner'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php self::render_sortable_header('date', __('Date', '7th-traditioner'), $sort_by, $sort_order); ?></th>
                            <th><?php self::render_sortable_header('name', __('Name', '7th-traditioner'), $sort_by, $sort_order); ?></th>
                            <th><?php self::render_sortable_header('email', __('Email', '7th-traditioner'), $sort_by, $sort_order); ?></th>
                            <th><?php self::render_sortable_header('phone', __('Phone', '7th-traditioner'), $sort_by, $sort_order); ?></th>
                            <th><?php esc_html_e('Individual/Group', '7th-traditioner'); ?></th>
                            <th><?php esc_html_e('Group Info', '7th-traditioner'); ?></th>
                            <th><?php self::render_sortable_header('amount', __('Amount', '7th-traditioner'), $sort_by, $sort_order); ?></th>
                            <th><?php esc_html_e('Details', '7th-traditioner'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contributions as $contribution) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('M j, Y', strtotime($contribution->contribution_date))); ?></td>
                                <td><?php echo esc_html($contribution->member_name); ?></td>
                                <td><?php echo esc_html($contribution->member_email); ?></td>
                                <td><?php echo esc_html(!empty($contribution->member_phone) ? $contribution->member_phone : '—'); ?></td>
                                <td><?php echo esc_html((!empty($contribution->contribution_type) && $contribution->contribution_type === 'group') ? __('Group', '7th-traditioner') : __('Individual', '7th-traditioner')); ?></td>
                                <td>
                                    <?php if (!empty($contribution->contribution_type) && $contribution->contribution_type === 'group' && !empty($contribution->group_name)) : ?>
                                        <?php echo esc_html($contribution->group_name); ?>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>
                                        <?php echo esc_html(seventh_trad_format_amount($contribution->amount, $contribution->currency)); ?>
                                    </strong>
                                    <?php if (!empty($contribution->is_recurring)) : ?>
                                        <br><span class="seventh-trad-badge"><?php echo !empty($contribution->is_renewal) ? esc_html__('Monthly renewal', '7th-traditioner') : esc_html__('Monthly', '7th-traditioner'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small view-details" data-id="<?php echo esc_attr($contribution->id); ?>">
                                        <?php esc_html_e('View Details', '7th-traditioner'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php
                        // Check if all contributions use the same currency
                        $currencies_used = array_unique(array_column($contributions, 'currency'));
                        $single_currency = (count($currencies_used) === 1) ? $currencies_used[0] : null;
                        ?>
                        <?php if ($single_currency) : ?>
                        <tr>
                            <td colspan="6" style="text-align: right; font-weight: bold;">
                                <?php esc_html_e('Total:', '7th-traditioner'); ?>
                            </td>
                            <td colspan="2" style="font-weight: bold; font-size: 16px;">
                                <?php echo esc_html(seventh_trad_format_amount($total_amount, $single_currency)); ?>
                            </td>
                        </tr>
                        <?php else : ?>
                        <tr>
                            <td colspan="8" style="text-align: center; font-style: italic; color: #666;">
                                <?php esc_html_e('Multiple currencies - totals cannot be combined', '7th-traditioner'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num">
                                <?php printf(_n('%s item', '%s items', $total_count, '7th-traditioner'), number_format_i18n($total_count)); ?>
                            </span>
                            <?php
                            $page_links = paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $paged
                            ));
                            echo $page_links;
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Details Modal -->
            <div id="contribution-details-modal" style="display: none;">
                <div id="contribution-details-content"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // View details
            $('.view-details').on('click', function() {
                var contributionId = $(this).data('id');

                $.post(ajaxurl, {
                    action: 'seventh_trad_get_contribution_details',
                    contribution_id: contributionId,
                    nonce: '<?php echo wp_create_nonce('seventh_trad_contribution_details'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#contribution-details-content').html(response.data.html);
                        $('#contribution-details-modal').dialog({
                            title: '<?php esc_html_e('Contribution Details', '7th-traditioner'); ?>',
                            width: 600,
                            modal: true
                        });
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render sortable column header
     */
    private static function render_sortable_header($column, $label, $current_sort, $current_order) {
        $url = add_query_arg(array(
            'sort_by' => $column,
            'sort_order' => ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC'
        ));

        $arrow = '';
        if ($current_sort === $column) {
            $arrow = $current_order === 'ASC' ? ' <span style="color: #2271b1;">▲</span>' : ' <span style="color: #2271b1;">▼</span>';
        } else {
            $arrow = ' <span style="color: #ddd;">▲▼</span>';
        }

        echo '<a href="' . esc_url($url) . '" style="text-decoration: none;">' . esc_html($label) . $arrow . '</a>';
    }
}
