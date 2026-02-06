<?php
/**
 * ParkourONE Consent - Early Loader
 *
 * MU-Plugin für frühestmögliches Cookie-Blocking
 * Lädt VOR allen anderen Plugins und dem Theme
 *
 * INSTALLATION:
 * Kopieren nach: wp-content/mu-plugins/parkourone-consent-early.php
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Early Consent Manager
 * Blockiert Cookies/Sessions bevor WordPress sie setzt
 */
class PO_Consent_Early {

	const CONSENT_COOKIE = 'po_consent';
	const CONSENT_VERSION = '1.0';

	private $consent = null;
	private $is_bot = false;

	public function __construct() {
		// Bot-Detection zuerst
		$this->detect_bot();

		// Consent aus Cookie laden
		$this->load_consent();

		// DNT/GPC respektieren
		$this->check_privacy_signals();

		// Server-Side Blocking
		if (!$this->is_bot) {
			$this->block_server_cookies();
		}
	}

	/**
	 * Bot/Crawler erkennen
	 * Bots brauchen keinen Consent-Banner
	 */
	private function detect_bot() {
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$bot_patterns = [
			'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
			'yandexbot', 'sogou', 'exabot', 'facebot', 'facebookexternalhit',
			'ia_archiver', 'alexabot', 'mj12bot', 'ahrefsbot', 'semrushbot',
			'dotbot', 'rogerbot', 'screaming frog', 'uptimerobot', 'pingdom',
			'pagespeed', 'lighthouse', 'gtmetrix', 'headlesschrome', 'phantomjs',
			'curl', 'wget', 'python-requests', 'apache-httpclient', 'java/',
		];

		$user_agent_lower = strtolower($user_agent);
		foreach ($bot_patterns as $pattern) {
			if (strpos($user_agent_lower, $pattern) !== false) {
				$this->is_bot = true;
				break;
			}
		}

		// Auch prüfen: Kein User-Agent = wahrscheinlich Bot
		if (empty($user_agent)) {
			$this->is_bot = true;
		}
	}

	/**
	 * Consent aus Cookie laden
	 */
	private function load_consent() {
		if (isset($_COOKIE[self::CONSENT_COOKIE])) {
			$decoded = json_decode(base64_decode($_COOKIE[self::CONSENT_COOKIE]), true);
			if ($decoded && isset($decoded['version']) && $decoded['version'] === self::CONSENT_VERSION) {
				$this->consent = $decoded;
			}
		}
	}

	/**
	 * DNT (Do Not Track) und GPC (Global Privacy Control) prüfen
	 */
	private function check_privacy_signals() {
		// Do Not Track Header
		$dnt = $_SERVER['HTTP_DNT'] ?? null;

		// Global Privacy Control (neuer Standard)
		$gpc = $_SERVER['HTTP_SEC_GPC'] ?? null;

		// Wenn DNT oder GPC aktiv und kein expliziter Consent
		if (($dnt === '1' || $gpc === '1') && $this->consent === null) {
			// Automatisch nur notwendige Cookies erlauben
			$this->consent = [
				'version' => self::CONSENT_VERSION,
				'categories' => [
					'necessary' => true,
					'functional' => false,
					'analytics' => false,
					'marketing' => false,
				],
				'source' => $gpc === '1' ? 'gpc' : 'dnt',
			];
		}
	}

	/**
	 * Server-Side Cookie Blocking
	 */
	private function block_server_cookies() {
		// PHP Session erst starten wenn Consent vorhanden
		if (!$this->has_consent('functional')) {
			// Session-Start verhindern
			ini_set('session.use_cookies', '0');
			ini_set('session.use_only_cookies', '0');

			// Bereits gesetzte Session-Cookies entfernen
			if (isset($_COOKIE['PHPSESSID'])) {
				setcookie('PHPSESSID', '', time() - 3600, '/');
				unset($_COOKIE['PHPSESSID']);
			}
		}

		// WordPress Comment Cookie blockieren wenn kein Consent
		if (!$this->has_consent('functional')) {
			add_filter('comment_cookie_lifetime', '__return_zero');
		}

		// Output Buffer für Header-Manipulation
		add_action('send_headers', [$this, 'filter_response_headers'], 1);
	}

	/**
	 * Response Headers filtern
	 */
	public function filter_response_headers() {
		// Set-Cookie Headers entfernen für nicht-notwendige Cookies
		if (!$this->has_consent('analytics') || !$this->has_consent('marketing')) {
			// PHP kann gesendete Header nicht gut filtern
			// Aber wir können verhindern dass neue gesetzt werden
			header_remove('Set-Cookie');
		}

		// Security Headers hinzufügen
		if (!headers_sent()) {
			// Referrer Policy - weniger Daten an Dritte
			header('Referrer-Policy: strict-origin-when-cross-origin');

			// Permissions Policy - Fingerprinting einschränken
			header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
		}
	}

	/**
	 * Consent prüfen
	 */
	public function has_consent($category) {
		if ($category === 'necessary') {
			return true;
		}

		if ($this->consent === null) {
			return false;
		}

		return !empty($this->consent['categories'][$category]);
	}

	/**
	 * Ist Bot?
	 */
	public function is_bot() {
		return $this->is_bot;
	}

	/**
	 * Privacy Signal aktiv?
	 */
	public function has_privacy_signal() {
		return isset($this->consent['source']) &&
			   in_array($this->consent['source'], ['dnt', 'gpc']);
	}
}

// Global verfügbar machen
global $po_consent_early;
$po_consent_early = new PO_Consent_Early();

/**
 * Helper: Ist aktueller Request ein Bot?
 */
function po_is_bot() {
	global $po_consent_early;
	return $po_consent_early ? $po_consent_early->is_bot() : false;
}

/**
 * Helper: Hat User Privacy Signal gesendet?
 */
function po_has_privacy_signal() {
	global $po_consent_early;
	return $po_consent_early ? $po_consent_early->has_privacy_signal() : false;
}
