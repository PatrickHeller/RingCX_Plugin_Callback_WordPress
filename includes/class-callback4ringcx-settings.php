<?php
/**
 * Settings service.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CallBack4RingCX_Settings {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'callback4ringcx_settings';

	/**
	 * Get option key.
	 *
	 * @return string
	 */
	public function get_option_key() {
		return self::OPTION_KEY;
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'enabled'                     => '1',
			'button_label'                => 'Rückruf anfordern',
			'success_message'             => 'Vielen Dank. Ihr Rückrufwunsch wurde erfolgreich übermittelt.',
			'base_url'                    => 'https://ringcx.ringcentral.com/voice/api/v1',
			'client_id'                   => '',
			'client_secret'               => '',
			'assertion'                   => '',
			'account_id'                  => '',
			'campaign_id'                 => '',
			'description'                 => 'Website Callback Request',
			'dial_priority'               => 'IMMEDIATE',
			'duplicate_handling'          => 'REMOVE_FROM_LIST',
			'list_state'                  => 'ACTIVE',
			'timezone_option'             => 'EXPLICIT',
			'lead_timezone'               => 'Europe/Berlin',
			'phone_numbers_i18n_enabled'  => '1',
			'international_number_format' => '0',
			'privacy_text'                => 'Mit dem Absenden stimmen Sie der Verarbeitung Ihrer Daten zum Zweck des Rückrufs zu.',
			'ringcx_access_token'         => '',
			'ringcx_refresh_token'        => '',
			'ringcx_token_expires_at'     => '',
			'agent_group_id'              => '',
		);
	}

	/**
	 * Get stored settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), $this->get_defaults() );
	}

	/**
	 * Save settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		return update_option( self::OPTION_KEY, $settings );
	}
}
