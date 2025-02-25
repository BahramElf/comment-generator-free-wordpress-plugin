<?php
defined('ABSPATH') || exit;
require_once(WPEX_COMMENT_GENERATOR_PLUGIN_PATH . 'includes/comment-generator-Register-settings.php');

/**
 * Renders the settings section description and guidelines
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_section_cb()
{
    echo '<p>' . esc_html__('Please specify the number of comments and posts according to your hosting and server resources.', 'comment-generator') . '<br>';
    echo esc_html__('If you intend to create a large number of comments, consider doing it in batches.', 'comment-generator') . '<br>';
    echo esc_html__('Otherwise, you may encounter temporary server resource errors or the website may become temporarily unavailable.', 'comment-generator') . '<br>';
    echo esc_html__('We suggest creating 200 to 400 comments in each batch.', 'comment-generator') . '</p>';
}

/**
 * Renders the comment mode selection field
 * Allows choosing between single post or category mode
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_comment_mode_cb()
{
    $comment_mode = get_option('wpex_comment_generator_comment_mode', 'single');
?>
    <input type="radio" name="wpex_comment_generator_comment_mode" value="single" <?php checked($comment_mode, 'single'); ?>>
    <label for="wpex_comment_generator_comment_mode_single"><?php esc_html_e('Single Post', 'comment-generator'); ?></label>
    <br>
    <input type="radio" name="wpex_comment_generator_comment_mode" value="category" <?php checked($comment_mode, 'category'); ?>>
    <label for="wpex_comment_generator_comment_mode_category"><?php esc_html_e('Category', 'comment-generator'); ?></label>
<?php
}

/**
 * Renders the post type selection dropdown
 * Displays all public post types as options
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_post_type_cb()
{
    $post_type = get_option('wpex_comment_generator_post_type', 'post');
    $post_types = get_post_types(array('public' => true), 'objects');
    echo '<select name="wpex_comment_generator_post_type" id="post-type">';
    echo '<option value="">-- ' . esc_html_e('Select PostType', 'comment-generator') . ' --</option>'; // Prompt to select
    foreach ($post_types as $type) {
        echo '<option value="' . esc_attr($type->name) . '" ' . selected($type->name, $post_type, false) . '>' . esc_html($type->label) . '</option>';
    }
    echo '</select>';
?><p>
        <?php esc_html_e('It is important to choose the correct post type, both in individual commenting mode and in group commenting mode.', 'comment-generator');
        ?></p>
<?php
}

/**
 * Renders the category selection dropdown based on selected post type
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_category_cb()
{
    $post_type = get_option('wpex_comment_generator_post_type', 'post');
    $selected_category = get_option('wpex_comment_generator_category', 0);

    $taxonomy = ($post_type === 'product') ? 'product_cat' : 'category';

    $categories = get_categories(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'object_type' => array($post_type),
    ));

    echo '<select name="wpex_comment_generator_category" id="post-category">';
    echo '<option value="0">-- ' . esc_html_e('Select Category', 'comment-generator') . ' --</option>';
    foreach ($categories as $category) {
        echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($category->term_id, $selected_category, false) . '>' . esc_html($category->name) . '</option>';
    }
    echo '</select>';

    // Localize the script data to pass PHP variables to the JavaScript file
    $localized_data = array(
        'selected_category' => $selected_category,
    );
    wp_localize_script('comment-generator-settings-scripts', 'wpex_comment_generator_data', $localized_data);
}

/**
 * Generates comments based on configured settings.
 * 
 * @since 1.0.0
 * @return array|bool Comment generation report or false on failure
 */
function wpex_comment_generator_get_categories()
{
    // Verify nonce
    check_ajax_referer('wpex_comment_generator_ajax_nonce', 'nonce');

    // Validate and sanitize post_type
    if (!isset($_GET['post_type'])) {
        wp_send_json_error('Post type is required');
    }

    $post_type = sanitize_key(wp_unslash($_GET['post_type']));
    $taxonomy = ($post_type === 'product') ? 'product_cat' : 'category';

    $categories = get_categories(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'object_type' => array($post_type),
    ));
    wp_send_json($categories);
}

// AJAX handler to get categories based on post type
add_action('wp_ajax_get_wpex_comment_generator_categories', 'wpex_comment_generator_get_categories');
add_action('wp_ajax_nopriv_get_wpex_comment_generator_categories', 'wpex_comment_generator_get_categories');

