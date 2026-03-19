<?php
defined('ABSPATH') || exit;

/**
 * ============================================
 * DIVI SHORTCODE CLEANUP (einmalig)
 * Entfernt alte Divi Builder Shortcodes aus allen Seiten.
 * Läuft einmal und deaktiviert sich dann selbst via Option.
 * ============================================
 */
add_action('admin_init', function() {
	if (get_option('parkourone_divi_cleanup_done')) return;
	if (!current_user_can('manage_options')) return;

	// Alle Seiten mit Divi-Shortcodes finden
	global $wpdb;
	$pages = $wpdb->get_results(
		"SELECT ID, post_content FROM {$wpdb->posts}
		 WHERE post_content LIKE '%[et_pb_%'
		 AND post_status IN ('publish', 'draft', 'private')
		 AND post_type IN ('page', 'post', 'product')"
	);

	if (empty($pages)) {
		update_option('parkourone_divi_cleanup_done', true);
		return;
	}

	$cleaned = 0;
	foreach ($pages as $page) {
		$content = $page->post_content;

		// Alle Divi-Shortcodes entfernen (öffnende + schließende)
		$clean = preg_replace('/\[\/?(et_pb_[^\]]*)\]/', '', $content);

		// Divi-Metadaten-Attribute entfernen die manchmal als Text übrig bleiben
		$clean = preg_replace('/\s*fb_built="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*_builder_version="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*global_colors_info="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*admin_label="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*background_[a-z_]+="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*custom_padding[a-z_]*="[^"]*"/', '', $clean);
		$clean = preg_replace('/\s*type="[0-9_]+"/', '', $clean);

		// Mehrfache Leerzeilen reduzieren
		$clean = preg_replace('/\n{3,}/', "\n\n", $clean);
		$clean = trim($clean);

		if ($clean !== $content) {
			$wpdb->update($wpdb->posts, ['post_content' => $clean], ['ID' => $page->ID]);
			$cleaned++;
			error_log('ParkourONE Divi Cleanup: Seite "' . get_the_title($page->ID) . '" (ID ' . $page->ID . ') bereinigt');
		}
	}

	update_option('parkourone_divi_cleanup_done', true);

	if ($cleaned > 0) {
		error_log('ParkourONE Divi Cleanup: ' . $cleaned . ' Seiten bereinigt');
		set_transient('parkourone_divi_cleanup_notice', $cleaned, 120);
	}
});

// Admin-Notice nach Cleanup
add_action('admin_notices', function() {
	$cleaned = get_transient('parkourone_divi_cleanup_notice');
	if ($cleaned) {
		delete_transient('parkourone_divi_cleanup_notice');
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo '<strong>Divi Cleanup abgeschlossen!</strong> ' . intval($cleaned) . ' Seite(n) von alten Divi-Shortcodes bereinigt.';
		echo '</p></div>';
	}
});

/**
 * ============================================
 * MAINTENANCE MODE (mit Admin-Toggle)
 * ============================================
 */

/**
 * Prüft ob Maintenance Mode aktiv ist
 */
function parkourone_is_maintenance_active() {
	return get_option('parkourone_maintenance_mode', false);
}

/**
 * Maintenance Mode - Zeigt "Wir sind gleich zurück" Seite
 */
function parkourone_maintenance_mode() {
	if (!parkourone_is_maintenance_active()) {
		return;
	}

	// Admins durchlassen
	if (current_user_can('manage_options')) {
		return;
	}

	// Login-Seite durchlassen
	if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
		return;
	}

	// Admin-Bereich durchlassen
	if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false) {
		return;
	}

	// AJAX durchlassen
	if (defined('DOING_AJAX') && DOING_AJAX) {
		return;
	}

	// Cron durchlassen
	if (defined('DOING_CRON') && DOING_CRON) {
		return;
	}

	// WooCommerce Checkout + Thank-You durchlassen
	if (function_exists('is_checkout') && is_checkout()) {
		return;
	}

	// WooCommerce Warenkorb durchlassen
	if (function_exists('is_cart') && is_cart()) {
		return;
	}

	// WooCommerce Mein Konto durchlassen (Verträge, Bestellungen etc.)
	if (function_exists('is_account_page') && is_account_page()) {
		return;
	}

	// WooCommerce AJAX (Fragments, Coupons etc.) durchlassen
	if (isset($_GET['wc-ajax'])) {
		return;
	}

	// REST API durchlassen
	if (defined('REST_REQUEST') && REST_REQUEST) {
		return;
	}

	// Maintenance-Seite laden
	$maintenance_file = get_template_directory() . '/maintenance.php';
	if (file_exists($maintenance_file)) {
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 3600');
		include $maintenance_file;
		exit;
	}
}
add_action('template_redirect', 'parkourone_maintenance_mode');

/**
 * Admin-Notice mit Toggle-Button
 */
function parkourone_maintenance_admin_notice() {
	if (!current_user_can('manage_options')) return;

	$is_active = parkourone_is_maintenance_active();

	if ($is_active) {
		echo '<div class="notice notice-warning" style="border-left-color: #2997ff; display: flex; align-items: center; justify-content: space-between;">';
		echo '<p><strong>🚧 Maintenance Mode aktiv!</strong> Besucher sehen die "Wir sind gleich zurück" Seite.</p>';
		echo '<form method="post" style="margin: 0;"><input type="hidden" name="parkourone_maintenance_toggle" value="off">';
		echo wp_nonce_field('parkourone_maintenance_toggle', '_wpnonce', true, false);
		echo '<button type="submit" class="button" style="background: #fff;">Deaktivieren</button></form>';
		echo '</div>';
	}
}
add_action('admin_notices', 'parkourone_maintenance_admin_notice');

/**
 * Toggle-Handler
 */
function parkourone_maintenance_toggle_handler() {
	if (!isset($_POST['parkourone_maintenance_toggle'])) return;
	if (!current_user_can('manage_options')) return;
	if (!wp_verify_nonce($_POST['_wpnonce'], 'parkourone_maintenance_toggle')) return;

	$new_state = $_POST['parkourone_maintenance_toggle'] === 'on';
	update_option('parkourone_maintenance_mode', $new_state);

	wp_redirect(remove_query_arg(['_wpnonce']));
	exit;
}
add_action('admin_init', 'parkourone_maintenance_toggle_handler');

/**
 * Admin Bar Toggle für schnellen Zugriff
 */
function parkourone_maintenance_admin_bar($wp_admin_bar) {
	if (!current_user_can('manage_options')) return;

	$is_active = parkourone_is_maintenance_active();

	$wp_admin_bar->add_node([
		'id' => 'maintenance-mode',
		'title' => $is_active
			? '<span style="color: #ffb900;">🚧 Maintenance AN</span>'
			: '<span style="color: #72aee6;">✓ Seite Live</span>',
		'href' => admin_url('admin.php?page=parkourone-maintenance'),
	]);
}
add_action('admin_bar_menu', 'parkourone_maintenance_admin_bar', 100);

/**
 * Admin-Seite für Maintenance Mode
 * Menü-Registrierung erfolgt in inc/admin-menu.php
 */
function parkourone_maintenance_admin_page_html($embedded = false) {
	if (!current_user_can('manage_options')) return;

	$is_active = parkourone_is_maintenance_active();
	?>
	<?php if (!$embedded): ?>
	<div class="wrap">
		<h1>Maintenance Mode</h1>
	<?php endif; ?>

		<div style="background: #fff; padding: 24px; border-radius: 8px; max-width: 600px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">

			<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
				<div style="width: 64px; height: 64px; border-radius: 50%; background: <?php echo $is_active ? '#fff3cd' : '#d4edda'; ?>; display: flex; align-items: center; justify-content: center; font-size: 32px;">
					<?php echo $is_active ? '🚧' : '✓'; ?>
				</div>
				<div>
					<h2 style="margin: 0; font-size: 24px;">
						<?php echo $is_active ? 'Maintenance Mode ist AKTIV' : 'Seite ist LIVE'; ?>
					</h2>
					<p style="margin: 4px 0 0; color: #666;">
						<?php echo $is_active
							? 'Besucher sehen die "Wir sind gleich zurück" Seite.'
							: 'Alle Besucher können die Seite normal sehen.'; ?>
					</p>
				</div>
			</div>

			<form method="post">
				<?php wp_nonce_field('parkourone_maintenance_toggle'); ?>
				<input type="hidden" name="parkourone_maintenance_toggle" value="<?php echo $is_active ? 'off' : 'on'; ?>">

				<button type="submit" class="button button-hero <?php echo $is_active ? '' : 'button-primary'; ?>" style="width: 100%;">
					<?php echo $is_active ? '🟢 Seite Live schalten' : '🚧 Maintenance Mode aktivieren'; ?>
				</button>
			</form>

			<?php if ($is_active): ?>
			<p style="margin-top: 16px; padding: 12px; background: #f0f0f0; border-radius: 4px; font-size: 13px;">
				<strong>Tipp:</strong> Du bist als Admin eingeloggt und siehst die Seite normal.
				<a href="<?php echo home_url('/?preview_maintenance=1'); ?>" target="_blank">Maintenance-Seite ansehen →</a>
			</p>
			<?php endif; ?>
		</div>
	<?php if (!$embedded): ?>
	</div>
	<?php endif; ?>
	<?php
}

/**
 * Preview der Maintenance-Seite für Admins
 */
function parkourone_maintenance_preview() {
	if (isset($_GET['preview_maintenance']) && current_user_can('manage_options')) {
		$maintenance_file = get_template_directory() . '/maintenance.php';
		if (file_exists($maintenance_file)) {
			include $maintenance_file;
			exit;
		}
	}
}
add_action('template_redirect', 'parkourone_maintenance_preview', 1);

// Includes
require_once get_template_directory() . '/inc/angebote-cpt.php';
require_once get_template_directory() . '/inc/testimonials-cpt.php';
require_once get_template_directory() . '/inc/faq-cpt.php';
require_once get_template_directory() . '/inc/jobs-cpt.php';
require_once get_template_directory() . '/inc/auto-pages.php';
require_once get_template_directory() . '/inc/event-images.php';
require_once get_template_directory() . '/inc/github-updater.php';
require_once get_template_directory() . '/inc/theme-images.php';
require_once get_template_directory() . '/inc/admin-menu.php';
require_once get_template_directory() . '/inc/cookie-consent/init.php';
require_once get_template_directory() . '/inc/analytics/init.php';
require_once get_template_directory() . '/inc/woocommerce/init.php';
require_once get_template_directory() . '/inc/health-data-consent.php';
require_once get_template_directory() . '/inc/promo-popup.php';
require_once get_template_directory() . '/inc/link-checker.php';
require_once get_template_directory() . '/inc/redirects.php';
require_once get_template_directory() . '/inc/probetraining-links.php';
require_once get_template_directory() . '/inc/webp-converter.php';
require_once get_template_directory() . '/inc/coach-avatar-cache.php';

/**
 * ============================================
 * MU-PLUGINS AUTOMATISCH INSTALLIEREN
 * Kopiert MU-Plugins vom Theme nach wp-content/mu-plugins/
 * Prüft Version — aktualisiert bei Theme-Update.
 * ============================================
 */
function parkourone_install_mu_plugins() {
	$mu_plugins = [
		'parkourone-consent-early.php',
		'parkourone-performance.php',
		'parkourone-github-webhook.php',
	];

	$source_dir = get_template_directory() . '/mu-plugins/';
	$dest_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (WP_CONTENT_DIR . '/mu-plugins');

	// mu-plugins Ordner erstellen falls nicht vorhanden
	if (!is_dir($dest_dir)) {
		if (!wp_mkdir_p($dest_dir)) {
			return; // Keine Schreibrechte
		}
	}

	if (!is_writable($dest_dir)) {
		return;
	}

	foreach ($mu_plugins as $plugin_file) {
		$source = $source_dir . $plugin_file;
		$dest = $dest_dir . '/' . $plugin_file;

		if (!file_exists($source)) {
			continue;
		}

		// Installieren wenn nicht vorhanden oder veraltet
		$needs_update = false;

		if (!file_exists($dest)) {
			$needs_update = true;
		} else {
			// Versions-Check: Theme-Version neuer als installierte?
			$source_hash = md5_file($source);
			$dest_hash = md5_file($dest);
			if ($source_hash !== $dest_hash) {
				$needs_update = true;
			}
		}

		if ($needs_update) {
			@copy($source, $dest);
		}
	}
}
add_action('after_setup_theme', 'parkourone_install_mu_plugins');

/**
 * ============================================
 * HERO IMAGE PRELOAD (LCP Optimization)
 * Preloads the hero image so the browser discovers it early
 * ============================================
 */
