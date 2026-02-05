<?php
$headline = $attributes['headline'] ?? 'Was ist dein nächster Sprung?';
$subtext = $attributes['subtext'] ?? 'Nimm eine neue Herausforderung an, unabhängig von deinem Fitnesslevel.';
$categories = $attributes['categories'] ?? [];
$count = count($categories);

// Fallback-Bilder für Zielgruppen (Action & Parkour)
$fallback_images = [
	'kids' => get_template_directory_uri() . '/assets/images/fallback/kids/2022-04_potsi_kids-126-scaled.jpg',
	'juniors' => get_template_directory_uri() . '/assets/images/fallback/juniors/grosserpsrung.jpg',
	'adults' => get_template_directory_uri() . '/assets/images/fallback/adults/466A7464-scaled.jpg',
];

// Feste Links für Kategorieseiten
$category_links = [
	'kids' => home_url('/kids/'),
	'juniors' => home_url('/juniors/'),
	'adults' => home_url('/adults/'),
];

// Fallback-Bilder und Links zuweisen
foreach ($categories as $index => $cat) {
	$label_lower = strtolower($cat['label'] ?? '');

	// Fallback-Bild
	if (empty($cat['imageUrl']) && isset($fallback_images[$label_lower])) {
		$categories[$index]['imageUrl'] = $fallback_images[$label_lower];
	}

	// Fester Link für bekannte Kategorien
	if (isset($category_links[$label_lower])) {
		$categories[$index]['linkUrl'] = $category_links[$label_lower];
	}
}
?>

<section class="po-cg">
	<div class="po-cg__header">
		<?php if ($headline): ?>
			<h2 class="po-cg__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<?php if ($subtext): ?>
			<p class="po-cg__subtext"><?php echo wp_kses_post($subtext); ?></p>
		<?php endif; ?>
	</div>
	
	<div class="po-cg__grid po-cg__grid--<?php echo esc_attr($count); ?>">
		<?php foreach ($categories as $cat): ?>
			<a href="<?php echo esc_url($cat['linkUrl'] ?? '#'); ?>" class="po-cg__card"<?php if (!empty($cat['imageUrl'])): ?> style="background-image: url('<?php echo esc_url($cat['imageUrl']); ?>')"<?php endif; ?>>
				<span class="po-cg__label">→ <?php echo esc_html($cat['label'] ?? ''); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	
	<div class="po-cg__slider">
		<div class="po-cg__slider-track">
			<?php foreach ($categories as $cat): ?>
				<a href="<?php echo esc_url($cat['linkUrl'] ?? '#'); ?>" class="po-cg__slide"<?php if (!empty($cat['imageUrl'])): ?> style="background-image: url('<?php echo esc_url($cat['imageUrl']); ?>')"<?php endif; ?>>
					<span class="po-cg__label">→ <?php echo esc_html($cat['label'] ?? ''); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
