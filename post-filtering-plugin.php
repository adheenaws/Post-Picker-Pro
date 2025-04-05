<?php
/*
Plugin Name: Post Picker
Description: A plugin to filter posts by tags, custom tag, and produce type.
Version: 1.0
Author: Weamse
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



/**
 * Check license against WHMCS
 * 
 * @param string $license_key License key to validate
 * @param string $local_key Local key for caching (optional)
 * @return array|bool License data if valid, false if invalid
 */
function pfp_check_license($license_key, $local_key = '') {
    if (!pfp_validate_license_format($license_key)) {
        error_log("[Post Picker] License validation failed: Invalid license key format");
        return array('status' => 'error', 'message' => 'Invalid license key format');
    }

    $whmcs_url = 'https://whmcsdev.weamse.dev/';
    $licensing_secret_key = 'test1234'; 
    $whmcs_username = 'wsteam';
    $whmcs_password = 'ws@20#hc24';
    $local_key = !empty($local_key) ? $local_key : get_option('pfp_local_key', '');

    error_log("[Post Picker] Sending license check request for key: " . substr($license_key, 0, 6) . "...");
    error_log("[Post Picker] Using local key: " . (!empty($local_key) ? "Yes" : "No"));

    try {
        $args = [
            'body' => [
                'licensekey' => $license_key,
                'domain' => $_SERVER['SERVER_NAME'],
                'ip' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'],
                'dir' => dirname(__FILE__),
                'check_token' => time() . md5(mt_rand(1000000000, 9999999999) . $license_key),
                'version' => '1.0',
                'localkey' => $local_key ?: '',
                'secret' => $licensing_secret_key
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($whmcs_username . ':' . $whmcs_password),
                'Accept' => 'application/xml'
            ],
            'timeout' => 30,
            'sslverify' => false // Temporarily disable for testing
        ];

        error_log("License check request: " . print_r($args, true));

        $response = wp_remote_post($whmcs_url . 'modules/servers/licensing/verify.php', $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("[Post Picker] WHMCS connection error: " . $error_message);
            return array('status' => 'error', 'message' => $error_message);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code == 401) {
            return array(
                'status' => 'error',
                'message' => 'Authentication failed - please check your WHMCS credentials'
            );
        }
        
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("[Post Picker] Response code: " . $response_code);
        error_log("[Post Picker] Raw response body: " . $response_body);

        // Parse the XML response
        try {
            // Fix malformed XML by adding a root element if needed
            $xml_body = $response_body;
            if (strpos($xml_body, '<?xml') === false) {
                $xml_body = '<?xml version="1.0"?><root>' . $xml_body . '</root>';
            }
            
            // Suppress XML warnings for malformed content
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xml_body);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    error_log("[Post Picker] XML Error: " . $error->message);
                }
                libxml_clear_errors();
                throw new Exception("Failed to parse XML response");
            }
            
            // Convert XML to array for easier handling
            $data = json_decode(json_encode($xml), true);
            
            // If we added a root element, get the first child
            if (isset($data['status']) && count($data) === 1) {
                $data = current($data);
            }
            
            error_log("[Post Picker] Parsed response data: " . print_r($data, true));

            // Handle response based on status
            if (isset($data['status'])) {
                if ($data['status'] === "Active") {
                    // If there's a local key, save it
                    if (!empty($data['localkey'])) {
                        update_option('pfp_local_key', $data['localkey']);
                    }
                    return array('status' => 'success', 'data' => $data);
                } elseif ($data['status'] === "Invalid") {
                    $message = isset($data['message']) ? $data['message'] : 'License is invalid';
                    return array('status' => 'error', 'message' => $message);
                }
            }
            
            return array('status' => 'error', 'message' => 'Unknown response format');

        } catch (Exception $xml_error) {
            error_log("[Post Picker] XML parsing error: " . $xml_error->getMessage());
            return array('status' => 'error', 'message' => 'Invalid server response format');
        }

    } catch (Exception $e) {
        error_log("[Post Picker] License check exception: " . $e->getMessage());
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}


// Add license settings section
function pfp_add_license_settings_section() {
    register_setting('pfp_settings_group', 'pfp_license_key', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    add_settings_section(
        'pfp_license_section',
        'License Settings',
        'pfp_license_section_text',
        'pfp-settings-general'
    );

    add_settings_field(
        'pfp_license_key',
        'License Key',
        'pfp_license_key_input',
        'pfp-settings-general',
        'pfp_license_section'
    );
}
add_action('admin_init', 'pfp_add_license_settings_section');

function pfp_license_section_text() {
    echo '<p>Enter your license key to unlock premium features.</p>';
}

function pfp_license_key_input() {
    $license_key = get_option('pfp_license_key', '');
    $is_valid = pfp_is_licensed();

    error_log("License Key: " . $license_key);
    error_log("License Valid: " . ($is_valid ? 'Yes' : 'No'));

    echo '<input type="text" id="pfp_license_key" name="pfp_license_key" value="' . esc_attr($license_key) . '" class="regular-text" />';

    if (!empty($license_key)) {
        if ($is_valid) {
            echo '<p style="color: green;">✓ License is valid and active</p>';
        } else {
            echo '<p style="color: red;">✗ License is invalid or expired</p>';
        }
    } else {
        echo '<p style="color: orange;">⚠ No license key entered</p>';
    }
}

function pfp_admin_license_notice() {
    if (current_user_can('manage_options') && !pfp_is_licensed()) {
        $settings_url = admin_url('admin.php?page=pfp-settings');
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo '<strong>Post Picker:</strong> Premium features like Font Sizes and Colors customization require a valid license. ';
        echo '<a href="' . esc_url($settings_url) . '">Enter your license key</a> to unlock all features.';
        echo '</p></div>';
    }
}
add_action('admin_notices', 'pfp_admin_license_notice');


// Set default values when the plugin is activated
function pfp_activate_plugin() {
    // Define default values for all settings
    $defaults = array(
        'pfp_predefined_key' => '',
        'pfp_category_title_font_size' => '20',
        'pfp_tablet_category_title_font_size' => '18',
        'pfp_mobile_category_title_font_size' => '16',
        'pfp_tab_font_size' => '14',
        'pfp_tablet_tab_font_size' => '12',
        'pfp_mobile_tab_font_size' => '10',
        'pfp_post_title_font_size' => '24',
        'pfp_tablet_post_title_font_size' => '20',
        'pfp_mobile_post_title_font_size' => '18',
        'pfp_post_excerpt_font_size' => '14',
        'pfp_tablet_post_excerpt_font_size' => '12',
        'pfp_mobile_post_excerpt_font_size' => '10',
        'pfp_pp_container_border_radius' => '0',
        'pfp_filter_container_border_radius' => '0',
        'pfp_post_item_border_radius' => '0',
        'pfp_post_item_img_border_radius' => '0',
        'pfp_pp_container_bg_color' => '#FFFFFF',
        'pfp_filter_container_bg_color' => '#FFFFFF',
        'pfp_post_item_bg_color' => '#FFFFFF',
        'pfp_pagination_bg_color' => '#FFFFFF',
        'pfp_pagination_active_bg_color' => '#000000',
        'pfp_selected_category_heading_color' => '#000000',
        'pfp_tab_item_color' => '#000000',
        'pfp_post_item_heading_color' => '#000000',
        'pfp_post_item_text_color' => '#000000',
        'pfp_subheading_font_color' => '#000000',
        'pfp_pagination_font_color' => '#000000',
        'pfp_pagination_active_font_color' => '#FFFFFF',
        'pfp_posts_per_row' => '4',
        'pfp_tablet_posts_per_row' => '2',
        'pfp_mobile_posts_per_row' => '1',
        'pfp_posts_per_page' => '16',
        'pfp_desktop_image_height' => '200',
        'pfp_tablet_image_height' => '150',
        'pfp_mobile_image_height' => '100',
        'pfp_heading_font_family' => '',
        'pfp_body_font_family' => '',
        'pfp_tag_label' => '',
        'pfp_produce_type_label' => '',
        'pfp_custom_tag_label' => '',
    );

    // Set each option if it doesn't already exist
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            update_option($key, $value);
        }
    }
}
register_activation_hook(__FILE__, 'pfp_activate_plugin');


/**
 * Validate license when settings are saved
 */
function pfp_validate_license_on_save() {
    if (isset($_POST['pfp_license_key'])) {
        $license_key = sanitize_text_field(trim($_POST['pfp_license_key']));
        $current_license = get_option('pfp_license_key', '');

        // Only validate if the key has changed or is empty
        if ($license_key !== $current_license || empty($current_license)) {
            error_log("[Post Picker] Validating license key: " . $license_key);

            // Clear any cached validation
            delete_transient('pfp_license_valid');
            delete_transient('pfp_license_last_check');

            // First validate the format
            if (!pfp_validate_license_format($license_key)) {
                add_settings_error(
                    'pfp_license_key',
                    'invalid_format',
                    '✗ Invalid license key format',
                    'error'
                );
                update_option('pfp_license_key', '');
                return;
            }

            $local_key = get_option('pfp_local_key', '');
            $result = pfp_check_license($license_key, $local_key);

            if ($result['status'] === 'success') {
                update_option('pfp_license_key', $license_key);
                add_settings_error(
                    'pfp_license_key',
                    'valid_license',
                    '✓ License is valid and active',
                    'updated'
                );
            } else {
                add_settings_error(
                    'pfp_license_key',
                    'invalid_license',
                    '✗ License error: ' . $result['message'],
                    'error'
                );
                update_option('pfp_license_key', '');
                update_option('pfp_local_key', '');
            }
        }
    }

    // Additional logic to reset premium fields if license is not valid
    $is_licensed = pfp_is_licensed();

    // if (!$is_licensed) {
    //     // Get all premium options
    //     $premium_options = array(
    //         // Font sizes
    //         'pfp_category_title_font_size',
    //         'pfp_tablet_category_title_font_size',
    //         'pfp_mobile_category_title_font_size',
    //         'pfp_tab_font_size',
    //         'pfp_tablet_tab_font_size',
    //         'pfp_mobile_tab_font_size',
    //         'pfp_post_title_font_size',
    //         'pfp_tablet_post_title_font_size',
    //         'pfp_mobile_post_title_font_size',
    //         'pfp_post_excerpt_font_size',
    //         'pfp_tablet_post_excerpt_font_size',
    //         'pfp_mobile_post_excerpt_font_size',
    //         // Colors
    //         'pfp_pp_container_bg_color',
    //         'pfp_filter_container_bg_color',
    //         'pfp_post_item_bg_color',
    //         'pfp_pagination_bg_color',
    //         'pfp_pagination_active_bg_color',
    //         'pfp_selected_category_heading_color',
    //         'pfp_tab_item_color',
    //         'pfp_post_item_heading_color',
    //         'pfp_post_item_text_color',
    //         'pfp_subheading_font_color',
    //         'pfp_pagination_font_color',
    //         'pfp_pagination_active_font_color'
    //     );

    //     // Reset any premium options that might have been submitted
    //     foreach ($premium_options as $option) {
    //         if (isset($_POST[$option])) {
    //             unset($_POST[$option]);
    //         }
    //     }
    // }
}
add_action('admin_init', 'pfp_validate_license_on_save', 5);




