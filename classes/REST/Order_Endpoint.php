<?php

namespace monnify\tec\classes\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;

use monnify\tec\classes\Gateway;
use monnify\tec\classes\Client;
use monnify\tec\classes\Merchant;

use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Order Endpoint.
 *
 * @package monnify\tec\classes\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @var string
	 */
	protected string $path = '/commerce/monnify/order';

	/**
	 * Register the actual endpoint on the WP REST API.
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_order_args(),
				'callback'            => array( $this, 'handle_create_order' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->update_order_args(),
				'callback'            => array( $this, 'handle_update_order' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => array(),
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Monnify gateway.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = array(
			'success' => false,
		);

		$data      = $request->get_json_params();
		$purchaser = tribe( Order::class )->get_purchaser_data( $data );

		if ( is_wp_error( $purchaser ) ) {
			return $purchaser;
		}

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		if ( ! $order ) {
			return new WP_Error(
				'tec-tc-gateway-monnify-error-creating-order',
				__( 'There was an error creating your order.', 'monnify-for-events-calendar' ),
				array()
			);
		}

		// Monnify rejects a reused payment reference outright, even for a
		// legitimate retry - and create_from_cart() upserts the same order
		// (same ID) for an unchanged cart, so the order ID alone isn't safe
		// to reuse as the reference across attempts. Add a random suffix
		// per attempt, and always overwrite gateway_order_id to the latest
		// one so return/webhook lookups match whichever attempt actually
		// went through.
		$reference     = $order->ID . '-' . wp_generate_password( 8, false, false );
		$update_values = array(
			'gateway_order_id' => $reference,
		);

		// Monnify's metaData field must be a flat key-value object, not an
		// array of {display_name, variable_name, type, value} objects (that
		// shape is a Paystack custom_fields convention, and Monnify's API
		// rejects it as malformed syntax).
		$metadata = array(
			'plugin'   => 'the-events-calendar',
			'order_id' => $order->ID,
		);

		// If we have a redirect_url, then initialize the transaction.
		if ( isset( $data['redirect_url'] ) ) {
			$client      = tribe( Client::class );
			$transaction = $client->initialize_transaction(
				array(
					'amount'      => $order->total_value->get_decimal(),
					'email'       => $order->purchaser['email'],
					'name'        => $order->purchaser['full_name'],
					'reference'   => $reference,
					'description' => sprintf(
						/* translators: %d: order ID */
						__( 'Order #%d', 'monnify-for-events-calendar' ),
						$order->ID
					),
					'currency'     => $order->currency,
					'redirect_url' => $data['redirect_url'],
					'metaData'     => $metadata,
				)
			);

			if ( true === $transaction['success'] ) {
				$response['redirect_url'] = $transaction['authorization_url'];
			} else {
				$response['message'] = $transaction['message'] ?? __( 'Unable to initialize the transaction.', 'monnify-for-events-calendar' );
				return new WP_REST_Response( $response );
			}
		}

		if ( Pending::SLUG === $order->status_slug ) {
			// Already pending from an earlier attempt on this same order
			// (e.g. the customer went back and retried an unpaid checkout).
			// modify_status() would reject this as a same-status transition,
			// and there isn't one to make - just point gateway_order_id at
			// this attempt's fresh reference directly.
			$updated = update_post_meta( $order->ID, Order::$gateway_order_id_meta_key, $reference );
		} else {
			$updated = tribe( Order::class )->modify_status(
				$order->ID,
				Pending::SLUG,
				$update_values
			);
		}

		if ( ! $updated ) {
			return new WP_Error(
				'tec-tc-gateway-monnify-error-updating-order',
				__( 'There was a problem updating your order.', 'monnify-for-events-calendar' ),
				array()
			);
		}

		$response['success'] = true;
		$response['id']      = $order->ID;

		return new WP_REST_Response( $response );
	}

	/**
	 * Handles the request that verifies and completes an order with Tickets Commerce and the Monnify gateway.
	 *
	 * The transaction status is always re-verified server-side against Monnify's
	 * API - a client-supplied status is never trusted on its own.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function handle_update_order( WP_REST_Request $request ) {
		$response = array(
			'success' => false,
		);

		$reference = $request->get_param( 'order_id' );

		// tec_tc_orders()->by_args() defaults to WP_Query's normal post_status
		// filtering, which silently excludes Tickets Commerce's custom order
		// statuses (tec-tc-pending, etc.) unless 'status' => 'any' is passed.
		// Use core's own lookup method, which already accounts for this.
		$order = tribe( Order::class )->get_from_gateway_order_id( $reference );

		if ( ! $order ) {
			return new WP_Error(
				'tec-tc-gateway-monnify-nonexistent-order-id',
				__( 'Order not found.', 'monnify-for-events-calendar' ),
				array()
			);
		}

		// Already resolved (e.g. by a webhook that beat us here) - nothing further to do.
		if ( in_array( $order->status_slug, array( Completed::SLUG, Denied::SLUG ), true ) ) {
			$response['success']      = true;
			$response['order_status'] = $order->status_slug;

			if ( Completed::SLUG === $order->status_slug ) {
				$response['redirect_url'] = add_query_arg( array( 'tc-order-id' => $order->gateway_order_id ), tribe( Success::class )->get_url() );
			}

			return new WP_REST_Response( $response );
		}

		$client      = tribe( Client::class );
		$transaction = $client->check_transaction( $reference );

		if ( true !== $transaction['success'] ) {
			$response['message'] = $transaction['message'] ?? __( 'Unable to verify the transaction.', 'monnify-for-events-calendar' );
			return new WP_REST_Response( $response );
		}

		if ( 'PAID' === $transaction['status'] ) {
			tribe( Order::class )->modify_status(
				$order->ID,
				Completed::SLUG,
				array(
					'gateway_order_id' => $reference,
				)
			);
			update_post_meta( $order->ID, 'gateway_transaction_id', $transaction['transaction_reference'] );

			tribe( Cart::class )->clear_cart();

			$response['success']      = true;
			$response['order_id']     = $order->ID;
			$response['redirect_url'] = add_query_arg( array( 'tc-order-id' => $reference ), tribe( Success::class )->get_url() );
		} elseif ( in_array( $transaction['status'], array( 'FAILED', 'EXPIRED' ), true ) ) {
			tribe( Order::class )->modify_status(
				$order->ID,
				Denied::SLUG,
				array(
					'gateway_order_id' => $reference,
				)
			);

			$response['success']      = true;
			$response['order_status'] = 'denied';
		} else {
			// PENDING, PARTIALLY_PAID, OVERPAID are left for manual admin
			// review rather than auto-completing or denying the order.
			$response['success']      = true;
			$response['order_status'] = 'pending';
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * Handles inbound Monnify webhook notifications, so that payments completed
	 * asynchronously (e.g. bank transfer, USSD) after the browser tab closes are
	 * still marked as paid.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		$response = array(
			'success' => false,
		);

		// only a POST with a Monnify signature header gets our attention.
		if ( ( strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'POST' ) || ! array_key_exists( 'HTTP_MONNIFY_SIGNATURE', $_SERVER ) ) {
			return new WP_Error( 'tec-tc-gateway-monnify-unauthorized-webhook', __( 'Unauthorized request.', 'monnify-for-events-calendar' ), array(), 401 );
		}

		$input  = @file_get_contents( 'php://input' );
		$secret = tribe( Merchant::class )->get_client_secret();

		if ( empty( $secret ) ) {
			return new WP_Error( 'tec-tc-gateway-monnify-unauthorized-webhook', __( 'Unauthorized request.', 'monnify-for-events-calendar' ), array(), 401 );
		}

		// Constant-time comparison to avoid a timing attack.
		if ( ! hash_equals( hash_hmac( 'sha512', $input, $secret ), $_SERVER['HTTP_MONNIFY_SIGNATURE'] ) ) {
			return new WP_Error( 'tec-tc-gateway-monnify-unauthorized-webhook', __( 'Unauthorized request.', 'monnify-for-events-calendar' ), array(), 401 );
		}

		http_response_code( 200 );

		$payload = json_decode( $input, true );

		if ( empty( $payload['eventType'] ) || empty( $payload['eventData'] ) ) {
			return new WP_REST_Response( $response );
		}

		if ( 'SUCCESSFUL_TRANSACTION' !== $payload['eventType'] ) {
			return new WP_REST_Response( $response );
		}

		$event_data        = $payload['eventData'];
		$payment_reference = isset( $event_data['paymentReference'] ) ? sanitize_text_field( $event_data['paymentReference'] ) : '';

		if ( '' === $payment_reference ) {
			return new WP_REST_Response( $response );
		}

		$order = tribe( Order::class )->get_from_gateway_order_id( $payment_reference );

		if ( ! $order ) {
			return new WP_REST_Response( $response );
		}

		$response['order_id'] = $order->ID;

		if ( Completed::SLUG !== $order->status_slug ) {
			tribe( Order::class )->modify_status(
				$order->ID,
				Completed::SLUG,
				array(
					'gateway_order_id' => $payment_reference,
				)
			);
			update_post_meta( $order->ID, 'gateway_transaction_id', $event_data['transactionReference'] ?? '' );
		}

		$response['success']      = true;
		$response['order_status'] = 'complete';

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for creating an order for Monnify.
	 *
	 * @return array
	 */
	public function create_order_args() {
		return array();
	}

	/**
	 * Arguments used for updating an order for Monnify.
	 *
	 * @return array
	 */
	public function update_order_args() {
		return array(
			'order_id' => array(
				'description'       => __( 'Order ID in Monnify', 'monnify-for-events-calendar' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The order ID argument must be a string.', array( 'status' => 400 ) );
					}

					return $value;
				},
				'sanitize_callback' => array( $this, 'sanitize_callback' ),
			),
		);
	}
}
