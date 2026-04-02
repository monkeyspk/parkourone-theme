<?php
$headline = $attributes['headline'] ?? 'Stundenplan';
$buttonText = $attributes['buttonText'] ?? 'Probetraining buchen';
$filterLayout = $attributes['filterLayout'] ?? 'fab';
$anchor = $attributes['anchor'] ?? '';

$args = [
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
];

$query = new WP_Query($args);
$klassen = [];
$weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
$klassen_by_day = array_fill_keys($weekdays, []);

// Coach-Profile für Sheet-Modals sammeln
$coach_profiles = [];

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
}

if ($ortschaft_parent && !is_wp_error($ortschaft_parent)) {
	$ortschaft_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false
	]);
}

$angebot_parent = get_term_by('slug', 'angebot', 'event_category');
$angebot_terms = [];

if ($angebot_parent && !is_wp_error($angebot_parent)) {
	$angebot_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $angebot_parent->term_id,
		'hide_empty' => false
	]);
	if (is_wp_error($angebot_terms)) $angebot_terms = [];
}

// Auto-detect: nur Ortschaften dieses Standorts anzeigen
$site_location = function_exists('parkourone_get_site_location')
	? parkourone_get_site_location()
	: null;

$local_ortschaft_slugs = [];
if ($site_location && $site_location['detected'] && !empty($ortschaft_terms)) {
	$site_slug = $site_location['slug'];

	foreach ($ortschaft_terms as $term) {
		if ($term->slug === $site_slug
			|| strpos($term->slug, $site_slug . '-') === 0) {
			$local_ortschaft_slugs[] = $term->slug;
		}
	}

	// Fallback: Name-basiertes Matching (für Umlaute wie zürich/zurich)
	if (empty($local_ortschaft_slugs)) {
		$site_name_lower = mb_strtolower($site_location['name']);
		foreach ($ortschaft_terms as $term) {
			$term_name_lower = mb_strtolower($term->name);
			if ($term_name_lower === $site_name_lower
				|| strpos($term_name_lower, $site_name_lower) === 0) {
				$local_ortschaft_slugs[] = $term->slug;
			}
		}
	}
}

$auto_filter_active = !empty($local_ortschaft_slugs);

$age_colors = [
	'minis' => '#ff9500',
	'kids' => '#34c759',
	'juniors' => '#0066cc',
	'adults' => '#0066cc',
	'juniors-adults' => '#0066cc',
	'seniors' => '#af52de',
	'masters' => '#af52de',
	'seniors-masters' => '#af52de',
	'women' => '#ff375f'
];

