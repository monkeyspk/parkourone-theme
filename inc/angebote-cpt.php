<?php
/**
 * Custom Post Type: Angebot
 * Für Workshops, Events, Camps und mehr
 */

defined('ABSPATH') || exit;

// =====================================================
// CPT & Taxonomy Registration
// =====================================================

function parkourone_register_angebot_cpt() {
	// Taxonomy: Angebotskategorie
	register_taxonomy('angebot_kategorie', 'angebot', [
		'labels' => [
			'name' => 'Kategorien',
			'singular_name' => 'Kategorie',
			'search_items' => 'Kategorien suchen',
			'all_items' => 'Alle Kategorien',
			'edit_item' => 'Kategorie bearbeiten',
			'update_item' => 'Kategorie aktualisieren',
			'add_new_item' => 'Neue Kategorie',
			'new_item_name' => 'Neuer Kategoriename',
			'menu_name' => 'Kategorien'
		],
		'hierarchical' => true,
		'public' => false,
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
		'rewrite' => false
	]);

	// CPT: Angebot
	register_post_type('angebot', [
		'labels' => [
			'name' => 'Angebote',
			'singular_name' => 'Angebot',
			'add_new' => 'Neues Angebot',
			'add_new_item' => 'Neues Angebot hinzufügen',
			'edit_item' => 'Angebot bearbeiten',
			'new_item' => 'Neues Angebot',
			'view_item' => 'Angebot ansehen',
			'search_items' => 'Angebote suchen',
			'not_found' => 'Keine Angebote gefunden',
			'not_found_in_trash' => 'Keine Angebote im Papierkorb'
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 26,
		'menu_icon' => 'dashicons-calendar-alt',
		'supports' => ['title', 'thumbnail', 'editor'],
		'has_archive' => false,
		'rewrite' => false,
		'show_in_rest' => true,
		'taxonomies' => ['angebot_kategorie']
	]);

	// Standard-Kategorien erstellen
	parkourone_create_default_angebot_kategorien();
}
add_action('init', 'parkourone_register_angebot_cpt');

function parkourone_create_default_angebot_kategorien() {
	$kategorien = [
		'kostenlos'        => 'Kostenlos',
		'workshop'         => 'Workshop',
		'camp'             => 'Camp',
		'privatunterricht' => 'Privatunterricht',
		'kurs'             => 'Kurs',
		'ferienkurs'       => 'Ferienkurs',
	];

	foreach ($kategorien as $slug => $name) {
		if (!term_exists($slug, 'angebot_kategorie')) {
			wp_insert_term($name, 'angebot_kategorie', ['slug' => $slug]);
		}
	}
}

// =====================================================
// Meta Boxes
// =====================================================

function parkourone_angebot_metaboxes() {
	add_meta_box(
		'angebot_details',
		'Angebot Details',
		'parkourone_angebot_details_metabox',
		'angebot',
		'normal',
		'high'
	);

	add_meta_box(
		'angebot_termine',
		'Termine (für buchbare Angebote)',
		'parkourone_angebot_termine_metabox',
		'angebot',
		'normal',
		'default'
	);

	add_meta_box(
		'angebot_settings',
		'Einstellungen',
		'parkourone_angebot_settings_metabox',
		'angebot',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'parkourone_angebot_metaboxes');

function parkourone_angebot_details_metabox($post) {
	wp_nonce_field('parkourone_angebot_save', 'parkourone_angebot_nonce');

	$kurzbeschreibung = get_post_meta($post->ID, '_angebot_kurzbeschreibung', true);
	$wann = get_post_meta($post->ID, '_angebot_wann', true);
	$saison = get_post_meta($post->ID, '_angebot_saison', true);
	$wo = get_post_meta($post->ID, '_angebot_wo', true);
	$maps_link = get_post_meta($post->ID, '_angebot_maps_link', true);
	$voraussetzungen = get_post_meta($post->ID, '_angebot_voraussetzungen', true);
	$was_mitbringen = get_post_meta($post->ID, '_angebot_was_mitbringen', true);
	$preis = get_post_meta($post->ID, '_angebot_preis', true);
	$ansprechperson = get_post_meta($post->ID, '_angebot_ansprechperson', true);
	?>
	<table class="form-table">
		<tr>
			<th><label for="_angebot_kurzbeschreibung">Kurzbeschreibung</label></th>
			<td>
				<textarea id="_angebot_kurzbeschreibung" name="_angebot_kurzbeschreibung" rows="2" class="large-text" placeholder="Max. 2 Sätze für die Card-Ansicht"><?php echo esc_textarea($kurzbeschreibung); ?></textarea>
				<p class="description">Wird auf der Card angezeigt. Der ausführliche Text kommt in den Editor oben.</p>
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_saison">Typ / Saison</label></th>
			<td>
				<select id="_angebot_saison" name="_angebot_saison">
					<option value="">Ganzjährig / Wiederkehrend</option>
					<option value="winter" <?php selected($saison, 'winter'); ?>>Nur Winter (Nov-März)</option>
					<option value="sommer" <?php selected($saison, 'sommer'); ?>>Nur Sommer (April-Okt)</option>
					<option value="einmalig" <?php selected($saison, 'einmalig'); ?>>Einmaliges Event</option>
				</select>
				<p class="description">Bei einmaligen Events werden vergangene Termine automatisch ausgeblendet.</p>
			</td>
		</tr>
		<tr id="wann-field-row" style="<?php echo $saison === 'einmalig' ? 'display:none;' : ''; ?>">
			<th><label for="_angebot_wann">Wann</label></th>
			<td>
				<input type="text" id="_angebot_wann" name="_angebot_wann" value="<?php echo esc_attr($wann); ?>" class="large-text" placeholder="z.B. jeden Mittwoch 18-20 Uhr">
				<p class="description">Für wiederkehrende Events. Konkrete Termine unten eintragen.</p>
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_wo">Wo / Treffpunkt</label></th>
			<td>
				<input type="text" id="_angebot_wo" name="_angebot_wo" value="<?php echo esc_attr($wo); ?>" class="large-text" placeholder="z.B. Volkspark Friedrichshain, Haupteingang">
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_maps_link">Google Maps Link</label></th>
			<td>
				<input type="url" id="_angebot_maps_link" name="_angebot_maps_link" value="<?php echo esc_url($maps_link); ?>" class="large-text" placeholder="https://maps.google.com/...">
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_voraussetzungen">Voraussetzungen</label></th>
			<td>
				<textarea id="_angebot_voraussetzungen" name="_angebot_voraussetzungen" rows="2" class="large-text" placeholder="z.B. Mindestalter 12 Jahre"><?php echo esc_textarea($voraussetzungen); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_was_mitbringen">Was mitbringen / anziehen</label></th>
			<td>
				<textarea id="_angebot_was_mitbringen" name="_angebot_was_mitbringen" rows="2" class="large-text" placeholder="z.B. Sportkleidung, keine Umkleide vorhanden"><?php echo esc_textarea($was_mitbringen); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_preis">Preis</label></th>
			<td>
				<input type="text" id="_angebot_preis" name="_angebot_preis" value="<?php echo esc_attr($preis); ?>" class="regular-text" placeholder="z.B. CHF 60.- oder 220€">
				<p class="description">Leer lassen für kostenlose Angebote.</p>
			</td>
		</tr>
		<tr>
			<th><label for="_angebot_ansprechperson">Coach</label></th>
			<td>
				<input type="text" id="_angebot_ansprechperson" name="_angebot_ansprechperson" value="<?php echo esc_attr($ansprechperson); ?>" class="large-text" placeholder="z.B. Max Mustermann oder max@parkourone.com">
				<p class="description">Name oder Kontakt des verantwortlichen Coaches.</p>
			</td>
		</tr>
	</table>

	<script>
	jQuery(document).ready(function($) {
		$('#_angebot_saison').on('change', function() {
			var isEinmalig = $(this).val() === 'einmalig';
			$('#wann-field-row').toggle(!isEinmalig);
		});
	});
	</script>
	<?php
}

function parkourone_angebot_termine_metabox($post) {
	$termine = get_post_meta($post->ID, '_angebot_termine', true);
	if (!is_array($termine)) {
		$termine = [];
	}

	// Single-Produkt-Modus erkennen: alle Termine zeigen auf die gleiche produkt_id,
	// und diese ist mit _event_single_product=1 markiert. In dem Fall gibt es nicht
	// pro Termin einen Buchungsstatus, sondern einen Gesamt-Status für den Kurs/Workshop.
	$single_pid = 0;
	if (!empty($termine)) {
		$first_pid = intval($termine[0]['produkt_id'] ?? 0);
		if ($first_pid && get_post_meta($first_pid, '_event_single_product', true) === '1') {
			$single_pid = $first_pid;
		}
	}

	if ($single_pid && class_exists('WooCommerce')) {
		$wc_single = wc_get_product($single_pid);
		if ($wc_single) {
			$stock_qty = (int) $wc_single->get_stock_quantity();
			$total_sold = parkourone_get_product_total_sold($single_pid);
			$initial_stock = $stock_qty + $total_sold;
			?>
			<div style="background:#f0f6fc;border:1px solid #c3e0f5;border-radius:6px;padding:12px 16px;margin-bottom:16px;">
				<strong style="display:block;margin-bottom:6px;">Gesamt-Buchungsstatus (Kurs/Workshop)</strong>
				<span style="font-size:13px;">
					<strong style="color:#2271b1;"><?php echo (int) $total_sold; ?></strong> gebucht
					von <strong><?php echo (int) $initial_stock; ?></strong> Plätzen
					&mdash; <strong style="color:<?php echo $stock_qty > 0 ? '#00a32a' : '#d63638'; ?>;"><?php echo $stock_qty; ?></strong> verfügbar
				</span>
				<p class="description" style="margin:8px 0 0;">Dieser Kurs/Workshop wird als Gesamtpaket über alle Termine gebucht. Stock wird automatisch aus AcademyBoard synchronisiert (max. Teilnehmer).</p>
			</div>
			<?php
		}
	}
	?>
	<p class="description">Für buchbare Workshops mit konkreten Terminen. Probetrainings buchen pro Termin, Kurse/Workshops als Gesamtpaket.</p>

	<div id="angebot-termine-container">
		<?php foreach ($termine as $index => $termin): ?>
		<div class="angebot-termin" data-index="<?php echo $index; ?>">
			<div class="termin-header">
				<strong>Termin <?php echo $index + 1; ?></strong>
				<button type="button" class="button-link termin-remove" style="color:#b32d2e;">Entfernen</button>
			</div>
			<table class="form-table" style="margin:0;">
				<tr>
					<th><label>Datum</label></th>
					<td><input type="date" name="_angebot_termine[<?php echo $index; ?>][datum]" value="<?php echo esc_attr($termin['datum'] ?? ''); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label>Uhrzeit</label></th>
					<td><input type="text" name="_angebot_termine[<?php echo $index; ?>][uhrzeit]" value="<?php echo esc_attr($termin['uhrzeit'] ?? ''); ?>" class="regular-text" placeholder="z.B. 10:00 - 12:00"></td>
				</tr>
				<tr>
					<th><label>Ort</label></th>
					<td><input type="text" name="_angebot_termine[<?php echo $index; ?>][ort]" value="<?php echo esc_attr($termin['ort'] ?? ''); ?>" class="large-text" placeholder="z.B. Brig, Zürich"></td>
				</tr>
				<tr>
					<th><label>Preis</label></th>
					<td><input type="text" name="_angebot_termine[<?php echo $index; ?>][preis]" value="<?php echo esc_attr($termin['preis'] ?? ''); ?>" class="regular-text" placeholder="z.B. CHF 60.-"></td>
				</tr>
				<?php
				// Kapazität wird ausschließlich aus AcademyBoard gezogen (available_seats
				// für Kurse/Workshops, trail_seats für Probetrainings). Der gecachte Wert
				// bleibt im Hidden-Feld erhalten, damit er beim Speichern nicht verloren geht.
				$pid = intval($termin['produkt_id'] ?? 0);
				$has_wc_product = $pid && class_exists('WooCommerce') && wc_get_product($pid);
				?>
				<input type="hidden" name="_angebot_termine[<?php echo $index; ?>][kapazitaet]" value="<?php echo esc_attr($termin['kapazitaet'] ?? ''); ?>">
				<tr>
					<th><label>WooCommerce Produkt ID</label></th>
					<td>
						<input type="number" name="_angebot_termine[<?php echo $index; ?>][produkt_id]" value="<?php echo esc_attr($termin['produkt_id'] ?? ''); ?>" class="small-text">
						<p class="description">Optional: ID eines bestehenden WooCommerce Produkts für die Buchung.</p>
					</td>
				</tr>
				<?php
				// Live WC-Buchungsstatus nur pro Termin anzeigen, wenn es KEIN Single-Product-Kurs ist
				// (sonst wäre der Status für jeden Termin gleich und redundant zum Gesamt-Status oben).
				if ($has_wc_product && $pid !== $single_pid):
					$wc_prod = wc_get_product($pid);
					$stock_qty = $wc_prod->get_stock_quantity();
					$total_sold = parkourone_get_product_total_sold($pid);
					$initial_stock = $stock_qty + $total_sold;
				?>
				<tr>
					<th><label>Buchungsstatus</label></th>
					<td>
						<span style="font-size: 13px;">
							<strong style="color: #2271b1;"><?php echo $total_sold; ?></strong> gebucht
							von <strong><?php echo $initial_stock; ?></strong> Plätzen
							&mdash; <span class="po-stock-display-<?php echo $pid; ?>"><strong style="color: <?php echo $stock_qty > 0 ? '#00a32a' : '#d63638'; ?>;"><?php echo $stock_qty; ?></strong> verfügbar</span>
						</span>
						<span class="po-stock-adjust" style="margin-left: 12px;">
							<button type="button" class="button button-small po-stock-btn" data-product-id="<?php echo $pid; ?>" data-action="decrease" title="Stock -1" style="padding: 0 6px; min-height: 26px; line-height: 24px;">−</button>
							<input type="number" class="po-stock-input" data-product-id="<?php echo $pid; ?>" value="<?php echo $stock_qty; ?>" style="width: 50px; height: 26px; text-align: center; margin: 0 2px;" min="0">
							<button type="button" class="button button-small po-stock-btn" data-product-id="<?php echo $pid; ?>" data-action="increase" title="Stock +1" style="padding: 0 6px; min-height: 26px; line-height: 24px;">+</button>
							<button type="button" class="button button-small po-stock-save" data-product-id="<?php echo $pid; ?>" style="margin-left: 4px; min-height: 26px; line-height: 24px;">Speichern</button>
							<span class="po-stock-msg-<?php echo $pid; ?>" style="margin-left: 6px; font-size: 12px;"></span>
						</span>
					</td>
				</tr>
				<?php endif; ?>
			</table>
			<hr style="margin: 20px 0;">
		</div>
		<?php endforeach; ?>
	</div>

	<button type="button" class="button" id="add-termin">+ Termin hinzufügen</button>

	<script>
	jQuery(document).ready(function($) {
		var terminIndex = <?php echo count($termine); ?>;

		$('#add-termin').on('click', function() {
			var html = '<div class="angebot-termin" data-index="' + terminIndex + '">' +
				'<div class="termin-header">' +
					'<strong>Termin ' + (terminIndex + 1) + '</strong>' +
					'<button type="button" class="button-link termin-remove" style="color:#b32d2e;">Entfernen</button>' +
				'</div>' +
				'<table class="form-table" style="margin:0;">' +
					'<tr><th><label>Datum</label></th><td><input type="date" name="_angebot_termine[' + terminIndex + '][datum]" class="regular-text"></td></tr>' +
					'<tr><th><label>Uhrzeit</label></th><td><input type="text" name="_angebot_termine[' + terminIndex + '][uhrzeit]" class="regular-text" placeholder="z.B. 10:00 - 12:00"></td></tr>' +
					'<tr><th><label>Ort</label></th><td><input type="text" name="_angebot_termine[' + terminIndex + '][ort]" class="large-text" placeholder="z.B. Brig, Zürich"></td></tr>' +
					'<tr><th><label>Preis</label></th><td><input type="text" name="_angebot_termine[' + terminIndex + '][preis]" class="regular-text" placeholder="z.B. CHF 60.-"></td></tr>' +
					'<tr><th><label>WooCommerce Produkt ID</label></th><td><input type="number" name="_angebot_termine[' + terminIndex + '][produkt_id]" class="small-text"><p class="description">Optional: ID eines bestehenden WooCommerce Produkts.</p></td></tr>' +
				'</table>' +
				'<hr style="margin: 20px 0;">' +
			'</div>';

			$('#angebot-termine-container').append(html);
			terminIndex++;
		});

		$(document).on('click', '.termin-remove', function() {
			$(this).closest('.angebot-termin').remove();
		});

		// Stock +/- Buttons
		$(document).on('click', '.po-stock-btn', function() {
			var pid = $(this).data('product-id');
			var input = $('.po-stock-input[data-product-id="' + pid + '"]');
			var val = parseInt(input.val()) || 0;
			if ($(this).data('action') === 'decrease') {
				input.val(Math.max(0, val - 1));
			} else {
				input.val(val + 1);
			}
		});

		// Stock speichern via AJAX
		$(document).on('click', '.po-stock-save', function() {
			var btn = $(this);
			var pid = btn.data('product-id');
			var newStock = parseInt($('.po-stock-input[data-product-id="' + pid + '"]').val());
			var msg = $('.po-stock-msg-' + pid);

			if (isNaN(newStock) || newStock < 0) {
				msg.html('<span style="color:#d63638;">Ungültiger Wert</span>');
				return;
			}

			btn.prop('disabled', true).text('...');
			$.post(ajaxurl, {
				action: 'po_update_wc_stock',
				product_id: pid,
				stock: newStock,
				_wpnonce: '<?php echo wp_create_nonce('po_update_wc_stock'); ?>'
			}, function(response) {
				btn.prop('disabled', false).text('Speichern');
				if (response.success) {
					var color = newStock > 0 ? '#00a32a' : '#d63638';
					$('.po-stock-display-' + pid).html('<strong style="color:' + color + ';">' + newStock + '</strong> verfügbar');
					msg.html('<span style="color:#00a32a;">✓</span>');
					setTimeout(function() { msg.html(''); }, 2000);
				} else {
					msg.html('<span style="color:#d63638;">' + (response.data || 'Fehler') + '</span>');
				}
			}).fail(function() {
				btn.prop('disabled', false).text('Speichern');
				msg.html('<span style="color:#d63638;">Netzwerkfehler</span>');
			});
		});
	});
	</script>
	<?php
}

function parkourone_angebot_settings_metabox($post) {
	$buchungsart = get_post_meta($post->ID, '_angebot_buchungsart', true);
	$cta_url = get_post_meta($post->ID, '_angebot_cta_url', true);
	$featured = get_post_meta($post->ID, '_angebot_featured', true);
	$teilnehmer_typ = get_post_meta($post->ID, '_angebot_teilnehmer_typ', true) ?: 'standard';
	$kontakt_email = get_post_meta($post->ID, '_angebot_kontakt_email', true);
	$quelle = get_post_meta($post->ID, '_angebot_quelle', true) ?: 'manual';
	?>
	<p>
		<label for="_angebot_buchungsart"><strong>Buchungsart</strong></label><br>
		<select id="_angebot_buchungsart" name="_angebot_buchungsart" style="width:100%;">
			<option value="kostenlos" <?php selected($buchungsart, 'kostenlos'); ?>>Kostenlos (einfach kommen)</option>
			<option value="kontakt" <?php selected($buchungsart, 'kontakt'); ?>>Kontaktformular</option>
			<option value="woocommerce" <?php selected($buchungsart, 'woocommerce'); ?>>WooCommerce (buchbar)</option>
			<option value="extern" <?php selected($buchungsart, 'extern'); ?>>Externer Link</option>
		</select>
	</p>

	<p id="teilnehmer-typ-field" style="<?php echo $buchungsart === 'woocommerce' ? '' : 'display:none;'; ?>">
		<label for="_angebot_teilnehmer_typ"><strong>Teilnehmer-Typ</strong></label><br>
		<select id="_angebot_teilnehmer_typ" name="_angebot_teilnehmer_typ" style="width:100%;">
			<option value="standard" <?php selected($teilnehmer_typ, 'standard'); ?>>Standard (1 Person)</option>
			<option value="paerchen" <?php selected($teilnehmer_typ, 'paerchen'); ?>>Pärchen (2 Personen, z.B. Generationenworkshop)</option>
		</select>
		<span class="description">Bei Pärchen werden 2 Teilnehmer-Formulare angezeigt.</span>
	</p>

	<p id="kontakt-email-field" style="<?php echo $buchungsart === 'kontakt' ? '' : 'display:none;'; ?>">
		<label for="_angebot_kontakt_email"><strong>Kontakt E-Mail</strong></label><br>
		<input type="email" id="_angebot_kontakt_email" name="_angebot_kontakt_email" value="<?php echo esc_attr($kontakt_email); ?>" style="width:100%;" placeholder="Optional, sonst Admin-E-Mail">
		<span class="description">Wohin sollen Anfragen gehen?</span>
	</p>

	<p id="cta-url-field" style="<?php echo $buchungsart === 'extern' ? '' : 'display:none;'; ?>">
		<label for="_angebot_cta_url"><strong>Externer Link</strong></label><br>
		<input type="url" id="_angebot_cta_url" name="_angebot_cta_url" value="<?php echo esc_url($cta_url); ?>" style="width:100%;" placeholder="https://...">
	</p>

	<p>
		<label>
			<input type="checkbox" name="_angebot_featured" value="1" <?php checked($featured, '1'); ?>>
			<strong>Auf Startseite zeigen</strong>
		</label>
		<br><span class="description">Wird im Karussell auf der Startseite angezeigt.</span>
	</p>

	<hr>

	<p>
		<strong>Quelle:</strong> <?php echo $quelle === 'manual' ? 'Manuell' : 'Academyboard'; ?>
	</p>
	<?php if ($quelle === 'academyboard'): ?>
	<p class="description">
		Stock wird bei AcademyBoard-Events automatisch aus der API gezogen
		(max. Teilnehmer für Kurse/Workshops, Trail Seats für Probetrainings).
	</p>
	<?php endif; ?>

	<script>
	jQuery(document).ready(function($) {
		$('#_angebot_buchungsart').on('change', function() {
			var val = $(this).val();
			$('#cta-url-field').toggle(val === 'extern');
			$('#teilnehmer-typ-field').toggle(val === 'woocommerce');
			$('#kontakt-email-field').toggle(val === 'kontakt');
		});
	});
	</script>
	<?php
}

// =====================================================
// Save Meta
// =====================================================

function parkourone_angebot_save_meta($post_id) {
	if (!isset($_POST['parkourone_angebot_nonce']) || !wp_verify_nonce($_POST['parkourone_angebot_nonce'], 'parkourone_angebot_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	// Text fields
	$text_fields = [
		'_angebot_kurzbeschreibung',
		'_angebot_wann',
		'_angebot_saison',
		'_angebot_wo',
		'_angebot_voraussetzungen',
		'_angebot_was_mitbringen',
		'_angebot_preis',
		'_angebot_ansprechperson',
		'_angebot_buchungsart',
		'_angebot_teilnehmer_typ',
		'_angebot_kontakt_email'
	];

	foreach ($text_fields as $field) {
		if (isset($_POST[$field])) {
			update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
		}
	}

	// URL fields
	if (isset($_POST['_angebot_maps_link'])) {
		update_post_meta($post_id, '_angebot_maps_link', esc_url_raw($_POST['_angebot_maps_link']));
	}
	if (isset($_POST['_angebot_cta_url'])) {
		update_post_meta($post_id, '_angebot_cta_url', esc_url_raw($_POST['_angebot_cta_url']));
	}

	// Checkboxes
	update_post_meta($post_id, '_angebot_featured', isset($_POST['_angebot_featured']) ? '1' : '0');

	// Termine (array)
	if (isset($_POST['_angebot_termine']) && is_array($_POST['_angebot_termine'])) {
		$termine = [];
		foreach ($_POST['_angebot_termine'] as $termin) {
			if (!empty($termin['datum']) || !empty($termin['ort'])) {
				$termine[] = [
					'datum' => sanitize_text_field($termin['datum'] ?? ''),
					'uhrzeit' => sanitize_text_field($termin['uhrzeit'] ?? ''),
					'ort' => sanitize_text_field($termin['ort'] ?? ''),
					'preis' => sanitize_text_field($termin['preis'] ?? ''),
					'kapazitaet' => absint($termin['kapazitaet'] ?? 0),
					'produkt_id' => absint($termin['produkt_id'] ?? 0)
				];
			}
		}
		update_post_meta($post_id, '_angebot_termine', $termine);
	} else {
		update_post_meta($post_id, '_angebot_termine', []);
	}

	// Quelle setzen wenn nicht vorhanden
	if (!get_post_meta($post_id, '_angebot_quelle', true)) {
		update_post_meta($post_id, '_angebot_quelle', 'manual');
	}

	// Stock wird ausschließlich vom custom-events-plugin aus der AcademyBoard-API gepflegt
	// (available_seats für Kurse/Workshops, trail_seats für Probetrainings). Kein Override
	// mehr im Angebot-Backend — die API ist die einzige Wahrheit.
}
add_action('save_post_angebot', 'parkourone_angebot_save_meta');

// =====================================================
// REST API Endpoint
// =====================================================

function parkourone_register_angebote_api() {
	register_rest_route('parkourone/v1', '/angebote', [
		'methods' => 'GET',
		'callback' => 'parkourone_get_angebote',
		'permission_callback' => '__return_true'
	]);
}
add_action('rest_api_init', 'parkourone_register_angebote_api');

function parkourone_get_angebote(WP_REST_Request $request) {
	$kategorie = $request->get_param('kategorie');
	$featured = $request->get_param('featured');

	$args = [
		'post_type' => 'angebot',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'menu_order',
		'order' => 'ASC'
	];

	if ($kategorie) {
		$args['tax_query'] = [
			[
				'taxonomy' => 'angebot_kategorie',
				'field' => 'slug',
				'terms' => $kategorie
			]
		];
	}

	if ($featured === '1' || $featured === 'true') {
		$args['meta_query'] = [
			[
				'key' => '_angebot_featured',
				'value' => '1'
			]
		];
	}

	$query = new WP_Query($args);
	$angebote = [];
	$heute = date('Y-m-d');

	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$id = get_the_ID();

			$saison = get_post_meta($id, '_angebot_saison', true);
			$termine = get_post_meta($id, '_angebot_termine', true);
			if (!is_array($termine)) {
				$termine = [];
			}

			// Einmalige Events: Komplett ausblenden wenn alle Termine vorbei
			if ($saison === 'einmalig' && !empty($termine)) {
				$hat_zukunft = false;
				foreach ($termine as $t) {
					if (!empty($t['datum']) && $t['datum'] >= $heute) {
						$hat_zukunft = true;
						break;
					}
				}
				if (!$hat_zukunft) {
					continue; // Event nicht anzeigen
				}
			}

			// Filter vergangene Termine
			$termine = array_filter($termine, function($t) use ($heute) {
				return empty($t['datum']) || $t['datum'] >= $heute;
			});

			$kategorien = wp_get_post_terms($id, 'angebot_kategorie', ['fields' => 'all']);
			$kategorie_data = !empty($kategorien) ? [
				'slug' => $kategorien[0]->slug,
				'name' => $kategorien[0]->name
			] : null;

			$angebote[] = [
				'id' => $id,
				'titel' => get_the_title(),
				'kurzbeschreibung' => get_post_meta($id, '_angebot_kurzbeschreibung', true),
				'beschreibung' => apply_filters('the_content', get_the_content()),
				'bild' => get_the_post_thumbnail_url($id, 'large'),
				'kategorie' => $kategorie_data,
				'wann' => get_post_meta($id, '_angebot_wann', true),
				'saison' => $saison,
				'wo' => get_post_meta($id, '_angebot_wo', true),
				'maps_link' => get_post_meta($id, '_angebot_maps_link', true),
				'voraussetzungen' => get_post_meta($id, '_angebot_voraussetzungen', true),
				'was_mitbringen' => get_post_meta($id, '_angebot_was_mitbringen', true),
				'preis' => get_post_meta($id, '_angebot_preis', true),
				'ansprechperson' => get_post_meta($id, '_angebot_ansprechperson', true),
				'buchungsart' => get_post_meta($id, '_angebot_buchungsart', true),
				'cta_url' => get_post_meta($id, '_angebot_cta_url', true),
				'featured' => get_post_meta($id, '_angebot_featured', true) === '1',
				'termine' => array_values($termine)
			];
		}
		wp_reset_postdata();
	}

	return $angebote;
}

// =====================================================
// Helper Functions
// =====================================================

/**
 * Gibt das Placeholder-Bild für ein Angebot zurück
 * Basierend auf dem Titel wird ein passendes Bild aus dem Theme geladen
 */
function parkourone_get_angebot_placeholder_image($post_id) {
	$title = strtolower(get_the_title($post_id));
	$base_url = get_template_directory_uri() . '/assets/images/angebote-placeholder/';

	// Mapping von Titeln zu Bildern
	$mappings = [
		'hellnight' => 'hellnight.jpg',
		'public meeting' => 'public-meeting.jpg',
		'move jam' => 'move-jam.jpg',
		'femme' => 'femme.jpg',
		'generationenworkshop' => 'generationenworkshop.jpg',
		'familienworkshop' => 'familienworkshop.jpg',
		'personal parkour' => 'privatunterricht.jpg',
		'privatunterricht' => 'privatunterricht.jpg',
		'fontainebleau' => 'fontainebleau.jpg',
		'onetainebleau' => 'fontainebleau.jpg',
		'elbsandstein' => 'elbsandstein.jpg',
	];

	// Suche nach passendem Bild
	foreach ($mappings as $keyword => $image) {
		if (strpos($title, $keyword) !== false) {
			return $base_url . $image;
		}
	}

	// Fallback: Kategorie-basiert
	$terms = wp_get_post_terms($post_id, 'angebot_kategorie', ['fields' => 'slugs']);
	$kategorie = !empty($terms) ? $terms[0] : '';

	$kategorie_fallbacks = [
		'kostenlos' => 'public-meeting.jpg',
		'workshop' => 'generationenworkshop.jpg',
		'camp' => 'fontainebleau.jpg',
		'privatunterricht' => 'privatunterricht.jpg',
	];

	if (isset($kategorie_fallbacks[$kategorie])) {
		return $base_url . $kategorie_fallbacks[$kategorie];
	}

	// Ultimativer Fallback
	return $base_url . 'public-meeting.jpg';
}

/**
 * Gibt das Bild für ein Angebot zurück (Featured Image oder Placeholder)
 */
function parkourone_get_angebot_image($post_id, $size = 'medium_large') {
	$featured_image = get_the_post_thumbnail_url($post_id, $size);

	if ($featured_image) {
		return $featured_image;
	}

	return parkourone_get_angebot_placeholder_image($post_id);
}

/**
 * Prüft ob ein Angebot noch angezeigt werden soll
 * Einmalige Events werden ausgeblendet wenn alle Termine vorbei sind
 */
function parkourone_angebot_is_visible($post_id) {
	$saison = get_post_meta($post_id, '_angebot_saison', true);

	// Nicht-einmalige Events sind immer sichtbar
	if ($saison !== 'einmalig') {
		return true;
	}

	$termine = get_post_meta($post_id, '_angebot_termine', true);

	// Keine Termine = sichtbar (wiederkehrendes Event ohne feste Termine)
	if (empty($termine) || !is_array($termine)) {
		return true;
	}

	// Prüfen ob mindestens ein Termin in der Zukunft liegt
	$heute = date('Y-m-d');
	foreach ($termine as $termin) {
		if (!empty($termin['datum']) && $termin['datum'] >= $heute) {
			return true;
		}
	}

	return false;
}

/**
 * Filtert vergangene Termine aus einem Termine-Array
 */
function parkourone_filter_vergangene_termine($termine) {
	if (empty($termine) || !is_array($termine)) {
		return [];
	}

	$heute = date('Y-m-d');
	return array_values(array_filter($termine, function($t) use ($heute) {
		return empty($t['datum']) || $t['datum'] >= $heute;
	}));
}

/**
 * Ergänzt Termine mit dem Live WC-Stock für die Frontend-Anzeige.
 */
function parkourone_enrich_termine_with_stock($termine) {
	if (empty($termine) || !is_array($termine) || !class_exists('WooCommerce')) {
		return $termine ?: [];
	}

	foreach ($termine as &$termin) {
		$pid = intval($termin['produkt_id'] ?? 0);
		if ($pid) {
			$product = wc_get_product($pid);
			if ($product) {
				$termin['verfuegbar'] = (int) $product->get_stock_quantity();
			}
		}
	}
	unset($termin);

	return $termine;
}

function parkourone_get_angebot_by_id($id) {
	$post = get_post($id);
	if (!$post || $post->post_type !== 'angebot') {
		return null;
	}

	$kategorien = wp_get_post_terms($id, 'angebot_kategorie', ['fields' => 'all']);
	$kategorie_data = !empty($kategorien) ? [
		'slug' => $kategorien[0]->slug,
		'name' => $kategorien[0]->name
	] : null;

	$termine = get_post_meta($id, '_angebot_termine', true);
	if (!is_array($termine)) {
		$termine = [];
	}

	return [
		'id' => $id,
		'titel' => $post->post_title,
		'kurzbeschreibung' => get_post_meta($id, '_angebot_kurzbeschreibung', true),
		'beschreibung' => apply_filters('the_content', $post->post_content),
		'bild' => get_the_post_thumbnail_url($id, 'large'),
		'kategorie' => $kategorie_data,
		'wann' => get_post_meta($id, '_angebot_wann', true),
		'saison' => get_post_meta($id, '_angebot_saison', true),
		'wo' => get_post_meta($id, '_angebot_wo', true),
		'maps_link' => get_post_meta($id, '_angebot_maps_link', true),
		'voraussetzungen' => get_post_meta($id, '_angebot_voraussetzungen', true),
		'was_mitbringen' => get_post_meta($id, '_angebot_was_mitbringen', true),
		'preis' => get_post_meta($id, '_angebot_preis', true),
		'ansprechperson' => get_post_meta($id, '_angebot_ansprechperson', true),
		'buchungsart' => get_post_meta($id, '_angebot_buchungsart', true),
		'cta_url' => get_post_meta($id, '_angebot_cta_url', true),
		'featured' => get_post_meta($id, '_angebot_featured', true) === '1',
		'teilnehmer_typ' => get_post_meta($id, '_angebot_teilnehmer_typ', true) ?: 'standard',
		'termine' => $termine
	];
}

// =====================================================
// WooCommerce Auto-Produkt-Erstellung
// =====================================================

function parkourone_angebot_create_woo_products($post_id) {
	if (!class_exists('WooCommerce')) return;

	// AB-Angebote: Produkte kommen vom Event-System, keine neuen erstellen
	if (get_post_meta($post_id, '_angebot_quelle', true) === 'academyboard') return;

	// Ferienkurse: haben ein Gesamtpaket-Produkt, keine Einzeltermin-Produkte
	if (get_post_meta($post_id, '_angebot_is_ferienkurs', true) === '1') return;

	$buchungsart = get_post_meta($post_id, '_angebot_buchungsart', true);
	if ($buchungsart !== 'woocommerce') return;

	$termine = get_post_meta($post_id, '_angebot_termine', true);
	if (!is_array($termine)) return;

	$angebot_title = get_the_title($post_id);
	$heute = date('Y-m-d');

	foreach ($termine as $index => $termin) {
		// Skip wenn schon Produkt-ID vorhanden oder Datum in Vergangenheit
		if (!empty($termin['produkt_id']) && get_post($termin['produkt_id'])) continue;
		if (!empty($termin['datum']) && $termin['datum'] < $heute) continue;

		// Produkt-Titel erstellen
		$product_title = $angebot_title;
		if (!empty($termin['ort'])) {
			$product_title .= ' - ' . $termin['ort'];
		}
		if (!empty($termin['datum'])) {
			$product_title .= ' - ' . date_i18n('d.m.Y', strtotime($termin['datum']));
		}

		// Preis parsen (CHF 60.- oder 60€ etc.)
		$preis_raw = $termin['preis'] ?? '';
		$preis = floatval(preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $preis_raw)));

		// WooCommerce Produkt erstellen
		$product = new WC_Product_Simple();
		$product->set_name($product_title);
		$product->set_status('publish');
		$product->set_catalog_visibility('hidden');
		$product->set_price($preis);
		$product->set_regular_price($preis);
		$product->set_manage_stock(true);
		$product->set_stock_quantity(absint($termin['kapazitaet'] ?? 0));
		$product->set_stock_status('instock');
		$product->set_sold_individually(false);

		// Beschreibung
		$desc = get_post_meta($post_id, '_angebot_kurzbeschreibung', true);
		if (!empty($termin['uhrzeit'])) $desc .= "\nUhrzeit: " . $termin['uhrzeit'];
		if (!empty($termin['ort'])) $desc .= "\nOrt: " . $termin['ort'];
		$product->set_short_description($desc);

		$product_id = $product->save();

		// Bild vom Angebot übernehmen
		$featured_image_id = get_post_thumbnail_id($post_id);
		if ($featured_image_id) {
			$product->set_image_id($featured_image_id);
			$product->save();
		}

		// Meta speichern
		update_post_meta($product_id, '_angebot_id', $post_id);
		update_post_meta($product_id, '_angebot_termin_index', $index);
		update_post_meta($product_id, '_angebot_termin_datum', $termin['datum'] ?? '');
		update_post_meta($product_id, '_angebot_termin_ort', $termin['ort'] ?? '');

		// Produkt-ID im Termin speichern
		$termine[$index]['produkt_id'] = $product_id;
	}

	// Termine mit Produkt-IDs aktualisieren
	update_post_meta($post_id, '_angebot_termine', $termine);
}
add_action('save_post_angebot', 'parkourone_angebot_create_woo_products', 20);

