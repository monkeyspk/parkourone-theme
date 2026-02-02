<?php
$badgeUrl = $attributes['badgeUrl'] ?? '';
$headline = $attributes['headline'] ?? '';
$subtext = $attributes['subtext'] ?? '';
$buttonText = $attributes['buttonText'] ?? '';
$buttonUrl = $attributes['buttonUrl'] ?? '#';
$buttonIcon = $attributes['buttonIcon'] ?? 'play';
$imageUrl = $attributes['imageUrl'] ?? '';
?>

<section class="po-pb">
	<div class="po-pb__inner">
		<div class="po-pb__content">
			<?php if ($badgeUrl): ?>
				<img src="<?php echo esc_url($badgeUrl); ?>" alt="" class="po-pb__badge">
			<?php endif; ?>
			<div class="po-pb__text">
				<?php if ($headline): ?>
					<h3 class="po-pb__headline"><?php echo wp_kses_post($headline); ?></h3>
				<?php endif; ?>
				<?php if ($subtext): ?>
					<p class="po-pb__subtext"><?php echo wp_kses_post($subtext); ?></p>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="po-pb__action">
			<?php if ($buttonText): ?>
				<a href="<?php echo esc_url($buttonUrl); ?>" class="po-pb__button">
					<?php if ($buttonIcon === 'play'): ?>
						<span class="po-pb__button-icon">
							<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
						</span>
					<?php elseif ($buttonIcon === 'arrow'): ?>
						<span class="po-pb__button-icon">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
						</span>
					<?php endif; ?>
					<span class="po-pb__button-text"><?php echo esc_html($buttonText); ?></span>
				</a>
			<?php endif; ?>
			<?php if ($imageUrl): ?>
				<div class="po-pb__image-wrapper">
					<img src="<?php echo esc_url($imageUrl); ?>" alt="" class="po-pb__image">
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
