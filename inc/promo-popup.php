<?php
/**
 * ParkourONE Promo Popup
 * Slide-in Banner unten rechts mit Admin-Verwaltung
 */

if (!defined('ABSPATH')) exit;

/**
 * Admin-Seite: Promo Popup Einstellungen
 */
function parkourone_promo_popup_page() {
	// Speichern
	if (isset($_POST['parkourone_promo_popup_save']) && check_admin_referer('parkourone_promo_popup_nonce')) {
		$current = get_option('parkourone_promo_popup', []);
		$old_version = isset($current['version']) ? (int) $current['version'] : 0;

		$options = [
			'enabled'       => !empty($_POST['promo_enabled']),
			'image_id'      => absint($_POST['promo_image_id'] ?? 0),
			'image_url'     => esc_url_raw($_POST['promo_image_url'] ?? ''),
			'title'         => wp_kses($_POST['promo_title'] ?? '', ['mark' => []]),
			'description'   => sanitize_text_field($_POST['promo_description'] ?? ''),
			'button_text'   => sanitize_text_field($_POST['promo_button_text'] ?? ''),
			'button_url'    => esc_url_raw($_POST['promo_button_url'] ?? ''),
			'delay_seconds' => max(1, absint($_POST['promo_delay_seconds'] ?? 5)),
			'version'       => $old_version + 1,
		];

		update_option('parkourone_promo_popup', $options);
		echo '<div class="notice notice-success"><p>Promo Popup gespeichert! (Version ' . esc_html($options['version']) . ')</p></div>';
	}

	// Aktuelle Werte laden
	$options = get_option('parkourone_promo_popup', []);
	$defaults = [
		'enabled'       => false,
		'image_id'      => 0,
		'image_url'     => '',
		'title'         => '',
		'description'   => '',
		'button_text'   => '',
		'button_url'    => '',
		'delay_seconds' => 5,
		'version'       => 1,
	];
	$options = wp_parse_args($options, $defaults);

	// Media Library einbinden
	wp_enqueue_media();
	?>
	<div class="wrap">
		<h1>Promo Popup</h1>
		<p>Slide-in Banner unten rechts. Erscheint nach einer konfigurierbaren Verzögerung und kann vom Besucher geschlossen werden.</p>

		<style>
			.po-promo-admin { max-width: 700px; margin-top: 20px; }
			.po-promo-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
			.po-promo-section h3 { margin: 0 0 15px; font-size: 14px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; }
			.po-form-row { display: grid; grid-template-columns: 160px 1fr; gap: 10px; margin-bottom: 15px; align-items: start; }
			.po-form-row label { font-weight: 500; padding-top: 8px; }
			.po-form-row input[type="text"],
			.po-form-row input[type="url"],
			.po-form-row input[type="number"] { width: 100%; max-width: 400px; }
			.po-image-preview { margin-top: 10px; }
			.po-image-preview img { max-width: 300px; height: auto; border-radius: 8px; border: 1px solid #ddd; }
			.po-toggle-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding: 15px; background: #f0f6fc; border-radius: 8px; border-left: 4px solid #2271b1; }
			.po-toggle-row.is-active { background: #edf7ee; border-left-color: #00a32a; }
			.po-preview-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 8px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 6px; text-decoration: none; color: #1d2327; font-size: 13px; }
			.po-preview-link:hover { background: #e0e0e1; color: #1d2327; }
			.po-hint { font-size: 13px; color: #646970; margin-top: 4px; }
		</style>

		<form method="post">
			<?php wp_nonce_field('parkourone_promo_popup_nonce'); ?>

			<div class="po-promo-admin">
				<!-- Status -->
				<div class="po-toggle-row <?php echo $options['enabled'] ? 'is-active' : ''; ?>">
					<label>
						<input type="checkbox" name="promo_enabled" value="1" <?php checked($options['enabled']); ?>>
						<strong>Promo Popup aktiv</strong>
					</label>
					<span style="color: <?php echo $options['enabled'] ? '#00a32a' : '#646970'; ?>;">
						<?php echo $options['enabled'] ? '● Aktiv' : '○ Inaktiv'; ?>
					</span>
				</div>

				<!-- Bild -->
				<div class="po-promo-section">
					<h3>Bild</h3>
					<div class="po-form-row">
						<label>Popup-Bild</label>
						<div>
							<input type="hidden" name="promo_image_id" id="promo-image-id" value="<?php echo esc_attr($options['image_id']); ?>">
							<input type="hidden" name="promo_image_url" id="promo-image-url" value="<?php echo esc_attr($options['image_url']); ?>">
							<button type="button" class="button" id="promo-image-upload">Bild auswählen</button>
							<button type="button" class="button" id="promo-image-remove" style="color: #a00; <?php echo empty($options['image_url']) ? 'display:none;' : ''; ?>">Entfernen</button>
							<div class="po-image-preview" id="promo-image-preview">
								<?php if (!empty($options['image_url'])): ?>
									<img src="<?php echo esc_url($options['image_url']); ?>" alt="Promo Bild">
								<?php endif; ?>
							</div>
							<p class="po-hint">Empfohlen: 680×360px (ca. 2:1 Verhältnis)</p>
						</div>
					</div>
				</div>

				<!-- Inhalt -->
				<div class="po-promo-section">
					<h3>Inhalt</h3>
					<div class="po-form-row">
						<label>Titel</label>
						<div>
							<input type="text" name="promo_title" value="<?php echo esc_attr($options['title']); ?>" placeholder="z.B. Herbst &lt;mark&gt;Special&lt;/mark&gt;">
							<p class="po-hint">Verwende <code>&lt;mark&gt;Wort&lt;/mark&gt;</code> für farbige Hervorhebung.</p>
						</div>
					</div>
					<div class="po-form-row">
						<label>Beschreibung</label>
						<div>
							<input type="text" name="promo_description" value="<?php echo esc_attr($options['description']); ?>" placeholder="z.B. 20% Rabatt auf alle Kurse">
							<p class="po-hint">Kurz halten — max. 2 Zeilen.</p>
						</div>
					</div>
				</div>

				<!-- Button -->
				<div class="po-promo-section">
					<h3>Call-to-Action</h3>
					<div class="po-form-row">
						<label>Button Text</label>
						<input type="text" name="promo_button_text" value="<?php echo esc_attr($options['button_text']); ?>" placeholder="z.B. Jetzt entdecken">
					</div>
					<div class="po-form-row">
						<label>Button URL</label>
						<input type="url" name="promo_button_url" value="<?php echo esc_attr($options['button_url']); ?>" placeholder="https://...">
					</div>
				</div>

				<!-- Einstellungen -->
				<div class="po-promo-section">
					<h3>Einstellungen</h3>
					<div class="po-form-row">
						<label>Verzögerung (Sek.)</label>
						<div>
							<input type="number" name="promo_delay_seconds" value="<?php echo esc_attr($options['delay_seconds']); ?>" min="1" max="60" style="max-width: 100px;">
							<p class="po-hint">Sekunden bis das Popup eingeblendet wird.</p>
						</div>
					</div>
					<div class="po-form-row">
						<label>Version</label>
						<div>
							<code style="font-size: 14px;"><?php echo esc_html($options['version']); ?></code>
							<p class="po-hint">Wird automatisch beim Speichern erhöht. Invalidiert den localStorage-Cache der Besucher.</p>
						</div>
					</div>
				</div>

				<!-- Actions -->
				<p style="display: flex; gap: 12px; align-items: center;">
					<button type="submit" name="parkourone_promo_popup_save" class="button button-primary button-large">Speichern</button>
					<a href="<?php echo esc_url(home_url('/?preview_promo=1')); ?>" target="_blank" class="po-preview-link">
						&#x1F50D; Vorschau anzeigen
					</a>
				</p>
			</div>
		</form>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Media Library Upload
		var frame;
		document.getElementById('promo-image-upload').addEventListener('click', function(e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: 'Promo Popup Bild auswählen',
				button: { text: 'Bild verwenden' },
				multiple: false,
				library: { type: 'image' }
			});
			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.sizes && attachment.sizes.medium_large
					? attachment.sizes.medium_large.url
					: attachment.url;
				document.getElementById('promo-image-id').value = attachment.id;
				document.getElementById('promo-image-url').value = url;
				document.getElementById('promo-image-preview').innerHTML = '<img src="' + url + '" alt="Promo Bild">';
				document.getElementById('promo-image-remove').style.display = '';
			});
			frame.open();
		});

		// Bild entfernen
		document.getElementById('promo-image-remove').addEventListener('click', function() {
			document.getElementById('promo-image-id').value = '0';
			document.getElementById('promo-image-url').value = '';
			document.getElementById('promo-image-preview').innerHTML = '';
			this.style.display = 'none';
		});

		// Toggle Styling
		var checkbox = document.querySelector('[name="promo_enabled"]');
		if (checkbox) {
			checkbox.addEventListener('change', function() {
				var row = this.closest('.po-toggle-row');
				var status = row.querySelector('span');
				if (this.checked) {
					row.classList.add('is-active');
					status.style.color = '#00a32a';
					status.textContent = '● Aktiv';
				} else {
					row.classList.remove('is-active');
					status.style.color = '#646970';
					status.textContent = '○ Inaktiv';
				}
			});
		}
	});
	</script>
	<?php
}