// =====================================================
// Auto-Cleanup für vergangene Termine
// =====================================================

function parkourone_angebot_cleanup_schedule() {
	if (!wp_next_scheduled('parkourone_angebot_cleanup_cron')) {
		wp_schedule_event(time(), 'daily', 'parkourone_angebot_cleanup_cron');
	}
}
add_action('wp', 'parkourone_angebot_cleanup_schedule');

function parkourone_angebot_cleanup() {
	if (!class_exists('WooCommerce')) return;

	$heute = date('Y-m-d');

	// Alle Angebote durchgehen
	$angebote = get_posts([
		'post_type' => 'angebot',
		'posts_per_page' => -1,
		'post_status' => 'any'
	]);

	foreach ($angebote as $angebot) {
		$termine = get_post_meta($angebot->ID, '_angebot_termine', true);
		if (!is_array($termine)) continue;

		$updated = false;
		foreach ($termine as $index => $termin) {
			if (empty($termin['datum'])) continue;

			// Vergangener Termin
			if ($termin['datum'] < $heute && !empty($termin['produkt_id'])) {
				// WooCommerce Produkt auf Draft setzen
				wp_update_post([
					'ID' => $termin['produkt_id'],
					'post_status' => 'draft'
				]);
				$updated = true;
			}
		}
	}
}
add_action('parkourone_angebot_cleanup_cron', 'parkourone_angebot_cleanup');

