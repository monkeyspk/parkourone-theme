<?php
/**
 * ParkourONE Link Checker
 * Scannt publizierte Seiten/Posts nach problematischen Links in Gutenberg-Blöcken.
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX-Handler registrieren
 */
add_action('wp_ajax_parkourone_scan_links', 'parkourone_ajax_scan_links');

/**
 * Admin-Seite rendern
 */
function parkourone_link_checker_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Zugriff verweigert.');
	}

	$nonce = wp_create_nonce('parkourone_scan_links');
	?>
	<div class="wrap">
		<h1>Link Check</h1>
		<p>Scannt alle publizierten Seiten und Posts nach leeren, Platzhalter- (<code>#</code>) und internen 404-Links in Gutenberg-Blöcken.</p>

		<p>
			<button id="po-link-scan-btn" class="button button-primary">Scan starten</button>
			<span id="po-link-scan-status" style="margin-left: 12px; display: none;">
				<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>
				Scanne&hellip;
			</span>
		</p>

		<div id="po-link-scan-summary" style="display: none; margin: 16px 0; padding: 12px 16px; background: #fff; border-left: 4px solid #d63638;"></div>

		<table id="po-link-scan-results" class="widefat striped" style="display: none;">
			<thead>
				<tr>
					<th>Seite</th>
					<th>Block</th>
					<th>Attribut</th>
					<th>Wert</th>
					<th>Problem</th>
					<th>Aktion</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>

		<div id="po-link-scan-ok" style="display: none; margin: 16px 0; padding: 12px 16px; background: #fff; border-left: 4px solid #00a32a;">
			Keine problematischen Links gefunden.
		</div>
	</div>

	<script>
	(function() {
		var btn = document.getElementById('po-link-scan-btn');
		var status = document.getElementById('po-link-scan-status');
		var summary = document.getElementById('po-link-scan-summary');
		var table = document.getElementById('po-link-scan-results');
		var tbody = table.querySelector('tbody');
		var okMsg = document.getElementById('po-link-scan-ok');

		btn.addEventListener('click', function() {
			btn.disabled = true;
			status.style.display = 'inline';
			summary.style.display = 'none';
			table.style.display = 'none';
			okMsg.style.display = 'none';
			tbody.innerHTML = '';

			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxurl);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function() {
				btn.disabled = false;
				status.style.display = 'none';

				if (xhr.status !== 200) {
					summary.style.display = 'block';
					summary.textContent = 'Fehler beim Scan (HTTP ' + xhr.status + ')';
					return;
				}

				var data;
				try { data = JSON.parse(xhr.responseText); } catch(e) {
					summary.style.display = 'block';
					summary.textContent = 'Ungueltige Antwort vom Server.';
					return;
				}

				if (!data.success) {
					summary.style.display = 'block';
					summary.textContent = data.data || 'Unbekannter Fehler.';
					return;
				}

				var issues = data.data.issues;
				if (!issues.length) {
					okMsg.style.display = 'block';
					return;
				}

				summary.style.display = 'block';
				summary.innerHTML = '<strong>' + issues.length + ' Problem' + (issues.length !== 1 ? 'e' : '') +
					'</strong> auf <strong>' + data.data.page_count + ' Seite' + (data.data.page_count !== 1 ? 'n' : '') + '</strong> gefunden.';

				for (var i = 0; i < issues.length; i++) {
					var r = issues[i];
					var tr = document.createElement('tr');
					var val = r.value || '';
					if (val.length > 60) val = val.substring(0, 57) + '...';

					tr.innerHTML =
						'<td><a href="' + r.edit_url + '" target="_blank">' + escHtml(r.page_title) + '</a></td>' +
						'<td><code>' + escHtml(r.block) + '</code></td>' +
						'<td><code>' + escHtml(r.attribute) + '</code></td>' +
						'<td><code>' + escHtml(val) + '</code></td>' +
						'<td>' + escHtml(r.problem) + '</td>' +
						'<td><a href="' + r.edit_url + '" class="button button-small" target="_blank">Bearbeiten</a></td>';
					tbody.appendChild(tr);
				}
				table.style.display = 'table';
			};
			xhr.onerror = function() {
				btn.disabled = false;
				status.style.display = 'none';
				summary.style.display = 'block';
				summary.textContent = 'Netzwerkfehler.';
			};
			xhr.send('action=parkourone_scan_links&_ajax_nonce=<?php echo esc_js($nonce); ?>');
		});

		function escHtml(s) {
			var d = document.createElement('div');
			d.appendChild(document.createTextNode(s));
			return d.innerHTML;
		}
	})();
	</script>
	<?php
}

/**
 * AJAX: Alle publizierten Seiten/Posts scannen
 */