if ($query->have_posts()) {
	while ($query->have_posts()) {
		$query->the_post();
		$event_id = get_the_ID();
		
		$event_dates = get_post_meta($event_id, '_event_dates', true);
		$first_date = is_array($event_dates) && !empty($event_dates) ? $event_dates[0] : null;
		
		$weekday_name = '';
		if ($first_date && !empty($first_date['date'])) {
			$timestamp = strtotime(str_replace('-', '.', $first_date['date']));
			if ($timestamp) {
				$days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
				$weekday_name = $days[date('w', $timestamp)];
			}
		}
		
		$start_time = get_post_meta($event_id, '_event_start_time', true);
		$end_time = get_post_meta($event_id, '_event_end_time', true);
		
		$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		$age_term_slugs = [];
		$age_term_names = [];
		$location_term_slug = '';
		$offer_term_slug = '';

		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && !is_wp_error($parent)) {
					if ($parent->slug === 'alter') {
						$age_term_slugs[] = $term->slug;
						$age_term_names[] = $term->name;
					}
					if ($parent->slug === 'ortschaft') {
						$location_term_slug = $term->slug;
					}
					if ($parent->slug === 'angebot') {
						$offer_term_slug = $term->slug;
					}
				}
			}
		}

		$age_slug_str = implode(' ', $age_term_slugs);
		$age_name_str = implode(' / ', $age_term_names);

		// Color: first matching slug
		$age_color = '#0066cc';
		foreach ($age_term_slugs as $s) {
			if (isset($age_colors[$s])) { $age_color = $age_colors[$s]; break; }
		}

		// Nur Probetrainings (oder Events ohne Angebots-Term) im wöchentlichen Stundenplan
		if ($offer_term_slug && $offer_term_slug !== 'probetraining') {
			continue;
		}

		// Skip events not matching this location (auto-detected via subdomain)
		if ($auto_filter_active && !empty($location_term_slug)
			&& !in_array($location_term_slug, $local_ortschaft_slugs)) {
			continue;
		}

		$headcoach_name = get_post_meta($event_id, '_event_headcoach', true);

		$klasse = [
			'id' => $event_id,
			'title' => get_the_title(),
			'headcoach' => $headcoach_name,
			'headcoach_image' => function_exists('parkourone_get_coach_display_image_by_name')
			? parkourone_get_coach_display_image_by_name(
				get_post_meta($event_id, '_event_headcoach', true),
				'80x80',
				get_post_meta($event_id, '_event_headcoach_image_url', true)
			)
			: get_post_meta($event_id, '_event_headcoach_image_url', true),
			'start_time' => $start_time,
			'end_time' => $end_time,
			'venue' => get_post_meta($event_id, '_event_venue', true),
			'weekday' => $weekday_name,
			'age_slug' => $age_slug_str,
			'age_name' => $age_name_str,
			'location_slug' => $location_term_slug,
			'offer_slug' => $offer_term_slug,
			'color' => $age_color,
			'dropdown_info' => get_post_meta($event_id, '_event_dropdown_info', true),
			'min_participants' => get_post_meta($event_id, '_event_min_participants', true),
			'coach_id' => null,
			'coach_has_profile' => false
		];

		// Coach-Profile sammeln wenn vorhanden
		if (!empty($headcoach_name) && !isset($coach_profiles[$headcoach_name])) {
			$coach_data = function_exists('parkourone_get_coach_by_name')
				? parkourone_get_coach_by_name($headcoach_name)
				: null;

			if ($coach_data) {
				$has_profile = function_exists('parkourone_coach_has_profile')
					? parkourone_coach_has_profile($coach_data)
					: false;

				if ($has_profile) {
					$coach_id = $coach_data['id'];
					$hero_bild = get_post_meta($coach_id, '_coach_hero_bild', true);
					$philosophie_bild = $coach_data['philosophie_bild'] ?? '';
					$moment_bild = $coach_data['moment_bild'] ?? '';
					// Cached Coach-Avatar
					$cached_avatar = function_exists('parkourone_get_coach_display_image')
						? parkourone_get_coach_display_image($coach_id, '300x300')
						: ($coach_data['api_image'] ?? '');

					$coach_profiles[$headcoach_name] = [
						'id' => $coach_id,
						'name' => $coach_data['name'],
						'rolle' => $coach_data['rolle'] ?? '',
						'standort' => $coach_data['standort'] ?? '',
						'parkour_seit' => get_post_meta($coach_id, '_coach_parkour_seit', true),
						'po_seit' => get_post_meta($coach_id, '_coach_po_seit', true),
						'kurzvorstellung' => $coach_data['kurzvorstellung'] ?? '',
						'moment' => $coach_data['moment'] ?? '',
						'leitsatz' => $coach_data['leitsatz'] ?? '',
						'hero_bild' => $hero_bild,
						'philosophie_bild' => $philosophie_bild,
						'moment_bild' => $moment_bild,
						'card_image' => $hero_bild ?: $philosophie_bild ?: $moment_bild ?: $cached_avatar
					];
				}
			}
		}

		// Coach-Info zur Klasse hinzufügen
		if (isset($coach_profiles[$headcoach_name])) {
			$klasse['coach_id'] = $coach_profiles[$headcoach_name]['id'];
			$klasse['coach_has_profile'] = true;
		}
		
		$klassen[] = $klasse;
		
		if ($weekday_name && isset($klassen_by_day[$weekday_name])) {
			$klassen_by_day[$weekday_name][] = $klasse;
		}
	}
	wp_reset_postdata();
}

foreach ($klassen_by_day as $day => $day_klassen) {
	usort($klassen_by_day[$day], function($a, $b) {
		return strcmp($a['start_time'], $b['start_time']);
	});
}

