<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Front_Action
 *
 * Abstract class for front functions
 *
 * @since 1.0
 */
abstract class Forminator_Front_Action {

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public $entry_type = '';

	/**
	 * Response message
	 *
	 * @var array
	 */
	protected static $response = array();

	/**
	 * Hold $_POST submitted data
	 *
	 * @since 1.5.1
	 * @var array
	 */
	protected $_post_data = array();

	/**
	 * Additional response attributes
	 *
	 * @var array
	 */
	protected static $response_attrs = array();

	public function __construct() {
		//Save entries
		if ( ! empty( $this->entry_type ) ) {
			add_action( 'wp', array( $this, 'maybe_handle_submit' ), 9 );
			add_action( 'wp_ajax_forminator_submit_form_' . $this->entry_type, array( $this, 'save_entry' ) );
			add_action( 'wp_ajax_nopriv_forminator_submit_form_' . $this->entry_type, array( $this, 'save_entry' ) );

			add_action( 'wp_ajax_forminator_submit_preview_form_' . $this->entry_type, array( $this, 'save_entry_preview' ) );
			add_action( 'wp_ajax_nopriv_forminator_submit_preview_form_' . $this->entry_type, array( $this, 'save_entry_preview' ) );

			add_action( 'wp_ajax_forminator_update_payment_amount', array( $this, 'update_payment_amount' ) );
			add_action( 'wp_ajax_nopriv_forminator_update_payment_amount', array( $this, 'update_payment_amount' ) );

			add_action( 'wp_ajax_forminator_multiple_file_upload', array( $this, 'multiple_file_upload' ) );
			add_action( 'wp_ajax_nopriv_forminator_multiple_file_upload', array( $this, 'multiple_file_upload' ) );
		}
	}

	/**
	 * Returns last
	 *
	 * @since 1.1
	 */
	public function get_last_entry( $form_id ) {

		$entries = Forminator_Form_Entry_Model::get_entries( $form_id );

		if ( 0 < count( $entries ) ) {
			return $entries[0]->entry_id;
		}

		return false;

	}

	/**
	 * Maybe handle form submit
	 *
	 * @since 1.0
	 */
	public function maybe_handle_submit() {
		if ( $this->is_force_validate_submissions_nonce() ) {
			if (
				isset( $_POST['forminator_nonce'] )
				&& wp_verify_nonce( $_POST['forminator_nonce'], 'forminator_submit_form' )
			) {
				$this->handle_submit();
			}
		} else {
			if ( isset( $_REQUEST['action'] ) && 'forminator_submit_form_' . $this->entry_type === $_REQUEST['action'] ) {
				$this->handle_submit();
			}
		}

	}

	/**
	 * Handle submit
	 *
	 * @since 1.0
	 */
	public function handle_submit() {
		$this->_post_data = $this->get_post_data();
		$post_data        = $this->_post_data;
		$form_id          = isset( $post_data['form_id'] ) ? sanitize_text_field( $post_data['form_id'] ) : false;
		if ( $form_id ) {
			/**
			 * Action called before full module submit
			 *
			 * @since 1.0.2
			 *
			 * @param int $form_id - the form id
			 */
			do_action( 'forminator_' . static::$module_slug . '_before_handle_submit', $form_id );

			$response = $this->handle_form( $form_id );

			// sanitize front end message
			if ( ! empty( $response['message'] ) ) {
				$response['message'] = wp_kses_post( $response['message'] );
			}

			/**
			 * Filter submit response
			 *
			 * @since 1.0.2
			 *
			 * @param array $response - the post response
			 * @param int $form_id - the form id
			 *
			 * @return array $response
			 */
			$response = apply_filters( 'forminator_' . static::$module_slug . '_submit_response', $response, $form_id );

			/**
			 * Action called after full form submit
			 *
			 * @since 1.0.2
			 *
			 * @param int $form_id - the form id
			 * @param array $response - the post response
			 */
			do_action( 'forminator_' . static::$module_slug . '_after_handle_submit', $form_id, $response );
			if ( $response && is_array( $response ) ) {
				self::$response = $response;
				if ( $response['success'] ) {
					if ( isset( $response['url'] ) && ( ! isset( $response['newtab'] ) || 'sametab' === $response['newtab'] ) ) {
						$url = apply_filters( 'forminator_' . static::$module_slug . '_submit_url', $response['url'], $form_id );
						wp_redirect( $url );
						exit;
					} else {
						add_action( 'forminator_' . static::$module_slug . '_post_message', array( $this, 'form_response_message' ), 10, 2 );
						// cleanup submitted data
						$_POST = array();
					}
				} else {
					add_action( 'wp_footer', array( $this, 'footer_message' ) );
				}
			}
		}
	}

