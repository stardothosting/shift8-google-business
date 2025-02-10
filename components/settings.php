<?php
/**
 * Shift8 Google Business Settings
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
        'name'               => __('Business Profiles', 'shift8'),
        'singular_name'      => __('Business Profile', 'shift8'),
        'menu_name'          => __('Business Profiles', 'shift8'),
        'name_admin_bar'     => __('Business Profile', 'shift8'),
        'add_new'            => __('Add New', 'shift8'),
        'add_new_item'       => __('Add New Business Profile', 'shift8'),
        'new_item'           => __('New Business Profile', 'shift8'),
        'edit_item'          => __('Edit Business Profile', 'shift8'),
        'view_item'          => __('View Business Profile', 'shift8'),
        'all_items'          => __('All Business Profiles', 'shift8'),
        'search_items'       => __('Search Business Profiles', 'shift8'),
        'parent_item_colon'  => __('Parent Business Profiles:', 'shift8'),
        'not_found'          => __('No Business Profiles found.', 'shift8'),
        'not_found_in_trash' => __('No Business Profiles found in Trash.', 'shift8')
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
register_activation_hook(__FILE__, 'shift8_store_hours_updater_activate');
register_deactivation_hook(__FILE__, 'shift8_store_hours_updater_deactivate');

function shift8_store_hours_updater_activate()
{
    // Register the custom post type on activation (in case it's not already registered).
    shift8_business_post_type();
    //flush_rewrite_rules();

    // Schedule the event if not already scheduled
    if (!wp_next_scheduled('shift8_store_hours_updater_cron_hook')) {
        wp_schedule_event(time(), 'once_per_day', 'shift8_store_hours_updater_cron_hook');
    }
}

function shift8_store_hours_updater_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('shift8_store_hours_updater_cron_hook');
    //flush_rewrite_rules();
}

/**
 * Add a custom cron schedule and hook our function into it.
 */
add_filter('cron_schedules', 'shift8_store_hours_updater_cron_schedule');
function shift8_store_hours_updater_cron_schedule($schedules)
{
    if (!isset($schedules['once_per_day'])) {
        $schedules['once_per_day'] = array(
            'interval' => 86400,
            'display'  => __('Once per Day')
        );
    }
    return $schedules;
}

add_action('shift8_store_hours_updater_cron_hook', 'shift8_store_hours_updater_update_store_hours');