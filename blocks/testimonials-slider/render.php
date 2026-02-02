<?php
/**
 * Testimonials Slider Block
 * Intelligente Testimonials wie FAQs - passend zum Seitentyp
 * Redesign: Vertikale, zentrierte Cards mit Profilbild
 */
$headline = $attributes['headline'] ?? 'Erfahrungen unserer Teilnehmer:';
$show_stars = $attributes['showStars'] ?? true;
$filter_age_group = $attributes['filterAgeGroup'] ?? '';
$page_type = $attributes['pageType'] ?? '';
$limit = $attributes['limit'] ?? 3;
$show_google_card = $attributes['showGoogleCard'] ?? true;
$google_reviews_url = $attributes['googleReviewsUrl'] ?? '';

// Google Reviews URL: Block-Attribut oder Customizer-Einstellung
if (empty($google_reviews_url) && function_exists('parkourone_get_google_reviews_url')) {
	$google_reviews_url = parkourone_get_google_reviews_url();
}

// Testimonials intelligent laden
if (!empty($page_type) && function_exists('parkourone_get_page_testimonials')) {
	// Intelligenter Modus: nach Seitentyp
	$testimonials = parkourone_get_page_testimonials($page_type, $limit, true);
} elseif (!empty($filter_age_group)) {
	// Legacy: nach Altersgruppe filtern
	$query_args = [
		'post_type' => 'testimonial',
		'posts_per_page' => $limit,
		'post_status' => 'publish',
		'orderby' => 'rand',
		'tax_query' => [
			[
				'taxonomy' => 'testimonial_age_group',
				'field' => 'slug',
				'terms' => $filter_age_group
			]
		]
	];
	$posts = get_posts($query_args);
	$testimonials = array_map(function($post) {
		$image_id = get_post_meta($post->ID, '_testimonial_image', true);
		return [
			'name' => $post->post_title,
			'text' => get_post_meta($post->ID, '_testimonial_text', true),
			'stars' => get_post_meta($post->ID, '_testimonial_stars', true) ?: 5,
			'source' => get_post_meta($post->ID, '_testimonial_source', true),
			'image' => $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : ''
		];
	}, $posts);
} else {
	// Fallback: gemischt
	if (function_exists('parkourone_get_testimonials_mixed')) {
		$testimonials = parkourone_get_testimonials_mixed($limit, true);
	} else {
		$posts = get_posts([
			'post_type' => 'testimonial',
			'posts_per_page' => $limit,
			'post_status' => 'publish',
			'orderby' => 'rand'
		]);
		$testimonials = array_map(function($post) {
			$image_id = get_post_meta($post->ID, '_testimonial_image', true);
			return [
				'name' => $post->post_title,
				'text' => get_post_meta($post->ID, '_testimonial_text', true),
				'stars' => get_post_meta($post->ID, '_testimonial_stars', true) ?: 5,
				'source' => get_post_meta($post->ID, '_testimonial_source', true),
				'image' => $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : ''
			];
		}, $posts);
	}
}

// Keine Testimonials vorhanden
if (empty($testimonials)) {
	return;
}
?>

<section class="po-testimonials alignfull">
	<div class="po-testimonials__header">
		<?php if ($headline): ?>
			<h2 class="po-testimonials__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<div class="po-testimonials__nav">
			<button class="po-testimonials__nav-btn po-testimonials__nav-btn--prev" aria-label="Zurück">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M15 18l-6-6 6-6"/>
				</svg>
			</button>
			<button class="po-testimonials__nav-btn po-testimonials__nav-btn--next" aria-label="Weiter">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M9 18l6-6-6-6"/>
				</svg>
			</button>
		</div>
	</div>

	<div class="po-testimonials__track">
		<?php foreach ($testimonials as $testimonial): ?>
			<article class="po-testimonials__card">
				<div class="po-testimonials__avatar">
					<?php if (!empty($testimonial['image'])): ?>
						<img src="<?php echo esc_url($testimonial['image']); ?>" alt="<?php echo esc_attr($testimonial['name']); ?>" class="po-testimonials__avatar-img">
					<?php else: ?>
						<span class="po-testimonials__avatar-initial"><?php echo esc_html(mb_substr($testimonial['name'], 0, 1)); ?></span>
					<?php endif; ?>
				</div>

				<?php if ($show_stars && $testimonial['stars']): ?>
					<div class="po-testimonials__stars">
						<?php for ($i = 0; $i < $testimonial['stars']; $i++): ?>
							<span class="po-testimonials__star">★</span>
						<?php endfor; ?>
					</div>
				<?php endif; ?>

				<blockquote class="po-testimonials__text"><?php echo esc_html($testimonial['text']); ?></blockquote>

				<div class="po-testimonials__author">
					<span class="po-testimonials__name"><?php echo esc_html($testimonial['name']); ?></span>
					<?php if (!empty($testimonial['source'])): ?>
						<span class="po-testimonials__source"><?php echo esc_html($testimonial['source']); ?></span>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>

		<?php // Google Reviews Card als letzter Slide ?>
		<?php if ($show_google_card && !empty($google_reviews_url)): ?>
			<article class="po-testimonials__card po-testimonials__card--google">
				<a href="<?php echo esc_url($google_reviews_url); ?>" target="_blank" rel="noopener noreferrer" class="po-testimonials__google-link">
					<div class="po-testimonials__google-icon">
						<svg width="56" height="56" viewBox="0 0 24 24" fill="none">
							<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
							<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
							<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
							<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
						</svg>
					</div>
					<h3 class="po-testimonials__google-title">Alle Rezensionen</h3>
					<p class="po-testimonials__google-text">Mehr Bewertungen auf Google lesen</p>
					<span class="po-testimonials__google-btn">Auf Google ansehen →</span>
				</a>
			</article>
		<?php endif; ?>
	</div>
</section>
