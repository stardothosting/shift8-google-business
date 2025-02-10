<?php
/**
 * Plugin Name: Shift8 Google Business Updater
 * Description: Automatically sync and update your store hours daily from Google Maps to keep customers informed in real time.
 * Version: 1.3
 * Author: Shift8web
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'shift8_store_hours_updater_activate');
register_deactivation_hook(__FILE__, 'shift8_store_hours_updater_deactivate');

function shift8_store_hours_updater_activate()
{
    if (!wp_next_scheduled('shift8_store_hours_updater_cron_hook')) {
        wp_schedule_event(time(), 'once_per_day', 'shift8_store_hours_updater_cron_hook');
    }
}

function shift8_store_hours_updater_deactivate()
{
    wp_clear_scheduled_hook('shift8_store_hours_updater_cron_hook');
}

add_filter('cron_schedules', 'shift8_store_hours_updater_cron_schedule');

function shift8_store_hours_updater_cron_schedule($schedules)
{
    $schedules['once_per_day'] = array(
        'interval' => 86400,
        'display'  => __('Once per Day')
    );
    return $schedules;
}

add_action('shift8_store_hours_updater_cron_hook', 'shift8_store_hours_updater_update_store_hours');

function shift8_store_hours_updater_update_store_hours()
{
    global $wpdb;

    $api_key = defined('SHIFT8_GOOGLE_API_KEY') ? SHIFT8_GOOGLE_API_KEY : null;
    $results = $wpdb->get_results("SELECT id, title, custom FROM wp_asl_stores");

    if (empty($results)) {
        shift8_log_message("No stores found in the database.");
        return;
    }

    foreach ($results as $store) {
        $store_name = sanitize_text_field($store->title);
        shift8_log_message("Processing store: {$store_name} (ID: {$store->id})");

        $custom_data = json_decode($store->custom, true);

        if (isset($custom_data['google_place_id'])) {
            $place_id = sanitize_text_field($custom_data['google_place_id']);
            $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&key=$api_key";

            $response = shift8_make_google_api_request($url);

            if ($response && isset($response['result'])) {
                $weekday_text = $response['result']['opening_hours']['periods'] ?? [];
                $website = sanitize_text_field($response['result']['url'] ?? '');
                $international_phone_number = sanitize_text_field($response['result']['international_phone_number'] ?? '');

                $formatted_hours_json = json_encode(shift8_format_opening_hours($weekday_text));

                $wpdb->update(
                    'wp_asl_stores',
                    [
                        'open_hours' => $formatted_hours_json,
                        'website' => $website,
                        'phone' => $international_phone_number
                    ],
                    ['id' => $store->id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );

                shift8_log_message("Store updated: {$store_name} (ID: {$store->id})");
            } else {
                shift8_log_message("No valid data found for Place ID: $place_id");
            }
        } else {
            shift8_log_message("No google_place_id found for store ID: {$store->id}");
        }
    }
}

function shift8_make_google_api_request($url)
{
    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            shift8_log_message('cURL Error: ' . curl_error($ch));
        } else {
            curl_close($ch);
            return json_decode($response, true);
        }

        curl_close($ch);
        sleep(1); // Delay before retry
    }

    return null;
}

function shift8_format_opening_hours($periods)
{
    $day_mapping = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    $formatted_hours = [
        'mon' => [],
        'tue' => [],
        'wed' => [],
        'thu' => [],
        'fri' => [],
        'sat' => [],
        'sun' => []
    ];

    foreach ($periods as $period) {
        $open_time = shift8_format_time_to_12_hour($period['open']['time']);
        $close_time = shift8_format_time_to_12_hour($period['close']['time']);

        $day_key = $day_mapping[$period['open']['day']];

        $formatted_hours[$day_key][] = "$open_time - $close_time";
    }

    foreach ($formatted_hours as $day => $hours) {
        if (empty($hours)) {
            $formatted_hours[$day] = '0';
        }
    }

    return $formatted_hours;
}

function shift8_format_time_to_12_hour($time24)
{
    $hours = (int)($time24 / 100);
    $minutes = $time24 % 100;

    $ampm = $hours >= 12 ? 'PM' : 'AM';
    $formatted_hours = $hours % 12 ?: 12;
    $formatted_minutes = $minutes < 10 ? '0' . $minutes : $minutes;

    return "$formatted_hours:$formatted_minutes $ampm";
}

function shift8_log_message($message)
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
    } else {
        error_log($message);
    }
}

// WP-CLI Integration
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('shift8_store_hours_updater', 'shift8_store_hours_updater_update_store_hours');
}
