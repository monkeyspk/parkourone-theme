<?php
$formType       = $attributes['formType'] ?? 'verletzungen';
$recipientEmail = $attributes['recipientEmail'] ?? '';
$bgVariant      = $attributes['backgroundColor'] ?? 'white';

// Defaults pro Formular-Typ
$type_defaults = [
	'verletzungen' => [
		'headline'    => 'Rückerstattung bei Verletzung',
		'description' => 'Du hast dich verletzt und kannst mindestens 30 Tage nicht trainieren? Reiche hier deinen Antrag auf Rückerstattung ein.',
		'buttonText'  => 'Antrag einreichen',
		'icon'        => '<svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
	],
	'ahv' => [
		'headline'    => 'AHV-Nummer melden',
		'description' => 'Für das J+S Programm benötigen wir deine AHV-Nummer. Melde sie hier sicher und unkompliziert.',
		'buttonText'  => 'AHV-Nummer senden',
		'icon'        => '<svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
	],
];

$d = $type_defaults[$formType] ?? $type_defaults['verletzungen'];

$headline    = !empty($attributes['headline']) ? $attributes['headline'] : $d['headline'];
$description = !empty($attributes['description']) ? $attributes['description'] : $d['description'];
$buttonText  = !empty($attributes['buttonText']) ? $attributes['buttonText'] : $d['buttonText'];
$icon        = $d['icon'];

// Math Captcha
$captcha_a = wp_rand(1, 15);
$captcha_b = wp_rand(1, 15);
$captcha_answer = $captcha_a + $captcha_b;
$captcha_hash = wp_hash($captcha_answer . 'po_member_captcha_salt');

$nonce = wp_create_nonce('po_member_form_nonce');
$unique_id = 'po-memberform-' . uniqid();

$section_classes = ['po-memberform', 'po-memberform--bg-' . $bgVariant];
if (!empty($attributes['align'])) {
	$section_classes[] = 'align' . $attributes['align'];
}
?>

