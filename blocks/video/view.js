(function() {
	'use strict';

	/**
	 * Build the iframe src URL for a given platform and video ID.
	 */
	function getEmbedUrl(platform, videoId) {
		if (platform === 'youtube') {
			return 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0';
		}
		if (platform === 'vimeo') {
			return 'https://player.vimeo.com/video/' + videoId + '?autoplay=1';
		}
		return '';
	}

	/**
	 * Create and insert an iframe replacing the placeholder.
	 */
	function loadIframe(wrapper, platform, videoId) {
		var src = getEmbedUrl(platform, videoId);
		if (!src) return;

		var iframe = document.createElement('iframe');
		iframe.className = 'po-video__media';
		iframe.src = src;
		iframe.setAttribute('frameborder', '0');
		iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture; encrypted-media');
		iframe.setAttribute('allowfullscreen', '');
		iframe.setAttribute('loading', 'eager');

		wrapper.appendChild(iframe);
	}

	/**
	 * Handle click on video placeholder.
	 * Uses event delegation for performance and dynamic content support.
	 */
	document.addEventListener('click', function(e) {
		var placeholder = e.target.closest('.po-video__placeholder');
		if (!placeholder) return;

		e.preventDefault();

		var wrapper = placeholder.closest('.po-video__wrapper');
		if (!wrapper) return;

		var platform = placeholder.getAttribute('data-platform');
		var videoId = placeholder.getAttribute('data-video-id');

		if (platform && videoId) {
			// Remove the placeholder
			placeholder.remove();

			// Load the iframe
			loadIframe(wrapper, platform, videoId);
		}
	});

	/**
	 * Handle HTML5 video: if a video element with poster exists,
	 * ensure clicking the wrapper area starts playback and shows controls.
	 */
	document.addEventListener('click', function(e) {
		var wrapper = e.target.closest('.po-video__wrapper');
		if (!wrapper) return;

		var video = wrapper.querySelector('video.po-video__media');
		if (!video) return;

		// Only intervene if the video is paused and the click target is not the controls
		if (video.paused && e.target !== video) {
			video.play();
		}
	});
})();
