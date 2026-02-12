<?php
$headline    = $attributes['headline'] ?? 'Nächste Probetrainings';
$buttonText  = $attributes['buttonText'] ?? 'Jetzt buchen';
$initialDays = $attributes['initialDays'] ?? 8;
$unique_id   = 'event-day-slider-' . uniqid();

// Farben fuer Altersgruppen
$age_colors = [
	'minis'   => '#ff9500',
	'kids'    => '#34c759',
	'juniors' => '#007aff',
	'adults'  => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

// Filter-Taxonomie-Terms laden
$alter_parent = get_term_by('slug', 'alter', 'event_category');
$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');

$alter_terms = [];
$ortschaft_terms = [];

if ($alter_parent && !is_wp_error($alter_parent)) {
	$alter_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $alter_parent->term_id,
		'hide_empty' => false
	]);
	if (is_wp_error($alter_terms)) $alter_terms = [];
}

if ($ortschaft_parent && !is_wp_error($ortschaft_parent)) {
	$ortschaft_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false
	]);
	if (is_wp_error($ortschaft_terms)) $ortschaft_terms = [];
}

// ========================================
// Events laden und nach Datum gruppieren
// ========================================

$query = new WP_Query([
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
]);

$today = strtotime('today');
$events_by_date = [];

if ($query->have_posts()) {
	while ($query->have_posts()) {
		$query->the_post();
		$event_id = get_the_ID();

		$event_dates = get_post_meta($event_id, '_event_dates', true);
		if (!is_array($event_dates) || empty($event_dates)) continue;

		$start_time = get_post_meta($event_id, '_event_start_time', true);
		$end_time   = get_post_meta($event_id, '_event_end_time', true);
		$headcoach  = get_post_meta($event_id, '_event_headcoach', true);
		$headcoach_image = get_post_meta($event_id, '_event_headcoach_image_url', true);
		$venue      = get_post_meta($event_id, '_event_venue', true);

		// Taxonomien ermitteln
		$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		$age_slug = '';
		$location_slug = '';
		$offer_slug = '';

		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && !is_wp_error($parent)) {
					if ($parent->slug === 'alter') $age_slug = $term->slug;
					if ($parent->slug === 'ortschaft') $location_slug = $term->slug;
					if ($parent->slug === 'angebot') $offer_slug = $term->slug;
				}
			}
		}

		// Ferienkurse ueberspringen
		if ($offer_slug === 'ferienkurs') continue;

		$color = $age_colors[$age_slug] ?? '#0066cc';

		// Alle Datumseintraege durchgehen
		foreach ($event_dates as $date_entry) {
			if (empty($date_entry['date'])) continue;

			// Datum parsen (Format: DD-MM-YYYY oder DD.MM.YYYY)
			$date_str = str_replace('.', '-', $date_entry['date']);
			$parts = explode('-', $date_str);
			if (count($parts) !== 3) continue;

			// Konsistent DD-MM-YYYY
			$day   = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
			$month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
			$year  = $parts[2];

			$timestamp = strtotime("$year-$month-$day");
			if (!$timestamp || $timestamp < $today) continue;

			$date_key = "$year-$month-$day"; // sortierbar

			// Stock/Verfuegbarkeit aus dem Datumseintrag
			$stock = isset($date_entry['available_seats']) ? intval($date_entry['available_seats']) : -1;

			// Product-ID fuer Buchung suchen
			$product_id = '';
			if (function_exists('parkourone_get_available_dates_for_event')) {
				// Nicht nochmal aufrufen – wir nutzen die date_entry Daten direkt
			}

			$events_by_date[$date_key][] = [
				'event_id'        => $event_id,
				'title'           => get_the_title(),
				'start_time'      => $start_time,
				'end_time'        => $end_time,
				'headcoach'       => $headcoach,
				'headcoach_image' => $headcoach_image,
				'venue'           => $date_entry['venue'] ?? $venue,
				'age_slug'        => $age_slug,
				'location_slug'   => $location_slug,
				'color'           => $color,
				'stock'           => $stock,
				'date_formatted'  => "$day.$month.$year",
				'filter_data'     => trim($age_slug . ' ' . $location_slug),
			];
		}
	}
	wp_reset_postdata();
}

// Nach Datum sortieren
ksort($events_by_date);

// Events innerhalb jedes Tages nach Startzeit sortieren
foreach ($events_by_date as $date_key => &$day_events) {
	usort($day_events, function($a, $b) {
		return strcmp($a['start_time'], $b['start_time']);
	});
}
unset($day_events);

// Tage generieren (ab heute, initialDays Stueck)
$day_labels = [];
for ($i = 0; $i < $initialDays; $i++) {
	$ts = strtotime("+$i days", $today);
	$date_key = date('Y-m-d', $ts);
	$day_of_week = date('w', $ts);
	$day_names = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
	$month_names = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];

	if ($i === 0) {
		$label = 'Heute';
	} elseif ($i === 1) {
		$label = 'Morgen';
	} elseif ($i === 2) {
		$label = 'Übermorgen';
	} else {
		$label = $day_names[$day_of_week] . ', ' . date('j', $ts) . '. ' . $month_names[date('n', $ts) - 1];
	}

	$day_labels[$date_key] = $label;
}

