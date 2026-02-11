<?php
$headline       = $attributes['headline'] ?? 'Trainings buchen';
$filterAge      = $attributes['filterAge'] ?? '';
$filterLocation = $attributes['filterLocation'] ?? '';
$filterOffer    = $attributes['filterOffer'] ?? '';
$filterWeekday  = $attributes['filterWeekday'] ?? '';
$buttonText     = $attributes['buttonText'] ?? 'Jetzt buchen';
$unique_id      = 'event-booking-' . uniqid();

// Filter-Optionen laden
$filters = function_exists('parkourone_get_event_filters') ? parkourone_get_event_filters() : [];
$age_terms      = $filters['age'] ?? [];
$location_terms = $filters['location'] ?? [];
$offer_terms    = $filters['offer'] ?? [];
$weekday_terms  = $filters['weekday'] ?? [];

// Farben fuer Altersgruppen (gleich wie Klassen-Slider)
$age_colors = [
	'minis'   => '#ff9500',
	'kids'    => '#34c759',
	'juniors' => '#007aff',
	'adults'  => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

$has_filters = !empty($age_terms) || !empty($location_terms) || !empty($offer_terms) || !empty($weekday_terms);
?>

<section class="po-eb" id="<?php echo esc_attr($unique_id); ?>"
	data-filter-age="<?php echo esc_attr($filterAge); ?>"
	data-filter-location="<?php echo esc_attr($filterLocation); ?>"
	data-filter-offer="<?php echo esc_attr($filterOffer); ?>"
	data-filter-weekday="<?php echo esc_attr($filterWeekday); ?>"
	data-button-text="<?php echo esc_attr($buttonText); ?>">

	<?php if ($headline): ?>
		<h2 class="po-eb__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php if ($has_filters): ?>
	<div class="po-eb__filters">
		<?php if (!empty($offer_terms)): ?>
		<div class="po-eb__dropdown" data-filter-type="offer">
			<button type="button" class="po-eb__dropdown-trigger" aria-expanded="false">
				<span class="po-eb__dropdown-value">Alle Angebote</span>
				<svg class="po-eb__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eb__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eb__dropdown-option is-selected" data-value="">Alle Angebote</button>
				<?php foreach ($offer_terms as $term): ?>
				<button type="button" class="po-eb__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($age_terms)): ?>
		<div class="po-eb__dropdown" data-filter-type="age">
			<button type="button" class="po-eb__dropdown-trigger" aria-expanded="false">
				<span class="po-eb__dropdown-value">Alle Altersgruppen</span>
				<svg class="po-eb__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eb__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eb__dropdown-option is-selected" data-value="">Alle Altersgruppen</button>
				<?php foreach ($age_terms as $term): ?>
				<button type="button" class="po-eb__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<span class="po-eb__dropdown-dot" style="background: <?php echo esc_attr($age_colors[$term['slug']] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($location_terms)): ?>
		<div class="po-eb__dropdown" data-filter-type="location">
			<button type="button" class="po-eb__dropdown-trigger" aria-expanded="false">
				<span class="po-eb__dropdown-value">Alle Standorte</span>
				<svg class="po-eb__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eb__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eb__dropdown-option is-selected" data-value="">Alle Standorte</button>
				<?php foreach ($location_terms as $term): ?>
				<button type="button" class="po-eb__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($weekday_terms)): ?>
		<div class="po-eb__dropdown" data-filter-type="weekday">
			<button type="button" class="po-eb__dropdown-trigger" aria-expanded="false">
				<span class="po-eb__dropdown-value">Alle Wochentage</span>
				<svg class="po-eb__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eb__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eb__dropdown-option is-selected" data-value="">Alle Wochentage</button>
				<?php foreach ($weekday_terms as $term): ?>
				<button type="button" class="po-eb__dropdown-option" data-value="<?php echo esc_attr($term['slug']); ?>">
					<?php echo esc_html($term['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Loading Skeleton -->
	<div class="po-eb__skeleton" aria-hidden="true">
		<div class="po-eb__skeleton-card"></div>
		<div class="po-eb__skeleton-card"></div>
		<div class="po-eb__skeleton-card"></div>
	</div>

	<!-- Cards Container (gefuellt via view.js) -->
	<div class="po-eb__grid" data-event-grid></div>

	<!-- Empty State -->
	<div class="po-eb__empty" style="display: none;">
		<p>Keine Events gefunden.</p>
	</div>

	<!-- Modal Container (gefuellt via view.js) -->
	<div data-event-modals></div>
</section>
