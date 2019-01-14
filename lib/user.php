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
		if (is_null($instance)) {
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
     * @param int/obj $user_id
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
        $is_admin = $user_id
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
        $is_admin = $user_id
                ? user_can( $user_id, $permission )
                : current_user_can( $permission );

        return $is_admin;
    }

    /**
     * Returns the ID of the currently logged in user or the ID of the supplied user obj.
     * @return int
     */
    public function getUserId( $user_id_or_obj = null ) {
        $user_obj = $this->getUser($user_id_or_obj);
        return !empty($user_obj->ID) ? $user_obj->ID : 0;
    }

    /**
     *
     * @return str
     */
    public function getEmail( $user_id = 0 ) {
        $user_obj = $this->getUser( $user_id );
        return !empty($user_obj->user_email) ? $user_obj->user_email : '';
    }

    public function sanitize_id( $id ) {
        return abs( $id );
    }

    public function isLoggedIn() {
        return $this->getUserId() > 0;
    }

    /**
     * Gets meta of the currently logged in user or the those of the supplied one
     * @param str $key
     * @param int $user_id
     * @return mixed
     */
    public function getMeta( $key, $user_id = 0 ) {
        if ( empty( $key ) ) {
            throw new Exception( "Key cannot be empty." );
        }

        $user_obj = $this->getUser( $user_id );
        $user_id = $this->getUserId( $user_obj );
        $user_id = $this->sanitize_id( $user_id );

        if ( empty( $user_id ) ) {
            return false;
        }
        
        $val = get_user_meta( $user_id, $this->meta_key_prefix . $key, true );
        
        return $val;
    }

    /**
     * Sets meta of the currently logged in user or the those of the supplied one
     * @param string $key
     * @param mixed $val
     * @param int $user_id
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
     * @return str
     */
    public function getMetaKeyWithPrefix( $key ) {
         return $this->meta_key_prefix . $key;
    }

    /**
     *
     * @param array $params
     * @return array
     */
    public function search( $params = array() ) {
        $args = array(
            /*'blog_id'      => $GLOBALS['blog_id'],
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

        $users = array();
        $raw_users_obj_arr = get_users( $args );

        if ( isset( $params['load_meta'] ) ) {
            foreach ( $raw_users_obj_arr as $user_obj ) {
                $u = (array) $user_obj;
                //$u['meta']['sys_api_key'] = $this->get_sys_api_key( $user_obj );
                $u['user'] = $user_obj->data->user_login;
                $u['email'] = $user_obj->data->user_email;
                $users[ $user_obj->ID ] = $u;
            }
        }

        return $users;
    }
}
