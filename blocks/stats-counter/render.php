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
	function initStatsCounter() {
		var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
		console.log('Stats Counter Init:', '<?php echo esc_js($unique_id); ?>', section);
		if (!section) return;

		var numbers = section.querySelectorAll('.po-stats__number');
		console.log('Stats Numbers found:', numbers.length);
		numbers.forEach(function(n) {
			console.log('Number data-target:', n.getAttribute('data-target'));
		});
		var animated = false;

		function animateNumber(el) {
			var targetStr = el.getAttribute('data-target') || '0';
			// Entferne alle nicht-numerischen Zeichen ausser Punkt/Komma
			var cleanNum = targetStr.replace(/[^\d.,]/g, '').replace(',', '.');
			var target = parseFloat(cleanNum) || 0;
			var isInteger = Number.isInteger(target);
			var duration = 2000;
			var startTime = null;

			function easeOutQuart(t) {
				return 1 - Math.pow(1 - t, 4);
			}

			function step(timestamp) {
				if (!startTime) startTime = timestamp;
				var progress = Math.min((timestamp - startTime) / duration, 1);
				var easedProgress = easeOutQuart(progress);
				var current = easedProgress * target;

				if (isInteger) {
					el.textContent = Math.floor(current).toLocaleString('de-DE');
				} else {
					el.textContent = current.toFixed(1).replace('.', ',');
				}

				if (progress < 1) {
					requestAnimationFrame(step);
				} else {
					if (isInteger) {
						el.textContent = target.toLocaleString('de-DE');
					} else {
						el.textContent = target.toFixed(1).replace('.', ',');
					}
				}
			}

			requestAnimationFrame(step);
		}

		function startAnimation() {
			console.log('Stats Counter: Starting animation!');
			if (animated) return;
			animated = true;
			section.classList.add('is-visible');
			numbers.forEach(function(num) {
				console.log('Animating number to:', num.getAttribute('data-target'));
				animateNumber(num);
			});
		}

		// Intersection Observer für zuverlässige Erkennung
		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting && !animated) {
						startAnimation();
						observer.disconnect();
					}
				});
			}, { threshold: 0.2 });

			observer.observe(section);
		} else {
			// Fallback für alte Browser
			function checkVisibility() {
				if (animated) return;
				var rect = section.getBoundingClientRect();
				var windowHeight = window.innerHeight || document.documentElement.clientHeight;

				if (rect.top <= windowHeight * 0.85 && rect.bottom >= 0) {
					startAnimation();
					window.removeEventListener('scroll', checkVisibility);
				}
			}

			window.addEventListener('scroll', checkVisibility, { passive: true });
			checkVisibility();
		}
	}

	// Warten bis DOM bereit ist
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initStatsCounter);
	} else {
		// DOM ist bereits geladen - kurz warten für Layout
		setTimeout(initStatsCounter, 100);
	}
})();
</script>
<?php endif; ?>
