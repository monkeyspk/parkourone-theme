<?php
// Standort automatisch erkennen für SEO-optimierte Headlines
$site_location = function_exists('parkourone_get_site_location') ? parkourone_get_site_location() : null;
$is_city_site = $site_location && !empty($site_location['detected']) && !in_array($site_location['slug'], ['parkourone', 'www', 'new', 'staging', 'dev', 'test', 'localhost']);

// Locations mit Artikel
$locations_with_article = ['schweiz', 'türkei', 'ukraine', 'slowakei', 'mongolei'];
$needs_article = $site_location && in_array($site_location['slug'], $locations_with_article);
$location_name = $site_location['name'] ?? '';
$in_location = $needs_article ? "in der {$location_name}" : "in {$location_name}";

$eyebrow = $attributes['eyebrow'] ?? '';
$headline = $attributes['headline'] ?? 'Stärke deinen Körper, schärfe deinen Geist';
$subtext = $attributes['subtext'] ?? 'Entdecke Parkour – für alle Altersgruppen, an mehreren Standorten.';
$layout = $attributes['layout'] ?? 'centered';

// Automatische Headline für Stadt-Seiten (überschreibt Default, nicht Custom)
if ($is_city_site && $headline === 'Stärke deinen Körper, schärfe deinen Geist') {
	$headline = "Parkour {$in_location}";
	$subtext = "Stärke deinen <span class=\"po-hero__highlight\">Körper</span>, schärfe deinen <span class=\"po-hero__highlight\">Geist</span>";
	$eyebrow = $eyebrow ?: "ParkourONE {$location_name}";
}
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
$useRandomFallback = $attributes['useRandomFallback'] ?? false;

// Theme-Fallback-Bilder
$desktopFallback = get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg';
$mobileFallback = get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg';

// Zufälliges Fallback-Bild aus Altersgruppen-Ordner
if ($useRandomFallback && empty($imageUrl)) {
	$folder = 'adults';
	if (!empty($ageCategory)) {
		$age_lower = strtolower($ageCategory);
		if (in_array($age_lower, ['kids', 'minis'])) {
			$folder = 'kids';
		} else {
			$folder = 'adults';
		}
	}
	$fallback_dir = get_template_directory() . '/assets/images/fallback/' . $folder;
	$fallback_url = get_template_directory_uri() . '/assets/images/fallback/' . $folder;
	if (is_dir($fallback_dir)) {
		$images = glob($fallback_dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
		if (!empty($images)) {
			$random_image = $images[array_rand($images)];
			$desktopFallback = $fallback_url . '/' . basename($random_image);
		}
	}
}

$desktopImage = !empty($imageUrl) ? $imageUrl : $desktopFallback;
$mobileImage = $mobileFallback;
$unique_id = 'hero-' . uniqid();

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

<style>
#<?php echo esc_attr($unique_id); ?> {
	background-image: url('<?php echo esc_url($mobileImage); ?>');
}
@media (min-width: 768px) {
	#<?php echo esc_attr($unique_id); ?> {
		background-image: url('<?php echo esc_url($desktopImage); ?>');
	}
}
#<?php echo esc_attr($unique_id); ?> .po-hero__overlay {
	background: rgba(0, 0, 0, <?php echo esc_attr($overlayOpacity / 100); ?>);
}
</style>

<section class="<?php echo esc_attr(implode(' ', $classes)); ?>" id="<?php echo esc_attr($unique_id); ?>">
	<?php if (!empty($videoBackgroundUrl)): ?>
	<video class="po-hero__video-bg" autoplay muted loop playsinline>
		<source src="<?php echo esc_url($videoBackgroundUrl); ?>" type="video/mp4">
	</video>
	<?php endif; ?>

	<div class="po-hero__overlay"></div>

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

<script>
(function() {
	var heroId = '<?php echo esc_js($unique_id); ?>';
	var hero = document.getElementById(heroId);
	if (!hero) return;

	// Smooth scroll für Anchor-Links
	hero.querySelectorAll('.po-hero__button[href^="#"]').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			var targetId = this.getAttribute('href').substring(1);
			var target = document.getElementById(targetId);
			if (target) {
				e.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});
	});

	<?php if ($youtube_id): ?>
	// Video Modal
	var modal = document.getElementById(heroId + '-modal');
	var videoBtn = hero.querySelector('.po-hero__button--video');
	var iframe = modal ? modal.querySelector('.po-video-modal__iframe') : null;
	var closeBtn = modal ? modal.querySelector('.po-video-modal__close') : null;
	var backdrop = modal ? modal.querySelector('.po-video-modal__backdrop') : null;

	function openModal() {
		if (!modal || !iframe) return;
		iframe.src = iframe.dataset.src;
		modal.classList.add('is-active');
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		if (!modal || !iframe) return;
		modal.classList.remove('is-active');
		modal.setAttribute('aria-hidden', 'true');
		iframe.src = '';
		document.body.style.overflow = '';
	}

	if (videoBtn) {
		videoBtn.addEventListener('click', openModal);
	}
	if (closeBtn) {
		closeBtn.addEventListener('click', closeModal);
	}
	if (backdrop) {
		backdrop.addEventListener('click', closeModal);
	}
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal && modal.classList.contains('is-active')) {
			closeModal();
		}
	});
	<?php endif; ?>
})();
</script>
