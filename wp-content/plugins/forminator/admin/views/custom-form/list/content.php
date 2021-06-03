<?php
/** @var Forminator_CForm_Page $this */

// Search keyword
$search_keyword = null;
$is_search = false;
$search_module_nonce = esc_attr( 'forminator-nonce-search-module' );
if ( isset( $_GET['search'] ) && strlen( trim( $_GET['search'] ) ) && check_admin_referer( $search_module_nonce, $search_module_nonce ) ) {
	$search_keyword = sanitize_text_field( $_GET['search'] );
	$is_search 		= true;
}

// Get modules
$modules = $this->getModules( $search_keyword );

// Count total forms
$count		  = ! $is_search ? $this->countModules() : count( $modules );

// Start date for retrieving the information of the last 30 days in sql format
$sql_month_start_date = date( 'Y-m-d H:i:s', strtotime( '-30 days midnight' ) );// phpcs:ignore

$entry_type    = 'custom-forms';
$wizard_page   = 'forminator-cform-wizard';
$create_dialog = 'custom_forms';
$preview_title = __( 'Preview Custom Form', 'forminator' );
$empty_title   = __( 'Create custom forms for all your needs with as many fields as you like. From contact forms to quote requests and everything in between.', 'forminator' );

require_once forminator_plugin_dir() . 'admin/views/common/list/summary.php';

if ( $count > 0 ) {
	require_once forminator_plugin_dir() . 'admin/views/common/list/main_content.php';
} else {
	require_once forminator_plugin_dir() . 'admin/views/common/list/empty_content.php';
}

$chart_args = array(
	'start_date' => $sql_month_start_date,
);
$this->template( 'common/list/chart_script', $chart_args );
