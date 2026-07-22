<?php
namespace monnify\tec\classes;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;

/**
 * The Monnify Commerce specific settings.
 *
 * @package monnify\tec\classes
 */
class Settings extends Abstract_Settings {

	/**
	 * @inheritDoc
	 */
	public static $option_sandbox = 'tickets-commerce-monnify-sandbox';

	/**
	 * The prefix for the fields.
	 *
	 * @var string
	 */
	public static $field_prefix = 'tec-tickets-commerce-gateway-monnify-merchant-';

	/**
	 * The fields for the settings page, used to save the data.
	 *
	 * @var array
	 */
	public static $fields = array(
		'monnify_mode'        => 'test',
		'api_key_test'        => '',
		'secret_key_test'     => '',
		'contract_code_test'  => '',
		'api_key_live'        => '',
		'secret_key_live'     => '',
		'contract_code_live'  => '',
	);

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		return [
			'tickets-commerce-monnify-commerce-configure' => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			],
		];
	}

	/**
	 * Get the Monnify Commerce settings section HTML.
	 *
	 * @return string
	 */
	public function get_connection_settings_html() {
		/** @var \Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );
		$merchant    = tribe( Merchant::class );

		$context = array(
			'plugin_url'            => MNFY_TEC_URL,
			'merchant'              => $merchant,
			'is_merchant_connected' => $merchant->is_connected(),
			'is_merchant_active'    => $merchant->is_active(),
			'gateway_key'           => tribe( Gateway::class )->get_key(),
			'webhook_url'           => tribe( REST\Order_Endpoint::class )->get_route_url() . '/webhook',
			'fields'                => self::$fields,
			'field_prefix'          => self::$field_prefix,
			'saved'                 => $merchant->to_array(),
		);

		$admin_views->add_template_globals( $context );

		return $admin_views->template( 'monnify/admin-views/main', $context, false );
	}

	/**
	 * Saves the posted settings fields to the Merchant.
	 *
	 * @return void
	 */
	public static function update_settings() {
		// No section gate needed here: this is only ever invoked via the
		// 'tribe_settings_save_tab_monnify' action, which itself only fires
		// when the admin is on the Monnify settings tab.
		$merchant = tribe( Merchant::class );

		foreach ( self::$fields as $key => $default ) {
			$field_name = self::$field_prefix . $key;
			$to_save    = tribe_get_request_var( $field_name, $default );
			$merchant->set_prop( $key, sanitize_text_field( $to_save ) );
		}

		$merchant->save();
	}
}
