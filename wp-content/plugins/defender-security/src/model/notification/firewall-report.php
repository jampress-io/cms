<?php

namespace WP_Defender\Model\Notification;

use WP_Defender\Controller\Firewall;
use WP_Defender\Model\Lockout_Log;

/**
 * Class Firewall_Report
 * @package WP_Defender\Model\Notification
 */
class Firewall_Report extends \WP_Defender\Model\Notification {
	protected $table = 'wd_lockout_report';

	public function before_load() {
		$default = array(
			'slug'                 => 'firewall-report',
			'title'                => __( 'Firewall - Reporting', 'wpdef' ),
			'status'               => \WP_Defender\Model\Notification::STATUS_DISABLED,
			'description'          => __( 'Configure Defender to automatically email you a lockout report for this website.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
			'out_house_recipients' => array(),
			'type'                 => 'report',
			'dry_run'              => false,
			'frequency'            => 'weekly',
			'day'                  => 'sunday',
			'day_n'                => '1',
			'time'                 => '4:00',
			'configs'              => array(),
		);
		$this->import( $default );
	}

	public function send() {
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( $recipient['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'] );
		}
		foreach ( $this->out_house_recipients as $recipient ) {
			if ( $recipient['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'] );
		}
		$this->last_sent     = $this->est_timestamp;
		$this->est_timestamp = $this->get_next_run()->getTimestamp();
		$this->save();
	}

	private function send_to_user( $name, $email ) {
		$subject = sprintf( __( "Defender Lockouts Report for %s", 'wpdef' ), network_site_url() );
		if ( $this->frequency === 'daily' ) {
			$count       = Lockout_Log::count_lockout_in_24_hours();
			$nf_count    = Lockout_Log::count( strtotime( '-24 hours' ), time(), [
				Lockout_Log::LOCKOUT_404
			] );
			$login_count = Lockout_Log::count( strtotime( '-24 hours' ), time(), [
				Lockout_Log::AUTH_LOCK
			] );
			$time_unit = __( "In the past 24 hours", 'wpdef' );
		} elseif ( $this->frequency === 'weekly' ) {
			$count       = Lockout_Log::count_lockout_in_7_days();
			$time_unit   = __( "In the past week", 'wpdef' );
			$nf_count    = Lockout_Log::count( strtotime( '-7 days' ), time(), [
				Lockout_Log::LOCKOUT_404
			] );
			$login_count = Lockout_Log::count( strtotime( '-7 days' ), time(), [
				Lockout_Log::AUTH_LOCK
			] );
		} else {
			$count       = Lockout_Log::count_lockout_in_30_days();
			$time_unit   = __( "In the month", 'wpdef' );
			$nf_count    = Lockout_Log::count( strtotime( '-30 days' ), time(), [
				Lockout_Log::LOCKOUT_404
			] );
			$login_count = Lockout_Log::count( strtotime( '-30 days' ), time(), [
				Lockout_Log::AUTH_LOCK
			] );
		}
		$content        = wd_di()->get( Firewall::class )->render_partial( 'email/firewall-report', [
			'name'          => $name,
			'count_total'   => $count,
			'last_lockout'  => Lockout_Log::get_last_lockout_date(),
			'time_unit'     => $time_unit,
			'lockout_404'   => $nf_count,
			'lockout_login' => $login_count
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
			'report'             => __( 'Firewall - Reporting', 'wpdef' ),
			'day'                => __( 'Day of', 'wpdef' ),
			'day_n'              => __( 'Day of', 'wpdef' ),
			'report_time'        => __( 'Time of day', 'wpdef' ),
			'report_frequency'   => __( 'Frequency', 'wpdef' ),
			'report_subscribers' => __( 'Recipients', 'wpdef' ),
			'dry_run'            => '',
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
