<?php
$headline   = $attributes['headline'] ?? 'Nächste Probetrainings';
$buttonText = $attributes['buttonText'] ?? 'Jetzt buchen';
$anchor = $attributes['anchor'] ?? '';$unique_id  = 'eds-' . uniqid();

// Farben für Altersgruppen
$age_colors = [
	'minis'   => '#ff9500',
	'kids'    => '#34c759',
	'juniors' => '#007aff',
	'adults'  => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

// Mood-Texte kommen jetzt aus parkourone_get_mood_text() in functions.php

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
// Events laden und als flache Liste sammeln
// ========================================

$query = new WP_Query([
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
]);

$today = strtotime('today');
$all_events = [];
$rendered_modals = [];
$event_data_for_modals = [];

$month_names = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$day_names   = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
$day_names_short = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

if ($query->have_posts()) {
	while ($query->have_posts()) {
		$query->the_post();
		$event_id = get_the_ID();

		$event_dates = get_post_meta($event_id, '_event_dates', true);
		if (!is_array($event_dates) || empty($event_dates)) continue;

		$start_time = get_post_meta($event_id, '_event_start_time', true);
		$end_time   = get_post_meta($event_id, '_event_end_time', true);
		$headcoach  = get_post_meta($event_id, '_event_headcoach', true);
		$headcoach_image = function_exists('parkourone_get_coach_display_image_by_name')
			? parkourone_get_coach_display_image_by_name($headcoach, '80x80', get_post_meta($event_id, '_event_headcoach_image_url', true))
			: get_post_meta($event_id, '_event_headcoach_image_url', true);
		$venue      = get_post_meta($event_id, '_event_venue', true);
		$venue_lat  = get_post_meta($event_id, '_event_venue_lat', true);
		$venue_lng  = get_post_meta($event_id, '_event_venue_lng', true);
		$venue_maps_url = ($venue_lat && $venue_lng)
			? 'https://www.google.com/maps?q=' . urlencode($venue_lat . ',' . $venue_lng)
			: '';

		// Taxonomien ermitteln
		$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		$age_slugs = [];
		$location_slug = '';
		$offer_slug = '';

		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && !is_wp_error($parent)) {
					if ($parent->slug === 'alter') $age_slugs[] = $term->slug;
					if ($parent->slug === 'ortschaft') $location_slug = $term->slug;
					if ($parent->slug === 'angebot') $offer_slug = $term->slug;
				}
			}
		}
		$age_slug = !empty($age_slugs) ? $age_slugs[0] : '';

		// Ferienkurse, Kurse und Workshops überspringen — dieser Block zeigt nur Probetrainings.
		if ($offer_slug === 'ferienkurs') continue;
		if ((int) get_post_meta($event_id, '_event_is_course', true) === 1) continue;
		if ((int) get_post_meta($event_id, '_event_is_workshop', true) === 1) continue;

		$color = $age_colors[$age_slug] ?? '#0066cc';

		// Wochentag ermitteln
		$weekday_name = '';
		$first_date_entry = is_array($event_dates) && !empty($event_dates) ? $event_dates[0] : null;
		if ($first_date_entry && !empty($first_date_entry['date'])) {
			$ts = strtotime(str_replace('-', '.', $first_date_entry['date']));
			if ($ts) {
				$wd = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
				$weekday_name = $wd[date('w', $ts)];
			}
		}

		// Coach-Profil prüfen
		$coach_has_profile = false;
		if (!empty($headcoach) && function_exists('parkourone_get_coach_by_name')) {
			$coach_data = parkourone_get_coach_by_name($headcoach);
			if ($coach_data && function_exists('parkourone_coach_has_profile') && parkourone_coach_has_profile($coach_data)) {
				$coach_has_profile = true;
			}
		}

		// Event-Daten für Modal merken (nur einmal pro Event-ID)
		if (!isset($event_data_for_modals[$event_id])) {
			$event_data_for_modals[$event_id] = [
				'event_id'        => $event_id,
				'title'           => get_the_title(),
				'start_time'      => $start_time,
				'end_time'        => $end_time,
				'headcoach'       => $headcoach,
				'coach_has_profile' => $coach_has_profile,
				'venue'           => $venue,
				'venue_maps_url'  => $venue_maps_url,
				'weekday'         => $weekday_name,
				'age_slug'        => $age_slug,
				'color'           => $color,
				'dropdown_info'   => get_post_meta($event_id, '_event_dropdown_info', true),
				'min_participants' => get_post_meta($event_id, '_event_min_participants', true),
				'description'     => get_post_meta($event_id, '_event_description', true),
			];
		}

		// Live WooCommerce-Produktbestand pro Datum laden (statt veralteter Import-Daten)
		$event_products = get_posts([
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => [
				['key' => '_event_id', 'value' => $event_id]
			],
			'fields' => 'ids',
		]);
		$product_stock_by_date = [];
		foreach ($event_products as $pid) {
			$pdate = get_post_meta($pid, '_event_date', true);
			if ($pdate) {
				$wc_product = wc_get_product($pid);
				if ($wc_product) {
					$product_stock_by_date[$pdate] = $wc_product->is_in_stock() ? max(0, (int) $wc_product->get_stock_quantity()) : 0;
				}
			}
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

			// Live-Bestand aus WooCommerce-Produkt verwenden
			$stock = isset($product_stock_by_date[$date_entry['date']]) ? $product_stock_by_date[$date_entry['date']] : -1;

			$all_events[] = [
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
				'filter_data'     => trim(implode(' ', $age_slugs) . ' ' . $location_slug),
				'date_key'        => $date_key,
				'timestamp'       => $timestamp,
			];
		}
	}
	wp_reset_postdata();
}

