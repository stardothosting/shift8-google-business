<?php
/**
 * Shift8 Google Business Main Functions
 *
 * Collection of functions used throughout the operation of the plugin
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

/**
 * The main function that polls Google API for each Place ID,
 * updates existing business posts or creates new ones.
 */
function shift8_store_hours_updater_update_store_hours()
{
    $api_key = get_option('shift8_google_api_key', '');
    $place_ids_raw = get_option('shift8_google_place_ids', '');

    // Bail if no API key or no place IDs
    if (empty($api_key) || empty($place_ids_raw)) {
        shift8_log_message("Shift8 GMB Updater: No API key or Place IDs found in settings.");
        return;
    }

    $place_ids = array_filter(array_map('trim', explode("\n", $place_ids_raw)));

    foreach ($place_ids as $place_id) {
        // Build the request URL
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($place_id) . "&key=" . urlencode($api_key);
        shift8_log_message("Polling Place ID: {$place_id}");

        $response = shift8_make_google_api_request($url);

        if (!$response || !isset($response['result'])) {
            shift8_log_message("Invalid response for Place ID: {$place_id}");
            continue;
        }

        // Dump everything we have for now in the post content
        $business_data = print_r($response['result'], true);

        // 5. Check if a post for this Place ID exists. If yes, update it. If not, create a new one.
        $existing_posts = get_posts(array(
            'post_type'  => 'shift8_business',
            'meta_key'   => 'shift8_google_place_id',
            'meta_value' => $place_id,
            'numberposts' => 1
        ));

        if (!empty($existing_posts)) {
            // Update the existing post
            $post_id = $existing_posts[0]->ID;
            wp_update_post(array(
                'ID'           => $post_id,
                'post_content' => $business_data,
            ));

            shift8_log_message("Updated existing post (ID: {$post_id}) for Place ID: {$place_id}");
        } else {
            // Create a new post
            $post_id = wp_insert_post(array(
                'post_title'   => sanitize_text_field($response['result']['name'] ?? 'Untitled Business'),
                'post_content' => $business_data,
                'post_type'    => 'shift8_business',
                'post_status'  => 'publish'
            ));

            // Store the place_id as post meta
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, 'shift8_google_place_id', $place_id);
                shift8_log_message("Created new post (ID: {$post_id}) for Place ID: {$place_id}");
            }
        }
    }
}

/**
 * Helper function to make the Google API Request with retries.
 */
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

/**
 * Logging helper.
 */
function shift8_log_message($message)
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
    } else {
        error_log("[Shift8 GMB Updater] " . $message);
    }
}

// WP-CLI Integration
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('shift8_store_hours_updater', 'shift8_store_hours_updater_update_store_hours');
}