	/**
	 * Add Error message on footer script if available
	 *
	 * @since 1.0
	 * @since 1.1 change $_POST to `get_post_data`
	 * @since 1.5.1 utilize `_post_data` which already defined on submit
	 */
	public function footer_message() {
		$submitted_data = $this->_post_data;

		$response  = self::$response;
		$form_id   = isset( $submitted_data['form_id'] ) ? sanitize_text_field( $submitted_data['form_id'] ) : false;
		$render_id = isset( $submitted_data['render_id'] ) ? sanitize_text_field( $submitted_data['render_id'] ) : '';
		$selector  = '#forminator-module-' . $form_id . '[data-forminator-render="' . $render_id . '"]';
		if ( ! empty( $response['errors'] ) ) {
			?>
            <script type="text/javascript">var ForminatorValidationErrors =
				<?php
				echo wp_json_encode(
					array(
						'selector' => $selector,
						'errors'   => $response['errors'],
					)
				);
				?>
            </script>
			<?php
		}
	}

	/**
	 * Validate ajax
	 *
	 * @since 1.0
	 *
	 * @param string|null $action - the HTTP action
	 * @param string      $request_method
	 * @param string      $nonce_field
	 *
	 * @return bool
	 */
	public function validate_ajax( $action = null, $request_method = 'POST', $nonce_field = '_wpnonce' ) {
		if ( ! $this->is_force_validate_submissions_nonce() ) {
			if ( isset( $_REQUEST['action'] ) && $action === $_REQUEST['action'] ) { // phpcs:ignore
				return true;
			}
		}

		if ( isset( $_REQUEST[ $nonce_field ] ) && wp_verify_nonce( $_REQUEST[ $nonce_field ], $action ) ) {
			return true;
		} else {
			// if default nonce verifier fail, check other $request_method and auto detect action
			switch ( $request_method ) {
				case 'GET':
					$request_fields = $_GET;
					break;

				case 'REQUEST':
				case 'any':
					$request_fields = $_REQUEST;
					break;

				case 'POST':
				default:
					$request_fields = $_POST;
					break;
			}

			if ( empty( $action ) ) {
				$action = ! empty( $request_fields['action'] ) ? $request_fields['action'] : '';
			}

			if ( ! empty( $request_fields[ $nonce_field ] )
				&& wp_verify_nonce( $request_fields[ $nonce_field ], $action )
			) {
				return true;
			}
		}

		// make sure its invalidated if all other above failed
		return false;
	}

	/**
	 * Save Entry
	 *
	 * @since 1.0
	 */
	public function save_entry() {
		$this->_post_data = $this->get_post_data();
		$post_data        = $this->_post_data;

		if ( $this->validate_ajax( 'forminator_submit_form', 'POST', 'forminator_nonce' ) ) {
			$form_id = isset( $post_data['form_id'] ) ? sanitize_text_field( $post_data['form_id'] ) : false;
			if ( $form_id ) {

				/**
				 * Action called before module ajax
				 *
				 * @since 1.0.2
				 *
				 * @param int $form_id - the form id
				 */
				do_action( 'forminator_' . static::$module_slug . '_before_save_entry', $form_id );

				$response = $this->handle_form( $form_id );

				// sanitize front end message
				if ( is_array( $response ) && ! empty( $response['message'] ) ) {
					$response['message'] = wp_kses_post( $response['message'] );
				}

				/**
				 * Filter ajax response
				 *
				 * @since 1.0.2
				 *
				 * @param array $response - the post response
				 * @param int $form_id - the form id
				 *
				 */
				$response = apply_filters( 'forminator_' . static::$module_slug . '_ajax_submit_response', $response, $form_id );

				/**
				 * Action called after form ajax
				 *
				 * @since 1.0.2
				 *
				 * @param int $form_id - the form id
				 * @param array $response - the post response
				 */
				do_action( 'forminator_' . static::$module_slug . '_after_save_entry', $form_id, $response );

				if ( $response && is_array( $response ) ) {
					if ( ! $response['success'] ) {
						wp_send_json_error( $response );
					} else {
						wp_send_json_success( $response );
					}
				}
						wp_send_json_error( 'invalid response' );
			}
						wp_send_json_error( 'invalid id' );
		}
						wp_send_json_error( 'invalid nonce' );
	}

