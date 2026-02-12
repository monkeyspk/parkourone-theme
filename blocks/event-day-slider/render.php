<?php
$headline   = $attributes['headline'] ?? 'Nächste Probetrainings';
$buttonText = $attributes['buttonText'] ?? 'Jetzt buchen';
$unique_id  = 'eds-' . uniqid();

// Farben fuer Altersgruppen
$age_colors = [
	'minis'   => '#ff9500',
	'kids'    => '#34c759',
	'juniors' => '#007aff',
	'adults'  => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

// Mood-Texte fuer Modal
$mood_texts = [
	'minis'   => 'Erste Bewegungserfahrungen in spielerischer Atmosphäre - hier entdecken die Kleinsten ihre motorischen Fähigkeiten.',
	'kids'    => 'Spielerisch Bewegungstalente entdecken: Klettern, Springen und Balancieren in einer sicheren Umgebung.',
	'juniors' => 'Von den Basics bis zu fortgeschrittenen Moves - hier entwickelst du deine Skills in einer motivierenden Gruppe.',
	'adults'  => 'Den eigenen Körper neu entdecken, Grenzen verschieben und Techniken verfeinern - Training für alle, die mehr wollen.',
	'women'   => 'In entspannter Atmosphäre unter Frauen trainieren - Kraft, Beweglichkeit und Selbstvertrauen aufbauen.',
	'seniors' => 'Koordination erhalten, Fitness aufbauen und mit Gleichgesinnten trainieren - beweglich bleiben in jedem Alter.',
	'masters' => 'Erfahrung trifft Bewegung - Training für alle, die auch mit den Jahren aktiv und beweglich bleiben wollen.',
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
$rendered_modals = []; // Deduplizierung: nur 1 Modal pro Event-ID
$event_data_for_modals = []; // Event-Daten fuer Modals sammeln

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

		// Event-Daten fuer Modal merken (nur einmal pro Event-ID)
		if (!isset($event_data_for_modals[$event_id])) {
			$event_data_for_modals[$event_id] = [
				'event_id'        => $event_id,
				'title'           => get_the_title(),
				'start_time'      => $start_time,
				'end_time'        => $end_time,
				'headcoach'       => $headcoach,
				'venue'           => $venue,
				'age_slug'        => $age_slug,
				'color'           => $color,
			];
		}

		// Alle Datumseintraege durchgehen
		foreach ($event_dates as $date_entry) {
			if (empty($date_entry['date'])) continue;

			// Datum parsen (Format: DD-MM-YYYY oder DD.MM.YYYY)
			$date_str = str_replace('.', '-', $date_entry['date']);
			$parts = explode('-', $date_str);
			if (count($parts) !== 3) continue;

			$day   = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
			$month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
			$year  = $parts[2];

			$timestamp = strtotime("$year-$month-$day");
			if (!$timestamp || $timestamp < $today) continue;

			$date_key = "$year-$month-$day";

			$stock = isset($date_entry['available_seats']) ? intval($date_entry['available_seats']) : -1;

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
				'filter_data'     => trim($age_slug . ' ' . $location_slug),
			];
		}
	}
	wp_reset_postdata();
}

// Events innerhalb jedes Tages nach Startzeit sortieren
foreach ($events_by_date as $date_key => &$day_events) {
	usort($day_events, function($a, $b) {
		return strcmp($a['start_time'], $b['start_time']);
	});
}
unset($day_events);

// ========================================
// Wochen generieren (8 Wochen ab aktueller Woche)
// ========================================

$today_key     = date('Y-m-d', $today);
$tomorrow_key  = date('Y-m-d', strtotime('+1 day', $today));
$day_after_key = date('Y-m-d', strtotime('+2 days', $today));

$day_names   = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$month_names = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];

// Montag dieser Woche berechnen
$current_dow = date('w', $today); // 0=So, 1=Mo, ...
$days_since_monday = ($current_dow === 0) ? 6 : ($current_dow - 1);
$this_monday = strtotime("-{$days_since_monday} days", $today);

$total_weeks = 8;
$weeks = [];

