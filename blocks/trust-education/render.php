<?php
$headline = $attributes['headline'] ?? 'TRUST Education';
$intro = $attributes['intro'] ?? '';
$goals_headline = $attributes['goalsHeadline'] ?? 'Unsere Bildungsziele';
$goals = $attributes['goals'] ?? [];
$bg_color = $attributes['backgroundColor'] ?? 'light';

$section_classes = ['po-trust', 'po-trust--bg-' . $bg_color];
if (!empty($attributes['align'])) {
	$section_classes[] = 'align' . $attributes['align'];
}

// Icons für die Bildungsziele
$icons = [
	'star' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
	'heart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
	'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'
];
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
	<div class="po-trust__container">
		<div class="po-trust__header">
			<?php if ($headline): ?>
				<h2 class="po-trust__headline"><?php echo esc_html($headline); ?></h2>
			<?php endif; ?>

			<?php if ($intro): ?>
				<p class="po-trust__intro"><?php echo esc_html($intro); ?></p>
			<?php endif; ?>
		</div>

		<?php if (!empty($goals)): ?>
			<div class="po-trust__goals">
				<?php if ($goals_headline): ?>
					<h3 class="po-trust__goals-headline"><?php echo esc_html($goals_headline); ?></h3>
				<?php endif; ?>

				<div class="po-trust__goals-grid">
					<?php foreach ($goals as $index => $goal):
						$icon_key = $goal['icon'] ?? ['star', 'heart', 'shield'][$index] ?? 'star';
						$icon_svg = $icons[$icon_key] ?? $icons['star'];
					?>
						<article class="po-trust__goal">
							<div class="po-trust__goal-icon">
								<?php echo $icon_svg; ?>
							</div>
							<h4 class="po-trust__goal-title"><?php echo esc_html($goal['title'] ?? ''); ?></h4>
							<p class="po-trust__goal-text"><?php echo esc_html($goal['text'] ?? ''); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
