<?php
$headline = $attributes['headline'] ?? 'Werde Teil unseres Teams';
$intro = $attributes['intro'] ?? '';
$jobs = $attributes['jobs'] ?? [];
$bgColor = $attributes['backgroundColor'] ?? '#ffffff';
?>
<section class="po-jobs alignfull" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<h2 class="po-jobs__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php if ($intro): ?>
		<p class="po-jobs__intro"><?php echo wp_kses_post($intro); ?></p>
	<?php endif; ?>
	
	<div class="po-jobs__grid">
		<?php foreach ($jobs as $j): ?>
			<div class="po-job-card">
				<h3 class="po-job-card__title"><?php echo esc_html($j['title']); ?></h3>
				<span class="po-job-card__type"><?php echo esc_html($j['type']); ?></span>
				<p class="po-job-card__desc"><?php echo esc_html($j['desc']); ?></p>
				<?php if (!empty($j['ctaText'])): ?>
					<a href="<?php echo esc_url($j['ctaUrl'] ?? '#'); ?>" class="po-job-card__cta"><?php echo esc_html($j['ctaText']); ?></a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
