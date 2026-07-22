<?php
namespace monnify\tec\classes;

/**
 * Setup Class
 *
 * @package monnify-tec-integration
 */
class Setup {

	/**
	 * Holds class instance
	 *
	 * @var object \monnify\tec\classes\Setup()
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'tribe_template_path_list', array( $this, 'filter_template_path_list' ), 15, 2 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object \monnify\tec\classes\Setup()    A single instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Filters the list of folders TEC will look up to find templates to add the ones defined by Tickets.
	 *
	 * @param array           $folders  The current list of folders that will be searched template files.
	 * @param \Tribe__Template $template Which template instance we are dealing with.
	 *
	 * @return array The filtered list of folders that will be searched for the templates.
	 */
	public function filter_template_path_list( array $folders, \Tribe__Template $template ) {
		$path = (array) rtrim( MNFY_TEC_PATH, '/' );

		$folders['event-tickets-monnify'] = array(
			'id'        => 'event-tickets-monnify',
			'namespace' => 'monnify\tec',
			'priority'  => 20,
			'path'      => implode( DIRECTORY_SEPARATOR, $path ),
		);

		return $folders;
	}
}
