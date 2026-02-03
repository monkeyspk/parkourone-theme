<?php
$headline = $attributes['headline'] ?? 'Unser Team';
$intro = $attributes['intro'] ?? 'Das sind wir. Ob draussen vor den Klassen oder im Büro hinter den Kulissen – lerne unser Team kennen.';

$all_coaches = get_posts([
	'post_type' => 'coach',
	'posts_per_page' => -1,
	'post_status' => 'publish'
]);

$all_events = get_posts([
	'post_type' => 'event',
	'posts_per_page' => -1,
	'post_status' => 'publish'
]);

$coach_locations = [];
foreach ($all_events as $event) {
	$headcoach = get_post_meta($event->ID, '_event_headcoach', true);
	if (empty($headcoach)) continue;
	
	$terms = wp_get_post_terms($event->ID, 'event_category', ['fields' => 'all']);
	foreach ($terms as $term) {
		if ($term->parent) {
			$parent = get_term($term->parent, 'event_category');
			if ($parent && !is_wp_error($parent) && $parent->slug === 'ortschaft') {
				$coach_key = strtolower(trim($headcoach));
				if (!isset($coach_locations[$coach_key])) {
					$coach_locations[$coach_key] = [];
				}
				if (!in_array($term->slug, $coach_locations[$coach_key])) {
					$coach_locations[$coach_key][] = $term->slug;
				}
			}
		}
	}
}

$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');
$location_terms = [];
if ($ortschaft_parent && !is_wp_error($ortschaft_parent)) {
	$location_terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false
	]);
}

$members = [];
foreach ($all_coaches as $coach) {
	$coach_id = $coach->ID;
	
	$hero_bild = get_post_meta($coach_id, '_coach_hero_bild', true);
	$philosophie_bild = get_post_meta($coach_id, '_coach_philosophie_bild', true);
	$moment_bild = get_post_meta($coach_id, '_coach_moment_bild', true);
	$api_image = get_post_meta($coach_id, '_coach_api_image', true);
	
	$card_image = $hero_bild ?: $philosophie_bild ?: $moment_bild ?: $api_image;
	
	$coach_key = strtolower(trim($coach->post_title));
	$locations = $coach_locations[$coach_key] ?? [];
	
	$coach_data = [
		'id' => $coach_id,
		'name' => $coach->post_title,
		'card_image' => $card_image,
		'api_image' => $api_image,
		'source' => get_post_meta($coach_id, '_coach_source', true),
		'rolle' => get_post_meta($coach_id, '_coach_rolle', true),
		'standort' => get_post_meta($coach_id, '_coach_standort', true),
		'parkour_seit' => get_post_meta($coach_id, '_coach_parkour_seit', true),
		'po_seit' => get_post_meta($coach_id, '_coach_po_seit', true),
		'kurzvorstellung' => get_post_meta($coach_id, '_coach_kurzvorstellung', true),
		'philosophie' => get_post_meta($coach_id, '_coach_philosophie', true),
		'philosophie_bild' => $philosophie_bild,
		'moment' => get_post_meta($coach_id, '_coach_moment', true),
		'moment_bild' => $moment_bild,
		'video_url' => get_post_meta($coach_id, '_coach_video_url', true),
		'ausserhalb' => get_post_meta($coach_id, '_coach_ausserhalb', true),
		'leitsatz' => get_post_meta($coach_id, '_coach_leitsatz', true),
		'hero_bild' => $hero_bild,
		'locations' => $locations
	];
	
	$has_profile = !empty($coach_data['rolle']) || !empty($coach_data['standort']) || 
		!empty($coach_data['kurzvorstellung']) || !empty($coach_data['philosophie']) || 
		!empty($coach_data['moment']) || !empty($coach_data['video_url']) || 
		!empty($coach_data['ausserhalb']) || !empty($coach_data['leitsatz']);
	
	$coach_data['has_profile'] = $has_profile;
	$members[] = $coach_data;
}

usort($members, function($a, $b) {
	$a_has_image = !empty($a['card_image']) ? 0 : 1;
	$b_has_image = !empty($b['card_image']) ? 0 : 1;
	if ($a_has_image !== $b_has_image) return $a_has_image - $b_has_image;
	return strcasecmp($a['name'], $b['name']);
});

