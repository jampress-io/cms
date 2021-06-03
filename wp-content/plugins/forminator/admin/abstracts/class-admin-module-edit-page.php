<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Module_Edit_Page
 *
 * @since 1.14.10
 */
abstract class Forminator_Admin_Module_Edit_Page extends Forminator_Admin_Page {

	/**
	 * Page number
	 *
	 * @var int
	 */
	protected $page_number = 1;

	/**
	 * Initialize
	 *
	 * @since 1.0
	 */
	public function init() {
		$pagenum           = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0; // WPCS: CSRF OK
		$this->page_number = max( 1, $pagenum );
		$this->processRequest();
	}

	/**
	 * Trigger before render
	 */
	public function before_render() {
		wp_enqueue_script( 'forminator-chart', forminator_plugin_url() . 'assets/js/library/Chart.bundle.min.js', array( 'jquery' ), '2.7.2', false );
	}

	/**
	 * Count modules
	 *
	 * @param string $status Modules status.
	 * @since 1.0
	 * @return int
	 */
	public function countModules( $status = '' ) {
		$class_name = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
		return $class_name::model()->count_all( $status );
	}

	/**
	 * Get modules
	 *
	 * @since 1.0
	 * @return array
	 */
	public function getModules( $search_keyword = null ) {
		$modules = array();
		$limit   = null;
		$const   = 'FORMINATOR_' . strtoupper( static::$module_slug ) . '_LIST_LIMIT';
		if ( defined( $const ) && constant( $const ) ) {
			$limit = constant( $const );
		}
		if ( ! is_null( $search_keyword ) ) {
			$limit = -1;
		}
		$data      = $this->get_models( $limit );
		$form_view = Forminator_Form_Views_Model::get_instance();

		// Fallback
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		if ( ! is_null( $search_keyword ) ) {
			$search_keyword = explode( " ", $search_keyword );
		}

		foreach ( $data['models'] as $model ) {

			// Form search
			if ( ! is_null( $search_keyword ) ) {

				foreach ( $search_keyword as $keyword ) {
					// If found
					if ( false !== stripos( $model->settings['formName'], $keyword ) ) {
						$modules[] = $this->module_array(
										$model->id,
										$model->name,
										$form_view->count_views( $model->id ),
										date( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
										$model->status,
										$model
									);
						// prevent duplicates
						break;
					}
				}

			// Display modules
			} else {
				$modules[] = $this->module_array(
								$model->id,
								$model->name,
								$form_view->count_views( $model->id ),
								date( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
								$model->status,
								$model
							);
			}
		}

		return $modules;
	}

	/**
	 * Calculate rate
	 *
	 * @since 1.0
	 *
	 * @param $module
	 *
	 * @return float|int
	 */
	public function getRate( $module ) {
		if ( $module['views'] > 0 ) {
			$rate = round( ( $module["entries"] * 100 ) / $module["views"], 1 );
		} else {
			$rate = 0;
		}

		return $rate;
	}

	/**
	 * Pagination
	 *
	 * @since 1.0
	 */
	public function pagination( $is_search, $count ) {
		echo '<span class="sui-pagination-results">'
			/* translators: ... */
			. esc_html( sprintf( _n( '%s result', '%s results', $count, 'forminator' ), $count ) )
			. '</span>';

		if ( $is_search ) {
			return;
		}
		forminator_list_pagination( $count );
	}

	/**
	 * Get models
	 *
	 * @since 1.0
	 * @since 1.6 add $limit
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_models( $limit = null ) {
		$class_name = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
		$data       = $class_name::model()->get_all_paged( $this->page_number, $limit );

		return $data;
	}

	/**
	 * Clone Module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function clone_module( $id ) {
		//check if this id is valid and the record is exists
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( is_object( $model ) ) {
			//create one
			//reset id
			$model->id = null;

			//update title
			if ( isset( $model->settings['formName'] ) ) {
				$model->settings['formName'] = sprintf( __( "Copy of %s", 'forminator' ), $model->settings['formName'] );
			}

			//save it to create new record
			$new_id = $model->save( true );

			/**
			 * Action called after module cloned
			 *
			 * @since 1.11
			 *
			 * @param int    $id - module id
			 * @param object $model - module model
			 *
			 */
			do_action( 'forminator_' . static::$module_slug . '_action_clone', $new_id, $model );

			$function = 'forminator_clone_' . static::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, $new_id );
			}

