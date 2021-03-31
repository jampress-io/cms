<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin
 *
 * @since 1.0
 */
class Forminator_Admin {

	/**
	 * @var array
	 */
	public $pages = array();

	/**
	 * Forminator_Admin constructor.
	 */
	public function __construct() {
		$this->includes();

		// Init admin pages
		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );
		add_action( 'admin_notices', array( $this, 'show_stripe_updated_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_rating_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_pro_available_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_cf7_importer_notice' ) );

		// Add plugin action links
		add_filter( 'plugin_action_links_' . FORMINATOR_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		if ( forminator_is_networkwide() ) {
			add_filter( 'network_admin_plugin_action_links_' . FORMINATOR_PLUGIN_BASENAME, array(
				$this,
				'add_plugin_action_links'
			) );
		}
		// Add links next to plugin details
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

		// Init Admin AJAX class
		new Forminator_Admin_AJAX();

		/**
		 * Triggered when Admin is loaded
		 */
		do_action( 'forminator_admin_loaded' );
	}

	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	private function includes() {
		// Admin pages
		include_once forminator_plugin_dir() . 'admin/pages/dashboard-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/entries-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/integrations-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/settings-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/upgrade-page.php';

		// Admin AJAX
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-ajax.php';

		// Admin Data
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-data.php';

		// Admin l10n
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-l10n.php';

		if ( forminator_is_import_plugin_enabled( 'cf7' ) ) {
			//CF7 Import
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-cf7.php';
		}

		if ( forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			//Ninjaforms Import
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-ninja.php';
		}

		if ( forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			//Gravityforms CF7 Import
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-gravity.php';
		}

	}

	/**
	 * Initialize Dashboard page
	 *
	 * @since 1.0
	 */
	public function add_dashboard_page() {
		$title = __( 'Forminator', Forminator::DOMAIN );
		if ( FORMINATOR_PRO ) {
			$title = __( 'Forminator Pro', Forminator::DOMAIN );
		}

		$this->pages['forminator']           = new Forminator_Dashboard_Page( 'forminator', 'dashboard', $title, $title, false, false );
		$this->pages['forminator-dashboard'] = new Forminator_Dashboard_Page( 'forminator', 'dashboard', __( 'Forminator Dashboard', Forminator::DOMAIN ), __( 'Dashboard', Forminator::DOMAIN ), 'forminator' );
	}

	/**
	 * Add Integrations page
	 *
	 * @since 1.1
	 */
	public function add_integrations_page() {
		add_action( 'admin_menu', array( $this, 'init_integrations_page' ) );
	}

	/**
	 * Initialize Integrations page
	 *
	 * @since 1.1
	 */
	public function init_integrations_page() {
		$this->pages['forminator-integrations'] = new Forminator_Integrations_Page(
			'forminator-integrations',
			'integrations',
			__( 'Integrations', Forminator::DOMAIN ),
			__( 'Integrations', Forminator::DOMAIN ),
			'forminator'
		);

		//TODO: remove this after converted to JS
		$addons = Forminator_Addon_Loader::get_instance()->get_addons()->to_array();
		foreach ( $addons as $slug => $addon_array ) {
			$addon_class = forminator_get_addon( $slug );

			if ( $addon_class && is_callable( array( $addon_class, 'admin_hook_html_version' ) ) ) {
				call_user_func( array( $addon_class, 'admin_hook_html_version' ) );
			}
		}

	}

	/**
	 * Add Settings page
	 *
	 * @since 1.0
	 */
	public function add_settings_page() {
		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
	}

	/**
	 * Initialize Settings page
	 *
	 * @since 1.0
	 */
	public function init_settings_page() {
		$this->pages['forminator-settings'] = new Forminator_Settings_Page( 'forminator-settings', 'settings', __( 'Global Settings', Forminator::DOMAIN ), __( 'Settings', Forminator::DOMAIN ), 'forminator' );
	}

	/**
	 * Add Entries page
	 *
	 * @since 1.0.5
	 */
	public function add_entries_page() {
		add_action( 'admin_menu', array( $this, 'init_entries_page' ) );
	}

	/**
	 * Initialize Entries page
	 *
	 * @since 1.0.5
	 */
	public function init_entries_page() {
		$this->pages['forminator-entries'] = new Forminator_Entries_Page(
			'forminator-entries',
			'entries',
			__( 'Forminator Submissions', Forminator::DOMAIN ),
			__( 'Submissions', Forminator::DOMAIN ),
			'forminator'
		);
	}

