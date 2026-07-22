<?php
namespace monnify\tec\classes;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

/**
 * Class Gateway
 *
 * @package monnify\tec\classes
 */
class Gateway extends Abstract_Gateway {

	/**
	 * @inheritDoc
	 */
	protected static string $key = 'monnify';

	/**
	 * @inheritDoc
	 */
	protected static string $settings = Settings::class;

	/**
	 * @inheritDoc
	 */
	protected static string $merchant = Merchant::class;

	/**
	 * @inheritDoc
	 */
	protected static array $supported_currencies = array(
		'NGN' => array(
			'code'   => 'NGN',
			'symbol' => '₦',
			'entity' => '&#8358;',
		),
	);

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'Monnify', 'monnify-for-events-calendar' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		return array(
			array(
				'slug'    => 'tc-monnify-currency-not-supported',
				'content' => __( 'Currency not supported', 'monnify-for-events-calendar' ),
				'type'    => 'error',
			),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_logo_url(): string {
		return MNFY_TEC_URL . '/assets/images/icon.png';
	}

	/**
	 * @inheritDoc
	 */
	public function get_subtitle(): string {
		return __( 'Accept card, bank transfer, and USSD payments via Monnify.', 'monnify-for-events-calendar' );
	}

	/**
	 * @inheritDoc
	 */
	public static function is_enabled(): bool {
		if ( ! static::should_show() ) {
			return false;
		}

		$option_value = tribe_get_option( static::get_enabled_option_key() );
		if ( '' !== $option_value ) {
			return (bool) $option_value;
		}

		return static::is_connected();
	}

	/**
	 * Check if a currency is supported by Monnify.
	 *
	 * @param string $currency_code
	 * @return bool
	 */
	public static function is_currency_supported( $currency_code ) {
		return isset( static::$supported_currencies[ $currency_code ] );
	}

	/**
	 * Filter to add any admin notices that might be needed.
	 *
	 * @return void
	 */
	public function filter_admin_notices() {
		$selected_currency = tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_currency_code );
		if ( $this->is_enabled() && ! $this->is_currency_supported( $selected_currency ) ) {
			?>
			<div class="notice notice-error">
				<?php echo wp_kses_post( $this->render_unsupported_currency_notice() ); ?>
			</div>
			<?php
		}
	}

	/**
	 * HTML for notice for unsupported currencies.
	 *
	 * @return string
	 */
	public function render_unsupported_currency_notice() {
		$notice_header = esc_html__( 'Monnify doesn\'t support your selected currency', 'monnify-for-events-calendar' );
		$notice_text   = esc_html__( 'Monnify only supports NGN (&#8358;) at this time. Please set your store currency to NGN.', 'monnify-for-events-calendar' );

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}

	/**
	 * Renders the Monnify checkout template.
	 *
	 * @param \Tribe__Template $template
	 * @return string
	 */
	public function render_checkout_template( \Tribe__Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "{$gateway_key}/checkout/container";

		return $template->template( $template_path, array() );
	}

	/**
	 * Gets a gateway option value for the current mode-appropriate key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get_option( $key = '' ) {
		if ( '' === $key ) {
			return false;
		}

		$options = get_option( 'tec_tickets_commerce_monnify_account' );
		if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
			return $options[ $key ];
		}

		return false;
	}
}
