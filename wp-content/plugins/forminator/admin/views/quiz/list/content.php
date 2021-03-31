<?php
/** @var Forminator_Quizz_Page $this */

// Empty message
$image_empty   = forminator_plugin_url() . 'assets/images/forminator-empty-message.png';
$image_empty2x = forminator_plugin_url() . 'assets/images/forminator-empty-message@2x.png';

// Count total forms
$count        = $this->countModules();
$count_active = $this->countModules( 'publish' );

// available bulk actions
$bulk_actions = $this->bulk_actions();

// Start date for retrieving the information of the last 30 days in sql format
$sql_month_start_date = date( 'Y-m-d H:i:s', strtotime( '-30 days midnight' ) );// phpcs:ignore

// Count total entries from last 30 days
$total_entries_from_last_month = count( Forminator_Form_Entry_Model::get_newer_entry_ids( 'quizzes', $sql_month_start_date ) );

$quiz_module = $this->getModules();

$most_entry = array_reduce( $quiz_module, function ( $a, $b ) {
	if ( isset( $a['entries'] ) && isset( $b['entries'] ) ) {
		return $a['entries'] > $b['entries'] ? $a : $b;
	}
} );
?>

<?php if ( $count > 0 ) { ?>

	<div class="sui-box sui-summary sui-summary-sm <?php echo esc_attr( $this->get_box_summary_classes() ); ?>">

		<div class="sui-summary-image-space" aria-hidden="true" style="<?php echo esc_attr( $this->get_box_summary_image_style() ); ?>"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details">

				<span class="sui-summary-large"><?php echo esc_html( $count_active ); ?></span>
				<?php if ( $count_active > 1 ) { ?>
					<span class="sui-summary-sub"><?php esc_html_e( 'Active Quizzes', Forminator::DOMAIN ); ?></span>
				<?php } else { ?>
					<span class="sui-summary-sub"><?php esc_html_e( 'Active Quiz', Forminator::DOMAIN ); ?></span>
				<?php } ?>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last Submission', Forminator::DOMAIN ); ?></span>
					<span class="sui-list-detail"><?php echo forminator_get_latest_entry_time( 'quizzes' ); // phpcs:ignore ?></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Submissions in the last 30 days', Forminator::DOMAIN ); ?></span>
					<span class="sui-list-detail"><?php echo esc_html( $total_entries_from_last_month ); ?></span>
				</li>
				<?php if ( ! empty( $most_entry ) && isset( $most_entry['entries'] ) && 0 !== (int) $most_entry['entries'] ) { ?>
                    <li>
                        <span class="sui-list-label"><?php esc_html_e( 'Most submissions', Forminator::DOMAIN ); ?></span>
                        <span class="sui-list-detail">
                            <a href="<?php echo $this->getAdminEditUrl( $most_entry['type'], $most_entry['id'] ); ?>">
                                <?php echo forminator_get_form_name( $most_entry['id'], 'quiz' ); ?>
                            </a>
                        </span>
                    </li>
				<?php } ?>
			</ul>

		</div>

	</div>

	<!-- START: Bulk actions and pagination -->
	<div class="fui-listings-pagination">

		<div class="fui-pagination-mobile sui-pagination-wrap">

			<span class="sui-pagination-results"><?php /* translators: ... */ echo esc_html( sprintf( _n( '%s result', '%s results', $count, Forminator::DOMAIN ), $count ) ); ?></span>

			<?php $this->pagination(); ?>

		</div>

		<div class="fui-pagination-desktop sui-box">

			<div class="sui-box-search">

				<form method="post" name="bulk-action-form" class="sui-search-left"
					style="display: flex; align-items: center;">

					<?php wp_nonce_field( 'forminatorQuizFormRequest', 'forminatorNonce' ); ?>

					<input type="hidden" name="ids" value="" />

					<label for="forminator-check-all-modules" class="sui-checkbox">
						<input type="checkbox" id="forminator-check-all-modules">
						<span aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all', Forminator::DOMAIN ); ?></span>
					</label>

					<select class="sui-select-sm sui-select-inline fui-select-listing-actions" name="forminator_action">
						<option value=""><?php esc_html_e( 'Bulk Action', Forminator::DOMAIN ); ?></option>
						<?php foreach ( $bulk_actions as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<button class="sui-button"><?php esc_html_e( 'Apply', Forminator::DOMAIN ); ?></button>

				</form>

				<div class="sui-search-right">

					<div class="sui-pagination-wrap">
						<span class="sui-pagination-results"><?php /* translators: ... */ echo esc_html( sprintf( _n( '%s result', '%s results', $count, Forminator::DOMAIN ), $count ) ); ?></span>
						<?php $this->pagination(); ?>
					</div>

				</div>

			</div>

		</div>

	</div>
	<!-- END: Bulk actions and pagination -->

	<div class="sui-accordion sui-accordion-block" id="forminator-modules-list">

		<?php
		foreach ( $quiz_module as $module ) {
			$module_entries_from_last_month = 0 !== $module['entries'] ? count( Forminator_Form_Entry_Model::get_newer_entry_ids_of_form_id( $module['id'], $sql_month_start_date ) ) : 0;
			$opened_class                   = '';
			$opened_chart                   = '';
			$has_leads                      = isset( $module['has_leads'] ) ? $module['has_leads'] : false;
			$leads_id                       = isset( $module['leads_id'] ) ? $module['leads_id'] : 0;

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
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Select this quiz', Forminator::DOMAIN ); ?></span>
						</label>

						<span class="sui-trim-text"><?php echo htmlspecialchars( forminator_get_form_name( $module['id'], 'quiz' ) ); // phpcs:ignore ?></span>

						<?php
						if ( 'publish' === $module['status'] ) {
							echo '<span class="sui-tag sui-tag-blue">' . esc_html__( 'Published', Forminator::DOMAIN ) . '</span>';
						}
						?>

						<?php
						if ( 'draft' === $module['status'] ) {
							echo '<span class="sui-tag">' . esc_html__( 'Draft', Forminator::DOMAIN ) . '</span>';
						}
						?>

					</div>

					<div class="sui-accordion-item-date"><strong><?php esc_html_e( 'Last Submission', Forminator::DOMAIN ); ?></strong> <?php echo esc_html( $module['last_entry_time'] ); ?></div>

					<div class="sui-accordion-col-auto">

						<a href="<?php echo $this->getAdminEditUrl( $module['type'], $module['id'] ); // phpcs:ignore ?>"
							class="sui-button sui-button-ghost sui-accordion-item-action">
							<i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit', Forminator::DOMAIN ); ?>
						</a>

						<div class="sui-dropdown sui-accordion-item-action fui-dropdown-soon">

							<button class="sui-button-icon sui-dropdown-anchor">
								<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Open list settings', Forminator::DOMAIN ); ?></span>
							</button>

							<ul>

								<li><a href="#"
									class="wpmudev-open-modal"
									data-modal="preview_quizzes"
									data-modal-title="<?php /* translators: ... */ echo sprintf( '%s - %s', __( 'Preview Quiz', Forminator::DOMAIN ), htmlspecialchars( htmlspecialchars( forminator_get_form_name( $module['id'], 'quiz' ) ) ) ); // phpcs:ignore ?>"
									data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
                           data-has-leads="<?php echo esc_attr( $has_leads ); ?>"
                           data-leads-id="<?php echo esc_attr( $leads_id ); ?>"
									data-nonce-preview="<?php echo esc_attr( wp_create_nonce( 'forminator_load_module' ) ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_preview_quizzes' ) ); ?>">
									<i class="sui-icon-eye" aria-hidden="true"></i> <?php esc_html_e( 'Preview', Forminator::DOMAIN ); ?>
								</a></li>

								<li>
									<button class="copy-clipboard" data-shortcode='[forminator_quiz id="<?php echo esc_attr( $module['id'] ); ?>"]'><i class="sui-icon-code" aria-hidden="true"></i> <?php esc_html_e( 'Copy Shortcode', Forminator::DOMAIN ); ?></button>
								</li>

								<li>
									<form method="post">
										<input type="hidden" name="forminator_action" value="update-status">
										<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>

										<?php if ( Forminator_Poll_Form_Model::STATUS_PUBLISH === $module['status'] ) : ?>
											<input type="hidden" name="status" value="draft"/>
										<?php elseif ( Forminator_Poll_Form_Model::STATUS_DRAFT === $module['status'] ) : ?>
											<input type="hidden" name="status" value="publish"/>
										<?php endif; ?>

                                        <?php
                                            $update_status_nonce = esc_attr( 'forminator-nonce-update-status-' . $module['id'] );
                                            wp_nonce_field( $update_status_nonce, $update_status_nonce );
                                        ?>
										<button type="submit">

											<?php if ( Forminator_Poll_Form_Model::STATUS_PUBLISH === $module['status'] ) : ?>
												<i class="sui-icon-unpublish" aria-hidden="true"></i> <?php esc_html_e( 'Unpublish', Forminator::DOMAIN ); ?>
											<?php elseif ( Forminator_Poll_Form_Model::STATUS_DRAFT === $module['status'] ) : ?>
												<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Publish', Forminator::DOMAIN ); ?>
											<?php endif; ?>

										</button>
									</form>
								</li>

								<li>
									<a href="<?php echo admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ); // phpcs:ignore ?>">
										<i class="sui-icon-community-people" aria-hidden="true"></i> <?php esc_html_e( 'View Submissions', Forminator::DOMAIN ); ?>
									</a>
								</li>

								<li <?php echo ( $module['has_leads'] ) ? 'aria-hidden="true"' : ''; ?>><form method="post">
									<input type="hidden" name="forminator_action" value="clone">
									<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
									<?php
										 $clone_nonce = esc_attr( 'forminator-nonce-clone-' . $module['id'] );
										 wp_nonce_field( $clone_nonce, $clone_nonce );
									?>
									<?php if ( $module['has_leads'] ): ?>
										<button type="submit" disabled="disabled" class="fui-button-with-tag sui-tooltip sui-tooltip-left sui-constrained" data-tooltip="<?php esc_html_e( 'Duplicate isn\'t supported at the moment for the quizzes with lead capturing enabled.', Forminator::DOMAIN ); ?>">
											<span class="sui-icon-page-multiple" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Duplicate', Forminator::DOMAIN ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', Forminator::DOMAIN ); ?></span>
										</button>
									<?php else: ?>
										<button type="submit"><i class="sui-icon-page-multiple" aria-hidden="true"></i> <?php esc_html_e( 'Duplicate', Forminator::DOMAIN ); ?></button>
									<?php endif; ?>
								</form></li>

								<li>
									<button
										class="wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php esc_attr_e( 'Reset Tracking Data', Forminator::DOMAIN ); ?>"
										data-modal-content="<?php esc_attr_e( 'Are you sure you wish reset the tracking data of this quiz?', Forminator::DOMAIN ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-action="reset-views"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator-nonce-reset-views-' . $module['id'] ) ); ?>"
									>
										<i class="sui-icon-update" aria-hidden="true"></i> <?php esc_html_e( 'Reset Tracking data', Forminator::DOMAIN ); ?>
									</button>
								</li>

								<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>
									<?php if ( $module['has_leads'] ): ?>
										<li aria-hidden="true"><a href="#" class="fui-button-with-tag sui-tooltip sui-tooltip-left"
											data-tooltip="<?php esc_html_e( 'Export isn\'t supported at the moment for the quizzes with lead capturing enabled.', Forminator::DOMAIN ); ?>">
											<span class="sui-icon-cloud-migration" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Export', Forminator::DOMAIN ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', Forminator::DOMAIN ); ?></span>
										</a></li>
									<?php else: ?>
										<li><a href="#"
											class="wpmudev-open-modal"
											data-modal="export_quiz"
											data-modal-title=""
											data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_export_quiz' ) ); ?>">
											<i class="sui-icon-cloud-migration" aria-hidden="true"></i> <?php esc_html_e( 'Export', Forminator::DOMAIN ); ?>
										</a></li>
									<?php endif; ?>

								<?php endif; ?>

								<li>
									<button
										class="sui-option-red wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php esc_attr_e( 'Delete Quiz', Forminator::DOMAIN ); ?>"
										data-modal-content="<?php esc_attr_e( 'Are you sure you wish to permanently delete this quiz?', Forminator::DOMAIN ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorQuizFormRequest' ) ); ?>"
									>
										<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', Forminator::DOMAIN ); ?>
									</button>
								</li>

							</ul>

						</div>

						<button class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_html_e( 'Open item', Forminator::DOMAIN ); ?>"><i class="sui-icon-chevron-down" aria-hidden="true"></i></button>

					</div>

				</div>

				<div class="sui-accordion-item-body">

					<ul class="sui-accordion-item-data">

						<li data-col="large">
							<strong><?php esc_html_e( 'Last Submission', Forminator::DOMAIN ); ?></strong>
							<span><?php echo esc_html( $module['last_entry_time'] ); ?></span>
						</li>

						<li data-col="small">
							<strong><?php esc_html_e( 'Views', Forminator::DOMAIN ); ?></strong>
							<span><?php echo esc_html( $module['views'] ); ?></span>
						</li>

						<li>
							<?php if ( $module['has_leads'] ) : ?>
                                <strong class="forminator-leads-leads" style="display:none;"><?php esc_html_e( 'Leads Collected', Forminator::DOMAIN ); ?></strong>
								<a href="<?php echo admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ); // phpcs:ignore ?>" class="forminator-leads-leads" style="display:none;"><?php echo esc_html( $module['leads'] ); ?></a>
							<?php endif; ?>
                            <strong class="forminator-leads-submissions"><?php esc_html_e( 'Submissions', Forminator::DOMAIN ); ?></strong>
                            <a href="<?php echo admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ); // phpcs:ignore ?>" class="forminator-leads-submissions"><?php echo esc_html( $module['entries'] ); ?></a>
						</li>

						<li>
							<strong><?php esc_html_e( 'Conversion Rate', Forminator::DOMAIN ); ?></strong>
							<span class="forminator-submission-rate"><?php echo $this->getRate( $module ); // phpcs:ignore ?>%</span>
							<?php if ( $module['has_leads'] ): ?>
								<span class="forminator-leads-rate" style="display:none;"><?php echo $this->getLeadsRate( $module ); // phpcs:ignore ?>%</span>
							<?php endif; ?>
						</li>

						<?php if ( $module['has_leads'] ): ?>
							<li class="fui-conversion-select" data-col="selector">
								<label class="fui-selector-label"><?php esc_html_e( 'View data for', Forminator::DOMAIN ); ?></label>
								<select class="sui-select-sm fui-selector-button fui-select-listing-data">
									<option value="submissions"><?php esc_html_e( 'Submissions', Forminator::DOMAIN ); ?></option>
									<option value="leads"><?php esc_html_e( 'Leads Form', Forminator::DOMAIN ); ?></option>
								</select>
							</li>
						<?php endif; ?>

					</ul>

					<div class="sui-chartjs sui-chartjs-animated<?php echo esc_attr( $opened_chart ); ?> forminator-stats-chart" data-chart-id="<?php echo esc_attr( $module['id'] ); ?>">

						<div class="sui-chartjs-message sui-chartjs-message--loading">
							<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading data...', Forminator::DOMAIN ); ?></p>
						</div>

						<?php if ( 0 === $module['entries'] ) { ?>

							<div class="sui-chartjs-message sui-chartjs-message--empty">
								<p><i class="sui-icon-info" aria-hidden="true"></i> <?php esc_html_e( "Your quiz doesn't have any submission yet. Try again in a moment.", Forminator::DOMAIN ); ?></p>
							</div>

						<?php } else { ?>

							<?php if ( 0 === $module_entries_from_last_month ) { ?>

								<div class="sui-chartjs-message sui-chartjs-message--empty">
									<p><i class="sui-icon-info" aria-hidden="true"></i> <?php esc_html_e( "Your quiz didn't collect submissions in the past 30 days.", Forminator::DOMAIN ); ?></p>
								</div>

							<?php } ?>

						<?php } ?>

						<div class="sui-chartjs-canvas">

							<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
								<canvas id="forminator-quiz-<?php echo esc_attr( $module['id'] ); ?>-stats"></canvas>
							<?php } ?>

						</div>

					</div>

					<?php if ( isset( $module['has_leads'] ) && $module['has_leads'] ) { ?>

					<div class="sui-chartjs sui-chartjs-animated<?php echo esc_attr( $opened_chart ); ?> forminator-leads-chart" style="display: none;" data-chart-id="<?php echo esc_attr( $module['leads_id'] ); ?>">

						<div class="sui-chartjs-message sui-chartjs-message--loading">
							<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading data...', Forminator::DOMAIN ); ?></p>
						</div>

						<?php if ( 0 === $module['entries'] ) { ?>

							<div class="sui-chartjs-message sui-chartjs-message--empty">
								<p><i class="sui-icon-info" aria-hidden="true"></i> <?php esc_html_e( "Your quiz doesn't have any submission yet. Try again in a moment.", Forminator::DOMAIN ); ?></p>
							</div>

						<?php } else { ?>

							<?php if ( 0 === $module_entries_from_last_month ) { ?>

								<div class="sui-chartjs-message sui-chartjs-message--empty">
									<p><i class="sui-icon-info" aria-hidden="true"></i> <?php esc_html_e( "Your quiz didn't collect submissions in the past 30 days.", Forminator::DOMAIN ); ?></p>
								</div>

							<?php } ?>

						<?php } ?>

						<div class="sui-chartjs-canvas">

							<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
								<canvas id="forminator-quiz-<?php echo esc_attr( $module['leads_id'] ); ?>-stats"></canvas>
							<?php } ?>

						</div>

					</div>

					<?php } ?>

				</div>

			</div>

		<?php } ?>

	</div>

