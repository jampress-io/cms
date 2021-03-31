<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Mail
 *
 * Handle mail sending
 *
 * @since 1.0
 */
abstract class Forminator_Mail {

	/**
	 * Mail recipient
	 * The email to receive the mail
	 *
	 * @var string
	 */
	protected $recipient = '';

	/**
	 * Mail recipients
	 * The emails to receive the mail
	 *
	 * @var array
	 */
	protected $recipients = array();

	/**
	 * Mail message
	 *
	 * @var string
	 */
	protected $message = '';

	/**
	 * Mail subject
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * Mail from email
	 *
	 * @var string
	 */
	protected $sender_email = '';

	/**
	 * Mail from name
	 *
	 * @var string
	 */
	protected $sender_name = '';

	/**
	 * Mail headers
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Mail headers
	 *
	 * @var array
	 */
	protected $attachment = array();

	/**
	 * Main constructor
	 *
	 * @since 1.0
	 *
	 * @param string $recipient - mail recipient email
	 * @param string $message   - mail message
	 * @param string $subject   - mail subject
	 */
	public function __construct( $recipient = '', $message = '', $subject = '' ) {
		if ( ! empty( $recipient ) && filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
			$this->recipient = $recipient;
		}
		if ( ! empty( $message ) ) {
			$this->message = $message;
		}
		if ( ! empty( $subject ) ) {
			$this->subject = $subject;
		}
		$this->sender_email = get_global_sender_email_address();
		$this->sender_name  = get_global_sender_name();
		$this->set_headers();
	}

	/**
	 * Set recipeint
	 *
	 * @since 1.0
	 *
	 * @param string $recipient - recipient email
	 */
	public function set_recipient( $recipient ) {
		if ( filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
			$this->recipient = $recipient;
		}
	}


	/**
	 * Set Recipients as array
	 *
	 * @since 1.0.3
	 *
	 * @param array $recipients
	 */
	public function set_recipients( $recipients ) {
		$this->recipients = array();
		if ( ! empty( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				if ( filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
					$this->recipients[] = $recipient;
				}
			}
		}
	}

	/**
	 * Set message
	 *
	 * @since 1.0
	 *
	 * @param string $message - the mail message
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}

	/**
	 * Set message with vars
	 *
	 * @since 1.0
	 *
	 * @param array  $message_vars - the mail message array variables
	 * @param string $message      - the mail message
	 */
	public function set_message_with_vars( $message_vars, $message ) {
		$this->message = str_replace(
			array_keys( $message_vars ),
			array_values( $message_vars ),
			stripslashes( $message )
		);
	}

	/**
	 * Set subject
	 *
	 * @since 1.0
	 *
	 * @param string $subject - the mail subject
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Set attachment
	 *
	 * @since 1.0
	 *
	 * @param array $attachment - the mail attachment
	 */
	public function set_attachment( $attachment ) {
		$this->attachment = $attachment;
	}

	/**
	 * Set headers
	 *
	 * @since 1.0
	 *
	 * @param array $headers - the mail headers
	 */
	public function set_headers( $headers = array() ) {
		if ( ! empty( $headers ) ) {
			$this->headers = $headers;
		} else {
			$this->headers   = array(
				'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
			);
			$this->headers[] = 'Content-Type: text/html; charset=UTF-8';
		}
	}

	/**
	 * Set sender details
	 *
	 * @since 1.0
	 *
	 * @param array $sender_details - the sender details
	 *                              ( 'email' => 'email', 'name' => 'name' )
	 */
	public function set_sender( $sender_details = array() ) {
		if ( ! empty( $sender_details ) ) {
			$this->sender_email = $sender_details['email'];
			$this->sender_name  = $sender_details['name'];
		}
	}

