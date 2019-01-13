<?php

$notif_api = Orbisius_Support_Tickets_Module_Core_Notifications::getInstance();

add_action('init', [ $notif_api, 'init' ] ) ;

class Orbisius_Support_Tickets_Module_Core_Notifications {
	public function init() {
		//add_action( 'orbisius_support_tickets_admin_action_register_settings', [ $this, 'notifyOnNewTicket' ] );
		add_action( 'orbisius_support_tickets_action_before_submit_ticket_after_insert', [ $this, 'notifyOnNewTicket' ] );
	}

	/**
	 * @param array $ctx
	 */
	public function notifyOnNewTicket($ctx = []) {
		if (empty($ctx['ticket_id'])) {
		    return;
        }

		$admin_api = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();
		$notif_key_in_opts = $admin_api->getPluginSettingsNotificationKey();
		$notif_opts = $admin_api->getOptions($notif_key_in_opts);

		if (empty($notif_opts['new_ticket_notification_enabled'])) {
		    return;
        }

		if (!empty($ctx['author_id'])) {
			$user_id = $ctx['author_id'];

			$user_obj = get_user_by('id', $user_id);

			if (empty($user_obj)) { // user not found.
				return;
			}

			$email = $user_obj->user_email;
		} elseif (!empty($ctx['recipient_email'])) {
			$email = $ctx['recipient_email'];
		} else {
			return '';
		}

		// 1 email to user
		$subject = $notif_opts['new_ticket_subject'];
		$message = $notif_opts['new_ticket_message'];

		$subject = do_shortcode($subject);
		$message = do_shortcode($message);

		$vars = [
			'ticket_id' => $ctx['ticket_id'],
		];

		$subject = Orbisius_Support_Tickets_String_Util::replaceVars($subject, $vars);
		$message = Orbisius_Support_Tickets_String_Util::replaceVars($message, $vars);

		$mail_sent_status = wp_mail($email, $subject, $message);

		if (empty($notif_opts['support_email_receiver'])) {
			return;
		}

		// 1 email to admin or just BCC?
//		$subject = $notif_opts['new_ticket_subject'];
//		$message = $notif_opts['new_ticket_message'];

	}

	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar Orbisius_Support_Tickets_Module_Core_Notifications $instance
	 * @return Orbisius_Support_Tickets_Module_Core_Notifications
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