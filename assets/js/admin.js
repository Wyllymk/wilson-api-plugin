/**
 * Admin Page JavaScript for Wilson API Plugin
 *
 * @package WilsonApiPlugin
 */

( function ( $ ) {
	'use strict';

	/**
	 * Initialize admin functionality
	 */
	$( document ).ready( function () {
		initRefreshButton();
	} );

	/**
	 * Initialize refresh button functionality
	 */
	function initRefreshButton() {
		const $refreshBtn = $( '#wilson-api-refresh' );

		if ( ! $refreshBtn.length ) {
			return;
		}

		$refreshBtn.on( 'click', function ( e ) {
			e.preventDefault();
			refreshData( $refreshBtn );
		} );
	}

	/**
	 * Refresh data from API
	 *
	 * @param {jQuery} $btn Refresh button element
	 */
	function refreshData( $btn ) {
		// Disable button
		$btn.prop( 'disabled', true );

		// Update button text
		const originalText = $btn.html();
		$btn.html(
			'<span class="dashicons dashicons-update spin"></span> ' +
				escapeHtml( wilsonApiAdmin.i18n.refreshing )
		);

		// Make AJAX request
		$.ajax( {
			url: wilsonApiAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wilson_api_refresh_data',
				nonce: wilsonApiAdmin.nonce,
			},
			dataType: 'json',
			success: function ( response ) {
				if ( response.success ) {
					showNotice( 'success', wilsonApiAdmin.i18n.success );

					// Update table with new data
					if ( response.data && response.data.data ) {
						updateDataTable( response.data.data );
					}

					// Reload page after 1 second to update cache info
					setTimeout( function () {
						location.reload();
					}, 1000 );
				} else {
					const errorMessage =
						response.data && response.data.message
							? response.data.message
							: wilsonApiAdmin.i18n.error;
					showNotice( 'error', errorMessage );
				}
			},
			error: function ( xhr, status, error ) {
				console.error( 'Wilson API Admin Error:', error );
				showNotice( 'error', wilsonApiAdmin.i18n.error );
			},
			complete: function () {
				// Re-enable button
				$btn.prop( 'disabled', false );
				$btn.html( originalText );
			},
		} );
	}

	/**
	 * Update data table with new data
	 *
	 * @param {Array|Object} data New data
	 */
	function updateDataTable( data ) {
		const $container = $( '#wilson-api-data-container' );

		if ( ! $container.length ) {
			return;
		}

		// Add fade effect
		$container.fadeOut( 200, function () {
			// Table will be regenerated on page reload
			$container.fadeIn( 200 );
		} );
	}

	/**
	 * Show admin notice
	 *
	 * @param {string} type Notice type (success, error, warning, info)
	 * @param {string} message Notice message
	 */
	function showNotice( type, message ) {
		// Remove existing notices
		$( '.wilson-api-notice' ).remove();

		// Create notice
		const $notice = $( '<div>' )
			.addClass( 'notice wilson-api-notice is-dismissible' )
			.addClass( 'notice-' + type )
			.append( $( '<p>' ).text( message ) );

		// Add dismiss button
		const $dismissBtn = $( '<button>' )
			.addClass( 'notice-dismiss' )
			.attr( 'type', 'button' )
			.on( 'click', function () {
				$notice.fadeOut( 200, function () {
					$( this ).remove();
				} );
			} );

		$notice.append( $dismissBtn );

		// Insert notice
		$( '.wilson-api-header' ).after( $notice );

		// Auto-dismiss after 5 seconds
		setTimeout( function () {
			$notice.fadeOut( 200, function () {
				$( this ).remove();
			} );
		}, 5000 );
	}

	/**
	 * Escape HTML to prevent XSS
	 *
	 * @param {string} text Text to escape
	 * @return {string} Escaped text
	 */
	function escapeHtml( text ) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};

		return String( text ).replace( /[&<>"']/g, function ( m ) {
			return map[ m ];
		} );
	}
} )( jQuery );
