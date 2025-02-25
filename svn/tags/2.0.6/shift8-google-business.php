<?php
/**
 * Plugin Name: Shift8 for Google Business
 * Description: Automatically sync and update your store hours and data daily from Google Maps to keep customers informed in real time. Now stores data in a custom post type and uses a settings page for configuration.
 * Version: 2.0.6
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(plugin_dir_path(__FILE__).'components/enqueuing.php' );
require_once(plugin_dir_path(__FILE__).'components/settings.php' );
require_once(plugin_dir_path(__FILE__).'components/functions.php' );


// This function is shared across multiple Shift8 plugins to provide a common settings area.
if (!function_exists('shift8_main_page')) {
    function shift8_main_page() {
    ?>
    <div class="wrap">
    <h2>Shift8 Plugins</h2>
    Shift8 is a Toronto based web development and design company. We specialize in Wordpress development and love to contribute back to the Wordpress community whenever we can! You can see more about us by visiting <a href="https://www.shift8web.ca" target="_new">our website</a>.
    </div>
    <?php
    }
}



/**
 * Create a settings page to store the Google API key
 * and the list of Place IDs to poll.
 */

// create custom plugin settings menu
add_action('admin_menu', 'shift8_business_create_menu');
function shift8_business_create_menu() {
        //create new top-level menu
        if ( empty ( $GLOBALS['admin_page_hooks']['shift8-settings'] ) ) {
                add_menu_page('Shift8 Settings', 'Shift8', 'administrator', 'shift8-settings', 'shift8_main_page' , 'dashicons-shift8' );
        }
        add_submenu_page(
            'shift8-settings', // Parent menu
            esc_html__('Shift8 for Google Business Settings', 'shift8-google-business'),
            esc_html__('Google Business', 'shift8-google-business'),
            'manage_options',
            'shift8_google_business_settings',
            'shift8_business_settings_callback'
        );
        //call register settings function
        add_action('admin_menu', 'shift8_google_business_register_settings');
}

// Register admin settings
function shift8_google_business_register_settings() {
    // Register settings
    register_setting('shift8_google_business_group', 'shift8_google_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('shift8_google_business_group', 'shift8_google_place_ids', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => ''
    ));

    // Add Section
    add_settings_section(
        'shift8_google_business_main',
        esc_html__('Google Business Settings', 'shift8-google-business'),
        'shift8_google_business_section_callback',
        'shift8_google_business_settings'
    );

    // API Key Field
    add_settings_field(
        'shift8_google_api_key',
        esc_html__('Google API Key', 'shift8-google-business'),
        'shift8_google_api_key_callback',
        'shift8_google_business_settings',
        'shift8_google_business_main'
    );

    // Place IDs Field
    add_settings_field(
        'shift8_google_place_ids',
        esc_html__('Google Places IDs (one per line)', 'shift8-google-business'),
        'shift8_google_place_ids_callback',
        'shift8_google_business_settings',
        'shift8_google_business_main'
    );
}

function shift8_business_settings_callback()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save the settings if the form is submitted
    if (isset($_POST['shift8_business_settings_submitted']) && check_admin_referer('shift8_business_settings_form')) {
        $submitted = sanitize_text_field($_POST['shift8_business_settings_submitted']);
        $api_key  = sanitize_text_field($_POST['shift8_google_api_key']);
        $place_ids = sanitize_textarea_field($_POST['shift8_google_place_ids']);

        update_option('shift8_google_api_key', $api_key);
        update_option('shift8_google_place_ids', $place_ids);

        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'shift8-google-business') . '</p></div>';
    }

    $stored_api_key  = get_option('shift8_google_api_key', '');
    $stored_place_ids = get_option('shift8_google_place_ids', '');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Shift8 for Google Business Settings', 'shift8-google-business'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('shift8_business_settings_form'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="shift8_google_api_key"><?php esc_html_e('Google API Key', 'shift8-google-business'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="shift8_google_api_key" name="shift8_google_api_key" value="<?php echo esc_attr($stored_api_key); ?>" size="50" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="shift8_google_place_ids"><?php esc_html_e('Google Places IDs (one per line)', 'shift8-google-business'); ?></label>
                    </th>
                    <td>
                        <textarea id="shift8_google_place_ids" name="shift8_google_place_ids" rows="5" cols="50"><?php echo esc_textarea($stored_place_ids); ?></textarea>
                        <p class="description"><?php esc_html_e('Enter multiple Place IDs, each on a new line.', 'shift8-google-business'); ?></p>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="shift8_business_settings_submitted" value="1" />
            <?php submit_button(esc_html__('Save Settings', 'shift8-google-business')); ?>
        </form>
        <h2><?php esc_html_e('Test API Key', 'shift8-google-business'); ?></h2>
        <p><?php esc_html_e('Click the button below to test your Google API key with the first Place ID in your list.', 'shift8-google-business'); ?></p>
        <button id="shift8_business_test_api" class="button button-primary"><?php esc_html_e('Test API', 'shift8-google-business'); ?></button>
        <div id="shift8_api_test_result" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; display: none;"></div>
    </div>
    <?php
}
