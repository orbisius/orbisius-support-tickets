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
//		add_shortcode('orbisius_support_list_tickets', [ $this, 'renderTickets' ] );
		add_shortcode('orbisius_support_submit_ticket', [ $this, 'renderSubmitTicketForm' ] );
	}

	private $defaults = [
        'subject' => '',
        'message' => '',
    ];

	/**
	 * Processes
	 * @return Orbisius_Support_Tickets_Result
	 */
	public function processTicketSubmission() {
	    try {
		    $user_id = get_current_user_id();
	        $res = new Orbisius_Support_Tickets_Result();

		    $cpt_api = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		    $post_type = $cpt_api->getCptSupportTicket();
		    $ins_post_data = [
			    'post_type' => $post_type,
			    'post_author' => $user_id,
			    'post_status' => 'private', // 'publish';
            ];

		    $raw_post_data = $this->getData();

		    // This is required
            if (empty($raw_post_data['subject'])) {
	            throw new Exception("Empty subject");
            }

            // ... but still let's define a default
		    $ins_post_data['post_title'] = empty($raw_post_data['subject'])
			    ? 'Untitled ' . current_time('mysql')
			    : $raw_post_data['subject'];

		    $ins_post_data['post_content'] = empty($raw_post_data['message'])
                ? ''
                : $raw_post_data['message'];

		    if ( ! empty( $raw_post_data['id'] ) ) {
		        // @todo check if the user is allowed to update this ticket
		        if (!current_user_can('manage_options')) {
			        throw new Exception("Cannot edit.");
		        }

                // admin OR the author???... only admin
			    $id = (int) $raw_post_data['id'];
			    $ins_post_data['ID'] = $id;
		    } else {
//			    if ($user_api->get_user_posts($post_type, $user_id) >= $this->max_snippets) {
//				    throw new Exception("You have reached your limits. Please, upgrade");
//			    }
		    }

		    $ctx = [
                'data' => $ins_post_data,
            ];

		    do_action('orbisius_support_tickets_action_before_submit_ticket_before_upsert', $ctx);

		    if (empty($ins_post_data['ID'])) {
			    do_action('orbisius_support_tickets_action_before_submit_ticket_before_insert', $ctx);
			    $id = wp_insert_post( $ins_post_data );
            } else {
			    do_action('orbisius_support_tickets_action_before_submit_ticket_before_update', $ctx);
			    wp_update_post( $ins_post_data );
		    }

		    if ( !is_numeric( $id ) || $id <= 0 ) {
			    throw new Exception( "Couldn't save item." );
		    }

            $res->data('id', $id);
		    $res->status(1);
        } catch (Exception $e) {
		    $res->msg($e->getMessage());
        }

        return $res;
	}

	/**
	 * Processes [orbisius_support_submit_ticket] shortcode
	 * @return string
	 */
	public function renderSubmitTicketForm($attribs = []) {
		ob_start();
		$id = 0;
		$msg = '';

		$data = $this->getData();
//
//		$ad_id = q('ad_id', 0);
//
//		if ( !orb_cust_ds_user::can_edit( $ad_id ) ) {
//			echo orb_cust_ds_msg::msg('Invalid Ad #', 0);
//			return;
//		}
var_dump($_REQUEST);

        if (!empty($data['submit'])) {
            $res_obj = $this->processTicketSubmission();

            if ($res_obj->isSuccess()) {
	            $msg = 'Created';
            } else {
	            $msg = $res_obj->msg();
            }
        }

		$ctx = [];

		$row_num = apply_filters('orbisius_support_tickets_filter_submit_ticket_form_message_row_num', 4);
		?>

		<div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action('orbisius_support_tickets_action_before_submit_ticket_form', $ctx); ?>

            <?php if (!isset($attribs['render_title']) || $attribs['render_title']) : ?>
			    <?php $title = empty($attribs['title']) ? 'Submit ticket' : esc_html($attribs['render_title']); ?>
			    <h3><?php _e($title, 'orbisius_support_tickets'); ?></h3>
            <?php endif; ?>

		    <?php echo $msg; ?>

            <div id="orbisius_support_tickets_submit_ticket_form_wrapper" class="orbisius_support_tickets_submit_ticket_form_wrapper">
				<form id="orbisius_support_tickets_submit_ticket_form"
                      class="orbisius_support_tickets_submit_ticket_form form-horizontal"
                      method="post" enctype="multipart/form-data">
					<?php do_action('orbisius_support_tickets_action_submit_ticket_form_header', $ctx); ?>
					<?php wp_nonce_field( 'orbisius_support_tickets_submit_ticket', 'orbisius_support_tickets_submit_ticket_nonce' ); ?>
					<input type="hidden" name="orbisius_support_tickets_data[submit]" value="1" />
					<input type="hidden" name="orbisius_support_tickets_data[id]" id="orbisius_support_tickets_data_id" value="<?php echo $id; ?>" />

                    <!-- Subject -->
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="orbisius_support_tickets_data_subject">Subject</label>
                        <div class="col-md-9">
                            <input name="orbisius_support_tickets_data[subject]"
                                   id="orbisius_support_tickets_data_subject"
                                   type="text" placeholder="Subject"
                                   value="<?php esc_attr_e($data['subject']); ?>"
                                   class="form-control orbisius_support_tickets_data_subject" />
                        </div>
                    </div>

                    <!-- Message body -->
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="orbisius_support_tickets_data_message">Message</label>
                        <div class="col-md-9">
                            <textarea id="orbisius_support_tickets_data_message"
                                      class="orbisius_support_tickets_data_message form-control"
                                      name="orbisius_support_tickets_data[message]"
                                      placeholder="Please enter the message here..."
                                      rows="<?php echo $row_num;?>"><?php esc_attr_e($data['message']); ?></textarea>
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

					<?php do_action('orbisius_support_tickets_action_submit_ticket_form_footer', $ctx); ?>
				</form>
			</div>
			<?php do_action('orbisius_support_tickets_action_after_submit_ticket_form', $ctx); ?>
		</div> <!-- /orbisius_support_tickets_submit_ticket_wrapper -->
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
     * @todo sanitize but allow some tags
	 * @return array
	 */
	public function getData() {
		$sanitized_data = empty($_REQUEST['orbisius_support_tickets_data']) ? [] : $_REQUEST['orbisius_support_tickets_data'];
		$sanitized_data = array_map('trim', $sanitized_data);
		$sanitized_data = array_replace_recursive($this->defaults, $sanitized_data);
		$sanitized_data = apply_filters('orbisius_support_tickets_filter_submit_ticket_form_sanitize_data', $sanitized_data);
		return $sanitized_data;
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
		if (is_null($instance)) {
			$instance = new static();
		}

		return $instance;
	}
}