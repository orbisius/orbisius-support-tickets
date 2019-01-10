<?php

class Orbisius_Support_Tickets_Msg {
    /**
     * Orbisius_Support_Tickets_Msg::msg();
     * @return res
     */
    public static function msg( $msg, $status = 0, $use_inline_css = 0 ) {
        $id = 'app';
        $cls = $extra = $inline_css = $extra_attribs = '';

        $msg = is_scalar($msg) ? $msg : join("\n<br/>", $msg);
        $icon = 'exclamation-sign';

        if ( $status == self::INFO ) { // notice
            $cls = 'app_info alert alert-info';
        } elseif ( $status === 6 ) { // dismissable notice
            $cls = 'app_info alert alert-danger alert-dismissable';
            $extra = ' <button type="button" class="close" data-dismiss="alert" aria-hidden="false"><span aria-hidden="true">&times;</span><span class="__sr-only">Close</span></button>';
            //$extra = ' <button type="button" class="close" data-dismiss="alert" aria-hidden="false">X</button>';
        } elseif ( $status === 4 ) { // dismissable notice
            $cls = 'app_info alert alert-info alert-dismissable';
            $extra = ' <button type="button" class="close" data-dismiss="alert" aria-hidden="false"><span aria-hidden="true">&times;</span><span class="__sr-only">Close</span></button>';
            //$extra = ' <button type="button" class="close" data-dismiss="alert" aria-hidden="false">X</button>';
        } elseif ( $status == self::ERROR || $status == 0 || $status === false ) {
            $cls = 'app_error alert alert-danger';
            $icon = 'remove';
        } elseif ( $status == self::SUCCESS || $status == 1 || $status === true ) {
            $cls = 'app_success alert alert-success';
            $icon = 'ok';
        }

        if (is_array($use_inline_css)) {
            $extra_attribs = self::array2data_attr($use_inline_css);
        } elseif (!empty($use_inline_css)) {
            $inline_css = empty($status) ? 'background-color:red;' : 'background-color:green;';
            $inline_css .= 'text-align:center;margin-left: auto; margin-right:auto; padding-bottom:10px;color:white;';
        }

        $msg_icon = "<span class='glyphicon glyphicon-$icon' aria-hidden='true'></span>";
        $msg = $msg_icon . ' ' . $msg;

        if ( $status != 2 ) {
            $msg = "<strong>$msg</strong>";
        }

        $str = <<<MSG_EOF
<div id='$id-notice' class='$cls' style="$inline_css" $extra_attribs> $msg $extra</div>
MSG_EOF;
        return $str;
    }

    /**
     *
     * @param array $attributes
     * @return string
     */
    public static function array2data_attr($attributes = array()) {
        $pairs = array();

        foreach ($attributes as $name => $value) {
            $name = 'data-' . $name; // prefix the keys with data- prefix so it's accessible later.

            $name  = htmlentities($name, ENT_QUOTES, 'UTF-8');
            $value = htmlentities($value, ENT_QUOTES, 'UTF-8');

            if (is_bool($value)) {
                if ($value) {
                    $pairs[] = $name;
                }
            } else {
                $pairs[] = sprintf('%s="%s"', $name, $value);
            }
        }

        return join(' ', $pairs);
    }

    /**
     * App_Sandbox_Util::m();
     * @param string $msg
     * @param int $status
     * @param bool $use_inline_css
     * @return string
     */
    public static function m($msg, $status = 0, $use_inline_css = 0) {
        $msg = self::msg($msg, $status, $use_inline_css);
        $msg = str_replace('div', 'span', $msg);
        $msg = str_replace('alert', 'label', $msg);
        return $msg;
    }

    const ERROR = 0;
    const SUCCESS = 1;
    const INFO = 2;

	public static function error( $string ) {
    	return self::msg($string, self::ERROR);
	}

	public static function success( $string ) {
    	return self::msg($string, self::SUCCESS);
	}
	public static function info( $string ) {
    	return self::msg($string, self::INFO);
	}
}
