<?php

namespace Calotes\Model;

use Calotes\Base\Model;
use WP_Defender\Component\Error_Code;

class Setting extends Model {
	protected $exclude = array( 'table' );

	public function __construct() {
		// we parse the annotations here, and only one
		$this->parse_annotations();
		$this->before_load();
		$this->load();
		$this->after_load();
		$this->sanitize();
	}

	public function save() {
		$data = $this->prepare_data();
		$data = json_encode( $data );
		$ret  = update_site_option( $this->table, $data );
		if ( $ret === false ) {
			$this->internal_logging[] = sprintf( 'Saving fail on %s with data %s', $this->table, json_encode( $data ) );
		}
	}

	/**
	 * Load data
	 *
	 * @throws \ReflectionException
	 */
	public function load() {
		$time = microtime( true );
		if ( empty( $this->table ) ) {
			throw new \Exception( 'Table must be defined before using' );
		}

		$data = get_site_option( $this->table );
		if ( $data === false ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			$data = json_decode( $data, true );
		}

		if ( ! is_array( $data ) ) {
			return;
		}

		$data = $this->prepare_data( $data );
		$this->import( $data );
		$this->log( sprintf( 'loaded %s - %s', $this->table, microtime( true ) - $time ) );
	}

	public function delete() {
		delete_site_option( $this->table );
	}

	/**
	 * Empty function
	 */
	protected function after_load() {
		return 'For child class to implement';
	}

	/**
	 * @return string
	 */
	protected function before_load() {
		return 'For child class to implement';
	}
}
