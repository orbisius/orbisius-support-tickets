<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;
add_action('init', [ $cpt, 'performAdminInit' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Admin {
	/**
	 * @var string
	 */
	private $req_cap = 'manage_options'; // admin

	public function init() {
	}

	public function performAdminInit() {
		add_action( 'admin_menu', array( $this, 'addMenuPages' ) );

	}

	/**
	 * Add Menu item on WP Backend
	 * @uses   addMenuPages
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function addMenuPages() {
		$ctx = [
			'top_menu_slug' => 'orbisius_support_tickets',
			'req_cap' => $this->req_cap,
		];

		$icon_url = '';

		$top_menu_page_hook = add_menu_page(
			__('Orbisius Support Tickets', 'orbisius_support_tickets'),
			__('Orbisius Support Tickets', 'orbisius_support_tickets'),
			$ctx['req_cap'],
			$ctx['top_menu_slug'],
			array( $this, 'renderPluginDashboardPage' ),
			$icon_url,
			2
		);

		$ctx['top_menu_page_hook'] = 'admin_print_scripts-' . $top_menu_page_hook;
		$ctx['top_menu_hook_suffix'] = $top_menu_page_hook;
//		add_action( $ctx['top_menu_page_hook'], array( $this, 'add_highlight_js' ) );

		// This way we have the top level menu leads to this and there's no duplication.
		add_submenu_page( $ctx['top_menu_slug'],
			__( 'Dashboard', 'orbisius_support_tickets'),
			__( 'Dashboard', 'orbisius_support_tickets'),
			$ctx['req_cap'],
			$ctx['top_menu_slug'],
			array( $this, 'renderPluginDashboardPage' )
		);

		do_action('orbisius_support_tickets_admin_action_setup_menu', $ctx);
	}

	/**
	 * Renders plugin's dashboard
	 *
	 * @uses
	 * @access public
	 * @since  1.
	 * @return void
	 */
	public function renderPluginDashboardPage() {
		?>
        <div class="wrap">
            <h1><?php esc_attr_e( 'Orbisius Support Tickets', 'orbisius_support_tickets' ); ?></h1>

            <div class="">
                <h2><?php esc_attr_e( '', 'orbisius_support_tickets' ); ?></h2>
                <h2 class="nav-tab-wrapper">
                    <a href="#" class="nav-tab nav-tab-active">Dashboard</a>
                    <a href="#" class="nav-tab ">Tickets</a>
                </h2>
            </div>
            <div>
                Show something useful here.
            </div>
        </div> <!-- .wrap -->
		<?php
	}

	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar Orbisius_Support_Tickets_Module_Core_Admin $instance
	 * @return Orbisius_Support_Tickets_Module_Core_Admin
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