/**
 * ParkourONE Analytics – Frontend Tracker
 * Lightweight, cookie-free, DSGVO-konform
 */
(function () {
	'use strict';

	if (!window.poAnalytics || !window.poAnalytics.endpoint) return;

	// ── Session ID (sessionStorage, kein Cookie) ──
	var SESSION_KEY = 'po_analytics_sid';
	var sessionId = sessionStorage.getItem(SESSION_KEY);
	if (!sessionId) {
		sessionId = Math.random().toString(36).substr(2, 12) + Date.now().toString(36);
		sessionStorage.setItem(SESSION_KEY, sessionId);
	}

	// ── Device Detection ──
	var ua = navigator.userAgent || '';

	function getDeviceType() {
		if (/Mobi|Android.*Mobile|iPhone|iPod/.test(ua)) return 'mobile';
		if (/iPad|Android(?!.*Mobile)|Tablet/.test(ua)) return 'tablet';
		return 'desktop';
	}

	function getBrowser() {
		if (ua.indexOf('Firefox') > -1) return 'Firefox';
		if (ua.indexOf('Edg') > -1) return 'Edge';
		if (ua.indexOf('Chrome') > -1) return 'Chrome';
		if (ua.indexOf('Safari') > -1) return 'Safari';
		if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) return 'Opera';
		return 'Other';
	}

	function getOS() {
		if (ua.indexOf('Win') > -1) return 'Windows';
		if (ua.indexOf('Mac') > -1) return 'macOS';
		if (ua.indexOf('Linux') > -1) return 'Linux';
		if (ua.indexOf('Android') > -1) return 'Android';
		if (/iPhone|iPad|iPod/.test(ua)) return 'iOS';
		return 'Other';
	}

	// ── UTM Parameter ──
	function getParam(name) {
		var match = location.search.match(new RegExp('[?&]' + name + '=([^&]*)'));
		return match ? decodeURIComponent(match[1]) : '';
	}

	// ── Page Load Time ──
	function getLoadTime() {
		try {
			var nav = performance.getEntriesByType('navigation')[0];
			if (nav) return Math.round(nav.loadEventEnd - nav.startTime);
			var t = performance.timing;
			if (t && t.loadEventEnd > 0) return t.loadEventEnd - t.navigationStart;
		} catch (e) {}
		return 0;
	}

	// ── Scroll Tracking ──
	var maxScroll = 0;

	function updateScroll() {
		var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
		var docHeight = Math.max(
			document.body.scrollHeight,
			document.documentElement.scrollHeight
		);
		var winHeight = window.innerHeight;
		if (docHeight <= winHeight) {
			maxScroll = 100;
			return;
		}
		var pct = Math.round((scrollTop / (docHeight - winHeight)) * 100);
		if (pct > maxScroll) maxScroll = pct;
	}

	window.addEventListener('scroll', updateScroll, { passive: true });
	updateScroll();

	// ── Send Event ──
	function send(data) {
		var payload = Object.assign(
			{
				session_id: sessionId,
				page_url: location.pathname + location.search,
				page_title: document.title || '',
				referrer: document.referrer || '',
				utm_source: getParam('utm_source'),
				utm_medium: getParam('utm_medium'),
				utm_campaign: getParam('utm_campaign'),
				device_type: getDeviceType(),
				browser: getBrowser(),
				os: getOS(),
				screen_width: screen.width || 0,
				language: (navigator.language || '').substr(0, 10),
			},
			data
		);

		var body = JSON.stringify(payload);
		if (navigator.sendBeacon) {
			navigator.sendBeacon(
				poAnalytics.endpoint,
				new Blob([body], { type: 'application/json' })
			);
		} else {
			fetch(poAnalytics.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: body,
				keepalive: true,
			}).catch(function () {});
		}
	}

	// ── Pageview ──
	var pageStart = Date.now();

	function sendPageview() {
		send({
			event_type: 'pageview',
			load_time: getLoadTime(),
			scroll_depth: maxScroll,
			time_on_page: 0,
		});
	}

	if (document.readyState === 'complete') {
		setTimeout(sendPageview, 100);
	} else {
		window.addEventListener('load', function () {
			setTimeout(sendPageview, 100);
		});
	}

	// ── Page Leave ──
	function sendUnload() {
		var timeSpent = Math.round((Date.now() - pageStart) / 1000);
		if (timeSpent < 2) return;

		send({
			event_type: 'page_leave',
			scroll_depth: maxScroll,
			time_on_page: timeSpent,
		});
	}

	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden') sendUnload();
	});
	window.addEventListener('pagehide', sendUnload);

	// ── Click Tracking ──
	document.addEventListener(
		'click',
		function (e) {
			var el = e.target.closest('a, button, [data-po-track]');
			if (!el) return;

			var label = '';
			var value = '';

			// Externe Links
			if (el.tagName === 'A' && el.hostname !== location.hostname) {
				label = 'outbound';
				value = el.href;
			}
			// Downloads
			else if (el.tagName === 'A' && /\.(pdf|zip|doc|xls|csv)/i.test(el.href)) {
				label = 'download';
				value = el.href.split('/').pop();
			}
			// Telefon / E-Mail
			else if (el.tagName === 'A' && /^(tel:|mailto:)/.test(el.href)) {
				label = el.href.startsWith('tel:') ? 'phone_click' : 'email_click';
				value = el.href.replace(/^(tel:|mailto:)/, '');
			}
			// Custom tracking
			else if (el.dataset.poTrack) {
				label = el.dataset.poTrack;
				value = el.dataset.poValue || el.textContent.trim().substr(0, 100);
			}
			// CTA Buttons
			else if (el.tagName === 'BUTTON' || (el.tagName === 'A' && el.classList.length > 0)) {
				var text = el.textContent.trim().toLowerCase();
				if (/anmeld|probetrain|kontakt|buchen|registr|jetzt|start|kaufen|bestell/.test(text)) {
					label = 'cta_click';
					value = el.textContent.trim().substr(0, 100);
				}
			}

			if (label) {
				send({
					event_type: 'click',
					event_label: label,
					event_value: value,
				});
			}
		},
		true
	);

	// ── Form Tracking ──
	document.addEventListener(
		'submit',
		function (e) {
			var form = e.target;
			if (!form || form.tagName !== 'FORM') return;

			var formId = form.id || form.action || 'unknown';
			var formName = form.getAttribute('name') || form.dataset.poForm || '';

			send({
				event_type: 'form_submit',
				event_label: formName || formId,
				event_value: form.action || '',
			});
		},
		true
	);

	// ── Public API ──
	window.poTrack = function (eventType, label, value) {
		send({
			event_type: String(eventType).substr(0, 30),
			event_label: String(label || '').substr(0, 255),
			event_value: String(value || '').substr(0, 255),
		});
	};
})();
