<?php
/**
 * Settings page view.
 *
 * Available variables:
 * - array $settings
 * - array $campaign_options
 * - array $agent_group_options
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$option_key = $this->settings->get_option_key();
?>

<div class="wrap">
<h1><?php echo esc_html__( 'CallBack4RingCX', 'callback4ringcx' ); ?></h1>

<form method="post" action="options.php">
<?php settings_fields( 'callback4ringcx_group' ); ?>

<table class="form-table" role="presentation">
<tr>
<th scope="row"><?php echo esc_html__( 'Widget aktiv', 'callback4ringcx' ); ?></th>
<td>
<label>
<input
type="checkbox"
name="<?php echo esc_attr( $option_key ); ?>[enabled]"
value="1"
<?php checked( $settings['enabled'], '1' ); ?>>
<?php echo esc_html__( 'Aktivieren', 'callback4ringcx' ); ?>
</label>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-button-label"><?php echo esc_html__( 'Button-Text', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="text"
class="regular-text"
id="callback4ringcx-button-label"
name="<?php echo esc_attr( $option_key ); ?>[button_label]"
value="<?php echo esc_attr( $settings['button_label'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-success-message"><?php echo esc_html__( 'Success-Text', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="text"
class="regular-text"
id="callback4ringcx-success-message"
name="<?php echo esc_attr( $option_key ); ?>[success_message]"
value="<?php echo esc_attr( $settings['success_message'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-base-url"><?php echo esc_html__( 'RingCX Base URL', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="url"
class="regular-text"
id="callback4ringcx-base-url"
name="<?php echo esc_attr( $option_key ); ?>[base_url]"
value="<?php echo esc_attr( $settings['base_url'] ); ?>">
<p class="description"><?php echo esc_html__( 'Standard: https://ringcx.ringcentral.com/voice/api/v1', 'callback4ringcx' ); ?></p>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-client-id"><?php echo esc_html__( 'Client ID', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="password"
class="regular-text"
id="callback4ringcx-client-id"
name="<?php echo esc_attr( $option_key ); ?>[client_id]"
value="<?php echo esc_attr( $settings['client_id'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-client-secret"><?php echo esc_html__( 'Client Secret', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="password"
class="regular-text"
id="callback4ringcx-client-secret"
name="<?php echo esc_attr( $option_key ); ?>[client_secret]"
value="<?php echo esc_attr( $settings['client_secret'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-assertion"><?php echo esc_html__( 'Assertion', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="password"
class="large-text"
id="callback4ringcx-assertion"
name="<?php echo esc_attr( $option_key ); ?>[assertion]"
value="<?php echo esc_attr( $settings['assertion'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-account-id"><?php echo esc_html__( 'Account ID', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="text"
class="regular-text"
id="callback4ringcx-account-id"
name="<?php echo esc_attr( $option_key ); ?>[account_id]"
value="<?php echo esc_attr( $settings['account_id'] ); ?>"
readonly>
<p class="description"><?php echo esc_html__( 'Wird nach dem Speichern der API-Zugangsdaten automatisch übernommen.', 'callback4ringcx' ); ?></p>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-campaign-id"><?php echo esc_html__( 'Campaign', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-campaign-id"
name="<?php echo esc_attr( $option_key ); ?>[campaign_id]">
<option value=""><?php echo esc_html__( 'Bitte auswählen', 'callback4ringcx' ); ?></option>
<?php foreach ( $campaign_options as $campaign ) : ?>
<option
value="<?php echo esc_attr( $campaign['id'] ); ?>"
<?php selected( $settings['campaign_id'], (string) $campaign['id'] ); ?>>
<?php
echo esc_html(
    $campaign['name'] .
    ' (' . $campaign['id'] . ')' .
    ( ! empty( $campaign['dial_group_name'] ) ? ' – ' . $campaign['dial_group_name'] : '' )
);
?>
</option>
<?php endforeach; ?>
</select>

<?php if ( empty( $campaign_options ) ) : ?>
<p class="description">
<?php echo esc_html__( 'Keine Campaigns gefunden. Bitte zuerst Zugangsdaten speichern und prüfen, ob Dial Groups und Campaigns verfügbar sind.', 'callback4ringcx' ); ?>
</p>
<?php else : ?>
<p class="description">
<?php echo esc_html__( 'Die Liste wird automatisch aus RingCX geladen.', 'callback4ringcx' ); ?>
</p>
<?php endif; ?>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-description"><?php echo esc_html__( 'Description', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="text"
class="regular-text"
id="callback4ringcx-description"
name="<?php echo esc_attr( $option_key ); ?>[description]"
value="<?php echo esc_attr( $settings['description'] ); ?>">
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-dial-priority"><?php echo esc_html__( 'Dial Priority', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-dial-priority"
name="<?php echo esc_attr( $option_key ); ?>[dial_priority]">
<option value="IMMEDIATE" <?php selected( $settings['dial_priority'], 'IMMEDIATE' ); ?>>IMMEDIATE</option>
<option value="NORMAL" <?php selected( $settings['dial_priority'], 'NORMAL' ); ?>>NORMAL</option>
</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-duplicate-handling"><?php echo esc_html__( 'Duplicate Handling', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-duplicate-handling"
name="<?php echo esc_attr( $option_key ); ?>[duplicate_handling]">
<option value="REMOVE_FROM_LIST" <?php selected( $settings['duplicate_handling'], 'REMOVE_FROM_LIST' ); ?>>REMOVE_FROM_LIST</option>
<option value="RETAIN_ALL" <?php selected( $settings['duplicate_handling'], 'RETAIN_ALL' ); ?>>RETAIN_ALL</option>
</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-list-state"><?php echo esc_html__( 'List State', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-list-state"
name="<?php echo esc_attr( $option_key ); ?>[list_state]">
<option value="ACTIVE" <?php selected( $settings['list_state'], 'ACTIVE' ); ?>>ACTIVE</option>
</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-timezone-option"><?php echo esc_html__( 'Time Zone Option', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-timezone-option"
name="<?php echo esc_attr( $option_key ); ?>[timezone_option]">
<option value="EXPLICIT" <?php selected( $settings['timezone_option'], 'EXPLICIT' ); ?>>EXPLICIT</option>
<option value="NPA_NXX" <?php selected( $settings['timezone_option'], 'NPA_NXX' ); ?>>NPA_NXX</option>
<option value="ZIPCODE" <?php selected( $settings['timezone_option'], 'ZIPCODE' ); ?>>ZIPCODE</option>
<option value="NOT_APPLICABLE" <?php selected( $settings['timezone_option'], 'NOT_APPLICABLE' ); ?>>NOT_APPLICABLE</option>
</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-lead-timezone"><?php echo esc_html__( 'Lead Timezone', 'callback4ringcx' ); ?></label>
</th>
<td>
<input
type="text"
class="regular-text"
id="callback4ringcx-lead-timezone"
name="<?php echo esc_attr( $option_key ); ?>[lead_timezone]"
value="<?php echo esc_attr( $settings['lead_timezone'] ); ?>">
</td>
</tr>

<tr>
<th scope="row"><?php echo esc_html__( 'Phone Numbers I18n Enabled', 'callback4ringcx' ); ?></th>
<td>
<label>
<input
type="checkbox"
name="<?php echo esc_attr( $option_key ); ?>[phone_numbers_i18n_enabled]"
value="1"
<?php checked( $settings['phone_numbers_i18n_enabled'], '1' ); ?>>
<?php echo esc_html__( 'Aktivieren', 'callback4ringcx' ); ?>
</label>
</td>
</tr>

<tr>
<th scope="row"><?php echo esc_html__( 'International Number Format', 'callback4ringcx' ); ?></th>
<td>
<label>
<input
type="checkbox"
name="<?php echo esc_attr( $option_key ); ?>[international_number_format]"
value="1"
<?php checked( $settings['international_number_format'], '1' ); ?>>
<?php echo esc_html__( 'Aktivieren', 'callback4ringcx' ); ?>
</label>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-agent-group-id"><?php echo esc_html__( 'Agent Group', 'callback4ringcx' ); ?></label>
</th>
<td>
<select
id="callback4ringcx-agent-group-id"
name="<?php echo esc_attr( $option_key ); ?>[agent_group_id]">
<option value=""><?php echo esc_html__( 'Bitte auswählen', 'callback4ringcx' ); ?></option>
<?php foreach ( $agent_group_options as $group ) : ?>
<option
value="<?php echo esc_attr( $group['id'] ); ?>"
<?php selected( $settings['agent_group_id'], (string) $group['id'] ); ?>>
<?php
echo esc_html(
    $group['name'] .
    ' (' . $group['id'] . ')' .
    ( ! empty( $group['is_default'] ) ? ' – Default' : '' )
);
?>
</option>
<?php endforeach; ?>
</select>

<?php if ( empty( $agent_group_options ) ) : ?>
<p class="description">
<?php echo esc_html__( 'Keine Agent Groups gefunden. Bitte zuerst Zugangsdaten speichern und prüfen, ob Agent Groups im Account vorhanden sind.', 'callback4ringcx' ); ?>
</p>
<?php else : ?>
<p class="description">
<?php echo esc_html__( 'Die Liste wird automatisch aus RingCX geladen.', 'callback4ringcx' ); ?>
</p>
<?php endif; ?>
</td>
</tr>

<tr>
<th scope="row">
<label for="callback4ringcx-privacy-text"><?php echo esc_html__( 'Privacy Text', 'callback4ringcx' ); ?></label>
</th>
<td>
<textarea
class="large-text"
rows="3"
id="callback4ringcx-privacy-text"
name="<?php echo esc_attr( $option_key ); ?>[privacy_text]"><?php echo esc_textarea( $settings['privacy_text'] ); ?></textarea>
</td>
</tr>
</table>

<?php submit_button(); ?>

    <p>
    <a
        href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=callback4ringcx_test_connection' ), 'callback4ringcx_test_connection' ) ); ?>"
        class="button button-secondary">
        <?php echo esc_html__( 'Verbindung prüfen / Account ID laden', 'callback4ringcx' ); ?>
    </a>

    </p>
</form>
</div>
