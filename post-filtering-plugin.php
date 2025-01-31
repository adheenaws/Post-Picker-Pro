<?php
/*
Plugin Name: Post Picker
Description: A plugin to filter posts by tags, state, and produce type.Shortcode [pfp_display_filtered_posts].
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
function pfp_add_settings_page() {
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
add_action('admin_menu', 'pfp_add_settings_page');

// Render the settings page
function pfp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Post Picker Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pfp_settings_group'); // Settings group
            do_settings_sections('pfp-settings');  // Settings page slug
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register plugin settings
function pfp_register_settings() {
    register_setting('pfp_settings_group', 'pfp_taxonomy_name', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tag_label', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_produce_type_label', 'sanitize_text_field');

    // New settings for font sizes
    register_setting('pfp_settings_group', 'pfp_heading_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_tab_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_post_title_font_size', 'sanitize_text_field');
    register_setting('pfp_settings_group', 'pfp_post_excerpt_font_size', 'sanitize_text_field');

    
      // New settings for background colors
    register_setting('pfp_settings_group', 'pfp_pp_container_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_filter_container_bg_color', 'sanitize_hex_color');
    register_setting('pfp_settings_group', 'pfp_post_item_bg_color', 'sanitize_hex_color');


    // New settings for posts per row and posts per page
    register_setting('pfp_settings_group', 'pfp_posts_per_row', 'intval');
    register_setting('pfp_settings_group', 'pfp_posts_per_page', 'intval');

    // New setting for post item image height
    register_setting('pfp_settings_group', 'pfp_post_item_img_height', 'intval');

    add_settings_section(
        'pfp_main_section', // Section ID
        'Customize Taxonomy and Tag Names', // Section title
        'pfp_section_text', // Callback function
        'pfp-settings'      // Page slug
    );

    add_settings_field(
        'pfp_taxonomy_name', // Field ID
        'Taxonomy Name',     // Field title
        'pfp_taxonomy_name_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_tag_label',     // Field ID
        'Tag Label',         // Field title
        'pfp_tag_label_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_produce_type_label', // Field ID
        'Produce Type Label',     // Field title
        'pfp_produce_type_label_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    // Add new fields for font sizes
    add_settings_field(
        'pfp_heading_font_size', // Field ID
        'Heading Font Size (px)', // Field title
        'pfp_heading_font_size_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_tab_font_size', // Field ID
        'Tab Title(px)', // Field title
        'pfp_tab_font_size_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_post_title_font_size', // Field ID
        'Post Title Font Size (px)', // Field title
        'pfp_post_title_font_size_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_post_excerpt_font_size', // Field ID
        'Post Excerpt Font Size (px)', // Field title
        'pfp_post_excerpt_font_size_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );
    // Add new fields for background colors
    add_settings_field(
        'pfp_pp_container_bg_color', // Field ID
        'PP Container Background Color', // Field title
        'pfp_pp_container_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_filter_container_bg_color', // Field ID
        'Filter Container Background Color', // Field title
        'pfp_filter_container_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_post_item_bg_color', // Field ID
        'Post Item Background Color', // Field title
        'pfp_post_item_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

     // Add new fields for background colors
     add_settings_field(
        'pfp_pp_container_bg_color', // Field ID
        'PP Container Background Color', // Field title
        'pfp_pp_container_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_filter_container_bg_color', // Field ID
        'Filter Container Background Color', // Field title
        'pfp_filter_container_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_post_item_bg_color', // Field ID
        'Post Item Background Color', // Field title
        'pfp_post_item_bg_color_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );
// Add new fields for posts per row and posts per page
    add_settings_field(
        'pfp_posts_per_row', // Field ID
        'Posts Per Row',     // Field title
        'pfp_posts_per_row_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );

    add_settings_field(
        'pfp_posts_per_page', // Field ID
        'Posts Per Page',     // Field title
        'pfp_posts_per_page_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );
    // Add new field for post item image height
    add_settings_field(
        'pfp_post_item_img_height', // Field ID
        'Post Item Image Height (px)', // Field title
        'pfp_post_item_img_height_input', // Callback function
        'pfp-settings',      // Page slug
        'pfp_main_section'   // Section ID
    );
}
add_action('admin_init', 'pfp_register_settings');

function pfp_produce_type_label_input() {
    $produce_type_label = get_option('pfp_produce_type_label', 'Produce Type'); // Default: 'Produce Type'
    echo '<input id="pfp_produce_type_label" name="pfp_produce_type_label" type="text" value="' . esc_attr($produce_type_label) . '" />';
}

// Section text
function pfp_section_text() {
    echo '<p>Enter the custom names for the taxonomy and tag label.</p>';
}

// Taxonomy name input field
function pfp_taxonomy_name_input() {
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'
    echo '<input id="pfp_taxonomy_name" name="pfp_taxonomy_name" type="text" value="' . esc_attr($taxonomy_name) . '" />';
}

// Tag label input field
function pfp_tag_label_input() {
    $tag_label = get_option('pfp_tag_label', 'Month'); // Default: 'Month'
    echo '<input id="pfp_tag_label" name="pfp_tag_label" type="text" value="' . esc_attr($tag_label) . '" />';
}

function pfp_heading_font_size_input() {
    $heading_font_size = get_option('pfp_heading_font_size', '62'); // Default: 62px
    echo '<input id="pfp_heading_font_size" name="pfp_heading_font_size" type="text" value="' . esc_attr($heading_font_size) . '" />';
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
}

function pfp_filter_container_bg_color_input() {
    $filter_container_bg_color = get_option('pfp_filter_container_bg_color', '#FFFFFF'); // Default: White
    echo '<input id="pfp_filter_container_bg_color" name="pfp_filter_container_bg_color" type="text" value="' . esc_attr($filter_container_bg_color) . '" class="pfp-color-field" placeholder="#FFFFFF" />';
}

function pfp_post_item_bg_color_input() {
    $post_item_bg_color = get_option('pfp_post_item_bg_color', '#FFFFFF'); // Default: White
    echo '<input id="pfp_post_item_bg_color" name="pfp_post_item_bg_color" type="text" value="' . esc_attr($post_item_bg_color) . '" class="pfp-color-field" placeholder="#FFFFFF" />';
}

function pfp_posts_per_row_input() {
    $posts_per_row = get_option('pfp_posts_per_row', 4); // Default: 4 posts per row
    echo '<input id="pfp_posts_per_row" name="pfp_posts_per_row" type="number" min="1" max="6" value="' . esc_attr($posts_per_row) . '" />';
}

function pfp_posts_per_page_input() {
    $posts_per_page = get_option('pfp_posts_per_page', 16); // Default: 16 posts per page
    echo '<input id="pfp_posts_per_page" name="pfp_posts_per_page" type="number" min="1" max="50" value="' . esc_attr($posts_per_page) . '" />';
}

function pfp_post_item_img_height_input() {
    $post_item_img_height = get_option('pfp_post_item_img_height', 200); // Default: 200px
    echo '<input id="pfp_post_item_img_height" name="pfp_post_item_img_height" type="number" min="50" max="1000" value="' . esc_attr($post_item_img_height) . '" />';
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


// Create the custom taxonomy
function create_state_taxonomy() {
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'
    $taxonomy_label = ucfirst($taxonomy_name); // Capitalize the label

    $labels = array(
        'name'              => _x($taxonomy_label . 's', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x($taxonomy_label, 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search ' . $taxonomy_label . 's', 'textdomain'),
        'all_items'         => __('All ' . $taxonomy_label . 's', 'textdomain'),
        'parent_item'       => __('Parent ' . $taxonomy_label, 'textdomain'),
        'parent_item_colon' => __('Parent ' . $taxonomy_label . ':', 'textdomain'),
        'edit_item'         => __('Edit ' . $taxonomy_label, 'textdomain'),
        'update_item'       => __('Update ' . $taxonomy_label, 'textdomain'),
        'add_new_item'      => __('Add New ' . $taxonomy_label, 'textdomain'),
        'new_item_name'     => __('New ' . $taxonomy_label . ' Name', 'textdomain'),
        'menu_name'         => __($taxonomy_label, 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true, // Like categories
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => $taxonomy_name),
    );

    register_taxonomy($taxonomy_name, array('post'), $args);
}
add_action('init', 'create_state_taxonomy', 0);




// Create a custom meta box for the custom taxonomy
function add_state_meta_box() {
    $taxonomy_label = ucfirst(get_option('pfp_taxonomy_name', 'state')); // Default: 'State'
    add_meta_box(
        'state_meta_box',            // ID of the meta box
        $taxonomy_label,             // Title (dynamic)
        'display_state_meta_box',    // Callback function
        'post',                      // Post type
        'side',                      // Context
        'default'                    // Priority
    );
}
add_action('add_meta_boxes', 'add_state_meta_box');

// Render the 'State' taxonomy meta box
// Render the custom taxonomy meta box
function display_state_meta_box($post) {
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'
    $taxonomy_label = ucfirst($taxonomy_name); // Capitalize the label

    // Get the terms for the custom taxonomy
    $terms = get_terms(array(
        'taxonomy' => $taxonomy_name,
        'hide_empty' => false,
    ));

    // Get selected terms for the current post
    $selected_terms = get_the_terms($post->ID, $taxonomy_name);
    $selected_terms = !empty($selected_terms) ? wp_list_pluck($selected_terms, 'term_id') : array();

    ?>
    <div id="statediv" class="categorydiv">
        <ul id="state-tabs" class="category-tabs">
            <li class="tabs"><a href="#state-all"><?php _e('All ' . $taxonomy_label . 's'); ?></a></li>
        </ul>
        <div id="state-all" class="tabs-panel">
            <ul id="statechecklist" class="categorychecklist form-no-clear">
                <?php foreach ($terms as $term) : ?>
                    <li>
                        <label>
                            <input type="checkbox" name="state[]" value="<?php echo esc_attr($term->term_id); ?>" <?php checked(in_array($term->term_id, $selected_terms)); ?> />
                            <?php echo esc_html($term->name); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}

// Save the state taxonomy terms when the post is saved

function save_state_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'

    if (isset($_POST['state'])) {
        $state_ids = array_map('intval', $_POST['state']);
        wp_set_post_terms($post_id, $state_ids, $taxonomy_name);
    } else {
        wp_set_post_terms($post_id, array(), $taxonomy_name);
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

// function sanitize_hex_color($color) {
//     // Sanitize hex color input
//     if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
//         return $color;
//     }
//     return '#FFFFFF'; // Default to white if invalid
// }

// Shortcode to display post filtering options and results
function pfp_display_filtered_posts() {
    ob_start(); // Start output buffering


    // Fetch custom background colors from settings
    $pp_container_bg_color = get_option('pfp_pp_container_bg_color', '#FFFFFF');
    $filter_container_bg_color = get_option('pfp_filter_container_bg_color', '#FFFFFF');
    $post_item_bg_color = get_option('pfp_post_item_bg_color', '#FFFFFF');

    // Output the HTML with inline styles

    // Fetch custom font sizes from settings
    $heading_font_size = get_option('pfp_heading_font_size', '62'); // Default: 62px
    $tab_font_size = get_option('pfp_tab_font_size', '14'); // Default: 14px
    $post_title_font_size = get_option('pfp_post_title_font_size', '24'); // Default: 24px
    $post_excerpt_font_size = get_option('pfp_post_excerpt_font_size', '14'); // Default: 14px

    // Fetch the custom taxonomy name from settings
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'
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
        <div class="seasonal-recipes">
        <div class="pp-container" style="background-color: <?php echo esc_attr($pp_container_bg_color); ?>;">

        <!-- Heading for Selected Category -->
        <h3 id="selected-category-heading" class="selected-category-heading" style="font-size: <?php echo esc_attr($heading_font_size); ?>px;"></h3>

        <div class="filter-container" style="background-color: <?php echo esc_attr($filter_container_bg_color); ?>;">

       <!-- Tabs for Categories -->
        <?php if ($categories): ?>
        <div class="tabs">
            <ul class="tab-list" style="margin:0px;">
                <?php foreach ($categories as $index => $category): ?>
                    <li data-category-id="<?php echo esc_attr($category->term_id); ?>" class="tab-item <?php echo $index === 0 ? 'active' : ''; ?>" style="font-size: <?php echo esc_attr($tab_font_size); ?>px;">
                        <?php echo esc_html($category->name); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="filter-options"  >
            <!-- Dropdown for Available Posts -->
            <div class="filter-dropdown" >
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
        .post-item {
            background-color: <?php echo esc_attr($post_item_bg_color); ?>;
        }
        .post-item h3 {
            font-size: <?php echo esc_attr($post_title_font_size); ?>px;
        }
        .post-item p {
            font-size: <?php echo esc_attr($post_excerpt_font_size); ?>px;
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
.post-item img {
    text-align:center;
    width: 100%;
    height: auto;
    margin-bottom: 10px;
    object-fit: cover;
}

/* .pp-container{
background-color: #FFF;
padding:40px;

} */

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
font-family: 'archeron-pro' !important;
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
    font-family: 'archeron-pro' !important;
    
}
.tab-item.active {
    border-bottom: 1px solid #0C2E3A; /* Underline color for active tab */
    border-radius:0px ;
    color: #0C2E3A;
    font-family: 'archeron-pro' !important;
    font-size: 14px;
}
select#available-posts,select#user-state ,select#month {
    font-family: 'archeron-pro' !important;
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
    color: #000;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}
