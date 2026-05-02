<?php
/**
 * Main plugin bootstrap class.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CallBack4RingCX {

	/**
	 * Settings service.
	 *
	 * @var CallBack4RingCX_Settings
	 */
	private $settings;

	/**
	 * API service.
	 *
	 * @var CallBack4RingCX_API
	 */
	private $api;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = new CallBack4RingCX_Settings();
		$this->api      = new CallBack4RingCX_API( $this->settings );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		$admin  = new CallBack4RingCX_Admin( $this->settings, $this->api );
		$public = new CallBack4RingCX_Public( $this->settings );
		$ajax   = new CallBack4RingCX_Ajax( $this->settings, $this->api );

		add_action( 'admin_menu', array( $admin, 'admin_menu' ) );
		add_action( 'admin_init', array( $admin, 'register_settings' ) );

		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $public, 'render_widget' ) );

		add_action( 'wp_ajax_callback4ringcx_submit', array( $ajax, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_callback4ringcx_submit', array( $ajax, 'handle_submit' ) );

		add_action( 'wp_ajax_callback4ringcx_load_agents', array( $ajax, 'load_agents' ) );
		add_action( 'wp_ajax_nopriv_callback4ringcx_load_agents', array( $ajax, 'load_agents' ) );
	}
}
