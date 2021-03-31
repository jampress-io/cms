<?php

/**
 * Forminator_Quiz_Front_Mail
 *
 * @since 1.6.2
 */
class Forminator_Quiz_Front_Mail extends Forminator_Mail {

	protected $message_vars;

	/**
	 * Default content type
	 *
	 * @var string
	 */
	protected $content_type = 'text/html; charset=UTF-8';

	/**
	 * Initialize the mail
	 *
	 * @param array $post_vars - post variables
	 */
	public function init( $post_vars ) {
		$user_email  = false;
		$user_name   = '';
		$user_login  = '';
		$embed_id    = $post_vars['page_id'];
		$embed_title = get_the_title( $embed_id );
		$embed_url   = forminator_get_current_url();
		$site_url    = site_url();

		//Check if user is logged in
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_email   = $current_user->user_email;
			if ( ! empty( $current_user->user_firstname ) ) {
				$user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			} elseif ( ! empty( $current_user->display_name ) ) {
				$user_name = $current_user->display_name;
			} else {
				$user_name = $current_user->display_name;
			}
			$user_login = $current_user->user_login;
		}

		//Set up mail variables
		$message_vars       = forminator_set_message_vars( $embed_id, $embed_title, $embed_url, $user_name, $user_email, $user_login, $site_url );
		$this->message_vars = $message_vars;

	}

	/**
	 * Process mail
	 *
	 * @since 1.6.2
	 *
	 * @param Forminator_Quiz_Form_Model  $quiz
	 * @param array                       $data
	 * @param Forminator_Form_Entry_Model $entry
	 */
	public function process_mail( $quiz, $data, Forminator_Form_Entry_Model $entry, $final_res = array() ) {
		forminator_maybe_log( __METHOD__ );

		$setting       = $quiz->settings;
		$notifications = $quiz->notifications;
		$lead_model    = null;
		$result_slug   = isset( $final_res['slug'] ) ? $final_res['slug'] : '';

		if ( ! isset( $data['current_url'] ) || empty( $data['current_url'] ) ) {
			$data['current_url'] = forminator_get_current_url();
		}

		$has_lead = isset( $setting['hasLeads'] ) ? $setting['hasLeads'] : false;
		if ( $has_lead ) {
			$lead_id     = isset( $setting['leadsId'] ) ? $setting['leadsId'] : 0;
			$lead_model  = Forminator_Custom_Form_Model::model()->load( $lead_id );
			$form_fields = forminator_addon_format_form_fields( $lead_model );
			$lead_data   = forminator_addons_lead_submitted_data( $form_fields, $entry );
			$data        = array_merge( $data, $lead_data );
			$files       = $this->get_lead_file_attachment( $lead_model, $data, $entry );
			foreach ( $data as $element => $element_value ) {
				if ( ! empty( $element_value ) && is_array( $element_value ) &&
				     ( stripos( $element, 'time-' ) !== false || stripos( $element, 'date-' ) !== false ) ) {
					foreach ( $element_value as $key => $value ) {
						$key_value               = $element . '-' . $key;
						$data[ $key_value ] = $value;
					}
				}
			}
		}

		/**
		 * Message data filter
		 *
		 * @since 1.6.2
		 *
		 * @param array                       $data - the post data
		 * @param Forminator_Quiz_Form_Model  $quiz - the quiz model
		 * @param Forminator_Form_Entry_Model $entry
		 *
		 *
		 * @return array $data
		 */
		$data = apply_filters( 'forminator_quiz_mail_data', $data, $quiz, $entry );

		/**
		 * Action called before mail is sent
		 *
		 * @param Forminator_Quiz_Front_Mail  $this - the current mail class
		 * @param Forminator_Quiz_Form_Model  $quiz - the current quiz
		 * @param array                       $data - current data
		 * @param Forminator_Form_Entry_Model $entry
		 */
		do_action( 'forminator_quiz_mail_before_send_mail', $this, $quiz, $data, $entry );

		if ( ! empty( $notifications ) ) {
			$this->init( $_POST ); // WPCS: CSRF OK
			//Process admin mail
			foreach ( $notifications as $notification ) {

				if ( $this->is_quiz_condition( $notification, $data, $quiz, $result_slug ) ) {
					continue;
				}

				$recipients = $this->get_admin_email_recipients( $notification, $data, $quiz, $entry, $lead_model );

				if ( ! empty( $recipients ) ) {
					$subject = '';
					$message = '';
					if ( isset( $notification['email-subject'] ) ) {
						$subject = forminator_replace_variables( $notification['email-subject'], $quiz->id, $data['current_url'] );
						$subject = forminator_replace_quiz_form_data( $subject, $quiz, $data, $entry );
						if ( $has_lead ) {
							$subject = forminator_replace_form_data( $subject, $data, $lead_model, $entry );
							$subject = forminator_replace_custom_form_data( $subject, $lead_model, $data, $entry );
						}
					}
					/**
					 * Quiz admin mail subject filter
					 *
					 * @since 1.6.2
					 *
					 * @param string                     $subject
					 * @param Forminator_Quiz_Form_Model $quiz the current quiz modal
					 *
					 * @return string $subject
					 */
					$subject = apply_filters( 'forminator_quiz_mail_admin_subject', $subject, $quiz, $data, $entry, $this );

					if ( isset( $notification['email-editor'] ) ) {
						$message = forminator_replace_variables( $notification['email-editor'], $quiz->id, $data['current_url'] );
						$message = forminator_replace_quiz_form_data( $message, $quiz, $data, $entry );
						if ( $has_lead ) {
							$message = forminator_replace_form_data( $message, $data, $lead_model, $entry );
							$message = forminator_replace_custom_form_data( $message, $lead_model, $data, $entry );
						}
					}
					/**
					 * Quiz admin mail message filter
					 *
					 * @since 1.6.2
					 *
					 * @param string                     $message
					 * @param Forminator_Quiz_Form_Model $quiz the current quiz
					 * @param array                      $data
					 * @param Forminator_Quiz_Front_Mail $this
					 *
					 * @return string $message
					 */
					$message = apply_filters( 'forminator_quiz_mail_admin_message', $message, $quiz, $data, $entry, $this );

					$from_name = $this->sender_name;
					if ( isset( $notification['from-name'] ) && ! empty( $notification['from-name'] ) ) {
						$notification_from_name = $notification['from-name'];
						$notification_from_name = forminator_replace_variables( $notification_from_name, $quiz->id, $data['current_url'] );
						$notification_from_name = forminator_replace_quiz_form_data( $notification_from_name, $quiz, $data, $entry );
						if ( $has_lead ) {
							$notification_from_name = forminator_replace_form_data( $notification_from_name, $data, $lead_model, $entry );
							$notification_from_name = forminator_replace_custom_form_data( $notification_from_name, $lead_model, $data, $entry );
						}

						if ( ! empty( $notification_from_name ) ) {
							$from_name = $notification_from_name;
						}
					}
					/**
					 * Filter `From` name of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param string                      $from_name
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$from_name = apply_filters( 'forminator_quiz_mail_admin_from_name', $from_name, $quiz, $data, $entry, $this );

					$from_email = $this->sender_email;

					if ( isset( $notification['form-email'] ) && ! empty( $notification['form-email'] ) ) {
						$notification_from_address = $notification['form-email'];
						$notification_from_address = forminator_replace_variables( $notification_from_address, $quiz->id, $data['current_url'] );
						$notification_from_address = forminator_replace_quiz_form_data( $notification_from_address, $quiz, $data, $entry );
						if ( $has_lead ) {
							$notification_from_address = forminator_replace_form_data( $notification_from_address, $data, $lead_model, $entry );
							$notification_from_address = forminator_replace_custom_form_data( $notification_from_address, $lead_model, $data, $entry );
						}

						if ( is_email( $notification_from_address ) ) {
							$from_email = $notification_from_address;
						}
					}
					/**
					 * Filter `From` email address of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param string                      $from_email
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$from_email = apply_filters( 'forminator_quiz_mail_admin_from_email', $from_email, $quiz, $data, $entry, $this );

					$reply_to_address = '';
					if ( isset( $notification['replyto-email'] ) && ! empty( $notification['replyto-email'] ) ) {
						$notification_reply_to_address = $notification['replyto-email'];
						$notification_reply_to_address = forminator_replace_variables( $notification_reply_to_address, $quiz->id, $data['current_url'] );
						$notification_reply_to_address = forminator_replace_quiz_form_data( $notification_reply_to_address, $quiz, $data, $entry );
						if ( $has_lead ) {
							$notification_reply_to_address = forminator_replace_form_data( $notification_reply_to_address, $data, $lead_model, $entry );
							$notification_reply_to_address = forminator_replace_custom_form_data( $notification_reply_to_address, $lead_model, $data, $entry );
						}

						if ( is_email( $notification_reply_to_address ) ) {
							$reply_to_address = $notification_reply_to_address;
						}
					}

					/**
					 * Filter `Reply To` email address of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param string                      $reply_to_address
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$reply_to_address = apply_filters( 'forminator_quiz_mail_admin_reply_to', $reply_to_address, $quiz, $data, $entry, $this );

					$cc_addresses = array();
					if ( isset( $notification['cc-email'] ) && ! empty( $notification['cc-email'] ) ) {
						$notification_cc_addresses = array_map( 'trim', explode( ',', $notification['cc-email'] ) );
						foreach ( $notification_cc_addresses as $key => $notification_cc_address ) {
							$notification_cc_address = forminator_replace_variables( $notification_cc_address, $quiz->id, $data['current_url'] );
							$notification_cc_address = forminator_replace_quiz_form_data( $notification_cc_address, $quiz, $data, $entry );
							if ( $has_lead ) {
								$notification_cc_address = forminator_replace_form_data( $notification_cc_address, $data, $lead_model, $entry );
								$notification_cc_address = forminator_replace_custom_form_data( $notification_cc_address, $lead_model, $data, $entry );
							}

							if ( is_email( $notification_cc_address ) ) {
								$cc_addresses[] = $notification_cc_address;
							}
						}

					}
					/**
					 * Filter `CC` email addresses of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param array                       $cc_addresses
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$cc_addresses = apply_filters( 'forminator_quiz_mail_admin_cc_addresses', $cc_addresses, $quiz, $data, $entry, $this );

					$bcc_addresses = array();
					if ( isset( $notification['bcc-email'] ) && ! empty( $notification['bcc-email'] ) ) {
						$notification_bcc_addresses = array_map( 'trim', explode( ',', $notification['bcc-email'] ) );

						foreach ( $notification_bcc_addresses as $key => $notification_bcc_address ) {
							$notification_bcc_address = forminator_replace_variables( $notification_bcc_address, $quiz->id, $data['current_url'] );
							$notification_bcc_address = forminator_replace_quiz_form_data( $notification_bcc_address, $quiz, $data, $entry );
							if ( $has_lead ) {
								$notification_bcc_address = forminator_replace_form_data( $notification_bcc_address, $data, $lead_model, $entry );
								$notification_bcc_address = forminator_replace_custom_form_data( $notification_bcc_address, $lead_model, $data, $entry );
							}
							if ( is_email( $notification_bcc_address ) ) {
								$bcc_addresses[] = $notification_bcc_address;
							}
						}
					}
					/**
					 * Filter `BCC` email addresses of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param array                       $bcc_addresses
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$bcc_addresses = apply_filters( 'forminator_quiz_mail_admin_bcc_addresses', $bcc_addresses, $quiz, $data, $entry, $this );

					$content_type = $this->content_type;
					/**
					 * Filter `Content-Type` of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param string                      $content_type
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$content_type = apply_filters( 'forminator_quiz_mail_admin_content_type', $content_type, $quiz, $data, $entry, $this );


					$headers = array();

					// only change From header if these two are valid
					if ( ! empty( $from_name ) && ! empty( $from_email ) ) {
						$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
					}

					if ( ! empty( $reply_to_address ) ) {
						$headers[] = 'Reply-To: ' . $reply_to_address;
					}

					if ( ! empty( $cc_addresses ) && is_array( $cc_addresses ) ) {
						$headers[] = 'Cc: ' . implode( ', ', $cc_addresses );
					}

					if ( ! empty( $bcc_addresses ) && is_array( $bcc_addresses ) ) {
						$headers[] = 'BCc: ' . implode( ', ', $bcc_addresses );
					}

					if ( ! empty( $content_type ) ) {
						$headers[] = 'Content-Type: ' . $content_type;
					}

					/**
					 * Filter headers of mail that send to admin
					 *
					 * @since 1.6.2
					 *
					 * @param array                       $headers
					 * @param Forminator_Quiz_Form_Model  $quiz  current quiz Model
					 * @param array                       $data  POST data
					 * @param Forminator_Form_Entry_Model $entry entry model
					 * @param Forminator_Quiz_Front_Mail  $this  mail class
					 */
					$headers = apply_filters( 'forminator_quiz_mail_admin_headers', $headers, $quiz, $data, $entry, $this );

					$this->set_headers( $headers );

					$this->set_subject( $subject );
					$this->set_recipients( $recipients );
					$this->set_message_with_vars( $this->message_vars, $message );
					if ( ! empty( $files ) && isset( $notification['email-attachment'] ) && 'true' === $notification['email-attachment'] ) {
						$this->set_attachment( $files );
					}
					$this->send_multiple();

					/**
					 * Action called after admin mail sent
					 *
					 * @param Forminator_Quiz_Front_Mail  $this       the mail class
					 * @param Forminator_Quiz_Form_Model  $quiz       the current quiz
					 * @param array                       $data       - current data
					 * @param Forminator_Form_Entry_Model $entry      - saved entry
					 * @param array                       $recipients - array or recipients
					 */
					do_action( 'forminator_quiz_mail_admin_sent', $this, $quiz, $data, $entry, $recipients );
				}
			}
		}

		/**
		 * Action called after mail is sent
		 *
		 * @param Forminator_Quiz_Front_Mail $this mail class
		 * @param Forminator_Quiz_Form_Model $quiz current quiz
		 * @param array                      $data current data
		 */
		do_action( 'forminator_quiz_mail_after_send_mail', $this, $quiz, $data );
	}

	/**
	 * Check if all conditions are met to send admin email
	 *
	 * @since 1.6.2
	 *
	 * @param array $setting - the quiz settings
	 *
	 * @return bool
	 */
	public function is_send_admin_mail( $setting ) {
		if ( isset( $setting['use-admin-email'] ) && ! empty( $setting['use-admin-email'] ) ) {
			if ( filter_var( $setting['use-admin-email'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( isset( $setting['admin-email-title'] ) && isset( $setting['admin-email-editor'] ) ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Get Recipients of admin emails
	 *
	 * @since 1.6.2
	 *
	 * @param array                       $notification
	 * @param array                       $data
	 * @param Forminator_Quiz_Form_Model  $quiz
	 * @param Forminator_Form_Entry_Model $entry
	 * @param                             $lead_model
	 *
	 * @return array
	 */
	public function get_admin_email_recipients( $notification, $data, $quiz, $entry, $lead_model ) {
		$email = array();
		if ( isset( $notification['email-recipients'] ) && 'routing' === $notification['email-recipients'] ) {
			if ( ! empty( $notification['routing'] ) ) {
				foreach ( $notification['routing'] as $routing ) {
					if ( $this->is_quiz_routing( $routing, $data, $quiz ) ) {
						$recipients = array_map( 'trim', explode( ',', $routing['email'] ) );
						if ( ! empty( $recipients ) ) {
							foreach ( $recipients as $key => $recipient ) {
								$recipient = forminator_replace_variables( $recipient, $quiz->id, $data['current_url'] );
								$recipient = forminator_replace_quiz_form_data( $recipient, $quiz, $data, $entry );
								if ( isset( $quiz->settings['hasLeads'] ) && $quiz->settings['hasLeads'] ) {
									$recipient        = forminator_replace_form_data( $recipient, $data, $lead_model, $entry );
									$recipient        = forminator_replace_custom_form_data( $recipient, $lead_model, $data, $entry );
								}
								if ( is_email( $recipient ) ) {
									$email[] = $recipient;
								}
							}
						}
					}
				}
			}
		} else if ( isset( $notification['recipients'] ) && ! empty( $notification['recipients'] ) ) {
			$recipients = array_map( 'trim', explode( ',', $notification['recipients'] ) );
			if ( ! empty( $recipients ) ) {
				foreach ( $recipients as $key => $recipient ) {
					$recipient = forminator_replace_variables( $recipient, $quiz->id, $data['current_url'] );
					$recipient = forminator_replace_quiz_form_data( $recipient, $quiz, $data, $entry );
					if ( isset( $quiz->settings['hasLeads'] ) && $quiz->settings['hasLeads'] ) {
						$recipient        = forminator_replace_form_data( $recipient, $data, $lead_model, $entry );
						$recipient        = forminator_replace_custom_form_data( $recipient, $lead_model, $data, $entry );
					}
					if ( is_email( $recipient ) ) {
						$email[] = $recipient;
					}
				}
			}
		}

		return apply_filters( 'forminator_quiz_get_admin_email_recipients', $email, $notification, $data, $quiz, $entry );
	}

	/**
	 * Lead file attachment
	 *
	 * @param $lead_model
	 * @param $data
	 * @param $entry
	 *
	 * @return array|mixed
	 */
	public function get_lead_file_attachment( $lead_model, $data, $entry ) {
		$files                 = array();
		$form_fields           = $lead_model->get_fields();
		$pseudo_submitted_data = Forminator_CForm_Front_Action::get_instance()->build_pseudo_submitted_data( $lead_model, $data );
		foreach ( $form_fields as $form_field ) {
			$field_array    = $form_field->to_formatted_array();
			$field_forms    = forminator_fields_to_array();
			$field_type     = $field_array['type'];
			$form_field_obj = $field_forms[ $field_type ];
			if ( 'upload' === $field_type && ! $form_field_obj->is_hidden( $field_array, $data, $pseudo_submitted_data ) ) {
				$field_slug = isset( $entry->meta_data[ $form_field->slug ] ) ? $entry->meta_data[ $form_field->slug ] : '';
				if ( ! empty( $field_slug ) && ! empty( $field_slug['value']['file'] ) ) {
					$email_files = isset( $field_slug['value']['file'] ) ? $field_slug['value']['file']['file_path'] : array();
					$files[]     = is_array( $email_files ) ? $email_files : array( $email_files );
				}
			}
		}
		if ( ! empty( $files ) ) {
			$files = call_user_func_array( 'array_merge', $files );
		}

		return $files;
	}
}