	/**
	 * Add Forminator Pro page
	 *
	 * @since 1.0
	 */
	public function add_upgrade_page() {
		add_action( 'admin_menu', array( $this, 'init_upgrade_page' ) );
	}

	/**
	 * Initialize Settings page
	 *
	 * @since 1.0
	 */
	public function init_upgrade_page() {
		$this->pages['forminator-upgrade'] = new Forminator_Upgrade_Page( 'forminator-upgrade', 'upgrade', __( 'Upgrade to Forminator Pro', Forminator::DOMAIN ), __( 'Forminator Pro', Forminator::DOMAIN ), 'forminator' );
	}

	/**
	 * Check if we have any Stripe form
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function has_stripe_forms() {
		$forms = Forminator_Custom_Form_Model::model()->get_models_by_field( 'stripe-1' );

		if ( count( $forms ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we have any old Stripe form
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function has_old_stripe_forms() {
		$forms = Forminator_Custom_Form_Model::model()->get_models_by_field_and_version( 'stripe-1', '1.9-alpha.1' );

		if ( count( $forms ) > 0 ) {
			return true;
		}

		return false;
	}


	/**
	 * Displays an admin notice when the user is an active member and doesn't have Forminator Pro installed
	 * Shown in forminator pages. Per user notification.
	 */
	public function show_pro_available_notice() {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		if ( 'forminator' !== substr( $page, 0, 10 ) || FORMINATOR_PRO ) {
			return;
		}

		// The notice was already dismissed.
		if ( self::was_notification_dismissed( 'forminator_pro_is_available' ) ) {
			return;
		}

		// Show the notice only to users who can do something about this and who are members.
		if ( ! self::user_can_update_plugins() || ! in_array( forminator_membership_status(), array( 'full', 'upgrade' ), true ) ) {
			return;
		}

		$url  = add_query_arg(
			array( 'page' => 'wpmudev-plugins' ),
			network_admin_url( 'admin.php' )
		) . '#pid=2097296';
		$link = '<a type="button" href="' . esc_url( $url ) . '" target="_self" class="button button-primary">' . esc_html__( 'Upgrade' ) . '</a>';

		$username = forminator_get_current_username();
		$name     = ! empty( $username ) ? $username : __( 'Hey', Forminator::DOMAIN );

		$message = '<p>';
		/* translators: user's name */
		$message .= sprintf( esc_html__( '%s, it appears you have an active WPMU DEV membership but haven\'t upgraded Forminator to the pro version. You won\'t lose any settings upgrading, go for it!', Forminator::DOMAIN ), $name );
		$message .= '</p>';
		$message .= '<p>' . $link . '</p>';

		echo '<div class="forminator-grouped-notice notice notice-info is-dismissible"'
			. ' data-notice-slug="forminator_pro_is_available"'
			. ' data-nonce="' . esc_attr( wp_create_nonce( 'forminator_dismiss_notice' ) ) . '">';
		echo $message;
		echo '</div>';
	}

	/**
	 * Check if the given notification was dismissed.
	 *
	 * @param string $notification_name Notification slug.
	 * @return bool
	 */
	public static function was_notification_dismissed( $notification_name ) {
		$dismissed = get_user_meta( get_current_user_id(), 'frmt_dismissed_messages', true );

		return ( is_array( $dismissed ) && in_array( $notification_name, $dismissed, true ) );
	}

	/**
	 * Check if the current user is able to update plugins
	 *
	 * @return bool
	 */
	public static function user_can_update_plugins() {
		$cap = is_multisite() ? 'manage_network_plugins' : 'update_plugins';

		return current_user_can( $cap );
	}

