<?php
$headline = $attributes['headline'] ?? '';
$filterMode = $attributes['filterMode'] ?? 'none';
$filterAge = $attributes['filterAge'] ?? '';
$filterLocation = $attributes['filterLocation'] ?? '';
$filterOffer = $attributes['filterOffer'] ?? '';
$filterWeekday = $attributes['filterWeekday'] ?? '';
$bookingPageUrl = $attributes['bookingPageUrl'] ?? '/probetraining-buchen/';
$buttonText = $attributes['buttonText'] ?? 'Probetraining buchen';
$hideIfEmpty = $attributes['hideIfEmpty'] ?? false;

// Coach-Profile für Modals sammeln
$coach_profiles = [];

$args = [
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
];

$tax_query = [];

if ($filterAge) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => $filterAge
	];
}
if ($filterLocation) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => $filterLocation
	];
}
if ($filterOffer) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => $filterOffer
	];
}
if ($filterWeekday) {
	$tax_query[] = [
		'taxonomy' => 'event_category',
		'field' => 'slug',
		'terms' => $filterWeekday
	];
}

if (!empty($tax_query)) {
	$tax_query['relation'] = 'AND';
	$args['tax_query'] = $tax_query;
}

$query = new WP_Query($args);
$klassen = [];

// Alle verwendeten Age und Location Slugs sammeln
$used_age_slugs = [];
$used_location_slugs = [];

if ($query->have_posts()) {
	while ($query->have_posts()) {
		$query->the_post();
		$event_id = get_the_ID();

		$permalink = get_post_meta($event_id, '_event_permalink', true);
		if (empty($permalink)) {
			$permalink = sanitize_title(get_the_title());
		}

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

		$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		$age_term = '';
		$offer_term = '';
		$location_term = '';

		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && !is_wp_error($parent)) {
					if ($parent->slug === 'alter') {
						$age_term = $term->slug;
						$used_age_slugs[] = $term->slug;
					}
					if ($parent->slug === 'angebot') $offer_term = $term->slug;
					if ($parent->slug === 'ortschaft') {
						$location_term = $term->slug;
						$used_location_slugs[] = $term->slug;
					}
				}
			}
		}

		// Zentrale Bildlogik mit automatischem Fallback (Event-spezifisch > Featured > Kategorie-Fallback)
		$event_image = function_exists('parkourone_get_event_image')
			? parkourone_get_event_image($event_id, $age_term)
			: get_the_post_thumbnail_url($event_id, 'full');

		$headcoach_name = get_post_meta($event_id, '_event_headcoach', true);

		$klasse = [
			'id' => $event_id,
			'title' => get_the_title(),
			'permalink' => $permalink,
			'headcoach' => $headcoach_name,
			'headcoach_image' => get_post_meta($event_id, '_event_headcoach_image_url', true),
			'headcoach_email' => get_post_meta($event_id, '_event_headcoach_email', true),
			'headcoach_phone' => get_post_meta($event_id, '_event_headcoach_phone', true),
			'start_time' => get_post_meta($event_id, '_event_start_time', true),
			'end_time' => get_post_meta($event_id, '_event_end_time', true),
			'venue' => get_post_meta($event_id, '_event_venue', true),
			'description' => get_post_meta($event_id, '_event_description', true),
			'weekday' => $weekday_name,
			'image' => $event_image,
			'category' => $age_term,
			'location' => $location_term,
			'offer' => $offer_term,
			'dropdown_info' => get_post_meta($event_id, '_event_dropdown_info', true),
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
					$api_image = $coach_data['api_image'] ?? '';

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
						'card_image' => $hero_bild ?: $philosophie_bild ?: $moment_bild ?: $api_image
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
	}
	wp_reset_postdata();
}

// Unique slugs
$used_age_slugs = array_unique(array_filter($used_age_slugs));
$used_location_slugs = array_unique(array_filter($used_location_slugs));

usort($klassen, function($a, $b) {
	$days_order = ['Montag' => 1, 'Dienstag' => 2, 'Mittwoch' => 3, 'Donnerstag' => 4, 'Freitag' => 5, 'Samstag' => 6, 'Sonntag' => 7];
	$a_order = $days_order[$a['weekday']] ?? 99;
	$b_order = $days_order[$b['weekday']] ?? 99;
	if ($a_order !== $b_order) return $a_order - $b_order;
	return strcmp($a['start_time'], $b['start_time']);
});

