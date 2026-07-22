<?php
/**
 * Tickets Commerce: Monnify Checkout container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/monnify/container.php
 *
 * @var bool $must_login [Global] Whether login is required to buy tickets or not.
 */

if ( $must_login ) {
	return;
}
?>
<div class="tribe-tickets__commerce-checkout-gateway tribe-tickets__commerce-checkout-monnify">
	<div class="tec-tc-gateway-monnify-payment-selection">

		<div id="tec-tc-gateway-monnify-payment-element" class="tribe-tickets__commerce-checkout-monnify-payment-element">
			<br />
			<?php
			if ( is_user_logged_in() || $must_login || empty( $items ) ) {
				$this->template( 'monnify/checkout/fields/name' );
				$this->template( 'monnify/checkout/fields/email' );
			}
			$this->template( 'monnify/checkout/fields/hidden' );
			?>
		</div>

		<button id="tec-tc-gateway-monnify-checkout-button" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button">
			<div class="spinner hidden" id="spinner"></div>
			<span id="button-text">
				<?php
				printf(
					// Translators: %1$s: Plural `Tickets` label.
					esc_html__( 'Purchase %1$s', 'monnify-for-events-calendar' ),
					esc_html( tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) )
				);
				?>
			</span>
		</button>

		<div class="tec-tc-gateway-monnify-payment-logos">
			<img
				src="<?php echo esc_url( MNFY_TEC_URL . 'assets/images/payment-logos.png' ); ?>"
				alt="<?php echo esc_attr__( 'Supported payment methods.', 'monnify-for-events-calendar' ); ?>"
				style="max-height: 72px;margin: 30px auto 0;"
			/>
		</div>
	</div>

	<div id="tec-tc-gateway-monnify-payment-message" class="hidden"></div>

	<div
		id="tec-tc-gateway-payment-errors"
		class="tribe-common-b2"
		role="alert"></div>
</div>