// Auch zukuenftige Tage mit Events einbeziehen die ueber initialDays hinausgehen
foreach ($events_by_date as $date_key => $evts) {
	if (!isset($day_labels[$date_key])) {
		$ts = strtotime($date_key);
		if ($ts >= $today) {
			$day_of_week = date('w', $ts);
			$day_names = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
			$month_names = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
			$day_labels[$date_key] = $day_names[$day_of_week] . ', ' . date('j', $ts) . '. ' . $month_names[date('n', $ts) - 1];
		}
	}
}
ksort($day_labels);

// Nur Tage mit Events fuer die Day-Cards
$days_with_events = [];
foreach ($day_labels as $date_key => $label) {
	if (!empty($events_by_date[$date_key])) {
		$days_with_events[$date_key] = $label;
	}
}

$has_events = !empty($days_with_events);
$has_filters = !empty($alter_terms) || !empty($ortschaft_terms);
?>

<?php if ($has_events): ?>
<section class="po-eds" id="<?php echo esc_attr($unique_id); ?>">

	<?php if ($headline): ?>
		<h2 class="po-eds__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php if ($has_filters): ?>
	<div class="po-eds__fab">
		<button type="button" class="po-eds__fab-trigger">
			<span class="po-eds__fab-text">Filtern</span>
			<svg class="po-eds__fab-icon" viewBox="0 0 24 24" fill="none">
				<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<div class="po-eds__fab-dropdown">
			<div class="po-eds__fab-group">
				<span class="po-eds__fab-group-label">Alle anzeigen</span>
				<button type="button" class="po-eds__fab-option is-active" data-filter="all">Alle Trainings</button>
			</div>
			<?php if (!empty($alter_terms)): ?>
			<div class="po-eds__fab-group">
				<span class="po-eds__fab-group-label">Nach Alter</span>
				<?php foreach ($alter_terms as $term): ?>
				<button type="button" class="po-eds__fab-option" data-filter="<?php echo esc_attr($term->slug); ?>">
					<span class="po-eds__fab-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php if (!empty($ortschaft_terms)): ?>
			<div class="po-eds__fab-group">
				<span class="po-eds__fab-group-label">Nach Standort</span>
				<?php foreach ($ortschaft_terms as $term): ?>
				<button type="button" class="po-eds__fab-option" data-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="po-eds__slider">
		<div class="po-eds__slider-track">
			<?php foreach ($days_with_events as $date_key => $label): ?>
				<?php $day_events = $events_by_date[$date_key]; ?>
				<div class="po-eds__day-card" data-date="<?php echo esc_attr($date_key); ?>">
					<div class="po-eds__day-card-header">
						<span class="po-eds__day-card-name"><?php echo esc_html($label); ?></span>
						<span class="po-eds__day-card-count"><?php echo count($day_events); ?></span>
					</div>
					<div class="po-eds__day-card-list">
						<?php foreach ($day_events as $ev):
							$filter_data = trim($ev['age_slug'] . ' ' . $ev['location_slug']);
							$time_text = $ev['start_time'];
							if ($ev['end_time']) $time_text .= ' – ' . $ev['end_time'];

							$stock = $ev['stock'];
							$is_soldout = ($stock === 0);
							$stock_html = '';
							if ($stock === 0) {
								$stock_html = '<span class="po-eds__card-stock po-eds__card-stock--none">Ausgebucht</span>';
							} elseif ($stock > 0 && $stock <= 3) {
								$stock_html = '<span class="po-eds__card-stock po-eds__card-stock--low">' . $stock . ($stock === 1 ? ' Platz' : ' Plätze') . '</span>';
							} elseif ($stock > 3) {
								$stock_html = '<span class="po-eds__card-stock">' . $stock . ' Plätze</span>';
							}
						?>
						<div class="po-eds__card-item<?php echo $is_soldout ? ' is-soldout' : ''; ?>" data-filters="<?php echo esc_attr($filter_data); ?>">
							<?php if (!empty($ev['headcoach_image'])): ?>
								<img src="<?php echo esc_url($ev['headcoach_image']); ?>" alt="<?php echo esc_attr($ev['headcoach'] ?? ''); ?>" class="po-eds__card-img">
							<?php endif; ?>
							<div class="po-eds__card-content">
								<span class="po-eds__card-time"><?php echo esc_html($time_text); ?></span>
								<span class="po-eds__card-title" style="color: <?php echo esc_attr($ev['color']); ?>"><?php echo esc_html($ev['title']); ?></span>
								<?php if ($ev['headcoach']): ?>
								<span class="po-eds__card-coach"><?php echo esc_html($ev['headcoach']); ?></span>
								<?php endif; ?>
								<?php if ($ev['venue']): ?>
								<span class="po-eds__card-venue"><?php echo esc_html($ev['venue']); ?></span>
								<?php endif; ?>
								<?php echo $stock_html; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if (empty($days_with_events)): ?>
	<div class="po-eds__empty">
		<p>Keine Trainings in den nächsten Tagen gefunden.</p>
	</div>
	<?php endif; ?>

</section>
<?php endif; ?>
