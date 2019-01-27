<?php

$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
add_action( 'init', array( $cpt_obj, 'init' ) );

register_activation_hook( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN, array( $cpt_obj, 'processPluginActivate' ) );
register_activation_hook( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN, array( $cpt_obj, 'processPluginDeactivate' ) );


class Orbisius_Support_Tickets_Module_Core_CPT extends Orbisius_Support_Tickets_Singleton {
	private $cpt_support_ticket = 'orb_support_ticket';

	/**
	 *
	 */
	public function init() {
		$this->registerOutput();
		$this->registerCustomContentTypes();
		$this->registerCommentAdd();
		$this->maybeEnableComments();

		add_action( 'orbisius_support_tickets_action_ticket_activity', array( $this, 'openClosedTicket' ) );
		add_filter( 'user_has_cap', array( $this, 'givePermissions' ), 50, 3 );
		add_filter( 'comment_on_draft', array( $this, 'addComment' ), 50, 1 );
	}

	// We want people to comment on draft (open) tickets).
	private $perms = array(
		'read_post',
	);

	/**
     * WP doesn't allow comments on drafts by default but provides a nice hook to we can actually insert a comment.
	 * @see http://fastwpdesign.co.uk/how-to-enable-comments-on-draft-posts-in-wordpress/
	 * @param int $post_id
	 */
	function addComment( $post_id ) {
	    // Not our post type.
		if ( get_post_type( $post_id ) != $this->getCptSupportTicket() ) {
			return;
		}

		// make sure the user is logged in
//		if ( ! is_user_logged_in() ) {
//			return;
//		}

		//  check we have a comment and if no comment then just return.
		if ( empty( $_POST['comment'] ) ) {
			return;
		}

        // remove all html and sanitize comment.
        $comment = sanitize_text_field( wp_strip_all_tags( $_POST['comment'] ) );

		// get the current user
		$current_user = wp_get_current_user();

		// set up the comment data
		$data = array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $current_user->user_login,
			'comment_author_email' => $current_user->user_email,
			'comment_author_url'   => '',
            'comment_content'       => $comment,
            'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $current_user->ID,
			//'comment_author_IP'    => '',
            //'comment_agent'        => '',
			'comment_date'         => date( "Y-m-d h:m:s" ),
			'comment_approved'     => 1,
		);

        // insert the comment and get the comment ID
		$comment_id = wp_insert_comment( $data );

		$permalink = wp_get_referer();

