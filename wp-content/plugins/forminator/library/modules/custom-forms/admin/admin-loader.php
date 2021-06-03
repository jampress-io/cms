<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Custom_Form_Admin
 *
 * @property Forminator_Custom_Forms module
 * @since 1.0
 */
class Forminator_Custom_Form_Admin extends Forminator_Admin_Module {

	/**
	 * Init module admin
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->module       = Forminator_Custom_Forms::get_instance();
		$this->page         = 'forminator-cform';
		$this->page_edit    = 'forminator-cform-wizard';
		$this->page_entries = 'forminator-cform-view';
		$this->dir          = dirname( __FILE__ );
	}

	/**
	 * Add module pages to Admin
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {
		new Forminator_CForm_Page( $this->page, 'custom-form/list', __( 'Forms', 'forminator' ), __( 'Forms', 'forminator' ), 'forminator' );
		new Forminator_CForm_New_Page( $this->page_edit, 'custom-form/wizard', __( 'Edit Form', 'forminator' ), __( 'New Custom Form', 'forminator' ), 'forminator' );
		new Forminator_CForm_View_Page( $this->page_entries, 'custom-form/entries', __( 'Submissions:', 'forminator' ), __( 'View Custom Form', 'forminator' ), 'forminator' );
	}

	/**
	 * Pass module defaults to JS
	 *
	 * @since 1.0
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		$model = null;
		if ( $this->is_admin_wizard() ) {
			$data['application'] = 'builder';

			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null;

			if ( ! is_null( $id ) ) {
				$data['formNonce'] = wp_create_nonce( 'forminator_save_builder_fields' );
				$model             = Forminator_Form_Model::model()->load( $id );
			}

			$wrappers = array();
			if ( is_object( $model ) ) {
				$wrappers = $model->get_fields_grouped();
			}

			// Load stored record
			$settings = apply_filters( 'forminator_form_settings', $this->get_form_settings( $model ), $model, $data, $this );

			if ( isset( $model->settings['form-type'] ) && 'registration' === $model->settings['form-type'] ) {
				$notifications = $this->get_registration_form_notifications( $model );
			} else {
				$notifications = $this->get_form_notifications( $model );
			}

			$form_id     = isset( $model->id ) ? $model->id : 0;
			$form_name   = isset( $model->name ) ? $model->name : '';
			$form_status = isset( $model->status ) ? $model->status : 'draft';

			$notifications = apply_filters( 'forminator_form_notifications', $notifications, $model, $data, $this );
			$data['currentForm'] = array(
				'wrappers'      => $wrappers,
				'settings'      => array_merge(
					array(
						'pagination-header' => 'nav',
						'paginationData'    => array(
							'pagination-header-design' => 'show',
							'pagination-header'        => 'nav',
						),
					),
					$settings,
					array(
						'form_id'     => $form_id,
						'form_name'   => $form_name,
						'form_status' => $form_status,
					)
				),
				'notifications' => $notifications,
			);
		}

		$data['modules']['custom_form'] = array(
			'templates'     => $this->module->get_templates(),
			'new_form_url'  => menu_page_url( $this->page_edit, false ),
			'form_list_url' => menu_page_url( $this->page, false ),
			'preview_nonce' => wp_create_nonce( 'forminator_popup_preview_form' ),
		);

		return apply_filters( 'forminator_form_admin_data', $data, $model, $this );
	}

	/**
	 * Localize module
	 *
	 * @since 1.0
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_l10n_strings( $data ) {
		$data['custom_form'] = array(
			'popup_label' => __( 'Choose Form Type', 'forminator' ),
		);

		$data['builder'] = array(
			"save" => __( "Save", 'forminator' ),
		);

		$data['product'] = array(
			"add_variations" => __( "Add some variations of your product.", 'forminator' ),
			"use_list"       => __( "Display in list?", 'forminator' ),
			"add_variation"  => __( "Add Variation", 'forminator' ),
			"image"          => __( "Image", 'forminator' ),
			"name"           => __( "Name", 'forminator' ),
			"price"          => __( "Price", 'forminator' ),
		);

		$data['appearance'] = array(
			"customize_typography"        => __( "Customize typography", 'forminator' ),
			"custom_font_family"          => __( "Enter custom font family name", 'forminator' ),
			"custom_font_placeholder"     => __( "E.g. 'Arial', sans-serif", 'forminator' ),
			"custom_font_description"     => __( "Type the font family name, as you would in CSS", 'forminator' ),
			"font_family"                 => __( "Font family", 'forminator' ),
			"font_size"                   => __( "Font size", 'forminator' ),
			"font_weight"                 => __( "Font weight", 'forminator' ),
			"select_font"                 => __( "Select font", 'forminator' ),
			"custom_font"                 => __( "Custom user font", 'forminator' ),
			"minutes"                     => __( "minute(s)", 'forminator' ),
			"hours"                       => __( "hour(s)", 'forminator' ),
			"days"                        => __( "day(s)", 'forminator' ),
			"weeks"                       => __( "week(s)", 'forminator' ),
			"months"                      => __( "month(s)", 'forminator' ),
			"years"                       => __( "year(s)", 'forminator' ),
		);

		$data['tab_appearance'] = array(
			"basic_selectors"                => __( "Basic selectors", 'forminator' ),
			"advanced_selectors"             => __( "Advanced selectors", 'forminator' ),
			"pagination_selectors"           => __( "Pagination selectors", 'forminator' ),
		);

		return $data;
	}

	/**
	 * Return template
	 *
	 * @since 1.0
	 * @return Forminator_Template|false
	 */
	private function get_template() {
		if ( isset( $_GET['template'] ) )  {
			$id = trim( sanitize_text_field( $_GET['template'] ) );
		} else {
			$id = 'blank';
		}

		foreach ( $this->module->templates as $key => $template ) {
			if ( $template->options['id'] === $id ) {
				return $template;
			}
		}

		return false;
	}

