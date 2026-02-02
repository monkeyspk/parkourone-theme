<?php
$headline = $attributes['headline'] ?? 'Wähle deinen Kurs';
$group_by = $attributes['groupBy'] ?? 'weekday';
$filter_age = $attributes['filterAge'] ?? '';
$filter_location = $attributes['filterLocation'] ?? '';
$show_booking_panel = $attributes['showBookingPanel'] ?? true;

// Build query args
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

if (empty($events)) {
	return;
}

// Group events
$grouped = [];
$weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

foreach ($events as $event) {
	$event_id = $event->ID;
	$dates = get_post_meta($event_id, '_event_dates', true);
	$first_date = is_array($dates) && !empty($dates) ? $dates[0] : null;

	// Determine group key
	$group_key = '';
	$group_label = '';

	if ($group_by === 'weekday' && $first_date && !empty($first_date['date'])) {
		$timestamp = strtotime(str_replace('-', '.', $first_date['date']));
		if ($timestamp) {
			$day_num = date('w', $timestamp);
			$group_key = $day_num;
			$group_label = $weekdays[$day_num == 0 ? 6 : $day_num - 1];
		}
	} elseif ($group_by === 'age' || $group_by === 'location') {
		$parent_slug = $group_by === 'age' ? 'alter' : 'ortschaft';
		$categories = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		foreach ($categories as $cat) {
			$cat_parent = $cat->parent ? get_term($cat->parent, 'event_category') : null;
			if ($cat_parent && $cat_parent->slug === $parent_slug) {
				$group_key = $cat->slug;
				$group_label = $cat->name;
				break;
			}
		}
	}

	if (!$group_key) {
		$group_key = 'other';
		$group_label = 'Weitere';
	}

	if (!isset($grouped[$group_key])) {
		$grouped[$group_key] = [
			'label' => $group_label,
			'events' => []
		];
	}

	// Prepare event data
	$headcoach = get_post_meta($event_id, '_event_headcoach', true);
	$headcoach_image = get_post_meta($event_id, '_event_headcoach_image_url', true);
	$start_time = get_post_meta($event_id, '_event_start_time', true);
	$end_time = get_post_meta($event_id, '_event_end_time', true);

	// Get location
	$location_name = '';
	$categories = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
	foreach ($categories as $cat) {
		$cat_parent = $cat->parent ? get_term($cat->parent, 'event_category') : null;
		if ($cat_parent && $cat_parent->slug === 'ortschaft') {
			$location_name = $cat->name;
			break;
		}
	}

	// Get available dates for booking
	$available_dates = [];
	if (function_exists('parkourone_get_available_dates_for_event')) {
		$available_dates = parkourone_get_available_dates_for_event($event_id);
	}

	$grouped[$group_key]['events'][] = [
		'id' => $event_id,
		'title' => $event->post_title,
		'headcoach' => $headcoach,
		'headcoach_image' => $headcoach_image,
		'start_time' => $start_time,
		'end_time' => $end_time,
		'location' => $location_name,
		'available_dates' => $available_dates
	];
}

// Sort weekday groups
if ($group_by === 'weekday') {
	uksort($grouped, function($a, $b) {
		$order = [1, 2, 3, 4, 5, 6, 0]; // Mon-Sun
		$pos_a = array_search((int)$a, $order);
		$pos_b = array_search((int)$b, $order);
		if ($pos_a === false) $pos_a = 99;
		if ($pos_b === false) $pos_b = 99;
		return $pos_a - $pos_b;
	});
}
?>

