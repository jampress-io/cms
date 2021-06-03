<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Author: Hoang Ngo
 *
 * @property  string $status
 */
abstract class Forminator_Base_Form_Model {
	const META_KEY = 'forminator_form_meta';
	/**
	 * Form ID
	 *
	 * @int
	 */
	public $id;
	/**
	 * Form name
	 *
	 * @string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $client_id;

	/**
	 * Contain fields of this form
	 *
	 * @var Forminator_Form_Field_Model[]
	 */
	public $fields = array();

	/**
	 * Form settings
	 * array
	 */
	public $settings = array();

	/**
	 * Form notification
	 * array
	 */
	public $notifications = array();

	/**
	 * WP_Post
	 */
	public $raw;
	/**
	 * This post type
	 *
	 * @string
	 */
	protected $post_type;

	const STATUS_PUBLISH = 'publish';
	const STATUS_DRAFT   = 'draft';

	/**
	 * Save form
	 *
	 * @since 1.0
	 *
	 * @param bool $clone
	 *
	 * @return mixed
	 */
	public function save( $clone = false ) {
		//todo use save_post for saving the form and update_post_meta for saving fields
		//prepare the data
		$maps      = array_merge( $this->get_default_maps(), $this->get_maps() );
		$post_data = array();
		$meta_data = array();
		if ( ! empty( $maps ) ) {
			foreach ( $maps as $map ) {
				$attribute = $map['property'];
				if ( 'post' === $map['type'] ) {
					if ( isset( $this->$attribute ) ) {
						$post_data[ $map['field'] ] = $this->$attribute;
					} elseif ( isset( $map['default'] ) ) {
						$post_data[ $map['field'] ] = $map['default'];
					}
				} else {
					if ( 'fields' === $map['field'] ) {
						$meta_data[ $map['field'] ] = $this->get_fields_as_array();
					} else {
						$meta_data[ $map['field'] ] = $this->$attribute;
					}
				}
			}
		}

		$post_data['post_type'] = $this->post_type;

		//storing
		if ( is_null( $this->id ) ) {
			$id = wp_insert_post( $post_data );
		} else {
			$id = wp_update_post( $post_data );
		}

		// If cloned we have to update the fromID
		if ( $clone ) {
			$meta_data['settings']['form_id'] = $id;
		}

		update_post_meta( $id, self::META_KEY, $meta_data );

		return $id;
	}

	/**
	 * @since 1.0
	 * @return Forminator_Form_Field_Model[]
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * @since 1.0
	 * @since 1.2 Add $sanitize_function as optional param
	 *
	 * @param        $property
	 * @param        $name
	 * @param        $array
	 * @param string $sanitize_function custom sanitize function to use, default is sanitize_title
	 */
	public function set_var_in_array( $property, $name, $array, $sanitize_function = 'sanitize_title' ) {
		$val = isset( $array[ $name ] ) ? $array[ $name ] : null;
		if ( is_callable( $sanitize_function ) ) {
			$val = call_user_func_array( $sanitize_function, array( $val ) );
		}
		$this->$property = $val;
	}

	/**
	 * Add field
	 *
	 * @since 1.0
	 *
	 * @param $field
	 */
	public function add_field( $field ) {
		$this->fields[] = $field;
	}

	/**
	 * Get field
	 *
	 * @since 1.0
	 *
	 * @param $slug
	 *
	 * @return Forminator_Form_Field|null
	 */
	public function get_field( $slug ) {
		//get a field and return as object
		return isset( $this->fields[ $slug ] ) ? $this->fields[ $slug ] : null;
	}

	/**
	 * Remove field
	 *
	 * @since 1.0
	 *
	 * @param $slug
	 */
	public function remove_field( $slug ) {
		unset( $this->fields[ $slug ] );
	}

	/**
	 * Clear fields
	 *
	 * @since 1.0
	 */
	public function clear_fields() {
		$this->fields = array();
	}

