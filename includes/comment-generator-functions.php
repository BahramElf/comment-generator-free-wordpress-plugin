<?php
// comment-generator-functions.php
defined('ABSPATH') || exit;
/**
 * Comment Generator Functions
 *
 * @package CommentGenerator
 * @since 1.0.0
 */

/**
 * Sanitizes number input ensuring it's a positive integer.
 *
 * @since 1.0.0
 * @param mixed $input The input value to sanitize
 * @return int Sanitized number, minimum value of 1
 */
function wpex_comment_generator_sanitize_number($input)
{
    $number = absint($input);
    return ($number > 0) ? $number : 1;
}

/**
 * Sanitizes sentences input, converting string to array if needed.
 *
 * @since 1.0.0
 * @param mixed $input String or array of sentences to sanitize
 * @return array Array of sanitized sentences
 */
function wpex_comment_generator_sanitize_sentences($input)
{
    if (!is_array($input)) {
        $input = explode("\n", $input);
    }
    return array_map('sanitize_text_field', array_filter($input));
}

/**
 * Sanitizes author input handling both name-only and name|email formats.
 *
 * @since 1.0.0
 * @param string $input Raw author input string
 * @return array Array of sanitized author strings
 */
function wpex_comment_generator_sanitize_authors($input)
{
    $authors = explode("\n", $input);
    $sanitized = array();

    foreach ($authors as $author) {
        $parts = explode('|', $author);

        if (count($parts) === 2) {
            // Handle name|email format
            $name = sanitize_text_field($parts[0]);
            $email = sanitize_email($parts[1]);
            if ($name && $email) {
                $sanitized[] = "$name|$email";
            }
        } elseif (count($parts) === 1) {
            // Handle name only format
            $name = sanitize_text_field($parts[0]);
            if ($name) {
                $sanitized[] = $name;
            }
        }
    }

    return $sanitized;
}


