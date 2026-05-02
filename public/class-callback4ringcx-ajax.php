<?php
/**
 * AJAX handlers for CallBack4RingCX.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CallBack4RingCX_Ajax {

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
	}

	/**
	 * Handle frontend callback form submission.
	 *
	 * @return void
	 */
	public function handle_submit() {
		check_ajax_referer( 'callback4ringcx_submit', 'nonce' );

		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_error(
				array(
					'message' => 'Spam erkannt.',
				),
				400
			);
		}

		$data = $this->get_form_data();

		if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['phone'] ) || empty( $data['callback_date'] ) || empty( $data['callback_time'] ) || empty( $data['note'] ) ) {
			wp_send_json_error(
				array(
					'message' => 'Bitte alle Pflichtfelder ausfüllen.',
				),
				400
			);
		}

		$settings = $this->settings->get_settings();

		if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) || empty( $settings['assertion'] ) || empty( $settings['campaign_id'] ) ) {
			wp_send_json_error(
				array(
					'message' => 'Plugin ist nicht vollständig konfiguriert.',
				),
				500
			);
		}

		$callback_date_utc = $this->get_callback_date_utc( $data['callback_date'], $data['callback_time'], $settings['lead_timezone'] );

		if ( is_wp_error( $callback_date_utc ) ) {
			wp_send_json_error(
				array(
					'message' => $callback_date_utc->get_error_message(),
				),
				400
			);
		}

		$lead_result = $this->api->create_lead( $data );

		if ( is_wp_error( $lead_result ) ) {
			wp_send_json_error(
				array(
					'message' => $lead_result->get_error_message(),
					'details' => $lead_result->get_error_data(),
				),
				500
			);
		}

		if ( empty( $data['agent_id'] ) ) {
			wp_send_json_error(
				array(
					'message'        => 'Lead wurde angelegt, aber es wurde kein Agent ausgewählt.',
					'ringcx_response' => $lead_result['response'],
				),
				400
			);
		}

		$callback_result = $this->api->set_scheduled_callback(
			$lead_result['extern_id'],
			(int) $data['agent_id'],
			$callback_date_utc
		);

		if ( is_wp_error( $callback_result ) ) {
			wp_send_json_error(
				array(
					'message'           => $callback_result->get_error_message(),
					'lead_response'     => $lead_result['response'],
					'callback_details'  => $callback_result->get_error_data(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'message'           => $settings['success_message'],
				'ringcx_response'   => $lead_result['response'],
				'callback_response' => $callback_result['response'],
				'extern_id'         => $lead_result['extern_id'],
				'callback_date_utc' => $callback_date_utc,
			)
		);
	}

	/**
	 * Load agents for configured agent group.
	 *
	 * @return void
	 */
	public function load_agents() {
		check_ajax_referer( 'callback4ringcx_submit', 'nonce' );

		$agents = $this->api->get_agents_by_group();

		if ( is_wp_error( $agents ) ) {
			wp_send_json_error(
				array(
					'message' => $agents->get_error_message(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'agents' => $agents,
			)
		);
	}

	/**
	 * Get sanitized form data from POST.
	 *
	 * @return array
	 */
	private function get_form_data() {
		return array(
			'first_name'    => sanitize_text_field( wp_unslash( $_POST['firstname'] ?? '' ) ),
			'last_name'     => sanitize_text_field( wp_unslash( $_POST['lastname'] ?? '' ) ),
			'phone'         => preg_replace( '/[^0-9+]/', '', wp_unslash( $_POST['phone'] ?? '' ) ),
			'callback_date' => sanitize_text_field( wp_unslash( $_POST['callback_date'] ?? '' ) ),
			'callback_time' => sanitize_text_field( wp_unslash( $_POST['callback_time'] ?? '' ) ),
			'agent_id'      => sanitize_text_field( wp_unslash( $_POST['agent_id'] ?? '' ) ),
			'agent_name'    => sanitize_text_field( wp_unslash( $_POST['agent_name'] ?? '' ) ),
			'note'          => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
		);
	}

	/**
	 * Convert local callback date/time into UTC timestamp format for RingCX.
	 *
	 * @param string $callback_date Local date.
	 * @param string $callback_time Local time.
	 * @param string $lead_timezone Lead timezone.
	 * @return string|WP_Error
	 */
	private function get_callback_date_utc( $callback_date, $callback_time, $lead_timezone ) {
		try {
			$local_timezone = new DateTimeZone( $lead_timezone ? $lead_timezone : 'Europe/Berlin' );
			$local_datetime = new DateTimeImmutable( $callback_date . ' ' . $callback_time, $local_timezone );
			$utc_datetime   = $local_datetime->setTimezone( new DateTimeZone( 'UTC' ) );

			return $utc_datetime->format( 'Y-m-d\TH:i:s.000\Z' );
		} catch ( Exception $exception ) {
			return new WP_Error( 'invalid_datetime', 'Ungültiges Datum oder Uhrzeit.' );
		}
	}
}