<section class="po-klassen-selektor alignfull" data-show-booking="<?php echo $show_booking_panel ? 'true' : 'false'; ?>">
	<?php if ($headline): ?>
		<h2 class="po-klassen-selektor__headline"><?php echo esc_html($headline); ?></h2>
	<?php endif; ?>

	<div class="po-klassen-selektor__layout">
		<!-- Accordion List -->
		<div class="po-klassen-selektor__list">
			<?php foreach ($grouped as $group_key => $group): ?>
				<div class="po-klassen-group" data-group="<?php echo esc_attr($group_key); ?>">
					<button class="po-klassen-group__header" aria-expanded="false">
						<span class="po-klassen-group__label"><?php echo esc_html($group['label']); ?></span>
						<span class="po-klassen-group__count"><?php echo count($group['events']); ?> Kurse</span>
						<svg class="po-klassen-group__chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="6,9 12,15 18,9"/>
						</svg>
					</button>
					<div class="po-klassen-group__content">
						<?php foreach ($group['events'] as $event): ?>
							<article class="po-klassen-item"
								data-event-id="<?php echo $event['id']; ?>"
								data-event-title="<?php echo esc_attr($event['title']); ?>"
								data-event-dates='<?php echo json_encode($event['available_dates']); ?>'>
								<div class="po-klassen-item__main">
									<?php if ($event['headcoach_image']): ?>
									<div class="po-klassen-item__avatar">
										<img src="<?php echo esc_url($event['headcoach_image']); ?>" alt="<?php echo esc_attr($event['headcoach']); ?>">
									</div>
									<?php endif; ?>
									<div class="po-klassen-item__info">
										<h4 class="po-klassen-item__title"><?php echo esc_html($event['title']); ?></h4>
										<div class="po-klassen-item__meta">
											<?php if ($event['headcoach']): ?>
												<span>mit <?php echo esc_html($event['headcoach']); ?></span>
											<?php endif; ?>
											<?php if ($event['start_time'] && $event['end_time']): ?>
												<span><?php echo esc_html($event['start_time']); ?> - <?php echo esc_html($event['end_time']); ?></span>
											<?php endif; ?>
											<?php if ($event['location']): ?>
												<span><?php echo esc_html($event['location']); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<button class="po-klassen-item__select" aria-label="Kurs auswählen">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="9,18 15,12 9,6"/>
									</svg>
								</button>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Booking Panel -->
		<?php if ($show_booking_panel): ?>
		<aside class="po-booking-panel" aria-hidden="true">
			<div class="po-booking-panel__content">
				<div class="po-booking-panel__placeholder">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
					<p>Wähle einen Kurs aus der Liste, um die Buchung zu starten.</p>
				</div>

				<div class="po-booking-panel__form" style="display: none;">
					<button class="po-booking-panel__back" aria-label="Zurück zur Auswahl">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="15,18 9,12 15,6"/>
						</svg>
						Zurück
					</button>

					<h3 class="po-booking-panel__title"></h3>

					<div class="po-booking-panel__step po-booking-panel__step--date">
						<label class="po-booking-panel__label">Datum wählen</label>
						<select class="po-booking-panel__date-select">
							<option value="">Datum auswählen...</option>
						</select>
					</div>

					<div class="po-booking-panel__step po-booking-panel__step--details" style="display: none;">
						<div class="po-booking-panel__field">
							<label>Vorname</label>
							<input type="text" name="vorname" required>
						</div>
						<div class="po-booking-panel__field">
							<label>Nachname</label>
							<input type="text" name="name" required>
						</div>
						<div class="po-booking-panel__field">
							<label>Geburtsdatum</label>
							<input type="date" name="geburtsdatum" required>
						</div>
					</div>

					<button class="po-booking-panel__submit" style="display: none;">
						Jetzt buchen
					</button>

					<div class="po-booking-panel__loading" style="display: none;">
						<div class="po-booking-panel__spinner"></div>
						<span>Wird gebucht...</span>
					</div>

					<div class="po-booking-panel__success" style="display: none;">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
							<polyline points="22,4 12,14.01 9,11.01"/>
						</svg>
						<p>Erfolgreich gebucht!</p>
					</div>
				</div>
			</div>
		</aside>
		<?php endif; ?>
	</div>
</section>

<?php if ($show_booking_panel && class_exists('WooCommerce')): ?>
<script>
window.poKlassenBooking = window.poKlassenBooking || {
	ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
	nonce: '<?php echo wp_create_nonce('po_booking_nonce'); ?>'
};
</script>
<?php endif; ?>