/**
 * Deletes commented items from options table.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_delete_commented_items()
{
    // Check if user has proper permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    // Verify the nonce
    if (!isset($_POST['wpex_comment_generator_ajax_nonce'])) {
        wp_send_json_error('Nonce is missing.');
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_ajax_nonce']));
    if (!isset($nonce) || !wp_verify_nonce($nonce, 'wpex_comment_generator_ajax_nonce')) {
        wp_send_json_error('Invalid nonce.');
    }

    //check_ajax_referer('wpex_comment_generator_ajax_nonce', 'nonce');

    // Check if the option exists before deleting
    if (get_option('wpex_comment_generator_commented_items')) {
        // Delete the commented items option
        delete_option('wpex_comment_generator_commented_items');

        // Send a success response
        wp_send_json_success();
    } else {
        // Send an error response
        wp_send_json_error('wpex_comment_generator_commented_items option not found.');
    }
}
// Callback function to handle the AJAX request and delete commented items
add_action('wp_ajax_wpex_comment_generator_delete_commented_items', 'wpex_comment_generator_delete_commented_items');
add_action('wp_ajax_nopriv_wpex_comment_generator_delete_commented_items', 'wpex_comment_generator_delete_commented_items');

/**
 * Initiates the comment generation process.
 * Validates nonce and triggers the comment generator.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_start_comment_generation()
{
    // Check if user has proper permissions
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'comment-generator'));
    }

    // Validate nonce exists before accessing
    if (!isset($_POST['wpex_comment_generator_nonce'])) {
        wp_die('Nonce is missing.');
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_nonce']));
    if (!wp_verify_nonce($nonce, 'wpex_comment_generator_nonce')) {
        wp_die('Security check failed. Please try again.');
    }

    $comment_generated_report = wpex_comment_generator_generate_comment();

    $generation_started_message  = esc_js(__('Comment generation started.', 'comment-generator'));
    wp_add_inline_script('comment-generator-settings-scripts', 'alert("' . $generation_started_message . '");');

    return $comment_generated_report;
}

// Hook the comment generation function to a WordPress action
add_action('admin_init', function () {

    if (isset($_POST['wpex_comment_generator_start'])) {
        // Verify nonce
        if (isset($_POST['wpex_comment_generator_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_nonce']));
            if (!wp_verify_nonce($nonce, 'wpex_comment_generator_nonce')) {
                wp_die('Security check failed.');
            }
        }
        $comment_generated_report = wpex_start_comment_generation();
        if ($comment_generated_report) {
            $translated_message = sprintf( // translators: 1: number of new comments, 2: number of new commented posts, 3: number of duplicate posts
                __('Comments generated Report! \n New comment created: %1$d \n New commented posts: %2$d \n Duplicate posts that were already commented: %3$d \n If this is not your desired output, change the number of posts in the settings or use "Delete Commented Posts" button', 'comment-generator'),
                $comment_generated_report['cmnt_created'],
                $comment_generated_report['created'],
                $comment_generated_report['exist']
            );
            $escaped_message = esc_js($translated_message);
            wp_add_inline_script('comment-generator-settings-scripts', 'alert("' . $escaped_message . '");');
        } else {
            $no_comments_message = esc_js(__('No Comments generated! Check the input values and settings.', 'comment-generator'));
            wp_add_inline_script('comment-generator-settings-scripts', 'alert("' . $no_comments_message . '");');
        }
    }
});

/**
 * Renders the settings page for the Comment Generator plugin.
 * Handles form submissions and displays settings fields.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'comment-generator'));
        return;
    }
    $general_sentences = get_option('wpex_comment_generator_general_sentences', array());
    $product_buyer_sentences = get_option('wpex_comment_generator_product_buyer_sentences', array());
    $product_non_buyer_sentences = get_option('wpex_comment_generator_product_non_buyer_sentences', array());
    $custom_authors = get_option('wpex_comment_generator_custom_authors', array());
    $comment_mode = get_option('wpex_comment_generator_comment_mode', 'single');


?>
    <div class="wrap">
        <h1><?php esc_html_e('Comment Generator Pro', 'comment-generator'); ?></h1>
        <form name="comment-generator-setting" id="comment-generator-setting" action="options.php" method="post">
            <?php settings_fields('wpex_comment_generator_settings_group'); ?>
            <?php wp_nonce_field('update_wpex_comment_generator_settings', 'wpex_comment_generator_settings_nonce'); ?>
            <?php do_settings_sections('wpex_comment_generator_settings_page'); ?>

            <?php
            // Save settings when the form is submitted
            if (isset($_POST) && !empty($_POST)) {

                if (
                    !isset($_POST['wpex_comment_generator_settings_nonce']) ||
                    !wp_verify_nonce(
                        sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_settings_nonce'])),
                        'update_wpex_comment_generator_settings'
                    )
                ) {
                    wp_die('Security check failed');
                }

                if (isset($_POST['wpex_comment_generator_post_type'])) {
                    update_option('wpex_comment_generator_post_type', sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_post_type'])));
                }

                if (isset($_POST['wpex_comment_generator_category'])) {
                    update_option('wpex_comment_generator_category', intval($_POST['wpex_comment_generator_category']));
                }

                if (isset($_POST['wpex_comment_generator_comment_count'])) {
                    update_option('wpex_comment_generator_comment_count', intval($_POST['wpex_comment_generator_comment_count']));
                }

                if (isset($_POST['wpex_comment_generator_comment_date'])) {
                    $comment_date = intval(wp_unslash($_POST['wpex_comment_generator_comment_date']));
                    update_option('wpex_comment_generator_comment_date', $comment_date);
                }

                if (isset($_POST['wpex_comment_generator_specific_post_id'])) {
                    update_option('wpex_comment_generator_specific_post_id', intval($_POST['wpex_comment_generator_specific_post_id']));
                }

                if (isset($_POST['wpex_comment_generator_product_limit'])) {
                    update_option('wpex_comment_generator_product_limit', intval($_POST['wpex_comment_generator_product_limit']));
                }

                if (isset($_POST['wpex_comment_generator_product_score'])) {
                    update_option('wpex_comment_generator_product_score', intval($_POST['wpex_comment_generator_product_score']));
                }

                if (isset($_POST['wpex_comment_generator_comment_status'])) {
                    update_option('wpex_comment_generator_comment_status', intval($_POST['wpex_comment_generator_comment_status']));
                }

                if (isset($_POST['wpex_comment_generator_comment_from'])) {
                    update_option(
                        'wpex_comment_generator_comment_from',
                        sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_comment_from']))
                    );
                }

                if (isset($_POST['wpex_comment_generator_product_stock_status'])) {
                    update_option(
                        'wpex_comment_generator_product_stock_status',
                        sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_product_stock_status']))
                    );
                }

                if (isset($_POST['wpex_comment_generator_comment_mode'])) {
                    update_option(
                        'wpex_comment_generator_comment_mode',
                        sanitize_text_field(wp_unslash($_POST['wpex_comment_generator_comment_mode']))
                    );
                }

                if (isset($_POST['wpex_comment_generator_general_sentences'])) {
                    $new_general_sentences = sanitize_textarea_field(
                        wp_unslash($_POST['wpex_comment_generator_general_sentences'])
                    );
                    $new_general_sentences = explode("\n", $new_general_sentences);
                    $new_general_sentences = array_map('trim', $new_general_sentences);

                    // Merge the new sentences with the existing ones
                    $general_sentences = array_merge($general_sentences, $new_general_sentences);

                    update_option('wpex_comment_generator_general_sentences', $general_sentences);
                }

                if (isset($_POST['wpex_comment_generator_product_buyer_sentences'])) {
                    $new_product_buyer_sentences = sanitize_textarea_field(
                        wp_unslash($_POST['wpex_comment_generator_product_buyer_sentences'])
                    );
                    $new_product_buyer_sentences = explode("\n", $new_product_buyer_sentences);
                    $new_product_buyer_sentences = array_map('trim', $new_product_buyer_sentences);

                    // Merge the new sentences with the existing ones
                    $product_buyer_sentences = array_merge($product_buyer_sentences, $new_product_buyer_sentences);

                    update_option('wpex_comment_generator_product_buyer_sentences', $product_buyer_sentences);
                }

                if (isset($_POST['wpex_comment_generator_product_non_buyer_sentences'])) {
                    $new_product_non_buyer_sentences = sanitize_textarea_field(
                        wp_unslash($_POST['wpex_comment_generator_product_non_buyer_sentences'])
                    );
                    $new_product_non_buyer_sentences = explode("\n", $new_product_non_buyer_sentences);
                    $new_product_non_buyer_sentences = array_map('trim', $new_product_non_buyer_sentences);

                    // Merge the new sentences with the existing ones
                    $product_non_buyer_sentences = array_merge($product_non_buyer_sentences, $new_product_non_buyer_sentences);

                    update_option('wpex_comment_generator_product_non_buyer_sentences', $product_non_buyer_sentences);
                }

                if (isset($_POST['wpex_comment_generator_custom_authors'])) {
                    $new_custom_authors = sanitize_textarea_field(
                        wp_unslash($_POST['wpex_comment_generator_custom_authors'])
                    );
                    $new_custom_authors = explode("\n", $new_custom_authors);
                    $new_custom_authors = array_map('trim', $new_custom_authors);

                    // Merge the new authors with the existing ones and remove duplicates
                    $custom_authors = array_merge($custom_authors, $new_custom_authors);

                    // Save the updated authors
                    update_option('wpex_comment_generator_custom_authors', $custom_authors);

                    // Retrieve the authors after saving
                    $custom_authors = get_option('wpex_comment_generator_custom_authors', array());
                }
            }
            ?>
            <p id="imp-note"><?php esc_html_e('After each change in the settings or entered information, first click the save changes button and then start creating comments.', 'comment-generator') ?></p>
            <?php submit_button(); ?>
        </form>
        <form name="wpex_comment_generator_start_form" id="comment-generator-start-form" method="post">
            <?php wp_nonce_field('wpex_comment_generator_nonce', 'wpex_comment_generator_nonce'); ?>
            <button type="submit" name="wpex_comment_generator_start" id="comment-generator-start-btn"><?php esc_html_e('Start Generating Comments', 'comment-generator'); ?></button>
        </form>
        <?php
        ?>
    </div>
<?php
}

/**
 * Enqueues admin settings JavaScript and localizes script data.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_script()
{
    // Get the current screen object
    $current_screen = get_current_screen();

    // Check if the current screen is the plugin settings page
    if ($current_screen->base === 'toplevel_page_wpex_comment_generator_settings') {
        wp_enqueue_script('comment-generator-settings-scripts', WPEX_COMMENT_GENERATOR_PLUGIN_url . 'assets/js/admin-setting.js', array('jquery'), WPEX_COMMENT_GENERATOR_VERSION, true);

        // Pass localization data to the script
        wp_localize_script('comment-generator-settings-scripts', 'commentGeneratorSettings', array(
            'emptyFieldNoProductMessage' => __('Please enter at least one Sentence and one Author for comments.', 'comment-generator'),
            'emptyFieldProductMessage' => __('Please enter at least one Buyer Sentence, one none Buyer Sentenceone and Author for comments.', 'comment-generator'),
            'emptyFieldProductBuyerMessage' => __('Please enter at least one Buyer Sentence and Author for comments.', 'comment-generator'),
            'emptyFieldProductNoneBuyerMessage' => __('Please enter at least one none Buyer Sentenceone and Author for comments.', 'comment-generator'),
            'nonce' => wp_create_nonce('wpex_comment_generator_ajax_nonce'),
            'deleteSuccess' => __('Commented items deleted successfully.', 'comment-generator'),
            'deleteFailed' => __('Failed to delete commented items. Please try again.', 'comment-generator'),
            'commentStarted' => esc_html__('Comment generation started.', 'comment-generator'),
            'noComments' => esc_html__('No comments to generate.', 'comment-generator')
        ));
    }
}
// Hook the function to the admin_enqueue_scripts action, so it loads on the plugin settings page
add_action('admin_enqueue_scripts', 'wpex_comment_generator_settings_script');

/**
 * Enqueues admin settings stylesheet.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_styles()
{
    // Get the current screen object
    $current_screen = get_current_screen();

    // Check if the current screen is the plugin settings page
    if ($current_screen->base === 'toplevel_page_wpex_comment_generator_settings') {
        wp_enqueue_style('comment-generator-settings-styles', WPEX_COMMENT_GENERATOR_PLUGIN_url . 'assets/css/admin-setting.css', array(), '1.0');
    }
}
// Hook the function to the admin_enqueue_scripts action, so it loads on the plugin settings page
add_action('admin_enqueue_scripts', 'wpex_comment_generator_settings_styles');

/**
 * Generates comments based on configured settings.
 * Handles both regular posts and WooCommerce products.
 * 
 * @since 1.0.0
 * @global wpdb $wpdb WordPress database abstraction object
 * @return array|bool {
 *     Comment generation report or false on failure
 *     
 *     @type bool  $success      Whether generation was successful
 *     @type int   $created      Number of posts that received new comments
 *     @type int   $exist        Number of posts already commented on
 *     @type int   $cmnt_created Total number of comments created
 * }
 */
