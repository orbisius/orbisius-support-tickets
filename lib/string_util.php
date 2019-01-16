<?php

/**
 * Most of the methods are borrowed from Slavi's qSandbox project.
 */
class Orbisius_Support_Tickets_String_Util {
    /**
     * Orbisius_Support_Tickets_String_Util::encode();
     * @return res
     */
    public static function encode( $msg ) {
        $str = htmlentities($msg, ENT_QUOTES, 'UTF-8');
        return $str;
    }
    
    /**
     * Orbisius_Support_Tickets_String_Util::sanitize();
     * @param str $str
     * @return str
     */
    public static function sanitize( $str ) {
        $str = strip_tags($str);
        $str = trim($str);
        return $str;
    }

    /**
     * Orbisius_Support_Tickets_String_Util::isAlphaNum();
     * Allowing a dash
     * @return res
     */
    public static function isAlphaNum( $str ) {
        return preg_match( '#^[\w\-]+$#si', $str );
    }

     /**
     * Orbisius_Support_Tickets_String_Util::trim();
     * 
     * @param type $str
     * @return string/array
     * Ideas gotten from: http://www.jonasjohn.de/snippets/php/trim-array.htm
      * borrowed from Slavi's qSandbox
     */
    public static function trim($data) {
        if ( is_scalar( $data ) ) {
            return trim( $data );
        }

        return array_map( 'self::trim', $data );
    }

    const STRIP_ALL_TAGS = 2;
    const STRIP_SOME_TAGS = 4;

    /**
     * Uses WP's wp_kses to clear some of the html tags but allow some attribs
     * usage: Orbisius_Support_Tickets_String_Util::stripSomeTags($str);
	 * uses WordPress' wp_kses()
     * @param str $buffer string buffer
     * @return str cleaned up text
     * borrowed from Slavi's qSandbox
     */
    public static function stripSomeTags($buffer, $flags = self::STRIP_SOME_TAGS ) {
        // these work only in WP ctx
        static $default_attribs = array(
            'id' => array(),
            'rel' => array(),
            'class' => array(),
            'title' => array(),
            'style' => array(),
            'data' => array(),
            'target' => array(),
            'data-mce-id' => array(),
            'data-mce-style' => array(),
            'data-mce-bogus' => array(),
        );

        $allowed_tags = array(
            'div'           => $default_attribs,
            'span'          => $default_attribs,
            'p'             => $default_attribs,
            'a'             => array_merge( $default_attribs, array(
                'href' => array(),
                'target' => array('_blank', '_top', '_self'),
            ) ),
            'u'             => $default_attribs,
            'i'             => $default_attribs,
            'q'             => $default_attribs,
            'b'             => $default_attribs,
            'ul'            => $default_attribs,
            'ol'            => $default_attribs,
            'li'            => $default_attribs,
            'br'            => $default_attribs,
            'hr'            => $default_attribs,
            'strong'        => $default_attribs,
            'strike'        => $default_attribs,
            'blockquote'    => $default_attribs,
            'del'           => $default_attribs,
            'em'            => $default_attribs,
            'pre'           => $default_attribs,
            'code'          => $default_attribs,
            'style'         => $default_attribs,
        );

        if (function_exists('wp_kses')) { // WP is here
            $buffer = wp_kses($buffer, $allowed_tags);
        } elseif ( $flags & self::STRIP_ALL_TAGS ) {
            $buffer = strip_tags($buffer);
        } else {
            $tags = array();

            foreach (array_keys($allowed_tags) as $tag) {
                $tags[] = "<$tag>";
            }

            $buffer = strip_tags($buffer, join('', $tags));
        }

        $buffer = self::trim($buffer);

        return $buffer;
    }

    /**
     * Orbisius_Support_Tickets_String_Util::hash();
     * @param mixed $param
     * @return string
     */
    public static function hash( $param, $sel_arr_keys = array() ) {
        // we want db NULL values to be treated as empty strings so the hashes
        // are the same
        $param = Orbisius_Support_Tickets_String_Util::convert_null_to_empty( $param );

        if ( is_array( $param ) ) {
            // We care only about certain keys for arrays
            // so we'll discard the rest
            if ( ! empty( $sel_arr_keys ) ) {
                $check_type = true; // if this is false in_array won't remove element 0 ?!?
                $sel_arr_keys = (array) $sel_arr_keys;
                
                foreach ( $param as $key => $val ) {
                    if ( ! in_array( $key, $sel_arr_keys, $check_type ) ) {
                        unset( $param[ $key ] );
                    }
                }
            }

            ksort( $param );
        }

        $param = serialize( $param );
        $param = $param . 'A55ffssssssA2345sdfsdfsfsdS23AAA590asfasF';
        $param = sha1( $param );
        $param = strtoupper( $param );
        
        return $param;
    }