function parkourone_ajax_scan_links() {
	check_ajax_referer('parkourone_scan_links');

	if (!current_user_can('manage_options')) {
		wp_send_json_error('Zugriff verweigert.');
	}

	$posts = get_posts([
		'post_type'      => ['page', 'post'],
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	]);

	$issues    = [];
	$page_ids  = [];

	foreach ($posts as $post) {
		$found = parkourone_parse_block_urls($post->post_content);

		foreach ($found as $item) {
			$problem = parkourone_classify_url($item['value']);
			if (!$problem) continue;

			$issues[] = [
				'page_title' => $post->post_title,
				'edit_url'   => get_edit_post_link($post->ID, 'raw'),
				'block'      => $item['block'],
				'attribute'  => $item['attribute'],
				'value'      => $item['value'],
				'problem'    => $problem,
			];
			$page_ids[$post->ID] = true;
		}
	}

	wp_send_json_success([
		'issues'     => $issues,
		'page_count' => count($page_ids),
	]);
}

/**
 * URL-Attribute die gescannt werden
 */
function parkourone_get_url_attributes() {
	return [
		'buttonUrl',
		'ctaUrl',
		'ctaSecondaryUrl',
		'secondButtonUrl',
		'linkUrl',
		'contactFormUrl',
		'impressumUrl',
		'datenschutzUrl',
		'cookiesUrl',
		'zentraleUrl',
		'badgeUrl',
	];
}

/**
 * Blocks parsen und URL-Attribute extrahieren
 */
function parkourone_parse_block_urls($content) {
	$blocks     = parse_blocks($content);
	$url_attrs  = parkourone_get_url_attributes();
	$results    = [];

	parkourone_walk_blocks($blocks, $url_attrs, $results);

	return $results;
}

/**
 * Rekursiv durch Blocks und deren Attribute gehen
 */
function parkourone_walk_blocks($blocks, $url_attrs, &$results) {
	foreach ($blocks as $block) {
		if (empty($block['blockName'])) {
			// Innere Blocks (innerBlocks) prüfen
			if (!empty($block['innerBlocks'])) {
				parkourone_walk_blocks($block['innerBlocks'], $url_attrs, $results);
			}
			continue;
		}

		$short_name = $block['blockName'];
		// parkourone/hero -> hero
		if (strpos($short_name, '/') !== false) {
			$short_name = substr($short_name, strpos($short_name, '/') + 1);
		}

		$attrs = $block['attrs'] ?? [];

		// Direkte URL-Attribute
		foreach ($url_attrs as $attr) {
			if (array_key_exists($attr, $attrs)) {
				$results[] = [
					'block'     => $short_name,
					'attribute' => $attr,
					'value'     => (string) $attrs[$attr],
				];
			}
		}

		// Verschachtelte Arrays (z.B. categories[], slides[], items[])
		foreach ($attrs as $key => $val) {
			if (!is_array($val)) continue;

			foreach ($val as $idx => $entry) {
				if (!is_array($entry)) continue;

				foreach ($url_attrs as $attr) {
					if (array_key_exists($attr, $entry)) {
						$results[] = [
							'block'     => $short_name,
							'attribute' => $key . '[' . $idx . '].' . $attr,
							'value'     => (string) $entry[$attr],
						];
					}
				}
			}
		}

		// Inner Blocks
		if (!empty($block['innerBlocks'])) {
			parkourone_walk_blocks($block['innerBlocks'], $url_attrs, $results);
		}
	}
}

/**
 * URL klassifizieren: leer, #, interner 404
 * Gibt Problem-String zurück oder null wenn OK
 */
function parkourone_classify_url($url) {
	$url = trim($url);

	if ($url === '') {
		return 'Leer';
	}

	if ($url === '#') {
		return 'Nur #';
	}

	// Externe URLs überspringen
	if (preg_match('#^https?://#i', $url)) {
		$site_url = home_url();
		// Nur interne URLs prüfen
		if (stripos($url, $site_url) !== 0) {
			return null;
		}
		// Absolute interne URL -> relativen Pfad extrahieren
		$url = substr($url, strlen($site_url));
	}

	// Anker-Links (z.B. #section) überspringen
	if (strpos($url, '#') === 0) {
		return null;
	}

	// Relative interne URLs prüfen
	if (strpos($url, '/') === 0) {
		return parkourone_check_internal_url($url);
	}

	return null;
}

/**
 * Prüfen ob ein interner Pfad existiert
 */
function parkourone_check_internal_url($path) {
	$path = rtrim($path, '/') . '/';

	// url_to_postid gibt 0 zurück wenn nicht gefunden
	$post_id = url_to_postid(home_url($path));

	if ($post_id > 0) {
		return null;
	}

	// Auch ohne trailing slash prüfen
	$post_id = url_to_postid(home_url(rtrim($path, '/')));

	if ($post_id > 0) {
		return null;
	}

	return 'Interner 404';
}
