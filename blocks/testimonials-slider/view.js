document.addEventListener('DOMContentLoaded', function() {
	const testimonials = document.querySelector('.po-testimonials');
	if (!testimonials) return;

	const track = testimonials.querySelector('.po-testimonials__track');
	const prevBtn = testimonials.querySelector('.po-testimonials__nav-btn--prev');
	const nextBtn = testimonials.querySelector('.po-testimonials__nav-btn--next');

	if (!track) return;

	// Scroll amount (card width + gap)
	const getScrollAmount = function() {
		const card = track.querySelector('.po-testimonials__card');
		if (!card) return 360;
		return card.offsetWidth + 20; // card width + gap
	};

	// Update button states
	const updateButtons = function() {
		if (!prevBtn || !nextBtn) return;

		const isAtStart = track.scrollLeft <= 10;
		const isAtEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 10;

		prevBtn.disabled = isAtStart;
		nextBtn.disabled = isAtEnd;
	};

	// Navigation
	if (prevBtn) {
		prevBtn.addEventListener('click', function() {
			track.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', function() {
			track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
		});
	}

	// Update buttons on scroll
	track.addEventListener('scroll', updateButtons);

	// Initial state
	updateButtons();

	// Drag to scroll
	let isDown = false;
	let startX;
	let scrollLeft;

	track.addEventListener('mousedown', function(e) {
		isDown = true;
		track.classList.add('is-grabbing');
		startX = e.pageX - track.offsetLeft;
		scrollLeft = track.scrollLeft;
	});

	track.addEventListener('mouseleave', function() {
		isDown = false;
		track.classList.remove('is-grabbing');
	});

	track.addEventListener('mouseup', function() {
		isDown = false;
		track.classList.remove('is-grabbing');
	});

	track.addEventListener('mousemove', function(e) {
		if (!isDown) return;
		e.preventDefault();
		const x = e.pageX - track.offsetLeft;
		const walk = (x - startX) * 1.5;
		track.scrollLeft = scrollLeft - walk;
	});
});
