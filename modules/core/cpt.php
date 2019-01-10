<?php

$cpt = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_CPT extends Orbisius_Support_Tickets_Singleton {
	private $cpt_support_ticket = 'orb_support_ticket';

	public function init() {
		$this->registerCustomContentTypes();
	}

	/**
	 *
	 */
	function registerCustomContentTypes() {
		$cpt_labels = array(// define the name of the custom post type
			'name' => _x('Ticket', 'custom post type general name'),
			'singular_name' => _x('Ticket', 'custom post type singular name'),
			'add_new' => _x('Add New', 'orbisius_support_tickets'),
			'add_new_item' => __('Add New Ticket', 'orbisius_support_tickets'),
			'edit_item' => __('Edit Ticket', 'orbisius_support_tickets'),
			'new_item' => __('New Ticket'),
			'all_items' => __('All Tickets'),
			'view_item' => __('View Ticket'),
			'search_items' => __('Search Ticket'),
			'not_found' => __('No Tickets Found'),
			'not_found_in_trash' => __('The Ticket Could Not Be Found in Trash'),
			'parent_item_colon' => '',
			'has_archive' => true,
			'hierarchical' => true,
			'menu_name' => 'Tickets',
			'menu_position' => null,
		);

		$cpt_labels = apply_filters('orbisius_support_tickets_filter_ticket_labels', $cpt_labels);

		$cpt_args = array(
			'labels' => $cpt_labels,
			'public' => true, // true=show the post type in the admin section
			'publicly_queryable' => true,
			'show_ui' => true, // generate a default admin user interface
			'show_in_menu' => true, // display as a top-level menu item
			'query_var' => true,
			'rewrite' => array('slug' => 'ticket'), // rewrite the url to make it pretty
			'menu_position' => 5, // show below Posts but above Media
			'supports' => array('title', 'editor', 'comments', /*'revisions', */ 'author',), //  'excerpt', 'custom-fields', 'thumbnail', 'post_formats', 'page-attributes'
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


}