function pfp_is_licensed() {
    $license_key = get_option('pfp_license_key', '');
    
    if (empty($license_key)) {
        error_log("[Post Picker] License check: No license key set");
        return false;
    }

    // Always do a fresh check in admin area
    if (is_admin()) {
        delete_transient('pfp_license_valid');
        delete_transient('pfp_license_last_check');
    }

    // Check if we have a cached valid result
    $is_valid = get_transient('pfp_license_valid');
    
    if (false === $is_valid) {
        $local_key = get_option('pfp_local_key', '');
        $result = pfp_check_license($license_key, $local_key);
        
        // Check license validity based on XML response format
        $is_valid = false;
        
        if (isset($result['status']) && $result['status'] === 'success') {
            // Success from the pfp_check_license wrapper
            $is_valid = true;
            
            // Additional validation against domain, IP, and directory if needed
            if (isset($result['data']['validdomain'])) {
                $current_domain = $_SERVER['SERVER_NAME'];
                $valid_domains = array_map('trim', explode(',', $result['data']['validdomain']));
                if (!in_array($current_domain, $valid_domains)) {
                    error_log("[Post Picker] License valid but domain mismatch");
                    $is_valid = false;
                }
            }
            
            if (isset($result['data']['validip'])) {
                $current_ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'];
                if ($current_ip !== $result['data']['validip']) {
                    error_log("[Post Picker] License valid but IP mismatch");
                    $is_valid = false;
                }
            }
            
            if (isset($result['data']['validdirectory'])) {
                $current_dir = dirname(__FILE__);
                if ($current_dir !== $result['data']['validdirectory']) {
                    error_log("[Post Picker] License valid but directory mismatch");
                    $is_valid = false;
                }
            }
        }
        elseif (isset($result['data']['status']) && $result['data']['status'] === 'Active') {
            // Direct Active status from XML
            $is_valid = true;
        }
        
        // Cache results for 12 hours if valid, 1 hour if invalid
        $cache_time = $is_valid ? 12 * HOUR_IN_SECONDS : 1 * HOUR_IN_SECONDS;
        set_transient('pfp_license_valid', $is_valid, $cache_time);
        set_transient('pfp_license_last_check', time(), $cache_time);
        
        if (!$is_valid) {
            error_log("[Post Picker] License validation failed - clearing local key");
            update_option('pfp_local_key', '');
            
            // Log specific error if available
            if (isset($result['message'])) {
                error_log("[Post Picker] License error: " . $result['message']);
            }
        }
    }
    
    return $is_valid;
}


function pfp_validate_license_format($license_key) {
    if (empty($license_key)) {
        error_log("Empty license key");
        return false;
    }
    
    // Basic validation - allow for different license key formats
    // WHMCS keys are typically 32 chars but can vary
    if (!preg_match('/^[a-zA-Z0-9-]{10,64}$/', $license_key)) {
        error_log("Invalid license key format: " . $license_key);
        return false;
    }
    
    return true;
}

// Enqueue necessary scripts and styles
function pfp_enqueue_scripts() {
    wp_enqueue_style('pfp-styles', plugin_dir_url(__FILE__) . '/post-filtering-plugin.css');
    wp_enqueue_script('pfp-scripts', plugin_dir_url(__FILE__) . '/post-filtering-plugin.js', array('jquery'), null, true);
    wp_localize_script('pfp-scripts', 'pfp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'pfp_enqueue_scripts');

// Add a settings page for the plugin
function pfp_add_settings_pages() {
    // Post Picker Settings Page
    add_menu_page(
        'Post Picker Settings', // Page title
        'Post Picker',          // Menu title
        'manage_options',       // Capability
        'pfp-settings',         // Menu slug
        'pfp_render_settings_page', // Callback function
        'dashicons-filter',     // Icon
        100                     // Position
    );

}
add_action('admin_menu', 'pfp_add_settings_pages');


// Render the settings page with tabs
function pfp_render_settings_page() {
    $is_licensed = pfp_is_licensed();
    ?>
     <div class="wrap">
        <h1>Post Picker Settings</h1>

        <?php settings_errors(); ?>

        <div class="pfp-settings-container">
            <div class="pfp-settings-form">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-general" class="nav-tab nav-tab-active">General Settings</a>
                    <a href="#tab-font-sizes" class="nav-tab">Font Sizes</a>
                    <a href="#tab-colors" class="nav-tab">Colors</a>
                    <a href="#tab-layout" class="nav-tab">Layout</a>
                </h2>

                <form method="post" action="options.php" id="pfp-settings-form">
                    <?php
                    settings_fields('pfp_settings_group');

                    // General Settings Tab (always visible)
                    echo '<div id="tab-general" class="tab-content active">';
                    do_settings_sections('pfp-settings-general');
                    echo '</div>';

                    // Font Sizes Tab (always visible)
                    echo '<div id="tab-font-sizes" class="tab-content">';
                    do_settings_sections('pfp-settings-font-sizes');
                    echo '</div>';

                    // Colors Tab (always visible)
                    echo '<div id="tab-colors" class="tab-content">';
                    do_settings_sections('pfp-settings-colors');
                    echo '</div>';

                    // Layout Tab (always visible)
                    echo '<div id="tab-layout" class="tab-content">';
                    do_settings_sections('pfp-settings-layout');
                    echo '</div>';

                    submit_button('Save Settings');
                    ?>
                </form>

                <!-- Move the Reset Button Outside the Form -->
                <button id="pfp-reset-settings" class="button button-secondary" style="display: none;">Reset Settings</button>
            </div>
        </div>
    </div>

      <!-- Premium Feature Popup -->
      <div id="pfp-premium-popup" style="display:none;">
    <div class="pfp-popup-content">
        <span class="pfp-popup-close">&times;</span>
        <h3>Premium Feature</h3>
        <p>This feature requires a valid license. Please enter your license key to unlock all premium features.</p>
        <a href="<?php echo admin_url('admin.php?page=pfp-settings'); ?>" class="button button-primary">Enter License Key</a>
        <!-- <div class="pfp-bonus">
            <strong>Bonus:</strong> Get 50% off your license for a limited time!
        </div> -->
        <!-- <a href="#" class="pfp-already-purchased">Already purchased?</a> -->
    </div>
</div>

<script>
        jQuery(document).ready(function($) {
            // Check license status
            var isLicensed = <?php echo $is_licensed ? 'true' : 'false'; ?>;

            // Show premium notice but don't block interaction
         // Add premium indicator to fields
            if (!isLicensed) {
                // $('#tab-font-sizes .form-table tr, #tab-colors .form-table tr').each(function() {
                //     $(this).addClass('pfp-premium-field');
                // });
            }

            // Initialize color pickers regardless of license status
            $('.pfp-color-picker').wpColorPicker();
        });
        </script>
    <style>

/* .premium-badge {
                background: #ffba00;
                color: #000;
                padding: 2px 5px;
                font-size: 10px;
                font-weight: bold;
                border-radius: 3px;
                margin-left: 10px;
            }
             */
            .pfp-premium-field {
                opacity: 1; /* Always show fields */
                pointer-events: auto; /* Allow interaction */
            }
      #pfp-premium-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5); /* Darker overlay */
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.pfp-popup-content {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    max-width: 420px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    position: relative;
}

.pfp-popup-content h3 {
    font-size: 22px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

.pfp-popup-content p {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
}

.pfp-popup-content .button-primary {
    background-color: #f47c40; /* Orange button */
    color: #fff;
    padding: 12px 20px;
    border-radius: 5px;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    margin-top: 10px;
    transition: background 0.3s ease-in-out;
}

.pfp-popup-content .button-primary:hover {
    background-color: #d66935;
}

.pfp-popup-content .pfp-bonus {
    background: #fdf8e3;
    padding: 12px;
    margin-top: 15px;
    border-radius: 5px;
    font-size: 14px;
    color: #555;
    font-weight: 500;
}

.pfp-popup-content .pfp-bonus strong {
    color: #28a745; /* Green text */
}

.pfp-popup-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 18px;
    cursor: pointer;
    color: #999;
}

.pfp-popup-close:hover {
    color: #666;
}

.pfp-already-purchased {
    display: block;
    margin-top: 10px;
    font-size: 13px;
    color: #666;
    text-decoration: none;
}

.pfp-already-purchased:hover {
    color: #333;
}

    </style>
      <script>
jQuery(document).ready(function($) {
    // Check license status
    var isLicensed = <?php echo $is_licensed ? 'true' : 'false'; ?>;

    // Show premium popup function
    function showPremiumPopup() {
        $('#pfp-premium-popup').fadeIn();
       // return false;
    }

    // Show premium popup when interacting with premium tabs
    $('.nav-tab-wrapper a').click(function(e) {
    // Always allow tab switching
    var tab = $(this).attr('href');
    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
    $(this).addClass('nav-tab-active');
    $('.tab-content').hide();
    $(tab).show();
});

    // Show popup when interacting with any premium field
    $('input[name="pfp_category_title_font_size"], \
       input[name="pfp_tablet_category_title_font_size"], \
       input[name="pfp_mobile_category_title_font_size"], \
       input[name="pfp_tab_font_size"], \
       input[name="pfp_tablet_tab_font_size"], \
       input[name="pfp_mobile_tab_font_size"], \
       input[name="pfp_post_title_font_size"], \
       input[name="pfp_tablet_post_title_font_size"], \
       input[name="pfp_mobile_post_title_font_size"], \
       input[name="pfp_post_excerpt_font_size"], \
       input[name="pfp_tablet_post_excerpt_font_size"], \
       input[name="pfp_mobile_post_excerpt_font_size"]').on('focus click', function(e) {
        if (!isLicensed) {
            e.preventDefault();
            return showPremiumPopup();
        }
    });

    // Close popup
    $('.pfp-popup-close').click(function() {
        $('#pfp-premium-popup').fadeOut();
    });

    // Add premium indicator to fields
    if (!isLicensed) {
        $('#tab-font-sizes .form-table tr, #tab-colors .form-table tr').each(function() {
            $(this).addClass('pfp-premium-field');
        });
    }

    // Handle color picker interactions
    if (!isLicensed) {
        // Remove existing color picker bindings
        $('.wp-color-result').off('click');
        
        // Prevent color picker from opening
        $('.wp-color-result').on('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return showPremiumPopup();
        });
        
        // Also prevent direct input interaction
        $('.pfp-color-picker').on('focus click', function(e) {
            e.preventDefault();
            return showPremiumPopup();
        });
        
        // Make it visually clear the field is disabled
        $('.wp-color-result').css({
            'cursor': 'default',
            'opacity': '0.7'
        });
    } else {
        // Initialize color pickers for licensed users
        $('.pfp-color-picker').wpColorPicker();
    }
});
    </script>


    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.nav-tab-wrapper a').click(function(e) {
                if ($(this).hasClass('disabled-tab')) {
                    e.preventDefault();
                    alert('Please enter a valid license key to access these settings.');
                    return false;
                }
                
                e.preventDefault();
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $($(this).attr('href')).show();

                // Show/Hide Reset Button based on the active tab
                if ($(this).attr('href') === '#tab-general') {
                    $('#pfp-reset-settings').hide();
                } else {
                    $('#pfp-reset-settings').show();
                }
            });

            // Show the first tab by default
            $('.nav-tab-wrapper a:first').click();

            // Handle reset button click
            $('#pfp-reset-settings').click(function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all settings to default?')) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'pfp_reset_settings'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Settings have been reset to default.');
                                location.reload(); // Reload the page to reflect the changes
                            } else {
                                alert('Failed to reset settings.');
                            }
                        }
                    });
                }
            });
        });
    </script>

 <!-- Enhanced CSS Styling -->
<style>
    .pfp-settings-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 30px;
    }

    .pfp-settings-form {
        flex: 1;
        max-width: 100%;
        min-width: 300px;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .pfp-settings-form h1 {
        font-size: 28px; /* Larger font size for the main title */
        margin-bottom: 20px;
        color: #333;
    }

    .pfp-settings-form h2 {
        font-size: 24px; /* Font size for section headings */
        margin-bottom: 20px;
        color: #333;
    }

    .pfp-settings-form h3 {
        margin-top: 20px;
        font-size: 20px; /* Adjust font size for subheadings */
        color: #444;
    }

    .pfp-settings-form h4 {
        font-size: 18px; /* Font size for smaller subheadings */
        color: #333;
    }

    .nav-tab-wrapper {
        display: flex;
        justify-content: space-around;
        margin-bottom: 20px;
    }

    .nav-tab {
        padding: 10px 20px;
        background-color: #f1f1f1;
        color: #555;
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid #ddd;
        margin-right: 10px;
        font-weight: bold;
        font-size: 16px; /* Font size for tab text */
    }

    .nav-tab-active {
        background-color: #0073aa;
        color: white;
        border-color: #0073aa;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .pfp-settings-form .section {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }

    .pfp-settings-form .section h4 {
        margin-top: 0;
        font-size: 16px; /* Font size for section titles */
        color: #333;
    }

    .pfp-settings-form .section input[type="text"], 
    .pfp-settings-form .section input[type="number"], 
    .pfp-settings-form .section select {
        width: 100%;
        padding: 8px;
        margin-top: 10px;
        border-radius: 4px;
        border: 1px solid #ccc;
        font-size: 14px; /* Font size for input fields */
    }

    .pfp-settings-form .section input[type="submit"],
    .pfp-settings-form .section button {
        padding: 10px 20px;
        background-color: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px; /* Font size for buttons */
    }

    .pfp-settings-form .section input[type="submit"]:hover,
    .pfp-settings-form .section button:hover {
        background-color: #005f8e;
    }

    /* Font Sizes Tab Specific */
    #tab-font-sizes .section {
        font-size: 18px; /* Default font size for the font sizes section */
    }

    #tab-font-sizes .section input[type="text"], 
    #tab-font-sizes .section input[type="number"], 
    #tab-font-sizes .section select {
        font-size: 16px; /* Adjust font size of inputs in the Font Sizes section */
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .pfp-settings-container {
            flex-direction: column;
        }

        .pfp-settings-form {
            width: 100%;
        }

        .nav-tab-wrapper {
            flex-direction: column;
            align-items: center;
        }

        .nav-tab {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }
    }
    .pfp-premium-field {
    opacity: 1 !important;
    pointer-events: auto !important;
}

