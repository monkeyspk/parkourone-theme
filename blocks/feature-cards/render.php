<?php
$headline = $attributes['headline'] ?? 'Unsere Bildungsziele';
$showHeadline = $attributes['showHeadline'] ?? true;
$cards = $attributes['cards'] ?? [];
$bgColor = $attributes['backgroundColor'] ?? '#f5f5f7';
$iconSize = intval($attributes['iconSize'] ?? 160);

$dark_bgs = ['#1d1d1f', '#000000', '#0066cc', '#ff3b30'];
$classes = ['po-feature-cards', 'alignfull'];
if (in_array($bgColor, $dark_bgs)) {
	$classes[] = 'po-feature-cards--dark';
}

$style = '';
if ($bgColor) $style .= 'background-color: ' . esc_attr($bgColor) . '; ';
$style .= '--po-icon-size: ' . $iconSize . 'px';
?>
<section<?php if (!empty($attributes['anchor'])) echo ' id="' . esc_attr($attributes['anchor']) . '"'; ?> class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo $style; ?>">
	<?php if ($showHeadline && $headline): ?>
		<h2 class="po-feature-cards__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>
	<div class="po-feature-cards__grid">
		<?php foreach ($cards as $c): ?>
			<div class="po-feature-card">
				<?php if (!empty($c['iconUrl'])): ?>
					<img class="po-feature-card__icon" src="<?php echo esc_url($c['iconUrl'], ['http', 'https', 'data']); ?>" alt="" role="presentation" loading="lazy">
				<?php endif; ?>
				<h3 class="po-feature-card__title"><?php echo esc_html($c['title']); ?></h3>
				<p class="po-feature-card__desc"><?php echo esc_html($c['desc']); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
