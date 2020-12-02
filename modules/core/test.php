<?php

if ( ORBISIUS_SUPPORT_TICKETS_LIVE_ENV ) {
	return;
}

if (!isset($_REQUEST['orbisius_support_tickets_test_data'])) {
	return;
}

$test_api = Orbisius_Support_Tickets_Module_Core_Test::getInstance();
add_action('init', array( $test_api, 'init' ) ) ;

class Orbisius_Support_Tickets_Module_Core_Test {
	public function init() {
		$res = '';
		$test_data = $_REQUEST['orbisius_support_tickets_test_data'];

		// http://orbclub.com.clients.com/?orbisius_support_tickets_test_data[orbisius_support_tickets_action_submit_ticket_after_insert]=1
		if (!empty($test_data['orbisius_support_tickets_action_submit_ticket_after_insert'])) {
			$ctx['author_id'] = 13; //
			$ctx['ticket_id'] = 123;
			do_action( 'orbisius_support_tickets_action_submit_ticket_after_insert', $ctx );

			//echo 'orbisius_support_tickets_action_submit_ticket_after_insert';
		}

		// http://orbclub.com.clients.com/?orbisius_support_tickets_test_data[change_status]=1&orbisius_support_tickets_test_data[ticket_id]=489&orbisius_support_tickets_test_data[new_status]=publish
		if (!empty($test_data['change_status'])) {
			$ctx['ticket_id'] = $test_data['ticket_id'];
			$ctx['new_status'] = $test_data['new_status'];

			$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
			$res = $cpt_obj->changeStatus($ctx['ticket_id'], $ctx['new_status']);
		}

		echo "<pre>";
		var_dump($res);
		echo "</pre>";

		exit;
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
