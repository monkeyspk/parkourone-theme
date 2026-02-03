<?php
$show_filter = $attributes['showFilter'] ?? true;
$columns = $attributes['columns'] ?? 3;

$alle_angebote = get_posts([
	'post_type' => 'angebot',
	'posts_per_page' => -1,
	'post_status' => 'publish',
	'orderby' => 'menu_order',
	'order' => 'ASC'
]);

// Vergangene einmalige Events ausfiltern
$angebote = array_filter($alle_angebote, function($angebot) {
	return parkourone_angebot_is_visible($angebot->ID);
});

$kategorien = get_terms([
	'taxonomy' => 'angebot_kategorie',
	'hide_empty' => true
]);

$kategorie_labels = [
	'kostenlos' => 'Kostenlos',
	'workshop' => 'Workshop',
	'camp' => 'Camp',
	'privatunterricht' => 'Privatunterricht'
];
?>

<section class="po-angebote-grid alignfull" data-columns="<?php echo esc_attr($columns); ?>">
	<?php if ($show_filter && !empty($kategorien)): ?>
	<div class="po-angebote-grid__filter">
		<button class="po-angebote-grid__filter-btn active" data-filter="alle">Alle</button>
		<?php foreach ($kategorien as $kat): ?>
			<button class="po-angebote-grid__filter-btn" data-filter="<?php echo esc_attr($kat->slug); ?>">
				<?php echo esc_html($kategorie_labels[$kat->slug] ?? $kat->name); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="po-angebote-grid__cards">
		<?php foreach ($angebote as $angebot):
			$id = $angebot->ID;
			$terms = wp_get_post_terms($id, 'angebot_kategorie', ['fields' => 'slugs']);
			$kategorie_slug = !empty($terms) ? $terms[0] : '';
			$kategorie_name = $kategorie_labels[$kategorie_slug] ?? '';

			$kurzbeschreibung = get_post_meta($id, '_angebot_kurzbeschreibung', true);
			$buchungsart = get_post_meta($id, '_angebot_buchungsart', true);
			$bild = parkourone_get_angebot_image($id, 'medium_large');

			// Daten für Modal
			$modal_data = [
				'id' => $id,
				'titel' => $angebot->post_title,
				'beschreibung' => apply_filters('the_content', $angebot->post_content),
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
				'ansprechperson' => get_post_meta($id, '_angebot_ansprechperson', true),
				'buchungsart' => $buchungsart,
				'teilnehmer_typ' => get_post_meta($id, '_angebot_teilnehmer_typ', true) ?: 'standard',
				'cta_url' => get_post_meta($id, '_angebot_cta_url', true),
				'termine' => parkourone_filter_vergangene_termine(get_post_meta($id, '_angebot_termine', true))
			];
		?>
		<article class="po-angebote-grid__card" data-kategorie="<?php echo esc_attr($kategorie_slug); ?>" data-modal='<?php echo esc_attr(json_encode($modal_data)); ?>'>
			<div class="po-angebote-grid__card-image">
				<img src="<?php echo esc_url($bild); ?>" alt="<?php echo esc_attr($angebot->post_title); ?>" loading="lazy">
				<?php if ($kategorie_name): ?>
					<span class="po-angebote-grid__badge po-angebote-grid__badge--<?php echo esc_attr($kategorie_slug); ?>">
						<?php echo esc_html($kategorie_name); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="po-angebote-grid__card-content">
				<h3 class="po-angebote-grid__card-title"><?php echo esc_html($angebot->post_title); ?></h3>
				<?php if ($kurzbeschreibung): ?>
					<p class="po-angebote-grid__card-desc"><?php echo esc_html($kurzbeschreibung); ?></p>
				<?php endif; ?>
				<span class="po-angebote-grid__card-cta">Mehr erfahren</span>
			</div>
		</article>
		<?php endforeach; ?>
	</div>

	<?php if (empty($angebote)): ?>
	<p class="po-angebote-grid__empty">Keine Angebote vorhanden.</p>
	<?php endif; ?>
</section>

<!-- Modal -->
<div class="po-angebote-modal" id="po-angebote-modal" aria-hidden="true">
	<div class="po-angebote-modal__backdrop"></div>
	<div class="po-angebote-modal__container">
		<button class="po-angebote-modal__close" aria-label="Schließen">&times;</button>
		<div class="po-angebote-modal__content">
			<!-- Content wird per JS eingefügt -->
		</div>
	</div>
</div>

<!-- AJAX Config für Buchungen -->
<script>
window.poAngebotBooking = window.poAngebotBooking || {
	ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
	nonce: '<?php echo wp_create_nonce('po_angebot_booking_nonce'); ?>',
	cartUrl: '<?php echo class_exists('WooCommerce') ? esc_url(wc_get_cart_url()) : ''; ?>'
};
</script>