$unique_id = 'team-grid-' . uniqid();

if (!function_exists('parkourone_get_initials')) {
	function parkourone_get_initials($name) {
		$parts = explode(' ', trim($name));
		$initials = '';
		foreach ($parts as $part) {
			if (!empty($part)) {
				$initials .= mb_strtoupper(mb_substr($part, 0, 1));
			}
		}
		return mb_substr($initials, 0, 2);
	}
}

if (!function_exists('parkourone_get_coach_trainings_with_dates')) {
	function parkourone_get_coach_trainings_with_dates($coach_name) {
		$events = get_posts([
			'post_type' => 'event',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		]);
		
		$trainings = [];
		foreach ($events as $event) {
			$headcoach = get_post_meta($event->ID, '_event_headcoach', true);
			if (strcasecmp(trim($headcoach), trim($coach_name)) === 0) {
				$event_dates = get_post_meta($event->ID, '_event_dates', true);
				$first_date = is_array($event_dates) && !empty($event_dates) ? $event_dates[0] : null;
				
				$weekday_name = '';
				if ($first_date && !empty($first_date['date'])) {
					$timestamp = strtotime(str_replace('-', '.', $first_date['date']));
					if ($timestamp) {
						$days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
						$weekday_name = $days[date('w', $timestamp)];
					}
				}
				
				$available_dates = function_exists('parkourone_get_available_dates_for_event') 
					? parkourone_get_available_dates_for_event($event->ID) 
					: [];
				
				$trainings[] = [
					'id' => $event->ID,
					'title' => $event->post_title,
					'weekday' => $weekday_name,
					'start_time' => get_post_meta($event->ID, '_event_start_time', true),
					'end_time' => get_post_meta($event->ID, '_event_end_time', true),
					'venue' => get_post_meta($event->ID, '_event_venue', true),
					'available_dates' => $available_dates
				];
			}
		}
		return $trainings;
	}
}
?>
<section class="po-tg" id="<?php echo esc_attr($unique_id); ?>">
	<?php if ($headline): ?>
	<h2 class="po-tg__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>
	<?php if ($intro): ?>
		<p class="po-tg__intro"><?php echo wp_kses_post($intro); ?></p>
	<?php endif; ?>
	
	<?php if (!empty($members)): ?>
	<div class="po-tg__grid">
		<?php foreach ($members as $m): ?>
			<?php 
			$location_data = !empty($m['locations']) ? implode(' ', $m['locations']) : '';
			?>
			<?php if ($m['has_profile']): ?>
			<button type="button" class="po-tg__card" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $m['id']); ?>" data-locations="<?php echo esc_attr($location_data); ?>">
			<?php else: ?>
			<div class="po-tg__card po-tg__card--static" data-locations="<?php echo esc_attr($location_data); ?>">
			<?php endif; ?>
				<?php if (!empty($m['card_image'])): ?>
				<div class="po-tg__card-image" style="background-image: url('<?php echo esc_url($m['card_image']); ?>')"></div>
				<?php else: ?>
				<div class="po-tg__card-image po-tg__card-image--empty"></div>
				<?php endif; ?>
				<div class="po-tg__card-overlay"></div>
				<div class="po-tg__card-content">
					<strong class="po-tg__card-name"><?php echo esc_html($m['name']); ?></strong>
					<?php if (!empty($m['rolle'])): ?>
					<span class="po-tg__card-role"><?php echo esc_html($m['rolle']); ?></span>
					<?php endif; ?>
				</div>
				<?php if ($m['has_profile']): ?>
				<span class="po-tg__card-action" aria-label="Mehr erfahren">
					<svg viewBox="0 0 24 24" fill="none">
						<circle cx="12" cy="12" r="12" fill="currentColor"/>
						<path d="M12 7v10M7 12h10" stroke="#000" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</span>
				<?php endif; ?>
			<?php if ($m['has_profile']): ?>
			</button>
			<?php else: ?>
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	
	<?php if (!empty($location_terms)): ?>
	<div class="po-tg__filter-fab">
		<button type="button" class="po-tg__filter-trigger">
			<span class="po-tg__filter-text">Standort filtern</span>
			<svg class="po-tg__filter-icon" viewBox="0 0 24 24" fill="none">
				<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<div class="po-tg__filter-dropdown">
			<button type="button" class="po-tg__filter-option is-active" data-filter="all">Alle Standorte</button>
			<?php foreach ($location_terms as $term): ?>
			<button type="button" class="po-tg__filter-option" data-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
