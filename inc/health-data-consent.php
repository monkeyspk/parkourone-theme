<?php
/**
 * Gesundheitsdaten-Einwilligung (DSGVO Art. 9)
 *
 * Stellt eine separate, ausdrückliche Einwilligung für die Verarbeitung
 * von Gesundheitsdaten bereit (besondere Kategorien personenbezogener Daten).
 *
 * Verwendung:
 * - Als Shortcode: [parkourone_health_consent]
 * - Als Template-Funktion: parkourone_render_health_consent()
 * - In Formularen: parkourone_health_consent_checkbox()
 */

defined('ABSPATH') || exit;

/**
 * Rendert das vollständige Gesundheitsdaten-Einwilligungsformular
 */
function parkourone_render_health_consent($args = []) {
	$defaults = [
		'form_context' => 'vertrag',
		'show_details' => true,
		'required'     => true,
	];
	$args = wp_parse_args($args, $defaults);

	ob_start();
	?>
	<div class="po-health-consent" role="group" aria-labelledby="po-health-consent-title">
		<div class="po-health-consent__header">
			<h3 id="po-health-consent-title" class="po-health-consent__title">Einwilligung zur Verarbeitung von Gesundheitsdaten</h3>
			<p class="po-health-consent__subtitle">Gemäß Art. 9 Abs. 2 lit. a DSGVO</p>
		</div>

		<?php if ($args['show_details']): ?>
		<div class="po-health-consent__info">
			<p>Im Rahmen Ihres Vertrags mit ParkourONE erheben wir folgende gesundheitsbezogene Daten:</p>
			<ul>
				<li>Angaben zu bestehenden Vorerkrankungen oder Einschränkungen, die für die sichere Trainingsteilnahme relevant sind</li>
				<li>Informationen zu Allergien oder medizinischen Besonderheiten</li>
				<li>Ggf. ärztliche Bescheinigungen zur Sporttauglichkeit</li>
			</ul>

			<div class="po-health-consent__purpose">
				<strong>Zweck der Verarbeitung:</strong>
				<p>Diese Daten werden ausschließlich verwendet, um:</p>
				<ul>
					<li>Ihre Sicherheit während des Trainings zu gewährleisten</li>
					<li>Das Training an Ihre individuellen Bedürfnisse anzupassen</li>
					<li>Im Notfall angemessen reagieren zu können</li>
				</ul>
			</div>

			<div class="po-health-consent__storage">
				<strong>Speicherung und Löschung:</strong>
				<p>Ihre Gesundheitsdaten werden für die Dauer der Vertragslaufzeit gespeichert und spätestens 6 Monate nach Vertragsende gelöscht, sofern keine gesetzlichen Aufbewahrungspflichten bestehen.</p>
			</div>

			<div class="po-health-consent__rights">
				<strong>Ihre Rechte:</strong>
				<p>Sie können diese Einwilligung jederzeit ohne Angabe von Gründen widerrufen. Der Widerruf berührt nicht die Rechtmäßigkeit der bis dahin erfolgten Verarbeitung. Zum Widerruf genügt eine formlose Mitteilung an uns.</p>
			</div>

			<div class="po-health-consent__hosting">
				<strong>Verarbeitungsort:</strong>
				<p>Ihre Daten werden auf Servern in der Schweiz verarbeitet (Angemessenheitsbeschluss der EU-Kommission gem. Art. 45 DSGVO).</p>
			</div>
		</div>
		<?php endif; ?>

		<div class="po-health-consent__checkbox-wrapper">
			<label class="po-health-consent__label" for="po-health-consent-check">
				<input
					type="checkbox"
					id="po-health-consent-check"
					name="health_data_consent"
					value="1"
					<?php echo $args['required'] ? 'required aria-required="true"' : ''; ?>
					class="po-health-consent__input"
				>
				<span class="po-health-consent__checkmark"></span>
				<span class="po-health-consent__text">
					Ich willige ausdrücklich in die Verarbeitung meiner Gesundheitsdaten durch ParkourONE zum oben genannten Zweck ein.
					Mir ist bewusst, dass es sich um besondere Kategorien personenbezogener Daten handelt und ich diese Einwilligung
					jederzeit widerrufen kann.
					<?php if ($args['required']): ?>
					<span class="po-health-consent__required">*</span>
					<?php endif; ?>
				</span>
			</label>
		</div>

		<input type="hidden" name="health_consent_timestamp" value="">
		<input type="hidden" name="health_consent_context" value="<?php echo esc_attr($args['form_context']); ?>">
	</div>

	<script>
	(function() {
		var wrapper = document.querySelector('.po-health-consent');
		if (!wrapper) return;
		var checkbox = wrapper.querySelector('#po-health-consent-check');
		var timestamp = wrapper.querySelector('[name="health_consent_timestamp"]');
		if (checkbox && timestamp) {
			checkbox.addEventListener('change', function() {
				timestamp.value = this.checked ? new Date().toISOString() : '';
			});
		}
	})();
	</script>
	<?php
	return ob_get_clean();
}

/**
 * Rendert nur die Checkbox (für Einbettung in bestehende Formulare)
 */
function parkourone_health_consent_checkbox($required = true) {
	ob_start();
	?>
	<div class="po-health-consent__field">
		<label class="po-health-consent__label" for="po-health-consent-inline">
			<input
				type="checkbox"
				id="po-health-consent-inline"
				name="health_data_consent"
				value="1"
				<?php echo $required ? 'required aria-required="true"' : ''; ?>
			>
			<span>
				Ich willige in die Verarbeitung meiner Gesundheitsdaten gemäß <a href="<?php echo esc_url(get_privacy_policy_url() ?: '/datenschutz/'); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a> ein.
				<?php if ($required): ?><span class="po-health-consent__required">*</span><?php endif; ?>
			</span>
		</label>
		<input type="hidden" name="health_consent_timestamp" value="">
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Shortcode: [parkourone_health_consent]
 */
function parkourone_health_consent_shortcode($atts) {
	$atts = shortcode_atts([
		'context'      => 'vertrag',
		'show_details' => 'true',
		'required'     => 'true',
	], $atts);

	return parkourone_render_health_consent([
		'form_context' => $atts['context'],
		'show_details' => $atts['show_details'] === 'true',
		'required'     => $atts['required'] === 'true',
	]);
}
add_shortcode('parkourone_health_consent', 'parkourone_health_consent_shortcode');

/**
 * Validiert die Gesundheitsdaten-Einwilligung bei Formular-Submission
 */
function parkourone_validate_health_consent() {
	if (isset($_POST['health_data_consent']) && $_POST['health_data_consent'] === '1') {
		return [
			'valid'     => true,
			'timestamp' => sanitize_text_field($_POST['health_consent_timestamp'] ?? ''),
			'context'   => sanitize_text_field($_POST['health_consent_context'] ?? 'unknown'),
		];
	}
	return ['valid' => false];
}
