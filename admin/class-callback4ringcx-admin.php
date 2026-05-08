<?php
/**
 * Admin functionality for CallBack4RingCX.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CallBack4RingCX_Admin {

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
     *
     * @param CallBack4RingCX_Settings $settings Settings service.
     * @param CallBack4RingCX_API      $api      API service.
     */
    public function __construct( $settings, $api ) {
        $this->settings = $settings;
        $this->api      = $api;
		add_action( 'admin_post_callback4ringcx_test_connection', array( $this, 'handle_test_connection' ) );
		
    }

    /**
     * Add plugin options page.
     *
     * @return void
     */
    public function admin_menu() {
        add_options_page(
            'CallBack4RingCX',
            'CallBack4RingCX',
            'manage_options',
            'callback4ringcx',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'callback4ringcx_group',
            $this->settings->get_option_key(),
                         array(
                             'sanitize_callback' => array( $this, 'sanitize_settings' ),
                         )
        );
    }

/**
 * Sanitize settings before saving.
 *
 * @param array $input Raw input.
 * @return array
 */
public function sanitize_settings( $input ) {
	static $already_ran = false;
	
    $current  = $this->settings->get_settings();
    $settings = wp_parse_args( (array) $input, $current );
    $notice_key = 'callback4ringcx_messages';

	if ( $already_ran ) {
        return $settings;
    }

    $already_ran = true;

    $settings['enabled']                     = ! empty( $input['enabled'] ) ? '1' : '0';
    $settings['button_label']                = sanitize_text_field( $settings['button_label'] ?? '' );
    $settings['success_message']             = sanitize_text_field( $settings['success_message'] ?? '' );
    $settings['base_url']                    = esc_url_raw( $settings['base_url'] ?? '' );
    //$settings['client_id']                 = sanitize_text_field( $settings['client_id'] ?? '' );
    //$settings['client_secret']             = sanitize_text_field( $settings['client_secret'] ?? '' );
    //$settings['assertion']                 = trim( $settings['assertion'] ?? '' );
	$settings['client_id']     				 = trim( sanitize_text_field( $settings['client_id'] ?? '' ) );
    $settings['client_secret'] 				 = trim( sanitize_text_field( $settings['client_secret'] ?? '' ) );
	$settings['assertion'] 					 = trim( (string) ( $settings['assertion'] ?? '' ) );
    //$settings['account_id'] 				 = '';
	$settings['account_id'] 				 = sanitize_text_field( $current['account_id'] ?? '' );
    $settings['campaign_id']                 = sanitize_text_field( $settings['campaign_id'] ?? '' );
    $settings['description']                 = sanitize_text_field( $settings['description'] ?? '' );
    $settings['dial_priority']               = sanitize_text_field( $settings['dial_priority'] ?? 'IMMEDIATE' );
    $settings['duplicate_handling']          = sanitize_text_field( $settings['duplicate_handling'] ?? 'REMOVE_FROM_LIST' );
    $settings['list_state']                  = sanitize_text_field( $settings['list_state'] ?? 'ACTIVE' );
    $settings['timezone_option']             = sanitize_text_field( $settings['timezone_option'] ?? 'NPA_NXX' );
    $settings['lead_timezone']               = sanitize_text_field( $settings['lead_timezone'] ?? 'Europe/Berlin' );
    $settings['phone_numbers_i18n_enabled']  = ! empty( $input['phone_numbers_i18n_enabled'] ) ? '1' : '0';
    $settings['international_number_format'] = ! empty( $input['international_number_format'] ) ? '1' : '0';
    $settings['agent_group_id']              = sanitize_text_field( $settings['agent_group_id'] ?? '' );
    $settings['privacy_text']                = sanitize_textarea_field( $settings['privacy_text'] ?? '' );

    if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) || empty( $settings['assertion'] ) ) {
        add_settings_error(
            $notice_key,
            'callback4ringcx_missing_credentials',
            'Bitte Client ID, Client Secret und Assertion vollständig eintragen.',
            'error'
        );
    }

    return $settings;
}

	public function handle_test_connection() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Keine Berechtigung.', 'callback4ringcx' ) );
    }

    check_admin_referer( 'callback4ringcx_test_connection' );

    $settings   = $this->settings->get_settings();
    $notice_key = 'callback4ringcx_messages';

    if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) || empty( $settings['assertion'] ) ) {
        add_settings_error(
            $notice_key,
            'callback4ringcx_missing_credentials',
            'Bitte zuerst Client ID, Client Secret und Assertion speichern.',
            'error'
        );

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'options-general.php?page=callback4ringcx' ) );
        exit;
    }

    $auth = $this->api->get_valid_ringcx_token();

    if ( is_wp_error( $auth ) ) {
        add_settings_error(
            $notice_key,
            'callback4ringcx_auth_failed',
            'Authentifizierung fehlgeschlagen: ' . $auth->get_error_message(),
            'error'
        );

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'options-general.php?page=callback4ringcx' ) );
        exit;
    }

    $account_id = $this->api->extract_account_id( $auth );

    if ( '' === $account_id ) {
        add_settings_error(
            $notice_key,
            'callback4ringcx_account_missing',
            'Authentifizierung erfolgreich, aber es konnte keine Account ID aus der Antwort gelesen werden.',
            'error'
        );

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'options-general.php?page=callback4ringcx' ) );
        exit;
    }

    $settings['account_id'] = $account_id;
    update_option( $this->settings->get_option_key(), $settings );

    add_settings_error(
        $notice_key,
        'callback4ringcx_auth_success',
        'Verbindung erfolgreich. Account ID wurde geladen: ' . $account_id,
        'updated'
    );

    set_transient( 'settings_errors', get_settings_errors(), 30 );
    wp_safe_redirect( admin_url( 'options-general.php?page=callback4ringcx' ) );
    exit;
}
    /**
     * Render settings page.
     *
     * @return void
     */
	
    public function settings_page() {
    $settings            = $this->settings->get_settings();
    $campaign_options    = array();
    $agent_group_options = array();

    $is_settings_update = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];

    if (
        ! $is_settings_update &&
        ! empty( $settings['client_id'] ) &&
        ! empty( $settings['client_secret'] ) &&
        ! empty( $settings['assertion'] )
    ) {
        $campaign_options    = $this->api->get_campaign_options();
        $agent_group_options = $this->api->get_agent_group_options();
    }

    //settings_errors( 'callback4ringcx_messages' );

    require CALLBACK4RINGCX_PATH . 'admin/views/settings-page.php';
	}
}
