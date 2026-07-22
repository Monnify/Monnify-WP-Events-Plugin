<?php
namespace monnify\tec\classes;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;

/**
 * Class Merchant.
 *
 * @package monnify\tec\classes
 */
class Merchant extends Abstract_Merchant {

	/**
	 * All account props we use for the merchant.
	 *
	 * @var string[]
	 */
	protected $account_props = array(
		'monnify_mode',
		'api_key_test',
		'secret_key_test',
		'contract_code_test',
		'api_key_live',
		'secret_key_live',
		'contract_code_live',
	);

	/**
	 * Determines if the data needs to be saved to the Database.
	 *
	 * @var boolean
	 */
	protected $needs_save = false;

	protected $monnify_mode;
	protected $api_key_test;
	protected $secret_key_test;
	protected $contract_code_test;
	protected $api_key_live;
	protected $secret_key_live;
	protected $contract_code_live;

	/**
	 * The nonce action used for disconnecting this merchant.
	 *
	 * @var string
	 */
	protected string $disconnect_action = 'mnfy_tec_disconnect';

	/**
	 * Constructor - Initialize the merchant with saved data.
	 */
	public function __construct() {
		$saved_data = $this->get_details_data();
		$this->setup_properties( $saved_data, false );
	}

	/**
	 * Fetches the current value for a given prop.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_prop( $key ) {
		return $this->$key;
	}

	/**
	 * Sets the value for a prop locally, in this instance of the Merchant.
	 *
	 * @param string  $key
	 * @param mixed   $value
	 * @param boolean $needs_save
	 */
	public function set_prop( $key, $value, $needs_save = true ) {
		$this->set_value( $key, $value, $needs_save );
	}

	/**
	 * Determines if this instance needs to be saved to the DB.
	 *
	 * @return bool
	 */
	public function needs_save() {
		return tribe_is_truthy( $this->needs_save );
	}

	/**
	 * Return array of merchant details.
	 *
	 * @return array
	 */
	public function to_array() {
		$to_array = array();
		foreach ( $this->account_props as $key ) {
			$to_array[ $key ] = $this->get_prop( $key );
		}
		return $to_array;
	}

	/**
	 * Returns the options key for the account.
	 *
	 * @return string
	 */
	public function get_account_key() {
		$gateway_key = Gateway::get_key();
		return "tec_tickets_commerce_{$gateway_key}_account";
	}

	/**
	 * Saves a given base value into the class props.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $needs_save
	 */
	protected function set_value( $key, $value, $needs_save = true ) {
		$this->{$key} = $value;

		if ( $needs_save ) {
			$this->needs_save = true;
		}
	}

	/**
	 * Setup properties from array.
	 *
	 * @param array   $data
	 * @param boolean $needs_save
	 */
	protected function setup_properties( array $data, $needs_save = true ) {
		foreach ( $this->account_props as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->set_prop( $key, $data[ $key ], $needs_save );
			} else {
				$this->set_prop( $key, '', $needs_save );
			}
		}
	}

	/**
	 * Validate merchant details.
	 *
	 * @param array $merchant_details
	 */
	public function validate( $merchant_details ) {
		$required = $this->account_props;

		if ( array_diff( $required, array_keys( $merchant_details ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the merchant details data.
	 *
	 * @return array
	 */
	protected function get_details_data() {
		return (array) get_option( $this->get_account_key(), array() );
	}

	/**
	 * Delete merchant account details from the Database.
	 *
	 * @return bool
	 */
	public function delete_data() {
		$status = update_option( $this->get_account_key(), null );

		if ( $status ) {
			$data = array_fill_keys( $this->account_props, null );
			$this->setup_properties( $data, false );
		}

		return $status;
	}

	/**
	 * Save merchant data to database.
	 *
	 * @return bool
	 */
	public function save() {
		if ( ! $this->needs_save() ) {
			return true;
		}

		$result = update_option( $this->get_account_key(), $this->to_array() );

		if ( $result ) {
			$this->needs_save = false;
		}

		return $result;
	}

	/**
	 * Disconnects the merchant completely.
	 *
	 * @return bool
	 */
	public function disconnect() {
		return $this->delete_data();
	}

	/**
	 * Determines if the Merchant is connected (i.e. credentials are entered
	 * for the currently selected mode).
	 *
	 * @param bool $recheck
	 * @return bool
	 */
	public function is_connected( $recheck = false ) {
		if ( 'live' === $this->monnify_mode ) {
			return '' !== $this->api_key_live && '' !== $this->secret_key_live && '' !== $this->contract_code_live;
		}

		return '' !== $this->api_key_test && '' !== $this->secret_key_test && '' !== $this->contract_code_test;
	}

	/**
	 * Determines if the Merchant is active.
	 *
	 * @param bool $recheck
	 * @return bool
	 */
	public function is_active( $recheck = false ) {
		if ( ! $this->is_connected() ) {
			return false;
		}

		return tribe( Gateway::class )->is_enabled();
	}

	/**
	 * Gets the client secret for the merchant, for the currently selected mode.
	 *
	 * @return ?string
	 */
	public function get_client_secret(): ?string {
		if ( 'live' === $this->monnify_mode ) {
			return $this->secret_key_live ?: null;
		}

		return $this->secret_key_test ?: null;
	}
}
