<?php
$empty_icon   = forminator_plugin_url() . 'assets/images/forminator-noicon.png';
$empty_icon2x = forminator_plugin_url() . 'assets/images/forminator-noicon@2x.png';
if ( empty( $module_id ) ) {
	$module_id = 0;
}

$show_action = false;

$icon_class_action = 'sui-icon-plus';
$tooltip           = __( 'Configure Integration', 'forminator' );
$action            = 'forminator_addon_settings';

$multi_id   = 0;
$multi_name = false;

if ( ! empty( $module_id ) ) {
	$action            = 'forminator_addon_module_settings';
	$icon_class_action = 'sui-icon-plus';
	if ( ! empty( $addon['is_' . $module_slug . '_settings_available'] ) && true === $addon['is_' . $module_slug . '_settings_available'] ) {
		$show_action = true;
		if ( $addon['is_allow_multi_on_' . $module_slug ] ) {
			if ( isset( $addon['multi_name'] ) ) {
				$icon_class_action = 'sui-icon-widget-settings-config';
				$tooltip           = __( 'Configure App', 'forminator' );
				$multi_id          = $addon['multi_id'];
				$multi_name        = $addon['multi_name'];
			} else {

				if ( isset( $addon['multi_id'] ) ) {
					$multi_id = $addon['multi_id'];
				}

				$icon_class_action = 'sui-icon-plus';
				$tooltip           = __( 'Activate App', 'forminator' );

			}
		} else {
			if ( $addon['is_' . $module_slug . '_connected'] ) {
				$icon_class_action = 'sui-icon-widget-settings-config';
				$tooltip           = __( 'Configure App', 'forminator' );
			} else {
				$icon_class_action = 'sui-icon-plus';
				$tooltip           = __( 'Activate App', 'forminator' );
			}
		}
	}
} else {

	// on integrations page
	if ( isset( $addon['is_settings_available'] ) && ! empty( $addon['is_settings_available'] ) && true === $addon['is_settings_available'] ) {

		$show_action = true;

		if ( $addon['is_connected'] ) {
			$icon_class_action = 'sui-icon-widget-settings-config';
			$tooltip           = __( 'Configure App', 'forminator' );
		} else {
			$icon_class_action = 'sui-icon-plus';
			$tooltip           = __( 'Connect App', 'forminator' );
		}
	}
}

$action_available = false;

if ( ! empty( $show_pro_info ) && $show_pro_info ) {
	$show_pro_info = true;
} else {
	$show_pro_info = false;
}

$advertising = false;

if ( 'zapier' === $addon['slug'] ) {
	$advertising = true;
}

/**
 * force Disable pro tag y default
 */
$show_pro_info = false;

$pro_url        = 'https://wpmudev.com';
$pro_url_target = '_blank';

// MULTI NAME (SAMPLE)
// To be added in the table later when design is ready.
/*
<td><?php if ( ! empty( $multi_name ) ): ?>
	<?php echo esc_html( $multi_name ); ?>
<?php endif; ?></td>
*/ ?>