	/**
	 * Show CF7 importer notice
	 *
	 * @since 1.11
	 */
	public function show_cf7_importer_notice() {
		$notice_dismissed = get_option( 'forminator_cf7_notice_dismissed', false );

		if ( $notice_dismissed ) {
			return;
		}

		if ( ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			return;
		}

		?>
        <div class="forminator-notice-cf7 forminator-notice notice notice-info"
             data-prop="forminator_cf7_notice_dismissed"
             data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">
            <p style="color: #1A2432; font-size: 14px; font-weight: bold;"><?php echo esc_html__( 'Forminator - Import your Contact Form 7 forms automatically', Forminator::DOMAIN ); ?></p>

            <p style="color: #72777C; line-height: 22px;"><?php echo esc_html__( 'We noticed that Contact Form 7 is active on your website. You can use our built-in Contact Form 7 importer to import your existing forms and the relevant plugin settings from Contact Form 7 to Forminator. The importer supports the most widely used add-ons as well.', Forminator::DOMAIN ); ?></p>

            <p>
                <a href="<?php echo esc_url( menu_page_url( 'forminator-settings', false ) . '&section=import' ); ?>"
                   class="button button-primary"><?php esc_html_e( 'Import Contact Form 7 Forms', Forminator::DOMAIN ); ?></a>
                <a href="#" class="dismiss-notice"
                   style="margin-left: 10px; text-decoration: none; color: #555; font-weight: 500;"><?php esc_html_e( 'Dismiss', Forminator::DOMAIN ); ?></a>
            </p>

        </div>

        <script type="text/javascript">
            jQuery('.forminator-notice-cf7 .button-primary').on('click', function (e) {
                e.preventDefault();

                var $self = jQuery(this);
                var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
                var ajaxUrl = '<?php echo forminator_ajax_url(); ?>';

                jQuery.post(
                    ajaxUrl,
                    {
                        action: 'forminator_dismiss_notification',
                        prop: $notice.data('prop'),
                        _ajax_nonce: $notice.data('nonce')
                    }
                ).always(function () {
                    location.href = $self.attr('href');
                });
            });

            jQuery('.forminator-notice-cf7 .dismiss-notice').on('click', function (e) {
                e.preventDefault();

                var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
                var ajaxUrl = '<?php echo forminator_ajax_url(); ?>';

                jQuery.post(
                    ajaxUrl,
                    {
                        action: 'forminator_dismiss_notification',
                        prop: $notice.data('prop'),
                        _ajax_nonce: $notice.data('nonce')
                    }
                ).always(function () {
                    $notice.hide();
                });
            });
        </script>
		<?php
	}