	/**
	 * Save Entry for Preview
	 *
	 * @since 1.6
	 */
	public function save_entry_preview() {
		$this->_post_data = $this->get_post_data();
		$post_data        = $this->_post_data;

		if ( $this->validate_ajax( 'forminator_submit_form', 'POST', 'forminator_nonce' ) ) {
			$form_id = isset( $post_data['form_id'] ) ? sanitize_text_field( $post_data['form_id'] ) : false;
			if ( $form_id ) {

				/**
				 * Action called before module ajax
				 *
				 * @since 1.0.2
				 *
				 * @param int $form_id - the form id
				 */
				do_action( 'forminator_' . static::$module_slug . '_before_save_entry', $form_id );

				$response = $this->handle_form( $form_id, true );
				// sanitize front end message
				if ( is_array( $response ) && ! empty( $response['message'] ) ) {
					$response['message'] = wp_kses_post( $response['message'] );
				}

				/**
				 * Filter ajax response
				 *
				 * @since 1.0.2
				 *
				 * @param array $response - the post response
				 * @param int $form_id - the form id
				 */
				$response = apply_filters( 'forminator_' . static::$module_slug . '_ajax_submit_response', $response, $form_id );

				/**
				 * Action called after form ajax
				 *
				 * @since 1.0.2
				 *
				 * @param int $form_id - the form id
				 * @param array $response - the post response
				 */
				do_action( 'forminator_' . static::$module_slug . '_after_save_entry', $form_id, $response );

				if ( $response && is_array( $response ) ) {
					if ( ! $response['success'] ) {
						wp_send_json_error( $response );
					} else {
						wp_send_json_success( $response );
					}
				}
			}
		}
	}

	/**
	 * Executor to add more entry fields for attached addons
	 *
	 * @since 1.1
	 *
	 * @param                              $module_id
	 * @param Forminator_Base_Form_Model $module_model
	 * @param array $current_entry_fields
	 *
	 * @return array added fields to entry
	 */
	protected function attach_addons_add_entry_fields( $module_id, Forminator_Base_Form_Model $module_model, $current_entry_fields ) {
		$additional_fields_data = array();
		$submitted_data         = static::get_submitted_data( $module_model, $current_entry_fields );

		$function         = 'forminator_get_addons_instance_connected_with_' . static::$module_slug;
		$connected_addons = $function( $module_id );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$method = 'get_addon_' . static::$module_slug . '_hooks';
				if ( method_exists( $connected_addon, $method ) ) {
					$hooks = $connected_addon->$method( $module_id );
				}
				if ( isset( $hooks ) && $hooks instanceof Forminator_Addon_Hooks_Abstract ) {
					$addon_fields = $hooks->add_entry_fields( $submitted_data, $current_entry_fields );
					//reformat additional fields
					$addon_fields           = self::format_addon_additional_fields( $connected_addon, $addon_fields );
					$additional_fields_data = array_merge( $additional_fields_data, $addon_fields );
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to ' . static::$module_slug . ' add_entry_fields', $e->getMessage() );
			}
		}

