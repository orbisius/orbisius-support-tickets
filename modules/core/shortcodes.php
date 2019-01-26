<?php

$shortcode_api = Orbisius_Support_Tickets_Module_Core_Shortcodes::getInstance();

add_action('init', array( $shortcode_api, 'init' ) ) ;

class Orbisius_Support_Tickets_Module_Core_Shortcodes {
	private $cpt_support_ticket = 'orb_support_tickets_item';

	public function init() {
		$this->registerCodes();
	}

	/**
	 *
	 */
	function registerCodes() {
		add_shortcode( 'orbisius_support_tickets_field', array( $this, 'renderTicketField' ) );
		add_shortcode( 'orbisius_support_tickets_view_ticket', array( $this, 'renderViewTicket' ) );
		add_shortcode( 'orbisius_support_tickets_list_tickets', array( $this, 'renderTickets' ) );
		add_shortcode( 'orbisius_support_tickets_submit_ticket', array( $this, 'renderSubmitTicketForm' ) );
		add_shortcode( 'orbisius_support_tickets_generate_page_link', array( $this, 'generatePageLink' ) );

		add_action('orbisius_support_tickets_view_ticket_after_ticket_content_wrapper', array( $this, 'renderSeparator' ) );
		add_action('orbisius_support_tickets_view_ticket_meta', array( $this, 'renderTicketInfo' ), 20 );
		add_action('orbisius_support_tickets_view_ticket_meta', array( $this, 'renderCloseTicketButton' ), 20 );
	}

	/**
     * This is used in submit ticket form
	 * @var array
	 */
	private $form_data_defaults = array(
		'id' => 0,
		'email' => '',
		'subject' => '',
		'message' => '',
    );