/* .pfp-premium-field::after {
    content: "";
    display: inline-block;
    width: 14px;
    height: 14px;
    margin-left: 8px;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffba00"><path d="M12 2C9.243 2 7 4.243 7 7v3H6a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V11a1 1 0 0 0-1-1h-1V7c0-2.757-2.243-5-5-5zm0 2c1.654 0 3 1.346 3 3v3H9V7c0-1.654 1.346-3 3-3zm-6 8h12v8H6v-8z"/></svg>');
    background-repeat: no-repeat;
    background-position: center;
    vertical-align: middle;
} */

.disabled-tab {
    opacity: 1 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
}

.pfp-premium-field input,
.pfp-premium-field select {
    background-color: #f5f5f5;
    border-color: #ddd;
}
</style>

    <?php
}



// Register plugin settings
function pfp_register_settings() {
  
    
    
    // Shortcode Section
add_settings_section(
    'pfp_shortcode_section',
    'Shortcode',
    'pfp_shortcode_section_text',
    'pfp-settings-general'
);

add_settings_field(
    'pfp_shortcode_display',
    'Shortcode',
    'pfp_shortcode_display_input',
    'pfp-settings-general',
    'pfp_shortcode_section'
);

  


    // Add a new section for category selection
    add_settings_section(
        'pfp_category_selection_section',
        'Category Selection',
        'pfp_category_selection_section_text',
        'pfp-settings-general'
    );

    // Add field for category selection
    add_settings_field(
        'pfp_selected_categories',
        'Select Categories to Display',
        'pfp_selected_categories_input',
        'pfp-settings-general',
        'pfp_category_selection_section'
    );
   
    register_setting('pfp_settings_group', 'pfp_category_title_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tablet_category_title_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_mobile_category_title_font_size', 'sanitize_text_field');


    register_setting('pfp_settings_group', 'pfp_tab_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tablet_tab_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_mobile_tab_font_size', 'sanitize_text_field');

    register_setting('pfp_settings_group', 'pfp_post_title_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tablet_post_title_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_mobile_post_title_font_size', 'sanitize_text_field');

    register_setting('pfp_settings_group', 'pfp_post_excerpt_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tablet_post_excerpt_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_mobile_post_excerpt_font_size', 'sanitize_text_field');

    // Font Sizes Section
    add_settings_section(
        'pfp_font_sizes_section',
        'Font Sizes',
        'pfp_font_sizes_section_text',
        'pfp-settings-font-sizes'
    );

    add_settings_section(
        'pfp_category_title_font_section',
        'Category Title Font',
        'pfp_category_title_font_section_text', // Callback function
        'pfp-settings-font-sizes'
    );
    
    // // Heading Font Size Fields
    add_settings_field(
        'pfp_category_title_font_size',
        'Category Title Font Size (px)',
        'pfp_category_title_font_size_input',
        'pfp-settings-font-sizes',
        'pfp_category_title_font_section'
    );
    add_settings_field(
        'pfp_tablet_category_title_font_size',
        'Category Title Tablet Font Size (px)',
        'pfp_tablet_category_title_font_size_input',
        'pfp-settings-font-sizes',
        'pfp_category_title_font_section'
    );
    add_settings_field(
        'pfp_mobile_category_title_font_size',
        'Category Title Mobile Font Size (px)',
        'pfp_mobile_category_title_font_size_input',
        'pfp-settings-font-sizes',
        'pfp_category_title_font_section'
    );
    

    // Tab Title Font Section
    add_settings_section(
        'pfp_tab_title_font_section',  // Section ID
        'Tab Title Font',              // Section title
        'pfp_tab_title_font_section_text', // Callback function
        'pfp-settings-font-sizes'      // Page where the section will appear
    );

    // Tab Title Font Size Fields
    add_settings_field(
        'pfp_tab_font_size', 
        'Tab Title Font Size (px)', 
        'pfp_tab_font_size_input', 
        'pfp-settings-font-sizes', 
        'pfp_tab_title_font_section'
    );
    add_settings_field(
        'pfp_tablet_tab_font_size', 
        'Tab Title Tablet Font Size (px)', 
        'pfp_tablet_tab_font_size_input', 
        'pfp-settings-font-sizes', 
        'pfp_tab_title_font_section'
    );
    add_settings_field(
        'pfp_mobile_tab_font_size', 
        'Tab Title Mobile Font Size (px)', 
        'pfp_mobile_tab_font_size_input', 
        'pfp-settings-font-sizes', 
        'pfp_tab_title_font_section'
    );

    // Post Title Font Section
    add_settings_section(
        'pfp_post_title_font_section',
        'Post Title Font',
        'pfp_post_title_font_section_text', // Callback function
        'pfp-settings-font-sizes'
    );

    // Post Title Font Size Fields
    add_settings_field('pfp_post_title_font_size', 'Post Title Font Size (px)', 'pfp_post_title_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_title_font_section');
    add_settings_field('pfp_tablet_post_title_font_size', 'Post Title Tablet Font Size (px)', 'pfp_tablet_post_title_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_title_font_section');
    add_settings_field('pfp_mobile_post_title_font_size', 'Post Title Mobile Font Size (px)', 'pfp_mobile_post_title_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_title_font_section');

    // Post Excerpt Font Section
    add_settings_section(
        'pfp_post_excerpt_font_section',
        'Post Excerpt Font',
        'pfp_post_excerpt_font_section_text', // Callback function
        'pfp-settings-font-sizes'
    );

    // Post Excerpt Font Size Fields
    add_settings_field('pfp_post_excerpt_font_size', 'Post Excerpt Font Size (px)', 'pfp_post_excerpt_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_excerpt_font_section');
    add_settings_field('pfp_tablet_post_excerpt_font_size', 'Post Excerpt Tablet Font Size (px)', 'pfp_tablet_post_excerpt_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_excerpt_font_section');
    add_settings_field('pfp_mobile_post_excerpt_font_size', 'Post Excerpt Mobile Font Size (px)', 'pfp_mobile_post_excerpt_font_size_input', 'pfp-settings-font-sizes', 'pfp_post_excerpt_font_section');

    // Font Family Settings
    register_setting('pfp_settings_group', 'pfp_heading_font_family', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_body_font_family', 'sanitize_text_field');

    add_settings_section(
        'pfp_font_family_section',
        'Font Family',
        'pfp_font_family_section_text',
        'pfp-settings-font-families'
    );

    add_settings_field('pfp_heading_font_family', 'Heading Font Family', 'pfp_heading_font_family_input', 'pfp-settings-font-families', 'pfp_font_family_section');
    add_settings_field('pfp_body_font_family', 'Body Font Family', 'pfp_body_font_family_input', 'pfp-settings-font-families', 'pfp_font_family_section');

    // General Settings
    //register_setting('pfp_settings_group', 'pfp_taxonomy_name', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tag_label', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_produce_type_label', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_custom_tag_label', 'sanitize_text_field'); // New Field


    add_settings_section(
        'pfp_general_section',
        'General Settings',
        'pfp_general_section_text',
        'pfp-settings-general'
    );

    //add_settings_field('pfp_taxonomy_name', 'Tag1', 'pfp_taxonomy_name_input', 'pfp-settings-general', 'pfp_general_section');
    add_settings_field('pfp_tag_label', 'Default Tag Label', 'pfp_tag_label_input', 'pfp-settings-general', 'pfp_general_section');
    add_settings_field('pfp_produce_type_label', 'Tag Label 1', 'pfp_produce_type_label_input', 'pfp-settings-general', 'pfp_general_section');
    add_settings_field('pfp_custom_tag_label', 'Tag Label 2', 'pfp_custom_tag_label_input', 'pfp-settings-general', 'pfp_general_section'); // New Field

    // Colors Section
    register_setting('pfp_settings_group', 'pfp_pp_container_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_filter_container_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_post_item_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_pagination_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_pagination_active_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_selected_category_heading_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_tab_item_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_post_item_heading_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_post_item_text_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_subheading_font_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_pagination_font_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_pagination_active_font_color', 'sanitize_hex_color');


    add_settings_field('pfp_subheading_font_color', 'Subheading Font Color', 'pfp_subheading_font_color_input', 'pfp-settings-colors', 'pfp_colors_section'); // New field for subheading font color

    add_settings_section(
        'pfp_colors_section',
        'Colors',
        'pfp_colors_section_text',
        'pfp-settings-colors'
    );

    add_settings_field('pfp_pp_container_bg_color', 'Filter Outer Container Background Color', 'pfp_pp_container_bg_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_filter_container_bg_color', 'Filter Container Background Color', 'pfp_filter_container_bg_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_post_item_bg_color', 'Post Item Background Color', 'pfp_post_item_bg_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_pagination_bg_color', 'Pagination Background Color', 'pfp_pagination_bg_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_pagination_active_bg_color', 'Active Pagination Background Color', 'pfp_pagination_active_bg_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_selected_category_heading_color', 'Selected Category Heading Color', 'pfp_selected_category_heading_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_tab_item_color', 'Tab Item Color', 'pfp_tab_item_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_post_item_heading_color', 'Post Item Heading Color', 'pfp_post_item_heading_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field('pfp_pagination_font_color', 'Pagination Font Color', 'pfp_pagination_font_color_input', 'pfp-settings-colors', 'pfp_colors_section');
    add_settings_field( 'pfp_pagination_active_font_color','Active Pagination Font Color','pfp_pagination_active_font_color_input','pfp-settings-colors', 'pfp_colors_section' );
    add_settings_field('pfp_post_item_text_color', 'Post Item Text Color', 'pfp_post_item_text_color_input', 'pfp-settings-colors', 'pfp_colors_section');

 // Layout Register Settings
register_setting('pfp_settings_group', 'pfp_posts_per_row', 'intval');
register_setting('pfp_settings_group', 'pfp_tablet_posts_per_row', 'intval'); // New: Tablet posts per row
register_setting('pfp_settings_group', 'pfp_mobile_posts_per_row', 'intval'); // New: Mobile posts per row
register_setting('pfp_settings_group', 'pfp_posts_per_page', 'intval');

// Register image height settings
register_setting('pfp_settings_group', 'pfp_desktop_image_height', 'intval');
register_setting('pfp_settings_group', 'pfp_tablet_image_height', 'intval');
register_setting('pfp_settings_group', 'pfp_mobile_image_height', 'intval');

// Register border radius settings
register_setting('pfp_settings_group', 'pfp_pp_container_border_radius', 'intval');
register_setting('pfp_settings_group', 'pfp_filter_container_border_radius', 'intval');
register_setting('pfp_settings_group', 'pfp_post_item_border_radius', 'intval');
register_setting('pfp_settings_group', 'pfp_post_item_img_border_radius', 'intval');

// Add a new section for border radius
add_settings_section(
    'pfp_border_radius_section',
    'Border Radius',
    'pfp_border_radius_section_text',
    'pfp-settings-layout'
);

// Add fields for border radius
add_settings_field(
    'pfp_pp_container_border_radius',
    'Outer Container Border Radius (px)',
    'pfp_pp_container_border_radius_input',
    'pfp-settings-layout',
    'pfp_border_radius_section'
);

add_settings_field(
    'pfp_filter_container_border_radius',
    'Filter Container Border Radius (px)',
    'pfp_filter_container_border_radius_input',
    'pfp-settings-layout',
    'pfp_border_radius_section'
);

add_settings_field(
    'pfp_post_item_border_radius',
    'Post Item Border Radius (px)',
    'pfp_post_item_border_radius_input',
    'pfp-settings-layout',
    'pfp_border_radius_section'
);

add_settings_field(
    'pfp_post_item_img_border_radius',
    'Post Item Image Border Radius (px)',
    'pfp_post_item_img_border_radius_input',
    'pfp-settings-layout',
    'pfp_border_radius_section'
);

// Add a new section for image height settings
add_settings_section(
    'pfp_image_height_section',
    'Image Height',
    'pfp_image_height_section_text',
    'pfp-settings-layout'
);

// Add fields for image height settings
add_settings_field(
    'pfp_desktop_image_height',
    'Desktop Image Height (px)',
    'pfp_desktop_image_height_input',
    'pfp-settings-layout',
    'pfp_image_height_section'
);

add_settings_field(
    'pfp_tablet_image_height',
    'Tablet Image Height (px)',
    'pfp_tablet_image_height_input',
    'pfp-settings-layout',
    'pfp_image_height_section'
);

add_settings_field(
    'pfp_mobile_image_height',
    'Mobile Image Height (px)',
    'pfp_mobile_image_height_input',
    'pfp-settings-layout',
    'pfp_image_height_section'
);

// Add Layout Section
add_settings_section(
    'pfp_layout_section',
    'Layout',
    'pfp_layout_section_text',
    'pfp-settings-layout'
);

// Add Post Row Section
add_settings_section(
    'pfp_post_row_section',
    'Post Row', // New section heading
    'pfp_post_row_section_text',
    'pfp-settings-layout'
);

// Add fields to Post Row Section
add_settings_field(
    'pfp_posts_per_row',
    'Posts Per Row',
    'pfp_posts_per_row_input',
    'pfp-settings-layout',
    'pfp_post_row_section' // Assign to Post Row section
);

add_settings_field(
    'pfp_tablet_posts_per_row',
    'Tablet Posts Per Row',
    'pfp_tablet_posts_per_row_input',
    'pfp-settings-layout',
    'pfp_post_row_section' // Assign to Post Row section
);

add_settings_field(
    'pfp_mobile_posts_per_row',
    'Mobile Posts Per Row',
    'pfp_mobile_posts_per_row_input',
    'pfp-settings-layout',
    'pfp_post_row_section' // Assign to Post Row section
);

// Add fields to Layout Section
add_settings_field(
    'pfp_posts_per_page',
    'Posts Per Page',
    'pfp_posts_per_page_input',
    'pfp-settings-layout',
    'pfp_layout_section'
);





}


add_action('admin_init', 'pfp_register_settings');

function pfp_shortcode_section_text() {
    echo '<p>Copy and paste this shortcode into any page to display the filtered posts.</p>';
}

function pfp_shortcode_display_input() {
    echo '<input type="text" readonly value="[pfp_display_filtered_posts]" style="width: 300px; background-color: #f3f3f3; border: 1px solid #ccc;">';
}


function pfp_category_selection_section_text() {
    echo '<p>Select the categories you want to display in the Post Picker plugin.</p>';
}

function pfp_sanitize_categories($input) {
    if (is_array($input)) {
        return array_map('intval', $input);
    }
    return array(); // Ensure it's always an array
}

function pfp_selected_categories_input() {
    $selected_categories = get_option('pfp_selected_categories', array());

    // Ensure the retrieved value is an array
    if (!is_array($selected_categories)) {
        $selected_categories = array();
    }

    $categories = get_categories(array('hide_empty' => false));

    foreach ($categories as $category) {
        $checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
        echo '<label><input type="checkbox" name="pfp_selected_categories[]" value="' . esc_attr($category->term_id) . '" ' . $checked . '> ' . esc_html($category->name) . '</label><br>';
    }
}


function pfp_border_radius_section_text() {
    echo '<p>Set the border radius for different elements in the Post Picker plugin.</p>';
}

function pfp_pp_container_border_radius_input() {
    $value = get_option('pfp_pp_container_border_radius', 0);
    echo '<input id="pfp_pp_container_border_radius" name="pfp_pp_container_border_radius" type="number" min="0" max="100" value="' . esc_attr($value) . '" />';
}

function pfp_filter_container_border_radius_input() {
    $value = get_option('pfp_filter_container_border_radius', 0);
    echo '<input id="pfp_filter_container_border_radius" name="pfp_filter_container_border_radius" type="number" min="0" max="100" value="' . esc_attr($value) . '" />';
}

function pfp_post_item_border_radius_input() {
    $value = get_option('pfp_post_item_border_radius', 0);
    echo '<input id="pfp_post_item_border_radius" name="pfp_post_item_border_radius" type="number" min="0" max="100" value="' . esc_attr($value) . '" />';
}

function pfp_post_item_img_border_radius_input() {
    $value = get_option('pfp_post_item_img_border_radius', 0);
    echo '<input id="pfp_post_item_img_border_radius" name="pfp_post_item_img_border_radius" type="number" min="0" max="100" value="' . esc_attr($value) . '" />';
}


// Callback function for the image height section text
function pfp_image_height_section_text() {
    echo '<p>Set the image height for the post grid on desktop, tablet, and mobile views.</p>';
}

// Callback function for desktop image height input
function pfp_desktop_image_height_input() {
    $desktop_image_height = get_option('pfp_desktop_image_height', 200); // Default: 200px
    echo '<input id="pfp_desktop_image_height" name="pfp_desktop_image_height" type="number" min="50" max="1000" value="' . esc_attr($desktop_image_height) . '" />';
}

// Callback function for tablet image height input
function pfp_tablet_image_height_input() {
    $tablet_image_height = get_option('pfp_tablet_image_height', 150); // Default: 150px
    echo '<input id="pfp_tablet_image_height" name="pfp_tablet_image_height" type="number" min="50" max="1000" value="' . esc_attr($tablet_image_height) . '" />';
}

// Callback function for mobile image height input
function pfp_mobile_image_height_input() {
    $mobile_image_height = get_option('pfp_mobile_image_height', 100); // Default: 100px
    echo '<input id="pfp_mobile_image_height" name="pfp_mobile_image_height" type="number" min="50" max="1000" value="' . esc_attr($mobile_image_height) . '" />';
}

// Function for the new field
function pfp_custom_tag_label_input() {
    $custom_tag_label = get_option('pfp_custom_tag_label', '');
    $placeholder = 'Eg : Publications'; // Placeholder text

    echo '<input id="pfp_custom_tag_label" name="pfp_custom_tag_label" type="text" value="' . esc_attr($custom_tag_label) . '" placeholder="' . esc_attr($placeholder) . '" />';
}


// Callback function for the predefined key input field
function pfp_predefined_key_input() {
    $predefined_key = get_option('pfp_predefined_key', '');
    echo '<input id="pfp_predefined_key" name="pfp_predefined_key" type="text" value="' . esc_attr($predefined_key) . '" />';
}

function pfp_category_title_font_section_text() {
    echo '<p>Set the font size for the selected category title across different screen sizes.</p>';
}
function pfp_category_title_font_size_input() {
    $value = get_option('pfp_category_title_font_size', '20');
    echo '<input type="text" name="pfp_category_title_font_size" value="' . esc_attr($value) . '" />';
}

function pfp_tablet_category_title_font_size_input() {
    $value = get_option('pfp_tablet_category_title_font_size', '');
    echo '<input type="text" name="pfp_tablet_category_title_font_size" value="' . esc_attr($value) . '" />';
}

function pfp_mobile_category_title_font_size_input() {
    $value = get_option('pfp_mobile_category_title_font_size', '');
    echo '<input type="text" name="pfp_mobile_category_title_font_size" value="' . esc_attr($value) . '" />';
}

function pfp_post_row_section_text() {
    echo '<p>Configure the number of posts displayed per row for different devices (desktop, tablet, mobile).</p>';
}


// Callback function for the Tab Title Font section
function pfp_tab_title_font_section_text() {
    echo '<p>Adjust the font size for the tab titles on different devices (desktop, tablet, and mobile).</p>';
}

// Callback function for the Heading Font section
function pfp_heading_font_section_text() {
    echo '<p>Adjust the font size for the headings on different devices (desktop, tablet, and mobile).</p>';
}

// Callback function for the Post Title Font section
function pfp_post_title_font_section_text() {
    echo '<p>Adjust the font size for the post titles on different devices (desktop, tablet, and mobile).</p>';
}

// Callback function for the Post Excerpt Font section
function pfp_post_excerpt_font_section_text() {
    echo '<p>Adjust the font size for the post excerpts on different devices (desktop, tablet, and mobile).</p>';
}

// Callback function for the Font Family section
function pfp_font_family_section_text() {
    echo '<p>Adjust the font family for the headings and body text.</p>';
}






function pfp_heading_font_family_input() {
    $heading_font_family = get_option('pfp_heading_font_family', ''); // Default: inherit
    echo '<input id="pfp_heading_font_family" name="pfp_heading_font_family" type="text" value="' . esc_attr($heading_font_family) . '" placeholder="Enter font family (e.g., Arial, sans-serif)" />';
    echo '<p class="description">Leave blank to inherit the theme\'s font family.</p>';
}

function pfp_body_font_family_input() {
    $body_font_family = get_option('pfp_body_font_family', ''); // Default: inherit
    echo '<input id="pfp_body_font_family" name="pfp_body_font_family" type="text" value="' . esc_attr($body_font_family) . '" placeholder="Enter font family (e.g., Arial, sans-serif)" />';
    echo '<p class="description">Leave blank to inherit the theme\'s font family.</p>';
}



function pfp_tablet_posts_per_row_input() {
    $tablet_posts_per_row = get_option('pfp_tablet_posts_per_row', 2); // Default: 2 posts per row for tablet
    echo '<input id="pfp_tablet_posts_per_row" name="pfp_tablet_posts_per_row" type="number" min="1" max="6" value="' . esc_attr($tablet_posts_per_row) . '" />';
}

function pfp_mobile_posts_per_row_input() {
    $mobile_posts_per_row = get_option('pfp_mobile_posts_per_row', 1); // Default: 1 post per row for mobile
    echo '<input id="pfp_mobile_posts_per_row" name="pfp_mobile_posts_per_row" type="number" min="1" max="6" value="' . esc_attr($mobile_posts_per_row) . '" />';
}

function pfp_general_section_text() {
    echo '<p>Customize the general settings for the Post Picker plugin.</p>';
}

function pfp_font_sizes_section_text() {
    echo '<p>Customize the font sizes for different elements in the Post Picker plugin.</p>';
}

function pfp_colors_section_text() {
    echo '<p>Customize the background colors and font color for different elements in the Post Picker plugin.</p>';
}

function pfp_layout_section_text() {
    echo '<p>Customize the layout settings for the Post Picker plugin.</p>';
}

function pfp_produce_type_label_input() {
    $produce_type_label = get_option('pfp_produce_type_label', '');
    $placeholder = 'Eg : Book Type'; // Placeholder text

    echo '<input id="pfp_produce_type_label" name="pfp_produce_type_label" type="text" value="' . esc_attr($produce_type_label) . '" placeholder="' . esc_attr($placeholder) . '" />';
}


// Section text
function pfp_section_text() {
    echo '<p>Enter the custom names for the taxonomy and tag label.</p>';
}


function pfp_tag_label_input() {
    $tag_label = get_option('pfp_tag_label', ''); // Fetch the saved option; default is empty
    echo '<input id="pfp_tag_label" name="pfp_tag_label" type="text" value="' . esc_attr($tag_label) . '" placeholder="Eg : Author" />';
}




function pfp_mobile_font_size_input() {
    $mobile_font_size = get_option('pfp_mobile_font_size', '12'); // Default: 12px
    echo '<input id="pfp_mobile_font_size" name="pfp_mobile_font_size" type="text" value="' . esc_attr($mobile_font_size) . '" />';
}
function pfp_tab_font_size_input() {
    $tab_font_size = get_option('pfp_tab_font_size', '14'); // Default: 14px
    echo '<input id="pfp_tab_font_size" name="pfp_tab_font_size" type="text" value="' . esc_attr($tab_font_size) . '" />';
}

function pfp_post_title_font_size_input() {
    $post_title_font_size = get_option('pfp_post_title_font_size', '24'); // Default: 24px
    echo '<input id="pfp_post_title_font_size" name="pfp_post_title_font_size" type="text" value="' . esc_attr($post_title_font_size) . '" />';
}

function pfp_post_excerpt_font_size_input() {
    $post_excerpt_font_size = get_option('pfp_post_excerpt_font_size', '14'); // Default: 14px
    echo '<input id="pfp_post_excerpt_font_size" name="pfp_post_excerpt_font_size" type="text" value="' . esc_attr($post_excerpt_font_size) . '" />';
}



function pfp_posts_per_row_input() {
    $posts_per_row = get_option('pfp_posts_per_row', 4); // Default: 4 posts per row
    echo '<input id="pfp_posts_per_row" name="pfp_posts_per_row" type="number" min="1" max="6" value="' . esc_attr($posts_per_row) . '" />';
}

function pfp_posts_per_page_input() {
    $posts_per_page = get_option('pfp_posts_per_page', 16); // Default: 16 posts per page
    echo '<input id="pfp_posts_per_page" name="pfp_posts_per_page" type="number" min="1" max="50" value="' . esc_attr($posts_per_page) . '" />';
}



// Input field callbacks for new settings
function pfp_tablet_tab_font_size_input() {
    $tablet_tab_font_size = get_option('pfp_tablet_tab_font_size', '12'); // Default: 12px
    echo '<input id="pfp_tablet_tab_font_size" name="pfp_tablet_tab_font_size" type="text" value="' . esc_attr($tablet_tab_font_size) . '" />';
}

function pfp_mobile_tab_font_size_input() {
    $mobile_tab_font_size = get_option('pfp_mobile_tab_font_size', '10'); // Default: 10px
    echo '<input id="pfp_mobile_tab_font_size" name="pfp_mobile_tab_font_size" type="text" value="' . esc_attr($mobile_tab_font_size) . '" />';
}

function pfp_tablet_post_title_font_size_input() {
    $tablet_post_title_font_size = get_option('pfp_tablet_post_title_font_size', '20'); // Default: 20px
    echo '<input id="pfp_tablet_post_title_font_size" name="pfp_tablet_post_title_font_size" type="text" value="' . esc_attr($tablet_post_title_font_size) . '" />';
}

function pfp_mobile_post_title_font_size_input() {
    $mobile_post_title_font_size = get_option('pfp_mobile_post_title_font_size', '18'); // Default: 18px
    echo '<input id="pfp_mobile_post_title_font_size" name="pfp_mobile_post_title_font_size" type="text" value="' . esc_attr($mobile_post_title_font_size) . '" />';
}

function pfp_tablet_post_excerpt_font_size_input() {
    $tablet_post_excerpt_font_size = get_option('pfp_tablet_post_excerpt_font_size', '12'); // Default: 12px
    echo '<input id="pfp_tablet_post_excerpt_font_size" name="pfp_tablet_post_excerpt_font_size" type="text" value="' . esc_attr($tablet_post_excerpt_font_size) . '" />';
}

function pfp_mobile_post_excerpt_font_size_input() {
    $mobile_post_excerpt_font_size = get_option('pfp_mobile_post_excerpt_font_size', '10'); // Default: 10px
    echo '<input id="pfp_mobile_post_excerpt_font_size" name="pfp_mobile_post_excerpt_font_size" type="text" value="' . esc_attr($mobile_post_excerpt_font_size) . '" />';
}



function pfp_custom_styles() {
    $pp_container_border_radius = get_option('pfp_pp_container_border_radius', 0);
    $filter_container_border_radius = get_option('pfp_filter_container_border_radius', 0);
    $post_item_border_radius = get_option('pfp_post_item_border_radius', 0);
    $post_item_img_border_radius = get_option('pfp_post_item_img_border_radius', 0);

    echo '<style>
        .pp-container {
            border-radius: ' . esc_attr($pp_container_border_radius) . 'px;
        }
        .filter-container {
            border-radius: ' . esc_attr($filter_container_border_radius) . 'px;
        }
        .post-item {
            border-radius: ' . esc_attr($post_item_border_radius) . 'px;
        }
        .post-item img {
            border-radius: ' . esc_attr($post_item_img_border_radius) . 'px;
        }
    </style>';
}
add_action('wp_head', 'pfp_custom_styles');


function pfp_enqueue_color_picker($hook_suffix) {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'pfp_enqueue_color_picker');

function pfp_color_picker_input($option_name, $default_color = '#000000') {
    $color = get_option($option_name, $default_color);
    ?>
    <input type="text" name="<?php echo esc_attr($option_name); ?>" value="<?php echo esc_attr($color); ?>" class="pfp-color-picker" data-default-color="<?php echo esc_attr($default_color); ?>" />
    <!-- <span class="pfp-color-preview" style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr($color); ?>; border: 1px solid #ccc; margin-left: 10px;"></span> -->
    <script>
        jQuery(document).ready(function($) {
            $('input[name="<?php echo esc_js($option_name); ?>"]').wpColorPicker({
                change: function(event, ui) {
                    $(this).next('.pfp-color-preview').css('background-color', ui.color.toString());
                },
                clear: function() {
                    $(this).next('.pfp-color-preview').css('background-color', '');
                }
            });
        });
    </script>
    <?php
}

function pfp_subheading_font_color_input() {
    pfp_color_picker_input('pfp_subheading_font_color', '#000000');
}

function pfp_pagination_font_color_input() {
    pfp_color_picker_input('pfp_pagination_font_color', '#000000');
}

function pfp_pagination_active_font_color_input() {
    pfp_color_picker_input('pfp_pagination_active_font_color', '#000000');
}

function pfp_pagination_bg_color_input() {
    pfp_color_picker_input('pfp_pagination_bg_color', '#FFFFFF');
}

function pfp_pagination_active_bg_color_input() {
    pfp_color_picker_input('pfp_pagination_active_bg_color', '#FFFFFF');
}

function pfp_pp_container_bg_color_input() {
    pfp_color_picker_input('pfp_pp_container_bg_color', '#FFFFFF');
}

function pfp_filter_container_bg_color_input() {
    pfp_color_picker_input('pfp_filter_container_bg_color', '#FFFFFF');
}

function pfp_post_item_bg_color_input() {
    pfp_color_picker_input('pfp_post_item_bg_color', '#FFFFFF');
}

function pfp_selected_category_heading_color_input() {
    pfp_color_picker_input('pfp_selected_category_heading_color', '#000000');
}

function pfp_tab_item_color_input() {
    pfp_color_picker_input('pfp_tab_item_color', '#000000');
}

function pfp_post_item_heading_color_input() {
    pfp_color_picker_input('pfp_post_item_heading_color', '#000000');
}

function pfp_post_item_text_color_input() {
    pfp_color_picker_input('pfp_post_item_text_color', '#000000');
}

function pfp_reset_settings() {
    // Define default values for all settings
    $defaults = array(
        'pfp_predefined_key' => '',
        'pfp_category_title_font_size' => '20',
        'pfp_tablet_category_title_font_size' => '18',
        'pfp_mobile_category_title_font_size' => '16',
        'pfp_tab_font_size' => '14',
        'pfp_tablet_tab_font_size' => '12',
        'pfp_mobile_tab_font_size' => '10',
        'pfp_post_title_font_size' => '24',
        'pfp_tablet_post_title_font_size' => '20',
        'pfp_mobile_post_title_font_size' => '18',
        'pfp_post_excerpt_font_size' => '14',
        'pfp_tablet_post_excerpt_font_size' => '12',
        'pfp_mobile_post_excerpt_font_size' => '10',
        'pfp_pp_container_border_radius' => '0',
        'pfp_filter_container_border_radius' => '0',
        'pfp_post_item_border_radius' => '0',
        'pfp_post_item_img_border_radius' => '0',
        'pfp_pp_container_bg_color' => '#FFFFFF',
        'pfp_filter_container_bg_color' => '#FFFFFF',
        'pfp_post_item_bg_color' => '#FFFFFF',
        'pfp_pagination_bg_color' => '#FFFFFF',
        'pfp_pagination_active_bg_color' => '#000000',
        'pfp_selected_category_heading_color' => '#000000',
        'pfp_tab_item_color' => '#000000',
        'pfp_post_item_heading_color' => '#000000',
        'pfp_post_item_text_color' => '#000000',
        'pfp_subheading_font_color' => '#000000',
        'pfp_pagination_font_color' => '#000000',
        'pfp_pagination_active_font_color' => '#FFFFFF',
        'pfp_posts_per_row' => '4',
        'pfp_tablet_posts_per_row' => '2',
        'pfp_mobile_posts_per_row' => '1',
        'pfp_posts_per_page' => '16',
        'pfp_desktop_image_height' => '200',
        'pfp_tablet_image_height' => '150',
        'pfp_mobile_image_height' => '100',
        'pfp_heading_font_family' => '',
        'pfp_body_font_family' => '',
        'pfp_tag_label' => '',
        'pfp_produce_type_label' => '',
        'pfp_custom_tag_label' => '',
    );

    // Reset each setting to its default value
    foreach ($defaults as $key => $value) {
        update_option($key, $value);
    }

    wp_send_json_success();
}
add_action('wp_ajax_pfp_reset_settings', 'pfp_reset_settings');

// Create the 'State' taxonomy
function create_state_taxonomy() {
    $labels = array(
        'name'              => _x('Custom Tags', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x('Custom Tags', 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search Custom Tags', 'textdomain'),
        'all_items'         => __('All Custom Tags', 'textdomain'),
        'parent_item'       => __('Parent Custom Tags', 'textdomain'),
        'parent_item_colon' => __('Parent Custom Tags:', 'textdomain'),
        'edit_item'         => __('Edit Custom Tags', 'textdomain'),
        'update_item'       => __('Update Custom Tags', 'textdomain'),
        'add_new_item'      => __('Add New Custom Tags', 'textdomain'),
        'new_item_name'     => __('New Custom Tags Name', 'textdomain'),
        'menu_name'         => __('Custom Tags', 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true, // Like categories
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'custom_tag'),
    );

    register_taxonomy('custom_tag', array('post'), $args);
}
add_action('init', 'create_state_taxonomy', 0);

// Create a custom meta box for 'State' taxonomy
function add_state_meta_box() {
    add_meta_box(
        'state_meta_box',            // ID of the meta box
        'Custom Tags',               // Title
        'display_state_meta_box',    // Callback function
        'post',                      // Post type
        'side',                      // Context
        'default'                    // Priority
    );
}
add_action('add_meta_boxes', 'add_state_meta_box');

function display_state_meta_box($post) {
    $states = get_terms(array(
        'taxonomy'   => 'custom_tag',
        'hide_empty' => false,
    ));

    $selected_states = get_the_terms($post->ID, 'custom_tag');
    $selected_states = !empty($selected_states) ? wp_list_pluck($selected_states, 'term_id') : array();
    // Retrieve custom tag label from the settings
    $custom_tag_label = get_option('pfp_custom_tag_label', 'Custom Tag'); // Default: 'Custom Tag'

    ?>
    <div id="statediv" class="categorydiv">
        <ul id="state-tabs" class="category-tabs">
            <li class="tabs"><a href="#state-all"><?php echo esc_html__('All ', 'text-domain') . esc_html($custom_tag_label); ?></a></li>
        </ul>
        <div id="state-all" class="tabs-panel">
            <input type="text" id="state-search" placeholder="<?php echo esc_html__('Search states...', 'text-domain'); ?>" style="margin-bottom: 10px; width: 100%;" />
            <ul id="statechecklist" class="categorychecklist form-no-clear">
                <?php foreach ($states as $state) : ?>
                    <li class="state-item">
                        <label>
                            <input type="checkbox" name="state[]" value="<?php echo esc_attr($state->term_id); ?>" <?php checked(in_array($state->term_id, $selected_states)); ?> />
                            <?php echo esc_html($state->name); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <script>
        document.getElementById('state-search').addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            var items = document.querySelectorAll('#statechecklist .state-item');
            items.forEach(function(item) {
                var label = item.querySelector('label').textContent.toLowerCase();
                if (label.indexOf(filter) > -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
    <?php
}


function save_state_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['state'])) {
        $state_ids = array_map('intval', $_POST['state']);
        wp_set_post_terms($post_id, $state_ids, 'custom_tag');
    } else {
        wp_set_post_terms($post_id, array(), 'custom_tag');
    }
}
add_action('save_post', 'save_state_meta_box');



// Create the 'Terms' taxonomy
function create_terms_taxonomy() {
    $labels = array(
        'name'              => _x('Terms', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x('Term', 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search Terms', 'textdomain'),
        'all_items'         => __('All Terms', 'textdomain'),
        'parent_item'       => __('Parent Term', 'textdomain'),
        'parent_item_colon' => __('Parent Term:', 'textdomain'),
        'edit_item'         => __('Edit Term', 'textdomain'),
        'update_item'       => __('Update Term', 'textdomain'),
        'add_new_item'      => __('Add New Term', 'textdomain'),
        'new_item_name'     => __('New Term Name', 'textdomain'),
        'menu_name'         => __('Term', 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true, // Like categories
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'term'),
    );

    register_taxonomy('term', array('post'), $args);
}
add_action('init', 'create_terms_taxonomy', 0);

// Create a custom meta box for 'Terms' taxonomy
function add_terms_meta_box() {
    add_meta_box(
        'terms_meta_box',            // ID of the meta box
        'Terms',                     // Title
        'display_terms_meta_box',    // Callback function
        'post',                      // Post type
        'side',                      // Context
        'default'                    // Priority
    );
}
add_action('add_meta_boxes', 'add_terms_meta_box');

// Render the 'Terms' taxonomy meta box with input field and dynamic suggestions
function display_terms_meta_box($post) {
    // Get the 'Terms' terms
    $terms = get_terms(array(
        'taxonomy'   => 'term',
        'hide_empty' => false,
    ));

    // Get selected terms
    $selected_terms = get_the_terms($post->ID, 'term');
    $selected_terms = !empty($selected_terms) ? wp_list_pluck($selected_terms, 'term_id') : array();

    // Create an array of term names for filtering
    $term_names = wp_list_pluck($terms, 'name');
    ?>
    <div id="termdiv" class="categorydiv">
        <ul id="term-tabs" class="category-tabs">
            <li class="tabs"><a href="#term-all"><?php _e('All Terms'); ?></a></li>
        </ul>
        <div id="term-all" class="tabs-panel">
            <!-- Input field to filter terms -->
            <label for="term-search"><?php _e('Search for terms:'); ?></label>
            <input type="text" id="term-search" name="term_search" placeholder="Start typing to search...">

            <!-- List of selected terms -->
            <ul id="term-suggestions" class="categorychecklist form-no-clear">
                <?php 
                // Display selected terms by default
                foreach ($terms as $term) : 
                    $is_selected = in_array($term->term_id, $selected_terms);
                    if ($is_selected) : 
                ?>
                    <li class="term-item" data-term-id="<?php echo esc_attr($term->term_id); ?>">
                        <label>
                            <input type="checkbox" name="term[]" value="<?php echo esc_attr($term->term_id); ?>" <?php checked($is_selected); ?> />
                            <?php echo esc_html($term->name); ?>
                        </label>
                    </li>
                <?php endif; endforeach; ?>
            </ul>

            <!-- List of unselected terms (initially hidden, shown when searching) -->
            <ul id="term-suggestions-other" class="categorychecklist form-no-clear" style="display:none;">
                <?php 
                foreach ($terms as $term) : 
                    $is_selected = in_array($term->term_id, $selected_terms);
                    if (!$is_selected) : 
                ?>
                    <li class="term-item" data-term-id="<?php echo esc_attr($term->term_id); ?>">
                        <label>
                            <input type="checkbox" name="term[]" value="<?php echo esc_attr($term->term_id); ?>" <?php checked($is_selected); ?> />
                            <?php echo esc_html($term->name); ?>
                        </label>
                    </li>
                <?php endif; endforeach; ?>
            </ul>
        </div>
    </div>



    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Get the search input field and suggestions list
            const searchInput = document.getElementById('term-search');
            const termSuggestions = document.getElementById('term-suggestions');
            const termSuggestionsOther = document.getElementById('term-suggestions-other');
            const allTerms = Array.from(termSuggestionsOther.getElementsByClassName('term-item'));

            // Initially show the selected terms and hide unselected terms
            termSuggestionsOther.style.display = 'none';

            // Function to filter terms based on input
            searchInput.addEventListener('input', function() {
                const searchQuery = searchInput.value.toLowerCase();

                let hasMatchingTerms = false;
                allTerms.forEach(function(termItem) {
                    const termName = termItem.textContent.toLowerCase();
                    if (termName.includes(searchQuery)) {
                        termItem.style.display = 'block'; // Show matching terms
                        hasMatchingTerms = true;
                    } else {
                        termItem.style.display = 'none'; // Hide non-matching terms
                    }
                });

                // Show or hide the unselected terms list based on search results
                if (hasMatchingTerms || searchQuery.length > 0) {
                    termSuggestionsOther.style.display = 'block'; // Show the filtered terms
                } else {
                    termSuggestionsOther.style.display = 'none'; // Hide if no terms match
                }
            });
        });
    </script>
    <?php
}


// Save the 'Terms' taxonomy terms when the post is saved
function save_terms_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['term'])) {
        $term_ids = array_map('intval', $_POST['term']);
        wp_set_post_terms($post_id, $term_ids, 'term');
    } else {
        wp_set_post_terms($post_id, array(), 'term');
    }
}
add_action('save_post', 'save_terms_meta_box');


function pfp_display_filtered_posts() {

    // if (!pfp_is_licensed()) {
    //     return '<div class="pfp-license-notice">Premium features require a valid license. Please enter your license key in the plugin settings.</div>';
    // }


    ob_start(); // Start output buffering



    // Fetch custom background colors from settings
    $pp_container_bg_color = get_option('pfp_pp_container_bg_color', '#FFFFFF');
    $filter_container_bg_color = get_option('pfp_filter_container_bg_color', '#FFFFFF');
    $post_item_bg_color = get_option('pfp_post_item_bg_color', '#FFFFFF');

    // Fetch custom font sizes from settings
    $desktop_size = get_option('pfp_category_title_font_size', '20px');
    $tablet_size = get_option('pfp_tablet_category_title_font_size', '18px');
    $mobile_size = get_option('pfp_mobile_category_title_font_size', '16px');
    $tab_font_size = get_option('pfp_tab_font_size', '14'); // Default: 14px
    $tablet_tab_font_size = get_option('pfp_tablet_tab_font_size', '12'); // Default: 12px
    $mobile_tab_font_size = get_option('pfp_mobile_tab_font_size', '10'); // Default: 10px
    $post_title_font_size = get_option('pfp_post_title_font_size', '24'); // Default: 24px
    $tablet_post_title_font_size = get_option('pfp_tablet_post_title_font_size', '20'); // Default: 20px
    $mobile_post_title_font_size = get_option('pfp_mobile_post_title_font_size', '18'); // Default: 18px
    $post_excerpt_font_size = get_option('pfp_post_excerpt_font_size', '14'); // Default: 14px
    $tablet_post_excerpt_font_size = get_option('pfp_tablet_post_excerpt_font_size', '12'); // Default: 12px
    $mobile_post_excerpt_font_size = get_option('pfp_mobile_post_excerpt_font_size', '10'); // Default: 10px
    // Fetch font family settings
    $heading_font_family = get_option('pfp_heading_font_family', ''); // Default: inherit
    $body_font_family = get_option('pfp_body_font_family', '');       // Default: inherit

    // Fetch custom font colors from settings
    $selected_category_heading_color = get_option('pfp_selected_category_heading_color', '#000000'); // Default: Black
    $tab_item_color = get_option('pfp_tab_item_color', '#000000'); // Default: Black
   // Retrieve the subheading font color from the database
    $subheading_color = get_option('pfp_subheading_font_color', '#000000'); // Default color is black
    
    

  // Fetch the custom taxonomy name from settings
  $taxonomy_name = get_option('pfp_taxonomy_name', 'custom_tag'); // Default: 'state'
  $taxonomy_label = ucfirst($taxonomy_name); // Capitalize the label

  // Fetch the custom "Produce Type" label from settings
  $produce_type_label = get_option('pfp_produce_type_label', 'Produce Type'); // Default: 'Produce Type'

  // Fetch selected categories from settings
  $selected_categories = get_option('pfp_selected_categories', array());

  // Fetch categories that have posts in reverse alphabetical order
  $categories = get_categories(array(
      'hide_empty' => true, // Only show categories that have posts
      'orderby' => 'name',  // Order by name
      'order' => 'DESC',     // Descending order
      'include' => $selected_categories, // Only include selected categories
  ));

  ?>
  <<div class="seasonal-recipes">
  <div class="pp-container" style="background-color: <?php echo esc_attr($pp_container_bg_color); ?>;">
      <!-- Heading for Selected Category -->
      <h3 id="selected-category-heading" class="selected-category-heading" style="color: <?php echo esc_attr($selected_category_heading_color); ?>;">
         
      </h3>


            <style>
                /* Add the CSS here */
        .filter-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
 /* Default font size */
 #selected-category-heading {
                font-size: <?php echo esc_attr($desktop_size); ?>px;
            }

            /* Tablet View */
            @media (max-width: 1024px) {
                #selected-category-heading {
                    font-size: <?php echo esc_attr($tablet_size); ?>px;
                }
            }

            /* Mobile View */
            @media (max-width: 767px) {
                #selected-category-heading {
                    font-size: <?php echo esc_attr($mobile_size); ?>px;
                }
            }

    
   /* Apply image height settings */
   .post-item img {
        height: <?php echo esc_attr(get_option('pfp_desktop_image_height', 200)); ?>px;
        object-fit: cover;
        width: 100%;
    }

    @media (max-width: 1024px) {
        .post-item img {
            height: <?php echo esc_attr(get_option('pfp_tablet_image_height', 150)); ?>px;
        }
    }

    @media (max-width: 768px) {
        .post-item img {
            height: <?php echo esc_attr(get_option('pfp_mobile_image_height', 100)); ?>px;
        }
    }

            
  
                   /* Heading font family */
        #selected-category-heading,
        .tab-item,
        #available-posts,
        #user-state,
        #month,.filter-dropdown label,
        .post-item h3 {
            font-family: <?php echo $heading_font_family ? esc_attr($heading_font_family) : 'inherit'; ?>;
        }
       
        .filter-dropdown label{
            color: <?php echo esc_attr($subheading_color); ?> !important;
        }



        /* Body font family */
        .post-item p {
            font-family: <?php echo $body_font_family ? esc_attr($body_font_family) : 'inherit'; ?>;
        }

        .tab-item {
    font-size: <?php echo esc_attr($tab_font_size); ?>px;
}

