<?php

if (!defined('ORBISIUS_SUPPORT_TICKETS_LIVE_ENV') || ORBISIUS_SUPPORT_TICKETS_LIVE_ENV) {
	return;
}

if (!isset($_REQUEST['orbisius_support_tickets_test_data'])) {
	return;
}

$test_api = Orbisius_Support_Tickets_Module_Core_Test::getInstance();
add_action('init', [ $test_api, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Test {
	public function init() {
		$test_data = $_REQUEST['orbisius_support_tickets_test_data'];

		// http://orbclub.com.clients.com/?orbisius_support_tickets_test_data[orbisius_support_tickets_action_before_submit_ticket_after_insert]=1
		if (!empty($test_data['orbisius_support_tickets_action_before_submit_ticket_after_insert'])) {
			$id = 123;
			$ctx['ticket_id'] = $id;
			do_action( 'orbisius_support_tickets_action_before_submit_ticket_after_insert', $ctx );

			//echo 'orbisius_support_tickets_action_before_submit_ticket_after_insert';
			exit;
		}
	}

	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar Orbisius_Support_Tickets_Module_Core_Test $instance
	 * @return Orbisius_Support_Tickets_Module_Core_Test
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
}