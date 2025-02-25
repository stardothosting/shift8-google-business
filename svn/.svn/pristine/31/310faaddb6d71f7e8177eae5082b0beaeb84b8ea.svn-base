<?php
/**
 * Shift8 for Google Business Settings
 *
 * Declaration of plugin settings used throughout
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

/**
 * Register the Custom Post Type
 * This will hold all business data fetched from Google.
 */
add_action('init', 'shift8_business_post_type');
function shift8_business_post_type()
{
    $labels = array(
        'name'               => esc_html__('Business Profiles', 'shift8-google-business'),
        'singular_name'      => esc_html__('Business Profile', 'shift8-google-business'),
        'menu_name'          => esc_html__('Business Profiles', 'shift8-google-business'),
        'name_admin_bar'     => esc_html__('Business Profile', 'shift8-google-business'),
        'add_new'            => esc_html__('Add New', 'shift8-google-business'),
        'add_new_item'       => esc_html__('Add New Business Profile', 'shift8-google-business'),
        'new_item'           => esc_html__('New Business Profile', 'shift8-google-business'),
        'edit_item'          => esc_html__('Edit Business Profile', 'shift8-google-business'),
        'view_item'          => esc_html__('View Business Profile', 'shift8-google-business'),
        'all_items'          => esc_html__('All Business Profiles', 'shift8-google-business'),
        'search_items'       => esc_html__('Search Business Profiles', 'shift8-google-business'),
        'parent_item_colon'  => esc_html__('Parent Business Profiles:', 'shift8-google-business'),
        'not_found'          => esc_html__('No Business Profiles found.', 'shift8-google-business'),
        'not_found_in_trash' => esc_html__('No Business Profiles found in Trash.', 'shift8-google-business')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => array('slug' => 'shift8_business'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-store',
        'supports'           => array('title', 'editor'),
    );

    register_post_type('shift8_business', $args);
}

/**
 * Register activation/deactivation hooks to set up cron.
 */
register_activation_hook(__FILE__, 'shift8_business_updater_activate');
register_deactivation_hook(__FILE__, 'shift8_business_updater_deactivate');

function shift8_business_updater_activate()
{
    // Register the custom post type on activation (in case it's not already registered).
    shift8_business_post_type();

    // Schedule the event if not already scheduled
    if (!wp_next_scheduled('shift8_business_updater_cron_hook')) {
        wp_schedule_event(time(), 'once_per_day', 'shift8_business_updater_cron_hook');
    }
}

function shift8_business_updater_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('shift8_business_updater_cron_hook');
}

/**
 * Add a custom cron schedule and hook our function into it.
 */
add_filter('cron_schedules', 'shift8_business_updater_cron_schedule');
function shift8_business_updater_cron_schedule($schedules)
{
    if (!isset($schedules['once_per_day'])) {
        $schedules['once_per_day'] = array(
            'interval' => 86400,
            'display'  => esc_html__('Once per Day', 'shift8-google-business')
        );
    }
    return $schedules;
}

add_action('shift8_business_updater_cron_hook', 'shift8_business_update');