.tab-item.active {
    font-size: <?php echo esc_attr($tab_font_size); ?>px !important;
}


@media (max-width: 1024px) {
    .tab-item {
        font-size: <?php echo esc_attr($tablet_tab_font_size); ?>px;
    }

    .tab-item.active {
        font-size: <?php echo esc_attr($tablet_tab_font_size); ?>px !important;
    }
}

@media (max-width: 768px) { /* Tablet View */



    .tab-item {
        font-size: <?php echo esc_attr($tablet_tab_font_size); ?>px;
    }

    .tab-item.active {
        font-size: <?php echo esc_attr($tablet_tab_font_size); ?>px !important;
    }
}

@media (max-width: 480px) { /* Mobile View */
    .tab-item {
        font-size: <?php echo esc_attr($mobile_tab_font_size); ?>px;
    }

    .tab-item.active {
        font-size: <?php echo esc_attr($mobile_tab_font_size); ?>px !important;
    }
}

</style>

<div class="filter-container" style="background-color: <?php echo esc_attr($filter_container_bg_color); ?>;">
    <!-- Tabs for Categories -->
    <?php if ($categories): ?>
        <div class="tabs">
             <!-- Left Arrow for Mobile -->
    <div class="tab-arrow left-arrow" onclick="scrollTabs(-1)">&#10094;</div>
            <ul class="tab-list" style="margin:0px;">
                <?php foreach ($categories as $index => $category): ?>
                    <li data-category-id="<?php echo esc_attr($category->term_id); ?>" 
                    class="tab-item <?php echo $index === 0 ? 'active' : ''; ?>"
                    style="color: <?php echo esc_attr($tab_item_color); ?>;">
                        <?php echo esc_html($category->name); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
             <!-- Right Arrow for Mobile -->
    <div class="tab-arrow right-arrow" onclick="scrollTabs(1)">&#10095;</div>
        </div>
                <?php endif; ?>

                <div class="filter-options">
                    <!-- Dropdown for Available Posts -->
                    <div class="filter-dropdown">
                        <label for="available-posts"><?php echo esc_html($produce_type_label); ?></label>
                        <select id="available-posts">
                            <option value="">Select post</option>
                        </select>
                    </div>

                    <!-- Dropdowns will be populated dynamically based on selected category -->
                    <div id="dynamic-filters">
                        <!-- Placeholder for dynamically populated dropdowns -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Container to display the filtered posts -->
        <div id="posts-container"></div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const tabItems = document.querySelectorAll(".tab-item");
        const leftArrow = document.querySelector(".tab-arrow.left-arrow");
        const rightArrow = document.querySelector(".tab-arrow.right-arrow");
    
        let activeIndex = Array.from(tabItems).findIndex(tab => tab.classList.contains("active"));
    
        function activateTab(index) {
            if (index >= 0 && index < tabItems.length) {
                tabItems.forEach(tab => tab.classList.remove("active"));
                tabItems[index].classList.add("active");
    
                // Scroll to the active tab (optional)
                tabItems[index].scrollIntoView({
                    behavior: "smooth",
                    inline: "center"
                });
    
                activeIndex = index;
    
                // You can also trigger your category filtering logic here if needed
                // Example: triggerCategoryChange(tabItems[index].dataset.categoryId);
            }
        }
    
        if (leftArrow) {
            leftArrow.addEventListener("click", () => {
                activateTab(activeIndex - 1);
            });
        }
    
        if (rightArrow) {
            rightArrow.addEventListener("click", () => {
                activateTab(activeIndex + 1);
            });
        }
    
        // Optional: Add click behavior on tabs to set active index
        tabItems.forEach((tab, index) => {
            tab.addEventListener("click", () => {
                activateTab(index);
            });
        });
    });
    
