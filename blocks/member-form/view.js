(function() {
	'use strict';

	// ===== Modal Logic =====

	function openModal(modal) {
		modal.hidden = false;
		// Force reflow for animation
		modal.offsetHeight;
		modal.classList.add('is-open');
		document.body.style.overflow = 'hidden';

		// Focus trap: focus first input
		var firstInput = modal.querySelector('input:not([type="hidden"]):not([tabindex="-1"]), select, textarea');
		if (firstInput) {
			setTimeout(function() { firstInput.focus(); }, 100);
		}
	}

	function closeModal(modal) {
		modal.classList.remove('is-open');
		document.body.style.overflow = '';

		// Wait for animation before hiding
		setTimeout(function() {
			modal.hidden = true;
		}, 300);
	}

	// Open modal on card button click
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('[data-modal-target]');
		if (!btn) return;

		var modalId = btn.getAttribute('data-modal-target');
		var modal = document.getElementById(modalId);
		if (modal) openModal(modal);
	});

	// Close on close button
	document.addEventListener('click', function(e) {
		var closeBtn = e.target.closest('.po-memberform__modal-close');
		if (!closeBtn) return;

		var modal = closeBtn.closest('.po-memberform__modal');
		if (modal) closeModal(modal);
	});

	// Close on backdrop click
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('po-memberform__modal-backdrop')) {
			var modal = e.target.closest('.po-memberform__modal');
			if (modal) closeModal(modal);
		}
	});

	// Close on ESC
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			var openModals = document.querySelectorAll('.po-memberform__modal.is-open');
			openModals.forEach(function(modal) {
				closeModal(modal);
			});
		}
	});

	// Focus trap
	document.addEventListener('keydown', function(e) {
		if (e.key !== 'Tab') return;

		var modal = document.querySelector('.po-memberform__modal.is-open');
		if (!modal) return;

		var focusable = modal.querySelectorAll('button, input:not([type="hidden"]):not([tabindex="-1"]), select, textarea, a[href]');
		if (focusable.length === 0) return;

		var first = focusable[0];
		var last = focusable[focusable.length - 1];

		if (e.shiftKey) {
			if (document.activeElement === first) {
				e.preventDefault();
				last.focus();
			}
		} else {
			if (document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});

	// ===== Form Validation + Submission =====

	document.addEventListener('submit', function(e) {
		var form = e.target.closest('.po-memberform__form');
		if (!form) return;
		e.preventDefault();

		// Honeypot
		var hp = form.querySelector('[name="po_website"]');
		if (hp && hp.value) return;

		var formType = form.getAttribute('data-form-type');

		// Clear previous states
		form.querySelectorAll('.po-memberform__field--invalid').forEach(function(f) {
			f.classList.remove('po-memberform__field--invalid');
		});

		var msg = form.querySelector('.po-memberform__message');
		if (msg) {
			msg.hidden = true;
			msg.className = 'po-memberform__message';
			msg.textContent = '';
		}

		// Validate required fields
		var valid = true;
		var requiredInputs = form.querySelectorAll('[required]');
		requiredInputs.forEach(function(input) {
			if (input.type === 'checkbox') {
				if (!input.checked) {
					valid = false;
					markInvalid(input);
				}
			} else if (!input.value.trim()) {
				valid = false;
				markInvalid(input);
			}
		});

		// Email validation (verletzungen only)
		if (formType === 'verletzungen') {
			var emailInput = form.querySelector('[name="email"]');
			if (emailInput && emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
				valid = false;
				markInvalid(emailInput);
			}

			// Date validation: Ende must be after Beginn, minimum 30 days
			var beginnInput = form.querySelector('[name="beginn"]');
			var endeInput = form.querySelector('[name="ende"]');
			if (beginnInput && endeInput && beginnInput.value && endeInput.value) {
				var beginn = new Date(beginnInput.value);
				var ende = new Date(endeInput.value);
				var diffDays = Math.ceil((ende - beginn) / (1000 * 60 * 60 * 24));

				if (ende <= beginn) {
					valid = false;
					markInvalid(endeInput);
					showMessage(msg, 'error', 'Das Ende des Trainingsausfalls muss nach dem Beginn liegen.');
					focusFirstInvalid(form);
					return;
				}

				if (diffDays < 30) {
					valid = false;
					markInvalid(endeInput);
					showMessage(msg, 'error', 'Der Trainingsausfall muss mindestens 30 Tage betragen.');
					focusFirstInvalid(form);
					return;
				}
			}

			// File validation (client-side)
			var fileInput = form.querySelector('[name="sportdispens"]');
			if (fileInput && fileInput.files.length > 0) {
				var file = fileInput.files[0];
				var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
				var maxSize = 64 * 1024 * 1024; // 64MB

				if (allowedTypes.indexOf(file.type) === -1) {
					valid = false;
					markInvalid(fileInput);
					showMessage(msg, 'error', 'Nur JPG, PNG oder GIF Dateien sind erlaubt.');
					focusFirstInvalid(form);
					return;
				}

				if (file.size > maxSize) {
					valid = false;
					markInvalid(fileInput);
					showMessage(msg, 'error', 'Die Datei ist zu gross (max. 64 MB).');
					focusFirstInvalid(form);
					return;
				}
			}
		}

		// AHV validation
		if (formType === 'ahv') {
			var ahvInput = form.querySelector('[name="ahv_nummer"]');
			if (ahvInput && ahvInput.value) {
				var ahvClean = ahvInput.value.replace(/[\.\s\-]/g, '');
				if (!/^756\d{10}$/.test(ahvClean)) {
					valid = false;
					markInvalid(ahvInput);
					showMessage(msg, 'error', 'Bitte eine gültige AHV-Nummer eingeben (13 Ziffern, beginnt mit 756).');
					focusFirstInvalid(form);
					return;
				}
			}
		}

		if (!valid) {
			showMessage(msg, 'error', 'Bitte fülle alle Pflichtfelder korrekt aus.');
			focusFirstInvalid(form);
			return;
		}

		// Loading state
		var btn = form.querySelector('.po-memberform__button');
		btn.classList.add('po-memberform__button--loading');
		btn.disabled = true;

		// Build FormData (supports file uploads as multipart)
		var formData = new FormData(form);

		// AJAX request with fetch
		fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: formData
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			btn.classList.remove('po-memberform__button--loading');
			btn.disabled = false;

			if (data.success) {
				showMessage(msg, 'success', data.data.message);
				form.reset();

				// Close modal after 3s
				var modal = form.closest('.po-memberform__modal');
				if (modal) {
					setTimeout(function() {
						closeModal(modal);
						// Reset message after modal closes
						setTimeout(function() {
							if (msg) {
								msg.hidden = true;
								msg.className = 'po-memberform__message';
								msg.textContent = '';
							}
						}, 400);
					}, 3000);
				}
			} else {
				showMessage(msg, 'error', data.data.message || 'Es gab ein Problem. Bitte versuche es erneut.');
			}
		})
		.catch(function() {
			btn.classList.remove('po-memberform__button--loading');
			btn.disabled = false;
			showMessage(msg, 'error', 'Es gab ein Problem. Bitte versuche es erneut.');
		});
	});

	function markInvalid(input) {
		var field = input.closest('.po-memberform__field');
		if (field) field.classList.add('po-memberform__field--invalid');
	}

	function focusFirstInvalid(form) {
		var first = form.querySelector('.po-memberform__field--invalid input, .po-memberform__field--invalid select, .po-memberform__field--invalid textarea');
		if (first) first.focus();
	}

	function showMessage(el, type, text) {
		if (!el) return;
		el.className = 'po-memberform__message po-memberform__message--' + type;
		el.textContent = text;
		el.hidden = false;
		el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}
})();
