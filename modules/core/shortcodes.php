<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Shortcodes::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Shortcodes {
	private $cpt_support_ticket = 'orb_support_tickets_item';

	public function init() {
		$this->registerCodes();
	}

	/**
	 *
	 */
	function registerCodes() {
		add_shortcode( 'orbisius_support_list_tickets', [ $this, 'renderTickets' ] );
		add_shortcode( 'orbisius_support_view_ticket', [ $this, 'renderViewTicket' ] );
		add_shortcode( 'orbisius_support_submit_ticket', [ $this, 'renderSubmitTicketForm' ] );
	}

	private $defaults = [
		'id' => 0,
		'subject' => '',
		'message' => '',
	];

	/**
	 * Processes
	 * @return Orbisius_Support_Tickets_Result
	 */
	public function processTicketSubmission($data = []) {
		try {
			$user_id = get_current_user_id();
			$res     = new Orbisius_Support_Tickets_Result();

			$cpt_api       = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
			$post_type     = $cpt_api->getCptSupportTicket();
			$ins_post_data = [
				'post_type'   => $post_type,
				'post_author' => $user_id,
				'post_status' => 'private', // 'publish';
			];

			$raw_post_data = empty($data) ? $this->getData() : $data;

			// This is required
			if ( empty( $raw_post_data['subject'] ) ) {
				throw new Exception( "Empty subject" );
			}

			// ... but still let's define a default
			$ins_post_data['post_title'] = empty( $raw_post_data['subject'] )
				? 'Untitled ' . current_time( 'mysql' )
				: $raw_post_data['subject'];

			$ins_post_data['post_content'] = empty( $raw_post_data['message'] )
				? ''
				: $raw_post_data['message'];

			if ( ! empty( $raw_post_data['id'] ) ) {
				// @todo check if the user is allowed to update this ticket
				if ( ! current_user_can( 'manage_options' ) ) {
					throw new Exception( "Cannot edit." );
				}

				// admin OR the author???... only admin
				$id                  = (int) $raw_post_data['id'];
				$ins_post_data['ID'] = $id;
			} else {
//			    if ($user_api->get_user_posts($post_type, $user_id) >= $this->max_snippets) {
//				    throw new Exception("You have reached your limits. Please, upgrade");
//			    }
			}

			$ctx = [
				'data' => $ins_post_data,
			];

			do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_upsert', $ctx );

			if ( empty( $ins_post_data['ID'] ) ) {
				do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_insert', $ctx );
				$id = wp_insert_post( $ins_post_data );
			} else {
				do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_update', $ctx );
				wp_update_post( $ins_post_data );
			}

			if ( ! is_numeric( $id ) || $id <= 0 ) {
				throw new Exception( "Couldn't save item." );
			}

			$res->data( 'id', $id );
			$res->status( 1 );
		} catch ( Exception $e ) {
			$res->msg( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Processes [orbisius_support_list_tickets] shortcode
	 * @return string
	 */
	public function renderTickets( $attribs = [] ) {
		ob_start();

		$id  = 0;
		$msg = '';
		global $wpdb;

		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$post_type = $cpt_api->getCptSupportTicket();

		$list_params = [
			'post_type'   => $post_type,
			'post_status' => array( 'publish', 'draft', 'private', 'trash' ),
		];

		$orb_cloud_lib_data            = [];
		$list_params['offset']         = empty( $orb_cloud_lib_data['offset'] ) ? 0 : int( $orb_cloud_lib_data['offset'] );
		$list_params['author']         = get_current_user_id();
		$list_params['posts_per_page'] = empty( $orb_cloud_lib_data['limit'] ) ? 250 : int( $orb_cloud_lib_data['limit'] );

		if ( ! empty( $orb_cloud_lib_data['q'] ) ) {
			$q = $orb_cloud_lib_data['q'];
		} elseif ( ! empty( $orb_cloud_lib_data['query'] ) ) {
			$q = $orb_cloud_lib_data['query'];
		} elseif ( ! empty( $orb_cloud_lib_data['search'] ) ) {
			$q = $orb_cloud_lib_data['search'];
		}

		if ( ! empty( $q ) ) {
			$q                             = preg_replace( '#\*+#si', '%', $q );
			$list_params['s']              = $wpdb->esc_like( $q );
			$list_params['posts_per_page'] = 10;
		}

		$ctx   = [];
		$items = get_posts( $list_params );
		?>
        <div id="orbisius_support_tickets_list_ticket_wrapper" class="orbisius_support_tickets_list_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_submit_ticket_form', $ctx ); ?>

			<?php if ( empty( $items ) ) : ?>
                <div class="orbisius_support_tickets_list_ticket_msg">
                    <?php echo Orbisius_Support_Tickets_Msg::info(__("No tickets found.", 'orbisius_support_tickets')) ?>
                </div>
			<?php else : ?>
                <div class="table-responsive-md">
                    <table class="table table-striped w-auto">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Created at</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
						<?php foreach ( $items as $item_obj ) : ?>
							<?php
							$link = $this->generateTicketLink( [ 'ticket_id' => $item_obj->ID ] );
							?>
                            <tr class="table-info">
                                <th scope="row"><?php echo $item_obj->ID; ?></th>
                                <td><a href="<?php echo $link; ?>"><?php esc_attr_e( $item_obj->post_title ); ?></a>
                                </td>
                                <td><?php esc_attr_e( $item_obj->post_date ); ?></td>
                                <td>-</td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>
        </div>
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Processes [orbisius_support_submit_ticket] shortcode
	 * @return string
	 */
	public function renderSubmitTicketForm( $attribs = [] ) {
		ob_start();
		$id  = 0;
		$msg = '';

		$data = $this->getData();

		if ( ! empty( $data['submit'] ) ) {
			$res_obj = $this->processTicketSubmission($data);

			if ( $res_obj->isSuccess() ) {
				$msg = Orbisius_Support_Tickets_Msg::success('Created');
			} else {
				$msg = Orbisius_Support_Tickets_Msg::error( $res_obj->msg() );
			}
		}

		$ctx = [];

		$row_num = apply_filters( 'orbisius_support_tickets_filter_submit_ticket_form_message_row_num', 4 );
		?>

        <div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_submit_ticket_form', $ctx ); ?>

			<?php if ( ! isset( $attribs['render_title'] ) || $attribs['render_title'] ) : ?>
				<?php $title = empty( $attribs['title'] ) ? 'Submit ticket' : esc_html( $attribs['render_title'] ); ?>
                <h3><?php _e( $title, 'orbisius_support_tickets' ); ?></h3>
			<?php endif; ?>

			<?php echo $msg; ?>

            <div id="orbisius_support_tickets_submit_ticket_form_wrapper"
                 class="orbisius_support_tickets_submit_ticket_form_wrapper">
                <form id="orbisius_support_tickets_submit_ticket_form"
                      class="orbisius_support_tickets_submit_ticket_form form-horizontal"
                      method="post" enctype="multipart/form-data">
					<?php do_action( 'orbisius_support_tickets_action_submit_ticket_form_header', $ctx ); ?>
					<?php wp_nonce_field( 'orbisius_support_tickets_submit_ticket', 'orbisius_support_tickets_submit_ticket_nonce' ); ?>
                    <input type="hidden" name="orbisius_support_tickets_data[submit]" value="1"/>
                    <input type="hidden" name="orbisius_support_tickets_data[id]" id="orbisius_support_tickets_data_id"
                           value="<?php echo $id; ?>"/>

                    <!-- Subject -->
                    <div class="form-group">
                        <label class="col-md-3 control-label"
                               for="orbisius_support_tickets_data_subject">Subject</label>
                        <div class="col-md-9">
                            <input name="orbisius_support_tickets_data[subject]"
                                   id="orbisius_support_tickets_data_subject"
                                   type="text" placeholder="Subject"
                                   value="<?php esc_attr_e( $data['subject'] ); ?>"
                                   class="form-control orbisius_support_tickets_data_subject"/>
                        </div>
                    </div>

                    <!-- Message body -->
                    <div class="form-group">
                        <label class="col-md-3 control-label"
                               for="orbisius_support_tickets_data_message">Message</label>
                        <div class="col-md-9">
                            <textarea id="orbisius_support_tickets_data_message"
                                      class="orbisius_support_tickets_data_message form-control"
                                      name="orbisius_support_tickets_data[message]"
                                      placeholder="Please enter the message here..."
                                      rows="<?php echo $row_num; ?>"><?php esc_attr_e( $data['message'] ); ?></textarea>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit"
                                    id="orbisius_support_tickets_submit_ticket_form_submit"
                                    name="orbisius_support_tickets_submit_ticket_form_submit"
                                    class="orbisius_support_tickets_submit_ticket_form_submit btn btn-primary">
                                Submit
                            </button>
                        </div>
                    </div>

					<?php do_action( 'orbisius_support_tickets_action_submit_ticket_form_footer', $ctx ); ?>
                </form>
            </div>
			<?php do_action( 'orbisius_support_tickets_action_after_submit_ticket_form', $ctx ); ?>
        </div> <!-- /orbisius_support_tickets_submit_ticket_wrapper -->
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Processes [orbisius_support_view_ticket] shortcode
	 * @return string
	 */
	public function renderViewTicket( $attribs = [] ) {
		ob_start();
		$ticket_id = $this->getData('ticket_id');
		$msg = '';
		$ticket_obj = '';

		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$post_type = $cpt_api->getCptSupportTicket();

		try {
            if (empty($ticket_id) || !is_numeric($ticket_id)) {
                throw new Exception(_("Invalid ticket ID", 'orbisius_support_tickets') );
	            $ticket_id = 0;
            }

			if (!is_user_logged_in()) {
				throw new Exception("You must be logged in to view the ticket.");
			}

			$ticket_obj = get_post($ticket_id);

			if (empty($ticket_obj)) {
				throw new Exception( __("Invalid ticket ID", 'orbisius_support_tickets') );
			}

			// The ID is a different post type
			if ($post_type != get_post_type($ticket_obj)) {
				throw new Exception( __("Invalid ticket ID", 'orbisius_support_tickets') );
			}

			$user_id = get_current_user_id();

			// The current user is not the author of the ticket
			if ($ticket_obj->post_author > 0 && $user_id != $ticket_obj->post_author) {
				throw new Exception(__("Invalid ticket ID", 'orbisius_support_tickets') );
			}

			$args = [
				'order' => 'DESC',
				'post_id' => $ticket_obj->ID,
				'count' => false,
				'status' => 'all',
				'post_type' => $post_type,
			];

			$items = get_comments( $args );
		} catch (Exception $e) {
			$msg = Orbisius_Support_Tickets_Msg::error( $e->getMessage() );
        }


		$ctx   = [];
		$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		?>
        <div id="orbisius_support_tickets_view_ticket_wrapper" class="orbisius_support_tickets_view_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_view_ticket', $ctx ); ?>

			<?php if ( empty( $ticket_obj ) ) : ?>
                <div class="orbisius_support_tickets_list_ticket_msg">
                    <?php echo $msg; ?>
                </div>
			<?php else : ?>
                <div class="table-responsive-md">
                    <table class="table table-striped w-auto">
                        <tbody>
                            <tr>
                                <td><h3><?php echo $cpt_obj->fixOutput($ticket_obj->post_title); ?></h3></td>
                            </tr>
                            <tr>
                                <td><?php echo $cpt_obj->fixOutput($ticket_obj->post_content); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="orbisius_support_tickets_view_ticket_discussion_wrapper" class="orbisius_support_tickets_view_ticket_discussion_wrapper">
	                <?php foreach ( $items as $item_obj ) : ?>
		                <?php
		                $id = $item_obj->comment_ID;
		                $link = get_permalink( $item_obj->comm );
		                $row_cls = $user_id == $item_obj->user_id
			                ? 'orbisius_support_tickets_view_ticket_author_msg'
                            : '';
		                ?>
                        <div class="orbisius_support_tickets_view_ticket_discussion_item <?php echo $row_cls;?>">
                            <div class="reply"><?php echo $cpt_obj->fixOutput($item_obj->comment_content); ?></div>
                            <div class="date">Posted on: <?php esc_attr_e( $item_obj->comment_date ); ?></div>
                        </div>
                        <hr/>
	                <?php endforeach; ?>
                </div>

                <div class="reply_form">
                    <?php
                    $comments_args = [
	                    'title_reply' => __('Reply', 'orbisius_support_tickets'),
	                    'title_reply_to' => '',
	                    'label_submit' => __('Send', 'orbisius_support_tickets'),
	                    'comment_notes_after' => '',
	                    'comment_notes_before' => '',
                    ];

                    if ($ticket_id) {
	                    comment_form( $comments_args, $ticket_id );
                    }
                    ?>
                </div>
			<?php endif; ?>
        </div>
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Gets the data that the plugin expects or the value for a given variable.
	 * @param string $key (optional
	 * @return array|mixed
	 */
	public function getData($key = '') {
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$data = $req_obj->getRaw('orbisius_support_tickets_data', []);
		$data = array_replace_recursive( $this->defaults, $data );
		$val = apply_filters( 'orbisius_support_tickets_filter_submit_ticket_form_sanitize_data', $data );

		if (!empty($key)) {
			$val = empty($data[$key]) ? '' : $data[$key];
        }

		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$val = $req_obj->trim($val);

		return $val;
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

	/**
	 * @param array $params
     * @return string
	 */
	public function generateTicketLink( array $params ) {
		$query_params = [
            'orbisius_support_tickets_data' => [
                'ticket_id' => $params['ticket_id'],
            ],
        ];

	    $link = site_url("/support/view-ticket/");
		$link .= '?' . http_build_query($query_params);
		return $link;
	}
}