    /**
     * Orbisius_Support_Tickets_String_Util::convert_null_to_empty();
     * 
     * @param mixed $param
     * @return str
     */
    public static function convert_null_to_empty( $param ) {
        if ( is_null( $param )  // php doc: is_scalar() does not consider NULL to be scalar.
                || ( is_scalar( $param ) && (  strcasecmp( $param, 'null' ) == 0 ) ) ) {
            $param = '';
        } elseif ( is_array( $param ) ) {
            $param = array_map( 'self::convert_null_to_empty', $param );
        }

        return $param;
    }
    
    /**
     * Orbisius_Support_Tickets_String_Util::text_plain();
     * 
     * @param mixed $param
     * @return str
     */
    public static function text_plain() {
        if ( ! headers_sent() ) {
            header( "Content-Type: text/plain", true );
        }
    }
    
    /**
     * Orbisius_Support_Tickets_String_Util::normalize_site();
     * 
     * @param mixed $param
     * @return str
     */
    public static function normalize_site($site) {
        if ( ! empty( $site ) && ! preg_match( '#^\w+:/+#si', $site ) ) {
            $site = 'http://' . $site;
        }
        
        return $site;
    }
    
    /**
     * Replaces the template variables
     * Orbisius_Support_Tickets_String_Util::replaceVars();
     * @param string buffer to operate on
     * @param array the keys are uppercased and surrounded by %%KEY_NAME%% or {KEY_NAME}
     * @return string modified data
     */
    public static function replaceVars( $buffer, $params = array() ) {
        if ( is_array( $buffer ) ) {
            foreach ( $buffer as $k => $v ) {
                $old_k = $k;

                $k = self::replaceVars( $k, $params );
                $v = self::replaceVars( $v, $params );

                $buffer[ $k ] = $v;

                // In case the key has to be replaced with some var.
                // remove the old key
                if ( $k != $old_k ) {
                    unset( $buffer[ $old_k ] );
                }
            }

            return $buffer;
        }

        // If there's nothing to replace we won't bother to loop thorugh the replace vars.
        if (    ! is_scalar( $buffer )
                || is_numeric( $buffer )
                || empty( $buffer )
                || (       ( strpos( $buffer, '{' ) === false )
                        && ( strpos( $buffer, '}' ) === false )
                        && ( strpos( $buffer, '%' ) === false )
                    )
                ) {
            return $buffer;
        }

        foreach ( $params as $key => $value ) {
            $key = trim( $key, '%{} ' );

            // Prevent regex things from being replaced from the value
            // e.g. $1 means $1 USD and not regex's first (match)
            $v = str_replace( '$', '\\$', $value );

            // template var can be surrounded by % or { and % or }
            $regex = '#[{%]+' . preg_quote( $key, '#' ) . '[}%]+#si';

            if ( preg_match( $regex, $buffer ) ) {
                $buffer = preg_replace( $regex, $v, $buffer );
            }
        }

        // Let's check if there are unreplaced variables which must start with a letter
        // Let's make this regex smart so it doesn't confuse the HTTP encoded stuff with tpl vars.
        if (0&&preg_match('#([{%]{2,99}[a-z][\w-]+[}%]{2,99})#si', $buffer, $matches)) {
            trigger_error("Not all template variables were replaced. Please, check the missing ones and add them to the input params."
                    . var_export($matches, 1), E_USER_WARNING);
        }

        return $buffer;
    }

    /**
     * 
     * @param str $val
     * @return str
     */
    public static function escape($val) {
        return self::encode($val);
    }

    /**
     *
     * Orbisius_Support_Tickets_String_Util::toAlphaNumeric();
     * @param str $val
     * @return str
     */
    public static function toAlphaNumeric($val) {
	    $val = preg_replace('#[^\w]+#si', '_', $val);
	    $val = preg_replace('#\_+#si', '_', $val);
	    $val = trim($val, '_');
	    $val = strtolower($val);

        return $val;
    }

	/**
	 * Orbisius_Support_Tickets_String_Util::asList()
	 * @param string $val
	 * @return string
	 */
	public static function asList($arr, $attribs = array()) {
		$lines = array();

		foreach ($arr as $value) {
			$lines[] = "\t<li>$value</li>";
		}

		$cls = empty($attribs['class']) ? 'df_crm_list' : $attribs['class'];

		$buff = '';
		$buff .= "<ul class='$cls'>\n";
		$buff .= join("\n", $lines);
		$buff .= "</ul>\n";

		return $buff;
	}
}
