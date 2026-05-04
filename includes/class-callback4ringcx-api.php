<?php
/**
 * API service for RingCentral and RingCX.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CallBack4RingCX_API {

    /**
     * Settings service.
     *
     * @var CallBack4RingCX_Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param CallBack4RingCX_Settings $settings Settings service.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Extract account ID from auth response.
     *
     * @param array $auth_response Authentication response.
     * @return string
     */
    public function extract_account_id( $auth_response ) {
        if ( ! empty( $auth_response['accountId'] ) ) {
            return (string) $auth_response['accountId'];
        }

        if ( ! empty( $auth_response['agentDetails'] ) && is_array( $auth_response['agentDetails'] ) ) {
            foreach ( $auth_response['agentDetails'] as $agent ) {
                if ( ! empty( $agent['accountId'] ) ) {
                    return (string) $agent['accountId'];
                }
            }
        }

        return '';
    }

    /**
     * Get RingCentral access token via JWT bearer flow.
     *
     * @param string $client_id Client ID.
     * @param string $client_secret Client secret.
     * @param string $assertion JWT assertion.
     * @return array|WP_Error
     */
    public function get_ringcentral_access_token( $client_id, $client_secret, $assertion ) {
        $response = wp_remote_post(
            'https://platform.ringcentral.com/restapi/oauth/token',
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                                   'Content-Type'  => 'application/x-www-form-urlencoded',
                                   'Accept'        => 'application/json',
                ),
                'body'    => array(
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $assertion,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            callback4ringcx_log( 'WordPress HTTP Fehler: ' . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 || empty( $body['access_token'] ) ) {
            callback4ringcx_log( 'RingCentral Access Token konnte nicht geholt werden.' );
            callback4ringcx_log( $body );
            return new WP_Error( 'rc_auth_failed', 'RingCentral Access Token konnte nicht geholt werden.' );
        }

        return $body;
    }

    /**
     * Exchange RingCentral access token for RingCX access token.
     *
     * @param string $rc_access_token RingCentral access token.
     * @return array|WP_Error
     */
    public function get_ringcx_access_token( $rc_access_token ) {
        $response = wp_remote_post(
		'https://ringcx.ringcentral.com/api/auth/login/rc/accesstoken',
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
			'body'    => array(
				'rcAccessToken' => $rc_access_token,
				'rcTokenType'   => 'Bearer',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		callback4ringcx_log( 'WordPress HTTP Fehler: ' . $response->get_error_message() );
		return $response;
	}

	$code     = wp_remote_retrieve_response_code( $response );
	$body_raw = wp_remote_retrieve_body( $response );
	$body     = json_decode( $body_raw, true );

	callback4ringcx_log( 'RingCX Login HTTP Code: ' . $code );
	callback4ringcx_log( 'RingCX Login Body: ' . $body_raw );

	if ( $code < 200 || $code >= 300 || empty( $body['accessToken'] ) ) {
		return new WP_Error( 'ringcx_auth_failed', 'RingCX Access Token konnte nicht geholt werden.' );
	}

	return $body;
    }

    /**
 * Refresh RingCX access token.
 *
 * @param string $refresh_token RingCX refresh token.
 * @return array|WP_Error
 */
public function refresh_ringcx_access_token( $refresh_token ) {
	$response = wp_remote_post(
		'https://ringcx.ringcentral.com/api/auth/token/refresh',
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
			'body'    => array(
				'refresh_token' => $refresh_token,
				'rcTokenType'   => 'Bearer',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code     = wp_remote_retrieve_response_code( $response );
	$body_raw = wp_remote_retrieve_body( $response );
	$body     = json_decode( $body_raw, true );

	callback4ringcx_log( 'RingCX Refresh HTTP Code: ' . $code );
	callback4ringcx_log( 'RingCX Refresh Body: ' . $body_raw );

	if ( $code < 200 || $code >= 300 || empty( $body['accessToken'] ) ) {
		return new WP_Error( 'ringcx_refresh_failed', 'RingCX Token Refresh fehlgeschlagen.' );
	}

	return $body;
}

    /**
     * Get a valid RingCX token, either from cache, refresh, or full auth flow.
     *
     * @return array|WP_Error
     */
    public function get_valid_ringcx_token( $persist_settings = true ) {
    $settings = $this->settings->get_settings();
    $now      = time();

    if (
        ! empty( $settings['ringcx_access_token'] ) &&
        ! empty( $settings['ringcx_token_expires_at'] ) &&
        $now < (int) $settings['ringcx_token_expires_at'] - 30 &&
        ! empty( $settings['account_id'] )
    ) {
        return array(
            'accessToken'  => $settings['ringcx_access_token'],
            'refreshToken' => $settings['ringcx_refresh_token'] ?? '',
            'accountId'    => $settings['account_id'],
            'expiresAt'    => (int) $settings['ringcx_token_expires_at'],
        );
    }

    if ( ! empty( $settings['ringcx_refresh_token'] ) ) {
        $refresh = $this->refresh_ringcx_access_token( $settings['ringcx_refresh_token'] );

        if ( ! is_wp_error( $refresh ) ) {
            $settings['ringcx_access_token']     = $refresh['accessToken'];
            $settings['ringcx_refresh_token']    = ! empty( $refresh['refreshToken'] ) ? $refresh['refreshToken'] : $settings['ringcx_refresh_token'];
            $settings['ringcx_token_expires_at'] = time() + 240;

            $account_id = $this->extract_account_id( $refresh );
            if ( '' !== $account_id ) {
                $settings['account_id'] = $account_id;
            }

            if ( $persist_settings ) {
                $this->settings->save_settings( $settings );
            }

            return array(
                'accessToken'  => $settings['ringcx_access_token'],
                'refreshToken' => $settings['ringcx_refresh_token'],
                'accountId'    => $settings['account_id'] ?? '',
                'expiresAt'    => (int) $settings['ringcx_token_expires_at'],
            );
        }
    }

    $rc_token_response = $this->get_ringcentral_access_token(
        $settings['client_id'],
        $settings['client_secret'],
        $settings['assertion']
    );

    if ( is_wp_error( $rc_token_response ) ) {
        return $rc_token_response;
    }

    $ringcx_token_response = $this->get_ringcx_access_token( $rc_token_response['access_token'] );

    if ( is_wp_error( $ringcx_token_response ) ) {
        return $ringcx_token_response;
    }

    $settings['ringcx_access_token']     = $ringcx_token_response['accessToken'];
    $settings['ringcx_refresh_token']    = ! empty( $ringcx_token_response['refreshToken'] ) ? $ringcx_token_response['refreshToken'] : '';
    $settings['ringcx_token_expires_at'] = time() + 240;

    $account_id = $this->extract_account_id( $ringcx_token_response );
    if ( '' !== $account_id ) {
        $settings['account_id'] = $account_id;
    }

    if ( $persist_settings ) {
        $this->settings->save_settings( $settings );
    }

    return array(
        'accessToken'  => $settings['ringcx_access_token'],
        'refreshToken' => $settings['ringcx_refresh_token'],
        'accountId'    => $settings['account_id'] ?? '',
        'expiresAt'    => (int) $settings['ringcx_token_expires_at'],
    );
}

    /**
     * Get available campaigns grouped across dial groups.
     *
     * @return array
     */
    public function get_campaign_options() {
        $campaigns = array();
        $settings  = $this->settings->get_settings();
        $auth      = $this->get_valid_ringcx_token();

        if ( is_wp_error( $auth ) ) {
            return $campaigns;
        }

        $ringcx_access_token = $auth['accessToken'];
        $ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];

        if ( empty( $ringcx_account_id ) ) {
            return $campaigns;
        }

        $base                = trailingslashit( rtrim( $settings['base_url'], '/' ) );
        $dial_groups_endpoint = $base . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/dialGroups';

        $groups_response = wp_remote_get(
            $dial_groups_endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                   'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $groups_response ) ) {
            return $campaigns;
        }

        $groups_code = wp_remote_retrieve_response_code( $groups_response );
        $groups_body = json_decode( wp_remote_retrieve_body( $groups_response ), true );

        if ( $groups_code < 200 || $groups_code >= 300 || ! is_array( $groups_body ) ) {
            return $campaigns;
        }

        foreach ( $groups_body as $group ) {
            if ( empty( $group['dialGroupId'] ) ) {
                continue;
            }

            $campaigns_endpoint = $base . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/dialGroups/' . rawurlencode( $group['dialGroupId'] ) . '/campaigns';

            $campaigns_response = wp_remote_get(
                $campaigns_endpoint,
                array(
                    'timeout' => 20,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                       'Accept'        => 'application/json',
                    ),
                )
            );

            if ( is_wp_error( $campaigns_response ) ) {
                continue;
            }

            $campaigns_code = wp_remote_retrieve_response_code( $campaigns_response );
            $campaigns_body = json_decode( wp_remote_retrieve_body( $campaigns_response ), true );

            if ( $campaigns_code < 200 || $campaigns_code >= 300 || ! is_array( $campaigns_body ) ) {
                continue;
            }

            foreach ( $campaigns_body as $campaign ) {
                if ( empty( $campaign['campaignId'] ) ) {
                    continue;
                }

                $campaigns[ $campaign['campaignId'] ] = array(
                    'id'             => $campaign['campaignId'],
                    'name'           => ! empty( $campaign['campaignName'] ) ? $campaign['campaignName'] : 'Campaign ' . $campaign['campaignId'],
                                                              'dial_group_name'=> ! empty( $group['dialGroupName'] ) ? $group['dialGroupName'] : '',
                );
            }
        }

        uasort(
            $campaigns,
            function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        return $campaigns;
    }

    /**
     * Get available agent groups.
     *
     * @return array
     */
    public function get_agent_group_options() {
        $groups   = array();
        $settings = $this->settings->get_settings();
        $auth     = $this->get_valid_ringcx_token();

        if ( is_wp_error( $auth ) ) {
            return $groups;
        }

        $ringcx_access_token = $auth['accessToken'];
        $ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];

        if ( empty( $ringcx_account_id ) ) {
            return $groups;
        }

        $endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/agentGroups';

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                   'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $groups;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code < 200 || $status_code >= 300 || ! is_array( $body ) ) {
            return $groups;
        }

        foreach ( $body as $group ) {
            if ( empty( $group['agentGroupId'] ) ) {
                continue;
            }

            $groups[ $group['agentGroupId'] ] = array(
                'id'         => $group['agentGroupId'],
                'name'       => ! empty( $group['groupName'] ) ? $group['groupName'] : 'Agent Group ' . $group['agentGroupId'],
                                                      'is_default' => ! empty( $group['isDefault'] ),
            );
        }

        uasort(
            $groups,
            function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        return $groups;
    }

    /**
     * Get agents for configured agent group.
     *
     * @return array|WP_Error
     */
    public function get_agents_by_group() {
        $settings = $this->settings->get_settings();

        if (
            empty( $settings['client_id'] ) ||
            empty( $settings['client_secret'] ) ||
            empty( $settings['assertion'] ) ||
            empty( $settings['account_id'] ) ||
            empty( $settings['agent_group_id'] )
        ) {
            return new WP_Error( 'config_incomplete', 'Agenten-Konfiguration ist unvollständig.' );
        }

        $auth = $this->get_valid_ringcx_token();

        if ( is_wp_error( $auth ) ) {
            return $auth;
        }

        $ringcx_access_token = $auth['accessToken'];
        $ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];

        $endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/agentGroups/' . rawurlencode( $settings['agent_group_id'] ) . '/agents';

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                   'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'agents_request_failed', 'Agenten konnten nicht geladen werden.' );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code < 200 || $status_code >= 300 || ! is_array( $body ) ) {
            return new WP_Error( 'agents_api_failed', 'RingCX hat keine Agenten geliefert.' );
        }

        $agents = array_map(
            function ( $agent ) {
                $label = trim(
                    ( ! empty( $agent['firstName'] ) ? $agent['firstName'] : '' ) . ' ' .
                    ( ! empty( $agent['lastName'] ) ? $agent['lastName'] : '' )
                );

                if ( '' === $label ) {
                    $label = ! empty( $agent['username'] ) ? $agent['username'] : 'Agent ' . $agent['agentId'];
                }

                return array(
                    'id'       => ! empty( $agent['agentId'] ) ? $agent['agentId'] : '',
                             'name'     => $label,
                             'username' => ! empty( $agent['username'] ) ? $agent['username'] : '',
                );
            },
            $body
        );

        return $agents;
    }



    /**
     * Create callback lead in RingCX.
     *
     * @param array $lead_data Lead input data.
     * @return array|WP_Error
     */
    public function create_lead( $lead_data ) {
        $settings = $this->settings->get_settings();
        $auth     = $this->get_valid_ringcx_token();

        if ( is_wp_error( $auth ) ) {
            return $auth;
        }

        $ringcx_access_token = $auth['accessToken'];
        $ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];

        if ( empty( $ringcx_account_id ) || empty( $settings['campaign_id'] ) ) {
            return new WP_Error( 'config_incomplete', 'Plugin ist nicht vollständig konfiguriert.' );
        }

        $extern_id = 'wp-callback-' . time() . '-' . wp_rand( 1000, 9999 );

        $payload = array(
            'description'                 => $settings['description'],
            'dialPriority'                => $settings['dial_priority'],
            'duplicateHandling'           => $settings['duplicate_handling'],
            'listState'                   => $settings['list_state'],
            'timeZoneOption'              => $settings['timezone_option'],
            'phoneNumbersI18nEnabled'     => '1' === $settings['phone_numbers_i18n_enabled'],
            'internationalNumberFormat'   => '1' === $settings['international_number_format'],
            'numberOriginCountry'         => $this->get_number_origin_country( $lead_data['phone'] ),
            'uploadLeads'                 => array(
                array(
                    'externId'     => $extern_id,
                    'auxData1'     => $lead_data['note'],
                    'leadPhone'    => $lead_data['phone'],
                    'firstName'    => $lead_data['first_name'],
                    'lastName'     => $lead_data['last_name'],
                    'leadTimezone' => $settings['lead_timezone'],
                ),
            ),
        );

        $endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/campaigns/' . rawurlencode( $settings['campaign_id'] ) . '/leadLoader/direct';

        callback4ringcx_log( '--- Sende Lead an RingCX ---' );
        callback4ringcx_log( 'Endpoint URL: ' . $endpoint );
        callback4ringcx_log( 'Gesendete Payload:' );
        callback4ringcx_log( $payload );

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                   'Content-Type'  => 'application/json',
                                   'Accept'        => 'application/json',
                ),
                'body'    => wp_json_encode( $payload ),
            )
        );

        if ( is_wp_error( $response ) ) {
            callback4ringcx_log( 'Lead WP-Error: ' . $response->get_error_message() );
            return new WP_Error( 'lead_request_failed', 'API-Verbindung zu RingCX fehlgeschlagen.' );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );
        $body        = json_decode( $body_raw, true );

        callback4ringcx_log( 'RingCX Antwort Code: ' . $status_code );
        callback4ringcx_log( 'RingCX Antwort Body: ' . $body_raw );
        callback4ringcx_log( '--- Ende Lead Senden ---' );

        if ( $status_code < 200 || $status_code >= 300 ) {
            return new WP_Error( 'lead_api_failed', 'RingCX hat die Anfrage abgelehnt.', array( 'body' => $body_raw ) );
        }

        return array(
            'extern_id' => $extern_id,
            'response'  => $body,
            'raw_body'  => $body_raw,
        );
    }

	/**
 * Create callback lead in a specific campaign for group callback flow.
 *
 * @param array $lead_data Lead input data.
 * @param int   $campaign_id Selected campaign ID.
 * @return array|WP_Error
 */