// Bei Angebot-Löschung auch Produkte löschen (nicht bei AB-Angeboten, die gehören dem Event-System)
function parkourone_angebot_delete_products($post_id) {
	if (get_post_type($post_id) !== 'angebot') return;
	if (!class_exists('WooCommerce')) return;
	if (get_post_meta($post_id, '_angebot_quelle', true) === 'academyboard') return;

	$termine = get_post_meta($post_id, '_angebot_termine', true);
	if (!is_array($termine)) return;

	foreach ($termine as $termin) {
		if (!empty($termin['produkt_id'])) {
			wp_delete_post($termin['produkt_id'], true);
		}
	}
}
add_action('before_delete_post', 'parkourone_angebot_delete_products');

// =====================================================
// WooCommerce Buchung AJAX
// =====================================================

// AJAX Config wird jetzt direkt in den render.php Dateien als Inline-Script ausgegeben
// Dies ist zuverlässiger als wp_localize_script bei Block-ViewScripts

function parkourone_angebot_add_to_cart() {
	check_ajax_referer('po_angebot_booking_nonce', 'nonce');

	if (!class_exists('WooCommerce')) {
		wp_send_json_error(['message' => 'WooCommerce nicht aktiv']);
	}

	// WooCommerce Session sicherstellen (für nicht-eingeloggte User)
	if (!WC()->session) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}
	if (!WC()->cart) {
		WC()->cart = new WC_Cart();
		WC()->cart->get_cart();
	}
	if (!WC()->customer) {
		WC()->customer = new WC_Customer(get_current_user_id(), true);
	}

	$product_id = absint($_POST['product_id'] ?? 0);
	$angebot_id = absint($_POST['angebot_id'] ?? 0);

	if (!$product_id) {
		wp_send_json_error(['message' => 'Produkt nicht gefunden (keine ID)']);
	}

	// Produkt-Validierung: existiert, ist published, hat Preis, ist auf Lager?
	$product = wc_get_product($product_id);
	if (!$product) {
		wp_send_json_error(['message' => 'Produkt #' . $product_id . ' existiert nicht']);
	}
	if ($product->get_status() !== 'publish') {
		wp_send_json_error(['message' => 'Produkt #' . $product_id . ' ist nicht veröffentlicht (Status: ' . $product->get_status() . ')']);
	}
	if (!$product->is_purchasable()) {
		wp_send_json_error(['message' => 'Produkt #' . $product_id . ' ist nicht kaufbar (Preis fehlt oder Produkt deaktiviert)']);
	}
	if (!$product->is_in_stock()) {
		wp_send_json_error(['message' => 'Produkt #' . $product_id . ' ist ausverkauft']);
	}

	// Teilnehmerdaten sammeln
	$teilnehmer = [];
	$teilnehmer_typ = get_post_meta($angebot_id, '_angebot_teilnehmer_typ', true) ?: 'standard';
	$anzahl_teilnehmer = $teilnehmer_typ === 'paerchen' ? 2 : 1;

	for ($i = 1; $i <= $anzahl_teilnehmer; $i++) {
		$vorname = sanitize_text_field($_POST['vorname_' . $i] ?? '');
		$name = sanitize_text_field($_POST['name_' . $i] ?? '');
		$geburtsdatum = sanitize_text_field($_POST['geburtsdatum_' . $i] ?? '');

		if (empty($vorname) || empty($name) || empty($geburtsdatum)) {
			wp_send_json_error(['message' => 'Bitte alle Teilnehmerdaten ausfüllen']);
		}

		$teilnehmer[] = [
			'vorname' => $vorname,
			'name' => $name,
			'geburtsdatum' => $geburtsdatum
		];
	}

	// Cart Item Data
	$cart_item_data = [
		'angebot_id' => $angebot_id,
		'angebot_teilnehmer' => $teilnehmer
	];

	// WC-Notices vorher leeren für saubere Fehlermeldung
	wc_clear_notices();

	$added = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

	if ($added) {
		wp_send_json_success([
			'message' => 'Erfolgreich zum Warenkorb hinzugefügt',
			'cart_url' => function_exists('parkourone_get_checkout_url') ? parkourone_get_checkout_url() : home_url('/kasse/'),
			'cart_count' => WC()->cart->get_cart_contents_count()
		]);
	} else {
		// WC-Fehlermeldungen auslesen für bessere Diagnose
		$errors = wc_get_notices('error');
		wc_clear_notices();
		$error_msg = 'Fehler beim Hinzufügen zum Warenkorb';
		if (!empty($errors)) {
			$messages = array_map(function($e) {
				return is_array($e) ? wp_strip_all_tags($e['notice']) : wp_strip_all_tags($e);
			}, $errors);
			$error_msg = implode(' ', $messages);
		}
		wp_send_json_error(['message' => $error_msg]);
	}
}
add_action('wp_ajax_po_angebot_add_to_cart', 'parkourone_angebot_add_to_cart');
add_action('wp_ajax_nopriv_po_angebot_add_to_cart', 'parkourone_angebot_add_to_cart');