function wpex_comment_generator_generate_comment()
{
    if (!current_user_can('manage_options')) {
        return false;
    }
    global $wpdb;
    $comment_generated_report = array(
        'success' => false,
        'created' => 0,
        'exist' => 0,
        'cmnt_created' => 0
    );
    $post_type = get_option('wpex_comment_generator_post_type', 'post');
    $category = get_option('wpex_comment_generator_category', 0);
    $product_limit = get_option('wpex_comment_generator_product_limit', 1);
    $comment_count = get_option('wpex_comment_generator_comment_count', 1);
    $commented_items = get_option('wpex_comment_generator_commented_items', array());
    $comment_score = get_option('wpex_comment_generator_product_score', 3);
    $comment_status = get_option('wpex_comment_generator_comment_status', 0);
    $product_stock_status = get_option('wpex_comment_generator_product_stock_status', 'instock');
    $specific_post_id = get_option('wpex_comment_generator_specific_post_id', '');
    $comment_mode = get_option('wpex_comment_generator_comment_mode', 'single');
    $comment_from = get_option('wpex_comment_generator_comment_from', 'buyer');
    $x_months_ago = get_option('wpex_comment_generator_comment_date', 3);

    // Array of example comments
    $general_comments = array();
    $product_buyer_comments = array();
    $product_non_buyer_comments = array();
    $comment_authors = array();

    // Retrieve general comment sentences from plugin settings
    $general_sentences = get_option('wpex_comment_generator_general_sentences', '');
    // Add general sentences to the comments array
    if ($general_sentences) {
        $general_sentences = explode("\n", $general_sentences);
        foreach ($general_sentences as $sentence) {
            $general_comments[] = trim($sentence);
        }
    }

    // Retrieve product buyer comment sentences from plugin settings
    $product_buyer_sentences = get_option('wpex_comment_generator_product_buyer_sentences', '');
    // Add product buyer sentences to the comments array
    if ($product_buyer_sentences) {
        $product_buyer_sentences = explode("\n", $product_buyer_sentences);
        foreach ($product_buyer_sentences as $sentence) {
            $product_buyer_comments[] = trim($sentence);
        }
    }

    // Retrieve product non-buyer comment sentences from plugin settings
    $product_non_buyer_sentences = get_option('wpex_comment_generator_product_non_buyer_sentences', '');
    // Add product non-buyer sentences to the comments array
    if ($product_non_buyer_sentences) {
        $product_non_buyer_sentences = explode("\n", $product_non_buyer_sentences);
        foreach ($product_non_buyer_sentences as $sentence) {
            $product_non_buyer_comments[] = trim($sentence);
        }
    }

    // Retrieve comment authors from plugin settings
    $custom_authors = get_option('wpex_comment_generator_custom_authors', '');
    if ($custom_authors) {
        $custom_authors = explode("\n", $custom_authors);
        foreach ($custom_authors as $authors) {
            $author_parts = explode('|', $authors);

            if (count($author_parts) === 2) {
                // Both name and email are provided
                $comment_authors[] = array(
                    'name'  => trim($author_parts[0]),
                    'email' => trim($author_parts[1]),
                );
            } else {
                // Only name is provided
                $comment_authors[] = array(
                    'name' => trim($authors),
                    'email' => '', // Set an empty email
                );
            }
        }
    }

    $num_comment_authors = count($comment_authors);

    // Store the array indices to be used for comment generation
    $general_index = get_option('wpex_comment_generator_general_index', 0);
    $product_buyer_index = get_option('wpex_comment_generator_product_buyer_index', 0);
    $product_non_buyer_index = get_option('wpex_comment_generator_product_non_buyer_index', 0);

    if ($comment_mode === 'single' && empty($specific_post_id)) {
        return $comment_generated_report;
    } elseif ($comment_mode === 'category' && $category == 0) {
        return $comment_generated_report;
    }
    if ($post_type !== 'product' && (empty($general_comments) || empty($comment_authors))) {
        return $comment_generated_report;
    } elseif ($post_type === 'product' && $comment_from == 'buyer' && (empty($comment_authors) || empty($product_buyer_comments))) { // || empty($product_non_buyer_comments))) {
        return $comment_generated_report;
    } elseif ($post_type === 'product' && $comment_from == 'user' && (empty($comment_authors) || empty($product_non_buyer_comments))) { // || empty($product_non_buyer_comments))) {
        return $comment_generated_report;
    }

    // If a specific post ID is provided, generate comments only for that post
    if (!empty($specific_post_id) && is_numeric($specific_post_id)) {
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => 1,
            'post__in'       => array($specific_post_id),
        );
    } else {
        // Otherwise, use the regular query to get posts based on post type and category
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => $product_limit,
        );

        // Check if WooCommerce is active and set the correct taxonomy
        if ($post_type == 'product') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category,
                ),
            );
            $args['meta_query'] = array(
                array(
                    'key' => '_stock_status',
                    'value' => $product_stock_status, // 'instock' for products in stock, 'outofstock' for products out of stock
                    'compare' => '=',
                ),
            );
        } elseif ($category !== '0') {
            $args['cat'] = $category; // For default "category" taxonomy
        }
    }

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $author_index = 0;
        while ($query->have_posts()) {
            $query->the_post();

            if (!in_array(get_the_ID(), $commented_items) || (!empty($specific_post_id) && is_numeric($specific_post_id))) {
                $comment_count_for_item = $comment_count; //min($comment_count, rand(10, 20));



                for ($i = 0; $i < $comment_count_for_item; $i++) {
                    $comment_generated_report['cmnt_created'] += 1;

                    // Randomly select an author from the custom authors list
                    //$author_index = rand(0, $num_comment_authors - 1);
                    $selected_author = $comment_authors[$author_index];

                    $comment_author = $selected_author['name'];
                    $comment_author_email = $selected_author['email'];


                    // Generate a random timestamp between the current date and 2 months ago
                    $current_timestamp = current_time('timestamp');
                    $x_months_ago_timestamp = strtotime("-$x_months_ago months", $current_timestamp);
                    $random_timestamp = wp_rand($x_months_ago_timestamp, $current_timestamp);

                    // Format the random timestamp to a date string
                    $comment_date = gmdate('Y-m-d H:i:s', $random_timestamp);

                    if ($post_type === 'product') {
                        // Check if there are available comments for product buyers and non-buyers
                        $has_product_buyer_comments = count($product_buyer_comments) > 0;
                        $has_product_non_buyer_comments = count($product_non_buyer_comments) > 0;

                        // Check if all comments have been used, and if so, reset the indices
                        /*if (!$has_product_buyer_comments && !$has_product_non_buyer_comments) {
                                $product_buyer_index = 0;
                                $product_non_buyer_index = 0;
                            }*/

                        // Determine whether the comment will be for a product buyer or non-buyer
                        if ($comment_from == 'buyer') {
                            $is_buyer = true;
                        } elseif ($comment_from == 'user') {
                            $is_buyer = false;
                        } else {
                            $is_buyer = wp_rand(1, 2) === 2;
                        }
                        //$is_buyer = rand(1, 3) === 3;

                        if ($is_buyer && $has_product_buyer_comments) {
                            $comment_index = $product_buyer_index % count($product_buyer_comments);
                            $comment_sentence = $product_buyer_comments[$comment_index];
                            $product_buyer_index++;
                        } elseif (!$is_buyer && $has_product_non_buyer_comments) {
                            $comment_index = $product_non_buyer_index % count($product_non_buyer_comments);
                            $comment_sentence = $product_non_buyer_comments[$comment_index];
                            $product_non_buyer_index++;
                        } else {
                            // If there are no comments for either buyers or non-buyers, use a general comment
                            /*$comment_index = $general_index % count($general_comments);
                                $comment_sentence = $general_comments[$comment_index];
                                $general_index++;*/
                        }

                        $star_rating = wp_rand($comment_score, 5);
                        $comment_data = array(
                            'comment_post_ID'      => get_the_ID(),
                            'comment_author'       => $comment_author,
                            'comment_author_email' => $comment_author_email,
                            'comment_content'      => $comment_sentence,
                            'comment_approved'     => $comment_status, // Set to 1 to automatically approve comments
                            'comment_type'         => 'review',
                            'comment_date'         => $comment_date, // Set the comment_date field to the random date
                            'comment_meta'         => array(
                                'rating'    => $star_rating,
                                'verified'  => $is_buyer,
                            )
                        );
                    } else {

                        $comment_index = $general_index % count($general_comments);
                        $comment_sentence = $general_comments[$comment_index];
                        $general_index++;

                        $comment_data = array(
                            'comment_post_ID'      => get_the_ID(),
                            'comment_author'       => $comment_author,
                            'comment_author_email' => $comment_author_email,
                            'comment_content'      => $comment_sentence,
                            'comment_approved'     => $comment_status, // Set to 1 to automatically approve comments
                            'comment_date'         => $comment_date, // Set the comment_date field to the random date
                        );
                    }

                    $comment_id = wp_insert_comment($comment_data); // Insert the comment and get the comment ID
                    if ($author_index < ($num_comment_authors - 1)) {
                        $author_index++;
                    } else {
                        $author_index = 0;
                    }
                }

                $comment_generated_report['success'] = true;
                $comment_generated_report['created'] += 1;
                $commented_items[] = get_the_ID();
                update_option('wpex_comment_generator_commented_items', $commented_items);

                // Update the stored indices in the database
                update_option('wpex_comment_generator_general_index', $general_index);
                update_option('wpex_comment_generator_product_buyer_index', $product_buyer_index);
                update_option('wpex_comment_generator_product_non_buyer_index', $product_non_buyer_index);
            } elseif (in_array(get_the_ID(), $commented_items)) {
                $comment_generated_report['exist'] += 1;
            }
        }
        wp_reset_postdata();
        update_option('wpex_comment_generator_specific_post_id', '');
    } else {
        wp_reset_postdata();
        update_option('wpex_comment_generator_specific_post_id', '');
    }
    return $comment_generated_report;
}