$unique_id = 'klassen-slider-' . uniqid();

// Hole Taxonomie-Terms fuer Filter
$alter_parent = get_term_by('slug', 'alter', 'event_category');
$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');

$alter_terms = [];
$ortschaft_terms = [];

if ($alter_parent && !is_wp_error($alter_parent)) {
	$alter_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $alter_parent->term_id,
		'hide_empty' => true,
		'orderby' => 'term_order',
		'order' => 'ASC'
	]);
	if (is_wp_error($alter_terms)) $alter_terms = [];
	// Nur Terms anzeigen, die auch in den Klassen vorkommen
	$alter_terms = array_filter($alter_terms, function($term) use ($used_age_slugs) {
		return in_array($term->slug, $used_age_slugs);
	});
}

if ($ortschaft_parent && !is_wp_error($ortschaft_parent)) {
	$ortschaft_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => true,
		'orderby' => 'name',
		'order' => 'ASC'
	]);
	if (is_wp_error($ortschaft_terms)) $ortschaft_terms = [];
	// Nur Terms anzeigen, die auch in den Klassen vorkommen
	$ortschaft_terms = array_filter($ortschaft_terms, function($term) use ($used_location_slugs) {
		return in_array($term->slug, $used_location_slugs);
	});
}

// Farben fuer Altersgruppen
$age_colors = [
	'minis' => '#ff9500',
	'kids' => '#34c759',
	'juniors' => '#007aff',
	'adults' => '#5856d6',
	'seniors' => '#af52de',
	'masters' => '#ff2d55'
];

// Filter anzeigen basierend auf filterMode
$showAgeFilter = in_array($filterMode, ['age', 'both']) && count($alter_terms) > 1;
$showLocationFilter = in_array($filterMode, ['location', 'both']) && count($ortschaft_terms) > 1;
$hasFilters = $showAgeFilter || $showLocationFilter;
?>

<?php if (!empty($klassen)): ?>
<section class="po-klassen-slider <?php echo $hasFilters ? 'po-klassen-slider--has-filter' : ''; ?>" id="<?php echo esc_attr($unique_id); ?>">
	<?php if ($headline): ?>
		<h2 class="po-klassen-slider__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<?php if ($hasFilters): ?>
	<div class="po-klassen-slider__filters">
		<?php if ($showAgeFilter): ?>
		<div class="po-klassen-slider__dropdown" data-filter-type="age">
			<button type="button" class="po-klassen-slider__dropdown-trigger" aria-expanded="false">
				<span class="po-klassen-slider__dropdown-value">Alle Altersgruppen</span>
				<svg class="po-klassen-slider__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-klassen-slider__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-klassen-slider__dropdown-option is-selected" data-value="all">
					Alle Altersgruppen
				</button>
				<?php foreach ($alter_terms as $term): ?>
				<button type="button" class="po-klassen-slider__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>">
					<span class="po-klassen-slider__dropdown-dot" style="background: <?php echo esc_attr($age_colors[$term->slug] ?? '#0066cc'); ?>"></span>
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ($showLocationFilter): ?>
		<div class="po-klassen-slider__dropdown" data-filter-type="location">
			<button type="button" class="po-klassen-slider__dropdown-trigger" aria-expanded="false">
				<span class="po-klassen-slider__dropdown-value">Alle Standorte</span>
				<svg class="po-klassen-slider__dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="po-klassen-slider__dropdown-panel" aria-hidden="true">
				<button type="button" class="po-klassen-slider__dropdown-option is-selected" data-value="all">
					Alle Standorte
				</button>
				<?php foreach ($ortschaft_terms as $term): ?>
				<button type="button" class="po-klassen-slider__dropdown-option" data-value="<?php echo esc_attr($term->slug); ?>">
					<?php echo esc_html($term->name); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="po-klassen-slider__track">
		<?php foreach ($klassen as $index => $klasse):
			$filter_data = trim($klasse['category'] . ' ' . $klasse['location']);
		?>
			<?php
			// Coach-Bild: Bevorzugt aus coach_profiles (hat Fallbacks), sonst aus Event-Meta
			$coach_avatar = '';
			if (!empty($klasse['headcoach']) && isset($coach_profiles[$klasse['headcoach']])) {
				$coach_avatar = $coach_profiles[$klasse['headcoach']]['card_image'] ?? '';
			}
			if (empty($coach_avatar) && !empty($klasse['headcoach_image'])) {
				$coach_avatar = $klasse['headcoach_image'];
			}
			?>
			<article class="po-card" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>" data-filters="<?php echo esc_attr($filter_data); ?>">
				<div class="po-card__visual">
					<?php if (!empty($klasse['image'])): ?>
						<img src="<?php echo esc_url($klasse['image']); ?>" alt="<?php echo esc_attr($klasse['title']); ?>" class="po-card__image" loading="lazy">
					<?php else: ?>
						<div class="po-card__placeholder"></div>
					<?php endif; ?>
					<div class="po-card__gradient"></div>
					<?php if (!empty($coach_avatar)): ?>
					<div class="po-card__coach">
						<img src="<?php echo esc_url($coach_avatar); ?>" alt="<?php echo esc_attr($klasse['headcoach']); ?>" loading="lazy">
					</div>
					<?php endif; ?>
				</div>
				<div class="po-card__body">
					<?php if ($klasse['offer'] === 'ferienkurs'): ?>
						<span class="po-card__badge po-card__badge--ferienkurs">Ferienkurs</span>
					<?php endif; ?>
					<span class="po-card__eyebrow"><?php echo esc_html($klasse['weekday']); ?></span>
					<h3 class="po-card__title"><?php echo esc_html($klasse['title']); ?></h3>
				</div>
				<button class="po-card__action" aria-label="Mehr erfahren">
					<svg viewBox="0 0 24 24" fill="none">
						<circle cx="12" cy="12" r="12" fill="currentColor"/>
						<path d="M12 7v10M7 12h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="po-klassen-slider__nav">
		<button type="button" class="po-klassen-slider__nav-btn po-klassen-slider__nav-prev" aria-label="Zurück" disabled>
			<svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="po-klassen-slider__nav-btn po-klassen-slider__nav-next" aria-label="Weiter">
			<svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>

	<div class="po-klassen-slider__empty-message" style="display: none;">
		<p>Keine Klassen für diese Auswahl gefunden.</p>
	</div>
