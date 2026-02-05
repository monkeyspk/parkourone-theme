<?php
$headline = $attributes['headline'] ?? '';
$stats = $attributes['stats'] ?? [];
$style = $attributes['style'] ?? 'light';
$unique_id = 'stats-counter-' . uniqid();

$class = 'po-stats po-stats--no-js';
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

<script>
(function() {
	var sectionId = '<?php echo esc_js($unique_id); ?>';
	var animated = false;
	var prepared = false;

	function animateNumber(el) {
		var targetStr = el.getAttribute('data-target') || '0';
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
				el.textContent = isInteger ? target.toLocaleString('de-DE') : target.toFixed(1).replace('.', ',');
			}
		}

		requestAnimationFrame(step);
	}

	function prepareAnimation(section) {
		if (prepared) return;
		prepared = true;
		// Entferne no-js Klasse (zeigt dass JS funktioniert)
		section.classList.remove('po-stats--no-js');
		// Setze Zahlen auf 0 für Animation
		section.querySelectorAll('.po-stats__number').forEach(function(el) {
			el.textContent = '0';
		});
	}

	function startAnimation(section) {
		if (animated) return;
		animated = true;
		section.classList.add('is-visible');
		section.querySelectorAll('.po-stats__number').forEach(animateNumber);
	}

	function initStatsCounter() {
		var section = document.getElementById(sectionId);
		if (!section || section.dataset.initialized) return;
		section.dataset.initialized = 'true';

		// Bereite Animation vor (setze Zahlen auf 0)
		prepareAnimation(section);

		// Intersection Observer
		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting && !animated) {
						startAnimation(section);
						observer.disconnect();
					}
				});
			}, {
				threshold: 0.1,
				rootMargin: '0px 0px -50px 0px'
			});

			observer.observe(section);

			// Fallback: Falls Observer nach 3 Sekunden nicht gefeuert hat und Element sichtbar ist
			setTimeout(function() {
				if (!animated) {
					var rect = section.getBoundingClientRect();
					if (rect.top < window.innerHeight && rect.bottom > 0) {
						startAnimation(section);
						observer.disconnect();
					}
				}
			}, 3000);
		} else {
			// Fallback für alte Browser
			function checkVisibility() {
				if (animated) return;
				var rect = section.getBoundingClientRect();
				if (rect.top < window.innerHeight * 0.9 && rect.bottom > 0) {
					startAnimation(section);
					window.removeEventListener('scroll', checkVisibility);
				}
			}
			window.addEventListener('scroll', checkVisibility, { passive: true });
			checkVisibility();
		}
	}

	// Initialisierung
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initStatsCounter);
	} else {
		initStatsCounter();
	}

	// Zusätzlicher Versuch nach kurzer Verzögerung
	setTimeout(initStatsCounter, 300);
})();
</script>
<?php endif; ?>
