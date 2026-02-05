<?php
$headline = $attributes['headline'] ?? 'TRUST Education';
$intro = $attributes['intro'] ?? '';
$goals_headline = $attributes['goalsHeadline'] ?? 'Unsere Bildungsziele';
$goals = $attributes['goals'] ?? [];
$cta_text = $attributes['ctaText'] ?? 'Mehr über TRUST erfahren';
$bg_color = $attributes['backgroundColor'] ?? 'light';

// Modal content
$vision_headline = $attributes['visionHeadline'] ?? 'Ganzheitliche Bildung';
$vision_text = $attributes['visionText'] ?? '';
$values_headline = $attributes['valuesHeadline'] ?? 'Die Wertehand';
$values_intro = $attributes['valuesIntro'] ?? '';
$values = $attributes['values'] ?? [];
$method_headline = $attributes['methodHeadline'] ?? 'Unsere Methodik';
$methods = $attributes['methods'] ?? [];

$section_classes = ['po-trust', 'po-trust--bg-' . $bg_color];
if (!empty($attributes['align'])) {
	$section_classes[] = 'align' . $attributes['align'];
}

$unique_id = 'po-trust-' . uniqid();
$theme_uri = get_template_directory_uri();

// Icon paths
$icon_images = [
	'potential' => $theme_uri . '/assets/images/trust/Potential.png',
	'hand' => $theme_uri . '/assets/images/trust/Hand.png',
	'health' => $theme_uri . '/assets/images/trust/Gesundheit.png'
];
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="<?php echo esc_attr($unique_id); ?>">
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
						$icon_key = $goal['icon'] ?? ['potential', 'hand', 'health'][$index] ?? 'potential';
						$icon_url = $icon_images[$icon_key] ?? $icon_images['potential'];
					?>
						<article class="po-trust__goal">
							<div class="po-trust__goal-icon">
								<img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($goal['title'] ?? ''); ?>">
							</div>
							<h4 class="po-trust__goal-title"><?php echo esc_html($goal['title'] ?? ''); ?></h4>
							<p class="po-trust__goal-text"><?php echo esc_html($goal['text'] ?? ''); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="po-trust__cta-wrap">
			<button type="button" class="po-trust__cta" data-modal-target="<?php echo esc_attr($unique_id . '-modal'); ?>">
				<?php echo esc_html($cta_text); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<path d="M12 8v8M8 12h8"/>
				</svg>
			</button>
		</div>
	</div>
</section>

<!-- TRUST Education Modal -->
<div class="po-overlay" id="<?php echo esc_attr($unique_id . '-modal'); ?>" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="po-overlay__backdrop"></div>
	<div class="po-overlay__panel po-overlay__panel--trust">
		<button class="po-overlay__close" aria-label="Schliessen">
			<svg viewBox="0 0 24 24" fill="none">
				<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
				<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>

		<div class="po-trust-modal">
			<!-- Vision Section -->
			<header class="po-trust-modal__header">
				<span class="po-trust-modal__eyebrow">Vision & Mission</span>
				<h2 class="po-trust-modal__title"><?php echo esc_html($vision_headline); ?></h2>
				<p class="po-trust-modal__vision-text"><?php echo esc_html($vision_text); ?></p>
			</header>

			<!-- Bildungsziele Detail -->
			<section class="po-trust-modal__section">
				<h3 class="po-trust-modal__section-title"><?php echo esc_html($goals_headline); ?></h3>
				<div class="po-trust-modal__goals">
					<?php foreach ($goals as $index => $goal):
						$icon_key = $goal['icon'] ?? ['potential', 'hand', 'health'][$index] ?? 'potential';
						$icon_url = $icon_images[$icon_key] ?? $icon_images['potential'];
					?>
						<div class="po-trust-modal__goal">
							<div class="po-trust-modal__goal-icon">
								<img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($goal['title'] ?? ''); ?>">
							</div>
							<div class="po-trust-modal__goal-content">
								<h4 class="po-trust-modal__goal-title"><?php echo esc_html($goal['title'] ?? ''); ?></h4>
								<p class="po-trust-modal__goal-text"><?php echo esc_html($goal['fullText'] ?? $goal['text'] ?? ''); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Wertehand Section -->
			<section class="po-trust-modal__section po-trust-modal__section--values">
				<h3 class="po-trust-modal__section-title"><?php echo esc_html($values_headline); ?></h3>
				<?php if ($values_intro): ?>
					<p class="po-trust-modal__section-intro"><?php echo esc_html($values_intro); ?></p>
				<?php endif; ?>

				<div class="po-trust-modal__values">
					<?php foreach ($values as $v): ?>
						<div class="po-trust-modal__value">
							<span class="po-trust-modal__value-finger"><?php echo esc_html($v['finger'] ?? ''); ?></span>
							<strong class="po-trust-modal__value-name"><?php echo esc_html($v['value'] ?? ''); ?></strong>
							<p class="po-trust-modal__value-desc"><?php echo esc_html($v['desc'] ?? ''); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Methodik Section -->
			<section class="po-trust-modal__section">
				<h3 class="po-trust-modal__section-title"><?php echo esc_html($method_headline); ?></h3>
				<div class="po-trust-modal__methods">
					<?php foreach ($methods as $m): ?>
						<div class="po-trust-modal__method">
							<h4 class="po-trust-modal__method-title"><?php echo esc_html($m['title'] ?? ''); ?></h4>
							<p class="po-trust-modal__method-desc"><?php echo esc_html($m['desc'] ?? ''); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Footer with link -->
			<footer class="po-trust-modal__footer">
				<a href="https://trusteducation.ch" target="_blank" rel="noopener" class="po-trust-modal__link">
					Mehr auf trusteducation.ch
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
						<polyline points="15 3 21 3 21 9"/>
						<line x1="10" y1="14" x2="21" y2="3"/>
					</svg>
				</a>
			</footer>
		</div>
	</div>
</div>

<script>
(function() {
	var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
	if (!section) return;

	var btn = section.querySelector('[data-modal-target]');
	var modalId = btn ? btn.getAttribute('data-modal-target') : null;
	var modal = modalId ? document.getElementById(modalId) : null;
	if (!btn || !modal) return;

	var closeBtn = modal.querySelector('.po-overlay__close');
	var backdrop = modal.querySelector('.po-overlay__backdrop');

	function openModal() {
		modal.classList.add('is-active');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('po-no-scroll');
	}

	function closeModal() {
		modal.classList.remove('is-active');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('po-no-scroll');
	}

	btn.addEventListener('click', openModal);
	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	if (backdrop) backdrop.addEventListener('click', closeModal);

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.classList.contains('is-active')) {
			closeModal();
		}
	});
})();
</script>
