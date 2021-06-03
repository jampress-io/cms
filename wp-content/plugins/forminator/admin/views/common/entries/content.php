<?php
$count = Forminator_Form_Entry_Model::count_all_entries();
?>

<?php if ( $count > 0 ) { ?>

	<?php
        $markup = $this->render_entries();
    
        if ( ! empty( $_GET[ 'form_type' ] ) ) {
            update_option( 'forminator_submissions_form_type', sanitize_text_field( $_GET[ 'form_type' ] ) );
        }
        if ( ! empty( $_GET[ 'form_id' ] ) ) {
            update_option( 'forminator_submissions_form_id', sanitize_text_field( $_GET[ 'form_id' ] ) );
        }

        $form_type = get_option( 'forminator_submissions_form_type' );
        $form_id   = get_option( 'forminator_submissions_form_id' );
    ?>

	<form method="get"
		name="bulk-action-form"
		class="sui-box">

		<div class="fui-entries-bar">

			<div class="fui-bar-selectors">

				<input type="hidden" name="page" value="forminator-entries" />

				<select
					name="form_type"
					onchange="submit()"
					class="sui-select-sm"
				>

					<?php foreach ( $this->get_form_types() as $post_type => $name ) { // phpcs:ignore ?>
						<option value="<?php echo esc_attr( $post_type ); ?>" <?php echo selected( $post_type, $form_type ); ?>><?php echo esc_html( $name ); ?></option>
					<?php } ?>

				</select>

				<?php echo $this->render_form_switcher( $form_type, $form_id ); // phpcs:ignore ?>

			</div>

			<button class="sui-button sui-button-blue show-submissions" onclick="submit()"><?php esc_html_e( 'Show Submissions', 'forminator' ); ?></button>

			<?php if ( $markup ) : ?>
				<a href="/" class="sui-button sui-button-ghost wpmudev-open-modal" data-modal="exports-schedule"><i class="sui-icon-paperclip" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?></a>
			<?php endif; ?>

		</div>

	</form>

	<?php if ( $markup ) : ?>

		<?php echo $markup; // phpcs:ignore ?>

	<?php else : ?>

		<div class="sui-box sui-message">

			<?php if ( forminator_is_show_branding() ) : ?>
				<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-disabled.png' ); ?>"
					srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-disabled.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-disabled@2x.png' ); ?> 2x"
					alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
					class="sui-image"/>
			<?php endif; ?>

			<div class="sui-message-content">

				<h2><?php esc_html_e( 'Almost there!', 'forminator' ); ?></h2>

				<p><?php esc_html_e( 'Select the form, poll or quiz module to view the corresponding submissions.', 'forminator' ); ?></p>

			</div>

		</div>

	<?php endif; ?>

	<?php
} else {
	$none_title = esc_html__( 'Submissions', 'forminator' );
	$none_text  = esc_html__( 'You haven’t received any form, poll or quiz submissions yet. When you do, you’ll be able to view all the data here.', 'forminator' );

	include_once forminator_plugin_dir() . 'admin/views/common/entries/content-none.php';
}
