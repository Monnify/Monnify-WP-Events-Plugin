<?php

namespace monnify\tec\classes;

/**
 * Class REST
 *
 * @package monnify\tec\classes
 */
class REST extends \tad_DI52_ServiceProvider {
	public function register() {
		$this->container->singleton( REST\Order_Endpoint::class );
	}

	/**
	 * Register the endpoints for handling orders and webhooks.
	 */
	public function register_endpoints() {
		$this->container->make( REST\Order_Endpoint::class )->register();
	}
}
