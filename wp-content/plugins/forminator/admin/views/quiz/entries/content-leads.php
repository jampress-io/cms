<?php
$form_type        = $this->get_form_type();
$url_entry_id = ( isset( $_GET['entry_id'] ) && ! empty( $_GET['entry_id'] ) ) ? (int) sanitize_text_field( $_GET['entry_id'] ) : 0;//phpcs:ignore
foreach ( $this->entries_iterator() as $entries ) {

	$db_entry_id = isset( $entries['entry_id'] ) ? $entries['entry_id'] : '';
	$entry_date = isset( $entries['entry_date'] ) ? $entries['entry_date'] : '';

	$summary       = $entries['summary'];
	$summary_items = $summary['items'];

	$detail       = $entries['detail'];
	$detail_items = $detail['items'];
	$quiz_entry   = $detail['quiz_entry'];
	$integrations = $detail['integrations'];
	//Open entry tab by received submission link
	$cls_open_tab = $url_entry_id === (int) $db_entry_id ? 'sui-accordion-item--open' : '';
	?>

    <tr class="sui-accordion-item <?php echo esc_attr( $cls_open_tab ); ?>" data-entry-id="<?php echo esc_attr( $db_entry_id ); ?>">

		<?php foreach ( $summary_items as $key => $summary_item ) { ?>

			<?php
			if ( ! $summary['num_fields_left'] && ( count( $summary_items ) - 1 ) === $key ) :

				echo '<td>';

					echo esc_html( $summary_item['value'] );

					echo '<span class="sui-accordion-open-indicator">';

					echo '<span class="sui-icon-chevron-down"></span>';

					echo '</span>';

				echo '</td>';

            elseif ( 1 === $summary_item['colspan'] ) :

				echo '<td class="sui-accordion-item-title">';

					echo '<label class="sui-checkbox">';

						echo '<input type="checkbox" name="ids[]" value="' . esc_attr( $db_entry_id ) . '" id="quiz-answer-' . esc_attr( $db_entry_id ) . '" />';

						echo '<span aria-hidden="true"></span>';

						echo '<span class="sui-screen-reader-text">' . sprintf(/* translators: ... */
								esc_html__( 'Select entry number %s', Forminator::DOMAIN ),
								esc_html( $db_entry_id )
							) . '</span>';

					echo '</label>';

					echo esc_html( $db_entry_id );

				echo '</td>';

			else :

				echo '<td>';

					echo esc_html( $summary_item['value'] );

					echo '<span class="sui-accordion-open-indicator fui-mobile-only" aria-hidden="true">';
						echo '<span class="sui-icon-chevron-down"></span>';
					echo '</span>';

				echo '</td>';

			endif;
			?>

		<?php } ?>

		<?php
		if ( $summary['num_fields_left'] ) {

			echo '<td>';
				echo '' . sprintf(/* translators: ... */
						esc_html__( '+ %s other fields', Forminator::DOMAIN ),
						esc_html( $summary['num_fields_left'] )
					) . '';
				echo '<span class="sui-accordion-open-indicator">';
					echo '<span class="sui-icon-chevron-down"></span>';
				echo '</span>';
			echo '</td>';

		}
		?>

    </tr>

    <tr class="sui-accordion-item-content">

        <td colspan="<?php echo esc_attr( $detail['colspan'] ); ?>">

            <div class="sui-box">

				<div class="sui-box-body fui-entries--knowledge">

					<?php // ROW: Title. ?>
					<div class="fui-entries-block">

						<h2 class="fui-entries-title"><?php echo '#' . esc_attr( $db_entry_id ); ?></h2>

						<p class="sui-description"><?php echo esc_html( $entry_date ); ?></p>

					</div>

					<?php // ROW: Lead Details. ?>
					<div class="fui-entries-block">

						<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Lead Details', Forminator::DOMAIN ); ?></h3>

						<?php if ( ! empty( $detail_items ) ) { ?>

							<table class="fui-entries-table" data-design="ghost">

								<tbody>

									<?php foreach ( $detail_items as $detail_item ) { ?>

										<?php $sub_entries = $detail_item['sub_entries']; ?>

										<?php if ( isset( $detail_item['type'] ) && ( 'stripe' === $detail_item['type'] || 'paypal' === $detail_item['type'] ) ) { ?>

											<?php if ( ! empty( $sub_entries ) ) { ?>

												<tr>

													<td><?php echo esc_html( $detail_item['label'] ); ?></td>

													<td>

														<table class="fui-entries-table" data-size="sm">

															<thead>

																<tr>

																	<?php
																	$end = count( $sub_entries );
																	foreach ( $sub_entries as $sub_key => $sub_entry ) {

																		$sub_key ++;

																		if ( $sub_key === $end ) {

																			echo '<th colspan="2">' . esc_html( $sub_entry['label'] ) . '</th>';

																		} else {

																			echo '<th>' . esc_html( $sub_entry['label'] ) . '</th>';

																		}
																	}
																	?>

																</tr>

															</thead>

															<tbody>

																<tr>

																	<?php
																	$end = count( $sub_entries );
																	foreach ( $sub_entries as $sub_key => $sub_entry ) {

																		$sub_key ++;

																		if ( $sub_key === $end ) {
																			// No escape for Stripe & PayPal transaction links because we generate it ourselves above
																			echo '<td colspan="2">' . $sub_entry['value'] . '</td>'; //phpcs:ignore -- html output intended

																		} else {

																			echo '<td>' . esc_html( $sub_entry['value'] ) . '</td>';

																		}
																	}
																	?>

																</tr>

															</tbody>

														</table>

													</td>

												</tr>

											<?php } ?>

										<?php } else { ?>

											<tr>

												<td><?php echo esc_html( $detail_item['label'] ); ?></td>

												<td>

													<?php if ( empty( $sub_entries ) ) { ?>

														<?php if ( 'textarea' === $detail_item['type'] && ( isset( $detail_item['rich'] ) && 'true' === $detail_item['rich'] ) ): ?>

															<div class="fui-rich-textarea"><?php echo( $detail_item['value'] );//phpcs:ignore -- html output intended ?></div>

														<?php else: ?>

															<?php echo( $detail_item['value'] );//phpcs:ignore -- html output intended ?>

														<?php endif; ?>

													<?php } else { ?>

														<?php foreach ( $sub_entries as $sub_entry ) { ?>

															<div class="sui-form-field">
																<span class="sui-settings-label"><?php echo esc_html( $sub_entry['label'] ); ?></span>
																<span class="sui-description"><?php echo( $sub_entry['value'] );//phpcs:ignore -- html output intended ?></span>
															</div>

														<?php } ?>

													<?php } ?>

												</td>

											</tr>

										<?php } ?>

									<?php } ?>

								</tbody>

							</table>

						<?php } else { ?>

							<div class="sui-notice">
								<p><?php esc_html_e( 'Lead details are not available for this submission. Looks like the participant opted to skip the lead generation form while submitting the quiz.', Forminator::DOMAIN ); ?></p>
							</div>

						<?php } ?>

					</div>

					<?php // ROW: Quiz Results. ?>
					<div class="fui-entries-block">

						<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Quiz Results', Forminator::DOMAIN ); ?></h3>

						<?php if ( ! empty( $quiz_entry ) ) {

							if ( 'knowledge' === $form_type ) {

								$meta  = isset( $quiz_entry['value'] ) ? $quiz_entry['value'] : array();
								$total = count( $meta );
								$right = 0;
								if ( ! empty( $meta ) ) {
									foreach ( $meta as $answer ) {
										if ( isset( $answer['isCorrect'] ) && $answer['isCorrect'] ) {
											$right ++;
										}
									}
								}
								?>

								<p class="sui-description"><?php echo sprintf( __( 'You got %s/%s correct answers.', Forminator::DOMAIN ), $right, $total ); // phpcs:ignore ?></p>

								<table class="fui-entries-table">

									<thead>

										<tr>
											<th><?php esc_html_e( 'Question', Forminator::DOMAIN ); ?></th>
											<th><?php esc_html_e( 'Answer', Forminator::DOMAIN ); ?></th>
										</tr>

									</thead>

									<tbody>

										<?php foreach ( $meta as $answer ) : ?>

											<?php $user_answer = $answer['answer']; ?>

											<tr>
												<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
												<td>
													<?php if ( $answer['isCorrect'] ) {
														echo '<span class="sui-tag sui-tag-success">' . esc_html( $user_answer ) . '</span>';
													} else {
														echo '<span class="sui-tag sui-tag-error">' . esc_html( $user_answer ) . '</span>';
													} ?>
                                            	</td>
											</tr>

										<?php endforeach; ?>

									</tbody>

									<tfoot aria-hidden="true">

										<tr>

											<td colspan="2">

												<div class="fui-entries-table-legend">

													<p class="correct"><?php esc_html_e( 'Correct', Forminator::DOMAIN ); ?></p>

													<p class="incorrect"><?php esc_html_e( 'Incorrect', Forminator::DOMAIN ); ?></p>

												</div>

											</td>

										</tr>

									</tfoot>

								</table>

							<?php } else { ?>

								<?php $meta = $quiz_entry['value'][0]['value']; ?>

								<?php if ( isset( $meta['answers'] ) && is_array( $meta['answers'] ) ) : ?>

									<table class="fui-entries-table">

										<thead>

											<tr>
												<th><?php esc_html_e( 'Question', Forminator::DOMAIN ); ?></th>
												<th><?php esc_html_e( 'Answer', Forminator::DOMAIN ); ?></th>
											</tr>

										</thead>

										<tbody>

											<?php foreach ( $meta['answers'] as $answer ) : ?>

												<tr>
													<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
													<td><?php echo esc_html( $answer['answer'] ); ?></td>
												</tr>

											<?php endforeach; ?>

										</tbody>

										<tfoot aria-hidden="true">

											<tr>

												<td colspan="2"><?php printf( __( '<strong>Quiz Result:</strong> %s', Forminator::DOMAIN ), $meta['result']['title'] ); // phpcs:ignore ?></td>

											</tr>

										</tfoot>

									</table>

								<?php endif; ?>

							<?php } ?>

						<?php } else { ?>
                            <div class="sui-notice">
                                <p><?php esc_html_e( 'Quiz results are not available for this submission. The participant either couldn\'t finish the quiz or had some errors while submitting the quiz.', Forminator::DOMAIN ); ?></p>
                            </div>
						<?php } ?>

					</div>

					<?php // ROW: Integrations. ?>
					<?php if ( ! empty( $integrations ) ) { ?>

						<div class="fui-entries-block">

							<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Active Integrations', Forminator::DOMAIN ); ?></h3>

							<p class="sui-description"><?php esc_html_e( 'Check the status and response of your active 3rd-party applications for this submissions.', Forminator::DOMAIN ); ?></p>

							<div class="sui-accordion">

								<div class="sui-accordion-header">
									<div><?php esc_html_e( 'Integration Name', Forminator::DOMAIN ); ?></div>
									<div><?php esc_html_e( 'Data send to integration', Forminator::DOMAIN ); ?></div>
									<div></div>
								</div>

								<?php foreach ( $integrations as $integration ) : ?>

									<div class="sui-accordion-item">

										<div class="sui-accordion-item-header">

											<div class="fui-app--wrapper">
												<img
													src="<?php echo esc_url( $integration['banner'] ); ?>"
													srcset="<?php echo esc_url( $integration['banner'] ); ?> 1x, <?php echo esc_url( $integration['banner_x2'] ); ?> 2x"
													alt="<?php echo esc_attr( $integration['label'] ); ?>"
													class="sui-image"
													style="width: 20px; height: 20px;"
												/>
												<span style="margin-left: 10px;"><?php echo $integration['title']; // phpcs:ignore -- html output intended ?></span>
											</div>

											<div>

												<?php
												$sub_entries = isset( $integration['sub_entries'] ) ? $integration['sub_entries'] : array();

												if ( ! empty( $sub_entries ) && is_array( $sub_entries ) ) :

													$success = isset( $sub_entries[1]['value'] ) ? $sub_entries[1]['value'] : '';
													?>

													<span class="integration-"<?php echo esc_attr( $success ); ?>><?php echo esc_html( $success ); ?></span>

												<?php else : ?>

													<span><?php echo( $integration['value'] ); // phpcs:ignore -- html output intended ?></span>

												<?php endif; ?>

											</div>

											<div>
												<span class="sui-button-icon sui-accordion-open-indicator" aria-label="Open item">
													<i class="sui-icon-chevron-down" aria-hidden="true"></i>
												</span>
											</div>

										</div>

										<div class="sui-accordion-item-body">

											<div class="sui-box">

												<div class="sui-box-body">

													<?php if ( ! empty( $sub_entries ) && is_array( $sub_entries ) ) { ?>

														<?php foreach ( $sub_entries as $sub_entry ) { ?>

															<div class="">
																<span class="sui-settings-label"><?php echo esc_html( $sub_entry['label'] ); ?></span>
																<span class="sui-description"><?php echo( $sub_entry['value'] ); // phpcs:ignore -- html output intended ?></span>
															</div>

														<?php } ?>

													<?php } ?>

												</div>

											</div>

										</div>

									</div>

								<?php endforeach; ?>

							</div>

						</div>

					<?php } ?>

				</div>

                <div class="sui-box-footer">

                    <button
                            type="button"
                            class="sui-button sui-button-ghost sui-button-red wpmudev-open-modal"
						<?php if ( isset( $entries['activation_key'] ) ) {
							$button_title = esc_html__( 'Delete Submission & User', Forminator::DOMAIN );
							$is_activation_key = true;
							?>
                            data-activation-key="<?php echo $entries['activation_key']; ?>"
                            data-modal="delete-unconfirmed-user-module"
                            data-entry-id="<?php echo esc_attr( $db_entry_id ); ?>"
                            data-form-id="<?php echo esc_attr( $this->model->id ); ?>"
						<?php } else {
							$button_title = esc_html__( 'Delete', Forminator::DOMAIN );
							$is_activation_key = false;
							?>
                            data-modal="delete-module"
                            data-form-id="<?php echo esc_attr( $db_entry_id ); ?>"
						<?php } ?>
                            data-modal-title="<?php esc_attr_e( 'Delete Submission', Forminator::DOMAIN ); ?>"
                            data-modal-content="<?php esc_attr_e( 'Are you sure you wish to permanently delete this submission?', Forminator::DOMAIN ); ?>"
                            data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorQuizEntries' ) ); ?>"
                    >
                        <i class="sui-icon-trash" aria-hidden="true"></i> <?php echo $button_title; ?>
                    </button>

					<?php if ( isset( $entries['activation_method'] ) && 'manual' === $entries['activation_method'] && $is_activation_key  ) { ?>

                        <div class="sui-actions-right">
                            <button
                                    type="button"
                                    class="sui-button wpmudev-open-modal"
                                    data-modal="approve-user-module"
                                    data-modal-title="<?php esc_attr_e( 'Approve User', Forminator::DOMAIN ); ?>"
                                    data-modal-content="<?php esc_attr_e( 'Are you sure you want to approve and activate this user?', Forminator::DOMAIN ); ?>"
                                    data-form-id="<?php echo esc_attr( $db_entry_id ); ?>"
                                    data-activation-key="<?php echo esc_attr( $entries['activation_key'] ); ?>"
                                    data-nonce="<?php echo wp_create_nonce( 'forminatorCustomFormEntries' ); // WPCS: XSS ok. ?>"
                            >
								<?php esc_html_e( 'Approve User', Forminator::DOMAIN ); ?>
                            </button>
                        </div>

					<?php } ?>

                </div>

            </div>

        </td>

    </tr>

<?php } ?>
