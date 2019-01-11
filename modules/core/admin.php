<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;
add_action('init', [ $cpt, 'performAdminInit' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Admin {
    private $plugin_settings_group_key = 'orbisius_support_tickets';
    private $plugin_settings_key = 'orbisius_support_tickets';

	/**
	 * @var string
	 */
	private $req_cap = 'manage_options'; // admin

	public function init() {
	}

	public function performAdminInit() {
		add_action( 'admin_menu', array( $this, 'addMenuPages' ) );
		register_setting($this->plugin_settings_group_key, $this->plugin_settings_key, array($this, 'validateSettingsData'));
	}

	/**
	 * This is called by WP after the user hits the submit button.
	 * The variables are trimmed first and then passed to the who ever wantsto filter them.
	 * @param array the entered data from the settings page.
	 * @return array the modified input array
	 */
	function validateSettingsData($input) { // whitelist options
		$ctx = [];
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$input = $req_obj->trim($input);

		// let extensions do their thing
		$input_filtered = apply_filters('orbisius_support_tickets_filter_admin_filter_settings', $input, $ctx);

		// did an extension break data?
		$input = is_array($input_filtered) ? $input_filtered : $input;

		return $input;
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

		// This way we have the top level menu leads to this and there's no duplication.
		add_submenu_page( $ctx['top_menu_slug'],
			__( 'Settings', 'orbisius_support_tickets'),
			__( 'Settings', 'orbisius_support_tickets'),
			$ctx['req_cap'],
			$ctx['top_menu_slug'] . '_settings',
			array( $this, 'renderPluginSettingsPage' )
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
            <h1><?php esc_attr_e( 'Orbisius Support Tickets', 'orbisius_support_tickets' ); ?> > Dashboard</h1>

            <div>
                Show something useful here.
            </div>
        </div> <!-- .wrap -->
		<?php
	}

	/**
	 * Renders tops level entity tabs and also renders the tab's content
	 */
	public function renderPluginSettingsPage() {
		$req_obj        = Orbisius_Support_Tickets_Request::getInstance();
		$settings_key = $this->plugin_settings_key;
		$settings_group = $this->plugin_settings_group_key;

		$opts = $this->getOptions();

		?>
        <h2><?php esc_attr_e( 'Orbisius Support Tickets', 'orbisius_support_tickets' ); ?></h2>

        <div class="wrap">

            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( 'Settings', 'orbisius_support_tickets' ); ?></h1>

            <div id="poststuff">

                <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">

                        <div class="meta-box-sortables ui-sortable">

                            <div class="postbox">

                                <button type="button" class="handlediv" aria-expanded="true" >
                                    <span class="screen-reader-text">Toggle panel</span>
                                    <span class="toggle-indicator" aria-hidden="true"></span>
                                </button>
                                <!-- Toggle -->
                                <h2 class="hndle"><span><?php esc_attr_e( 'General Settings', 'orbisius_support_tickets' ); ?></span></h2>

                                <div class="inside">
                                    <p>
                                        <form method="post" action="options.php">
		                                    <?php settings_fields($settings_group); ?>
                                            <table class="form-table">
                                                <tr valign="top">
                                                    <th scope="row">Submit Ticket Page</th>
                                                    <td>
                                                        <?php
                                                            $args = array(
                                                                'name' => "{$settings_key}[submit_ticket_page_id]",
                                                                'id' => 'orbisius_support_tickets_data_submit_ticket_page_id',
                                                                'depth'            => 0,
                                                                'child_of'         => 0,
                                                                'echo'             => 0,
                                                                'show_option_none'      => '== Select Page ==', // string
                                                                'option_none_value'     => null, // string
                                                            );

                                                            if ( ! empty( $opts['submit_ticket_page_id'] ) ) {
                                                                $args['selected'] = $opts['submit_ticket_page_id'];
                                                            }

                                                            $pages_dropdown = wp_dropdown_pages($args); // must be hierachical
                                                            echo $pages_dropdown;
                                                        ?>
                                                        <br/><br/>
                                                        <div>
                                                            The page needs to contain this shortcode which shows ticket submission form:
                                                            <input type="text" class="widefat orbisius_support_tickets_selectable"
                                                                   readonly="readonly" value="<?php esc_attr_e('[orbisius_support_submit_ticket]');?>" />
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr valign="top">
                                                    <th scope="row">My Tickets Page</th>
                                                    <td>
			                                            <?php
			                                            $args = array(
				                                            'name' => "{$settings_key}[list_tickets_page_id]",
				                                            'id' => 'orbisius_support_tickets_data_list_tickets_page_id',
				                                            'depth'            => 0,
				                                            'child_of'         => 0,
				                                            'echo'             => 0,
				                                            'show_option_none'      => '== Select Page ==', // string
				                                            'option_none_value'     => null, // string
			                                            );

			                                            if ( ! empty( $opts['list_tickets_page_id'] ) ) {
				                                            $args['selected'] = $opts['list_tickets_page_id'];
			                                            }

			                                            $pages_dropdown = wp_dropdown_pages($args); // must be hierachical
			                                            echo $pages_dropdown;
			                                            ?>
                                                        <br/><br/>
                                                        <div>
                                                            The page needs to contain this shortcode which lists user's tickets.
                                                            <input type="text" class="widefat orbisius_support_tickets_selectable"
                                                                   readonly="readonly" value="<?php esc_attr_e('[orbisius_support_list_tickets]');?>" />
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">View Single Ticket Page</th>
                                                    <td>
			                                            <?php
			                                            $args = array(
				                                            'name' => "{$settings_key}[view_ticket_page_id]",
				                                            'id' => 'orbisius_support_tickets_data_view_ticket_page_id',
				                                            'depth'            => 0,
				                                            'child_of'         => 0,
				                                            'echo'             => 0,
				                                            'show_option_none'      => '== Select Page ==', // string
				                                            'option_none_value'     => null, // string
			                                            );

			                                            if ( ! empty( $opts['view_ticket_page_id'] ) ) {
				                                            $args['selected'] = $opts['view_ticket_page_id'];
			                                            }

			                                            $pages_dropdown = wp_dropdown_pages($args); // must be hierachical
			                                            echo $pages_dropdown;
			                                            ?>
                                                        <br/><br/>
                                                        <div>
                                                            The page needs to contain this shortcode which lists a single ticket's data.
                                                            <input type="text" class="widefat orbisius_support_tickets_selectable" readonly="readonly"
                                                                   value="<?php esc_attr_e('[orbisius_support_view_ticket]');?>" />
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <p class="submit">
                                                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                                            </p>
                                         </form>
                                    </p>
                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->

                    </div>
                    <!-- post-body-content -->

                    <!-- sidebar -->
                    <div id="postbox-container-1" class="postbox-container">

                        <div class="meta-box-sortables">

                            <div class="postbox">

                                <button type="button" class="handlediv" aria-expanded="true" >
                                    <span class="screen-reader-text">Toggle panel</span>
                                    <span class="toggle-indicator" aria-hidden="true"></span>
                                </button>
                                <!-- Toggle -->

                                <h2 class="hndle"><span><?php esc_attr_e(
											'Sidebar Content Header', 'orbisius_support_tickets'
										); ?></span></h2>

                                <div class="inside">
                                    <p><?php esc_attr_e( 'Everything you see here, from the documentation to the code itself, was created by and for the community. WordPress is an Open Source project, which means there are hundreds of people all over the world working on it. (More than most commercial platforms.) It also means you are free to use it for anything from your cat’s home page to a Fortune 500 web site without paying anyone a license fee and a number of other important freedoms.',
											'orbisius_support_tickets' ); ?></p>
                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables -->

                    </div>
                    <!-- #postbox-container-1 .postbox-container -->

                </div>
                <!-- #post-body .metabox-holder .columns-2 -->

                <br class="clear">
            </div>
            <!-- #poststuff -->

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

	private $plugin_default_opts = array(
		'view_ticket_page_id' => '',
		'list_tickets_page_id' => '',
		'submit_ticket_page_id' => '',
    );

	/**
	 * @return array
	 */
	public function getOptions() {
		$opts = get_option($this->plugin_settings_key);
		$opts = empty($opts) ? array() : (array) $opts;
		$opts = array_replace_recursive($this->plugin_default_opts, $opts);
		return $opts;
	}
}