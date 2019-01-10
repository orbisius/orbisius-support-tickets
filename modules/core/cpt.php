<?php

$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
add_action('init', [ $cpt_obj, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_CPT extends Orbisius_Support_Tickets_Singleton {
	private $cpt_support_ticket = 'orb_support_ticket';

	public function init() {
		$this->registerOutput();
		$this->registerCustomContentTypes();
	}

	/**
	 * Registers the main CPT
	 */
	function registerCustomContentTypes() {
		$cpt_labels = array(// define the name of the custom post type
			'name' => _x('Ticket', 'custom post type general name'),
			'singular_name' => _x('Ticket', 'custom post type singular name'),
			'add_new' => _x('Add New', 'orbisius_support_tickets'),
			'add_new_item' => __('Add New Ticket', 'orbisius_support_tickets'),
			'edit_item' => __('Edit Ticket', 'orbisius_support_tickets'),
			'new_item' => __('New Ticket', 'orbisius_support_tickets'),
			'all_items' => __('All Tickets', 'orbisius_support_tickets'),
			'view_item' => __('View Ticket', 'orbisius_support_tickets'),
			'search_items' => __('Search Ticket', 'orbisius_support_tickets'),
			'not_found' => __('No Tickets Found', 'orbisius_support_tickets'),
			'not_found_in_trash' => __('The Ticket Could Not Be Found in Trash', 'orbisius_support_tickets'),
			'parent_item_colon' => '',
			'has_archive' => true,
			'hierarchical' => true,
			'menu_name' => __('Tickets', 'orbisius_support_tickets'),
			'menu_position' => null,
		);

		$cpt_labels = apply_filters('orbisius_support_tickets_filter_ticket_labels', $cpt_labels);

		// https://codex.wordpress.org/Function_Reference/register_post_type
		$cpt_args = array(
			'labels' => $cpt_labels,
			'public' => true, // true=show the post type in the admin section
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true, // generate a default admin user interface
			'show_in_menu' => true, // display as a top-level menu item
			'query_var' => true,
			'rewrite' => array('slug' => 'ticket'), // rewrite the url to make it pretty
			'menu_position' => 2, // show below Posts but above Media
			'supports' => array( 'title', 'editor', 'comments', 'author', ), // /*'revisions', */  'excerpt', 'custom-fields', 'thumbnail', 'post_formats', 'page-attributes'
			'has_archive' => true,
			'hierarchical' => true,
			//'taxonomies' => array('orb_support_tickets_cat', 'orb_support_tickets_tag'), // just use default categories and tags
			'show_in_nav_menus' => true, // makes this post type available for selection in navigation menus
			'menu_position' => 200,
		);

		$cpt_args = apply_filters('orbisius_support_tickets_filter_ticket_arg', $cpt_args);
		register_post_type($this->getCptSupportTicket(), $cpt_args);
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

		if ($stat) {
			return $stat;
		}

//		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
//		$data = $req_obj->getRaw('orbisius_support_tickets_data', []);
//
//		if (!empty($data['ticket_id'])) {
//			$stat = get_post_type($data['ticket_id']) == $this->getCptSupportTicket();
//		}

		return $stat;
	}

	public function registerOutput() {
		if ($this->isMyCpt()) {
			add_filter( 'the_content', [ $this, 'fixOutput' ], 9999 );
		}
	}

	/**
	 * Because the support text can include anything we'll escape things
	 * @param string $buff
	 * @return string
	 */
	public function fixOutput($buff) {
		$buff = esc_html($buff);
		$buff = "<pre id=\"orbisius_support_tickets_fmt_content' class='orbisius_support_tickets_fmt_content'>$buff</pre>";
		return $buff;
	}
}