</section>

<?php foreach ($klassen as $index => $klasse): ?>
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
$category = $klasse['category'] ?? '';
// Coach-Text mit Link wenn Profil vorhanden
if (!empty($klasse['headcoach'])) {
	if ($klasse['coach_has_profile']) {
		$coach_text = ' von <button type="button" class="po-ks__coach-link-inline" data-goto-slide="coach">' . esc_html($klasse['headcoach']) . '</button> geleitet und';
	} else {
		$coach_text = ' von ' . esc_html($klasse['headcoach']) . ' geleitet und';
	}
} else {
	$coach_text = '';
}
$time_text = $klasse['start_time'] ? $klasse['start_time'] . ($klasse['end_time'] ? ' - ' . $klasse['end_time'] . ' Uhr' : ' Uhr') : '';
?>
<div class="po-overlay" id="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-overlay__backdrop"></div>
	<div class="po-overlay__panel">
		<button class="po-overlay__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>

		<div class="po-steps" data-step="0" data-class-title="<?php echo esc_attr($klasse['title']); ?>">
			<div class="po-steps__track">

				<div class="po-steps__slide is-active" data-slide="0">
					<header class="po-steps__header">
						<span class="po-steps__eyebrow"><?php echo (($klasse['offer'] ?? '') === 'ferienkurs') ? 'Ferienkurs' : esc_html($klasse['weekday']); ?></span>
						<h2 class="po-steps__heading"><?php echo esc_html($klasse['title']); ?></h2>
					</header>

					<dl class="po-steps__meta">
						<?php if ($klasse['start_time']): ?>
						<div class="po-steps__meta-item">
							<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></dt>
							<dd><?php echo esc_html($klasse['start_time']); ?><?php if ($klasse['end_time']): ?> - <?php echo esc_html($klasse['end_time']); ?><?php endif; ?> Uhr</dd>
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
						<?php if (($klasse['offer'] ?? '') === 'ferienkurs'): ?>
						Dieser Ferienkurs wird<?php echo $coach_text; ?> findet von <?php echo esc_html($time_text); ?> statt.<?php if (!empty($klasse['venue'])): ?> Treffpunkt ist <?php echo esc_html($klasse['venue']); ?>.<?php endif; ?> <?php echo esc_html($mood_texts[$category]); ?>
						<?php else: ?>
						Dieses Training wird<?php echo $coach_text; ?> findet wöchentlich <?php echo esc_html($klasse['weekday']); ?> von <?php echo esc_html($time_text); ?> statt.<?php if (!empty($klasse['venue'])): ?> Treffpunkt ist <?php echo esc_html($klasse['venue']); ?>.<?php endif; ?> <?php echo esc_html($mood_texts[$category]); ?>
						<?php endif; ?>
					</p>
					<?php endif; ?>

					<?php if (!empty($klasse['description'])): ?>
					<div class="po-steps__content">
						<?php echo wp_kses_post($klasse['description']); ?>
					</div>
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
							<label for="vorname-<?php echo esc_attr($unique_id . '-' . $index); ?>">Vorname</label>
							<input type="text" id="vorname-<?php echo esc_attr($unique_id . '-' . $index); ?>" name="vorname" required autocomplete="given-name">
						</div>

						<div class="po-steps__field">
							<label for="name-<?php echo esc_attr($unique_id . '-' . $index); ?>">Nachname</label>
							<input type="text" id="name-<?php echo esc_attr($unique_id . '-' . $index); ?>" name="name" required autocomplete="family-name">
						</div>

						<div class="po-steps__field">
							<label for="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $index); ?>">Geburtsdatum</label>
							<input type="date" id="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $index); ?>" name="geburtsdatum" required>
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

				<?php // Coach-Slide (ausserhalb des normalen Step-Flows) ?>
				<?php if ($klasse['coach_has_profile'] && isset($coach_profiles[$klasse['headcoach']])):
					$coach = $coach_profiles[$klasse['headcoach']];
				?>
				<div class="po-steps__slide po-ks__coach-slide" data-slide="coach">
					<button type="button" class="po-ks__back-to-overview po-ks__back-to-overview--top">← Zurück zur Klasse</button>
					<div class="po-ks-coach">
						<header class="po-ks-coach__header">
							<?php if (!empty($coach['card_image'])): ?>
							<img src="<?php echo esc_url($coach['card_image']); ?>" alt="Coach <?php echo esc_attr($coach['name']); ?>" class="po-ks-coach__avatar">
							<?php endif; ?>
							<div class="po-ks-coach__info">
								<h2 class="po-ks-coach__name"><?php echo esc_html($coach['name']); ?></h2>
								<?php if (!empty($coach['rolle'])): ?>
								<p class="po-ks-coach__role"><?php echo esc_html($coach['rolle']); ?></p>
								<?php endif; ?>
							</div>
						</header>

						<?php if (!empty($coach['hero_bild'])): ?>
						<div class="po-ks-coach__hero">
							<img src="<?php echo esc_url($coach['hero_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?> beim Training">
						</div>
						<?php endif; ?>

						<?php if (!empty($coach['parkour_seit']) || !empty($coach['po_seit']) || !empty($coach['leitsatz'])): ?>
						<dl class="po-ks-coach__facts">
							<?php if (!empty($coach['parkour_seit'])): ?>
							<div class="po-ks-coach__fact">
								<dt>Parkour seit</dt>
								<dd><?php echo esc_html($coach['parkour_seit']); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($coach['po_seit'])): ?>
							<div class="po-ks-coach__fact">
								<dt>Bei ParkourONE seit</dt>
								<dd><?php echo esc_html($coach['po_seit']); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($coach['leitsatz'])): ?>
							<div class="po-ks-coach__fact po-ks-coach__fact--full">
								<dt>Leitsatz</dt>
								<dd>&laquo;<?php echo esc_html($coach['leitsatz']); ?>&raquo;</dd>
							</div>
							<?php endif; ?>
						</dl>
						<?php endif; ?>

						<?php if (!empty($coach['kurzvorstellung'])): ?>
						<div class="po-ks-coach__section">
							<p><?php echo esc_html($coach['kurzvorstellung']); ?></p>
							<?php if (!empty($coach['philosophie_bild'])): ?>
							<img src="<?php echo esc_url($coach['philosophie_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?>" class="po-ks-coach__image">
							<?php endif; ?>
						</div>
						<?php endif; ?>

						<?php if (!empty($coach['moment'])): ?>
						<div class="po-ks-coach__section">
							<p><strong>Ein Parkour Moment, der mich geprägt hat:</strong> <?php echo esc_html($coach['moment']); ?></p>
							<?php if (!empty($coach['moment_bild'])): ?>
							<img src="<?php echo esc_url($coach['moment_bild']); ?>" alt="<?php echo esc_attr($coach['name']); ?> - Parkour Moment" class="po-ks-coach__image">
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>

					<button type="button" class="po-ks__back-to-overview">← Zurück zur Klasse</button>
				</div>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>