<?php
// Stabiler Hash-Anchor für Deep-Links (z.B. /infos/#rueckerstattung)
$hash_map = [
	'verletzungen' => 'rueckerstattung',
	'ahv'          => 'ahv',
];
$form_hash = $hash_map[$formType] ?? $formType;
?>
<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="<?php echo esc_attr($unique_id); ?>" data-form-hash="<?php echo esc_attr($form_hash); ?>">
	<!-- Karte -->
	<div class="po-memberform__card">
		<div class="po-memberform__card-icon" aria-hidden="true">
			<?php echo $icon; ?>
		</div>
		<h3 class="po-memberform__card-title"><?php echo esc_html($headline); ?></h3>
		<p class="po-memberform__card-desc"><?php echo esc_html($description); ?></p>
		<button type="button" class="po-memberform__card-btn" data-modal-target="<?php echo esc_attr($unique_id); ?>-modal">
			<?php echo esc_html($buttonText); ?>
		</button>
	</div>

	<!-- Modal -->
	<div class="po-memberform__modal" id="<?php echo esc_attr($unique_id); ?>-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($unique_id); ?>-modal-title" hidden>
		<div class="po-memberform__modal-backdrop"></div>
		<div class="po-memberform__modal-container">
			<div class="po-memberform__modal-header">
				<h2 class="po-memberform__modal-title" id="<?php echo esc_attr($unique_id); ?>-modal-title"><?php echo esc_html($headline); ?></h2>
				<button type="button" class="po-memberform__modal-close" aria-label="Schliessen">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<div class="po-memberform__modal-body">
				<form class="po-memberform__form" data-form-type="<?php echo esc_attr($formType); ?>" novalidate>
					<input type="hidden" name="action" value="po_member_form_submit">
					<input type="hidden" name="_nonce" value="<?php echo esc_attr($nonce); ?>">
					<input type="hidden" name="form_type" value="<?php echo esc_attr($formType); ?>">
					<input type="hidden" name="recipient_email" value="<?php echo esc_attr($recipientEmail); ?>">
					<input type="hidden" name="captcha_hash" value="<?php echo esc_attr($captcha_hash); ?>">

					<!-- Honeypot -->
					<div class="po-memberform__hp" aria-hidden="true" tabindex="-1">
						<label for="<?php echo esc_attr($unique_id); ?>_website">Website</label>
						<input type="text" name="po_website" id="<?php echo esc_attr($unique_id); ?>_website" autocomplete="off" tabindex="-1">
					</div>

					<div class="po-memberform__grid">
						<?php if ($formType === 'verletzungen'): ?>

							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_name">Name <span class="required">*</span></label>
								<input type="text" name="name" id="<?php echo esc_attr($unique_id); ?>_name" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_vorname">Vorname <span class="required">*</span></label>
								<input type="text" name="vorname" id="<?php echo esc_attr($unique_id); ?>_vorname" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_plz">PLZ <span class="required">*</span></label>
								<input type="text" name="plz" id="<?php echo esc_attr($unique_id); ?>_plz" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_ort">Ort <span class="required">*</span></label>
								<input type="text" name="ort" id="<?php echo esc_attr($unique_id); ?>_ort" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_email">E-Mail <span class="required">*</span></label>
								<input type="email" name="email" id="<?php echo esc_attr($unique_id); ?>_email" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_klasse">Deine Klasse <span class="required">*</span></label>
								<select name="klasse" id="<?php echo esc_attr($unique_id); ?>_klasse" required>
									<option value="">Bitte wählen</option>
									<option value="Kids">Kids</option>
									<option value="Juniors">Juniors</option>
									<option value="Adults">Adults</option>
									<option value="Seniors">Seniors</option>
									<option value="Masters">Masters</option>
								</select>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_beginn">Beginn Trainingsausfall <span class="required">*</span></label>
								<input type="date" name="beginn" id="<?php echo esc_attr($unique_id); ?>_beginn" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_ende">Ende Trainingsausfall <span class="required">*</span></label>
								<input type="date" name="ende" id="<?php echo esc_attr($unique_id); ?>_ende" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_iban">IBAN <span class="required">*</span></label>
								<input type="text" name="iban" id="<?php echo esc_attr($unique_id); ?>_iban" required placeholder="z.B. CH93 0076 2011 6238 5295 7">
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_kontoinhaber">Kontoinhaber <span class="required">*</span></label>
								<input type="text" name="kontoinhaber" id="<?php echo esc_attr($unique_id); ?>_kontoinhaber" required>
							</div>
							<div class="po-memberform__field po-memberform__field--full">
								<label for="<?php echo esc_attr($unique_id); ?>_sportdispens">Arztzeugnis / Sportdispens hochladen <span class="optional">(PDF, JPG, PNG – max. 64 MB)</span></label>
								<input type="file" name="sportdispens" id="<?php echo esc_attr($unique_id); ?>_sportdispens" accept=".jpg,.jpeg,.png,.gif,.pdf,application/pdf">
								<span class="po-memberform__field-hint">Das Arztzeugnis muss mindestens 30 Tage Trainingsausfall bescheinigen. Pro 30 Tage wird ein Monat rückerstattet. Es werden nur Arztzeugnisse akzeptiert, die maximal 30 Tage zurückliegen.</span>
							</div>

							<!-- Checkboxen -->
							<div class="po-memberform__field po-memberform__field--full po-memberform__field--checkbox">
								<label>
									<input type="checkbox" name="agb" value="1" required>
									<span>Ich akzeptiere die <a href="/datenschutz/" target="_blank">Datenschutzerklärung</a> und <a href="/impressum/" target="_blank">AGB</a>. <span class="required">*</span></span>
								</label>
							</div>
							<div class="po-memberform__field po-memberform__field--full po-memberform__field--checkbox">
								<label>
									<input type="checkbox" name="versicherung" value="1" required>
									<span>Ich bestätige, dass meine Versicherung keine Rückerstattung leistet. <span class="required">*</span></span>
								</label>
							</div>

						<?php else: ?>

							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_name">Name <span class="required">*</span></label>
								<input type="text" name="name" id="<?php echo esc_attr($unique_id); ?>_name" required>
							</div>
							<div class="po-memberform__field">
								<label for="<?php echo esc_attr($unique_id); ?>_vorname">Vorname <span class="required">*</span></label>
								<input type="text" name="vorname" id="<?php echo esc_attr($unique_id); ?>_vorname" required>
							</div>
							<div class="po-memberform__field po-memberform__field--full">
								<label for="<?php echo esc_attr($unique_id); ?>_ahv">AHV-Nummer <span class="required">*</span></label>
								<input type="text" name="ahv_nummer" id="<?php echo esc_attr($unique_id); ?>_ahv" required placeholder="756.XXXX.XXXX.XX" pattern="756\.\d{4}\.\d{4}\.\d{2}">
								<span class="po-memberform__field-hint">13-stellig, beginnt mit 756 (z.B. 756.1234.5678.90)</span>
							</div>

						<?php endif; ?>

						<!-- Math Captcha -->
						<div class="po-memberform__field po-memberform__field--captcha">
							<label for="<?php echo esc_attr($unique_id); ?>_captcha"><?php echo esc_html($captcha_a . ' + ' . $captcha_b); ?> = <span class="required">*</span></label>
							<input type="number" name="captcha" id="<?php echo esc_attr($unique_id); ?>_captcha" required autocomplete="off">
						</div>
					</div>

					<div class="po-memberform__submit">
						<button type="submit" class="po-memberform__button">
							<span class="po-memberform__button-text"><?php echo esc_html($buttonText); ?></span>
							<span class="po-memberform__button-loading" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m11.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
							</span>
						</button>
					</div>

					<div class="po-memberform__message" aria-live="polite" hidden></div>
				</form>
			</div>
		</div>
	</div>
</section>