</script>
    <style>

        /* admin-styles.css */
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab {
    padding: 10px 15px;
    text-decoration: none;
    border: 1px solid #ccc;
    border-bottom: none;
    background-color: #f1f1f1;
    margin-right: 5px;
    cursor: pointer;
}

.nav-tab-active {
    background-color: #fff;
    border-bottom: 1px solid #fff;
}

.tab-content {
    display: none;
    padding: 20px;
    border: 1px solid #ccc;
    background-color: #fff;
}

.tab-content.active {
    display: block;
}
        .post-item {
            background-color: <?php echo esc_attr($post_item_bg_color); ?>;
        }
        .post-item h3 {
            font-size: <?php echo esc_attr($post_title_font_size); ?>px;
        }
        .post-item p {
            font-size: <?php echo esc_attr($post_excerpt_font_size); ?>px;
        }

             /* Tablet view */
             @media only screen and (min-width: 768px) and (max-width: 1024px) {
          
         
           
            .post-item h3{
                font-size: <?php echo esc_attr($tablet_post_title_font_size); ?>px;
            }
            .post-item p {
                font-size: <?php echo esc_attr($tablet_post_excerpt_font_size); ?>px;
            }
            .post-grid {
        grid-template-columns: repeat(<?php echo esc_attr(get_option('pfp_tablet_posts_per_row', 2)); ?>, 1fr) !important;
    }
        }

        /* Mobile view */
        @media only screen and (max-width: 767px) {
          
           
            .post-item h3{
                font-size: <?php echo esc_attr($mobile_post_title_font_size); ?>px;
            }
            .post-item p {
                font-size: <?php echo esc_attr($mobile_post_excerpt_font_size); ?>px;
            }
            .post-grid {
        grid-template-columns: repeat(<?php echo esc_attr(get_option('pfp_mobile_posts_per_row', 1)); ?>, 1fr) !important;
    }
    }
        
    </style>

    <script>
    // JavaScript to handle category selection and update heading
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-item');
        const heading = document.getElementById('selected-category-heading');

        // Set the initial heading based on the first tab
        if (tabs.length > 0) {
            const initialCategoryName = tabs[0].textContent; // Get the first category name
            heading.textContent = initialCategoryName;
            heading.style.display = 'block'; // Show the heading
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Get the category name from the clicked tab
                const categoryName = this.textContent;
                // Update the heading text and display it
                heading.textContent = categoryName;
                heading.style.display = 'block'; // Show the heading
            });
        });
    });
    </script>

    <script>
        jQuery(document).ready(function($) {
            // Load posts for the first category by default
            var initialCategoryId = $('.tab-item').first().data('category-id');
            loadPosts(initialCategoryId);
            loadAvailablePosts(initialCategoryId); // Load available posts for the first category
            loadDynamicFilters(initialCategoryId); // Load filters for the first category

            // On tab click, load posts and available posts for the selected category
            $('.tab-item').click(function() {
                $('.tab-item').removeClass('active'); // Remove active class from all tabs
                $(this).addClass('active'); // Add active class to the clicked tab
                var categoryId = $(this).data('category-id');
                loadPosts(categoryId);
                loadAvailablePosts(categoryId); // Load available posts for the selected category
                loadDynamicFilters(categoryId); // Load filters for the selected category
            });

            // On filter change, reload posts based on selected filters
            $(document).on('change', '.filter-dropdown select', function() {
                var userState = $('#user-state').val();
                var month = $('#month').val();
                var availableTerm = $('#available-posts').val();  // The term ID from the dropdown
                var activeCategoryId = $('.tab-item.active').data('category-id');  // Get the active tab category

                loadPosts(activeCategoryId, userState, month, availableTerm);  // Pass the selected term
            });

            function loadPosts(categoryId, userState = '', month = '', availablePost = '', page = 1) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'pfp_filter_posts',
                        category_id: categoryId,
                        user_state: userState,
                        month: month,
                        available_post: availablePost,
                        paged: page,
                    },
                    success: function(response) {
                        console.log(response); // Check what is returned
                        if (response.trim() !== '') {
                            $('#posts-container').html(response);
                            $('.page-link').on('click', function (e) {
                                e.preventDefault();
                                var page = $(this).data('page');
                                loadPosts(categoryId, userState, month, availablePost, page);
                            });
                        } else {
                            $('#posts-container').html('<p>No posts available.</p>');
                        }
                    }
                });
            }

            function loadAvailablePosts(categoryId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'pfp_get_available_terms',  // Use a new action for terms
                        category_id: categoryId
                    },
                    success: function(response) {
                        // Populate the dropdown with terms
                        if (response.trim() === '0') {
                            $('#available-posts').html('<option value="">No terms available</option>');
                        } else {
                            $('#available-posts').html('<option value="">Select your <?php echo esc_html(strtolower($produce_type_label)); ?></option>' + response); // Populate with "Select term"
                        }
                    },
                    error: function() {
                        $('#available-posts').html('<option value="">Error loading terms</option>');
                    }
                });
            }

            function loadDynamicFilters(categoryId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'pfp_get_dynamic_filters',
                        category_id: categoryId
                    },
                    success: function(response) {
                        // Check if the response is "0" and handle it accordingly
                        if (response.trim() === "0") {
                            $('#dynamic-filters').html(''); // Clear the filters if response is "0"
                        } else {
                            $('#dynamic-filters').html(response); // Populate the filters
                        }
                    }
                });
            }
        });
    </script>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');