// Pro Event-ID prüfen ob IRGENDEIN zukünftiges Datum noch Plätze hat
$event_has_availability = [];
foreach ($all_events as $ev) {
	if ($ev['stock'] > 0) {
		$event_has_availability[$ev['event_id']] = true;
	}
}

// Nach Datum + Startzeit sortieren
usort($all_events, function($a, $b) {
	if ($a['date_key'] === $b['date_key']) {
		return strcmp($a['start_time'], $b['start_time']);
	}
	return strcmp($a['date_key'], $b['date_key']);
});

// Initiale Anzeige: maximal 15 Events
$initial_items = min(15, count($all_events));

$total_events = count($all_events);
$has_more = ($initial_items < $total_events);
$has_any_events = !empty($all_events);
$has_filters = !empty($alter_terms) || !empty($ortschaft_terms);

// Datum-Labels generieren
$today_key     = date('Y-m-d', $today);
$tomorrow_key  = date('Y-m-d', strtotime('+1 day', $today));
$day_after_key = date('Y-m-d', strtotime('+2 days', $today));

function po_eds_format_date($date_key, $today_key, $tomorrow_key, $day_after_key, $day_names_short, $month_names) {
	$ts = strtotime($date_key);
	if ($date_key === $today_key) {
		return 'Heute, ' . $day_names_short[date('w', $ts)] . '. ' . date('j', $ts) . '. ' . $month_names[date('n', $ts) - 1];
	} elseif ($date_key === $tomorrow_key) {
		return 'Morgen, ' . $day_names_short[date('w', $ts)] . '. ' . date('j', $ts) . '. ' . $month_names[date('n', $ts) - 1];
	}
	return $day_names_short[date('w', $ts)] . '. ' . date('j', $ts) . '. ' . $month_names[date('n', $ts) - 1];
}
?>

