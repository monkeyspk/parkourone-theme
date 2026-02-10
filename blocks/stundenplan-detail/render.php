<?php
$headline = $attributes['headline'] ?? 'Unser Stundenplan';
$show_filters = $attributes['showFilters'] ?? true;
$filter_age = $attributes['filterAge'] ?? '';
$filter_location = $attributes['filterLocation'] ?? '';
$compact_view = $attributes['compactView'] ?? false;

// Get filter options
$alter_parent = get_term_by('slug', 'alter', 'event_category');
$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');

$age_options = [];
$location_options = [];

if ($alter_parent) {
	$age_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $alter_parent->term_id,
		'hide_empty' => false
	]);
	foreach ($age_terms as $term) {
		$age_options[] = ['slug' => $term->slug, 'name' => $term->name];
	}
}

if ($ortschaft_parent) {
	$location_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false
	]);
	foreach ($location_terms as $term) {
		$location_options[] = ['slug' => $term->slug, 'name' => $term->name];
	}
}

// Build query
$args = [
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
];

$tax_query = [];
if ($filter_age) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => explode(',', $filter_age)
	];
}
if ($filter_location) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => explode(',', $filter_location)
	];
}
if (!empty($tax_query)) {
	$tax_query['relation'] = 'AND';
	$args['tax_query'] = $tax_query;
}

$events = get_posts($args);

// Organize by weekday
$weekdays = [
	1 => ['name' => 'Montag', 'short' => 'Mo', 'events' => []],
	2 => ['name' => 'Dienstag', 'short' => 'Di', 'events' => []],
	3 => ['name' => 'Mittwoch', 'short' => 'Mi', 'events' => []],
	4 => ['name' => 'Donnerstag', 'short' => 'Do', 'events' => []],
	5 => ['name' => 'Freitag', 'short' => 'Fr', 'events' => []],
	6 => ['name' => 'Samstag', 'short' => 'Sa', 'events' => []],
	0 => ['name' => 'Sonntag', 'short' => 'So', 'events' => []]
];

foreach ($events as $event) {
	$event_id = $event->ID;
	$dates = get_post_meta($event_id, '_event_dates', true);
	$first_date = is_array($dates) && !empty($dates) ? $dates[0] : null;

	if (!$first_date || empty($first_date['date'])) continue;

	$timestamp = strtotime(str_replace('-', '.', $first_date['date']));
	if (!$timestamp) continue;

	$day_num = (int) date('w', $timestamp);

	// Get event data
	$headcoach = get_post_meta($event_id, '_event_headcoach', true);
	$headcoach_image = get_post_meta($event_id, '_event_headcoach_image_url', true);
	$start_time = get_post_meta($event_id, '_event_start_time', true);
	$end_time = get_post_meta($event_id, '_event_end_time', true);

	// Get categories
	$categories = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
	$age_name = '';
	$location_name = '';
	$age_slug = '';
	$location_slug = '';

	$offer_slug = '';
	foreach ($categories as $cat) {
		$cat_parent = $cat->parent ? get_term($cat->parent, 'event_category') : null;
		if ($cat_parent) {
			if ($cat_parent->slug === 'alter') {
				$age_name = $cat->name;
				$age_slug = $cat->slug;
			} elseif ($cat_parent->slug === 'ortschaft') {
				$location_name = $cat->name;
				$location_slug = $cat->slug;
			} elseif ($cat_parent->slug === 'angebot') {
				$offer_slug = $cat->slug;
			}
		}
	}

	// Skip Ferienkurse – they don't belong in the weekly Stundenplan
	if ($offer_slug === 'ferienkurs') {
		continue;
	}

	$weekdays[$day_num]['events'][] = [
		'id' => $event_id,
		'title' => $event->post_title,
		'headcoach' => $headcoach,
		'headcoach_image' => $headcoach_image,
		'start_time' => $start_time,
		'end_time' => $end_time,
		'age' => $age_name,
		'age_slug' => $age_slug,
		'location' => $location_name,
		'location_slug' => $location_slug
	];
}