for ($w = 0; $w < $total_weeks; $w++) {
	$week_monday = strtotime("+{$w} weeks", $this_monday);
	$week_sunday = strtotime('+6 days', $week_monday);

	// Wochen-Label: "10. – 16. Feb." oder "24. Feb. – 2. März"
	$mon_month = date('n', $week_monday);
	$sun_month = date('n', $week_sunday);
	if ($mon_month === $sun_month) {
		$week_label = date('j', $week_monday) . '. – ' . date('j', $week_sunday) . '. ' . $month_names[$mon_month - 1] . ' ' . date('Y', $week_monday);
	} else {
		$week_label = date('j', $week_monday) . '. ' . $month_names[$mon_month - 1] . ' – ' . date('j', $week_sunday) . '. ' . $month_names[$sun_month - 1] . ' ' . date('Y', $week_sunday);
	}

	$days = [];
	$week_has_events = false;

	for ($d = 0; $d < 7; $d++) {
		$day_ts = strtotime("+{$d} days", $week_monday);
		$day_key = date('Y-m-d', $day_ts);
		$is_past = ($day_ts < $today);
		$is_today = ($day_key === $today_key);

		// Label generieren
		if ($day_key === $today_key) {
			$day_label = 'Heute';
		} elseif ($day_key === $tomorrow_key) {
			$day_label = 'Morgen';
		} elseif ($day_key === $day_after_key) {
			$day_label = 'Übermorgen';
		} else {
			$day_label = $day_names[date('w', $day_ts)] . ', ' . date('j', $day_ts) . '. ' . $month_names[date('n', $day_ts) - 1];
		}

		$day_events = [];
		if (!$is_past && !empty($events_by_date[$day_key])) {
			$day_events = $events_by_date[$day_key];
			$week_has_events = true;
		}

		$days[] = [
			'date_key'  => $day_key,
			'label'     => $day_label,
			'is_past'   => $is_past,
			'is_today'  => $is_today,
			'events'    => $day_events,
		];
	}

	$weeks[] = [
		'index'      => $w,
		'label'      => $week_label,
		'days'       => $days,
		'has_events' => $week_has_events,
	];
}

$has_any_events = !empty($events_by_date);
$has_filters = !empty($alter_terms) || !empty($ortschaft_terms);
?>

