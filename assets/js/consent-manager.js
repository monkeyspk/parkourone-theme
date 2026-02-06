/**
 * ParkourONE Consent Manager
 *
 * DSGVO-konformes Consent Management
 * - Prior Consent (Opt-In)
 * - Script Blocking
 * - Google Consent Mode v2
 * - Race-Condition-sicher
 */

(function() {
	'use strict';

	// ========================================
	// Configuration
	// ========================================
	const COOKIE_NAME = 'po_consent';
	const COOKIE_EXPIRY_DAYS = 365;

	// ========================================
	// Consent Manager Class
	// ========================================
	class POConsentManager {
		constructor() {
			this.config = window.poConsentConfig || {};
			this.consent = window.poConsent || null;
			this.banner = null;
			this.mainView = null;
			this.settingsView = null;
			this.initialized = false;

			// Early script blocking (vor DOMContentLoaded)
			this.blockScriptsEarly();

			// DOM Ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', () => this.init());
			} else {
				this.init();
			}
		}

		/**
		 * Initialize
		 */
		init() {
			if (this.initialized) return;
			this.initialized = true;

			this.banner = document.getElementById('po-consent-banner');
			this.mainView = document.getElementById('po-consent-main');
			this.settingsView = document.getElementById('po-consent-settings');

			if (!this.banner) {
				console.warn('PO Consent: Banner element not found');
				return;
			}

			this.bindEvents();
			this.setupMutationObserver();

			// Scripts aktivieren wenn Consent vorhanden
			if (this.consent) {
				this.activateConsentedScripts();
			}
		}

		/**
		 * Block scripts early (before DOM is ready)
		 * Uses MutationObserver to catch dynamically added scripts
		 */
		blockScriptsEarly() {
			// Bereits geladene Scripts können nicht blockiert werden
			// Aber wir können neue Scripts abfangen

			const blockedCategories = ['analytics', 'marketing', 'functional'];
			const hasConsent = (category) => {
				if (!this.consent || !this.consent.categories) return false;
				return this.consent.categories[category] === true;
			};

			// ========================================
			// localStorage/sessionStorage Blocking
			// ========================================
			this.blockStorageAPIs(hasConsent);

			// ========================================
			// Fingerprinting Protection
			// ========================================
			this.protectAgainstFingerprinting(hasConsent);

			// Script patterns to block
			const blockPatterns = [
				{ pattern: /google-analytics\.com|googletagmanager\.com|gtag/, category: 'analytics' },
				{ pattern: /facebook\.net|connect\.facebook|fbevents\.js/, category: 'marketing' },
				{ pattern: /doubleclick\.net|googlesyndication|googleads/, category: 'marketing' },
				{ pattern: /youtube\.com\/embed|youtube-nocookie\.com/, category: 'functional' },
				{ pattern: /maps\.googleapis\.com|maps\.google\.com/, category: 'functional' },
				{ pattern: /mailerlite|mlcdn/, category: 'marketing' },
			];

			// Check if script should be blocked
			const shouldBlock = (src) => {
				if (!src) return false;
				for (const { pattern, category } of blockPatterns) {
					if (pattern.test(src) && !hasConsent(category)) {
						return { blocked: true, category };
					}
				}
				return { blocked: false };
			};

			// Override appendChild to catch dynamically added scripts
			const originalAppendChild = Element.prototype.appendChild;
			Element.prototype.appendChild = function(element) {
				if (element.tagName === 'SCRIPT') {
					const src = element.src || element.getAttribute('src');
					const result = shouldBlock(src);

					if (result.blocked) {
						// Convert to blocked script
						element.type = 'text/plain';
						element.setAttribute('data-consent-src', src);
						element.setAttribute('data-consent-category', result.category);
						element.removeAttribute('src');
						console.log('PO Consent: Blocked script', src);
					}
				}
				return originalAppendChild.call(this, element);
			};

			// Also override insertBefore
			const originalInsertBefore = Element.prototype.insertBefore;
			Element.prototype.insertBefore = function(element, reference) {
				if (element.tagName === 'SCRIPT') {
					const src = element.src || element.getAttribute('src');
					const result = shouldBlock(src);

					if (result.blocked) {
						element.type = 'text/plain';
						element.setAttribute('data-consent-src', src);
						element.setAttribute('data-consent-category', result.category);
						element.removeAttribute('src');
						console.log('PO Consent: Blocked script', src);
					}
				}
				return originalInsertBefore.call(this, element, reference);
			};
		}

		/**
		 * Setup MutationObserver for dynamically added content
		 */
		setupMutationObserver() {
			const observer = new MutationObserver((mutations) => {
				mutations.forEach((mutation) => {
					mutation.addedNodes.forEach((node) => {
						// Handle iframes
						if (node.tagName === 'IFRAME') {
							this.handleIframe(node);
						}

						// Handle scripts
						if (node.tagName === 'SCRIPT') {
							this.handleScript(node);
						}

						// Check children
						if (node.querySelectorAll) {
							node.querySelectorAll('iframe').forEach(iframe => this.handleIframe(iframe));
							node.querySelectorAll('script[data-consent-src]').forEach(script => this.handleScript(script));
						}
					});
				});
			});

			observer.observe(document.documentElement, {
				childList: true,
				subtree: true
			});
		}

		/**
		 * Handle iframe consent
		 */
		handleIframe(iframe) {
			const src = iframe.src || iframe.dataset.src;
			if (!src) return;

			const patterns = {
				'functional': [/youtube\.com/, /youtube-nocookie\.com/, /vimeo\.com/, /maps\.google/],
				'marketing': [/facebook\.com/, /instagram\.com/]
			};

			for (const [category, categoryPatterns] of Object.entries(patterns)) {
				for (const pattern of categoryPatterns) {
					if (pattern.test(src) && !this.hasConsent(category)) {
						// Create placeholder
						const placeholder = this.createPlaceholder(iframe, category, src);
						iframe.parentNode.replaceChild(placeholder, iframe);
						return;
					}
				}
			}
		}

		/**
		 * Handle script consent
		 */
		handleScript(script) {
			const category = script.dataset.consentCategory;
			const src = script.dataset.consentSrc;

			if (category && src && this.hasConsent(category)) {
				// Reactivate script
				script.type = 'text/javascript';
				script.src = src;
			}
		}

		/**
		 * Create content placeholder
		 */
		createPlaceholder(element, category, src) {
			const placeholder = document.createElement('div');
			placeholder.className = 'po-consent-placeholder';
			placeholder.dataset.consentCategory = category;
			placeholder.dataset.originalElement = element.outerHTML;

			const categoryInfo = this.config.categories?.[category] || { name: category };

			placeholder.innerHTML = `
				<div class="po-consent-placeholder__content">
					<p class="po-consent-placeholder__text">
						Dieser Inhalt benötigt Ihre Zustimmung für "${categoryInfo.name}" Cookies.
					</p>
					<button class="po-consent-placeholder__btn" data-consent-accept="${category}">
						${categoryInfo.name} aktivieren
					</button>
					<button class="po-consent-placeholder__settings" data-consent-action="show-settings">
						Cookie-Einstellungen
					</button>
				</div>
			`;

			// Style placeholder
			placeholder.style.cssText = `
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: 200px;
				background: #f5f5f5;
				border: 1px solid #ddd;
				padding: 2rem;
				text-align: center;
			`;

			return placeholder;
		}

		/**
		 * Bind event handlers
		 */
		bindEvents() {
			// Delegate click events - use closest() to handle clicks on child elements (e.g., SVG icons)
			document.addEventListener('click', (e) => {
				const actionElement = e.target.closest('[data-consent-action]');
				const acceptElement = e.target.closest('[data-consent-accept]');

				if (actionElement) {
					e.preventDefault();
					this.handleAction(actionElement.dataset.consentAction);
				}

				if (acceptElement) {
					e.preventDefault();
					this.acceptCategory(acceptElement.dataset.consentAccept);
				}
			});

			// Expand/collapse categories
			document.querySelectorAll('.po-consent-category__expand').forEach(btn => {
				btn.addEventListener('click', () => {
					const expanded = btn.getAttribute('aria-expanded') === 'true';
					btn.setAttribute('aria-expanded', !expanded);
					const services = btn.nextElementSibling;
					if (services) {
						services.style.display = expanded ? 'none' : 'block';
					}
				});
			});

			// Keyboard navigation with focus trap (WCAG 2.1)
			this.banner.addEventListener('keydown', (e) => {
				if (e.key === 'Escape') {
					if (this.settingsView.style.display !== 'none') {
						this.hideSettings();
					}
				}

				// Focus trap - Tab must stay within banner
				if (e.key === 'Tab') {
					const focusableElements = this.banner.querySelectorAll(
						'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
					);
					const visibleElements = Array.from(focusableElements).filter(el => {
						return el.offsetParent !== null && getComputedStyle(el).visibility !== 'hidden';
					});

					if (visibleElements.length === 0) return;

					const firstElement = visibleElements[0];
					const lastElement = visibleElements[visibleElements.length - 1];

					if (e.shiftKey && document.activeElement === firstElement) {
						e.preventDefault();
						lastElement.focus();
					} else if (!e.shiftKey && document.activeElement === lastElement) {
						e.preventDefault();
						firstElement.focus();
					}
				}
			});

			// Set initial focus when banner is shown
			if (this.config.showBanner) {
				const firstButton = this.banner.querySelector('button');
				if (firstButton) {
					// Delay to ensure DOM is ready
					setTimeout(() => firstButton.focus(), 100);
				}
			}
		}

		/**
		 * Handle actions
		 */
		handleAction(action) {
			switch (action) {
				case 'accept-all':
					this.acceptAll();
					break;
				case 'reject-all':
					this.rejectAll();
					break;
				case 'show-settings':
					this.showSettings();
					break;
				case 'hide-settings':
					this.hideSettings();
					break;
				case 'save-selection':
					this.saveSelection();
					break;
			}
		}

		/**
		 * Accept all cookies
		 */
		acceptAll() {
			const categories = ['necessary', 'functional', 'analytics', 'marketing'];
			this.saveConsent(categories);
		}

		/**
		 * Reject all (only necessary)
		 */
		rejectAll() {
			this.saveConsent(['necessary']);
		}

		/**
		 * Accept specific category
		 */
		acceptCategory(category) {
			const currentCategories = this.consent?.categories || {};
			const categories = Object.keys(currentCategories).filter(cat => currentCategories[cat]);

			if (!categories.includes(category)) {
				categories.push(category);
			}

			if (!categories.includes('necessary')) {
				categories.push('necessary');
			}

			this.saveConsent(categories);
		}

		/**
		 * Save current selection from checkboxes
		 */
		saveSelection() {
			const categories = ['necessary'];

			document.querySelectorAll('[data-consent-category]:checked').forEach(checkbox => {
				const category = checkbox.dataset.consentCategory;
				if (!categories.includes(category)) {
					categories.push(category);
				}
			});

			this.saveConsent(categories);
		}

		/**
		 * Save consent to server and cookie
		 */
		saveConsent(categories) {
			const formData = new FormData();
			formData.append('action', 'po_save_consent');
			formData.append('nonce', this.config.nonce);
			categories.forEach(cat => formData.append('categories[]', cat));

			// First, delete any existing oversized cookie
			this.deleteOldCookie();

			fetch(this.config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then(response => {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(data => {
				if (data.success) {
					this.consent = data.data.consent;
					window.poConsent = this.consent;

					// Update Google Consent Mode
					if (data.data.googleConsentUpdate && typeof gtag === 'function') {
						gtag('consent', 'update', data.data.googleConsentUpdate);
					}

					// Activate consented scripts
					this.activateConsentedScripts();

					// Hide banner
					this.hideBanner();

					// Reload to activate blocked content
					setTimeout(() => location.reload(), 100);
				}
			})
			.catch(error => {
				console.warn('PO Consent: AJAX failed, using fallback cookie', error);
				// Fallback: Set cookie directly in JavaScript
				this.setFallbackCookie(categories);
			});
		}

		/**
		 * Delete old/oversized cookie
		 */
		deleteOldCookie() {
			// Delete cookie by setting it expired
			document.cookie = 'po_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
		}

		/**
		 * Fallback: Set cookie directly via JavaScript
		 * Used when AJAX fails (e.g., blocked by security plugin)
		 */
		setFallbackCookie(categories) {
			// Build minimal cookie data (same format as PHP)
			const cookieData = {
				v: this.config.version || '1.0',
				c: {
					n: 1,
					f: categories.includes('functional') ? 1 : 0,
					a: categories.includes('analytics') ? 1 : 0,
					m: categories.includes('marketing') ? 1 : 0
				},
				t: Math.floor(Date.now() / 1000),
				id: Math.random().toString(36).substr(2, 8)
			};

			// Base64 encode
			const cookieValue = btoa(JSON.stringify(cookieData));

			// Set cookie (365 days)
			const expires = new Date();
			expires.setFullYear(expires.getFullYear() + 1);
			document.cookie = `po_consent=${cookieValue}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;

			// Update local state
			this.consent = {
				version: cookieData.v,
				categories: {
					necessary: true,
					functional: cookieData.c.f === 1,
					analytics: cookieData.c.a === 1,
					marketing: cookieData.c.m === 1
				}
			};
			window.poConsent = this.consent;

			console.log('PO Consent: Fallback cookie set successfully');

			// Hide banner and reload
			this.hideBanner();
			setTimeout(() => location.reload(), 100);
		}

		/**
		 * Activate scripts that now have consent
		 */
		activateConsentedScripts() {
			// Find blocked scripts
			document.querySelectorAll('script[data-consent-src]').forEach(script => {
				const category = script.dataset.consentCategory;
				if (this.hasConsent(category)) {
					const newScript = document.createElement('script');
					newScript.src = script.dataset.consentSrc;
					newScript.type = 'text/javascript';

					// Copy attributes
					Array.from(script.attributes).forEach(attr => {
						if (!['type', 'src', 'data-consent-src', 'data-consent-category'].includes(attr.name)) {
							newScript.setAttribute(attr.name, attr.value);
						}
					});

					script.parentNode.replaceChild(newScript, script);
				}
			});

			// Restore placeholders
			document.querySelectorAll('.po-consent-placeholder').forEach(placeholder => {
				const category = placeholder.dataset.consentCategory;
				if (this.hasConsent(category) && placeholder.dataset.originalElement) {
					const temp = document.createElement('div');
					temp.innerHTML = placeholder.dataset.originalElement;
					const originalElement = temp.firstChild;
					placeholder.parentNode.replaceChild(originalElement, placeholder);
				}
			});
		}

		/**
		 * Check if category has consent
		 */
		hasConsent(category) {
			if (category === 'necessary') return true;
			return this.consent?.categories?.[category] === true;
		}

		/**
		 * Show settings view
		 */
		showSettings() {
			this.banner.style.display = '';
			this.mainView.style.display = 'none';
			this.settingsView.style.display = '';
			this.settingsView.querySelector('.po-consent-banner__close')?.focus();
		}

		/**
		 * Hide settings view
		 */
		hideSettings() {
			if (this.consent) {
				this.hideBanner();
			} else {
				this.mainView.style.display = '';
				this.settingsView.style.display = 'none';
			}
		}

		/**
		 * Hide banner completely
		 */
		hideBanner() {
			this.banner.style.display = 'none';
		}

		/**
		 * Show banner (for footer link)
		 */
		showBanner() {
			this.banner.style.display = '';
			this.mainView.style.display = 'none';
			this.settingsView.style.display = '';
		}

		/**
		 * Block localStorage/sessionStorage für Tracking
		 * Nur erlauben wenn Consent vorhanden
		 */
		blockStorageAPIs(hasConsent) {
			// Tracking-Keys die blockiert werden sollen
			const blockedKeyPatterns = [
				/^_ga/, /^_gid/, /^_gat/,           // Google Analytics
				/^_fbp/, /^_fbc/,                    // Facebook
				/^amplitude/, /^mixpanel/,           // Analytics Tools
				/^intercom/, /^crisp/, /^tidio/,     // Chat Tools
				/^mailerlite/, /^ml_/,               // MailerLite
			];

			const shouldBlockKey = (key) => {
				for (const pattern of blockedKeyPatterns) {
					if (pattern.test(key)) {
						return true;
					}
				}
				return false;
			};

			// localStorage überschreiben
			const originalSetItem = localStorage.setItem.bind(localStorage);
			localStorage.setItem = (key, value) => {
				if (shouldBlockKey(key) && !hasConsent('analytics') && !hasConsent('marketing')) {
					console.log('PO Consent: Blocked localStorage.setItem for', key);
					return;
				}
				return originalSetItem(key, value);
			};

			// sessionStorage überschreiben
			const originalSessionSetItem = sessionStorage.setItem.bind(sessionStorage);
			sessionStorage.setItem = (key, value) => {
				if (shouldBlockKey(key) && !hasConsent('analytics') && !hasConsent('marketing')) {
					console.log('PO Consent: Blocked sessionStorage.setItem for', key);
					return;
				}
				return originalSessionSetItem(key, value);
			};
		}

		/**
		 * Fingerprinting-Schutz
		 * Warnt vor Canvas/WebGL Fingerprinting Versuchen
		 */
		protectAgainstFingerprinting(hasConsent) {
			// Canvas Fingerprinting Detection
			const originalToDataURL = HTMLCanvasElement.prototype.toDataURL;
			HTMLCanvasElement.prototype.toDataURL = function(...args) {
				// Nur warnen wenn kein Consent für Analytics/Marketing
				if (!hasConsent('analytics') && !hasConsent('marketing')) {
					// Prüfen ob es sich um ein verstecktes Canvas handelt (typisch für Fingerprinting)
					if (this.width < 300 && this.height < 300 && !this.isConnected) {
						console.warn('PO Consent: Possible canvas fingerprinting detected and blocked');
						// Leicht verrauschte Daten zurückgeben um Fingerprinting zu erschweren
						return 'data:image/png;base64,blocked';
					}
				}
				return originalToDataURL.apply(this, args);
			};

			// WebGL Fingerprinting Detection
			const originalGetParameter = WebGLRenderingContext.prototype.getParameter;
			WebGLRenderingContext.prototype.getParameter = function(param) {
				// UNMASKED_VENDOR_WEBGL und UNMASKED_RENDERER_WEBGL sind Fingerprinting-typisch
				if (!hasConsent('analytics') && !hasConsent('marketing')) {
					if (param === 37445 || param === 37446) { // UNMASKED constants
						console.warn('PO Consent: WebGL fingerprinting attempt blocked');
						return 'blocked';
					}
				}
				return originalGetParameter.call(this, param);
			};
		}
	}

	// ========================================
	// Initialize
	// ========================================
	window.POConsentManager = new POConsentManager();

})();