// Teilnehmerdaten im Warenkorb anzeigen
function parkourone_angebot_cart_item_data($item_data, $cart_item) {
	$product_id = $cart_item['product_id'];
	$angebot_id = isset($cart_item['angebot_id']) ? $cart_item['angebot_id'] : get_post_meta($product_id, '_angebot_id', true);

	// Event-Details (Ort + Datum) – kompakt
	$termin_ort = get_post_meta($product_id, '_angebot_termin_ort', true);
	$termin_datum = get_post_meta($product_id, '_angebot_termin_datum', true);

	if ($termin_datum) {
		$item_data[] = [
			'key' => 'Datum',
			'value' => date_i18n('d. M Y', strtotime($termin_datum))
		];
	}

	if ($termin_ort) {
		$item_data[] = [
			'key' => 'Ort',
			'value' => $termin_ort
		];
	}

	// Teilnehmer – nur Name, kein Geburtsdatum
	if (isset($cart_item['angebot_teilnehmer'])) {
		$namen = [];
		foreach ($cart_item['angebot_teilnehmer'] as $teilnehmer) {
			$namen[] = $teilnehmer['vorname'] . ' ' . $teilnehmer['name'];
		}
		$item_data[] = [
			'key' => count($namen) > 1 ? 'Teilnehmer' : 'Teilnehmer',
			'value' => implode(', ', $namen)
		];
	}

	return $item_data;
}
add_filter('woocommerce_get_item_data', 'parkourone_angebot_cart_item_data', 10, 2);

