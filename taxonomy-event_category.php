<?php
/**
 * Taxonomy Archive Template for Event Categories
 * Handles both age groups (Zielgruppen) and locations (Städte/Standorte)
 */

get_header();

$term = get_queried_object();
$term_slug = $term->slug;
$term_name = $term->name;
$parent = $term->parent ? get_term($term->parent, 'event_category') : null;
$parent_slug = $parent ? $parent->slug : '';

// Determine type: is this an age group or location?
$is_age_group = ($parent_slug === 'alter');
$is_location = ($parent_slug === 'ortschaft');

// Get SEO content
$seo_content = parkourone_get_seo_content($term_slug);

// Fallback content if no SEO content defined
if (!$seo_content) {
	$seo_content = [
		'title' => "Parkour {$term_name}",
		'hero_subtitle' => "Entdecke Parkour-Trainings für {$term_name}",
		'intro_headline' => "Parkour für {$term_name}",
		'intro_text' => "Finde die passenden Parkour-Trainings für {$term_name} bei ParkourONE.",
		'benefits' => [
			'Professionelles Training',
			'Erfahrene Coaches',
			'Kleine Gruppen',
			'Sichere Trainingsumgebung'
		],
		'meta_description' => "Parkour für {$term_name}. Jetzt Probetraining buchen!"
	];
}

// City-specific handling
if ($is_location) {
	$city_name = parkourone_get_city_display_name($term_slug);
	$seo_content['title'] = "Parkour in {$city_name}";
	$seo_content['hero_subtitle'] = "Professionelles Parkour-Training in {$city_name}";
	$seo_content['intro_headline'] = "Dein Parkour-Training in {$city_name}";
	$seo_content['intro_text'] = "Bei ParkourONE {$city_name} findest du professionelles Parkour-Training für alle Altersgruppen. Unsere erfahrenen Coaches begleiten dich auf deinem Weg zur Bewegungsfreiheit.";
}

// Get events for this category
$events = get_posts([
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish',
	'tax_query' => [
		[
			'taxonomy' => 'event_category',
			'field' => 'slug',
			'terms' => $term_slug
		]
	]
]);
?>