public function create_lead_for_campaign( $lead_data, $campaign_id ) {
	$settings = $this->settings->get_settings();
	$auth     = $this->get_valid_ringcx_token();

	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$ringcx_access_token = $auth['accessToken'];
	$ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];
	$campaign_id         = (int) $campaign_id;

	if ( empty( $ringcx_account_id ) || empty( $campaign_id ) ) {
		return new WP_Error( 'config_incomplete', 'Campaign-Callback ist nicht vollständig konfiguriert.' );
	}

	$extern_id = 'wp-callback-' . time() . '-' . wp_rand( 1000, 9999 );

	$payload = array(
		'description'               => $settings['description'],
		'dialPriority'              => $settings['dial_priority'],
		'duplicateHandling'         => $settings['duplicate_handling'],
		'timeZoneOption'            => $settings['timezone_option'],
		'phoneNumbersI18nEnabled'   => '1' === $settings['phone_numbers_i18n_enabled'],
		'internationalNumberFormat' => '1' === $settings['international_number_format'],
		'numberOriginCountry'       => $this->get_number_origin_country( $lead_data['phone'] ),
		'uploadLeads'               => array(
			array(
				'externId'  => $extern_id,
				'auxData1'  => $lead_data['note'],
				'firstName' => $lead_data['first_name'],
				'lastName'  => $lead_data['last_name'],
				'leadPhone' => $lead_data['phone'],
			),
		),
	);

	$endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) .
		'admin/accounts/' . rawurlencode( $ringcx_account_id ) .
		'/campaigns/' . rawurlencode( $campaign_id ) .
		'/leadLoader/direct';

	callback4ringcx_log( '--- Sende Group Lead an RingCX ---' );
	callback4ringcx_log( 'Endpoint URL: ' . $endpoint );
	callback4ringcx_log( 'Gesendete Payload:' );
	callback4ringcx_log( $payload );

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		callback4ringcx_log( 'Group Lead WP-Error: ' . $response->get_error_message() );
		return new WP_Error( 'lead_request_failed', 'API-Verbindung zu RingCX fehlgeschlagen.' );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body_raw    = wp_remote_retrieve_body( $response );
	$body        = json_decode( $body_raw, true );

	callback4ringcx_log( 'RingCX Antwort Code: ' . $status_code );
	callback4ringcx_log( 'RingCX Antwort Body: ' . $body_raw );
	callback4ringcx_log( '--- Ende Group Lead Senden ---' );

	if ( $status_code < 200 || $status_code >= 300 ) {
		return new WP_Error(
			'lead_api_failed',
			'RingCX hat die Campaign-Lead-Anfrage abgelehnt.',
			array( 'body' => $body_raw )
		);
	}

	return array(
		'extern_id' => $extern_id,
		'response'  => $body,
		'raw_body'  => $body_raw,
	);
}
	/**
 * Set group callback for an existing lead via MANUAL_LEADS.
 *
 * @param string $extern_id   Extern ID of the created lead.
 * @param int    $campaign_id Selected campaign ID.
 * @param int    $pass_delay  Delay in minutes from now.
 * @return array|WP_Error
 */
