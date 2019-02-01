<?php

$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
add_action( 'init', array( $cpt_obj, 'init' ) );

register_activation_hook( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN, array( $cpt_obj, 'processPluginActivate' ) );
register_activation_hook( ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN, array( $cpt_obj, 'processPluginDeactivate' ) );


class Orbisius_Support_Tickets_Module_Core_CPT extends Orbisius_Support_Tickets_Singleton {
	private $cpt_support_ticket = 'orb_support_ticket';
	private $cpt_support_ticket_reply = 'orb_sup_tx_reply';

	// Sync prefix if necessary.
	private $meta_prefix = '_orb_sup_tx_';
	const USER_IP = '_orb_sup_tx_user_ip';
	const USER_EMAIL = '_orb_sup_tx_user_email';

	/**
	 *
	 */
	public function init() {
		$this->registerOutput();
		$this->registerCustomContentTypes();
		$this->registerCommentAdd();
		$this->maybeEnableComments();
		$this->installExcludeTicketRepliesHook();

		add_action( 'orbisius_support_tickets_action_ticket_activity', array( $this, 'openClosedTicket' ) );
		add_filter( 'user_has_cap', array( $this, 'givePermissions' ), 50, 3 );
		add_filter( 'comment_on_draft', array( $this, 'addComment' ), 50, 1 );
		add_filter( 'comment_on_password_protected', array( $this, 'addComment' ), 50, 1 );

		add_filter( 'comment_form_defaults', array( $this, 'addReplyCommentTypeToForm' ), 50 );
		add_filter( 'comment_form_default_fields', array( $this, 'modifyCommentDefaultFields' ) );
		add_filter( 'preprocess_comment', array( $this, 'preProcessCommentData' ) );
		add_filter( 'comment_flood_filter', array( $this, 'maybeDeactivateFastRepliesCheck' ), 20, 3 );
		add_filter( 'notify_moderator', array( $this, 'deActivateModerationEmails' ), 20, 2 );
	}

	/**
     * WP sends emails when somebody comments on a pwd protected post
	 * @param bool $maybe_notify
	 * @param int $comment_id
	 */
    public function deActivateModerationEmails($maybe_notify, $comment_id) {
        $comment = get_comment($comment_id);

        // This comment is a ticket reply.
        if (!empty($comment->comment_post_ID) && $this->isTicketResource($comment->comment_post_ID)) {
	        return false;
        }

	    return $maybe_notify;
    }

	// We want people to comment on draft (open) tickets).
	private $perms = array(
		'read_post',
	);

	/**
     * @see https://wordpress.stackexchange.com/questions/227868/filter-out-comments-with-certain-meta-key-s-in-the-admin-backend
	 * @param $q
	 */
	function maybeDeactivateFastRepliesCheck($block, $time_last_comment, $time_new_comment) {
	    if ($this->isTicketResource()) {
		    return false;
        }

		return $block;
    }

	/**
     * @see https://wordpress.stackexchange.com/questions/227868/filter-out-comments-with-certain-meta-key-s-in-the-admin-backend
	 * @param $q
	 */
	function preProcessCommentData($comment_data) {
	    if (!$this->isTicketResource()) {
	        return $comment_data;
        }

		$comment_data['comment_type'] .= $this->getCptSupportTicketReplyType();
		$comment_data['comment_approved'] = 1;

		return $comment_data;
    }

    public function addReplyCommentTypeToForm($defaults) {
	    if (!$this->isTicketResource()) {
		    return $defaults;
	    }

	    $comment_type = $this->getCptSupportTicketReplyType();
	    $defaults['title_reply_after'] .= sprintf('<input type="hidden" name="comment_type" value="%s" id="comment_type" />', esc_attr($comment_type));
	    //$defaults['comment_field'] .= sprintf('<input type="hidden" name="comment_type" value="%s" id="comment_type" />', esc_attr($comment_type));
	    //$defaults['fields']['comment_type'] = '<input type="hidden" name="comment_type" value="%s" id="comment_type" />';

        return $defaults;
    }

	/**
     * Disable URL field in the comment form
	 * @param array $fields
	 * @return array $fields
	 */
	function modifyCommentDefaultFields($fields) {
		if (!$this->isTicketResource()) {
			return;
		}

		$comment_type = $this->getCptSupportTicketReplyType();
		$fields['comment_type'] = sprintf('<input type="hidden" name="comment_type" value="%s" id="comment_type" />', esc_attr($comment_type));

		unset($fields['url']);
		return $fields;
	}

