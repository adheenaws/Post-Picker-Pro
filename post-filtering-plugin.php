<?php
/*
Plugin Name: Post Picker
Description: A plugin to filter posts by tags, custom tag, and produce type.Shortcode [pfp_display_filtered_posts].
Version: 1.0
Author: Weamse
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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

    // License Settings Page (added under Settings menu)
    add_options_page(
        'My Plugin License Settings',  // Page title
        'My Plugin Settings',          // Menu title
        'manage_options',              // Capability required to access the page
        'my-plugin-license',           // Slug for the page
        'my_plugin_license_settings_page' // Callback function that displays the page content
    );
}
add_action('admin_menu', 'pfp_add_settings_pages');


// Render the settings page with tabs
function pfp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Post Picker Settings</h1>

        

        <div class="pfp-settings-container">
            <!-- Left Side: Settings Form -->
            <div class="pfp-settings-form">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-general" class="nav-tab nav-tab-active">General Settings</a>
                    <a href="#tab-font-sizes" class="nav-tab">Font Sizes</a>
                    <a href="#tab-colors" class="nav-tab">Colors</a>
                    <a href="#tab-layout" class="nav-tab">Layout</a>
                </h2>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('pfp_settings_group'); // Settings group

                    // General Settings Tab
                    echo '<div id="tab-general" class="tab-content active">';
                    do_settings_sections('pfp-settings-general'); // General settings section
                    echo '</div>';

                    // Font Sizes Tab
                    echo '<div id="tab-font-sizes" class="tab-content">';
                    do_settings_sections('pfp-settings-font-sizes'); // Font sizes section
                    echo '</div>';

                    // Colors Tab
                    echo '<div id="tab-colors" class="tab-content">';
                    do_settings_sections('pfp-settings-colors'); // Colors section
                    echo '</div>';

                    // Layout Tab
                    echo '<div id="tab-layout" class="tab-content">';
                    do_settings_sections('pfp-settings-layout'); // Layout section
                    echo '</div>';

                    submit_button();
                    ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Switching JavaScript -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.nav-tab-wrapper a').click(function(e) {
                e.preventDefault();
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });

            // Show the first tab by default
            $('.nav-tab-wrapper a:first').click();
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
</style>

    <?php
}



// Register plugin settings
function pfp_register_settings() {
  
    // Register the predefined key setting
    register_setting('pfp_settings_group', 'pfp_predefined_key', 'sanitize_text_field');

    // Add a new section for the predefined key
    add_settings_section(
        'pfp_predefined_key_section',
        'License Key',
        'pfp_predefined_key_section_text',
        'pfp-settings-general'
    );

    // Add the predefined key input field
    add_settings_field(
        'pfp_predefined_key',
        'License Key',
        'pfp_predefined_key_input',
        'pfp-settings-general',
        'pfp_predefined_key_section'
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
    add_settings_field('pfp_tag_label', 'General Tag Label', 'pfp_tag_label_input', 'pfp-settings-general', 'pfp_general_section');
    add_settings_field('pfp_produce_type_label', 'Term Label', 'pfp_produce_type_label_input', 'pfp-settings-general', 'pfp_general_section');
    add_settings_field('pfp_custom_tag_label', 'Custom Tag Label', 'pfp_custom_tag_label_input', 'pfp-settings-general', 'pfp_general_section'); // New Field

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

function pfp_pagination_font_color_input() {
    $color = get_option('pfp_pagination_font_color', '#000000'); // Default to black
    echo '<input type="text" class="color-picker" name="pfp_pagination_font_color" value="' . esc_attr($color) . '" placeholder="#000000" data-default-color="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_pagination_active_font_color_input() {
    $color = get_option('pfp_pagination_active_font_color', '#000000'); // Default color is black
    echo '<input type="text" name="pfp_pagination_active_font_color" value="' . esc_attr($color) . '" class="my-color-field" placeholder="#000000" data-default-color="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

// Function for the new field
function pfp_custom_tag_label_input() {
    $custom_tag_label = get_option('pfp_custom_tag_label', 'Custom Tag'); // Default: 'Custom Tag'
    echo '<input id="pfp_custom_tag_label" name="pfp_custom_tag_label" type="text" value="' . esc_attr($custom_tag_label) . '" />';
}

function pfp_subheading_font_color_input() {
    $subheading_color = get_option('pfp_subheading_font_color', '#000000'); // Default color is black
    echo '<input type="text" name="pfp_subheading_font_color" value="' . esc_attr($subheading_color) . '" class="color-picker" placeholder="#000000" data-default-color="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($subheading_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}


// Callback function for the predefined key section text
function pfp_predefined_key_section_text() {
    echo '<p>Enter the license key.</p>';
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
    $value = get_option('pfp_category_title_font_size', '');
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

function pfp_pagination_bg_color_input() {
    $color = get_option('pfp_pagination_bg_color', '#FFFFFF'); // Default color is white
    echo '<input type="text" name="pfp_pagination_bg_color" value="' . esc_attr($color) . '" class="color-picker" placeholder="#FFFFFF" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_pagination_active_bg_color_input() {
    $color = get_option('pfp_pagination_active_bg_color', '#FFFFFF'); // Default color is white
    echo '<input type="text" name="pfp_pagination_active_bg_color" value="' . esc_attr($color) . '" class="color-picker" placeholder="#FFFFFF" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
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
    $produce_type_label = get_option('pfp_produce_type_label', 'Produce Type'); // Default: 'Produce Type'
    echo '<input id="pfp_produce_type_label" name="pfp_produce_type_label" type="text" value="' . esc_attr($produce_type_label) . '" />';
}

// Section text
function pfp_section_text() {
    echo '<p>Enter the custom names for the taxonomy and tag label.</p>';
}


// Tag label input field
function pfp_tag_label_input() {
    $tag_label = get_option('pfp_tag_label', 'Month'); // Default: 'Month'
    echo '<input id="pfp_tag_label" name="pfp_tag_label" type="text" value="' . esc_attr($tag_label) . '" />';
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

function pfp_pp_container_bg_color_input() {
    $pp_container_bg_color = get_option('pfp_pp_container_bg_color', '#FFFFFF'); // Default: White
    echo '<input id="pfp_pp_container_bg_color" name="pfp_pp_container_bg_color" type="text" value="' . esc_attr($pp_container_bg_color) . '" class="pfp-color-field" placeholder="#FFFFFF" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($pp_container_bg_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_filter_container_bg_color_input() {
    $filter_container_bg_color = get_option('pfp_filter_container_bg_color', '#FFFFFF'); // Default: White
    echo '<input id="pfp_filter_container_bg_color" name="pfp_filter_container_bg_color" type="text" value="' . esc_attr($filter_container_bg_color) . '" class="pfp-color-field" placeholder="#FFFFFF" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($filter_container_bg_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_post_item_bg_color_input() {
    $post_item_bg_color = get_option('pfp_post_item_bg_color', '#FFFFFF'); // Default: White
    echo '<input id="pfp_post_item_bg_color" name="pfp_post_item_bg_color" type="text" value="' . esc_attr($post_item_bg_color) . '" class="pfp-color-field" placeholder="#FFFFFF" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($post_item_bg_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
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

function pfp_selected_category_heading_color_input() {
    $selected_category_heading_color = get_option('pfp_selected_category_heading_color', '#000000'); // Default: Black
    echo '<input type="text" id="pfp_selected_category_heading_color" name="pfp_selected_category_heading_color" value="' . esc_attr($selected_category_heading_color) . '" class="color-picker" placeholder="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($selected_category_heading_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_tab_item_color_input() {
    $tab_item_color = get_option('pfp_tab_item_color', '#000000'); // Default: Black
    echo '<input type="text" id="pfp_tab_item_color" name="pfp_tab_item_color" value="' . esc_attr($tab_item_color) . '" class="color-picker" placeholder="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($tab_item_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_post_item_heading_color_input() {
    $post_item_heading_color = get_option('pfp_post_item_heading_color', '#000000'); // Default: Black
    echo '<input type="text" id="pfp_post_item_heading_color" name="pfp_post_item_heading_color" value="' . esc_attr($post_item_heading_color) . '" class="color-picker" placeholder="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($post_item_heading_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}

function pfp_post_item_text_color_input() {
    $post_item_text_color = get_option('pfp_post_item_text_color', '#000000'); // Default: Black
    echo '<input type="text" id="pfp_post_item_text_color" name="pfp_post_item_text_color" value="' . esc_attr($post_item_text_color) . '" class="color-picker" placeholder="#000000" />';
    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($post_item_text_color) . '; border: 1px solid #ccc; margin-left: 10px;"></span>';
}




function pfp_enqueue_color_picker($hook) {
    // Only load on the plugin's settings page
    if ($hook === 'toplevel_page_pfp-settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('pfp-color-picker-init', plugin_dir_url(__FILE__) . 'pfp-color-picker.js', array('wp-color-picker'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'pfp_enqueue_color_picker');


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
    ob_start(); // Start output buffering

    // Fetch the predefined key from settings
    $predefined_key = get_option('pfp_predefined_key', '');

    // Check if the predefined key matches
    if ($predefined_key === 'YOUR_PREDEFINED_KEY') {
        // Display custom fields only if the key matches
        echo '<div class="custom-fields">';
        echo '<p>Custom fields go here.</p>';
        echo '</div>';
    } 

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

    // Fetch categories that have posts in reverse alphabetical order
    $categories = get_categories(array(
        'hide_empty' => true, // Only show categories that have posts
        'orderby' => 'name',  // Order by name
        'order' => 'DESC',     // Descending order
    ));

    ?>
    <div class="seasonal-recipes">
        <div class="pp-container" style="background-color: <?php echo esc_attr($pp_container_bg_color); ?>;">
            <!-- Heading for Selected Category -->
            <h3 id="selected-category-heading" class="selected-category-heading" style="font-size: <?php echo esc_attr($desktop_size); ?>px; color: <?php echo esc_attr($selected_category_heading_color); ?>;"></h3>

            <style>
                /* Add the CSS here */
        .filter-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
             #selected-category-heading {
            font-size: <?php echo esc_attr($desktop_size); ?>px;
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

            
        @media (max-width: 1024px) {
            #selected-category-heading {
                font-size: <?php echo esc_attr($tablet_size ); ?>px;
            }
        }
        @media (max-width: 768px) {
            #selected-category-heading {
                font-size: <?php echo esc_attr($mobile_size); ?>px;
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
            <ul class="tab-list" style="margin:0px;">
                <?php foreach ($categories as $index => $category): ?>
                    <li data-category-id="<?php echo esc_attr($category->term_id); ?>" 
                    class="tab-item <?php echo $index === 0 ? 'active' : ''; ?>"
                    style="color: <?php echo esc_attr($tab_item_color); ?>;">
                        <?php echo esc_html($category->name); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
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
    border-radius: 10px; /* Rounded corners */
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

.tab-list {
    list-style-type: none; /* Remove list styles */
    padding: 0; /* Remove default padding */
    display: flex; /* Horizontal layout */
    justify-content: center; /* Center the tabs */
}

.tab-item {
    margin: 0 10px; /* Space between tabs */
    cursor: pointer; /* Pointer cursor for interactivity */
    padding: 10px 15px; /* Space inside each tab */
    border: 1px solid transparent; /* Border for inactive tabs */
    border-radius: 5px; /* Rounded corners */
   
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
    border-radius: 5px; /* Rounded corners */
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
    border-radius: 5px;
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
    border-radius: 12px;

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
.tab-list {
    list-style-type: none;
    padding: 0;
    display: flex;
}
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
border-radius: 12px 12px 12px 12px;
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
    border-radius: 5px;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}
a.page-link {
    
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
    /* grid-template-columns: repeat(2, 1fr); /* 4 columns */
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

    $filtered_posts_html = pfp_get_filtered_posts($category_id, $user_state, $month, $available_term, $paged);

    if (!empty($filtered_posts_html)) {
        echo $filtered_posts_html;
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
            <a href="<?php the_permalink(); ?>" class="post-link">
                <div class="posts-section">
                    <div class="post-item">
                        <?php the_post_thumbnail('medium', ['style' => 'border-radius: 8px;']); ?>
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