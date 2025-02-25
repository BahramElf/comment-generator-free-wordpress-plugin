<?php
defined('ABSPATH') || exit;
/**
 * Registers the admin menu for Comment Generator settings page.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_menu()
{
    add_menu_page(
        __('Comment Generator Pro', 'comment-generator'),
        __('Comment Generator', 'comment-generator'),
        'manage_options',
        'wpex_comment_generator_settings',
        'wpex_comment_generator_settings_page',
        'dashicons-format-chat', // Icon
        100 // Position
    );
}
add_action('admin_menu', 'wpex_comment_generator_settings_menu');

/**
 * Adds settings link to plugins page.
 * 
 * @since 1.0.0
 * @param array $links Array of plugin action links
 * @param string $file Plugin file path
 * @return array Modified array of plugin action links
 */
function wpex_comment_generator_add_plugin_settings_link($links, $file)
{
    if ($file === 'comment-generator/comment-generator.php') {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wpex_comment_generator_settings')) . '">' . __('Settings', 'comment-generator') . '</a>';
        array_push($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'wpex_comment_generator_add_plugin_settings_link', 10, 2);


/**
 * Initializes plugin settings and registers setting fields.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_settings_init()
{
    // Add explicit sanitization callbacks for each setting
    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_specific_post_id',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_post_type',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'post',
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_category',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_product_limit',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_number',
            'default' => 1,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_comment_count',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_number',
            'default' => 1,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_comment_date',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_product_stock_status',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'instock',
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_product_score',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_comment_status',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_comment_from',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'buyer',
            'show_in_rest' => false
        )
    );


    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_general_sentences',
        array(
            'type' => 'array',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_sentences',
            'default' => array(),
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_product_buyer_sentences',
        array(
            'type' => 'array',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_sentences',
            'default' => array(),
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_product_non_buyer_sentences',
        array(
            'type' => 'array',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_sentences',
            'default' => array(),
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_custom_authors',
        array(
            'type' => 'array',
            'sanitize_callback' => 'wpex_comment_generator_sanitize_authors',
            'default' => array(),
            'show_in_rest' => false
        )
    );

    register_setting(
        'wpex_comment_generator_settings_group',
        'wpex_comment_generator_comment_mode',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'single',
            'show_in_rest' => false
        )
    );


    add_settings_section(
        'wpex_comment_generator_settings_section',
        __('Comment Generation Settings', 'comment-generator'),
        'wpex_comment_generator_settings_section_cb',
        'wpex_comment_generator_settings_page'
    );
    // Add the comment mode selection field to the settings page
    add_settings_field(
        'wpex_comment_generator_comment_mode',
        __('Comment Mode', 'comment-generator'),
        'wpex_comment_generator_comment_mode_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    add_settings_field(
        'wpex_comment_generator_specific_post_id',
        __('Specific post id', 'comment-generator'),
        'wpex_comment_generator_specific_post_id_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'single-mode-fields')
    );
    add_settings_field(
        'wpex_comment_generator_post_type',
        __('Post Type', 'comment-generator'),
        'wpex_comment_generator_post_type_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    add_settings_field(
        'wpex_comment_generator_category',
        __('Category', 'comment-generator'),
        'wpex_comment_generator_category_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'category-mode-fields')
    );
    add_settings_field(
        'wpex_comment_generator_product_limit',
        __('Number of Posts', 'comment-generator'),
        'wpex_comment_generator_product_limit_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'category-mode-fields')
    );
    add_settings_field(
        'wpex_comment_generator_comment_count',
        __('Number of Comments', 'comment-generator'),
        'wpex_comment_generator_comment_count_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    add_settings_field(
        'wpex_comment_generator_comment_date',
        __('Date of Comments from', 'comment-generator'),
        'wpex_comment_generator_comment_date_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    add_settings_field(
        'wpex_comment_generator_product_score',
        __('Product review rating', 'comment-generator'),
        'wpex_comment_generator_product_score_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'product-fields')
    );
    add_settings_field(
        'wpex_comment_generator_product_stock_status',
        __('Product stock status', 'comment-generator'),
        'wpex_comment_generator_product_stock_status_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'product-fields')
    );
    add_settings_field(
        'wpex_comment_generator_comment_status',
        __('Comment status', 'comment-generator'),
        'wpex_comment_generator_comment_status_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    add_settings_field(
        'wpex_comment_generator_comment_from',
        __('Comment from', 'comment-generator'),
        'wpex_comment_generator_comment_from_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'comment-from')
    );
    // Add the General Comment Sentences field to the settings page
    add_settings_field(
        'wpex_comment_generator_general_sentences',
        __('General Comment Sentences', 'comment-generator'),
        'wpex_comment_generator_general_sentences_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'none-product-fields')
    );
    // Add the product buyer Comment Sentences field to the settings page
    add_settings_field(
        'wpex_comment_generator_product_buyer_sentences',
        __('General product buyer Sentences', 'comment-generator'),
        'wpex_comment_generator_product_buyer_sentences_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'product-fields')
    );
    // Add the product none buyer Comment Sentences field to the settings page
    add_settings_field(
        'wpex_comment_generator_product_non_buyer_sentences',
        __('General product none buyer Sentences', 'comment-generator'),
        'wpex_comment_generator_product_non_buyer_sentences_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section',
        array('class' => 'product-fields')
    );
    // Add the Custom Comment Authors field to the settings page
    add_settings_field(
        'wpex_comment_generator_custom_authors',
        __('Custom Comment Authors', 'comment-generator'),
        'wpex_comment_generator_custom_authors_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
    // Add the Delete Commented Items button to the settings page
    add_settings_field(
        'wpex_comment_generator_delete_commented_items',
        __('Delete Commented Items', 'comment-generator'),
        'wpex_comment_generator_delete_commented_items_cb',
        'wpex_comment_generator_settings_page',
        'wpex_comment_generator_settings_section'
    );
}
add_action('admin_init', 'wpex_comment_generator_settings_init');
