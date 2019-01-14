<?php

$lang_obj = Orbisius_Support_Tickets_Module_Core_Lang::getInstance();
add_action('plugins_loaded', array( $lang_obj, 'loadTextDomain' ) );

class Orbisius_Support_Tickets_Module_Core_Lang {
	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar Orbisius_Support_Tickets_Module_Core_Lang $instance
	 * @return Orbisius_Support_Tickets_Module_Core_Lang
	 */
	public static function getInstance() {
		static $instance = null;

		// This will make the calling class to be instantiated.
		// no need each sub class to define this method.
		if (is_null($instance)) {
			$instance = new static();
		}

		return $instance;
	}

	function loadTextDomain() {
		load_plugin_textdomain( 'orbisius_support_tickets', false, dirname( plugin_basename(ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN) ) . '/lang/' );
	}
}