<section class="po-eds" id="<?php echo esc_attr($unique_id); ?>">

	<?php if ($headline): ?>
		<h2 class="po-eds__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php // ======== Wochennavigation ======== ?>
	<div class="po-eds__week-nav">
		<button type="button" class="po-eds__week-prev" disabled aria-label="Vorherige Woche">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
		</button>
		<span class="po-eds__week-label"><?php echo esc_html($weeks[0]['label']); ?></span>
		<button type="button" class="po-eds__week-next" aria-label="Nächste Woche">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
		</button>
	</div>

	<?php // ======== Wochen-Grids ======== ?>
	<?php foreach ($weeks as $week): ?>
	<div class="po-eds__week-grid<?php echo $week['index'] === 0 ? ' is-active' : ''; ?>" data-week="<?php echo $week['index']; ?>" data-week-label="<?php echo esc_attr($week['label']); ?>">
		<?php foreach ($week['days'] as $day): ?>
		<div class="po-eds__day-col<?php echo $day['is_past'] ? ' is-past' : ''; ?><?php echo $day['is_today'] ? ' is-today' : ''; ?>" data-date="<?php echo esc_attr($day['date_key']); ?>">
			<div class="po-eds__day-header">
				<span class="po-eds__day-name"><?php echo esc_html($day['label']); ?></span>
				<?php if (!$day['is_past'] && !empty($day['events'])): ?>
				<span class="po-eds__day-count"><?php echo count($day['events']); ?></span>
				<?php endif; ?>
			</div>
			<div class="po-eds__day-events">
				<?php if ($day['is_past']): ?>
					<span class="po-eds__day-past-text">Vergangen</span>
				<?php elseif (empty($day['events'])): ?>
					<span class="po-eds__day-empty-text">Keine Trainings</span>
				<?php else: ?>
					<?php foreach ($day['events'] as $ev):
						$filter_data = trim($ev['age_slug'] . ' ' . $ev['location_slug']);
						$time_text = $ev['start_time'];
						if ($ev['end_time']) $time_text .= ' – ' . $ev['end_time'];
						$modal_id = $unique_id . '-modal-' . $ev['event_id'];

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
					<button type="button" class="po-eds__card-item<?php echo $is_soldout ? ' is-soldout' : ''; ?>" data-filters="<?php echo esc_attr($filter_data); ?>" data-modal-target="<?php echo esc_attr($modal_id); ?>">
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
					</button>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>

	<?php // ======== FAB Filter ======== ?>
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

	<?php // ======== Modals (1 pro Event-ID) ======== ?>
	<?php foreach ($event_data_for_modals as $event_id => $ev):
		$modal_id = $unique_id . '-modal-' . $event_id;
		$available_dates = function_exists('parkourone_get_available_dates_for_event')
			? parkourone_get_available_dates_for_event($event_id) : [];
		$category = $ev['age_slug'] ?? '';
		$time_text = $ev['start_time'] ? $ev['start_time'] . ($ev['end_time'] ? ' – ' . $ev['end_time'] . ' Uhr' : ' Uhr') : '';
	?>
	<div class="po-overlay" id="<?php echo esc_attr($modal_id); ?>" aria-hidden="true" role="dialog" aria-modal="true">
		<div class="po-overlay__backdrop"></div>
		<div class="po-overlay__panel">
			<button class="po-overlay__close" aria-label="Schließen">
				<svg viewBox="0 0 24 24" fill="none">
					<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
					<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>

			<div class="po-steps" data-step="0" data-class-title="<?php echo esc_attr($ev['title']); ?>">
				<div class="po-steps__track">

					<?php // Slide 0: Uebersicht ?>
					<div class="po-steps__slide is-active" data-slide="0">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Probetraining</span>
							<h2 class="po-steps__heading"><?php echo esc_html($ev['title']); ?></h2>
						</header>

						<dl class="po-steps__meta">
							<?php if ($ev['start_time']): ?>
							<div class="po-steps__meta-item">
								<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></dt>
								<dd><?php echo esc_html($time_text); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($ev['venue'])): ?>
							<div class="po-steps__meta-item">
								<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></dt>
								<dd><?php echo esc_html($ev['venue']); ?></dd>
							</div>
							<?php endif; ?>
						</dl>

						<?php if ($category && isset($mood_texts[$category])): ?>
						<p class="po-steps__description"><?php echo esc_html($mood_texts[$category]); ?></p>
						<?php endif; ?>

						<?php if (!empty($available_dates) && !empty($available_dates[0]['price'])): ?>
						<div class="po-steps__price">
							<span class="po-steps__price-label">Probetraining</span>
							<span class="po-steps__price-value"><?php echo wp_kses_post($available_dates[0]['price']); ?></span>
						</div>
						<?php endif; ?>

						<button type="button" class="po-steps__cta po-steps__next">
							<?php echo esc_html($buttonText); ?>
						</button>
					</div>

					<?php // Slide 1: Termin waehlen ?>
					<div class="po-steps__slide is-next" data-slide="1">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Schritt 1 von 2</span>
							<h2 class="po-steps__heading">Termin wählen</h2>
							<p class="po-steps__subheading"><?php echo esc_html($ev['title']); ?></p>
						</header>

						<?php if (!empty($available_dates)): ?>
						<div class="po-steps__dates">
							<?php foreach ($available_dates as $date): ?>
							<button type="button" class="po-steps__date po-steps__next" data-product-id="<?php echo esc_attr($date['product_id']); ?>" data-date-text="<?php echo esc_attr($date['date_formatted']); ?>">
								<span class="po-steps__date-text"><?php echo esc_html($date['date_formatted']); ?></span>
								<span class="po-steps__date-stock"><?php echo esc_html($date['stock']); ?> <?php echo $date['stock'] === 1 ? 'Platz' : 'Plätze'; ?> frei</span>
							</button>
							<?php endforeach; ?>
						</div>
						<?php else: ?>
						<div class="po-steps__empty">
							<p>Aktuell sind keine Probetraining-Termine verfügbar.</p>
							<p>Kontaktiere uns für weitere Informationen.</p>
						</div>
						<?php endif; ?>

						<button type="button" class="po-steps__back-link">&larr; Zurück zur Übersicht</button>
					</div>

					<?php // Slide 2: Formular ?>
					<div class="po-steps__slide is-next" data-slide="2">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Schritt 2 von 2</span>
							<h2 class="po-steps__heading">Wer nimmt teil?</h2>
							<p class="po-steps__subheading po-steps__selected-date"></p>
						</header>

						<form class="po-steps__form">
							<input type="hidden" name="product_id" value="">
							<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">

							<div class="po-steps__field">
								<label for="vorname-<?php echo esc_attr($modal_id); ?>">Vorname</label>
								<input type="text" id="vorname-<?php echo esc_attr($modal_id); ?>" name="vorname" required autocomplete="given-name">
							</div>

							<div class="po-steps__field">
								<label for="name-<?php echo esc_attr($modal_id); ?>">Nachname</label>
								<input type="text" id="name-<?php echo esc_attr($modal_id); ?>" name="name" required autocomplete="family-name">
							</div>

							<div class="po-steps__field">
								<label for="geburtsdatum-<?php echo esc_attr($modal_id); ?>">Geburtsdatum</label>
								<input type="date" id="geburtsdatum-<?php echo esc_attr($modal_id); ?>" name="geburtsdatum" required>
							</div>

							<button type="submit" class="po-steps__cta po-steps__submit">Zum Warenkorb hinzufügen</button>
						</form>

						<button type="button" class="po-steps__back-link">&larr; Anderer Termin</button>
					</div>

					<?php // Slide 3: Erfolg ?>
					<div class="po-steps__slide is-next" data-slide="3">
						<div class="po-steps__success">
							<div class="po-steps__success-icon">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</div>
							<h2 class="po-steps__heading">Hinzugefügt!</h2>
							<p class="po-steps__subheading"><?php echo esc_html($ev['title']); ?></p>
							<p class="po-steps__selected-date-confirm"></p>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

</section>
