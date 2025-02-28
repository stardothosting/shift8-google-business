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

function shift8_business_admin_scripts($hook) {
    // Enqueue admin script
    wp_enqueue_script( 'shift8_business_admin', plugin_dir_url(dirname(__FILE__)) . 'js/shift8-business-admin.js', array(), '2.1.0' );

    // Localize script for AJAX
    wp_localize_script('shift8_business_admin', 'shift8_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('shift8_business_test_api'),
    ]);

    // Enqueue CSS
    wp_enqueue_style(
        'shift8_business_css',
        plugin_dir_url(dirname(__FILE__)) . 'css/shift8_business_admin.css',
        [],
        '2.0.2'
    );
}

add_action('admin_enqueue_scripts', 'shift8_business_admin_scripts');
