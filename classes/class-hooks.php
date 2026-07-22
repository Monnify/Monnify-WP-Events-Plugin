<?php
/**
 * Handles hooking all the actions and filters used by the module.
 */

namespace monnify\tec\classes;

/**
 * Class Hooks.
 *
 * @package monnify\tec\classes
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Commerce component.
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_action( 'admin_notices', array( $this, 'filter_admin_notices' ) );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', array( $this, 'filter_add_gateway' ), 10, 2 );
	}

	/**
	 * Register the Endpoints from Monnify.
	 */
	public function register_endpoints() {
		$this->container->make( REST::class )->register_endpoints();
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @param array $gateways List of available gateways.
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}

	/**
	 * Filter admin notices.
	 */
	public function filter_admin_notices() {
		return $this->container->make( Gateway::class )->filter_admin_notices();
	}
}
