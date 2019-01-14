<?php

$notif_api = Orbisius_Support_Tickets_Module_Core_Notifications::getInstance();

add_action('init', array( $notif_api, 'init' ) ) ;

class Orbisius_Support_Tickets_Module_Core_Notifications {
	public function init() {
		//add_action( 'orbisius_support_tickets_admin_action_register_settings', array(  $this, 'notifyOnNewTicket' ) );
		add_action( 'orbisius_support_tickets_action_before_submit_ticket_after_insert', array(  $this, 'notifyOnNewTicket' ) );
		add_action( 'orbisius_support_tickets_action_ticket_activity', array( $this, 'notifyOnTicketActivity' ) );
	}

	/**
	 * @param array $ctx
	 */
	public function notifyOnNewTicket($ctx = array()) {
		if (empty($ctx['ticket_id'])) {
		    return;
        }

		$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$ticket_obj = $cpt_obj->getTicket($ctx['ticket_id']);

		if (empty($ticket_obj->ID)) {
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

		$recipient_email = $email;

		$host = 'localhost';

		if (!empty($_SERVER['SERVER_NAME'])) {
			$host = $_SERVER['SERVER_NAME'];
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
		} elseif (function_exists('shell_exec')) {
			$host = shell_exec('hostname');
		}

		$host = wp_strip_all_tags($host);
		$host = str_replace('www.', '', $host);
		$host = trim($host);

		$shortcode_api = Orbisius_Support_Tickets_Module_Core_Shortcodes::getInstance();

		$vars = array(
			'domain' => $host,
			'site_url' => site_url('/'),
			'site_name' => get_bloginfo('name'),
			'ticket_id' => $ctx['ticket_id'],
			'ticket_url' => $shortcode_api->generateViewTicketLink( array( 'ticket_id' => $ctx['ticket_id'] ) ),
			'recipient_email' => $email,
		);

		$vars = apply_filters( 'orbisius_support_tickets_filter_notification_replace_vars', $vars, $ctx );

		// subject
		$subject = $notif_opts['new_ticket_subject'];
		$subject = do_shortcode($subject);
		$subject = Orbisius_Support_Tickets_String_Util::replaceVars($subject, $vars);

		// message
		$message = $notif_opts['new_ticket_message'];
		$message = do_shortcode($message);
		$message = Orbisius_Support_Tickets_String_Util::replaceVars($message, $vars);
		//$message = nl2br($message);
		$headers = array();

		$from_name = empty($notif_opts['support_from_name']) ? get_bloginfo('name') . ' Support' : $notif_opts['support_from_name'];
		$from_email = empty($notif_opts['support_from_email']) ? 'mailer@' . $host : $notif_opts['support_from_email'];
		$from_full_email = "'$from_name' <$from_email>";
		$from_full_email = Orbisius_Support_Tickets_String_Util::replaceVars($from_full_email, $vars);
		$headers[] = "From: $from_full_email";

		$reply_to_full_email = '';

		if (!empty($notif_opts['support_email_reply_to'])) {
			$reply_to_full_email = $notif_opts['support_email_reply_to'];
		} elseif (!empty($notif_opts['support_email_receiver'])) {
			$reply_to_full_email = $notif_opts['support_email_receiver'];
		}

		if (!empty($reply_to_full_email)) {
			$reply_to_full_email = Orbisius_Support_Tickets_String_Util::replaceVars($reply_to_full_email, $vars);
			$headers[] = "Reply-to: $reply_to_full_email";
		}

		$headers = apply_filters( 'orbisius_support_tickets_filter_notification_email_headers', $headers, $ctx );

		$attachments = array();
		$attachments = apply_filters( 'orbisius_support_tickets_filter_notification_email_attachments', $attachments, $ctx );

		// For now let's BCC the admin
		if (!empty($notif_opts['support_email_receiver'])) {
			$headers[] = "Bcc: " . $notif_opts['support_email_receiver'];
		}

		$mail_sent_status = wp_mail($recipient_email, $subject, $message, $headers, $attachments);

		// @todo 1 email to admin or just BCC?
//		$subject = $notif_opts['new_ticket_subject_admin'];
//		$message = $notif_opts['new_ticket_message_admin'];
	}

	/**
	 * Notifies the other party about the ticket activity. Admin posts -> notify client. Client posts -> notify admin
	 * @param array $ctx
	 */
	public function notifyOnTicketActivity($ctx = array()) {
		if (empty($ctx['ticket_id'])) {
		    return;
        }

		$cpt_obj = Orbisius_Support_Tickets_Module_Core_CPT::getInstance();
		$ticket_obj = $cpt_obj->getTicket($ctx['ticket_id']);

		if (empty($ticket_obj->ID)) {
			return;
		}

		$admin_api = Orbisius_Support_Tickets_Module_Core_Admin::getInstance();
		$notif_key_in_opts = $admin_api->getPluginSettingsNotificationKey();
		$notif_opts = $admin_api->getOptions($notif_key_in_opts);

		if (empty($notif_opts['ticket_activity_notification_enabled'])) {
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

		$host = 'localhost';

		if (!empty($_SERVER['SERVER_NAME'])) {
			$host = $_SERVER['SERVER_NAME'];
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
		} elseif (function_exists('shell_exec')) {
			$host = shell_exec('hostname');
		}

		$host = wp_strip_all_tags($host);
		$host = str_replace('www.', '', $host);
		$host = trim($host);

		$user_api = Orbisius_Support_Tickets_User::getInstance();
		$recipient_email = $notif_opts['support_email_receiver'];

		// support posts a reply -> notify client
		if ($user_api->isEditor($ctx['author_id'])) {
			if (!empty($ticket_obj->post_author)) {
				$client_obj = $user_api->getUser( $ticket_obj->post_author ); // Find who created the ticket.

				if ( ! empty( $client_obj->user_email ) ) {
					$recipient_email = $client_obj->user_email;
				}
			}
		}

		$shortcode_api = Orbisius_Support_Tickets_Module_Core_Shortcodes::getInstance();

		$vars = array(
			'domain' => $host,
			'site_url' => site_url('/'),
			'site_name' => get_bloginfo('name'),
			'ticket_id' => $ctx['ticket_id'],
			'ticket_url' => $shortcode_api->generateViewTicketLink( array( 'ticket_id' => $ctx['ticket_id'] ) ),
			'recipient_email' => $recipient_email,
		);

		$vars = apply_filters( 'orbisius_support_tickets_filter_notification_replace_vars', $vars, $ctx );

		// subject
		$subject = $notif_opts['ticket_activity_subject'];
		$subject = do_shortcode($subject);
		$subject = Orbisius_Support_Tickets_String_Util::replaceVars($subject, $vars);

		// message
		$message = $notif_opts['ticket_activity_message'];
		$message = do_shortcode($message);
		$message = Orbisius_Support_Tickets_String_Util::replaceVars($message, $vars);
		//$message = nl2br($message);
		$headers = array();

		$from_name = empty($notif_opts['support_from_name']) ? get_bloginfo('name') . ' Support' : $notif_opts['support_from_name'];
		$from_email = empty($notif_opts['support_from_email']) ? 'mailer@' . $host : $notif_opts['support_from_email'];
		$from_full_email = "'$from_name' <$from_email>";
		$from_full_email = Orbisius_Support_Tickets_String_Util::replaceVars($from_full_email, $vars);
		$headers[] = "From: $from_full_email";

		$reply_to_full_email = '';

		if (!empty($notif_opts['support_email_reply_to'])) {
			$reply_to_full_email = $notif_opts['support_email_reply_to'];
		} elseif (!empty($notif_opts['support_email_receiver'])) {
			$reply_to_full_email = $notif_opts['support_email_receiver'];
		}

		if (!empty($reply_to_full_email)) {
			$reply_to_full_email = Orbisius_Support_Tickets_String_Util::replaceVars($reply_to_full_email, $vars);
			$headers[] = "Reply-to: $reply_to_full_email";
		}

		$headers = apply_filters( 'orbisius_support_tickets_filter_notification_email_headers', $headers, $ctx );

		$attachments = array();
		$attachments = apply_filters( 'orbisius_support_tickets_filter_notification_email_attachments', $attachments, $ctx );

		$mail_sent_status = wp_mail($recipient_email, $subject, $message, $headers, $attachments);

		if (empty($notif_opts['support_email_receiver'])) {
			return;
		}
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