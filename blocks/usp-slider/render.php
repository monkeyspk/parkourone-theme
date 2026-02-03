<?php
$headline = $attributes['headline'] ?? 'Warum ParkourONE?';
$slides = $attributes['slides'] ?? [];
$unique_id = 'usp-slider-' . uniqid();

// Standard-Bilder fuer USP-Slides (werden nur verwendet, wenn kein Bild gesetzt)
$default_images = [
	get_template_directory_uri() . '/assets/images/fallback/adults/balance.jpg',
	get_template_directory_uri() . '/assets/images/fallback/juniors/grosserpsrung.jpg',
	get_template_directory_uri() . '/assets/images/fallback/juniors/20190831_Last10_Berlin_Saturday_0304-scaled.jpg',
	get_template_directory_uri() . '/assets/images/fallback/adults/1T2A6249.jpg',
	get_template_directory_uri() . '/assets/images/fallback/kids/slider_kids_balance-scaled.jpg',
];

// Fallback-Bilder zuweisen, wenn keine gesetzt
foreach ($slides as $index => &$slide) {
	if (empty($slide['imageUrl']) && isset($default_images[$index])) {
		$slide['imageUrl'] = $default_images[$index];
	}
}
unset($slide);
?>

<?php if (!empty($slides)): ?>
<section class="po-usp" id="<?php echo esc_attr($unique_id); ?>">
	<?php if ($headline): ?>
	<h2 class="po-usp__headline"><?php echo wp_kses_post($headline); ?></h2>
	<?php endif; ?>

	<div class="po-usp__wrapper">
		<div class="po-usp__track">
			<?php foreach ($slides as $index => $slide): ?>
				<article class="po-usp__card" data-modal="<?php echo esc_attr($unique_id . '-modal-' . $index); ?>">
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

<script>
(function() {
	var section = document.getElementById('<?php echo esc_js($unique_id); ?>');
	if (!section) return;

	var wrapper = section.querySelector('.po-usp__wrapper');
	var track = section.querySelector('.po-usp__track');
	var cards = section.querySelectorAll('.po-usp__card');
	var prevBtn = section.querySelector('.po-usp__nav-prev');
	var nextBtn = section.querySelector('.po-usp__nav-next');

	function getCardWidth() {
		return cards[0].offsetWidth + 24;
	}

	function getVisibleCards() {
		return Math.floor(wrapper.offsetWidth / getCardWidth());
	}

	function updateNav() {
		var scrollLeft = wrapper.scrollLeft;
		var maxScroll = track.scrollWidth - wrapper.offsetWidth;
		prevBtn.disabled = scrollLeft <= 0;
		nextBtn.disabled = scrollLeft >= maxScroll - 10;
	}

	prevBtn.addEventListener('click', function() {
		wrapper.scrollBy({ left: -getCardWidth() * getVisibleCards(), behavior: 'smooth' });
	});

	nextBtn.addEventListener('click', function() {
		wrapper.scrollBy({ left: getCardWidth() * getVisibleCards(), behavior: 'smooth' });
	});

	wrapper.addEventListener('scroll', updateNav);
	window.addEventListener('resize', updateNav);
	updateNav();

	// Cards sind komplett klickbar
	cards.forEach(function(card) {
		var modalId = card.getAttribute('data-modal');
		var modal = document.getElementById(modalId);
		if (!modal) return;

		var closeBtn = modal.querySelector('.po-overlay__close');
		var backdrop = modal.querySelector('.po-overlay__backdrop');

		function openModal(e) {
			e.preventDefault();
			modal.classList.add('is-active');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('po-no-scroll');
		}

		function closeModal() {
			modal.classList.remove('is-active');
			modal.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('po-no-scroll');
		}

		card.addEventListener('click', openModal);
		closeBtn.addEventListener('click', closeModal);
		backdrop.addEventListener('click', closeModal);

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && modal.classList.contains('is-active')) {
				closeModal();
			}
		});
	});
})();
</script>
<?php endif; ?>
