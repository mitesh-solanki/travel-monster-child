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

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array(  ) );
		wp_enqueue_style('chld_thm_cfg_child', trailingslashit(get_stylesheet_directory_uri()) . 'style.css', array('travel-monster-style', 'travel-monster-style', 'travel-monster-elementor'));
		wp_enqueue_script('chld_thm_cfg_child_script', trailingslashit(get_stylesheet_directory_uri()) . 'main.js', array(), time());
        wp_localize_script('chld_thm_cfg_child_script', 'ajaxUrl', array('ajax_url' => admin_url('admin-ajax.php')));
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION


add_action('wp_ajax_nopriv_submit_traveler_information', 'submit_traveler_information');
add_action('wp_ajax_submit_traveler_information', 'submit_traveler_information');
function submit_traveler_information()
{
    $form_datas = $_POST['form_data'];
    $booking_id = $_POST['booking_id'];
    $outputArray = [];
    foreach ($form_datas as $item) {
        if ($item['name'] == 'nonce') {
            continue;
        }

        // Extract the parts of the name
        preg_match('/wp_travel_engine_placeorder_setting\[place_order\]\[travelers\]\[([^\]]+)\]\[([^\]]+)\]/', $item['name'], $matches);

        if (count($matches) == 3) {
            $key = $matches[1];
            $index = $matches[2];
            $value = $item['value'];

            $outputArray['place_order']['travelers'][$key][$index] = $value;
        }

        preg_match('/wp_travel_engine_placeorder_setting\[place_order\]\[relation\]\[([^\]]+)\]\[([^\]]+)\]/', $item['name'], $matches);

        if (count($matches) == 3) {
            $key = $matches[1];
            $index = $matches[2];
            $value = $item['value'];

            $outputArray['place_order']['relation'][$key][$index] = $value;
        }
    }

    if ($outputArray) {
        $update =  update_post_meta($booking_id, 'wp_travel_engine_placeorder_setting', $outputArray);
        update_post_meta($booking_id, 'traveller_info', $outputArray);
        if ($update == true) {

            echo 'successfull';
        }
    }

    wp_die();
}


add_action('save_post_booking', 'cpm_after_chekout_submit');

function cpm_after_chekout_submit($post_id)
{
    //if (isset($_POST['wp_travel_engine_nw_bkg_submit'])) {
        $prev_booking_id = $_GET['bookingId'];
        $traveler_info = get_post_meta($prev_booking_id, 'wp_travel_engine_placeorder_setting', true);
        $update =  update_post_meta($post_id, 'wp_travel_engine_placeorder_setting', $traveler_info);
        $traveller_details = get_post_meta($prev_booking_id, 'traveller_info', true);
        update_post_meta($post_id, 'traveller_info', $traveller_details);
        webhook_trigger_call($traveler_info, $prev_booking_id, $post_id);
    //}
}


// Add settings page to the WordPress admin menu
function custom_webhook_settings_page()
{
    add_submenu_page(
        'edit.php?post_type=booking',
        'Webhook Integration', // Page title
        'Webhook Settings',    // Menu title
        'manage_options',      // Capability
        'webhook-settings',    // Menu slug
        'render_webhook_settings_page', // Callback function
    );
}
add_action('admin_menu', 'custom_webhook_settings_page');

