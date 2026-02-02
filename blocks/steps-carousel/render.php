<?php
$headline = $attributes['headline'] ?? 'So funktioniert\'s';
$subheadline = $attributes['subheadline'] ?? 'In 4 einfachen Schritten zum ersten Training';
$steps = $attributes['steps'] ?? [];
$background_color = $attributes['backgroundColor'] ?? 'light';
$age_category = $attributes['ageCategory'] ?? 'default';
$align = $attributes['align'] ?? 'full';

if (empty($steps)) return;

$unique_id = 'steps-timeline-' . uniqid();
$theme_uri = get_template_directory_uri();

$align_class = '';
if ($align === 'wide') $align_class = 'alignwide';
if ($align === 'full') $align_class = 'alignfull';

// Bilder-Mapping nach Kategorie
$category_images = [
	'kids' => [
		$theme_uri . '/assets/images/fallback/kids/2022-04_potsi_kids-126-scaled.jpg',
		$theme_uri . '/assets/images/fallback/kids/minis.jpg',
		$theme_uri . '/assets/images/fallback/kids/slider_kids_balance-scaled.jpg',
		$theme_uri . '/assets/images/fallback/kids/EveryONE_5MP-49-von-152-scaled.jpg',
	],
	'juniors' => [
		$theme_uri . '/assets/images/fallback/juniors/G1A2147.jpg',
		$theme_uri . '/assets/images/fallback/juniors/RubikONE-Eroeffnung-scaled.jpg',
		$theme_uri . '/assets/images/fallback/juniors/ParkourONE-46-scaled-e1713961164686.jpg',
		$theme_uri . '/assets/images/fallback/juniors/grosserpsrung.jpg',
	],
	'adults' => [
		$theme_uri . '/assets/images/fallback/adults/IMG_2604SeeDo-Bern-1-scaled-e1705321049774.jpg',
		$theme_uri . '/assets/images/fallback/adults/2022_MastersBern-34-scaled.jpg',
		$theme_uri . '/assets/images/fallback/adults/balance.jpg',
		$theme_uri . '/assets/images/fallback/adults/EveryONE_5MP-149-von-152-scaled.jpg',
	],
	'default' => [
		$theme_uri . '/assets/images/fallback/juniors/G1A2147.jpg',
		$theme_uri . '/assets/images/fallback/kids/minis.jpg',
		$theme_uri . '/assets/images/fallback/adults/balance.jpg',
		$theme_uri . '/assets/images/fallback/juniors/grosserpsrung.jpg',
	],
];

// Bilder für aktuelle Kategorie
$images = $category_images[$age_category] ?? $category_images['default'];
?>

<section class="po-steps-timeline po-steps-timeline--<?php echo esc_attr($background_color); ?> <?php echo esc_attr($align_class); ?>" id="<?php echo esc_attr($unique_id); ?>">
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
				$image_url = $images[$index] ?? $images[0];
			?>
			<div class="po-steps-timeline__step">
				<div class="po-steps-timeline__number-wrap">
					<span class="po-steps-timeline__number"><?php echo $index + 1; ?></span>
				</div>
				<div class="po-steps-timeline__image-wrap">
					<img
						src="<?php echo esc_url($image_url); ?>"
						alt="<?php echo esc_attr($step['title']); ?>"
						class="po-steps-timeline__image"
						loading="lazy"
					/>
				</div>
				<h3 class="po-steps-timeline__title"><?php echo esc_html($step['title']); ?></h3>
				<p class="po-steps-timeline__desc"><?php echo esc_html($step['description']); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
