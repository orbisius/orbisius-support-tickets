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
	//private $req_cap = 'manage_options'; // admin
	private $req_cap = 'edit_others_posts'; // editor

	public function init() {
	}

	public function performAdminInit() {
		add_action( 'admin_menu', array( $this, 'addMenuPages' ) );
		register_setting($this->plugin_settings_group_key, $this->plugin_settings_key, array($this, 'validateSettingsData'));

		$settings_key = $this->plugin_settings_key;
		$settings_group = $this->plugin_settings_group_key;

		$notif_settings_key = $settings_key . '_notification';
		$notif_settings_group = $settings_group . '_notification';
		register_setting($notif_settings_group, $notif_settings_key, array($this, 'validateNotificationSettingsData'));

		$ctx = [
			'settings_key' => $this->plugin_settings_key,
			'settings_group_key' => $this->plugin_settings_group_key,
        ];

		do_action('orbisius_support_tickets_admin_action_register_settings', $ctx);
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
	 * This is called by WP after the user hits the submit button.
	 * The variables are trimmed first and then passed to the who ever wantsto filter them.
	 * @param array the entered data from the settings page.
	 * @return array the modified input array
	 */
	function validateNotificationSettingsData($input) { // whitelist options
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

		if ($req_obj->has('orbisius_support_tickets_admin_create_pages')) {
			$create_pages_res = $this->createPages();

			$updates = 0;

			foreach ($this->plugin_default_opts as $key => $default_val) {
				if ( empty( $opts[$key] ) && $create_pages_res->data($key) > 0) {
					$opts[$key] = $create_pages_res->data($key);
					$updates++;
				}
            }

			if ($updates) {
				$opts = $this->setOptions($opts);
            }
		}

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
                                                                   readonly="readonly" value="<?php esc_attr_e('[orbisius_support_tickets_submit_ticket]');?>" />
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

			                                            $pages_dropdown = wp_dropdown_pages($args); // must be hierarchical
			                                            echo $pages_dropdown;
			                                            ?>
                                                        <br/><br/>
                                                        <div>
                                                            The page needs to contain this shortcode which lists user's tickets.
                                                            <input type="text" class="widefat orbisius_support_tickets_selectable orbisius_support_tickets_full_width"
                                                                   readonly="readonly" value="<?php esc_attr_e('[orbisius_support_tickets_list_tickets]');?>" />
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
                                                            <input type="text" class="widefat orbisius_support_tickets_selectable orbisius_support_tickets_full_width" readonly="readonly"
                                                                   value="<?php esc_attr_e('[orbisius_support_tickets_view_ticket]');?>" />
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


                            <?php
                            $notif_settings_key = $settings_key . '_notification';
                            $notif_settings_group = $settings_group . '_notification';
                            $notif_opts = $this->getOptions($notif_settings_key);
                            ?>
                            <div class="postbox">

                                <button type="button" class="handlediv" aria-expanded="true" >
                                    <span class="screen-reader-text">Toggle panel</span>
                                    <span class="toggle-indicator" aria-hidden="true"></span>
                                </button>
                                <!-- Toggle -->
                                <h2 class="hndle"><span><?php esc_attr_e( 'Notification Settings', 'orbisius_support_tickets' ); ?></span></h2>

                                <div class="inside">
                                    <p>
                                        <form method="post" action="options.php">
		                                    <?php settings_fields($notif_settings_group); ?>
                                            <table class="form-table">
                                                <tr valign="top">
                                                    <th scope="row">Ticket update subject</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[subject]";?>"
                                                               value="<?php empty($notif_opts['subject']) ? '' : esc_attr_e($notif_opts['subject']);?>" />
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Ticket update message</th>
                                                    <td>
                                                        <textarea class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[message]";?>" rows="8"><?php
                                                            empty($notif_opts['message']) ? '' : esc_attr_e($notif_opts['message']);?></textarea>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Enable notification</th>
                                                    <td>
                                                        <input type="checkbox" class=""
                                                               name="<?php echo "{$notif_settings_key}[enabled]";?>"
                                                               <?php checked(empty($notif_opts['enabled']) ? '' : $notif_opts['enabled'], 1); ?>
                                                               value="1" />
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
											'Extra Info', 'orbisius_support_tickets'
										); ?></span></h2>

                                <div class="inside">
                                    <p>
                                        The plugin can create the pages for you. If a page already exists it won't be created.
                                        <br/>
                                        <a href="<?php echo esc_url(add_query_arg('orbisius_support_tickets_admin_create_pages', '1')); ?>"
                                           class="button"
                                            >Create pages</a>
                                    </p>
                                    <hr/>
                                    <p>
                                        To make a suggestion submit a ticket at
                                        <a href="https://github.com/orbisius/orbisius-support-tickets/issues" target="_blank">github</a>
                                    </p>
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
	public function getOptions($key = '') {
		$opts = get_option(empty($key) ? $this->plugin_settings_key : $key);
		$opts = empty($opts) ? array() : (array) $opts;
		$opts = array_replace_recursive($this->plugin_default_opts, $opts);
		return $opts;
	}

	public function setOptions( array $opts ) {
		$opts = update_option($this->plugin_settings_key, $opts);
		return $opts;
	}

	/**
	 * @return Orbisius_Support_Tickets_Result
	 */
	public function createPages() {
		$res_obj = new Orbisius_Support_Tickets_Result();
	    $parent_page_title = 'Support';
	    $parent_page_slug = 'support';
		$create_page_defaults = array(
			'post_type'     => 'page',
			'page_title'  => '',
			'ping_status' => 'closed',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_content'  => '<p> </p>',
			'comment_status' => 'closed',
		);

	    $child_pages = [
            [
                'id' => 'list_tickets_page_id',
                'slug' => 'my-tickets',
                'page_title' => 'My Tickets',
                'post_content' => '<p>[orbisius_support_tickets_list_tickets]</p>',
            ],
            [
	            'id' => 'submit_ticket_page_id',
                'slug' => 'submit-ticket',
                'page_title' => 'Submit Ticket',
                'post_content' => '<p>[orbisius_support_tickets_submit_ticket]</p>',
            ],
            [
	            'id' => 'view_ticket_page_id',
                'slug' => 'view-ticket',
                'page_title' => 'View Ticket',
                'post_content' => '<p>[orbisius_support_tickets_view_ticket]</p>',
            ],
        ];

        $parent_page = get_page_by_path($parent_page_slug);

        if (empty($parent_page)) {
            $my_post = array_replace_recursive($create_page_defaults, array(
                'post_name'     => $parent_page_slug,
                'post_title'    => wp_strip_all_tags( $parent_page_title ),
                //'post_content'  => '<p> </p>',
            ));

            // Insert the post into the database
            $parent_page_id = wp_insert_post( $my_post ); // or error
        } else {
	        $parent_page_id = $parent_page->ID;
        }

		$res_obj->data($parent_page_slug, $parent_page_id);

	    foreach ($child_pages as $page_rec) {
		    $slug = $page_rec['slug'];
		    $page = get_page_by_path($slug);

		    if (empty($page)) {
			    $page = get_page_by_path("$parent_page_slug/$slug");
            }

		    if (empty($page)) {
			    $my_post = array_replace_recursive($create_page_defaults, array(
				    'post_name'     => $slug,
				    'post_title'    => wp_strip_all_tags( $page_rec['page_title'] ),
				    'post_content'  => $page_rec['post_content'],
				    'post_parent'   => $parent_page_id,
			    ));

			    $page_id = wp_insert_post( $my_post ); // or error
            } else {
			    $page_id = $page->ID;
            }

		    $res_obj->data($page_rec['id'], $page_id);
        }

		return $res_obj;
	}
}