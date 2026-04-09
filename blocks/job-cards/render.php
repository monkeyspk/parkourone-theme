<?php
/**
 * Job Cards Block
 * Zeigt Stellenangebote aus dem Jobs CPT an
 * Versteckt sich automatisch wenn keine Jobs vorhanden sind
 */

// Jobs aus CPT holen
$jobs = function_exists('parkourone_get_jobs') ? parkourone_get_jobs() : [];

// Wenn keine Jobs, Block nicht anzeigen
if (empty($jobs)) {
	return;
}

$headline = $attributes['headline'] ?? 'Werde Teil unseres Teams';
$intro = $attributes['intro'] ?? '';
$bgColor = $attributes['backgroundColor'] ?? '#f5f5f7';
$show_filter = !empty($attributes['showFilter']);
$anchor = $attributes['anchor'] ?? '';
static $po_jobs_instance = 0; $po_jobs_instance++;
$unique_id = $anchor ?: ('po-jobs-' . $po_jobs_instance);

// Filter-Achsen: Label + Taxonomie-Slug + Darstellung.
// Jede Achse wird nur angezeigt, wenn mindestens ein Term existiert.
$filter_axes = [];
if ($show_filter) {
	$axes_config = [
		['slug' => 'job_location', 'key' => 'location', 'label' => 'Wo?'],
		['slug' => 'job_bereich',  'key' => 'bereich',  'label' => 'Was?'],
		['slug' => 'job_stelle',   'key' => 'stelle',   'label' => 'Stelle'],
		['slug' => 'job_wochentag','key' => 'wochentag','label' => 'Wochentag'],
	];
	foreach ($axes_config as $axis) {
		$terms = get_terms([
			'taxonomy'   => $axis['slug'],
			'hide_empty' => true,
		]);
		if (!is_wp_error($terms) && !empty($terms)) {
			$axis['terms'] = $terms;
			$filter_axes[] = $axis;
		}
	}
}
?>
<section class="po-jobs alignfull" id="<?php echo esc_attr($unique_id); ?>" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<div class="po-jobs__header">
		<h2 class="po-jobs__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php if ($intro): ?>
			<p class="po-jobs__intro"><?php echo wp_kses_post($intro); ?></p>
		<?php endif; ?>
	</div>

	<?php if (!empty($filter_axes)): ?>
	<div class="po-jobs__filter" role="region" aria-label="Jobs filtern">
		<?php foreach ($filter_axes as $axis): ?>
			<div class="po-jobs__filter-group" data-filter-key="<?php echo esc_attr($axis['key']); ?>">
				<button type="button" class="po-jobs__filter-toggle" aria-expanded="false">
					<span class="po-jobs__filter-label"><?php echo esc_html($axis['label']); ?></span>
					<span class="po-jobs__filter-count" aria-hidden="true">Alle</span>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
				</button>
				<div class="po-jobs__filter-options" hidden>
					<?php foreach ($axis['terms'] as $term): ?>
						<label class="po-jobs__filter-option">
							<input type="checkbox" value="<?php echo esc_attr($term->slug); ?>">
							<span><?php echo esc_html($term->name); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<div class="po-jobs__filter-group po-jobs__filter-group--date" data-filter-key="abWann">
			<label class="po-jobs__filter-toggle po-jobs__filter-toggle--date">
				<span class="po-jobs__filter-label">Ab wann</span>
				<input type="date" class="po-jobs__filter-date" aria-label="Frühestes Startdatum">
			</label>
		</div>
		<button type="button" class="po-jobs__filter-reset" hidden>Filter zurücksetzen</button>
	</div>
	<?php endif; ?>

	<p class="po-jobs__empty-state" hidden>Keine offenen Stellen für die gewählten Filter.</p>

	<div class="po-jobs__grid">
		<?php foreach ($jobs as $index => $j):
			$data_location  = implode(',', $j['locations']  ?? []);
			$data_bereich   = implode(',', $j['bereiche']   ?? []);
			$data_stelle    = implode(',', $j['stellen']    ?? []);
			$data_wochentag = implode(',', $j['wochentage'] ?? []);
			$data_ab_wann   = $j['abWann'] ?? '';
		?>
			<article class="po-job-card"
				data-location="<?php echo esc_attr($data_location); ?>"
				data-bereich="<?php echo esc_attr($data_bereich); ?>"
				data-stelle="<?php echo esc_attr($data_stelle); ?>"
				data-wochentag="<?php echo esc_attr($data_wochentag); ?>"
				data-ab-wann="<?php echo esc_attr($data_ab_wann); ?>">
				<div class="po-job-card__content">
					<?php if (!empty($j['type'])): ?>
						<span class="po-job-card__type"><?php echo esc_html($j['type']); ?></span>
					<?php endif; ?>
					<h3 class="po-job-card__title"><?php echo esc_html($j['title']); ?></h3>
					<?php if (!empty($j['desc'])): ?>
						<p class="po-job-card__desc"><?php echo esc_html($j['desc']); ?></p>
					<?php endif; ?>
				</div>
				<button type="button" class="po-job-card__cta" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>">
					Mehr erfahren
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</button>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="po-jobs__nav">
		<button type="button" class="po-jobs__nav-btn po-jobs__nav-prev" aria-label="Zurück" disabled>
			<svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="po-jobs__nav-btn po-jobs__nav-next" aria-label="Weiter">
			<svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
