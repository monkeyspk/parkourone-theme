<?php
/**
 * Probetraining Link-Generator
 * Backend-Seite zum Erstellen vorgefertigter Filter-URLs fuer die Probetraining-Buchungsseite.
 */

function parkourone_probetraining_links_page() {
	// Taxonomie-Terms laden
	$age_colors = [
		'minis'   => '#ff9500',
		'kids'    => '#34c759',
		'juniors' => '#007aff',
		'adults'  => '#5856d6',
		'seniors' => '#af52de',
		'masters' => '#ff2d55'
	];

	$alter_parent = get_term_by('slug', 'alter', 'event_category');
	$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');

	$alter_terms = [];
	$ortschaft_terms = [];

	if ($alter_parent && !is_wp_error($alter_parent)) {
		$alter_terms = get_terms(['taxonomy' => 'event_category', 'parent' => $alter_parent->term_id, 'hide_empty' => false]);
		if (is_wp_error($alter_terms)) $alter_terms = [];
	}

	if ($ortschaft_parent && !is_wp_error($ortschaft_parent)) {
		$ortschaft_terms = get_terms(['taxonomy' => 'event_category', 'parent' => $ortschaft_parent->term_id, 'hide_empty' => false]);
		if (is_wp_error($ortschaft_terms)) $ortschaft_terms = [];
	}

	$base_url = home_url('/probetraining-buchen/');

	$weekdays = [
		1 => 'Montag',
		2 => 'Dienstag',
		3 => 'Mittwoch',
		4 => 'Donnerstag',
		5 => 'Freitag',
		6 => 'Samstag',
		0 => 'Sonntag',
	];

	?>
	<div class="wrap">
		<h1>Probetraining Links</h1>
		<p>Erstelle vorgefertigte Links zur Probetraining-Seite mit vorausgewählten Filtern.</p>

		<style>
			.po-links-admin { max-width: 720px; margin-top: 20px; }
			.po-links-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
			.po-links-section h3 { margin: 0 0 16px; font-size: 14px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; }
			.po-links-row { display: grid; grid-template-columns: 120px 1fr; gap: 10px; margin-bottom: 14px; align-items: center; }
			.po-links-row label { font-weight: 500; font-size: 13px; }
			.po-links-row select { max-width: 300px; width: 100%; }
			.po-links-result { margin-top: 20px; padding: 16px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 6px; }
			.po-links-result label { display: block; font-weight: 500; font-size: 12px; text-transform: uppercase; color: #646970; margin-bottom: 6px; letter-spacing: 0.5px; }
			.po-links-url-wrap { display: flex; gap: 8px; align-items: center; }
			.po-links-url { flex: 1; padding: 8px 12px; font-size: 13px; font-family: monospace; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; color: #1d2327; word-break: break-all; }
			.po-links-copy { padding: 8px 16px; background: #1d1d1f; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; white-space: nowrap; }
			.po-links-copy:hover { background: #2d2d2f; }
			.po-links-copy.is-copied { background: #34c759; }
			.po-links-preview { margin-top: 12px; }
			.po-links-preview a { font-size: 13px; color: #2271b1; text-decoration: none; }
			.po-links-preview a:hover { text-decoration: underline; }
			.po-links-hint { font-size: 12px; color: #646970; margin-top: 20px; line-height: 1.6; }
			.po-links-hint code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
		</style>

		<div class="po-links-admin">
			<div class="po-links-section">
				<h3>Filter wählen</h3>

				<div class="po-links-row">
					<label for="po-link-alter">Altersgruppe</label>
					<select id="po-link-alter">
						<option value="">– Alle –</option>
						<?php foreach ($alter_terms as $term): ?>
						<option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="po-links-row">
					<label for="po-link-standort">Standort</label>
					<select id="po-link-standort">
						<option value="">– Alle –</option>
						<?php foreach ($ortschaft_terms as $term): ?>
						<option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="po-links-row">
					<label for="po-link-tag">Wochentag</label>
					<select id="po-link-tag">
						<option value="">– Alle –</option>
						<?php foreach ($weekdays as $num => $name): ?>
						<option value="<?php echo esc_attr($num); ?>"><?php echo esc_html($name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="po-links-result">
					<label>Generierter Link</label>
					<div class="po-links-url-wrap">
						<input type="text" class="po-links-url" id="po-link-url" value="<?php echo esc_url($base_url); ?>" readonly>
						<button type="button" class="po-links-copy" id="po-link-copy">Kopieren</button>
					</div>
					<div class="po-links-preview">
						<a href="<?php echo esc_url($base_url); ?>" id="po-link-open" target="_blank">↗ Im Frontend öffnen</a>
					</div>
				</div>
			</div>

			<div class="po-links-hint">
				<strong>URL-Parameter:</strong><br>
				<code>alter</code> = Altersgruppen-Slug (z.B. kids, adults)<br>
				<code>standort</code> = Standort-Slug (z.B. berlin-mitte)<br>
				<code>tag</code> = Wochentag-Nummer (0=So, 1=Mo, 2=Di, 3=Mi, 4=Do, 5=Fr, 6=Sa)<br>
				Beispiel: <code><?php echo esc_html($base_url); ?>?alter=kids&standort=berlin-mitte&tag=3</code>
			</div>
		</div>

		<script>
		(function() {
			var baseUrl = <?php echo wp_json_encode($base_url); ?>;
			var alterSelect = document.getElementById('po-link-alter');
			var standortSelect = document.getElementById('po-link-standort');
			var tagSelect = document.getElementById('po-link-tag');
			var urlField = document.getElementById('po-link-url');
			var copyBtn = document.getElementById('po-link-copy');
			var openLink = document.getElementById('po-link-open');

			function updateUrl() {
				var params = [];
				if (alterSelect.value) params.push('alter=' + encodeURIComponent(alterSelect.value));
				if (standortSelect.value) params.push('standort=' + encodeURIComponent(standortSelect.value));
				if (tagSelect.value !== '') params.push('tag=' + encodeURIComponent(tagSelect.value));
				var url = baseUrl + (params.length ? '?' + params.join('&') : '');
				urlField.value = url;
				openLink.href = url;
			}

			alterSelect.addEventListener('change', updateUrl);
			standortSelect.addEventListener('change', updateUrl);
			tagSelect.addEventListener('change', updateUrl);

			copyBtn.addEventListener('click', function() {
				navigator.clipboard.writeText(urlField.value).then(function() {
					copyBtn.textContent = 'Kopiert!';
					copyBtn.classList.add('is-copied');
					setTimeout(function() {
						copyBtn.textContent = 'Kopieren';
						copyBtn.classList.remove('is-copied');
					}, 2000);
				});
			});
		})();
		</script>
	</div>
	<?php
}
