<?php
/*
Plugin Name: Joe's Recent Users Activity
Stable tag: 2.4
Version: 2.4
Description: A mobile-responsive plugin showing the last 100 logged-in users & their last page in admin via a 'Recent Activity' menu.
Author: Joe Wakeford
Author URI: https://www.joewakeford.com/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

register_activation_hook(__FILE__, 'joes_recent_users_activity_activate');
register_deactivation_hook(__FILE__, 'joes_recent_users_activity_deactivate');

function joes_recent_users_activity_activate()
{
    joes_recent_users_activity_create_table();
}

function joes_recent_users_activity_deactivate()
{
    // No action needed here since we want to preserve the data
}

function joes_recent_users_activity_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'joes_recent_users_activity';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        last_login datetime DEFAULT NULL,
        last_page varchar(255) DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Sanitize data using sanitize_text_field or appropriate functions
function joes_recent_users_activity_sanitize_data($data)
{
    if (is_array($data)) {
        return array_map('joes_recent_users_activity_sanitize_data', $data);
    } else {
        return sanitize_text_field($data);
    }
}

function joes_recent_users_activity_log_activity($user_id, $last_page)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'joes_recent_users_activity';

    $timestamp = current_time('mysql');
    $ip_address = joes_recent_users_activity_get_user_ip();

    // Delete previous records for the same user_id and session
    $wpdb->delete(
        $table_name,
        array(
            'user_id' => absint($user_id), // Sanitize user_id as an integer
            'ip_address' => sanitize_text_field($ip_address), // Sanitize IP address as text
        ),
        array('%d', '%s')
    );

    $data = array(
        'user_id' => absint($user_id), // Sanitize user_id as an integer
        'timestamp' => sanitize_text_field($timestamp), // Sanitize timestamp as text
        'last_login' => joes_recent_users_activity_get_last_login($user_id),
        'last_page' => esc_url_raw($last_page), // Sanitize last_page as a URL
        'ip_address' => sanitize_text_field($ip_address), // Sanitize IP address as text
    );

    $data = array_map('joes_recent_users_activity_sanitize_data', $data);

    $wpdb->insert($table_name, $data);
}

function joes_recent_users_activity_get_last_login($user_id)
{
    $last_login = get_user_meta($user_id, 'joes_recent_users_activity_last_login', true);
    return $last_login ? sanitize_text_field($last_login) : null; // Sanitize last_login as text
}

function joes_recent_users_activity_update_last_login($user_login, $user)
{
    if ($user && is_a($user, 'WP_User')) {
        $user_id = $user->ID;
        update_user_meta($user_id, 'joes_recent_users_activity_last_login', current_time('mysql'));
    }
}

add_action('wp_login', 'joes_recent_users_activity_update_last_login', 10, 2);

function joes_recent_users_activity_get_user_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
    } else {
        $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }

    return $ip_address;
}

function joes_recent_users_activity_display_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'joes_recent_users_activity';

    $nonce = wp_create_nonce('joes_recent_users_activity_clear_nonce');

    // Check if 'Clear all results' button is clicked
    if (isset($_POST['clear_results'])) {
        if (isset($_POST['joes_recent_users_activity_nonce']) && wp_verify_nonce($_POST['joes_recent_users_activity_nonce'], 'joes_recent_users_activity_clear_nonce')) {
            $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $table_name)); // Clear all data from the table
        } else {
            echo esc_html('Nonce verification failed. Try again.');
        }
    }

    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_activity';
    $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

    $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    $total_users_activity = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE last_page IS NOT NULL");

    $total_pages = ceil($total_users_activity / $per_page);

    $recent_activity = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, MAX(timestamp) as last_activity, MAX(last_login) as last_login, last_page, ip_address
            FROM $table_name
            WHERE last_page IS NOT NULL
            GROUP BY user_id
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    $has_data = !empty($recent_activity);
	
	// Retrieve top 10 exited pages (only front-end pages and exclude admin-ajax.php, 'nonce', 'wp-json', and others not matching front-end permalinks)
$exited_pages = $wpdb->get_results(
    "SELECT last_page, COUNT(*) as count
    FROM $table_name
    WHERE last_page IS NOT NULL AND last_page NOT LIKE '%wp-admin%' AND last_page NOT LIKE '%admin-ajax.php%' AND last_page NOT LIKE '%nonce%' AND last_page NOT LIKE '%wp-json%' AND last_page NOT LIKE '%wp-cron.php%' AND last_page REGEXP '^" . esc_sql(home_url()) . "(/|$)'
    GROUP BY last_page
    ORDER BY count DESC
    LIMIT 10"
);
    ?>

<style>
    #joes-recent-activity-table th,
    #joes-recent-activity-table td {
        text-align: left;
    }

    @media only screen and (max-width: 600px) {
        #joes-recent-activity-table th,
        #joes-recent-activity-table td {
            display: block;
            width: 100%;
        }

        #joes-recent-activity-table thead {
            display: none;
        }

        #joes-recent-activity-table tbody td {
		    max-width: -webkit-fill-available;
            border-bottom: 1px solid #ddd;
            display: block;
            padding: 10px;
            text-align: left;
			white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis; 
        }

        #joes-recent-activity-table tbody td:before {
            content: attr(data-label);
            float: left;
            font-weight: bold;
            text-align: left;
            padding-right: 10px;
        }

        #joes-recent-activity-table tbody tr:nth-child(odd) td {
            background-color: #f2f2f2;
        }

        #joes-recent-activity-table tbody tr:last-child td:last-child {
            border-bottom: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('joes-recent-activity-table');
        var headerRow = table.querySelector('thead tr');
        var cellIndex = 0;

        table.querySelectorAll('tbody tr').forEach(function (row) {
            var cells = row.querySelectorAll('td');
            cells.forEach(function (cell) {
                cell.setAttribute('data-label', headerRow.cells[cellIndex].innerText);
                cellIndex++;
            });
            cellIndex = 0;
        });
    });
</script>

    <div class="wrap">
        <h1><?php echo esc_html('Joe\'s Recent Users Activity'); ?></h1>
        <p>A display of the most recent 100 logged-in users' activity. All results since activation are stored in the database.</p>
        <p><a href="https://www.paypal.com/donate/?hosted_button_id=XQGAWEWDS4DG2" title="Donate" target="_blank">Donate</a> if you find the plugin useful. Suggest a feature (or get support) on our <a href="https://wordpress.org/support/plugin/joes-recent-users-activity/" target="_blank">WordPress Support Forum</a>, or <a href="mailto:plugins@joewakeford.com" title="Contact me" target="_blank">contact me</a>.</p>
        <br><?php if (!$has_data) : ?>
            <p>As soon as there is some website activity, the data will be shown here. Try refreshing the page when you have had a logged-in visitor.</p>
        <?php endif; ?>

<style>
 /* Default styles for larger screens (desktop) */

