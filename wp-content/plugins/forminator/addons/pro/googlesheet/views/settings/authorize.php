<?php
// Defaults
$vars = array(
	'auth_url' => '',
	'token'    => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="integration-header">

	<h3 id="dialogTitle2" class="sui-box-title"><?php echo esc_html( sprintf( /* translators: ... */ __( 'Connect %1$s', 'forminator' ), 'Google Sheets' ) ); ?></h3>

	<?php if ( ! empty( $vars['token'] ) ) : ?>
		<span class="sui-description" style="margin-top: 20px;"><?php esc_html_e( 'Click button below to re-authorize.', 'forminator' ); ?></span>
	<?php else : ?>
		<span class="sui-description" style="margin-top: 20px;"><?php esc_html_e( 'Authorize Forminator to connect with your Google account in order to send data from your forms.', 'forminator' ); ?></span>
	<?php endif; ?>

</div>

<?php if ( empty( $vars['token'] ) ) : ?>
	<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
		target="_blank"
		class="sui-button sui-button-blue forminator-addon-connect">
		<?php esc_html_e( 'AUTHORIZE', 'forminator' ); ?>
	</a>
<?php else : ?>
	<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
		target="_blank"
		class="sui-button sui-button-blue forminator-addon-connect">
		<?php esc_html_e( 'RE-AUTHORIZE', 'forminator' ); ?>
	</a>
<?php endif; ?>
