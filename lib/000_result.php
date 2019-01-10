<?php

/**
 * Class Orbisius_Support_Tickets_Result
 */
class Orbisius_Support_Tickets_Result {
	// I put them as public even though I need them private.
	// reason: private fields don't appear in a JSON output
	private $msg = '';
	private $code = '';
	private $status = 0;
	private $data = array();

	/**
	 * Populates the internal variables from contr params.
	 * @param str/array $json
	 */
	public function __construct( $json = '' ) {
		if ( ! empty( $json ) ) {
			if ( is_scalar( $json ) ) {
				if ( is_bool($json) || is_numeric($json)) {
					$this->status = abs((int) $json);
				} elseif ( is_string( $json ) ) {
					$json = json_decode( $json, true );
				}
			} elseif ( is_object( $json ) ) {
				$json = (array) $json;
			}

			if ( is_array( $json ) ) {
				foreach ( $json as $key => $value ) {
					$this->$key = $value;
				}
			}
		}
	}

	public function status( $new_status = null ) {
		if ( ! is_null( $new_status ) ) {
			$this->status = $new_status;
		}

		return $this->status;
	}

	/**
	 * returns or sets a message
	 * @param str $msg
	 * @return str
	 */
	public function code($code = '') {
		if (!empty($code)) {
			$this->code = $code;
		}

		return $this->code;
	}

	/**
	 * Alias to msg
	 * @param str $new_message
	 * @return str
	 */
	public function message( $new_message = null ) {
		return $this->msg($new_message);
	}

	/**
	 * returns or sets a message
	 * @param str $msg
	 * @return str
	 */
	public function msg($msg = '') {
		if (!empty($msg)) {
			$this->msg = trim( $msg );
		}

		return $this->msg;
	}

	public function success() {
		return !empty($this->status);
	}

	public function isSuccess() {
		return !empty($this->status);
	}

	public function error() {
		return empty($this->status);
	}

	public function is_error() {
		return empty($this->status);
	}

	const OVERRIDE_FLAG = 2;
	const DONT_OVERRIDE_FLAG = 4;

	/**
	 * Extracts data from the params and populates the internal data array.
	 * It's useful when storing data from another request
	 *
	 * @param str/array/obj $json
	 * @param int $flag
	 */
	public function populate_data($json, $flag = self::DONT_OVERRIDE_FLAG ) {
		if ( is_string( $json ) ) {
			$json = json_decode( $json, true );
		} else if ( is_object( $json ) ) {
			$json = (array) $json;
		}

		if ( is_array( $json ) ) {
			foreach ( $json as $key => $value ) {
				if ( isset( $this->data[$key] ) && ( $flag & self::DONT_OVERRIDE_FLAG ) ) {
					continue;
				}

				$this->data[$key] = $value;
			}
		}
	}

	/**
	 * Data container.
	 *
	 * @param str $key
	 * @param str $val
	 * @return mixed
	 */
	public function data($key = '', $val = null) {
		if (is_array($key)) { // when we pass an array -> override all
			$this->data = empty($this->data) ? $key : array_merge($this->data, $key);
		} elseif (!empty($key)) {
			if (!is_null($val)) { // add/update a value
				$this->data[$key] = $val;
			}

			return isset($this->data[$key]) ? $this->data[$key] : null;
		} else { // nothing return all data
			$val = $this->data;
		}

		return $val;
	}
}

