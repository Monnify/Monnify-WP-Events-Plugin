=== Monnify for The Events Calendar ===
Contributors: monnify
Tags: the events calendar, event tickets, tickets commerce, monnify, payments
Requires at least: 6.6
Tested up to: 7.0.2
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept payments for event tickets via Monnify (card, bank transfer, and USSD) with The Events Calendar's Tickets Commerce.

== Description ==

Monnify for The Events Calendar adds Monnify as a Tickets Commerce payment gateway. Ticket buyers are redirected to Monnify's hosted checkout to complete their card, bank transfer, or USSD payment, then returned to your site once payment is verified.

> This plugin requires the Event Tickets add-on (with Tickets Commerce enabled) to be active. It cannot work without Event Tickets and The Events Calendar.

= Plugin Features =

* Hosted-checkout (redirect) payment flow - no card data ever touches your server
* Test/Live mode with separate API credentials for each
* Server-side transaction verification on return - order status is never trusted from the redirect alone
* Webhook listener for asynchronous bank-transfer/USSD payment confirmation
* NGN currency support registered with both Tickets Commerce currency systems

== Installation ==

1. Make sure The Events Calendar and Event Tickets are installed and activated, with Tickets Commerce enabled.
2. Upload the plugin files to the `/wp-content/plugins/monnify-for-events-calendar` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Tickets > Settings > Payments > Monnify and enter your Monnify API Key, Secret Key, and Contract Code (for Test and/or Live mode).
5. If testing locally without HTTPS, also enable "Enable Test Mode" under Tickets > Settings > Payments > General - Tickets Commerce forces HTTPS REST endpoint URLs otherwise, which will fail on a plain-HTTP site.
6. Copy the generated Webhook URL into your Monnify dashboard's webhook settings.
7. Enable the Monnify gateway toggle on its settings tab once credentials are saved.

== Changelog ==

= 1.0.0 =
* Initial release.
