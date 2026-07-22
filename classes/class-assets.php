<?php
/**
 * Handles registering and setup for assets on Tickets Commerce.
 *
 * @package monnify\tec\classes
 */
namespace monnify\tec\classes;

use TEC\Tickets\Commerce\Checkout;

/**
 * Class Assets
 *
 * @package monnify\tec\classes
 */
class Assets extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		$plugin = \Tribe__Tickets__Main::instance();

		require_once( MNFY_TEC_PATH . '/classes/REST/Order_Endpoint.php' );

		/**
		 * This file is intentionally enqueued on every page of the administration.
		 */
		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-monnify-global-admin-styles',
			MNFY_TEC_URL . 'assets/css/admin-settings.css',
			array(),
			'admin_enqueue_scripts',
			array()
		);

		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-monnify-checkout',
			MNFY_TEC_URL . 'assets/js/checkout.js',
			array(
				'jquery',
				'tribe-common',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-toggler',
			),
			'tec-tickets-commerce-checkout-shortcode-assets',
			array(
				'groups'       => array(
					'tec-tickets-commerce-gateway-monnify',
				),
				'conditionals' => array( $this, 'should_enqueue_assets' ),
				'localize'     => array(
					'name' => 'tecTicketsMonnifyCheckout',
					'data' => array(
						'orderEndpoint' => tribe( \monnify\tec\classes\REST\Order_Endpoint::class )->get_route_url(),
						'errorMessages' => array(
							'name'          => esc_html__( 'Name is required', 'monnify-for-events-calendar' ),
							'email_address' => esc_html__( 'A valid email address is required', 'monnify-for-events-calendar' ),
							'connection'    => esc_html__( 'An error has occurred, please refresh the page and try again.', 'monnify-for-events-calendar' ),
							'createOrder'   => esc_html__( 'There was an error creating your order, please try again or contact the website administrator for assistance.', 'monnify-for-events-calendar' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Define if the assets for Monnify should be enqueued or not.
	 *
	 * @return bool
	 */
	public function should_enqueue_assets() {
		return tribe( Checkout::class )->is_current_page() && tribe( Gateway::class )->is_enabled() && tribe( Gateway::class )->is_active();
	}
}