<section class="po-eds" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">

	<?php if ($headline): ?>
		<h2 class="po-eds__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php if ($has_filters): ?>
	<div class="po-eds__filters">
		<?php if (!empty($alter_terms)): ?>
		<div class="po-eds__dropdown" data-filter-type="age">
			<button type="button" class="po-eds__dropdown-trigger" aria-expanded="false">
				<span class="po-eds__dropdown-value">Altersgruppe</span>
				<svg class="po-eds__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eds__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eds__dropdown-option is-selected" data-value="all">Altersgruppe</button>
				<?php foreach ($alter_terms as $term): ?>
				<button type="button" class="po-eds__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>">
					<span class="po-eds__dropdown-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		<?php if (!empty($ortschaft_terms)): ?>
		<div class="po-eds__dropdown" data-filter-type="location">
			<button type="button" class="po-eds__dropdown-trigger" aria-expanded="false">
				<span class="po-eds__dropdown-value">Standort</span>
				<svg class="po-eds__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eds__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eds__dropdown-option is-selected" data-value="all">Standort</button>
				<?php foreach ($ortschaft_terms as $term): ?>
				<button type="button" class="po-eds__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		<div class="po-eds__dropdown" data-filter-type="weekday">
			<button type="button" class="po-eds__dropdown-trigger" aria-expanded="false">
				<span class="po-eds__dropdown-value">Wochentag</span>
				<svg class="po-eds__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-eds__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-eds__dropdown-option is-selected" data-value="all">Wochentag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="1">Montag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="2">Dienstag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="3">Mittwoch</button>
				<button type="button" class="po-eds__dropdown-option" data-value="4">Donnerstag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="5">Freitag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="6">Samstag</button>
				<button type="button" class="po-eds__dropdown-option" data-value="0">Sonntag</button>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if (!$has_any_events): ?>
		<div class="po-eds__empty">
			<p>Aktuell sind keine Trainings geplant.</p>
		</div>
	<?php else: ?>

	<?php // ======== Flache Event-Liste ======== ?>
	<div class="po-eds__list" data-initial="<?php echo esc_attr($initial_items); ?>" data-total="<?php echo esc_attr($total_events); ?>">
		<?php foreach ($all_events as $idx => $ev):
			$filter_data = $ev['filter_data'];
			$time_text = $ev['start_time'];
			if ($ev['end_time']) $time_text .= ' – ' . $ev['end_time'];
			$modal_id = $unique_id . '-modal-' . $ev['event_id'];
			$date_label = po_eds_format_date($ev['date_key'], $today_key, $tomorrow_key, $day_after_key, $day_names_short, $month_names);

			$stock = $ev['stock'];
			$has_other_dates = !empty($event_has_availability[$ev['event_id']]);
			// Nur komplett ausgrauen wenn ALLE Termine des Events ausgebucht sind
			$is_soldout = ($stock === 0) && !$has_other_dates;
			$stock_html = '';
			if ($stock === 0 && $has_other_dates) {
				$stock_html = '<span class="po-eds__card-stock po-eds__card-stock--alt">Weitere Termine verfügbar</span>';
			} elseif ($stock === 0) {
				$stock_html = '<span class="po-eds__card-stock po-eds__card-stock--none">Ausgebucht</span>';
			} elseif ($stock > 0 && $stock <= 3) {
				$stock_html = '<span class="po-eds__card-stock po-eds__card-stock--low">' . $stock . ($stock === 1 ? ' Platz' : ' Plätze') . '</span>';
			} elseif ($stock > 3) {
				$stock_html = '<span class="po-eds__card-stock">' . $stock . ' Plätze</span>';
			}

			$is_hidden = ($idx >= $initial_items);
		?>
		<button type="button"
			class="po-eds__card<?php echo $is_soldout ? ' is-soldout' : ''; ?><?php echo $is_hidden ? ' is-hidden' : ''; ?>"
			data-filters="<?php echo esc_attr($filter_data); ?>"
			data-weekday="<?php echo date('w', $ev['timestamp']); ?>"
			data-modal-target="<?php echo esc_attr($modal_id); ?>"
			data-index="<?php echo $idx; ?>">
			<?php if (!empty($ev['headcoach_image'])): ?>
				<img src="<?php echo esc_url($ev['headcoach_image']); ?>" alt="<?php echo esc_attr($ev['headcoach'] ?? ''); ?>" class="po-eds__card-img" loading="lazy">
			<?php endif; ?>
			<div class="po-eds__card-body">
				<span class="po-eds__card-date"><?php echo esc_html($date_label); ?></span>
				<span class="po-eds__card-time"><?php echo esc_html($time_text); ?> Uhr</span>
				<span class="po-eds__card-title" style="color: <?php echo esc_attr($ev['color']); ?>"><?php echo esc_html($ev['title']); ?></span>
				<?php if (!empty($ev['min_participants']) && intval($ev['min_participants']) > 0): ?>
				<span class="po-eds__card-badge-min">min. <?php echo intval($ev['min_participants']); ?> Teilnehmende</span>
				<?php endif; ?>
				<div class="po-eds__card-meta">
					<?php if ($ev['headcoach']): ?>
					<span class="po-eds__card-coach"><?php echo esc_html($ev['headcoach']); ?></span>
					<?php endif; ?>
					<?php if ($ev['venue']): ?>
					<?php if (!empty($ev['venue_maps_url'])): ?>
					<a href="<?php echo esc_url($ev['venue_maps_url']); ?>" target="_blank" rel="noopener" class="po-eds__card-venue" onclick="event.stopPropagation();"><?php echo esc_html($ev['venue']); ?></a>
					<?php else: ?>
					<span class="po-eds__card-venue"><?php echo esc_html($ev['venue']); ?></span>
					<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php echo $stock_html; ?>
			</div>
			<svg class="po-eds__card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
				<path d="M9 18l6-6-6-6"/>
			</svg>
		</button>
		<?php endforeach; ?>
	</div>

	<?php if ($has_more): ?>
	<div class="po-eds__load-more-wrap">
		<button type="button" class="po-eds__load-more">Weitere Termine laden</button>
	</div>
	<?php endif; ?>

	<?php endif; ?>

	<?php // ======== Modals (1 pro Event-ID) ======== ?>
	<?php foreach ($event_data_for_modals as $event_id => $ev):
		$modal_id = $unique_id . '-modal-' . $event_id;
		$available_dates = function_exists('parkourone_get_available_dates_for_event')
			? parkourone_get_available_dates_for_event($event_id) : [];
		$mood_text = function_exists('parkourone_get_mood_text')
			? parkourone_get_mood_text($ev['age_slug'] ?? '', $ev['title'] ?? '')
			: '';
		$time_text = $ev['start_time'] ? $ev['start_time'] . ($ev['end_time'] ? ' – ' . $ev['end_time'] . ' Uhr' : ' Uhr') : '';
		// Coach-Text mit Link wenn Profil vorhanden (wie klassen-slider)
		$coach_text = '';
		if (!empty($ev['headcoach'])) {
			if (!empty($ev['coach_has_profile'])) {
				$coach_text = ' von <button type="button" class="po-ks__coach-link-inline" data-goto-slide="coach">' . esc_html($ev['headcoach']) . '</button> geleitet und';
			} else {
				$coach_text = ' von ' . esc_html($ev['headcoach']) . ' geleitet und';
			}
		}
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
			<?php echo parkourone_share_button(
				add_query_arg('training', $event_id, get_permalink()),
				$ev['title'] . ' – ParkourONE',
				'',
				true
			); ?>

			<div class="po-steps" data-step="0" data-class-title="<?php echo esc_attr($ev['title']); ?>">
				<div class="po-steps__track">

					<?php // Slide 0: Übersicht ?>
					<div class="po-steps__slide is-active" data-slide="0">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow"><?php echo esc_html($ev['weekday'] ?: 'Probetraining'); ?></span>
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
								<dd><?php if (!empty($ev['venue_maps_url'])): ?>
									<a href="<?php echo esc_url($ev['venue_maps_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($ev['venue']); ?></a>
								<?php else: ?>
									<?php echo esc_html($ev['venue']); ?>
								<?php endif; ?></dd>
							</div>
							<?php endif; ?>
						</dl>

						<?php if ($mood_text): ?>
						<p class="po-steps__description">
							Dieses Training wird<?php echo $coach_text; ?> findet wöchentlich <?php echo esc_html($ev['weekday']); ?> von <?php echo esc_html($time_text); ?> statt.<?php if (!empty($ev['venue'])): ?> Treffpunkt ist <?php if (!empty($ev['venue_maps_url'])): ?><a href="<?php echo esc_url($ev['venue_maps_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($ev['venue']); ?></a><?php else: ?><?php echo esc_html($ev['venue']); ?><?php endif; ?>.<?php endif; ?> <?php echo esc_html($mood_text); ?>
						</p>
						<?php endif; ?>

						<?php if (!empty($ev['description'])): ?>
						<div class="po-steps__content">
							<?php echo wp_kses_post($ev['description']); ?>
						</div>
						<?php endif; ?>

						<?php if (!empty($ev['dropdown_info'])): ?>
						<div class="po-steps__info-notice">
							<?php echo wp_kses_post($ev['dropdown_info']); ?>
						</div>
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

					<?php // Slide 1: Termin wählen ?>
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
								<span class="po-steps__date-meta">
									<?php if (!empty($date['venue'])): ?>
										<?php if (!empty($date['venue_maps_url'])): ?>
											<a href="<?php echo esc_url($date['venue_maps_url']); ?>" target="_blank" rel="noopener" class="po-steps__date-venue" onclick="event.stopPropagation();"><?php echo esc_html($date['venue']); ?></a>
										<?php else: ?>
											<span class="po-steps__date-venue"><?php echo esc_html($date['venue']); ?></span>
										<?php endif; ?>
									<?php endif; ?>
									<span class="po-steps__date-stock"><?php echo esc_html($date['stock']); ?> <?php echo $date['stock'] === 1 ? 'Platz' : 'Plätze'; ?> frei</span>
								</span>
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
