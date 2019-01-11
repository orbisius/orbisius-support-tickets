<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Assets::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Assets {
	public function init() {
		add_action('wp_enqueue_scripts', [ $this, 'enqueue' ]);
		add_action('admin_enqueue_scripts', [ $this, 'enqueue' ]);
	}

	public function enqueue() {
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$req_url = $req_obj->get_request_url();

		if (!is_admin()) {
			// @todo Should we check if it's any of our pages first before outputting this css?
			$file_rel = '/assets/css/orbisius-support-tickets.css';
			wp_enqueue_style(
				'orbisius-support-ticket',
				plugins_url( $file_rel, ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ),
				array(),
				filemtime( plugin_dir_path( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ) . $file_rel )
			);
		}

		// No assets if not out page
		if (!preg_match('#orbisius[\-\_]support[\-\_]ticket#si', $req_url, $matches)) {
			return;
		}

		$shared_libs = [
			'jquery-' => [],
			'jquery-ui-core' => [],
			'jquery-ui-autocomplete' => [],

			'chosen_js' => [
				'file_rel' => '/shared/chosen_v1.7.0/chosen.jquery.min.js',
//	            'ext_src' => '//cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.jquery.min.js',
				'ext_src' => '//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.6/chosen.jquery.min.js',
			],

			'chosen_css' => [
				'file_rel' => '/shared/chosen_v1.7.0/chosen.min.css',
				'ext_src' => '//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.6/chosen.min.css',
			],

			// https://clipboardjs.com/
			'clipboard_js' => [
				'file_rel' => '/shared/clipboard.js-2.0.1/dist/clipboard.min.js',

				// https://github.com/zenorocha/clipboard.js/wiki/CDN-Providers
				'ext_src' => '//cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js',
			],
		];

		// https://cdnjs.com/libraries/chosen
		$wp_scripts = wp_scripts();
		wp_enqueue_style(
			'jquery-ui-theme-smoothness',
			sprintf(
				'//ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/smoothness/jquery-ui.css', // working for https as well now
				$wp_scripts->registered['jquery-ui-core']->ver
			)
		);

		// We load the shared stuff first so we can use them in the main assets
		foreach ($shared_libs as $id => $rec) {
			if (empty($rec)) {
				wp_enqueue_script($id); // js script shipped with wp
				continue;
			} elseif (empty($rec['file_rel']) || !preg_match('#\.(css|js)#si', $rec['file_rel'], $matches)) {
				continue;
			}

			$handle = 'orbisius_support_tickets_asset_' . $id;
			$file_rel = $rec['file_rel'];
			$local_url = plugins_url( $file_rel, ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN );
			$local_file = plugin_dir_path( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ) . $file_rel;
			$ver = is_file($local_file) ? filemtime($local_file) : ''; // we'll use last modified as version so the browser know when to load the new file.

			if ($matches[1] == 'css') {
				wp_enqueue_style(
					$handle,
					$local_url,
					array(),
					$ver
				);
			} else {
				wp_enqueue_script(
					$handle,
					$local_url, array( 'jquery', ),
					$ver,
					true // in footer
				);
			}
		} // shared libs

		$file_rel = '/assets/js/orbisius-support-tickets.js';
		wp_enqueue_script(
			'orbisius-support-tickets',
			plugins_url( $file_rel, ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ), array( 'jquery', ),
			filemtime( plugin_dir_path( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ) . $file_rel ),
			true
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