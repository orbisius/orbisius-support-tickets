<?php

/**
 * $user_api = Orbisius_Support_Tickets_User::getInstance();
 */
class Orbisius_Support_Tickets_User {
	/**
	 * Singleton pattern i.e. we have only one instance of this obj
	 * @staticvar Orbisius_Support_Tickets_User $instance
	 * @return Orbisius_Support_Tickets_User
	 */
	public static function getInstance() {
		static $instance = null;

		// This will make the calling class to be instantiated.
		// no need each sub class to define this method.
		if ( is_null( $instance ) ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * This is prefixed to all meta keys. _ means system and won't be shown for editing
	 * @var str
	 */
	private $meta_key_prefix = '_orb_support_ticket_';

	/**
	 * Returns user obj for a given user id or currently logged in one.
	 * The search can be done by email, id, username etc.
	 *
	 * @param int/obj $user_id
	 *
	 * @return obj
	 */
	public function getUser( $user_id = 0 ) {
		$user_obj = null;

		if ( empty( $user_id ) ) {
			$user_obj = wp_get_current_user();
		} elseif ( is_object( $user_id ) ) {
			$user_obj = $user_id;
		} elseif ( ! is_scalar( $user_id ) ) {
			throw new Exception( "Wrong ID." );
		} elseif ( is_numeric( $user_id ) ) {
			$user_obj = get_user_by( 'id', (int) $user_id );
		} elseif ( strpos( $user_id, '@' ) !== false ) {
			$user_obj = get_user_by( 'email', $user_id );
		} else {
			$user_obj = get_user_by( 'login', $user_id );
		}

		return $user_obj;
	}

	/**
	 * orb->obj->isAdmin();
	 *
	 * @return bool
	 */
	public function isAdmin( $user_id = 0 ) {
		$permission = 'manage_options';
		$is_admin   = $user_id
			? user_can( $user_id, $permission )
			: current_user_can( $permission );

		return $is_admin;
	}

	/**
	 * orb->obj->isEditor();
	 *
	 * @return bool
	 * @see https://codex.wordpress.org/Roles_and_Capabilities#Editor
	 */
	public function isEditor( $user_id = 0 ) {
		$permission = 'edit_others_posts';
		$is_admin   = $user_id
			? user_can( $user_id, $permission )
			: current_user_can( $permission );

		return $is_admin;
	}

	/**
	 * Returns the ID of the currently logged in user or the ID of the supplied user obj.
	 * @return int
	 */
	public function getUserId( $user_id_or_obj = null ) {
		$user_obj = $this->getUser( $user_id_or_obj );

		return ! empty( $user_obj->ID ) ? $user_obj->ID : 0;
	}

	/**
	 *
	 * @return str
	 */
	public function getEmail( $user_id = 0 ) {
		$user_obj = $this->getUser( $user_id );

		return ! empty( $user_obj->user_email ) ? $user_obj->user_email : '';
	}

	public function sanitize_id( $id ) {
		return abs( $id );
	}

	public function isLoggedIn() {
		return $this->getUserId() > 0;
	}

	/**
	 * Gets meta of the currently logged in user or the those of the supplied one
	 *
	 * @param str $key
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	public function getMeta( $key, $user_id = 0 ) {
		if ( empty( $key ) ) {
			throw new Exception( "Key cannot be empty." );
		}

		$user_obj = $this->getUser( $user_id );
		$user_id  = $this->getUserId( $user_obj );
		$user_id  = $this->sanitize_id( $user_id );

		if ( empty( $user_id ) ) {
			return false;
		}

		$val = get_user_meta( $user_id, $this->meta_key_prefix . $key, true );

		return $val;
	}

	/**
	 * Sets meta of the currently logged in user or the those of the supplied one
	 *
	 * @param string $key
	 * @param mixed $val
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	public function setMeta( $key, $val = null, $user_id = 0 ) {
		if ( empty( $key ) ) {
			throw new Exception( "Key cannot be empty." );
		}

		$user_id = $user_id ? $user_id : $this->getUserId();
		$user_id = $this->sanitize_id( $user_id );

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( empty( $val ) ) {
			delete_user_meta( $user_id, $this->meta_key_prefix . $key );
		} else {
			update_user_meta( $user_id, $this->meta_key_prefix . $key, $val );
		}

		return $val;
	}

	/**
	 * using internal meta key with the key
	 *
	 * @param str $key
	 *
	 * @return str
	 */
	public function getMetaKeyWithPrefix( $key ) {
		return $this->meta_key_prefix . $key;
	}

	/**
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function search( $params = array() ) {
		$args = array(/*'blog_id'      => $GLOBALS['blog_id'],
            'role'         => '',
            'role__in'     => array(),
            'role__not_in' => array(),
            'meta_key'     => '',
            'meta_value'   => '',
            'meta_compare' => '',
            'meta_query'   => array(),
            'date_query'   => array(),
            'include'      => array(),
            'exclude'      => array(),
            'orderby'      => 'login',
            'order'        => 'ASC',
            'offset'       => '',
            'search'       => '',
            'number'       => '',
            'count_total'  => false,
            'fields'       => 'all',
            'who'          => ''*/
		);

		$users             = array();
		$raw_users_obj_arr = get_users( $args );

		if ( isset( $params['load_meta'] ) ) {
			foreach ( $raw_users_obj_arr as $user_obj ) {
				$u = (array) $user_obj;
				//$u['meta']['sys_api_key'] = $this->get_sys_api_key( $user_obj );
				$u['user']              = $user_obj->data->user_login;
				$u['email']             = $user_obj->data->user_email;
				$users[ $user_obj->ID ] = $u;
			}
		}

		return $users;
	}

