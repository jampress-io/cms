<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_New_Page
 *
 * @since 1.0
 */
class Forminator_CForm_New_Page extends Forminator_Admin_Page {

	/**
	 * Get wizard title
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function getWizardTitle() {
		if ( isset( $_REQUEST['id'] ) ) { // WPCS: CSRF OK
			return __( "Edit Form", 'forminator' );
		} else {
			return __( "New Form", 'forminator' );
		}
	}

	/**
	 * Add page screen hooks
	 *
	 * @since 1.0
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		// Load admin scripts
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'assets/js/form-scripts.js',
			array(
				'jquery',
				'wp-color-picker',
				'react',
				'react-dom',
			),
			FORMINATOR_VERSION,
			true
		);
		forminator_common_admin_enqueue_scripts( true );

		// for preview
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = "4.0.3";

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;
		wp_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput

		wp_enqueue_script( 'forminator-field-moment',
			forminator_plugin_url() . 'assets/js/library/moment.min.js',
			array( 'jquery' ),
			'2.22.2',
			true );

        wp_enqueue_script( 'forminator-field-datepicker-range',
			forminator_plugin_url() . 'assets/js/library/daterangepicker.min.js',
			array('forminator-field-moment'),
			'3.0.3',
			true );
	}
}
