<?php
// Standort-Text über zentrale Funktion (inkl. Admin-Konfiguration)
$site_location = function_exists('parkourone_get_site_location') ? parkourone_get_site_location() : ['name' => 'Berlin', 'location_text' => 'in Berlin'];
$location_text = $site_location['location_text'];

// Automatischer Eyebrow basierend auf Standort
$default_eyebrow = "Parkour {$location_text}";

$eyebrow = $attributes['eyebrow'] ?? $default_eyebrow;
// Wenn eyebrow leer oder der alte Default, dann automatisch setzen
if (empty($eyebrow) || $eyebrow === 'Parkour für alle') {
	$eyebrow = $default_eyebrow;
}

// Headline IMMER mit Glow auf Körper und Geist
$headline = $attributes['headline'] ?? 'Stärke deinen Körper, schärfe deinen Geist';
// Glow hinzufügen wenn Default-Headline
if ($headline === 'Stärke deinen Körper, schärfe deinen Geist') {
	$headline = 'Stärke deinen <span class="po-hero__highlight">Körper</span>, schärfe deinen <span class="po-hero__highlight">Geist</span>';
}

$subtext = $attributes['subtext'] ?? 'Entdecke Parkour – für alle Altersgruppen, an mehreren Standorten.';
$layout = $attributes['layout'] ?? 'centered';
$buttonText = $attributes['buttonText'] ?? 'Jetzt starten';
$buttonUrl = $attributes['buttonUrl'] ?? '#stundenplan';
$secondButtonText = $attributes['secondButtonText'] ?? '';
$secondButtonUrl = $attributes['secondButtonUrl'] ?? '';
$videoButtonText = $attributes['videoButtonText'] ?? 'Film ansehen';
$videoUrl = $attributes['videoUrl'] ?? '';
$imageUrl = $attributes['imageUrl'] ?? '';
$videoBackgroundUrl = $attributes['videoBackgroundUrl'] ?? '';
$overlayOpacity = $attributes['overlayOpacity'] ?? 50;
$stats = $attributes['stats'] ?? [];
$ageCategory = $attributes['ageCategory'] ?? '';
$accentColor = $attributes['accentColor'] ?? '#2997ff';

// Bild-Logik: Gewähltes Bild → zufälliges Fallback → statisches Fallback
if (!empty($imageUrl)) {
	// Gewähltes Bild: auf Desktop UND Mobile verwenden
	$desktopImage = $imageUrl;
	$mobileImage = $imageUrl;
} else {
	// Kein Bild gewählt → zufälliges Fallback aus verschiedenen Kategorien
	$fallback_categories = ['adults', 'kids', 'juniors'];
	if (!empty($ageCategory)) {
		// Gewählte Kategorie bevorzugen
		array_unshift($fallback_categories, $ageCategory);
		$fallback_categories = array_unique($fallback_categories);
	}
	$random_category = $fallback_categories[array_rand($fallback_categories)];

	$landscape_fallback = parkourone_get_fallback_image($random_category, 'landscape');
	$portrait_fallback = parkourone_get_fallback_image($random_category, 'portrait');

	$desktopImage = $landscape_fallback ?: (get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg');
	$mobileImage = $portrait_fallback ?: (get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg');
}
static $po_hero_instance = 0; $po_hero_instance++;
$anchor = $attributes['anchor'] ?? '';$unique_id = 'hero-' . $po_hero_instance;

// YouTube Video ID extrahieren
$youtube_id = '';
if (!empty($videoUrl)) {
	if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $matches)) {
		$youtube_id = $matches[1];
	}
}

// CSS-Klassen
$classes = ['po-hero', 'po-hero--' . $layout];
if (!empty($videoBackgroundUrl)) {
	$classes[] = 'po-hero--video-bg';
}
?>