static $po_stundenplan_instance = 0; $po_stundenplan_instance++;
$unique_id = 'stundenplan-' . $po_stundenplan_instance;

$all_times = [];
foreach ($klassen as $k) {
	if ($k['start_time']) {
		$hour = (int) substr($k['start_time'], 0, 2);
		$all_times[$hour] = true;
	}
}
ksort($all_times);
$time_slots = array_keys($all_times);
if (empty($time_slots)) {
	$time_slots = range(9, 20);
}

$used_age_slugs = [];
foreach ($klassen as $k) {
	foreach (explode(' ', $k['age_slug']) as $s) {
		if ($s) $used_age_slugs[] = $s;
	}
}
$used_age_slugs = array_unique($used_age_slugs);
?>

<?php if (!empty($klassen)): ?>
<section class="po-sp <?php echo $filterLayout === 'inline' ? 'po-sp--inline-filter' : ''; ?>" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">
	<?php if ($headline): ?>
		<h2 class="po-sp__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php // Inline Filter (Custom Dropdowns nebeneinander) ?>
	<?php if ($filterLayout === 'inline' && (!empty($alter_terms) || !empty($ortschaft_terms) || !empty($angebot_terms))): ?>
	<div class="po-sp__inline-filters">
		<?php if (!empty($alter_terms)): ?>
		<div class="po-sp__custom-dropdown" data-filter-type="age">
			<button type="button" class="po-sp__dropdown-trigger" aria-expanded="false">
				<span class="po-sp__dropdown-value">Alle Altersgruppen</span>
				<svg class="po-sp__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-sp__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-sp__dropdown-option is-selected" data-value="all">
					Alle Altersgruppen
				</button>
				<?php foreach ($alter_terms as $term): ?>
				<button type="button" class="po-sp__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>">
					<span class="po-sp__dropdown-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($ortschaft_terms) && !$auto_filter_active): ?>
		<div class="po-sp__custom-dropdown" data-filter-type="location">
			<button type="button" class="po-sp__dropdown-trigger" aria-expanded="false">
				<span class="po-sp__dropdown-value">Alle Standorte</span>
				<svg class="po-sp__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-sp__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-sp__dropdown-option is-selected" data-value="all">
					Alle Standorte
				</button>
				<?php foreach ($ortschaft_terms as $term): ?>
				<button type="button" class="po-sp__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>">
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($angebot_terms)): ?>
		<div class="po-sp__custom-dropdown" data-filter-type="offer">
			<button type="button" class="po-sp__dropdown-trigger" aria-expanded="false">
				<span class="po-sp__dropdown-value">Alle Angebote</span>
				<svg class="po-sp__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-sp__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-sp__dropdown-option is-selected" data-value="all">Alle Angebote</button>
				<?php foreach ($angebot_terms as $term): ?>
				<button type="button" class="po-sp__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php if (!empty($used_age_slugs)): ?>
	<div class="po-sp__legend">
		<?php foreach ($alter_terms as $term): ?>
			<?php if (in_array($term->slug, $used_age_slugs)): ?>
			<div class="po-sp__legend-item">
				<span class="po-sp__legend-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
				<span class="po-sp__legend-label"><?php echo esc_html($term->name); ?></span>
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	
	<div class="po-sp__grid-wrapper">
		<div class="po-sp__grid">
			<div class="po-sp__header">
				<div class="po-sp__time-col"></div>
				<?php foreach ($weekdays as $day): ?>
					<div class="po-sp__day-col"><?php echo esc_html(substr($day, 0, 2)); ?></div>
				<?php endforeach; ?>
			</div>
			
			<div class="po-sp__body">
				<?php foreach ($time_slots as $hour): 
					$has_events = false;
					foreach ($weekdays as $day) {
						foreach ($klassen_by_day[$day] as $klasse) {
							if ((int) substr($klasse['start_time'], 0, 2) === $hour) {
								$has_events = true;
								break 2;
							}
						}
					}
				?>
					<div class="po-sp__row <?php echo $has_events ? '' : 'po-sp__row--empty'; ?>">
						<div class="po-sp__time-cell"><?php echo sprintf('%02d:00', $hour); ?></div>
						<?php foreach ($weekdays as $day): ?>
							<div class="po-sp__cell" data-day="<?php echo esc_attr($day); ?>" data-hour="<?php echo esc_attr($hour); ?>">
								<?php 
								foreach ($klassen_by_day[$day] as $klasse):
									$klasse_hour = (int) substr($klasse['start_time'], 0, 2);
									if ($klasse_hour === $hour):
										$filter_data = trim($klasse['age_slug'] . ' ' . $klasse['location_slug'] . ' ' . $klasse['offer_slug']);
								?>
									<button type="button" class="po-sp__event" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $klasse['id']); ?>" data-filters="<?php echo esc_attr($filter_data); ?>">
										<?php if (!empty($klasse['headcoach_image'])): ?>
											<img src="<?php echo esc_url($klasse['headcoach_image']); ?>" alt="<?php echo esc_attr($klasse['headcoach'] ?? ''); ?>" class="po-sp__event-img">
										<?php endif; ?>
										<div class="po-sp__event-content">
											<span class="po-sp__event-time"><?php echo esc_html($klasse['start_time']); ?></span>
											<span class="po-sp__event-title" style="color: <?php echo esc_attr($klasse['color']); ?>"><?php echo esc_html($klasse['title']); ?></span>
											<?php if (!empty($klasse['min_participants']) && intval($klasse['min_participants']) > 0): ?>
											<span class="po-sp__event-badge-min">min. <?php echo intval($klasse['min_participants']); ?></span>
											<?php endif; ?>
											<?php if (!empty($klasse['headcoach'])): ?>
											<span class="po-sp__event-coach"><?php echo esc_html($klasse['headcoach']); ?></span>
											<?php endif; ?>
										</div>
									</button>
								<?php 
									endif;
								endforeach;
								?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	
	<div class="po-sp__slider">
		<div class="po-sp__slider-track">
			<?php foreach ($weekdays as $day): ?>
				<?php if (!empty($klassen_by_day[$day])): ?>
				<div class="po-sp__day-card" data-day="<?php echo esc_attr($day); ?>">
					<div class="po-sp__day-card-header">
						<span class="po-sp__day-card-name"><?php echo esc_html($day); ?></span>
						<span class="po-sp__day-card-count"><?php echo count($klassen_by_day[$day]); ?></span>
					</div>
					<div class="po-sp__day-card-list">
						<?php foreach ($klassen_by_day[$day] as $klasse): 
							$filter_data = trim($klasse['age_slug'] . ' ' . $klasse['location_slug'] . ' ' . $klasse['offer_slug']);
						?>
						<button type="button" class="po-sp__card-item" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $klasse['id']); ?>" data-filters="<?php echo esc_attr($filter_data); ?>">
							<?php if (!empty($klasse['headcoach_image'])): ?>
								<img src="<?php echo esc_url($klasse['headcoach_image']); ?>" alt="<?php echo esc_attr($klasse['headcoach'] ?? ''); ?>" class="po-sp__card-img">
							<?php endif; ?>
							<div class="po-sp__card-content">
								<span class="po-sp__card-time"><?php echo esc_html($klasse['start_time']); ?><?php if ($klasse['end_time']): ?> – <?php echo esc_html($klasse['end_time']); ?><?php endif; ?></span>
								<span class="po-sp__card-title" style="color: <?php echo esc_attr($klasse['color']); ?>"><?php echo esc_html($klasse['title']); ?></span>
								<?php if (!empty($klasse['min_participants']) && intval($klasse['min_participants']) > 0): ?>
								<span class="po-sp__event-badge-min">min. <?php echo intval($klasse['min_participants']); ?></span>
								<?php endif; ?>
								<?php if ($klasse['headcoach']): ?>
								<span class="po-sp__card-coach"><?php echo esc_html($klasse['headcoach']); ?></span>
								<?php endif; ?>
								<?php if ($klasse['venue']): ?>
								<span class="po-sp__card-venue"><?php echo esc_html($klasse['venue']); ?></span>
								<?php endif; ?>
							</div>
						</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	
	<?php // FAB Filter (nur wenn filterLayout !== 'inline') ?>
	<?php if ($filterLayout !== 'inline' && (!empty($alter_terms) || !empty($ortschaft_terms) || !empty($angebot_terms))): ?>
	<div class="po-sp__filter-fab">
		<button type="button" class="po-sp__filter-trigger">
			<span class="po-sp__filter-text">Filtern</span>
			<svg class="po-sp__filter-icon" viewBox="0 0 24 24" fill="none">
				<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<div class="po-sp__filter-dropdown">
			<div class="po-sp__filter-group">
				<span class="po-sp__filter-group-label">Alle anzeigen</span>
				<button type="button" class="po-sp__filter-option is-active" data-filter="all">Alle Trainings</button>
			</div>
			<?php if (!empty($alter_terms)): ?>
			<div class="po-sp__filter-group">
				<span class="po-sp__filter-group-label">Nach Alter</span>
				<?php foreach ($alter_terms as $term): ?>
				<button type="button" class="po-sp__filter-option" data-filter="<?php echo esc_attr($term->slug); ?>">
					<span class="po-sp__filter-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php if (!empty($ortschaft_terms) && !$auto_filter_active): ?>
			<div class="po-sp__filter-group">
				<span class="po-sp__filter-group-label">Nach Standort</span>
				<?php foreach ($ortschaft_terms as $term): ?>
				<button type="button" class="po-sp__filter-option" data-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php if (!empty($angebot_terms)): ?>
			<div class="po-sp__filter-group">
				<span class="po-sp__filter-group-label">Nach Angebot</span>
				<?php foreach ($angebot_terms as $term): ?>
				<button type="button" class="po-sp__filter-option" data-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</section>

