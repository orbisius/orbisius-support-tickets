<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Assets::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Assets {
	public function init() {
		add_action('wp_enqueue_scripts', [ $this, 'enqueue' ]);
	}

	public function enqueue() {
		$assets = [

		];

		$file_rel = '/assets/css/orbisius-support-tickets.css';
		wp_enqueue_style(
			'orbisius-support-ticket',
			plugins_url( $file_rel, ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ),
			array(),
			filemtime( plugin_dir_path( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ) . $file_rel )
		);
	}

	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar self $instance
	 * @return self
	 */
	public static function getInstance() {
		static $instance = null;

		// This will make the calling class to be instantiated.
		// no need each sub class to define this method.
		if ( is_null( $instance ) ) {
			$instance = new static();
		}

		return $instance;
	}
}