/**
 * Path to this script in the global tribe Object.
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.monnify = tribe.tickets.commerce.gateway.monnify || {};

(function ( $, monnifyCheckout ) {
	"use strict";

	monnifyCheckout = {
		init: function () {
			if ( 0 < $( "#tec-tc-gateway-monnify-checkout-button" ).length ) {
				this.setVariables();
				this.watchSubmit();
				this.checkForReturn();

				tribe.tickets.debug.log( 'monnifyInit', this, tecTicketsMonnifyCheckout );
			}
		},
		setVariables: function () {
			this.errors = [];
			this.name = $( '#tec-tc-purchaser-name' );
			this.email_address = $( '#tec-tc-purchaser-email' );
			this.container = $( tribe.tickets.commerce.selectors.checkoutContainer );
		},
		getPurchaserData: function () {
			return tribe.tickets.commerce.getPurchaserData( this.container );
		},
		watchSubmit: function () {
			let $this = this;
			$( "#tec-tc-gateway-monnify-checkout-button" ).on( 'click', function ( event ) {
				event.preventDefault();
				$this.validateFields();
				$this.maybeCreateOrder();
			} );
		},
		validateFields: function () {
			let $this    = this;
			$this.errors = [];

			if ( '' === $this.name.val() ) {
				$this.errors.push( tecTicketsMonnifyCheckout.errorMessages.name );
			}
			if ( '' === $this.email_address.val() ) {
				$this.errors.push( tecTicketsMonnifyCheckout.errorMessages.email_address );
			}

			tribe.tickets.debug.log( 'monnifyValidate', $this.errors );
		},
		maybeCreateOrder: function () {
			if ( 0 < this.errors.length ) {
				console.log( this.errors );
			} else {
				this.createOrder();
			}
		},
		createOrder: function () {
			let $this = this;

			let bodyArgs = {
				purchaser: $this.getPurchaserData(),
				redirect_url: window.location.href,
			};

			return fetch(
				tecTicketsMonnifyCheckout.orderEndpoint,
				{
					method: 'POST',
					body: JSON.stringify( bodyArgs ),
					headers: {
						'Content-Type': 'application/json',
					},
				}
			)
				.then( response => response.json() )
				.then( data => {
					tribe.tickets.debug.log( 'monnifyCreateOrderResponse', data );
					if ( data.success && undefined !== data.redirect_url ) {
						window.location.href = data.redirect_url;
					} else {
						alert( data.message || tecTicketsMonnifyCheckout.errorMessages.createOrder );
					}
				} )
				.catch( () => {
					alert( tecTicketsMonnifyCheckout.errorMessages.createOrder );
				} );
		},

		/**
		 * Server-side verifies and completes the order, then follows the
		 * returned redirect_url (the TEC Success page) on success.
		 */
		verifyAndComplete: function ( reference ) {
			return fetch(
				tecTicketsMonnifyCheckout.orderEndpoint + '/' + reference,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( {} ),
				}
			)
				.then( response => response.json() )
				.then( data => {
					tribe.tickets.debug.log( 'monnifyVerifyResponse', data );
					if ( data.success && data.redirect_url ) {
						window.location.href = data.redirect_url;
					}
				} )
				.catch( () => {
					alert( tecTicketsMonnifyCheckout.errorMessages.connection );
				} );
		},

		/**
		 * Checks if we've just been redirected back from Monnify's checkout.
		 *
		 * Monnify appends "?paymentReference=..." to the redirectUrl using a
		 * literal "?" even when that URL already has a query string, which
		 * can produce a malformed "...?foo=bar?paymentReference=xyz" URL
		 * that breaks standard URLSearchParams parsing. Extract it
		 * defensively with a regex instead.
		 */
		checkForReturn: function () {
			let $this = this;

			let match     = window.location.search.match( /[?&]paymentReference=([^&?]+)/ );
			let reference = match ? decodeURIComponent( match[ 1 ] ) : null;

			tribe.tickets.debug.log( 'monnifyCheckForReturn', reference );

			if ( reference ) {
				$this.verifyAndComplete( reference );
			}
		},
	};

	tribe.tickets.commerce.gateway.monnify = monnifyCheckout;

	$( document ).ready( function ( $ ) {
		monnifyCheckout.init();
	} );

} )( jQuery, tribe.tickets.commerce.gateway.monnify );
