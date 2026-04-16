<?php
$headline = $attributes['headline'] ?? 'Angebote & Workshops';
$subtext = $attributes['subtext'] ?? '';
$show_all_link = $attributes['showAllLink'] ?? true;
$all_link_url = $attributes['allLinkUrl'] ?? '/angebote';
$all_link_text = $attributes['allLinkText'] ?? 'Alle Angebote';

// Featured Angebote laden
$alle_angebote = get_posts([
	'post_type' => 'angebot',
	'posts_per_page' => 10,
	'post_status' => 'publish',
	'orderby' => 'menu_order',
	'order' => 'ASC',
	'meta_query' => [
		[
			'key' => '_angebot_featured',
			'value' => '1'
		]
	]
]);

// Vergangene einmalige Events ausfiltern
$angebote = array_filter($alle_angebote, function($angebot) {
	return parkourone_angebot_is_visible($angebot->ID);
});

// Fallback: Alle Angebote wenn keine featured
if (empty($angebote)) {
	$alle_angebote = get_posts([
		'post_type' => 'angebot',
		'posts_per_page' => 6,
		'post_status' => 'publish',
		'orderby' => 'menu_order',
		'order' => 'ASC'
	]);
	$angebote = array_filter($alle_angebote, function($angebot) {
		return parkourone_angebot_is_visible($angebot->ID);
	});
}

$kategorie_labels = [
	'kostenlos'        => 'Kostenlos',
	'workshop'         => 'Workshop',
	'camp'             => 'Camp',
	'privatunterricht' => 'Privatunterricht',
	'kurs'             => 'Kurs',
	'ferienkurs'       => 'Ferienkurs',
];

if (empty($angebote)) {
	return;
}
?>

