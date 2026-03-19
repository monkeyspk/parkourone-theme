<?php
$headline = $attributes['headline'] ?? 'So funktioniert\'s';
$subheadline = $attributes['subheadline'] ?? 'In 4 einfachen Schritten zum ersten Training';
$steps = $attributes['steps'] ?? [];
$background_color = $attributes['backgroundColor'] ?? 'light';
$age_category = $attributes['ageCategory'] ?? 'default';
$align = $attributes['align'] ?? 'full';

// Fallback auf globale Probetraining Steps aus dem Backend
if (empty($steps) && function_exists('parkourone_get_global_steps')) {
	$steps = parkourone_get_global_steps();
}

if (empty($steps)) return;

$anchor = $attributes['anchor'] ?? '';
$unique_id = 'steps-timeline-' . uniqid();
$theme_uri = get_template_directory_uri();

$align_class = '';
if ($align === 'wide') $align_class = 'alignwide';
if ($align === 'full') $align_class = 'alignfull';

// Count steps that need fallback images (no custom imageUrl)
$fallback_needed = 0;
foreach ($steps as $step) {
	if (empty($step['imageUrl'])) $fallback_needed++;
}

// Bilder-Mapping nach Kategorie - nur für Schritte ohne Custom Bild
$fallback_images = [];
if ($fallback_needed > 0) {
	if (!function_exists('parkourone_get_step_images')) {
		function parkourone_get_step_images($category, $count = 4) {
			$all_images = parkourone_get_theme_images($category, 'landscape');
			if (empty($all_images)) {
				$all_images = parkourone_get_theme_images('adults', 'landscape');
			}
			if (empty($all_images)) {
				return [];
			}
			shuffle($all_images);
			$selected = array_slice($all_images, 0, $count);
			return array_map(function($img) { return $img['url']; }, $selected);
		}
	}

	$fallback_images = parkourone_get_step_images($age_category, $fallback_needed);
	if (empty($fallback_images)) {
		$fallback_images = parkourone_get_step_images('adults', $fallback_needed);
	}
}
$fallback_index = 0;
?>

<section class="po-steps-timeline po-steps-timeline--<?php echo esc_attr($background_color); ?> <?php echo esc_attr($align_class); ?>" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">
	<div class="po-steps-timeline__container">
		<?php if ($headline || $subheadline): ?>
		<header class="po-steps-timeline__header">
			<?php if ($headline): ?>
			<h2 class="po-steps-timeline__headline"><?php echo wp_kses_post($headline); ?></h2>
			<?php endif; ?>
			<?php if ($subheadline): ?>
			<p class="po-steps-timeline__subheadline"><?php echo wp_kses_post($subheadline); ?></p>
			<?php endif; ?>
		</header>
		<?php endif; ?>

		<div class="po-steps-timeline__track">
			<?php foreach ($steps as $index => $step):
				// Use custom image if available, otherwise fallback
				if (!empty($step['imageUrl'])) {
					$image_url = $step['imageUrl'];
				} else {
					$image_url = $fallback_images[$fallback_index] ?? ($fallback_images[0] ?? '');
					$fallback_index++;
				}
			?>
			<div class="po-steps-timeline__step">
				<div class="po-steps-timeline__number-wrap">
					<span class="po-steps-timeline__number"><?php echo $index + 1; ?></span>
				</div>
				<?php if ($image_url): ?>
				<div class="po-steps-timeline__image-wrap">
					<img
						src="<?php echo esc_url($image_url); ?>"
						alt="<?php echo esc_attr($step['title']); ?>"
						class="po-steps-timeline__image"
						loading="lazy"
					/>
				</div>
				<?php endif; ?>
				<h3 class="po-steps-timeline__title"><?php echo esc_html($step['title']); ?></h3>
				<p class="po-steps-timeline__desc"><?php echo esc_html($step['description']); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
