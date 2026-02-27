<?php
/**
 * ParkourONE Redirects
 * Redirect-Verwaltung und 404-Logging
 */

if (!defined('ABSPATH')) exit;

// =====================================================
// A) Frontend Redirect-Ausführung
// =====================================================

function parkourone_execute_redirects() {
	if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON)) {
		return;
	}

	$redirects = get_option('parkourone_redirects', []);
	if (empty($redirects)) return;

	$current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

	foreach ($redirects as $index => $redirect) {
		$source = trim($redirect['source'] ?? '', '/');
		if ($source !== '' && $source === $current_path) {
			$redirects[$index]['hits'] = ($redirect['hits'] ?? 0) + 1;
			update_option('parkourone_redirects', $redirects);

			$target = $redirect['target'] ?? '/';
			if (strpos($target, 'http') !== 0) {
				$target = home_url($target);
			}

			$type = in_array((int)($redirect['type'] ?? 301), [301, 302], true) ? (int) $redirect['type'] : 301;
			wp_redirect($target, $type);
			exit;
		}
	}
}
add_action('template_redirect', 'parkourone_execute_redirects', 11);

// =====================================================
// B) 404-Logging
// =====================================================

function parkourone_log_404() {
	if (!is_404() || is_admin()) return;

	$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

	// Admin/Asset-Pfade ignorieren
	if (strpos($path, 'wp-admin') === 0 || strpos($path, 'wp-content') === 0 || strpos($path, 'wp-json') === 0) {
		return;
	}

	$log = get_option('parkourone_404_log', []);
	$found = false;

	foreach ($log as &$entry) {
		if (trim($entry['url'] ?? '', '/') === $path) {
			$entry['count'] = ($entry['count'] ?? 0) + 1;
			$entry['last_seen'] = time();
			$found = true;
			break;
		}
	}
	unset($entry);

	if (!$found) {
		$log[] = [
			'url'       => '/' . $path,
			'count'     => 1,
			'last_seen' => time(),
			'referrer'  => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
		];
	}

	// Max 100 Einträge
	if (count($log) > 100) {
		usort($log, function($a, $b) { return ($b['count'] ?? 0) - ($a['count'] ?? 0); });
		$log = array_slice($log, 0, 100);
	}

	update_option('parkourone_404_log', $log, false);
}
add_action('template_redirect', 'parkourone_log_404', 20);

// =====================================================
// C) Migration: Hardcoded Redirect übernehmen
// =====================================================

function parkourone_migrate_hardcoded_redirects() {
	if (get_option('parkourone_redirects_migrated', false)) return;

	$redirects = get_option('parkourone_redirects', []);

	$exists = false;
	foreach ($redirects as $r) {
		if (trim($r['source'] ?? '', '/') === 'angebot/klassen/probetraining') {
			$exists = true;
			break;
		}
	}

	if (!$exists) {
		$redirects[] = [
			'source'  => '/angebot/klassen/probetraining',
			'target'  => '/bringyourbuddy',
			'type'    => 301,
			'hits'    => 0,
			'created' => time(),
		];
		update_option('parkourone_redirects', $redirects);
	}

	update_option('parkourone_redirects_migrated', true);
}
add_action('admin_init', 'parkourone_migrate_hardcoded_redirects');

// =====================================================
// D) Admin-Seite
// =====================================================