#MobileTitle {
  display: none;
}

/* Media query for mobile screens */

@media only screen and (max-width: 600px) {
  #MobileTitle {
    display: block;
  }
}
</style>

<!-- Add title and subheader for mobile version -->
<h2 id="MobileTitle"><?php echo esc_html__('Most Recent User Activity', 'joes-recent-users-activity'); ?></h2>
<p id="MobileTitle"><?php echo esc_html__('To sort the data please use a table or desktop display.', 'joes-recent-users-activity'); ?></p>

        <table class="widefat fixed" id="joes-recent-activity-table">
            <thead>
                <tr>
                    <?php
                    // Define columns
                    $columns = array(
                        'user_id' => 'User ID',
                        'user_login' => 'Username',
                        'role' => 'Role',
                        'last_page' => 'Last Page Viewed',
                        'ip_address' => 'IP Address',
                        'last_login' => 'Last Login',
                        'time_active' => 'Time Active',
                    );

                    // Output the table header
                    foreach ($columns as $column_key => $column_name) {
                        $is_sorted = $orderby === $column_key;
                        $column_order = $is_sorted && $order === 'ASC' ? 'desc' : 'asc';
                        $sorted_class = $is_sorted ? "sorted $order" : '';

                        echo '<th scope="col" class="manage-column column-' . esc_attr($column_key) . ' ' . esc_attr($sorted_class) . '">';
                        echo '<a href="' . esc_url(admin_url('admin.php?page=joes_recent_users_activity&orderby=' . esc_attr($column_key) . '&order=' . esc_attr($column_order))) . '">';
                        echo esc_html($column_name);
                        echo '</a>';
                        echo '</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Output the table rows
                if (!$has_data) {
                    echo '<tr><td colspan="' . esc_attr(count($columns)) . '">' . esc_html('No data available') . '</td></tr>';
                } else {
                    foreach ($recent_activity as $activity) {
                        $user_info = get_userdata($activity->user_id);
                        $user_edit_link = get_edit_user_link($activity->user_id);
                        $username = $user_info ? sanitize_text_field($user_info->user_login) : 'Unknown User';
                        $role = $user_info ? implode(', ', $user_info->roles) : 'Unknown Role';
                        $last_page = esc_url($activity->last_page);
                        $last_page_permalink = preg_replace('@^(?:https?:\/\/)?(?:www\.)?' . preg_quote(home_url(), '@') . '@i', '', $last_page);
                        $ip_address = sanitize_text_field($activity->ip_address);
                        $last_activity = date('Y-m-d H:i:s', strtotime($activity->last_activity));

                        // Format the Last Login using WordPress settings
                        $last_login = sanitize_text_field($activity->last_login);
                        if ($last_login) {
                            $date_format = get_option('date_format');
                            $time_format = get_option('time_format');
                            $formatted_last_login = date_i18n($date_format . ', ' . $time_format, strtotime($last_login));
                        } else {
                            $formatted_last_login = '-';
                        }

                        $time_active = $last_login ? human_time_diff(strtotime($last_login), strtotime($activity->last_activity)) : '';

                        echo '<tr>';
                        echo '<td>' . esc_html(absint($activity->user_id)) . '</td>';
                        echo '<td>' . ($user_info ? '<a href="' . esc_url($user_edit_link) . '">' . esc_html($username) . '</a>' : 'Unknown User') . '</td>';
                        echo '<td>' . esc_html($role) . '</td>';
                        echo '<td><a href="' . esc_url($last_page) . '">' . esc_html($last_page_permalink) . '</a></td>';
                        echo '<td>' . esc_html($ip_address) . '</td>';
                        echo '<td>' . esc_html($formatted_last_login) . '</td>';
                        echo '<td>' . esc_html($time_active) . '</td>';
                        echo '</tr>';
                    }
                }

                // Output pagination links
                echo '</tbody>';
                echo '</table>';

                echo '<div class="tablenav bottom">';
                echo '<div class="tablenav-pages">';
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $page,
                ));
                echo '</div>';
                echo '</div>';
                ?>
            </tbody>
        </table>

        <!-- 'Clear all results' button -->
        <form method="post">
            <?php wp_nonce_field('joes_recent_users_activity_clear_nonce', 'joes_recent_users_activity_nonce'); ?>
            <p align="right">
                <button type="submit" class="button" name="clear_results" onclick="return confirm('<?php echo esc_html__('Are you sure you want to clear all recent visitor data? This cannot be undone.', 'joes-recent-users-activity'); ?>')">
                    <?php echo esc_html__('Clear ALL visitor data', 'joes-recent-users-activity'); ?>
                </button>
            </p>
        </form>
        <!-- End 'Clear all results' button -->

        <!-- Add section for Top 10 Exited Pages -->
