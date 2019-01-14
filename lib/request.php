<?php

/**
 */
class Orbisius_Support_Tickets_Request {
	protected $data = null;
	const INT = 2;
	const FLOAT = 4;
	const ESC_ATTR = 8;
	const JS_ESC_ATTR = 16;
	const EMPTY_STR = 32; // when int/float nubmers are 0 make it an empty str
	const STRIP_SOME_TAGS = 64;
	const STRIP_ALL_TAGS = 128;
	const SKIP_STRIP_ALL_TAGS = 256;
	const DONT_SANITIZE = 256;

	/**
	 * @var array
	 * @see https://codex.wordpress.org/Function_Reference/wp_kses
	 */
	private $allowed_permissive_html_tags = array(
		'a' => array(
			'href' => array(),
			'title' => array(),
			'target' => array(),
			'class' => array(),
		),
		'br' => array(),
		'em' => array(),
		'p' => array(),
		'div' => array(),
		'hr' => array(),
		'i' => array(),
		'strong' => array(),
		'style' => array(),
		'span' => array(),
	);

	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 *
	 * @staticvar type $instance
	 * @return Orbisius_Support_Tickets_Request
	 */
	public static function getInstance() {
		static $instance = null;

		// This will make the calling class to be instantiated.
		// no need each sub class to define this method.
		if (is_null($instance)) {
			$instance = new static();
			$instance->req_init();
		}

		return $instance;
	}

	public function init() {

	}

	/**
	 * if a key exists in the request
	 * @param $key
	 * @return bool
	 */
	public function has( $key) {
		return isset($this->data[$key]);
	}

	private $raw_data = array();

	/**
	 * Gets a variable from the request and sanitizes it.
	 * @param str $key
	 * @return mixed
	 */
	public function get( $key, $default_val = '', $force_type = self::STRIP_ALL_TAGS ) {
		$key = $this->trim($key);
		$val = isset($this->data[$key]) ? $this->data[$key] : $default_val;

		if ( $force_type & self::INT ) {
			$val = intval($val);

			if ( $val == 0 && $force_type & self::EMPTY_STR ) {
				$val = "";
			}
		}

		if ( $force_type & self::FLOAT ) {
			$val = floatval($val);

			if ( $val == 0 && $force_type & self::EMPTY_STR ) {
				$val = "";
			}
		}

		if ( $force_type & self::ESC_ATTR ) {
			$val = esc_attr($val);
		}

		if ( $force_type & self::JS_ESC_ATTR ) {
			$val = esc_js($val);
		}

		if ( $force_type & self::STRIP_SOME_TAGS ) {
			$val = $this->sanitize( $val, $force_type );
		}

		// Sanitizing a var
		if ( $force_type & self::STRIP_ALL_TAGS ) {
			$val = $this->sanitize( $val, $force_type );
		}

		$val = $this->trim($val);

		// Some data is passed via the data[ffid] array
		// we'll try harder to find params
		if (empty($val)) {
			if (!empty($this->data[ $key ])) {
				$val = $this->data[ $key ];
			} elseif (!empty($this->data[ $key . 'x'] )) { // ffidx
				$val = $this->data[ $key ] . 'x';
			} elseif (!empty($this->data['data'][$key])) { // data['ffid']
				$val = $this->data['data'][ $key ];
			} elseif (!empty($this->data['data'][$key . 'x'])) { // data['ffidx']
				$val = $this->data['data'][ $key . 'x' ];
			}
		}

		return $val;
	}

	/**
	 *
	 * @param string $key
	 * @param string $default_val
	 * @return mixed|string
	 */
	public function getRaw( $key, $default_val = '' ) {
		$val = isset($this->raw_data[$key]) ? $this->raw_data[$key] : $default_val;
		return $val;
	}

	/**
	 * @param $val
	 * @param int $flag
	 *
	 * @return mixed|string
	 * @throws Exception
	 */
	public function sanitize( $val, $flag = self::STRIP_ALL_TAGS ) {
		if ( is_scalar( $val ) ) {
			if ( $flag & self::STRIP_ALL_TAGS ) {
				$val = wp_kses($val, array());
			}

			if ( $flag & self::STRIP_SOME_TAGS ) {
				$val = wp_kses($val, $this->allowed_permissive_html_tags);
			}

			$val = trim( $val );
		} elseif ( is_array( $val ) ) {
			foreach ( $val as $key => & $loop_val ) {
				$loop_val = $this->sanitize( $loop_val, $flag );
			}
		} else {
			throw new Exception("Wrong type." );
		}

		return $val;
	}

	/**
	 * @param $val
	 * @param int $flag
	 *
	 * @return mixed|string
	 * @throws Exception
	 */
	public function trim( $val) {
		if ( is_scalar( $val ) ) {
			$val = trim( $val );
		} elseif ( is_array( $val ) ) {
			foreach ( $val as $key => & $loop_val ) {
				$loop_val = $this->trim( $loop_val );
			}
		} elseif (is_null($val)) {
			// should be ok
		} else {
			throw new Exception("Wrong type. Type: " . gettype($val));
		}

		return $val;
	}

