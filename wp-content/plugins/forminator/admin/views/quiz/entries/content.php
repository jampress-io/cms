<?php
/** @var Forminator_Quizz_Renderer_Entries $this */
$plugin_path       = forminator_plugin_url();
$count             = $this->filtered_total_entries();
$entries_per_page  = $this->get_per_page();
$is_filter_enabled = $this->is_filter_box_enabled();
$total_page        = ceil( $count / $entries_per_page );
?>
<?php if ( $this->error_message() ) : ?>
	<span class="sui-notice sui-notice-error"><p><?php echo esc_html( $this->error_message() ); ?></p></span>
<?php endif; ?>

<?php if ( $count > 0 ) : ?>

	<form method="get" class="sui-box fui-box-entries forminator-entries-actions">

        <div class="fui-pagination-entries sui-pagination-wrap">

            <span class="sui-pagination-results"><?php if ( 1 === $count ) { printf( esc_html__( '%s result', Forminator::DOMAIN ), $count ); } else { printf( esc_html__( '%s results', Forminator::DOMAIN ), $count ); } // phpcs:ignore ?></span>

			<?php $this->paginate(); ?>

        </div>

        <div class="sui-box fui-box-entries">

            <fieldset class="forminator-entries-nonce">
	            <?php wp_nonce_field( 'forminatorQuizEntries', 'forminatorEntryNonce' ); ?>
            </fieldset>

            <div class="sui-box-body fui-box-actions">

                <?php $this->template( 'quiz/entries/prompt' ); ?>

                <input type="hidden" name="page" value="<?php echo esc_attr( $this->get_admin_page() ); ?>">
                <input type="hidden" name="form_type" value="<?php echo esc_attr( $this->forminator_get_form_type() ); ?>">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $this->form_id ); ?>"/>

                <div class="sui-box-search">

                    <div class="sui-search-left">

                        <?php $this->bulk_actions(); ?>

                    </div>

                    <div class="sui-search-right">

                        <div class="sui-pagination-wrap">

                            <span class="sui-pagination-results">
                                <?php
                                if ( 1 === $count ) {
                                    /* translators: ... */
                                    printf( esc_html__( '%s result', Forminator::DOMAIN ), esc_html( $count ) );
                                } else {
                                    /* translators: ... */
                                    printf( esc_html__( '%s results', Forminator::DOMAIN ), esc_html( $count ) );
                                }
                                ?>
                            </span>

                            <?php $this->paginate(); ?>

                            <button class="sui-button-icon sui-button-outlined forminator-toggle-entries-filter <?php echo( $is_filter_enabled ? 'sui-active' : '' ); ?>">
                                <i class="sui-icon-filter" aria-hidden="true"></i>
                            </button>

                        </div>

                    </div>

                </div>

                <?php $this->template( 'quiz/entries/filter' ); ?>

            </div>

            <?php if ( isset( $is_filter_enabled ) &&  true === $is_filter_enabled ) : ?>

                <div class="sui-box-body fui-box-actions-filters">

                    <label class="sui-label"><?php esc_html_e( 'Active Filters', Forminator::DOMAIN ); ?></label>

                    <div class="sui-pagination-active-filters forminator-entries-fields-filters">

                        <?php if ( isset( $this->filters['search'] ) ) : ?>
                            <div class="sui-active-filter">
                                <?php
                                printf(/* translators: ... */
                                    esc_html__( 'Keyword: %s', Forminator::DOMAIN ),
                                    esc_html( $this->filters['search'] )
                                );
                                ?>
                                <button class="sui-active-filter-remove" type="submit" name="search" value="">
                                    <span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', Forminator::DOMAIN ); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $this->filters['min_id'] ) ) : ?>
                            <div class="sui-active-filter">
                                <?php
                                printf(/* translators: ... */
                                    esc_html__( 'From ID: %s', Forminator::DOMAIN ),
                                    esc_html( $this->filters['min_id'] )
                                );
                                ?>
                                <button class="sui-active-filter-remove" type="submit" name="min_id" value="">
                                    <span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', Forminator::DOMAIN ); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $this->filters['max_id'] ) ) : ?>
                            <div class="sui-active-filter">
                                <?php
                                printf(/* translators: ... */
                                    esc_html__( 'To ID: %s', Forminator::DOMAIN ),
                                    esc_html( $this->filters['max_id'] )
                                );
                                ?>
                                <button class="sui-active-filter-remove" type="submit" name="max_id" value="">
                                    <span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', Forminator::DOMAIN ); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $this->filters['date_created'][0] ) || isset( $this->filters['date_created'][1] ) ) : ?>
                            <div class="sui-active-filter">
                                <?php
                                printf(/* translators: ... */
                                    esc_html__( 'Submission Date Range: %1$s to %2$s', Forminator::DOMAIN ),
                                    esc_html( $this->filters['date_created'][0] ),
                                    esc_html( $this->filters['date_created'][1] )
                                );
                                ?>
                                <button class="sui-active-filter-remove" type="submit" name="date_range" value="">
                                    <span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', Forminator::DOMAIN ); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="sui-active-filter">
                            <?php
                            esc_html_e( 'Sort Order', Forminator::DOMAIN );
                            echo ': ';
                            if ( 'DESC' === $this->order['order'] ) {
                                esc_html_e( 'Descending', Forminator::DOMAIN );
                            } else {
                                esc_html_e( 'Ascending', Forminator::DOMAIN );
                            }
                            ?>
                        </div>

                    </div>

                </div>

            <?php endif; ?>

            <table class="sui-table sui-table-flushed sui-accordion fui-table-entries">

                <?php $this->entries_header(); ?>

                <tbody>

                    <?php if ( $this->has_leads() ) {
                        $this->template( 'quiz/entries/content-leads' );
                    } else {
                        $this->template( 'quiz/entries/content-leads-none' );
                    } ?>
                </tbody>

            </table>

            <div class="sui-box-body">

                <div class="sui-box-search">

                    <div class="sui-search-left">

                        <?php $this->bulk_actions( 'bottom' ); ?>

                    </div>

                    <div class="sui-search-right">

                        <div class="sui-pagination-wrap">

                            <span class="sui-pagination-results">
                                <?php
                                if ( 1 === $count ) {
                                    /* translators: ... */
                                    printf( esc_html__( '%s result', Forminator::DOMAIN ), esc_html( $count ) );
                                } else {
                                    /* translators: ... */
                                    printf( esc_html__( '%s results', Forminator::DOMAIN ), esc_html( $count ) );
                                }
                                ?>
                            </span>

                            <?php $this->paginate(); ?>

                        </div>

                    </div>

                </div>

            </div>
        </div>

	</form>

<?php else : ?>

	<div class="sui-box sui-message">

		<?php if ( forminator_is_show_branding() ) : ?>
			<img src="<?php echo esc_url( $plugin_path . 'assets/img/forminator-submissions.png' ); ?>"
				srcset="<?php echo esc_url( $plugin_path . 'assets/img/forminator-submissions.png' ); ?> 1x,
				<?php echo esc_url( $plugin_path . 'assets/img/forminator-submissions@2x.png' ); ?> 2x"
				alt="<?php esc_html_e( 'Forminator', Forminator::DOMAIN ); ?>"
				class="sui-image"
				aria-hidden="true"/>
		<?php endif; ?>

		<div class="sui-message-content">

			<h2><?php echo forminator_get_form_name( $this->form_id, 'quiz' ); // phpcs:ignore ?></h2>

			<p><?php esc_html_e( 'You haven\'t received any submissions for this quiz yet. When you do, you\'ll be able to view all the data here.', Forminator::DOMAIN ); ?></p>

		</div>

	</div>

<?php endif; ?>