<?php } else { ?>

	<div class="sui-box sui-message">

		<?php if ( forminator_is_show_branding() ) : ?>
			<img src="<?php echo esc_url( $image_empty ); ?>"
				srcset="<?php echo esc_url( $image_empty2x ); ?> 1x, <?php echo esc_url( $image_empty2x ); ?> 2x"
				alt="<?php esc_html_e( 'Empty quizzes', Forminator::DOMAIN ); ?>"
				class="sui-image"
				aria-hidden="true"/>
		<?php endif; ?>

		<div class="sui-message-content">

			<p><?php esc_html_e( 'Create fun or challenging quizzes for your visitors to take and share on social media.', Forminator::DOMAIN ); ?></p>

			<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>

				<p>
					<button class="sui-button sui-button-blue wpmudev-button-open-modal" data-modal="quizzes"><i class="sui-icon-plus" aria-hidden="true"></i> <?php esc_html_e( 'Create', Forminator::DOMAIN ); ?></button>

					<a href="#"
						class="sui-button wpmudev-open-modal"
						data-modal="import_quiz"
						data-modal-title=""
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_import_quiz' ) ); ?>">
						<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Import', Forminator::DOMAIN ); ?>
					</a>
				</p>

			<?php else : ?>

				<p><button class="sui-button sui-button-blue wpmudev-button-open-modal" data-modal="quizzes"><i class="sui-icon-plus" aria-hidden="true"></i> <?php esc_html_e( 'Create', Forminator::DOMAIN ); ?></button></p>

			<?php endif; ?>

		</div>

	</div>

<?php } ?>