	/**
	 * Show Stripe admin notice
	 *
	 * @since 1.9
	 */
	public function show_stripe_updated_notice() {
		$notice_dismissed = get_option( 'forminator_stripe_notice_dismissed', false );

		if ( $notice_dismissed ) {
			return;
		}

		if ( ! $this->has_old_stripe_forms() ) {
			return;
		}
		?>

		<div class="forminator-notice notice notice-warning" data-prop="forminator_stripe_notice_dismissed" data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

			<p style="color: #72777C; line-height: 22px;"><?php echo sprintf( __( 'To make Forminator\'s Stripe field <a href="%s" target="_blank">SCA Compliant</a>, we have replaced the Stripe Checkout modal with Stripe Elements which adds an inline field to collect your customer\'s credit or debit card details. Your existing forms with Stripe field are automatically updated, but we recommend checking them to ensure everything works fine.', Forminator::DOMAIN ), 'https://stripe.com/gb/guides/strong-customer-authentication' ); ?></p>

			<p>
				<a href="<?php echo esc_url( menu_page_url( 'forminator', false ) . '&show_stripe_dialog=true' ); ?>" class="button button-primary"><?php esc_html_e( 'Learn more', Forminator::DOMAIN ); ?></a>
				<a href="#" class="dismiss-notice" style="margin-left: 10px; text-decoration: none; color: #555; font-weight: 500;"><?php esc_html_e( 'Dismiss', Forminator::DOMAIN ); ?></a>
			</p>

		</div>

		<script type="text/javascript">
			jQuery( '.forminator-notice .dismiss-notice' ).on( 'click', function( e ) {
				e.preventDefault();

				var $notice = jQuery( e.currentTarget ).closest( '.forminator-notice' );
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
		<?php
	}

	/**
	 * Show rating admin notice
	 *
	 * @since 1.10
	 */
	public function show_rating_notice() {

		if ( FORMINATOR_PRO ) {
			return;
		}

		$notice_success   = get_option( 'forminator_rating_success', false );
		$notice_dismissed = get_option( 'forminator_rating_dismissed', false );

		if ( $notice_dismissed || $notice_success ) {
			return;
		}
		$published_modules     = forminator_total_forms( 'publish' );
		$publish_later         = get_option( 'forminator_publish_rating_later', false );
		$publish_later_dismiss = get_option( 'forminator_publish_rating_later_dismiss', false );

		if ( ( ( 5 < $published_modules && 10 >= $published_modules ) && ! $publish_later ) || ( 10 < $published_modules && ! $publish_later_dismiss ) ) {

			$milestone = ( 10 >= $published_modules ) ? 5 : 10;
			?>

			<div id="forminator-free-publish-notice" class="forminator-rating-notice notice notice-info fui-wordpress-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

				<p style="color: #72777C; line-height: 22px;"><?php printf( __( 'Awesome! You\'ve published more than %d modules with Forminator. Hope you are enjoying it so far. We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.', Forminator::DOMAIN ), $milestone ); ?></p>

				<p>
					<a type="button" href="#" target="_blank" class="button button-primary button-large" data-prop="forminator_rating_success"><?php esc_html_e( 'Rate Forminator', Forminator::DOMAIN ); ?></a>

					<button type="button" class="button button-large" style="margin-left: 11px;" data-prop="<?php echo 10 > $published_modules ?  'forminator_publish_rating_later' : 'forminator_publish_rating_later_dismiss'; ?>"><?php esc_html_e( 'Maybe later', Forminator::DOMAIN ); ?></button>

					<a href="#" class="dismiss" style="margin-left: 11px; color: #555; line-height: 16px; font-weight: 500; text-decoration: none;" data-prop="forminator_rating_dismissed"><?php esc_html_e( 'No Thanks', Forminator::DOMAIN ); ?></a>
				</p>

            </div>

		<?php } else {

			$install_date = get_site_option( 'forminator_free_install_date', false );
			$days_later_dismiss = get_option( 'forminator_days_rating_later_dismiss', false );

			if ( $install_date && current_time( 'timestamp' ) > strtotime( '+7 days', $install_date ) && ! $publish_later && ! $publish_later_dismiss && ! $days_later_dismiss ) { ?>

                <div id="forminator-free-usage-notice"
                     class="forminator-rating-notice notice notice-info fui-wordpress-notice"
                     data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

					<p style="color: #72777C; line-height: 22px;"><?php esc_html_e( 'Excellent! You\'ve been using Forminator for a while now. Hope you are enjoying it so far. We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.', Forminator::DOMAIN ); ?></p>

                    <p>
                        <a type="button" href="#" target="_blank" class="button button-primary button-large"
                           data-prop="forminator_rating_success"><?php esc_html_e( 'Rate Forminator', Forminator::DOMAIN ); ?></a>

                        <a href="#" class="dismiss"
                           style="margin-left: 11px; color: #555; line-height: 16px; font-weight: 500; text-decoration: none;"
                           data-prop="forminator_days_rating_later_dismiss"><?php esc_html_e( 'Maybe later', Forminator::DOMAIN ); ?></a>
                    </p>

                </div>

				<?php
			}
		}

		?>

        <script type="text/javascript">
            jQuery('.forminator-rating-notice a, .forminator-rating-notice button').on('click', function (e) {
                e.preventDefault();

                var $notice = jQuery(e.currentTarget).closest('.forminator-rating-notice'),
                    prop = jQuery(this).data('prop'),
                    ajaxUrl = '<?php echo forminator_ajax_url(); ?>';

                if ('forminator_rating_success' === prop) {
                    window.open('https://wordpress.org/support/plugin/forminator/reviews/#new-post', '_blank');
                }

                jQuery.post(
                    ajaxUrl,
                    {
                        action: 'forminator_dismiss_notification',
                        prop: prop,
                        _ajax_nonce: $notice.data('nonce')
                    }
                ).always(function () {
                    $notice.hide();
                });
            });
        </script>

	<?php }

	/**
	 * Show action links on the plugin screen.
	 *
	 * @since 1.13
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return mixed
	 */
	public function add_plugin_action_links( $links ) {
		// Settings link.
		if ( forminator_get_admin_cap() ) {
			$action_links['dashboard'] = '<a href="' . admin_url( 'admin.php?page=forminator-settings' ) . '" aria-label="' . esc_attr( __( 'Go to Forminator Settings', Forminator::DOMAIN ) ) . '">' . esc_html__( 'Settings', Forminator::DOMAIN ) . '</a>';
		}
		// Documentation link.
		$action_links['docs'] = '<a href="' . forminator_get_link( 'docs', 'forminator_pluginlist_docs' ) . '" aria-label="' . esc_attr( __( 'Docs', Forminator::DOMAIN ) ) . '" target="_blank">' . esc_html__( 'Docs', Forminator::DOMAIN ) . '</a>';

		// WPMUDEV membership status.
		$membership = forminator_membership_status();

		// Upgrade or Renew links.
		if ( ! FORMINATOR_PRO || 'upgrade' === $membership ) {
			$action_links['upgrade'] = '<a href="' . forminator_get_link( 'plugin', 'forminator_pluginlist_upgrade' ) . '" aria-label="' . esc_attr( __( 'Upgrade to Forminator Pro', Forminator::DOMAIN ) ) . '" style="color: #8D00B1;" target="_blank">' . esc_html__( 'Upgrade', Forminator::DOMAIN ) . '</a>';
		} elseif ( 'expired' === $membership || 'free' === $membership ) {
			$action_links['renew'] = '<a href="' . forminator_get_link( 'plugin', 'forminator_pluginlist_renew' ) . '" aria-label="' . esc_attr( __( 'Renew Your Membership', Forminator::DOMAIN ) ) . '" style="color: #8D00B1;" target="_blank">' . esc_html__( 'Renew Membership', Forminator::DOMAIN ) . '</a>';
		}

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @since 1.13
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file Plugin Base file.
	 * @param array $plugin_data Plugin data.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file, $plugin_data ) {
		if ( FORMINATOR_PLUGIN_BASENAME === $file ) {
			// Show network meta links only when activated network wide.
			if ( is_network_admin() && ! forminator_is_networkwide() ) {
				return $links;
			}

			// Change AuthorURI link.
			if ( isset( $links[1] ) ){
				$author_uri = FORMINATOR_PRO ? 'https://premium.wpmudev.org/' : 'https://profiles.wordpress.org/wpmudev/';
				$author_uri = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$author_uri,
					__( 'WPMU DEV' )
				);
				$links[1] = sprintf( __( 'By %s' ), $author_uri );
			}

			if ( ! FORMINATOR_PRO ) {
				// Change AuthorURI link.
				if( isset( $links[2] ) && false === strpos( $links[2], 'target="_blank"' ) ) {
					if ( ! isset( $plugin_data['slug'] ) && $plugin_data['Name'] ) {
						$links[2] = sprintf(
							'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
							esc_url(
								network_admin_url(
									'plugin-install.php?tab=plugin-information&plugin=forminator' .
									'&TB_iframe=true&width=600&height=550'
								)
							),
							/* translators: %s: Plugin name. */
							esc_attr( sprintf( __( 'More information about %s' ), $plugin_data['Name'] ) ),
							esc_attr( $plugin_data['Name'] ),
							__( 'View details' )
						);
					} else {
						$links[2] = str_replace( 'href=', 'target="_blank" href=', $links[2] );
					}
				}
				$row_meta['rate']    = '<a href="' . esc_url( forminator_get_link( 'rate' ) ) . '" aria-label="' . esc_attr__( 'Rate Forminator', Forminator::DOMAIN ) . '" target="_blank">' . esc_html__( 'Rate Forminator', Forminator::DOMAIN ) . '</a>';
				$row_meta['support'] = '<a href="' . esc_url( forminator_get_link( 'support' ) ) . '" aria-label="' . esc_attr__( 'Support', Forminator::DOMAIN ) . '" target="_blank">' . esc_html__( 'Support', Forminator::DOMAIN ) . '</a>';
			} else {
				// Change 'Visit plugins' link to 'View details'.
				if ( isset( $links[2] ) && false !== strpos( $links[2], 'project/forminator' ) ) {
					$links[2] = sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( forminator_get_link( 'pro_link', '', 'project/forminator-pro/' ) ),
						__( 'View details' )
					);
				}
				$row_meta['support'] = '<a href="' . esc_url( forminator_get_link( 'support' ) ) . '" aria-label="' . esc_attr__( 'Premium Support', Forminator::DOMAIN ) . '" target="_blank">' . esc_html__( 'Premium Support', Forminator::DOMAIN ) . '</a>';
			}
			$row_meta['roadmap'] = '<a href="' . esc_url( forminator_get_link( 'roadmap' ) ) . '" aria-label="' . esc_attr__( 'Roadmap', Forminator::DOMAIN ) . '" target="_blank">' . esc_html__( 'Roadmap', Forminator::DOMAIN ) . '</a>';

			return array_merge( $links, $row_meta );
		}

		return $links;
	}
}
