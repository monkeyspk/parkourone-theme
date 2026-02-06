<?php
/**
 * ParkourONE Analytics
 *
 * Datenschutzkonformes Website-Analytics mit Ampel-Dashboard
 * Cookie-frei, DSGVO-konform, für Schulleiter optimiert
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class PO_Analytics {

	/**
	 * Version
	 */
	const VERSION = '1.0.0';

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
		// Tabellen erstellen bei Theme-Aktivierung
		add_action('after_switch_theme', [$this, 'create_tables']);

		// Frontend Tracker
		add_action('wp_enqueue_scripts', [$this, 'enqueue_tracker']);

		// REST API
		add_action('rest_api_init', [$this, 'register_rest_routes']);

		// Admin Menu
		add_action('admin_menu', [$this, 'add_admin_menu'], 20);

		// Cron Jobs
		add_action('po_analytics_daily_insights', [$this, 'generate_insights']);
		add_action('po_analytics_weekly_report', [$this, 'send_weekly_report']);
		add_action('po_analytics_daily_insights', [$this, 'cleanup_old_data']);

		// Cron Schedule registrieren
		add_action('init', [$this, 'schedule_crons']);

		// Tabellen bei Bedarf erstellen
		$this->maybe_create_tables();
	}

	/**
	 * Tabellen bei Bedarf erstellen
	 */
	private function maybe_create_tables() {
		if (get_option('po_analytics_version') !== self::VERSION) {
			$this->create_tables();
		}
	}

	/**
	 * Datenbank-Tabellen erstellen
	 */
	public function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		// Events-Tabelle
		$sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}po_analytics_events (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			session_id VARCHAR(64) NOT NULL,
			visitor_hash VARCHAR(64) NOT NULL,
			event_type VARCHAR(30) NOT NULL DEFAULT 'pageview',
			page_url VARCHAR(500) NOT NULL,
			page_title VARCHAR(255) DEFAULT '',
			referrer VARCHAR(500) DEFAULT '',
			utm_source VARCHAR(100) DEFAULT '',
			utm_medium VARCHAR(100) DEFAULT '',
			utm_campaign VARCHAR(100) DEFAULT '',
			device_type VARCHAR(20) DEFAULT '',
			browser VARCHAR(50) DEFAULT '',
			os VARCHAR(50) DEFAULT '',
			screen_width SMALLINT UNSIGNED DEFAULT 0,
			language VARCHAR(10) DEFAULT '',
			scroll_depth TINYINT UNSIGNED DEFAULT 0,
			time_on_page SMALLINT UNSIGNED DEFAULT 0,
			load_time SMALLINT UNSIGNED DEFAULT 0,
			event_label VARCHAR(255) DEFAULT '',
			event_value VARCHAR(255) DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_created (created_at),
			INDEX idx_session (session_id),
			INDEX idx_visitor (visitor_hash),
			INDEX idx_type (event_type),
			INDEX idx_page (page_url(191))
		) $charset;";

		// Insights-Tabelle
		$sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}po_analytics_insights (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			insight_key VARCHAR(100) NOT NULL,
			status ENUM('green','yellow','red','info') NOT NULL DEFAULT 'info',
			title VARCHAR(255) NOT NULL,
			message TEXT NOT NULL,
			detail TEXT DEFAULT '',
			metric_value DECIMAL(10,2) DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_created (created_at),
			INDEX idx_status (status)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql1);
		dbDelta($sql2);

		update_option('po_analytics_version', self::VERSION);
	}

	/**
	 * Cron Jobs einrichten
	 */
	public function schedule_crons() {
		if (!wp_next_scheduled('po_analytics_daily_insights')) {
			wp_schedule_event(strtotime('tomorrow 06:00'), 'daily', 'po_analytics_daily_insights');
		}
		if (!wp_next_scheduled('po_analytics_weekly_report')) {
			wp_schedule_event(strtotime('next monday 08:00'), 'weekly', 'po_analytics_weekly_report');
		}
	}

	/**
	 * Frontend Tracker einbinden
	 */
	public function enqueue_tracker() {
		// Admins nicht tracken
		if (is_user_logged_in() && current_user_can('manage_options')) {
			return;
		}

		wp_enqueue_script(
			'po-analytics-tracker',
			get_template_directory_uri() . '/assets/js/analytics-tracker.js',
			[],
			self::VERSION,
			true
		);

		wp_localize_script('po-analytics-tracker', 'poAnalytics', [
			'endpoint' => rest_url('parkourone/v1/analytics/track'),
			'nonce' => wp_create_nonce('wp_rest'),
		]);
	}

	/**
	 * REST API Routen registrieren
	 */
	public function register_rest_routes() {
		register_rest_route('parkourone/v1', '/analytics/track', [
			'methods' => 'POST',
			'callback' => [$this, 'handle_track'],
			'permission_callback' => '__return_true',
		]);
	}

	/**
	 * Tracking-Event verarbeiten
	 */
	public function handle_track(WP_REST_Request $request) {
		global $wpdb;
		$data = $request->get_json_params();

		if (empty($data['page_url'])) {
			return new WP_REST_Response(['error' => 'missing page_url'], 400);
		}

		// Visitor-Hash (DSGVO-konform: IP + UA + Datum, nicht rückverfolgbar)
		$raw = ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '') . date('Y-m-d');
		$visitor_hash = hash('sha256', $raw);

		$wpdb->insert("{$wpdb->prefix}po_analytics_events", [
			'session_id' => sanitize_text_field($data['session_id'] ?? ''),
			'visitor_hash' => $visitor_hash,
			'event_type' => sanitize_text_field($data['event_type'] ?? 'pageview'),
			'page_url' => esc_url_raw(substr($data['page_url'], 0, 500)),
			'page_title' => sanitize_text_field(substr($data['page_title'] ?? '', 0, 255)),
			'referrer' => esc_url_raw(substr($data['referrer'] ?? '', 0, 500)),
			'utm_source' => sanitize_text_field($data['utm_source'] ?? ''),
			'utm_medium' => sanitize_text_field($data['utm_medium'] ?? ''),
			'utm_campaign' => sanitize_text_field($data['utm_campaign'] ?? ''),
			'device_type' => sanitize_text_field($data['device_type'] ?? ''),
			'browser' => sanitize_text_field($data['browser'] ?? ''),
			'os' => sanitize_text_field($data['os'] ?? ''),
			'screen_width' => absint($data['screen_width'] ?? 0),
			'language' => sanitize_text_field(substr($data['language'] ?? '', 0, 10)),
			'scroll_depth' => min(100, absint($data['scroll_depth'] ?? 0)),
			'time_on_page' => absint($data['time_on_page'] ?? 0),
			'load_time' => absint($data['load_time'] ?? 0),
			'event_label' => sanitize_text_field($data['event_label'] ?? ''),
			'event_value' => sanitize_text_field($data['event_value'] ?? ''),
		]);

		return new WP_REST_Response(['ok' => true], 200);
	}

	/**
	 * Admin Menu hinzufügen
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'parkourone',
			'Analytics',
			'Analytics',
			'manage_options',
			'parkourone-analytics',
			[$this, 'render_dashboard']
		);

		add_submenu_page(
			'parkourone',
			'Analytics Rohdaten',
			'Analytics Rohdaten',
			'manage_options',
			'parkourone-analytics-raw',
			[$this, 'render_raw_data']
		);

		add_submenu_page(
			'parkourone',
			'Analytics Einstellungen',
			'Analytics Einstellungen',
			'manage_options',
			'parkourone-analytics-settings',
			[$this, 'render_settings']
		);
	}

	/**
	 * Dashboard Stats holen
	 */
	private function get_stats($days = 7) {
		global $wpdb;
		$table = "{$wpdb->prefix}po_analytics_events";
		$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

		return [
			'pageviews' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE event_type='pageview' AND created_at >= %s", $since
			)),
			'visitors' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT visitor_hash) FROM $table WHERE created_at >= %s", $since
			)),
			'sessions' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM $table WHERE created_at >= %s", $since
			)),
		];
	}

	/**
	 * Probetraining-Anfragen zählen
	 */
	private function get_probetraining_count($days = 7) {
		global $wpdb;
		$table = "{$wpdb->prefix}po_analytics_events";
		$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

		// Formular-Submits auf Probetraining-Seiten + CTA-Klicks
		$forms = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table
			WHERE event_type = 'form_submit'
			AND (page_url LIKE '%%probetraining%%' OR page_url LIKE '%%probe%%' OR event_label LIKE '%%probetraining%%')
			AND created_at >= %s", $since
		));

		$clicks = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table
			WHERE event_type = 'click'
			AND (event_label LIKE '%%probetraining%%' OR event_value LIKE '%%probetraining%%' OR event_value LIKE '%%Probetraining%%')
			AND created_at >= %s", $since
		));

		return $forms + $clicks;
	}

	/**
	 * Top Seiten holen
	 */
	private function get_top_pages($days = 7, $limit = 5) {
		global $wpdb;
		$table = "{$wpdb->prefix}po_analytics_events";
		$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

		return $wpdb->get_results($wpdb->prepare(
			"SELECT page_url, page_title, COUNT(*) as views
			FROM $table
			WHERE event_type='pageview' AND created_at >= %s
			GROUP BY page_url, page_title
			ORDER BY views DESC
			LIMIT %d", $since, $limit
		));
	}

	/**
	 * Dashboard rendern
	 */
	public function render_dashboard() {
		global $wpdb;

		// Manueller Insight-Trigger
		if (isset($_GET['run_insights']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'po_run_insights')) {
			$this->generate_insights();
			echo '<div class="notice notice-success"><p>Insights wurden neu berechnet!</p></div>';
		}

		// Stats diese Woche vs. letzte Woche
		$this_week = $this->get_stats(7);
		$last_week_start = date('Y-m-d H:i:s', strtotime('-14 days'));
		$last_week_end = date('Y-m-d H:i:s', strtotime('-7 days'));

		$table = "{$wpdb->prefix}po_analytics_events";
		$last_week = [
			'pageviews' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE event_type='pageview' AND created_at >= %s AND created_at < %s",
				$last_week_start, $last_week_end
			)),
			'visitors' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT visitor_hash) FROM $table WHERE created_at >= %s AND created_at < %s",
				$last_week_start, $last_week_end
			)),
			'sessions' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM $table WHERE created_at >= %s AND created_at < %s",
				$last_week_start, $last_week_end
			)),
		];

		// Probetraining-Anfragen
		$probetraining_this = $this->get_probetraining_count(7);
		$probetraining_last = $this->get_probetraining_count(14) - $probetraining_this;

		// Top Seiten
		$top_pages = $this->get_top_pages(7, 5);

		// Insights
		$insights = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}po_analytics_insights ORDER BY created_at DESC LIMIT 15"
		);

		// Trend berechnen
		$calc_trend = function($current, $previous) {
			if ($previous == 0) return $current > 0 ? 100 : 0;
			return round((($current - $previous) / $previous) * 100);
		};

		$trends = [
			'visitors' => $calc_trend($this_week['visitors'], $last_week['visitors']),
			'pageviews' => $calc_trend($this_week['pageviews'], $last_week['pageviews']),
			'sessions' => $calc_trend($this_week['sessions'], $last_week['sessions']),
			'probetraining' => $calc_trend($probetraining_this, $probetraining_last),
		];
		?>
		<div class="wrap" style="max-width: 1000px;">
			<h1 style="margin-bottom: 5px;">Analytics</h1>
			<p style="color: #666; margin-top: 0;">Website-Statistiken der letzten 7 Tage</p>

			<!-- Schnellzahlen mit Vergleich -->
			<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 24px 0;">
				<?php
				$cards = [
					['icon' => '', 'label' => 'Besucher', 'value' => $this_week['visitors'], 'trend' => $trends['visitors'], 'prev' => $last_week['visitors']],
					['icon' => '', 'label' => 'Seitenaufrufe', 'value' => $this_week['pageviews'], 'trend' => $trends['pageviews'], 'prev' => $last_week['pageviews']],
					['icon' => '', 'label' => 'Besuche', 'value' => $this_week['sessions'], 'trend' => $trends['sessions'], 'prev' => $last_week['sessions']],
					['icon' => '', 'label' => 'Probetraining', 'value' => $probetraining_this, 'trend' => $trends['probetraining'], 'prev' => $probetraining_last, 'highlight' => true],
				];
				foreach ($cards as $card):
					$trend_color = $card['trend'] > 0 ? '#22c55e' : ($card['trend'] < 0 ? '#ef4444' : '#888');
					$trend_icon = $card['trend'] > 0 ? '↑' : ($card['trend'] < 0 ? '↓' : '→');
					$bg = !empty($card['highlight']) ? 'linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%)' : '#fff';
					$text_color = !empty($card['highlight']) ? '#fff' : '#1e3a5f';
					$sub_color = !empty($card['highlight']) ? 'rgba(255,255,255,0.7)' : '#888';
				?>
				<div style="background: <?php echo $bg; ?>; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.1); text-align: center;">
					<div style="font-size: 32px; font-weight: 700; color: <?php echo $text_color; ?>;"><?php echo number_format($card['value']); ?></div>
					<div style="color: <?php echo $sub_color; ?>; font-size: 13px; margin-top: 4px;"><?php echo $card['label']; ?></div>
					<div style="font-size: 12px; margin-top: 8px; color: <?php echo !empty($card['highlight']) ? 'rgba(255,255,255,0.9)' : $trend_color; ?>;">
						<?php echo $trend_icon; ?> <?php echo abs($card['trend']); ?>% vs. Vorwoche
						<span style="color: <?php echo $sub_color; ?>;">(<?php echo $card['prev']; ?>)</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
				<!-- Top Seiten -->
				<div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
					<h2 style="font-size: 16px; margin: 0 0 16px;">Top 5 Seiten</h2>
					<?php if (empty($top_pages)): ?>
						<p style="color: #888;">Noch keine Daten vorhanden.</p>
					<?php else: ?>
						<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
							<?php foreach ($top_pages as $idx => $page): ?>
							<tr style="border-bottom: 1px solid #eee;">
								<td style="padding: 8px 0; color: #888; width: 24px;"><?php echo $idx + 1; ?>.</td>
								<td style="padding: 8px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($page->page_url); ?>">
									<?php echo esc_html($page->page_title ?: $page->page_url); ?>
								</td>
								<td style="padding: 8px 0; text-align: right; font-weight: 600;"><?php echo $page->views; ?></td>
							</tr>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
				</div>

				<!-- Traffic-Quellen -->
				<div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
					<h2 style="font-size: 16px; margin: 0 0 16px;">Traffic-Quellen</h2>
					<?php
					$sources = $wpdb->get_results($wpdb->prepare(
						"SELECT
							CASE
								WHEN referrer LIKE '%%google%%' THEN 'Google'
								WHEN referrer LIKE '%%instagram%%' OR referrer LIKE '%%l.instagram%%' THEN 'Instagram'
								WHEN referrer LIKE '%%facebook%%' OR referrer LIKE '%%l.facebook%%' THEN 'Facebook'
								WHEN referrer LIKE '%%tiktok%%' THEN 'TikTok'
								WHEN referrer = '' THEN 'Direkt'
								ELSE 'Andere'
							END as source,
							COUNT(*) as cnt
						FROM {$wpdb->prefix}po_analytics_events
						WHERE created_at >= %s
						GROUP BY source
						ORDER BY cnt DESC",
						date('Y-m-d H:i:s', strtotime('-7 days'))
					));
					if (empty($sources)): ?>
						<p style="color: #888;">Noch keine Daten vorhanden.</p>
					<?php else: ?>
						<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
							<?php foreach ($sources as $source): ?>
							<tr style="border-bottom: 1px solid #eee;">
								<td style="padding: 8px 0;"><?php echo esc_html($source->source); ?></td>
								<td style="padding: 8px 0; text-align: right; font-weight: 600;"><?php echo $source->cnt; ?></td>
							</tr>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Insights / Ampel -->
			<h2 style="margin-top: 32px; font-size: 18px;">Was läuft – und was nicht</h2>
			<?php if (empty($insights)): ?>
				<div style="background: #f0f6ff; padding: 20px; border-radius: 12px; margin: 16px 0;">
					<p style="margin: 0;">Noch keine Insights vorhanden. Die erste Auswertung erfolgt automatisch morgen früh.</p>
					<p style="margin: 10px 0 0;">
						<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=parkourone-analytics&run_insights=1'), 'po_run_insights'); ?>" class="button">
							Jetzt auswerten
						</a>
					</p>
				</div>
			<?php else: ?>
				<?php
				$colors = [
					'green' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'icon' => ''],
					'yellow' => ['bg' => '#fefce8', 'border' => '#eab308', 'icon' => ''],
					'red' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'icon' => ''],
					'info' => ['bg' => '#f0f6ff', 'border' => '#3b82f6', 'icon' => ''],
				];
				foreach ($insights as $i):
					$c = $colors[$i->status] ?? $colors['info'];
				?>
				<div style="background: <?php echo $c['bg']; ?>; border-left: 4px solid <?php echo $c['border']; ?>; padding: 16px 20px; border-radius: 0 12px 12px 0; margin: 12px 0;">
					<div style="font-weight: 600; font-size: 15px; margin-bottom: 6px;"><?php echo $c['icon']; ?> <?php echo esc_html($i->title); ?></div>
					<div style="color: #444; font-size: 14px; line-height: 1.6;"><?php echo esc_html($i->message); ?></div>
					<?php if ($i->detail): ?>
						<div style="color: #666; font-size: 13px; margin-top: 8px; font-style: italic;"><?php echo esc_html($i->detail); ?></div>
					<?php endif; ?>
					<div style="color: #aaa; font-size: 11px; margin-top: 8px;"><?php echo date('d.m.Y H:i', strtotime($i->created_at)); ?></div>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Rohdaten-Seite rendern
	 */
	public function render_raw_data() {
		global $wpdb;
		$table = "{$wpdb->prefix}po_analytics_events";
		$page = max(1, intval($_GET['paged'] ?? 1));
		$per = 50;
		$offset = ($page - 1) * $per;
		$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
		$events = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT $per OFFSET $offset");
		?>
		<div class="wrap">
			<h1>Analytics Rohdaten</h1>
			<p style="color: #666;"><?php echo number_format($total); ?> Events gesamt</p>

			<table class="widefat striped" style="margin-top: 16px;">
				<thead>
					<tr>
						<th>Zeit</th>
						<th>Typ</th>
						<th>Seite</th>
						<th>Referrer</th>
						<th>Gerät</th>
						<th>Scroll</th>
						<th>Ladezeit</th>
						<th>Label</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($events)): ?>
						<tr><td colspan="8" style="text-align: center; color: #666;">Noch keine Events vorhanden.</td></tr>
					<?php else: ?>
						<?php foreach ($events as $e): ?>
						<tr>
							<td style="white-space: nowrap; font-size: 12px;"><?php echo date('d.m. H:i', strtotime($e->created_at)); ?></td>
							<td><code style="font-size: 11px;"><?php echo esc_html($e->event_type); ?></code></td>
							<td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; font-size: 12px;" title="<?php echo esc_attr($e->page_url); ?>">
								<?php echo esc_html($e->page_title ?: $e->page_url); ?>
							</td>
							<td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; font-size: 12px;">
								<?php echo esc_html(parse_url($e->referrer, PHP_URL_HOST) ?: '-'); ?>
							</td>
							<td style="font-size: 12px;"><?php echo esc_html($e->device_type ?: '-'); ?></td>
							<td style="font-size: 12px;"><?php echo $e->scroll_depth; ?>%</td>
							<td style="font-size: 12px;"><?php echo $e->load_time ? round($e->load_time / 1000, 1) . 's' : '-'; ?></td>
							<td style="font-size: 12px;"><?php echo esc_html($e->event_label ?: '-'); ?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php
			$total_pages = ceil($total / $per);
			if ($total_pages > 1):
			?>
			<div style="margin-top: 16px; display: flex; gap: 8px;">
				<?php for ($i = max(1, $page - 3); $i <= min($total_pages, $page + 3); $i++): ?>
					<a href="?page=parkourone-analytics-raw&paged=<?php echo $i; ?>"
					   style="padding: 6px 12px; border-radius: 6px; text-decoration: none; <?php echo $i === $page ? 'background: #2271b1; color: #fff;' : 'background: #f0f0f0;'; ?>">
						<?php echo $i; ?>
					</a>
				<?php endfor; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Einstellungen-Seite rendern
	 */
	public function render_settings() {
		if (isset($_POST['po_analytics_save']) && check_admin_referer('po_analytics_settings')) {
			update_option('po_analytics_email_enabled', isset($_POST['email_enabled']) ? 1 : 0);
			update_option('po_analytics_email_recipient', sanitize_email($_POST['email_recipient'] ?? ''));
			echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
		}

		// Test-E-Mail senden
		if (isset($_GET['test_email']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'po_test_email')) {
			$this->send_weekly_report();
			echo '<div class="notice notice-success"><p>Test-E-Mail wurde gesendet!</p></div>';
		}

		$email_enabled = get_option('po_analytics_email_enabled', 1);
		$email_recipient = get_option('po_analytics_email_recipient', get_option('admin_email'));
		?>
		<div class="wrap" style="max-width: 600px;">
			<h1>Analytics Einstellungen</h1>

			<form method="post">
				<?php wp_nonce_field('po_analytics_settings'); ?>

				<table class="form-table">
					<tr>
						<th>Wöchentlicher E-Mail-Report</th>
						<td>
							<label>
								<input type="checkbox" name="email_enabled" value="1" <?php checked($email_enabled, 1); ?>>
								Jeden Montag um 08:00 einen Report per E-Mail senden
							</label>
						</td>
					</tr>
					<tr>
						<th>Empfänger</th>
						<td>
							<input type="email" name="email_recipient" value="<?php echo esc_attr($email_recipient); ?>" class="regular-text">
							<p class="description">An diese Adresse wird der wöchentliche Report gesendet.</p>
						</td>
					</tr>
				</table>

				<p>
					<input type="submit" name="po_analytics_save" class="button button-primary" value="Speichern">
					<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=parkourone-analytics-settings&test_email=1'), 'po_test_email'); ?>" class="button" style="margin-left: 8px;">
						Test-E-Mail senden
					</a>
				</p>
			</form>

			<hr style="margin: 30px 0;">

			<h2>DSGVO-Konformität</h2>
			<div style="background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #22c55e;">
				<p style="margin: 0 0 10px;"><strong>Dieses Analytics-System ist datenschutzkonform:</strong></p>
				<ul style="margin: 0; padding-left: 20px;">
					<li><strong>Keine Cookies</strong> – Nutzt Session Storage (verfällt beim Schliessen)</li>
					<li><strong>Kein Fingerprinting</strong> – Visitor-Hash wird täglich neu berechnet</li>
					<li><strong>Keine externen Services</strong> – Alle Daten bleiben auf deinem Server</li>
					<li><strong>IP-Anonymisierung</strong> – IPs werden nur gehasht gespeichert</li>
					<li><strong>Automatische Löschung</strong> – Daten werden nach 365 Tagen gelöscht</li>
					<li><strong>Admins werden nicht getrackt</strong></li>
				</ul>
				<p style="margin: 15px 0 0;"><strong>Kein Cookie-Banner nötig!</strong></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Insights generieren (täglich)
	 */
	public function generate_insights() {
		global $wpdb;
		$table = "{$wpdb->prefix}po_analytics_events";
		$insight_table = "{$wpdb->prefix}po_analytics_insights";

		$week = date('Y-m-d H:i:s', strtotime('-7 days'));
		$prevw = date('Y-m-d H:i:s', strtotime('-14 days'));

		// Alte Insights löschen (älter als 30 Tage)
		$wpdb->query("DELETE FROM $insight_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

		// --- Traffic-Trend ---
		$this_week = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT visitor_hash) FROM $table WHERE created_at >= %s", $week
		));
		$last_week = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT visitor_hash) FROM $table WHERE created_at >= %s AND created_at < %s", $prevw, $week
		));

		if ($last_week > 0) {
			$change = round((($this_week - $last_week) / $last_week) * 100);
			if ($change > 20) {
				$this->save_insight('traffic_trend', 'green', 'Traffic wächst!',
					"Diese Woche {$this_week} Besucher – das sind {$change}% mehr als letzte Woche ({$last_week}). Weiter so!",
					'', $change);
			} elseif ($change < -20) {
				$this->save_insight('traffic_trend', 'red', 'Traffic-Rückgang',
					"Diese Woche nur {$this_week} Besucher – das sind " . abs($change) . "% weniger als letzte Woche ({$last_week}).",
					'Tipp: Prüfe ob dein Google-Ranking sich verändert hat und ob deine Social-Media-Links aktuell sind.', $change);
			} else {
				$this->save_insight('traffic_trend', 'info', 'Traffic stabil',
					"Diese Woche {$this_week} Besucher, letzte Woche {$last_week}. Alles im grünen Bereich.",
					'', $change);
			}
		} elseif ($this_week > 0) {
			$this->save_insight('traffic_trend', 'info', 'Erste Daten!',
				"Diese Woche haben {$this_week} Personen deine Seite besucht. Ab nächster Woche können wir Trends erkennen.",
				'', $this_week);
		}

		// --- Mobile Performance ---
		$mobile_slow = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE device_type = 'mobile' AND load_time > 3000 AND created_at >= %s", $week
		));
		$mobile_total = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE device_type = 'mobile' AND load_time > 0 AND created_at >= %s", $week
		));

		if ($mobile_total > 10) {
			$slow_pct = round(($mobile_slow / $mobile_total) * 100);
			if ($slow_pct > 50) {
				$avg_load = round($wpdb->get_var($wpdb->prepare(
					"SELECT AVG(load_time) FROM $table WHERE device_type = 'mobile' AND load_time > 0 AND created_at >= %s", $week
				)) / 1000, 1);
				$this->save_insight('mobile_speed', 'red', 'Handy-Seite ist zu langsam',
					"Deine Seite braucht auf dem Handy durchschnittlich {$avg_load} Sekunden zum Laden. {$slow_pct}% der mobilen Besucher warten über 3 Sekunden.",
					'Tipp: Bilder komprimieren und prüfen ob grosse Dateien die Seite verlangsamen.', $avg_load);
			} else {
				$this->save_insight('mobile_speed', 'green', 'Handy-Performance ist gut',
					"Deine Seite lädt auf dem Handy schnell genug. Nur {$slow_pct}% der Besuche waren langsam.",
					'', $slow_pct);
			}
		}

		// --- Bounce Rate ---
		$total_sessions = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT session_id) FROM $table WHERE created_at >= %s", $week
		));
		$single_page = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM (SELECT session_id FROM $table WHERE event_type='pageview' AND created_at >= %s GROUP BY session_id HAVING COUNT(*) = 1) t", $week
		));

		if ($total_sessions > 10) {
			$bounce = round(($single_page / $total_sessions) * 100);
			if ($bounce > 70) {
				$this->save_insight('bounce_rate', 'yellow', 'Viele Besucher schauen nur eine Seite an',
					"{$bounce}% der Besucher verlassen die Seite nach nur einer Seite. Sie finden nicht sofort was sie suchen.",
					'Tipp: Füge auf deiner Startseite klare Links zu Stundenplan, Probetraining und Preise hinzu.', $bounce);
			} elseif ($bounce < 40) {
				$this->save_insight('bounce_rate', 'green', 'Besucher erkunden deine Seite',
					"Nur {$bounce}% der Besucher schauen nur eine Seite an. Die meisten klicken sich durch!",
					'', $bounce);
			}
		}

		// --- Probetraining-Anfragen ---
		$probetraining = $this->get_probetraining_count(7);
		$probetraining_last = $this->get_probetraining_count(14) - $probetraining;

		if ($probetraining > 0 || $probetraining_last > 0) {
			if ($probetraining > $probetraining_last) {
				$this->save_insight('probetraining', 'green', 'Mehr Probetraining-Interesse!',
					"Diese Woche {$probetraining} Probetraining-Anfragen, letzte Woche {$probetraining_last}. Läuft!",
					'', $probetraining);
			} elseif ($probetraining < $probetraining_last && $probetraining_last > 0) {
				$this->save_insight('probetraining', 'yellow', 'Weniger Probetraining-Anfragen',
					"Diese Woche nur {$probetraining} Probetraining-Anfragen, letzte Woche waren es {$probetraining_last}.",
					'Tipp: Ist der Probetraining-Button gut sichtbar? Funktioniert das Formular?', $probetraining);
			}
		}

		// --- Top Traffic-Quelle ---
		$top_source = $wpdb->get_row($wpdb->prepare(
			"SELECT CASE
				WHEN referrer LIKE '%%google%%' THEN 'Google'
				WHEN referrer LIKE '%%instagram%%' OR referrer LIKE '%%l.instagram%%' THEN 'Instagram'
				WHEN referrer LIKE '%%facebook%%' OR referrer LIKE '%%l.facebook%%' THEN 'Facebook'
				WHEN referrer LIKE '%%tiktok%%' THEN 'TikTok'
				WHEN referrer = '' THEN 'Direkt'
				ELSE 'Andere'
			END as source, COUNT(*) as cnt
			FROM $table WHERE created_at >= %s GROUP BY source ORDER BY cnt DESC LIMIT 1", $week
		));

		if ($top_source && $top_source->cnt > 5) {
			$msg = match($top_source->source) {
				'Google' => 'Dein Google-Ranking scheint zu funktionieren!',
				'Instagram' => 'Deine Instagram-Arbeit zahlt sich aus!',
				'Direkt' => 'Viele kennen deine Webadresse bereits – gute Markenbekanntheit.',
				default => 'Gut, dass du auf verschiedenen Kanälen präsent bist.'
			};
			$this->save_insight('top_source', 'info', "Die meisten Besucher kommen über {$top_source->source}",
				"{$top_source->cnt} Besuche diese Woche über {$top_source->source}. {$msg}",
				'', $top_source->cnt);
		}
	}

	/**
	 * Insight speichern
	 */
	private function save_insight($key, $status, $title, $message, $detail = '', $value = 0) {
		global $wpdb;
		$wpdb->insert("{$wpdb->prefix}po_analytics_insights", [
			'insight_key' => $key,
			'status' => $status,
			'title' => $title,
			'message' => $message,
			'detail' => $detail,
			'metric_value' => $value,
		]);
	}

	/**
	 * Wöchentlichen E-Mail Report senden
	 */
	public function send_weekly_report() {
		if (!get_option('po_analytics_email_enabled', 1)) {
			return;
		}

		global $wpdb;
		$recipient = get_option('po_analytics_email_recipient', get_option('admin_email'));
		$site_name = get_bloginfo('name');

		$stats = $this->get_stats(7);
		$top_pages = $this->get_top_pages(7, 5);
		$probetraining = $this->get_probetraining_count(7);

		$insights = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}po_analytics_insights
			WHERE created_at >= '" . date('Y-m-d', strtotime('-7 days')) . "'
			ORDER BY FIELD(status, 'red', 'yellow', 'green', 'info'), created_at DESC
			LIMIT 8"
		);

		$status_emoji = ['red' => '', 'yellow' => '', 'green' => '', 'info' => ''];
		$status_color = ['red' => '#fef2f2', 'yellow' => '#fefce8', 'green' => '#f0fdf4', 'info' => '#f0f6ff'];
		$border_color = ['red' => '#ef4444', 'yellow' => '#eab308', 'green' => '#22c55e', 'info' => '#3b82f6'];

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 20px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08);">

			<div style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); color: #fff; padding: 32px; text-align: center;">
				<h1 style="margin: 0; font-size: 22px;">Wochenreport</h1>
				<p style="margin: 8px 0 0; opacity: 0.8; font-size: 14px;"><?php echo esc_html($site_name); ?> – <?php echo date('d.m.Y', strtotime('-7 days')); ?> bis <?php echo date('d.m.Y'); ?></p>
			</div>

			<div style="padding: 24px;">
				<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
					<tr>
						<td style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 12px;">
							<div style="font-size: 28px; font-weight: 700; color: #1e3a5f;"><?php echo number_format($stats['visitors']); ?></div>
							<div style="font-size: 12px; color: #888; margin-top: 4px;">Besucher</div>
						</td>
						<td width="12"></td>
						<td style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 12px;">
							<div style="font-size: 28px; font-weight: 700; color: #1e3a5f;"><?php echo number_format($stats['pageviews']); ?></div>
							<div style="font-size: 12px; color: #888; margin-top: 4px;">Seitenaufrufe</div>
						</td>
						<td width="12"></td>
						<td style="text-align: center; padding: 16px; background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); border-radius: 12px;">
							<div style="font-size: 28px; font-weight: 700; color: #fff;"><?php echo number_format($probetraining); ?></div>
							<div style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 4px;">Probetraining</div>
						</td>
					</tr>
				</table>

				<?php if (!empty($insights)): ?>
				<h2 style="font-size: 16px; margin: 24px 0 12px;">Was diese Woche wichtig war</h2>
				<?php foreach ($insights as $i): ?>
				<div style="background: <?php echo $status_color[$i->status]; ?>; border-left: 4px solid <?php echo $border_color[$i->status]; ?>; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 8px 0;">
					<div style="font-weight: 600; font-size: 14px;"><?php echo $status_emoji[$i->status]; ?> <?php echo esc_html($i->title); ?></div>
					<div style="font-size: 13px; color: #444; margin-top: 4px;"><?php echo esc_html($i->message); ?></div>
				</div>
				<?php endforeach; ?>
				<?php endif; ?>

				<?php if (!empty($top_pages)): ?>
				<h2 style="font-size: 16px; margin: 24px 0 12px;">Beliebteste Seiten</h2>
				<table width="100%" style="font-size: 13px; border-collapse: collapse;">
					<?php foreach ($top_pages as $idx => $p): ?>
					<tr style="border-bottom: 1px solid #eee;">
						<td style="padding: 8px 0; color: #888;"><?php echo $idx + 1; ?>.</td>
						<td style="padding: 8px;"><?php echo esc_html($p->page_title ?: $p->page_url); ?></td>
						<td style="padding: 8px 0; text-align: right; font-weight: 600;"><?php echo $p->views; ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php endif; ?>

				<div style="text-align: center; margin: 32px 0 16px;">
					<a href="<?php echo admin_url('admin.php?page=parkourone-analytics'); ?>"
					   style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
						Zum Dashboard
					</a>
				</div>
			</div>

			<div style="background: #f8fafc; padding: 16px 24px; text-align: center; font-size: 11px; color: #aaa;">
				ParkourONE Analytics – Dieser Report wird automatisch jeden Montag gesendet.
			</div>
		</div>
		</body>
		</html>
		<?php
		$html = ob_get_clean();

		wp_mail(
			$recipient,
			"Wochenreport {$site_name} – " . date('d.m.Y'),
			$html,
			['Content-Type: text/html; charset=UTF-8']
		);
	}

	/**
	 * Alte Daten löschen (> 365 Tage)
	 */
	public function cleanup_old_data() {
		global $wpdb;
		$wpdb->query("DELETE FROM {$wpdb->prefix}po_analytics_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)");
	}
}