// Produktname im Warenkorb: Angebots-Titel statt WC-Produktname
function parkourone_angebot_cart_item_name($name, $cart_item, $cart_item_key) {
	$product_id = $cart_item['product_id'];
	$angebot_id = isset($cart_item['angebot_id']) ? $cart_item['angebot_id'] : get_post_meta($product_id, '_angebot_id', true);

	if ($angebot_id) {
		$angebot_title = get_the_title($angebot_id);
		if ($angebot_title) {
			return $angebot_title;
		}
	}

	return $name;
}
add_filter('woocommerce_cart_item_name', 'parkourone_angebot_cart_item_name', 10, 3);

// Produktbild im Warenkorb: Event-Bild → Angebots-Bild als Fallback
function parkourone_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
	$product_id = $cart_item['product_id'];

	if (!has_post_thumbnail($product_id)) {
		$image_url = parkourone_get_cart_item_image_url($cart_item, 'woocommerce_thumbnail');
		if ($image_url) {
			$alt = esc_attr(parkourone_get_cart_item_source_title($cart_item));
			return '<img src="' . esc_url($image_url) . '" alt="' . $alt . '" class="woocommerce-placeholder">';
		}
	}

	return $thumbnail;
}
add_filter('woocommerce_cart_item_thumbnail', 'parkourone_cart_item_thumbnail', 10, 3);

// Produktbild im Cart Block (Store API): Event-Bild → Angebots-Bild als Fallback
function parkourone_store_api_cart_images($product_images, $cart_item, $cart_item_key) {
	$product_id = $cart_item['product_id'];

	if (has_post_thumbnail($product_id)) {
		return $product_images;
	}

	$image_url = parkourone_get_cart_item_image_url($cart_item, 'woocommerce_thumbnail');
	if ($image_url) {
		$alt = parkourone_get_cart_item_source_title($cart_item);
		return [
			(object) [
				'id'        => 0,
				'src'       => $image_url,
				'thumbnail' => $image_url,
				'srcset'    => '',
				'sizes'     => '',
				'name'      => $alt,
				'alt'       => $alt,
			]
		];
	}

	return $product_images;
}
add_filter('woocommerce_store_api_cart_item_images', 'parkourone_store_api_cart_images', 10, 3);

/**
 * Zentrale Bild-URL für ein Cart-Item ermitteln
 * Prüft: Event-Bild → Angebots-Bild
 */
function parkourone_get_cart_item_image_url($cart_item, $size = 'thumbnail') {
	$product_id = $cart_item['product_id'];

	// 1. Academyboard Event
	$event_id = isset($cart_item['event_id']) ? $cart_item['event_id'] : get_post_meta($product_id, '_event_id', true);
	if ($event_id && function_exists('parkourone_get_event_image')) {
		$url = parkourone_get_event_image($event_id);
		if ($url) return $url;
	}

	// 2. Angebot
	$angebot_id = isset($cart_item['angebot_id']) ? $cart_item['angebot_id'] : get_post_meta($product_id, '_angebot_id', true);
	if ($angebot_id && function_exists('parkourone_get_angebot_image')) {
		$url = parkourone_get_angebot_image($angebot_id, $size);
		if ($url) return $url;
	}

	return '';
}

/**
 * Quell-Titel für ein Cart-Item (Event-Titel oder Angebot-Titel)
 */
function parkourone_get_cart_item_source_title($cart_item) {
	$product_id = $cart_item['product_id'];

	$event_id = isset($cart_item['event_id']) ? $cart_item['event_id'] : get_post_meta($product_id, '_event_id', true);
	if ($event_id) return get_the_title($event_id);

	$angebot_id = isset($cart_item['angebot_id']) ? $cart_item['angebot_id'] : get_post_meta($product_id, '_angebot_id', true);
	if ($angebot_id) return get_the_title($angebot_id);

	return get_the_title($product_id);
}

// Teilnehmerdaten in Bestellung speichern
function parkourone_angebot_order_item_meta($item, $cart_item_key, $values, $order) {
	if (isset($values['angebot_teilnehmer'])) {
		foreach ($values['angebot_teilnehmer'] as $i => $teilnehmer) {
			$label = count($values['angebot_teilnehmer']) > 1 ? 'Teilnehmer ' . ($i + 1) : 'Teilnehmer';
			$item->add_meta_data($label, $teilnehmer['vorname'] . ' ' . $teilnehmer['name'] . ' (' . $teilnehmer['geburtsdatum'] . ')');
		}
		if (isset($values['angebot_id'])) {
			$item->add_meta_data('Angebot', get_the_title($values['angebot_id']));
		}
	}
}
add_action('woocommerce_checkout_create_order_line_item', 'parkourone_angebot_order_item_meta', 10, 4);

// =====================================================
// Kontaktformular AJAX
// =====================================================

function parkourone_angebot_kontakt_submit() {
	// Rate Limiting (einfach)
	$ip = $_SERVER['REMOTE_ADDR'];
	$transient_key = 'po_kontakt_' . md5($ip);
	if (get_transient($transient_key)) {
		wp_send_json_error(['message' => 'Bitte warte einen Moment bevor du eine weitere Anfrage sendest.']);
	}
	set_transient($transient_key, 1, 60); // 1 Minute Pause

	$angebot_id = absint($_POST['angebot_id'] ?? 0);
	$name = sanitize_text_field($_POST['name'] ?? '');
	$email = sanitize_email($_POST['email'] ?? '');
	$telefon = sanitize_text_field($_POST['telefon'] ?? '');
	$anzahl = sanitize_text_field($_POST['anzahl'] ?? '');
	$nachricht = sanitize_textarea_field($_POST['nachricht'] ?? '');
	$agb = isset($_POST['agb']) && $_POST['agb'] === '1';

	// Validierung
	if (empty($name) || empty($email)) {
		wp_send_json_error(['message' => 'Bitte Name und E-Mail ausfüllen.']);
	}

	if (!is_email($email)) {
		wp_send_json_error(['message' => 'Bitte eine gültige E-Mail-Adresse eingeben.']);
	}

	if (!$agb) {
		wp_send_json_error(['message' => 'Bitte die AGB akzeptieren.']);
	}

	// Angebot-Infos
	$angebot_title = $angebot_id ? get_the_title($angebot_id) : 'Allgemeine Anfrage';
	$kontakt_email = get_post_meta($angebot_id, '_angebot_kontakt_email', true);
	$to_email = $kontakt_email ?: get_option('admin_email');

	// E-Mail an Admin/Ansprechperson
	$subject = 'Neue Anfrage: ' . $angebot_title;
	$message = "Neue Anfrage über das Kontaktformular\n\n";
	$message .= "Angebot: " . $angebot_title . "\n";
	$message .= "Name: " . $name . "\n";
	$message .= "E-Mail: " . $email . "\n";
	if ($telefon) $message .= "Telefon: " . $telefon . "\n";
	if ($anzahl) $message .= "Anzahl Teilnehmende: " . $anzahl . "\n";
	$message .= "\nNachricht:\n" . $nachricht . "\n";
	$message .= "\n---\nGesendet von: " . home_url();

	$headers = [
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>'
	];

	$sent_admin = wp_mail($to_email, $subject, $message, $headers);

	// Bestätigungs-E-Mail an Kunde
	$confirm_subject = 'Deine Anfrage bei ' . get_bloginfo('name');
	$confirm_message = "Hallo " . $name . ",\n\n";
	$confirm_message .= "vielen Dank für deine Anfrage zu \"" . $angebot_title . "\".\n\n";
	$confirm_message .= "Wir haben deine Nachricht erhalten und werden uns schnellstmöglich bei dir melden.\n\n";
	$confirm_message .= "Deine Angaben:\n";
	if ($telefon) $confirm_message .= "Telefon: " . $telefon . "\n";
	if ($anzahl) $confirm_message .= "Anzahl Teilnehmende: " . $anzahl . "\n";
	$confirm_message .= "Nachricht: " . $nachricht . "\n\n";
	$confirm_message .= "Liebe Grüsse\nDein " . get_bloginfo('name') . " Team";

	$confirm_headers = ['Content-Type: text/plain; charset=UTF-8'];
	wp_mail($email, $confirm_subject, $confirm_message, $confirm_headers);

	if ($sent_admin) {
		wp_send_json_success(['message' => 'Vielen Dank! Deine Anfrage wurde gesendet. Du erhältst eine Bestätigung per E-Mail.']);
	} else {
		wp_send_json_error(['message' => 'Es gab ein Problem beim Senden. Bitte versuche es später erneut.']);
	}
}
add_action('wp_ajax_po_angebot_kontakt', 'parkourone_angebot_kontakt_submit');
add_action('wp_ajax_nopriv_po_angebot_kontakt', 'parkourone_angebot_kontakt_submit');

// =====================================================
// Setup-Wizard für Angebote-Vorlagen
// =====================================================

/**
 * Preset-Angebote Daten
 */
function parkourone_get_preset_angebote() {
	return [
		'hellnight' => [
			'titel' => 'Hellnight',
			'kurzbeschreibung' => 'Nächtliches Training unter Flutlicht. Komm vorbei und trainiere mit der Community!',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'wann' => 'Jeden Freitag, 20:00 - 22:00 Uhr',
			'featured' => true
		],
		'public_meeting' => [
			'titel' => 'Public Meeting',
			'kurzbeschreibung' => 'Offenes Training für alle Level. Lerne neue Leute kennen und trainiere gemeinsam.',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'wann' => 'Jeden Samstag, 14:00 - 17:00 Uhr',
			'featured' => true
		],
		'move_jam' => [
			'titel' => 'MoVe Jam',
			'kurzbeschreibung' => 'Movement & Parkour Jam. Freies Training, Austausch und Spass.',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'featured' => false
		],
		'femme' => [
			'titel' => 'FEMME',
			'kurzbeschreibung' => 'Training nur für Frauen und nicht-binäre Personen. Safe Space zum Ausprobieren.',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'featured' => true
		],
		'generationenworkshop' => [
			'titel' => 'Generationenworkshop',
			'kurzbeschreibung' => 'Parkour für Jung und Alt. Erlebt gemeinsam Bewegung und überwindet Hindernisse als Team.',
			'beschreibung' => 'Der Generationenworkshop bringt verschiedene Altersgruppen zusammen. Ideal für Grosseltern mit Enkeln oder Eltern mit Kindern. Gemeinsam entdeckt ihr die Grundlagen des Parkour und stärkt eure Verbindung durch Bewegung.',
			'kategorie' => 'workshop',
			'buchungsart' => 'woocommerce',
			'teilnehmer_typ' => 'paerchen',
			'preis' => '60.- pro Pärchen',
			'featured' => true
		],
		'familienworkshop' => [
			'titel' => 'Familienworkshop',
			'kurzbeschreibung' => 'Parkour für die ganze Familie. Auf Anfrage buchbar.',
			'kategorie' => 'workshop',
			'buchungsart' => 'kontakt',
			'preis' => 'Auf Anfrage',
			'featured' => false
		],
		'privatunterricht' => [
			'titel' => 'Personal Parkour Training',
			'kurzbeschreibung' => 'Individuelles 1:1 Coaching mit unseren erfahrenen TRUST Headcoaches.',
			'beschreibung' => 'Unsere erfahrenen TRUST Headcoaches mit jahrelanger Parkourerfahrung zeigen dir, wie du Hindernisse effizient überwindest und deine eigenen Grenzen sprengst. In deinem persönlichen Coaching erwartet dich ein massgeschneidertes Training, das dich physisch herausfordert, mental stärkt und emotional motiviert.

<strong>Was erwartet dich im Personal Training?</strong>
– Individuelles Training: Genau auf deine Bedürfnisse und dein Level angepasst.
– Fokussierte Betreuung: 1:1 Coaching für maximalen Fortschritt.
– Flexible Termine: Vereinbare deinen Trainingstermin, wann es dir am besten passt.
– Zielorientierte Entwicklung: Wir arbeiten Schritt für Schritt an deinen Zielen.',
			'kategorie' => 'privatunterricht',
			'buchungsart' => 'kontakt',
			'preis' => 'Ab 99.- pro Stunde',
			'featured' => true
		],
		'fontainebleau' => [
			'titel' => 'ONEtainebleau',
			'kurzbeschreibung' => 'Parkour-Reise nach Fontainebleau. Das Mekka für Boulderer und Traceure.',
			'kategorie' => 'camp',
			'buchungsart' => 'extern',
			'cta_url' => 'https://parkourone.com/parkour-fontainebleau/',
			'saison' => 'einmalig',
			'featured' => false
		],
		'elbsandstein' => [
			'titel' => 'Elbsandsteinfahrt',
			'kurzbeschreibung' => 'Abenteuer im Elbsandsteingebirge. Klettern, Parkour und Natur.',
			'kategorie' => 'camp',
			'buchungsart' => 'kontakt',
			'saison' => 'einmalig',
			'featured' => false
		],
		'sommerfest' => [
			'titel' => 'Sommerfest',
			'kurzbeschreibung' => 'Unser jährliches Sommerfest mit Shows, Workshops und Community.',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'saison' => 'einmalig',
			'featured' => false
		],
		'weihnachtstraining' => [
			'titel' => 'Weihnachtstraining',
			'kurzbeschreibung' => 'Festliches Training zum Jahresende. Gemeinsam das Jahr ausklingen lassen.',
			'kategorie' => 'kostenlos',
			'buchungsart' => 'kostenlos',
			'saison' => 'einmalig',
			'featured' => false
		]
	];
}

