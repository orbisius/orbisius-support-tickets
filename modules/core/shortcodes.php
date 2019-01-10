<?php

$cpt = Orbisius_Support_Tickets_Module_Core_Shortcodes::getInstance();

add_action('init', [ $cpt, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Shortcodes extends Orbisius_Support_Tickets_Singleton {
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
	 * Processes [orbisius_support_submit_ticket] shortcode
	 * @return string
	 */
	public function renderSubmitTicketForm($attribs = []) {
		ob_start();
		$id = 0;

		$sanitized_data = $this->getData();
		$data = array_replace_recursive($this->defaults, $sanitized_data);
//
//		$ad_id = q('ad_id', 0);
//
//		if ( !orb_cust_ds_user::can_edit( $ad_id ) ) {
//			echo orb_cust_ds_msg::msg('Invalid Ad #', 0);
//			return;
//		}
var_dump($_REQUEST);

		$ctx = [];

		$row_num = apply_filters('orbisius_support_tickets_filter_submit_ticket_form_message_row_num', 4);
		?>

		<div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action('orbisius_support_tickets_action_before_submit_ticket_form', $ctx); ?>

            <?php if (!isset($attribs['render_title']) || $attribs['render_title']) : ?>
			    <?php $title = empty($attribs['title']) ? 'Submit ticket' : esc_html($attribs['render_title']); ?>
			    <h3><?php _e($title, 'orbisius_support_tickets'); ?></h3>
            <?php endif; ?>

            <div id="orbisius_support_tickets_submit_ticket_form_wrapper" class="orbisius_support_tickets_submit_ticket_form_wrapper">
				<form id="orbisius_support_tickets_submit_ticket_form"
                      class="orbisius_support_tickets_submit_ticket_form form-horizontal"
                      method="post" enctype="multipart/form-data">
					<?php do_action('orbisius_support_tickets_action_submit_ticket_form_header', $ctx); ?>
					<?php wp_nonce_field( 'orbisius_support_tickets_submit_ticket', 'orbisius_support_tickets_submit_ticket_nonce' ); ?>
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
                        <label class="col-md-3 control-label" for="orbisius_support_tickets_data_message">Your message</label>
                        <div class="col-md-9">
                            <textarea id="orbisius_support_tickets_data_message"
                                      class="orbisius_support_tickets_data_message form-control"
                                      name="orbisius_support_tickets_data[message]"
                                      placeholder="Please enter your message here..."
                                      rows="<?php echo $row_num;?>"><?php esc_attr_e($data['message']); ?></textarea>
                        </div>
                    </div>

                    <button type="submit"
                            id="orbisius_support_tickets_submit_ticket_form_submit"
                            name="orbisius_support_tickets_submit_ticket_form_submit"
                            class="orbisius_support_tickets_submit_ticket_form_submit">
                        Submit
                    </button>
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
		$sanitized_data = apply_filters('orbisius_support_tickets_filter_submit_ticket_form_sanitize_data', $sanitized_data);
		return $sanitized_data;
	}
}