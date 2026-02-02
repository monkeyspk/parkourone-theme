<?php
$headline = $attributes['headline'] ?? '';
$stats = $attributes['stats'] ?? [];
$style = $attributes['style'] ?? 'light';
$unique_id = 'stats-counter-' . uniqid();

$class = 'po-stats';
$class .= ' po-stats--' . $style;
?>

<?php if (!empty($stats)): ?>
<section class="<?php echo esc_attr($class); ?>" id="<?php echo esc_attr($unique_id); ?>">
	<div class="po-stats__inner">
		<?php if ($headline): ?>
		<h2 class="po-stats__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>

		<div class="po-stats__grid">
			<?php foreach ($stats as $stat): ?>
			<div class="po-stats__item">
				<div class="po-stats__number-wrapper">
					<span class="po-stats__number" data-target="<?php echo esc_attr($stat['number']); ?>">0</span><?php if (!empty($stat['suffix'])): ?><span class="po-stats__suffix"><?php echo esc_html($stat['suffix']); ?></span><?php endif; ?>
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

<script>
(function() {
	var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
	if (!section) return;

	var numbers = section.querySelectorAll('.po-stats__number');
	var animated = false;

	function animateNumber(el) {
		var target = parseInt(el.getAttribute('data-target'), 10);
		var duration = 2000;
		var startTime = null;

		function easeOutQuart(t) {
			return 1 - Math.pow(1 - t, 4);
		}

		function step(timestamp) {
			if (!startTime) startTime = timestamp;
			var progress = Math.min((timestamp - startTime) / duration, 1);
			var easedProgress = easeOutQuart(progress);
			var current = Math.floor(easedProgress * target);
			el.textContent = current.toLocaleString('de-DE');
			if (progress < 1) {
				requestAnimationFrame(step);
			} else {
				el.textContent = target.toLocaleString('de-DE');
			}
		}

		requestAnimationFrame(step);
	}

	function checkVisibility() {
		if (animated) return;
		var rect = section.getBoundingClientRect();
		var windowHeight = window.innerHeight || document.documentElement.clientHeight;

		if (rect.top <= windowHeight * 0.85 && rect.bottom >= 0) {
			animated = true;
			section.classList.add('is-visible');
			numbers.forEach(function(num) {
				animateNumber(num);
			});
		}
	}

	window.addEventListener('scroll', checkVisibility, { passive: true });
	window.addEventListener('resize', checkVisibility, { passive: true });
	checkVisibility();
})();
</script>
<?php endif; ?>
