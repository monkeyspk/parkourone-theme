<?php
/**
 * ParkourONE Cookie Consent System
 *
 * DSGVO-konformes Consent Management
 * Initialisiert alle Consent-Klassen
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

// Klassen laden
require_once __DIR__ . '/class-consent-manager.php';
require_once __DIR__ . '/class-consent-services.php';

// Admin nur im Backend laden
if (is_admin()) {
	require_once __DIR__ . '/class-consent-admin.php';
}

/**
 * Consent Manager initialisieren
 */
function po_init_consent_manager() {
	// Manager initialisieren (Singleton)
	PO_Consent_Manager::get_instance();
}
add_action('plugins_loaded', 'po_init_consent_manager', 5);

/**
 * Cookie-Einstellungen Link zum Footer hinzufügen
 */
function po_add_consent_link_to_footer($cookies_url) {
	// Wenn der Link auf #cookies zeigt, JavaScript-Trigger verwenden
	if (strpos($cookies_url, '#cookies') !== false) {
		return '#';
	}
	return $cookies_url;
}

/**
 * JavaScript für Footer Cookie-Link hinzufügen
 */
function po_consent_footer_script() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Footer Cookie-Link zum Öffnen der Einstellungen verwenden
		var cookieLinks = document.querySelectorAll('.po-footer__legal a[href*="cookies"]');
		cookieLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				if (window.POConsentManager) {
					window.POConsentManager.showSettings();
				}
			});
		});
	});
	</script>
	<?php
}
add_action('wp_footer', 'po_consent_footer_script', 99);

/**
 * Placeholder CSS für geblockte Inhalte
 */
function po_consent_placeholder_styles() {
	?>
	<style>
	.po-consent-placeholder {
		display: flex;
		align-items: center;
		justify-content: center;
		min-height: 200px;
		background: #f5f5f5;
		border: 2px dashed #ddd;
		padding: 2rem;
		text-align: center;
		font-family: inherit;
	}
	.po-consent-placeholder__content {
		max-width: 400px;
	}
	.po-consent-placeholder__text {
		margin: 0 0 1rem;
		color: #666;
		font-size: 0.9375rem;
	}
	.po-consent-placeholder__btn {
		display: inline-block;
		padding: 0.75rem 1.5rem;
		background: #000;
		color: #fff;
		border: none;
		cursor: pointer;
		font-size: 0.875rem;
		margin-right: 0.5rem;
		transition: opacity 0.2s;
	}
	.po-consent-placeholder__btn:hover {
		opacity: 0.8;
	}
	.po-consent-placeholder__settings {
		display: inline-block;
		padding: 0.75rem 1.5rem;
		background: transparent;
		color: #666;
		border: 1px solid #ccc;
		cursor: pointer;
		font-size: 0.875rem;
		transition: all 0.2s;
	}
	.po-consent-placeholder__settings:hover {
		border-color: #000;
		color: #000;
	}
	</style>
	<?php
}
add_action('wp_head', 'po_consent_placeholder_styles', 100);

/**
 * Filter für YouTube Embeds
 */
function po_consent_filter_youtube_embeds($html, $url, $attr) {
	// Prüfen ob Consent vorhanden
	if (!function_exists('po_has_consent') || po_has_consent('functional')) {
		return $html;
	}

	// YouTube URLs erkennen
	if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
		$placeholder = sprintf(
			'<div class="po-consent-placeholder" data-consent-category="functional" data-original-url="%s">
				<div class="po-consent-placeholder__content">
					<p class="po-consent-placeholder__text">Dieses Video benötigt Ihre Zustimmung für funktionale Cookies.</p>
					<button class="po-consent-placeholder__btn" data-consent-accept="functional">Video aktivieren</button>
					<button class="po-consent-placeholder__settings" data-consent-action="show-settings">Cookie-Einstellungen</button>
				</div>
			</div>',
			esc_attr($url)
		);
		return $placeholder;
	}

	return $html;
}
add_filter('embed_oembed_html', 'po_consent_filter_youtube_embeds', 10, 3);

/**
 * Filter für Vimeo Embeds
 */
function po_consent_filter_vimeo_embeds($html, $url, $attr) {
	// Prüfen ob Consent vorhanden
	if (!function_exists('po_has_consent') || po_has_consent('functional')) {
		return $html;
	}

	// Vimeo URLs erkennen
	if (strpos($url, 'vimeo.com') !== false) {
		$placeholder = sprintf(
			'<div class="po-consent-placeholder" data-consent-category="functional" data-original-url="%s">
				<div class="po-consent-placeholder__content">
					<p class="po-consent-placeholder__text">Dieses Video benötigt Ihre Zustimmung für funktionale Cookies.</p>
					<button class="po-consent-placeholder__btn" data-consent-accept="functional">Video aktivieren</button>
					<button class="po-consent-placeholder__settings" data-consent-action="show-settings">Cookie-Einstellungen</button>
				</div>
			</div>',
			esc_attr($url)
		);
		return $placeholder;
	}

	return $html;
}
add_filter('embed_oembed_html', 'po_consent_filter_vimeo_embeds', 10, 3);