/**
 * Admin Notice für Setup-Wizard
 */
function parkourone_angebot_setup_notice() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'angebot') {
		return;
	}

	// Bereits eingerichtet?
	if (get_option('parkourone_angebote_setup_done')) {
		return;
	}

	// Gibt es bereits Angebote?
	$count = wp_count_posts('angebot');
	if ($count->publish > 0 || $count->draft > 0) {
		update_option('parkourone_angebote_setup_done', true);
		return;
	}

	?>
	<div class="notice notice-info" id="po-angebote-setup-notice">
		<h2>Willkommen bei den Angeboten!</h2>
		<p>Wähle die Angebote aus, die du für deine Schule übernehmen möchtest. Du kannst sie danach beliebig anpassen.</p>

		<form id="po-angebote-setup-form" method="post">
			<?php wp_nonce_field('po_angebote_setup', 'po_setup_nonce'); ?>
			<input type="hidden" name="action" value="po_angebote_setup">

			<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0;">
				<?php foreach (parkourone_get_preset_angebote() as $key => $angebot): ?>
				<label style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 1rem; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
					<input type="checkbox" name="angebote[]" value="<?php echo esc_attr($key); ?>" checked style="margin-top: 3px;">
					<div>
						<strong><?php echo esc_html($angebot['titel']); ?></strong><br>
						<small style="color: #666;"><?php echo esc_html($angebot['kurzbeschreibung']); ?></small>
					</div>
				</label>
				<?php endforeach; ?>
			</div>

			<p>
				<button type="submit" class="button button-primary button-large">Ausgewählte Angebote anlegen</button>
				<button type="button" class="button button-secondary" id="po-skip-setup" style="margin-left: 0.5rem;">Überspringen</button>
			</p>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#po-angebote-setup-form').on('submit', function(e) {
			e.preventDefault();
			var $form = $(this);
			var $btn = $form.find('button[type="submit"]');
			$btn.prop('disabled', true).text('Wird angelegt...');

			$.post(ajaxurl, $form.serialize(), function(response) {
				if (response.success) {
					$('#po-angebote-setup-notice').html(
						'<p><strong>✓ ' + response.data.count + ' Angebote wurden angelegt!</strong> Die Seite wird neu geladen...</p>'
					);
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					alert('Fehler: ' + response.data);
					$btn.prop('disabled', false).text('Ausgewählte Angebote anlegen');
				}
			});
		});

		$('#po-skip-setup').on('click', function() {
			$.post(ajaxurl, { action: 'po_angebote_skip_setup' }, function() {
				$('#po-angebote-setup-notice').fadeOut();
			});
		});
	});
	</script>
	<?php
}
add_action('admin_notices', 'parkourone_angebot_setup_notice');

/**
 * AJAX: Setup durchführen
 */
function parkourone_angebot_do_setup() {
	check_ajax_referer('po_angebote_setup', 'po_setup_nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Keine Berechtigung');
	}

	$selected = $_POST['angebote'] ?? [];
	if (empty($selected)) {
		update_option('parkourone_angebote_setup_done', true);
		wp_send_json_success(['count' => 0]);
	}

	$presets = parkourone_get_preset_angebote();
	$count = 0;

	foreach ($selected as $key) {
		if (!isset($presets[$key])) continue;

		$data = $presets[$key];

		// Post erstellen
		$post_id = wp_insert_post([
			'post_type' => 'angebot',
			'post_title' => $data['titel'],
			'post_content' => $data['beschreibung'] ?? '',
			'post_status' => 'draft', // Als Entwurf, damit Schule erst anpassen kann
			'menu_order' => $count
		]);

		if (is_wp_error($post_id)) continue;

		// Kategorie setzen
		if (!empty($data['kategorie'])) {
			wp_set_object_terms($post_id, $data['kategorie'], 'angebot_kategorie');
		}

		// Meta-Felder setzen
		$meta_fields = [
			'kurzbeschreibung', 'wann', 'saison', 'wo', 'maps_link',
			'voraussetzungen', 'was_mitbringen', 'preis', 'ansprechperson',
			'buchungsart', 'teilnehmer_typ', 'cta_url'
		];

		foreach ($meta_fields as $field) {
			if (isset($data[$field])) {
				update_post_meta($post_id, '_angebot_' . $field, $data[$field]);
			}
		}

		// Featured
		if (!empty($data['featured'])) {
			update_post_meta($post_id, '_angebot_featured', '1');
		}

		// Quelle
		update_post_meta($post_id, '_angebot_quelle', 'preset');

		$count++;
	}

	update_option('parkourone_angebote_setup_done', true);
	wp_send_json_success(['count' => $count]);
}
add_action('wp_ajax_po_angebote_setup', 'parkourone_angebot_do_setup');

/**
 * AJAX: Setup überspringen
 */
function parkourone_angebot_skip_setup() {
	update_option('parkourone_angebote_setup_done', true);
	wp_send_json_success();
}
add_action('wp_ajax_po_angebote_skip_setup', 'parkourone_angebot_skip_setup');

// =====================================================
// WC Buchungs-Helpers
// =====================================================

/**
 * Ermittelt die Anzahl verkaufter Einheiten eines WC-Produkts.
 * Nutzt WooCommerce's eigenen total_sales Counter (performant).
 */
function parkourone_get_product_total_sold($product_id) {
	if (!class_exists('WooCommerce')) return 0;
	$product = wc_get_product($product_id);
	if (!$product) return 0;
	return (int) $product->get_total_sales();
}

/**
 * AJAX: WC-Produkt Stock direkt aus Angebot-Backend anpassen.
 */
function parkourone_ajax_update_wc_stock() {
	check_ajax_referer('po_update_wc_stock');

	if (!current_user_can('edit_products')) {
		wp_send_json_error('Keine Berechtigung');
	}

	$product_id = intval($_POST['product_id'] ?? 0);
	$new_stock  = intval($_POST['stock'] ?? -1);

	if (!$product_id || $new_stock < 0) {
		wp_send_json_error('Ungültige Parameter');
	}

	$product = wc_get_product($product_id);
	if (!$product) {
		wp_send_json_error('Produkt nicht gefunden');
	}

	$product->set_stock_quantity($new_stock);
	$product->set_manage_stock(true);
	$product->save();

	wp_send_json_success(['stock' => $new_stock]);
}
add_action('wp_ajax_po_update_wc_stock', 'parkourone_ajax_update_wc_stock');

// =====================================================
// Academyboard Event → Angebot Sync
// =====================================================

/**
 * Sync eines Events (Ferienkurs/Workshop/Kurs) in das Angebote-System.
 * Wird bei jedem save_post_event ausgelöst (Prio 30, nach Event-Image-Save).
 * Probetraining-Events werden übersprungen.
 */