/**
 * Registers plugin settings menu in WordPress admin.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_specific_post_id_cb()
{
    $specific_post_id = get_option('wpex_comment_generator_specific_post_id', '');
    echo '<input type="number" name="wpex_comment_generator_specific_post_id" value="' . esc_attr($specific_post_id) . '">';
    echo '<p>' . esc_html_e(
        'You can use plugins like "Reveal IDs" or "Catch IDs" to find and view the ID of a pages, articles, products, etc.',
        'comment-generator'
    ) . '</p>';
}

/**
 * Renders the product/post limit input field for category mode
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_product_limit_cb()
{
    $limit = get_option('wpex_comment_generator_product_limit', 1);
    echo '<input type="number" name="wpex_comment_generator_product_limit" value="' . esc_attr($limit) . '" min="1">';
}

/**
 * Renders the comment count input field
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_comment_count_cb()
{
    $comment_count = get_option('wpex_comment_generator_comment_count', 1);
    echo '<input type="number" name="wpex_comment_generator_comment_count" value="' . esc_attr($comment_count) . '" min="1">';
}

/**
 * Renders the comment date selection dropdown
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_comment_date_cb()
{
    $comment_date = get_option('wpex_comment_generator_comment_date', 3);
?>
    <select name="wpex_comment_generator_comment_date">
        <?php for ($month = 1; $month <= 12; $month++) : ?>
            <option value="<?php echo esc_attr($month); ?>" <?php selected($comment_date, $month); ?>><?php echo esc_html($month); ?> <?php esc_html_e('Month ago', 'comment-generator'); ?></option>
        <?php endfor; ?>
    </select>
<?php
}

/**
 * Renders the product review score selection dropdown
 * Allows selecting minimum star rating for generated reviews
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_product_score_cb()
{
    $comment_score = get_option('wpex_comment_generator_product_score', 3);
?>
    <select name="wpex_comment_generator_product_score">
        <option value="3" <?php selected($comment_score, 3); ?>><?php esc_html_e('3 and above (out of 5 stars)', 'comment-generator'); ?></option>
        <option value="4" <?php selected($comment_score, 4); ?>><?php esc_html_e('4 and above (out of 5 stars)', 'comment-generator'); ?></option>
    </select>
<?php
}

/**
 * Renders the comment status selection dropdown
 * Controls whether generated comments are approved or pending
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_comment_status_cb()
{
    $comment_status = get_option('wpex_comment_generator_comment_status', 0);
?>
    <select name="wpex_comment_generator_comment_status">
        <option value="1" <?php selected($comment_status, 1); ?>><?php esc_html_e('Approved', 'comment-generator'); ?></option>
        <option value="0" <?php selected($comment_status, 0); ?>><?php esc_html_e('Pending', 'comment-generator'); ?></option>
    </select>
<?php
}

/**
 * Renders the comment author type selection dropdown
 * Allows choosing between buyer, user or random comment authors
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_comment_from_cb()
{
    $comment_from = get_option('wpex_comment_generator_comment_from', 'buyer');
?>
    <select name="wpex_comment_generator_comment_from" id="wpex_comment_generator_comment_from">
        <option value="buyer" <?php selected($comment_from, 'buyer'); ?>><?php esc_html_e('Buyer', 'comment-generator'); ?></option>
        <option value="user" <?php selected($comment_from, 'user'); ?>><?php esc_html_e('User', 'comment-generator'); ?></option>
        <option value="random" <?php selected($comment_from, 'random'); ?>><?php esc_html_e('Random', 'comment-generator'); ?></option>
    </select>
<?php
}

/**
 * Renders the product stock status selection dropdown
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_product_stock_status_cb()
{
    $product_stock_status = get_option('wpex_comment_generator_product_stock_status', 'instock');
?>
    <select name="wpex_comment_generator_product_stock_status">
        <option value="instock" <?php selected($product_stock_status, 'instock'); ?>><?php esc_html_e('In stock', 'comment-generator'); ?></option>
        <option value="outofstock" <?php selected($product_stock_status, 'outofstock'); ?>><?php esc_html_e('Out of stock', 'comment-generator'); ?></option>
    </select>
<?php
}

/**
 * Renders the general comment sentences textarea
 * Handles deduplication and formatting of sentence input
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_general_sentences_cb()
{
    $general_sentences = (array) get_option('wpex_comment_generator_general_sentences', array());

    // Split textarea content into individual sentences using line breaks
    $sentences = explode("\n", implode("\n", $general_sentences));

    // Remove duplicates while considering leading and trailing spaces
    $filtered_sentences = array();
    foreach ($sentences as $sentence) {
        $trimmed_sentence = trim($sentence);
        if (!empty($trimmed_sentence) && !in_array($trimmed_sentence, $filtered_sentences)) {
            $filtered_sentences[] = $trimmed_sentence;
        }
    }
?>
    <p><?php esc_html_e('General Comments, Enter one sentence per line', 'comment-generator'); ?></p>
    <textarea name="wpex_comment_generator_general_sentences" id="wpex_comment_generator_general_sentences" rows="5" cols="50">
    <?php
    foreach ($filtered_sentences as $sentence) {
        echo esc_textarea($sentence) . "\n";
    }
    ?></textarea>
<?php
}

/**
 * Renders the product buyer comment sentences textarea
 * Handles deduplication and formatting of buyer-specific sentences
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_product_buyer_sentences_cb()
{
    $product_buyer_sentences = (array) get_option('wpex_comment_generator_product_buyer_sentences', array());

    // Split textarea content into individual sentences using line breaks
    $sentences = explode("\n", implode("\n", $product_buyer_sentences));

    // Remove duplicates while considering leading and trailing spaces
    $filtered_sentences = array();
    foreach ($sentences as $sentence) {
        $trimmed_sentence = trim($sentence);
        if (!empty($trimmed_sentence) && !in_array($trimmed_sentence, $filtered_sentences)) {
            $filtered_sentences[] = $trimmed_sentence;
        }
    }
?>
    <p><?php esc_html_e('Comments from buyers of products, Enter one sentence per line', 'comment-generator'); ?></p>
    <textarea name="wpex_comment_generator_product_buyer_sentences" id="wpex_comment_generator_product_buyer_sentences" rows="5" cols="50">
    <?php
    foreach ($filtered_sentences as $sentence) {
        echo esc_textarea($sentence) . "\n";
    }
    ?></textarea>
<?php
}

/**
 * Renders the product non-buyer comment sentences textarea
 * Handles deduplication and formatting of non-buyer sentences
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_product_non_buyer_sentences_cb()
{
    $product_non_buyer_sentences = (array) get_option('wpex_comment_generator_product_non_buyer_sentences', array());

    // Split textarea content into individual sentences using line breaks
    $sentences = explode("\n", implode("\n", $product_non_buyer_sentences));

    // Remove duplicates while considering leading and trailing spaces
    $filtered_sentences = array();
    foreach ($sentences as $sentence) {
        $trimmed_sentence = trim($sentence);
        if (!empty($trimmed_sentence) && !in_array($trimmed_sentence, $filtered_sentences)) {
            $filtered_sentences[] = $trimmed_sentence;
        }
    }
?>
    <p><?php esc_html_e('Products general comments from regular users (Non buyers),Enter one sentence per line', 'comment-generator'); ?></p>
    <textarea name="wpex_comment_generator_product_non_buyer_sentences" id="wpex_comment_generator_product_non_buyer_sentences" rows="5" cols="50">
    <?php
    foreach ($filtered_sentences as $sentence) {
        echo esc_textarea($sentence) . "\n";
    }
    ?></textarea>
<?php
}

/**
 * Renders the custom comment authors textarea
 * Handles author name and email input in specified format
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_custom_authors_cb()
{
    $custom_authors = (array) get_option('wpex_comment_generator_custom_authors', array());

    // Split textarea content into individual authors using line breaks
    $authors = explode("\n", implode("\n", $custom_authors));

    // Remove duplicates while considering leading and trailing spaces
    $filtered_authors = array();
    foreach ($authors as $author) {
        $trimmed_author = trim($author);
        if (!empty($trimmed_author) && !in_array($trimmed_author, $filtered_authors)) {
            $filtered_authors[] = $trimmed_author;
        }
    }
?>
    <p><?php esc_html_e('Add one author per line in the format "Author Name|author@example.com".', 'comment-generator'); ?></p>
    <textarea name="wpex_comment_generator_custom_authors" id="wpex_comment_generator_custom_authors" rows="5" cols="50">
    <?php
    foreach ($filtered_authors as $author) {
        echo esc_textarea($author) . "\n";
    }
    ?>
    </textarea>
<?php
}

/**
 * Renders the delete commented items button and description
 * Provides functionality to clear saved post IDs
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_delete_commented_items_cb()
{
?>
    <p>
        <?php esc_html_e('If a comment is made on a post or product, the plugin saves its ID, so as to avoid creating consecutive comments on a post in a segmented implementation.', 'comment-generator'); ?><br>
        <?php esc_html_e('You can delete the list of saved IDs by pressing the button below.', 'comment-generator'); ?><br>
        <?php esc_html_e('This will not delete any comments.', 'comment-generator'); ?>
    </p>
    <button type="button" id="wpex_comment_generator_ajax_nonce-btn" class="button button-secondary"><?php esc_html_e('Delete Commented Posts', 'comment-generator'); ?></button>
<?php
}