// Render the settings page
function render_webhook_settings_page()
{
    if (isset($_POST['webhook_endpoint'])) {
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
                        <p class="description">Enter your Webhook Endpoint URL.</p>
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
    $webhook_endpoint = get_option('webhook_endpoint');
    if (empty($webhook_endpoint)) {
        return;
    }
    // die(print_r(wp_json_encode($data)));
    $returned_data = wp_remote_post($webhook_endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body'    => wp_json_encode($data),
        'timeout' => 120,
    ]);
    // Determine the type of endpoint (URL, Email, or UUID)
    // if (filter_var($webhook_endpoint, FILTER_VALIDATE_URL)) {

    //     // Send the data to a URL
    //     wp_remote_post($webhook_endpoint, [
    //         'headers' => [
    //             'Content-Type' => 'application/json',
    //         ],
    //         'body'    => wp_json_encode($data),
    //     ]);
    // } elseif (filter_var($webhook_endpoint, FILTER_VALIDATE_EMAIL)) {
    //     // Send the data via email
    //     wp_mail($webhook_endpoint, 'Webhook Data', json_encode($data, JSON_PRETTY_PRINT));
    // } elseif (preg_match('/^[a-f0-9\-]{36}$/', $webhook_endpoint)) {
    //     // Handle UUID format (if a specific API needs to be called, modify here)
    //     // Example: Add prefix or use a specific service
    //     $url = home_url().'/' . $webhook_endpoint;
    //     wp_remote_post($url, [
    //         'headers' => [
    //             'Content-Type' => 'application/json',
    //         ],
    //         'body'    => wp_json_encode($data),
    //     ]);
    // }
}

// This function is called when 
function webhook_trigger_call($traveler_info, $prev_booking_id, $post_id)
{
    $productName = '';
    $formattedDate = '';
    $id = '';
    // die(print_r($traveler_info));
    $package_info = get_post_meta($post_id, 'order_trips', true);
    foreach ($package_info as $key => $details) {
        $productName = $details['package_name'];
        $id = $details['ID'];
        $date = $details['datetime'];
        $formattedDate = date('d/m/Y', strtotime($date)); // Convert to DD/MM/YYYY format
    }
    $output = [
        "bookingID" => $post_id,
        "productName" => $productName,
        "productCode" => "LA",
        "departureDateDDMMYYYY" => $formattedDate,
        "DepartureCode" => "",
        "TravellerCount" => count($traveler_info['place_order']['travelers']['fname']),
        "travellers" => [],
        "emergencyContact" => [
            "firstName" => $traveler_info['place_order']['relation']['fname'][1],
            "lastName" => $traveler_info['place_order']['relation']['fname'][1],
            "primaryPhoneNumber" => $traveler_info['place_order']['relation']['phone'][1],
            "primaryPhoneNumberType" => "",
            "primaryPhoneNumberCountry" => "",
            "alternatePhoneNumber" => "",
            "alternatePhoneNumberType" => "",
            "alternatePhoneNumberCountry" => "",
            "relationship" => $traveler_info['place_order']['relation']['relation'][1],
            "email" => "",
        ],
        "howDidYouHearAboutUs" => $traveler_info['place_order']['travelers']['hear_about'][1],
    ];

    foreach ($traveler_info['place_order']['travelers']['fname'] as $key => $title) {
        $output['travellers'][] = [
            "firstName" => $traveler_info['place_order']['travelers']['fname'][$key],
            "middleName" => "",
            "lastName" => $traveler_info['place_order']['travelers']['lname'][$key],
            "dobDDMMYYYY" => date('d/m/Y', strtotime($traveler_info['place_order']['travelers']['dob'][$key])),
            "ageAtDepartureDate" => 66,
            "phoneDaytime" => $traveler_info['place_order']['travelers']['phone'][$key],
            "email" => $traveler_info['place_order']['travelers']['email'][$key],
            "postalAddress" => $traveler_info['place_order']['travelers']['address'][$key],
            "postalCity" => "Auckland",
            "postalState" => "",
            "postalCode" => $traveler_info['place_order']['travelers']['postcode'][$key],
            "postalCountry" => $traveler_info['place_order']['travelers']['country'][$key],
            "height" => $traveler_info['place_order']['travelers']['height'][$key],
            "weight" => 66,
            "occupation" => "Retired",
            "preExistingMedicalConditions" => 0,
            "preExistingMedicalConditionsDetail" => "",
            "specialDietaryRequirements" => 0,
            "specialDietaryRequirementsDetail" => "",
            "bikeRental" => $traveler_info['place_order']['travelers']['bike'][$key],
            "cyclingSkillLevel" => "Reasonable - cycle occasionally for up to 1hr",
        ];
    }

    $data = json_encode($output, );
    send_webhook_data($data);
}

