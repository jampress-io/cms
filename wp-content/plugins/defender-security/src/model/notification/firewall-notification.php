<?php

namespace WP_Defender\Model\Notification;

use WP_Defender\Controller\Firewall;
use WP_Defender\Model\Email_Track;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Traits\IO;

/**
 * Class Firewall_Notification
 * @package WP_Defender\Model\Notification
 */
class Firewall_Notification extends \WP_Defender\Model\Notification {
	use IO;

	public $table = 'wd_malware_firewall_notification';

	public function before_load() {
		$default = array(
			'title'                => __( 'Firewall - Notification', 'wpdef' ),
			'slug'                 => 'firewall-notification',
			'status'               => self::STATUS_DISABLED,
			'description'          => __( 'Get email when a user or IP is locked out for trying to access your login area.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
			'out_house_recipients' => array(),
			'type'                 => 'notification',
			'dry_run'              => false,
			'configs'              => array(
				'login_lockout' => false,
				'nf_lockout'    => false,
				'limit'         => false,
				'threshold'     => 3,
				'cool_off'      => 24,
			),
		);
		$this->import( $default );
	}

	/**
	 * @return bool
	 */
	public function maybe_send() {
		if ( self::STATUS_ACTIVE !== $this->status ) {
			return false;
		}
		if ( $this->configs['login_lockout'] === true || $this->configs['nf_lockout'] === true ) {
			return true;
		}

		return false;
	}

	public function send( Lockout_Log $model ) {
		if ( filter_var( $this->configs['login_lockout'], FILTER_VALIDATE_BOOLEAN ) === true && $model->type === Lockout_Log::AUTH_LOCK ) {
			$template = 'login-lockout';
		} else {
			$template = 'lockout-404';
		}

		foreach ( $this->in_house_recipients as $user ) {
			if ( $user['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template );
		}

		foreach ( $this->out_house_recipients as $user ) {
			if ( $user['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template );
		}
	}

	/**
	 * @param $email
	 * @param $name
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	private function send_to_user( $email, $name, $model, $template ) {
		//check if this meet the threshold
		if ( $this->configs['limit'] === true ) {
			$count = Email_Track::count( $this->slug, $email, strtotime( '-' . $this->configs['cool_off'] . ' hours' ), time() );
			if ( $count >= $this->configs['threshold'] ) {
				//no send
				return;
			}
		}
		if ( $template === 'login-lockout' ) {
			$subject  = sprintf( __( 'Login lockout alert for %s', 'wpdef' ), network_site_url() );
			$settings = wd_di()->get( Login_Lockout::class );
			$text     = __( 'We\'ve just locked out the host <strong>%s</strong> from %s due to more than <strong>%s</strong> failed login attempts. %s', 'wpdef' );
			if ( $settings->lockout_type === 'permanent' ) {
				$text = sprintf( $text, $model->ip, network_site_url(), $settings->attempt, __( 'They have been banned permanently.', 'wpdef' ) );
			} else {
				$string = sprintf( __( 'They have been locked out for <strong>%s %s.</strong>', 'wpdef' ), $settings->duration, $settings->duration_unit );
				$text   = sprintf( $text, $model->ip, network_site_url(), $settings->attempt, $string );
			}
		} else {
			$subject  = sprintf( __( '404 lockout alert for %s', 'wpdef' ), network_site_url() );
			$settings = wd_di()->get( Notfound_Lockout::class );
			if ( $settings->lockout_type === 'permanent' ) {
				$text = sprintf( __( "We've just locked out the host <strong>%s</strong> from %s due to more than <strong>%s</strong> 404 requests for the file <strong>%s</strong>. They have been banned permanently.", 'wpdef' ),
					$model->ip, network_site_url(), $settings->attempt, $model->tried, $settings->duration, $settings->duration_unit
				);
			} else {
				$text = sprintf( __( "We've just locked out the host <strong>%s</strong> from %s due to more than <strong>%s</strong> 404 requests for the file <strong>%s</strong>. They have been locked out for <strong>%s %s.</strong>", 'wpdef' ),
					$model->ip, network_site_url(), $settings->attempt, $model->tried, $settings->duration, $settings->duration_unit
				);
			}
		}
		$logs_url       = network_admin_url( "admin.php?page=wdf-ip-lockout&view=logs" );
		$logs_url       = apply_filters( 'report_email_logs_link', $logs_url, $email );
		$content        = wd_di()->get( Firewall::class )->render_partial( 'email/' . $template, [
			'name'     => $name,
			'text'     => $text,
			'logs_url' => $logs_url,
		], false );
		$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
		$no_reply_email = apply_filters( 'wd_lockout_noreply_email', $no_reply_email );
		$headers        = array(
			'From: Defender <' . $no_reply_email . '>',
			'Content-Type: text/html; charset=UTF-8'
		);

		$ret = wp_mail( $email, $subject, $content, $headers );
		if ( $ret ) {
			$this->save_log( $email );
		}
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
			'notification'               => __( 'Firewall - Notification', 'wpdef' ),
			'login_lockout_notification' => __( 'Login Protection Lockout', 'wpdef' ),
			'ip_lockout_notification'    => __( '404 Detection Lockout', 'wpdef' ),
			'notification_subscribers'   => __( 'Recipients', 'wpdef' ),
			'cooldown_enabled'           => __( 'Limit email notifications for repeat lockouts', 'wpdef' ),
			'cooldown_number_lockout'    => __( 'Repeat Lockouts Threshold', 'wpdef' ),
			'cooldown_period'            => __( 'Repeat Lockouts Period', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
