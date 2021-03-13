<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Two_Fa extends Setting {
	public $table = 'wd_2auth_settings';

	/**
	 * Feature status
	 *
	 * @defender_property
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * @defender_property
	 * @var bool
	 */
	public $lost_phone = true;

	/**
	 * @defender_property
	 * @var bool
	 */
	public $force_auth = false;

	/**
	 * @var string
	 * @defender_property
	 */
	public $force_auth_mess = '';

	/**
	 * @var array
	 * @defender_property
	 */
	public $user_roles = [];

	/**
	 * @var array
	 * @defender_property
	 */
	public $force_auth_roles = [];

	/**
	 * @var bool
	 * @defender_property
	 */
	public $custom_graphic = false;
	/**
	 * @var string
	 * @defender_property
	 */
	public $custom_graphic_url = '';

	/**
	 * @var array
	 */
	public $is_conflict = array();

	/**
	 * @var string
	 * @defender_property
	 */
	public $email_subject = '';

	/**
	 * @var string
	 * @defender_property
	 */
	public $email_sender = '';

	/**
	 * @defender_property
	 * @var string
	 */
	public $email_body = '';

	/**
	 * @var string
	 * @defender_property
	 */
	public $app_title = '';

	/**
	 * @var string
	 */
	public $app_text = '';

	protected function before_load() {
		//default we will load all rules
		if ( function_exists( 'get_editable_roles' ) ) {
			//we only need this inside admin, no need to load the user.php everywhere
			$this->user_roles = array_keys( get_editable_roles() );
		}
		//define some other defaults
		$this->custom_graphic_url = defender_asset_url( '/assets/img/2factor-disabled.svg' );
		$this->email_subject      = __( 'Your OTP code', 'wpdef' );
		$this->email_sender       = 'admin';
		$this->email_body         = 'Hi {{display_name}},

Your temporary login passcode is <strong>{{passcode}}</strong>.

Copy and paste the passcode into the input field on the login screen to complete logging in.

Regards,
Administrator';
		$this->app_title          = get_bloginfo( 'name' );
		$this->force_auth_mess    = __( 'You are required to setup two-factor authentication to use this site.', 'wpdef' );
		$this->app_text           = __( 'Open your authentication app and type the 6 digit passcode to log in to your account.', 'wpdef' );
	}

	/**
	 * @param $plugin
	 *
	 * @return bool|int
	 */
	public function is_conflict( $plugin ) {
		if ( in_array( $plugin, $this->is_conflict ) ) {
			return true;
		} elseif ( in_array( '!' . $plugin, $this->is_conflict ) ) {
			return false;
		}

		return 0;
	}

	/**
	 * @param $plugin
	 */
	public function mark_as_conflict( $plugin ) {
		if ( ! in_array( $plugin, $this->is_conflict ) ) {
			$this->is_conflict[] = $plugin;
			$this->save();
		}
	}

	/**
	 * @param $plugin
	 */
	public function mark_as_un_conflict( $plugin ) {
		if ( ( $i = array_search( $plugin, $this->is_conflict ) ) !== false ) {
			unset( $this->is_conflict[ $i ] );
		}
		if ( ! in_array( '!' . $plugin, $this->is_conflict ) ) {
			$this->is_conflict [] = '!' . $plugin;
		}
		$this->save();
	}

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'enabled'            => __( 'Mask Login Area', 'wpdef' ),
			'user_roles'         => __( 'Enabled user roles', 'wpdef' ),
			'force_auth_roles'   => __( 'Force users to log in with two-factor authentication', 'wpdef' ),
			'lost_phone'         => __( 'Allow lost phone recovery option', 'wpdef' ),
			'email_subject'      => __( 'Subject', 'wpdef' ),
			'email_body'         => __( 'Body', 'wpdef' ),
			'email_sender'       => __( 'Sender', 'wpdef' ),
			'force_auth'         => __( 'Force 2FA on user roles', 'wpdef' ),
			'force_auth_mess'    => __( 'Force 2FA login warning message', 'wpdef' ),
			'custom_graphic'     => __( 'Use custom login branding graphic', 'wpdef' ),
			'custom_graphic_url' => __( 'Custom Graphic', 'wpdef' ),
			'app_title'          => __( 'App Title', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
