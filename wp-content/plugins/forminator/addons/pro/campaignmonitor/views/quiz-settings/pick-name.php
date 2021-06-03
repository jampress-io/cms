<?php
// Defaults
$vars = array(
	'error_message' => '',
	'name'          => '',
	'name_error'    => '',
	'multi_id'      => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="integration-header">

	<h3 class="sui-box-title" id="dialogTitle2"><?php echo esc_html( __( 'Setup Name', 'forminator' ) ); ?></h3>

	<span class="sui-description" style="margin-top: 20px;"><?php esc_html_e( 'Setup friendly name for this integration, so it will be easily identified by you.', 'forminator' ); ?></span>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<span class="sui-notice sui-notice-error"><p><?php echo esc_html( $vars['error_message'] ); ?></p></span>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['name_error'] ) ? ' sui-form-field-error' : '' ); ?>"
		style="margin-bottom: 0;">

		<label class="sui-label"><?php esc_html_e( 'Name', 'forminator' ); ?></label>

		<input name="name"
			placeholder="<?php echo esc_attr( __( 'Friendly Name', 'forminator' ) ); ?>"
			value="<?php echo esc_attr( $vars['name'] ); ?>"
			class="sui-form-control" />

		<?php if ( ! empty( $vars['name_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['name_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>
