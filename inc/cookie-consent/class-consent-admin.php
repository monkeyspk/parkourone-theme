<?php
/**
 * ParkourONE Consent Admin Settings
 *
 * Admin-Oberfläche für Cookie-Consent-Konfiguration
 *
 * @package ParkourONE
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class PO_Consent_Admin {

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
		add_action('admin_menu', [$this, 'add_admin_menu'], 15);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_init', [$this, 'handle_export']);
		add_action('admin_init', [$this, 'handle_mu_plugin_download']);
		add_action('admin_notices', [$this, 'mu_plugin_notice']);
	}

	/**
	 * Prüfen ob MU-Plugin installiert ist
	 */
	public static function is_mu_plugin_installed() {
		$mu_plugin_path = WPMU_PLUGIN_DIR . '/parkourone-consent-early.php';
		return file_exists($mu_plugin_path);
	}

	/**
	 * Admin Notice wenn MU-Plugin fehlt
	 */
	public function mu_plugin_notice() {
		// Nur auf ParkourONE Seiten anzeigen
		$screen = get_current_screen();
		if (!$screen || strpos($screen->id, 'parkourone') === false) {
			return;
		}

		// Prüfen ob installiert
		if (self::is_mu_plugin_installed()) {
			return;
		}

		$download_url = wp_nonce_url(
			admin_url('admin.php?page=parkourone-consent&action=download_mu_plugin'),
			'po_download_mu_plugin'
		);
		?>
		<div class="notice notice-error">
			<p>
				<strong>⚠️ Cookie-Consent: MU-Plugin fehlt!</strong>
			</p>
			<p>
				Für vollständige DSGVO-Compliance muss das MU-Plugin installiert werden.
				Es blockiert Tracking-Cookies bevor WordPress lädt.
			</p>
			<p>
				<a href="<?php echo esc_url($download_url); ?>" class="button button-primary">
					MU-Plugin herunterladen
				</a>
				<span style="margin-left: 10px; color: #666;">
					→ Dann nach <code>wp-content/mu-plugins/</code> hochladen
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * MU-Plugin Download Handler
	 */
	public function handle_mu_plugin_download() {
		if (!isset($_GET['page']) || $_GET['page'] !== 'parkourone-consent') {
			return;
		}

		if (!isset($_GET['action']) || $_GET['action'] !== 'download_mu_plugin') {
			return;
		}

		if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'po_download_mu_plugin')) {
			wp_die('Sicherheitsprüfung fehlgeschlagen.');
		}

		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung.');
		}

		$mu_plugin_source = get_template_directory() . '/mu-plugins/parkourone-consent-early.php';

		if (!file_exists($mu_plugin_source)) {
			wp_die('MU-Plugin Quelldatei nicht gefunden.');
		}

		$content = file_get_contents($mu_plugin_source);

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="parkourone-consent-early.php"');
		header('Content-Length: ' . strlen($content));

		echo $content;
		exit;
	}

	/**
	 * CSV Export Handler
	 */
	public function handle_export() {
		if (!isset($_GET['page']) || $_GET['page'] !== 'parkourone-consent') {
			return;
		}

		if (!isset($_GET['action']) || $_GET['action'] !== 'export_log') {
			return;
		}

		if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'po_export_log')) {
			wp_die('Sicherheitsprüfung fehlgeschlagen.');
		}

		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung.');
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'po_consent_log';
		$logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC", ARRAY_A);

		// CSV Header
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=consent-log-' . date('Y-m-d') . '.csv');

		$output = fopen('php://output', 'w');

		// BOM für Excel UTF-8
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

		// Header Row
		fputcsv($output, [
			'Consent-ID',
			'Zeitstempel',
			'IP-Hash',
			'User-ID',
			'User-Agent',
			'Kategorien',
			'Consent-Version',
			'Legal-Version',
			'Domain',
			'DNT',
			'GPC',
			'Integrity-Hash',
		], ';');

		// Data Rows
		foreach ($logs as $log) {
			fputcsv($output, [
				$log['consent_id'],
				$log['timestamp'],
				$log['ip_hash'],
				$log['user_id'] ?? '',
				$log['user_agent'],
				$log['consent_data'],
				$log['consent_version'],
				$log['legal_version'] ?? '',
				$log['domain'] ?? '',
				$log['dnt_enabled'] ?? 0,
				$log['gpc_enabled'] ?? 0,
				$log['integrity_hash'],
			], ';');
		}

		fclose($output);
		exit;
	}

	/**
	 * Admin Menu hinzufügen
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'parkourone',
			'Cookie & Datenschutz',
			'Cookie & Datenschutz',
			'manage_options',
			'parkourone-consent',
			[$this, 'render_admin_page']
		);
	}

	/**
	 * Settings registrieren
	 */
	public function register_settings() {
		register_setting('parkourone_consent', 'parkourone_consent', [
			'sanitize_callback' => [$this, 'sanitize_options']
		]);
	}

	/**
	 * Options sanitizen
	 */
	public function sanitize_options($input) {
		$sanitized = [];

		// Enabled services
		$sanitized['enabled_services'] = [];
		if (!empty($input['enabled_services']) && is_array($input['enabled_services'])) {
			$available = array_keys(PO_Consent_Services::get_instance()->get_available_services());
			foreach ($input['enabled_services'] as $service) {
				if (in_array($service, $available)) {
					$sanitized['enabled_services'][] = sanitize_key($service);
				}
			}
		}

		// Google Consent Mode
		$sanitized['google_consent_mode'] = !empty($input['google_consent_mode']);

		// Banner Text
		$sanitized['banner_title'] = sanitize_text_field($input['banner_title'] ?? '');
		$sanitized['banner_text'] = sanitize_textarea_field($input['banner_text'] ?? '');

		// Button Labels
		$sanitized['btn_accept_all'] = sanitize_text_field($input['btn_accept_all'] ?? '');
		$sanitized['btn_reject_all'] = sanitize_text_field($input['btn_reject_all'] ?? '');
		$sanitized['btn_settings'] = sanitize_text_field($input['btn_settings'] ?? '');

		// Custom CSS Class
		$sanitized['custom_css_class'] = sanitize_html_class($input['custom_css_class'] ?? '');

		// Position (bottom/top/center)
		$sanitized['position'] = in_array($input['position'] ?? '', ['bottom', 'top', 'center'])
			? $input['position']
			: 'bottom';

		return $sanitized;
	}

	/**
	 * Admin Seite rendern
	 */
	public function render_admin_page() {
		// Speichern
		if (isset($_POST['parkourone_consent_save']) && check_admin_referer('parkourone_consent_nonce')) {
			$options = $this->sanitize_options($_POST['parkourone_consent'] ?? []);
			update_option('parkourone_consent', $options);
			echo '<div class="notice notice-success"><p>Cookie-Einstellungen gespeichert!</p></div>';
		}

		$options = get_option('parkourone_consent', []);
		$defaults = [
			'enabled_services' => ['mailerlite'],
			'google_consent_mode' => false,
			'banner_title' => '',
			'banner_text' => '',
			'btn_accept_all' => '',
			'btn_reject_all' => '',
			'btn_settings' => '',
			'custom_css_class' => '',
			'position' => 'bottom',
		];
		$options = wp_parse_args($options, $defaults);

		$available_services = PO_Consent_Services::get_instance()->get_services_by_category();
		$category_info = PO_Consent_Manager::get_instance()->get_category_info();
		$mu_plugin_installed = self::is_mu_plugin_installed();
		$download_url = wp_nonce_url(
			admin_url('admin.php?page=parkourone-consent&action=download_mu_plugin'),
			'po_download_mu_plugin'
		);
		?>
		<div class="wrap">
			<h1>Cookie & Datenschutz</h1>

			<!-- System-Status -->
			<div class="po-consent-status" style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
				<div style="padding: 15px 20px; border-radius: 4px; flex: 1; min-width: 200px; <?php echo $mu_plugin_installed ? 'background: #d4edda; border: 1px solid #c3e6cb;' : 'background: #f8d7da; border: 1px solid #f5c6cb;'; ?>">
					<strong style="<?php echo $mu_plugin_installed ? 'color: #155724;' : 'color: #721c24;'; ?>">
						<?php echo $mu_plugin_installed ? '✓ MU-Plugin installiert' : '✗ MU-Plugin fehlt'; ?>
					</strong>
					<?php if (!$mu_plugin_installed): ?>
						<p style="margin: 8px 0 0; font-size: 13px; color: #721c24;">
							Server-seitiges Cookie-Blocking ist nicht aktiv.
							<a href="<?php echo esc_url($download_url); ?>" style="color: #721c24; font-weight: 600;">
								Jetzt herunterladen →
							</a>
						</p>
					<?php else: ?>
						<p style="margin: 8px 0 0; font-size: 13px; color: #155724;">
							Server-seitiges Cookie-Blocking ist aktiv.
						</p>
					<?php endif; ?>
				</div>

				<div style="padding: 15px 20px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; flex: 1; min-width: 200px;">
					<strong style="color: #0c5460;">Consent-Version: <?php echo esc_html(PO_Consent_Manager::CONSENT_VERSION); ?></strong>
					<p style="margin: 8px 0 0; font-size: 13px; color: #0c5460;">
						Legal-Version: <?php echo esc_html(PO_Consent_Manager::LEGAL_TEXT_VERSION); ?>
					</p>
				</div>
			</div>

			<style>
				.po-consent-admin { max-width: 900px; }
				.po-admin-tabs { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 20px; }
				.po-admin-tab { padding: 12px 20px; background: #f0f0f1; border: 1px solid #c3c4c7; border-bottom: none; margin-bottom: -1px; cursor: pointer; text-decoration: none; color: #1d2327; }
				.po-admin-tab.active { background: #fff; border-bottom-color: #fff; font-weight: 600; }
				.po-admin-tab:hover { background: #fff; }
				.po-admin-panel { display: none; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none; }
				.po-admin-panel.active { display: block; }
				.po-form-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
				.po-form-section:last-child { border-bottom: none; }
				.po-form-section h3 { margin: 0 0 15px; font-size: 14px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; }
				.po-form-row { display: grid; grid-template-columns: 200px 1fr; gap: 10px; margin-bottom: 15px; align-items: start; }
				.po-form-row label { font-weight: 500; padding-top: 8px; }
				.po-form-row input[type="text"],
				.po-form-row textarea { width: 100%; max-width: 500px; }
				.po-form-row textarea { min-height: 80px; }
				.po-service-list { display: grid; gap: 10px; }
				.po-service-item { display: flex; gap: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #e5e5e5; align-items: flex-start; }
				.po-service-item input[type="checkbox"] { margin-top: 4px; }
				.po-service-info { flex: 1; }
				.po-service-name { font-weight: 600; margin-bottom: 4px; }
				.po-service-desc { font-size: 13px; color: #666; margin-bottom: 4px; }
				.po-service-meta { font-size: 12px; color: #999; }
				.po-service-meta span { margin-right: 15px; }
				.po-service-country-warning { color: #d63638; }
				.po-category-section { margin-bottom: 25px; }
				.po-category-header { font-size: 14px; font-weight: 600; margin-bottom: 10px; padding: 10px; background: #f0f0f1; border-left: 3px solid #2271b1; }
				.po-log-table { width: 100%; border-collapse: collapse; }
				.po-log-table th, .po-log-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e5e5; }
				.po-log-table th { background: #f0f0f1; font-weight: 600; }
				.po-info-box { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px; }
				.po-info-box h4 { margin: 0 0 10px; }
				.po-info-box p { margin: 0; }
				.po-info-box ul { margin: 10px 0 0 20px; }
			</style>

			<div class="po-consent-admin">
				<div class="po-admin-tabs">
					<a href="#services" class="po-admin-tab active" data-tab="services">Dienste/Cookies</a>
					<a href="#design" class="po-admin-tab" data-tab="design">Banner-Design</a>
					<a href="#log" class="po-admin-tab" data-tab="log">Consent-Log</a>
					<a href="#info" class="po-admin-tab" data-tab="info">DSGVO-Info</a>
				</div>

				<form method="post">
					<?php wp_nonce_field('parkourone_consent_nonce'); ?>

					<!-- Services Tab -->
					<div class="po-admin-panel active" id="panel-services">
						<div class="po-info-box">
							<h4>Welche Dienste verwendet Ihre Website?</h4>
							<p>Aktivieren Sie alle Dienste, die auf dieser Website verwendet werden. Jeder aktivierte Dienst wird im Cookie-Banner angezeigt und benötigt die Zustimmung der Besucher.</p>
						</div>

						<?php foreach ($available_services as $category_id => $services): ?>
							<?php if (empty($services)) continue; ?>
							<div class="po-category-section">
								<div class="po-category-header">
									<?php echo esc_html($category_info[$category_id]['name'] ?? $category_id); ?>
									<span style="font-weight: normal; color: #666;"> - <?php echo esc_html($category_info[$category_id]['description'] ?? ''); ?></span>
								</div>
								<div class="po-service-list">
									<?php foreach ($services as $service_id => $service): ?>
										<div class="po-service-item">
											<input
												type="checkbox"
												name="parkourone_consent[enabled_services][]"
												value="<?php echo esc_attr($service_id); ?>"
												id="service_<?php echo esc_attr($service_id); ?>"
												<?php checked(in_array($service_id, $options['enabled_services'])); ?>
											>
											<div class="po-service-info">
												<label for="service_<?php echo esc_attr($service_id); ?>" class="po-service-name">
													<?php echo esc_html($service['name']); ?>
												</label>
												<div class="po-service-desc"><?php echo esc_html($service['description']); ?></div>
												<div class="po-service-meta">
													<?php if (!empty($service['provider'])): ?>
														<span><strong>Anbieter:</strong> <?php echo esc_html($service['provider']); ?></span>
													<?php endif; ?>
													<?php if (!empty($service['country'])): ?>
														<span class="<?php echo $service['country'] !== 'EU' ? 'po-service-country-warning' : ''; ?>">
															<strong>Land:</strong> <?php echo esc_html($service['country']); ?>
															<?php if ($service['country'] !== 'EU'): ?>
																(Drittland-Transfer!)
															<?php endif; ?>
														</span>
													<?php endif; ?>
													<?php if (!empty($service['cookies'])): ?>
														<span><strong>Cookies:</strong> <?php echo esc_html(implode(', ', array_slice($service['cookies'], 0, 3))); ?><?php echo count($service['cookies']) > 3 ? '...' : ''; ?></span>
													<?php endif; ?>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>

						<div class="po-form-section">
							<h3>Google Consent Mode v2</h3>
							<div class="po-form-row">
								<label>Aktivieren</label>
								<label style="display: flex; align-items: center; gap: 10px;">
									<input type="checkbox" name="parkourone_consent[google_consent_mode]" value="1" <?php checked($options['google_consent_mode']); ?>>
									Google Consent Mode v2 aktivieren
								</label>
							</div>
							<p class="description">Wenn aktiviert, werden Google Analytics und Google Ads automatisch über den Google Consent Mode v2 gesteuert. Dies ist ab März 2024 für Google Ads Pflicht.</p>
						</div>
					</div>

					<!-- Design Tab -->
					<div class="po-admin-panel" id="panel-design">
						<div class="po-form-section">
							<h3>Banner-Texte</h3>
							<p class="description" style="margin-bottom: 15px;">Lassen Sie Felder leer um die Standardtexte zu verwenden.</p>

							<div class="po-form-row">
								<label>Titel</label>
								<input type="text" name="parkourone_consent[banner_title]" value="<?php echo esc_attr($options['banner_title']); ?>" placeholder="Wir respektieren Ihre Privatsphäre">
							</div>
							<div class="po-form-row">
								<label>Text</label>
								<textarea name="parkourone_consent[banner_text]" placeholder="Wir verwenden Cookies, um Ihre Erfahrung zu verbessern..."><?php echo esc_textarea($options['banner_text']); ?></textarea>
							</div>
						</div>

						<div class="po-form-section">
							<h3>Button-Beschriftungen</h3>
							<div class="po-form-row">
								<label>"Alle akzeptieren"</label>
								<input type="text" name="parkourone_consent[btn_accept_all]" value="<?php echo esc_attr($options['btn_accept_all']); ?>" placeholder="Alle akzeptieren">
							</div>
							<div class="po-form-row">
								<label>"Nur Notwendige"</label>
								<input type="text" name="parkourone_consent[btn_reject_all]" value="<?php echo esc_attr($options['btn_reject_all']); ?>" placeholder="Nur Notwendige">
							</div>
							<div class="po-form-row">
								<label>"Einstellungen"</label>
								<input type="text" name="parkourone_consent[btn_settings]" value="<?php echo esc_attr($options['btn_settings']); ?>" placeholder="Einstellungen anpassen">
							</div>
						</div>

						<div class="po-form-section">
							<h3>Position</h3>
							<div class="po-form-row">
								<label>Banner-Position</label>
								<select name="parkourone_consent[position]">
									<option value="bottom" <?php selected($options['position'], 'bottom'); ?>>Unten (Standard)</option>
									<option value="center" <?php selected($options['position'], 'center'); ?>>Mitte (Modal)</option>
								</select>
							</div>
						</div>
					</div>

					<!-- Log Tab -->
					<div class="po-admin-panel" id="panel-log">
						<div class="po-info-box">
							<h4>Consent-Protokoll (Audit-Trail)</h4>
							<p>Hier werden alle Cookie-Zustimmungen protokolliert. Dies dient als Nachweis für Aufsichtsbehörden gemäß Art. 7 Abs. 1 DSGVO.</p>
						</div>

						<?php
						global $wpdb;
						$table_name = $wpdb->prefix . 'po_consent_log';
						$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

						if ($table_exists):
							// Audit-Log Integrität prüfen
							$integrity = PO_Consent_Manager::verify_audit_log_integrity();
							?>

							<!-- Integritäts-Status -->
							<div class="po-integrity-status" style="margin-bottom: 20px; padding: 15px; border-radius: 4px; <?php echo $integrity['valid'] ? 'background: #d4edda; border: 1px solid #c3e6cb;' : 'background: #f8d7da; border: 1px solid #f5c6cb;'; ?>">
								<?php if ($integrity['valid']): ?>
									<strong style="color: #155724;">✓ Audit-Log Integrität: VERIFIZIERT</strong>
									<p style="margin: 5px 0 0; color: #155724;">Alle <?php echo number_format($integrity['total_entries']); ?> Einträge sind kryptographisch verkettet und unverändert.</p>
								<?php else: ?>
									<strong style="color: #721c24;">✗ Audit-Log Integrität: FEHLER</strong>
									<p style="margin: 5px 0 0; color: #721c24;"><?php echo count($integrity['broken_links']); ?> fehlerhafte Verkettungen gefunden. Mögliche Manipulation!</p>
								<?php endif; ?>
							</div>

							<?php
							$logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");
							?>
							<table class="po-log-table">
								<thead>
									<tr>
										<th>Datum/Zeit</th>
										<th>IP (Hash)</th>
										<th>User</th>
										<th>Kategorien</th>
										<th>DNT/GPC</th>
										<th>Kette</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($logs)): ?>
										<tr><td colspan="6" style="text-align: center; color: #666;">Noch keine Einträge vorhanden.</td></tr>
									<?php else: ?>
										<?php foreach ($logs as $log): ?>
											<tr>
												<td><?php echo esc_html($log->timestamp); ?></td>
												<td><code title="<?php echo esc_attr($log->ip_hash); ?>"><?php echo esc_html(substr($log->ip_hash, 0, 8)); ?>...</code></td>
												<td>
													<?php if (!empty($log->user_id)): ?>
														<?php $user = get_userdata($log->user_id); ?>
														<?php echo $user ? esc_html($user->user_login) : 'User #' . $log->user_id; ?>
													<?php else: ?>
														<span style="color: #999;">Gast</span>
													<?php endif; ?>
												</td>
												<td>
													<?php
													$cats = json_decode($log->consent_data, true);
													$active = [];
													if ($cats) {
														foreach ($cats as $cat => $enabled) {
															if ($enabled) $active[] = ucfirst(substr($cat, 0, 1));
														}
													}
													echo esc_html(implode('/', $active));
													?>
												</td>
												<td>
													<?php if (!empty($log->dnt_enabled)): ?><span title="Do Not Track">DNT</span><?php endif; ?>
													<?php if (!empty($log->gpc_enabled)): ?><span title="Global Privacy Control">GPC</span><?php endif; ?>
													<?php if (empty($log->dnt_enabled) && empty($log->gpc_enabled)): ?>-<?php endif; ?>
												</td>
												<td>
													<code title="Previous: <?php echo esc_attr($log->previous_hash ?? 'genesis'); ?>"><?php echo esc_html(substr($log->integrity_hash, 0, 6)); ?></code>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>

							<p style="margin-top: 15px;">
								<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=parkourone-consent&action=export_log'), 'po_export_log'); ?>" class="button">
									CSV Export (Art. 15 DSGVO)
								</a>
							</p>
						<?php else: ?>
							<p style="color: #666;">Die Log-Tabelle wird erstellt, sobald der erste Besucher eine Cookie-Entscheidung trifft.</p>
						<?php endif; ?>
					</div>

					<!-- Info Tab -->
					<div class="po-admin-panel" id="panel-info">
						<!-- MU-Plugin Installation -->
						<div class="po-form-section" style="<?php echo $mu_plugin_installed ? 'background: #d4edda; padding: 20px; margin: -20px -20px 20px; border-bottom: 2px solid #28a745;' : 'background: #f8d7da; padding: 20px; margin: -20px -20px 20px; border-bottom: 2px solid #dc3545;'; ?>">
							<h3 style="margin-top: 0; <?php echo $mu_plugin_installed ? 'color: #155724;' : 'color: #721c24;'; ?>">
								<?php echo $mu_plugin_installed ? '✓ MU-Plugin Installation' : '⚠️ MU-Plugin Installation (ERFORDERLICH)'; ?>
							</h3>

							<?php if ($mu_plugin_installed): ?>
								<p style="color: #155724; margin: 0;">
									Das MU-Plugin ist korrekt installiert. Server-seitiges Cookie-Blocking ist aktiv.
								</p>
							<?php else: ?>
								<p style="color: #721c24;">
									<strong>Das MU-Plugin muss installiert werden für vollständige DSGVO-Compliance!</strong>
								</p>
								<p style="color: #721c24;">Es blockiert Tracking-Cookies <em>bevor</em> WordPress lädt - das kann das Theme alleine nicht.</p>

								<div style="background: #fff; padding: 15px; border-radius: 4px; margin-top: 15px;">
									<p style="margin: 0 0 10px;"><strong>Installation in 2 Schritten:</strong></p>
									<ol style="margin: 0; padding-left: 20px;">
										<li style="margin-bottom: 10px;">
											<a href="<?php echo esc_url($download_url); ?>" class="button button-primary">
												1. Datei herunterladen
											</a>
										</li>
										<li>
											Per FTP/SFTP hochladen nach:<br>
											<code style="background: #f0f0f1; padding: 5px 10px; display: inline-block; margin-top: 5px;">
												wp-content/mu-plugins/parkourone-consent-early.php
											</code>
										</li>
									</ol>
									<p style="margin: 15px 0 0; font-size: 12px; color: #666;">
										Falls der Ordner <code>mu-plugins</code> nicht existiert, bitte erstellen.
									</p>
								</div>
							<?php endif; ?>
						</div>

						<div class="po-info-box">
							<h4>DSGVO-Compliance Checkliste</h4>
							<p>Dieses Cookie-Consent-System erfüllt folgende Anforderungen:</p>
							<ul>
								<li><strong>Opt-In (Prior Consent):</strong> Cookies werden erst nach Zustimmung gesetzt</li>
								<li><strong>Granulare Zustimmung:</strong> Nutzer können einzelne Kategorien wählen</li>
								<li><strong>Gleichwertige Buttons:</strong> "Ablehnen" ist genauso prominent wie "Akzeptieren"</li>
								<li><strong>Widerrufs-Möglichkeit:</strong> Link im Footer zum Ändern der Einstellungen</li>
								<li><strong>Consent-Logging:</strong> Alle Zustimmungen werden protokolliert</li>
								<li><strong>IP-Anonymisierung:</strong> IPs werden gehasht und gekürzt</li>
								<li><strong>Integrity-Hash:</strong> Log-Einträge sind manipulationssicher</li>
								<li><strong>Informationspflichten:</strong> Anbieter, Land und Cookies werden angezeigt</li>
								<li><strong>Drittland-Hinweis:</strong> USA-Dienste werden speziell gekennzeichnet</li>
								<li><strong>DNT/GPC Support:</strong> Do Not Track und Global Privacy Control werden respektiert</li>
								<li><strong>Cross-Domain:</strong> Consent gilt für alle ParkourONE Standorte</li>
							</ul>
						</div>

						<div class="po-form-section">
							<h3>Wichtige Hinweise</h3>
							<p><strong>Datenschutzerklärung:</strong> Aktualisieren Sie Ihre Datenschutzerklärung und fügen Sie Informationen zu allen verwendeten Cookies hinzu.</p>
							<p><strong>Impressum:</strong> Das Banner verlinkt automatisch zu /impressum/ und zur WordPress Datenschutz-Seite.</p>
							<p><strong>Cookie-Link im Footer:</strong> Der Footer enthält bereits den Link "Cookie-Einstellungen" der automatisch funktioniert.</p>
						</div>
					</div>

					<p style="margin-top: 20px;">
						<button type="submit" name="parkourone_consent_save" class="button button-primary button-large">Einstellungen speichern</button>
					</p>
				</form>
			</div>

			<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Tab switching
				document.querySelectorAll('.po-admin-tab').forEach(function(tab) {
					tab.addEventListener('click', function(e) {
						e.preventDefault();
						var targetId = 'panel-' + this.dataset.tab;

						// Tabs
						document.querySelectorAll('.po-admin-tab').forEach(t => t.classList.remove('active'));
						this.classList.add('active');

						// Panels
						document.querySelectorAll('.po-admin-panel').forEach(p => p.classList.remove('active'));
						document.getElementById(targetId).classList.add('active');
					});
				});
			});
			</script>
		</div>
		<?php
	}
}

// Initialize
PO_Consent_Admin::get_instance();
