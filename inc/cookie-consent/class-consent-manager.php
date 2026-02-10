<?php
/**
 * ParkourONE Cookie Consent Manager
 *
 * DSGVO-konformes Consent Management System
 * - Opt-In vor dem Laden von Scripts (Prior Consent)
 * - Consent-Logging mit Audit-Trail
 * - Granulare Kategorien
 * - Google Consent Mode v2 Support
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class PO_Consent_Manager {

	/**
	 * Singleton Instance
	 */
	private static $instance = null;

	/**
	 * Consent Kategorien
	 */
	const CATEGORY_NECESSARY = 'necessary';
	const CATEGORY_FUNCTIONAL = 'functional';
	const CATEGORY_ANALYTICS = 'analytics';
	const CATEGORY_MARKETING = 'marketing';

	/**
	 * Cookie Name für Consent
	 */
	const CONSENT_COOKIE = 'po_consent';

	/**
	 * Cookie Laufzeit in Tagen
	 * DSGVO/EDPB: Max. 6-12 Monate empfohlen
	 */
	const CONSENT_EXPIRY_DAYS = 180;

	/**
	 * Versionsnummer für Consent (bei Änderung erneute Zustimmung nötig)
	 * WICHTIG: Bei Änderung der Rechtstexte oder Services Version erhöhen!
	 */
	const CONSENT_VERSION = '1.0';

	/**
	 * Rechtstext-Version (separat von Consent-Version)
	 * Bei Änderung der Datenschutzerklärung erhöhen
	 */
	const LEGAL_TEXT_VERSION = '2024-01';

	/**
	 * Cross-Domain: Alle ParkourONE Standorte
	 */
	const PARKOURONE_DOMAINS = [
		'parkourone.com',
		'schweiz.parkourone.com',
		'berlin.parkourone.com',
		'hannover.parkourone.com',
		'muenster.parkourone.com',
		'dresden.parkourone.com',
		'rheinruhr.parkourone.com',
		'augsburg.parkourone.com',
	];

	/**
	 * Registrierte Services
	 */
	private $services = [];

	/**
	 * Aktueller Consent Status
	 */
	private $current_consent = null;

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
		$this->load_consent_from_cookie();
		$this->register_default_services();

		add_action('init', [$this, 'init']);
		add_action('template_redirect', [$this, 'handle_nojs_consent']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 1);
		add_action('wp_head', [$this, 'output_consent_config'], 1);
		add_action('wp_footer', [$this, 'output_banner'], 100);
		add_action('wp_ajax_po_save_consent', [$this, 'ajax_save_consent']);
		add_action('wp_ajax_nopriv_po_save_consent', [$this, 'ajax_save_consent']);

		// Script-Filter für Blocking
		add_filter('script_loader_tag', [$this, 'filter_script_tag'], 10, 3);
	}

	/**
	 * Init
	 */
	public function init() {
		// Google Consent Mode initialisieren (muss vor allen anderen Scripts)
		if ($this->is_google_consent_mode_enabled()) {
			add_action('wp_head', [$this, 'output_google_consent_mode'], 0);
		}
	}

	/**
	 * Lade Consent aus Cookie
	 */
	private function load_consent_from_cookie() {
		if (isset($_COOKIE[self::CONSENT_COOKIE])) {
			// Use wp_unslash instead of sanitize_text_field to preserve base64 characters (+, /, =)
			$cookie_value = wp_unslash($_COOKIE[self::CONSENT_COOKIE]);
			$decoded = json_decode(base64_decode($cookie_value), true);

			if (!$decoded) {
				return;
			}

			// Neues kompaktes Format (v, c, t)
			if (isset($decoded['v']) && $decoded['v'] === self::CONSENT_VERSION) {
				$this->current_consent = [
					'version' => $decoded['v'],
					'timestamp' => $decoded['t'] ?? time(),
					'legal_version' => $decoded['l'] ?? self::LEGAL_TEXT_VERSION, // Legal version from cookie or current
					'categories' => [
						self::CATEGORY_NECESSARY => true,
						self::CATEGORY_FUNCTIONAL => !empty($decoded['c']['f']),
						self::CATEGORY_ANALYTICS => !empty($decoded['c']['a']),
						self::CATEGORY_MARKETING => !empty($decoded['c']['m']),
					],
				];
				return;
			}

			// Legacy-Format (version, categories) - für Übergangszeit
			if (isset($decoded['version']) && $decoded['version'] === self::CONSENT_VERSION) {
				$this->current_consent = $decoded;
			}
		}
	}

	/**
	 * Registriere Standard-Services
	 */
	private function register_default_services() {
		// Notwendige Cookies (immer aktiv, keine Zustimmung nötig)
		$this->register_service([
			'id' => 'wordpress',
			'name' => 'WordPress',
			'description' => 'Technisch notwendige Cookies für die Funktion der Website.',
			'category' => self::CATEGORY_NECESSARY,
			'cookies' => ['wordpress_*', 'wp-*', 'comment_*'],
			'required' => true,
		]);

		$this->register_service([
			'id' => 'po_consent',
			'name' => 'Cookie-Einstellungen',
			'description' => 'Speichert Ihre Cookie-Präferenzen.',
			'category' => self::CATEGORY_NECESSARY,
			'cookies' => ['po_consent'],
			'required' => true,
		]);
	}

	/**
	 * Service registrieren
	 */
	public function register_service($service) {
		$defaults = [
			'id' => '',
			'name' => '',
			'description' => '',
			'category' => self::CATEGORY_FUNCTIONAL,
			'cookies' => [],
			'scripts' => [],
			'required' => false,
			'default' => false,
			'privacy_policy_url' => '',
			'provider' => '',
			'country' => 'EU',
		];

		$service = wp_parse_args($service, $defaults);

		if (!empty($service['id'])) {
			$this->services[$service['id']] = $service;
		}
	}

	/**
	 * Alle Services abrufen
	 */
	public function get_services() {
		return apply_filters('po_consent_services', $this->services);
	}

	/**
	 * Services nach Kategorie gruppieren
	 */
	public function get_services_by_category() {
		$grouped = [
			self::CATEGORY_NECESSARY => [],
			self::CATEGORY_FUNCTIONAL => [],
			self::CATEGORY_ANALYTICS => [],
			self::CATEGORY_MARKETING => [],
		];

		foreach ($this->get_services() as $id => $service) {
			$category = $service['category'] ?? self::CATEGORY_FUNCTIONAL;
			$grouped[$category][$id] = $service;
		}

		return $grouped;
	}

	/**
	 * Kategorie-Informationen
	 */
	public function get_category_info($category = null) {
		$categories = [
			self::CATEGORY_NECESSARY => [
				'name' => 'Notwendig',
				'description' => 'Diese Cookies sind für die Grundfunktionen der Website erforderlich und können nicht deaktiviert werden.',
				'required' => true,
			],
			self::CATEGORY_FUNCTIONAL => [
				'name' => 'Funktional',
				'description' => 'Diese Cookies ermöglichen erweiterte Funktionen wie Videos, Karten und Chat-Widgets.',
				'required' => false,
			],
			self::CATEGORY_ANALYTICS => [
				'name' => 'Statistik',
				'description' => 'Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.',
				'required' => false,
			],
			self::CATEGORY_MARKETING => [
				'name' => 'Marketing',
				'description' => 'Diese Cookies werden verwendet, um Werbung relevanter für Sie zu gestalten.',
				'required' => false,
			],
		];

		if ($category !== null) {
			return $categories[$category] ?? null;
		}

		return $categories;
	}

	/**
	 * Prüfe ob Consent für Kategorie erteilt wurde
	 */
	public function has_consent($category) {
		// Notwendige Cookies immer erlaubt
		if ($category === self::CATEGORY_NECESSARY) {
			return true;
		}

		// Kein Consent = kein Zugriff (Opt-In!)
		if ($this->current_consent === null) {
			return false;
		}

		return !empty($this->current_consent['categories'][$category]);
	}

	/**
	 * Prüfe ob Consent für Service erteilt wurde
	 */
	public function has_service_consent($service_id) {
		$services = $this->get_services();

		if (!isset($services[$service_id])) {
			return false;
		}

		$service = $services[$service_id];

		// Required Services immer erlaubt
		if (!empty($service['required'])) {
			return true;
		}

		return $this->has_consent($service['category']);
	}

	/**
	 * Aktuellen Consent Status abrufen
	 */
	public function get_current_consent() {
		return $this->current_consent;
	}

	/**
	 * Google Consent Mode aktiviert?
	 */
	public function is_google_consent_mode_enabled() {
		$options = get_option('parkourone_consent', []);
		return !empty($options['google_consent_mode']);
	}

	/**
	 * Assets laden
	 */
	public function enqueue_assets() {
		// CSS
		wp_enqueue_style(
			'po-consent-banner',
			get_template_directory_uri() . '/assets/css/consent-banner.css',
			[],
			filemtime(get_template_directory() . '/assets/css/consent-banner.css')
		);

		// JavaScript
		wp_enqueue_script(
			'po-consent-manager',
			get_template_directory_uri() . '/assets/js/consent-manager.js',
			[],
			filemtime(get_template_directory() . '/assets/js/consent-manager.js'),
			false // Im Head laden für frühes Blocking
		);

		wp_localize_script('po-consent-manager', 'poConsentConfig', $this->get_js_config());
	}

	/**
	 * JavaScript Konfiguration
	 */
	private function get_js_config() {
		return [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('po_consent_nonce'),
			'consent' => $this->current_consent,
			'showBanner' => $this->should_show_banner(),
			'version' => self::CONSENT_VERSION,
			'cookieDomain' => $this->get_cross_domain_cookie_domain(),
			'categories' => $this->get_category_info(),
			'services' => $this->get_services_by_category(),
			'i18n' => [
				'bannerTitle' => __('Wir respektieren Ihre Privatsphäre', 'parkourone'),
				'bannerText' => __('Wir verwenden Cookies, um Ihre Erfahrung zu verbessern. Einige sind notwendig, andere helfen uns die Website zu optimieren.', 'parkourone'),
				'acceptAll' => __('Alle akzeptieren', 'parkourone'),
				'rejectAll' => __('Nur Notwendige', 'parkourone'),
				'settings' => __('Einstellungen', 'parkourone'),
				'save' => __('Auswahl speichern', 'parkourone'),
				'close' => __('Schließen', 'parkourone'),
				'moreInfo' => __('Mehr erfahren', 'parkourone'),
				'privacyPolicy' => __('Datenschutzerklärung', 'parkourone'),
				'imprint' => __('Impressum', 'parkourone'),
			],
			'links' => [
				'privacyPolicy' => get_privacy_policy_url() ?: '/datenschutz/',
				'imprint' => '/impressum/',
			],
		];
	}

	/**
	 * Consent Config im Head ausgeben (für Script-Blocking vor DOM-Ready)
	 */
	public function output_consent_config() {
		$consent_json = wp_json_encode($this->current_consent ?: new stdClass());
		?>
		<script>
		window.poConsent = <?php echo $consent_json; ?>;
		window.poConsentVersion = '<?php echo esc_js(self::CONSENT_VERSION); ?>';
		</script>
		<?php
	}

	/**
	 * Google Consent Mode v2 initialisieren
	 */
	public function output_google_consent_mode() {
		$analytics = $this->has_consent(self::CATEGORY_ANALYTICS) ? 'granted' : 'denied';
		$marketing = $this->has_consent(self::CATEGORY_MARKETING) ? 'granted' : 'denied';
		?>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('consent', 'default', {
			'ad_storage': '<?php echo esc_js($marketing); ?>',
			'ad_user_data': '<?php echo esc_js($marketing); ?>',
			'ad_personalization': '<?php echo esc_js($marketing); ?>',
			'analytics_storage': '<?php echo esc_js($analytics); ?>',
			'functionality_storage': 'granted',
			'personalization_storage': '<?php echo esc_js($marketing); ?>',
			'security_storage': 'granted',
			'wait_for_update': 500
		});
		gtag('set', 'ads_data_redaction', <?php echo $marketing === 'denied' ? 'true' : 'false'; ?>);
		gtag('set', 'url_passthrough', true);
		</script>
		<?php
	}

	/**
	 * Script Tags filtern für Consent-Blocking
	 */
	public function filter_script_tag($tag, $handle, $src) {
		// Scripts die blockiert werden sollen
		$blocked_scripts = apply_filters('po_consent_blocked_scripts', [
			// Analytics
			'google-analytics' => self::CATEGORY_ANALYTICS,
			'gtag' => self::CATEGORY_ANALYTICS,
			'plausible' => self::CATEGORY_ANALYTICS,
			'matomo' => self::CATEGORY_ANALYTICS,
			// Marketing
			'facebook-pixel' => self::CATEGORY_MARKETING,
			'google-ads' => self::CATEGORY_MARKETING,
			// Functional
			'google-maps' => self::CATEGORY_FUNCTIONAL,
			'youtube-embed' => self::CATEGORY_FUNCTIONAL,
		]);

		foreach ($blocked_scripts as $script_handle => $category) {
			if ($handle === $script_handle || strpos($handle, $script_handle) !== false) {
				if (!$this->has_consent($category)) {
					// Script deaktivieren und als data-src speichern
					$tag = str_replace(' src=', ' data-consent-category="' . esc_attr($category) . '" data-consent-src=', $tag);
					$tag = str_replace("type='text/javascript'", "type='text/plain'", $tag);
					$tag = str_replace('type="text/javascript"', 'type="text/plain"', $tag);
				}
			}
		}

		return $tag;
	}

	/**
	 * Banner HTML ausgeben
	 */
	public function output_banner() {
		if (is_admin()) {
			return;
		}

		// Kein Banner für Bots/Crawler
		if (function_exists('po_is_bot') && po_is_bot()) {
			return;
		}

		include get_template_directory() . '/inc/cookie-consent/templates/banner.php';
	}

	/**
	 * Prüfe ob Legal Text Version aktuell ist
	 * Bei Änderung der Datenschutzerklärung muss erneut zugestimmt werden
	 */
	public function is_legal_text_current() {
		if ($this->current_consent === null) {
			return false;
		}

		$consent_legal_version = $this->current_consent['legal_version'] ?? '0';
		return version_compare($consent_legal_version, self::LEGAL_TEXT_VERSION, '>=');
	}

	/**
	 * Prüfe ob Banner angezeigt werden soll (erweitert)
	 */
	public function should_show_banner() {
		// Kein Consent vorhanden
		if ($this->current_consent === null) {
			return true;
		}

		// Legal Text veraltet - erneute Zustimmung nötig
		if (!$this->is_legal_text_current()) {
			return true;
		}

		return false;
	}

	/**
	 * No-JS Fallback: Consent per POST-Formular speichern
	 * Wird ausgelöst wenn JavaScript deaktiviert ist
	 */
	public function handle_nojs_consent() {
		if (empty($_POST['po_consent_nojs_action'])) {
			return;
		}

		// Nonce prüfen (zwei mögliche Felder: Hauptansicht oder Einstellungen)
		$nonce_valid = false;
		if (!empty($_POST['po_consent_nojs_nonce']) && wp_verify_nonce($_POST['po_consent_nojs_nonce'], 'po_consent_nojs')) {
			$nonce_valid = true;
		}
		if (!empty($_POST['po_consent_nojs_nonce_settings']) && wp_verify_nonce($_POST['po_consent_nojs_nonce_settings'], 'po_consent_nojs')) {
			$nonce_valid = true;
		}

		if (!$nonce_valid) {
			return;
		}

		$action = sanitize_text_field($_POST['po_consent_nojs_action']);

		// Kategorien je nach Aktion bestimmen
		$sanitized_categories = [
			self::CATEGORY_NECESSARY => true,
			self::CATEGORY_FUNCTIONAL => false,
			self::CATEGORY_ANALYTICS => false,
			self::CATEGORY_MARKETING => false,
		];

		if ($action === 'accept-all') {
			$sanitized_categories[self::CATEGORY_FUNCTIONAL] = true;
			$sanitized_categories[self::CATEGORY_ANALYTICS] = true;
			$sanitized_categories[self::CATEGORY_MARKETING] = true;
		} elseif ($action === 'save-selection') {
			// Checkboxes aus dem Einstellungen-Formular auslesen
			$sanitized_categories[self::CATEGORY_FUNCTIONAL] = !empty($_POST['consent_functional']);
			$sanitized_categories[self::CATEGORY_ANALYTICS] = !empty($_POST['consent_analytics']);
			$sanitized_categories[self::CATEGORY_MARKETING] = !empty($_POST['consent_marketing']);
		}
		// reject-all: Standardwerte behalten (nur necessary)

		// Consent-ID generieren
		$consent_id = wp_generate_uuid4();

		// Cookie-Daten (kompakt)
		$cookie_data = [
			'v' => self::CONSENT_VERSION,
			'l' => self::LEGAL_TEXT_VERSION,
			'c' => [
				'n' => 1,
				'f' => $sanitized_categories[self::CATEGORY_FUNCTIONAL] ? 1 : 0,
				'a' => $sanitized_categories[self::CATEGORY_ANALYTICS] ? 1 : 0,
				'm' => $sanitized_categories[self::CATEGORY_MARKETING] ? 1 : 0,
			],
			't' => time(),
			'id' => substr($consent_id, 0, 8),
		];

		// Cookie setzen
		$cookie_value = base64_encode(wp_json_encode($cookie_data));
		$expiry = time() + (self::CONSENT_EXPIRY_DAYS * DAY_IN_SECONDS);
		$cookie_domain = $this->get_cross_domain_cookie_domain();

		setcookie(
			self::CONSENT_COOKIE,
			$cookie_value,
			[
				'expires' => $expiry,
				'path' => '/',
				'domain' => $cookie_domain,
				'secure' => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			]
		);

		// Audit-Log
		$user_id = is_user_logged_in() ? get_current_user_id() : null;
		$audit_data = [
			'consent_id' => $consent_id,
			'version' => self::CONSENT_VERSION,
			'legal_version' => self::LEGAL_TEXT_VERSION,
			'timestamp' => current_time('c'),
			'categories' => $sanitized_categories,
			'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
			'ip_hash' => hash('sha256', $this->get_anonymized_ip()),
			'user_id' => $user_id,
			'domain' => $_SERVER['HTTP_HOST'] ?? '',
			'dnt' => !empty($_SERVER['HTTP_DNT']),
			'gpc' => !empty($_SERVER['HTTP_SEC_GPC']),
		];

		$last_hash = $this->get_last_consent_hash();
		$audit_data['previous_hash'] = $last_hash;
		$audit_data['integrity'] = hash('sha256', wp_json_encode($audit_data) . wp_salt('auth'));

		$this->log_consent($audit_data);

		// Zurück zur gleichen Seite redirecten
		$redirect_path = !empty($_POST['po_consent_nojs_redirect'])
			? sanitize_text_field($_POST['po_consent_nojs_redirect'])
			: '/';

		// Absolute URL aus relativem Pfad erstellen und validieren
		$redirect = home_url($redirect_path);
		$redirect = wp_validate_redirect($redirect, home_url('/'));

		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * AJAX: Consent speichern
	 */
	public function ajax_save_consent() {
		check_ajax_referer('po_consent_nonce', 'nonce');

		$categories = isset($_POST['categories']) ? (array) $_POST['categories'] : [];

		// Sanitize
		$allowed_categories = [
			self::CATEGORY_NECESSARY,
			self::CATEGORY_FUNCTIONAL,
			self::CATEGORY_ANALYTICS,
			self::CATEGORY_MARKETING,
		];

		$sanitized_categories = [];
		foreach ($allowed_categories as $cat) {
			$sanitized_categories[$cat] = in_array($cat, $categories) || $cat === self::CATEGORY_NECESSARY;
		}

		// User ID für eingeloggte Benutzer (DSGVO Art. 15 - Auskunftsrecht)
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		// Consent-ID generieren
		$consent_id = wp_generate_uuid4();

		// MINIMALES Cookie-Objekt (nur was der Browser braucht)
		// Alle anderen Daten gehören in die Datenbank!
		$cookie_data = [
			'v' => self::CONSENT_VERSION,
			'l' => self::LEGAL_TEXT_VERSION, // Legal text version for re-consent check
			'c' => [
				'n' => 1, // necessary - immer true
				'f' => $sanitized_categories[self::CATEGORY_FUNCTIONAL] ? 1 : 0,
				'a' => $sanitized_categories[self::CATEGORY_ANALYTICS] ? 1 : 0,
				'm' => $sanitized_categories[self::CATEGORY_MARKETING] ? 1 : 0,
			],
			't' => time(),
			'id' => substr($consent_id, 0, 8), // Kurz-ID für Referenz
		];

		// VOLLSTÄNDIGES Audit-Objekt für Datenbank
		$audit_data = [
			'consent_id' => $consent_id,
			'version' => self::CONSENT_VERSION,
			'legal_version' => self::LEGAL_TEXT_VERSION,
			'timestamp' => current_time('c'),
			'categories' => $sanitized_categories,
			'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
			'ip_hash' => hash('sha256', $this->get_anonymized_ip()),
			'user_id' => $user_id,
			'domain' => $_SERVER['HTTP_HOST'] ?? '',
			'dnt' => !empty($_SERVER['HTTP_DNT']),
			'gpc' => !empty($_SERVER['HTTP_SEC_GPC']),
		];

		// Letzten Hash für Verkettung holen (Blockchain-ähnliche Integrität)
		$last_hash = $this->get_last_consent_hash();
		$audit_data['previous_hash'] = $last_hash;

		// Integrity Hash für Audit (inkl. vorherigem Hash = Kette)
		$audit_data['integrity'] = hash('sha256', wp_json_encode($audit_data) . wp_salt('auth'));

		// Cookie setzen - KOMPAKT (ca. 80-100 Bytes statt 800+)
		$cookie_value = base64_encode(wp_json_encode($cookie_data));
		$expiry = time() + (self::CONSENT_EXPIRY_DAYS * DAY_IN_SECONDS);

		// Cross-Domain Cookie für alle ParkourONE Standorte
		$cookie_domain = $this->get_cross_domain_cookie_domain();

		setcookie(
			self::CONSENT_COOKIE,
			$cookie_value,
			[
				'expires' => $expiry,
				'path' => '/',
				'domain' => $cookie_domain,
				'secure' => is_ssl(),
				'httponly' => false, // JS muss lesen können
				'samesite' => 'Lax',
			]
		);

		// In Datenbank loggen (für Audit-Trail) - mit vollständigen Daten
		$this->log_consent($audit_data);

		// Google Consent Mode Update zurückgeben
		$response = [
			'success' => true,
			'consent' => [
				'categories' => $sanitized_categories,
				'version' => self::CONSENT_VERSION,
			],
			'googleConsentUpdate' => [
				'ad_storage' => $sanitized_categories[self::CATEGORY_MARKETING] ? 'granted' : 'denied',
				'ad_user_data' => $sanitized_categories[self::CATEGORY_MARKETING] ? 'granted' : 'denied',
				'ad_personalization' => $sanitized_categories[self::CATEGORY_MARKETING] ? 'granted' : 'denied',
				'analytics_storage' => $sanitized_categories[self::CATEGORY_ANALYTICS] ? 'granted' : 'denied',
			],
		];

		wp_send_json_success($response);
	}

	/**
	 * Cross-Domain Cookie Domain ermitteln
	 * Erlaubt Consent-Sharing zwischen allen Standorten
	 */
	private function get_cross_domain_cookie_domain() {
		$host = $_SERVER['HTTP_HOST'] ?? '';

		// Prüfen ob es eine ParkourONE Domain ist
		foreach (self::PARKOURONE_DOMAINS as $domain) {
			if (strpos($host, 'parkourone.com') !== false) {
				// Haupt-Domain für alle Subdomains
				return '.parkourone.com';
			}
		}

		// Lokale Entwicklung oder andere Domain
		return COOKIE_DOMAIN ?: '';
	}

	/**
	 * Letzten Consent-Hash für Verkettung holen
	 */
	private function get_last_consent_hash() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'po_consent_log';

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			return 'genesis'; // Erster Eintrag
		}

		$last_hash = $wpdb->get_var("SELECT integrity_hash FROM $table_name ORDER BY id DESC LIMIT 1");
		return $last_hash ?: 'genesis';
	}

	/**
	 * IP anonymisieren (letztes Oktett entfernen)
	 */
	private function get_anonymized_ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

		// IPv4
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return preg_replace('/\.\d+$/', '.0', $ip);
		}

		// IPv6
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return preg_replace('/:[0-9a-f]+$/i', ':0000', $ip);
		}

		return '0.0.0.0';
	}

	/**
	 * Consent in Datenbank loggen
	 */
	private function log_consent($consent) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'po_consent_log';

		// Tabelle erstellen falls nicht vorhanden
		$this->maybe_create_log_table();

		$wpdb->insert(
			$table_name,
			[
				'consent_id' => wp_generate_uuid4(),
				'timestamp' => current_time('mysql'),
				'ip_hash' => $consent['ip_hash'],
				'user_id' => $consent['user_id'],
				'user_agent' => substr($consent['user_agent'], 0, 500),
				'consent_data' => wp_json_encode($consent['categories']),
				'consent_version' => $consent['version'],
				'legal_version' => $consent['legal_version'],
				'domain' => $consent['domain'],
				'dnt_enabled' => $consent['dnt'] ? 1 : 0,
				'gpc_enabled' => $consent['gpc'] ? 1 : 0,
				'previous_hash' => $consent['previous_hash'],
				'integrity_hash' => $consent['integrity'],
			],
			['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
		);
	}

	/**
	 * Log-Tabelle erstellen
	 */
	private function maybe_create_log_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'po_consent_log';

		// Prüfen ob Tabelle existiert und aktuell ist
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

		if (!$table_exists) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				consent_id varchar(36) NOT NULL,
				timestamp datetime NOT NULL,
				ip_hash varchar(64) NOT NULL,
				user_id bigint(20) DEFAULT NULL,
				user_agent varchar(500) DEFAULT '',
				consent_data text NOT NULL,
				consent_version varchar(10) NOT NULL,
				legal_version varchar(20) DEFAULT '',
				domain varchar(255) DEFAULT '',
				dnt_enabled tinyint(1) DEFAULT 0,
				gpc_enabled tinyint(1) DEFAULT 0,
				previous_hash varchar(64) DEFAULT 'genesis',
				integrity_hash varchar(64) NOT NULL,
				PRIMARY KEY (id),
				KEY consent_id (consent_id),
				KEY timestamp (timestamp),
				KEY ip_hash (ip_hash),
				KEY user_id (user_id),
				KEY integrity_chain (previous_hash, integrity_hash)
			) $charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}

	/**
	 * Consent-Daten für Benutzer exportieren (Art. 15 DSGVO - Auskunftsrecht)
	 */
	public static function export_user_consent_data($user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'po_consent_log';

		$consents = $wpdb->get_results($wpdb->prepare(
			"SELECT consent_id, timestamp, consent_data, consent_version, legal_version, domain
			 FROM $table_name
			 WHERE user_id = %d
			 ORDER BY timestamp DESC",
			$user_id
		), ARRAY_A);

		return [
			'user_id' => $user_id,
			'export_date' => current_time('c'),
			'consent_history' => $consents,
		];
	}

	/**
	 * Consent-Daten für Benutzer löschen (Art. 17 DSGVO - Recht auf Löschung)
	 */
	public static function delete_user_consent_data($user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'po_consent_log';

		// Anonymisieren statt löschen (für Audit-Trail-Integrität)
		$wpdb->update(
			$table_name,
			[
				'user_id' => null,
				'ip_hash' => 'DELETED',
				'user_agent' => 'DELETED',
			],
			['user_id' => $user_id],
			['%s', '%s', '%s'],
			['%d']
		);

		return true;
	}

	/**
	 * Audit-Log Integrität prüfen
	 */
	public static function verify_audit_log_integrity() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'po_consent_log';

		$entries = $wpdb->get_results(
			"SELECT id, previous_hash, integrity_hash FROM $table_name ORDER BY id ASC",
			ARRAY_A
		);

		$expected_previous = 'genesis';
		$broken_chain = [];

		foreach ($entries as $entry) {
			if ($entry['previous_hash'] !== $expected_previous) {
				$broken_chain[] = [
					'id' => $entry['id'],
					'expected' => $expected_previous,
					'actual' => $entry['previous_hash'],
				];
			}
			$expected_previous = $entry['integrity_hash'];
		}

		return [
			'valid' => empty($broken_chain),
			'total_entries' => count($entries),
			'broken_links' => $broken_chain,
		];
	}
}

/**
 * Helper Funktion für Template-Nutzung
 */
function po_has_consent($category) {
	return PO_Consent_Manager::get_instance()->has_consent($category);
}

/**
 * Helper für Service-Consent
 */
function po_has_service_consent($service_id) {
	return PO_Consent_Manager::get_instance()->has_service_consent($service_id);
}
