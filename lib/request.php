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
			$instance->initialize();
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
	public function initialize( $data = null ) {
		// see https://codex.wordpress.org/Function_Reference/stripslashes_deep
		if ( is_null( $this->data ) ) {
			$data = empty( $data ) ? $_REQUEST : $data;
			$this->raw_data = $data;
			$data = stripslashes_deep( $data );
			$data = $this->sanitizeData( $data );
			$this->data = $data;
		}
	}

	/**
	 *
	 * @param mixed|str|array|null $data
	 * @return mixed|str|array|null
	 * @throws Exception
	 */
	public function sanitizeData( $data = null ) {
		if ( is_scalar( $data ) ) {
			$data = wp_kses_data( $data );
			$data = trim( $data );
		} elseif ( is_array( $data ) ) {
			$data = array_map( array( $this, 'sanitizeData' ), $data );
		} else {
			// we don't know what to do with this type of data so we'll leave it as is.
		}

		return $data;
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
			echo '<script language="javascript">window.parent.location="' . esc_url($url) . '";</script>';
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

	/**
	 * This is used in submit ticket form
	 * @var array
	 */
	private $form_data_defaults = array(
		'id' => 0,
		'email' => '',
		'subject' => '',
		'message' => '',
		'pass' => '',
	);

	/**
	 * Gets the data that the plugin expects or the value for a given variable.
	 * @param string $key (optional
	 * @return int|string|array|mixed
	 */
	public function getTicketData($key = '') {
		$data = $this->getRaw('orbisius_support_tickets_data', array());
		$data = array_replace_recursive( $this->form_data_defaults, $data );
		$val = apply_filters( 'orbisius_support_tickets_filter_submit_ticket_form_sanitize_data', $data );

		if (!empty($key)) {
			$val = empty($data[$key]) ? '' : $data[$key];
		}

		$val = $this->trim($val);

		if (preg_match('#id#si', $key)) {
			$val = absint($val);
		}

		return $val;
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