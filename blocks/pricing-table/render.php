<?php
$headline = $attributes['headline'] ?? 'Unsere Preise';
$subtext = $attributes['subtext'] ?? '';
$categories = $attributes['categories'] ?? [];
$style = $attributes['style'] ?? 'light';
$currency = $attributes['currency'] ?? '€';
$period = $attributes['period'] ?? 'pro Monat';
$footnote = $attributes['footnote'] ?? '';
$unique_id = 'pricing-table-' . uniqid();

$class = 'po-pricing';
$class .= ' po-pricing--' . $style;
?>

<?php if (!empty($categories)): ?>
<section class="<?php echo esc_attr($class); ?>" id="<?php echo esc_attr($unique_id); ?>">
	<div class="po-pricing__inner">
		<?php if ($headline || $subtext): ?>
		<div class="po-pricing__header">
			<?php if ($headline): ?>
			<h2 class="po-pricing__headline"><?php echo wp_kses_post($headline); ?></h2>
			<?php endif; ?>
			<?php if ($subtext): ?>
			<p class="po-pricing__subtext"><?php echo wp_kses_post($subtext); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="po-pricing__grid<?php echo count($categories) === 5 ? ' po-pricing__grid--five' : ''; ?>">
			<?php foreach ($categories as $index => $cat):
				$is_highlighted = !empty($cat['highlighted']);
				$card_class = 'po-pricing__card';
				if ($is_highlighted) $card_class .= ' po-pricing__card--highlighted';
			?>
			<div class="<?php echo esc_attr($card_class); ?>">
				<?php if ($is_highlighted): ?>
				<span class="po-pricing__badge">Beliebt</span>
				<?php endif; ?>

				<h3 class="po-pricing__card-title"><?php echo esc_html($cat['name'] ?? ''); ?></h3>

				<div class="po-pricing__price-wrapper">
					<?php if (count($cat['classes'] ?? []) > 1): ?>
					<span class="po-pricing__price-label">ab</span>
					<?php endif; ?>
					<span class="po-pricing__price"><?php echo esc_html($cat['fromPrice'] ?? '0'); ?><?php echo esc_html($currency); ?></span>
					<span class="po-pricing__period"><?php echo esc_html($period); ?></span>
				</div>

				<?php if (!empty($cat['classes'])): ?>
				<div class="po-pricing__classes">
					<?php foreach ($cat['classes'] as $class_item): ?>
					<div class="po-pricing__class-row">
						<span class="po-pricing__class-name"><?php echo esc_html($class_item['name'] ?? ''); ?></span>
						<span class="po-pricing__class-price"><?php echo esc_html($class_item['price'] ?? ''); ?><?php echo esc_html($currency); ?></span>
					</div>
					<?php if (!empty($class_item['details'])): ?>
					<span class="po-pricing__class-details"><?php echo esc_html($class_item['details']); ?></span>
					<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if (!empty($cat['features'])): ?>
				<ul class="po-pricing__features">
					<?php foreach ($cat['features'] as $feature): ?>
					<li class="po-pricing__feature">
						<svg class="po-pricing__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="20 6 9 17 4 12"></polyline>
						</svg>
						<?php echo esc_html($feature); ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

				<?php if (!empty($cat['ctaText']) && !empty($cat['ctaUrl'])): ?>
				<a href="<?php echo esc_url($cat['ctaUrl']); ?>" class="po-pricing__cta">
					<?php echo esc_html($cat['ctaText']); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ($footnote): ?>
		<p class="po-pricing__footnote"><?php echo esc_html($footnote); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>