function parkourone_sync_event_to_angebot($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (wp_is_post_revision($post_id)) return;
	if (get_post_type($post_id) !== 'event') return;
	if (get_post_status($post_id) !== 'publish') return;

	// Angebotstyp aus event_category ermitteln (Kind-Term unter Parent 'angebot')
	$angebot_typ = parkourone_get_event_angebot_typ($post_id);
	if (!$angebot_typ) return;

	// Probetraining → skip
	if ($angebot_typ === 'probetraining') return;

	// Nur bekannte Typen synchronisieren
	$sync_typen = ['ferienkurs', 'workshop', 'kurs'];
	if (!in_array($angebot_typ, $sync_typen, true)) return;

	// Ferienkurs-Erkennung (Typ oder Titelsuche) — dient nur noch als UI-Label.
	$is_ferienkurs = ($angebot_typ === 'ferienkurs') || (stripos(get_the_title($post_id), 'ferienkurs') !== false);

	// Single-Product-Modus: Ein gemeinsames WC-Produkt für Kurse/Workshops/Ferienkurse.
	// Quelle ist das Event-Flag is_course / is_workshop (vom custom-events-plugin aus der API).
	$event_is_course   = (int) get_post_meta($post_id, '_event_is_course', true) === 1;
	$event_is_workshop = (int) get_post_meta($post_id, '_event_is_workshop', true) === 1;
	$is_single_product = $event_is_course || $event_is_workshop;

	// Event-Kategorie → Angebot-Kategorie mappen
	$kategorie_map = [
		'ferienkurs' => 'ferienkurs',
		'workshop'   => 'workshop',
		'kurs'       => 'kurs',
	];
	$angebot_kategorie = $kategorie_map[$angebot_typ] ?? $angebot_typ;

	// Bestehendes Angebot suchen: zuerst per Event-Post-ID
	$existing = get_posts([
		'post_type'      => 'angebot',
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'meta_query'     => [
			['key' => '_angebot_academyboard_event_id', 'value' => $post_id]
		],
		'fields' => 'ids',
	]);

	// Fallback: per course_id suchen (Event kann neue Post-ID haben nach Reimport)
	if (empty($existing)) {
		$event_course_id = get_post_meta($post_id, '_event_course_id', true);
		if (!empty($event_course_id)) {
			// Alle Events mit dieser course_id finden (inkl. alter, gelöschter IDs)
			$events_with_course_id = get_posts([
				'post_type'      => 'event',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => [
					['key' => '_event_course_id', 'value' => $event_course_id]
				],
				'fields' => 'ids',
			]);
			if (!empty($events_with_course_id)) {
				$existing = get_posts([
					'post_type'      => 'angebot',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_query'     => [
						[
							'key'     => '_angebot_academyboard_event_id',
							'value'   => $events_with_course_id,
							'compare' => 'IN',
						]
					],
					'fields' => 'ids',
				]);
			}
		}
	}

	// Fallback 2: per Event-Titel suchen (letzter Versuch)
	if (empty($existing)) {
		$event_title_check = get_the_title($post_id);
		if (!empty($event_title_check)) {
			$title_match = get_page_by_title($event_title_check, OBJECT, 'angebot');
			if ($title_match && get_post_meta($title_match->ID, '_angebot_quelle', true) === 'academyboard') {
				$existing = [$title_match->ID];
			}
		}
	}

	$event_title = get_the_title($post_id);

	// Event-Daten sammeln
	$event_venue       = get_post_meta($post_id, '_event_venue', true);
	$event_headcoach   = get_post_meta($post_id, '_event_headcoach', true);
	$event_description = get_post_meta($post_id, '_event_description', true);
	$event_price       = get_post_meta($post_id, '_event_price', true);
	$event_start_time  = get_post_meta($post_id, '_event_start_time', true);
	$event_end_time    = get_post_meta($post_id, '_event_end_time', true);
	$event_coach_email = get_post_meta($post_id, '_event_headcoach_email', true);
	$event_lat         = get_post_meta($post_id, '_event_venue_lat', true);
	$event_lng         = get_post_meta($post_id, '_event_venue_lng', true);

	// Google Maps Link aus Koordinaten generieren
	$maps_link = '';
	if (!empty($event_lat) && !empty($event_lng)) {
		$maps_link = 'https://www.google.com/maps?q=' . $event_lat . ',' . $event_lng;
	}

	// Preis formatieren mit WooCommerce-Währung
	$preis_display = '';
	if (!empty($event_price) && floatval($event_price) > 0) {
		$currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€';
		$preis_display = number_format(floatval($event_price), 0, ',', '.') . ' ' . $currency_symbol;
	}

	// Wann-Text aus Zeiten bauen
	$wann_text = '';
	if ($event_start_time) {
		$wann_text = $event_start_time;
		if ($event_end_time) {
			$wann_text .= ' – ' . $event_end_time;
		}
	}

	// Termin-Daten aufbereiten (mit Preis)
	$termine = parkourone_build_angebot_termine_from_event($post_id);

	if (!empty($existing)) {
		// UPDATE: AB-Felder aktualisieren
		$angebot_id = $existing[0];

		wp_update_post([
			'ID'         => $angebot_id,
			'post_title' => $event_title,
		]);

		// Event-ID Verknüpfung aktualisieren (Event kann neue Post-ID haben nach Reimport)
		update_post_meta($angebot_id, '_angebot_academyboard_event_id', $post_id);

		// Termine: bestehende Kapazität beibehalten wenn manuell korrigiert
		$existing_termine = get_post_meta($angebot_id, '_angebot_termine', true);
		if (is_array($existing_termine) && !empty($existing_termine)) {
			// Lookup: datum → bestehende Kapazität
			$existing_kap = [];
			foreach ($existing_termine as $et) {
				if (!empty($et['datum'])) {
					$existing_kap[$et['datum']] = $et['kapazitaet'] ?? 0;
				}
			}
			// Neue Termine: Kapazität nur überschreiben wenn AB einen WC-Stock liefert,
			// sonst bestehenden manuellen Wert beibehalten
			foreach ($termine as &$termin) {
				if (isset($existing_kap[$termin['datum']]) && empty($termin['kapazitaet'])) {
					$termin['kapazitaet'] = $existing_kap[$termin['datum']];
				}
			}
			unset($termin);
		}
		update_post_meta($angebot_id, '_angebot_termine', $termine);

		// Ort + Coach immer aus AB aktualisieren (strukturelle Daten)
		update_post_meta($angebot_id, '_angebot_wo', $event_venue);
		update_post_meta($angebot_id, '_angebot_ansprechperson', $event_headcoach);

		// Folgende Felder nur setzen wenn auf dem Angebot noch LEER
		// → manuelle Einstellungen werden nicht überschrieben
		if (!empty($event_description) && empty(get_post_meta($angebot_id, '_angebot_kurzbeschreibung', true))) {
			update_post_meta($angebot_id, '_angebot_kurzbeschreibung', $event_description);
		}
		if (!empty($preis_display) && empty(get_post_meta($angebot_id, '_angebot_preis', true))) {
			update_post_meta($angebot_id, '_angebot_preis', $preis_display);
		}
		if (!empty($wann_text) && empty(get_post_meta($angebot_id, '_angebot_wann', true))) {
			update_post_meta($angebot_id, '_angebot_wann', $wann_text);
		}
		if (!empty($event_coach_email) && empty(get_post_meta($angebot_id, '_angebot_kontakt_email', true))) {
			update_post_meta($angebot_id, '_angebot_kontakt_email', $event_coach_email);
		}
		if (!empty($maps_link) && empty(get_post_meta($angebot_id, '_angebot_maps_link', true))) {
			update_post_meta($angebot_id, '_angebot_maps_link', $maps_link);
		}

		// Buchungsart + Featured: NUR setzen wenn noch leer (manuelle Werte schützen)
		if (empty(get_post_meta($angebot_id, '_angebot_featured', true))) {
			update_post_meta($angebot_id, '_angebot_featured', '1');
		}
		if (empty(get_post_meta($angebot_id, '_angebot_buchungsart', true))) {
			update_post_meta($angebot_id, '_angebot_buchungsart', 'woocommerce');
		}

		if ($is_ferienkurs) {
			update_post_meta($angebot_id, '_angebot_is_ferienkurs', '1');
		}
		// Single-Produkt spiegeln (Kurs/Workshop/Ferienkurs → ein WC-Produkt vom Plugin)
		if ($is_single_product && get_post_meta($angebot_id, '_angebot_buchungsart', true) === 'woocommerce') {
			parkourone_sync_ferienkurs_wc_product($angebot_id, $post_id, $event_title, $termine);
		}
	} else {
		// NEU: Draft-Angebot erstellen
		$angebot_id = wp_insert_post([
			'post_type'   => 'angebot',
			'post_title'  => $event_title,
			'post_status' => 'draft',
		]);

		if (is_wp_error($angebot_id)) return;

		// Verknüpfung + Quelle
		update_post_meta($angebot_id, '_angebot_academyboard_event_id', $post_id);
		update_post_meta($angebot_id, '_angebot_quelle', 'academyboard');

		// Alle AB-Daten übernehmen
		update_post_meta($angebot_id, '_angebot_termine', $termine);
		update_post_meta($angebot_id, '_angebot_wo', $event_venue);
		update_post_meta($angebot_id, '_angebot_ansprechperson', $event_headcoach);
		update_post_meta($angebot_id, '_angebot_saison', 'einmalig');

		if (!empty($event_description)) {
			update_post_meta($angebot_id, '_angebot_kurzbeschreibung', $event_description);
		}
		if (!empty($preis_display)) {
			update_post_meta($angebot_id, '_angebot_preis', $preis_display);
		}
		if (!empty($wann_text)) {
			update_post_meta($angebot_id, '_angebot_wann', $wann_text);
		}
		if (!empty($event_coach_email)) {
			update_post_meta($angebot_id, '_angebot_kontakt_email', $event_coach_email);
		}
		if (!empty($maps_link)) {
			update_post_meta($angebot_id, '_angebot_maps_link', $maps_link);
		}

		// AB-Events: immer WooCommerce-Buchung + Featured
		update_post_meta($angebot_id, '_angebot_buchungsart', 'woocommerce');
		update_post_meta($angebot_id, '_angebot_featured', '1');

		// Kategorie setzen
		wp_set_object_terms($angebot_id, $angebot_kategorie, 'angebot_kategorie');

		if ($is_ferienkurs) {
			update_post_meta($angebot_id, '_angebot_is_ferienkurs', '1');
		}
		if ($is_single_product && get_post_meta($angebot_id, '_angebot_buchungsart', true) === 'woocommerce') {
			parkourone_sync_ferienkurs_wc_product($angebot_id, $post_id, $event_title, $termine);
		}
	}
}
add_action('save_post_event', 'parkourone_sync_event_to_angebot', 30);

/**
 * Spiegelt das vom custom-events-plugin angelegte Single-Product für einen
 * Kurs/Workshop/Ferienkurs in die Angebot-Meta _angebot_ferienkurs_produkt_id,
 * damit Frontend + Backend die bestehenden Code-Pfade weiter nutzen können.
 *
 * Das Theme erstellt KEIN eigenes Paket-Produkt mehr — Stock, Preis und Titel
 * werden ausschließlich vom Plugin gepflegt.
 */
function parkourone_sync_ferienkurs_wc_product($angebot_id, $event_post_id, $event_title, $termine) {
	$single_products = get_posts([
		'post_type'      => 'product',
		'post_status'    => ['publish', 'private'],
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [
			['key' => '_event_id',             'value' => $event_post_id],
			['key' => '_event_single_product', 'value' => '1'],
		],
	]);

	if (empty($single_products)) return;

	$new_pid = (int) $single_products[0];
	$old_pid = (int) get_post_meta($angebot_id, '_angebot_ferienkurs_produkt_id', true);

	// Alte vom Theme erstellte Ferienkurs-Paket-Produkte deprecaten, wenn sie
	// durch das neue Plugin-Single-Product abgelöst wurden. Nicht löschen wegen
	// möglicher Order-Historie — nur auf privat + out-of-stock setzen.
	if ($old_pid && $old_pid !== $new_pid && get_post($old_pid)) {
		if (get_post_meta($old_pid, '_is_ferienkurs_product', true) === '1') {
			wp_update_post(['ID' => $old_pid, 'post_status' => 'private']);
			update_post_meta($old_pid, '_stock', 0);
			update_post_meta($old_pid, '_stock_status', 'outofstock');
			update_post_meta($old_pid, '_deprecated_by_sync', '1');
			if (function_exists('wc_delete_product_transients')) {
				wc_delete_product_transients($old_pid);
			}
		}
	}

	update_post_meta($angebot_id, '_angebot_ferienkurs_produkt_id', $new_pid);
}

/**
 * Löst das Single-Product für ein Angebot auf — direkt aus dem verknüpften
 * AB-Event, NICHT aus dem gecachten Meta. So bleibt das Frontend immer
 * synchron, selbst wenn das _angebot_ferienkurs_produkt_id-Meta veraltet ist.
 */
function parkourone_get_angebot_single_product_id($angebot_id) {
	$event_id = (int) get_post_meta($angebot_id, '_angebot_academyboard_event_id', true);
	if ($event_id) {
		$products = get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				['key' => '_event_id',             'value' => $event_id],
				['key' => '_event_single_product', 'value' => '1'],
			],
		]);
		if (!empty($products)) {
			return (int) $products[0];
		}
	}
	// Fallback auf gecachtes Meta (nur wenn das Produkt noch gültig und nicht deprecated ist).
	$cached = (int) get_post_meta($angebot_id, '_angebot_ferienkurs_produkt_id', true);
	if ($cached && get_post($cached) && get_post_status($cached) === 'publish') {
		return $cached;
	}
	return 0;
}

/**
 * Auto-detect Ferienkurs bei manuellem Speichern eines Angebots.
 */
