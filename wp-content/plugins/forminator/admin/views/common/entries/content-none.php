<div class="sui-box sui-message">

	<?php if ( forminator_is_show_branding() ) : ?>
		<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-submissions.png' ); ?>"
			srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-submissions.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/img/forminator-submissions@2x.png' ); ?> 2x"
			alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
			class="sui-image"
			aria-hidden="true"/>
	<?php endif; ?>

	<div class="sui-message-content">

		<h2>
		<?php
		if ( empty( $none_title ) ) {
			echo forminator_get_form_name( $this->form_id ); // phpcs:ignore
		} else {
			echo esc_html( $none_title );
		}
		?>
		</h2>

		<p>
		<?php
		if ( empty( $none_text ) ) {
			printf( esc_html__( 'You haven’t received any submissions for this %s yet. When you do, you’ll be able to view all the data here.', 'forminator' ), esc_html( static::$module_slug ) );
		} else {
			echo esc_html( $none_text );
		}
		?>
		</p>

	</div>

</div>
