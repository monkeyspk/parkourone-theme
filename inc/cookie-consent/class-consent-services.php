<?php
/**
 * ParkourONE Consent Services
 *
 * Vordefinierte Services/Cookies für schnelle Konfiguration
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class PO_Consent_Services {

	/**
	 * Singleton Instance
	 */
	private static $instance = null;

	/**
	 * Get Singleton Instance
	 */
	public static function get_instance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action('init', [$this, 'register_services'], 5);
	}

	/**
	 * Register Services
	 */
	public function register_services() {
		$consent_manager = PO_Consent_Manager::get_instance();
		$options = get_option('parkourone_consent', []);
		$enabled_services = $options['enabled_services'] ?? [];

		// Alle verfügbaren Services
		$all_services = $this->get_available_services();

		// Nur aktivierte Services registrieren
		foreach ($enabled_services as $service_id) {
			if (isset($all_services[$service_id])) {
				$consent_manager->register_service($all_services[$service_id]);
			}
		}

		// Auch manuell aktivierte Services registrieren
		$this->register_auto_detected_services($consent_manager);
	}

	/**
	 * Alle verfügbaren Services
	 */
	public function get_available_services() {
		return [
			// ========================================
			// Analytics
			// ========================================
			'google_analytics' => [
				'id' => 'google_analytics',
				'name' => 'Google Analytics',
				'description' => 'Analysiert das Nutzerverhalten auf der Website für Statistikzwecke.',
				'category' => PO_Consent_Manager::CATEGORY_ANALYTICS,
				'cookies' => ['_ga', '_gid', '_gat', '_ga_*'],
				'cookie_duration' => '2 Jahre (_ga), 24 Stunden (_gid)',
				'legal_basis' => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)',
				'scripts' => ['google-analytics.com', 'googletagmanager.com'],
				'provider' => 'Google LLC',
				'country' => 'USA',
				'third_country_transfer' => 'Datenübermittlung in die USA auf Basis von EU-Standardvertragsklauseln (SCCs)',
				'privacy_policy_url' => 'https://policies.google.com/privacy',
			],

			'plausible' => [
				'id' => 'plausible',
				'name' => 'Plausible Analytics',
				'description' => 'Datenschutzfreundliche Webanalyse ohne Cookies.',
				'category' => PO_Consent_Manager::CATEGORY_ANALYTICS,
				'cookies' => [],
				'scripts' => ['plausible.io'],
				'provider' => 'Plausible Insights OÜ',
				'country' => 'EU',
				'privacy_policy_url' => 'https://plausible.io/privacy',
			],

			'matomo' => [
				'id' => 'matomo',
				'name' => 'Matomo',
				'description' => 'Open-Source Webanalyse (selbst-gehostet).',
				'category' => PO_Consent_Manager::CATEGORY_ANALYTICS,
				'cookies' => ['_pk_id', '_pk_ses', '_pk_ref'],
				'provider' => 'Selbst gehostet',
				'country' => 'EU',
			],

			// ========================================
			// Marketing
			// ========================================
			'mailerlite' => [
				'id' => 'mailerlite',
				'name' => 'MailerLite',
				'description' => 'Newsletter-Anmeldung und E-Mail-Marketing.',
				'category' => PO_Consent_Manager::CATEGORY_MARKETING,
				'cookies' => ['mailerlite_*'],
				'cookie_duration' => '1 Jahr',
				'legal_basis' => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)',
				'scripts' => ['mlcdn.com', 'mailerlite.com'],
				'provider' => 'MailerLite Limited',
				'country' => 'EU',
				'privacy_policy_url' => 'https://www.mailerlite.com/legal/privacy-policy',
			],

			'facebook_pixel' => [
				'id' => 'facebook_pixel',
				'name' => 'Facebook Pixel',
				'description' => 'Ermöglicht Werbung auf Facebook und Instagram.',
				'category' => PO_Consent_Manager::CATEGORY_MARKETING,
				'cookies' => ['_fbp', 'fr'],
				'scripts' => ['facebook.net', 'connect.facebook.com'],
				'provider' => 'Meta Platforms Inc.',
				'country' => 'USA',
				'privacy_policy_url' => 'https://www.facebook.com/privacy/policy',
			],

			'google_ads' => [
				'id' => 'google_ads',
				'name' => 'Google Ads',
				'description' => 'Ermöglicht personalisierte Werbung über Google.',
				'category' => PO_Consent_Manager::CATEGORY_MARKETING,
				'cookies' => ['_gcl_*', 'IDE', 'ANID'],
				'scripts' => ['googleads.g.doubleclick.net', 'googlesyndication.com'],
				'provider' => 'Google LLC',
				'country' => 'USA',
				'privacy_policy_url' => 'https://policies.google.com/privacy',
			],

			// ========================================
			// Functional
			// ========================================
			'youtube' => [
				'id' => 'youtube',
				'name' => 'YouTube Videos',
				'description' => 'Ermöglicht die Einbettung von YouTube-Videos.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => ['VISITOR_INFO1_LIVE', 'YSC', 'PREF'],
				'cookie_duration' => '6 Monate (VISITOR_INFO1_LIVE), Session (YSC)',
				'legal_basis' => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)',
				'scripts' => ['youtube.com', 'youtube-nocookie.com'],
				'provider' => 'Google LLC',
				'country' => 'USA',
				'third_country_transfer' => 'Datenübermittlung in die USA auf Basis von EU-Standardvertragsklauseln (SCCs)',
				'privacy_policy_url' => 'https://policies.google.com/privacy',
			],

			'vimeo' => [
				'id' => 'vimeo',
				'name' => 'Vimeo Videos',
				'description' => 'Ermöglicht die Einbettung von Vimeo-Videos.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => ['vuid', 'player'],
				'scripts' => ['player.vimeo.com'],
				'provider' => 'Vimeo, Inc.',
				'country' => 'USA',
				'privacy_policy_url' => 'https://vimeo.com/privacy',
			],

			'google_maps' => [
				'id' => 'google_maps',
				'name' => 'Google Maps',
				'description' => 'Zeigt interaktive Karten und Standorte an.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => ['NID', 'OGPC', 'CONSENT'],
				'scripts' => ['maps.googleapis.com', 'maps.google.com'],
				'provider' => 'Google LLC',
				'country' => 'USA',
				'privacy_policy_url' => 'https://policies.google.com/privacy',
			],

			'openstreetmap' => [
				'id' => 'openstreetmap',
				'name' => 'OpenStreetMap',
				'description' => 'Zeigt Karten von OpenStreetMap an.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => [],
				'scripts' => ['openstreetmap.org', 'tile.osm.org'],
				'provider' => 'OpenStreetMap Foundation',
				'country' => 'EU',
				'privacy_policy_url' => 'https://wiki.osmfoundation.org/wiki/Privacy_Policy',
			],

			'google_fonts' => [
				'id' => 'google_fonts',
				'name' => 'Google Fonts',
				'description' => 'Lädt Schriftarten von Google-Servern.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => [],
				'scripts' => ['fonts.googleapis.com', 'fonts.gstatic.com'],
				'provider' => 'Google LLC',
				'country' => 'USA',
				'privacy_policy_url' => 'https://policies.google.com/privacy',
			],

			// ========================================
			// Chat / Support
			// ========================================
			'tidio' => [
				'id' => 'tidio',
				'name' => 'Tidio Chat',
				'description' => 'Live-Chat-Widget für Kundensupport.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => ['tidio_*'],
				'scripts' => ['widget.tidio.co'],
				'provider' => 'Tidio Ltd.',
				'country' => 'EU',
				'privacy_policy_url' => 'https://www.tidio.com/privacy-policy/',
			],

			'crisp' => [
				'id' => 'crisp',
				'name' => 'Crisp Chat',
				'description' => 'Live-Chat-Widget für Kundensupport.',
				'category' => PO_Consent_Manager::CATEGORY_FUNCTIONAL,
				'cookies' => ['crisp-*'],
				'scripts' => ['client.crisp.chat'],
				'provider' => 'Crisp IM SAS',
				'country' => 'EU',
				'privacy_policy_url' => 'https://crisp.chat/en/privacy/',
			],
		];
	}

	/**
	 * Auto-detect and register services based on plugins/theme
	 */
	private function register_auto_detected_services($consent_manager) {
		$footer_options = get_option('parkourone_footer', []);

		// MailerLite im Footer?
		if (!empty($footer_options['newsletter_embed']) && strpos($footer_options['newsletter_embed'], 'mailerlite') !== false) {
			$services = $this->get_available_services();
			if (isset($services['mailerlite'])) {
				$consent_manager->register_service($services['mailerlite']);
			}
		}

		// YouTube in Content?
		// Das prüfen wir per JavaScript

		// Weitere Auto-Detection kann hier hinzugefügt werden
	}

	/**
	 * Get services grouped by category
	 */
	public function get_services_by_category() {
		$services = $this->get_available_services();
		$grouped = [
			PO_Consent_Manager::CATEGORY_ANALYTICS => [],
			PO_Consent_Manager::CATEGORY_MARKETING => [],
			PO_Consent_Manager::CATEGORY_FUNCTIONAL => [],
		];

		foreach ($services as $id => $service) {
			$category = $service['category'];
			if (isset($grouped[$category])) {
				$grouped[$category][$id] = $service;
			}
		}

		return $grouped;
	}
}

// Initialize
PO_Consent_Services::get_instance();