	/**
	 * Clean mail variables
	 *
	 * @since 1.0
	 */
	private function clean() {
		$subject       = stripslashes( $this->subject );
		$subject       = wp_strip_all_tags( $subject );
		$this->subject = $subject;

		$message       = stripslashes( $this->message );
		$message       = wpautop( $message );
		$message       = make_clickable( $message );
		$this->message = $message;
	}

	/**
	 * Get Forminator mailer headers
	 *
	 * @since 1.5
	 * @return array
	 */
	public function get_headers() {
		$headers = $this->headers;

		/**
		 * Filter headers that will be sent by Forminator Mailer
		 *
		 * @since 1.5
		 *
		 * @param array $headers
		 */
		$headers = apply_filters( 'forminator_mailer_headers', $headers );

		return $headers;
	}

	/**
	 * Send mail
	 *
	 * @since 1.0
	 * @since 1.5 use `get_headers`
	 * @return bool
	 */
	public function send() {
		$sent    = false;
		$headers = $this->get_headers();
		if ( ! empty( $this->recipient ) && ! empty( $this->subject ) && ! empty( $this->message ) ) {
			$this->clean();
			$sent = wp_mail( $this->recipient, $this->subject, $this->message, $headers );
		}

		return $sent;
	}

	/**
	 * Send mail for multiple recipients
	 *
	 * @since 1.0.3
	 * @since 1.5 use `get_headers`
	 *
	 * @return bool
	 */
	public function send_multiple() {
		$sent    = false;
		$headers = $this->get_headers();
		if ( ! empty( $this->recipients ) && ! empty( $this->subject ) && ! empty( $this->message ) ) {
			$this->clean();
			if ( $this->attachment ) {
				$sent = wp_mail( $this->recipients, $this->subject, $this->message, $headers, $this->attachment );
			} else {
				$sent = wp_mail( $this->recipients, $this->subject, $this->message, $headers );
			}
		}

		return $sent;
	}