	/**
	 * get and esc
	 * @param str $key
	 * @param int $force_type
	 * @return str
	 */
	public function gete( $key, $force_type = 1 ) {
		$v = $this->get( $key, $force_type );
		$v = esc_attr( $v );
		return $v;
	}

	/**
	 * WP puts slashes in the values so we need to remove them.
	 * @param array $data
	 */
	public function req_init( $data = null ) {
		// see https://codex.wordpress.org/Function_Reference/stripslashes_deep
		if ( is_null( $this->data ) ) {
			$data = empty( $data ) ? $_REQUEST : $data;
			$this->raw_data = $data;
			$data = stripslashes_deep( $data );
			$data = $this->sanitize_data( $data );
			$this->data = $data;
		}
	}

	/**
	 *
	 * @param str/array $data
	 * @return str/array
	 * @throws Exception
	 */
	public function sanitize_data( $data = null ) {
		if ( is_scalar( $data ) ) {
			$data = wp_kses_data( $data );
			$data = trim( $data );
		} elseif ( is_array( $data ) ) {
			$data = array_map( array( $this, 'sanitize_data' ), $data );
		} else {
			throw new Exception( "Invalid data type passed for sanitization" );
		}

		return $data;
	}

	/**
	 * Goes through the list of items and checks against the validators
	 * @param array/void $params
	 * @return df_crm_result
	 */
	public function validate($data, $all_validators) {
		$res = new df_crm_result();
		$failed_fields = array();

		foreach ($all_validators as $field => $field_validators) {
			if (!isset($data[$field])) {
				continue;
			}

			$val = $data[$field];

			foreach ($field_validators as $validator) {
				$label = $validator->get_label() ? $validator->get_label() : $field;
				$label = ucfirst($label);

				if ( ! $validator->validate( $val ) ) {
					$failed_fields[ $label ]   = empty( $failed_fields[ $field ] ) ? array() : $failed_fields[ $label ];
					$failed_fields[ $label ][] = $validator->get_error();
				}
			}
		}

		if (empty($failed_fields)) {
			$res->status( 1 );
			return $res;
		}

		$res->data('failed_fields', $failed_fields);
		$error_lines = array();

		foreach ($failed_fields as $field => $errors) {
			$error_lines[] = $field . ': ' . df_crm_string_util::asList($errors);
		}

		$msg = 'Form validation failed: <br/>' . join("<br/>\n", $error_lines);
		//$msg = 'Form validation failed: <br/>' . df_crm_string_util::asList($error_lines);
		$res->msg($msg);

		return $res;
	}

	/**
	 *
	 * @param array/void $params
	 * @return bool
	 */
	public function is_post($params = array()) {
		return !empty($_POST);
	}

	/**
	 * @param $url
	 * @param array $params
	 */
	public function call($url, $params = array(), $extra = array()) {
		$res = new df_crm_result();

		$req_params = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'sslverify' => 0,
			'blocking' => true,
			'headers' => array(),
			'body' => $params,
			'cookies' => array(),
		);

		// Can be used by hash auth.
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$req_params['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		$req_params['headers'] = empty('headers') ? array() : $req_params['headers'];

		if (!empty($extra['headers'])) {
			$req_params['headers'] = array_merge($extra['headers'], $req_params['headers']);
		}

		if (!empty($extra['method'])) {
			$req_params['method'] = $extra['method'];
		}

		// @todo smartly detect when to enter this depending on the host.
		$username = 'wbs';
		$password = 'wbs777';

		$req_params['headers'] = array_merge( array(
			'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
		), $req_params['headers'] );

		$req_params = apply_filters('wbs_filter_req_call_params', $req_params);
		$response = wp_remote_post( $url, $req_params );

		$response_code = wp_remote_retrieve_response_code($response);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$res->msg("Error: $error_message" );
			$res->data('response_code', $response_code);
		} else {
			$buff = wp_remote_retrieve_body( $response );
			$res = new df_crm_result($buff);
			$res->data('raw_data', $buff);
			$res->data('url', $url);
			$res->data('request_params', $req_params);
			$res->data('response_code', $response_code);
		}

		return $res;
	}

	/**
	 * Smart redirect method. Sends header redirect or HTTP meta redirect.
	 * @param string $url
	 */
	public function redirect($url) {
		if (empty($url)) {
			throw new Exception("Cannot redirect to an empty URL.");
		}

		if (defined('WP_CLI')) {
			// don't do anything if WP-CLI is running.
			return;
		}

		if ( headers_sent() ) {
			echo '<meta http-equiv="refresh" content="0;URL=\'' . $url . '\'" />  '; // jic
			echo '<script language="javascript">window.parent.location="' . $url . '";</script>';
		} else {
			wp_redirect($url, 302);
		}

		exit;
	}

	/**
	 * @return string
	 */
	public function getRequestUrl() {
		$req_url = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];
		return $req_url;
	}

	// https://code.tutsplus.com/tutorials/deciphering-magic-methods-in-php--net-13085
	public function __get( $name ) {
		return $this->get($name);
	}

	public function set( $name, $val ) {
		return $this->__set($name, $val);
	}

	public function __set( $name, $val ) {
		return $this->data[$name] = $val;
	}

	public function __unset( $name ) {
		unset($this->data[$name]);
	}

	public function __isset( $name ) {
		return $this->has($name);
	}
}