<?php endforeach; ?>

<script>
(function() {
	var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
	if (!section) return;

	var track = section.querySelector('.po-klassen-slider__track');
	var cards = section.querySelectorAll('.po-card');
	var emptyMessage = section.querySelector('.po-klassen-slider__empty-message');

	// ========================================
	// FILTER LOGIC
	// ========================================
	var dropdowns = section.querySelectorAll('.po-klassen-slider__dropdown');
	var currentAgeFilter = 'all';
	var currentLocationFilter = 'all';

	function applyFilters() {
		var visibleCount = 0;

		cards.forEach(function(card) {
			var filters = card.getAttribute('data-filters') || '';
			var matchAge = currentAgeFilter === 'all' || filters.indexOf(currentAgeFilter) !== -1;
			var matchLocation = currentLocationFilter === 'all' || filters.indexOf(currentLocationFilter) !== -1;

			if (matchAge && matchLocation) {
				card.style.display = '';
				visibleCount++;
			} else {
				card.style.display = 'none';
			}
		});

		// Empty message anzeigen/verstecken
		if (emptyMessage) {
			emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';
		}
	}

	dropdowns.forEach(function(dropdown) {
		var trigger = dropdown.querySelector('.po-klassen-slider__dropdown-trigger');
		var panel = dropdown.querySelector('.po-klassen-slider__dropdown-panel');
		var valueEl = dropdown.querySelector('.po-klassen-slider__dropdown-value');
		var options = dropdown.querySelectorAll('.po-klassen-slider__dropdown-option');
		var filterType = dropdown.getAttribute('data-filter-type');

		// Toggle dropdown
		trigger.addEventListener('click', function(e) {
			e.stopPropagation();
			var isOpen = dropdown.classList.contains('is-open');

			// Close all other dropdowns
			dropdowns.forEach(function(d) {
				d.classList.remove('is-open');
				d.querySelector('.po-klassen-slider__dropdown-trigger').setAttribute('aria-expanded', 'false');
				d.querySelector('.po-klassen-slider__dropdown-panel').setAttribute('aria-hidden', 'true');
			});

			// Toggle this dropdown
			if (!isOpen) {
				dropdown.classList.add('is-open');
				trigger.setAttribute('aria-expanded', 'true');
				panel.setAttribute('aria-hidden', 'false');
			}
		});

		// Option selection
		options.forEach(function(option) {
			option.addEventListener('click', function(e) {
				e.stopPropagation();
				var value = option.getAttribute('data-value');

				// Update selected state
				options.forEach(function(o) { o.classList.remove('is-selected'); });
				option.classList.add('is-selected');

				// Update trigger text
				valueEl.textContent = option.textContent.trim();

				// Update filter value
				if (filterType === 'age') {
					currentAgeFilter = value;
				} else if (filterType === 'location') {
					currentLocationFilter = value;
				}

				// Apply filters
				applyFilters();

				// Close dropdown
				dropdown.classList.remove('is-open');
				trigger.setAttribute('aria-expanded', 'false');
				panel.setAttribute('aria-hidden', 'true');
			});
		});
	});

	// Close dropdowns on outside click
	document.addEventListener('click', function() {
		dropdowns.forEach(function(dropdown) {
			dropdown.classList.remove('is-open');
			dropdown.querySelector('.po-klassen-slider__dropdown-trigger').setAttribute('aria-expanded', 'false');
			dropdown.querySelector('.po-klassen-slider__dropdown-panel').setAttribute('aria-hidden', 'true');
		});
	});

	// ========================================
	// MODAL LOGIC
	// ========================================
	cards.forEach(function(card) {
		var modalId = card.getAttribute('data-modal-target');
		var modal = document.getElementById(modalId);
		if (!modal) return;

		var closeBtn = modal.querySelector('.po-overlay__close');
		var backdrop = modal.querySelector('.po-overlay__backdrop');

		function openModal() {
			modal.classList.add('is-active');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('po-no-scroll');
			setTimeout(function() {
				modal.querySelector('.po-overlay__close').focus();
			}, 100);
		}

		function closeModal() {
			modal.classList.remove('is-active');
			modal.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('po-no-scroll');
		}

		card.addEventListener('click', openModal);
		closeBtn.addEventListener('click', closeModal);
		backdrop.addEventListener('click', closeModal);

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && modal.classList.contains('is-active')) {
				closeModal();
			}
		});
	});

	// ========================================
	// COACH-SLIDE NAVIGATION
	// ========================================
	document.querySelectorAll('.po-ks__coach-link-inline').forEach(function(link) {
		link.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var stepsContainer = this.closest('.po-steps');
			if (!stepsContainer) return;

			var slides = stepsContainer.querySelectorAll('.po-steps__slide');
			var coachSlide = stepsContainer.querySelector('[data-slide="coach"]');

			if (coachSlide) {
				slides.forEach(function(s) {
					s.classList.remove('is-active');
					s.classList.add('is-next');
				});
				coachSlide.classList.remove('is-next');
				coachSlide.classList.add('is-active');
			}
		});
	});

	// Back to Overview from Coach-Slide
	document.querySelectorAll('.po-ks__back-to-overview').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var stepsContainer = this.closest('.po-steps');
			if (!stepsContainer) return;

			var slides = stepsContainer.querySelectorAll('.po-steps__slide:not(.po-ks__coach-slide)');
			var coachSlide = stepsContainer.querySelector('.po-ks__coach-slide');
			var overviewSlide = stepsContainer.querySelector('[data-slide="0"]');

			// Coach-Slide verstecken
			if (coachSlide) {
				coachSlide.classList.remove('is-active');
			}

			// Normale Slides zurücksetzen
			slides.forEach(function(s, i) {
				s.classList.remove('is-active', 'is-prev', 'is-next');
				if (i === 0) {
					s.classList.add('is-active');
				} else {
					s.classList.add('is-next');
				}
			});

			// data-step synchron halten
			stepsContainer.setAttribute('data-step', '0');
		});
	});

	// ========================================
	// DRAG SCROLL
	// ========================================
	var isDown = false;
	var startX;
	var scrollLeft;

	track.addEventListener('mousedown', function(e) {
		isDown = true;
		track.classList.add('is-grabbing');
		startX = e.pageX - track.offsetLeft;
		scrollLeft = track.scrollLeft;
	});

	track.addEventListener('mouseleave', function() {
		isDown = false;
		track.classList.remove('is-grabbing');
	});

	track.addEventListener('mouseup', function() {
		isDown = false;
		track.classList.remove('is-grabbing');
	});

	track.addEventListener('mousemove', function(e) {
		if (!isDown) return;
		e.preventDefault();
		var x = e.pageX - track.offsetLeft;
		var walk = (x - startX) * 1.5;
		track.scrollLeft = scrollLeft - walk;
	});

	// ========================================
	// NAV BUTTONS
	// ========================================
	var prevBtn = section.querySelector('.po-klassen-slider__nav-prev');
	var nextBtn = section.querySelector('.po-klassen-slider__nav-next');

	if (prevBtn && nextBtn) {
		function getCardWidth() {
			var firstCard = track.querySelector('.po-card');
			if (!firstCard) return 300;
			return firstCard.offsetWidth + 20; // card width + gap
		}

		function getVisibleCards() {
			return Math.max(1, Math.floor(track.offsetWidth / getCardWidth()));
		}

		function updateNav() {
			var sl = track.scrollLeft;
			var maxScroll = track.scrollWidth - track.offsetWidth;
			prevBtn.disabled = sl <= 0;
			nextBtn.disabled = sl >= maxScroll - 10;
		}

		prevBtn.addEventListener('click', function() {
			track.scrollBy({ left: -getCardWidth() * getVisibleCards(), behavior: 'smooth' });
		});

		nextBtn.addEventListener('click', function() {
			track.scrollBy({ left: getCardWidth() * getVisibleCards(), behavior: 'smooth' });
		});

		track.addEventListener('scroll', updateNav);
		window.addEventListener('resize', updateNav);
		updateNav();
	}
})();
</script>
<?php else: ?>
<?php if (!$hideIfEmpty): ?>
<section class="po-klassen-slider po-klassen-slider--empty">
	<p>Keine Klassen gefunden.</p>
</section>
<?php endif; ?>
<?php endif; ?>