<?php
$days_array    = array();
$default_array = array();

for ( $h = 30; $h >= 0; $h-- ) {
	$time                   = strtotime( '-' . $h . ' days' );
	$date                   = date( 'Y-m-d', $time );// phpcs:ignore
	$default_array[ $date ] = 0;
	$days_array[]           = date( 'M j, Y', $time );// phpcs:ignore
}

foreach ( $quiz_module as $module ) {

	if ( 0 === $module['entries'] ) {
		$submissions_data = $default_array;
	} else {
		$submissions       = Forminator_Form_Entry_Model::get_form_latest_entries_count_grouped_by_day( $module['id'], $sql_month_start_date );
		$submissions_array = wp_list_pluck( $submissions, 'entries_amount', 'date_created' );
		$submissions_data  = array_merge( $default_array, array_intersect_key( $submissions_array, $default_array ) );
	}

	// Get highest value
	$highest_submission = max( $submissions_data );

	// Calculate canvas top spacing
	$canvas_top_spacing = $highest_submission + 8;

	?>

<script>
	var ctx = document.getElementById( 'forminator-quiz-<?php echo $module['id']; // phpcs:ignore ?>-stats' );

	var monthDays = [ '<?php echo implode( "', '", $days_array ); // phpcs:ignore ?>' ],
		submissions = [ <?php echo implode( ', ', $submissions_data );  // phpcs:ignore ?> ];

	var chartData = {
		labels: monthDays,
		datasets: [{
			label: 'Submissions',
			data: submissions,
			backgroundColor: [
				'#E1F6FF'
			],
			borderColor: [
				'#17A8E3'
			],
			borderWidth: 2,
			pointRadius: 0,
			pointHitRadius: 20,
			pointHoverRadius: 5,
			pointHoverBorderColor: '#17A8E3',
			pointHoverBackgroundColor: '#17A8E3'
		}]
	};

	var chartOptions = {
		maintainAspectRatio: false,
		legend: {
			display: false
		},
		scales: {
			xAxes: [{
				display: false,
				gridLines: {
					color: 'rgba(0, 0, 0, 0)'
				}
			}],
			yAxes: [{
				display: false,
				gridLines: {
					color: 'rgba(0, 0, 0, 0)'
				},
				ticks: {
					beginAtZero: false,
					min: 0,
					max: <?php echo esc_attr( $canvas_top_spacing ); ?>,
					stepSize: 1
				}
			}]
		},
		elements: {
			line: {
				tension: 0
			},
			point: {
				radius: 0
			}
		},
		tooltips: {
			custom: function( tooltip ) {
				if ( ! tooltip ) return;
				// disable displaying the color box;
				tooltip.displayColors = false;
			},
			callbacks: {
				title: function( tooltipItem, data ) {
					return tooltipItem[0].yLabel + " Submissions";
				},
				label: function( tooltipItem, data ) {
					return tooltipItem.xLabel;
				},
				// Set label text color
				labelTextColor:function( tooltipItem, chart ) {
					return '#AAAAAA';
				}
			}
		},
		plugins: {
			datalabels: {
				display: false
			}
		}
	};

	if (ctx) {
		var myChart = new Chart(ctx, {
			type: 'line',
			fill: 'start',
			data: chartData,
			plugins: [
				ChartDataLabels
			],
			options: chartOptions
		});
	}


</script>

<?php
if ( isset( $module['has_leads'] ) && $module['has_leads'] ) {

	if ( ! isset( $module['leads'] ) || 0 === $module['leads'] ) {
		$submissions_data = $default_array;
	} else {
		$submissions       = Forminator_Form_Entry_Model::get_form_latest_lead_entries_count_grouped_by_day( $module['id'], $sql_month_start_date );
		$submissions_array = wp_list_pluck( $submissions, 'entries_amount', 'date_created' );
		$submissions_data  = array_merge( $default_array, array_intersect_key( $submissions_array, $default_array ) );
	}

	// Get highest value
	$highest_submission = max( $submissions_data );

	// Calculate canvas top spacing
	$canvas_top_spacing = $highest_submission + 8;
	?>
	<script>
		var ctx = document.getElementById( 'forminator-quiz-<?php echo $module['leads_id']; // phpcs:ignore ?>-stats' );

		var monthDays = [ '<?php echo implode( "', '", $days_array ); // phpcs:ignore ?>' ],
			submissions = [ <?php echo implode( ', ', $submissions_data );  // phpcs:ignore ?> ];

		var chartData = {
			labels: monthDays,
			datasets: [{
				label: 'Submissions',
				data: submissions,
				backgroundColor: [
					'#E1F6FF'
				],
				borderColor: [
					'#17A8E3'
				],
				borderWidth: 2,
				pointRadius: 0,
				pointHitRadius: 20,
				pointHoverRadius: 5,
				pointHoverBorderColor: '#17A8E3',
				pointHoverBackgroundColor: '#17A8E3'
			}]
		};

		var chartOptions = {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			scales: {
				xAxes: [{
					display: false,
					gridLines: {
						color: 'rgba(0, 0, 0, 0)'
					}
				}],
				yAxes: [{
					display: false,
					gridLines: {
						color: 'rgba(0, 0, 0, 0)'
					},
					ticks: {
						beginAtZero: false,
						min: 0,
						max: <?php echo esc_attr( $canvas_top_spacing ); ?>,
						stepSize: 1
					}
				}]
			},
			elements: {
				line: {
					tension: 0
				},
				point: {
					radius: 0
				}
			},
			tooltips: {
				custom: function( tooltip ) {
					if ( ! tooltip ) return;
					// disable displaying the color box;
					tooltip.displayColors = false;
				},
				callbacks: {
					title: function( tooltipItem, data ) {
						return tooltipItem[0].yLabel + " Submissions";
					},
					label: function( tooltipItem, data ) {
						return tooltipItem.xLabel;
					},
					// Set label text color
					labelTextColor:function( tooltipItem, chart ) {
						return '#AAAAAA';
					}
				}
			},
			plugins: {
				datalabels: {
					display: false
				}
			}
		};

		if (ctx) {
			var myChart = new Chart(ctx, {
				type: 'line',
				fill: 'start',
				data: chartData,
				plugins: [
					ChartDataLabels
				],
				options: chartOptions
			});
		}


	</script>

	<?php } ?>

<?php } ?>

<script>
	jQuery( '.fui-select-listing-data' ).change( function( e ) {
		var $el   = jQuery( this ),
			$parent = $el.closest( '.sui-accordion-item' ),
			submissions = $parent.find( '.forminator-leads-submissions' ),
			leads = $parent.find( '.forminator-leads-leads'),
			submissionsRate = $parent.find( '.forminator-submission-rate' ),
			leadsRate = $parent.find( '.forminator-leads-rate' ),
			statsChart = $parent.find( '.forminator-stats-chart'),
			leadsChart = $parent.find( '.forminator-leads-chart'),
			value = $el.val()
		;

		if ( value === 'leads' ) {
			submissions.hide();
			submissionsRate.hide();
			statsChart.hide();
			leads.show();
			leadsRate.show();
			leadsChart.show();
		} else {
			submissions.show();
			submissionsRate.show();
			statsChart.show();
			leads.hide();
			leadsRate.hide();
			leadsChart.hide();
		}
	});
</script>
