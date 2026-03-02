<?php
$headline        = $attributes['headline'] ?? 'Jetzt anfragen';
$subtext         = $attributes['subtext'] ?? '';
$formType        = $attributes['formType'] ?? 'workshop';
$recipientEmail  = $attributes['recipientEmail'] ?? '';
$showLocation    = $attributes['showLocation'] ?? true;
$showParticipants = $attributes['showParticipants'] ?? true;
$showDates       = $attributes['showDates'] ?? true;
$showProjectLength = $attributes['showProjectLength'] ?? false;
$showClassCount  = $attributes['showClassCount'] ?? false;
$submitText      = $attributes['submitText'] ?? 'Anfrage senden';
$bgVariant = $attributes['backgroundColor'] ?? 'dark';
$bgClasses = ['dark' => 'po-inquiry--dark', 'light' => 'po-inquiry--light', 'white' => 'po-inquiry--white'];
$bgClass   = $bgClasses[$bgVariant] ?? 'po-inquiry--dark';

// Math captcha
$captcha_a = wp_rand(1, 15);
$captcha_b = wp_rand(1, 15);
$captcha_answer = $captcha_a + $captcha_b;
$captcha_hash = wp_hash($captcha_answer . 'po_captcha_salt');

$nonce = wp_create_nonce('po_inquiry_nonce');
?>
<section class="po-inquiry alignfull <?php echo esc_attr($bgClass); ?>" id="anfrage">
	<div class="po-inquiry__inner">
		<?php if ($headline): ?>
			<h2 class="po-inquiry__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<?php if ($subtext): ?>
			<p class="po-inquiry__subtext"><?php echo wp_kses_post($subtext); ?></p>
		<?php endif; ?>

		<form class="po-inquiry__form" data-form-type="<?php echo esc_attr($formType); ?>" data-recipient="<?php echo esc_attr($recipientEmail); ?>" novalidate>
			<input type="hidden" name="action" value="po_inquiry_submit">
			<input type="hidden" name="_nonce" value="<?php echo esc_attr($nonce); ?>">
			<input type="hidden" name="form_type" value="<?php echo esc_attr($formType); ?>">
			<input type="hidden" name="recipient_email" value="<?php echo esc_attr($recipientEmail); ?>">
			<input type="hidden" name="captcha_hash" value="<?php echo esc_attr($captcha_hash); ?>">

			<!-- Honeypot -->
			<div class="po-inquiry__hp" aria-hidden="true" tabindex="-1">
				<label for="po_website">Website</label>
				<input type="text" name="po_website" id="po_website" autocomplete="off" tabindex="-1">
			</div>

			<div class="po-inquiry__grid">
				<!-- Feste Felder -->
				<div class="po-inquiry__field">
					<label for="po_nachname">Nachname <span class="required">*</span></label>
					<input type="text" name="nachname" id="po_nachname" required>
				</div>
				<div class="po-inquiry__field">
					<label for="po_vorname">Vorname <span class="required">*</span></label>
					<input type="text" name="vorname" id="po_vorname" required>
				</div>
				<div class="po-inquiry__field">
					<label for="po_adresse">Adresse <span class="required">*</span></label>
					<input type="text" name="adresse" id="po_adresse" required>
				</div>
				<div class="po-inquiry__field">
					<label for="po_plz_ort">PLZ / Ort <span class="required">*</span></label>
					<input type="text" name="plz_ort" id="po_plz_ort" required>
				</div>
				<div class="po-inquiry__field">
					<label for="po_telefon">Telefon <span class="required">*</span></label>
					<input type="tel" name="telefon" id="po_telefon" required>
				</div>
				<div class="po-inquiry__field">
					<label for="po_email">E-Mail <span class="required">*</span></label>
					<input type="email" name="email" id="po_email" required>
				</div>

				<!-- Optionale Felder -->
				<?php if ($showLocation): ?>
				<div class="po-inquiry__field">
					<label for="po_ort">Gewünschter Ort</label>
					<input type="text" name="ort" id="po_ort">
				</div>
				<?php endif; ?>

				<?php if ($showParticipants): ?>
				<div class="po-inquiry__field">
					<label for="po_teilnehmer">Anzahl Personen</label>
					<input type="number" name="teilnehmer" id="po_teilnehmer" min="1">
				</div>
				<?php endif; ?>

				<?php if ($showDates): ?>
				<div class="po-inquiry__field">
					<label for="po_datum">Gewünschte Daten</label>
					<input type="text" name="datum" id="po_datum" placeholder="z.B. März 2026, flexibel">
				</div>
				<?php endif; ?>

				<?php if ($showProjectLength): ?>
				<div class="po-inquiry__field">
					<label for="po_projektlaenge">Länge des Projektes</label>
					<select name="projektlaenge" id="po_projektlaenge">
						<option value="">Bitte wählen</option>
						<option value="einzeltermin">Einzeltermin</option>
						<option value="projektwoche">Projektwoche (5 Tage)</option>
						<option value="semester">Semesterprojekt</option>
						<option value="schuljahr">Schuljahr</option>
						<option value="andere">Andere</option>
					</select>
				</div>
				<?php endif; ?>

				<?php if ($showClassCount): ?>
				<div class="po-inquiry__field">
					<label for="po_klassen">Anzahl Klassen</label>
					<input type="number" name="klassen" id="po_klassen" min="1">
				</div>
				<?php endif; ?>

				<!-- Textarea über volle Breite -->
				<div class="po-inquiry__field po-inquiry__field--full">
					<label for="po_nachricht">Weitere Infos</label>
					<textarea name="nachricht" id="po_nachricht" rows="4"></textarea>
				</div>

				<!-- Math Captcha -->
				<div class="po-inquiry__field po-inquiry__field--captcha">
					<label for="po_captcha"><?php echo esc_html($captcha_a . ' + ' . $captcha_b); ?> = <span class="required">*</span></label>
					<input type="number" name="captcha" id="po_captcha" required autocomplete="off">
				</div>

				<!-- AGB Checkbox -->
				<div class="po-inquiry__field po-inquiry__field--full po-inquiry__field--checkbox">
					<label>
						<input type="checkbox" name="agb" value="1" required>
						<span>Ich akzeptiere die <a href="/datenschutz/" target="_blank">Datenschutzerklärung</a> und <a href="/impressum/" target="_blank">AGB</a>. <span class="required">*</span></span>
					</label>
				</div>
			</div>

			<div class="po-inquiry__submit">
				<button type="submit" class="po-inquiry__button">
					<span class="po-inquiry__button-text"><?php echo esc_html($submitText); ?></span>
					<span class="po-inquiry__button-loading" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m11.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
					</span>
				</button>
			</div>

			<div class="po-inquiry__message" aria-live="polite" hidden></div>
		</form>
	</div>
</section>
