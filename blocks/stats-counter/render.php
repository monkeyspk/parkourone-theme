<?php
$headline = $attributes['headline'] ?? '';
$stats = $attributes['stats'] ?? [];
$style = $attributes['style'] ?? 'light';
static $po_stats_instance = 0; $po_stats_instance++;
$anchor = $attributes['anchor'] ?? '';$unique_id = 'stats-counter-' . $po_stats_instance;

$class = 'po-stats po-stats--no-js';
$class .= ' po-stats--' . $style;
?>

<?php if (!empty($stats)): ?>
<section class="<?php echo esc_attr($class); ?>" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">
	<div class="po-stats__inner">
		<?php if ($headline): ?>
		<h2 class="po-stats__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>

		<div class="po-stats__grid">
			<?php foreach ($stats as $stat): ?>
			<div class="po-stats__item">
				<div class="po-stats__number-wrapper">
					<span class="po-stats__number" data-target="<?php echo esc_attr($stat['number']); ?>"><?php echo esc_html($stat['number']); ?></span><?php if (!empty($stat['suffix'])): ?><span class="po-stats__suffix"><?php echo esc_html($stat['suffix']); ?></span><?php endif; ?>
				</div>
				<?php if (!empty($stat['label'])): ?>
				<span class="po-stats__label"><?php echo esc_html($stat['label']); ?></span>
				<?php endif; ?>
				<?php if (!empty($stat['subtext'])): ?>
				<span class="po-stats__subtext"><?php echo esc_html($stat['subtext']); ?></span>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php endif; ?>
