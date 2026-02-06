/**
 * ParkourONE Side Cart
 * Apple-style slide-out cart functionality
 */
(function() {
	'use strict';

	class ParkourONESideCart {
		constructor() {
			this.cart = document.getElementById('po-side-cart');
			if (!this.cart) return;

			this.panel = this.cart.querySelector('.po-side-cart__panel');
			this.overlay = this.cart.querySelector('.po-side-cart__overlay');
			this.content = this.cart.querySelector('[data-cart-content]');
			this.countBadge = this.cart.querySelector('[data-cart-count]');
			this.totalEl = this.cart.querySelector('[data-cart-total]');

			this.isOpen = false;
			this.isUpdating = false;

			this.bindEvents();
			this.initWooCommerceHooks();
		}

		/**
		 * Bind event handlers
		 */
		bindEvents() {
			// Close button
			this.cart.addEventListener('click', (e) => {
				if (e.target.closest('[data-close-cart]')) {
					this.close();
				}
			});

			// Overlay click
			this.overlay.addEventListener('click', () => this.close());

			// ESC key
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.isOpen) {
					this.close();
				}
			});

			// Quantity buttons
			this.cart.addEventListener('click', (e) => {
				const minusBtn = e.target.closest('[data-qty-minus]');
				const plusBtn = e.target.closest('[data-qty-plus]');
				const removeBtn = e.target.closest('[data-remove-item]');

				if (minusBtn) {
					const item = minusBtn.closest('[data-cart-item]');
					if (item) this.updateQuantity(item.dataset.cartItem, -1);
				}

				if (plusBtn) {
					const item = plusBtn.closest('[data-cart-item]');
					if (item) this.updateQuantity(item.dataset.cartItem, 1);
				}

				if (removeBtn) {
					const item = removeBtn.closest('[data-cart-item]');
					if (item) this.removeItem(item.dataset.cartItem);
				}
			});

			// Cart trigger buttons (anywhere on page)
			document.addEventListener('click', (e) => {
				const trigger = e.target.closest('[data-open-cart], .po-cart-trigger');
				if (trigger) {
					e.preventDefault();
					this.open();
				}
			});

			// Focus trap
			this.cart.addEventListener('keydown', (e) => {
				if (e.key === 'Tab' && this.isOpen) {
					this.handleFocusTrap(e);
				}
			});
		}

		/**
		 * Initialize WooCommerce event hooks
		 */
		initWooCommerceHooks() {
			// jQuery events from WooCommerce
			if (typeof jQuery !== 'undefined') {
				const $ = jQuery;
				const self = this;

				// Open cart when product is added
				$(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
					self.open();
					self.updateFromFragments(fragments);
				});

				// Update cart when fragments refresh
				$(document.body).on('wc_fragments_refreshed', function() {
					self.refreshContent();
				});

				// Handle WooCommerce cart updated event
				$(document.body).on('updated_cart_totals', function() {
					self.refreshContent();
				});
			}
		}

		/**
		 * Open side cart
		 */
		open() {
			this.isOpen = true;
			this.cart.setAttribute('aria-hidden', 'false');
			document.body.classList.add('po-side-cart-open');

			// Focus first interactive element
			setTimeout(() => {
				const closeBtn = this.cart.querySelector('[data-close-cart]');
				if (closeBtn) closeBtn.focus();
			}, 100);
		}

		/**
		 * Close side cart
		 */
		close() {
			this.isOpen = false;
			this.cart.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('po-side-cart-open');
		}

		/**
		 * Toggle side cart
		 */
		toggle() {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		}

		/**
		 * Update quantity
		 */
		async updateQuantity(cartItemKey, change) {
			if (this.isUpdating) return;

			const item = this.cart.querySelector(`[data-cart-item="${cartItemKey}"]`);
			if (!item) return;

			const qtyEl = item.querySelector('.po-side-cart__qty-value');
			const currentQty = parseInt(qtyEl.textContent, 10);
			const newQty = currentQty + change;

			// Remove if quantity is 0
			if (newQty < 1) {
				this.removeItem(cartItemKey);
				return;
			}

			// Visual feedback
			item.classList.add('is-updating');
			this.isUpdating = true;

			try {
				const response = await fetch(poSideCart.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'po_update_cart_item',
						nonce: poSideCart.nonce,
						cart_item_key: cartItemKey,
						quantity: newQty
					})
				});

				const data = await response.json();

				if (data.success) {
					// Update content
					if (data.data.cart_html) {
						this.content.innerHTML = data.data.cart_html;
					}
					if (data.data.cart_total) {
						this.updateTotal(data.data.cart_total);
					}
					if (typeof data.data.cart_count !== 'undefined') {
						this.updateCount(data.data.cart_count);
					}

					// Trigger WooCommerce event
					if (typeof jQuery !== 'undefined') {
						jQuery(document.body).trigger('wc_fragment_refresh');
					}
				}
			} catch (error) {
				console.error('Side Cart: Update failed', error);
			} finally {
				item.classList.remove('is-updating');
				this.isUpdating = false;
			}
		}

		/**
		 * Remove item from cart
		 */
		async removeItem(cartItemKey) {
			if (this.isUpdating) return;

			const item = this.cart.querySelector(`[data-cart-item="${cartItemKey}"]`);
			if (!item) return;

			// Visual feedback
			item.classList.add('is-updating');
			this.isUpdating = true;

			try {
				const response = await fetch(poSideCart.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'po_remove_cart_item',
						nonce: poSideCart.nonce,
						cart_item_key: cartItemKey
					})
				});

				const data = await response.json();

				if (data.success) {
					// Animate removal
					item.style.opacity = '0';
					item.style.transform = 'translateX(20px)';

					setTimeout(() => {
						// Update content
						if (data.data.cart_html) {
							this.content.innerHTML = data.data.cart_html;
						}
						if (data.data.cart_total) {
							this.updateTotal(data.data.cart_total);
						}
						if (typeof data.data.cart_count !== 'undefined') {
							this.updateCount(data.data.cart_count);
						}

						// Handle empty cart
						if (data.data.cart_count === 0) {
							this.renderEmptyState();
						}

						// Trigger WooCommerce event
						if (typeof jQuery !== 'undefined') {
							jQuery(document.body).trigger('wc_fragment_refresh');
						}
					}, 200);
				}
			} catch (error) {
				console.error('Side Cart: Remove failed', error);
				item.classList.remove('is-updating');
			} finally {
				this.isUpdating = false;
			}
		}

		/**
		 * Refresh cart content via AJAX
		 */
		async refreshContent() {
			try {
				const response = await fetch(poSideCart.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'po_get_cart_content',
						nonce: poSideCart.nonce
					})
				});

				const data = await response.json();

				if (data.success) {
					if (data.data.cart_html) {
						this.content.innerHTML = data.data.cart_html;
					}
					if (data.data.cart_total) {
						this.updateTotal(data.data.cart_total);
					}
					if (typeof data.data.cart_count !== 'undefined') {
						this.updateCount(data.data.cart_count);
					}
				}
			} catch (error) {
				console.error('Side Cart: Refresh failed', error);
			}
		}

		/**
		 * Update from WooCommerce fragments
		 */
		updateFromFragments(fragments) {
			if (!fragments) return;

			// Update custom side cart content if fragment exists
			if (fragments['.po-side-cart__content']) {
				this.content.innerHTML = fragments['.po-side-cart__content'];
			}

			// Update cart count badges
			if (fragments['.po-cart-count']) {
				this.updateCount(parseInt(fragments['.po-cart-count'], 10));
			}

			// Refresh content anyway to be sure
			setTimeout(() => this.refreshContent(), 100);
		}

		/**
		 * Update cart count badge
		 */
		updateCount(count) {
			// Update badge in side cart
			if (this.countBadge) {
				this.countBadge.textContent = count;
			}

			// Update all cart triggers on page
			document.querySelectorAll('.po-cart-trigger__count, [data-cart-count]').forEach(el => {
				el.textContent = count;
				el.dataset.count = count;
			});
		}

		/**
		 * Update cart total
		 */
		updateTotal(total) {
			if (this.totalEl) {
				this.totalEl.innerHTML = total;
			}
		}

		/**
		 * Render empty cart state
		 */
		renderEmptyState() {
			this.content.innerHTML = `
				<div class="po-side-cart__empty">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="9" cy="21" r="1"></circle>
						<circle cx="20" cy="21" r="1"></circle>
						<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
					</svg>
					<p>Dein Warenkorb ist leer</p>
					<a href="${poSideCart.shopUrl || '/angebote/'}" class="po-side-cart__btn po-side-cart__btn--primary">
						Angebote entdecken
					</a>
				</div>
			`;

			// Hide footer
			const footer = this.cart.querySelector('.po-side-cart__footer');
			if (footer) footer.style.display = 'none';
		}

		/**
		 * Focus trap for accessibility
		 */
		handleFocusTrap(e) {
			const focusableElements = this.panel.querySelectorAll(
				'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);
			const visibleElements = Array.from(focusableElements).filter(el => {
				return el.offsetParent !== null;
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
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			window.poSideCartInstance = new ParkourONESideCart();
		});
	} else {
		window.poSideCartInstance = new ParkourONESideCart();
	}

	// Global functions for external use
	window.poOpenSideCart = function() {
		if (window.poSideCartInstance) {
			window.poSideCartInstance.open();
		}
	};

	window.poCloseSideCart = function() {
		if (window.poSideCartInstance) {
			window.poSideCartInstance.close();
		}
	};

})();