	/**
	 * Credit: https://itman.in/en/how-to-get-client-ip-address-in-php/
	 * @return mixed
	 */
	public function getUserIP() {
		// check for shared internet/ISP IP
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) && $this->validateIP( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return $_SERVER['HTTP_CLIENT_IP'];
		}

		// check for IPs passing through proxies
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// check if multiple ips exist in var
			if ( strpos( $_SERVER['HTTP_X_FORWARDED_FOR'], ',' ) !== false ) {
				$iplist = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );

				foreach ( $iplist as $ip ) {
					if ( $this->validateIP( $ip ) ) {
						return $ip;
					}
				}
			} else {
				if ( $this->validateIP( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					return $_SERVER['HTTP_X_FORWARDED_FOR'];
				}
			}
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED'] ) && $this->validateIP( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			return $_SERVER['HTTP_X_FORWARDED'];
		}

		if ( ! empty( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) && $this->validateIP( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) ) {
			return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		}

		if ( ! empty( $_SERVER['HTTP_FORWARDED_FOR'] ) && $this->validateIP( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			return $_SERVER['HTTP_FORWARDED_FOR'];
		}

		if ( ! empty( $_SERVER['HTTP_FORWARDED'] ) && $this->validateIP( $_SERVER['HTTP_FORWARDED'] ) ) {
			return $_SERVER['HTTP_FORWARDED'];
		}

		// return unreliable ip since all else failed
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Ensures an ip address is both a valid IP and does not fall within
	 * a private network range.
	 */
	function validateIP( $ip ) {
		if ( strtolower( $ip ) === 'unknown' ) {
			return false;
		}

		// generate ipv4 network address
		$ip = ip2long( $ip );

		// if the ip is set and not equivalent to 255.255.255.255
		if ( $ip !== false && $ip !== - 1 ) {
			// make sure to get unsigned long representation of ip
			// due to discrepancies between 32 and 64 bit OSes and
			// signed numbers (ints default to signed in PHP)
			$ip = sprintf( '%u', $ip );
			// do private network range checking
			if ( $ip >= 0 && $ip <= 50331647 ) {
				return false;
			}
			if ( $ip >= 167772160 && $ip <= 184549375 ) {
				return false;
			}
			if ( $ip >= 2130706432 && $ip <= 2147483647 ) {
				return false;
			}
			if ( $ip >= 2851995648 && $ip <= 2852061183 ) {
				return false;
			}
			if ( $ip >= 2886729728 && $ip <= 2887778303 ) {
				return false;
			}
			if ( $ip >= 3221225984 && $ip <= 3221226239 ) {
				return false;
			}
			if ( $ip >= 3232235520 && $ip <= 3232301055 ) {
				return false;
			}
			if ( $ip >= 4294967040 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * For now we'll consider people who have at least editor role are suppot reps.
	 * @todo come up with a better way because a support rep could be replying to another support rep via a ticket.
	 * orb->obj->isEditor();
	 * @param $user_id
	 * @return bool
	 */
	public function isSupportRep( $user_id = 0 ) {
		$res = $this->isEditor($user_id);
		return $res;
	}
}