<section class="<?php echo esc_attr(implode(' ', $classes)); ?>" id="<?php echo esc_attr($anchor ?: $unique_id); ?>" style="--po-hero-accent: <?php echo esc_attr($accentColor); ?>">
	<?php if (!empty($videoBackgroundUrl)): ?>
	<video class="po-hero__video-bg" autoplay muted loop playsinline>
		<source src="<?php echo esc_url($videoBackgroundUrl); ?>" type="video/mp4">
	</video>
	<?php endif; ?>

	<picture class="po-hero__bg-picture" style="display:block;position:absolute;inset:0;z-index:0;">
		<source media="(min-width: 768px)" srcset="<?php echo esc_url($desktopImage); ?>">
		<img src="<?php echo esc_url($mobileImage); ?>" alt="" class="po-hero__bg-img"
			 style="display:block;width:100%;height:100%;object-fit:cover;object-position:center;"
			 fetchpriority="high" loading="eager" decoding="async" width="1920" height="1080">
	</picture>

	<div class="po-hero__overlay" style="background: rgba(0, 0, 0, <?php echo esc_attr($overlayOpacity / 100); ?>)"></div>

	<div class="po-hero__content">
		<?php if ($eyebrow): ?>
			<span class="po-hero__eyebrow"><?php echo esc_html($eyebrow); ?></span>
		<?php endif; ?>

		<?php if ($headline): ?>
			<h1 class="po-hero__headline"><?php echo wp_kses_post($headline); ?></h1>
		<?php endif; ?>

		<?php if ($subtext): ?>
			<p class="po-hero__subtext"><?php echo wp_kses_post($subtext); ?></p>
		<?php endif; ?>

		<?php if ($buttonText || $youtube_id || $secondButtonText): ?>
		<div class="po-hero__buttons">
			<?php if ($buttonText && $buttonUrl): ?>
				<a href="<?php echo esc_url($buttonUrl); ?>" class="po-hero__button po-hero__button--primary">
					<?php echo esc_html($buttonText); ?>
				</a>
			<?php endif; ?>

			<?php if ($youtube_id): ?>
				<button type="button" class="po-hero__button po-hero__button--video" data-video-id="<?php echo esc_attr($youtube_id); ?>">
					<svg class="po-hero__play-icon" viewBox="0 0 24 24" fill="currentColor">
						<path d="M8 5v14l11-7z"/>
					</svg>
					<?php echo esc_html($videoButtonText); ?>
				</button>
			<?php elseif ($secondButtonText && $secondButtonUrl): ?>
				<a href="<?php echo esc_url($secondButtonUrl); ?>" class="po-hero__button po-hero__button--secondary">
					<?php echo esc_html($secondButtonText); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php if (!empty($stats)): ?>
	<div class="po-hero__stats">
		<?php foreach ($stats as $stat): ?>
		<div class="po-hero__stat">
			<span class="po-hero__stat-number">
				<?php echo esc_html($stat['number'] ?? ''); ?><?php if (!empty($stat['suffix'])): ?><span class="po-hero__stat-suffix"><?php echo esc_html($stat['suffix']); ?></span><?php endif; ?>
			</span>
			<span class="po-hero__stat-label"><?php echo esc_html($stat['label'] ?? ''); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="po-hero__scroll-hint">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M12 5v14M5 12l7 7 7-7"/>
		</svg>
	</div>
</section>

<?php if ($youtube_id): ?>
<!-- Video Modal -->
<div class="po-video-modal" id="<?php echo esc_attr($unique_id); ?>-modal" aria-hidden="true">
	<div class="po-video-modal__backdrop"></div>
	<div class="po-video-modal__container">
		<button class="po-video-modal__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M18 6L6 18M6 6l12 12"/>
			</svg>
		</button>
		<div class="po-video-modal__wrapper">
			<iframe class="po-video-modal__iframe"
				src=""
				data-src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&rel=0"
				frameborder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				allowfullscreen>
			</iframe>
		</div>
	</div>
</div>
<?php endif; ?>