<?php foreach ($klassen as $klasse): ?>
<?php
$available_dates = function_exists('parkourone_get_available_dates_for_event') ? parkourone_get_available_dates_for_event($klasse['id']) : [];
$mood_texts = [
	'minis' => 'Erste Bewegungserfahrungen in spielerischer Atmosphäre - hier entdecken die Kleinsten ihre motorischen Fähigkeiten.',
	'kids' => 'Spielerisch Bewegungstalente entdecken: Klettern, Springen und Balancieren in einer sicheren Umgebung.',
	'juniors' => 'Von den Basics bis zu fortgeschrittenen Moves - hier entwickelst du deine Skills in einer motivierenden Gruppe.',
	'adults' => 'Den eigenen Körper neu entdecken, Grenzen verschieben und Techniken verfeinern - Training für alle, die mehr wollen.',
	'women' => 'In entspannter Atmosphäre unter Frauen trainieren - Kraft, Beweglichkeit und Selbstvertrauen aufbauen.',
	'original' => 'Das Original-Training für alle, die Parkour in seiner ursprünglichen Form erleben wollen - authentisch und intensiv.',
	'masters' => 'Erfahrung trifft Bewegung - Training für alle, die auch mit den Jahren aktiv und beweglich bleiben wollen.',
	'seniors' => 'Koordination erhalten, Fitness aufbauen und mit Gleichgesinnten trainieren - beweglich bleiben in jedem Alter.',
	'juniors-adults' => 'Den eigenen Körper kennenlernen, Grenzen austesten und fortgeschrittene Techniken meistern - intensives Training mit der Möglichkeit, an den eigenen Grenzen zu wachsen.',
	'seniors-masters' => 'Koordination erhalten, Fitness aufbauen und mit Gleichgesinnten trainieren - beweglich bleiben und den Körper langfristig fit halten.'
];
// Mood-Text: kombinierter Slug (z.B. juniors-adults) hat Vorrang
$category = '';
$age_parts = array_filter(explode(' ', $klasse['age_slug'] ?? ''));
if (count($age_parts) > 1) {
	sort($age_parts);
	$combined = implode('-', $age_parts);
	if (isset($mood_texts[$combined])) $category = $combined;
	if (!$category) { $rev = implode('-', array_reverse($age_parts)); if (isset($mood_texts[$rev])) $category = $rev; }
}
if (!$category) {
	foreach ($age_parts as $s) { if (isset($mood_texts[$s])) { $category = $s; break; } }
}
// Coach-Text mit Link wenn Profil vorhanden
if (!empty($klasse['headcoach'])) {
	if ($klasse['coach_has_profile']) {
		$coach_text = ' von <button type="button" class="po-sp__coach-link-inline" data-goto-slide="coach">' . esc_html($klasse['headcoach']) . '</button> geleitet und';
	} else {
		$coach_text = ' von ' . esc_html($klasse['headcoach']) . ' geleitet und';
	}
} else {
	$coach_text = '';
}
$time_text = $klasse['start_time'] ? $klasse['start_time'] . ($klasse['end_time'] ? ' - ' . $klasse['end_time'] . ' Uhr' : ' Uhr') : '';
?>
<div class="po-overlay" id="<?php echo esc_attr($unique_id . '-modal-' . $klasse['id']); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-overlay__backdrop"></div>
	<div class="po-overlay__panel">
		<button class="po-overlay__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<?php echo parkourone_share_button(
			add_query_arg('training', $klasse['id'], get_permalink()),
			$klasse['title'] . ' – ParkourONE',
			'',
			true
		); ?>

		<div class="po-steps" data-step="0" data-class-title="<?php echo esc_attr($klasse['title']); ?>">
			<div class="po-steps__track">
				
				<div class="po-steps__slide is-active" data-slide="0">
					<header class="po-steps__header">
						<span class="po-steps__eyebrow"><?php echo esc_html($klasse['weekday']); ?></span>
						<h2 class="po-steps__heading"><?php echo esc_html($klasse['title']); ?></h2>
					</header>

					<dl class="po-steps__meta">
						<?php if ($klasse['start_time']): ?>
						<div class="po-steps__meta-item">
							<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></dt>
							<dd><?php echo esc_html($klasse['start_time']); ?><?php if ($klasse['end_time']): ?> – <?php echo esc_html($klasse['end_time']); ?><?php endif; ?> Uhr</dd>
						</div>
						<?php endif; ?>
						<?php if (!empty($klasse['venue'])): ?>
						<div class="po-steps__meta-item">
							<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></dt>
							<dd><?php echo esc_html($klasse['venue']); ?></dd>
						</div>
						<?php endif; ?>
					</dl>

					<?php if ($category && isset($mood_texts[$category])): ?>
					<p class="po-steps__description">
						Dieses Training wird<?php echo $coach_text; ?> findet wöchentlich <?php echo esc_html($klasse['weekday']); ?> von <?php echo esc_html($time_text); ?> statt.<?php if (!empty($klasse['venue'])): ?> Treffpunkt ist <?php echo esc_html($klasse['venue']); ?>.<?php endif; ?> <?php echo esc_html($mood_texts[$category]); ?>
					</p>
					<?php endif; ?>

					<?php if (!empty($klasse['dropdown_info'])): ?>
					<div class="po-steps__info-notice">
						<?php echo wp_kses_post($klasse['dropdown_info']); ?>
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

				<div class="po-steps__slide is-next" data-slide="1">
					<header class="po-steps__header">
						<span class="po-steps__eyebrow">Schritt 1 von 2</span>
						<h2 class="po-steps__heading">Termin wählen</h2>
						<p class="po-steps__subheading"><?php echo esc_html($klasse['title']); ?></p>
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
					
					<button type="button" class="po-steps__back-link">← Zurück zur Übersicht</button>
				</div>
				
				<div class="po-steps__slide is-next" data-slide="2">
					<header class="po-steps__header">
						<span class="po-steps__eyebrow">Schritt 2 von 2</span>
						<h2 class="po-steps__heading">Wer nimmt teil?</h2>
						<p class="po-steps__subheading po-steps__selected-date"></p>
					</header>
					
					<form class="po-steps__form">
						<input type="hidden" name="product_id" value="">
						<input type="hidden" name="event_id" value="<?php echo esc_attr($klasse['id']); ?>">
						
						<div class="po-steps__field">
							<label for="vorname-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>">Vorname</label>
							<input type="text" id="vorname-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>" name="vorname" required autocomplete="given-name">
						</div>
						
						<div class="po-steps__field">
							<label for="name-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>">Nachname</label>
							<input type="text" id="name-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>" name="name" required autocomplete="family-name">
						</div>
						
						<div class="po-steps__field">
							<label for="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>">Geburtsdatum</label>
							<input type="date" id="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $klasse['id']); ?>" name="geburtsdatum" required>
						</div>
						
						<button type="submit" class="po-steps__cta po-steps__submit">Zum Warenkorb hinzufügen</button>
					</form>
					
					<button type="button" class="po-steps__back-link">← Anderer Termin</button>
				</div>
				
				<div class="po-steps__slide is-next" data-slide="3">
					<div class="po-steps__success">
						<div class="po-steps__success-icon">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M20 6L9 17l-5-5"/>
							</svg>
						</div>
						<h2 class="po-steps__heading">Hinzugefügt!</h2>
						<p class="po-steps__subheading"><?php echo esc_html($klasse['title']); ?></p>
						<p class="po-steps__selected-date-confirm"></p>
					</div>
				</div>

				<?php // Coach-Slide (außerhalb des normalen Step-Flows) ?>
				<?php if ($klasse['coach_has_profile'] && isset($coach_profiles[$klasse['headcoach']])):
					$coach = $coach_profiles[$klasse['headcoach']];
				?>
				<div class="po-steps__slide po-sp__coach-slide" data-slide="coach">
					<button type="button" class="po-sp__back-to-overview po-sp__back-to-overview--top">← Zurück zur Klasse</button>
					<div class="po-sp-coach">
						<header class="po-sp-coach__header">
							<?php if (!empty($coach['card_image'])): ?>
							<img src="<?php echo esc_url($coach['card_image']); ?>" alt="Coach <?php echo esc_attr($coach['name']); ?>" class="po-sp-coach__avatar">
							<?php endif; ?>
							<div class="po-sp-coach__info">
								<h2 class="po-sp-coach__name"><?php echo esc_html($coach['name']); ?></h2>
								<?php if (!empty($coach['rolle'])): ?>
								<p class="po-sp-coach__role"><?php echo esc_html($coach['rolle']); ?></p>
								<?php endif; ?>
							</div>
						</header>

						<?php if (!empty($coach['hero_bild'])): ?>
						<div class="po-sp-coach__hero">
							<img src="<?php echo esc_url($coach['hero_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?> beim Training">
						</div>
						<?php endif; ?>

						<?php if (!empty($coach['parkour_seit']) || !empty($coach['po_seit']) || !empty($coach['leitsatz'])): ?>
						<dl class="po-sp-coach__facts">
							<?php if (!empty($coach['parkour_seit'])): ?>
							<div class="po-sp-coach__fact">
								<dt>Parkour seit</dt>
								<dd><?php echo esc_html($coach['parkour_seit']); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($coach['po_seit'])): ?>
							<div class="po-sp-coach__fact">
								<dt>Bei ParkourONE seit</dt>
								<dd><?php echo esc_html($coach['po_seit']); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($coach['leitsatz'])): ?>
							<div class="po-sp-coach__fact po-sp-coach__fact--full">
								<dt>Leitsatz</dt>
								<dd>&laquo;<?php echo esc_html($coach['leitsatz']); ?>&raquo;</dd>
							</div>
							<?php endif; ?>
						</dl>
						<?php endif; ?>

						<?php if (!empty($coach['kurzvorstellung'])): ?>
						<div class="po-sp-coach__section">
							<p><?php echo esc_html($coach['kurzvorstellung']); ?></p>
							<?php if (!empty($coach['philosophie_bild'])): ?>
							<img src="<?php echo esc_url($coach['philosophie_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?>" class="po-sp-coach__image">
							<?php endif; ?>
						</div>
						<?php endif; ?>

						<?php if (!empty($coach['moment'])): ?>
						<div class="po-sp-coach__section">
							<p><strong>Ein Parkour Moment, der mich geprägt hat:</strong> <?php echo esc_html($coach['moment']); ?></p>
							<?php if (!empty($coach['moment_bild'])): ?>
							<img src="<?php echo esc_url($coach['moment_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?> - Parkour Moment" class="po-sp-coach__image">
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>

					<button type="button" class="po-sp__back-to-overview">← Zurück zur Klasse</button>
				</div>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>
<?php endforeach; ?>

<?php else: ?>
<section class="po-sp po-sp--empty">
	<p>Keine Trainings gefunden.</p>
</section>
<?php endif; ?>
