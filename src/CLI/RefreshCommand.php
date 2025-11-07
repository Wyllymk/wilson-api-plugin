<?php
/**
 * WP-CLI Command for refreshing API data
 *
 * @package WilsonApiPlugin\CLI
 */

namespace WilsonApiPlugin\CLI;

use WilsonApiPlugin\Api\ApiClient;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RefreshCommand Class
 *
 * Provides WP-CLI command to force refresh API data
 */
class RefreshCommand {

	/**
	 * Force refresh API data on next request
	 *
	 * This command marks the data for refresh, overriding the 1-hour cache limit.
	 * The actual refresh will occur on the next AJAX request or page load.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wilson-api refresh
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments
	 * @param array $assoc_args Associative arguments
	 * @return void
	 */
	public static function refresh( $args, $assoc_args ) {
		$api_client = new ApiClient();

		// Mark data for force refresh
		$marked = $api_client->mark_for_refresh();

		if ( $marked ) {
			\WP_CLI::success(
				__( 'Data marked for refresh. The cache will be bypassed on the next request.', 'wilson-api-plugin' )
			);

			// Optionally fetch now to provide immediate feedback
			\WP_CLI::log( __( 'Fetching fresh data from API...', 'wilson-api-plugin' ) );

			$data = $api_client->get_data( true );

			if ( is_wp_error( $data ) ) {
				\WP_CLI::error(
					sprintf(
						/* translators: %s: Error message */
						__( 'Failed to fetch data: %s', 'wilson-api-plugin' ),
						$data->get_error_message()
					)
				);
				return;
			}

			// Display summary of fetched data
			$item_count = is_array( $data ) ? count( $data ) : 1;

			\WP_CLI::success(
				sprintf(
					/* translators: %d: Number of items */
					_n(
						'Successfully fetched %d item from API.',
						'Successfully fetched %d items from API.',
						$item_count,
						'wilson-api-plugin'
					),
					$item_count
				)
			);

			// Show cache info
			$cache_info = $api_client->get_cache_info();

			\WP_CLI::log(
				sprintf(
					/* translators: %s: Expiration time */
					__( 'Cache will expire in: %s', 'wilson-api-plugin' ),
					human_time_diff( time(), time() + $cache_info['expires_in'] )
				)
			);

		} else {
			\WP_CLI::error(
				__( 'Failed to mark data for refresh. Please try again.', 'wilson-api-plugin' )
			);
		}
	}
}