function parkourone_preload_hero_image() {
	if (is_admin() || !is_singular()) return;

	$post = get_post();
	if (!$post || !has_block('parkourone/hero', $post)) return;

	$blocks = parse_blocks($post->post_content);
	foreach ($blocks as $block) {
		if ($block['blockName'] !== 'parkourone/hero') continue;

		$attrs = $block['attrs'] ?? [];
		$imageUrl = $attrs['imageUrl'] ?? '';

		if (!empty($imageUrl)) {
			$desktopImage = $imageUrl;
			$mobileImage = $imageUrl;
		} else {
			// Fallback images — same logic as render.php
			$ageCategory = $attrs['ageCategory'] ?? '';
			$fallback_categories = ['adults', 'kids', 'juniors'];
			if (!empty($ageCategory)) {
				array_unshift($fallback_categories, $ageCategory);
				$fallback_categories = array_unique($fallback_categories);
			}
			// Use first category deterministically for preload (not random)
			$category = $fallback_categories[0];

			$landscape = function_exists('parkourone_get_fallback_image') ? parkourone_get_fallback_image($category, 'landscape') : '';
			$portrait = function_exists('parkourone_get_fallback_image') ? parkourone_get_fallback_image($category, 'portrait') : '';

			$desktopImage = $landscape ?: (get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg');
			$mobileImage = $portrait ?: (get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg');
		}

		// Preload mobile image (default) and desktop image (min-width: 768px)
		if ($desktopImage === $mobileImage) {
			echo '<link rel="preload" as="image" href="' . esc_url($desktopImage) . '" fetchpriority="high">' . "\n";
		} else {
			echo '<link rel="preload" as="image" href="' . esc_url($mobileImage) . '" media="(max-width: 767px)" fetchpriority="high">' . "\n";
			echo '<link rel="preload" as="image" href="' . esc_url($desktopImage) . '" media="(min-width: 768px)" fetchpriority="high">' . "\n";
		}
		break; // Only first hero block
	}
}
add_action('wp_head', 'parkourone_preload_hero_image', 1);

/**
 * ============================================
 * RESOURCE HINTS: PRECONNECT / DNS-PREFETCH
 * Frühzeitig Verbindungen zu externen Domains aufbauen
 * ============================================
 */
function parkourone_resource_hints($hints, $relation_type) {
	if ($relation_type === 'preconnect') {
		$hints[] = [
			'href' => 'https://academyboard.parkourone.com',
			'crossorigin' => 'anonymous',
		];
		if (po_has_consent('functional')) {
			$hints[] = [
				'href' => 'https://use.typekit.net',
				'crossorigin' => 'anonymous',
			];
		}
	}

	if ($relation_type === 'dns-prefetch') {
		$hints[] = 'https://academyboard.parkourone.com';
		if (po_has_consent('analytics')) {
			$hints[] = 'https://www.googletagmanager.com';
		}
	}

	return $hints;
}
add_filter('wp_resource_hints', 'parkourone_resource_hints', 10, 2);

/**
 * Menü-Positionen registrieren
 * Ermöglicht Drag & Drop Menü-Verwaltung im WordPress Admin
 */
function parkourone_register_menus() {
    register_nav_menus([
        'main-menu' => 'Hauptmenü (3. Spalte: Über uns, Kontakt, etc.)',
        'footer-menu' => 'Footer Menü'
    ]);
}
add_action('after_setup_theme', 'parkourone_register_menus');

/**
 * Erstellt das Standard-Menü für Spalte 3 beim Theme-Aktivierung
 */
function parkourone_create_default_menu() {
    // Prüfen ob main-menu bereits zugewiesen ist
    $menu_locations = get_nav_menu_locations();
    if (!empty($menu_locations['main-menu'])) {
        return; // Menü existiert bereits
    }

    // Prüfen ob "Hauptmenü" Menü existiert
    $menu_name = 'Hauptmenü';
    $menu_exists = wp_get_nav_menu_object($menu_name);

    if (!$menu_exists) {
        // Menü erstellen
        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return;
        }

        // Standard-Einträge hinzufügen
        $default_items = [
            ['title' => 'Über uns', 'url' => home_url('/ueber-uns/')],
            ['title' => 'Angebote', 'url' => home_url('/angebote/')],
            ['title' => 'Kontakt', 'url' => home_url('/kontakt/')],
        ];

        foreach ($default_items as $item) {
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $item['title'],
                'menu-item-url' => $item['url'],
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }
    } else {
        $menu_id = $menu_exists->term_id;
    }

    // Menü der Position zuweisen
    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['main-menu'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('after_switch_theme', 'parkourone_create_default_menu');

/**
 * Admin-Button zum Erstellen des Standard-Menüs
 */
function parkourone_menu_setup_notice() {
    $menu_locations = get_nav_menu_locations();
    if (empty($menu_locations['main-menu'])) {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'nav-menus') {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>ParkourONE:</strong> Noch kein Menü für Spalte 3 zugewiesen.
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=parkourone_create_menu'), 'parkourone_create_menu'); ?>" class="button button-primary" style="margin-left: 10px;">Standard-Menü erstellen</a>
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'parkourone_menu_setup_notice');

/**
 * Handler für Standard-Menü erstellen Button
 */
function parkourone_handle_create_menu() {
    if (!current_user_can('edit_theme_options')) {
        wp_die('Keine Berechtigung');
    }
    check_admin_referer('parkourone_create_menu');

    parkourone_create_default_menu();

    wp_redirect(admin_url('nav-menus.php?menu_created=1'));
    exit;
}
add_action('admin_post_parkourone_create_menu', 'parkourone_handle_create_menu');

/**
 * Admin-Styles für den Menü-Editor
 * Macht Parent-Items visuell erkennbar
 */
function parkourone_menu_admin_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'nav-menus') {
        ?>
        <style>
            /* Hilfe-Box über dem Menü */
            .po-menu-help {
                background: #f0f6fc;
                border: 1px solid #c3d4e6;
                border-left: 4px solid #2271b1;
                padding: 12px 16px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .po-menu-help h4 {
                margin: 0 0 8px 0;
                font-size: 14px;
                color: #1d2327;
            }
            .po-menu-help p {
                margin: 0 0 8px 0;
                font-size: 13px;
                color: #50575e;
            }
            .po-menu-help ul {
                margin: 8px 0 0 20px;
                padding: 0;
            }
            .po-menu-help li {
                font-size: 13px;
                color: #50575e;
                margin-bottom: 4px;
            }
            .po-menu-help code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
            }

            /* Parent-Items hervorheben */
            .menu-item-depth-0 > .menu-item-bar {
                border-left: 4px solid #2271b1;
            }
            .menu-item-depth-0 > .menu-item-bar .menu-item-title {
                font-weight: 600;
            }

            /* Child-Items einrücken mit Verbindungslinie */
            .menu-item-depth-1 {
                position: relative;
            }
            .menu-item-depth-1::before {
                content: '';
                position: absolute;
                left: 30px;
                top: 0;
                bottom: 50%;
                width: 2px;
                background: #c3c4c7;
            }
            .menu-item-depth-1::after {
                content: '';
                position: absolute;
                left: 30px;
                top: 50%;
                width: 15px;
                height: 2px;
                background: #c3c4c7;
            }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // Hilfe-Box einfügen
            var helpBox = `
                <div class="po-menu-help">
                    <h4>ParkourONE Menü (3. Spalte)</h4>
                    <p>Das Menü hat 3 Spalten:</p>
                    <ul>
                        <li><strong>Spalte 1:</strong> Stundenplan + Altersgruppen (automatisch)</li>
                        <li><strong>Spalte 2:</strong> Standorte (automatisch)</li>
                        <li><strong>Spalte 3:</strong> Dieses Menü hier (manuell)</li>
                    </ul>
                    <p style="margin-top: 10px;">Füge hier Seiten wie <strong>Über uns</strong>, <strong>Angebote</strong>, <strong>Kontakt</strong> hinzu.</p>
                </div>
            `;
            $('#menu-to-edit').before(helpBox);
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'parkourone_menu_admin_styles');

/**
 * Custom Link Beschreibung anpassen
 */
function parkourone_menu_item_description($item_id, $item) {
    if ($item->url === '#') {
        echo '<p class="description" style="color: #2271b1; font-style: italic;">→ Nur Überschrift (nicht klickbar)</p>';
    }
}
add_action('wp_nav_menu_item_custom_fields', 'parkourone_menu_item_description', 10, 2);

/**
 * Menü-Vorschau Content (wiederverwendbar)
 */
function parkourone_render_menu_preview_content() {
    ?>
    <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 40px; max-width: 1200px;">
        <style>
            .po-preview .po-menu__columns {
                display: flex;
                flex-wrap: wrap;
                gap: 2.5rem;
            }
            .po-preview .po-menu__column {
                flex: 1;
                min-width: 150px;
                padding: 1rem;
                background: #f9f9f9;
                border-radius: 8px;
            }
            .po-preview .po-menu__column::before {
                display: block;
                font-size: 0.75rem;
                color: #666;
                margin-bottom: 0.5rem;
                font-weight: 600;
                text-transform: uppercase;
            }
            .po-preview .po-menu__column:nth-child(1)::before { content: 'Spalte 1'; }
            .po-preview .po-menu__column:nth-child(2)::before { content: 'Spalte 2'; }
            .po-preview .po-menu__column:nth-child(3)::before { content: 'Spalte 3'; }
            .po-preview .po-menu__column:nth-child(4)::before { content: 'Spalte 4'; }
            .po-preview .po-menu__list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .po-preview .po-menu__item {
                margin-bottom: 0.5rem;
            }
            .po-preview .po-menu__link {
                font-size: 1rem;
                color: #1d1d1f;
                text-decoration: none;
            }
            .po-preview .po-menu__link:hover {
                text-decoration: underline;
            }
            .po-preview .po-menu__link--highlight {
                font-weight: 700;
            }
        </style>
        <div class="po-preview">
            <?php echo parkourone_render_main_menu(); ?>
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-radius: 4px; max-width: 1200px;">
        <strong>Hinweis:</strong> Auto-Blöcke werden automatisch aus den Event-Kategorien generiert.
        Alle 4 Spalten können im <a href="<?php echo admin_url('admin.php?page=parkourone-menu-footer'); ?>">Menü Builder</a> bearbeitet werden.
    </div>
    <?php
}

/**
 * Rendert das Hauptmenü für das Fullscreen Overlay
 * Liest die 4-Spalten-Konfiguration aus parkourone_menu_columns
 */
function parkourone_render_main_menu() {
    $columns = get_option('parkourone_menu_columns', []);

    // Fallback auf Legacy-Rendering wenn Option leer
    if (empty($columns)) {
        return parkourone_render_main_menu_legacy();
    }

    $output = '<nav class="po-menu__columns">';

    foreach ($columns as $col) {
        if (empty($col['items'])) continue;

        $output .= '<div class="po-menu__column">';
        $output .= '<ul class="po-menu__list">';

        foreach ($col['items'] as $item) {
            $output .= parkourone_render_menu_item($item);
        }

        $output .= '</ul>';
        $output .= '</div>';
    }

    $output .= '</nav>';

    return $output;
}

/**
 * Rendert ein einzelnes Menü-Item
 */
function parkourone_render_menu_item($item) {
    $output = '';
    $type = $item['type'] ?? '';

    switch ($type) {
        case 'custom':
            $url = $item['url'] ?? '#';
            if (strpos($url, '/') === 0) {
                $url = home_url($url);
            }
            $highlight_class = !empty($item['highlight']) ? ' po-menu__link--highlight' : '';
            $output .= '<li class="po-menu__item">';
            $output .= '<a href="' . esc_url($url) . '" class="po-menu__link' . $highlight_class . '">' . esc_html($item['label'] ?? '') . '</a>';
            $output .= '</li>';
            break;

        case 'auto_block':
            $source = $item['source'] ?? '';
            $taxonomy = $item['taxonomy'] ?? 'event_category';
            $parent = get_term_by('slug', $source, $taxonomy);
            if ($parent) {
                $children = get_terms([
                    'taxonomy' => $taxonomy,
                    'parent' => $parent->term_id,
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ]);
                if (!is_wp_error($children) && !empty($children)) {
                    foreach ($children as $child) {
                        $url = home_url('/' . $child->slug . '/');
                        $output .= '<li class="po-menu__item">';
                        $output .= '<a href="' . esc_url($url) . '" class="po-menu__link">' . esc_html($child->name) . '</a>';
                        $output .= '</li>';
                    }
                }
            }
            break;

        case 'page':
            $page_id = $item['page_id'] ?? 0;
            if ($page_id) {
                $page = get_post($page_id);
                if ($page && $page->post_status === 'publish') {
                    $label = !empty($item['label']) ? $item['label'] : $page->post_title;
                    $output .= '<li class="po-menu__item">';
                    $output .= '<a href="' . esc_url(get_permalink($page_id)) . '" class="po-menu__link">' . esc_html($label) . '</a>';
                    $output .= '</li>';
                }
            }
            break;
    }

    return $output;
}

/**
 * Legacy-Rendering (3 Spalten, hardcoded)
 */
function parkourone_render_main_menu_legacy() {
    $output = '<nav class="po-menu__columns">';

    // Spalte 1: Stundenplan + Altersgruppen
    $output .= '<div class="po-menu__column">';
    $output .= '<ul class="po-menu__list">';
    $output .= '<li class="po-menu__item">';
    $output .= '<a href="' . esc_url(home_url('/stundenplan/')) . '" class="po-menu__link po-menu__link--highlight">Stundenplan</a>';
    $output .= '</li>';

    $alter_parent = get_term_by('slug', 'alter', 'event_category');
    if ($alter_parent) {
        $altersgruppen = get_terms([
            'taxonomy' => 'event_category',
            'parent' => $alter_parent->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        if (!is_wp_error($altersgruppen) && !empty($altersgruppen)) {
            foreach ($altersgruppen as $gruppe) {
                $output .= '<li class="po-menu__item">';
                $output .= '<a href="' . esc_url(home_url('/' . $gruppe->slug . '/')) . '" class="po-menu__link">' . esc_html($gruppe->name) . '</a>';
                $output .= '</li>';
            }
        }
    }
    $output .= '</ul></div>';

    // Spalte 2: Standorte
    $ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');
    if ($ortschaft_parent) {
        $standorte = get_terms([
            'taxonomy' => 'event_category',
            'parent' => $ortschaft_parent->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        if (!is_wp_error($standorte) && !empty($standorte)) {
            $output .= '<div class="po-menu__column"><ul class="po-menu__list">';
            foreach ($standorte as $standort) {
                $output .= '<li class="po-menu__item">';
                $output .= '<a href="' . esc_url(home_url('/' . $standort->slug . '/')) . '" class="po-menu__link">' . esc_html($standort->name) . '</a>';
                $output .= '</li>';
            }
            $output .= '</ul></div>';
        }
    }

    // Spalte 3: Manuelle Links
    $output .= parkourone_render_manual_menu_column();

    $output .= '</nav>';
    return $output;
}

/**
 * Gibt schönen Anzeigenamen für Altersgruppen zurück
 */
function parkourone_get_age_display_name($slug, $fallback) {
    $names = [
        'kids' => 'Parkour für Kids',
        'mini' => 'Parkour Mini',
        'teens' => 'Parkour für Teens',
        'juniors' => 'Parkour Juniors',
        'adults' => 'Parkour Erwachsene',
        'seniors' => 'Parkour Seniors',
        'women' => 'Parkour Women',
        'original' => 'Parkour Original',
    ];

    return $names[$slug] ?? 'Parkour ' . ucfirst($fallback);
}

/**
 * Rendert die manuelle Menü-Spalte (Über uns, Kontakt, etc.)
 * Liest Links aus ParkourONE > Menü & Footer Einstellungen
 */
function parkourone_render_manual_menu_column() {
    // Links aus Options laden
    $menu_links = get_option('parkourone_menu_links', []);

    // Fallback: Standard-Links wenn nichts konfiguriert
    if (empty($menu_links)) {
        $menu_links = [
            ['name' => 'Über uns', 'url' => '/ueber-uns/'],
            ['name' => 'Angebote', 'url' => '/angebote/'],
            ['name' => 'Team', 'url' => '/team/'],
            ['name' => 'Kontakt', 'url' => '/kontakt/'],
        ];
    }

    $output = '<div class="po-menu__column po-menu__column--manual">';
    $output .= '<ul class="po-menu__list">';

    foreach ($menu_links as $link) {
        if (empty($link['name'])) continue;

        // URL mit home_url() wenn relativ (beginnt mit /)
        $url = $link['url'] ?? '#';
        if (strpos($url, '/') === 0) {
            $url = home_url($url);
        }

        $output .= '<li class="po-menu__item">';
        $output .= '<a href="' . esc_url($url) . '" class="po-menu__link">' . esc_html($link['name']) . '</a>';
        $output .= '</li>';
    }

    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}

function parkourone_block_categories($categories) {
    $custom_categories = [
        [
            'slug'  => 'parkourone-startseite',
            'title' => 'ParkourONE - Startseite',
        ],
        [
            'slug'  => 'parkourone-blocks',
            'title' => 'ParkourONE - Blocks',
        ],
        [
            'slug'  => 'parkourone-about',
            'title' => 'ParkourONE - About',
        ],
        [
            'slug'  => 'parkourone-layout',
            'title' => 'ParkourONE - Layout',
        ],
        [
            'slug'  => 'parkourone-basis',
            'title' => 'ParkourONE - Basis Blöcke',
        ],
    ];

    return array_merge($custom_categories, $categories);
}
add_filter('block_categories_all', 'parkourone_block_categories');

/**
 * Ticket #3: Nur ParkourONE Blöcke im Editor anzeigen
 * Standard WordPress Blöcke werden ausgeblendet für Schulleiter
 */
function parkourone_allowed_block_types($allowed_blocks, $editor_context) {
    // Alle ParkourONE Blöcke
    $parkourone_blocks = [
        // Startseite & Hero
        'parkourone/hero',
        'parkourone/page-header',
        'parkourone/promo-banner',

        // Content
        'parkourone/about-section',
        'parkourone/split-content',
        'parkourone/intro-section',
        'parkourone/text-reveal',
        'parkourone/trust-education',

        // Klassen & Events
        'parkourone/klassen-slider',
        'parkourone/klassen-selektor',
        'parkourone/stundenplan',
        'parkourone/stundenplan-detail',
        'parkourone/steps-carousel',
        'parkourone/event-day-slider',

        // Grids & Slider
        'parkourone/zielgruppen-grid',
        'parkourone/angebote-grid',
        'parkourone/angebote-karussell',
        'parkourone/testimonials-slider',
        'parkourone/usp-slider',
        'parkourone/team-grid',
        'parkourone/schulen-grid',
        'parkourone/job-cards',
        'parkourone/feature-cards',
        'parkourone/stats-counter',

        // FAQ, Gutschein & Footer
        'parkourone/faq',
        'parkourone/gutschein',
        'parkourone/produkt-showcase',
        'parkourone/footer',

        // Formulare & Buchung
        'parkourone/member-form',
        'parkourone/personal-training',
        'parkourone/inquiry-form',
        'parkourone/event-booking',
        'parkourone/pricing-table',
        'parkourone/video',

        // Basis Blöcke für Schulleiter (Ticket #2)
        'parkourone/po-text',
        'parkourone/po-image',
        'parkourone/po-icon',
        'parkourone/po-columns',
        'parkourone/po-section',

        // Minimale Core-Blöcke für Kompatibilität
        'core/paragraph',
        'core/heading',
        'core/spacer',
        'core/separator',
        'core/group',
        'core/columns',
        'core/column',
        'core/image',
        'core/list',
        'core/list-item',
        'core/buttons',
        'core/button',
        'core/html',
        'core/shortcode',

        // WooCommerce Blöcke
        'woocommerce/cart',
        'woocommerce/checkout',
        'woocommerce/filled-cart-block',
        'woocommerce/empty-cart-block',
        'woocommerce/cart-items-block',
        'woocommerce/cart-line-items-block',
        'woocommerce/cart-totals-block',
        'woocommerce/cart-order-summary-block',
        'woocommerce/cart-order-summary-heading-block',
        'woocommerce/cart-order-summary-subtotal-block',
        'woocommerce/cart-order-summary-coupon-form-block',
        'woocommerce/cart-order-summary-totals-block',
        'woocommerce/cart-express-payment-block',
        'woocommerce/proceed-to-checkout-block',
        'woocommerce/cart-accepted-payment-methods-block',
        'woocommerce/checkout-fields-block',
        'woocommerce/checkout-express-payment-block',
        'woocommerce/checkout-contact-information-block',
        'woocommerce/checkout-shipping-address-block',
        'woocommerce/checkout-billing-address-block',
        'woocommerce/checkout-shipping-methods-block',
        'woocommerce/checkout-payment-block',
        'woocommerce/checkout-additional-information-block',
        'woocommerce/checkout-order-note-block',
        'woocommerce/checkout-terms-block',
        'woocommerce/checkout-actions-block',
        'woocommerce/checkout-totals-block',
        'woocommerce/checkout-order-summary-block',
        'woocommerce/checkout-order-summary-cart-items-block',
        'woocommerce/checkout-order-summary-subtotal-block',
        'woocommerce/checkout-order-summary-coupon-form-block',
        'woocommerce/checkout-order-summary-totals-block',
    ];

    return $parkourone_blocks;
}
add_filter('allowed_block_types_all', 'parkourone_allowed_block_types', 10, 2);

// Pattern-Kategorien registrieren
function parkourone_register_pattern_categories() {
    register_block_pattern_category('parkourone-seiten', [
        'label' => 'ParkourONE - Seiten-Vorlagen'
    ]);
    register_block_pattern_category('parkourone-sektionen', [
        'label' => 'ParkourONE - Sektionen'
    ]);
}
add_action('init', 'parkourone_register_pattern_categories');

function parkourone_register_block_patterns() {
    // Pattern 1: Text + Bild nebeneinander
    register_block_pattern('parkourone/text-bild-sektion', [
        'title' => 'Text + Bild Sektion',
        'description' => 'Text links, Bild rechts in einer Section',
        'categories' => ['parkourone-sektionen'],
        'content' => '<!-- wp:parkourone/po-section {"paddingSize":"medium","maxWidth":"default"} --><!-- wp:columns --><!-- wp:column --><!-- wp:heading {"level":2} --><h2>Überschrift hier</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Beschreibungstext hier einfügen. Dieser Text kann beliebig lang sein und beschreibt den Inhalt der Sektion.</p><!-- /wp:paragraph --><!-- wp:buttons --><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link">Mehr erfahren</a></div><!-- /wp:button --><!-- /wp:buttons --><!-- /wp:column --><!-- wp:column --><!-- wp:image --><figure class="wp-block-image"><img alt=""/></figure><!-- /wp:image --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:parkourone/po-section -->'
    ]);

    // Pattern 2: 3-Spalten mit Icons
    register_block_pattern('parkourone/drei-spalten-icons', [
        'title' => '3 Spalten mit Icons',
        'description' => 'Drei Spalten mit zentriertem Icon und Text',
        'categories' => ['parkourone-sektionen'],
        'content' => '<!-- wp:parkourone/po-section {"paddingSize":"large","maxWidth":"default","showHeadline":true,"headline":"Unsere Vorteile"} --><!-- wp:columns --><!-- wp:column --><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="has-text-align-center">Vorteil 1</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Beschreibung des ersten Vorteils.</p><!-- /wp:paragraph --><!-- /wp:column --><!-- wp:column --><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="has-text-align-center">Vorteil 2</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Beschreibung des zweiten Vorteils.</p><!-- /wp:paragraph --><!-- /wp:column --><!-- wp:column --><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="has-text-align-center">Vorteil 3</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Beschreibung des dritten Vorteils.</p><!-- /wp:paragraph --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:parkourone/po-section -->'
    ]);

    // Pattern 3: CTA Banner
    register_block_pattern('parkourone/cta-banner', [
        'title' => 'Call-to-Action Banner',
        'description' => 'Auffälliger CTA-Bereich mit Hintergrundfarbe',
        'categories' => ['parkourone-sektionen'],
        'content' => '<!-- wp:parkourone/po-section {"paddingSize":"large","maxWidth":"narrow","backgroundColor":"#0066cc"} --><!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} --><h2 class="has-text-align-center has-text-color" style="color:#ffffff">Bereit für dein erstes Training?</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} --><p class="has-text-align-center has-text-color" style="color:#ffffffcc">Starte jetzt und entdecke, was Parkour für dich tun kann.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#0066cc"}}} --><div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" style="color:#0066cc;background-color:#ffffff">Probetraining buchen</a></div><!-- /wp:button --><!-- /wp:buttons --><!-- /wp:parkourone/po-section -->'
    ]);

    // Pattern 4: Textsektion mit Überschrift
    register_block_pattern('parkourone/text-sektion', [
        'title' => 'Textsektion mit Überschrift',
        'description' => 'Einfache Textsektion mit zentrierter Überschrift',
        'categories' => ['parkourone-sektionen'],
        'content' => '<!-- wp:parkourone/po-section {"paddingSize":"medium","maxWidth":"narrow","showHeadline":true,"headline":"Über uns"} --><!-- wp:paragraph --><p>Hier kommt der Inhalt. Dieser Block eignet sich perfekt für Fliesstext, Beschreibungen oder andere Textinhalte die eine eigene Sektion brauchen.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Füge beliebig viele Absätze, Bilder, Listen oder andere Blöcke hinzu.</p><!-- /wp:paragraph --><!-- /wp:parkourone/po-section -->'
    ]);

    // Pattern 5: Bild-Galerie Sektion
    register_block_pattern('parkourone/bild-galerie-sektion', [
        'title' => 'Bild-Galerie Sektion',
        'description' => 'Sektion mit Überschrift und Bildergalerie',
        'categories' => ['parkourone-sektionen'],
        'content' => '<!-- wp:parkourone/po-section {"paddingSize":"large","maxWidth":"wide","backgroundColor":"#f5f5f7","showHeadline":true,"headline":"Impressionen"} --><!-- wp:gallery {"columns":3,"linkTo":"none"} --><!-- wp:image --><figure class="wp-block-image"><img alt=""/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img alt=""/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img alt=""/></figure><!-- /wp:image --><!-- /wp:gallery --><!-- /wp:parkourone/po-section -->'
    ]);
}
add_action('init', 'parkourone_register_block_patterns');

function parkourone_register_blocks() {
    $blocks_dir = get_template_directory() . '/blocks/';

    $blocks = [
        'page-header',
        'split-content',
        'stats-counter',
        'feature-cards',
        'team-grid',
        'job-cards',
        'schulen-grid',
        'footer',
        'klassen-slider',
        'stundenplan',
        'hero',
        'zielgruppen-grid',
        'usp-slider',
        'promo-banner',
        'about-section',
        'angebote-grid',
        'angebote-karussell',
        'testimonials-slider',
        'klassen-selektor',
        'stundenplan-detail',
        'intro-section',
        'faq',
        'trust-education',
        'text-reveal',
        'steps-carousel',
        'pricing-table',
        'event-booking',
        'event-day-slider',
        'gutschein',
        'produkt-showcase',
        // Ticket #2: Basis Building Blocks für Schulleiter
        'po-text',
        'po-image',
        'po-icon',
        'po-columns',
        'po-section',
        'video',
        'personal-training',
        'inquiry-form',
        'member-form',
        '404-hero'
    ];

    foreach ($blocks as $block) {
        $block_folder = $blocks_dir . $block;

        if (file_exists($block_folder . '/block.json')) {
            register_block_type($block_folder);
        }
    }
}
add_action('init', 'parkourone_register_blocks');

/**
 * Anchor-Support für alle ParkourONE-Blöcke.
 * Setzt die im Editor eingetragene Anchor-ID als id-Attribut auf das äussere Element.
 * Blöcke die get_block_wrapper_attributes() nutzen, bekommen den Anchor automatisch.
 */
function parkourone_block_anchor_support($block_content, $block) {
	if (empty($block['attrs']['anchor'])) return $block_content;
	if (strpos($block['blockName'] ?? '', 'parkourone/') !== 0) return $block_content;

	$anchor = esc_attr($block['attrs']['anchor']);
	$block_content = trim($block_content);

	if (preg_match('/^<[^>]*\bid=["\']' . preg_quote($anchor, '/') . '["\']/', $block_content)) {
		// Anchor ist bereits gesetzt (z.B. via get_block_wrapper_attributes)
		return $block_content;
	}

	if (preg_match('/^(<[^>]*)\bid="[^"]*"/', $block_content)) {
		// Bestehende id ersetzen
		$block_content = preg_replace('/^(<[^>]*)\bid="[^"]*"/', '$1id="' . $anchor . '"', $block_content, 1);
	} else {
		// id zum ersten Element hinzufügen
		$block_content = preg_replace('/^(<\w+)(\s|>)/', '$1 id="' . $anchor . '"$2', $block_content, 1);
	}

	return $block_content;
}
add_filter('render_block', 'parkourone_block_anchor_support', 10, 2);

function parkourone_enqueue_swiper() {
    // Only register — actual enqueue happens when a slider block is present
    wp_register_style('swiper', get_template_directory_uri() . '/assets/vendor/swiper/swiper-bundle.min.css', [], '11.0.0');
    wp_register_script('swiper', get_template_directory_uri() . '/assets/vendor/swiper/swiper-bundle.min.js', [], '11.0.0', true);

    // Only load if a slider block is on the page
    $slider_blocks = [
        'parkourone/klassen-slider',
        'parkourone/testimonials-slider',
        'parkourone/steps-carousel',
        'parkourone/angebote-karussell',
        'parkourone/usp-slider',
        'parkourone/event-day-slider',
    ];

    $post = get_post();
    if ($post) {
        foreach ($slider_blocks as $block_name) {
            if (has_block($block_name, $post)) {
                wp_enqueue_style('swiper');
                wp_enqueue_script('swiper');
                break;
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_swiper');

function parkourone_enqueue_theme_styles() {
    // Shared Components (Modals, Steps, etc.)
    wp_enqueue_style(
        'parkourone-components',
        get_template_directory_uri() . '/assets/css/components.css',
        [],
        filemtime(get_template_directory() . '/assets/css/components.css')
    );

    // Header & Footer Styles
    wp_enqueue_style(
        'parkourone-header-footer',
        get_template_directory_uri() . '/assets/css/header-footer.css',
        ['parkourone-components'],
        filemtime(get_template_directory() . '/assets/css/header-footer.css')
    );

    // Header Scroll Script
    wp_enqueue_script(
        'parkourone-header',
        get_template_directory_uri() . '/assets/js/header.js',
        [],
        filemtime(get_template_directory() . '/assets/js/header.js'),
        true
    );

    // Fullscreen Menü Script
    wp_enqueue_script(
        'parkourone-menu',
        get_template_directory_uri() . '/assets/js/menu.js',
        [],
        filemtime(get_template_directory() . '/assets/js/menu.js'),
        true
    );

    // Scroll Animations CSS
    wp_enqueue_style(
        'parkourone-animations',
        get_template_directory_uri() . '/assets/css/animations.css',
        [],
        filemtime(get_template_directory() . '/assets/css/animations.css')
    );

    // Scroll Animations JS
    wp_enqueue_script(
        'parkourone-scroll-animations',
        get_template_directory_uri() . '/assets/js/scroll-animations.js',
        [],
        filemtime(get_template_directory() . '/assets/js/scroll-animations.js'),
        true
    );

    // Accessibility Utilities (WCAG 2.1 AA)
    wp_enqueue_script(
        'parkourone-accessibility',
        get_template_directory_uri() . '/assets/js/accessibility.js',
        [],
        filemtime(get_template_directory() . '/assets/js/accessibility.js'),
        true
    );

    // Health Data Consent CSS
    wp_enqueue_style(
        'parkourone-health-consent',
        get_template_directory_uri() . '/assets/css/health-consent.css',
        [],
        filemtime(get_template_directory() . '/assets/css/health-consent.css')
    );

    // Shared Overlay Handler (modal open/close for all .po-overlay modals)
    wp_enqueue_script(
        'parkourone-overlay-handler',
        get_template_directory_uri() . '/assets/js/overlay-handler.js',
        [],
        filemtime(get_template_directory() . '/assets/js/overlay-handler.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_theme_styles');

/**
 * ============================================
 * PERFORMANCE: CSS Defer (non-critical stylesheets)
 * Uses media="print" + onload trick to load CSS async
 * ============================================
 */
function parkourone_defer_non_critical_css($html, $handle, $href, $media) {
	if (is_admin()) return $html;

	$defer_handles = [
		'parkourone-animations',
		'parkourone-health-consent',
		'po-consent-banner',
		'parkourone-woocommerce',
		'parkourone-side-cart',
	];

	if (in_array($handle, $defer_handles, true)) {
		// Handle both single and double quotes WordPress may use
		$html = str_replace(
			["media='all'", 'media="all"'],
			["media='print' onload=\"this.media='all'\"", 'media="print" onload="this.media=\'all\'"'],
			$html
		);
		// Add noscript fallback
		$html .= '<noscript><link rel="stylesheet" href="' . esc_url($href) . '"></noscript>' . "\n";
	}

	return $html;
}
add_filter('style_loader_tag', 'parkourone_defer_non_critical_css', 10, 4);

/**
 * Inline critical CSS for animated elements (prevents FOUC before animations.css loads)
 */
function parkourone_inline_critical_animation_css() {
	echo "<style>[data-animate]{opacity:0}</style>\n";
}
add_action('wp_head', 'parkourone_inline_critical_animation_css', 5);

/**
 * ============================================
 * PERFORMANCE: Script defer attributes
 * ============================================
 */
function parkourone_defer_scripts($tag, $handle, $src) {
	if (is_admin()) return $tag;

	$defer_handles = [
		'parkourone-scroll-animations',
		'parkourone-accessibility',
		'parkourone-side-cart',
	];

	if (in_array($handle, $defer_handles, true) && strpos($tag, 'defer') === false) {
		$tag = str_replace(' src=', ' defer src=', $tag);
	}

	return $tag;
}
add_filter('script_loader_tag', 'parkourone_defer_scripts', 10, 3);

/**
 * Rendert den Custom Header mit Fullscreen-Menü
 */
function parkourone_render_header() {
    $logo_id = get_theme_mod('custom_logo');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

    // Fallback: Theme-Logo verwenden wenn kein Custom Logo gesetzt
    if (!$logo_url) {
        $logo_url = get_template_directory_uri() . '/assets/images/admin-logo.png';
    }

    $site_name = get_bloginfo('name');
    $home_url = home_url('/');
    $probetraining_url = home_url('/probetraining-buchen/');

    // Cart count for WooCommerce
    $cart_count = 0;
    if (class_exists('WooCommerce') && WC()->cart) {
        $cart_count = WC()->cart->get_cart_contents_count();
    }

    ?>
    <!-- Skip-Link für Barrierefreiheit (WCAG 2.1) -->
    <a class="po-skip-link" href="#po-main-content">Zum Inhalt springen</a>

    <header class="po-header" id="po-header">
        <div class="po-header__inner">
            <a href="<?php echo esc_url($home_url); ?>" class="po-header__logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="po-header__logo-img">
            </a>

            <div class="po-header__actions">
                <a href="<?php echo esc_url($probetraining_url); ?>" class="po-header__cta">Probetraining buchen</a>

                <?php if (class_exists('WooCommerce')) : ?>
                <button type="button" class="po-header__cart" data-open-side-cart aria-label="Warenkorb öffnen">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
                    </svg>
                    <span class="po-header__cart-count" data-cart-count="<?php echo esc_attr($cart_count); ?>"><?php echo esc_html($cart_count); ?></span>
                </button>
                <?php endif; ?>

                <button class="po-header__toggle" id="po-menu-toggle" aria-label="Menü öffnen" aria-expanded="false">
                    <span class="po-header__toggle-bar"></span>
                    <span class="po-header__toggle-bar"></span>
                </button>
            </div>
        </div>
    </header>

    <div class="po-menu-overlay" id="po-menu-overlay" aria-hidden="true">
        <div class="po-menu-overlay__inner">
            <?php echo parkourone_render_main_menu(); ?>
        </div>
    </div>
    <?php
}
add_action('wp_body_open', 'parkourone_render_header');

function parkourone_register_api_endpoints() {
    register_rest_route('parkourone/v1', '/event-filters', [
        'methods' => 'GET',
        'callback' => 'parkourone_get_event_filters',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route('parkourone/v1', '/klassen', [
        'methods' => 'GET',
        'callback' => 'parkourone_get_klassen',
        'permission_callback' => '__return_true'
    ]);
}
add_action('rest_api_init', 'parkourone_register_api_endpoints');

/**
 * REST-Felder für WC-Produkte: price_html und featured_media_src_url.
 * Wird vom Produkt-Showcase-Block im Editor für die Live-Vorschau benötigt.
 */
function parkourone_register_product_rest_fields() {
	if (!function_exists('wc_get_product')) {
		return;
	}

	register_rest_field('product', 'price_html', [
		'get_callback' => function ($product_data) {
			$product = wc_get_product($product_data['id']);
			return $product ? $product->get_price_html() : '';
		},
		'schema' => ['type' => 'string', 'context' => ['view', 'edit']],
	]);

	register_rest_field('product', 'featured_media_src_url', [
		'get_callback' => function ($product_data) {
			$product = wc_get_product($product_data['id']);
			if (!$product) return '';
			$image_id = $product->get_image_id();
			return $image_id ? wp_get_attachment_url($image_id) : '';
		},
		'schema' => ['type' => 'string', 'context' => ['view', 'edit']],
	]);
}
add_action('rest_api_init', 'parkourone_register_product_rest_fields');

/**
 * Exclude events without any event_category from all front-end queries.
 * Events must be assigned to at least one category to appear.
 */
function parkourone_exclude_uncategorized_events($query) {
    if (is_admin()) {
        return;
    }

    // Only apply to event queries
    $post_type = $query->get('post_type');
    if ($post_type !== 'event') {
        return;
    }

    $existing = $query->get('tax_query') ?: [];

    // Wrap existing conditions + our new one in AND relation
    $tax_query = [
        'relation' => 'AND',
        // Must have at least one event_category term
        [
            'taxonomy' => 'event_category',
            'operator' => 'EXISTS',
        ],
    ];

    // Preserve any existing tax_query conditions
    if (!empty($existing)) {
        $tax_query[] = $existing;
    }

    $query->set('tax_query', $tax_query);
}
add_action('pre_get_posts', 'parkourone_exclude_uncategorized_events');

/**
 * Ensure 'ferienkurs' term exists under 'angebot' parent in event_category taxonomy.
 * Runs once on init; uses a transient to avoid repeated DB lookups.
 */
function parkourone_ensure_ferienkurs_term() {
    if (get_transient('po_ferienkurs_term_exists')) {
        return;
    }

    $angebot_parent = get_term_by('slug', 'angebot', 'event_category');
    if (!$angebot_parent || is_wp_error($angebot_parent)) {
        return;
    }

    $existing = get_term_by('slug', 'ferienkurs', 'event_category');
    if (!$existing) {
        wp_insert_term('Ferienkurs', 'event_category', [
            'slug'   => 'ferienkurs',
            'parent' => $angebot_parent->term_id,
        ]);
    }

    set_transient('po_ferienkurs_term_exists', true, DAY_IN_SECONDS);
}
add_action('init', 'parkourone_ensure_ferienkurs_term', 20);

function parkourone_get_event_filters() {
    $filters = [];
    
    $parent_slugs = [
        'alter' => 'age',
        'ortschaft' => 'location', 
        'angebot' => 'offer',
        'wochentag' => 'weekday'
    ];
    
    foreach ($parent_slugs as $slug => $key) {
        $parent = get_term_by('slug', $slug, 'event_category');
        $terms = [];
        
        if ($parent) {
            $children = get_terms([
                'taxonomy' => 'event_category',
                'hide_empty' => false,
                'parent' => $parent->term_id
            ]);
            
            if (!is_wp_error($children)) {
                foreach ($children as $term) {
                    $terms[] = [
                        'slug' => $term->slug,
                        'name' => $term->name
                    ];
                }
            }
        }
        
        $filters[$key] = $terms;
    }
    
    return $filters;
}

function parkourone_get_klassen(WP_REST_Request $request) {
    $age = $request->get_param('age');
    $location = $request->get_param('location');
    $offer = $request->get_param('offer');
    $weekday = $request->get_param('weekday');
    
    $args = [
        'post_type' => 'event',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];
    
    $tax_query = [];
    
    if ($age) {
        $tax_query[] = [
            'taxonomy' => 'event_category',
            'field' => 'slug',
            'terms' => $age
        ];
    }
    if ($location) {
        $tax_query[] = [
            'taxonomy' => 'event_category',
            'field' => 'slug',
            'terms' => $location
        ];
    }
    if ($offer) {
        $tax_query[] = [
            'taxonomy' => 'event_category',
            'field' => 'slug',
            'terms' => $offer
        ];
    }
    if ($weekday) {
        $tax_query[] = [
            'taxonomy' => 'event_category',
            'field' => 'slug',
            'terms' => $weekday
        ];
    }
    
    if (!empty($tax_query)) {
        $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    }
    
    $query = new WP_Query($args);
    $klassen = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $event_id = get_the_ID();
            
            $permalink = get_post_meta($event_id, '_event_permalink', true);
            if (empty($permalink)) {
                $permalink = sanitize_title(get_the_title());
            }
            
            $event_dates = get_post_meta($event_id, '_event_dates', true);
            $first_date = is_array($event_dates) && !empty($event_dates) ? $event_dates[0] : null;
            
            $weekday_name = '';
            if ($first_date && !empty($first_date['date'])) {
                $timestamp = strtotime(str_replace('-', '.', $first_date['date']));
                if ($timestamp) {
                    $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
                    $weekday_name = $days[date('w', $timestamp)];
                }
            }

            // Altersgruppe für Fallback-Bild ermitteln
            $age_term = '';
            $categories = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
            foreach ($categories as $cat) {
                if ($cat->parent) {
                    $parent = get_term($cat->parent, 'event_category');
                    if ($parent && $parent->slug === 'alter') {
                        $age_term = $cat->slug;
                        break;
                    }
                }
            }

            // Bild über zentrale Funktion holen (volle Größe für beste Qualität)
            $event_image = function_exists('parkourone_get_event_image')
                ? parkourone_get_event_image($event_id, $age_term)
                : get_the_post_thumbnail_url($event_id, 'full');

            $klassen[] = [
                'id' => $event_id,
                'title' => get_the_title(),
                'permalink' => $permalink,
                'headcoach' => get_post_meta($event_id, '_event_headcoach', true),
                'headcoach_image' => get_post_meta($event_id, '_event_headcoach_image_url', true),
                'start_time' => get_post_meta($event_id, '_event_start_time', true),
                'end_time' => get_post_meta($event_id, '_event_end_time', true),
                'weekday' => $weekday_name,
                'image' => $event_image,
                'categories' => wp_get_post_terms($event_id, 'event_category', ['fields' => 'slugs'])
            ];
        }
        wp_reset_postdata();
    }
    
    return $klassen;
}

function parkourone_enqueue_block_assets() {
    $blocks_dir = get_template_directory() . '/blocks/';
    $blocks_url = get_template_directory_uri() . '/blocks/';

    $blocks = [
        'page-header',
        'split-content',
        'stats-counter',
        'feature-cards',
        'team-grid',
        'job-cards',
        'schulen-grid',
        'footer',
        'klassen-slider',
        'stundenplan',
        'hero',
        'zielgruppen-grid',
        'usp-slider',
        'promo-banner',
        'about-section',
        'angebote-grid',
        'angebote-karussell',
        'testimonials-slider',
        'klassen-selektor',
        'stundenplan-detail',
        'intro-section',
        'faq',
        'trust-education',
        'text-reveal',
        'steps-carousel',
        'event-booking',
        'gutschein',
        'produkt-showcase',
        // Ticket #2: Basis Building Blocks für Schulleiter
        'po-text',
        'po-image',
        'po-icon',
        'po-columns',
        'po-section',
        'video',
        'personal-training',
        'inquiry-form',
        'member-form'
    ];

    foreach ($blocks as $block) {
        $js_file = $blocks_dir . $block . '/index.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'parkourone-' . $block,
                $blocks_url . $block . '/index.js',
                ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-api-fetch'],
                filemtime($js_file),
                false
            );
        }
    }
}
add_action('enqueue_block_editor_assets', 'parkourone_enqueue_block_assets');

/**
 * Enqueue frontend view scripts for custom blocks
 * Diese Scripts verwenden Event Delegation und können immer geladen werden
 */
function parkourone_enqueue_block_view_scripts() {
    // Nur im Frontend, nicht im Admin
    if (is_admin()) return;

    $blocks_dir = get_template_directory() . '/blocks/';
    $blocks_url = get_template_directory_uri() . '/blocks/';

    // Blocks mit View-Scripts - werden immer geladen da sie Event Delegation nutzen
    // und keine Performance-Probleme verursachen wenn der Block nicht auf der Seite ist
    // Hinweis: Blocks mit "viewScript" in block.json werden automatisch geladen
    // Hier nur Blocks ohne block.json viewScript oder mit speziellen Anforderungen
    $view_script_blocks = [
        'faq',
        'event-booking',
        'event-day-slider',
    ];

    foreach ($view_script_blocks as $folder) {
        $view_file = $blocks_dir . $folder . '/view.js';
        if (file_exists($view_file)) {
            $deps = [];
            if ($folder === 'event-booking' || $folder === 'event-day-slider') {
                $deps = ['jquery', 'parkourone-booking'];
            }
            wp_enqueue_script(
                'parkourone-' . $folder . '-view',
                $blocks_url . $folder . '/view.js',
                $deps,
                filemtime($view_file),
                true // In footer laden
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_block_view_scripts');

function parkourone_get_available_dates_for_event($event_id) {
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_event_id',
                'value' => $event_id
            ]
        ]
    ]);
    
    $available_dates = [];
    $today = date('Y-m-d');
    
    foreach ($products as $product) {
        $product_id = $product->ID;
        $event_date = get_post_meta($product_id, '_event_date', true);
        $stock = get_post_meta($product_id, '_stock', true);
        $stock_status = get_post_meta($product_id, '_stock_status', true);
        
        if (strpos($event_date, '.') !== false) {
            $date_parts = explode('.', $event_date);
        } else {
            $date_parts = explode('-', $event_date);
        }
        
        if (count($date_parts) === 3) {
            if (strlen($date_parts[0]) === 4) {
                $formatted_date = $event_date;
            } else {
                $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            }
        } else {
            $formatted_date = $event_date;
        }
        
        $stock_int = (int) $stock;

        // Get price from WooCommerce product
        $price = '';
        $price_raw = 0;
        if (function_exists('wc_get_product')) {
            $wc_product = wc_get_product($product_id);
            if ($wc_product) {
                $price_raw = $wc_product->get_price();
                $price = $wc_product->get_price_html();
            }
        } else {
            $price_raw = get_post_meta($product_id, '_price', true);
            $price = $price_raw ? number_format((float)$price_raw, 2, ',', '.') . ' €' : '';
        }

        if ($formatted_date >= $today && $stock_int > 0 && $stock_status === 'instock') {
            $available_dates[] = [
                'product_id' => $product_id,
                'date' => $event_date,
                'date_formatted' => date_i18n('l, j. F Y', strtotime($formatted_date)),
                'stock' => $stock_int,
                'price' => $price,
                'price_raw' => $price_raw
            ];
        }
    }
    
    usort($available_dates, function($a, $b) {
        $date_a = explode('-', $a['date']);
        $date_b = explode('-', $b['date']);
        $ts_a = mktime(0, 0, 0, $date_a[1], $date_a[0], $date_a[2]);
        $ts_b = mktime(0, 0, 0, $date_b[1], $date_b[0], $date_b[2]);
        return $ts_a - $ts_b;
    });
    
    return $available_dates;
}

function parkourone_booking_scripts() {
    if (!class_exists('WooCommerce')) return;

    wp_enqueue_script(
        'parkourone-booking',
        get_template_directory_uri() . '/assets/js/booking.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/booking.js'),
        true
    );

    wp_localize_script('parkourone-booking', 'poBooking', [
        'restUrl' => rest_url('parkourone/v1/add-to-cart'),
        'nonce'   => wp_create_nonce('wp_rest'),
        // Legacy-Fallback für ältere Aufrufe
        'ajaxUrl' => rest_url('parkourone/v1/add-to-cart'),
    ]);
}
add_action('wp_enqueue_scripts', 'parkourone_booking_scripts');

/**
 * REST API Endpoint für Probetraining-Buchungen
 * Umgeht AIOS-Firewall die admin-ajax.php für Nicht-Eingeloggte blockiert
 */
function parkourone_register_booking_rest_route() {
    register_rest_route('parkourone/v1', '/add-to-cart', [
        'methods'             => 'POST',
        'callback'            => 'parkourone_rest_add_to_cart',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'parkourone_register_booking_rest_route');

function parkourone_rest_add_to_cart($request) {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('no_woocommerce', 'WooCommerce nicht aktiv', ['status' => 500]);
    }

    // Nonce aus X-WP-Nonce Header (wird von fetch automatisch gesendet)
    // oder aus POST-Body als Fallback
    $params = $request->get_params();

    $product_id   = absint($params['product_id'] ?? 0);
    $event_id     = absint($params['event_id'] ?? 0);
    $vorname      = sanitize_text_field($params['vorname'] ?? '');
    $name         = sanitize_text_field($params['name'] ?? '');
    $geburtsdatum = sanitize_text_field($params['geburtsdatum'] ?? '');

    if (!$product_id || !$vorname || !$name || !$geburtsdatum) {
        return new WP_REST_Response([
            'success' => false,
            'data'    => ['message' => 'Bitte alle Felder ausfüllen'],
        ], 400);
    }

    // WooCommerce Session sicherstellen (für nicht-eingeloggte User)
    if (!WC()->session) {
        WC()->session = new WC_Session_Handler();
        WC()->session->init();
    }
    if (!WC()->cart) {
        WC()->cart = new WC_Cart();
        WC()->cart->get_cart();
    }
    if (!WC()->customer) {
        WC()->customer = new WC_Customer(get_current_user_id(), true);
    }

    // Participant-Daten in $_POST setzen (für Event-Plugins die darauf zugreifen)
    $_POST['event_id']                       = $event_id;
    $_POST['event_participant_name']          = [$name];
    $_POST['event_participant_vorname']       = [$vorname];
    $_POST['event_participant_geburtsdatum']  = [$geburtsdatum];

    $added = WC()->cart->add_to_cart($product_id, 1);

    if ($added) {
        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'message'    => 'Erfolgreich zum Warenkorb hinzugefügt',
                'cart_count' => WC()->cart->get_cart_contents_count(),
            ],
        ], 200);
    }

    return new WP_REST_Response([
        'success' => false,
        'data'    => ['message' => 'Fehler beim Hinzufügen zum Warenkorb'],
    ], 200);
}

/**
 * AJAX: Produkt-Showcase — Add-to-Cart für Simple + Variable Products.
 */
function parkourone_produkt_showcase_add_to_cart() {
	check_ajax_referer('po_produkt_showcase_nonce', 'nonce');

	if (!function_exists('WC')) {
		wp_send_json_error(['message' => 'WooCommerce nicht aktiv']);
	}

	// Session initialisieren falls nicht vorhanden (häufig bei AJAX-Calls)
	if (is_null(WC()->session)) {
		WC()->session = new \WC_Session_Handler();
		WC()->session->init();
	}

	if (is_null(WC()->cart)) {
		WC()->initialize_cart();
	}

	$product_id   = isset($_POST['product_id'])   ? absint($_POST['product_id'])   : 0;
	$variation_id = isset($_POST['variation_id'])  ? absint($_POST['variation_id']) : 0;

	if (!$product_id) {
		wp_send_json_error(['message' => 'Kein Produkt angegeben']);
	}

	$product = wc_get_product($product_id);
	if (!$product || !$product->is_purchasable()) {
		wp_send_json_error(['message' => 'Produkt ist nicht verfügbar']);
	}

	// Variable Products: Variation validieren und Attribute sammeln
	if ($product->is_type('variable')) {
		if (!$variation_id) {
			wp_send_json_error(['message' => 'Bitte wähle eine Variante']);
		}

		$variation = wc_get_product($variation_id);
		if (!$variation || !$variation->is_in_stock()) {
			wp_send_json_error(['message' => 'Variante nicht verfügbar']);
		}

		// Variation-Attribute aus POST sammeln
		$variation_attributes = [];
		$parent_attributes = $product->get_variation_attributes();
		foreach ($parent_attributes as $attr_name => $options) {
			$key = 'attribute_' . sanitize_title($attr_name);
			if (isset($_POST[$key])) {
				$variation_attributes[$key] = sanitize_text_field($_POST[$key]);
			}
		}

		$added = WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation_attributes);
	} else {
		// Simple Product
		if (!$product->is_in_stock()) {
			wp_send_json_error(['message' => 'Produkt ist nicht verfügbar']);
		}
		$added = WC()->cart->add_to_cart($product_id, 1);
	}

	if ($added) {
		wp_send_json_success([
			'message'    => 'Erfolgreich zum Warenkorb hinzugefuegt',
			'cart_count' => WC()->cart->get_cart_contents_count(),
		]);
	} else {
		// WC-Fehlermeldungen auslesen für bessere Diagnose
		$errors = wc_get_notices('error');
		wc_clear_notices();
		$error_msg = 'Fehler beim Hinzufügen zum Warenkorb';
		if (!empty($errors)) {
			$messages = array_map(function($e) {
				return is_array($e) ? wp_strip_all_tags($e['notice']) : wp_strip_all_tags($e);
			}, $errors);
			$error_msg = implode(' ', $messages);
		}
		wp_send_json_error(['message' => $error_msg]);
	}
}
add_action('wp_ajax_po_produkt_showcase_add_to_cart', 'parkourone_produkt_showcase_add_to_cart');
add_action('wp_ajax_nopriv_po_produkt_showcase_add_to_cart', 'parkourone_produkt_showcase_add_to_cart');

/**
 * AJAX: Personal Training — Add-to-Cart mit Paket-Info
 */
function parkourone_pt_add_to_cart() {
	check_ajax_referer('po_pt_nonce', 'nonce');

	if (!function_exists('WC')) {
		wp_send_json_error(['message' => 'WooCommerce nicht aktiv']);
	}

	if (is_null(WC()->session)) {
		WC()->session = new \WC_Session_Handler();
		WC()->session->init();
	}
	if (is_null(WC()->cart)) {
		WC()->initialize_cart();
	}

	$product_id    = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
	$package_title = isset($_POST['package_title']) ? sanitize_text_field($_POST['package_title']) : '';
	$package_price = isset($_POST['package_price']) ? sanitize_text_field($_POST['package_price']) : '';
	$work_on       = isset($_POST['work_on']) ? array_map('sanitize_text_field', (array) $_POST['work_on']) : [];
	$work_on_other = isset($_POST['work_on_other']) ? sanitize_textarea_field($_POST['work_on_other']) : '';

	if (!$product_id) {
		wp_send_json_error(['message' => 'Kein Produkt angegeben']);
	}

	$product = wc_get_product($product_id);
	if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
		wp_send_json_error(['message' => 'Produkt ist nicht verfügbar']);
	}

	$cart_item_data = [
		'po_pt_package' => $package_title,
		'po_pt_price'   => $package_price,
		'po_pt_work_on' => $work_on,
	];
	if ($work_on_other) {
		$cart_item_data['po_pt_work_on_other'] = $work_on_other;
	}

	$added = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

	if ($added) {
		wp_send_json_success([
			'message'    => 'Erfolgreich zum Warenkorb hinzugefuegt',
			'cart_count' => WC()->cart->get_cart_contents_count(),
		]);
	} else {
		$errors = wc_get_notices('error');
		wc_clear_notices();
		$error_msg = 'Fehler beim Hinzufügen zum Warenkorb';
		if (!empty($errors)) {
			$messages = array_map(function($e) {
				return is_array($e) ? wp_strip_all_tags($e['notice']) : wp_strip_all_tags($e);
			}, $errors);
			$error_msg = implode(' ', $messages);
		}
		wp_send_json_error(['message' => $error_msg]);
	}
}
add_action('wp_ajax_po_pt_add_to_cart', 'parkourone_pt_add_to_cart');
add_action('wp_ajax_nopriv_po_pt_add_to_cart', 'parkourone_pt_add_to_cart');

function parkourone_register_coach_cpt() {
	// Schul-Taxonomie für Coaches
	register_taxonomy('coach_school', 'coach', [
		'labels' => [
			'name' => 'Schulen',
			'singular_name' => 'Schule',
			'search_items' => 'Schulen suchen',
			'all_items' => 'Alle Schulen',
			'edit_item' => 'Schule bearbeiten',
			'update_item' => 'Schule aktualisieren',
			'add_new_item' => 'Neue Schule hinzufügen',
			'new_item_name' => 'Neue Schule',
			'menu_name' => 'Schulen'
		],
		'hierarchical' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => false
	]);

	register_post_type('coach', [
		'labels' => [
			'name' => 'Coaches',
			'singular_name' => 'Coach',
			'add_new' => 'Neuer Coach',
			'add_new_item' => 'Neuen Coach hinzufügen',
			'edit_item' => 'Coach bearbeiten',
			'new_item' => 'Neuer Coach',
			'view_item' => 'Coach ansehen',
			'search_items' => 'Coaches suchen',
			'not_found' => 'Keine Coaches gefunden',
			'not_found_in_trash' => 'Keine Coaches im Papierkorb'
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 25,
		'menu_icon' => 'dashicons-groups',
		'supports' => ['title', 'thumbnail'],
		'has_archive' => false,
		'rewrite' => false,
		'taxonomies' => ['coach_school']
	]);

	// Standard-Schulen erstellen
	parkourone_create_default_coach_schools();
}
add_action('init', 'parkourone_register_coach_cpt');

/**
 * Erstellt die Standard-Schulen für Coaches
 */
function parkourone_create_default_coach_schools() {
	$schools = [
		'schweiz' => 'Schweiz',
		'berlin' => 'Berlin',
		'dresden' => 'Dresden',
		'hannover' => 'Hannover',
		'muenster' => 'Münster',
		'augsburg' => 'Augsburg',
		'rheinruhr' => 'Rheinruhr',
	];

	foreach ($schools as $slug => $name) {
		if (!term_exists($slug, 'coach_school')) {
			wp_insert_term($name, 'coach_school', ['slug' => $slug]);
		}
	}
}

function parkourone_coach_metaboxes() {
	add_meta_box(
		'coach_profile_fields',
		'Profil-Informationen',
		'parkourone_coach_profile_metabox',
		'coach',
		'normal',
		'high'
	);
	
	add_meta_box(
		'coach_source_info',
		'Coach-Quelle',
		'parkourone_coach_source_metabox',
		'coach',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'parkourone_coach_metaboxes');

function parkourone_coach_source_metabox($post) {
	$source = get_post_meta($post->ID, '_coach_source', true);
	$api_image = get_post_meta($post->ID, '_coach_api_image', true);
	$profile_image = get_post_meta($post->ID, '_coach_profile_image', true);

	echo '<p><strong>Quelle:</strong> ' . ($source === 'manual' ? 'Manuell erstellt' : ($source === 'preset' ? 'Preset' : 'API (Academyboard)')) . '</p>';

	if ($api_image) {
		echo '<p><strong>API-Bild:</strong></p>';
		echo '<img src="' . esc_url($api_image) . '" style="max-width:100%;height:auto;border-radius:8px;margin-bottom:8px;">';
	} else {
		echo '<p style="color:#999;">Kein API-Bild vorhanden.</p>';
	}

	if ($profile_image) {
		echo '<p><strong>Manuelles Profilbild:</strong> aktiv</p>';
	} elseif (!$api_image) {
		echo '<p style="color:#b32d2e;"><strong>Hinweis:</strong> Kein Bild vorhanden. Bitte unter "Profilbild (für Grid-Karte)" ein Bild hochladen.</p>';
	}
}

function parkourone_coach_profile_metabox($post) {
	wp_nonce_field('parkourone_coach_save', 'parkourone_coach_nonce');
	
	$fields = [
		'_coach_profile_image' => ['label' => 'Profilbild (für Grid-Karte)', 'type' => 'image'],
		'_coach_email' => ['label' => 'E-Mail (für Profil-Link)', 'type' => 'email', 'placeholder' => 'coach@parkourone.ch'],
		'_coach_rolle' => ['label' => 'Rolle', 'type' => 'text', 'placeholder' => 'z.B. Head Coach, Coach, Gründer'],
		'_coach_standort' => ['label' => 'Standort', 'type' => 'text', 'placeholder' => 'z.B. Bern, Zürich'],
		'_coach_parkour_seit' => ['label' => 'Parkour seit', 'type' => 'text', 'placeholder' => 'z.B. 2015'],
		'_coach_po_seit' => ['label' => 'Bei ParkourONE seit', 'type' => 'text', 'placeholder' => 'z.B. 2019'],
		'_coach_hero_bild' => ['label' => 'Hero-Bild (Action-Foto)', 'type' => 'image'],
		'_coach_leitsatz' => ['label' => 'Ein Satz, der mir Kraft gibt', 'type' => 'text', 'placeholder' => 'z.B. Être fort pour être utile'],
		'_coach_kurzvorstellung' => ['label' => 'Meine Geschichte', 'type' => 'textarea', 'placeholder' => 'Erzähl deine Geschichte...'],
		'_coach_philosophie_bild' => ['label' => 'Bild zur Geschichte', 'type' => 'image'],
		'_coach_video_url' => ['label' => 'Video (YouTube/Vimeo URL)', 'type' => 'url', 'placeholder' => 'https://www.youtube.com/watch?v=...'],
		'_coach_moment' => ['label' => 'Ein Parkour Moment, der mich geprägt hat', 'type' => 'textarea', 'placeholder' => 'Ein besonderer Moment...'],
		'_coach_moment_bild' => ['label' => 'Bild zum Moment', 'type' => 'image']
	];
	
	echo '<table class="form-table"><tbody>';
	
	foreach ($fields as $key => $field) {
		$value = get_post_meta($post->ID, $key, true);
		echo '<tr>';
		echo '<th><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th>';
		echo '<td>';
		
		if ($field['type'] === 'textarea') {
			echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="4" class="large-text" placeholder="' . esc_attr($field['placeholder'] ?? '') . '">' . esc_textarea($value) . '</textarea>';
		} elseif ($field['type'] === 'image') {
			echo '<div class="coach-image-field">';
			echo '<input type="hidden" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
			echo '<div id="' . esc_attr($key) . '_preview" style="margin-bottom:10px;">';
			if ($value) {
				echo '<img src="' . esc_url($value) . '" style="max-width:300px;height:auto;border-radius:8px;">';
			}
			echo '</div>';
			echo '<button type="button" class="button coach-upload-image" data-field="' . esc_attr($key) . '">Bild auswählen</button> ';
			echo '<button type="button" class="button coach-remove-image" data-field="' . esc_attr($key) . '" ' . ($value ? '' : 'style="display:none;"') . '>Bild entfernen</button>';
			echo '</div>';
		} else {
			echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="large-text" placeholder="' . esc_attr($field['placeholder'] ?? '') . '">';
		}
		
		echo '</td>';
		echo '</tr>';
	}
	
	echo '</tbody></table>';
	
	echo '<script>
	jQuery(document).ready(function($) {
		var mediaUploader;
		
		$(".coach-upload-image").on("click", function(e) {
			e.preventDefault();
			var fieldId = $(this).data("field");
			
			mediaUploader = wp.media({
				title: "Bild auswählen",
				button: { text: "Auswählen" },
				multiple: false
			});
			
			mediaUploader.on("select", function() {
				var attachment = mediaUploader.state().get("selection").first().toJSON();
				$("#" + fieldId).val(attachment.url);
				$("#" + fieldId + "_preview").html("<img src=\"" + attachment.url + "\" style=\"max-width:300px;height:auto;border-radius:8px;\">");
				$(".coach-remove-image[data-field=\"" + fieldId + "\"]").show();
			});
			
			mediaUploader.open();
		});
		
		$(".coach-remove-image").on("click", function(e) {
			e.preventDefault();
			var fieldId = $(this).data("field");
			$("#" + fieldId).val("");
			$("#" + fieldId + "_preview").html("");
			$(this).hide();
		});
	});
	</script>';
}

function parkourone_coach_save_meta($post_id) {
	if (!isset($_POST['parkourone_coach_nonce']) || !wp_verify_nonce($_POST['parkourone_coach_nonce'], 'parkourone_coach_save')) {
		return;
	}
	
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	
	$fields = [
		'_coach_profile_image',
		'_coach_email',
		'_coach_rolle',
		'_coach_standort',
		'_coach_parkour_seit',
		'_coach_po_seit',
		'_coach_hero_bild',
		'_coach_leitsatz',
		'_coach_kurzvorstellung',
		'_coach_philosophie_bild',
		'_coach_video_url',
		'_coach_moment',
		'_coach_moment_bild'
	];
	
	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
		}
	}
	
	if (!get_post_meta($post_id, '_coach_source', true)) {
		update_post_meta($post_id, '_coach_source', 'manual');
	}
}
add_action('save_post_coach', 'parkourone_coach_save_meta');

/**
 * Coaches immer auf "publish" erzwingen — keine Entwürfe erlaubt
 */
function parkourone_force_coach_publish($data, $postarr) {
	if ($data['post_type'] !== 'coach') {
		return $data;
	}

	// Auto-Drafts und Papierkorb nicht anfassen
	if (in_array($data['post_status'], ['auto-draft', 'trash'], true)) {
		return $data;
	}

	$data['post_status'] = 'publish';
	return $data;
}
add_filter('wp_insert_post_data', 'parkourone_force_coach_publish', 10, 2);

function parkourone_coach_admin_scripts($hook) {
	global $post_type;
	
	if ($post_type === 'coach' && in_array($hook, ['post.php', 'post-new.php'])) {
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'parkourone_coach_admin_scripts');

function parkourone_sync_coaches_from_events() {
	$events = get_posts([
		'post_type' => 'event',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	]);

	$api_coaches = [];

	foreach ($events as $event) {
		// Headcoach
		$name = get_post_meta($event->ID, '_event_headcoach', true);
		$image = get_post_meta($event->ID, '_event_headcoach_image_url', true);
		$email = get_post_meta($event->ID, '_event_headcoach_email', true);

		if (!empty($name) && !isset($api_coaches[$name])) {
			$api_coaches[$name] = [
				'image' => $image,
				'email' => $email
			];
		}

		// Assistenz-Coaches aus AcademyBoard (Format: [{"name": "...", "image_url": "..."}, ...])
		$coaches_meta = get_post_meta($event->ID, '_event_coaches', true);
		if (!empty($coaches_meta)) {
			$coaches_list = is_array($coaches_meta) ? $coaches_meta : json_decode($coaches_meta, true);
			if (is_array($coaches_list)) {
				foreach ($coaches_list as $coach) {
					$coach_name = $coach['name'] ?? '';
					if (!empty($coach_name) && !isset($api_coaches[$coach_name])) {
						$api_coaches[$coach_name] = [
							'image' => $coach['image_url'] ?? '',
							'email' => ''
						];
					}
				}
			}
		}
	}

	foreach ($api_coaches as $name => $data) {
		// Zuerst nach Name suchen
		$existing = get_posts([
			'post_type' => 'coach',
			'title' => $name,
			'posts_per_page' => 1,
			'post_status' => ['publish', 'draft']
		]);

		// Wenn nicht gefunden, nach E-Mail suchen
		if (empty($existing) && !empty($data['email'])) {
			$existing = get_posts([
				'post_type' => 'coach',
				'posts_per_page' => 1,
				'post_status' => ['publish', 'draft'],
				'meta_query' => [
					['key' => '_coach_email', 'value' => $data['email']]
				]
			]);
		}

		if (empty($existing)) {
			// Neuen Coach erstellen
			$coach_id = wp_insert_post([
				'post_type' => 'coach',
				'post_title' => $name,
				'post_status' => 'publish'
			]);

			if ($coach_id && !is_wp_error($coach_id)) {
				update_post_meta($coach_id, '_coach_source', 'api');
				update_post_meta($coach_id, '_coach_api_image', $data['image']);
				if (!empty($data['email'])) {
					update_post_meta($coach_id, '_coach_email', $data['email']);
				}
			}
		} else {
			// Existierenden Coach aktualisieren
			$coach_id = $existing[0]->ID;
			$source = get_post_meta($coach_id, '_coach_source', true);

			// API-Bild immer aktualisieren (das ist die aktuelle Info aus der API)
			if (!empty($data['image'])) {
				update_post_meta($coach_id, '_coach_api_image', $data['image']);
			}

			// E-Mail nur setzen wenn noch leer
			if (!empty($data['email']) && empty(get_post_meta($coach_id, '_coach_email', true))) {
				update_post_meta($coach_id, '_coach_email', $data['email']);
			}

			// Source NICHT ändern wenn manual oder preset (damit bleibt geschützt)
			// Nur bei draft auf publish setzen
			if ($existing[0]->post_status === 'draft') {
				wp_update_post([
					'ID' => $coach_id,
					'post_status' => 'publish'
				]);
			}
		}
	}

	do_action('parkourone_coaches_synced');
}

function parkourone_sync_coaches_on_admin_load($screen) {
	if ($screen->post_type === 'coach' && $screen->base === 'edit') {
		parkourone_sync_coaches_from_events();
	}
}
add_action('current_screen', 'parkourone_sync_coaches_on_admin_load');

// =====================================================
// Preset Coaches für Import
// =====================================================

/**
 * Gibt die Preset-Coaches für eine Schule zurück
 */
function parkourone_get_preset_coaches($school = 'berlin') {
	$coaches = [
		'berlin' => [
			// Klassenleiter (mit AcademyBoard Bildern)
			['name' => 'Minh', 'email' => 'minh@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/avatar_757_1762954933.jpeg'],
			['name' => 'Carina', 'email' => 'carina@parkourone.com', 'rolle' => 'Klassenleiterin', 'image' => 'https://academyboard.parkourone.com/storage/avatars/carinahötschl-2023-11-29_10:18:13-avatar.png'],
			['name' => 'Marie', 'email' => 'marie@parkourone.com', 'rolle' => 'Klassenleiterin', 'image' => 'https://academyboard.parkourone.com/storage/avatars/mariefechner-2019-10-12_22:46:35-2.JPG'],
			['name' => 'Raguel', 'email' => 'raguel@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/raguel_coach-2025-05-20_16:10:59-avatar.png'],
			['name' => 'Luca', 'email' => 'luca@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/luca_coach-2021-04-13_10:45:40-IMG5928.JPG'],
			['name' => 'Marius', 'email' => 'marius@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/marius_coach-2020-09-17_18:48:14-elbi8079preview.jpeg'],
			['name' => 'Marty', 'email' => 'marty@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/marty_coach-2023-04-17_10:49:57-avatar.png'],
			['name' => 'Martin', 'email' => 'martin@parkourone.com', 'rolle' => 'Klassenleiter', 'image' => 'https://academyboard.parkourone.com/storage/avatars/martingessinger_coach-2025-02-07_20:42:37-avatar.png'],
			// Co-Leitung (ohne AcademyBoard Bilder)
			['name' => 'Anne', 'email' => 'anne.damrau@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
			['name' => 'Jasper', 'email' => 'jasper.schuppan@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
			['name' => 'Peer', 'email' => 'peer@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
			['name' => 'Fabian', 'email' => 'fabian.nonnenmacher@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
			['name' => 'Ole', 'email' => 'ole.brekenfeld@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
			['name' => 'Paul', 'email' => 'paul.reithmeier@parkourone.com', 'rolle' => 'Co-Leitung', 'image' => ''],
		],
		'rheinruhr' => [
			['name' => 'Deniz', 'email' => 'deniz@parkourone.com', 'rolle' => 'Schulleiter', 'image' => ''],
			['name' => 'Emil', 'email' => 'emil@parkourone.com', 'rolle' => 'Coach', 'image' => ''],
		],
		'schweiz' => [
			// Hier können Schweizer Coaches hinzugefügt werden
		],
	];

	if ($school === 'all') {
		$all = [];
		foreach ($coaches as $school_coaches) {
			$all = array_merge($all, $school_coaches);
		}
		return $all;
	}

	return $coaches[$school] ?? [];
}

/**
 * Admin Notice für Coach Import
 */
function parkourone_coaches_admin_notice() {
	$screen = get_current_screen();
	if ($screen->post_type !== 'coach') return;

	// Welche Schulen haben Preset-Coaches?
	$schools_with_presets = [];
	$school_names = [
		'berlin' => 'Berlin',
		'schweiz' => 'Schweiz',
		'dresden' => 'Dresden',
		'hannover' => 'Hannover',
		'muenster' => 'Münster',
		'augsburg' => 'Augsburg',
		'rheinruhr' => 'Rheinruhr',
	];

	foreach ($school_names as $slug => $name) {
		$presets = parkourone_get_preset_coaches($slug);
		if (!empty($presets)) {
			// Prüfen wie viele noch nicht importiert sind
			$not_imported = 0;
			foreach ($presets as $preset) {
				$existing = get_posts([
					'post_type' => 'coach',
					'posts_per_page' => 1,
					'post_status' => 'any',
					'meta_query' => [
						'relation' => 'OR',
						['key' => '_coach_email', 'value' => $preset['email']],
					]
				]);
				// Auch nach Name suchen
				if (empty($existing)) {
					$existing = get_posts([
						'post_type' => 'coach',
						'title' => $preset['name'],
						'posts_per_page' => 1,
						'post_status' => 'any'
					]);
				}
				if (empty($existing)) {
					$not_imported++;
				}
			}
			if ($not_imported > 0) {
				$schools_with_presets[$slug] = [
					'name' => $name,
					'total' => count($presets),
					'not_imported' => $not_imported
				];
			}
		}
	}

	if (empty($schools_with_presets)) return;

	// Erkannte Schule (Subdomain) vorselektieren
	$detected_school = '';
	if (function_exists('parkourone_get_site_location')) {
		$location = parkourone_get_site_location();
		$subdomain_to_school = [
			'berlin' => 'berlin',
			'schweiz' => 'schweiz',
			'dresden' => 'dresden',
			'hannover' => 'hannover',
			'muenster' => 'muenster',
			'augsburg' => 'augsburg',
			'duisburg' => 'rheinruhr',
			'düsseldorf' => 'rheinruhr',
			'krefeld' => 'rheinruhr',
			'localhost' => 'berlin',
			'new' => 'berlin',
		];
		$detected_school = $subdomain_to_school[$location['slug']] ?? '';
	}
	?>
	<div class="notice notice-info is-dismissible" id="po-coaches-import-notice">
		<p><strong>Coaches importieren</strong></p>
		<p>Es gibt Preset-Coaches die noch nicht importiert wurden:</p>
		<p>
			<select id="po-coach-school-select" style="margin-right: 10px;">
				<?php foreach ($schools_with_presets as $slug => $info): ?>
					<option value="<?php echo esc_attr($slug); ?>"<?php echo $slug === $detected_school ? ' selected' : ''; ?>><?php echo esc_html($info['name']); ?> (<?php echo $info['not_imported']; ?> von <?php echo $info['total']; ?> neu)</option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button button-primary" id="po-import-coaches">Coaches importieren</button>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-import-coaches').on('click', function() {
			var school = $('#po-coach-school-select').val();
			$(this).prop('disabled', true).text('Importiere...');
			$.post(ajaxurl, {
				action: 'po_import_coaches',
				nonce: '<?php echo wp_create_nonce('po_import_coaches'); ?>',
				school: school
			}, function(response) {
				if (response.success) {
					alert('Erfolgreich: ' + response.data.imported + ' Coaches importiert, ' + response.data.updated + ' aktualisiert.');
					location.reload();
				} else {
					alert('Fehler: ' + response.data.message);
					$('#po-import-coaches').prop('disabled', false).text('Coaches importieren');
				}
			});
		});
	});
	</script>
	<?php
}
add_action('admin_notices', 'parkourone_coaches_admin_notice');

/**
 * AJAX Handler für Coach Import
 */
function parkourone_ajax_import_coaches() {
	check_ajax_referer('po_import_coaches', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error(['message' => 'Keine Berechtigung']);
	}

	$school = sanitize_text_field($_POST['school'] ?? 'berlin');
	$presets = parkourone_get_preset_coaches($school);

	if (empty($presets)) {
		wp_send_json_error(['message' => 'Keine Preset-Coaches für diese Schule']);
	}

	$imported = 0;
	$updated = 0;

	foreach ($presets as $preset) {
		// Zuerst nach E-Mail suchen
		$existing = null;
		if (!empty($preset['email'])) {
			$found = get_posts([
				'post_type' => 'coach',
				'posts_per_page' => 1,
				'post_status' => 'any',
				'meta_query' => [
					['key' => '_coach_email', 'value' => $preset['email']]
				]
			]);
			if (!empty($found)) {
				$existing = $found[0];
			}
		}

		// Dann nach Name suchen
		if (!$existing) {
			$found = get_posts([
				'post_type' => 'coach',
				'title' => $preset['name'],
				'posts_per_page' => 1,
				'post_status' => 'any'
			]);
			if (!empty($found)) {
				$existing = $found[0];
			}
		}

		if ($existing) {
			// Existierenden Coach aktualisieren - nur leere Felder füllen
			$coach_id = $existing->ID;

			// E-Mail nur setzen wenn leer
			if (!empty($preset['email']) && empty(get_post_meta($coach_id, '_coach_email', true))) {
				update_post_meta($coach_id, '_coach_email', $preset['email']);
			}

			// Rolle nur setzen wenn leer
			if (!empty($preset['rolle']) && empty(get_post_meta($coach_id, '_coach_rolle', true))) {
				update_post_meta($coach_id, '_coach_rolle', $preset['rolle']);
			}

			// API-Bild immer aktualisieren (kommt von AcademyBoard)
			if (!empty($preset['image'])) {
				update_post_meta($coach_id, '_coach_api_image', $preset['image']);
			}

			// Schule zuweisen wenn nicht vorhanden
			$existing_schools = wp_get_post_terms($coach_id, 'coach_school', ['fields' => 'slugs']);
			if (!in_array($school, $existing_schools)) {
				wp_set_object_terms($coach_id, $school, 'coach_school', true); // true = append
			}

			// Auf publish setzen wenn draft
			if ($existing->post_status === 'draft') {
				wp_update_post(['ID' => $coach_id, 'post_status' => 'publish']);
			}

			$updated++;
		} else {
			// Neuen Coach erstellen
			$coach_id = wp_insert_post([
				'post_type' => 'coach',
				'post_title' => $preset['name'],
				'post_status' => 'publish'
			]);

			if ($coach_id && !is_wp_error($coach_id)) {
				update_post_meta($coach_id, '_coach_source', 'preset');
				update_post_meta($coach_id, '_coach_email', $preset['email']);
				update_post_meta($coach_id, '_coach_rolle', $preset['rolle']);

				if (!empty($preset['image'])) {
					update_post_meta($coach_id, '_coach_api_image', $preset['image']);
				}

				// Schule zuweisen
				wp_set_object_terms($coach_id, $school, 'coach_school');

				$imported++;
			}
		}
	}

	wp_send_json_success(['imported' => $imported, 'updated' => $updated]);
}
add_action('wp_ajax_po_import_coaches', 'parkourone_ajax_import_coaches');

function parkourone_get_coach_by_name($name) {
	$coaches = get_posts([
		'post_type' => 'coach',
		'title' => $name,
		'posts_per_page' => 1,
		'post_status' => 'publish'
	]);
	
	if (empty($coaches)) {
		return null;
	}
	
	$coach = $coaches[0];
	$coach_id = $coach->ID;
	
	return [
		'id' => $coach_id,
		'name' => $coach->post_title,
		'api_image' => get_post_meta($coach_id, '_coach_api_image', true),
		'profile_image' => get_post_meta($coach_id, '_coach_profile_image', true),
		'source' => get_post_meta($coach_id, '_coach_source', true),
		'rolle' => get_post_meta($coach_id, '_coach_rolle', true),
		'standort' => get_post_meta($coach_id, '_coach_standort', true),
		'kurzvorstellung' => get_post_meta($coach_id, '_coach_kurzvorstellung', true),
		'philosophie' => get_post_meta($coach_id, '_coach_philosophie', true),
		'philosophie_bild' => get_post_meta($coach_id, '_coach_philosophie_bild', true),
		'moment' => get_post_meta($coach_id, '_coach_moment', true),
		'moment_bild' => get_post_meta($coach_id, '_coach_moment_bild', true),
		'video_url' => get_post_meta($coach_id, '_coach_video_url', true),
		'ausserhalb' => get_post_meta($coach_id, '_coach_ausserhalb', true),
		'leitsatz' => get_post_meta($coach_id, '_coach_leitsatz', true)
	];
}

function parkourone_coach_has_profile($coach_data) {
	if (!$coach_data) return false;
	
	$profile_fields = ['rolle', 'standort', 'kurzvorstellung', 'philosophie', 'moment', 'video_url', 'ausserhalb', 'leitsatz'];
	
	foreach ($profile_fields as $field) {
		if (!empty($coach_data[$field])) {
			return true;
		}
	}
	
	return false;
}

function parkourone_coach_profile_page_init() {
	add_rewrite_rule('^mein-coach-profil/?$', 'index.php?coach_profile_page=1', 'top');
	add_rewrite_tag('%coach_profile_page%', '1');
	add_rewrite_tag('%coach_token%', '([a-zA-Z0-9]+)');
	add_rewrite_rule('^mein-coach-profil/([a-zA-Z0-9]+)/?$', 'index.php?coach_profile_page=1&coach_token=$matches[1]', 'top');
}
add_action('init', 'parkourone_coach_profile_page_init');

function parkourone_coach_profile_template($template) {
	if (get_query_var('coach_profile_page')) {
		$custom_template = get_template_directory() . '/templates/coach-profile.php';
		if (file_exists($custom_template)) {
			return $custom_template;
		}
	}
	return $template;
}
add_filter('template_include', 'parkourone_coach_profile_template');

function parkourone_generate_coach_token($coach_id) {
	$token = bin2hex(random_bytes(32));
	$expiry = time() + (7 * 24 * 60 * 60);
	update_post_meta($coach_id, '_coach_token', $token);
	update_post_meta($coach_id, '_coach_token_expiry', $expiry);
	return $token;
}

function parkourone_verify_coach_token($token) {
	$coaches = get_posts([
		'post_type' => 'coach',
		'posts_per_page' => 1,
		'post_status' => ['publish', 'draft'],
		'meta_query' => [
			[
				'key' => '_coach_token',
				'value' => $token
			]
		]
	]);
	
	if (empty($coaches)) {
		return false;
	}
	
	$coach = $coaches[0];
	$expiry = get_post_meta($coach->ID, '_coach_token_expiry', true);
	
	if ($expiry && $expiry < time()) {
		return false;
	}
	
	return $coach;
}

function parkourone_find_coach_by_email($email) {
	$coaches = get_posts([
		'post_type' => 'coach',
		'posts_per_page' => 1,
		'post_status' => ['publish', 'draft'],
		'meta_query' => [
			[
				'key' => '_coach_email',
				'value' => $email
			]
		]
	]);
	
	return !empty($coaches) ? $coaches[0] : null;
}

function parkourone_send_coach_magic_link($coach_id) {
	$email = get_post_meta($coach_id, '_coach_email', true);
	if (empty($email)) {
		error_log('PO Debug: No email found for coach ID ' . $coach_id);
		return false;
	}
	
	$token = parkourone_generate_coach_token($coach_id);
	$coach = get_post($coach_id);
	$link = home_url('/mein-coach-profil/' . $token . '/');
	
	error_log('PO Debug: Coach ID ' . $coach_id . ', Email: ' . $email . ', Link: ' . $link);
	
	$subject = 'Dein ParkourONE Coach-Profil';
	$message = "Hallo " . $coach->post_title . ",\n\n";
	$message .= "Hier ist dein persönlicher Link um dein Coach-Profil zu bearbeiten:\n\n";
	$message .= $link . "\n\n";
	$message .= "Der Link ist 7 Tage gültig.\n\n";
	$message .= "Liebe Grüsse\nDein ParkourONE Team";
	
	$admin_email = get_option('admin_email');
	$site_name = get_bloginfo('name');
	$headers = [
		'Content-Type: text/plain; charset=UTF-8',
		'From: ' . $site_name . ' <' . $admin_email . '>'
	];
	
	error_log('PO Debug: Headers: ' . print_r($headers, true));
	
	$result = wp_mail($email, $subject, $message, $headers);
	error_log('PO Debug: wp_mail result: ' . ($result ? 'true' : 'false'));
	
	if (!$result) {
		global $phpmailer;
		if (isset($phpmailer) && is_object($phpmailer)) {
			error_log('PO Debug: PHPMailer error: ' . $phpmailer->ErrorInfo);
		}
	}
	
	return $result;
}

function parkourone_coach_profile_request_ajax() {
	if (!isset($_POST['email']) || !is_email($_POST['email'])) {
		wp_send_json_error(['message' => 'Bitte gib eine gültige E-Mail-Adresse ein.']);
	}
	
	$email = sanitize_email($_POST['email']);
	$coach = parkourone_find_coach_by_email($email);
	
	if (!$coach) {
		wp_send_json_error(['message' => 'Diese E-Mail-Adresse wurde nicht gefunden.']);
	}
	
	$sent = parkourone_send_coach_magic_link($coach->ID);
	
	if ($sent) {
		wp_send_json_success(['message' => 'Ein Link wurde an deine E-Mail-Adresse gesendet.']);
	} else {
		wp_send_json_error(['message' => 'E-Mail konnte nicht gesendet werden. Bitte versuche es später erneut.']);
	}
}
add_action('wp_ajax_nopriv_coach_profile_request', 'parkourone_coach_profile_request_ajax');
add_action('wp_ajax_coach_profile_request', 'parkourone_coach_profile_request_ajax');

function parkourone_coach_profile_save_ajax() {
	if (!isset($_POST['token'])) {
		wp_send_json_error(['message' => 'Ungültiger Token.']);
	}
	
	$coach = parkourone_verify_coach_token(sanitize_text_field($_POST['token']));
	
	if (!$coach) {
		wp_send_json_error(['message' => 'Token ungültig oder abgelaufen.']);
	}
	
	$fields = [
		'rolle', 'standort', 'parkour_seit', 'po_seit', 
		'leitsatz', 'kurzvorstellung', 'moment'
	];
	
	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			update_post_meta($coach->ID, '_coach_' . $field, sanitize_textarea_field($_POST[$field]));
		}
	}
	
	delete_post_meta($coach->ID, '_coach_token');
	delete_post_meta($coach->ID, '_coach_token_expiry');
	
	wp_send_json_success(['message' => 'Dein Profil wurde gespeichert! Du kannst jederzeit einen neuen Link anfordern um Änderungen vorzunehmen.']);
}
add_action('wp_ajax_nopriv_coach_profile_save', 'parkourone_coach_profile_save_ajax');
add_action('wp_ajax_coach_profile_save', 'parkourone_coach_profile_save_ajax');

function parkourone_coach_image_upload_ajax() {
	if (!isset($_POST['token']) || !isset($_POST['field'])) {
		wp_send_json_error(['message' => 'Ungültige Anfrage.']);
	}
	
	$coach = parkourone_verify_coach_token(sanitize_text_field($_POST['token']));
	if (!$coach) {
		wp_send_json_error(['message' => 'Token ungültig oder abgelaufen.']);
	}
	
	$allowed_fields = ['hero_bild', 'philosophie_bild', 'moment_bild'];
	$field = sanitize_text_field($_POST['field']);
	if (!in_array($field, $allowed_fields)) {
		wp_send_json_error(['message' => 'Ungültiges Feld.']);
	}
	
	if (!isset($_POST['image_data']) || empty($_POST['image_data'])) {
		wp_send_json_error(['message' => 'Kein Bild übermittelt.']);
	}
	
	$image_data = $_POST['image_data'];
	
	if (strpos($image_data, 'data:image/') !== 0) {
		wp_send_json_error(['message' => 'Ungültiges Bildformat.']);
	}
	
	$image_parts = explode(',', $image_data);
	if (count($image_parts) !== 2) {
		wp_send_json_error(['message' => 'Ungültige Bilddaten.']);
	}
	
	preg_match('/data:image\/(\w+);base64/', $image_parts[0], $matches);
	$extension = isset($matches[1]) ? $matches[1] : 'jpg';
	if ($extension === 'jpeg') $extension = 'jpg';
	
	$allowed_extensions = ['jpg', 'png', 'webp'];
	if (!in_array($extension, $allowed_extensions)) {
		wp_send_json_error(['message' => 'Nur JPG, PNG oder WebP erlaubt.']);
	}
	
	$decoded = base64_decode($image_parts[1]);
	if ($decoded === false) {
		wp_send_json_error(['message' => 'Bild konnte nicht dekodiert werden.']);
	}
	
	$upload_dir = wp_upload_dir();
	$filename = 'coach-' . $coach->ID . '-' . $field . '-' . time() . '.' . $extension;
	$filepath = $upload_dir['path'] . '/' . $filename;
	
	if (file_put_contents($filepath, $decoded) === false) {
		wp_send_json_error(['message' => 'Bild konnte nicht gespeichert werden.']);
	}
	
	$old_image = get_post_meta($coach->ID, '_coach_' . $field, true);
	if ($old_image) {
		$old_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_image);
		if (file_exists($old_path)) {
			unlink($old_path);
		}
	}
	
	$image_url = $upload_dir['url'] . '/' . $filename;
	update_post_meta($coach->ID, '_coach_' . $field, $image_url);
	
	wp_send_json_success([
		'message' => 'Bild gespeichert!',
		'url' => $image_url
	]);
}
add_action('wp_ajax_nopriv_coach_image_upload', 'parkourone_coach_image_upload_ajax');
add_action('wp_ajax_coach_image_upload', 'parkourone_coach_image_upload_ajax');

function parkourone_coach_image_delete_ajax() {
	if (!isset($_POST['token']) || !isset($_POST['field'])) {
		wp_send_json_error(['message' => 'Ungültige Anfrage.']);
	}
	
	$coach = parkourone_verify_coach_token(sanitize_text_field($_POST['token']));
	if (!$coach) {
		wp_send_json_error(['message' => 'Token ungültig oder abgelaufen.']);
	}
	
	$allowed_fields = ['hero_bild', 'philosophie_bild', 'moment_bild'];
	$field = sanitize_text_field($_POST['field']);
	if (!in_array($field, $allowed_fields)) {
		wp_send_json_error(['message' => 'Ungültiges Feld.']);
	}
	
	$upload_dir = wp_upload_dir();
	$old_image = get_post_meta($coach->ID, '_coach_' . $field, true);
	if ($old_image) {
		$old_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_image);
		if (file_exists($old_path)) {
			unlink($old_path);
		}
	}
	
	delete_post_meta($coach->ID, '_coach_' . $field);
	
	wp_send_json_success(['message' => 'Bild gelöscht.']);
}
add_action('wp_ajax_nopriv_coach_image_delete', 'parkourone_coach_image_delete_ajax');
add_action('wp_ajax_coach_image_delete', 'parkourone_coach_image_delete_ajax');

// =====================================================
// Inquiry Form AJAX Handler
// =====================================================

function parkourone_inquiry_submit() {
	// Nonce check
	if (!wp_verify_nonce($_POST['_nonce'] ?? '', 'po_inquiry_nonce')) {
		wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen. Bitte Seite neu laden.']);
	}

	// Honeypot
	if (!empty($_POST['po_website'])) {
		// Pretend success for bots
		wp_send_json_success(['message' => 'Vielen Dank! Deine Anfrage wurde gesendet.']);
	}

	// Rate Limiting (60s per IP)
	$ip = $_SERVER['REMOTE_ADDR'];
	$transient_key = 'po_inquiry_' . md5($ip);
	if (get_transient($transient_key)) {
		wp_send_json_error(['message' => 'Bitte warte einen Moment bevor du eine weitere Anfrage sendest.']);
	}
	set_transient($transient_key, 1, 60);

	// Sanitize fields
	$form_type   = sanitize_text_field($_POST['form_type'] ?? 'workshop');
	$nachname    = sanitize_text_field($_POST['nachname'] ?? '');
	$vorname     = sanitize_text_field($_POST['vorname'] ?? '');
	$adresse     = sanitize_text_field($_POST['adresse'] ?? '');
	$plz_ort     = sanitize_text_field($_POST['plz_ort'] ?? '');
	$telefon     = sanitize_text_field($_POST['telefon'] ?? '');
	$email       = sanitize_email($_POST['email'] ?? '');
	$ort         = sanitize_text_field($_POST['ort'] ?? '');
	$teilnehmer  = sanitize_text_field($_POST['teilnehmer'] ?? '');
	$datum       = sanitize_text_field($_POST['datum'] ?? '');
	$projektlaenge = sanitize_text_field($_POST['projektlaenge'] ?? '');
	$klassen     = sanitize_text_field($_POST['klassen'] ?? '');
	$nachricht   = sanitize_textarea_field($_POST['nachricht'] ?? '');
	$agb         = isset($_POST['agb']) && $_POST['agb'] === '1';

	// Checkbox groups
	$checkbox_groups = [];
	if (!empty($_POST['checkbox_group']) && is_array($_POST['checkbox_group'])) {
		foreach ($_POST['checkbox_group'] as $group_name => $options) {
			$safe_name = sanitize_text_field($group_name);
			if (is_array($options)) {
				$safe_options = array_map('sanitize_text_field', $options);
				$checkbox_groups[$safe_name] = $safe_options;
			}
		}
	}

	// Validation
	if (empty($nachname) || empty($vorname) || empty($adresse) || empty($plz_ort) || empty($telefon) || empty($email)) {
		wp_send_json_error(['message' => 'Bitte alle Pflichtfelder ausfüllen.']);
	}

	if (!is_email($email)) {
		wp_send_json_error(['message' => 'Bitte eine gültige E-Mail-Adresse eingeben.']);
	}

	if (!$agb) {
		wp_send_json_error(['message' => 'Bitte die Datenschutzerklärung und AGB akzeptieren.']);
	}

	// Captcha validation
	$captcha_answer = absint($_POST['captcha'] ?? 0);
	$captcha_hash   = sanitize_text_field($_POST['captcha_hash'] ?? '');
	if (!$captcha_answer || wp_hash($captcha_answer . 'po_captcha_salt') !== $captcha_hash) {
		wp_send_json_error(['message' => 'Die Rechenaufgabe wurde falsch gelöst. Bitte versuche es erneut.']);
	}

	// Form type labels
	$type_labels = [
		'workshop'  => 'Impulsworkshop',
		'schulen'   => 'Parkour für Schulen',
		'teamevent' => 'Teamevent',
	];
	$type_label = $type_labels[$form_type] ?? $form_type;

	// Recipient
	$recipient_email = sanitize_email($_POST['recipient_email'] ?? '');
	$to_email = $recipient_email ?: get_option('admin_email');

	// Build admin email
	$site_name = get_bloginfo('name');
	$subject = 'Neue Anfrage: ' . $type_label . ' – ' . $site_name;

	$message = "Neue Anfrage über das Kontaktformular\n\n";
	$message .= "Typ: " . $type_label . "\n";
	foreach ($checkbox_groups as $group_name => $options) {
		$message .= $group_name . ": " . implode(', ', $options) . "\n";
	}
	$message .= "Name: " . $nachname . " " . $vorname . "\n";
	$message .= "Adresse: " . $adresse . "\n";
	$message .= "PLZ/Ort: " . $plz_ort . "\n";
	$message .= "Telefon: " . $telefon . "\n";
	$message .= "E-Mail: " . $email . "\n";
	if ($ort) $message .= "Gewünschter Ort: " . $ort . "\n";
	if ($teilnehmer) $message .= "Anzahl Personen: " . $teilnehmer . "\n";
	if ($datum) $message .= "Gewünschte Daten: " . $datum . "\n";
	if ($projektlaenge) $message .= "Projektlänge: " . $projektlaenge . "\n";
	if ($klassen) $message .= "Anzahl Klassen: " . $klassen . "\n";
	$message .= "\nNachricht:\n" . $nachricht . "\n";
	$message .= "\n---\nGesendet von: " . home_url();

	$headers = [
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $vorname . ' ' . $nachname . ' <' . $email . '>'
	];

	$sent_admin = wp_mail($to_email, $subject, $message, $headers);

	// Confirmation email to sender
	$confirm_subject = 'Deine Anfrage bei ' . $site_name;
	$confirm_message = "Hallo " . $vorname . ",\n\n";
	$confirm_message .= "vielen Dank für deine Anfrage zu \"" . $type_label . "\".\n\n";
	$confirm_message .= "Wir haben deine Nachricht erhalten und werden uns schnellstmöglich bei dir melden.\n\n";
	$confirm_message .= "Deine Angaben:\n";
	foreach ($checkbox_groups as $group_name => $options) {
		$confirm_message .= $group_name . ": " . implode(', ', $options) . "\n";
	}
	$confirm_message .= "Name: " . $nachname . " " . $vorname . "\n";
	if ($ort) $confirm_message .= "Gewünschter Ort: " . $ort . "\n";
	if ($teilnehmer) $confirm_message .= "Anzahl Personen: " . $teilnehmer . "\n";
	if ($datum) $confirm_message .= "Gewünschte Daten: " . $datum . "\n";
	if ($projektlaenge) $confirm_message .= "Projektlänge: " . $projektlaenge . "\n";
	if ($klassen) $confirm_message .= "Anzahl Klassen: " . $klassen . "\n";
	if ($nachricht) $confirm_message .= "Nachricht: " . $nachricht . "\n";
	$confirm_message .= "\nLiebe Grüsse\nDein " . $site_name . " Team";

	$confirm_headers = ['Content-Type: text/plain; charset=UTF-8'];
	wp_mail($email, $confirm_subject, $confirm_message, $confirm_headers);

	if ($sent_admin) {
		wp_send_json_success(['message' => 'Vielen Dank! Deine Anfrage wurde gesendet. Du erhältst eine Bestätigung per E-Mail.']);
	} else {
		wp_send_json_error(['message' => 'Es gab ein Problem beim Senden. Bitte versuche es später erneut.']);
	}
}
add_action('wp_ajax_po_inquiry_submit', 'parkourone_inquiry_submit');
add_action('wp_ajax_nopriv_po_inquiry_submit', 'parkourone_inquiry_submit');

/**
 * ============================================
 * MEMBER FORM AJAX HANDLER
 * Verletzungs-Rückerstattung + AHV-Nummer
 * ============================================
 */
function parkourone_member_form_submit() {
	// Nonce check
	if (!wp_verify_nonce($_POST['_nonce'] ?? '', 'po_member_form_nonce')) {
		wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen. Bitte Seite neu laden.']);
	}

	// Honeypot
	if (!empty($_POST['po_website'])) {
		wp_send_json_success(['message' => 'Vielen Dank! Dein Formular wurde gesendet.']);
	}

	// Rate Limiting (60s per IP)
	$ip = $_SERVER['REMOTE_ADDR'];
	$transient_key = 'po_member_' . md5($ip);
	if (get_transient($transient_key)) {
		wp_send_json_error(['message' => 'Bitte warte einen Moment bevor du ein weiteres Formular sendest.']);
	}
	set_transient($transient_key, 1, 60);

	// Captcha validation
	$captcha_answer = absint($_POST['captcha'] ?? 0);
	$captcha_hash   = sanitize_text_field($_POST['captcha_hash'] ?? '');
	if (!$captcha_answer || wp_hash($captcha_answer . 'po_member_captcha_salt') !== $captcha_hash) {
		wp_send_json_error(['message' => 'Die Rechenaufgabe wurde falsch gelöst. Bitte versuche es erneut.']);
	}

	$form_type = sanitize_text_field($_POST['form_type'] ?? '');

	if ($form_type === 'verletzungen') {
		parkourone_handle_verletzungen_form();
	} elseif ($form_type === 'ahv') {
		parkourone_handle_ahv_form();
	} else {
		wp_send_json_error(['message' => 'Ungültiger Formular-Typ.']);
	}
}

function parkourone_handle_verletzungen_form() {
	// Sanitize
	$name          = sanitize_text_field($_POST['name'] ?? '');
	$vorname       = sanitize_text_field($_POST['vorname'] ?? '');
	$plz           = sanitize_text_field($_POST['plz'] ?? '');
	$ort           = sanitize_text_field($_POST['ort'] ?? '');
	$email         = sanitize_email($_POST['email'] ?? '');
	$klasse        = sanitize_text_field($_POST['klasse'] ?? '');
	$beginn        = sanitize_text_field($_POST['beginn'] ?? '');
	$ende          = sanitize_text_field($_POST['ende'] ?? '');
	$iban          = sanitize_text_field($_POST['iban'] ?? '');
	$kontoinhaber  = sanitize_text_field($_POST['kontoinhaber'] ?? '');
	$agb           = isset($_POST['agb']) && $_POST['agb'] === '1';
	$versicherung  = isset($_POST['versicherung']) && $_POST['versicherung'] === '1';

	// Validation
	if (empty($name) || empty($vorname) || empty($plz) || empty($ort) || empty($email) || empty($klasse) || empty($beginn) || empty($ende) || empty($iban) || empty($kontoinhaber)) {
		wp_send_json_error(['message' => 'Bitte alle Pflichtfelder ausfüllen.']);
	}

	if (!is_email($email)) {
		wp_send_json_error(['message' => 'Bitte eine gültige E-Mail-Adresse eingeben.']);
	}

	if (!$agb) {
		wp_send_json_error(['message' => 'Bitte die Datenschutzerklärung und AGB akzeptieren.']);
	}

	if (!$versicherung) {
		wp_send_json_error(['message' => 'Bitte bestätige, dass deine Versicherung keine Rückerstattung leistet.']);
	}

	// Date validation
	$beginn_ts = strtotime($beginn);
	$ende_ts   = strtotime($ende);
	if (!$beginn_ts || !$ende_ts) {
		wp_send_json_error(['message' => 'Bitte gültige Daten eingeben.']);
	}

	if ($ende_ts <= $beginn_ts) {
		wp_send_json_error(['message' => 'Das Ende des Trainingsausfalls muss nach dem Beginn liegen.']);
	}

	$diff_days = ($ende_ts - $beginn_ts) / (60 * 60 * 24);
	if ($diff_days < 30) {
		wp_send_json_error(['message' => 'Der Trainingsausfall muss mindestens 30 Tage betragen.']);
	}

	// File upload
	$attachment_path = '';
	if (!empty($_FILES['sportdispens']) && $_FILES['sportdispens']['error'] === UPLOAD_ERR_OK) {
		$file = $_FILES['sportdispens'];

		// MIME check with finfo
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
		if (!in_array($mime, $allowed_mimes, true)) {
			wp_send_json_error(['message' => 'Nur PDF, JPG, PNG oder GIF Dateien sind erlaubt.']);
		}

		// Size check (64MB)
		if ($file['size'] > 64 * 1024 * 1024) {
			wp_send_json_error(['message' => 'Die Datei ist zu gross (max. 64 MB).']);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_handle_upload($file, ['test_form' => false]);

		if (isset($upload['error'])) {
			wp_send_json_error(['message' => 'Fehler beim Hochladen: ' . $upload['error']]);
		}

		$attachment_path = $upload['file'];
	}

	// Recipient
	$recipient_email = sanitize_email($_POST['recipient_email'] ?? '');
	$to_email = $recipient_email ?: get_option('admin_email');
	$site_name = get_bloginfo('name');

	// Admin email
	$subject = 'Rückerstattung Verletzung: ' . $vorname . ' ' . $name . ' – ' . $site_name;

	$message = "Neuer Antrag auf Rückerstattung bei Verletzung\n\n";
	$message .= "Name: " . $name . "\n";
	$message .= "Vorname: " . $vorname . "\n";
	$message .= "PLZ: " . $plz . "\n";
	$message .= "Ort: " . $ort . "\n";
	$message .= "E-Mail: " . $email . "\n";
	$message .= "Klasse: " . $klasse . "\n";
	$message .= "Beginn Trainingsausfall: " . $beginn . "\n";
	$message .= "Ende Trainingsausfall: " . $ende . "\n";
	$message .= "Dauer: " . round($diff_days) . " Tage\n";
	$message .= "IBAN: " . $iban . "\n";
	$message .= "Kontoinhaber: " . $kontoinhaber . "\n";
	$message .= "AGB akzeptiert: Ja\n";
	$message .= "Versicherung leistet keine Rückerstattung: Ja\n";
	if ($attachment_path) {
		$message .= "Sportdispens: Im Anhang\n";
	}
	$message .= "\n---\nGesendet von: " . home_url();

	$headers = [
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $vorname . ' ' . $name . ' <' . $email . '>'
	];

	$attachments = $attachment_path ? [$attachment_path] : [];
	$sent_admin = wp_mail($to_email, $subject, $message, $headers, $attachments);

	// Confirmation email to sender
	$confirm_subject = 'Dein Rückerstattungs-Antrag bei ' . $site_name;
	$confirm_message = "Hallo " . $vorname . ",\n\n";
	$confirm_message .= "vielen Dank für deinen Antrag auf Rückerstattung bei Verletzung.\n\n";
	$confirm_message .= "Wir haben deine Angaben erhalten und prüfen deinen Antrag.\n";
	$confirm_message .= "Du wirst von uns hören, sobald wir deinen Antrag bearbeitet haben.\n\n";
	$confirm_message .= "Deine Angaben:\n";
	$confirm_message .= "Klasse: " . $klasse . "\n";
	$confirm_message .= "Trainingsausfall: " . $beginn . " bis " . $ende . "\n";
	$confirm_message .= "IBAN: " . $iban . "\n";
	$confirm_message .= "\nLiebe Grüsse\nDein " . $site_name . " Team";

	$confirm_headers = ['Content-Type: text/plain; charset=UTF-8'];
	wp_mail($email, $confirm_subject, $confirm_message, $confirm_headers);

	if ($sent_admin) {
		wp_send_json_success(['message' => 'Vielen Dank! Dein Antrag wurde gesendet. Du erhältst eine Bestätigung per E-Mail.']);
	} else {
		wp_send_json_error(['message' => 'Es gab ein Problem beim Senden. Bitte versuche es später erneut.']);
	}
}

function parkourone_handle_ahv_form() {
	$name      = sanitize_text_field($_POST['name'] ?? '');
	$vorname   = sanitize_text_field($_POST['vorname'] ?? '');
	$ahv_raw   = sanitize_text_field($_POST['ahv_nummer'] ?? '');

	// Validation
	if (empty($name) || empty($vorname) || empty($ahv_raw)) {
		wp_send_json_error(['message' => 'Bitte alle Pflichtfelder ausfüllen.']);
	}

	// AHV format: 13 digits starting with 756
	$ahv_clean = preg_replace('/[\.\s\-]/', '', $ahv_raw);
	if (!preg_match('/^756\d{10}$/', $ahv_clean)) {
		wp_send_json_error(['message' => 'Bitte eine gültige AHV-Nummer eingeben (13 Ziffern, beginnt mit 756).']);
	}

	// Recipient
	$recipient_email = sanitize_email($_POST['recipient_email'] ?? '');
	$to_email = $recipient_email ?: get_option('admin_email');
	$site_name = get_bloginfo('name');

	// Admin email
	$subject = 'AHV-Nummer: ' . $vorname . ' ' . $name . ' – ' . $site_name;

	$message = "Neue AHV-Nummer Meldung (J+S Programm)\n\n";
	$message .= "Name: " . $name . "\n";
	$message .= "Vorname: " . $vorname . "\n";
	$message .= "AHV-Nummer: " . $ahv_raw . "\n";
	$message .= "\n---\nGesendet von: " . home_url();

	$headers = ['Content-Type: text/plain; charset=UTF-8'];
	$sent = wp_mail($to_email, $subject, $message, $headers);

	if ($sent) {
		wp_send_json_success(['message' => 'Vielen Dank! Deine AHV-Nummer wurde übermittelt.']);
	} else {
		wp_send_json_error(['message' => 'Es gab ein Problem beim Senden. Bitte versuche es später erneut.']);
	}
}

add_action('wp_ajax_po_member_form_submit', 'parkourone_member_form_submit');
add_action('wp_ajax_nopriv_po_member_form_submit', 'parkourone_member_form_submit');

/**
 * Automatisch "Startseite" als Homepage setzen
 * Wird ausgeführt wenn eine Seite mit dem Slug "startseite" veröffentlicht wird
 */
function parkourone_auto_set_homepage($post_id, $post, $update) {
	// Nur für Seiten
	if ($post->post_type !== 'page') {
		return;
	}

	// Nur für veröffentlichte Seiten
	if ($post->post_status !== 'publish') {
		return;
	}

	// Prüfen ob Slug "startseite" ist
	if ($post->post_name !== 'startseite') {
		return;
	}

	// Prüfen ob bereits eine andere Homepage gesetzt ist
	$current_homepage = get_option('page_on_front');
	if ($current_homepage && $current_homepage != $post_id) {
		// Nur setzen wenn keine Homepage existiert oder es dieselbe Seite ist
		$current_page = get_post($current_homepage);
		if ($current_page && $current_page->post_status === 'publish') {
			return; // Bestehende Homepage nicht überschreiben
		}
	}

	// Als statische Homepage setzen
	update_option('show_on_front', 'page');
	update_option('page_on_front', $post_id);
}
add_action('save_post', 'parkourone_auto_set_homepage', 10, 3);

/**
 * Beim Theme-Aktivierung prüfen ob Startseite existiert und setzen
 */
function parkourone_set_homepage_on_activation() {
	$startseite = get_page_by_path('startseite');

	if ($startseite && $startseite->post_status === 'publish') {
		$current_homepage = get_option('page_on_front');

		// Nur setzen wenn keine Homepage existiert
		if (!$current_homepage || get_post_status($current_homepage) !== 'publish') {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $startseite->ID);
		}
	}
}
add_action('after_switch_theme', 'parkourone_set_homepage_on_activation');

/**
 * Einmaliger Check beim ersten Laden - setzt Startseite als Homepage falls keine gesetzt
 */
function parkourone_check_homepage_on_init() {
	// Nur einmal pro Stunde prüfen (via Transient)
	if (get_transient('parkourone_homepage_checked')) {
		return;
	}
	set_transient('parkourone_homepage_checked', true, HOUR_IN_SECONDS);

	// Prüfen ob bereits eine gültige Homepage gesetzt ist
	$current_homepage = get_option('page_on_front');
	if ($current_homepage && get_post_status($current_homepage) === 'publish') {
		return; // Homepage existiert bereits
	}

	// Startseite suchen und setzen
	$startseite = get_page_by_path('startseite');
	if ($startseite && $startseite->post_status === 'publish') {
		update_option('show_on_front', 'page');
		update_option('page_on_front', $startseite->ID);
	}
}
add_action('init', 'parkourone_check_homepage_on_init');

/**
 * Erstellt rechtlich vorgeschriebene Seiten automatisch bei Theme-Aktivierung
 * (Datenschutz und Impressum)
 */
function parkourone_create_legal_pages() {
	$legal_pages = [
		'datenschutz' => [
			'title'   => 'Datenschutz',
			'pattern' => 'parkourone/page-datenschutz',
		],
		'impressum' => [
			'title'   => 'Impressum',
			'pattern' => 'parkourone/page-impressum',
		],
	];

	foreach ($legal_pages as $slug => $page_data) {
		// Prüfen ob Seite bereits existiert
		$existing = get_page_by_path($slug);
		if ($existing) {
			continue;
		}

		// Pattern-Inhalt laden
		$pattern_content = '';
		$pattern_file = get_template_directory() . '/patterns/page-' . $slug . '.php';

		if (file_exists($pattern_file)) {
			ob_start();
			include $pattern_file;
			$pattern_content = ob_get_clean();

			// PHP-Header entfernen (alles vor dem ersten HTML-Kommentar)
			$pattern_content = preg_replace('/^.*?(?=<!--)/s', '', $pattern_content);
		}

		// Seite erstellen
		$page_id = wp_insert_post([
			'post_title'   => $page_data['title'],
			'post_name'    => $slug,
			'post_content' => $pattern_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		]);

		if ($page_id && !is_wp_error($page_id)) {
			// Für Datenschutz: WordPress Privacy Policy Page setzen
			if ($slug === 'datenschutz') {
				update_option('wp_page_for_privacy_policy', $page_id);
			}
		}
	}
}
add_action('after_switch_theme', 'parkourone_create_legal_pages');

/**
 * Admin-Button zum manuellen Erstellen der Legal Pages
 */
function parkourone_legal_pages_admin_notice() {
	if (!current_user_can('manage_options')) {
		return;
	}

	// Prüfen ob beide Seiten existieren
	$datenschutz = get_page_by_path('datenschutz');
	$impressum = get_page_by_path('impressum');

	if ($datenschutz && $impressum) {
		return; // Beide Seiten existieren
	}

	$missing = [];
	if (!$datenschutz) $missing[] = 'Datenschutz';
	if (!$impressum) $missing[] = 'Impressum';

	// Button-Handler
	if (isset($_POST['parkourone_create_legal_pages']) && wp_verify_nonce($_POST['_wpnonce'], 'parkourone_create_legal_pages')) {
		parkourone_create_legal_pages();
		echo '<div class="notice notice-success"><p>Rechtliche Seiten wurden erstellt!</p></div>';
		return;
	}

	?>
	<div class="notice notice-warning">
		<p><strong>ParkourONE:</strong> Folgende rechtlich vorgeschriebene Seiten fehlen: <?php echo implode(', ', $missing); ?></p>
		<form method="post" style="margin-bottom: 10px;">
			<?php wp_nonce_field('parkourone_create_legal_pages'); ?>
			<button type="submit" name="parkourone_create_legal_pages" class="button button-primary">
				Seiten jetzt erstellen
			</button>
		</form>
	</div>
	<?php
}
add_action('admin_notices', 'parkourone_legal_pages_admin_notice');
