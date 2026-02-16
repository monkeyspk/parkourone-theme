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
	'kostenlos' => 'Kostenlos',
	'workshop' => 'Workshop',
	'camp' => 'Camp',
	'privatunterricht' => 'Privatunterricht'
];

if (empty($angebote)) {
	return;
}
?>

<section class="po-angebote-karussell alignfull">
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
			$bild = parkourone_get_angebot_image($id, 'medium_large');

			// Modal-Daten
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
				'buchungsart' => get_post_meta($id, '_angebot_buchungsart', true),
				'teilnehmer_typ' => get_post_meta($id, '_angebot_teilnehmer_typ', true) ?: 'standard',
				'cta_url' => get_post_meta($id, '_angebot_cta_url', true),
				'termine' => parkourone_filter_vergangene_termine(get_post_meta($id, '_angebot_termine', true))
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
	cartUrl: '<?php echo class_exists('WooCommerce') ? esc_url(wc_get_checkout_url()) : ''; ?>'
};
</script>
