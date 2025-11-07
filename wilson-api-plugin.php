<?php
/**
 * Plugin Name: Wilson API Challenge Plugin
 * Plugin URI: https://github.com/wilson/api-challenge-plugin
 * Description: A secure, modern WordPress plugin that retrieves and displays data from a remote API with caching, custom Gutenberg block, and WP-CLI support.
 * Version: 1.0.0
 * Author: Wilson
 * Author URI: https://wilson.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wilson-api-plugin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WilsonApiPlugin
 */

namespace WilsonApiPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WILSON_API_PLUGIN_VERSION', '1.0.0');
define('WILSON_API_PLUGIN_FILE', __FILE__);
define('WILSON_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WILSON_API_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WILSON_API_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader
if (file_exists(WILSON_API_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WILSON_API_PLUGIN_DIR . 'vendor/autoload.php';
}

// Require manual autoloader if Composer is not available
if (!class_exists('WilsonApiPlugin\\Core\\Plugin')) {
    spl_autoload_register(function ($class) {
        $prefix = 'WilsonApiPlugin\\';
        $base_dir = WILSON_API_PLUGIN_DIR . 'src/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

/**
 * Initialize the plugin
 *
 * @return void
 */
function wilson_api_plugin_init() {
    try {
        $plugin = \WilsonApiPlugin\Core\Plugin::get_instance();
        $plugin->run();
    } catch (\Exception $e) {
        // Log error and show admin notice
        error_log('Wilson API Plugin Error: ' . $e->getMessage());
        
        add_action('admin_notices', function() use ($e) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: %s: Error message */
                        __('Wilson API Plugin Error: %s', 'wilson-api-plugin'),
                        $e->getMessage()
                    )
                )
            );
        });
    }
}

add_action('plugins_loaded', __NAMESPACE__ . '\\wilson_api_plugin_init');

/**
 * Plugin activation hook
 *
 * @return void
 */
function wilson_api_plugin_activate() {
    // Set default options
    if (false === get_option('wilson_api_cache_duration')) {
        add_option('wilson_api_cache_duration', HOUR_IN_SECONDS);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\\wilson_api_plugin_activate');

/**
 * Plugin deactivation hook
 *
 * @return void
 */
function wilson_api_plugin_deactivate() {
    // Clear cached data
    delete_transient('wilson_api_data');
    delete_transient('wilson_api_data_timestamp');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\wilson_api_plugin_deactivate');