	/**
	 * Load model
	 *
	 * @since 1.0
	 *
	 * @param $id
	 *
	 * @return bool|$this
	 */
	public function load( $id, $callback = false ) {
		$post = get_post( $id );

		if ( ! is_object( $post ) ) {
			// If we haven't saved yet, fallback to latest ID and replace the data
			if ( $callback ) {
				$id   = $this->get_latest_id();
				$post = get_post( $id );

				if ( ! is_object( $post ) ) {
					return false;
				}
			} else {
				return false;
			}
		}

		return $this->_load( $post );
	}

	/**
	 * Load preview
	 *
	 * @since 1.0
	 *
	 * @param $id
	 * @param $data
	 *
	 * @return bool|Forminator_Base_Form_Model
	 */
	public function load_preview( $id, $data ) {
		$form_model = $this->load( $id, true );

		// If bool, abort.
		if ( is_bool( $form_model ) ) {
			return false;
		}

		$form_model->clear_fields();
		$form_model->set_var_in_array( 'name', 'formName', $data );

		// build the settings.
		if ( isset( $data['settings'] ) ) {
			$settings             = $data['settings'];
			$form_model->settings = $settings;
		}

		$form_model = static::prepare_data_for_preview( $form_model, $data );

		return $form_model;
	}

	/**
	 * Get relevant module object based on its ID.
	 *
	 * @param int $id Module ID.
	 * @return boolean|object
	 */
	public static function get_model( $id ) {
		$class = self::get_model_class( $id );
		if ( $class ) {
			$model = $class::model()->load( $id );
			return $model;
		}

		return false;
	}

	/**
	 * Get module class by module ID
	 *
	 * @param int $id Module ID.
	 * @return boolean|string
	 */
	public static function get_model_class( $id ) {
		$post = get_post( $id );
		if ( empty( $post->post_type ) ) {
			return false;
		}
		switch ( $post->post_type ) {
			case 'forminator_forms':
				$class = 'Forminator_Form_Model';
				break;
			case 'forminator_polls':
				$class = 'Forminator_Poll_Model';
				break;
			case 'forminator_quizzes':
				$class = 'Forminator_Quiz_Model';
				break;
			default:
				return false;
		}

		return $class;
	}

	/**
	 * Return latest id for the post_type
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_latest_id() {
		$id   = 1;
		$args = array(
			'post_type'   => $this->post_type,
			'numberposts' => 1,
			'fields'      => 'ids',
		);

		$post = get_posts( $args );

		if ( isset( $post[0] ) ) {
			$id = $post[0];
		}

		return $id;
	}

	/**
	 * Count all form types
	 *
	 * @since 1.0
	 * @since 1.6 add optional param `status`
	 *
	 * @param string $status
	 *
	 * @return int
	 */
	public function count_all( $status = '' ) {
		$count_posts = wp_count_posts( $this->post_type );
		$count_posts = (array) $count_posts;
		if ( ! empty( $status ) ) {
			if ( isset( $count_posts[ $status ] ) ) {
				return $count_posts[ $status ];
			} else {
				return 0;
			}
		} else {
			if ( 'forminator_forms' === $this->post_type ) {
				unset( $count_posts['leads'] );
			}
		}

		return array_sum( $count_posts );
	}

	/**
	 * Get entry type
	 *
	 * @return string
	 */
	public function get_entry_type() {
		$post_type = $this->get_post_type();
		switch ( $post_type ) {
			case 'forminator_forms':
				$entry_type = 'custom-forms';
				break;
			case 'forminator_polls':
				$entry_type = 'polls';
				break;
			case 'forminator_quizzes':
				$entry_type = 'quizzes';
				break;
			default:
				$entry_type = '';
				break;
		}

		return $entry_type;
	}

	/**
	 * Get all paginated
	 *
	 * @since 1.2
	 * @since 1.5.4 add optional param per_page
	 * @since 1.5.4 add optional param $status
	 *
	 * @param int      $current_page
	 * @param null|int $per_page
	 * @param string   $status
	 *
	 * @return array
	 */
	public function get_all_paged( $current_page = 1, $per_page = null, $status = '' ) {
		if ( is_null( $per_page ) ) {
			$per_page = forminator_form_view_per_page();
		}
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}

