(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-hero').forEach(function(hero) {
			if (hero.dataset.poInit) return;
			hero.dataset.poInit = 'true';

			// Smooth scroll for anchor links
			hero.querySelectorAll('.po-hero__button[href^="#"]').forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					var targetId = this.getAttribute('href').substring(1);
					var target = document.getElementById(targetId);
					if (target) {
						e.preventDefault();
						target.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				});
			});

			// Video Modal (uses .po-video-modal, not .po-overlay)
			var videoBtn = hero.querySelector('.po-hero__button--video');
			if (!videoBtn) return;

			var heroId = hero.id;
			var modal = document.getElementById(heroId + '-modal');
			if (!modal) return;

			var iframe = modal.querySelector('.po-video-modal__iframe');
			var closeBtn = modal.querySelector('.po-video-modal__close');
			var backdrop = modal.querySelector('.po-video-modal__backdrop');

			function openModal() {
				if (!iframe) return;
				iframe.src = iframe.dataset.src;
				modal.classList.add('is-active');
				modal.setAttribute('aria-hidden', 'false');
				document.body.style.overflow = 'hidden';
			}

			function closeModal() {
				if (!iframe) return;
				modal.classList.remove('is-active');
				modal.setAttribute('aria-hidden', 'true');
				iframe.src = '';
				document.body.style.overflow = '';
			}

			videoBtn.addEventListener('click', openModal);
			if (closeBtn) closeBtn.addEventListener('click', closeModal);
			if (backdrop) backdrop.addEventListener('click', closeModal);

			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && modal.classList.contains('is-active')) {
					closeModal();
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
