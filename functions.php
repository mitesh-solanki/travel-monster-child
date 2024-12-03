<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'travel-monster-style','travel-monster-style','travel-monster-elementor' ) );
        wp_enqueue_script( 'chld_thm_cfg_child_script', trailingslashit( get_stylesheet_directory_uri() ) . 'main.js',array(),time());
        wp_localize_script( 'chld_thm_cfg_child_script', 'ajaxUrl',array( 'ajax_url' => admin_url( 'admin-ajax.php' )));
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 20 );

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
   
    }

}