.pfp-tabs {
    margin: 20px 0;
}


.tab-links {
    list-style: none;
    padding: 0;
    display: flex;
}

.tab-links li {
    margin-right: 10px;
}

.tab-links a {
    text-decoration: none;
    padding: 10px 15px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    color: #333;
    
}



.tab-links .active a {
    background: #fff;
    border-bottom: 1px solid #fff;
    font-weight: bold;
}

.tab-content {
    display: none;
    border: 1px solid #ddd;
    border-top: none;
    padding: 15px;
}

.tab-content.active {
    display: block;
}
.seasonal-receipes {
    max-width: 800px; /* Set a max width for the container */
    margin: 0 auto; /* Center the container */
    padding: 20px; /* Add some padding */
    background: #f9f9f9; /* Light background color */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Soft shadow */
}

h2 {
    text-align: center; /* Center the heading */
    color: #2c3e50; /* Darker text color */
    margin-bottom: 20px; /* Space below the heading */
}

.tabs {
    margin-bottom: 20px; /* Space below the tabs */
}

/*  */

.tab-item {
    margin: 0 10px; /* Space between tabs */
    cursor: pointer; /* Pointer cursor for interactivity */
    padding: 10px 15px; /* Space inside each tab */
    border: 1px solid transparent; /* Border for inactive tabs */
   
}

