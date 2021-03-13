<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;

class Notfound_Lockout extends Component {
	const SCENARIO_ERROR_404 = 'error_404', SCENARIO_ERROR_404_IGNORE = 'error_404_ignore', SCENARIO_LOCKOUT_404 = '404_lockout';
	/**
	 * Use for cache
	 *
	 * @var \WP_Defender\Model\Setting\Notfound_Lockout
	 */
	public $model;

	public function __construct() {
		$this->model = wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class );
	}

	/**
	 * Queue hooks when this class init
	 */
	public function add_hooks() {
		add_action( 'template_redirect', array( &$this, 'process_404_detect' ) );
	}

	/**
	 * Check if useragent is looks like from google
	 *
	 * @param  string  $user_agent
	 *
	 * @return bool
	 */
	private function is_google_ua( $user_agent = '' ) {
		if ( empty( $user_agent ) ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			$user_agent = mb_strtolower( $user_agent, 'UTF-8' );
		} else {
			$user_agent = strtolower( $user_agent );
		}

		if ( false !== stristr( $user_agent, 'googlebot' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if IP is from google, base on https://support.google.com/webmasters/answer/80553?hl=en
	 *
	 * @param $ip
	 *
	 * @return bool
	 */
	private function is_google_ip( $ip ) {
		$hostname = gethostbyaddr( $ip );
		//check if this hostname has googlebot or google.com
		if ( preg_match( '/\.googlebot|google\.com$/i', $hostname ) ) {
			$hosts = gethostbynamel( $hostname );

			if ( ! is_array( $hosts ) ) {
				return false;
			}

			//check if this match the oringal ip
			foreach ( $hosts as $host ) {
				if ( $ip === $host ) {
					return true;
				}
			}
		}

		return false;
	}

	private function is_bing_ua( $user_agent = '' ) {
		if ( empty( $user_agent ) ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			$user_agent = mb_strtolower( $user_agent, 'UTF-8' );
		} else {
			$user_agent = strtolower( $user_agent );
		}
		//MSN Bot Useragent https://www.bing.com/webmaster/help/which-crawlers-does-bing-use-8c184ec0
		$msn_ua = 'Bingbot|MSNBot|MSNBot-Media|AdIdxBot|BingPreview';

		if ( preg_match( '/' . $msn_ua . '/i', $user_agent ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if IP is from Bing, base on https://www.bing.com/webmaster/help/how-to-verify-bingbot-3905dc26
	 *
	 * @param $ip
	 *
	 * @return bool
	 */
	private function is_bing_ip( $ip ) {
		$hostname = gethostbyaddr( $ip );
		if ( preg_match( '/\.msnbot|msn\.com$/i', $hostname ) ) {
			$hosts = gethostbynamel( $hostname );

			if ( ! is_array( $hosts ) ) {
				return false;
			}

			//check if this match the oringal ip
			foreach ( $hosts as $host ) {
				if ( $ip === $host ) {
					return true;
				}
			}
		}

		return false;
	}

	public function process_404_detect() {
		if ( ! is_404() ) {
			return;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			//only track subscriber
			return;
		}

		$ip = $this->get_user_ip();
		//now check if this from google
		if ( $this->is_google_ua() && $this->is_google_ip( $ip ) ) {
			return;
		}

		//or bing
		if ( $this->is_bing_ua() && $this->is_bing_ip( $ip ) ) {
			return;
		}

		if ( false === $this->model->detect_logged && is_user_logged_in() ) {
			return;
		}

		$uri = $_SERVER['REQUEST_URI'];
		//strip encode
		$uri   = urldecode( $uri );
		$model = Lockout_Ip::get( $ip );
		$model = $this->record_fail_attempt( $ip, $model );

		$ext = pathinfo( $uri, PATHINFO_EXTENSION );
		$ext = trim( $ext );
		//downfall from match URL to extension
		foreach ( $this->model->get_lockout_list( 'allowlist' ) as $pattern ) {
			$pattern = preg_quote( $pattern, '/' );
			if ( preg_match( '/' . $pattern . '$/i', $uri ) ) {
				//whitelisted, just return
				return;
			}
		}

		foreach ( $this->model->get_lockout_list( 'blocklist' ) as $pattern ) {
			$pattern = preg_quote( $pattern, '/' );
			if ( preg_match( '/' . $pattern . '$/i', $uri ) ) {
				$this->lock( $model, 'blacklist', $uri );
				$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );

				return;
			}
		}

		if ( strlen( $ext ) ) {
			//if ext not null
			foreach ( $this->model->get_lockout_list( 'allowlist' ) as $whitelist_ext ) {
				if ( str_replace( '.', '', strtolower( $whitelist_ext ) ) === $ext ) {
					//ext is whitelist, log and return
					$this->log_event( $ip, $uri, self::SCENARIO_ERROR_404_IGNORE );

					return;
				}
			}

			foreach ( $this->model->get_lockout_list( 'blocklist' ) as $blacklist_ext ) {
				if ( str_replace( '.', '', strtolower( $blacklist_ext ) ) === $ext ) {
					//block it
					$this->lock( $model, 'blacklist', $uri );
					$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );

					return;
				}
			}
		}

		$this->log_event( $ip, $uri, self::SCENARIO_ERROR_404 );

		//Count the attempt
		$window = strtotime( '- ' . $this->model->timeframe . ' seconds' );

		//we will get the latest till oldest, limit by attempt
		if ( ! is_array( $model->meta['nf'] ) ) {
			$model->meta['nf'] = [];
		}

		$checks = array_slice( $model->meta['nf'], $this->model->attempt * - 1 );

		if ( count( $checks ) < $this->model->attempt ) {
			//do nothing
			return;
		}
		//if the last time is larger
		$check = min( $checks );
		if ( $check >= $window ) {
			//lock it
			$this->lock( $model, 'normal', $uri );
			$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );
		}
	}

	/**
	 * @param  Lockout_Ip  $model
	 * @param $scenario
	 * @param $uri
	 */
	private function lock( Lockout_Ip $model, $scenario = 'normal', $uri = '' ) {
		if ( 'permanent' === $this->model->lockout_type ) {
			$scenario = 'blacklist';
		}
		$model->status        = Lockout_Ip::STATUS_BLOCKED;
		$model->lock_time_404 = time();
		if ( 'blacklist' === $scenario ) {
			$model->release_time = strtotime( '+5 years' );
		} else {
			$model->release_time = strtotime( '+ ' . $this->model->duration . ' ' . $this->model->duration_unit );
		}
		$model->lockout_message = $this->model->lockout_message;
		$model->save();

		if ( 'blacklist' === $scenario ) {
			do_action( 'wd_blacklist_this_ip', $model->ip );
		}

		do_action( 'wd_404_lockout', $model, $scenario );
	}

	/**
	 * Store the fail attempt of current IP
	 *
	 * @param $ip
	 * @param  Lockout_Ip  $model
	 *
	 * @return Lockout_Ip
	 */
	protected function record_fail_attempt( $ip, $model ) {
		// Fix warning with a non-numeric value
		if ( '' === $model->attempt_404 ) {
			$model->attempt_404 = 1;
		} else {
			$model->attempt_404 += 1;
		}
		$model->ip = $ip;

		// cache the time here, so it consume less memory than query the logs.
		if (
			! isset( $model->meta['nf'] ) ||
			( isset( $model->meta['nf'] ) && ! is_array( $model->meta['nf'] ) )
		) {
			$model->meta['nf'] = [];
		}

		$model->meta['nf'][] = time();
		$model->save();

		return $model;
	}

	/**
	 * Log the event into db, we will use the data in logs page later
	 *
	 * @param $ip
	 * @param $uri
	 * @param $scenario
	 */
	public function log_event( $ip, $uri, $scenario ) {
		$model             = new Lockout_Log();
		$model->ip         = $ip;
		$model->user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$model->date       = time();
		$model->tried      = $uri;
		$model->blog_id    = get_current_blog_id();

		switch ( $scenario ) {
			case self::SCENARIO_ERROR_404:
				$model->type = Lockout_Log::ERROR_404;
				$model->log  = sprintf( __( "Request for file %s which doesn't exist", 'wpdef' ), $uri );
				break;
			case self::SCENARIO_ERROR_404_IGNORE:
				$model->type = Lockout_Log::ERROR_404_IGNORE;
				$model->log  = sprintf( __( "Request for file %s which doesn't exist", 'wpdef' ), $uri );
				break;
			case self::SCENARIO_LOCKOUT_404:
			default:
				$model->type = Lockout_Log::LOCKOUT_404;
				$model->log  = sprintf( __( 'Lockout occurred:  Too many 404 requests for %s', 'wpdef' ), $uri );
				break;
		}
		if ( $model->type === Lockout_Log::LOCKOUT_404 ) {
			do_action( 'defender_notify', 'firewall-notification', $model );
		}
		$model->save();
	}
}