        // redirect back to the page that the comment was on.
		wp_redirect( $permalink );
		exit;
	}

	/**
	 * @param $all_caps
	 * @param $cap
	 * @param $args
	 *
	 * @return mixed
	 */
	public function givePermissions( $all_caps, $cap, $args ) {
		// Bail out if we're not asking about a post:
		if ( empty( $args[2] ) ) { // no post id
			return $all_caps;
		}

		if ( ! in_array( $args[0], $this->perms ) ) {
			return $all_caps;
		}

		$post_id = $args[2];

		// give author some permissions
		if ( get_post_type( $post_id ) == $this->getCptSupportTicket() ) {
			$all_caps[ $args[0] ] = true;
		}

		return $all_caps;
	}


	/**
	 * Registers the main CPT
	 */
	function registerCustomContentTypes() {
		$cpt_labels = array(// define the name of the custom post type
			'name'               => _x( 'Ticket', 'custom post type general name' ),
			'singular_name'      => _x( 'Ticket', 'custom post type singular name' ),
			'add_new'            => _x( 'Add New', 'orbisius_support_tickets' ),
			'add_new_item'       => __( 'Add New Ticket', 'orbisius_support_tickets' ),
			'edit_item'          => __( 'Edit Ticket', 'orbisius_support_tickets' ),
			'new_item'           => __( 'New Ticket', 'orbisius_support_tickets' ),
			'all_items'          => __( 'All Tickets', 'orbisius_support_tickets' ),
			'view_item'          => __( 'View Ticket', 'orbisius_support_tickets' ),
			'search_items'       => __( 'Search Ticket', 'orbisius_support_tickets' ),
			'not_found'          => __( 'No Tickets Found', 'orbisius_support_tickets' ),
			'not_found_in_trash' => __( 'The Ticket Could Not Be Found in Trash', 'orbisius_support_tickets' ),
			'parent_item_colon'  => '',
			'has_archive'        => true,
			'hierarchical'       => true,
			'menu_name'          => __( 'Tickets', 'orbisius_support_tickets' ),
			'menu_position'      => null,
		);

		$cpt_labels = apply_filters( 'orbisius_support_tickets_filter_ticket_labels', $cpt_labels );

		// https://codex.wordpress.org/Function_Reference/register_post_type
		$cpt_args = array(
			'labels'              => $cpt_labels,
			'public'              => true,
			// true=show the post type in the admin section
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			// generate a default admin user interface
			'show_in_menu'        => admin_url( 'admin.php?page=' . $this->getCptSupportTicket() ),
			// display as a submenu menu item
			'show_in_menu'        => false,
			// display as a top-level menu item
			//'show_in_menu' => admin_url('admin.php?page=' . $this->getCptSupportTicket()), // display as a submenu menu item
			//'show_in_menu' => admin_url('edit.php?page=' . $this->getCptSupportTicket()), // display as a submenu menu item
			'show_in_nav_menus'   => true,
			// makes this post type available for selection in navigation menus
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'orbisius_support_ticket' ),
			// rewrite the url to make it pretty
			'menu_position'       => 2,
			// show below Posts but above Media
			'supports'            => array( 'title', 'editor', 'comments', 'author', ),
			// /*'revisions', */  'excerpt', 'custom-fields', 'thumbnail', 'post_formats', 'page-attributes'
			'has_archive'         => true,
			'hierarchical'        => false,
			//'taxonomies' => array('orb_support_tickets_cat', 'orb_support_tickets_tag'), // just use default categories and tags
			'menu_position'       => 200,
			//'capability_type' => 'post',
		);

		$cpt_args = apply_filters( 'orbisius_support_tickets_filter_ticket_arg', $cpt_args );
		register_post_type( $this->getCptSupportTicket(), $cpt_args );
	}

	/**
	 * @return string
	 */
	public function getCptSupportTicket() {
		return $this->cpt_support_ticket;
	}

	/**
	 * @param string $cpt_support_ticket
	 */
	public function setCptSupportTicket( $cpt_support_ticket ) {
		$this->cpt_support_ticket = $cpt_support_ticket;
	}

	/**
	 * @return Orbisius_Support_Tickets_Module_Core_CPT
	 */
	public static function getInstance() {
		return parent::getInstance();
	}

	public function isMyCpt() {
		$stat = get_post_type() == $this->getCptSupportTicket();

		if ( $stat ) {
			return $stat;
		}

//		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
//		$data = $req_obj->getRaw('orbisius_support_tickets_data', array());
//
//		if (!empty($data['ticket_id'])) {
//			$stat = get_post_type($data['ticket_id']) == $this->getCptSupportTicket();
//		}

		return $stat;
	}

	public function registerOutput() {
		if ( $this->isMyCpt() ) {
			add_filter( 'the_content', array( $this, 'fixOutput' ), 9999 );
		}
	}

	/**
	 * Because the support text can include anything we'll escape things
	 *
	 * @param string $buff
	 *
	 * @return string
	 */
	public function fixOutput( $buff ) {
		$buff = esc_html( $buff );

		//$buff = "<pre id='orbisius_support_tickets_fmt_content' class='orbisius_support_tickets_fmt_content'>$buff</pre>";
		return $buff;
	}

	private $statuses = array(
		'draft'   => 'open',
		'private' => 'open',
		'publish' => 'closed',
	);

	/**
	 *
	 */
	public function getStatuses() {
		$statuses = apply_filters( 'orbisius_support_tickets_filter_ticket_statuses', $this->statuses );

		return $statuses;
	}

	const STATUS_OPEN = 'draft'; // private don't allow comments ?!?
	const STATUS_CLOSED = 'publish';

	/**
	 * @param $checkingStatus
	 *
	 * @return bool
	 */
	public function isStatus( $checkingStatus ) {
		return 0;
	}

	/**
	 * @param $item_obj
	 */
	public function getTicketStatus( $item_obj ) {
		$statuses = $this->getStatuses();

		return ! empty( $item_obj->post_status ) && ! empty( $statuses[ $item_obj->post_status ] ) ? $statuses[ $item_obj->post_status ] : '';
	}

	/**
	 * @param void
	 */
	public function processPluginActivate() {
		$this->registerCustomContentTypes();
		flush_rewrite_rules();
	}

	/**
	 * @param void
	 */
	public function processPluginDeactivate() {
		flush_rewrite_rules();
	}

	public function registerCommentAdd() {
		add_action( 'comment_post', array( $this, 'processCommentAdd' ), 20, 3 );
	}

	/**
	 * This hooks into comment adding which a ticket activity event regardless who did it.
	 *
	 * @param int $comment_ID
	 * @param int $comment_approved
	 * @param array $comment_data
	 */
	public function processCommentAdd( $comment_ID, $comment_approved, $comment_data ) {
		// Something is missing so we won't care. This is ticket it
		if ( empty( $comment_ID ) || empty( $comment_data['comment_post_ID'] ) ) {
			return;
		}

		// Not our ticket comment type
		if ( $this->getCptSupportTicket() != get_post_type( $comment_data['comment_post_ID'] ) ) {
			return;
		}

		//
		$ctx = array_replace_recursive( $comment_data, array(
			'reply_id'  => $comment_ID,
			'ticket_id' => $comment_data['comment_post_ID'],
			'author_id' => $comment_data['user_id'],
		) );

		$maybe_email = get_post_meta( $ctx['ticket_id'], '_orbsuptx_email', true );

		if ( ! empty( $maybe_email ) ) {
			$recipient_email        = $maybe_email;
			$ctx['recipient_email'] = $recipient_email;
		}

		do_action( 'orbisius_support_tickets_action_ticket_activity', $ctx );
	}

	/**
	 * @param array $user_filter
	 *
	 * @return array
	 */
	public function getItems( array $user_filter = array() ) {
		global $wpdb;
		$post_type = $this->getCptSupportTicket();

		$filter = array(
			'post_type'   => $post_type,
			'post_status' => array( 'publish', 'draft', 'private', 'trash' ),
		);

		$filter['offset']         = empty( $user_filter['offset'] ) ? 0 : absint( $user_filter['offset'] );
		$filter['posts_per_page'] = empty( $user_filter['limit'] ) ? 250 : absint( $user_filter['limit'] );

		if ( ! empty( $user_filter['q'] ) ) {
			$q = $user_filter['q'];
		} elseif ( ! empty( $user_filter['query'] ) ) {
			$q = $user_filter['query'];
		} elseif ( ! empty( $user_filter['search'] ) ) {
			$q = $user_filter['search'];
		}

		// @todo if this is done hook temporarily to filter out non requested cols
		if ( ! empty( $user_filter['fields'] ) ) {
			$filter['fields'] = $user_filter['fields'];
		}

		if ( ! empty( $user_filter['order'] ) ) {
			$filter['order'] = $user_filter['order'];
		}

		if ( ! empty( $user_filter['order_by'] ) ) {
			$filter['orderby'] = $user_filter['order_by'];
		}

		if ( ! empty( $user_filter['author'] ) ) {
			$filter['author'] = $user_filter['author'];
		}

		if ( ! empty( $q ) ) {
			$q                        = preg_replace( '#\*+#si', '%', $q );
			$filter['s']              = $wpdb->esc_like( $q );
			$filter['posts_per_page'] = 10;
		}

		$items = get_posts( $filter );
		$items = empty( $items ) ? array() : $items;

		return $items;
	}

	/**
	 * @param int $ticket_id
	 * @param string $new_status
	 */
	public function changeStatus( $ticket_id, $new_status ) {
		$status_wp_mapping = $this->getStatuses();
		$wp_post_status    = empty( $status_wp_mapping[ $new_status ] ) ? self::STATUS_CLOSED : $new_status;

		$up_parent_page_arr = array(
			'ID'          => $ticket_id,
			'post_status' => $wp_post_status,
		);

		$stat = wp_update_post( $up_parent_page_arr, true );

		if ( ! empty( $stat ) && is_numeric( $stat ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param int $ticket_id
	 */
	public function getTicket( $ticket_id ) {
		$post_obj = get_post( $ticket_id );

		return $post_obj;
	}

	/**
	 * @param int|WP_Post $ticket_obj
	 *
	 * @return string
	 */
	public function getStatus( $ticket_obj ) {
		if ( is_numeric( $ticket_obj ) ) {
			$ticket_obj = $this->getTicket( $ticket_obj );
		}

		return empty( $ticket_obj->post_status ) ? '' : $ticket_obj->post_status;
	}

	/**
	 * @param array $ctx
	 *
	 * @return void
	 */
	public function openClosedTicket( array $ctx ) {
		if ( empty( $ctx['ticket_id'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $ctx['ticket_id'] ) ) {
			return;
		}

		if ( $this->getStatus( $ctx['ticket_id'] ) == Orbisius_Support_Tickets_Module_Core_CPT::STATUS_CLOSED ) {
			$this->changeStatus( $ctx['ticket_id'], self::STATUS_OPEN );
		}
	}

	/**
	 * Hooked into 'comments_open' filter to ensure that our tickets will always have comments enabled as some people might have then deactivated globally.
	 */
	public function maybeEnableComments() {
		add_filter( 'comments_open', array( $this, 'enableCommentsForTickets' ), 999, 2 );
	}

	public function enableCommentsForTickets( $open, $post_id ) {
		if ( $this->getCptSupportTicket() == get_post_type( $post_id ) ) {
			$open = true;
		}

		return $open;
	}

	private $meta_prefix = '_orbsuptx_';

	public function getMeta( $post_id, $key ) {
		$key = $this->meta_prefix . $key;
		$val = get_post_meta( $post_id, $key, true );

		return $val;
	}

	public function setMeta( $post_id, $key, $val ) {
		$key = $this->meta_prefix . $key;
		$val = update_post_meta( $post_id, $key, $val );

		return $val;
	}

	/**
	 * @return string
	 */
	public function getMetaPrefix() {
		return $this->meta_prefix;
	}

	/**
	 * @param int|WP_Post $ticket_obj
	 *
	 * @return bool
	 */
	public function isPasswordRequired( $ticket_obj ) {
		$pwd = $this->getTicketPassword( $ticket_obj );
		// has access
		// has access password for this ticket
		return ! empty( $pwd );
	}

	/**
	 * @param $ticket_obj
	 *
	 * @return string
	 */
	public function getPasswordForm( $ticket_obj ) {
		ob_start();
		$attribs   = array();
		$msg       = '';
		$ctx       = array();
		$res_obj   = new Orbisius_Support_Tickets_Result();
		$show_form = 1;
		$ticket_id = $this->getTicketId( $ticket_obj );

		try {
			$req_obj   = Orbisius_Support_Tickets_Request::getInstance();
			$data      = $req_obj->getTicketData();
			$ticket_id = $req_obj->getTicketData( 'ticket_id' );

			if ( ! empty( $data['orbisius_support_tickets_submit_ticket_password_form_submit'] ) ) {
				if ( empty( $_POST['orbisius_support_tickets_submit_ticket_password_nonce'] )
				     || ! wp_verify_nonce( $_POST['orbisius_support_tickets_submit_ticket_password_nonce'], 'orbisius_support_tickets_submit_ticket' ) ) {
					$err_msg = __( "Invalid ticket password", 'orbisius_support_tickets' );
					$res_obj->msg( $err_msg );
					throw new Exception( $err_msg );
				}

				//$msg = Orbisius_Support_Tickets_Msg::success($msg);
			}
		} catch ( Exception $e ) {
			$msg = Orbisius_Support_Tickets_Msg::error( $res_obj->msg() );
		}

		$ctx['ticket_id'] = $ticket_id;
		?>

        <div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_submit_ticket_password_form', $ctx ); ?>

			<?php if ( ! isset( $attribs['render_title'] ) || $attribs['render_title'] ) : ?>
				<?php $title = empty( $attribs['title'] ) ? 'Enter Password' : esc_html( $attribs['render_title'] ); ?>
                <h3><?php _e( $title, 'orbisius_support_tickets' ); ?></h3>
			<?php endif; ?>

			<?php echo $msg; ?>

			<?php if ( $show_form && ( empty( $data['submit'] ) || $res_obj->isError() ) ) : ?>
                <div id="orbisius_support_tickets_submit_ticket_password_form_wrapper"
                     class="orbisius_support_tickets_submit_ticket_password_form_wrapper">
                    <form id="orbisius_support_tickets_submit_ticket_password_form"
                          class="orbisius_support_tickets_submit_ticket_password_form form-horizontal"
                          method="post" enctype="multipart/form-data">
						<?php do_action( 'orbisius_support_tickets_action_submit_ticket_password_form_header', $ctx ); ?>
						<?php wp_nonce_field( 'orbisius_support_tickets_submit_ticket_password', 'orbisius_support_tickets_submit_ticket_password_nonce' ); ?>
                        <input type="hidden" name="orbisius_support_tickets_data[submit]" value="1"/>
                        <input type="hidden" name="orbisius_support_tickets_data[ticket_id]"
                               id="orbisius_support_tickets_data_id"
                               value="<?php echo $ticket_id; ?>"/>

                        <div class="form-group">
                            <label class="col-md-3 control-label"
                                   for="orbisius_support_tickets_data_password">
								<?php _e( 'Password', 'orbisius_support_tickets' ); ?></label>
                            <div class="col-md-9">
                                <input name="orbisius_support_tickets_data[pass]"
                                       id="orbisius_support_tickets_data_password"
                                       type="password"
                                       placeholder="<?php _e( 'Password', 'orbisius_support_tickets' ); ?>"
                                       value="<?php esc_attr_e( $data['pass'] ); ?>"
                                       class="form-control orbisius_support_tickets_data_password"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12 text-right">
                                <button type="submit"
                                        id="orbisius_support_tickets_submit_ticket_password_form_submit"
                                        name="orbisius_support_tickets_submit_ticket_password_form_submit"
                                        class="orbisius_support_tickets_submit_ticket_password_form_submit btn btn-primary">
									<?php _e( 'Submit', 'orbisius_support_tickets' ); ?>
                                </button>
                            </div>
                        </div>

						<?php do_action( 'orbisius_support_tickets_action_submit_ticket_password_form_footer', $ctx ); ?>
                    </form>
                </div> <!-- /orbisius_support_tickets_submit_ticket_password_form_wrapper -->
				<?php do_action( 'orbisius_support_tickets_action_after_submit_ticket_password_form', $ctx ); ?>
			<?php endif; ?>
        </div> <!-- /orbisius_support_tickets_submit_ticket_wrapper -->
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	const USER_IP = 'user_ip';
	const PASSWORD = 'pass';

	public function getTicketPassword( $ticket_obj ) {
		return $this->getField( $ticket_obj, self::PASSWORD );
	}

	/**
	 * @param int|WP_Post $ticket
	 * @param string $field
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getField( $ticket, $field ) {
		$id = $this->getTicketId( $ticket );

		if ( $id == 0 ) {
			throw new Exception( "Invalid ticket data field format." );
		}

		return $this->getMeta( $id, $field );
	}

	/**
	 * @param int|WP_Post $ticket
	 *
	 * @return int
	 */
	public function getTicketId( $ticket ) {
		$id = 0;

		if ( is_object( $ticket ) ) {
			$id = $ticket->ID;
		} elseif ( is_numeric( $ticket ) ) {
			$id = $ticket;
		}

		$id = absint( $id );

		return $id;
	}
}