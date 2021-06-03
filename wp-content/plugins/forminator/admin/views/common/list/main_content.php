<!-- START: Bulk actions and pagination -->
<div class="fui-listings-pagination">

	<div class="fui-pagination-mobile sui-pagination-wrap">
		<?php $this->pagination( $is_search, $count ); ?>
	</div>

	<div class="fui-pagination-desktop sui-box">

		<div class="sui-box-search">

			<form
				method="post"
				name="bulk-action-form"
				class="sui-search-left"
				style="display: flex; align-items: center;"
				>

				<?php wp_nonce_field( 'forminator_' . static::$module_slug . '_request', 'forminatorNonce' ); ?>

				<input type="hidden" name="ids" value="" />

				<label for="forminator-check-all-modules" class="sui-checkbox">
					<input type="checkbox" id="forminator-check-all-modules">
					<span aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all', 'forminator' ); ?></span>
				</label>

				<select class="sui-select-sm sui-select-inline fui-select-listing-actions" name="forminator_action">
					<option value=""><?php esc_html_e( 'Bulk Action', 'forminator' ); ?></option>
					<?php foreach ( $this->bulk_actions() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<button class="sui-button"><?php esc_html_e( 'Apply', 'forminator' ); ?></button>

			</form>

			<div class="sui-search-right">

				<div class="sui-pagination-wrap">
					<?php $this->pagination( $is_search, $count ); ?>
				</div>

			</div>

		</div>

	</div>

</div>
<!-- END: Bulk actions and pagination -->

<?php
	$preview_dialog = 'preview_' . forminator_get_prefix( static::$module_slug, 'c', false, true );
	$export_dialog  = 'export_' . forminator_get_prefix( static::$module_slug, 'c' );
	$post_type      = 'forminator_' . forminator_get_prefix( static::$module_slug, '', false, true );
	$soon           = 'quiz' === static::$module_slug;
	?>
	<div class="sui-accordion sui-accordion-block" id="forminator-modules-list">

		<?php
		foreach ( $modules as $module ) {
			$module_entries_from_last_month = 0 !== $module['entries'] ? count( Forminator_Form_Entry_Model::get_newer_entry_ids_of_form_id( $module['id'], $sql_month_start_date ) ) : 0;
			$opened_class                   = '';
			$opened_chart                   = '';
			$has_leads                      = isset( $module['has_leads'] ) ? $module['has_leads'] : false;
			$leads_id                       = isset( $module['leads_id'] ) ? $module['leads_id'] : 0;
			if ( isset( $wizard_page ) && ! isset( $module['type'] ) ) {
				$edit_url = admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $module['id'] );
			} else {
				// For quizzes.
				$edit_url = admin_url( 'admin.php?page=forminator-' . ( 'nowrong' === $module['type'] ? $module['type'] : 'knowledge' ) . '-wizard&id=' . $module['id'] );
			}

			if( isset( $_GET['view-stats'] ) && intval( $_GET['view-stats'] ) === intval( $module['id'] ) ) { // phpcs:ignore
				$opened_class = ' sui-accordion-item--open forminator-scroll-to';
				$opened_chart = ' sui-chartjs-loaded';
			}
			?>

			<div class="sui-accordion-item<?php echo esc_attr( $opened_class ); ?>">

				<div class="sui-accordion-item-header">

					<div class="sui-accordion-item-title sui-trim-title">

						<label for="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" class="sui-checkbox sui-accordion-item-action">
							<input type="checkbox" id="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" value="<?php echo esc_html( $module['id'] ); ?>">
							<span aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Select this module', 'forminator' ); ?></span>
						</label>

						<span class="sui-trim-text"><?php echo htmlspecialchars( forminator_get_form_name( $module['id'] ) ); // phpcs:ignore ?></span>

						<?php
						if ( 'publish' === $module['status'] ) {
							echo '<span class="sui-tag sui-tag-blue">' . esc_html__( 'Published', 'forminator' ) . '</span>';
						}
						?>

						<?php
						if ( 'draft' === $module['status'] ) {
							echo '<span class="sui-tag">' . esc_html__( 'Draft', 'forminator' ) . '</span>';
						}
						?>

					</div>

					<div class="sui-accordion-item-date"><strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong> <?php echo esc_html( $module['last_entry_time'] ); ?></div>

					<div class="sui-accordion-col-auto">

						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="sui-button sui-button-ghost sui-accordion-item-action sui-desktop-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit', 'forminator' ); ?>
						</a>

						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="sui-button-icon sui-accordion-item-action sui-mobile-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Edit', 'forminator' ); ?></span>
						</a>

						<div class="sui-dropdown sui-accordion-item-action<?php echo $soon ? ' fui-dropdown-soon' : ''; ?>">

							<button class="sui-button-icon sui-dropdown-anchor">
								<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Open list settings', 'forminator' ); ?></span>
							</button>

							<ul>

								<li><a href="#"
									class="wpmudev-open-modal"
									data-modal="<?php echo esc_attr( $preview_dialog ); ?>"
									data-modal-title="<?php printf( '%s - %s', esc_html( $preview_title ), htmlspecialchars( htmlspecialchars( forminator_get_form_name( $module['id'] ) ) ) ); // phpcs:ignore ?>"
									data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
									data-has-leads="<?php echo esc_attr( $has_leads ); ?>"
									data-leads-id="<?php echo esc_attr( $leads_id ); ?>"
									data-nonce-preview="<?php echo esc_attr( wp_create_nonce( 'forminator_load_module' ) ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_' . $preview_dialog ) ); ?>">
									<i class="sui-icon-eye" aria-hidden="true"></i> <?php esc_html_e( 'Preview', 'forminator' ); ?>
								</a></li>

								<li>
									<button class="copy-clipboard" data-shortcode='[forminator_<?php echo esc_attr( static::$module_slug ); ?> id="<?php echo esc_attr( $module['id'] ); ?>"]'><i class="sui-icon-code" aria-hidden="true"></i> <?php esc_html_e( 'Copy Shortcode', 'forminator' ); ?></button>
								</li>

								<li>
									<form method="post">
										<input type="hidden" name="forminator_action" value="update-status">
										<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>

										<?php if ( 'publish' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="draft"/>
										<?php elseif ( 'draft' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="publish"/>
										<?php endif; ?>

										<?php
											$update_status_nonce = esc_attr( 'forminator-nonce-update-status-' . $module['id'] );
											wp_nonce_field( $update_status_nonce, $update_status_nonce );
										?>
										<button type="submit">

											<?php if ( 'publish' === $module['status'] ) : ?>
												<i class="sui-icon-unpublish" aria-hidden="true"></i> <?php esc_html_e( 'Unpublish', 'forminator' ); ?>
											<?php elseif ( 'draft' === $module['status'] ) : ?>
												<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Publish', 'forminator' ); ?>
											<?php endif; ?>

										</button>
									</form>
								</li>

								<li><a href="<?php echo admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&form_id=' . $module['id'] ); // phpcs:ignore ?>">
									<i class="sui-icon-community-people" aria-hidden="true"></i> <?php esc_html_e( 'View Submissions', 'forminator' ); ?>
								</a></li>

								<li <?php echo ( $has_leads ) ? 'aria-hidden="true"' : ''; ?>><form method="post">
									<input type="hidden" name="forminator_action" value="clone">
									<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
									<?php
										$clone_nonce = esc_attr( 'forminator-nonce-clone-' . $module['id'] );
										wp_nonce_field( $clone_nonce, 'forminatorNonce' );
									?>
									<?php if ( $has_leads ): ?>
										<button type="submit" disabled="disabled" class="fui-button-with-tag sui-tooltip sui-tooltip-left sui-constrained" data-tooltip="<?php esc_html_e( 'Duplicate isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-page-multiple" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Duplicate', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</button>
									<?php else: ?>
										<button type="submit">
											<i class="sui-icon-page-multiple" aria-hidden="true"></i> <?php esc_html_e( 'Duplicate', 'forminator' ); ?>
										</button>
									<?php endif; ?>
								</form></li>

								<li>
									<button
										class="wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php esc_attr_e( 'Reset Tracking Data', 'forminator' ); ?>"
										data-modal-content="<?php printf( esc_attr__( 'Are you sure you wish reset the tracking data of this %s?', 'forminator' ), static::$module_slug ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-action="reset-views"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator-nonce-reset-views-' . $module['id'] ) ); ?>"
									>
										<i class="sui-icon-update" aria-hidden="true"></i> <?php esc_html_e( 'Reset Tracking data', 'forminator' ); ?>
									</button>
								</li>

								<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>
									<?php if ( $has_leads ): ?>
										<li aria-hidden="true"><a href="#" class="fui-button-with-tag sui-tooltip sui-tooltip-left"
											data-tooltip="<?php esc_html_e( 'Export isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-cloud-migration" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Export', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</a></li>
									<?php else: ?>
										<li><a href="#"
											class="wpmudev-open-modal"
											data-modal="<?php echo esc_attr( $export_dialog ); ?>"
											data-modal-title=""
											data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_export_' . static::$module_slug ) ); ?>">
											<i class="sui-icon-cloud-migration" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?>
										</a></li>
									<?php endif; ?>

								<?php endif; ?>

								<li>
									<button
										class="sui-option-red wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php printf( esc_attr__( 'Delete %s', 'forminator' ), forminator_get_prefix( static::$module_slug, '', true ) ); ?>"
										data-modal-content="<?php printf( esc_attr__( 'Are you sure you wish to permanently delete this %s?', 'forminator' ), static::$module_slug ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_' . static::$module_slug . '_request' ) ); ?>"
									>
										<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', 'forminator' ); ?>
									</button>
								</li>

							</ul>

						</div>

						<button class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_html_e( 'Open item', 'forminator' ); ?>"><i class="sui-icon-chevron-down" aria-hidden="true"></i></button>

					</div>

				</div>

				<div class="sui-accordion-item-body">

					<ul class="sui-accordion-item-data">

						<li data-col="large">
							<strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong>
							<span><?php echo esc_html( $module['last_entry_time'] ); ?></span>
						</li>

						<li data-col="small">
							<strong><?php esc_html_e( 'Views', 'forminator' ); ?></strong>
							<span><?php echo esc_html( $module['views'] ); ?></span>
						</li>

						<li>
							<?php if ( $has_leads ) : ?>
                                <strong class="forminator-leads-leads" style="display:none;"><?php esc_html_e( 'Leads Collected', 'forminator' ); ?></strong>
								<a href="<?php echo admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ); // phpcs:ignore ?>" class="forminator-leads-leads" style="display:none;"><?php echo esc_html( $module['leads'] ); ?></a>
							<?php endif; ?>
							<strong class="forminator-leads-submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></strong>
							<a href="<?php echo admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&form_id=' . $module['id'] ); // phpcs:ignore ?>" class="forminator-leads-submissions"><?php echo esc_html( $module['entries'] ); ?></a>
						</li>

						<li>
							<strong><?php esc_html_e( 'Conversion Rate', 'forminator' ); ?></strong>
							<span class="forminator-submission-rate"><?php echo esc_html( $this->getRate( $module ) ); ?>%</span>
							<?php if ( $has_leads ): ?>
								<span class="forminator-leads-rate" style="display:none;"><?php echo $this->getLeadsRate( $module ); // phpcs:ignore ?>%</span>
							<?php endif; ?>
						</li>

						<?php if ( $has_leads ): ?>
							<li class="fui-conversion-select" data-col="selector">
								<label class="fui-selector-label"><?php esc_html_e( 'View data for', 'forminator' ); ?></label>
								<select class="sui-select-sm fui-selector-button fui-select-listing-data">
									<option value="submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></option>
									<option value="leads"><?php esc_html_e( 'Leads Form', 'forminator' ); ?></option>
								</select>
							</li>
						<?php endif; ?>

					</ul>

					<div class="sui-chartjs sui-chartjs-animated<?php echo esc_attr( $opened_chart ); ?> forminator-stats-chart" data-chart-id="<?php echo esc_attr( $module['id'] ); ?>">

						<div class="sui-chartjs-message sui-chartjs-message--loading">
							<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading data...', 'forminator' ); ?></p>
						</div>

						<?php
						unset( $message );
						if ( 0 === $module['entries'] ) {
							$message = sprintf( esc_html__( "Your %s doesn't have any submission yet. Try again in a moment.", 'forminator' ), static::$module_slug );
						} else if ( 'draft' === $module['status'] ) {
							$message = sprintf( esc_html__( "This %s is in draft state, so we've paused collecting data until you publish it live.", 'forminator' ), static::$module_slug );
						} else if ( 0 === $module_entries_from_last_month ) {
							$message = sprintf( esc_html__( "Your %s didn't collect submissions the past 30 days.", 'forminator' ), static::$module_slug );
						}
						?>
						<?php if ( ! empty( $message ) ) { ?>

							<div class="sui-chartjs-message sui-chartjs-message--empty">
								<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
							</div>

						<?php } ?>

						<div class="sui-chartjs-canvas">

							<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
								<canvas id="forminator-module-<?php echo $module['id']; // phpcs:ignore ?>-stats"></canvas>
							<?php } ?>

						</div>

					</div>

					<?php if ( $has_leads ) { ?>

						<div class="sui-chartjs sui-chartjs-animated<?php echo esc_attr( $opened_chart ); ?> forminator-leads-chart" style="display: none;" data-chart-id="<?php echo esc_attr( $leads_id ); ?>">

							<div class="sui-chartjs-message sui-chartjs-message--loading">
								<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading data...', 'forminator' ); ?></p>
							</div>

							<?php if ( ! empty( $message ) ) { ?>

								<div class="sui-chartjs-message sui-chartjs-message--empty">
									<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
								</div>

							<?php } ?>

							<div class="sui-chartjs-canvas">

								<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
									<canvas id="forminator-module-<?php echo esc_attr( $leads_id ); ?>-stats"></canvas>
								<?php } ?>

							</div>

						</div>

					<?php } ?>

				</div>

			</div>

		<?php } ?>

	</div>
