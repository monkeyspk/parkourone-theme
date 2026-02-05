<?php
$quotes = $attributes['quotes'] ?? [];
$layout = $attributes['layout'] ?? 'single';
$style = $attributes['style'] ?? 'light';

$class = 'po-quote-highlight';
$class .= ' po-quote-highlight--' . $layout;
$class .= ' po-quote-highlight--' . $style;

// Fallback-Bilder (Portrait für Testimonials)
$fallback_images = [
	parkourone_get_fallback_image('adults', 'portrait'),
	parkourone_get_fallback_image('juniors', 'portrait'),
];
?>

<?php if (!empty($quotes)): ?>
<section class="<?php echo esc_attr($class); ?>">
	<div class="po-quote-highlight__inner">
		<div class="po-quote-highlight__grid">
			<?php foreach (array_slice($quotes, 0, 2) as $index => $quote): ?>
			<blockquote class="po-quote-highlight__item">
				<div class="po-quote-highlight__icon">
					<svg viewBox="0 0 24 24" fill="currentColor">
						<path d="M6 17h3l2-4V7H5v6h3l-2 4zm8 0h3l2-4V7h-6v6h3l-2 4z"/>
					</svg>
				</div>
				<p class="po-quote-highlight__text"><?php echo esc_html($quote['text']); ?></p>
				<footer class="po-quote-highlight__footer">
					<?php 
					$img = !empty($quote['imageUrl']) ? $quote['imageUrl'] : ($fallback_images[$index] ?? $fallback_images[0]);
					?>
					<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($quote['author']); ?>" class="po-quote-highlight__avatar" loading="lazy">
					<div class="po-quote-highlight__meta">
						<cite class="po-quote-highlight__author"><?php echo esc_html($quote['author']); ?></cite>
						<?php if (!empty($quote['role'])): ?>
						<span class="po-quote-highlight__role"><?php echo esc_html($quote['role']); ?></span>
						<?php endif; ?>
					</div>
				</footer>
			</blockquote>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