	/**
	 * Return Form Settins
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Form_Model $form
	 *
	 * @return mixed
	 */
	public function get_form_settings( $form ) {

		if ( ! isset( $form ) ) {
			$form = new stdClass();
		}

		// If not using the new "submission-behaviour" setting, set it according to the previous settings
		if ( ! isset( $form->settings['submission-behaviour'] ) ) {
			$redirect = ( isset( $form->settings['redirect'] ) && filter_var( $form->settings['redirect'], FILTER_VALIDATE_BOOLEAN ) );
			$thankyou = ( isset( $form->settings['thankyou'] ) && filter_var( $form->settings['thankyou'], FILTER_VALIDATE_BOOLEAN ) );

			if ( ! $redirect && ! $thankyou ) {
				$form->settings['submission-behaviour'] = 'behaviour-thankyou';
			} elseif ( $thankyou ) {
				$form->settings['submission-behaviour'] = 'behaviour-thankyou';
			} elseif ( $redirect ) {
				$form->settings['submission-behaviour'] = 'behaviour-redirect';
			}
		}

		if ( Forminator_Form_Model::has_stripe_or_paypal( $form ) && $this->is_ajax_submit( $form ) ) {
			if ( isset( $form->settings['submission-behaviour'] ) && "behaviour-thankyou" === $form->settings['submission-behaviour'] ) {
				$form->settings['submission-behaviour'] = 'behaviour-hide';
			}
		}

		return $form->settings;
	}

	/**
	 * Return Form notifications
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Form_Model|null $form
	 *
	 * @return mixed
	 */
	public function get_form_notifications( $form ) {
		if ( ! isset( $form ) || ! isset( $form->notifications ) ) {
			return array(
				array(
					'slug'             => 'notification-1234-4567',
					'label'            => 'Admin Email',
					'email-recipients' => 'default',
					'recipients'       => get_option( 'admin_email' ),
					'email-subject'    => __( "New Form Entry #{submission_id} for {form_name}", 'forminator' ),
					'email-editor'     => __( "You have a new website form submission: <br/> {all_fields} <br/>---<br/> This message was sent from {site_url}.", 'forminator' ),
					'email-attachment' => "true",
				)
			);
		}

		return $form->notifications;
	}

