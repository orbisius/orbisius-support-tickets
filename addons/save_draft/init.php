<?php

/**
 *
 */
$addon_obj = Orbisius_Support_Tickets_Addon_Save_Draft::getInstance();

add_action('init', array( $addon_obj, 'init' ) ) ;

class Orbisius_Support_Tickets_Addon_Save_Draft {
	public function init() {

	}

	/**
	 * Singleton
	 * @staticvar static $instance
	 * @return static
	 */
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new static();
		}

		return $instance;
	}
}
