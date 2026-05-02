<?php
/**
 * Public-facing functionality for CallBack4RingCX.
 *
 * @package CallBack4RingCX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CallBack4RingCX_Public {

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
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$settings = $this->settings->get_settings();

		if ( '1' !== $settings['enabled'] ) {
			return;
		}

		$css_file = CALLBACK4RINGCX_PATH . 'assets/callback-widget.css';
		$js_file  = CALLBACK4RINGCX_PATH . 'assets/callback-widget.js';

		wp_enqueue_style(
			'callback4ringcx-widget',
			CALLBACK4RINGCX_URL . 'assets/callback-widget.css',
			array(),
			file_exists( $css_file ) ? filemtime( $css_file ) : CALLBACK4RINGCX_VERSION
		);

		wp_enqueue_script(
			'callback4ringcx-widget',
			CALLBACK4RINGCX_URL . 'assets/callback-widget.js',
			array(),
			file_exists( $js_file ) ? filemtime( $js_file ) : CALLBACK4RINGCX_VERSION,
			true
		);

		wp_localize_script(
			'callback4ringcx-widget',
			'callback4ringcxData',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'callback4ringcx_submit' ),
				'submitAction'     => 'callback4ringcx_submit',
				'loadAgentsAction' => 'callback4ringcx_load_agents',
				'successMessage'   => $settings['success_message'],
			)
		);
	}

	/**
	 * Render widget HTML in frontend footer.
	 *
	 * @return void
	 */
	public function render_widget() {
		$settings = $this->settings->get_settings();

		if ( '1' !== $settings['enabled'] ) {
			return;
		}

		?>
		<div class="callback4ringcx-widget" id="callback4ringcx-widget">
			<button
				class="callback4ringcx-trigger"
				id="callback4ringcx-trigger"
				aria-expanded="false"
				aria-controls="callback4ringcx-modal"
				type="button">
				<?php echo esc_html( $settings['button_label'] ); ?>
			</button>

			<div class="callback4ringcx-modal" id="callback4ringcx-modal" hidden>
				<div class="callback4ringcx-card">
					<button
						class="callback4ringcx-close"
						id="callback4ringcx-close"
						aria-label="<?php echo esc_attr__( 'Schließen', 'callback4ringcx' ); ?>"
						type="button">×</button>

					<h3><?php echo esc_html__( 'Rückruf anfordern', 'callback4ringcx' ); ?></h3>
					<p><?php echo esc_html__( 'Bitte hinterlassen Sie Ihre Daten. Wir melden uns zum Wunschtermin.', 'callback4ringcx' ); ?></p>

					<form id="callback4ringcx-form">
						<input
							type="text"
							name="website"
							class="callback4ringcx-honeypot"
							tabindex="-1"
							autocomplete="off">

						<div class="callback4ringcx-name-row">
							<div class="callback4ringcx-field">
								<label for="callback4ringcx-firstname"><?php echo esc_html__( 'Vorname', 'callback4ringcx' ); ?></label>
								<input type="text" id="callback4ringcx-firstname" name="firstname" required>
							</div>

							<div class="callback4ringcx-field">
								<label for="callback4ringcx-lastname"><?php echo esc_html__( 'Nachname', 'callback4ringcx' ); ?></label>
								<input type="text" id="callback4ringcx-lastname" name="lastname" required>
							</div>
						</div>

						<div class="callback4ringcx-field">
							<label for="callback4ringcx-phone"><?php echo esc_html__( 'Telefonnummer', 'callback4ringcx' ); ?></label>
							<input type="tel" id="callback4ringcx-phone" name="phone" required>
						</div>

						<div class="callback4ringcx-field">
							<label for="callback4ringcx-callback-date"><?php echo esc_html__( 'Wunschdatum', 'callback4ringcx' ); ?></label>
							<input type="date" id="callback4ringcx-callback-date" name="callback_date" required>
						</div>

						<div class="callback4ringcx-field">
							<label for="callback4ringcx-callback-time"><?php echo esc_html__( 'Wunschzeit', 'callback4ringcx' ); ?></label>
							<input type="time" id="callback4ringcx-callback-time" name="callback_time" required>
						</div>
						<div class="callback4ringcx-field">
    						<label for="callback4ringcx_target_type">Rückruf durch</label>
    						<select id="callback4ringcx_target_type" name="callback_target_type">
        						<option value="agent">Mitarbeiter</option>
        						<option value="group">Gruppe</option>
    						</select>
						</div>
						<div class="callback4ringcx-field">
							<label for="callback4ringcx-agent-id"><?php echo esc_html__( 'Gewünschter Ansprechpartner', 'callback4ringcx' ); ?></label>
							<select id="callback4ringcx-agent-id" name="agent_id">
								<option value=""><?php echo esc_html__( 'Bitte auswählen', 'callback4ringcx' ); ?></option>
							</select>
							<input type="hidden" id="callback4ringcx-agent-name" name="agent_name" value="">
						</div>

						<div class="callback4ringcx-field">
							<label for="callback4ringcx-note"><?php echo esc_html__( 'Nachricht', 'callback4ringcx' ); ?></label>
							<textarea id="callback4ringcx-note" name="note" rows="3" required></textarea>
						</div>

						<p class="callback4ringcx-privacy-text">
							<?php echo esc_html( $settings['privacy_text'] ); ?>
						</p>

						<div class="callback4ringcx-status" id="callback4ringcx-status" aria-live="polite"></div>

						<button type="submit" class="callback4ringcx-submit">
							<?php echo esc_html__( 'Absenden', 'callback4ringcx' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
