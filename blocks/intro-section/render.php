<?php
$headline = $attributes['headline'] ?? '';
$text = $attributes['text'] ?? '';
$show_benefits = $attributes['showBenefits'] ?? true;
$benefits_headline = $attributes['benefitsHeadline'] ?? 'Was dich erwartet:';
$benefits = $attributes['benefits'] ?? [];
$layout = $attributes['layout'] ?? 'default';
$bg_color = $attributes['backgroundColor'] ?? 'white';

$section_classes = [
	'po-intro-section',
	'po-intro-section--' . $layout,
	'po-intro-section--bg-' . $bg_color
];

if (!empty($attributes['align'])) {
	$section_classes[] = 'align' . $attributes['align'];
}
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
	<div class="po-intro-section__container">
		<div class="po-intro-section__content">
			<?php if ($headline): ?>
				<h2 class="po-intro-section__headline">
					<?php echo wp_kses_post($headline); ?>
				</h2>
			<?php endif; ?>

			<?php if ($text): ?>
				<p class="po-intro-section__text">
					<?php echo wp_kses_post($text); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ($show_benefits && !empty($benefits)): ?>
			<div class="po-intro-section__benefits">
				<?php if ($benefits_headline): ?>
					<h3 class="po-intro-section__benefits-headline">
						<?php echo esc_html($benefits_headline); ?>
					</h3>
				<?php endif; ?>

				<ul class="po-intro-section__benefits-list">
					<?php foreach ($benefits as $index => $benefit): ?>
						<li class="po-intro-section__benefit" style="--delay: <?php echo $index * 0.1; ?>s">
							<span class="po-intro-section__benefit-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
									<circle cx="12" cy="12" r="12" fill="currentColor" fill-opacity="0.1"/>
									<path d="M7 12.5L10 15.5L17 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="po-intro-section__benefit-text">
								<?php echo esc_html($benefit); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