<tr class="
	<?php
	if ( ! $is_active && true === $advertising ) {
		echo 'fui-app--promote '; }
	?>
	<?php echo( $is_active ? 'fui-integration-enabled' : '' ); ?>">

	<td class="sui-table-item-title">

		<div class="fui-app--wrapper">

			<?php if ( true === $advertising && ! $is_active ) { ?>

				<?php if ( isset( $addon['banner'] ) && ! empty( $addon['banner'] ) ) { ?>

					<div
							role="banner"
							class="fui-app--banner"
							data-app="<?php echo esc_attr( $addon['slug'] ); ?>"
							aria-hidden="true"
					>

						<img
								src="<?php echo esc_url( $addon['banner'] ); ?>"
								srcset="<?php echo esc_url( $addon['banner'] ); ?> 1x, <?php echo esc_url( $addon['banner_x2'] ); ?> 2x"
								alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
								class="sui-image"
						/>

					</div>

				<?php } ?>

				<div class="fui-app--content">

					<div class="fui-app--title">

						<span><?php echo esc_html( $addon['title'] ); ?><?php if ( $show_pro_info && $addon['is_pro'] ) : ?>
								<span class="sui-tag sui-tag-pro">
								<?php esc_html_e( 'PRO', 'forminator' ); ?>
							</span>
							<?php endif; ?></span>

						<?php if ( ! $is_active && ! empty( $addon['documentation'] ) && forminator_is_show_documentation_link() ) { ?>
							<a href="<?php echo esc_url( $addon['documentation'] ); ?>" target="_blank"><?php esc_html_e( 'View Docs', 'forminator' ); ?></a>
						<?php } ?>

						<?php if ( $show_action ) : ?>
							<button class="sui-button-icon sui-tooltip sui-tooltip-top-right connect-integration"
									data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
									data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
									data-title="<?php echo esc_attr( $addon['title'] ); ?>"
									data-image="<?php echo esc_attr( $addon['image'] ); ?>"
									data-imagex2="<?php echo esc_attr( $addon['image_x2'] ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addon_action' ) ); ?>"
									data-action="<?php echo esc_attr( $action ); ?>"
									data-<?php echo esc_attr( $module_slug ); ?>-id="<?php echo esc_attr( $module_id ); ?>"
									data-multi-id="<?php echo esc_attr( $multi_id ); ?>">
								<i class="<?php echo esc_attr( $icon_class_action ); ?>" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Connect this integration', 'forminator' ); ?></span>
							</button>
						<?php endif; ?>

					</div>

					<?php if ( ! $is_active && isset( $addon['promotion'] ) && ! empty( $addon['promotion'] ) ) { ?>
						<span class="fui-app--description"><?php echo wp_kses_post( $addon['promotion'] ); ?></span>
					<?php } ?>

				</div>

			<?php } else { ?>

				<?php if ( isset( $addon['icon'] ) && ! empty( $addon['icon'] ) ) { ?>
					<img
							src="<?php echo esc_url( $addon['icon'] ); ?>"
							srcset="<?php echo esc_url( $addon['icon'] ); ?> 1x, <?php echo esc_url( $addon['icon_x2'] ); ?> 2x"
							alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
							class="sui-image"
							aria-hidden="true"
					/>
				<?php } else { ?>
					<img
							src="<?php echo esc_url( $empty_icon ); ?>"
							srcset="<?php echo esc_url( $empty_icon ); ?> 1x, <?php echo esc_url( $empty_icon2x ); ?> 2x"
							alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
							class="sui-image"
							aria-hidden="true"
					/>
				<?php } ?>

				<span><?php echo esc_html( $addon['title'] ); ?><?php if ( $show_pro_info && $addon['is_pro'] ) : ?>
						<span class="sui-tag sui-tag-pro">
						<?php esc_html_e( 'PRO', 'forminator' ); ?>
					</span>
					<?php endif; ?></span>

				<?php if ( $show_action ) : ?>
				<button class="sui-button-icon sui-tooltip sui-tooltip-top-right connect-integration"
						data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
						data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
						data-title="<?php echo esc_attr( $addon['title'] ); ?>"
						data-image="<?php echo esc_attr( $addon['image'] ); ?>"
						data-imagex2="<?php echo esc_attr( $addon['image_x2'] ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addon_action' ) ); ?>"
						data-action="<?php echo esc_attr( $action ); ?>"
						data-<?php echo esc_attr( $module_slug ); ?>-id="<?php echo esc_attr( $module_id ); ?>"
						data-multi-id="<?php echo esc_attr( $multi_id ); ?>">
					<i class="<?php echo esc_attr( $icon_class_action ); ?>" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Connect this integration', 'forminator' ); ?></span>
				</button>
				<?php endif; ?>

			<?php } ?>

			</div>

	</td>

</tr>
