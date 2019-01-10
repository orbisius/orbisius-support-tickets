<?php
/*
  Plugin Name: Orbisius Support Tickets
  Plugin URI: http://orbisius.com/products/
  Description: Orbisius ticket support system
  Version: 1.0.0
  Author: Svetoslav Marinov (Slavi)
  Author URI: http://orbisius.com
  Text Domain: orbisius_support_tickets
  Domain Path: /lang
 */

define('ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN', __FILE__);
define('ORBISIUS_SUPPORT_TICKETS_BASE_DIR', dirname(__FILE__));
define('ORBISIUS_SUPPORT_TICKETS_BASE_URL', plugins_url( '', ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ));
define('ORBISIUS_SUPPORT_TICKETS_DATA_DIR', ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/data');
define('ORBISIUS_SUPPORT_TICKETS_SHARE_DIR', ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/share');
define('ORBISIUS_SUPPORT_TICKETS_DEV_ENV', !empty($_SERVER['DEV_ENV'])
                         || (!empty($_SERVER['HTTP_HOST']) && preg_match('#localhost|devel\.ca|qsandbox|\.clients\.|staging#si', $_SERVER['HTTP_HOST'])));
define('ORBISIUS_SUPPORT_TICKETS_LIVE_ENV', !ORBISIUS_SUPPORT_TICKETS_DEV_ENV);

require_once ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/conf/config.php';

$libs = glob(ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/lib/*.php');
$module_libs = glob(ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/modules/*/lib/*.php');
$mods = glob(ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/modules/*/*.php');

if (file_exists(ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/vendor/autoload.php')) {
	$mods[] = ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/vendor/autoload.php';
}

$libs = array_merge((array) $libs, (array) $mods, (array) $module_libs);
$libs = array_unique($libs);
$libs = array_filter($libs);

foreach ($libs as $lib_file) {
	require_once( $lib_file );
}