function parkourone_angebot_detect_ferienkurs($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (wp_is_post_revision($post_id)) return;
	if (get_post_type($post_id) !== 'angebot') return;

	$title = get_the_title($post_id);
	$is_ferienkurs = stripos($title, 'ferienkurs') !== false;
	$was_ferienkurs = get_post_meta($post_id, '_angebot_is_ferienkurs', true) === '1';

	if ($is_ferienkurs && !$was_ferienkurs) {
		update_post_meta($post_id, '_angebot_is_ferienkurs', '1');
		wp_set_object_terms($post_id, 'ferienkurs', 'angebot_kategorie');
	} elseif (!$is_ferienkurs && $was_ferienkurs) {
		update_post_meta($post_id, '_angebot_is_ferienkurs', '0');
	}
}
add_action('save_post_angebot', 'parkourone_angebot_detect_ferienkurs', 5);

/**
 * Ermittelt den Angebotstyp eines Events aus der event_category Taxonomie.
 * Sucht Kind-Terms unter dem Parent 'angebot'.
 */
function parkourone_get_event_angebot_typ($event_id) {
	$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
	if (is_wp_error($terms) || empty($terms)) return null;

	foreach ($terms as $term) {
		if (!$term->parent) continue;
		$parent = get_term($term->parent, 'event_category');
		if ($parent && !is_wp_error($parent) && $parent->slug === 'angebot') {
			return $term->slug;
		}
	}

	return null;
}

/**
 * Baut die Angebot-Termine aus den Event-Daten.
 *
 * Produkt-Zuordnung:
 *  - Kurs/Workshop (is_course || is_workshop): EIN gemeinsames WC-Produkt für
 *    das ganze Event (via _event_id + _event_single_product=1). Alle Termin-
 *    Einträge erhalten dieselbe produkt_id und denselben Stock-Wert.
 *  - Probetraining: pro Datum ein eigenes WC-Produkt (via _event_id + _event_date).
 */
function parkourone_build_angebot_termine_from_event($event_id) {
	$event_dates = get_post_meta($event_id, '_event_dates', true);
	if (!is_array($event_dates)) return [];

	$start_time  = get_post_meta($event_id, '_event_start_time', true);
	$end_time    = get_post_meta($event_id, '_event_end_time', true);
	$venue       = get_post_meta($event_id, '_event_venue', true);
	$event_price = get_post_meta($event_id, '_event_price', true);

	$is_workshop = (int) get_post_meta($event_id, '_event_is_workshop', true) === 1;
	$is_course   = (int) get_post_meta($event_id, '_event_is_course', true) === 1;
	$single_product_mode = $is_workshop || $is_course;

	// Preis formatieren für Termin-Anzeige
	$preis_termin = '';
	if (!empty($event_price) && floatval($event_price) > 0) {
		$currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€';
		$preis_termin = number_format(floatval($event_price), 0, ',', '.') . ' ' . $currency_symbol;
	}

	$uhrzeit = '';
	if ($start_time) {
		$uhrzeit = $start_time;
		if ($end_time) {
			$uhrzeit .= ' - ' . $end_time;
		}
	}

	// Single-Produkt-Modus: EIN Produkt einmal auflösen und auf alle Termine anwenden.
	$single_produkt_id = 0;
	$single_kapazitaet = 0;
	if ($single_product_mode) {
		$single_products = get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				['key' => '_event_id',             'value' => $event_id],
				['key' => '_event_single_product', 'value' => '1'],
			],
		]);
		if (!empty($single_products) && class_exists('WooCommerce')) {
			$single_produkt_id = (int) $single_products[0];
			$wc_product = wc_get_product($single_produkt_id);
			if ($wc_product) {
				$single_kapazitaet = (int) $wc_product->get_stock_quantity();
			}
		}
	}

	$termine = [];

	foreach ($event_dates as $date_entry) {
		if (empty($date_entry['date'])) continue;

		// Datum parsen (DD-MM-YYYY oder DD.MM.YYYY → YYYY-MM-DD)
		$date_str = str_replace('.', '-', $date_entry['date']);
		$parts = explode('-', $date_str);
		if (count($parts) !== 3) continue;

		$day   = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
		$month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
		$year  = $parts[2];
		$datum_iso = "$year-$month-$day";

		// Ort: aus date_entry oder Fallback auf Event-Venue
		$ort = !empty($date_entry['venue']) ? $date_entry['venue'] : $venue;

		if ($single_product_mode) {
			$produkt_id = $single_produkt_id;
			$kapazitaet = $single_kapazitaet;
		} else {
			// Probetraining: per-Datum-Produkt suchen
			$produkt_id = 0;
			$kapazitaet = 0;
			$event_products = get_posts([
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => [
					['key' => '_event_id',   'value' => $event_id],
					['key' => '_event_date', 'value' => $date_entry['date']],
				],
			]);

			if (!empty($event_products)) {
				$produkt_id = (int) $event_products[0];
				if (class_exists('WooCommerce')) {
					$wc_product = wc_get_product($produkt_id);
					if ($wc_product) {
						$kapazitaet = (int) $wc_product->get_stock_quantity();
					}
				}
			}
		}

		$termine[] = [
			'datum'      => $datum_iso,
			'uhrzeit'    => $uhrzeit,
			'ort'        => $ort ?: '',
			'preis'      => $preis_termin,
			'kapazitaet' => $kapazitaet ?: 0,
			'produkt_id' => $produkt_id,
		];
	}

	return $termine;
}

/**
 * Manueller Sync: Alle non-Probetraining Events durchgehen und Angebote erstellen/aktualisieren.
 * Gibt ein Array mit Statistiken zurück.
 */
function parkourone_sync_all_events_to_angebote() {
	$events = get_posts([
		'post_type'      => 'event',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	]);

	$created = 0;
	$updated = 0;

	foreach ($events as $event) {
		$angebot_typ = parkourone_get_event_angebot_typ($event->ID);
		if (!$angebot_typ || $angebot_typ === 'probetraining') continue;
		if (!in_array($angebot_typ, ['ferienkurs', 'workshop', 'kurs'], true)) continue;

		// Prüfen ob schon vorhanden
		$existing = get_posts([
			'post_type'      => 'angebot',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => [
				['key' => '_angebot_academyboard_event_id', 'value' => $event->ID]
			],
			'fields' => 'ids',
		]);

		$was_existing = !empty($existing);

		// Sync auslösen (nutzt die gleiche Funktion)
		parkourone_sync_event_to_angebot($event->ID);

		if ($was_existing) {
			$updated++;
		} else {
			$created++;
		}
	}

	return ['created' => $created, 'updated' => $updated];
}

// =====================================================
// Admin UI: Quelle-Spalte, Hinweis, Manueller Sync
// =====================================================

/**
 * Custom Column: "Quelle" in Angebote-Liste
 */
function parkourone_angebot_admin_columns($columns) {
	$new = [];
	foreach ($columns as $key => $val) {
		$new[$key] = $val;
		if ($key === 'title') {
			$new['angebot_quelle'] = 'Quelle';
			$new['angebot_buchungen'] = 'Buchungen';
		}
	}
	return $new;
}
add_filter('manage_angebot_posts_columns', 'parkourone_angebot_admin_columns');

function parkourone_angebot_admin_column_content($column, $post_id) {
	if ($column === 'angebot_quelle') {
		$quelle = get_post_meta($post_id, '_angebot_quelle', true);
		if ($quelle === 'academyboard') {
			echo '<span style="display:inline-block;padding:2px 8px;background:#0073aa;color:#fff;border-radius:3px;font-size:11px;font-weight:600;">AB</span>';
		} else {
			echo '<span style="color:#999;">Manuell</span>';
		}
		return;
	}

	if ($column === 'angebot_buchungen') {
		$termine = get_post_meta($post_id, '_angebot_termine', true);
		if (!is_array($termine) || empty($termine)) {
			echo '<span style="color:#999;">—</span>';
			return;
		}

		$total_sold = 0;
		$total_capacity = 0;
		$total_remaining = 0;
		$has_products = false;

		foreach ($termine as $termin) {
			$pid = intval($termin['produkt_id'] ?? 0);
			if ($pid && class_exists('WooCommerce')) {
				$wc_prod = wc_get_product($pid);
				if ($wc_prod) {
					$sold = parkourone_get_product_total_sold($pid);
					$stock = (int) $wc_prod->get_stock_quantity();
					$total_sold += $sold;
					$total_capacity += $sold + $stock;
					$total_remaining += $stock;
					$has_products = true;
				}
			}
		}

		if (!$has_products) {
			echo '<span style="color:#999;">—</span>';
			return;
		}

		$pct = $total_capacity > 0 ? round(($total_sold / $total_capacity) * 100) : 0;
		$color = $pct >= 80 ? '#00a32a' : ($pct >= 50 ? '#dba617' : '#2271b1');
		echo '<strong style="color:' . $color . ';">' . $total_sold . '</strong>';
		echo '<span style="color:#999;"> / ' . $total_capacity . '</span>';
		echo '<br><span style="font-size:11px;color:#999;">' . $pct . '% belegt</span>';
	}
}
add_action('manage_angebot_posts_custom_column', 'parkourone_angebot_admin_column_content', 10, 2);

/**
 * Admin-Notice auf Edit-Screen für AB-Angebote
 */
function parkourone_angebot_ab_edit_notice() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'angebot' || $screen->base !== 'post') return;

	global $post;
	if (!$post || get_post_meta($post->ID, '_angebot_quelle', true) !== 'academyboard') return;

	?>
	<div class="notice notice-info" style="border-left-color:#0073aa;">
		<p>
			<strong>Aus Eventsystem:</strong>
			Titel und Termine werden automatisch aus dem Academyboard synchronisiert.
			Änderungen an diesen Feldern werden beim nächsten Import überschrieben.
		</p>
	</div>
	<?php
}
add_action('admin_notices', 'parkourone_angebot_ab_edit_notice');

/**
 * Manueller Sync-Button auf Angebote-Listenseite
 */
function parkourone_angebot_sync_button_notice() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'angebot' || $screen->base !== 'edit') return;

	// Event-CPT muss existieren
	if (!post_type_exists('event')) return;

	?>
	<div class="notice notice-info" id="po-event-sync-notice" style="display:flex;align-items:center;gap:1rem;">
		<p style="flex:1;margin:0;">
			<strong>Event-Sync:</strong> Ferienkurse, Workshops und Kurse aus dem Eventsystem als Angebote importieren.
		</p>
		<button type="button" class="button button-primary" id="po-sync-events-btn">Events synchronisieren</button>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-sync-events-btn').on('click', function() {
			var $btn = $(this);
			$btn.prop('disabled', true).text('Synchronisiere...');
			$.post(ajaxurl, { action: 'po_sync_events_to_angebote', _wpnonce: '<?php echo wp_create_nonce('po_sync_events'); ?>' }, function(response) {
				if (response.success) {
					var d = response.data;
					$('#po-event-sync-notice').html(
						'<p><strong>' + d.created + ' neue Angebote erstellt, ' + d.updated + ' aktualisiert.</strong> <a href="">Seite neu laden</a></p>'
					);
				} else {
					alert('Fehler: ' + (response.data || 'Unbekannt'));
					$btn.prop('disabled', false).text('Events synchronisieren');
				}
			});
		});
	});
	</script>
	<?php
}
add_action('admin_notices', 'parkourone_angebot_sync_button_notice');

/**
 * AJAX: Manueller Event-Sync
 */
function parkourone_ajax_sync_events_to_angebote() {
	check_ajax_referer('po_sync_events', '_wpnonce');
	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Keine Berechtigung');
	}

	$result = parkourone_sync_all_events_to_angebote();
	wp_send_json_success($result);
}
add_action('wp_ajax_po_sync_events_to_angebote', 'parkourone_ajax_sync_events_to_angebote');