<section class="po-angebote-karussell alignfull"<?php if (!empty($attributes['anchor'])) echo ' id="' . esc_attr($attributes['anchor']) . '"'; ?>>
	<div class="po-angebote-karussell__header">
		<?php if ($headline): ?>
			<h2 class="po-angebote-karussell__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<?php if ($subtext): ?>
			<p class="po-angebote-karussell__subtext"><?php echo wp_kses_post($subtext); ?></p>
		<?php endif; ?>
	</div>

	<div class="po-angebote-karussell__track">
		<?php foreach ($angebote as $angebot):
			$id = $angebot->ID;
			$terms = wp_get_post_terms($id, 'angebot_kategorie', ['fields' => 'slugs']);
			$kategorie_slug = !empty($terms) ? $terms[0] : '';
			$kategorie_name = $kategorie_labels[$kategorie_slug] ?? '';
			$kurzbeschreibung = get_post_meta($id, '_angebot_kurzbeschreibung', true);
			$ansprechperson = get_post_meta($id, '_angebot_ansprechperson', true);
			$bild = parkourone_get_angebot_image($id, 'medium_large');

			// Single-Product-Daten (Kurs/Workshop/Ferienkurs teilen sich EIN WC-Produkt).
			// Direkt über Event auflösen statt aus gecachtem Meta — so wirkt der Sync
			// auch ohne dass save_post_event frisch gefeuert hat.
			$is_ferienkurs = get_post_meta($id, '_angebot_is_ferienkurs', true) === '1';
			$ferienkurs_produkt_id = function_exists('parkourone_get_angebot_single_product_id')
				? parkourone_get_angebot_single_product_id($id)
				: (int) get_post_meta($id, '_angebot_ferienkurs_produkt_id', true);
			$is_single_product = $ferienkurs_produkt_id > 0;

			$datum_range = '';
			$alle_termine = get_post_meta($id, '_angebot_termine', true);
			if ($is_single_product && is_array($alle_termine) && !empty($alle_termine)) {
				$daten = array_filter(array_column($alle_termine, 'datum'));
				sort($daten);
				if (count($daten) >= 2) {
					$first = strtotime(reset($daten));
					$last = strtotime(end($daten));
					if (date('n.Y', $first) === date('n.Y', $last)) {
						$datum_range = date_i18n('j.', $first) . ' – ' . date_i18n('j. F Y', $last);
					} else {
						$datum_range = date_i18n('j. F', $first) . ' – ' . date_i18n('j. F Y', $last);
					}
				} elseif (count($daten) === 1) {
					$datum_range = date_i18n('j. F Y', strtotime(reset($daten)));
				}
			}

			$ferienkurs_verfuegbar = null;
			$angebot_buchungsart = get_post_meta($id, '_angebot_buchungsart', true);
			if ($is_single_product && $angebot_buchungsart === 'woocommerce' && function_exists('wc_get_product')) {
				$fk_product = wc_get_product($ferienkurs_produkt_id);
				if ($fk_product && $fk_product->managing_stock()) {
					$ferienkurs_verfuegbar = (int) $fk_product->get_stock_quantity();
				}
			}

			// Beschreibung fürs Modal: post_content bevorzugt, sonst Fallback auf Kurzbeschreibung.
			$modal_beschreibung = apply_filters('the_content', $angebot->post_content);
			if (empty(trim(strip_tags($modal_beschreibung))) && !empty($kurzbeschreibung)) {
				$modal_beschreibung = wpautop($kurzbeschreibung);
			}

			// Termin-Label für die Card: Datum-Range (multi-day) oder nächstes Datum (buchbare Workshops).
			$card_termin_label = '';
			if ($datum_range) {
				$card_termin_label = $datum_range;
			} elseif (is_array($alle_termine) && !empty($alle_termine)) {
				$filtered_termine = parkourone_filter_vergangene_termine($alle_termine);
				if (!empty($filtered_termine)) {
					$first_termin = reset($filtered_termine);
					if (!empty($first_termin['datum'])) {
						$ts = strtotime($first_termin['datum']);
						if ($ts) {
							$card_termin_label = date_i18n('j. F Y', $ts);
						}
					}
				}
			}
			$show_card_termin = $card_termin_label && in_array($kategorie_slug, ['kurs', 'workshop', 'ferienkurs', 'camp'], true);

			// Modal-Daten
			$modal_data = [
				'id' => $id,
				'titel' => $angebot->post_title,
				'beschreibung' => $modal_beschreibung,
				'bild' => parkourone_get_angebot_image($id, 'large'),
				'kategorie' => $kategorie_name,
				'kategorie_slug' => $kategorie_slug,
				'wann' => get_post_meta($id, '_angebot_wann', true),
				'saison' => get_post_meta($id, '_angebot_saison', true),
				'wo' => get_post_meta($id, '_angebot_wo', true),
				'maps_link' => get_post_meta($id, '_angebot_maps_link', true),
				'voraussetzungen' => get_post_meta($id, '_angebot_voraussetzungen', true),
				'was_mitbringen' => get_post_meta($id, '_angebot_was_mitbringen', true),
				'preis' => get_post_meta($id, '_angebot_preis', true),
				'ansprechperson' => $ansprechperson,
				'ansprechperson_image' => function_exists('parkourone_get_coach_display_image_by_name')
					? parkourone_get_coach_display_image_by_name($ansprechperson, '80x80')
					: '',
				'buchungsart' => get_post_meta($id, '_angebot_buchungsart', true),
				'teilnehmer_typ' => get_post_meta($id, '_angebot_teilnehmer_typ', true) ?: 'standard',
				'cta_url' => get_post_meta($id, '_angebot_cta_url', true),
				'termine' => parkourone_enrich_termine_with_stock(
					parkourone_filter_vergangene_termine($alle_termine)
				),
				'is_ferienkurs' => $is_ferienkurs,
				'ferienkurs_produkt_id' => $ferienkurs_produkt_id,
				'datum_range' => $datum_range,
				'ferienkurs_verfuegbar' => $ferienkurs_verfuegbar,
			];
		?>
		<article class="po-angebote-karussell__card" data-modal='<?php echo esc_attr(json_encode($modal_data)); ?>'>
			<div class="po-angebote-karussell__card-image">
				<img src="<?php echo esc_url($bild); ?>" alt="<?php echo esc_attr($angebot->post_title); ?>" loading="lazy">
				<?php if ($kategorie_name): ?>
					<span class="po-angebote-karussell__badge po-angebote-karussell__badge--<?php echo esc_attr($kategorie_slug); ?>">
						<?php echo esc_html($kategorie_name); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="po-angebote-karussell__card-gradient"></div>
			<div class="po-angebote-karussell__card-content">
				<h3 class="po-angebote-karussell__card-title"><?php echo esc_html($angebot->post_title); ?></h3>
				<?php if ($kurzbeschreibung): ?>
					<p class="po-angebote-karussell__card-desc"><?php echo esc_html($kurzbeschreibung); ?></p>
				<?php endif; ?>
				<?php if ($show_card_termin): ?>
					<p class="po-angebote-karussell__card-termin"><?php
						echo $datum_range
							? esc_html($card_termin_label)
							: esc_html('Nächster Termin: ' . $card_termin_label);
					?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php endforeach; ?>
	</div>

	<?php if ($show_all_link && $all_link_url): ?>
	<div class="po-angebote-karussell__footer">
		<a href="<?php echo esc_url($all_link_url); ?>" class="po-angebote-karussell__all-link">
			<?php echo esc_html($all_link_text); ?> &rarr;
		</a>
	</div>
	<?php endif; ?>
</section>

<!-- Modal (gleiche Struktur wie Grid) -->
<div class="po-angebote-modal" id="po-angebote-karussell-modal" aria-hidden="true">
	<div class="po-angebote-modal__backdrop"></div>
	<div class="po-angebote-modal__container">
		<button class="po-angebote-modal__close" aria-label="Schließen">&times;</button>
		<div class="po-angebote-modal__content"></div>
	</div>
</div>

<!-- AJAX Config für Buchungen -->
<script>
window.poAngebotBooking = window.poAngebotBooking || {
	ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
	nonce: '<?php echo wp_create_nonce('po_angebot_booking_nonce'); ?>',
	cartUrl: '<?php echo esc_url(function_exists('parkourone_get_checkout_url') ? parkourone_get_checkout_url() : home_url('/kasse/')); ?>'
};
</script>