.filter-options {
    display: flex; /* Flexbox for layout */
    justify-content: space-between; /* Space items evenly */
    align-items: start; /* Center items vertically */
    margin-top: 20px; /* Space above filter options */
    width:90%;
}

.filter-dropdown {
    flex: 1; /* Grow the dropdowns equally */
    /* margin: 0 10px; Space between dropdowns */
    width:60%;
}

.filter-dropdown label {
    display: block; /* Block layout for label */
    /* margin-bottom: 5px; Space below label */
    font-weight: bold; /* Bold label text */
    color: #2c3e50; /* Dark label color */
}

.filter-dropdown select {
    width: 100%; /* Full width for dropdown */
    /* padding: 10px; Space inside dropdown */
    border: 1px solid #ccc; /* Light border */
    font-size: 16px; /* Font size */
    color: #333; /* Dark text color */
    background-color: #fff; /* White background */

}

.filter-dropdown select:focus {
    border-color: #2c3e50; /* Dark border on focus */
    outline: none; /* Remove default outline */
}
.post-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.post-item {
    border: 1px solid #ddd;
    padding: 15px;
    width: calc(33.333% - 20px); /* Adjust for 3 columns */
}
.post-item h3 {
    margin: 0 0 10px;
}


.filter-container{
    /* background-color: #FFF; */
    padding: 30px;
    box-shadow: 0px 4px 30px 0px #0000000F;
    border: 1px solid #10647F1F;

}
.filter-dropdown select{
    padding:0px;
}

select#user-state{
    color:#08303D59;
}
h3#selected-category-heading{
color:  #10647F;

font-weight: 500;
line-height: 74.4px;
text-align: center;

}

li.tab-item{
    width:100%;
    text-align: center;
}

/* Basic styles for tabs */
.tabs {
    margin-bottom: 20px;
}



/* Mobile View */

.tab-item {
    margin-right: 15px;
    cursor: pointer;
    padding: 10px 15px;
    border-bottom: 2px solid transparent; /* Set transparent border */
    color: #08303D59;
   
    
}
.tab-item.active {
    border-bottom: 1px solid #0C2E3A; /* Underline color for active tab */
    border-radius:0px ;
    color: #0C2E3A;
   
    font-size: 14px;
}
select#available-posts,select#user-state ,select#month {
    
    border: none;
    font-size:14px;
    font-weight: 400;
    line-height: 16.8px;
    color:  #0C2E3A;
    box-shadow:none;
}

.vertical-separator {
    width: 1px; /* Width of the separator */
    background-color: #ccc; /* Color of the separator */
    top:10px; 
    height: 60px; /* Height of the separator */
    margin: 15px 15px; /* Margin to space out the separator */
    display: inline-block; /* Makes it behave like an inline element */
}

.post-grid {
    display: grid;
    gap: 20px; /* Space between posts */
    padding: 20px;
}


.posts-section{
    width:100%;
    height:100%;
}

.post-item {
width:100%;
height: 100%; /* Make sure the items take full height */
padding: 20px;
gap: 15px;
opacity: 0px;
box-shadow: 0px 4px 20px 0px #0000000F;
border: 0px;


}
.filter-dropdown option {
    color:  #0C2E3A;
    /* Text color of dropdown options */
}

select#available-posts{
    width:100%;
    box-shadow:none;
}
.woocommerce-js select:focus{
    padding:0px !important;
}
.woocommerce-js select{
    padding-left: 0px !important;
}
.no-post{
 padding:50px;
}

.pagination {
    display: flex;
    justify-content: center; /* Centers the pagination horizontally */
    align-items: center; /* Aligns items vertically if needed */
    gap: 5px; /* Space between pagination links */
    margin: 20px 0; /* Optional: Adds spacing around the pagination */
}

.page-link {
    text-decoration: none;
    padding: 5px 10px;
    border: 1px solid #ddd;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

a.page-link.active {
    color:white;;
}
.page-link.active {
    color: #000;
    font-weight: bold;
}

.ellipsis {
    padding: 5px 10px;
    color: #999;
}


/*responsive side*/


@media only screen and (min-width: 767px) {
    .filter-options {
        height:80px;
        width:100%;
    }

}

@media only screen and (min-width: 425px) and (max-width: 768px) {

    .vertical-separator{
        display: none;
    }
    .post-grid {
    display: grid;
    gap: 10px; /* Space between posts */
    padding:20px;
}


.post-item img{
    width:100% !important;
}
.post-item{
    width:100%;
   gap:20px;
}
.filter-options{
    display:block;
    height:auto;
}
}

@media only screen and (max-width: 424px) {

    .filter-options{
        display: block;
        width:100%;
    }
    .filter-dropdown{
        width:100%;
    }

    .vertical-separator{
        display: none;
    }

    .post-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr); /* 4 columns */
    gap: 10px; /* Space between posts */
    padding:20px;
}
.posts-section{
    width:100%;
    height:100%;
}
.post-item{
    width:100%;
  
}
.post-item img{
    width:100% !important;
}

}

