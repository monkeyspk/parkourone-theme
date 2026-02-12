<?php
$headline     = $attributes['headline'] ?? 'Nächste Probetrainings';
$buttonText   = $attributes['buttonText'] ?? 'Jetzt buchen';
$initialDays  = $attributes['initialDays'] ?? 8;
$unique_id    = 'event-day-slider-' . uniqid();

// Filter-Optionen laden (nur Alter + Standort)
$filters        = function_exists('parkourone_get_event_filters') ? parkourone_get_event_filters() : [];
$age_terms      = $filters['age'] ?? [];
$location_terms = $filters['location'] ?? [];

// Farben fuer Altersgruppen (gleich wie event-booking)
$age_colors = [
	'minis'   => '#ff9500',
	'kids'    => '#34c759',
	'juniors' => '#007aff',
	'adults'  => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

$has_filters = !empty($age_terms) || !empty($location_terms);
?>

<section class="po-eds" id="<?php echo esc_attr($unique_id); ?>"
	data-button-text="<?php echo esc_attr($buttonText); ?>"
	data-initial-days="<?php echo esc_attr($initialDays); ?>"
	data-age-colors="<?php echo esc_attr(json_encode($age_colors)); ?>">

	<?php if ($headline): ?>
		<h2 class="po-eds__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php if ($has_filters): ?>
	<div class="po-eds__filters">
		<?php if (!empty($age_terms)): ?>
		<div class="po-eds__dropdown" data-filter-type="age">
			<button type="button" class="po-eds__dropdown-trigger" aria-expanded="false">
				<span class="po-eds__dropdown-value">Alle Altersgruppen</span>
				<svg class="po-eds__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eds__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eds__dropdown-option is-selected" data-value="">Alle Altersgruppen</button>
				<?php foreach ($age_terms as $term): ?>
				<button type="button" class="po-eds__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<span class="po-eds__dropdown-dot" style="background: <?php echo esc_attr($age_colors[$term['slug']] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($location_terms)): ?>
		<div class="po-eds__dropdown" data-filter-type="location">
			<button type="button" class="po-eds__dropdown-trigger" aria-expanded="false">
				<span class="po-eds__dropdown-value">Alle Standorte</span>
				<svg class="po-eds__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eds__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eds__dropdown-option is-selected" data-value="">Alle Standorte</button>
				<?php foreach ($location_terms as $term): ?>
				<button type="button" class="po-eds__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Date Tabs -->
	<div class="po-eds__date-nav">
		<div class="po-eds__date-track" data-date-track></div>
	</div>

	<!-- Event Cards fuer ausgewaehlten Tag -->
	<div class="po-eds__slider">
		<div class="po-eds__slider-track" data-slider-track></div>
	</div>

	<!-- Loading Skeleton -->
	<div class="po-eds__skeleton" data-skeleton>
		<div class="po-eds__skeleton-tabs">
			<div class="po-eds__skeleton-tab"></div>
			<div class="po-eds__skeleton-tab"></div>
			<div class="po-eds__skeleton-tab"></div>
			<div class="po-eds__skeleton-tab"></div>
		</div>
		<div class="po-eds__skeleton-cards">
			<div class="po-eds__skeleton-card"></div>
			<div class="po-eds__skeleton-card"></div>
			<div class="po-eds__skeleton-card"></div>
		</div>
	</div>

	<!-- Empty State -->
	<div class="po-eds__empty" style="display: none;" data-empty>
		<p>Keine Trainings in den nächsten Tagen gefunden.</p>
	</div>
</section>
