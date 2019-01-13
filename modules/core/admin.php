<?php

$admin_api = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();

add_action('init', [ $admin_api, 'init' ] ) ;
add_action('init', [ $admin_api, 'performAdminInit' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Admin {
    private $plugin_settings_group_key = 'orbisius_support_tickets';
    private $plugin_settings_key = 'orbisius_support_tickets';

	private $plugin_settings_notification_key = 'orbisius_support_tickets_notification';
	private $plugin_settings_notification_group_key = 'orbisius_support_tickets_notification';

	/**
	 * @var string
	 */
	//private $req_cap = 'manage_options'; // admin
	private $req_cap = 'edit_others_posts'; // editor

	private $replace_vars = [
		'domain' => 'The current domain',
		'site_url' => 'Site URL',
		'site_name' => 'Your site name (from WP settings)',
		'ticket_id' => 'The ticket id',
		'ticket_url' => 'The view ticket link',
		'recipient_email' => 'Who is going to receive the email (ticket creator usually)',
		//'recipient_name' => 'Who is going to receive',
    ];

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
			'top_menu_slug' => $this->plugin_settings_key,
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

		$ctx['settings_slug'] = $ctx['top_menu_slug'] . '_settings';

		// This way we have the top level menu leads to this and there's no duplication.
		add_submenu_page( $ctx['top_menu_slug'],
			__( 'Settings', 'orbisius_support_tickets'),
			__( 'Settings', 'orbisius_support_tickets'),
			$ctx['req_cap'],
			$ctx['settings_slug'],
			array( $this, 'renderPluginSettingsPage' )
		);

		do_action('orbisius_support_tickets_admin_action_setup_menu', $ctx);

		add_filter( 'plugin_action_links', [ $this, 'addQuickLinksIoPluginListing' ], 10, 2 );
	}

	/**
	 * Adds the action link to settings when listing Plugins
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	function addQuickLinksIoPluginListing($links, $file) {
		if ($file == plugin_basename(ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN)) {
		    $old_links = $links;
			$links = [];

			// Add the links in the order we want
			$link = admin_url('admin.php?page=' . urlencode($this->plugin_settings_key . '_settings'));
			$link = "<a href='{$link}'>Settings</a>";
			$links[] = $link;

			$link = 'https://github.com/orbisius/orbisius-support-tickets/issues';
			$link = "<a href='{$link}' target='_blank' title='Opens in a new window'>Report a bug</a>";
			$links[] = $link;

			$link = 'https://orbisius.com/products/wordpress-plugins/orbisius-support-tickets';
			$link = "<a href='{$link}' target='_blank' title='Opens in a new window'>Product Page</a>";
			$links[] = $link;

			$links = array_merge($links, $old_links);
		}

		return $links;
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
                                                                'class' => 'orbisius_support_tickets_dropdown_field',
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
				                                            'class' => 'orbisius_support_tickets_dropdown_field',
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
				                                            'class' => 'orbisius_support_tickets_dropdown_field',
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
                                                    <th scope="row" colspan="2">Admin</th>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">From Name</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[support_from_name]";?>"
                                                               value="<?php esc_attr_e( empty($notif_opts['support_from_name'])
				                                                   ? ''
				                                                   : $notif_opts['support_from_name']);?>" />
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">From Email</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[support_from_email]";?>"
                                                               value="<?php esc_attr_e( empty($notif_opts['support_from_email'])
                                                                   ? ''
                                                                   : $notif_opts['support_from_email']);?>" />
                                                        <div>
                                                            Example: <br/>
                                                            <img src="<?php echo ORBISIUS_SUPPORT_TICKETS_BASE_URL; ?>/assets/help/images/email_from_stuff.png" alt="Shows what is email from name and email" />
                                                        </div>
                                                    </td>

                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Reply-to Email (optional)</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[support_email_reply_to]";?>"
                                                               value="<?php esc_attr_e( empty($notif_opts['support_email_reply_to'])
                                                                   ? ''
                                                                   : $notif_opts['support_email_reply_to']);?>" />
                                                        <div>
                                                            You can leave this blank. If your customers hit the reply button the email will used as recipient to you.
                                                            <br/>
                                                            Example:
                                                            &lt;awesome.support@example.com&gt; OR
                                                            MyCompany Awesome Support &lt;awesome.support@example.com&gt;
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr valign="top">
                                                    <th scope="row">Support Notification Recipient</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[support_email_receiver]";?>"
                                                               value="<?php esc_attr_e( empty($notif_opts['support_email_receiver'])
                                                                   ? get_option('admin_email')
                                                                   : $notif_opts['support_email_receiver']);?>" />
                                                        <div>
                                                            Who from your team should be notified when new ticket is created, updated, closed.
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr valign="top">
                                                    <th scope="row" colspan="2">New ticket
                                                        <hr/>
                                                    </th>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Subject</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[new_ticket_subject]";?>"
                                                               value="<?php empty($notif_opts['new_ticket_subject']) ? '' : esc_attr_e($notif_opts['new_ticket_subject']);?>" />
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Message</th>
                                                    <td>
                                                        <textarea class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[new_ticket_message]";?>" rows="8"><?php
                                                            empty($notif_opts['new_ticket_message']) ? '' : esc_attr_e($notif_opts['new_ticket_message']);?></textarea>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Enable/Disable</th>
                                                    <td>
                                                        <input type="hidden" name="<?php echo "{$notif_settings_key}[new_ticket_notification_enabled]";?>" value="0" />
                                                        <label>
                                                            <input type="checkbox" class=""
                                                               name="<?php echo "{$notif_settings_key}[new_ticket_notification_enabled]";?>"
                                                               <?php checked(empty($notif_opts['new_ticket_notification_enabled'])
                                                                    ? '' : $notif_opts['new_ticket_notification_enabled'], 1); ?>
                                                               value="1" />
                                                            Enable notification
                                                        </label>
                                                    </td>
                                                </tr>

                                                <tr valign="top">
                                                    <th scope="row" colspan="2">Ticket activity<hr/></th>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Subject</th>
                                                    <td>
                                                        <input type="text" class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[ticket_activity_subject]";?>"
                                                               value="<?php empty($notif_opts['ticket_activity_subject']) ? '' : esc_attr_e($notif_opts['ticket_activity_subject']);?>" />
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Message</th>
                                                    <td>
                                                        <textarea class="widefat"
                                                               name="<?php echo "{$notif_settings_key}[ticket_activity_message]";?>" rows="8"><?php
                                                            empty($notif_opts['ticket_activity_message']) ? '' : esc_attr_e($notif_opts['ticket_activity_message']);?></textarea>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th scope="row">Enable/Disable</th>
                                                    <td>
                                                        <input type="hidden" name="<?php echo "{$notif_settings_key}[ticket_activity_notification_enabled]";?>" value="0" />
                                                        <label>
                                                            <input type="checkbox" class=""
                                                               name="<?php echo "{$notif_settings_key}[ticket_activity_notification_enabled]";?>"
                                                               <?php checked(empty($notif_opts['ticket_activity_notification_enabled'])
                                                                    ? '' : $notif_opts['ticket_activity_notification_enabled'], 1); ?>
                                                               value="1" />
                                                            Enable notification
                                                        </label>
                                                    </td>
                                                </tr>
                                            </table>

                                            <p class="submit">
                                                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                                            </p>
                                         </form>


                                        <div>
                                            <h3>Supported merge tags (variables) in the email: </h3>
                                            <div>
                                                You can use these tags in subject, message, from email, from name, reply-to.
                                            </div>
                                            <ul>
                                                <?php foreach ($this->getReplaceVars() as $key => $val) : ?>
                                                    <li>
                                                        <?php echo '{' . $key . "} -> " . $val; ?>
                                                    </li>
                                                <?php endforeach;?>
                                            </ul>
                                        </div>

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
											'Extra Stuff', 'orbisius_support_tickets'
										); ?></span></h2>

                                <div class="inside">
                                    <p>
                                    <h4>Support Pages Creation Tool</h4>
                                    Click the buttom below and the plugin will create the pages with the shortcodes for you and set the options.
                                    If a page already exists it won't be created (but support page maybe updated to include links to submit ticket & my tickets).
                                    <br/>
                                    <a href="<?php echo esc_url(add_query_arg('orbisius_support_tickets_admin_create_pages', '1')); ?>"
                                       class="button"
                                    ><?php esc_attr_e(
				                            'Create pages', 'orbisius_support_tickets'
			                            ); ?>
                                    </a>
                                    </p>
                                </div>
                                <!-- .inside -->
                                <hr/>
                                <div class="inside">
                                    <p>
                                        <h4>Custom WordPress Plugin Development</h4>
                                        Do you need a custom plugin developed specifically for your needs?
                                        <br/>
                                        <a href="//orbisius.com/free-quote/?utm_source=orbisius_support_tickets"
                                           class="button"
                                            ><?php esc_attr_e(
		                                        'Contact us', 'orbisius_support_tickets'
	                                        ); ?>
                                        </a>
                                    </p>
                                    <hr/>
                                    <p>
                                        <h4>Want to help?</h4>
                                        If you want to help, make a suggestion or found a glitch
                                    <a class="button" href="https://github.com/orbisius/orbisius-support-tickets/issues" target="_blank">Submit a ticket</a>
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

	private $plugin_default_opts_other = array(
        'orbisius_support_tickets_notification' => [
            'support_from_name' => "{site_name} Mailer",
            'support_from_email' => 'mailer@{domain}',
            'support_email_reply_to' => 'support@{domain}',
            'support_email_receiver' => 'support@{domain}',

            // New ticket stuff
            'new_ticket_subject' => '[#{ticket_id}] New ticket has been created',
            'new_ticket_message' => "We've received your message and will get back to you within next 24h.

Ticket ID: {ticket_id}
Ticket link: {ticket_url}
",
            'new_ticket_notification_enabled' => 1,

            // Ticket activity
            'ticket_activity_subject' => '[#{ticket_id}] The ticket has been updated',
            'ticket_activity_message' => "The ticket has been updated. Please, visit the ticket page to view the update.

Ticket ID: {ticket_id}
Ticket link: {ticket_url}
",
            'ticket_activity_notification_enabled' => 1,
        ],
    );

	/**
	 * @return array
	 */
	public function getOptions($key = '') {
		$opts = get_option(empty($key) ? $this->plugin_settings_key : $key);
		$opts = empty($opts) ? array() : (array) $opts;

		if (!empty($key)) {
		    // There are defaults for a section
		    if (!empty($this->plugin_default_opts_other[$key])) {
			    $opts = array_replace_recursive( $this->plugin_default_opts_other[ $key ], $opts );
		    } else {
		        // Let's search for a partial match
		        $keys = preg_grep('#' . preg_quote($key) . '#si', array_keys($this->plugin_default_opts_other));

		        if (count($keys) == 1) {
			        $found_key = array_shift($keys);
			        $opts = array_replace_recursive( $this->plugin_default_opts_other[ $found_key ], $opts );
		        }
            }
        } else {
			$opts = array_replace_recursive( $this->plugin_default_opts, $opts );
		}

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

	    $parent_page_rec = [
            'id' => 'support_page_id',
            'slug' => $parent_page_slug,
            'page_title' => __('Support', 'orbisius_support_tickets'),
            'post_content' => "\n<div><a href='[orbisius_support_tickets_generate_page_link page=submit_ticket esc=1]'>Submit Ticket</a></div>
<div><a href='[orbisius_support_tickets_generate_page_link page=list_tickets esc=1]'>My Tickets</a></div>\n",
        ];

	    $child_pages = [
            [
                'id' => 'list_tickets_page_id',
                'slug' => 'my-tickets',
                'page_title' => 'My Tickets',
                'post_content' => '<div>[orbisius_support_tickets_list_tickets]</div>',
            ],
            [
	            'id' => 'submit_ticket_page_id',
                'slug' => 'submit-ticket',
                'page_title' => 'Submit Ticket',
                'post_content' => '<div>[orbisius_support_tickets_submit_ticket]</div>',
            ],
            [
	            'id' => 'view_ticket_page_id',
                'slug' => 'view-ticket',
                'page_title' => 'View Ticket',
                'post_content' => '<div>[orbisius_support_tickets_view_ticket]</div>',
            ],
        ];

        $parent_page = get_page_by_path($parent_page_slug);
		$parent_page_created = 0;

        if (empty($parent_page->ID)) {
            $my_post = array_replace_recursive($create_page_defaults, array(
                'post_name'     => $parent_page_slug,
                'post_title'    => $parent_page_rec['page_title'],
                'post_content'  => $parent_page_rec['post_content'],
            ));

            // Insert the post into the database
            $parent_page_id = wp_insert_post( $my_post ); // or error
            $parent_page_created = 1;
        } else {
	        $parent_page_id = $parent_page->ID;

	        // The support page exists but doesn't have the links to the relevant pages so we will add links to them.
	        if ((stripos($parent_page->post_content, '[orbisius_support_tickets_generate_page_link') === false)
                    && !preg_match('#Submit[\-\_]*Ticket#si', $parent_page->post_content)
                    && !preg_match('#(list|my)[\-\_]*Ticket#si', $parent_page->post_content)
                ) {
	            $up_parent_page_arr = [
                    'ID' => $parent_page->ID,
                    'post_content' => $parent_page->post_content . "<br/>" . $parent_page_rec['post_content'],
                ];

		        $stat = wp_update_post($up_parent_page_arr, true);
            }
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

	    $res_obj->data('parent_page_created', $parent_page_created);

		return $res_obj;
	}

	/**
	 * @return string
	 */
	public function getPluginSettingsNotificationKey() {
		return $this->plugin_settings_notification_key;
	}

	/**
	 * @return array
	 */
	public function getReplaceVars() {
		return $this->replace_vars;
	}

	/**
	 * @param array $replace_vars
	 */
	public function setReplaceVars( $replace_vars ) {
		$this->replace_vars = $replace_vars;
	}
}