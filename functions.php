<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('child_theme_configurator_css')):
    function child_theme_configurator_css()
    {
        wp_enqueue_style('chld_thm_cfg_child', trailingslashit(get_stylesheet_directory_uri()) . 'style.css', array('travel-monster-style', 'travel-monster-style', 'travel-monster-elementor'));
        wp_enqueue_script('chld_thm_cfg_child_script', trailingslashit(get_stylesheet_directory_uri()) . 'main.js', array(), time());
        wp_localize_script('chld_thm_cfg_child_script', 'ajaxUrl', array('ajax_url' => admin_url('admin-ajax.php')));
    }
endif;
add_action('wp_enqueue_scripts', 'child_theme_configurator_css', 20);

// END ENQUEUE PARENT ACTION


add_action('wp_ajax_nopriv_submit_traveler_information', 'submit_traveler_information');
add_action('wp_ajax_submit_traveler_information', 'submit_traveler_information');
function submit_traveler_information()
{
    $form_datas = $_POST['form_data'];
    $booking_id = $_POST['booking_id'];
    $outputArray = [];
    foreach ($form_datas as $item) {
        // Skip the nonce
        if ($item['name'] == 'nonce') {
            continue;
        }

        // Extract the parts of the name
        preg_match('/wp_travel_engine_placeorder_setting\[place_order\]\[travelers\]\[([^\]]+)\]\[([^\]]+)\]/', $item['name'], $matches);

        if (count($matches) == 3) {
            $key = $matches[1];
            $index = $matches[2];
            $value = $item['value'];

            // Assign the value to the output array
            $outputArray['place_order']['travelers'][$key][$index] = $value;
        }
    }

    if ($outputArray) {
        $update =  update_post_meta($booking_id, 'wp_travel_engine_placeorder_setting', $outputArray);
        if ($update == true) {

            echo 'successfull';
        }
    }

    wp_die();
}


add_action('save_post_booking', 'cpm_after_chekout_submit');

function cpm_after_chekout_submit($post_id)
{
    if (isset($_POST['wp_travel_engine_nw_bkg_submit'])) {
        $prev_booking_id = $_GET['bookingId'];
        // die(var_dump($prev_booking_id));
        $traveler_info = get_post_meta($prev_booking_id, 'wp_travel_engine_placeorder_setting', true);
        $update =  update_post_meta($post_id, 'wp_travel_engine_placeorder_setting', $traveler_info);
        webhook_trigger_call($traveler_info, $post_id);
    }
}


// Add settings page to the WordPress admin menu
function custom_webhook_settings_page()
{
    add_menu_page(
        'Webhook Integration', // Page title
        'Webhook Settings',    // Menu title
        'manage_options',      // Capability
        'webhook-settings',    // Menu slug
        'render_webhook_settings_page', // Callback function
        'dashicons-admin-generic',      // Icon
        80                             // Position
    );
}
add_action('admin_menu', 'custom_webhook_settings_page');

// Render the settings page
function render_webhook_settings_page()
{
    // Check if the user has submitted the form
    if (isset($_POST['webhook_endpoint'])) {
        // Save the webhook endpoint
        $webhook_endpoint = sanitize_text_field($_POST['webhook_endpoint']);
        update_option('webhook_endpoint', $webhook_endpoint);
        echo '<div class="updated"><p>Webhook Endpoint Saved!</p></div>';
    }

    // Get the current webhook endpoint
    $current_endpoint = get_option('webhook_endpoint', '');
?>
    <div class="wrap">
        <h1>Webhook Integration Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="webhook_endpoint">Webhook Endpoint</label></th>
                    <td>
                        <input type="text" name="webhook_endpoint" id="webhook_endpoint" class="regular-text"
                            value="<?php echo esc_attr($current_endpoint); ?>" required>
                        <p class="description">Enter your Webhook Endpoint URL, Email, or UUID.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Webhook Endpoint'); ?>
        </form>
    </div>
<?php
}

// Function to send JSON data to the webhook
function send_webhook_data($data)
{
    // Retrieve the saved webhook endpoint
    $webhook_endpoint = get_option('webhook_endpoint');
    if (empty($webhook_endpoint)) {
        return; // No endpoint configured, exit
    }
    // die(var_dump($data));
    $returned_data = wp_remote_post($webhook_endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body'    => wp_json_encode($data),
        'timeout' => 120,
    ]);

    // die(var_dump($returned_data));
    // Determine the type of endpoint (URL, Email, or UUID)
    // if (filter_var($webhook_endpoint, FILTER_VALIDATE_URL)) {
    //     die('if');

    //     // Send the data to a URL
    //     wp_remote_post($webhook_endpoint, [
    //         'headers' => [
    //             'Content-Type' => 'application/json',
    //         ],
    //         'body'    => wp_json_encode($data),
    //     ]);
    // } elseif (filter_var($webhook_endpoint, FILTER_VALIDATE_EMAIL)) {
    //     // Send the data via email
    //     die('ifelse');
    //     wp_mail($webhook_endpoint, 'Webhook Data', json_encode($data, JSON_PRETTY_PRINT));
    // } elseif (preg_match('/^[a-f0-9\-]{36}$/', $webhook_endpoint)) {
    //     // Handle UUID format (if a specific API needs to be called, modify here)
    //     // Example: Add prefix or use a specific service
    //     die('ifelse 2');
    //     $url = home_url().'/' . $webhook_endpoint;
    //     wp_remote_post($url, [
    //         'headers' => [
    //             'Content-Type' => 'application/json',
    //         ],
    //         'body'    => wp_json_encode($data),
    //     ]);
    // }
}

// Example hook to trigger the webhook (e.g., when a booking is created)
function webhook_trigger_call($traveler_info, $post_id)
{
    // if (isset($_GET['trigger_webhook']) && current_user_can('manage_options')) {
    // Sample data format
    // Initialize the output structure
    $output = [
        "bookingID" => $post_id,
        "productName" => "Test",
        "productCode" => "LA",
        "departureDateDDMMYYYY" => "16/10/2023",
        "TravellerCount" => count($traveler_info['place_order']['travelers']['title']),
        "travellers" => [],
        "spaces" => "",
    ];

    // Process each traveler
    foreach ($traveler_info['place_order']['travelers']['title'] as $key => $title) {
        $output['travellers'][] = [
            "prefix" => $title,
            "firstName" => $traveler_info['place_order']['travelers']['fname'][$key],
            "lastName" => $traveler_info['place_order']['travelers']['lname'][$key],
            "phoneDaytime" => $traveler_info['place_order']['travelers']['phone'][$key],
            "email" => $traveler_info['place_order']['travelers']['email'][$key],
            "postalAddress" => $traveler_info['place_order']['travelers']['address'][$key],
            "postalCountry" => $traveler_info['place_order']['travelers']['country'][$key],
            "customer-note" => ""
        ];
    }

    $data = json_encode($output, JSON_PRETTY_PRINT);

    // Send the data
    send_webhook_data($data);
}
