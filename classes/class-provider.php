<?php
namespace monnify\tec\classes;

/**
 * Service provider for the Monnify Tickets Commerce Integration.
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 */
	public function register() {
		require_once( MNFY_TEC_PATH . '/classes/class-gateway.php' );
		$this->container->singleton( Gateway::class );

		// Register Monnify as an available payment gateway.
		add_filter( 'tec_tickets_commerce_gateways', array( $this, 'register_monnify_gateway' ) );
		add_action( 'init', array( $this, 'ensure_gateway_availability' ) );

		// NGN isn't in event-tickets' legacy currency map by default, which
		// throws "Undefined array key" warnings anywhere that map is read
		// for a Monnify (NGN-only) form/ticket.
		add_filter( 'tribe_tickets_commerce_currency_code_options_map', array( $this, 'register_legacy_currency' ) );

		$this->register_hooks();
		$this->register_assets();

		require_once( MNFY_TEC_PATH . '/classes/class-merchant.php' );
		$this->container->singleton( Merchant::class, Merchant::class, array( 'init' ) );

		require_once( MNFY_TEC_PATH . '/classes/class-settings.php' );
		$this->container->singleton( Settings::class );
		add_action( 'tribe_settings_save_tab_monnify', '\monnify\tec\classes\Settings::update_settings', 10, 1 );

		require_once( MNFY_TEC_PATH . '/classes/class-client.php' );
		$this->container->singleton( Client::class );

		require_once( MNFY_TEC_PATH . '/classes/REST/Order_Endpoint.php' );
		require_once( MNFY_TEC_PATH . '/classes/class-rest.php' );
		$this->register_endpoints();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for assets.
	 */
	protected function register_assets() {
		require_once( MNFY_TEC_PATH . '/classes/class-assets.php' );
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for hooks.
	 */
	protected function register_hooks() {
		require_once( MNFY_TEC_PATH . '/classes/class-hooks.php' );
		$hooks = new Hooks( $this->container );
		$hooks->register();

		$this->container->singleton( Hooks::class, $hooks );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		$hooks = new REST( $this->container );
		$hooks->register();

		$this->container->singleton( REST::class, $hooks );
	}

	/**
	 * Register Monnify as an available gateway.
	 */
	public function register_monnify_gateway( $gateways ) {
		if ( ! isset( $gateways['monnify'] ) ) {
			$gateways['monnify'] = Gateway::class;
		}
		return $gateways;
	}

	/**
	 * Ensure gateway is available and properly registered.
	 */
	public function ensure_gateway_availability() {
		add_filter( 'tec_tickets_commerce_available_gateways', function ( $available_gateways ) {
			if ( ! isset( $available_gateways['monnify'] ) ) {
				$available_gateways['monnify'] = Gateway::class;
			}
			return $available_gateways;
		} );
	}

	/**
	 * Adds NGN to event-tickets' legacy currency options map, since it's
	 * absent by default there (unlike the newer TEC\Tickets\Commerce\Utils\Currency
	 * class, which already includes it).
	 *
	 * @param array $map Currency code => { name, symbol, thousands_sep, decimal_point }.
	 * @return array
	 */
	public function register_legacy_currency( $map ) {
		if ( ! isset( $map['NGN'] ) ) {
			$map['NGN'] = array(
				'name'          => __( 'Nigerian Naira (NGN)', 'monnify-for-events-calendar' ),
				'symbol'        => '&#8358;',
				'thousands_sep' => ',',
				'decimal_point' => '.',
			);
		}

		return $map;
	}
}
