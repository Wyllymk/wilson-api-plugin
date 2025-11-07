<?php
/**
 * API Client for external API communication
 *
 * @package WilsonApiPlugin\Api
 */

namespace WilsonApiPlugin\Api;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ApiClient Class
 *
 * Handles all communication with the external API and manages caching
 */
class ApiClient {

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://miusage.com/v1/challenge/1/';

	/**
	 * Cache transient name
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'wilson_api_data';

	/**
	 * Cache timestamp transient name
	 *
	 * @var string
	 */
	private const CACHE_TIMESTAMP_KEY = 'wilson_api_data_timestamp';

	/**
	 * Force refresh flag transient name
	 *
	 * @var string
	 */
	private const FORCE_REFRESH_KEY = 'wilson_api_force_refresh';

	/**
	 * Cache duration in seconds (1 hour)
	 *
	 * @var int
	 */
	private const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Get data from API with caching
	 *
	 * This method implements intelligent caching to ensure the external API
	 * is never called more than once per hour, unless forced.
	 *
	 * @param bool $force_refresh Whether to force a refresh of cached data
	 * @return array|WP_Error Array of data on success, WP_Error on failure
	 */
	public function get_data( $force_refresh = false ) {
		// Check if force refresh is requested
		$should_force_refresh = $force_refresh || get_transient( self::FORCE_REFRESH_KEY );

		// If not forcing refresh, try to get cached data
		if ( ! $should_force_refresh ) {
			$cached_data = $this->get_cached_data();

			if ( false !== $cached_data ) {
				return $cached_data;
			}
		}

		// Clear force refresh flag if it was set
		if ( $should_force_refresh ) {
			delete_transient( self::FORCE_REFRESH_KEY );
		}

		// Fetch fresh data from API
		$data = $this->fetch_from_api();

		if ( is_wp_error( $data ) ) {
			// If API call fails, return cached data if available (stale cache fallback)
			$stale_cache = get_transient( self::CACHE_KEY );
			if ( false !== $stale_cache ) {
				// Log the error but return stale data
				error_log( 'Wilson API Plugin: API call failed, returning stale cache. Error: ' . $data->get_error_message() );
				return $stale_cache;
			}

			return $data;
		}

		// Cache the fresh data
		$this->cache_data( $data );

		return $data;
	}

	/**
	 * Get cached data if still valid
	 *
	 * @return array|false Cached data if valid, false otherwise
	 */
	private function get_cached_data() {
		$cached_data     = get_transient( self::CACHE_KEY );
		$cache_timestamp = get_transient( self::CACHE_TIMESTAMP_KEY );

		// Check if cache exists and is still valid
		if ( false !== $cached_data && false !== $cache_timestamp ) {
			$cache_age = time() - $cache_timestamp;

			if ( $cache_age < self::CACHE_DURATION ) {
				return $cached_data;
			}
		}

		return false;
	}

	/**
	 * Fetch data from external API
	 *
	 * @return array|WP_Error Array of data on success, WP_Error on failure
	 */
	private function fetch_from_api() {
		// Make HTTP request to external API
		$response = wp_remote_get(
			self::API_ENDPOINT,
			array(
				'timeout'   => 15,
				'headers'   => array(
					'Accept' => 'application/json',
				),
				'sslverify' => true,
			)
		);

		// Check for HTTP errors
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Failed to fetch data from API: %s', 'wilson-api-plugin' ),
					$response->get_error_message()
				)
			);
		}

		// Get response code
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new \WP_Error(
				'api_invalid_response',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'API returned invalid response code: %d', 'wilson-api-plugin' ),
					$response_code
				)
			);
		}

		// Get and decode response body
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Validate JSON decode
		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error(
				'api_invalid_json',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse API response: %s', 'wilson-api-plugin' ),
					json_last_error_msg()
				)
			);
		}

		// Validate data structure
		if ( ! $this->validate_data( $data ) ) {
			return new \WP_Error(
				'api_invalid_data',
				__( 'API returned invalid data structure', 'wilson-api-plugin' )
			);
		}

		return $data;
	}

	/**
	 * Validate data structure
	 *
	 * @param mixed $data Data to validate
	 * @return bool True if valid, false otherwise
	 */
	private function validate_data( $data ) {
		// Check if data is an array
		if ( ! is_array( $data ) ) {
			return false;
		}

		// If data is empty, it's still valid
		if ( empty( $data ) ) {
			return true;
		}

		// Basic structure validation
		// Add more specific validation based on expected data structure
		return true;
	}

	/**
	 * Cache data with timestamp
	 *
	 * @param array $data Data to cache
	 * @return void
	 */
	private function cache_data( $data ) {
		// Store data with expiration
		set_transient( self::CACHE_KEY, $data, self::CACHE_DURATION );

		// Store timestamp separately for precise cache age calculation
		set_transient( self::CACHE_TIMESTAMP_KEY, time(), self::CACHE_DURATION );
	}

	/**
	 * Mark data for force refresh on next request
	 *
	 * This is used by the WP-CLI command to override the cache
	 *
	 * @return bool True on success, false on failure
	 */
	public function mark_for_refresh() {
		return set_transient( self::FORCE_REFRESH_KEY, true, MINUTE_IN_SECONDS );
	}

	/**
	 * Clear all cached data
	 *
	 * @return void
	 */
	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
		delete_transient( self::CACHE_TIMESTAMP_KEY );
		delete_transient( self::FORCE_REFRESH_KEY );
	}

	/**
	 * Get cache information
	 *
	 * @return array Cache information including age and validity
	 */
	public function get_cache_info() {
		$cached_data     = get_transient( self::CACHE_KEY );
		$cache_timestamp = get_transient( self::CACHE_TIMESTAMP_KEY );

		if ( false === $cached_data || false === $cache_timestamp ) {
			return array(
				'has_cache' => false,
				'age'       => 0,
				'is_valid'  => false,
			);
		}

		$cache_age = time() - $cache_timestamp;

		return array(
			'has_cache'  => true,
			'age'        => $cache_age,
			'is_valid'   => $cache_age < self::CACHE_DURATION,
			'timestamp'  => $cache_timestamp,
			'expires_in' => max( 0, self::CACHE_DURATION - $cache_age ),
		);
	}
}