// Sort events by start time
foreach ($weekdays as &$day) {
	usort($day['events'], function($a, $b) {
		return strcmp($a['start_time'], $b['start_time']);
	});
}
unset($day);
?>

<section class="po-stundenplan-detail alignfull <?php echo $compact_view ? 'is-compact' : ''; ?>"
	data-filter-age="<?php echo esc_attr($filter_age); ?>"
	data-filter-location="<?php echo esc_attr($filter_location); ?>">

	<div class="po-stundenplan-detail__header">
		<?php if ($headline): ?>
			<h2 class="po-stundenplan-detail__headline"><?php echo esc_html($headline); ?></h2>
		<?php endif; ?>

		<?php if ($show_filters): ?>
		<div class="po-stundenplan-detail__filters">
			<?php if (!empty($age_options)): ?>
			<div class="po-filter">
				<label for="filter-age">Altersgruppe</label>
				<select id="filter-age" class="po-filter__select" data-filter="age">
					<option value="">Alle</option>
					<?php foreach ($age_options as $option): ?>
						<option value="<?php echo esc_attr($option['slug']); ?>"><?php echo esc_html($option['name']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if (!empty($location_options)): ?>
			<div class="po-filter">
				<label for="filter-location">Standort</label>
				<select id="filter-location" class="po-filter__select" data-filter="location">
					<option value="">Alle</option>
					<?php foreach ($location_options as $option): ?>
						<option value="<?php echo esc_attr($option['slug']); ?>"><?php echo esc_html($option['name']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="po-stundenplan-detail__grid">
		<?php foreach ($weekdays as $day_num => $day): ?>
			<div class="po-stundenplan-day" data-day="<?php echo $day_num; ?>">
				<div class="po-stundenplan-day__header">
					<span class="po-stundenplan-day__name"><?php echo esc_html($day['name']); ?></span>
					<span class="po-stundenplan-day__short"><?php echo esc_html($day['short']); ?></span>
					<span class="po-stundenplan-day__count"><?php echo count($day['events']); ?></span>
				</div>
				<div class="po-stundenplan-day__events">
					<?php if (empty($day['events'])): ?>
						<div class="po-stundenplan-day__empty">Keine Kurse</div>
					<?php else: ?>
						<?php foreach ($day['events'] as $event): ?>
							<article class="po-schedule-event"
								data-age="<?php echo esc_attr($event['age_slug']); ?>"
								data-location="<?php echo esc_attr($event['location_slug']); ?>">
								<div class="po-schedule-event__time">
									<?php echo esc_html($event['start_time']); ?>
									<?php if (!$compact_view): ?>
										<span class="po-schedule-event__time-end">- <?php echo esc_html($event['end_time']); ?></span>
									<?php endif; ?>
								</div>
								<div class="po-schedule-event__content">
									<h4 class="po-schedule-event__title"><?php echo esc_html($event['title']); ?></h4>
									<?php if (!$compact_view): ?>
										<div class="po-schedule-event__meta">
											<?php if ($event['headcoach']): ?>
												<span class="po-schedule-event__coach">
													<?php if ($event['headcoach_image']): ?>
														<img src="<?php echo esc_url($event['headcoach_image']); ?>" alt="<?php echo esc_attr($event['headcoach']); ?>">
													<?php endif; ?>
													<?php echo esc_html($event['headcoach']); ?>
												</span>
											<?php endif; ?>
											<?php if ($event['location']): ?>
												<span class="po-schedule-event__location"><?php echo esc_html($event['location']); ?></span>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<?php if ($event['age']): ?>
										<span class="po-schedule-event__badge"><?php echo esc_html($event['age']); ?></span>
									<?php endif; ?>
								</div>
								<a href="/probetraining-buchen/?event=<?php echo $event['id']; ?>" class="po-schedule-event__link" aria-label="Probetraining buchen">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="9,18 15,12 9,6"/>
									</svg>
								</a>
							</article>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="po-stundenplan-detail__footer">
		<a href="/probetraining-buchen/" class="po-stundenplan-detail__cta">Probetraining buchen</a>
	</div>
</section>
