<?php

/**
 *
 */
$addon_obj = Orbisius_Support_Tickets_Addon_Save_Draft::getInstance();

add_action( 'init', array( $addon_obj, 'init' ) );

class Orbisius_Support_Tickets_Addon_Save_Draft {
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	/**
	 * Singleton
	 * @staticvar static $instance
	 * @return static
	 */
	public static function getInstance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Enqueue the javascript file
	 */
	public function enqueue_script() {
		wp_enqueue_script( 'orbisius-support-ticket-save-draft', plugins_url( '/js/save-draft.js', __FILE__ ), array( 'jquery' ), null, true );
	}
}