<?php if (!empty($exited_pages)) : ?>
    <h2><?php echo esc_html__('Top 10 Exited Pages', 'joes-recent-users-activity'); ?></h2>
    <p><?php echo esc_html__('The ten most common pages for logged-in visitors to see before they leave the site.', 'joes-recent-users-activity'); ?></p>
    <ol>
        <?php foreach ($exited_pages as $exited_page) : ?>
            <?php
            // Get the page permalink without the website URL
            $permalink = preg_replace('@^(?:https?:\/\/)?(?:www\.)?' . preg_quote(home_url(), '@') . '@i', '', $exited_page->last_page);
            // Get the correct plural form for "visit" based on the count
            $visit_count_text = sprintf(
                _n('%d visit', '%d visits', absint($exited_page->count), 'joes-recent-users-activity'),
                absint($exited_page->count)
            );
            ?>
            <li><a href="<?php echo esc_url($exited_page->last_page); ?>"><?php echo esc_html($permalink); ?></a> (<?php echo esc_html($visit_count_text); ?>)</li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
    </div>

    <br>
     <div id="donate-button-container">
        <div id="donate-button"></div>
        <script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>
        <script>
            PayPal.Donation.Button({
                env: 'production',
                hosted_button_id: 'XQGAWEWDS4DG2',
                image: {
                    src: 'https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif',
                    alt: 'Donate with PayPal button',
                    title: 'PayPal - The safer, easier way to pay online!',
                }
            }).render('#donate-button');
        </script>
    </div>

    <style>
        #joes-recent-activity-table th,
        #joes-recent-activity-table td {
            text-align: left;
        }
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#joes-recent-activity-table').DataTable({
                "order": [[5, "desc"]], // 5 represents the column index for Last Login (0-based)
                "columnDefs": [
                    { "targets": [0, 2], "visible": false }, // Hide User ID and Role columns by default
                    { "orderable": false, "targets": [0, 2] } // Disable sorting for hidden columns
                ]
            });

            // Hide the message if there is data available
            <?php if ($has_data) : ?>
                $('p').hide();
            <?php endif; ?>
        });
    </script>
    <?php
}

function joes_recent_users_activity_admin_menu()
{
    add_menu_page(
        'Joe\'s Recent Users Activity',
        'Recent Activity',
        'manage_options',
        'joes_recent_users_activity',
        'joes_recent_users_activity_display_admin_page',
		'dashicons-groups'
    );
}
add_action('admin_menu', 'joes_recent_users_activity_admin_menu');

function joes_recent_users_activity_log_activity_on_login($user_login, $user)
{
    if ($user && is_a($user, 'WP_User')) {
        $user_id = $user->ID;
        $last_page = joes_recent_users_activity_get_current_url();

        joes_recent_users_activity_log_activity($user_id, $last_page);
    }
}
add_action('wp_login', 'joes_recent_users_activity_log_activity_on_login', 10, 2);

function joes_recent_users_activity_log_page_view()
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $last_page = joes_recent_users_activity_get_current_url();

        joes_recent_users_activity_log_activity($user_id, $last_page);
    }
}
add_action('template_redirect', 'joes_recent_users_activity_log_page_view');

function joes_recent_users_activity_get_current_url()
{
    $page_url = '';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $page_url .= "https://";
    } else {
        $page_url .= "http://";
    }

    $page_url .= sanitize_text_field($_SERVER['HTTP_HOST']);
    $page_url .= sanitize_text_field($_SERVER['REQUEST_URI']);

    return esc_url_raw($page_url); // Sanitize page_url as a raw URL
}
