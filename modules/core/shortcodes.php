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

	/**
	 * Processes [orbisius_support_submit_ticket] shortcode
	 * @return string
	 */
	public static function renderSubmitTicketForm($attribs = []) {
		ob_start();
//
//		$ad_id = q('ad_id', 0);
//
//		if ( !orb_cust_ds_user::can_edit( $ad_id ) ) {
//			echo orb_cust_ds_msg::msg('Invalid Ad #', 0);
//			return;
//		}

		$ad_id = 0;

		?>

		<div id="orbisius_support_tickets_submit_ticket_wrapper" class="orbisius_support_tickets_submit_ticket_wrapper">
			<?php do_action('orbisius_support_tickets_action_before_submit_ticket_form'); ?>

            <?php if (!isset($attribs['render_title']) || $attribs['render_title']) : ?>
			    <?php $title = empty($attribs['title']) ? 'Submit ticket' : esc_html($attribs['render_title']); ?>
			    <h3><?php _e($title, 'orbisius_support_tickets'); ?></h3>
            <?php endif; ?>

            <div id="orbisius_support_tickets_submit_ticket_form_wrapper" class="orbisius_support_tickets_submit_ticket_form_wrapper">
				<form id="orbisius_support_tickets_submit_ticket_form"
                      class="orbisius_support_tickets_submit_ticket_form form-horizontal"
                      method="post" enctype="multipart/form-data">
					<?php do_action('orbisius_support_tickets_action_submit_ticket_form_header'); ?>
					<?php wp_nonce_field( 'orbisius_support_tickets_submit_ticket', 'orbisius_support_tickets_submit_ticket_nonce' ); ?>
					<input type="hidden" name="ad_id" id="ad_id" value="<?php echo $ad_id; ?>" />

                    <!-- Subject -->
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="orbisius_support_tickets_data_subject">Subject</label>
                        <div class="col-md-9">
                            <input name="orbisius_support_tickets_data[subject]"
                                   id="orbisius_support_tickets_data_subject"
                                   type="text" placeholder="Subject"
                                   class="form-control">
                        </div>
                    </div>

                    <button type="submit"
                            id="orbisius_support_tickets_submit_ticket_form_submit"
                            name="orbisius_support_tickets_submit_ticket_form_submit"
                            class="orbisius_support_tickets_submit_ticket_form_submit">
                        Submit
                    </button>
					<?php do_action('orbisius_support_tickets_action_submit_ticket_form_footer'); ?>
				</form>
			</div>
			<?php do_action('orbisius_support_tickets_action_after_submit_ticket_form'); ?>
		</div> <!-- /orbisius_support_tickets_submit_ticket_wrapper -->
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}