	/**
	 * Check if notification is routing
	 *
	 * @since 1.0
	 *
	 * @param $routing
	 * @param $form_data
	 * @param $pseudo_submitted_data
	 *
	 * @return bool
	 */
	public function is_routing( $routing, $form_data, $pseudo_submitted_data = array() ) {

		// empty conditions
		if ( empty( $routing ) ) {
			return false;
		}

		$element_id = $routing['element_id'];
		if ( stripos( $element_id, 'signature-' ) !== false ) {
			// We have signature field
			$is_condition_fulfilled = false;
			$signature_id = 'field-' . $element_id;

			if ( isset( $form_data[ $signature_id ] ) ) {
				$signature_data = 'ctlSignature' . $form_data[ $signature_id ] . '_data';

				if ( isset( $form_data[ $signature_data ] ) ) {
					$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $signature_data ], $routing );
				}
			}
			return $is_condition_fulfilled;
		} elseif ( stripos( $element_id, 'calculation-' ) !== false || stripos( $element_id, 'stripe-' ) !== false ) {
			$is_condition_fulfilled = false;
			if ( isset( $pseudo_submitted_data[ $element_id ] ) ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $pseudo_submitted_data[ $element_id ], $routing );
			}
			return $is_condition_fulfilled;
		} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( ! isset( $form_data[ $element_id ] ) ) {
			return false;
		} else {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		}
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @since 1.0
	 * @since 1.7 add $pseudo_submitted_data to get value of calculation and stripe etc
	 *
	 * @param $notification
	 * @param $form_data
	 * @param $pseudo_submitted_data
	 *
	 * @return bool
	 */
	public function is_condition( $notification, $form_data, $pseudo_submitted_data = array(), $form_object = false ) {
		// empty conditions
		if ( empty( $notification['conditions'] ) ) {
			return false;
		}

		$condition_action = isset( $notification['condition_action'] ) ? $notification['condition_action'] : 'send';
		$condition_rule   = isset( $notification['condition_rule'] ) ? $notification['condition_rule'] : 'all';

		$condition_fulfilled = 0;

		$all_conditions = $notification['conditions'];

		foreach ( $all_conditions as $condition ) {
			$element_id = $condition['element_id'];

			if ( stripos( $element_id, 'signature-' ) !== false ) {
				// We have signature field
				$is_condition_fulfilled = false;
				$signature_id = 'field-' . $element_id;

				if ( isset( $form_data[ $signature_id ] ) ) {
 					$signature_data = 'ctlSignature' . $form_data[ $signature_id ] . '_data';

					if ( isset( $form_data[ $signature_data ] ) ) {
						$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $signature_data ], $condition );
					}
				}
			} elseif ( stripos( $element_id, 'calculation-' ) !== false || stripos( $element_id, 'stripe-' ) !== false ) {
				$is_condition_fulfilled = false;
				if ( isset( $pseudo_submitted_data[ $element_id ] ) ) {
					$is_condition_fulfilled = self::is_condition_fulfilled( $pseudo_submitted_data[ $element_id ], $condition );
				}
			} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( ! isset( $form_data[ $element_id ] ) ) {
				$is_condition_fulfilled = false;
			} else {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			}

			if ( $is_condition_fulfilled ) {
				$condition_fulfilled ++;
			}
		}

		//initialized as hidden
		if ( 'send' === $condition_action ) {
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return false;
			}

			return true;
		} else {
			//initialized as shown
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return true;
			}

			return false;
		}
	}

	/**
	 * Check if Form Field value fullfilled the condition
	 *
	 * @since 1.0
	 *
	 * @param $form_field_value
	 * @param $condition
	 *
	 * @return bool
	 */
	public static function is_condition_fulfilled( $form_field_value, $condition ) {
		switch ( $condition['rule'] ) {
			case 'is':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1
					return in_array( $condition['value'], $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
				if ( is_numeric( $condition['value'] ) ) {
					return ( (int) $form_field_value === (int) $condition['value'] );
				}

				return ( $form_field_value === $condition['value'] );
			case 'is_not':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1
					return ! in_array( $condition['value'], $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}

				return ( $form_field_value !== $condition['value'] );
			case 'is_great':
				if ( ! is_numeric( $condition['value'] ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value > $condition['value'];
			case 'is_less':
				if ( ! is_numeric( $condition['value'] ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value < $condition['value'];
			case 'contains':
				return ( stripos( $form_field_value, $condition['value'] ) === false ? false : true );
			case 'starts':
				return ( stripos( $form_field_value, $condition['value'] ) === 0 ? true : false );
			case 'ends':
				return ( stripos( $form_field_value, $condition['value'] ) === ( strlen( $form_field_value - 1 ) ) ? true : false );
			case 'is_correct':
				return $form_field_value ? true : false;
			case 'is_incorrect':
				return ! $form_field_value ? true : false;
			case 'is_final_result':
				return $form_field_value === $condition['element_id'];
			case 'is_not_final_result':
				return $form_field_value !== $condition['element_id'];
			default:
				return false;
		}
	}

	/**
	 * Check if notification is routing
	 *
	 * @since 1.0
	 *
	 * @param $routing
	 * @param $form_data
	 * @param $quiz_model
	 *
	 * @return bool
	 */
	public function is_quiz_routing( $routing, $form_data, $quiz_model ) {
		// empty conditions
		if ( empty( $routing ) ) {
			return false;
		}

		$element_id = $routing['element_id'];
		if ( stripos( $element_id, 'signature-' ) !== false ) {
			// We have signature field
			$signature_id = $element_id;
			$signature_data = '';
			if ( isset( $form_data[ $signature_id ] ) && isset( $form_data[ $signature_id ]['file']['file_url'] ) ) {
				$signature_data = $form_data[ $signature_id ]['file']['file_url'];
			}
			return self::is_condition_fulfilled( $signature_data, $routing );
		} elseif ( stripos( $element_id, 'url-' ) !== false ) {
			$parts = ! empty( $routing['value'] ) ? wp_parse_url( $routing['value'] ) : false;
			if ( false !== $parts ) {
				if ( ! isset( $parts['scheme'] ) ) {
					$routing['value'] = 'http://' . $routing['value'];
				}
			}
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( stripos( $element_id, 'question-' ) !== false ) {
			$is_correct  = self::is_correct_answer( $element_id, $form_data['answers'][ $element_id ], $quiz_model );
			return self::is_condition_fulfilled( $is_correct, $routing );
		} elseif ( 'final_result' === $element_id ) {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( ! isset( $form_data[ $element_id ] ) ) {
			return false;
		} else {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		}
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @since 1.0
	 * @since 1.7 add $pseudo_submitted_data to get value of calculation and stripe etc
	 *
	 * @param $notification
	 * @param $form_data
	 * @param $quiz_model
	 *
	 * @return bool
	 */
	public function is_quiz_condition( $notification, $form_data, $quiz_model, $result = '' ) {
		// empty conditions
		if ( empty( $notification['conditions'] ) ) {
			return false;
		}

		$condition_action = isset( $notification['condition_action'] ) ? $notification['condition_action'] : 'send';
		$condition_rule   = isset( $notification['condition_rule'] ) ? $notification['condition_rule'] : 'all';

		$condition_fulfilled = 0;

		$all_conditions = $notification['conditions'];

		foreach ( $all_conditions as $condition ) {
			$element_id = $condition['element_id'];

			if ( stripos( $element_id, 'signature-' ) !== false ) {
				// We have signature field
				$signature_id = $element_id;
				$signature_data = '';
				if ( isset( $form_data[ $signature_id ] ) && isset( $form_data[ $signature_id ]['file']['file_url'] ) ) {
					$signature_data = $form_data[ $signature_id ]['file']['file_url'];
				}
				$is_condition_fulfilled = self::is_condition_fulfilled( $signature_data, $condition );
			} elseif ( stripos( $element_id, 'url-' ) !== false ) {
				// We have signature field
				$parts = ! empty( $routing['value'] ) ? wp_parse_url( $condition['value'] ) : false;
				if ( false !== $parts ) {
					if ( ! isset( $parts['scheme'] ) ) {
						$condition['value'] = 'http://' . $condition['value'];
					}
				}
				$is_condition_fulfilled =  self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( stripos( $element_id, 'question-' ) !== false ) {
				$is_correct  = self::is_correct_answer( $element_id, $form_data['answers'][ $element_id ], $quiz_model );
				$is_condition_fulfilled = self::is_condition_fulfilled( $is_correct, $condition );
			} elseif ( stripos( $element_id, 'result-' ) !== false ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $result, $condition );
			} elseif ( 'final_result' === $element_id ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( ! isset( $form_data[ $element_id ] ) ) {
				$is_condition_fulfilled = false;
			} else {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			}

			if ( $is_condition_fulfilled ) {
				$condition_fulfilled ++;
			}
		}
		//initialized as hidden
		if ( 'send' === $condition_action ) {
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return false;
			}

			return true;
		} else {
			//initialized as shown
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return true;
			}

			return false;
		}
	}

	/**
	 * Check whether answer is correct for a question on Knowledge Quiz
	 *
	 * @since 1.6.2
	 *
	 * @param string $slug         question slug
	 * @param  int   $answer_index answer index
	 * @param  int   $quiz_model
	 *
	 * @return bool
	 */
	public static function is_correct_answer( $slug, $answer_index, $quiz_model ) {
		if ( ! empty( $quiz_model->questions ) ) {
			foreach ( $quiz_model->questions as $question ) {
				if ( isset( $question['slug'] ) && $question['slug'] === $slug ) {
					$answers = $question['answers'];
					foreach ( $answers as $k => $answer ) {
						if ( isset( $answer['toggle'] ) && filter_var( $answer['toggle'], FILTER_VALIDATE_BOOLEAN ) === true ) {
							if ( (int) $answer_index === (int) $k ) {
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}
}
