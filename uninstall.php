<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Callback function to delete plugin options when the plugin is uninstalled
function wpex_comment_generator_uninstall()
{
    // List of option names to delete
    $options_to_delete = array(
        'wpex_comment_generator_category',
        'wpex_comment_generator_comment_count',
        'wpex_comment_generator_comment_mode',
        'wpex_comment_generator_comment_status',
        'wpex_comment_generator_commented_items',
        'wpex_comment_generator_custom_authors',
        'wpex_comment_generator_general_index',
        'wpex_comment_generator_general_sentences',
        'wpex_comment_generator_post_type',
        'wpex_comment_generator_product_buyer_index',
        'wpex_comment_generator_product_buyer_sentences',
        'wpex_comment_generator_product_limit',
        'wpex_comment_generator_product_non_buyer_index',
        'wpex_comment_generator_product_non_buyer_sentences',
        'wpex_comment_generator_product_score',
        'wpex_comment_generator_product_stock_status',
        'wpex_comment_generator_specific_post_id',
        'wpex_comment_generator_deactivated',
    );

    // Delete each option
    foreach ($options_to_delete as $option_name) {
        delete_option($option_name);
    }
}

wpex_comment_generator_uninstall();
