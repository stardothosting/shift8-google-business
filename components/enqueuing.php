<?php
/**
 * Shift8 Enqueuing Files
 *
 * Function to load styles and front end scripts
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

// Register admin scripts for custom fields
function load_shift8_google_business_wp_admin_style() {
        // admin always last
        wp_enqueue_style( 'shift8_google_business_css', plugin_dir_url(dirname(__FILE__)) . 'css/shift8_google_business_admin.css', array(), '1.1.5' ); 
}
add_action( 'admin_enqueue_scripts', 'load_shift8_google_business_wp_admin_style' );