	/**
	 * Get Registration Form notifications
	 *
	 * @since 1.11
	 *
	 * @param Forminator_Form_Model|null $form
	 * @param Forminator_Template|null          $template
	 *
	 * @return mixed
	 */
	public function get_registration_form_notifications( $form, $template = null ) {
		if ( ! isset( $form ) || ! isset( $form->notifications ) ) {
			$msg_footer = __( 'This message was sent from {site_url}', 'forminator' );
			//For admin
			$message = __( "New user registration on your site {site_url}: <br/><br/> {all_fields} <br/><br/> Click {submission_url} to view the submission.<br/>", 'forminator' );
			$message .= "<br/>---<br/>";
			$message .= $msg_footer;

			$message_method_email = $message;

			$message_method_manual = __( "New user registration on your site {site_url}: <br/><br/> {all_fields} <br/><br/> The account is still not activated and needs your approval. To activate this account, click the link below.", 'forminator' );
			$message_method_manual .= "<br/>{account_approval_link} <br/><br/>";
			$message_method_manual .= __( "Click {submission_url} to view the submission on your website's dashboard.<br/><br/>", 'forminator' );
			$message_method_manual .= $msg_footer;

			$notifications[] = array(
				'slug'             => 'notification-1111-1111',
				'label'            => __( 'Admin Email', 'forminator' ),
				'email-recipients' => 'default',
				'recipients'       => get_option( 'admin_email' ),
				'email-subject'    => __( 'New User Registration on {site_url}', 'forminator' ),
				'email-editor'     => $message,

				'email-subject-method-email'  => __( 'New User Registration on {site_url}', 'forminator' ),
				'email-editor-method-email'   => $message_method_email,
				'email-subject-method-manual' => __( 'New User Registration on {site_url} needs approval.', 'forminator' ),
				'email-editor-method-manual'  => $message_method_manual,
			);
			if ( ! is_null( $template )) {
				$email = $this->get_registration_form_customer_email_slug( $template );
			} else {
				$email = $this->get_registration_form_customer_email_slug( $form );
			}
			//For customer
			$message  = __( "Your new account on our site {site_title} is ready to go. Here's your details: <br/><br/> {all_fields} <br/><br/>", 'forminator' );
			$message .= sprintf( __( 'Login to your new account <a href="%s">here</a>.', 'forminator' ), wp_login_url() );
			$message .= "<br/><br/>---<br/>";
			$message .= $msg_footer;

			$message_method_email = __( "Dear {username} <br/><br/>", 'forminator' );
			$message_method_email .= __( 'Thank you for signing up on our website. You are one step away from activating your account. ', 'forminator' );
			$message_method_email .= __( "We have sent you another email containing a confirmation link. Please click on that link to activate your account.<br/><br/>", 'forminator' );
			$message_method_email .= $msg_footer;

			$message_method_manual = __( "Your new account on {site_title} is under review.<br/>", 'forminator' );
			$message_method_manual .= __( "You'll receive another email once the site admin approves your account. You should be able to login into your account after that.", 'forminator' );
			$message_method_manual .= "<br/><br/>---<br/>";
			$message_method_manual .= $msg_footer;

			$notifications[] = array(
				'slug'             => 'notification-1111-1112',
				'label'            => __( 'User Confirmation Email', 'forminator' ),
				'email-recipients' => 'default',
				'recipients'       => $email,
				'email-subject'    => __( 'Your new account on {site_title}', 'forminator' ),
				'email-editor'     => $message,

				'email-subject-method-email'  => __( 'Activate your account on {site_url}', 'forminator' ),
				'email-editor-method-email'   => $message_method_email,
				'email-subject-method-manual' => __( 'Your new account on {site_title} is under review.', 'forminator' ),
				'email-editor-method-manual'  => $message_method_manual,
			);

			return $notifications;
		}

		return $form->notifications;
	}

	/**
	 * Get customer email as field slug
	 *
	 * @since 1.11
	 *
	 * @param Forminator_Form_Model|Forminator_Template $form
	 * @param string                                           $default
	 *
	 * @return string
	 */
	public function get_registration_form_customer_email_slug( $form, $default = '{email-1}' ) {
		if ( isset( $form->settings['registration-email-field'] ) && ! empty( $form->settings['registration-email-field'] ) ) {
			$email = $form->settings['registration-email-field'];
			if ( false === strpos( $email, '{' ) ) {
				$email = '{' . $email . '}';
			}

			return $email;
		}

		return $default;
	}

