<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_Page
 *
 * @since 1.0
 */
class Forminator_Poll_Page extends Forminator_Admin_Page {

	/**
	 * Page number
	 *
	 * @var int
	 */
	protected $page_number = 1;

	/**
	 * Init
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
	 * @param $status
	 *
	 * @since 1.0
	 * @return int
	 */
	public function countModules( $status = '' ) {
		return Forminator_Poll_Form_Model::model()->count_all( $status );
	}

	/**
	 * Get models
	 *
	 * @return Forminator_Base_Form_Model[]
	 *
	 * @since 1.0
	 */
	public function get_models() {
		$data = Forminator_Poll_Form_Model::model()->get_all_paged( $this->page_number );

		return $data;
	}

	/**
	 * Get rate
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
	 * Get modules
	 *
	 * @since 1.0
	 * @return array
	 */
	public function getModules() {
		$modules   = array();
		$data      = $this->get_models();
		$form_view = Forminator_Form_Views_Model::get_instance();

		// Fallback
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		foreach ( $data['models'] as $model ) {
			$modules[] = array(
				"id"              => $model->id,
				"title"           => $model->name,
				"entries"         => Forminator_Form_Entry_Model::count_entries( $model->id ),
				"last_entry_time" => forminator_get_latest_entry_time_by_form_id( $model->id ),
				"views"           => $form_view->count_views( $model->id ),
				"date"            => date( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
				'status'          => $model->status,
				'name'            => forminator_get_name_from_model( $model ),
			);
		}

		return $modules;
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
        // Check if the page is not poll page and not forminator dashboard page.
		if ( ! isset( $_REQUEST['page'] ) || ( 'forminator-poll' !== $_REQUEST['page'] && 'forminator' !== $_REQUEST['page'] ) ) {
			return;
		}
        // In forminator dashboard, check if form type is not poll.
		if ( 'forminator' === $_REQUEST['page'] && isset( $_REQUEST['form_type'] ) && 'poll' !== $_REQUEST['form_type'] ) {
			return;
		}

		$action = isset( $_POST['forminator_action'] ) ? $_POST['forminator_action'] : '';
        $id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        // Set nonce names first for verification.
		switch ( $action ) {
			case 'clone':
                $nonce_name   = 'forminator-nonce-clone-' . $id;
                $nonce_action = $nonce_name;
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
                $nonce_action = 'forminatorPollFormRequest';
				break;
		}

        // Verify nonce.
        if ( ! isset( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( $_POST[ $nonce_name ], $nonce_action ) ) {
            return;
        }

		$is_redirect = true;
        $ids         = isset( $_POST['ids'] ) ? $_POST['ids'] : '';
		switch ( $action ) {
			case 'delete':
				if ( ! empty( $id ) ) {
					$this->delete_module( $id );
					$notice = 'poll_deleted';
				}
				break;

			case 'clone':
				if ( ! empty( $id ) ) {
					$this->clone_module( $id );
					$notice = 'poll_duplicated';
				}
				break;

			case 'reset-views' :
				if ( ! empty( $id ) ) {
					$this->reset_module_views( $id );
					$notice = 'poll_reset';
				}
				break;

			case 'delete-votes' :
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

			case 'delete-polls' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $id ) {
							$this->delete_module( $id );
						}
					}
				}
				break;

			case 'delete-votes-polls' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $id ) {
							$this->delete_module_entries( $id );
						}
					}
				}
				break;

			case 'clone-polls' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $form_id ) {
							$this->clone_module( $form_id );
						}
					}
				}
				break;

			case 'reset-views-polls' :
				if ( ! empty( $ids ) ) {
					$form_ids = explode( ',', $ids );
					if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
						foreach ( $form_ids as $id ) {
							$this->reset_module_views( $id );
						}
					}
				}
				break;
			case 'update-status' :
				$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

				if ( ! empty( $id ) && ! empty( $status ) ) {
					// only publish and draft status avail
					if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
						$model = Forminator_Poll_Form_Model::model()->load( $id );
						if ( $model instanceof Forminator_Poll_Form_Model ) {
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
								$model = Forminator_Poll_Form_Model::model()->load( $id );
								if ( $model instanceof Forminator_Poll_Form_Model ) {
									$model->status = $status;
									$model->save();
								}
							}
						}
					}
				}
				break;
			default:
				break;
		}

		if ( $is_redirect ) {
			$to_referer = true;

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
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_polls_bulk_actions',
			array(
				'clone-polls'        => __( "Duplicate", Forminator::DOMAIN ),
				'reset-views-polls'  => __( "Reset Tracking Data", Forminator::DOMAIN ),
				'delete-votes-polls' => __( "Delete Votes", Forminator::DOMAIN ),
				'delete-polls'       => __( "Delete", Forminator::DOMAIN ),
			) );
	}

	/**
	 * Pagination
	 *
	 * @since 1.0
	 */
	public function pagination() {
		$count = $this->countModules();
		forminator_list_pagination( $count );
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
		$model = Forminator_Poll_Form_Model::model()->load( $id );
		if ( is_object( $model ) ) {
			//create one
			//reset id
			$model->id = null;

			//update title
			if ( isset( $model->settings['formName'] ) ) {
				$model->settings['formName'] = sprintf( __( "Copy of %s", Forminator::DOMAIN ), $model->settings['formName'] );
			}

			//save it to create new record
			$new_id = $model->save( true );

			/**
			 * Action called after poll cloned
			 *
			 * @since 1.11
			 *
			 * @param int    $id - poll id
			 * @param object $model - poll model
			 *
			 */
			do_action( 'forminator_poll_action_clone', $new_id, $model );

			forminator_clone_poll_ip_address_retention( $id, $new_id );
		}
	}

	/**
	 * Reset views data
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function reset_module_views( $id ) {
		$form_view = Forminator_Form_Views_Model::get_instance();
		$model     = Forminator_Poll_Form_Model::model()->load( $id );
		if ( is_object( $model ) ) {
			$form_view->delete_by_form( $id );
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
		$model = Forminator_Poll_Form_Model::model()->load( $id );
		if ( is_object( $model ) ) {
			Forminator_Form_Entry_Model::delete_by_form( $id );
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );
			forminator_update_poll_ip_address_retention( $id, null, null );
			wp_delete_post( $id );

			/**
			 * Action called after quiz deleted
			 *
			 * @since 1.11
			 *
			 * @param int    $id - quiz id
			 *
			 */
			do_action( 'forminator_poll_action_delete', $id );
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
		$model = Forminator_Poll_Form_Model::model()->load( $id );
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
		$model      = Forminator_Poll_Form_Model::model()->load( $id );
		if ( $model instanceof Forminator_Poll_Form_Model ) {
			$model_name = $model->name;
			$exportable = $model->to_exportable_data();
		}
		$encoded = wp_json_encode( $exportable );
		$fp      = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $fp, $encoded ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fseek( $fp, 0 );

		$filename = sanitize_title( __( 'forminator', FORMINATOR::DOMAIN ) ) . '-' . sanitize_title( $model_name ) . '-poll-export' . '.txt';

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

		forminator_print_polls_admin_styles( FORMINATOR_VERSION );
		forminator_print_front_scripts( FORMINATOR_VERSION );
	}
}