<main class="po-taxonomy-archive">

	<!-- Hero Section -->
	<section class="po-taxonomy-hero">
		<div class="po-taxonomy-hero__content">
			<h1 class="po-taxonomy-hero__title"><?php echo esc_html($seo_content['title']); ?></h1>
			<p class="po-taxonomy-hero__subtitle"><?php echo esc_html($seo_content['hero_subtitle']); ?></p>
			<a href="/probetraining-buchen/" class="po-taxonomy-hero__cta">Probetraining buchen</a>
		</div>
	</section>

	<!-- Intro Section -->
	<section class="po-taxonomy-intro">
		<div class="po-taxonomy-intro__container">
			<h2><?php echo esc_html($seo_content['intro_headline']); ?></h2>
			<p class="po-taxonomy-intro__text"><?php echo esc_html($seo_content['intro_text']); ?></p>

			<?php if (!empty($seo_content['benefits'])): ?>
			<div class="po-taxonomy-intro__benefits">
				<h3>Was dich erwartet:</h3>
				<ul class="po-taxonomy-benefits">
					<?php foreach ($seo_content['benefits'] as $benefit): ?>
						<li class="po-taxonomy-benefits__item">
							<svg class="po-taxonomy-benefits__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
								<path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<?php echo esc_html($benefit); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Events/Classes Section -->
	<?php if (!empty($events)): ?>
	<section class="po-taxonomy-events">
		<div class="po-taxonomy-events__container">
			<h2 class="po-taxonomy-events__headline">Verfügbare Kurse</h2>

			<div class="po-taxonomy-events__grid">
				<?php foreach ($events as $event):
					$event_id = $event->ID;
					$headcoach = get_post_meta($event_id, '_event_headcoach', true);
					$headcoach_image = get_post_meta($event_id, '_event_headcoach_image_url', true);
					$start_time = get_post_meta($event_id, '_event_start_time', true);
					$end_time = get_post_meta($event_id, '_event_end_time', true);
					$dates = get_post_meta($event_id, '_event_dates', true);
					$first_date = is_array($dates) && !empty($dates) ? $dates[0] : null;

					// Get weekday
					$weekday = '';
					if ($first_date && !empty($first_date['date'])) {
						$timestamp = strtotime(str_replace('-', '.', $first_date['date']));
						if ($timestamp) {
							$days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
							$weekday = $days[date('w', $timestamp)];
						}
					}

					// Get location from categories
					$categories = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
					$location_name = '';
					foreach ($categories as $cat) {
						$cat_parent = $cat->parent ? get_term($cat->parent, 'event_category') : null;
						if ($cat_parent && $cat_parent->slug === 'ortschaft') {
							$location_name = $cat->name;
							break;
						}
					}
				?>
				<article class="po-event-card">
					<div class="po-event-card__header">
						<?php if ($headcoach_image): ?>
						<div class="po-event-card__coach-image">
							<img src="<?php echo esc_url($headcoach_image); ?>" alt="<?php echo esc_attr($headcoach); ?>">
						</div>
						<?php endif; ?>
						<div class="po-event-card__info">
							<h3 class="po-event-card__title"><?php echo esc_html($event->post_title); ?></h3>
							<?php if ($headcoach): ?>
							<p class="po-event-card__coach">mit <?php echo esc_html($headcoach); ?></p>
							<?php endif; ?>
						</div>
					</div>

					<div class="po-event-card__details">
						<?php if ($weekday): ?>
						<div class="po-event-card__detail">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
								<line x1="16" y1="2" x2="16" y2="6"/>
								<line x1="8" y1="2" x2="8" y2="6"/>
								<line x1="3" y1="10" x2="21" y2="10"/>
							</svg>
							<?php echo esc_html($weekday); ?>
						</div>
						<?php endif; ?>

						<?php if ($start_time && $end_time): ?>
						<div class="po-event-card__detail">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<circle cx="12" cy="12" r="10"/>
								<polyline points="12,6 12,12 16,14"/>
							</svg>
							<?php echo esc_html($start_time); ?> - <?php echo esc_html($end_time); ?> Uhr
						</div>
						<?php endif; ?>

						<?php if ($location_name): ?>
						<div class="po-event-card__detail">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
								<circle cx="12" cy="10" r="3"/>
							</svg>
							<?php echo esc_html($location_name); ?>
						</div>
						<?php endif; ?>
					</div>

					<a href="/probetraining-buchen/?event=<?php echo $event_id; ?>" class="po-event-card__cta">
						Probetraining buchen
					</a>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php else: ?>
	<section class="po-taxonomy-no-events">
		<div class="po-taxonomy-no-events__container">
			<p>Aktuell sind keine Kurse in dieser Kategorie verfügbar. Kontaktiere uns für weitere Informationen.</p>
			<a href="/kontakt/" class="po-btn">Kontakt aufnehmen</a>
		</div>
	</section>
	<?php endif; ?>

	<!-- Testimonials Section (if block exists) -->
	<?php
	$testimonials = get_posts([
		'post_type' => 'testimonial',
		'posts_per_page' => 6,
		'post_status' => 'publish'
	]);

	if (!empty($testimonials)):
	?>
	<section class="po-testimonials alignfull">
		<div class="po-testimonials__header">
			<h2 class="po-testimonials__headline">Erfahrungen unserer Teilnehmer_innen:</h2>
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
			<?php foreach ($testimonials as $testimonial):
				$name = $testimonial->post_title;
				$text = get_post_meta($testimonial->ID, '_testimonial_text', true);
				$stars = get_post_meta($testimonial->ID, '_testimonial_stars', true) ?: 5;
				$source = get_post_meta($testimonial->ID, '_testimonial_source', true);
			?>
			<article class="po-testimonials__card">
				<div class="po-testimonials__avatar">
					<?php echo esc_html(mb_substr($name, 0, 1)); ?>
				</div>
				<div class="po-testimonials__content">
					<div class="po-testimonials__header-row">
						<span class="po-testimonials__name"><?php echo esc_html($name); ?></span>
						<div class="po-testimonials__stars">
							<?php for ($i = 0; $i < $stars; $i++): ?>
								<span class="po-testimonials__star">★</span>
							<?php endfor; ?>
						</div>
					</div>
					<?php if ($source): ?>
						<span class="po-testimonials__source"><?php echo esc_html($source); ?></span>
					<?php endif; ?>
					<p class="po-testimonials__text"><?php echo esc_html($text); ?></p>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- CTA Section -->
	<section class="po-taxonomy-cta">
		<div class="po-taxonomy-cta__container">
			<h2>Bereit für dein erstes Training?</h2>
			<p>Buche jetzt dein Probetraining und erlebe Parkour hautnah.</p>
			<a href="/probetraining-buchen/" class="po-taxonomy-cta__button">Probetraining buchen</a>
		</div>
	</section>

</main>

<?php
// Add structured data for SEO
parkourone_taxonomy_structured_data($term, $seo_content, $events);
?>

<?php get_footer(); ?>
