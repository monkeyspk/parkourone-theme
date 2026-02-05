<?php
// Subdomain automatisch erkennen für Eyebrow
$host = parse_url(home_url(), PHP_URL_HOST);
$subdomain = explode('.', $host)[0];
$subdomain_display = ucfirst($subdomain); // "new" → "New", "berlin" → "Berlin"

// Locations mit Artikel (für korrekte Grammatik)
$locations_with_article = ['schweiz', 'türkei', 'ukraine', 'slowakei', 'mongolei'];
$needs_article = in_array(strtolower($subdomain), $locations_with_article);
$location_text = $needs_article ? "in der {$subdomain_display}" : "in {$subdomain_display}";

// Automatischer Eyebrow basierend auf Subdomain
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
$useRandomFallback = $attributes['useRandomFallback'] ?? false;

// Theme-Fallback-Bilder
$desktopFallback = get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg';
$mobileFallback = get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg';

// Zufälliges Fallback-Bild aus Altersgruppen-Ordner (Landscape für Hero)
if ($useRandomFallback && empty($imageUrl)) {
	$category = !empty($ageCategory) ? $ageCategory : 'adults';
	$fallback = parkourone_get_fallback_image($category, 'landscape');
	if ($fallback) {
		$desktopFallback = $fallback;
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
