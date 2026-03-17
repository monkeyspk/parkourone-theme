<?php
$headline = $attributes['headline'] ?? 'Weitere Schulen';
$intro = $attributes['intro'] ?? '';
$schulen = $attributes['schulen'] ?? [];
$bgColor = $attributes['backgroundColor'] ?? '#ffffff';
$hide_current = $attributes['hideCurrentSchool'] ?? true;

// Aktuelle Schule anhand der Domain erkennen
$current_school_slug = '';
if ($hide_current) {
	$host = $_SERVER['HTTP_HOST'] ?? '';

	// Domain-Mapping: subdomain.parkourone.com -> slug
	$domain_mappings = [
		'schweiz' => 'schweiz',
		'berlin' => 'berlin',
		'augsburg' => 'augsburg',
		'dresden' => 'dresden',
		'rheinruhr' => 'rheinruhr',
		'rhein-ruhr' => 'rheinruhr',
		'muenster' => 'muenster',
		'münster' => 'muenster',
		'hannover' => 'hannover',
	];

	// Subdomain extrahieren (z.B. "berlin" aus "berlin.parkourone.com")
	$parts = explode('.', $host);
	if (count($parts) >= 2) {
		$subdomain = strtolower($parts[0]);
		$current_school_slug = $domain_mappings[$subdomain] ?? '';
	}

	// Fallback: Prüfe ob Domain einen bekannten Schulnamen enthält
	if (empty($current_school_slug)) {
		foreach ($domain_mappings as $domain => $slug) {
			if (stripos($host, $domain) !== false) {
				$current_school_slug = $slug;
				break;
			}
		}
	}
}

// Filtere die aktuelle Schule heraus
$filtered_schulen = array_filter($schulen, function($s) use ($current_school_slug, $hide_current) {
	if (!$hide_current || empty($current_school_slug)) {
		return true;
	}
	$slug = $s['slug'] ?? sanitize_title($s['name'] ?? '');
	return $slug !== $current_school_slug;
});

// Fallback-Bilder aus dem Adults-Ordner laden
$adults_fallback_images = parkourone_get_theme_fallback_images('adults');

// Schulen-spezifische Fallback-Zuweisung (basierend auf Index für Konsistenz)
$school_slugs = ['schweiz', 'berlin', 'augsburg', 'dresden', 'rheinruhr', 'muenster', 'hannover'];
$fallback_images = [];
foreach ($school_slugs as $index => $slug) {
	if (!empty($adults_fallback_images)) {
		$fallback_images[$slug] = $adults_fallback_images[$index % count($adults_fallback_images)];
	} else {
		$fallback_images[$slug] = '';
	}
}

if (empty($filtered_schulen)) {
	return;
}
?>
<section<?php if (!empty($attributes['anchor'])) echo ' id="' . esc_attr($attributes['anchor']) . '"'; ?> class="po-schulen alignfull" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<h2 class="po-schulen__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php if ($intro): ?>
		<p class="po-schulen__intro"><?php echo wp_kses_post($intro); ?></p>
	<?php endif; ?>

	<div class="po-schulen__grid">
		<?php foreach ($filtered_schulen as $s):
			$slug = $s['slug'] ?? sanitize_title($s['name'] ?? '');
			$image_url = $s['imageUrl'] ?? '';

			// Fallback-Bild verwenden, wenn kein Bild gesetzt
			if (empty($image_url) && isset($fallback_images[$slug])) {
				$image_url = $fallback_images[$slug];
			}
		?>
			<a href="<?php echo esc_url($s['url'] ?? '#'); ?>" class="po-schule-card" target="_blank" rel="noopener">
				<div class="po-schule-card__image" <?php if (!empty($image_url)): ?>style="background-image: url('<?php echo esc_url($image_url); ?>')"<?php endif; ?>>
					<?php if (empty($image_url)): ?>
						<span class="po-schule-card__placeholder"><?php echo esc_html(mb_substr($s['name'], 0, 1)); ?></span>
					<?php endif; ?>
				</div>
				<h3 class="po-schule-card__name"><?php echo esc_html($s['name']); ?></h3>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="po-schulen__nav">
		<button type="button" class="po-schulen__nav-btn po-schulen__nav-prev" aria-label="Zurück" disabled>
			<svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="po-schulen__nav-btn po-schulen__nav-next" aria-label="Weiter">
			<svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
</section>

<script>
(function(){
	var section = document.querySelector('.po-schulen');
	if (!section) return;

	var grid = section.querySelector('.po-schulen__grid');
	var prevBtn = section.querySelector('.po-schulen__nav-prev');
	var nextBtn = section.querySelector('.po-schulen__nav-next');
	if (!grid || !prevBtn || !nextBtn) return;

	var cardWidth = grid.querySelector('.po-schule-card');
	var scrollAmount = cardWidth ? cardWidth.offsetWidth + 24 : 320;

	function updateButtons() {
		prevBtn.disabled = grid.scrollLeft <= 5;
		nextBtn.disabled = grid.scrollLeft + grid.offsetWidth >= grid.scrollWidth - 5;
	}

	prevBtn.addEventListener('click', function() {
		grid.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
	});
	nextBtn.addEventListener('click', function() {
		grid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
	});

	grid.addEventListener('scroll', updateButtons, { passive: true });
	updateButtons();

	// Update on resize
	window.addEventListener('resize', function() {
		var card = grid.querySelector('.po-schule-card');
		scrollAmount = card ? card.offsetWidth + 24 : 320;
		updateButtons();
	});
})();
</script>
