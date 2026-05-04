<?php
/**
 * Plugin Name: RingCX_Plugin_Callback_WordPress
 * Description: Floating Callback Button für WordPress mit Übergabe an RingCX Voice als Lead/Callback.
 * Version: 1.2.0
 * Author: Patrick Heller
 * GitHub Plugin URI: https://github.com/PatrickHeller/RingCX_Plugin_Callback_WordPress
 * Primary Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'callback4ringcx_log' ) ) {
	/**
	 * Debug logger.
	 *
	 * @param mixed $message Message to log.
	 * @return void
	 */
	function callback4ringcx_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}

define( 'CALLBACK4RINGCX_VERSION', '1.0.1' );
define( 'CALLBACK4RINGCX_FILE', __FILE__ );
define( 'CALLBACK4RINGCX_PATH', plugin_dir_path( __FILE__ ) );
define( 'CALLBACK4RINGCX_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( CALLBACK4RINGCX_PATH . 'vendor/autoload.php' ) ) {
	require_once CALLBACK4RINGCX_PATH . 'vendor/autoload.php';
}

require_once CALLBACK4RINGCX_PATH . 'includes/class-callback4ringcx-settings.php';
require_once CALLBACK4RINGCX_PATH . 'includes/class-callback4ringcx-api.php';
require_once CALLBACK4RINGCX_PATH . 'admin/class-callback4ringcx-admin.php';
require_once CALLBACK4RINGCX_PATH . 'public/class-callback4ringcx-public.php';
require_once CALLBACK4RINGCX_PATH . 'public/class-callback4ringcx-ajax.php';
require_once CALLBACK4RINGCX_PATH . 'includes/class-callback4ringcx.php';

/**
 * Run plugin.
 *
 * @return void
 */
function callback4ringcx_run() {
	$plugin = new CallBack4RingCX();
	$plugin->run();
}

callback4ringcx_run();
