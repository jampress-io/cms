<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_Page
 *
 * @since 1.0
 */
class Forminator_Quiz_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Return module array
	 *
	 * @since 1.14.10
	 *
	 * @param $id
	 * @param $title
	 * @param $views
	 * @param $date
	 * @param $status
	 * @param name
	 *
	 * @return array
	 */
	protected function module_array( $id, $title, $views, $date, $status, $model ) {
		return array(
					"id"              => $id,
					"title"           => $title,
					"entries"         => Forminator_Form_Entry_Model::count_entries( $id ),
					"has_leads"       => $this->has_leads( $model ),
					"leads_id"        => $this->get_leads_id( $model ),
					"leads"           => Forminator_Form_Entry_Model::count_leads( $id ),
					"last_entry_time" => forminator_get_latest_entry_time_by_form_id( $id ),
					"views"           => $views,
					'type'            => $model->quiz_type,
					"date"            => $date,
					'status'          => $status,
					'name'            => forminator_get_name_from_model( $model ),
				);
	}

	/**
	 * Check if quiz has leads
	 *
	 * @param $model
	 *
	 * @return bool
	 */
	public function has_leads( $model ) {
		if ( isset( $model->settings['hasLeads'] ) && "true" === $model->settings['hasLeads'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check has lead
	 *
	 * @param $model
	 *
	 * @return int
	 */
	public function get_leads_id( $model ) {
		$leadsId = 0;
		if ( $this->has_leads( $model ) && isset( $model->settings['leadsId'] ) ) {
			$leadsId = $model->settings['leadsId'];
		}

		return $leadsId;
	}

	/**
	 * Return leads rate
	 *
	 * @since 1.14
	 *
	 * @param $module
	 *
	 * @return float|int
	 */
	public function getLeadsRate( $module ) {
		if ( $module['views'] > 0 ) {
			$rate = round( ( $module["leads"] * 100 ) / $module["views"], 1 );
		} else {
			$rate = 0;
		}

		return $rate;
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_quizzes_bulk_actions',
			array(
				//'clone-quizzes'          => __( "Duplicate", 'forminator' ),
				'reset-views-quizzes'    => __( "Reset Tracking Data", 'forminator' ),
				'delete-entries-quizzes' => __( "Delete Submissions", 'forminator' ),
				'delete-quizzes'         => __( "Delete", 'forminator' ),
			) );
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
		if ( is_object( $model ) ) {// Delete leads form on quiz delete
			if ( isset( $model->settings['hasLeads'] ) && isset( $model->settings['leadsId'] ) && $model->settings['hasLeads'] ) {
				$leads_id = $model->settings['leadsId'];
				$leads_model = Forminator_Form_Model::model()->load( $leads_id );

				if ( is_object( $leads_model ) ) {
					wp_delete_post( $leads_id );
				}
			}
			parent::delete_module( $id );
		}
	}
}
