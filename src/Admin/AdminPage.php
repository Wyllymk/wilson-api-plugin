<?php
/**
 * Admin Page for displaying API data
 *
 * @package WilsonApiPlugin\Admin
 */

namespace WilsonApiPlugin\Admin;

use WilsonApiPlugin\Api\ApiClient;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AdminPage Class
 *
 * Creates and manages the WordPress admin page styled similar to WP Mail SMTP
 */
class AdminPage {
    
    /**
     * API Client instance
     *
     * @var ApiClient
     */
    private $api_client;
    
    /**
     * Page slug
     *
     * @var string
     */
    private const PAGE_SLUG = 'wilson-api-data';
    
    /**
     * Constructor
     *
     * @param ApiClient $api_client API Client instance
     */
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Initialize admin page
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_menu_page']);
    }
    
    /**
     * Register admin menu page
     *
     * @return void
     */
    public function register_menu_page() {
        add_menu_page(
            __('Wilson API Data', 'wilson-api-plugin'),
            __('Wilson API Data', 'wilson-api-plugin'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page'],
            'dashicons-database-view',
            30
        );
    }
    
    /**
     * Render admin page
     *
     * @return void
     */
    public function render_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'wilson-api-plugin')
            );
        }
        
        // Get data
        $data = $this->api_client->get_data();
        $cache_info = $this->api_client->get_cache_info();
        $has_error = is_wp_error($data);
        
        ?>
<div class="wrap wilson-api-wrap">
    <div class="wilson-api-header">
        <div class="wilson-api-header-content">
            <h1 class="wilson-api-title">
                <?php echo esc_html__('Wilson API Data', 'wilson-api-plugin'); ?>
            </h1>
            <p class="wilson-api-subtitle">
                <?php echo esc_html__('View and manage data retrieved from the external API', 'wilson-api-plugin'); ?>
            </p>
        </div>
    </div>

    <div class="wilson-api-content">
        <div class="wilson-api-card">
            <div class="wilson-api-card-header">
                <h2><?php echo esc_html__('API Data', 'wilson-api-plugin'); ?></h2>
                <div class="wilson-api-actions">
                    <?php if ($cache_info['has_cache']) : ?>
                    <span class="wilson-api-cache-info">
                        <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: %s: Time since last update */
                                            __('Last updated: %s ago', 'wilson-api-plugin'),
                                            human_time_diff($cache_info['timestamp'])
                                        )
                                    );
                                    ?>
                    </span>
                    <?php endif; ?>
                    <button type="button" class="button button-primary wilson-api-refresh-btn" id="wilson-api-refresh">
                        <span class="dashicons dashicons-update"></span>
                        <?php echo esc_html__('Refresh Data', 'wilson-api-plugin'); ?>
                    </button>
                </div>
            </div>

            <div class="wilson-api-card-body">
                <?php if ($has_error) : ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php echo esc_html__('Error:', 'wilson-api-plugin'); ?></strong>
                        <?php echo esc_html($data->get_error_message()); ?>
                    </p>
                </div>
                <?php else : ?>
                <div id="wilson-api-data-container">
                    <?php $this->render_data_table($data); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="wilson-api-card wilson-api-info-card">
            <div class="wilson-api-card-header">
                <h2><?php echo esc_html__('Information', 'wilson-api-plugin'); ?></h2>
            </div>
            <div class="wilson-api-card-body">
                <div class="wilson-api-info-grid">
                    <div class="wilson-api-info-item">
                        <span class="wilson-api-info-label">
                            <?php echo esc_html__('API Endpoint:', 'wilson-api-plugin'); ?>
                        </span>
                        <code>https://miusage.com/v1/challenge/1/</code>
                    </div>
                    <div class="wilson-api-info-item">
                        <span class="wilson-api-info-label">
                            <?php echo esc_html__('Cache Duration:', 'wilson-api-plugin'); ?>
                        </span>
                        <span><?php echo esc_html__('1 hour', 'wilson-api-plugin'); ?></span>
                    </div>
                    <div class="wilson-api-info-item">
                        <span class="wilson-api-info-label">
                            <?php echo esc_html__('WP-CLI Command:', 'wilson-api-plugin'); ?>
                        </span>
                        <code>wp wilson-api refresh</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    }
    
    /**
     * Render data table
     *
     * @param array $data Data to display
     * @return void
     */
    private function render_data_table($data) {
        if (empty($data)) {
            echo '<p class="wilson-api-no-data">' . esc_html__('No data available.', 'wilson-api-plugin') . '</p>';
            return;
        }
        
        // Determine if data is array of objects or single object
        $is_list = isset($data[0]) && is_array($data[0]);
        
        if ($is_list) {
            $this->render_list_table($data);
        } else {
            $this->render_single_object($data);
        }
    }
    
    /**
     * Render list table
     *
     * @param array $data List of data items
     * @return void
     */
    private function render_list_table($data) {
        // Get headers from first item
        $headers = array_keys($data[0]);
        
        ?>
<div class="wilson-api-table-wrapper">
    <table class="wilson-api-table widefat striped">
        <thead>
            <tr>
                <?php foreach ($headers as $header) : ?>
                <th><?php echo esc_html(ucwords(str_replace('_', ' ', $header))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row) : ?>
            <tr>
                <?php foreach ($headers as $header) : ?>
                <td>
                    <?php 
                                    $value = isset($row[$header]) ? $row[$header] : '';
                                    if (is_array($value) || is_object($value)) {
                                        echo '<code>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
                                    } else {
                                        echo esc_html($value);
                                    }
                                    ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
    }
    
    /**
     * Render single object as key-value pairs
     *
     * @param array $data Data object
     * @return void
     */
    private function render_single_object($data) {
        ?>
<div class="wilson-api-table-wrapper">
    <table class="wilson-api-table widefat">
        <tbody>
            <?php foreach ($data as $key => $value) : ?>
            <tr>
                <th><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                <td>
                    <?php 
                                if (is_array($value) || is_object($value)) {
                                    echo '<pre><code>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</code></pre>';
                                } else {
                                    echo esc_html($value);
                                }
                                ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
    }
}