			// Purge count forms cache
			$cache_prefix = 'forminator_' . static::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );
		}
	}

	/**
	 * Delete module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function delete_module( $id ) {
		//check if this id is valid and the record is exists
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			Forminator_Form_Entry_Model::delete_by_form( $id );
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );

			$function = 'forminator_update_' . static::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, null, null );
			}
			wp_delete_post( $id );

			// Purge count forms cache
			$cache_prefix = 'forminator_' . static::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );

			/**
			 * Action called after module deleted
			 *
			 * @since 1.11
			 *
			 * @param int    $id - module id
			 *
			 */
			do_action( 'forminator_' . static::$module_slug . '_action_delete', $id );
		}
	}

	/**
	 * Delete module entries
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function delete_module_entries( $id ) {
		//check if this id is valid and the record is exists
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			Forminator_Form_Entry_Model::delete_by_form( $id );
		}
	}

	/**
	 * Export module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function export_module( $id ) {

		$exportable = array();
		$model_name = '';
		$model      = Forminator_Base_Form_Model::get_model( $id );
		if ( $model instanceof Forminator_Base_Form_Model ) {
			$model_name = $model->name;
			$exportable = $model->to_exportable_data();
		}
		$encoded = wp_json_encode( $exportable );
		$fp      = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $fp, $encoded ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fseek( $fp, 0 );

		$filename = sanitize_title( __( 'forminator', 'forminator' ) ) . '-' . sanitize_title( $model_name ) . '-' . static::$module_slug . '-export' . '.txt';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Content-Length: ' . strlen( $encoded ) );

		// make php send the generated csv lines to the browser
		fpassthru( $fp );
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.11
	 *
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}

	/**
	 * Process request
	 *
	 * @since 1.0
	 */
	public function processRequest() {
		if ( ! isset( $_POST['forminator_action'] ) ) {
			return;
		}
        // Check if the page is not the relevant module type page and not forminator dashboard page.
		if ( ! isset( $_REQUEST['page'] ) || ( 'forminator-' . forminator_get_prefix( static::$module_slug, 'c' ) !== $_REQUEST['page'] && 'forminator' !== $_REQUEST['page'] ) ) {
			return;
		}
        // In forminator dashboard, check if form type is not the relevant module type.
		if ( 'forminator' === $_REQUEST['page'] && isset( $_REQUEST['form_type'] ) && forminator_get_prefix( static::$module_slug, 'custom-' ) !== $_REQUEST['form_type'] ) {
			return;
		}

		$action = isset( $_POST['forminator_action'] ) ? $_POST['forminator_action'] : '';
        $id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        // Set nonce names first for verification.
		switch ( $action ) {
			case 'clone':
				$nonce_name   = 'forminatorNonce';
                $nonce_action = 'forminator-nonce-clone-' . $id;
				break;

			case 'reset-views':
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator-nonce-reset-views-' . $id;
				break;

			case 'update-status' :
                $nonce_name   = 'forminator-nonce-update-status-' . $id;
                $nonce_action = $nonce_name;
				break;

			default:
                $nonce_name   = 'forminatorNonce';
                $nonce_action = 'forminator_' . static::$module_slug . '_request';
				break;
		}

        // Verify nonce.
        if ( ! isset( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( $_POST[ $nonce_name ], $nonce_action ) ) {
            return;
        }

		$plural_slug = forminator_get_prefix( static::$module_slug, '', false, true );
		$is_redirect = true;
        $ids         = isset( $_POST['ids'] ) ? $_POST['ids'] : '';
		switch ( $action ) {
			case 'delete':
				if ( ! empty( $id ) ) {
					$this->delete_module( $id );
					$notice = static::$module_slug . '_deleted';
				}
				break;

			case 'clone':
				if ( ! empty( $id ) ) {
					$this->clone_module( $id );
					$notice = static::$module_slug . '_duplicated';
				}
				break;

			case 'reset-views' :
				if ( ! empty( $id ) ) {
					self::reset_module_views( $id );
					$notice = static::$module_slug . '_reset';
				}
				break;

			case 'delete-votes' :
			case 'delete-entries' :
				if ( ! empty( $id ) ) {
					$this->delete_module_entries( $id );
				}
				break;

			case 'export':
				if ( ! empty( $id ) ) {
					$this->export_module( $id );
				}
				$is_redirect = false;
				break;

			case 'delete-' . $plural_slug :
				if ( ! empty( $ids ) ) {
					$module_ids = explode( ',', $ids );
					if ( is_array( $module_ids ) && count( $module_ids ) > 0 ) {
						foreach ( $module_ids as $id ) {
							$this->delete_module( $id );
						}
					}
				}
				break;

			case 'delete-votes-polls' :
			case 'delete-entries-' . $plural_slug :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $id ) {
							$this->delete_module_entries( $id );
						}
					}
				}
				break;

			case 'clone-' . $plural_slug :
				if ( ! empty( $ids ) ) {
					$module_ids = explode( ',', $ids );
					if ( is_array( $module_ids ) && count( $module_ids ) > 0 ) {
						foreach ( $module_ids as $id ) {
							$this->clone_module( $id );
						}
					}
				}
				break;

			case 'reset-views-' . $plural_slug :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $id ) {
							self::reset_module_views( $id );
						}
					}
				}
				break;

			case 'update-status' :
				$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

				if ( ! empty( $id ) && ! empty( $status ) ) {
					// only publish and draft status avail
					if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
						$model = Forminator_Base_Form_Model::get_model( $id );
						if ( $model instanceof Forminator_Base_Form_Model ) {
							$model->status = $status;
							$model->save();
						}
					}
				}
				break;
			case 'update-statuses' :
				$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

				if ( ! empty( $ids ) && ! empty( $status ) ) {
					// only publish and draft status avail
					if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
						$form_ids = explode( ',', $ids );
						if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
							foreach ( $form_ids as $id ) {
								$model = Forminator_Base_Form_Model::get_model( $id );
								if ( $model instanceof Forminator_Base_Form_Model ) {
									$model->status = $status;
									$model->save();
								}
							}
						}
					}
				}
				break;

			case 'draft-forms' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $form_id ) {
							$this->update_module_status( $form_id, 'draft' );
						}
					}
				}
				break;

			case 'publish-forms' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $form_id ) {
							$this->update_module_status( $form_id, 'publish' );
						}
					}
				}
				break;

			default:
				break;
		}

		if ( $is_redirect ) {
			$to_referer = true;

			if ( isset( $_POST['forminatorRedirect' ] ) && "false" === $_POST['forminatorRedirect' ] ) {
				$to_referer = false;
			}

			$args = array(
				'page' => $this->get_admin_page(),
			);
			if ( ! empty( $notice ) ) {
				$args['forminator_notice'] = $notice;
				$to_referer                = false;
			}
			$fallback_redirect = add_query_arg(
				$args,
				admin_url( 'admin.php' )
			);

			$this->maybe_redirect_to_referer( $fallback_redirect, $to_referer );
		}

		exit;
	}

	/**
	 * Update Module Status
	 *
	 * @since 1.6
	 *
	 * @param $id
	 * @param $status
	 */
	public function update_module_status( $id, $status ) {
		// only publish and draft status avail
		if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$model = Forminator_Base_Form_Model::get_model( $id );
			if ( $model instanceof Forminator_Base_Form_Model ) {
				$model->status = $status;
				$model->save();
			}
		}
	}

	/**
	 * Reset views data
	 *
	 * @since 1.6
	 *
	 * @param int $id Module ID.
	 */
	public static function reset_module_views( $id ) {
		$form_types = forminator_form_types();
		$module     = get_post( $id );
		if ( ! empty( $module->post_type ) && in_array( $module->post_type, $form_types, true ) ) {
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );
		}
	}
}
