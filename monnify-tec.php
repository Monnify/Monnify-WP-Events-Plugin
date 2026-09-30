<?php
/*
 * Plugin Name:	Monnify for The Events Calendar
 * Plugin URI:	https://monnify.com/
 * Description:	Add-on for The Events Calendar that allows you to accept payments for event tickets via Monnify
 * Author:		Monnify
 * Version: 	1.0.0
 * Author URI: 	https://monnify.com/
 * License: 	GPLv3 or later
 * Text Domain: monnify-for-events-calendar
 * Domain Path: /languages/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'MNFY_TEC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MNFY_TEC_CORE', __FILE__ );
define( 'MNFY_TEC_URL', plugin_dir_url( __FILE__ ) );
define( 'MNFY_TEC_VER', '1.0.0' );

// Monnify API base URLs.
define( 'MNFY_TEC_LIVE_BASE_URL', 'https://api.monnify.com' );
define( 'MNFY_TEC_SANDBOX_BASE_URL', 'https://sandbox.monnify.com' );

/* ======================= Below is the Plugin Class init ========================= */
require_once MNFY_TEC_PATH . '/classes/class-core.php';

/**
 * Plugin kicks off with this function.
 *
 * @return void
 */
function mnfy_tec() {
	return \monnify\tec\classes\Core::get_instance();
}
mnfy_tec();