	/**
	 * Processes
	 * @return Orbisius_Support_Tickets_Result
	 */
	public function processTicketSubmission($data = array()) {
		try {
			$user_id = get_current_user_id();
			$res     = new Orbisius_Support_Tickets_Result();
			$cpt_api       = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
			$post_type     = $cpt_api->getCptSupportTicket();

			$pwd = wp_generate_password( 8, false );

			$ins_post_data = array(
				'post_type'   => $post_type,
				'post_author' => $user_id,
				'post_status' => Orbisius_Support_Tickets_Module_Core_CPT::STATUS_OPEN,
                'post_password' => $pwd, // WP will remove this but we'll put it there anyways.
                'meta_input' => array(),
            );

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

			// Add guest user's
			if (!empty($data['email'])) {
			    $ins_post_data['meta_input']['_orbsuptx_email'] = $data['email'];
            }

			$user_api = Orbisius_Support_Tickets_User::getInstance();
			$ins_post_data['meta_input']['_orbsuptx_user_ip'] = $user_api->getUserIP();

			$ctx = array(
				'author_id' => $user_id,
				'data' => $ins_post_data,
            );

			do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_upsert', $ctx );

			if ( empty( $ins_post_data['ID'] ) ) {
				do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_insert', $ctx );

				// insert pwd here
				$id = wp_insert_post( $ins_post_data );

				if ( empty($id) || ! is_numeric( $id ) || $id <= 0 ) {
					throw new Exception( "Couldn't save item." );
				}

				// Hack!: WP removes the password for private posts ?!? We'll add this.
                // We could have hooked into insert post data but I need to give the PWD to the user
                // if he/she is not logged in. Using a hook would force me to use globals.
				global $wpdb;
				$hack_pwd_update_res = $wpdb->update(
					$wpdb->posts,
					array(
						'post_password' => $pwd,
					),
					array( 'ID' => $id ),
					array(
						'%s',	// value1
					),
					array( '%d' )
				);

				$res->data( 'ticket_pwd', $pwd );
				$ctx['ticket_id'] = $id;
				do_action( 'orbisius_support_tickets_action_before_submit_ticket_after_insert', $ctx );
			} else {
				$ctx['ticket_id'] = $ins_post_data['ID'];
				do_action( 'orbisius_support_tickets_action_before_submit_ticket_before_update', $ctx );
				$id = wp_update_post( $ins_post_data );

				if ( empty($id) || ! is_numeric( $id ) || $id <= 0 ) {
					throw new Exception( "Couldn't save item." );
				}

				do_action( 'orbisius_support_tickets_action_before_submit_ticket_after_update', $ctx );
			}

			$res->data( 'id', $id );
			$res->status( 1 );
		} catch ( Exception $e ) {
			$res->msg( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Processes [orbisius_support_tickets_list_tickets] shortcode
	 * @return string
	 */
	public function renderTickets( $attribs = array() ) {
		ob_start();

		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();

		$filter = array();
		$filter['offset']         = empty( $orb_cloud_lib_data['offset'] ) ? 0 : int( $orb_cloud_lib_data['offset'] );
		$filter['author']         = get_current_user_id();
		$filter['posts_per_page'] = empty( $orb_cloud_lib_data['limit'] ) ? 250 : int( $orb_cloud_lib_data['limit'] );
		$orb_cloud_lib_data       = array();

		if ( ! empty( $orb_cloud_lib_data['search'] ) ) {
			$filter['search'] = $orb_cloud_lib_data['search'];
		}

		$items = $cpt_api->getItems($filter);

		$ctx   = array();
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
							$link = $this->generateViewTicketLink( array( 'ticket_id' => $item_obj->ID ) );
							$status = $cpt_api->getTicketStatus($item_obj);
							?>
                            <tr class="table-info">
                                <td scope="row"><?php echo $item_obj->ID; ?></td>
                                <td>
                                    <a href="<?php echo $link; ?>"><?php esc_attr_e( $item_obj->post_title ); ?></a>
                                </td>
                                <td><?php esc_attr_e( $item_obj->post_date ); ?></td>
                                <td><?php esc_attr_e( $status ); ?></td>
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
     * Outputs some
	 * @return string
	 */
	public function renderSeparator( $ctx = array() ) {
		?>
        <hr class="ticket_sep"/>
		<?php
	}

	/**
     * Outputs some
	 * @return string
	 */
	public function renderTicketInfo( $ctx = array() ) {
		?>
        <div id="ticket_meta_ticket_id_wrapper" class="ticket_meta_ticket_item ticket_meta_ticket_id_wrapper">
            <?php do_action('orbisius_support_tickets_view_ticket_before_ticket_id', $ctx); ?>
            <?php echo sprintf( __( "Ticket ID: %s", 'orbisius_support_tickets' ), $ctx['ticket_id' ] ); ?>
            <?php do_action('orbisius_support_tickets_view_ticket_after_ticket_id', $ctx); ?>
        </div>

        <?php
		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$statuses = $cpt_api->getStatuses();
		$status = $cpt_api->getStatus($ctx['ticket_id' ]);
        ?>
        <div class="ticket_meta_ticket_status_wrapper ticket_meta_ticket_item ticket_meta_ticket_status_<?php echo $status;?>">
			<?php
			if (!empty($statuses[ $status ])) {
				echo __('Status: ', 'orbisius_support_tickets' ) . sprintf("<span class='status_value'>%s</span>", $statuses[ $status ] );
			}
			?>
        </div> <!-- /ticket_meta_ticket_status_wrapper -->

		<?php
		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$ticket_obj = $cpt_api->getTicket($ctx['ticket_id']);
		?>
        <div class="ticket_meta_ticket_creation_date_wrapper ticket_meta_ticket_item">
			<?php
            echo __('Created on: ', 'orbisius_support_tickets' ) . sprintf("<span class='creation_date_value'>%s</span>", $ticket_obj->post_date );
			?>
        </div> <!-- /ticket_meta_ticket_creation_date_wrapper -->
		<?php
	}

	/**
	 * Renders the close ticket button if necessary.
	 * @return string
	 */
	public function renderCloseTicketButton( $ctx = array() ) {
	    if (empty($ctx['ticket_id'])) {
	        return;
        }

		$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$ticket_obj = $cpt_obj->getTicket($ctx['ticket_id']);

		if (empty($ticket_obj)) {
			return;
		}

		if ($cpt_obj->getStatus($ticket_obj) == Orbisius_Support_Tickets_Module_Core_CPT::STATUS_CLOSED) {
			return;
		}

		$redir = 0;
		ob_start();
		$data = $this->getData();
        $admin_api    = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();
        $settings_key = $admin_api->getPluginSettingsKey();
        $view_ticket_link = $this->generateViewTicketLink( array( 'ticket_id' => $ctx['ticket_id'] ) );

		if (!empty($data['sub_cmd']) && $data['sub_cmd'] == 'close') {
			$status = $cpt_obj->changeStatus( $ctx['ticket_id'], Orbisius_Support_Tickets_Module_Core_CPT::STATUS_CLOSED );

			if ( $status ) {
				$msg = Orbisius_Support_Tickets_Msg::success( __( 'Ticket closed', 'orbisius_support_tickets' ) );
			} else {
				$msg = Orbisius_Support_Tickets_Msg::error( __( 'Cannot close the ticket', 'orbisius_support_tickets' ) );
			}

			echo $msg;
			$redir = 1;
        } else {
            echo '<div id="orbisius_support_tickets_close_ticket_wrapper" class="ticket_meta_ticket_item orbisius_support_tickets_close_ticket_wrapper">';
            $view_ticket_link = add_query_arg( "{$settings_key}_data[cmd]", 'view_ticket', $view_ticket_link );
            $view_ticket_link = add_query_arg( "{$settings_key}_data[sub_cmd]", 'close', $view_ticket_link );
            $label            = __( 'Close Ticket', 'orbisius_support_tickets' );
            echo "<a href='$view_ticket_link' id='close_ticket_btn' class='close_ticket_btn btn button'>$label</a>";
            echo '</div> <!-- /orbisius_support_tickets_close_ticket_wrapper -->';
        }

		$html = ob_get_contents();
		ob_end_clean();

		echo $html;

		if ($redir) {
			$req_obj = Orbisius_Support_Tickets_Request::getInstance();

			// we redirect because we want the url to change. The current one has cmd to close the ticket
			$req_obj->redirect( $view_ticket_link );
		}
	}

	/**
	 * Processes [orbisius_support_tickets_submit_ticket] shortcode
	 * @return string
	 */
	public function renderSubmitTicketForm( $attribs = array() ) {
		ob_start();
		$id  = 0;
		$msg = '';
		$data = $this->getData();
		$res_obj = new Orbisius_Support_Tickets_Result();
		$show_form = 1;
		$show_email_field = ! is_user_logged_in();

		try {
			$admin_api = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();

			$opts = $admin_api->getOptions();

			if (empty($opts['allow_guests_to_submit_tickets']) && !is_user_logged_in()) {
			    $login_link = '#';
			    $register_link = '#';
				$req_login_reg_msg = sprintf("You need to <a href='%s'>Login</a> or <a href='%s'>Register</a> in order to submit a ticket", $login_link, $register_link);
				$res_obj->msg($req_login_reg_msg);
				$show_form = 0;
				throw new Exception( __( $res_obj->msg(), 'orbisius_support_tickets') );
            }

			if ( ! empty( $data['submit'] ) ) {
				if ( empty( $_POST['orbisius_support_tickets_submit_ticket_nonce'] )
				    || ! wp_verify_nonce( $_POST['orbisius_support_tickets_submit_ticket_nonce'], 'orbisius_support_tickets_submit_ticket' ) ) {
					throw new Exception( __("Invalid submission", 'orbisius_support_tickets') );
				}

				// If the user is not logged in we'll have to ask for an email.
				if ($show_email_field && ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) ) {
					$missing_email_msg = sprintf( __( "Empty or invalid email", 'orbisius_support_tickets' ) );
					throw new Exception( $missing_email_msg );
				}

				$res_obj = $this->processTicketSubmission($data);

				if ( $res_obj->isError() ) {
				    throw new Exception( $res_obj->msg() );
				}

                $ticket_id = $res_obj->data('id');
                $ticket_link = $this->generateViewTicketLink( array( 'ticket_id' => $ticket_id, ) );
                $msg = sprintf( __( "Ticket created. <a href='%s'>Ticket #%d</a>", 'orbisius_support_tickets' ), $ticket_link, $ticket_id);

                if ($res_obj->data( 'ticket_pwd')) {
	                $msg .= "<br/>" . sprintf( __( "Ticket password: %s", 'orbisius_support_tickets' ), $res_obj->data( 'ticket_pwd'));
				}

                $msg = Orbisius_Support_Tickets_Msg::success($msg);
			}
        } catch (Exception $e) {
			$msg = Orbisius_Support_Tickets_Msg::error( $res_obj->msg() );
		}

		$ctx = array();
		$row_num = apply_filters( 'orbisius_support_tickets_filter_submit_ticket_form_message_row_num', 4 );
		?>

        <div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_submit_ticket_form', $ctx ); ?>

			<?php if ( ! isset( $attribs['render_title'] ) || $attribs['render_title'] ) : ?>
				<?php $title = empty( $attribs['title'] ) ? 'Submit ticket' : esc_html( $attribs['render_title'] ); ?>
                <h3><?php _e( $title, 'orbisius_support_tickets' ); ?></h3>
			<?php endif; ?>

			<?php echo $msg; ?>

            <?php if ( $show_form && (empty( $data['submit'] ) || $res_obj->isError() ) ) : ?>
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

	                    <?php if ( $show_email_field ) : ?>
                            <div class="form-group">
                                <label class="col-md-3 control-label"
                                       for="orbisius_support_tickets_data_email">
				                    <?php _e( 'Email', 'orbisius_support_tickets' ); ?></label>
                                <div class="col-md-9">
                                    <input name="orbisius_support_tickets_data[email]"
                                           id="orbisius_support_tickets_data_email"
                                           type="text" placeholder="<?php _e( 'Email', 'orbisius_support_tickets' ); ?>"
                                           value="<?php esc_attr_e( $data['email'] ); ?>"
                                           class="form-control orbisius_support_tickets_data_email orbisius_support_tickets_full_width"/>
                                </div>
                            </div>
	                    <?php endif; ?>

                        <div class="form-group">
                            <label class="col-md-3 control-label"
                                   for="orbisius_support_tickets_data_subject">
                                <?php _e( 'Subject', 'orbisius_support_tickets' ); ?></label>
                            <div class="col-md-9">
                                <input name="orbisius_support_tickets_data[subject]"
                                       id="orbisius_support_tickets_data_subject"
                                       type="text" placeholder="<?php _e( 'Subject', 'orbisius_support_tickets' ); ?>"
                                       value="<?php esc_attr_e( $data['subject'] ); ?>"
                                       class="form-control orbisius_support_tickets_data_subject orbisius_support_tickets_full_width"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label"
                                   for="orbisius_support_tickets_data_message">
                                <?php _e( 'Message', 'orbisius_support_tickets' ); ?></label>
                            <div class="col-md-9">
                                <textarea id="orbisius_support_tickets_data_message"
                                          class="orbisius_support_tickets_data_message form-control orbisius_support_tickets_full_width"
                                          name="orbisius_support_tickets_data[message]"
                                          placeholder="Please enter the message here..."
                                          rows="<?php echo $row_num; ?>"><?php esc_attr_e( $data['message'] ); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12 text-right">
                                <button type="submit"
                                        id="orbisius_support_tickets_submit_ticket_form_submit"
                                        name="orbisius_support_tickets_submit_ticket_form_submit"
                                        class="orbisius_support_tickets_submit_ticket_form_submit btn btn-primary">
                                    <?php _e( 'Submit', 'orbisius_support_tickets' ); ?>
                                </button>
                            </div>
                        </div>

                        <?php do_action( 'orbisius_support_tickets_action_submit_ticket_form_footer', $ctx ); ?>
                    </form>
                </div> <!-- /orbisius_support_tickets_submit_ticket_form_wrapper -->
                <?php do_action( 'orbisius_support_tickets_action_after_submit_ticket_form', $ctx ); ?>
            <?php endif; ?>
        </div> <!-- /orbisius_support_tickets_submit_ticket_wrapper -->
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Processes [orbisius_support_tickets_view_ticket] shortcode
	 * @return string
	 */
	public function renderViewTicket( $attribs = array() ) {
		ob_start();
		$msg = '';
		$ctx = array();
		$items = array();
		$ticket_id = $this->getData('ticket_id');
		$ticket_obj = '';
		$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$user_api = Orbisius_Support_Tickets_User::getInstance();

		$cpt_api   = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$post_type = $cpt_api->getCptSupportTicket();

		try {
            if (empty($ticket_id) || !is_numeric($ticket_id)) {
                throw new Exception(__("Invalid ticket ID", 'orbisius_support_tickets') );
	            $ticket_id = 0;
            }

			$ticket_obj = $cpt_obj->getTicket($ticket_id);

			if (empty($ticket_obj)) {
				throw new Exception( __("Invalid ticket ID", 'orbisius_support_tickets') );
			}

			// The ID is a different post type
			if ($post_type != get_post_type($ticket_obj)) {
				throw new Exception( __("Invalid ticket ID", 'orbisius_support_tickets') );
			}

			$user_id = get_current_user_id();

			if (is_user_logged_in()) {
				// The current user is not the author of the ticket
				if ($ticket_obj->post_author > 0 && $user_id != $ticket_obj->post_author) {
					if (!$user_api->isEditor()) { // editor is OK to view stuff
						throw new Exception( __( "Invalid ticket ID", 'orbisius_support_tickets' ) );
					}
				}
			} elseif ($req_obj->has('post_password')) {
				if (empty($ticket_obj->post_password)
                    || $req_obj->get('post_password') != $ticket_obj->post_password) {
					throw new Exception( __( "Invalid ticket ID", 'orbisius_support_tickets' ) );
				}
			} elseif (post_password_required($ticket_obj)) {
                $pwd_form   = get_the_password_form( $ticket_obj );
                $msg        = $pwd_form;
                $ticket_id  = 0;
                $ticket_obj = null;
            }

			if ($ticket_id) {
				$args = array(
					'order'     => 'ASC', // DESC
					'post_id'   => $ticket_id,
					'count'     => false,
					'status'    => 'all',
					'post_type' => $post_type,
				);

				$items = get_comments( $args );

				$ctx = array(
					'ticket_id' => $ticket_id,
				);
			}
		} catch (Exception $e) {
			$msg = Orbisius_Support_Tickets_Msg::error( $e->getMessage() );
        }

		?>
        <div id="orbisius_support_tickets_view_ticket_wrapper" class="orbisius_support_tickets_view_ticket_wrapper">
			<?php do_action( 'orbisius_support_tickets_action_before_view_ticket', $ctx ); ?>

			<?php if ( empty( $ticket_obj ) ) : ?>
                <div id="orbisius_support_tickets_view_ticket_msg" class="orbisius_support_tickets_view_ticket_msg">
                    <?php echo $msg; ?>
                </div>
			<?php else : ?>
				<?php echo $msg; ?>

                <div class="ticket_wrapper">
	                <?php do_action('orbisius_support_tickets_view_ticket_before_ticket_title_wrapper', $ctx); ?>
                    <div class="ticket_title_wrapper">
	                    <?php do_action('orbisius_support_tickets_view_ticket_before_ticket_title', $ctx); ?>
                        <h3><?php echo $cpt_obj->fixOutput($ticket_obj->post_title); ?></h3>
	                    <?php do_action('orbisius_support_tickets_view_ticket_after_ticket_title', $ctx); ?>
                    </div>

	                <?php do_action('orbisius_support_tickets_view_ticket_after_ticket_title_wrapper', $ctx); ?>

                    <div id="ticket_meta_wrapper" class="ticket_meta_wrapper">
	                    <?php do_action('orbisius_support_tickets_view_ticket_meta', $ctx); ?>
                    </div> <!-- /ticket_meta_wrapper -->

	                <?php do_action('orbisius_support_tickets_view_ticket_before_ticket_content_wrapper', $ctx); ?>
                    <div class="ticket_content_wrapper">
	                    <?php do_action('orbisius_support_tickets_view_ticket_before_ticket_content', $ctx); ?>
	                    <?php echo $cpt_obj->fixOutput($ticket_obj->post_content); ?>
                        <?php do_action('orbisius_support_tickets_view_ticket_after_ticket_content', $ctx); ?>
                    </div>
                    <?php do_action('orbisius_support_tickets_view_ticket_after_ticket_content_wrapper', $ctx); ?>
                </div>

                <div id="orbisius_support_tickets_view_ticket_discussion_wrapper" class="orbisius_support_tickets_view_ticket_discussion_wrapper">
	                <?php foreach ( $items as $item_obj ) : ?>
		                <?php
		                $id = $item_obj->comment_ID;
		                $row_cls = $user_id == $item_obj->user_id
			                ? 'orbisius_support_tickets_view_ticket_author_msg'
                            : 'orbisius_support_tickets_view_ticket_rep_msg';
		                ?>

                        <div id="comment-<?php echo $id;?>" class="orbisius_support_tickets_view_ticket_discussion_item <?php echo $row_cls;?>">
                            <div class="reply"><?php echo $cpt_obj->fixOutput($item_obj->comment_content); ?></div>
                            <div class="date">Posted on: <?php esc_attr_e( $item_obj->comment_date ); ?></div>
                        </div>
                        <hr/>
	                <?php endforeach; ?>
                </div>


                <?php
				$ctx = array(
					'ticket_id' => $ticket_id,
					'ticket_obj' => $ticket_obj,
                );

				do_action( 'orbisius_support_tickets_action_view_ticket_after_initial_post', $ctx );
                ?>
                <div class="reply_form">
                    <?php
                    $comments_args = array(
	                    'title_reply' => __('Reply', 'orbisius_support_tickets'),
	                    'title_reply_to' => '',
	                    'label_submit' => __('Send', 'orbisius_support_tickets'),
	                    'comment_notes_after' => '',
	                    'comment_notes_after' => '',
	                    'comment_notes_before' => '',
	                    'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . '</label> <textarea id="comment" name="comment" cols="45" rows="5" maxlength="65525" required="required"></textarea></p>',
                    );

                    if ($ticket_id) {
                        add_action('comment_form_top', array( $this, 'injectRedirect' ) );
	                    comment_form( $comments_args, $ticket_id );
	                    remove_action('comment_form_top', array( $this, 'injectRedirect' ) );
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
	 * Processes [orbisius_support_tickets_generate_page_link] shortcode and returns the page URL for a given requested page
	 * @return string
	 */
	public function generatePageLink( $attribs = array() ) {
		$link = '#';

		if (empty($attribs['page'])) {
		    return $link;
        }

		$admin_api = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();
		$opts = $admin_api->getOptions();

		// req: submit_ticket, In the settings page we'll look for 'submit_ticket_page_id'
        // and if it's set we'll return the link to it
		if ( empty( $opts[ $attribs['page'] . '_page_id' ] ) ) {
			return $link;
        }

		$link = get_permalink($opts[ $attribs['page'] . '_page_id' ]);

		if (!empty($attribs['esc'])) {
		    $link = esc_url($link);
        }

		return $link;
	}

	private $supported_ticket_fields = array(
        'ticket_id' => '',
    );

	/**
	 * Processes [orbisius_support_tickets_field] shortcode and returns the page URL for a given requested page
	 * @return string
	 */
	public function renderTicketField( $attribs = array() ) {
		$field = '';

		if (!empty($attribs['id'])) {
			$field = $attribs['id'];
        } elseif (!empty($attribs['field'])) {
			$field = $attribs['field'];
		} else {
		    return '';
        }

		$field = 'TODO';

		return $field;
	}

	public function injectRedirect() {
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$req_url = $req_obj->getRequestUrl();
		$req_url_esc = esc_url($req_url);
		echo "<input type='hidden' name='redirect_to' value='$req_url_esc'>";
    }

	/**
	 * Gets the data that the plugin expects or the value for a given variable.
	 * @param string $key (optional
	 * @return array|mixed
	 */
	public function getData($key = '') {
		$req_obj = Orbisius_Support_Tickets_Request::getInstance();
		$data = $req_obj->getRaw('orbisius_support_tickets_data', array());
		$data = array_replace_recursive( $this->form_data_defaults, $data );
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
	public function generateViewTicketLink( array $params ) {
		$query_params = array(
            'orbisius_support_tickets_data' => array(
                'ticket_id' => $params['ticket_id'],
            ),
        );

		$cpt  = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();
        $opts = $cpt->getOptions();

        if (!empty($opts['view_ticket_page_id'])) {
            $link = get_page_link($opts['view_ticket_page_id']);
        } else {
            $link = site_url('/');
        }

		$link = add_query_arg($query_params, $link);

		return $link;
	}

	/**
	 * @return array
	 */
	public function getSupportedTicketFields() {
		return apply_filters('orbisius_support_tickets_filter_ticket_supported_fields', $this->supported_ticket_fields);
	}

	/**
	 * @param array $supported_ticket_fields
	 */
	public function setSupportedTicketFields( $supported_ticket_fields ) {
		$this->supported_ticket_fields = $supported_ticket_fields;
	}
}