/**
 * Filter traveler fields based on user roles
 * 
 * @param array $fields Array of traveler fields
 * @return array Modified array of fields
 */
function os_restrict_traveller_fields($fields, $traveller_index = 1, $one_Day_trip = false) {
    if ( $one_Day_trip ) {        
        // Define restricted fields for different roles
        $common_fields = array(
            'traveller_title',
            'traveller_passport_number', 
            'traveller_address',
            'traveller_city',
            'traveller_country',
            'traveller_postcode',
            'traveller_dob',
        );

        $specific_fields = array(
            'customer_note',
            'how_did_you_hear_about_us',
            'traveller_email',
            'traveller_phone',
        );

        foreach ($common_fields as $field) {
            unset($fields[$field]);
        }

        if ($traveller_index > 1) {
            // Remove restricted fields
            foreach ($specific_fields as $field) {
                unset($fields[$field]);
            }
        }
    }

    return $fields;
}
add_filter('os_traveller_fields', 'os_restrict_traveller_fields', 99, 3);

add_action('wp_travel_engine_booking_fields_display', 'os_restrict_traveller_fields', 99, 3);
/**
 * Filter emergency contact fields based on user roles
 * 
 * @param array $fields Array of emergency contact fields
 * @return array Modified array of fields
 */ 

function os_restrict_emergency_contact_fields($fields, $traveller_index = 1, $one_Day_trip = false) {
    
    if ( $one_Day_trip ) {
    
        $common_fields = array(
            'traveller_emergency_title',   
        );

        $specific_fields = array(
            'traveller_emergency_first_name',
            'traveller_emergency_last_name', 
            'traveller_emergency_phone',
            'traveller_emergency_relation',
        );

        foreach ($common_fields as $field) {
            unset($fields[$field]);
        }

        if ($traveller_index > 1) {
            // Remove restricted fields
            foreach ($specific_fields as $field) {
                unset($fields[$field]);
            }
        }
    }
    
    return $fields;
}
add_filter('os_emergency_contact_fields', 'os_restrict_emergency_contact_fields', 10, 3);


/**
 * Fire a custom event after a booking is completed in WP Travel Engine.
 */
add_action( 'wp_travel_engine_after_booking_process_completed', 'custom_event_on_booking', 10 );
function custom_event_on_booking( $booking_id ) {
    // Retrieve booking details
    $booking_details = get_post_meta( $booking_id, 'wp_travel_engine_booking_setting', true );
    $trip_id = get_post_meta( $booking_id, 'wp_travel_engine_booking_trip_id', true );
    $customer_email = get_post_meta( $booking_id, 'wp_travel_engine_booking_contact_email', true );
    $traveler_info = get_post_meta( $booking_id, 'wp_travel_engine_placeorder_setting', true );

    // Prepare data to pass to the custom event
    $event_data = array(
        'booking_id'    => $booking_id,
        'payment_id'    => $payment_id,
        'trip_id'       => $trip_id,
        'customer_email' => $customer_email,
        'booking_details' => $booking_details,
    );

    error_log( 'Custom booking event fired for Booking ID: ' . $booking_id );
    webhook_trigger_call($traveler_info, $booking_id, $booking_id);
}

add_action( 'wp_travel_engine_after_traveller_information_save', 'wp_traveller_information_save' );

function wp_traveller_information_save( $booking_id ) {
    update_post_meta($booking_id, 'wp_travel_engine_placeorder_setting', $_POST['wp_travel_engine_placeorder_setting']);
    update_post_meta($booking_id, 'traveller_info', $_POST['wp_travel_engine_placeorder_setting']);
}