<?php
namespace monnify\tec\classes;

/**
 * This class loads the other classes and function files
 *
 * @package monnify-tec-integration
 */
class Core {

	/**
	 * Holds class instance
	 *
	 * @var object \monnify\tec\classes\Core()
	 */
	protected static $instance = null;

	/**
	 * @var object \monnify\tec\classes\Setup();
	 */
	public $setup;

	/**
	 * @var object \monnify\tec\classes\Provider();
	 */
	public $provider;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( class_exists( 'Tribe__Tickets__Main' ) ) {
			add_action( 'init', array( $this, 'load_classes' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object \monnify\tec\classes\Core()    A single instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads the variable classes and the static classes.
	 */
	public function load_classes() {
		if ( function_exists( 'tribe' ) ) {
			require_once( MNFY_TEC_PATH . '/classes/class-setup.php' );
			$this->setup = Setup::get_instance();

			require_once( MNFY_TEC_PATH . '/classes/class-provider.php' );
			$this->provider = new Provider( tribe() );
			$this->provider->register();
		}
	}
}