public function set_group_callback( $extern_id, $campaign_id, $pass_delay = 10 ) {
	$settings = $this->settings->get_settings();
	$auth     = $this->get_valid_ringcx_token();

	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$ringcx_access_token = $auth['accessToken'];
	$ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];
	$campaign_id         = (int) $campaign_id;
	$pass_delay          = max( 1, (int) $pass_delay );

	if ( empty( $ringcx_account_id ) || empty( $campaign_id ) || empty( $extern_id ) ) {
		return new WP_Error( 'config_incomplete', 'Group-Callback ist nicht vollständig konfiguriert.' );
	}

	$payload = array(
		'campaignLeadSearchCriteria' => array(
			'externIds'   => array( $extern_id ),
			'campaignIds' => array( $campaign_id ),
		),
		'leadActionParams' => array(
			'paramMap' => array(
				'PASS_DISPOSITION' => 'QUEUE_CALLBACK',
				'REQUEUE'          => true,
				'DO_NOT_CALL'      => false,
				'PASS_DELAY'       => $pass_delay,
				'MERGE_ORIGINAL'   => true,
			),
		),
	);

	$endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) .
		'admin/accounts/' . rawurlencode( $ringcx_account_id ) .
		'/campaignLeads/actions?leadAction=MANUAL_LEADS';

	callback4ringcx_log( '--- Setze Group Callback ---' );
	callback4ringcx_log( 'Group Callback Endpoint URL: ' . $endpoint );
	callback4ringcx_log( 'Group Callback Payload:' );
	callback4ringcx_log( $payload );

	$response = wp_remote_request(
		$endpoint,
		array(
			'method'  => 'PUT',
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		callback4ringcx_log( 'Group Callback WP-Error: ' . $response->get_error_message() );
		return new WP_Error(
			'group_callback_request_failed',
			'Lead wurde angelegt, aber der Group-Callback konnte nicht gesetzt werden.'
		);
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body_raw    = wp_remote_retrieve_body( $response );
	$body        = json_decode( $body_raw, true );

	callback4ringcx_log( 'Group Callback Antwort Code: ' . $status_code );
	callback4ringcx_log( 'Group Callback Antwort Body: ' . $body_raw );
	callback4ringcx_log( '--- Ende Group Callback ---' );

	if ( $status_code < 200 || $status_code >= 300 ) {
		return new WP_Error(
			'group_callback_api_failed',
			'Lead wurde angelegt, aber RingCX hat den Group-Callback abgelehnt.',
			array( 'body' => $body_raw )
		);
	}

	return array(
		'response' => $body,
		'raw_body' => $body_raw,
	);
}

	/**
 * Ermittelt den passenden RingCX numberOriginCountry Wert aus einer Telefonnummer.
 *
 * @param string $phone Telefonnummer aus dem Formular.
 * @return string
 */
private function get_number_origin_country( $phone ) {
	try {
		$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
		$number     = $phone_util->parse( $phone, 'DE' );

		if ( ! $phone_util->isValidNumber( $number ) ) {
			return 'GER';
		}

		$region_code = $phone_util->getRegionCodeForNumber( $number );

		if ( empty( $region_code ) ) {
			return 'GER';
		}

		return $this->map_alpha2_to_alpha3( strtoupper( $region_code ) );
	} catch ( \Throwable $e ) {
		callback4ringcx_log( 'Phone country detection failed: ' . $e->getMessage() );
		return 'GER';
	}
}

/**
 * Mappt ISO-3166-1 alpha-2 auf alpha-3.
 *
 * @param string $alpha2 Zweistelliger Ländercode.
 * @return string
 */
private function map_alpha2_to_alpha3( $alpha2 ) {
	$map = array(
		'DE' => 'GER',
		'AT' => 'AUT',
		'CH' => 'CHE',
		'GB' => 'GBR',
		'IE' => 'IRL',
		'FR' => 'FRA',
		'IT' => 'ITA',
		'ES' => 'ESP',
		'NL' => 'NLD',
		'BE' => 'BEL',
		'LU' => 'LUX',
		'PL' => 'POL',
		'CZ' => 'CZE',
		'SK' => 'SVK',
		'HU' => 'HUN',
		'US' => 'USA',
		'CA' => 'CAN',
		'AU' => 'AUS',
		'NZ' => 'NZL',
	);

	return $map[ $alpha2 ] ?? 'GER';
}
	
	
    /**
     * Set scheduled agent callback for an existing lead.
     *
     * @param string $extern_id Extern ID of the created lead.
     * @param int    $agent_id Agent ID.
     * @param string $callback_date_utc Callback datetime in UTC format.
     * @return array|WP_Error
     */
    public function set_scheduled_callback( $extern_id, $agent_id, $callback_date_utc ) {
        $settings = $this->settings->get_settings();
        $auth     = $this->get_valid_ringcx_token();

        if ( is_wp_error( $auth ) ) {
            return $auth;
        }

        $ringcx_access_token = $auth['accessToken'];
        $ringcx_account_id   = ! empty( $auth['accountId'] ) ? $auth['accountId'] : $settings['account_id'];

        if ( empty( $ringcx_account_id ) || empty( $settings['campaign_id'] ) ) {
            return new WP_Error( 'config_incomplete', 'Plugin ist nicht vollständig konfiguriert.' );
        }

        $payload = array(
            'campaignLeadSearchCriteria' => array(
                'campaignIds' => array( (int) $settings['campaign_id'] ),
                                                  'externIds'   => array( $extern_id ),
            ),
            'leadActionParams'           => array(
                'paramMap' => array(
                    'ACTION_TYPE'          => 'AGENT',
                    'CALLBACK_DATE'        => $callback_date_utc,
                    'RESERVATION_AGENT_ID' => (int) $agent_id,
                ),
            ),
        );

        $endpoint = trailingslashit( rtrim( $settings['base_url'], '/' ) ) . 'admin/accounts/' . rawurlencode( $ringcx_account_id ) . '/campaignLeads/actions?leadAction=CALLBACK_LEADS';

        callback4ringcx_log( '--- Setze Scheduled Agent Callback ---' );
        callback4ringcx_log( 'Callback Endpoint URL: ' . $endpoint );
        callback4ringcx_log( 'Callback Payload:' );
        callback4ringcx_log( $payload );

        $response = wp_remote_request(
            $endpoint,
            array(
                'method'  => 'PUT',
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . trim( $ringcx_access_token ),
                                   'Content-Type'  => 'application/json',
                                   'Accept'        => 'application/json',
                ),
                'body'    => wp_json_encode( $payload ),
            )
        );

        if ( is_wp_error( $response ) ) {
            callback4ringcx_log( 'Callback WP-Error: ' . $response->get_error_message() );
            return new WP_Error( 'callback_request_failed', 'Lead wurde angelegt, aber der Agent-Callback konnte nicht gesetzt werden.' );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );
        $body        = json_decode( $body_raw, true );

        callback4ringcx_log( 'Callback Antwort Code: ' . $status_code );
        callback4ringcx_log( 'Callback Antwort Body: ' . $body_raw );
        callback4ringcx_log( '--- Ende Scheduled Agent Callback ---' );

        if ( $status_code < 200 || $status_code >= 300 ) {
            return new WP_Error(
                'callback_api_failed',
                'Lead wurde angelegt, aber RingCX hat den Scheduled Agent Callback abgelehnt.',
                array( 'body' => $body_raw )
            );
        }

        return array(
            'response'  => $body,
            'raw_body'  => $body_raw,
        );
    }

}
