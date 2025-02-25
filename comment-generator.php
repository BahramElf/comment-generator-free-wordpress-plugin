<?php

/**
 * Plugin Name: Comment Generator
 * Plugin URI: https://www.wordpress.com/plugins/comment-generator
 * Description: A powerful plugin for creating comments on all kinds of posts, pages and products in WordPress and WooCommerce with professional settings for inserting comments in different modes. All rights of this plugin belongs to WPEX.ir
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: wpexir
 * Author URI: https://wpex.ir/en
 * Text Domain: comment-generator
 * Domain Path: /languages
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * @package CommentGenerator
 */

defined('ABSPATH') || exit;

/**
 * Define plugin constants
 */
if (!defined('WPEX_COMMENT_GENERATOR_VERSION')) {
    define('WPEX_COMMENT_GENERATOR_VERSION', '1.0.0');
}
if (!defined('WPEX_COMMENT_GENERATOR_PLUGIN_PATH')) {
    define('WPEX_COMMENT_GENERATOR_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('WPEX_COMMENT_GENERATOR_PLUGIN_url')) {
    define('WPEX_COMMENT_GENERATOR_PLUGIN_url', plugin_dir_url(__FILE__));
}

/**
 * Include required files
 */
require_once(WPEX_COMMENT_GENERATOR_PLUGIN_PATH . 'includes/comment-generator-settings.php');
require_once(WPEX_COMMENT_GENERATOR_PLUGIN_PATH . 'includes/comment-generator-functions.php');

/**
 * Handles plugin activation tasks.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_activate()
{
    /**
     * Fires after plugin activation
     * 
     * @since 1.0.0
     */
    do_action('wpex_comment_generator_after_activate');
}
register_activation_hook(__FILE__, 'wpex_comment_generator_activate');

/**
 * Handles plugin deactivation tasks.
 * 
 * @since 1.0.0
 * @return void
 */
function wpex_comment_generator_deactivate()
{
    /**
     * Fires after plugin deactivation
     * 
     * @since 1.0.0
     */
    do_action('wpex_comment_generator_after_deactivate');
}
register_deactivation_hook(__FILE__, 'wpex_comment_generator_deactivate');
