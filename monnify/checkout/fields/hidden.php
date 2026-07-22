<?php
/**
 * Tickets Commerce: Checkout - Hidden Fields
 *
 * @var \TEC\Tickets\Commerce\Utils\Value $total_value [Global] The cart's total value.
 */
?>
<div class="tribe-tickets__commerce-checkout-monnify-form-field-wrapper hidden">
	<input
		type="hidden"
		id="tec-monnify-total"
		name="monnify-total"
		autocomplete="off"
		value="<?php echo esc_attr( $total_value->get_decimal() ); ?>"
	/>
</div>