	/**
	 * Check if Custom form has stripe field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_stripe_field( $form ) {
		$fields = isset( $form->fields ) ? $form->fields : array();

		foreach ( $fields as $field ) {
			$field = $field->to_formatted_array();
			if ( isset( $field['type'] ) && 'stripe' === $field['type'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if submit is handled with AJAX
	 *
	 * @since 1.9.3
	 *
	 * @return bool
	 */
	public function is_ajax_submit( $form ) {
		$form_settings  = $form->settings;

		// Force AJAX submit if form contains Stripe payment field
		if ( $this->has_stripe_field( $form ) ) {
			return true;
		}

		if ( ! isset( $form_settings['enable-ajax'] ) || empty( $form_settings['enable-ajax'] ) ) {
			return false;
		}

		return filter_var( $form_settings['enable-ajax'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Form default data
	 *
	 * @param $name
	 * @param array $settings
	 *
	 * @return array
	 */
	public function get_default_settings( $name, $settings = array() ) {
		return array_merge(
			array(
				'formName'             => $name,
				'pagination-header'    => 'nav',
				'version'              => FORMINATOR_VERSION,
				'form-border-style'    => 'solid',
				'form-padding'         => '',
				'form-border'          => '',
				'fields-style'         => 'open',
				'validation'           => 'on_submit',
				'form-style'           => 'default',
				'enable-ajax'          => 'true',
				'autoclose'            => 'true',
				'submission-indicator' => 'show',
				'indicator-label'      => __( 'Submitting...', 'forminator' ),
				'paginationData'       => array(
					'pagination-header-design' => 'show',
					'pagination-header'        => 'nav',
				),
			),
			$settings
		);
	}

	/**
	 * Create quiz module
	 *
	 * @since 1.14
	 *
	 * @return no return
	 */
	public function create_module() {
		$model = null;

		if ( $this->is_admin_wizard() ) {
			$data['application'] = 'builder';

			if ( ! self::is_edit() ) {
				$settings      = array();

				// Create new model
				$model = new Forminator_Form_Model();

				// Save nonce
				$data['formNonce'] = wp_create_nonce( 'forminator_save_builder_fields' );

				// Load settings from template
				$template = $this->get_template();

				$name = '';
				if ( isset( $_GET['name'] ) ) { // WPCS: CSRF ok.
					$name = sanitize_text_field( $_GET['name'] );
				}

				if ( isset( $model->notifications ) ) {
					unset( $model->notifications );
				}

				// Setup notifications
				if ( isset( $template->settings['form-type'] ) && in_array( $template->settings['form-type'], array(
						'registration',
						'login'
					) ) ) {
					$notifications = 'registration' === $template->settings['form-type']
						? $this->get_registration_form_notifications( $model, $template )
						: array();
				} else {
					$notifications = $this->get_form_notifications( $model );
				}

				// If template, load from file
				if ( $template ) {
					$settings = $this->get_default_settings( $name, $template->settings );

					// Setup template fields
					foreach ( $template->fields as $row ) {
						foreach ( $row['fields'] as $f ) {
							$field          = new Forminator_Form_Field_Model();
							$field->form_id = $row['wrapper_id'];
							$field->slug    = $f['element_id'];
							unset( $f['element_id'] );
							$field->import( $f );
							$model->add_field( $field );
						}
					}
				} else {
					$settings = $this->get_default_settings( $name, array() );
				}

				$model->name          = $name;
				$model->notifications = $notifications;

				// form name & version
				$settings['formName'] = $name;
				$settings['version']  = FORMINATOR_VERSION;

				// settings
				$model->settings = $settings;

				// status
				$model->status = Forminator_Form_Model::STATUS_DRAFT;

				// Save data
				$id = $model->save();

				$wizard_url = admin_url( 'admin.php?page=forminator-cform-wizard&id=' . $id );

				wp_safe_redirect( $wizard_url );
			}
		}
	}
}