</section>

<?php foreach ($members as $m): ?>
<?php if ($m['has_profile']): ?>
<?php
$trainings = parkourone_get_coach_trainings_with_dates($m['name']);
?>
<div class="po-tg-modal" id="<?php echo esc_attr($unique_id . '-modal-' . $m['id']); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-tg-modal__backdrop"></div>
	<div class="po-tg-modal__panel">
		<button class="po-tg-modal__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		
		<div class="po-tg-coach">
			<div class="po-tg-coach__header">
				<h2 class="po-tg-coach__name"><?php echo esc_html($m['name']); ?></h2>
				<?php if (!empty($m['rolle']) || !empty($m['standort'])): ?>
					<p class="po-tg-coach__meta">
						<?php 
						$meta_parts = [];
						if (!empty($m['rolle'])) $meta_parts[] = $m['rolle'];
						if (!empty($m['standort'])) $meta_parts[] = $m['standort'];
						echo esc_html(implode(' · ', $meta_parts));
						?>
					</p>
				<?php endif; ?>
			</div>
			
			<?php if (!empty($m['hero_bild'])): ?>
			<div class="po-tg-coach__hero-image">
				<img src="<?php echo esc_url($m['hero_bild']); ?>" alt="<?php echo esc_attr($m['name']); ?>">
			</div>
			<?php endif; ?>
			
			<?php if (!empty($m['parkour_seit']) || !empty($m['po_seit']) || !empty($m['leitsatz'])): ?>
			<dl class="po-tg-coach__facts">
				<?php if (!empty($m['parkour_seit'])): ?>
				<div class="po-tg-coach__fact">
					<dt>Parkour seit</dt>
					<dd><?php echo esc_html($m['parkour_seit']); ?></dd>
				</div>
				<?php endif; ?>
				<?php if (!empty($m['po_seit'])): ?>
				<div class="po-tg-coach__fact">
					<dt>Bei ParkourONE seit</dt>
					<dd><?php echo esc_html($m['po_seit']); ?></dd>
				</div>
				<?php endif; ?>
				<?php if (!empty($m['leitsatz'])): ?>
				<div class="po-tg-coach__fact po-tg-coach__fact--full">
					<dt>Ein Satz, der mir Kraft gibt</dt>
					<dd>&laquo;<?php echo esc_html($m['leitsatz']); ?>&raquo;</dd>
				</div>
				<?php endif; ?>
			</dl>
			<?php endif; ?>
			
			<?php if (!empty($m['kurzvorstellung'])): ?>
			<div class="po-tg-coach__card">
				<div class="po-tg-coach__content">
					<p><strong>Meine Geschichte.</strong> <?php echo esc_html($m['kurzvorstellung']); ?></p>
					<?php if (!empty($m['philosophie_bild'])): ?>
					<img src="<?php echo esc_url($m['philosophie_bild']); ?>" alt="" class="po-tg-coach__image">
					<?php endif; ?>
					<?php if (!empty($m['video_url'])): ?>
					<div class="po-tg-coach__video">
						<?php echo wp_oembed_get($m['video_url']); ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<?php if (!empty($m['moment'])): ?>
			<div class="po-tg-coach__card">
				<div class="po-tg-coach__content">
					<p><strong>Ein Parkour Moment, der mich geprägt hat.</strong> <?php echo esc_html($m['moment']); ?></p>
					<?php if (!empty($m['moment_bild'])): ?>
					<img src="<?php echo esc_url($m['moment_bild']); ?>" alt="" class="po-tg-coach__image">
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<?php if (!empty($trainings)): ?>
			<div class="po-tg-coach__trainings">
				<h3 class="po-tg-coach__trainings-title">Diese Klassen leite ich</h3>
				<div class="po-tg-coach__trainings-list">
					<?php foreach ($trainings as $tindex => $training): ?>
					<div class="po-tg-coach__training-item">
						<div class="po-tg-coach__training-info">
							<span class="po-tg-coach__training-name"><?php echo esc_html($training['title']); ?></span>
							<span class="po-tg-coach__training-time">
								<?php if ($training['weekday']): ?>
									<?php echo esc_html($training['weekday']); ?>, 
								<?php endif; ?>
								<?php echo esc_html($training['start_time']); ?> – <?php echo esc_html($training['end_time']); ?>
								<?php if (!empty($training['venue'])): ?>
									· <?php echo esc_html($training['venue']); ?>
								<?php endif; ?>
							</span>
						</div>
						<button type="button" class="po-tg-coach__training-cta po-tg-coach__booking-trigger" 
							data-training-index="<?php echo esc_attr($tindex); ?>"
							data-event-id="<?php echo esc_attr($training['id']); ?>">
							Buchen
						</button>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			
			<?php foreach ($trainings as $tindex => $training): ?>
			<div class="po-tg-booking" data-training-index="<?php echo esc_attr($tindex); ?>" style="display: none;">
				<div class="po-tg-booking__steps" data-step="0">
					<div class="po-tg-booking__track">
						
						<div class="po-tg-booking__slide is-active" data-slide="0">
							<header class="po-tg-booking__header">
								<span class="po-tg-booking__eyebrow">Probetraining</span>
								<h2 class="po-tg-booking__heading"><?php echo esc_html($training['title']); ?></h2>
								<p class="po-tg-booking__subheading">
									<?php if ($training['weekday']): ?>
										<?php echo esc_html($training['weekday']); ?>, 
									<?php endif; ?>
									<?php echo esc_html($training['start_time']); ?> – <?php echo esc_html($training['end_time']); ?>
								</p>
							</header>
							
							<button type="button" class="po-tg-booking__cta po-tg-booking__next">
								Probetraining buchen
							</button>
							
							<button type="button" class="po-tg-booking__back po-tg-coach__booking-back">← Zurück zum Profil</button>
						</div>
						
						<div class="po-tg-booking__slide is-next" data-slide="1">
							<header class="po-tg-booking__header">
								<span class="po-tg-booking__eyebrow">Schritt 1 von 2</span>
								<h2 class="po-tg-booking__heading">Termin wählen</h2>
								<p class="po-tg-booking__subheading"><?php echo esc_html($training['title']); ?></p>
							</header>
							
							<?php if (!empty($training['available_dates'])): ?>
							<div class="po-tg-booking__dates">
								<?php foreach ($training['available_dates'] as $date): ?>
								<button type="button" class="po-tg-booking__date po-tg-booking__next" data-product-id="<?php echo esc_attr($date['product_id']); ?>" data-date-text="<?php echo esc_attr($date['date_formatted']); ?>">
									<span class="po-tg-booking__date-text"><?php echo esc_html($date['date_formatted']); ?></span>
									<span class="po-tg-booking__date-stock"><?php echo esc_html($date['stock']); ?> <?php echo $date['stock'] === 1 ? 'Platz' : 'Plätze'; ?> frei</span>
								</button>
								<?php endforeach; ?>
							</div>
							<?php else: ?>
							<div class="po-tg-booking__empty">
								<p>Aktuell sind keine Probetraining-Termine verfügbar.</p>
								<p>Kontaktiere uns für weitere Informationen.</p>
							</div>
							<?php endif; ?>
							
							<button type="button" class="po-tg-booking__back">← Zurück</button>
						</div>
						
						<div class="po-tg-booking__slide is-next" data-slide="2">
							<header class="po-tg-booking__header">
								<span class="po-tg-booking__eyebrow">Schritt 2 von 2</span>
								<h2 class="po-tg-booking__heading">Wer nimmt teil?</h2>
								<p class="po-tg-booking__subheading po-tg-booking__selected-date"></p>
							</header>
							
							<form class="po-tg-booking__form">
								<input type="hidden" name="product_id" value="">
								<input type="hidden" name="event_id" value="<?php echo esc_attr($training['id']); ?>">
								
								<div class="po-tg-booking__field">
									<label for="vorname-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>">Vorname</label>
									<input type="text" id="vorname-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>" name="vorname" required autocomplete="given-name">
								</div>
								
								<div class="po-tg-booking__field">
									<label for="name-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>">Nachname</label>
									<input type="text" id="name-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>" name="name" required autocomplete="family-name">
								</div>
								
								<div class="po-tg-booking__field">
									<label for="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>">Geburtsdatum</label>
									<input type="date" id="geburtsdatum-<?php echo esc_attr($unique_id . '-' . $m['id'] . '-' . $tindex); ?>" name="geburtsdatum" required>
								</div>
								
								<button type="submit" class="po-tg-booking__cta po-tg-booking__submit">Zum Warenkorb hinzufügen</button>
							</form>
							
							<button type="button" class="po-tg-booking__back">← Anderer Termin</button>
						</div>
						
						<div class="po-tg-booking__slide is-next" data-slide="3">
							<div class="po-tg-booking__success">
								<div class="po-tg-booking__success-icon">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M20 6L9 17l-5-5"/>
									</svg>
								</div>
								<h2 class="po-tg-booking__heading">Hinzugefügt!</h2>
								<p class="po-tg-booking__subheading"><?php echo esc_html($training['title']); ?></p>
								<p class="po-tg-booking__selected-date-confirm"></p>
							</div>
						</div>
						
					</div>
				</div>
			</div>
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
	if (!section) return;
	
	var fab = section.querySelector('.po-tg__filter-fab');
	var trigger = section.querySelector('.po-tg__filter-trigger');
	var filterText = section.querySelector('.po-tg__filter-text');
	var options = section.querySelectorAll('.po-tg__filter-option');
	var cards = section.querySelectorAll('.po-tg__card');
	
	if (fab && trigger) {
		var observer = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					fab.classList.add('is-visible');
				} else {
					fab.classList.remove('is-visible');
					fab.classList.remove('is-open');
				}
			});
		}, { threshold: 0.15, rootMargin: '-10% 0px -10% 0px' });
		
		observer.observe(section);
		
		trigger.addEventListener('click', function(e) {
			e.stopPropagation();
			fab.classList.toggle('is-open');
		});
		
		document.addEventListener('click', function(e) {
			if (!fab.contains(e.target)) {
				fab.classList.remove('is-open');
			}
		});
		
		options.forEach(function(option) {
			option.addEventListener('click', function() {
				options.forEach(function(o) { o.classList.remove('is-active'); });
				this.classList.add('is-active');
				
				var filterValue = this.getAttribute('data-filter');
				var filterName = this.textContent;
				
				if (filterValue === 'all') {
					filterText.textContent = 'Standort filtern';
				} else {
					filterText.textContent = filterName;
				}
				
				cards.forEach(function(card) {
					var locations = card.getAttribute('data-locations') || '';
					if (filterValue === 'all' || locations.indexOf(filterValue) !== -1) {
						card.style.display = '';
					} else {
						card.style.display = 'none';
					}
				});
				
				fab.classList.remove('is-open');
			});
		});
	}
	
	var buttons = section.querySelectorAll('[data-modal-target]');
	
	buttons.forEach(function(btn) {
		var modalId = btn.getAttribute('data-modal-target');
		var modal = document.getElementById(modalId);
		if (!modal) return;
		
		var closeBtn = modal.querySelector('.po-tg-modal__close');
		var backdrop = modal.querySelector('.po-tg-modal__backdrop');
		
		function openModal() {
			modal.classList.add('is-active');
			document.body.classList.add('po-tg-no-scroll');
		}
		
		function closeModal() {
			modal.classList.remove('is-active');
			document.body.classList.remove('po-tg-no-scroll');
			var panel = modal.querySelector('.po-tg-modal__panel');
			if (panel) panel.classList.remove('is-booking-active');
			modal.querySelectorAll('.po-tg-booking').forEach(function(p) {
				p.style.display = 'none';
				var steps = p.querySelector('.po-tg-booking__steps');
				if (steps) {
					steps.setAttribute('data-step', '0');
					steps.querySelectorAll('.po-tg-booking__slide').forEach(function(slide, i) {
						slide.classList.remove('is-active', 'is-prev', 'is-next');
						slide.classList.add(i === 0 ? 'is-active' : 'is-next');
					});
				}
			});
			modal.querySelectorAll('.po-tg-coach__header, .po-tg-coach__hero-image, .po-tg-coach__facts, .po-tg-coach__card, .po-tg-coach__trainings').forEach(function(el) {
				el.style.display = '';
			});
		}
		
		btn.addEventListener('click', openModal);
		if (closeBtn) closeBtn.addEventListener('click', closeModal);
		if (backdrop) backdrop.addEventListener('click', closeModal);
		
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && modal.classList.contains('is-active')) {
				closeModal();
			}
		});
		
		modal.querySelectorAll('.po-tg-coach__booking-trigger').forEach(function(trigger) {
			trigger.addEventListener('click', function() {
				var index = this.getAttribute('data-training-index');
				var panel = modal.querySelector('.po-tg-modal__panel');
				if (panel) panel.classList.add('is-booking-active');
				
				modal.querySelectorAll('.po-tg-coach__header, .po-tg-coach__hero-image, .po-tg-coach__facts, .po-tg-coach__card, .po-tg-coach__trainings').forEach(function(el) {
					el.style.display = 'none';
				});
				
				modal.querySelectorAll('.po-tg-booking').forEach(function(p) {
					p.style.display = p.getAttribute('data-training-index') === index ? 'block' : 'none';
				});
			});
		});
		
		modal.querySelectorAll('.po-tg-coach__booking-back').forEach(function(backBtn) {
			backBtn.addEventListener('click', function() {
				var panel = modal.querySelector('.po-tg-modal__panel');
				if (panel) panel.classList.remove('is-booking-active');
				
				modal.querySelectorAll('.po-tg-booking').forEach(function(p) {
					p.style.display = 'none';
					var steps = p.querySelector('.po-tg-booking__steps');
					if (steps) {
						steps.setAttribute('data-step', '0');
						steps.querySelectorAll('.po-tg-booking__slide').forEach(function(slide, i) {
							slide.classList.remove('is-active', 'is-prev', 'is-next');
							slide.classList.add(i === 0 ? 'is-active' : 'is-next');
						});
					}
				});
				modal.querySelectorAll('.po-tg-coach__header, .po-tg-coach__hero-image, .po-tg-coach__facts, .po-tg-coach__card, .po-tg-coach__trainings').forEach(function(el) {
					el.style.display = '';
				});
			});
		});
		
		modal.querySelectorAll('.po-tg-booking__next').forEach(function(nextBtn) {
			nextBtn.addEventListener('click', function() {
				var stepsEl = this.closest('.po-tg-booking__steps');
				if (!stepsEl) return;
				
				var currentStep = parseInt(stepsEl.getAttribute('data-step')) || 0;
				
				if (this.classList.contains('po-tg-booking__date')) {
					var prodInput = stepsEl.querySelector('[name="product_id"]');
					var dateEl = stepsEl.querySelector('.po-tg-booking__selected-date');
					var confirmEl = stepsEl.querySelector('.po-tg-booking__selected-date-confirm');
					if (prodInput) prodInput.value = this.getAttribute('data-product-id');
					if (dateEl) dateEl.textContent = this.getAttribute('data-date-text');
					if (confirmEl) confirmEl.textContent = this.getAttribute('data-date-text');
				}
				
				var slides = stepsEl.querySelectorAll('.po-tg-booking__slide');
				slides.forEach(function(slide, i) {
					slide.classList.remove('is-active', 'is-prev', 'is-next');
					if (i < currentStep + 1) slide.classList.add('is-prev');
					else if (i === currentStep + 1) slide.classList.add('is-active');
					else slide.classList.add('is-next');
				});
				stepsEl.setAttribute('data-step', currentStep + 1);
			});
		});
		
		modal.querySelectorAll('.po-tg-booking__back:not(.po-tg-coach__booking-back)').forEach(function(backBtn) {
			backBtn.addEventListener('click', function() {
				var stepsEl = this.closest('.po-tg-booking__steps');
				if (!stepsEl) return;
				
				var currentStep = parseInt(stepsEl.getAttribute('data-step')) || 0;
				if (currentStep === 0) return;
				
				var slides = stepsEl.querySelectorAll('.po-tg-booking__slide');
				slides.forEach(function(slide, i) {
					slide.classList.remove('is-active', 'is-prev', 'is-next');
					if (i < currentStep - 1) slide.classList.add('is-prev');
					else if (i === currentStep - 1) slide.classList.add('is-active');
					else slide.classList.add('is-next');
				});
				stepsEl.setAttribute('data-step', currentStep - 1);
			});
		});
		
	});
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.po-tg-booking__form').forEach(function(form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			var submitBtn = this.querySelector('.po-tg-booking__submit');
			var stepsEl = this.closest('.po-tg-booking__steps');
			var modal = this.closest('.po-tg-modal');
			var formEl = this;
			
			if (!submitBtn || !stepsEl) return;
			
			submitBtn.disabled = true;
			submitBtn.textContent = 'Wird hinzugefuegt...';
			
			if (typeof jQuery === 'undefined' || typeof poBooking === 'undefined') {
				alert('Buchung nicht möglich. Bitte Seite neu laden.');
				submitBtn.disabled = false;
				submitBtn.textContent = 'Zum Warenkorb hinzufügen';
				return;
			}
			
			var data = {
				action: 'po_add_to_cart',
				nonce: poBooking.nonce,
				product_id: formEl.querySelector('[name="product_id"]').value,
				event_id: formEl.querySelector('[name="event_id"]').value,
				vorname: formEl.querySelector('[name="vorname"]').value,
				name: formEl.querySelector('[name="name"]').value,
				geburtsdatum: formEl.querySelector('[name="geburtsdatum"]').value
			};
			
			jQuery.post(poBooking.ajaxUrl, data, function(response) {
				if (response.success) {
					var slides = stepsEl.querySelectorAll('.po-tg-booking__slide');
					slides.forEach(function(slide, i) {
						slide.classList.remove('is-active', 'is-prev', 'is-next');
						if (i < 3) slide.classList.add('is-prev');
						else if (i === 3) slide.classList.add('is-active');
						else slide.classList.add('is-next');
					});
					stepsEl.setAttribute('data-step', '3');
					formEl.reset();
					
					setTimeout(function() {
						if (modal) {
							modal.classList.remove('is-active');
							document.body.classList.remove('po-tg-no-scroll');
							var panel = modal.querySelector('.po-tg-modal__panel');
							if (panel) panel.classList.remove('is-booking-active');
							modal.querySelectorAll('.po-tg-booking').forEach(function(p) { p.style.display = 'none'; });
							modal.querySelectorAll('.po-tg-coach__header, .po-tg-coach__hero-image, .po-tg-coach__facts, .po-tg-coach__card, .po-tg-coach__trainings').forEach(function(el) { el.style.display = ''; });
							stepsEl.querySelectorAll('.po-tg-booking__slide').forEach(function(slide, i) {
								slide.classList.remove('is-active', 'is-prev', 'is-next');
								slide.classList.add(i === 0 ? 'is-active' : 'is-next');
							});
							stepsEl.setAttribute('data-step', '0');
						}
						jQuery(document.body).trigger('wc_fragment_refresh');
						jQuery(document.body).trigger('added_to_cart');
					}, 1500);
				} else {
					alert('Fehler beim Hinzufügen');
					submitBtn.disabled = false;
					submitBtn.textContent = 'Zum Warenkorb hinzufügen';
				}
			}).fail(function() {
				alert('Fehler');
				submitBtn.disabled = false;
				submitBtn.textContent = 'Zum Warenkorb hinzufügen';
			});
		});
	});
});
</script>
