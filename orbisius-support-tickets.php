<?php
/**
 * Plugin Name: Orbisius Support Tickets
 * Plugin URI: https://orbisius.com/products/wordpress-plugins/orbisius-support-tickets
 * Description: Minimalistic support ticket system that enables you to start providing awesome support in 2 minutes.
 * Version: 1.0.3
 * Author: Svetoslav Marinov (Slavi) | Orbisius.com
 * Author URI: http://orbisius.com
 * Text Domain: orbisius_support_tickets
 * Domain Path: /lang
 */

define( 'ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN', __FILE__ );
define( 'ORBISIUS_SUPPORT_TICKETS_BASE_DIR', dirname( __FILE__ ) );
define( 'ORBISIUS_SUPPORT_TICKETS_BASE_URL', plugins_url( '', ORBISIUS_SUPPORT_TICKETS_BASE_PLUGIN ) );
define( 'ORBISIUS_SUPPORT_TICKETS_DATA_DIR', ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/data' );
define( 'ORBISIUS_SUPPORT_TICKETS_SHARE_DIR', ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/share' );

// Set the following to false to enable dev mode.
define( 'ORBISIUS_SUPPORT_TICKETS_LIVE_ENV', true );

$libs = glob( ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/lib/*.php' );
$mods = glob( ORBISIUS_SUPPORT_TICKETS_BASE_DIR . '/modules/*/*.php' );

$libs = array_merge( (array) $libs, (array) $mods );
$libs = array_unique( $libs );
$libs = array_filter( $libs );

foreach ( $libs as $lib_file ) {
	require_once $lib_file;
}
