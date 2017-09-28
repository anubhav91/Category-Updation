<?php
/*
  Plugin Name: Category Updation
  Version: 1.0
  Description: This plugin is used to fetch category data from an REST API.
  Author: Abstain Solutions
 */


/*
 * A custom cron schedule for 30 minutes is set for this cron
 */
add_filter('cron_schedules', 'wp_add_30_min_schedule');

function wp_add_30_min_schedule($schedules) {
    $schedules['30_min'] = array(
        'interval' => (60 * 30),
        'display' => __('Thirty Minutes')
    );

    return $schedules;
}

/*
 * On Plug-in activation cron will be scheduled to get category data
 */
register_activation_hook(__FILE__, 'category_update_activation');

function category_update_activation() {
    // set cron.
    if (!wp_next_scheduled('wp_fetch_categories')) {
        wp_schedule_event(time(), '30_min', 'wp_fetch_categories');
    }
}

/*
 * On deactivating the plug-in scheduled cron will be unhooked/deleted
 */
register_deactivation_hook(__FILE__, 'category_update_deactivation');

function category_update_deactivation() {
    wp_clear_scheduled_hook('wp_fetch_categories');
}

/*
 * Adding hook to fetch_categories function
 */
add_action('wp_fetch_categories', 'fetch_categories');

/*
 *  Function to fetch the categories data from REST API
 */

function fetch_categories() {
    //API URL
    $url = get_option('category_update_api_url');

    if (empty($url)) {
        wp_send_json_error('URL missing.');
    }

    $json = wp_remote_get($url);

    $id_array_map = [];
    $data = [];

    if (isset($json['body'])) {
        $data = json_decode($json['body'], true);
    }

    if (isset($data['categories'])) {
        foreach ($data['categories'] as $category) {

            // If 'id' or 'name' fields are not set for any record, then skip it
            if (!isset($category['id']) || !isset($category['name'])) {
                continue;
            }

            $name = $category['name'];
            $slug = '';
            $description = '';
            $parent_id = -1;

            if (isset($category['slug'])) {
                $slug = $category['slug'];
            } else {
                $slug = strtolower($name);
                $slug = str_replace(' ', '-', $slug);
            }

            if (isset($category['description'])) {
                $description = $category['description'];
            }

            if (!empty($category['parent_id']) && isset($id_array_map[$category['parent_id']])) {
                $parent_id = $id_array_map[$category['parent_id']];
            }

            $args = [
                'taxonomy' => 'category',
                'tag-name' => $name,
                'slug' => $slug,
                'parent' => $parent_id,
                'description' => $description
            ];

            $result = wp_insert_term($name, $args['taxonomy'], $args);

            if (is_wp_error($result) && isset($result->error_data['term_exists'])) {
                $id_array_map[$category['id']] = $result->error_data['term_exists'];
            } else if (isset($result['term_id'])) {
                $id_array_map[$category['id']] = $result['term_id'];
            }
        }
        wp_send_json_success('Categories Updated');
    }
    wp_send_json_error('Categories not updated');
}

/*
 *  Adding update button in Settings->General form page
 */
add_action('init', 'category_updation_menu');

function category_updation_menu() {
    add_filter('admin_init', 'register_fields');
}

function register_fields() {
    register_setting('general', 'update_categories', 'esc_attr');
    add_settings_field('zz_update_cat', '<label for="update_categories">' . __('Update categories now?', 'update_categories') . '</label>', 'fields_html', 'general');
}

function fields_html() {
    echo '<input type="button" onclick="fetchCategories()" name="update_category" name="Update" value="Update" />';
    echo '<script>
        function fetchCategories() {
            var data = {
                "action": "fetch_categories_action"
            };
            jQuery.get(ajaxurl + "?action=fetch_categories_action", function(response) {
                var res_class = "";
                if (response.success) {
                    res_class = "updated";
                } else {
                    res_class = "error";
                }
                jQuery(".categories-error").remove();
                msg = "<div id=\"setting-error-categories_updated\" class=\""+res_class+" categories-error notice is-dismissible\">" 
                    + "<p>"
                    + "<strong>"+response.data+"</strong>"
                    + "</p>"
                    + "</div>";
                jQuery( ".wrap h1" ).after( msg );
                jQuery("html, body").animate({ scrollTop: 0 }, "slow");
            });
        }
    </script>';
}

/*
 * Adding hook for fetch_categories() function with ajax call
 */
add_action('wp_ajax_fetch_categories_action', 'fetch_categories');
add_action('wp_ajax_nopriv_fetch_categories_action', 'fetch_categories');


add_action('admin_menu', 'category_admin_func');

function category_admin_func() {
    add_menu_page('Category Add Url', 'Category Add Url', 'manage_options', 'category_add_url', 'category_update_method');
}

function category_update_method() {
    $plugin_main_url = site_url() . '/wp-admin/admin.php?page=category_add_url';
    $existing_val = get_option('category_update_api_url');
    
    if (isset($_POST['submit'])) {
        $url = $_POST['api_url'];

        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL) !== FALSE) {
            if (empty($existing_val)) {
                add_option('category_update_api_url', $url);
            } else {
                update_option('category_update_api_url', $url);
            }
            echo '<script>alert("Data has been submitted successfully.");window.location="' . $plugin_main_url . '";</script>';
        } else {
            echo '<script>alert("Kindly provide correct API Url.");window.location="' . $plugin_main_url . '";</script>';
        }
    }
    ?>
    <h3>Category Updation API URL</h3>
    <form method="post" action="">
        <table>
            <tbody>
                <tr>
                    <td>API URL</td>
                    <td> <input required placeholder="URL" value="<?php echo $existing_val; ?>" type="text" name="api_url"></td>
                </tr>

                <tr>
                    <td><input type="submit" value="Submit" name="submit"></td>
                </tr>
            </tbody>
        </table>
    </form>
    <?php
}