		return $additional_fields_data;
	}

	/**
	 * Executor action for attached addons after entry saved on storage
	 *
	 * @since 1.1
	 *
	 * @param                             $module_id
	 * @param Forminator_Form_Entry_Model $entry_model
	 */
	protected function attach_addons_after_entry_saved( $module_id, Forminator_Form_Entry_Model $entry_model ) {
		//find is_form_connected
		$function         = 'forminator_get_addons_instance_connected_with_' . static::$module_slug;
		$connected_addons = $function( $module_id );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$method = 'get_addon_' . static::$module_slug . '_hooks';
				if ( method_exists( $connected_addon, $method ) ) {
					$hooks = $connected_addon->$method( $module_id );
				}
				if ( isset( $hooks ) && $hooks instanceof Forminator_Addon_Hooks_Abstract ) {
					$hooks->after_entry_saved( $entry_model );// run and forget
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to ' . static::$module_slug . ' attach_addons_after_entry_saved', $e->getMessage() );
			}
		}
	}

	/**
	 * Update payment amount
	 *
	 * @since 1.7.3
	 */
	public function update_payment_amount() {}

	/**
	 * Handle file upload
	 *
	 * @since 1.0
	 * @since 1.1 Bugfix filter `forminator_file_upload_allow` `$file_name` passed arg
	 *
	 * @param string $field_name - the input file name
	 *
	 * @return bool|array
	 */
	public function handle_file_upload( $field_name ) {
		if ( isset( $_FILES[ $field_name ] ) ) {
			if ( isset( $_FILES[ $field_name ]['name'] ) && ! empty( $_FILES[ $field_name ]['name'] ) ) {
				$file_name = sanitize_file_name( $_FILES[ $field_name ]['name'] );
				$valid     = wp_check_filetype( $file_name );

				if ( false === $valid['ext'] ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
					);
				}

				$allow = apply_filters( 'forminator_file_upload_allow', true, $field_name, $file_name, $valid );
				if ( false === $allow ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
					);
				}

				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
				/** @var WP_Filesystem_Base $wp_filesystem */
				global $wp_filesystem;
				if ( ! is_uploaded_file( $_FILES[ $field_name ]['tmp_name'] ) ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Failed to read uploaded file.', 'forminator' ),
					);
				}

				$upload_dir       = wp_upload_dir(); // Set upload folder
				$unique_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
				$filename         = basename( $unique_file_name ); // Create base file name

				if ( 0 === $_FILES[ $field_name ]['size'] || $_FILES[ $field_name ]['size'] > wp_max_upload_size() ) {

					$max_size = wp_max_upload_size();
					$max_size = round( $max_size / 1000000 ) . ' MB';

					return array(
						'success' => false,
						'message' => sprintf( /* translators: ... */ __( 'Error saving form. Uploaded file size exceeds %1$s upload limit. ', 'forminator' ), $max_size ),
					);
				}

				if ( UPLOAD_ERR_OK !== $_FILES[ $field_name ]['error'] ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}

				if ( ! $wp_filesystem->is_dir( $upload_dir['path'] ) ) {
					$wp_filesystem->mkdir( $upload_dir['path'] );
				}

				if ( $wp_filesystem->is_writable( $upload_dir['path'] ) ) {
					$file_path = $upload_dir['path'] . '/' . $filename;
					$file_url  = $upload_dir['url'] . '/' . $filename;
				} else {
					$file_path = $upload_dir['basedir'] . '/' . $filename;
					$file_url  = $upload_dir['baseurl'] . '/' . $filename;
				}

				// use move_uploaded_file instead of $wp_filesystem->put_contents
				// increase performance, and avoid permission issues
				if ( false !== move_uploaded_file( $_FILES[ $field_name ]['tmp_name'], $file_path ) ) {
					return array(
						'success'   => true,
						'file_url'  => $file_url,
						'file_path' => $file_path,
					);
				} else {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}
			}
		}

		return false;
	}

	/**
	 * Get $_POST data
	 *
	 * @since 1.1
	 *
	 * @param array $nonce_args         {
	 *                                  nonce validation options, its numeric array
	 *                                  0 => 'action' string of action name to be validated,
	 *                                  2 => 'nonce_field' string of field name on $_POST contains nonce value
	 *                                  }
	 *
	 * @param array $sanitize_callbacks {
	 *                                  custom sanitize options, its assoc array
	 *                                  'field_name_1' => 'function_to_call_1' function will called with `call_user_func_array`,
	 *                                  'field_name_2' => 'function_to_call_2',
	 *                                  }
	 *
	 * @return array
	 */
	protected function get_post_data( $nonce_args = array(), $sanitize_callbacks = array() ) {
		// do nonce / caps check when requested
		$nonce_action = '';
		$nonce_field  = '';
		if ( isset( $nonce_args[0] ) && ! empty( $nonce_args[0] ) ) {
			$nonce_action = $nonce_args[0];
		}
		if ( isset( $nonce_args[1] ) && ! empty( $nonce_args[1] ) ) {
			$nonce_field = $nonce_args[1];
		}
		if ( ! empty( $nonce_action ) && ! empty( $nonce_field ) ) {
			$validated = $this->validate_ajax( $nonce_action, 'POST', $nonce_field );
			if ( ! $validated ) {
				// return empty data when its not validated
				return array();
			}
		}

		$post_data = $_POST; // phpcs:ignore -- validate_ajax

		// do some sanitize
		foreach ( $sanitize_callbacks as $field => $sanitize_func ) {
			if ( isset( $post_data[ $field ] ) ) {
				if ( is_callable( $sanitize_func ) ) {
					$post_data[ $field ] = call_user_func_array( array( $sanitize_func ), array( $post_data[ $field ] ) );
				}
			}
		}

		// do some validation

		return $post_data;
	}


	/**
	 * Formatting additional fields from addon
	 * Format used is `forminator_addon_{$slug}_{$field_name}`
	 *
	 * @since 1.6.1
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $additional_fields
	 *
	 * @return array
	 */
	protected static function format_addon_additional_fields( Forminator_Addon_Abstract $addon, $additional_fields ) {
		//to `name` and `value` basis
		$formatted_additional_fields = array();
		if ( ! is_array( $additional_fields ) ) {
			return array();
		}

		foreach ( $additional_fields as $additional_field ) {
			if ( ! isset( $additional_field['name'] ) || ! isset( $additional_field['value'] ) ) {
				continue;
			}
			$formatted_additional_fields[] = array(
				'name'  => 'forminator_addon_' . $addon->get_slug() . '_' . $additional_field['name'],
				'value' => $additional_field['value'],
			);
		}

		return $formatted_additional_fields;
	}

	/**
	 * Check if validate nonce should be executed
	 *
	 * @return bool
	 */
	protected function is_force_validate_submissions_nonce() {
		// default is disabled unless `FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE` = true,
		// this behaviour is to support full page cache
		$enabled = ( defined( 'FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE' ) && FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE );

		/**
		 * Filter the status of nonce submissions
		 *
		 * @since 1.6.1
		 *
		 * @param bool $enabled current status of nonce submissions
		 */
		$enabled = apply_filters( 'forminator_is_force_validate_submissions_nonce', $enabled );

		return $enabled;
	}

	/**
	 * Prepare error array.
	 *
	 * @param string $error Error message.
	 * @param array  $errors Error Optional. Fields errors.
	 * @return array
	 */
	protected static function return_error( $error, $errors = array() ) {
		$response = array(
			'message' => $error,
			'success' => false,
		);

		if ( ! empty( $errors ) ) {
			$response['errors'] = $errors;
		}

		unset( self::$response_attrs['message'] );

		if ( self::$response_attrs ) {
			$response = array_merge( $response, self::$response_attrs );
		}

		return $response;
	}

	/**
	 * Prepare success array.
	 *
	 * @return array
	 */
	protected static function return_success( $message = null ) {
		$response = array(
			'message' => ! is_null( $message ) ? $message : __( 'Form entry saved', 'forminator' ),
			'success' => true,
		);

		if ( self::$response_attrs ) {
			$response = array_merge( $response, self::$response_attrs );
		}

		return $response;
	}
}
