<?php
/**
 * AJAX Handler for frontend requests
 *
 * @package WilsonApiPlugin\Api
 */

namespace WilsonApiPlugin\Api;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AjaxHandler Class
 *
 * Handles AJAX requests from both logged-in and non-logged-in users
 */
class AjaxHandler {

	/**
	 * API Client instance
	 *
	 * @var ApiClient
	 */
	private $api_client;

	/**
	 * AJAX action name
	 *
	 * @var string
	 */
	private const ACTION = 'wilson_api_get_data';

	/**
	 * AJAX refresh action name
	 *
	 * @var string
	 */
	private const REFRESH_ACTION = 'wilson_api_refresh_data';

	/**
	 * Constructor
	 *
	 * @param ApiClient $api_client API Client instance
	 */
	public function __construct( ApiClient $api_client ) {
		$this->api_client = $api_client;
	}

	/**
	 * Initialize AJAX handlers
	 *
	 * @return void
	 */
	public function init() {
		// Register AJAX handler for logged-in users
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_get_data' ) );

		// Register AJAX handler for non-logged-in users
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle_get_data' ) );

		// Register AJAX handler for refresh (logged-in users only)
		add_action( 'wp_ajax_' . self::REFRESH_ACTION, array( $this, 'handle_refresh_data' ) );
	}

	/**
	 * Handle AJAX request to get data
	 *
	 * This endpoint is accessible to both logged-in and non-logged-in users
	 * as per requirements.
	 *
	 * @return void
	 */
	public function handle_get_data() {
		// Get data from API (with caching)
		$data = $this->api_client->get_data();

		// Check for errors
		if ( is_wp_error( $data ) ) {
			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				500
			);
			return;
		}

		// Get cache info for debugging
		$cache_info = $this->api_client->get_cache_info();

		// Sanitize and prepare response
		$response = array(
			'data'       => $this->sanitize_data( $data ),
			'cache_info' => array(
				'is_cached' => $cache_info['has_cache'],
				'cache_age' => $cache_info['age'],
			),
			'timestamp'  => current_time( 'mysql' ),
		);

		// Send successful response
		wp_send_json_success( $response );
	}

	/**
	 * Handle AJAX request to refresh data
	 *
	 * This endpoint is only accessible to logged-in users with proper permissions
	 *
	 * @return void
	 */
	public function handle_refresh_data() {
		// Verify user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You must be logged in to refresh data.', 'wilson-api-plugin' ),
				),
				401
			);
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to refresh data.', 'wilson-api-plugin' ),
				),
				403
			);
			return;
		}

		// Verify nonce for security
		if ( ! check_ajax_referer( 'wilson_api_refresh', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'wilson-api-plugin' ),
				),
				403
			);
			return;
		}

		// Force refresh data
		$data = $this->api_client->get_data( true );

		// Check for errors
		if ( is_wp_error( $data ) ) {
			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				500
			);
			return;
		}

		// Prepare response
		$response = array(
			'data'      => $this->sanitize_data( $data ),
			'message'   => __( 'Data refreshed successfully!', 'wilson-api-plugin' ),
			'timestamp' => current_time( 'mysql' ),
		);

		// Send successful response
		wp_send_json_success( $response );
	}

	/**
	 * Sanitize data for output
	 *
	 * Recursively sanitizes all data to prevent XSS attacks
	 *
	 * @param mixed $data Data to sanitize
	 * @return mixed Sanitized data
	 */
	private function sanitize_data( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_data' ), $data );
		}

		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}

		if ( is_numeric( $data ) ) {
			return $data;
		}

		if ( is_bool( $data ) ) {
			return $data;
		}

		if ( null === $data ) {
			return null;
		}

		// For objects or other types, convert to string and sanitize
		return sanitize_text_field( (string) $data );
	}

	/**
	 * Get AJAX URL for frontend use
	 *
	 * @return string AJAX URL
	 */
	public static function get_ajax_url() {
		return admin_url( 'admin-ajax.php' );
	}

	/**
	 * Get AJAX action name
	 *
	 * @return string Action name
	 */
	public static function get_action() {
		return self::ACTION;
	}
}
