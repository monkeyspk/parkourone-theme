<?php
$headline = $attributes['headline'] ?? 'Warum ParkourONE?';
$slides = $attributes['slides'] ?? [];
static $po_usp_instance = 0; $po_usp_instance++;
$anchor = $attributes['anchor'] ?? '';$unique_id = 'usp-slider-' . $po_usp_instance;

// Standard-Bilder für USP-Slides - Landscape-Bilder aus verschiedenen Kategorien
$categories = ['adults', 'juniors', 'kids', 'adults', 'juniors'];
$default_images = [];
foreach ($categories as $cat) {
	$default_images[] = parkourone_get_fallback_image($cat, 'landscape');
}

// Fallback-Bilder zuweisen, wenn keine gesetzt
foreach ($slides as $index => &$slide) {
	if (empty($slide['imageUrl']) && isset($default_images[$index])) {
		$slide['imageUrl'] = $default_images[$index];
	}
}
unset($slide);
?>

<?php if (!empty($slides)): ?>
<section class="po-usp" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">
	<?php if ($headline): ?>
	<h2 class="po-usp__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<div class="po-usp__wrapper">
		<div class="po-usp__track">
			<?php foreach ($slides as $index => $slide): ?>
				<article class="po-usp__card" data-modal-target="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>">
					<?php if (!empty($slide['imageUrl'])): ?>
					<div class="po-usp__card-image">
						<img src="<?php echo esc_url($slide['imageUrl']); ?>" alt="<?php echo esc_attr($slide['eyebrow'] ?? ''); ?>" loading="lazy">
					</div>
					<?php endif; ?>
					<div class="po-usp__card-gradient"></div>
					<div class="po-usp__card-content">
						<?php if (!empty($slide['eyebrow'])): ?>
							<span class="po-usp__eyebrow"><?php echo esc_html($slide['eyebrow']); ?></span>
						<?php endif; ?>
						<?php if (!empty($slide['headline'])): ?>
							<h3 class="po-usp__title"><?php echo esc_html($slide['headline']); ?></h3>
						<?php endif; ?>
					</div>
					<button type="button" class="po-usp__plus" aria-label="Mehr erfahren">
						<svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</button>
				</article>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="po-usp__nav">
		<button type="button" class="po-usp__nav-btn po-usp__nav-prev" aria-label="Zurück" disabled>
			<svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="po-usp__nav-btn po-usp__nav-next" aria-label="Weiter">
			<svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
</section>

<?php foreach ($slides as $index => $slide): ?>
<div class="po-overlay" id="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-overlay__backdrop"></div>
	<div class="po-overlay__panel po-overlay__panel--compact">
		<button class="po-overlay__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
		<div class="po-usp-modal">
			<?php if (!empty($slide['eyebrow'])): ?>
				<span class="po-usp-modal__eyebrow"><?php echo esc_html($slide['eyebrow']); ?></span>
			<?php endif; ?>
			<?php if (!empty($slide['headline'])): ?>
				<h2 class="po-usp-modal__headline"><?php echo esc_html($slide['headline']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($slide['modalText'])): ?>
				<p class="po-usp-modal__text"><?php echo esc_html($slide['modalText']); ?></p>
			<?php endif; ?>
			<?php if (!empty($slide['buttonText'])): ?>
				<a href="<?php echo esc_url($slide['buttonUrl'] ?? '#'); ?>" class="po-usp-modal__button"><?php echo esc_html($slide['buttonText']); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endforeach; ?>

<?php endif; ?>
