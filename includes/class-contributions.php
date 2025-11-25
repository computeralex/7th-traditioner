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
     * Render contributions page
     */
    public static function render_page() {
        // Get filter parameters
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'date';
        $sort_order = isset($_GET['sort_order']) ? sanitize_text_field($_GET['sort_order']) : 'DESC';
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

        // Build query args
        $args = array(
            'limit' => $per_page,
            'offset' => ($paged - 1) * $per_page,
            'order_by' => $sort_by,
            'order' => $sort_order,
        );

        if (!empty($search)) {
            $args['search'] = $search;
        }
        if (!empty($date_from)) {
            $args['date_from'] = $date_from;
        }
        if (!empty($date_to)) {
            $args['date_to'] = $date_to;
        }

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
                        </div>
                    </div>
                </form>
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
