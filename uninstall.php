<?php
/**
 * Uninstall CallBack4RingCX.
 *
 * Fired when the plugin is deleted from WordPress.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'callback4ringcx_settings' );