</style>

    <?php

    return ob_get_clean(); // Return the buffered output
}
add_shortcode('pfp_display_filtered_posts', 'pfp_display_filtered_posts');

// New AJAX handler to get available terms based on category
function pfp_get_available_terms() {
    $category_id = intval($_POST['category_id']);
    
    // Fetch the custom "Produce Type" label from settings
    $produce_type_label = get_option('pfp_produce_type_label', 'Produce Type'); // Default: 'Produce Type'

    // Fetch terms from your custom taxonomy (replace 'term' with your actual taxonomy name if different)
    $terms = get_terms(array(
        'taxonomy'   => 'term',
        'hide_empty' => false,
    ));
    
    // Check if terms were found
    if (!empty($terms)) {
        foreach ($terms as $term) {
            // Output each term as an option in the dropdown
            echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
        }
    } else {
        // If no terms found, output a placeholder option
        echo '<option value="">No ' . esc_html(strtolower($produce_type_label)) . ' available</option>';
    }

    wp_die(); // Required to terminate immediately and return a proper response
}
add_action('wp_ajax_pfp_get_available_terms', 'pfp_get_available_terms');
add_action('wp_ajax_nopriv_pfp_get_available_terms', 'pfp_get_available_terms');

function pfp_get_dynamic_filters() {
    $category_id = intval($_POST['category_id']);

    // Fetch the custom tag label value from settings
    $custom_tag_label = get_option('pfp_custom_tag_label', 'Custom Tag'); // Default: 'User State'
     // Fetch the custom tag label value from settings
     $tag_label = get_option('pfp_tag_label', 'Tag Label'); // Default: 'User State'


    // Fetch states associated with the selected category
    $states = get_terms(array(
        'taxonomy'   => 'custom_tag',
        'hide_empty' => true,
        'object_ids' => get_objects_in_term($category_id, 'category'),
    ));

    // Fetch states associated with the selected category
    $mystates = get_terms(array(
        'taxonomy'   => 'custom_tag',
        'hide_empty' => false,
    ));

    // Fetch tags associated with the selected category
    $tags = get_tags(array(
        'hide_empty' => true,
        'object_ids' => get_objects_in_term($category_id, 'category'),
    ));

    // Fetch tags associated with the selected category
    $mytags = get_terms(array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
        'orderby' => 'term_id',
    ));
    
    ob_start(); // Start output buffering

    // Vertical Separator before the custom tag dropdown
    if ($states || $tags) { ?>
        <div class="vertical-separator"></div>
    <?php } ?>

    <!-- Dropdown for Custom Tag Label -->
    <?php if ($states) { ?>
        <div class="filter-dropdown">
            <label for="user-state"><?php echo esc_html($custom_tag_label); ?></label>
            <select id="user-state">
                <option value=""><?php echo esc_html__('Select ', 'text-domain') . esc_html($custom_tag_label); ?></option>
                <?php foreach ($mystates as $state) { ?>
                    <option value="<?php echo esc_attr($state->term_id); ?>"><?php echo esc_html($state->name); ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } ?>

    <!-- Vertical Separator before Month Dropdown -->
    <?php if ($states && $tags) { ?>
        <div class="vertical-separator"></div>
    <?php } ?>

    

    <!-- Dropdown for Month -->
    <?php if ($tags) { ?>
        <div class="filter-dropdown">
            <label for="month"><?php echo esc_html($tag_label); ?></label>
            <select id="month">
            <option value=""><?php echo esc_html__('Select ', 'text-domain') . esc_html($tag_label); ?></option>
                <?php foreach ($mytags as $tag) { ?>
                    <option value="<?php echo esc_attr($tag->term_id); ?>"><?php echo esc_html($tag->name); ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } ?>

    <?php
    echo ob_get_clean(); // Return the buffered output
    wp_die(); // Properly terminate AJAX request
}
add_action('wp_ajax_pfp_get_dynamic_filters', 'pfp_get_dynamic_filters');
add_action('wp_ajax_nopriv_pfp_get_dynamic_filters', 'pfp_get_dynamic_filters');

add_action('wp_ajax_pfp_get_available_posts', 'pfp_get_available_posts');
add_action('wp_ajax_nopriv_pfp_get_available_posts', 'pfp_get_available_posts');

function pfp_get_available_posts() {
    $category_id = intval($_POST['category_id']);
    
    // Fetch posts based on category ID
    $args = array(
        'post_type' => 'post',
        'cat' => $category_id,
        'posts_per_page' => -1,
    );

    $posts = get_posts($args);
    
    // Check if posts were found
    if (!empty($posts)) {
        foreach ($posts as $post) {
            // Output each post as an option
            echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
        }
    } else {
        // If no posts found, output a placeholder option
        echo '<option value="">No posts available</option>';
    }

    wp_die(); // Required to terminate immediately and return a proper response
}


function pfp_filter_posts() {
    error_log(print_r($_POST, true)); // Log the POST data

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';
    $user_state  = isset($_POST['user_state']) ? intval($_POST['user_state']) : '';
    $month       = isset($_POST['month']) ? intval($_POST['month']) : '';
    $available_term = isset($_POST['available_post']) ? intval($_POST['available_post']) : '';
    $paged       = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

    error_log("Category ID: $category_id");
    error_log("User State: $user_state");
    error_log("Month: $month");
    error_log("Available Term: $available_term");
    error_log("Page: $paged");

    $selected_categories = get_option('pfp_selected_categories', array());

    if (!empty($selected_categories)) {
        if (!in_array($category_id, $selected_categories)) {
            echo '<p>No posts found.</p>';
            wp_die();
        }
    }

    $filtered_posts_html = pfp_get_filtered_posts($category_id, $user_state, $month, $available_term, $paged);

    if (!empty($filtered_posts_html)) {
        echo $filtered_posts_html;
    } else {
        echo '<p>No posts found.</p>';
    }

    wp_die();
}

add_action('wp_ajax_pfp_filter_posts', 'pfp_filter_posts');
add_action('wp_ajax_nopriv_pfp_filter_posts', 'pfp_filter_posts');

function pfp_get_filtered_posts($category_id = '', $user_state = '', $month = '', $available_term = '', $paged = 1) {
    // Get the number of posts per row and posts per page from settings
    $posts_per_row = get_option('pfp_posts_per_row', 4); // Default: 4 posts per row
    $posts_per_page = get_option('pfp_posts_per_page', 16); // Default: 16 posts per page
    

    $post_item_heading_color = get_option('pfp_post_item_heading_color', '#000000'); 
    $post_item_text_color = get_option('pfp_post_item_text_color', '#000000');
    $pagination_font_color = esc_attr(get_option('pfp_pagination_font_color', '#000000')); // Default black
    $pagination_active_font_color = esc_attr(get_option('pfp_pagination_active_font_color', '#ffffff'));

    // Setup the query parameters
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => $posts_per_page, // Use the setting for posts per page
        'paged'          => $paged, // Current page number
        'tax_query'      => array('relation' => 'AND'), // Taxonomy query
    );

    // Filter by category
    if ($category_id) {
        $args['tax_query'][] = array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        );
    }

      // Filter by user state
      if ($user_state) {
        $args['tax_query'][] = array(
            'taxonomy' => 'custom_tag',
            'field'    => 'term_id',
            'terms'    => $user_state,
        );
    }

    // Filter by month
    if ($month) {
        $args['tax_query'][] = array(
            'taxonomy' => 'post_tag',  // Adjust this if you're using a custom taxonomy for months
            'field'    => 'term_id',
            'terms'    => $month,
        );
    }

    // Filter by available term (if set)
    if ($available_term) {
        $args['tax_query'][] = array(
            'taxonomy' => 'term',  // Custom taxonomy for terms
            'field'    => 'term_id',
            'terms'    => $available_term,
        );
    }

    // Use WP_Query to fetch the posts
    $query = new WP_Query($args);

    ob_start(); // Start output buffering

   
    ?>
   
    <?php

    if ($query->have_posts()) {
        // Pass the posts_per_row value to the grid container
        echo '<div class="post-grid" style="grid-template-columns: repeat(' . esc_attr($posts_per_row) . ', 1fr);">'; // Use the setting for posts per row
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <a href="<?php the_permalink(); ?>" class="post-link" style="text-decoration: none;">
                <div class="posts-section">
                    <div class="post-item">
                        <?php the_post_thumbnail('medium'); ?>
                        <h3 style="text-align: center; font-weight: 700; color: <?php echo esc_attr($post_item_heading_color); ?>;">
        <?php the_title(); ?>
        </h3>

        <p style="color: <?php echo esc_attr($post_item_text_color); ?>; text-align: center; font-weight: 400;">
            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
        </p>
                    </div>
                </div>
            </a>
            <?php
        }
        echo '</div>'; // End the grid container

        // Pagination
        $total_pages = $query->max_num_pages;
        if ($total_pages > 1) {
            echo '<div class="pagination">';

            // Previous button
            if ($paged > 1) {
                echo '<a href="#" class="page-link" data-page="' . ($paged - 1) . '" style="background-color:' . $pagination_bg_color . '; color:' . $pagination_font_color . ';">&laquo; Previous</a>';
            }

            // Display first page and ellipsis if needed
            if ($paged > 3) {
                echo '<a href="#" class="page-link" data-page="1">1</a>';
                if ($paged > 4) {
                    echo '<span class="ellipsis">...</span>';
                }
            }

            // Display current range of pages
            $range = 2; // Number of pages around the current page
            $start = max(1, $paged - $range);
            $end = min($total_pages, $paged + $range);

            $pagination_bg_color = esc_attr(get_option('pfp_pagination_bg_color', '#ffffff'));
            $pagination_active_bg_color = esc_attr(get_option('pfp_pagination_active_bg_color', '#000000'));

            for ($i = $start; $i <= $end; $i++) {
                $active = $i == $paged ? 'active' : '';
                $bg_color = $i == $paged ? $pagination_active_bg_color : $pagination_bg_color;
                $font_color = $i == $paged ? $pagination_active_font_color : $pagination_font_color;
            
                echo '<a href="#" class="page-link ' . $active . '" data-page="' . $i . '" 
                style="background-color:' . $bg_color . '; color:' . $font_color . ';">' . $i . '</a>';
            }

            // Display ellipsis and last page if needed
            if ($paged < $total_pages - 2) {
                if ($paged < $total_pages - 3) {
                    echo '<span class="ellipsis">...</span>';
                }
                echo '<a href="#" class="page-link" data-page="' . $total_pages . '" style="background-color:' . $pagination_bg_color . '; color:' . $pagination_font_color . ';">' . $total_pages . '</a>';
            }

            // Next button
            if ($paged < $total_pages) {
                echo '<a href="#" class="page-link" data-page="' . ($paged + 1) . '" style="background-color:' . $pagination_bg_color . '; color:' . $pagination_font_color . ';">Next &raquo;</a>';
            }

            echo '</div>';
        }

        wp_reset_postdata(); // Reset the global $post object
    } else {
        echo '<p class="no-post">No posts found matching your criteria.</p>';
    }

    return ob_get_clean(); // Return the buffered output
}

// AJAX action hooks
add_action('wp_ajax_pfp_filter_posts', 'pfp_filter_posts');
add_action('wp_ajax_nopriv_pfp_filter_posts', 'pfp_filter_posts');
add_action('wp_ajax_pfp_get_available_posts', 'pfp_get_available_posts');
add_action('wp_ajax_nopriv_pfp_get_available_posts', 'pfp_get_available_posts');

// Enqueue necessary scripts and styles for tabs
function pfp_enqueue_tab_scripts() {
    wp_enqueue_style('pfp-tab-styles', plugin_dir_url(__FILE__) . '/post-filtering-plugin.css');
    wp_enqueue_script('pfp-tab-scripts', plugin_dir_url(__FILE__) . '/post-filtering-plugin.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'pfp_enqueue_tab_scripts');