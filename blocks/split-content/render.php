<?php
$headline = $attributes['headline'] ?? '';
$text = $attributes['text'] ?? '';
$imageUrl = $attributes['imageUrl'] ?? '';
$imageAlt = $attributes['imageAlt'] ?? '';
$imageRight = $attributes['imageRight'] ?? true;
$showCta = $attributes['showCta'] ?? false;
$ctaText = $attributes['ctaText'] ?? 'Mehr erfahren';
$ctaUrl = $attributes['ctaUrl'] ?? '#';
$bgColor = $attributes['backgroundColor'] ?? '#ffffff';

$class = 'po-split';
if ($imageRight) {
	$class .= ' po-split--image-right';
}
?>
<section class="<?php echo esc_attr($class); ?> alignfull" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<div class="po-split__text">
		<?php if ($headline): ?>
			<h2 class="po-split__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<?php if ($text): ?>
			<div class="po-split__content"><?php echo wp_kses_post($text); ?></div>
		<?php endif; ?>
		<?php if ($showCta && $ctaText): ?>
			<a href="<?php echo esc_url($ctaUrl); ?>" class="po-split__cta"><?php echo esc_html($ctaText); ?></a>
		<?php endif; ?>
	</div>
	<div class="po-split__media">
		<?php if ($imageUrl): ?>
			<img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($imageAlt ?: wp_strip_all_tags($headline ?? 'ParkourONE')); ?>" loading="lazy">
		<?php endif; ?>
	</div>
</section>
