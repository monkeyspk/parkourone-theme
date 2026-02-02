<?php
$headline = $attributes['headline'] ?? 'Stärke deinen Körper, schärfe deinen Geist – erlebe Parkour';
$subtext = $attributes['subtext'] ?? 'Bei ParkourONE lernst du, Hindernisse zu überwinden und Bewegung neu zu erleben.';
$buttonText = $attributes['buttonText'] ?? 'Jetzt beginnen';
$buttonUrl = $attributes['buttonUrl'] ?? '#';
$imageUrl = $attributes['imageUrl'] ?? '';
$ageCategory = $attributes['ageCategory'] ?? '';
$useRandomFallback = $attributes['useRandomFallback'] ?? false;

// Theme-Fallback-Bilder (Standard für Startseite)
$desktopFallback = get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg';
$mobileFallback = get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg';

// Zufälliges Fallback-Bild aus Altersgruppen-Ordner holen
if ($useRandomFallback && empty($imageUrl)) {
	$folder = 'adults'; // Default

	// Mapping Altersgruppe zu Ordner (nur kids oder adults)
	if (!empty($ageCategory)) {
		$age_lower = strtolower($ageCategory);
		if (in_array($age_lower, ['kids', 'minis'])) {
			$folder = 'kids';
		} else {
			$folder = 'adults'; // juniors, adults, etc. -> adults
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

// Custom oder Fallback
$desktopImage = !empty($imageUrl) ? $imageUrl : $desktopFallback;
$mobileImage = $mobileFallback;

$unique_id = 'hero-' . uniqid();
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
</style>

<section class="po-hero" id="<?php echo esc_attr($unique_id); ?>">
	<div class="po-hero__overlay"></div>
	<div class="po-hero__content">
		<?php if ($headline): ?>
			<h1 class="po-hero__headline"><?php echo wp_kses_post($headline); ?></h1>
		<?php endif; ?>
		<?php if ($subtext): ?>
			<p class="po-hero__subtext"><?php echo wp_kses_post($subtext); ?></p>
		<?php endif; ?>
		<?php if ($buttonText && $buttonUrl): ?>
			<a href="<?php echo esc_url($buttonUrl); ?>" class="po-hero__button"><?php echo esc_html($buttonText); ?></a>
		<?php endif; ?>
	</div>
</section>
