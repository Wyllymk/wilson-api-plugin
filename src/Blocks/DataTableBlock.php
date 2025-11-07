<?php
/**
 * Data Table Gutenberg Block
 *
 * @package WilsonApiPlugin\Blocks
 */

namespace WilsonApiPlugin\Blocks;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DataTableBlock Class
 *
 * Registers and manages the custom Gutenberg block for displaying API data
 */
class DataTableBlock {
    
    /**
     * Block name
     *
     * @var string
     */
    private const BLOCK_NAME = 'wilson-api/data-table';
    
    /**
     * Initialize block
     *
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }
    
    /**
     * Register Gutenberg block
     *
     * @return void
     */
    public function register_block() {
        // Check if Gutenberg is active
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register block
        register_block_type(
            self::BLOCK_NAME,
            [
                'editor_script' => 'wilson-api-block-editor',
                'editor_style' => 'wilson-api-block-editor',
                'style' => 'wilson-api-block',
                'render_callback' => [$this, 'render_block'],
                'attributes' => [
                    'visibleColumns' => [
                        'type' => 'object',
                        'default' => [],
                    ],
                    'showHeader' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'blockId' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ]
        );
        
        // Register block assets
        $this->register_block_assets();
    }
    
    /**
     * Register block assets
     *
     * @return void
     */
    private function register_block_assets() {
        // Register editor script
        wp_register_script(
            'wilson-api-block-editor',
            WILSON_API_PLUGIN_URL . 'build/block.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n',
                'wp-block-editor',
            ],
            WILSON_API_PLUGIN_VERSION,
            true
        );
        
        // Register editor styles
        wp_register_style(
            'wilson-api-block-editor',
            WILSON_API_PLUGIN_URL . 'build/block-editor.css',
            ['wp-edit-blocks'],
            WILSON_API_PLUGIN_VERSION
        );
        
        // Register frontend styles
        wp_register_style(
            'wilson-api-block',
            WILSON_API_PLUGIN_URL . 'assets/css/block.css',
            [],
            WILSON_API_PLUGIN_VERSION
        );
        
        // Localize script with AJAX data
        wp_localize_script(
            'wilson-api-block-editor',
            'wilsonApiBlock',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'action' => 'wilson_api_get_data',
                'i18n' => [
                    'loading' => __('Loading data...', 'wilson-api-plugin'),
                    'error' => __('Error loading data', 'wilson-api-plugin'),
                    'noData' => __('No data available', 'wilson-api-plugin'),
                ],
            ]
        );
    }
    
    /**
     * Enqueue editor assets
     *
     * @return void
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script('wilson-api-block-editor');
        wp_enqueue_style('wilson-api-block-editor');
    }
    
    /**
     * Render block on frontend
     *
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes) {
        // Generate unique block ID if not set
        $block_id = !empty($attributes['blockId']) 
            ? sanitize_key($attributes['blockId'])
            : 'wilson-api-block-' . wp_rand();
        
        // Sanitize attributes
        $visible_columns = isset($attributes['visibleColumns']) 
            ? $attributes['visibleColumns'] 
            : [];
        
        $show_header = isset($attributes['showHeader']) 
            ? (bool) $attributes['showHeader'] 
            : true;
        
        // Enqueue frontend script
        wp_enqueue_script(
            'wilson-api-block-frontend',
            WILSON_API_PLUGIN_URL . 'assets/js/block.js',
            ['jquery'],
            WILSON_API_PLUGIN_VERSION,
            true
        );
        
        // Sanitize block ID for JavaScript variable name (replace non-alphanumeric with underscore)
        $sanitized_block_id = preg_replace('/[^a-zA-Z0-9_]/', '_', $block_id);
        
        // Localize script with sanitized variable name
        wp_localize_script(
            'wilson-api-block-frontend',
            'wilsonApiBlockData_' . $sanitized_block_id,
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'action' => 'wilson_api_get_data',
                'blockId' => $block_id,
                'visibleColumns' => $visible_columns,
                'showHeader' => $show_header,
                'i18n' => [
                    'loading' => __('Loading data...', 'wilson-api-plugin'),
                    'error' => __('Error loading data. Please try again later.', 'wilson-api-plugin'),
                    'noData' => __('No data available.', 'wilson-api-plugin'),
                ],
            ]
        );
        
        // Enqueue styles
        wp_enqueue_style('wilson-api-block');
        
        // Build block HTML
        ob_start();
        ?>
<div class="wilson-api-block" id="<?php echo esc_attr($block_id); ?>" data-block-id="<?php echo esc_attr($block_id); ?>"
    data-sanitized-id="<?php echo esc_attr($sanitized_block_id); ?>"
    data-visible-columns="<?php echo esc_attr(wp_json_encode($visible_columns)); ?>"
    data-show-header="<?php echo esc_attr($show_header ? '1' : '0'); ?>">
    <div class="wilson-api-block-loading">
        <span class="spinner"></span>
        <p><?php echo esc_html__('Loading data...', 'wilson-api-plugin'); ?></p>
    </div>
</div>
<?php
        
        return ob_get_clean();
    }
}