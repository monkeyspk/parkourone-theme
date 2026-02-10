<?php
$headline = $attributes['headline'] ?? 'Unsere Bildungsziele';
$showHeadline = $attributes['showHeadline'] ?? true;
$cards = $attributes['cards'] ?? [];
$bgColor = $attributes['backgroundColor'] ?? '#f5f5f7';
?>
<section class="po-feature-cards alignfull" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<?php if ($showHeadline && $headline): ?>
		<h2 class="po-feature-cards__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>
	<div class="po-feature-cards__grid">
		<?php foreach ($cards as $c): ?>
			<div class="po-feature-card">
				<?php if (!empty($c['iconUrl'])): ?>
					<img class="po-feature-card__icon" src="<?php echo esc_url($c['iconUrl']); ?>" alt="" role="presentation" loading="lazy">
				<?php endif; ?>
				<h3 class="po-feature-card__title"><?php echo esc_html($c['title']); ?></h3>
				<p class="po-feature-card__desc"><?php echo esc_html($c['desc']); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