	/**
     * WP doesn't allow comments on drafts by default but provides a nice hook to we can actually insert a comment.
	 * @see http://fastwpdesign.co.uk/how-to-enable-comments-on-draft-posts-in-wordpress/
	 * @param int $post_id
	 */
	function addComment( $post_id ) {
		// Not our post type.
		if ( get_post_type($post_id) != $this->getCptSupportTicket() ) {
			return;
		}

		//  check we have a comment and if no comment then just return.
		if ( empty( $_POST['comment'] ) ) {
			return;
		}

        // remove all html and sanitize comment.
        $comment = sanitize_text_field( wp_strip_all_tags( $_POST['comment'] ) );

		// get the current user
		$current_user = wp_get_current_user();

		if (empty($current_user->ID)) {
			$email = '';

			if (!empty($_REQUEST['email'])) {
				$email = sanitize_email($_REQUEST['email']);
				$email = empty($email) || ! is_email($email) ? '' : $email;
			}

			$current_user = new stdClass();
			$current_user->ID = 0;
			$current_user->user_email = $email;
			$current_user->user_login = '';
        }

		$user_api = Orbisius_Support_Tickets_User::getInstance();

		// set up the comment data
		$data = array(
			'comment_post_ID'      => $post_id,
			'comment_author_IP'    => $user_api->getUserIP(),
			'comment_author_url'   => '',
			'comment_author'       => $current_user->user_login,
			'comment_author_email' => $current_user->user_email,
			'user_id'              => $current_user->ID,
            'comment_content'      => $comment,
            'comment_type'         => $this->getCptSupportTicketReplyType(),
			'comment_parent'       => 0,
            'comment_agent'        => empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'],
			'comment_date'         => current_time('mysql'),
			'comment_approved'     => 1,
		);

        // insert the comment and get the comment ID
		$comment_id = wp_insert_comment( $data );

		// not sure why this is not called by WP on draft posts.
		// could this cause a loop?
		do_action( 'comment_post', $comment_id, $data['comment_approved'], $data );

		$permalink = wp_get_referer();

		if (empty($permalink)) {

		}

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

		// Not a ticket so take a break
		if ( ! $this->isTicketResource( $comment_data['comment_post_ID'] ) ) {
			return;
		}

		//
		$ctx = array_replace_recursive( $comment_data, array(
			'reply_id'  => $comment_ID,
			'ticket_id' => $comment_data['comment_post_ID'],
			'author_id' => $comment_data['user_id'],
			'author_email' => '',
		) );


		$author_email = '';

		if ( ! empty( $comment_data['comment_author_email'] ) ) {
			$author_email = sanitize_email($comment_data['comment_author_email']);
		} else {
			$maybe_email = $this->getMeta($comment_data['comment_post_ID'], Orbisius_Support_Tickets_Module_Core_CPT::USER_EMAIL);

			if ( ! empty( $maybe_email ) ) {
				$author_email = $maybe_email;
			}
		}

		$ctx['author_email'] = is_email($author_email) ? $author_email : '';

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
	 * @param int|WP_Post $ticket_id
	 */
	public function getTicket( $ticket ) {
		$post_obj = get_post( $ticket );
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
	 * @return void
	 */
	public function openClosedTicket( array $ctx ) {
		if ( empty( $ctx['ticket_id'] ) ) {
			return;
		}

		// author? check admin or author??? what about non-logged in user?
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
	 * @return bool
	 */
	public function isPasswordRequired( $ticket_obj ) {
		return post_password_required($ticket_obj);
	}

	/**
	 * @param $ticket_obj
	 * @return string
	 */
	public function getPasswordForm( $ticket_obj ) {
		return get_the_password_form($ticket_obj);
	}

	public function getTicketPassword( $ticket_obj ) {
	    $ticket_obj = $this->getTicket($ticket_obj);

	    if (!empty($ticket_obj->post_password)) {
		    return $ticket_obj->post_password;
        }

		return '';
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
	 * @return int
	 */
	public function getTicketId( $ticket = '' ) {
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$ticket_id = 0;

		if ( empty( $ticket ) || $ticket <= 0 ) {
			if ($req_obj->getTicketData('ticket_id')) {
				$ticket_id = $req_obj->getTicketData('ticket_id');
			} elseif ($req_obj->has('comment_post_ID')) {
				$ticket_id = $req_obj->get('comment_post_ID');
			} elseif ($req_obj->has('post_id')) {
				$ticket_id = $req_obj->get('post_id');
			}
		} elseif ( is_object( $ticket ) ) {
			$ticket_id = $ticket->ID;
		} elseif ( is_numeric( $ticket ) ) {
			$ticket_id = $ticket;
		}

		$ticket_id = absint( $ticket_id );

		return $ticket_id;
	}

	/**
	 * @return string
	 */
	public function getCptSupportTicketReplyType() {
		return $this->cpt_support_ticket_reply;
	}

	/**
	 * @param string $cpt_support_ticket_reply
	 */
	public function setCptSupportTicketReplyType( $cpt_support_ticket_reply ) {
		$this->cpt_support_ticket_reply = $cpt_support_ticket_reply;
	}

	/**
	 * @return bool
	 */
	public function isTicketResource( $inp_ticket_id = 0 ) {
		$ticket_id = $this->getTicketId($inp_ticket_id);

		// No ticket id so it's not our comment form
		if ( empty($ticket_id ) ) {
			return false;
		}

		// Not our post type. JIC.
		if ( get_post_type( $ticket_id ) != $this->getCptSupportTicket() ) {
			return false;
		}

		return true;
	}

	public function installExcludeTicketRepliesHook() {
		add_filter( 'pre_get_comments', array( $this, 'filterOutTicketReplies' ) );
	}

	/**
	 * Here we want to exclude ticket replies from the regular comments unless we specifically query them
     * Miight be useful: Better support for custom comment types https://core.trac.wordpress.org/ticket/12668
	 * @param $query_obj
	 */
	function filterOutTicketReplies($query_obj) {
	    if (is_admin()) { // admin can see everything
	        return;
        }

		$reply_type = $this->getCptSupportTicketReplyType();

		// If the type is set that means it's ours.
		if (!empty($query_obj->query_vars['type']) && $reply_type == $query_obj->query_vars['type']) { // already set and it's ours
			return;
		}

		// All other comment queries should exclude our reply type from the listing
		$query_obj->query_vars['type__not_in'] = $reply_type;
	}
}