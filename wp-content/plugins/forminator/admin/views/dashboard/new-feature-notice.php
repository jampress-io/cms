<?php
$banner_1x = forminator_plugin_url() . 'assets/images/graphic-upgradetour-feature.png';
$banner_2x = forminator_plugin_url() . 'assets/images/graphic-upgradetour-feature@2x.png';

/*
if ( ! FORMINATOR_PRO ) {
	$banner_1x = forminator_plugin_url() . 'assets/images/graphic-upgradetour-feature.png';
	$banner_2x = forminator_plugin_url() . 'assets/images/graphic-upgradetour-feature@2x.png';
}
*/
?>

<div
	id="forminator-new-feature"
	class="sui-dialog sui-dialog-onboard"
	aria-hidden="true"
>

	<div class="sui-dialog-overlay sui-fade-out" data-a11y-dialog-hide="forminator-new-feature" aria-hidden="true"></div>

	<div
		class="sui-dialog-content sui-fade-out"
		role="dialog"
	>

		<div class="sui-slider forminator-feature-modal" data-prop="forminator_dismiss_feature_114" data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

			<ul role="document" class="sui-slider-content">

				<li class="sui-current sui-loaded" data-slide="1">

					<div class="sui-box">

						<div class="sui-box-banner" role="banner" aria-hidden="true">
							<script src="https://fast.wistia.com/embed/medias/sbu0fqxgiu.jsonp" async></script><script src="https://fast.wistia.com/assets/external/E-v1.js" async></script><div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;"><div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;"><span class="wistia_embed wistia_async_sbu0fqxgiu popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%">&nbsp;</span></div></div>
						</div>

						<div class="sui-box-header sui-block-content-center">

							<button data-a11y-dialog-hide="forminator-new-feature" style="z-index: 2" class="sui-dialog-close forminator-dismiss-new-feature" aria-label="<?php esc_html_e( 'Close this dialog window', 'forminator' ); ?>"></button>

							<?php //if ( FORMINATOR_PRO ) { ?>

								<h2 class="sui-box-title"><?php esc_html_e( 'New! Capture leads on quizzes', 'forminator' ); ?></h2>

								<p class="sui-description"><?php printf( esc_html__( 'That\'s right! You can %scapture participants data%s (such as name, email, etc.) on your quizzes with this release.', 'forminator' ), '<strong>', '</strong>' ); ?></p>

								<p class="sui-description"><?php printf( esc_html__( 'While creating a quiz, you\'ll see a new option to collect leads and enabling that will add a new tab called "Leads" into the quiz editor where you can configure your lead generation from settings.', 'forminator' ), '<strong>', '</strong>' ); ?></p>

							<?php //} else { ?>

						 	<?php //} ?>

						</div>

						<?php if ( FORMINATOR_PRO ) { ?>

							<div class="sui-box-footer sui-block-content-center" sui-space-bottom="60">

								<button class="sui-button forminator-dismiss-new-feature" type="button" data-a11y-dialog-hide="forminator-new-feature"><?php esc_html_e( 'Got It', 'forminator' ); ?></button>

							</div>

						<?php } else { ?>

							<div class="sui-box-footer sui-block-content-center" sui-space-bottom="60">

								<button class="sui-button forminator-dismiss-new-feature" type="button" data-a11y-dialog-hide="forminator-new-feature"><?php esc_html_e( 'Got It', 'forminator' ); ?></button>

							</div>

						<?php } ?>

					</div>

				</li>

			</ul>

		</div>

	</div>

</div>

<script type="text/javascript">
	jQuery( '#forminator-new-feature .forminator-dismiss-new-feature' ).on( 'click', function( e ) {
		e.preventDefault();

		var $notice = jQuery( e.currentTarget ).closest( '.forminator-feature-modal' );
		var ajaxUrl = '<?php echo forminator_ajax_url();// phpcs:ignore ?>';

		jQuery.post(
			ajaxUrl,
			{
				action: 'forminator_dismiss_notification',
				prop: $notice.data('prop'),
				_ajax_nonce: $notice.data('nonce')
			}
		).always( function() {
			$notice.hide();
		});
	});
</script>