</section>

<?php foreach ($jobs as $index => $j):
	$requirements = !empty($j['requirements']) ? array_filter(explode("\n", $j['requirements'])) : [];
	$benefits = !empty($j['benefits']) ? array_filter(explode("\n", $j['benefits'])) : [];
?>
<div class="po-overlay" id="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-overlay__backdrop"></div>
	<div class="po-overlay__panel po-overlay__panel--job">
		<button class="po-overlay__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<?php echo parkourone_share_button(
			add_query_arg('job', $j['id'] ?? $index, get_permalink()),
			$j['title'] . ' – ParkourONE Jobs',
			'',
			true
		); ?>

		<div class="po-job-modal">
			<header class="po-job-modal__header">
				<?php if (!empty($j['type'])): ?>
					<span class="po-job-modal__type"><?php echo esc_html($j['type']); ?></span>
				<?php endif; ?>
				<h2 class="po-job-modal__title"><?php echo esc_html($j['title']); ?></h2>
			</header>

			<?php if (!empty($j['fullDescription'])): ?>
			<div class="po-job-modal__section">
				<p class="po-job-modal__description"><?php echo wp_kses_post(nl2br($j['fullDescription'])); ?></p>
			</div>
			<?php endif; ?>

			<?php if (!empty($requirements)): ?>
			<div class="po-job-modal__section">
				<h3 class="po-job-modal__section-title">Was du mitbringst</h3>
				<ul class="po-job-modal__list">
					<?php foreach ($requirements as $req): ?>
						<li><?php echo esc_html(trim($req)); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if (!empty($benefits)): ?>
			<div class="po-job-modal__section">
				<h3 class="po-job-modal__section-title">Was wir bieten</h3>
				<ul class="po-job-modal__list po-job-modal__list--benefits">
					<?php foreach ($benefits as $benefit): ?>
						<li><?php echo esc_html(trim($benefit)); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if (!empty($j['howToApply']) || !empty($j['contactEmail'])): ?>
			<div class="po-job-modal__section po-job-modal__section--apply">
				<h3 class="po-job-modal__section-title">So bewirbst du dich</h3>
				<?php if (!empty($j['howToApply'])): ?>
					<p><?php echo wp_kses_post(nl2br($j['howToApply'])); ?></p>
				<?php endif; ?>
				<?php if (!empty($j['contactEmail'])): ?>
					<a href="mailto:<?php echo esc_attr($j['contactEmail']); ?>" class="po-job-modal__apply-btn">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						<?php echo esc_html($j['contactEmail']); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endforeach; ?>

