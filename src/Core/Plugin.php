<?php
/**
 * Main Plugin Class
 *
 * @package WilsonApiPlugin\Core
 */

namespace WilsonApiPlugin\Core;

use WilsonApiPlugin\Admin\AdminPage;
use WilsonApiPlugin\Api\AjaxHandler;
use WilsonApiPlugin\Api\ApiClient;
use WilsonApiPlugin\Blocks\DataTableBlock;
use WilsonApiPlugin\CLI\RefreshCommand;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 *
 * Implements Singleton pattern to ensure only one instance exists
 */
class Plugin {
    
    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static $instance = null;
    
    /**
     * API Client instance
     *
     * @var ApiClient
     */
    private $api_client;
    
    /**
     * AJAX Handler instance
     *
     * @var AjaxHandler
     */
    private $ajax_handler;
    
    /**
     * Admin Page instance
     *
     * @var AdminPage
     */
    private $admin_page;
    
    /**
     * Data Table Block instance
     *
     * @var DataTableBlock
     */
    private $data_table_block;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init_components();
    }
    
    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components() {
        $this->api_client = new ApiClient();
        $this->ajax_handler = new AjaxHandler($this->api_client);
        $this->admin_page = new AdminPage($this->api_client);
        $this->data_table_block = new DataTableBlock();
    }
    
    /**
     * Run the plugin
     *
     * @return void
     */
    public function run() {
        // Load text domain for translations
        add_action('init', [$this, 'load_textdomain']);
        
        // Initialize AJAX handler
        $this->ajax_handler->init();
        
        // Initialize admin page
        $this->admin_page->init();
        
        // Initialize Gutenberg block
        $this->data_table_block->init();
        
        // Register WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('wilson-api refresh', [RefreshCommand::class, 'refresh']);
        }
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Load plugin text domain for translations
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wilson-api-plugin',
            false,
            dirname(WILSON_API_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ('toplevel_page_wilson-api-data' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'wilson-api-admin',
            WILSON_API_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WILSON_API_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'wilson-api-admin',
            WILSON_API_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WILSON_API_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script(
            'wilson-api-admin',
            'wilsonApiAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wilson_api_refresh'),
                'i18n' => [
                    'refreshing' => __('Refreshing...', 'wilson-api-plugin'),
                    'success' => __('Data refreshed successfully!', 'wilson-api-plugin'),
                    'error' => __('Error refreshing data. Please try again.', 'wilson-api-plugin'),
                ],
            ]
        );
    }
    
    /**
     * Prevent cloning of the instance
     *
     * @return void
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     *
     * @return void
     */
    public function __wakeup() {
        throw new \Exception(__('Cannot unserialize singleton', 'wilson-api-plugin'));
    }
}