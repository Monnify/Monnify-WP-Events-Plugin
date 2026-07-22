<?php
/**
 * Handles requests to and from Monnify.
 *
 * @package monnify\tec\classes
 */
namespace monnify\tec\classes;

/**
 * Client class, used to contact Monnify.
 *
 * @see https://developers.monnify.com/api
 */
class Client {

	/**
	 * Gets the current mode's credentials.
	 *
	 * @return array { api_key, secret_key, contract_code }
	 */
	protected function get_credentials() {
		$gateway = tribe( Gateway::class );
		$mode    = $gateway->get_option( 'monnify_mode' );
		if ( '' === $mode || false === $mode ) {
			$mode = 'test';
		}

		if ( 'test' === $mode ) {
			return array(
				'api_key'       => (string) $gateway->get_option( 'api_key_test' ),
				'secret_key'    => (string) $gateway->get_option( 'secret_key_test' ),
				'contract_code' => (string) $gateway->get_option( 'contract_code_test' ),
			);
		}

		return array(
			'api_key'       => (string) $gateway->get_option( 'api_key_live' ),
			'secret_key'    => (string) $gateway->get_option( 'secret_key_live' ),
			'contract_code' => (string) $gateway->get_option( 'contract_code_live' ),
		);
	}

	/**
	 * Gets the base URL for the current mode.
	 *
	 * @return string
	 */
	protected function get_base_url() {
		$gateway = tribe( Gateway::class );
		$mode    = $gateway->get_option( 'monnify_mode' );

		return 'live' === $mode ? MNFY_TEC_LIVE_BASE_URL : MNFY_TEC_SANDBOX_BASE_URL;
	}

	/**
	 * Gets a valid access token, requesting/caching a new one if needed.
	 *
	 * @param boolean $force_refresh
	 * @return string|false
	 */
	protected function get_access_token( $force_refresh = false ) {
		$credentials = $this->get_credentials();

		if ( '' === $credentials['api_key'] || '' === $credentials['secret_key'] ) {
			return false;
		}

		$gateway        = tribe( Gateway::class );
		$mode           = $gateway->get_option( 'monnify_mode' );
		$transient_key  = 'mnfy_tec_token_' . ( 'live' === $mode ? 'live' : 'test' );

		if ( ! $force_refresh ) {
			$cached = get_transient( $transient_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$auth = base64_encode( $credentials['api_key'] . ':' . $credentials['secret_key'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$response = wp_remote_post(
			$this->get_base_url() . '/api/v1/auth/login',
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $auth,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'MNFY_TEC_DEBUG login WP_Error: ' . $response->get_error_message() );
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			error_log( 'MNFY_TEC_DEBUG login non-200: ' . wp_remote_retrieve_response_code( $response ) . ' body=' . wp_remote_retrieve_body( $response ) );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->requestSuccessful ) || empty( $body->responseBody->accessToken ) ) {
			error_log( 'MNFY_TEC_DEBUG login bad body: ' . wp_remote_retrieve_body( $response ) );
			return false;
		}

		$expires_in = isset( $body->responseBody->expiresIn ) ? (int) $body->responseBody->expiresIn : 3600;
		set_transient( $transient_key, $body->responseBody->accessToken, max( 60, $expires_in - 60 ) );

		return $body->responseBody->accessToken;
	}

	/**
	 * Sends an authenticated request to the Monnify API, retrying once on a 401
	 * with a freshly refreshed access token.
	 *
	 * @param string     $method  GET|POST|PUT.
	 * @param string     $path    Path beginning with /api/..., may include a query string.
	 * @param array|null $body    Request body for POST/PUT requests.
	 * @param boolean    $retrying Internal flag used for the single retry.
	 * @return object|false Decoded JSON response, or false on failure.
	 */
	protected function request( $method, $path, $body = null, $retrying = false ) {
		$token = $this->get_access_token( $retrying );
		if ( false === $token ) {
			return false;
		}

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->get_base_url() . $path, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'MNFY_TEC_DEBUG request WP_Error: ' . $response->get_error_message() );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $code && ! $retrying ) {
			error_log( 'MNFY_TEC_DEBUG request got 401, retrying once with fresh token' );
			return $this->request( $method, $path, $body, true );
		}

		if ( $code < 200 || $code >= 300 ) {
			error_log( 'MNFY_TEC_DEBUG request non-2xx: code=' . $code . ' body=' . wp_remote_retrieve_body( $response ) );
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Initializes a hosted-checkout transaction.
	 *
	 * @param array $fields { amount, email, name, reference, description, currency, redirect_url, metaData }
	 * @return array { success, message?, authorization_url?, reference?, transaction_reference? }
	 */
	public function initialize_transaction( $fields = array() ) {
		$return = array(
			'success' => false,
		);

		$credentials = $this->get_credentials();

		if ( empty( $fields ) || '' === $credentials['contract_code'] ) {
			$return['message'] = __( 'Payment fields or Monnify credentials are empty.', 'monnify-for-events-calendar' );
			return $return;
		}

		$body = array(
			'contractCode'        => $credentials['contract_code'],
			'amount'              => (float) $fields['amount'],
			'customerEmail'       => $fields['email'],
			'customerName'        => $fields['name'] ?? $fields['email'],
			'paymentReference'    => $fields['reference'],
			'paymentDescription'  => $fields['description'] ?? '',
			'currencyCode'        => $fields['currency'],
			'redirectUrl'         => $fields['redirect_url'],
		);

		if ( ! empty( $fields['metaData'] ) ) {
			$body['metaData'] = $fields['metaData'];
		}

		$response = $this->request( 'POST', '/api/v1/merchant/transactions/init-transaction', $body );

		if ( false === $response || empty( $response->requestSuccessful ) || empty( $response->responseBody->checkoutUrl ) ) {
			$return['message'] = __( 'There was an error while initializing the transaction.', 'monnify-for-events-calendar' );
			return $return;
		}

		$return['success']                = true;
		$return['authorization_url']       = $response->responseBody->checkoutUrl;
		$return['reference']               = $response->responseBody->paymentReference;
		$return['transaction_reference']   = $response->responseBody->transactionReference;

		return $return;
	}

	/**
	 * Queries Monnify for the status of a transaction, keyed by OUR OWN
	 * previously generated payment reference (never a client-supplied value).
	 *
	 * @param string $reference
	 * @return array { success, message?, status?, transaction_reference?, amount_paid? }
	 */
	public function check_transaction( $reference = '' ) {
		$return = array(
			'success' => false,
		);

		if ( '' === $reference ) {
			$return['message'] = __( 'The reference field is empty.', 'monnify-for-events-calendar' );
			return $return;
		}

		$response = $this->request( 'GET', '/api/v2/merchant/transactions/query?paymentReference=' . rawurlencode( $reference ) );

		if ( false === $response || empty( $response->requestSuccessful ) || empty( $response->responseBody ) ) {
			$return['message'] = __( 'There was an error while checking the transaction.', 'monnify-for-events-calendar' );
			return $return;
		}

		$body = $response->responseBody;

		$return['success']               = true;
		$return['status']                = $body->paymentStatus ?? '';
		$return['transaction_reference'] = $body->transactionReference ?? '';
		$return['amount_paid']           = $body->amountPaid ?? 0;

		return $return;
	}
}
