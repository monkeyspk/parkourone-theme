/**
 * Modern Cart Block - Frontend JavaScript
 * Handles AJAX updates for quantity and item removal
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const carts = document.querySelectorAll('.po-modern-cart');

		carts.forEach(function(cart) {
			initCart(cart);
		});
	});

	function initCart(cart) {
		const ajaxUrl = cart.dataset.ajaxUrl;
		const nonce = cart.dataset.nonce;

		// Quantity buttons
		cart.addEventListener('click', function(e) {
			const btn = e.target.closest('[data-action]');
			if (!btn) return;

			const action = btn.dataset.action;
			const item = btn.closest('[data-cart-item-key]');

			if (!item) return;

			const cartItemKey = item.dataset.cartItemKey;

			if (action === 'increase' || action === 'decrease') {
				handleQuantityChange(cart, item, cartItemKey, action, ajaxUrl, nonce);
			} else if (action === 'remove') {
				handleRemoveItem(cart, item, cartItemKey, ajaxUrl, nonce);
			}
		});

		// Coupon toggle
		const couponToggle = cart.querySelector('[data-coupon-toggle]');
		const couponForm = cart.querySelector('[data-coupon-form]');

		if (couponToggle && couponForm) {
			couponToggle.addEventListener('click', function() {
				const isVisible = couponForm.style.display !== 'none';
				couponForm.style.display = isVisible ? 'none' : 'flex';
			});
		}

		// Coupon apply
		const couponApply = cart.querySelector('[data-coupon-apply]');
		const couponInput = cart.querySelector('[data-coupon-input]');

		if (couponApply && couponInput) {
			couponApply.addEventListener('click', function() {
				handleApplyCoupon(cart, couponInput.value.trim(), ajaxUrl, nonce);
			});

			couponInput.addEventListener('keypress', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					handleApplyCoupon(cart, couponInput.value.trim(), ajaxUrl, nonce);
				}
			});
		}
	}

	function handleQuantityChange(cart, item, cartItemKey, action, ajaxUrl, nonce) {
		const qtyElement = item.querySelector('[data-quantity]');
		if (!qtyElement) return;

		let quantity = parseInt(qtyElement.textContent, 10) || 1;

		if (action === 'increase') {
			quantity++;
		} else if (action === 'decrease' && quantity > 1) {
			quantity--;
		} else if (action === 'decrease' && quantity <= 1) {
			// Remove item if quantity would be 0
			handleRemoveItem(cart, item, cartItemKey, ajaxUrl, nonce);
			return;
		}

		// Update UI immediately
		qtyElement.textContent = quantity;
		item.classList.add('is-updating');

		// Send AJAX request
		fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'po_modern_cart_update',
				nonce: nonce,
				cart_item_key: cartItemKey,
				quantity: quantity
			})
		})
		.then(response => response.json())
		.then(data => {
			item.classList.remove('is-updating');

			if (data.success) {
				// Update subtotal
				const subtotalEl = item.querySelector('[data-subtotal]');
				if (subtotalEl && data.data.item_subtotal) {
					subtotalEl.innerHTML = data.data.item_subtotal;
				}

				// Update cart totals
				updateCartTotals(cart, data.data);

				// Update quantity buttons state
				updateQuantityButtons(item, quantity, data.data.max_qty);
			} else {
				// Revert quantity on error
				qtyElement.textContent = data.data.current_quantity || quantity;
			}
		})
		.catch(error => {
			console.error('Cart update error:', error);
			item.classList.remove('is-updating');
		});
	}

	function handleRemoveItem(cart, item, cartItemKey, ajaxUrl, nonce) {
		item.classList.add('is-removing');

		fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'po_modern_cart_remove',
				nonce: nonce,
				cart_item_key: cartItemKey
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Remove item from DOM with animation
				setTimeout(() => {
					item.remove();

					// Check if cart is empty
					const remainingItems = cart.querySelectorAll('[data-cart-item-key]');
					if (remainingItems.length === 0) {
						// Reload page to show empty cart state
						location.reload();
					} else {
						// Update cart totals
						updateCartTotals(cart, data.data);
						updateCartCount(cart, data.data.cart_count);
					}
				}, 300);
			} else {
				item.classList.remove('is-removing');
			}
		})
		.catch(error => {
			console.error('Cart remove error:', error);
			item.classList.remove('is-removing');
		});
	}

	function handleApplyCoupon(cart, couponCode, ajaxUrl, nonce) {
		if (!couponCode) return;

		showLoading(cart, true);

		fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'po_modern_cart_coupon',
				nonce: nonce,
				coupon_code: couponCode
			})
		})
		.then(response => response.json())
		.then(data => {
			showLoading(cart, false);

			if (data.success) {
				// Reload to show updated totals with coupon
				location.reload();
			} else {
				alert(data.data.message || 'Gutscheincode konnte nicht angewendet werden.');
			}
		})
		.catch(error => {
			console.error('Coupon error:', error);
			showLoading(cart, false);
		});
	}

	function updateCartTotals(cart, data) {
		if (data.cart_subtotal) {
			const subtotalEl = cart.querySelector('[data-cart-subtotal]');
			if (subtotalEl) subtotalEl.innerHTML = data.cart_subtotal;
		}

		if (data.cart_total) {
			const totalEl = cart.querySelector('[data-cart-total]');
			if (totalEl) totalEl.innerHTML = data.cart_total;
		}
	}

	function updateCartCount(cart, count) {
		const countEl = cart.querySelector('.po-modern-cart__count');
		if (countEl) {
			countEl.textContent = count + ' ' + (count === 1 ? 'Artikel' : 'Artikel');
		}

		// Also update header cart count if exists
		const headerCount = document.querySelector('.po-header__cart-count');
		if (headerCount) {
			headerCount.textContent = count;
			headerCount.dataset.cartCount = count;
		}
	}

	function updateQuantityButtons(item, quantity, maxQty) {
		const decreaseBtn = item.querySelector('[data-action="decrease"]');
		const increaseBtn = item.querySelector('[data-action="increase"]');

		if (decreaseBtn) {
			decreaseBtn.disabled = quantity <= 1;
		}

		if (increaseBtn && maxQty > 0) {
			increaseBtn.disabled = quantity >= maxQty;
		}
	}

	function showLoading(cart, show) {
		const loading = cart.querySelector('[data-loading]');
		if (loading) {
			loading.style.display = show ? 'flex' : 'none';
		}
	}
})();
