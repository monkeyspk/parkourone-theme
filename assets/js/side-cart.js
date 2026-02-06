/**
 * ParkourONE Side Cart
 * Handles open/close, AJAX updates, and WooCommerce integration
 */
(function($) {
	'use strict';

	const SideCart = {
		// Elements
		cart: null,
		overlay: null,
		content: null,
		footer: null,

		// State
		isOpen: false,

		/**
		 * Initialize Side Cart
		 */
		init: function() {
			this.cart = document.querySelector('[data-side-cart]');
			this.overlay = document.querySelector('[data-side-cart-overlay]');
			this.content = document.querySelector('[data-side-cart-content]');
			this.footer = document.querySelector('[data-side-cart-footer]');

			if (!this.cart) return;

			this.bindEvents();
			this.initWooCommerceHooks();
		},

		/**
		 * Bind DOM events
		 */
		bindEvents: function() {
			const self = this;

			// Open triggers (header cart button, any element with data-open-side-cart)
			document.querySelectorAll('[data-open-side-cart]').forEach(function(trigger) {
				trigger.addEventListener('click', function(e) {
					e.preventDefault();
					self.open();
				});
			});

			// Close button
			const closeBtn = document.querySelector('[data-side-cart-close]');
			if (closeBtn) {
				closeBtn.addEventListener('click', function() {
					self.close();
				});
			}

			// Overlay click
			if (this.overlay) {
				this.overlay.addEventListener('click', function() {
					self.close();
				});
			}

			// ESC key
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && self.isOpen) {
					self.close();
				}
			});

			// Remove item
			document.addEventListener('click', function(e) {
				const removeBtn = e.target.closest('[data-remove-item]');
				if (removeBtn) {
					e.preventDefault();
					const cartItemKey = removeBtn.dataset.removeItem;
					self.removeItem(cartItemKey, removeBtn.closest('.po-side-cart__item'));
				}
			});
		},

		/**
		 * Hook into WooCommerce events
		 */
		initWooCommerceHooks: function() {
			const self = this;

			if (typeof $ !== 'undefined') {
				// WooCommerce standard add-to-cart (returns fragments)
				$(document.body).on('added_to_cart', function(e, fragments, cart_hash) {
					if (fragments) {
						// Standard WC flow: fragments available
						self.updateFragments(fragments);
						self.open();
					} else {
						// Custom AJAX (booking.js): no fragments, refresh manually
						self.refresh(function() {
							self.open();
						});
					}
				});

				// WooCommerce fragments refreshed (after wc_fragment_refresh)
				$(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
					self.updateReferences();
					self.updateFooterVisibility();
				});
			}

			// Native event listener as fallback
			document.body.addEventListener('added_to_cart', function(e) {
				// Only handle if jQuery didn't already
				if (!e._jQueryHandled) {
					self.refresh(function() {
						self.open();
					});
				}
			});
		},

		/**
		 * Open the side cart
		 */
		open: function() {
			this.cart.classList.add('is-open');
			this.overlay.classList.add('is-visible');
			document.body.classList.add('po-side-cart-open');
			this.cart.setAttribute('aria-hidden', 'false');
			this.isOpen = true;
		},

		/**
		 * Close the side cart
		 */
		close: function() {
			this.cart.classList.remove('is-open');
			this.overlay.classList.remove('is-visible');
			document.body.classList.remove('po-side-cart-open');
			this.cart.setAttribute('aria-hidden', 'true');
			this.isOpen = false;
		},

		/**
		 * Toggle the side cart
		 */
		toggle: function() {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		},

		/**
		 * Refresh side cart content via AJAX
		 */
		refresh: function(callback) {
			const self = this;

			$.ajax({
				type: 'POST',
				url: poSideCart.ajaxUrl,
				data: {
					action: 'po_side_cart_refresh',
					nonce: poSideCart.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update content
						const content = document.querySelector('[data-side-cart-content]');
						if (content) {
							content.innerHTML = response.data.content_html;
						}

						// Update counts
						self.updateCounts(response.data.cart_count);

						// Update total
						self.updateTotal(response.data.cart_total);

						// Show footer
						self.updateFooterVisibility();
					}

					if (typeof callback === 'function') {
						callback();
					}
				},
				error: function() {
					if (typeof callback === 'function') {
						callback();
					}
				}
			});
		},

		/**
		 * Update cart fragments from WooCommerce
		 */
		updateFragments: function(fragments) {
			if (!fragments) return;

			// Update each fragment
			for (const selector in fragments) {
				const el = document.querySelector(selector);
				if (el) {
					const temp = document.createElement('div');
					temp.innerHTML = fragments[selector];
					const newEl = temp.firstElementChild;

					if (newEl) {
						el.replaceWith(newEl);
					} else {
						el.innerHTML = fragments[selector];
					}
				}
			}

			this.updateReferences();
			this.updateFooterVisibility();
		},

		/**
		 * Update DOM references after content changes
		 */
		updateReferences: function() {
			this.content = document.querySelector('[data-side-cart-content]');
			this.footer = document.querySelector('[data-side-cart-footer]');
		},

		/**
		 * Remove item from cart via AJAX
		 */
		removeItem: function(cartItemKey, itemElement) {
			const self = this;

			if (!cartItemKey || !itemElement) return;

			// Add removing state
			itemElement.classList.add('is-removing');

			// AJAX request
			$.ajax({
				type: 'POST',
				url: poSideCart.ajaxUrl,
				data: {
					action: 'po_side_cart_remove',
					nonce: poSideCart.nonce,
					cart_item_key: cartItemKey
				},
				success: function(response) {
					if (response.success) {
						// Animate removal
						itemElement.style.height = itemElement.offsetHeight + 'px';
						itemElement.style.overflow = 'hidden';
						itemElement.style.transition = 'all 0.3s ease';

						requestAnimationFrame(function() {
							itemElement.style.height = '0';
							itemElement.style.paddingTop = '0';
							itemElement.style.paddingBottom = '0';
							itemElement.style.opacity = '0';
						});

						setTimeout(function() {
							// Update content
							const content = document.querySelector('[data-side-cart-content]');
							if (content) {
								content.innerHTML = response.data.content_html;
							}

							// Update counts
							self.updateCounts(response.data.cart_count);

							// Update total
							self.updateTotal(response.data.cart_total);

							// Update footer visibility
							self.updateFooterVisibility();
						}, 300);
					} else {
						itemElement.classList.remove('is-removing');
					}
				},
				error: function() {
					itemElement.classList.remove('is-removing');
				}
			});
		},

		/**
		 * Update cart counts in various places
		 */
		updateCounts: function(count) {
			// Side cart badge
			const badge = document.querySelector('[data-side-cart-count]');
			if (badge) {
				badge.textContent = count;
			}

			// Header cart count
			const headerCount = document.querySelector('.po-header__cart-count');
			if (headerCount) {
				headerCount.textContent = count;
				headerCount.dataset.cartCount = count;
			}
		},

		/**
		 * Update cart total
		 */
		updateTotal: function(total) {
			const totalEl = document.querySelector('[data-side-cart-total]');
			if (totalEl) {
				totalEl.innerHTML = total;
			}
		},

		/**
		 * Show/hide footer based on cart contents
		 */
		updateFooterVisibility: function() {
			const items = document.querySelectorAll('.po-side-cart__item');
			const footer = document.querySelector('[data-side-cart-footer]');

			if (footer) {
				footer.style.display = items.length > 0 ? 'block' : 'none';
			}
		}
	};

	// Initialize on DOM ready
	document.addEventListener('DOMContentLoaded', function() {
		SideCart.init();
	});

	// Expose globally for external use
	window.poSideCartInstance = SideCart;

})(jQuery);
