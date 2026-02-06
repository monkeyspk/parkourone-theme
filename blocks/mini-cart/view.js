/**
 * Mini Cart Block - Frontend JavaScript
 * Handles drawer open/close and AJAX updates
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const miniCarts = document.querySelectorAll('.po-mini-cart');

		miniCarts.forEach(function(miniCart) {
			initMiniCart(miniCart);
		});

		// Listen for WooCommerce add to cart events
		document.body.addEventListener('added_to_cart', function(e, fragments, cart_hash, $button) {
			// Find mini carts that should open on add
			miniCarts.forEach(function(miniCart) {
				if (miniCart.dataset.showOnAdd === 'true') {
					openDrawer(miniCart);
					refreshMiniCart(miniCart);
				}
			});
		});

		// jQuery fallback for WooCommerce events
		if (typeof jQuery !== 'undefined') {
			jQuery(document.body).on('added_to_cart', function() {
				miniCarts.forEach(function(miniCart) {
					if (miniCart.dataset.showOnAdd === 'true') {
						openDrawer(miniCart);
						refreshMiniCart(miniCart);
					}
				});
			});
		}
	});

	function initMiniCart(miniCart) {
		const trigger = miniCart.querySelector('[data-mini-cart-trigger]');
		const overlay = miniCart.querySelector('[data-mini-cart-overlay]');
		const closeBtn = miniCart.querySelector('[data-mini-cart-close]');
		const drawer = miniCart.querySelector('[data-mini-cart-drawer]');
		const ajaxUrl = miniCart.dataset.ajaxUrl;
		const nonce = miniCart.dataset.nonce;

		// Open drawer
		if (trigger) {
			trigger.addEventListener('click', function() {
				openDrawer(miniCart);
			});
		}

		// Close drawer
		if (closeBtn) {
			closeBtn.addEventListener('click', function() {
				closeDrawer(miniCart);
			});
		}

		if (overlay) {
			overlay.addEventListener('click', function() {
				closeDrawer(miniCart);
			});
		}

		// Close on ESC key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && miniCart.classList.contains('is-open')) {
				closeDrawer(miniCart);
			}
		});

		// Remove item
		miniCart.addEventListener('click', function(e) {
			const removeBtn = e.target.closest('[data-remove-item]');
			if (!removeBtn) return;

			const item = removeBtn.closest('[data-cart-item-key]');
			if (!item) return;

			const cartItemKey = item.dataset.cartItemKey;
			removeItem(miniCart, item, cartItemKey, ajaxUrl, nonce);
		});
	}

	function openDrawer(miniCart) {
		miniCart.classList.add('is-open');
		document.body.classList.add('po-mini-cart-open');

		const drawer = miniCart.querySelector('[data-mini-cart-drawer]');
		if (drawer) {
			drawer.setAttribute('aria-hidden', 'false');
		}
	}

	function closeDrawer(miniCart) {
		miniCart.classList.remove('is-open');
		document.body.classList.remove('po-mini-cart-open');

		const drawer = miniCart.querySelector('[data-mini-cart-drawer]');
		if (drawer) {
			drawer.setAttribute('aria-hidden', 'true');
		}
	}

	function removeItem(miniCart, item, cartItemKey, ajaxUrl, nonce) {
		item.classList.add('is-removing');

		fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'po_mini_cart_remove',
				nonce: nonce,
				cart_item_key: cartItemKey
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Animate removal
				item.style.height = item.offsetHeight + 'px';
				item.style.overflow = 'hidden';
				item.style.transition = 'all 0.3s ease';

				requestAnimationFrame(() => {
					item.style.height = '0';
					item.style.padding = '0 24px';
					item.style.opacity = '0';
				});

				setTimeout(() => {
					item.remove();

					// Update counts
					updateCounts(miniCart, data.data.cart_count);
					updateTotal(miniCart, data.data.cart_total);

					// If cart is empty, refresh to show empty state
					if (data.data.cart_count === 0) {
						refreshMiniCart(miniCart);
					}
				}, 300);
			} else {
				item.classList.remove('is-removing');
			}
		})
		.catch(error => {
			console.error('Remove error:', error);
			item.classList.remove('is-removing');
		});
	}

	function updateCounts(miniCart, count) {
		// Trigger count
		const triggerCount = miniCart.querySelector('.po-mini-cart__trigger-count');
		if (triggerCount) {
			triggerCount.textContent = count;
			triggerCount.dataset.cartCount = count;
		}

		// Drawer count
		const drawerCount = miniCart.querySelector('[data-drawer-count]');
		if (drawerCount) {
			drawerCount.textContent = count;
		}

		// Header cart count
		const headerCount = document.querySelector('.po-header__cart-count');
		if (headerCount) {
			headerCount.textContent = count;
			headerCount.dataset.cartCount = count;
		}
	}

	function updateTotal(miniCart, total) {
		const totalEl = miniCart.querySelector('[data-mini-cart-total]');
		if (totalEl) {
			totalEl.innerHTML = total;
		}
	}

	function refreshMiniCart(miniCart) {
		const ajaxUrl = miniCart.dataset.ajaxUrl;
		const nonce = miniCart.dataset.nonce;
		const content = miniCart.querySelector('[data-mini-cart-content]');

		if (!content) return;

		fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'po_mini_cart_refresh',
				nonce: nonce
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				content.innerHTML = data.data.content_html;
				updateCounts(miniCart, data.data.cart_count);
				updateTotal(miniCart, data.data.cart_total);

				// Update footer visibility
				const footer = miniCart.querySelector('.po-mini-cart__footer');
				if (footer) {
					footer.style.display = data.data.cart_count > 0 ? 'block' : 'none';
				}
			}
		})
		.catch(error => {
			console.error('Refresh error:', error);
		});
	}

	// Expose functions globally for external use
	window.poMiniCart = {
		open: function() {
			const miniCart = document.querySelector('.po-mini-cart');
			if (miniCart) openDrawer(miniCart);
		},
		close: function() {
			const miniCart = document.querySelector('.po-mini-cart');
			if (miniCart) closeDrawer(miniCart);
		},
		refresh: function() {
			const miniCart = document.querySelector('.po-mini-cart');
			if (miniCart) refreshMiniCart(miniCart);
		}
	};
})();
