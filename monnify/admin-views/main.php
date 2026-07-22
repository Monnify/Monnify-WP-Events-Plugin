<?php
/**
 * The Template for displaying the Tickets Commerce Monnify Settings.
 *
 * @var Tribe__Tickets__Admin__Views $this          [Global] Template object.
 * @var string                       $plugin_url    [Global] The plugin URL.
 * @var monnify\tec\classes\Merchant $merchant      [Global] The merchant class.
 * @var bool                         $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                         $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var string                       $webhook_url   [Global] The generated webhook URL.
 * @var array                        $fields        [Global] Field key => default value.
 * @var string                       $field_prefix  [Global] Field name prefix.
 * @var array                        $saved         [Global] Currently saved values, keyed like $fields.
 */

$classes = array(
	'tec-tickets__admin-settings-tickets-commerce-gateway',
	'tec-tickets__admin-settings-tickets-commerce-gateway--connected' => $is_merchant_connected,
);

$mode = $saved['monnify_mode'] ?: 'test';
?>

<div <?php tribe_classes( $classes ); ?>>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-logo">
		<img
			src="<?php echo esc_url( MNFY_TEC_URL . 'assets/images/icon.png' ); ?>"
			alt="<?php esc_attr_e( 'Monnify Logo Image', 'monnify-for-events-calendar' ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-logo-image"
			style="max-width: 200px;"
		>

		<ul>
			<li><?php esc_html_e( 'Card, bank transfer, and USSD payments', 'monnify-for-events-calendar' ); ?></li>
			<li><?php esc_html_e( 'Simple, fixed pricing across Nigerian payment methods', 'monnify-for-events-calendar' ); ?></li>
		</ul>
	</div>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Mode', 'monnify-for-events-calendar' ); ?></th>
			<td>
				<select name="<?php echo esc_attr( $field_prefix . 'monnify_mode' ); ?>">
					<option value="test" <?php selected( $mode, 'test' ); ?>><?php esc_html_e( 'Test Mode', 'monnify-for-events-calendar' ); ?></option>
					<option value="live" <?php selected( $mode, 'live' ); ?>><?php esc_html_e( 'Live Mode', 'monnify-for-events-calendar' ); ?></option>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Test API Key', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'api_key_test' ); ?>" value="<?php echo esc_attr( $saved['api_key_test'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Test Secret Key', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'secret_key_test' ); ?>" value="<?php echo esc_attr( $saved['secret_key_test'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Test Contract Code', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'contract_code_test' ); ?>" value="<?php echo esc_attr( $saved['contract_code_test'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Live API Key', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'api_key_live' ); ?>" value="<?php echo esc_attr( $saved['api_key_live'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Live Secret Key', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'secret_key_live' ); ?>" value="<?php echo esc_attr( $saved['secret_key_live'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Live Contract Code', 'monnify-for-events-calendar' ); ?></th>
			<td><input type="text" style="width: 100%; max-width: 340px;" name="<?php echo esc_attr( $field_prefix . 'contract_code_live' ); ?>" value="<?php echo esc_attr( $saved['contract_code_live'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Webhook URL', 'monnify-for-events-calendar' ); ?></th>
			<td>
				<input type="text" readonly onfocus="this.select();" style="width: 100%; max-width: 500px;" value="<?php echo esc_attr( $webhook_url ); ?>" />
				<p class="description"><?php esc_html_e( 'Paste this URL into the Webhook URL field of your Monnify dashboard.', 'monnify-for-events-calendar' ); ?></p>
			</td>
		</tr>
	</table>
</div>
