(function() {
	'use strict';

	document.addEventListener('submit', function(e) {
		var form = e.target.closest('.po-inquiry__form');
		if (!form) return;
		e.preventDefault();

		// Honeypot check
		var hp = form.querySelector('[name="po_website"]');
		if (hp && hp.value) return;

		// Clear previous states
		var fields = form.querySelectorAll('.po-inquiry__field--invalid');
		fields.forEach(function(f) { f.classList.remove('po-inquiry__field--invalid'); });

		var msg = form.closest('.po-inquiry').querySelector('.po-inquiry__message');
		if (msg) {
			msg.hidden = true;
			msg.className = 'po-inquiry__message';
			msg.textContent = '';
		}

		// Validate required fields
		var valid = true;
		var requiredInputs = form.querySelectorAll('[required]');
		requiredInputs.forEach(function(input) {
			if (!input.value.trim() && input.type !== 'checkbox') {
				valid = false;
				input.closest('.po-inquiry__field').classList.add('po-inquiry__field--invalid');
			}
			if (input.type === 'checkbox' && !input.checked) {
				valid = false;
				input.closest('.po-inquiry__field').classList.add('po-inquiry__field--invalid');
			}
		});

		// Email validation
		var emailInput = form.querySelector('[name="email"]');
		if (emailInput && emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
			valid = false;
			emailInput.closest('.po-inquiry__field').classList.add('po-inquiry__field--invalid');
		}

		// Captcha validation
		var captchaInput = form.querySelector('[name="captcha"]');
		if (captchaInput && !captchaInput.value.trim()) {
			valid = false;
			captchaInput.closest('.po-inquiry__field').classList.add('po-inquiry__field--invalid');
		}

		if (!valid) {
			showMessage(msg, 'error', 'Bitte fülle alle Pflichtfelder korrekt aus.');
			var firstInvalid = form.querySelector('.po-inquiry__field--invalid input, .po-inquiry__field--invalid select, .po-inquiry__field--invalid textarea');
			if (firstInvalid) firstInvalid.focus();
			return;
		}

		// Loading state
		var btn = form.querySelector('.po-inquiry__button');
		btn.classList.add('po-inquiry__button--loading');
		btn.disabled = true;

		// Build FormData
		var formData = new FormData(form);

		// AJAX request
		var xhr = new XMLHttpRequest();
		xhr.open('POST', (typeof poInquiry !== 'undefined' ? poInquiry.ajaxUrl : '/wp-admin/admin-ajax.php'), true);
		xhr.onreadystatechange = function() {
			if (xhr.readyState !== 4) return;

			btn.classList.remove('po-inquiry__button--loading');
			btn.disabled = false;

			try {
				var response = JSON.parse(xhr.responseText);
				if (response.success) {
					showMessage(msg, 'success', response.data.message);
					form.reset();
				} else {
					showMessage(msg, 'error', response.data.message || 'Es gab ein Problem. Bitte versuche es erneut.');
				}
			} catch (err) {
				showMessage(msg, 'error', 'Es gab ein Problem. Bitte versuche es erneut.');
			}
		};
		xhr.send(formData);
	});

	function showMessage(el, type, text) {
		if (!el) return;
		el.className = 'po-inquiry__message po-inquiry__message--' + type;
		el.textContent = text;
		el.hidden = false;
		el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}
})();