a.page-link {
    color:#10647F;
}
a.page-link.active {
    color:white;;
}
.page-link.active {
    background-color: #36CC7D;
    color: #000;
    font-weight: bold;
    border-color: #36CC7D;
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
    
    // Fetch the custom taxonomy name from settings
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'
    $taxonomy_label = ucfirst($taxonomy_name); // Capitalize the label

    // Fetch states associated with the selected category
    $states = get_terms(array(
        'taxonomy'   => $taxonomy_name, // Use dynamic taxonomy name
        'hide_empty' => true,
        'object_ids' => get_objects_in_term($category_id, 'category'),
    ));

    // Fetch all states
    $mystates = get_terms(array(
        'taxonomy'   => $taxonomy_name, // Use dynamic taxonomy name
        'hide_empty' => false,
    ));

    // Fetch tags associated with the selected category
    $tags = get_tags(array(
        'hide_empty' => true,
        'object_ids' => get_objects_in_term($category_id, 'category'),
    ));

    // Fetch all tags
    $mytags = get_terms(array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
        'orderby' => 'term_id', // Sort by term ID (creation order)
    ));

    ob_start(); // Start output buffering

    // Vertical Separator before User State Dropdown
    if ($states || $tags) { ?>
        <div class="vertical-separator"></div>
    <?php } 

    // Dropdown for User States
    if ($states) { ?>
        <div class="filter-dropdown">
            <label for="user-state"><?php echo esc_html($taxonomy_label); ?></label>
            <select id="user-state">
                <option value="">Select your <?php echo esc_html(strtolower($taxonomy_label)); ?></option>
                <?php foreach ($mystates as $state) { ?>
                    <option value="<?php echo esc_attr($state->term_id); ?>"><?php echo esc_html($state->name); ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } 

    // Vertical Separator before Month Dropdown
    if ($states && $tags) { ?>
        <div class="vertical-separator"></div>
    <?php } 

    // Dropdown for Month
    $tag_label = get_option('pfp_tag_label', 'Month'); // Default: 'Month'
    
    if ($tags) { ?>
        <div class="filter-dropdown">
            <label for="month"><?php echo esc_html($tag_label); ?></label>
            <select id="month">
                <option value="">Select <?php echo esc_html(strtolower($tag_label)); ?></option>
                <?php foreach ($mytags as $tag) { ?>
                    <option value="<?php echo esc_attr($tag->term_id); ?>"><?php echo esc_html($tag->name); ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } 

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
    // Get filter values from AJAX request
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';
    $user_state  = isset($_POST['user_state']) ? intval($_POST['user_state']) : '';
    $month       = isset($_POST['month']) ? intval($_POST['month']) : '';
    $available_term = isset($_POST['available_post']) ? intval($_POST['available_post']) : '';  // 'available_post' is now the term ID
    $paged       = isset($_POST['paged']) ? intval($_POST['paged']) : 1; // Get the current page


    // Ensure we only output what's necessary
    $filtered_posts_html = pfp_get_filtered_posts($category_id, $user_state, $month, $available_term,$paged);

    // Return only the HTML that should be displayed
    if (!empty($filtered_posts_html)) {
        echo $filtered_posts_html;
    }

    wp_die(); // Ensure the script stops here without any extra output
}

function pfp_get_filtered_posts($category_id = '', $user_state = '', $month = '', $available_term = '', $paged = 1) {
    // Get the number of posts per row and posts per page from settings
    $posts_per_row = get_option('pfp_posts_per_row', 4); // Default: 4 posts per row
    $posts_per_page = get_option('pfp_posts_per_page', 16); // Default: 16 posts per page
    $post_item_img_height = get_option('pfp_post_item_img_height', 200); // Default: 200px

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

    // Fetch the custom taxonomy name from settings
    $taxonomy_name = get_option('pfp_taxonomy_name', 'state'); // Default: 'state'

    // Filter by user state
    if ($user_state) {
        $args['tax_query'][] = array(
            'taxonomy' => $taxonomy_name, // Use dynamic taxonomy name
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

  
    if ($query->have_posts()) {
        // Pass the posts_per_row value to the grid container
        echo '<div class="post-grid" style="grid-template-columns: repeat(' . esc_attr($posts_per_row) . ', 1fr);">'; // Use the setting for posts per row
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <a href="<?php the_permalink(); ?>" class="post-link">
                <div class="posts-section">
                    <div class="post-item">
                        <?php the_post_thumbnail('medium', ['style' => 'border-radius: 8px; height: ' . esc_attr($post_item_img_height) . 'px;']); ?>
                        <h3 style="text-align: center; font-family: \'archeron-pro\' !important; font-weight: 700; color: #08303D;"><?php the_title(); ?></h3>
                        <p style="color: #08303D; text-align: center;  font-weight: 400; font-family: \'Poppins\', sans-serif;"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
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
                echo '<a href="#" class="page-link" data-page="' . ($paged - 1) . '">&laquo; Previous</a>';
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

            for ($i = $start; $i <= $end; $i++) {
                $active = $i == $paged ? 'active' : '';
                echo '<a href="#" class="page-link ' . $active . '" data-page="' . $i . '">' . $i . '</a>';
            }

            // Display ellipsis and last page if needed
            if ($paged < $total_pages - 2) {
                if ($paged < $total_pages - 3) {
                    echo '<span class="ellipsis">...</span>';
                }
                echo '<a href="#" class="page-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
            }

            // Next button
            if ($paged < $total_pages) {
                echo '<a href="#" class="page-link" data-page="' . ($paged + 1) . '">Next &raquo;</a>';
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





