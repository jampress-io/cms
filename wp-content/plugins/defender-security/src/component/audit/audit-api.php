<?php

namespace WP_Defender\Component\Audit;

use Calotes\Base\Component;
use WP_Defender\Component\Error_Code;
use WP_Defender\Traits\Formats;

/**
 * Class Audit_Api
 * @package WP_Defender\Component\Audit
 * @deprecated
 */
class Audit_Api extends Component {
	use Formats;

	const ACTION_ADDED = 'added',
		ACTION_UPDATED = 'updated',
		ACTION_DELETED = 'deleted',
		ACTION_TRASHED = 'trashed',
		ACTION_RESTORED = 'restored';

	public static $end_point = 'audit.wpmudev.org';

	public static function get_event_type() {
		return \Calotes\Helper\Array_Cache::get( 'event_types' );
	}

	/**
	 * @param $slug
	 *
	 * @return mixed
	 */
	public static function get_action_text( $slug ) {
		$dic = \Calotes\Helper\Array_Cache::get( 'dictionary', array() );

		return isset( $dic[ $slug ] ) ? $dic[ $slug ] : $slug;
	}

	/**
	 * We get all the hooks from internal component and add it to wp hook system on wp_load time
	 */
	public static function setup_events() {
		//we only queue for
		if ( defined( 'DOING_CRON' ) && constant( 'DOING_CRON' ) == true ) {
			//this is cron, we only queue the core audit to catch auto update
			$events_class = array(
				//Todo: new Core_Audit()
			);
		} else {
			$events_class = array(
				new Comment_Audit(),
				//Todo: new Core_Audit(),
				//Todo: new Media_Audit(),
				//Todo: new Options_Audit(),
				//Todo: new Post_Audit(),
				//Todo: new Users_Audit()
			);
		}

		//we will build up the dictionary here
		$dictionary  = self::dictionary();
		$event_types = array();

		foreach ( $events_class as $class ) {
			$hooks      = $class->get_hooks();
			$dictionary = array_merge( $class->dictionary(), $dictionary );
			foreach ( $hooks as $key => $hook ) {
				$func = function () use ( $key, $hook, $class ) {
					//this is argurements of the hook
					$args = func_get_args();
					//this is hook data, defined in each events class
					$class->build_log_data( $key, $args, $hook );
				};
				add_action( $key, $func, 11, count( $hook['args'] ) );
				$event_types[] = $hook['event_type'];
			}
		}

		\Calotes\Helper\Array_Cache::set( 'event_types', array_unique( $event_types ) );
		\Calotes\Helper\Array_Cache::set( 'dictionary', $dictionary );
	}

	/**
	 * Queue event data prepare for submitting
	 *
	 * @param $data
	 */
	public static function queue_events_data( $data ) {
		$events   = \Calotes\Helper\Array_Cache::get( 'events_queue', array() );
		$events[] = $data;
		\Calotes\Helper\Array_Cache::set( 'events_queue', $events );
	}
}
