/**
 * ParkourONE Share Module
 * Single share button: Web Share API on mobile, clipboard fallback on desktop.
 */
(function() {
	'use strict';

	var SHARE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';

	// Event delegation for all .po-share-btn clicks
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.po-share-btn');
		if (!btn) return;

		e.preventDefault();
		e.stopPropagation();

		var url = btn.dataset.shareUrl || window.location.href;
		var title = btn.dataset.shareTitle || document.title;
		var text = btn.dataset.shareText || '';

		if (navigator.share) {
			navigator.share({ title: title, text: text, url: url }).catch(function() {});
		} else {
			copyToClipboard(url, btn);
		}
	});

	function copyToClipboard(text, btn) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function() {
				showCopied(btn);
			}).catch(function() {
				fallbackCopy(text, btn);
			});
		} else {
			fallbackCopy(text, btn);
		}
	}

	function fallbackCopy(text, btn) {
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.opacity = '0';
		document.body.appendChild(textarea);
		textarea.select();
		try { document.execCommand('copy'); showCopied(btn); } catch(e) {}
		document.body.removeChild(textarea);
	}

	function showCopied(btn) {
		var label = btn.querySelector('.po-share-btn__label');
		if (label) {
			var original = label.textContent;
			label.textContent = 'Link kopiert!';
			btn.classList.add('po-share-btn--copied');
			setTimeout(function() {
				label.textContent = original;
				btn.classList.remove('po-share-btn--copied');
			}, 2000);
		} else {
			btn.classList.add('po-share-btn--copied');
			btn.setAttribute('title', 'Link kopiert!');
			setTimeout(function() {
				btn.classList.remove('po-share-btn--copied');
				btn.removeAttribute('title');
			}, 2000);
		}
	}

	// Public API for creating share button HTML (used by JS-rendered blocks)
	window.poShare = {
		icon: SHARE_ICON,
		buttonHtml: function(url, title, text, small) {
			var cls = 'po-share-btn' + (small ? ' po-share-btn--sm' : '');
			var labelHtml = small ? '' : '<span class="po-share-btn__label">Teilen</span>';
			return '<button type="button" class="' + cls + '" ' +
				'data-share-url="' + escAttr(url) + '" ' +
				'data-share-title="' + escAttr(title) + '" ' +
				'data-share-text="' + escAttr(text) + '" ' +
				'aria-label="Teilen">' +
				SHARE_ICON + labelHtml +
				'</button>';
		}
	};

	function escAttr(str) {
		if (!str) return '';
		return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}
})();