/**
 * Frontend: Promo Popup HTML ausgeben
 */
function parkourone_render_promo_popup() {
	$options = get_option('parkourone_promo_popup', []);

	// Nicht anzeigen wenn deaktiviert
	if (empty($options['enabled'])) {
		// Ausnahme: Preview-Modus für Admins
		if (!isset($_GET['preview_promo']) || !current_user_can('manage_options')) {
			return;
		}
	}

	// Pflichtfelder prüfen
	if (empty($options['title']) || empty($options['button_text']) || empty($options['button_url'])) {
		return;
	}

	// Für Admins im normalen Modus nicht anzeigen (ausser Preview)
	if (current_user_can('manage_options') && !isset($_GET['preview_promo'])) {
		return;
	}

	$version = isset($options['version']) ? (int) $options['version'] : 1;
	$delay = isset($options['delay_seconds']) ? (int) $options['delay_seconds'] : 5;
	?>
	<div class="po-promo-popup" data-version="<?php echo esc_attr($version); ?>" data-delay="<?php echo esc_attr($delay); ?>" style="display:none;" role="dialog" aria-label="<?php echo esc_attr(wp_strip_all_tags($options['title'])); ?>">
		<button class="po-promo-popup__close" type="button" aria-label="Schliessen">
			<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		</button>
		<?php if (!empty($options['image_url'])): ?>
		<div class="po-promo-popup__image">
			<img src="<?php echo esc_url($options['image_url']); ?>" alt="<?php echo esc_attr(wp_strip_all_tags($options['title'])); ?>" loading="lazy">
		</div>
		<?php endif; ?>
		<div class="po-promo-popup__body">
			<h3 class="po-promo-popup__title"><?php echo wp_kses($options['title'], ['mark' => []]); ?></h3>
			<?php if (!empty($options['description'])): ?>
			<p class="po-promo-popup__desc"><?php echo esc_html($options['description']); ?></p>
			<?php endif; ?>
			<a href="<?php echo esc_url($options['button_url']); ?>" class="po-promo-popup__cta">
				<?php echo esc_html($options['button_text']); ?>
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</a>
		</div>
	</div>
	<?php
}
add_action('wp_footer', 'parkourone_render_promo_popup', 50);

/**
 * Frontend: CSS & JS einbinden
 */
function parkourone_enqueue_promo_popup() {
	// Nur laden wenn Popup aktiv oder Preview
	$options = get_option('parkourone_promo_popup', []);
	$is_preview = isset($_GET['preview_promo']) && current_user_can('manage_options');

	if (empty($options['enabled']) && !$is_preview) {
		return;
	}

	if (is_admin()) {
		return;
	}

	$theme_version = wp_get_theme()->get('Version');

	wp_enqueue_style(
		'parkourone-promo-popup',
		get_template_directory_uri() . '/assets/css/promo-popup.css',
		[],
		$theme_version
	);

	wp_enqueue_script(
		'parkourone-promo-popup',
		get_template_directory_uri() . '/assets/js/promo-popup.js',
		[],
		$theme_version,
		true
	);
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_promo_popup');
