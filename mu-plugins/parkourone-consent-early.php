<?php
/**
 * ParkourONE Consent - Early Loader
 *
 * MU-Plugin für frühestmögliches Cookie-Blocking
 * Lädt VOR allen anderen Plugins und dem Theme
 *
 * Features:
 * - Server-Side Cookie Blocking
 * - Output Buffer Blocking (fängt ALLE Scripts/Styles im HTML ab)
 * - Cookie Header Filtering
 * - Security Headers
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

	/**
	 * URL-Patterns für externe Scripts/Styles die blockiert werden sollen
	 */
	private $blocked_url_patterns = [
		// Analytics
		'google-analytics\.com'      => 'analytics',
		'googletagmanager\.com'      => 'analytics',
		'sourcebuster'               => 'analytics',
		'sbjs'                       => 'analytics',
		'plausible\.io'              => 'analytics',
		'matomo'                     => 'analytics',
		// Marketing
		'facebook\.net'              => 'marketing',
		'connect\.facebook'          => 'marketing',
		'fbevents'                   => 'marketing',
		'doubleclick\.net'           => 'marketing',
		'googlesyndication'          => 'marketing',
		'googleads'                  => 'marketing',
		'google\.com/ccm'            => 'marketing',
		'capi-automation'            => 'marketing',
		'clientParamBuilder'         => 'marketing',
		'mailerlite'                 => 'marketing',
		'mlcdn'                      => 'marketing',
		// Functional
		'typekit\.net'               => 'functional',
		'fonts\.adobe\.com'          => 'functional',
	];

	/**
	 * Inline Script Patterns die blockiert werden sollen
	 */
	private $blocked_inline_patterns = [
		// Analytics
		'gtag\s*\(\s*[\'"](?:js|config|event)[\'"]\s*,' => 'analytics',
		'GoogleAnalyticsObject'                          => 'analytics',
		'_gaq\.push'                                     => 'analytics',
		// Marketing
		'fbq\s*\('                                       => 'marketing',
		'_fbq\s*='                                       => 'marketing',
	];

	/**
	 * Resource Hint Domains die blockiert werden sollen
	 */
	private $blocked_hint_domains = [
		'googletagmanager.com'   => 'analytics',
		'google-analytics.com'   => 'analytics',
		'www.google-analytics.com' => 'analytics',
		'typekit.net'            => 'functional',
		'fonts.adobe.com'        => 'functional',
		'p.typekit.net'          => 'functional',
		'use.typekit.net'        => 'functional',
		'facebook.net'           => 'marketing',
		'connect.facebook.net'   => 'marketing',
		'doubleclick.net'        => 'marketing',
	];

	/**
	 * Cookie-Prefixes die ohne Consent blockiert werden
	 */
	private $blocked_cookie_prefixes = [
		'analytics' => ['_ga', '_gid', '_gat'],
		'marketing' => ['_fbp', '_fbc', 'IDE', '_gcl'],
		'functional' => ['wpcw_', 'sbjs_'],
	];

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

		// Output Buffer Blocking (nur Frontend, nicht Admin/AJAX/Cron/REST)
		add_action('template_redirect', [$this, 'start_output_buffer'], 0);
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
			if (!$decoded) {
				return;
			}

			// Neues kompaktes Format (v, c)
			if (isset($decoded['v']) && $decoded['v'] === self::CONSENT_VERSION) {
				$this->consent = [
					'version' => $decoded['v'],
					'categories' => [
						'necessary' => true,
						'functional' => !empty($decoded['c']['f']),
						'analytics' => !empty($decoded['c']['a']),
						'marketing' => !empty($decoded['c']['m']),
					],
				];
				return;
			}

			// Legacy-Format (version, categories)
			if (isset($decoded['version']) && $decoded['version'] === self::CONSENT_VERSION) {
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
	 * Output Buffer starten (template_redirect, Priority 0)
	 * Fängt ALLE Scripts/Styles im finalen HTML ab — unabhängig davon
	 * ob sie via wp_enqueue_script, wp_head Action oder Plugin-Output eingefügt wurden.
	 */
	public function start_output_buffer() {
		// Nur im Frontend
		if (is_admin()) {
			return;
		}

		// Nicht bei AJAX, Cron, REST
		if (wp_doing_ajax() || wp_doing_cron()) {
			return;
		}
		if (defined('REST_REQUEST') && REST_REQUEST) {
			return;
		}

		ob_start([$this, 'process_output_buffer']);
	}

	/**
	 * Output Buffer Callback — HTML-weites Blocking
	 *
	 * Wird aufgerufen wenn der Buffer geflushed wird (am Ende der Seite).
	 * Durchsucht das gesamte HTML und blockiert:
	 * A1. Externe <script src="..."> Tags
	 * A2. Inline <script> Tags mit Tracking-Code
	 * A3. Resource Hints (<link rel="preconnect|dns-prefetch">)
	 * A4. Stylesheets (<link rel="stylesheet">)
	 */
	public function process_output_buffer($html) {
		if (empty($html)) {
			return $html;
		}

		// Nur HTML-Responses verarbeiten (kein JSON, XML, etc.)
		if (stripos($html, '<html') === false && stripos($html, '<!DOCTYPE') === false) {
			return $html;
		}

		// A1: Externe Script Tags blockieren
		$html = $this->block_external_scripts($html);

		// A2: Inline Script Tags blockieren
		$html = $this->block_inline_scripts($html);

		// A3: Resource Hints blockieren
		$html = $this->block_resource_hints($html);

		// A4: Stylesheets blockieren
		$html = $this->block_stylesheets($html);

		return $html;
	}

	/**
	 * A1: Externe <script src="..."> Tags blockieren
	 */
	private function block_external_scripts($html) {
		return preg_replace_callback(
			'/<script\b([^>]*)\bsrc\s*=\s*["\']([^"\']+)["\']([^>]*)>([\s\S]*?)<\/script>/i',
			function ($match) {
				$before_src = $match[1];
				$src = $match[2];
				$after_src = $match[3];
				$content = $match[4];
				$full_tag = $match[0];

				// Bereits blockiert oder eigenes Script → überspringen
				if (strpos($full_tag, 'data-consent-manager') !== false) {
					return $full_tag;
				}
				if (strpos($full_tag, 'data-consent-src') !== false) {
					return $full_tag;
				}

				// application/ld+json → überspringen (SEO Structured Data)
				if (preg_match('/type\s*=\s*["\']application\/ld\+json["\']/i', $full_tag)) {
					return $full_tag;
				}

				// Prüfe src gegen blockierte URL-Patterns
				$category = $this->get_blocked_category_for_url($src);
				if ($category === null) {
					return $full_tag;
				}

				// Consent vorhanden → nicht blockieren
				if ($this->has_consent($category)) {
					return $full_tag;
				}

				// Script blockieren: src → data-consent-src, type → text/plain
				$attrs = $before_src . $after_src;

				// Bestehenden type entfernen
				$attrs = preg_replace('/\btype\s*=\s*["\'][^"\']*["\']/i', '', $attrs);

				return '<script type="text/plain" data-consent-category="' . esc_attr($category) . '" data-consent-src="' . esc_attr($src) . '"' . $attrs . '>' . $content . '</script>';
			},
			$html
		);
	}

	/**
	 * A2: Inline <script> Tags blockieren (ohne src)
	 */
	private function block_inline_scripts($html) {
		return preg_replace_callback(
			'/<script\b([^>]*)>([\s\S]*?)<\/script>/i',
			function ($match) {
				$attrs = $match[1];
				$content = $match[2];
				$full_tag = $match[0];

				// Hat src → wird von block_external_scripts behandelt
				if (preg_match('/\bsrc\s*=/i', $attrs)) {
					return $full_tag;
				}

				// Bereits blockiert (data-consent-src) → überspringen
				if (strpos($attrs, 'data-consent-src') !== false) {
					return $full_tag;
				}

				// Eigenes Script → überspringen
				if (strpos($attrs, 'data-consent-manager') !== false) {
					return $full_tag;
				}

				// application/ld+json oder application/json → überspringen
				if (preg_match('/type\s*=\s*["\']application\/(ld\+json|json)["\']/i', $attrs)) {
					return $full_tag;
				}

				// Bereits auf text/plain → überspringen
				if (preg_match('/type\s*=\s*["\']text\/plain["\']/i', $attrs)) {
					return $full_tag;
				}

				// Prüfe Inhalt gegen blockierte Inline-Patterns
				$category = $this->get_blocked_category_for_inline($content);
				if ($category === null) {
					return $full_tag;
				}

				// Consent vorhanden → nicht blockieren
				if ($this->has_consent($category)) {
					return $full_tag;
				}

				// Bestehenden type entfernen
				$new_attrs = preg_replace('/\btype\s*=\s*["\'][^"\']*["\']/i', '', $attrs);

				return '<script type="text/plain" data-consent-category="' . esc_attr($category) . '"' . $new_attrs . '>' . $content . '</script>';
			},
			$html
		);
	}

	/**
	 * A3: Resource Hints blockieren (<link rel="preconnect|dns-prefetch">)
	 */
	private function block_resource_hints($html) {
		return preg_replace_callback(
			'/<link\b[^>]*\brel\s*=\s*["\'](?:preconnect|dns-prefetch)["\'][^>]*\/?>/i',
			function ($match) {
				$tag = $match[0];

				// href extrahieren
				if (!preg_match('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $tag, $href_match)) {
					return $tag;
				}

				$href = $href_match[1];

				// Domain aus href extrahieren
				$host = parse_url($href, PHP_URL_HOST);
				if (!$host) {
					// Manche dns-prefetch haben //domain.com Format
					$host = parse_url('https:' . $href, PHP_URL_HOST);
				}
				if (!$host) {
					return $tag;
				}

				// Prüfe gegen blockierte Hint-Domains
				$category = $this->get_blocked_category_for_hint_domain($host);
				if ($category === null) {
					return $tag;
				}

				// Consent vorhanden → nicht blockieren
				if ($this->has_consent($category)) {
					return $tag;
				}

				// Resource Hint entfernen (kein Fallback nötig)
				return '<!-- consent-blocked: ' . esc_html($host) . ' (' . esc_html($category) . ') -->';
			},
			$html
		);
	}

	/**
	 * A4: Stylesheets blockieren (<link rel="stylesheet">)
	 */
	private function block_stylesheets($html) {
		return preg_replace_callback(
			'/<link\b[^>]*\brel\s*=\s*["\']stylesheet["\'][^>]*\/?>/i',
			function ($match) {
				$tag = $match[0];

				// Bereits blockiert → überspringen
				if (strpos($tag, 'data-consent-href') !== false) {
					return $tag;
				}

				// href extrahieren
				if (!preg_match('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $tag, $href_match)) {
					return $tag;
				}

				$href = $href_match[1];

				// Prüfe href gegen blockierte URL-Patterns (nur Functional)
				$category = $this->get_blocked_category_for_url($href);
				if ($category === null) {
					return $tag;
				}

				// Consent vorhanden → nicht blockieren
				if ($this->has_consent($category)) {
					return $tag;
				}

				// Stylesheet blockieren: href → data-consent-href, rel → consent-pending
				$blocked = $tag;
				$blocked = preg_replace('/\brel\s*=\s*(["\'])stylesheet\1/i', 'rel="consent-pending"', $blocked);
				$blocked = preg_replace('/\bhref\s*=\s*(["\'])([^"\']+)\1/i', 'data-consent-category="' . esc_attr($category) . '" data-consent-href="$2"', $blocked);

				return $blocked;
			},
			$html
		);
	}

	/**
	 * URL gegen blockierte Patterns prüfen
	 *
	 * @return string|null Kategorie oder null wenn nicht blockiert
	 */
	private function get_blocked_category_for_url($url) {
		foreach ($this->blocked_url_patterns as $pattern => $category) {
			if (preg_match('/' . $pattern . '/i', $url)) {
				return $category;
			}
		}
		return null;
	}

	/**
	 * Inline Script Inhalt gegen blockierte Patterns prüfen
	 *
	 * @return string|null Kategorie oder null wenn nicht blockiert
	 */
	private function get_blocked_category_for_inline($content) {
		foreach ($this->blocked_inline_patterns as $pattern => $category) {
			if (preg_match('/' . $pattern . '/i', $content)) {
				return $category;
			}
		}
		return null;
	}

	/**
	 * Domain gegen blockierte Hint-Domains prüfen
	 *
	 * @return string|null Kategorie oder null wenn nicht blockiert
	 */
	private function get_blocked_category_for_hint_domain($host) {
		// Exakter Match
		if (isset($this->blocked_hint_domains[$host])) {
			return $this->blocked_hint_domains[$host];
		}

		// Subdomain-Match (z.B. www.googletagmanager.com → googletagmanager.com)
		foreach ($this->blocked_hint_domains as $domain => $category) {
			if (substr($host, -strlen($domain) - 1) === '.' . $domain || $host === $domain) {
				return $category;
			}
		}

		return null;
	}

	/**
	 * Response Headers filtern
	 */
	public function filter_response_headers() {
		// Security Headers hinzufügen
		if (!headers_sent()) {
			header('Referrer-Policy: strict-origin-when-cross-origin');
			header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
			header('X-Content-Type-Options: nosniff');
			header('X-Frame-Options: SAMEORIGIN');

			// HSTS: Nur bei SSL-Verbindungen (DSGVO-Audit Empfehlung)
			if (is_ssl()) {
				header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
			}

			// CSP: Report-Only zuerst, enforce nach Testphase
			header("Content-Security-Policy-Report-Only: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' *.parkourone.com *.google-analytics.com *.googletagmanager.com *.facebook.net *.typekit.net *.mlcdn.com; style-src 'self' 'unsafe-inline' *.typekit.net fonts.googleapis.com; img-src 'self' data: *.parkourone.com *.google-analytics.com *.googletagmanager.com *.facebook.com *.doubleclick.net; font-src 'self' use.typekit.net fonts.gstatic.com; connect-src 'self' *.parkourone.com *.google-analytics.com *.googletagmanager.com *.facebook.com *.mlcdn.com; frame-src 'self' *.youtube.com *.youtube-nocookie.com *.google.com *.facebook.com; report-uri /csp-report-endpoint");

			// Fix C: Cookie Header Filtering
			$this->filter_cookie_headers();
		}
	}

	/**
	 * Fix C: Set-Cookie Headers filtern
	 * Blockiert Tracking-Cookies die von Plugins per header() gesetzt werden
	 */
	private function filter_cookie_headers() {
		$headers = headers_list();
		$cookie_headers = [];
		$has_blocked = false;

		foreach ($headers as $header) {
			if (stripos($header, 'Set-Cookie:') === 0) {
				$cookie_value = trim(substr($header, 11));
				$cookie_name = explode('=', $cookie_value, 2)[0];
				$cookie_name = trim($cookie_name);

				if ($this->should_block_cookie($cookie_name)) {
					$has_blocked = true;
				} else {
					$cookie_headers[] = $cookie_value;
				}
			}
		}

		// Nur wenn mindestens ein Cookie blockiert wurde: alle entfernen und erlaubte re-adden
		if ($has_blocked) {
			header_remove('Set-Cookie');
			foreach ($cookie_headers as $cookie) {
				header('Set-Cookie: ' . $cookie, false);
			}
		}
	}

	/**
	 * Prüfe ob ein Cookie blockiert werden soll
	 */
	private function should_block_cookie($cookie_name) {
		foreach ($this->blocked_cookie_prefixes as $category => $prefixes) {
			if ($this->has_consent($category)) {
				continue;
			}
			foreach ($prefixes as $prefix) {
				if (strpos($cookie_name, $prefix) === 0) {
					return true;
				}
			}
		}
		return false;
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