		if ( 'forminator_forms' === $this->post_type ) {
			$args['meta_key']     = 'forminator_form_meta';
			$args['meta_value']   = 'form-type";s:5:"leads"';
			$args['meta_compare'] = 'NOT LIKE';
		}

		$query  = new WP_Query( $args );
		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return array(
			'totalPages'   => $query->max_num_pages,
			'totalRecords' => $query->post_count,
			'models'       => $models,
		);
	}

	/**
	 * Get all
	 *
	 * @since 1.0
	 * @since 1.6 add `status` in param
	 * @since 1.6 add `limit` in param
	 *
	 * @param string $status post_status arg
	 * @param int    $limit
	 *
	 * @return array()
	 */
	public function get_all_models( $status = '', $limit = - 1 ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $limit,
		);

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}
		$query  = new WP_Query( $args );
		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return array(
			'totalPages'   => $query->max_num_pages,
			'totalRecords' => $query->post_count,
			'models'       => $models,
		);
	}

	/**
	 * Get modules from field id
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_models_by_field( $id ) {
		$modules = array();
		$data    = $this->get_models( 999 );

		foreach ( $data as $model ) {
			if ( $model->get_field( $id ) ) {
				$modules[] = array(
					'id'      => $model->id,
					'title'   => $model->name,
					'version' => $model->version
				);
			}
		}

		return $modules;
	}

	/**
	 * Get modules from field id & version
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_models_by_field_and_version( $id, $version ) {
		$modules   = array();
		$data      = $this->get_models(999);

		foreach ( array_filter( $data ) as $model ) {
			if ( $model->get_field( $id ) && version_compare( $model->settings['version'], $version, 'lt' ) ) {
				$modules[] = array(
					'id'      => $model->id,
					'title'   => $model->name,
					'version' => $model->settings['version']
				);
			}
		}

		return $modules;
	}

	/**
	 * Get Models
	 *
	 * @since 1.0
	 * @since 1.6 add `status` as optional param
	 *
	 * @param int    $total - the total. Defaults to 4
	 * @param string $status
	 *
	 * @return array $models
	 */
	public function get_models( $total = 4, $status = '' ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $total,
			'order'          => 'DESC',
		);
		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}
		$query  = new WP_Query( $args );
		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return $models;
	}

	/**
	 * @since 1.0
	 *
	 * @param $post
	 *
	 * @return mixed
	 */
	private function _load( $post ) {
		if ( $this->post_type === $post->post_type ) {
			$class         = get_class( $this );
			$object        = new $class();
			$meta          = get_post_meta( $post->ID, self::META_KEY, true );
			$maps          = array_merge( $this->get_default_maps(), $this->get_maps() );
			$fields        = ! empty( $meta['fields'] ) ? $meta['fields'] : array();
			$form_settings = array(
				'version'                    => '1.0',
				'cform-section-border-color' => '#E9E9E9',
			);

			// Update version from form settings
			if ( isset( $meta['settings']['version'] ) ) {
				$form_settings['version'] = $meta['settings']['version'];
			}

			// Update section border color
			if ( isset( $meta['settings']['cform-section-border-color'] ) ) {
				$form_settings['cform-section-border-color'] = $meta['settings']['cform-section-border-color'];
			}

			if ( ! empty( $maps ) ) {
				foreach ( $maps as $map ) {
					$attribute = $map['property'];
					if ( 'post' === $map['type'] ) {
						$att                = $map['field'];
						$object->$attribute = $post->$att;
					} else {
						if ( ! empty( $meta['fields'] ) && 'fields' === $map['field'] ) {
							foreach ( $meta['fields'] as $field_data ) {
								// Prevent creating empty wrappers
								if ( isset( $field_data['type'] ) && 'honeypot' === $field_data['type'] ) {
									continue;
								}
								$field          = new Forminator_Form_Field_Model( $form_settings );
								$field->form_id = $post->ID;
								$field->slug    = $field_data['id'];
								unset( $field_data['id'] );
								$field->import( $field_data );
								$object->add_field( $field );
							}
						} else {
							if ( isset( $meta[ $map['field'] ] ) ) {
								$object->$attribute = $meta[ $map['field'] ];
							}
						}
					}
				}
			}

			$form_settings = $object->settings;
			if ( ! isset( $form_settings['form_id'] ) ) {
				$form_settings['form_id'] = $object->id;
			}

			// Migrate settings Custom Form
			if ( 'forminator_forms' === $this->post_type ) {
				$form_settings         = self::validate_registration_fields_mapping( $form_settings, $fields );
				$object->settings      = Forminator_Migration::migrate_custom_form_settings( $form_settings, $fields );
				$object->notifications = Forminator_Migration::migrate_custom_form_notifications( $object->notifications, $form_settings, $meta );
			}

			// Migrate settings Polls
			if ( 'forminator_polls' === $this->post_type ) {
				$object->settings = Forminator_Migration::migrate_polls_settings( $form_settings );
			}

			// Migrate settings Polls
			if ( 'forminator_quizzes' === $this->post_type ) {
				$object->settings      = Forminator_Migration::migrate_quizzes_settings( $form_settings );
				$object->notifications = Forminator_Migration::migrate_quizzes_notifications( $object->notifications, $form_settings, $meta );
			}

			$object->raw = $post;

			return $object;
		}

		return false;
	}

	/**
	 * Validate registration fields mapping for Registration forms
	 * If the field is removed - replace it to the first field in the list
	 *
	 * @param array $form_settings Form settings.
	 * @param array $fields Form fields.
	 * @return array
	 */
	private static function validate_registration_fields_mapping( $form_settings, $fields ) {
		$field_ids = wp_list_pluck( $fields, 'id' );
		if ( ! empty( $form_settings['form-type'] ) && 'registration' === $form_settings['form-type'] && ! empty( $field_ids ) ) {
			// Get first field id (not password).
			$i = 0;
			do {
				$first_id = isset( $field_ids[ $i ] ) ? $field_ids[ $i ] : null;
				$i++;
				$is_password = false !== strpos( $first_id, 'password' );
				$go_next     = empty( $first_id ) || $is_password;
			} while ( $go_next );

			foreach ( $form_settings as $key => $value ) {
				if ( ! is_string( $value ) ) {
					continue;
				}
				$value_parts = explode( '-', $value );
				if ( ! $first_id
						|| 'registration-' !== substr( $key, 0, 13 )
						|| '-field' !== substr( $key, -6 )
						|| 'registration-role-field' === $key
						|| in_array( $value, $field_ids, true )
						// for multiple fields like name, address.
						|| 2 < count( $value_parts ) && in_array( $value_parts[0] . '-' . $value_parts[1], $field_ids, true )
					) {
					continue;
				}
				if ( 'registration-password-field' === $key ) {
					$form_settings[ $key ] = 'auto';
				} else {
					$form_settings[ $key ] = $first_id;
				}

			}
		}

		return $form_settings;
	}

	/**
	 * Return fields as array
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields_as_array() {
		$arr = array();

		if ( empty( $this->fields ) ) {
			return $arr;
		}

		foreach ( $this->fields as $field ) {
			$arr[] = $field->to_array();
		}

		return $arr;
	}

	/**
	 * Return fields grouped
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields_grouped() {
		$wrappers = array();

		if ( empty( $this->fields ) ) {
			return $wrappers;
		}

		foreach ( $this->fields as $field ) {
			/** @var Forminator_Form_Field_Model $field */
			if ( strpos( $field->form_id, 'wrapper-' ) === 0 ) {
				$form_id = $field->form_id;
			} else {
				// Backward Compat
				$form_id = $field->formID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			}

			if ( ! isset( $wrappers[ $form_id ] ) ) {
				$wrappers[ $form_id ] = array(
					'wrapper_id' => $form_id,
					'fields'     => array(),
				);
			}

			$field_data                       = $field->to_formatted_array();
			$wrappers[ $form_id ]['fields'][] = $field_data;
		}
		$wrappers = array_values( $wrappers );

		return $wrappers;
	}

	/**
	 * Model to array
	 *
	 * @since 1.0
	 * @return array
	 */
	public function to_array() {
		$data = array();
		$maps = array_merge( $this->get_default_maps(), $this->get_maps() );

		if ( empty( $maps ) ) {
			return $data;
		}

		foreach ( $maps as $map ) {
			$property          = $map['property'];
			$data[ $property ] = $this->$property;
		}

		return $data;
	}

	/**
	 * Model to json
	 *
	 * @since 1.0
	 * @return mixed|string
	 */
	public function to_json() {
		$wrappers = array();

		if ( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $field ) {
				$wrappers[] = $field->to_json();
			}
		}

		$settings      = $this->settings;
		$notifications = $this->notifications;
		$data          = array_merge(
			array(
				'wrappers' => array(
					'fields' => $wrappers,
				),
			),
			$settings,
			$notifications
		);
		$ret           = array(
			'formName' => $this->name,
			'data'     => $data,
		);

		return wp_json_encode( $ret );
	}

	/**
	 * In here we will define how we store the properties
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_default_maps() {
		return array(
			array(
				'type'     => 'post',
				'property' => 'id',
				'field'    => 'ID',
			),
			array(
				'type'     => 'post',
				'property' => 'name',
				'field'    => 'post_title',
			),
			array(
				'type'     => 'post',
				'property' => 'status',
				'field'    => 'post_status',
				'default'  => self::STATUS_PUBLISH,
			),
			array(
				'type'     => 'meta',
				'property' => 'fields',
				'field'    => 'fields',
			),
			array(
				'type'     => 'meta',
				'property' => 'settings',
				'field'    => 'settings',
			),
			array(
				'type'     => 'meta',
				'property' => 'client_id',
				'field'    => 'client_id',
			),
			array(
				'type'     => 'meta',
				'property' => 'notifications',
				'field'    => 'notifications',
			),
		);
	}

	/**
	 * This should be get override by children
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_maps() {
		return array();
	}

	/**
	 * Return model
	 *
	 * @since 1.0
	 *
	 * @param string $class_name
	 *
	 * @return self
	 */
	public static function model( $class_name = null ) {
		if ( is_null( $class_name ) ) {
			$class_name = static::class;
		}
		$class = new $class_name();

		return $class;
	}

	/**
	 * Get Post Type of cpt
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get export Model
	 *
	 * @since 1.4
	 * Override-able, but use of hook `forminator_{$module_type}_model_to_exportable_data` encouraged
	 * @return array
	 */
	public function to_exportable_data() {

		if ( ! Forminator::is_import_export_feature_enabled() ) {
			return array();
		}

		$hook = 'forminator_' . static::$module_slug . '_model_to_exportable_data';
		if ( Forminator::is_export_integrations_feature_enabled() ) {
			add_filter( $hook, array( $this, 'export_integrations_data' ), 1, 1 );
		}

		$model_id    = $this->id;
		$module_type = static::$module_slug;

		// cleanup form id
		$post_meta = get_post_meta( $this->id, self::META_KEY, true );
		if ( is_array( $post_meta ) ) {
			if ( isset( $post_meta['settings'] ) && isset( $post_meta['settings'] ) ) {
				if ( isset( $post_meta['settings']['form_id'] ) ) {
					unset( $post_meta['settings']['form_id'] );
				}
				if ( isset( $post_meta['settings']['formID'] ) ) {
					unset( $post_meta['settings']['formID'] );
				}
			}
		}

		$exportable_data = array(
			'type'    => $module_type,
			'data'    => $post_meta,
			'status'  => $this->status,
			'version' => FORMINATOR_VERSION,
		);

		$exportable_data = apply_filters( "forminator_{$module_type}_model_to_exportable_data", $exportable_data, $module_type, $model_id );

		// avoid filter executed on next cycle
		remove_filter( $hook, array( $this, 'export_integrations_data' ), 1 );

		return $exportable_data;
	}

	/**
	 * Export integrations setting
	 *
	 * @since 1.4
	 *
	 * @param $exportable_data
	 *
	 * @return array
	 */
	public function export_integrations_data( $exportable_data ) {
		$model_id                = $this->id;
		$exportable_integrations = array();

		$function         = 'forminator_get_addons_instance_connected_with_' . static::$module_slug;
		$connected_addons = function_exists( $function ) ? $function( $model_id ) : array();

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$settings = $connected_addon->get_addon_form_settings( $model_id );
				if ( $settings instanceof Forminator_Addon_Settings_Abstract ) {
					$exportable_integrations[ $connected_addon->get_slug() ] = $settings->to_exportable_data();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to get to_exportable_data', $e->getMessage() );
			}
		}

		/**
		 * Filter integrations data to export
		 *
		 * @since 1.4
		 *
		 * @param array $exportable_integrations
		 * @param array $exportable_data all exportable data from model, useful
		 */
		$exportable_integrations         = apply_filters( 'forminator_' . static::$module_slug . '_model_export_integrations_data', $exportable_integrations, $model_id );
		$exportable_data['integrations'] = $exportable_integrations;

		return $exportable_data;
	}

	/**
	 * Create model from import data
	 *
	 * @since 1.4
	 *
	 * @param        $import_data
	 *
	 * @return self|Forminator_Form_Model|Forminator_Poll_Model|Forminator_Quiz_Model|WP_Error
	 */
	public static function create_from_import_data( $import_data ) {
		$class = static::class;

		if ( Forminator::is_import_integrations_feature_enabled() ) {
			add_filter( 'forminator_import_model', array( $class, 'import_integrations_data' ), 1, 3 );
		}

		try {
			if ( ! Forminator::is_import_export_feature_enabled() ) {
				throw new Exception( __( 'Export Import feature disabled', 'forminator' ) );
			}

			if ( ! is_callable( array( $class, 'model' ) ) ) {
				throw new Exception( __( 'Model loader for importer does not exist.', 'forminator' ) );
			}

			// call static method ::model
			$model = call_user_func( array( $class, 'model' ) );

			/**
			 * Executes before create model from import data
			 *
			 * @since 1.4
			 *
			 * @param array  $import_data
			 * @param string $module
			 */
			do_action( 'forminator_before_create_model_from_import_data', $import_data, $class );

			if ( ! isset( $import_data['type'] ) || empty( $import_data['type'] ) ) {
				throw new Exception( __( 'Invalid format of import data type', 'forminator' ) );
			}

			$meta = ( isset( $import_data['data'] ) ? $import_data['data'] : array() );

			if ( empty( $meta ) ) {
				throw new Exception( __( 'Invalid format of import data', 'forminator' ) );
			}

			if ( ! isset( $meta['settings'] ) || empty( $meta['settings'] ) ) {
				throw new Exception( __( 'Invalid format of import data settings', 'forminator' ) );
			}

			if ( ! isset( $meta['settings']['formName'] ) || empty( $meta['settings']['formName'] ) ) {
				throw new Exception( __( 'Invalid format of import data name', 'forminator' ) );
			}

			$form_name = $meta['settings']['formName'];

			$type = $import_data['type'];
			switch ( $type ) {
				case 'quiz':
					$post_type = 'forminator_quizzes';
					break;
				case 'poll':
					$post_type = 'forminator_polls';
					break;
				default:
					$post_type = 'forminator_forms';
					break;
			}

			$post_status = ( isset( $import_data['status'] ) && ! empty( $import_data['status'] ) ) ? $import_data['status'] : self::STATUS_PUBLISH;

			/**
			 * todo : use @see self::save()
			 */
			$post_data = array(
				'post_title'  => $form_name,
				'post_type'   => $post_type,
				'post_status' => $post_status,
			);

			$post_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				throw new Exception( $post_id->get_error_message(), $post_id->get_error_code() );
			}

			if ( ! isset( $meta['settings'] ) ) {
				$meta['settings'] = array();
			}
			// update form_id
			$meta['settings']['form_id'] = $post_id;

			update_post_meta( $post_id, self::META_KEY, $meta );

			/** @var Forminator_Base_Form_Model|Forminator_Poll_Model|Forminator_Quiz_Model|Forminator_Form_Model $model */
			$model = $model->load( $post_id );

			if ( ! $model instanceof $class ) {
				throw new Exception( __( 'Failed to load imported Forminator model', 'forminator' ) );
			}


			/**
			 * Action called after module imported
			 *
			 * @since 1.11
			 *
			 * @param int    $post_id - module id
			 * @param string $post_status - module status
			 * @param object $model - module model
			 *
			 */
			do_action( 'forminator_' . $type . '_action_imported', $post_id, $post_status, $model );
		} catch ( Exception $e ) {
			$code = $e->getCode();
			if ( empty( $code ) ) {
				$code = 'forminator_import_model_error';
			}
			$model = new WP_Error( $code, $e->getMessage(), $import_data );
		}

		/**
		 * Filter imported model of form
		 *
		 * @since 1.4
		 *
		 * @param Forminator_Base_Form_Model|WP_Error $model
		 * @param array                               $import_data
		 * @param string                              $module
		 */
		$model = apply_filters( 'forminator_import_model', $model, $import_data, $class );

		// avoid filter executed on next cycle.
		remove_filter( 'forminator_import_model', array( $class, 'import_integrations_data' ), 1 );

		return $model;
	}

	/**
	 * Import Integrations data model
	 *
	 * @since 1.4
	 *
	 * @param $model
	 * @param $import_data
	 * @param $module
	 *
	 * @return Forminator_Base_Form_Model
	 */
	public static function import_integrations_data( $model, $import_data, $module ) {
		// return what it is.
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		if ( static::class !== $module ) {
			return $model;
		}

		if ( ! isset( $import_data['integrations'] ) || empty( $import_data['integrations'] ) || ! is_array( $import_data['integrations'] ) ) {
			return $model;
		}

		$integrations_data = $import_data['integrations'];
		foreach ( $integrations_data as $slug => $integrations_datum ) {
			try {
				$addon = forminator_get_addon( $slug );
				if ( $addon instanceof Forminator_Addon_Abstract ) {
					$method = 'get_addon_' . static::$module_slug . '_settings';
					if ( method_exists( $addon, $method ) ) {
						$settings = $addon->$method( $model->id );
					}
					if ( ! empty( $settings ) && $settings instanceof Forminator_Addon_Form_Settings_Abstract ) {
						$settings->import_data( $integrations_datum );
					}
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $slug, 'failed to get import module settings', $e->getMessage() );
			}
		}

		return $model;
	}

	/**
	 * Get status of prevent_store
	 *
	 * @since 1.5
	 *
	 * @param int $id
	 * @param array $settings
	 *
	 * @return boolean
	 */
	public function is_prevent_store( $id = null, $settings = array() ) {
		$module_id = ! empty( $id ) ? $id : (int) $this->id;
		$settings  = ! empty( $settings ) ? $settings : $this->settings;

		// default is always store.
		$is_prevent_store = false;

		$is_prevent_store = isset( $settings['store'] ) ? $settings['store'] : $is_prevent_store;
		$is_prevent_store = filter_var( $is_prevent_store, FILTER_VALIDATE_BOOLEAN );

		/**
		 * Filter is_prevent_store flag of the module
		 *
		 * @since 1.5
		 *
		 * @param bool  $is_prevent_store
		 * @param int   $module_id
		 * @param array $settings
		 */
		$is_prevent_store = apply_filters( 'forminator_' . static::$module_slug . '_is_prevent_store', $is_prevent_store, $module_id, $settings );

		return $is_prevent_store;
	}

	/**
	 * Flag if module should be loaded via ajax
	 *
	 * @since 1.6.1
	 *
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function is_ajax_load( $force = false ) {
		$module_id      = (int) $this->id;
		$settings       = $this->settings;
		$global_enabled = self::is_global_ajax_load( $force );

		$enabled = isset( $settings['use_ajax_load'] ) ? $settings['use_ajax_load'] : false;
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		$enabled = $global_enabled || $enabled;

		/**
		 * Filter is ajax load for module
		 *
		 * @since 1.6.1
		 *
		 * @param bool  $enabled
		 * @param bool  $global_enabled
		 * @param int   $form_id
		 * @param array $form_settings
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_' . static::$module_slug . '_is_ajax_load', $enabled, $global_enabled, $module_id, $settings );

		return $enabled;
	}

	/**
	 * Flag if module should be loaded via ajax (Global settings)
	 *
	 * @since 1.6.1
	 *
	 * @param bool $force
	 *
	 * @return bool
	 */
	private static function is_global_ajax_load( $force = false ) {
		//default disabled

		// from settings
		$settings_enabled = get_option( 'forminator_module_enable_load_ajax', false );

		// from constant
		$enabled = defined( 'FORMINATOR_MODULE_ENABLE_LOAD_AJAX' ) && FORMINATOR_MODULE_ENABLE_LOAD_AJAX;

		// if one is true, then its enabled
		$enabled = $force || $settings_enabled || $enabled;

		/**
		 * Filter flag is ajax load of module
		 *
		 * @since  1.6
		 *
		 * @param bool $enabled
		 * @param bool $settings_enabled
		 * @param bool $force
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_module_is_ajax_load', $enabled, $settings_enabled, $force );

		return $enabled;
	}

	/**
	 * Flag to use `DONOTCACHEPAGE`
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_use_donotcachepage_constant() {
		$module_id      = (int) $this->id;
		$settings       = $this->settings;
		$global_enabled = self::is_global_use_donotcachepage_constant();

		$enabled = isset( $settings['use_donotcachepage'] ) ? $settings['use_donotcachepage'] : false;
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		$enabled = $global_enabled || $enabled;

		/**
		 * Filter use `DONOTCACHEPAGE` Module
		 *
		 * @since 1.6.1
		 *
		 * @param bool  $enabled
		 * @param bool  $global_enabled
		 * @param int   $module_id
		 * @param array $settings
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_custom_form_is_use_donotcachepage_constant', $enabled, $global_enabled, $module_id, $settings );

		return $enabled;
	}

	/**
	 * Flag to use `DONOTCACHEPAGE`
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	private static function is_global_use_donotcachepage_constant() {
		//default disabled

		// from settings
		$settings_enabled = get_option( 'forminator_module_use_donotcachepage', false );

		// from constant
		$enabled = defined( 'FORMINATOR_MODULE_USE_DONOTCACHEPAGE' ) && FORMINATOR_MODULE_USE_DONOTCACHEPAGE;

		// if one is true, then its enabled
		$enabled = $settings_enabled || $enabled;

		/**
		 * Filter flag is use `DONOTCACHEPAGE` of module
		 *
		 * @since  1.6
		 *
		 * @param bool $enabled
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_module_is_use_donotcachepage_constant', $enabled );

		return $enabled;
	}

	/**
	 * Check if the result enable for share
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function is_entry_share_enabled() {
		$module_id   = (int) $this->id;
		$module_type = $this->post_type;

		// from settings
		$settings_enabled = get_option( 'forminator_module_enable_share_entry', false );

		// from constant
		$enabled = defined( 'FORMINATOR_MODULE_ENABLE_SHARE_ENTRY' ) && FORMINATOR_MODULE_ENABLE_SHARE_ENTRY;

		// if one is true, then its enabled
		$enabled = $settings_enabled || $enabled;

		/**
		 * Filter flag is use `ENABLE_SHARE_ENTRY` of module
		 *
		 * @since  1.7
		 *
		 * @param bool   $enabled
		 * @param int    $module_id
		 * @param string $module_type
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_module_is_entry_share_enabled', $enabled, $module_id, $module_type );

		return $enabled;
	}
}