function parkourone_redirects_page() {
	$redirects = get_option('parkourone_redirects', []);
	$log = get_option('parkourone_404_log', []);
	$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'redirects';
	$notice = '';

	// ── Redirect hinzufügen / bearbeiten ──
	if (isset($_POST['parkourone_redirect_save']) && check_admin_referer('parkourone_redirects_nonce')) {
		$source = '/' . trim(sanitize_text_field($_POST['redirect_source'] ?? ''), '/');
		$target_raw = trim($_POST['redirect_target'] ?? '');
		$target = (strpos($target_raw, 'http') === 0) ? esc_url_raw($target_raw) : '/' . trim(sanitize_text_field($target_raw), '/');
		$type = in_array((int)($_POST['redirect_type'] ?? 301), [301, 302], true) ? (int) $_POST['redirect_type'] : 301;
		$edit_index = isset($_POST['redirect_edit_index']) && $_POST['redirect_edit_index'] !== '' ? absint($_POST['redirect_edit_index']) : null;

		if ($source !== '/' && $target !== '/') {
			// Duplikat-Check (nur bei Neuanlage oder wenn Source geändert)
			$duplicate = false;
			foreach ($redirects as $i => $r) {
				if ($i !== $edit_index && trim($r['source'] ?? '', '/') === trim($source, '/')) {
					$duplicate = true;
					break;
				}
			}

			if ($duplicate) {
				$notice = '<div class="notice notice-error"><p>Ein Redirect für diesen Quell-Pfad existiert bereits.</p></div>';
			} else {
				if ($edit_index !== null && isset($redirects[$edit_index])) {
					$redirects[$edit_index]['source'] = $source;
					$redirects[$edit_index]['target'] = $target;
					$redirects[$edit_index]['type'] = $type;
					$notice = '<div class="notice notice-success"><p>Redirect aktualisiert.</p></div>';
				} else {
					$redirects[] = [
						'source'  => $source,
						'target'  => $target,
						'type'    => $type,
						'hits'    => 0,
						'created' => time(),
					];
					$notice = '<div class="notice notice-success"><p>Redirect hinzugefügt.</p></div>';
				}
				update_option('parkourone_redirects', $redirects);
			}
		} else {
			$notice = '<div class="notice notice-error"><p>Bitte Quelle und Ziel angeben.</p></div>';
		}
	}

	// ── Redirect löschen ──
	if (isset($_POST['parkourone_redirect_delete']) && check_admin_referer('parkourone_redirect_delete_nonce')) {
		$del_index = absint($_POST['redirect_delete_index'] ?? -1);
		if (isset($redirects[$del_index])) {
			array_splice($redirects, $del_index, 1);
			update_option('parkourone_redirects', $redirects);
			$notice = '<div class="notice notice-success"><p>Redirect gelöscht.</p></div>';
		}
	}

	// ── 404 → Redirect erstellen ──
	$prefill_source = '';
	if (isset($_POST['parkourone_404_to_redirect']) && check_admin_referer('parkourone_404_redirect_nonce')) {
		$idx = absint($_POST['log_index'] ?? -1);
		if (isset($log[$idx])) {
			$prefill_source = $log[$idx]['url'] ?? '';
			array_splice($log, $idx, 1);
			update_option('parkourone_404_log', $log, false);
			$active_tab = 'redirects';
		}
	}

	// ── 404 Eintrag löschen ──
	if (isset($_POST['parkourone_404_delete']) && check_admin_referer('parkourone_404_delete_nonce')) {
		$idx = absint($_POST['log_delete_index'] ?? -1);
		if (isset($log[$idx])) {
			array_splice($log, $idx, 1);
			update_option('parkourone_404_log', $log, false);
			$notice = '<div class="notice notice-success"><p>404-Eintrag gelöscht.</p></div>';
		}
		$active_tab = '404';
	}

	// ── 404 Log leeren ──
	if (isset($_POST['parkourone_404_clear']) && check_admin_referer('parkourone_404_clear_nonce')) {
		$log = [];
		update_option('parkourone_404_log', [], false);
		$notice = '<div class="notice notice-success"><p>404-Log geleert.</p></div>';
		$active_tab = '404';
	}

	// 404 sortieren nach Aufrufen
	usort($log, function($a, $b) { return ($b['count'] ?? 0) - ($a['count'] ?? 0); });

	?>
	<div class="wrap">
		<h1>Redirects</h1>
		<p>Verwalte URL-Weiterleitungen und behalte 404-Fehler im Blick.</p>
		<?php echo $notice; ?>

		<style>
			.po-redirect-admin { max-width: 960px; margin-top: 20px; }
			.po-tabs { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 20px; }
			.po-tab { padding: 10px 20px; cursor: pointer; border: 1px solid transparent; border-bottom: none; border-radius: 4px 4px 0 0; background: transparent; font-size: 14px; font-weight: 500; color: #646970; position: relative; bottom: -1px; }
			.po-tab:hover { color: #1d2327; }
			.po-tab.is-active { background: #fff; border-color: #c3c4c7; color: #1d2327; }
			.po-panel { display: none; }
			.po-panel.is-active { display: block; }
			.po-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
			.po-section h3 { margin: 0 0 15px; font-size: 14px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; }
			.po-form-row { display: grid; grid-template-columns: 120px 1fr; gap: 10px; margin-bottom: 12px; align-items: center; }
			.po-form-row label { font-weight: 500; }
			.po-form-row input[type="text"] { width: 100%; max-width: 400px; }
			.po-form-row select { max-width: 200px; }
			.po-redirect-table { border-collapse: collapse; width: 100%; }
			.po-redirect-table th { text-align: left; padding: 10px 12px; border-bottom: 2px solid #c3c4c7; font-size: 13px; color: #646970; }
			.po-redirect-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f1; font-size: 13px; vertical-align: middle; }
			.po-redirect-table tr:hover td { background: #f9f9f9; }
			.po-redirect-table .po-path { font-family: monospace; font-size: 12px; background: #f0f0f1; padding: 2px 8px; border-radius: 4px; word-break: break-all; }
			.po-redirect-table .po-actions { white-space: nowrap; }
			.po-redirect-table .po-actions button { background: none; border: none; cursor: pointer; color: #2271b1; font-size: 13px; padding: 2px 6px; }
			.po-redirect-table .po-actions button:hover { color: #135e96; text-decoration: underline; }
			.po-redirect-table .po-actions button.po-delete { color: #b32d2e; }
			.po-redirect-table .po-actions button.po-delete:hover { color: #a00; }
			.po-badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
			.po-badge--301 { background: #e7f5ea; color: #00a32a; }
			.po-badge--302 { background: #fcf0e3; color: #996800; }
			.po-hits { color: #646970; font-variant-numeric: tabular-nums; }
			.po-empty { text-align: center; padding: 40px 20px; color: #646970; }
			.po-404-count { font-weight: 600; font-variant-numeric: tabular-nums; }
			.po-404-referrer { font-size: 11px; color: #999; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
			.po-clear-row { text-align: right; margin-bottom: 15px; }
		</style>

		<div class="po-redirect-admin">
			<div class="po-tabs">
				<button type="button" class="po-tab <?php echo $active_tab === 'redirects' ? 'is-active' : ''; ?>" data-tab="redirects">
					Redirects <?php if (!empty($redirects)): ?><span style="color:#646970;">(<?php echo count($redirects); ?>)</span><?php endif; ?>
				</button>
				<button type="button" class="po-tab <?php echo $active_tab === '404' ? 'is-active' : ''; ?>" data-tab="404">
					404-Seiten <?php if (!empty($log)): ?><span style="color:#b32d2e;">(<?php echo count($log); ?>)</span><?php endif; ?>
				</button>
			</div>

			<!-- ═══ Tab 1: Redirects ═══ -->
			<div class="po-panel <?php echo $active_tab === 'redirects' ? 'is-active' : ''; ?>" data-panel="redirects">

				<?php if (!empty($redirects)): ?>
					<table class="po-redirect-table">
						<thead>
							<tr>
								<th>Quelle</th>
								<th>Ziel</th>
								<th>Typ</th>
								<th>Hits</th>
								<th>Erstellt</th>
								<th>Aktionen</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($redirects as $i => $r): ?>
								<tr>
									<td><span class="po-path"><?php echo esc_html($r['source'] ?? ''); ?></span></td>
									<td><span class="po-path"><?php echo esc_html($r['target'] ?? ''); ?></span></td>
									<td><span class="po-badge po-badge--<?php echo (int)($r['type'] ?? 301); ?>"><?php echo (int)($r['type'] ?? 301); ?></span></td>
									<td class="po-hits"><?php echo number_format_i18n($r['hits'] ?? 0); ?></td>
									<td><?php echo !empty($r['created']) ? date_i18n('j. M Y', $r['created']) : '—'; ?></td>
									<td class="po-actions">
										<button type="button" class="po-edit-redirect"
												data-index="<?php echo $i; ?>"
												data-source="<?php echo esc_attr($r['source'] ?? ''); ?>"
												data-target="<?php echo esc_attr($r['target'] ?? ''); ?>"
												data-type="<?php echo (int)($r['type'] ?? 301); ?>">Bearbeiten</button>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field('parkourone_redirect_delete_nonce'); ?>
											<input type="hidden" name="redirect_delete_index" value="<?php echo $i; ?>">
											<button type="submit" name="parkourone_redirect_delete" class="po-delete" onclick="return confirm('Redirect wirklich löschen?');">Löschen</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<div class="po-empty">
						<p>Noch keine Redirects vorhanden.</p>
					</div>
				<?php endif; ?>

				<!-- Formular: Redirect hinzufügen/bearbeiten -->
				<div class="po-section" style="margin-top: 20px;">
					<h3 id="po-redirect-form-title">Redirect hinzufügen</h3>
					<form method="post" id="po-redirect-form">
						<?php wp_nonce_field('parkourone_redirects_nonce'); ?>
						<input type="hidden" name="redirect_edit_index" id="po-redirect-edit-index" value="">

						<div class="po-form-row">
							<label for="po-redirect-source">Quelle</label>
							<input type="text" id="po-redirect-source" name="redirect_source" placeholder="/alter-pfad" value="<?php echo esc_attr($prefill_source); ?>" required>
						</div>
						<div class="po-form-row">
							<label for="po-redirect-target">Ziel</label>
							<input type="text" id="po-redirect-target" name="redirect_target" placeholder="/neuer-pfad oder https://..." required>
						</div>
						<div class="po-form-row">
							<label for="po-redirect-type">Typ</label>
							<select id="po-redirect-type" name="redirect_type">
								<option value="301">301 – Permanent</option>
								<option value="302">302 – Temporär</option>
							</select>
						</div>
						<div style="margin-top: 15px;">
							<button type="submit" name="parkourone_redirect_save" class="button button-primary" id="po-redirect-submit">Redirect hinzufügen</button>
							<button type="button" class="button" id="po-redirect-cancel" style="display:none;">Abbrechen</button>
						</div>
					</form>
				</div>
			</div>

			<!-- ═══ Tab 2: 404-Seiten ═══ -->
			<div class="po-panel <?php echo $active_tab === '404' ? 'is-active' : ''; ?>" data-panel="404">

				<?php if (!empty($log)): ?>
					<div class="po-clear-row">
						<form method="post" style="display:inline;">
							<?php wp_nonce_field('parkourone_404_clear_nonce'); ?>
							<button type="submit" name="parkourone_404_clear" class="button" onclick="return confirm('Gesamtes 404-Log leeren?');">Log leeren</button>
						</form>
					</div>

					<table class="po-redirect-table">
						<thead>
							<tr>
								<th>URL</th>
								<th>Aufrufe</th>
								<th>Zuletzt gesehen</th>
								<th>Referrer</th>
								<th>Aktionen</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($log as $i => $entry): ?>
								<tr>
									<td><span class="po-path"><?php echo esc_html($entry['url'] ?? ''); ?></span></td>
									<td class="po-404-count"><?php echo number_format_i18n($entry['count'] ?? 0); ?></td>
									<td><?php echo !empty($entry['last_seen']) ? date_i18n('j. M Y, H:i', $entry['last_seen']) : '—'; ?></td>
									<td><span class="po-404-referrer" title="<?php echo esc_attr($entry['referrer'] ?? ''); ?>"><?php echo esc_html($entry['referrer'] ?? '—'); ?></span></td>
									<td class="po-actions">
										<form method="post" style="display:inline;">
											<?php wp_nonce_field('parkourone_404_redirect_nonce'); ?>
											<input type="hidden" name="log_index" value="<?php echo $i; ?>">
											<button type="submit" name="parkourone_404_to_redirect">Redirect erstellen</button>
										</form>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field('parkourone_404_delete_nonce'); ?>
											<input type="hidden" name="log_delete_index" value="<?php echo $i; ?>">
											<button type="submit" name="parkourone_404_delete" class="po-delete">Löschen</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<div class="po-empty">
						<p>Keine 404-Fehler geloggt. Sobald Besucher nicht existierende Seiten aufrufen, erscheinen diese hier.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		(function() {
			// Tab-Switching
			document.querySelectorAll('.po-tab').forEach(function(tab) {
				tab.addEventListener('click', function() {
					document.querySelectorAll('.po-tab').forEach(function(t) { t.classList.remove('is-active'); });
					document.querySelectorAll('.po-panel').forEach(function(p) { p.classList.remove('is-active'); });
					tab.classList.add('is-active');
					var panel = document.querySelector('[data-panel="' + tab.dataset.tab + '"]');
					if (panel) panel.classList.add('is-active');
				});
			});

			// Edit-Redirect Buttons
			document.querySelectorAll('.po-edit-redirect').forEach(function(btn) {
				btn.addEventListener('click', function() {
					document.getElementById('po-redirect-edit-index').value = btn.dataset.index;
					document.getElementById('po-redirect-source').value = btn.dataset.source;
					document.getElementById('po-redirect-target').value = btn.dataset.target;
					document.getElementById('po-redirect-type').value = btn.dataset.type;
					document.getElementById('po-redirect-form-title').textContent = 'Redirect bearbeiten';
					document.getElementById('po-redirect-submit').textContent = 'Aktualisieren';
					document.getElementById('po-redirect-cancel').style.display = '';
					document.getElementById('po-redirect-form').scrollIntoView({ behavior: 'smooth' });
				});
			});

			// Cancel Edit
			var cancelBtn = document.getElementById('po-redirect-cancel');
			if (cancelBtn) {
				cancelBtn.addEventListener('click', function() {
					document.getElementById('po-redirect-edit-index').value = '';
					document.getElementById('po-redirect-source').value = '';
					document.getElementById('po-redirect-target').value = '';
					document.getElementById('po-redirect-type').value = '301';
					document.getElementById('po-redirect-form-title').textContent = 'Redirect hinzufügen';
					document.getElementById('po-redirect-submit').textContent = 'Redirect hinzufügen';
					cancelBtn.style.display = 'none';
				});
			}
		})();
		</script>
	</div>
	<?php
}
