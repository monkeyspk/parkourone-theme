<?php
/**
 * ParkourONE Admin Menu
 * Zentrales Admin-Menü für alle Theme-Funktionen
 */

if (!defined('ABSPATH')) exit;

/**
 * Haupt-Menü und Untermenüs registrieren
 */
function parkourone_register_admin_menu() {
	// Hauptmenü mit Logo
	$logo_url = get_template_directory_uri() . '/assets/images/admin-logo.png';

	add_menu_page(
		'ParkourONE',
		'', // Kein Text, nur Logo
		'manage_options',
		'parkourone',
		'parkourone_admin_dashboard',
		$logo_url,
		3
	);

	// Dashboard (erstes Untermenü umbenennen)
	add_submenu_page(
		'parkourone',
		'Dashboard',
		'Dashboard',
		'manage_options',
		'parkourone',
		'parkourone_admin_dashboard'
	);

	// Menü & Footer
	add_submenu_page(
		'parkourone',
		'Menü & Footer',
		'Menü & Footer',
		'manage_options',
		'parkourone-menu-footer',
		'parkourone_menu_footer_page'
	);

	// Seiten Generator
	add_submenu_page(
		'parkourone',
		'Seiten Generator',
		'Seiten Generator',
		'manage_options',
		'parkourone-pages',
		'parkourone_auto_pages_admin_page'
	);

	// Kurs-Bilder
	add_submenu_page(
		'parkourone',
		'Kurs-Bilder',
		'Kurs-Bilder',
		'manage_options',
		'parkourone-images',
		'parkourone_event_images_admin_page'
	);

	// Fallback-Bilder
	add_submenu_page(
		'parkourone',
		'Fallback-Bilder',
		'Fallback-Bilder',
		'manage_options',
		'parkourone-fallback-images',
		'parkourone_fallback_images_page'
	);

	// Probetraining Steps
	add_submenu_page(
		'parkourone',
		'Probetraining Steps',
		'Probetraining Steps',
		'manage_options',
		'parkourone-steps',
		'parkourone_probetraining_steps_page'
	);

	// Promo Popup
	add_submenu_page(
		'parkourone',
		'Promo Popup',
		'Promo Popup',
		'manage_options',
		'parkourone-promo-popup',
		'parkourone_promo_popup_page'
	);

	// Redirects
	add_submenu_page(
		'parkourone',
		'Redirects',
		'Redirects',
		'manage_options',
		'parkourone-redirects',
		'parkourone_redirects_page'
	);

	// Maintenance Mode
	add_submenu_page(
		'parkourone',
		'Maintenance Mode',
		'Maintenance Mode',
		'manage_options',
		'parkourone-maintenance',
		'parkourone_maintenance_admin_page_html'
	);

	// Theme Updates
	add_submenu_page(
		'parkourone',
		'Theme Updates',
		'Theme Updates',
		'manage_options',
		'parkourone-updates',
		'parkourone_theme_updates_page'
	);
}
add_action('admin_menu', 'parkourone_register_admin_menu', 5);

/**
 * Admin Menu Logo Styling
 */
function parkourone_admin_menu_logo_styles() {
	?>
	<style>
		/* ParkourONE Admin Menu Logo - Full Width Banner */
		#adminmenu .toplevel_page_parkourone {
			margin-bottom: 5px;
		}
		#adminmenu .toplevel_page_parkourone > a.menu-top {
			height: auto !important;
			padding: 15px 12px !important;
			background: #1d1d1f !important;
		}
		#adminmenu .toplevel_page_parkourone > a.menu-top:hover {
			background: #2c2c2e !important;
		}
		#adminmenu .toplevel_page_parkourone .wp-menu-image {
			float: none !important;
			width: 100% !important;
			height: auto !important;
			margin: 0 !important;
			padding: 0 !important;
			text-align: center !important;
			display: block !important;
		}
		#adminmenu .toplevel_page_parkourone .wp-menu-image img {
			width: 100%;
			max-width: 160px;
			height: auto;
			padding: 0 !important;
			margin: 0 auto;
			display: block;
			filter: invert(1);
		}
		#adminmenu .toplevel_page_parkourone .wp-menu-name {
			display: none !important;
		}
		/* Collapsed Sidebar - kleines Icon */
		.folded #adminmenu .toplevel_page_parkourone > a.menu-top {
			padding: 10px 0 !important;
		}
		.folded #adminmenu .toplevel_page_parkourone .wp-menu-image img {
			width: 28px;
			max-width: 28px;
			padding: 0 4px !important;
		}
	</style>
	<?php
}
add_action('admin_head', 'parkourone_admin_menu_logo_styles');

/**
 * CPTs zum ParkourONE Menü hinzufügen
 */
function parkourone_add_cpts_to_menu() {
	global $menu, $submenu;

	// CPT Slugs die wir verschieben wollen
	$cpts = ['coach', 'faq', 'testimonial', 'job', 'angebot'];

	foreach ($cpts as $cpt) {
		$cpt_obj = get_post_type_object($cpt);
		if (!$cpt_obj) continue;

		// Submenu zum ParkourONE Menü hinzufügen
		add_submenu_page(
			'parkourone',
			$cpt_obj->labels->name,
			$cpt_obj->labels->menu_name,
			'edit_posts',
			'edit.php?post_type=' . $cpt
		);
	}
}
add_action('admin_menu', 'parkourone_add_cpts_to_menu', 20);

/**
 * Original CPT Menüs ausblenden
 */
function parkourone_hide_original_cpt_menus() {
	$cpts = ['coach', 'faq', 'testimonial', 'job', 'angebot'];

	foreach ($cpts as $cpt) {
		remove_menu_page('edit.php?post_type=' . $cpt);
	}
}
add_action('admin_menu', 'parkourone_hide_original_cpt_menus', 999);

/**
 * Dashboard Seite
 */
function parkourone_admin_dashboard() {
	$counts = [
		'coach' => wp_count_posts('coach')->publish ?? 0,
		'faq' => wp_count_posts('faq')->publish ?? 0,
		'testimonial' => wp_count_posts('testimonial')->publish ?? 0,
		'job' => wp_count_posts('job')->publish ?? 0,
		'angebot' => wp_count_posts('angebot')->publish ?? 0,
		'event' => wp_count_posts('event')->publish ?? 0,
	];
	?>
	<div class="wrap">
		<h1>ParkourONE Dashboard</h1>
		<div class="po-admin-dashboard">
			<style>
				.po-admin-dashboard { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
				.po-admin-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; text-align: center; }
				.po-admin-card h3 { margin: 0 0 10px; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px; }
				.po-admin-card .count { font-size: 48px; font-weight: 600; color: #1d2327; line-height: 1; }
				.po-admin-card a { display: inline-block; margin-top: 15px; text-decoration: none; }
			</style>

			<div class="po-admin-card">
				<h3>Coaches</h3>
				<div class="count"><?php echo esc_html($counts['coach']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=coach'); ?>" class="button">Verwalten</a>
			</div>

			<div class="po-admin-card">
				<h3>FAQs</h3>
				<div class="count"><?php echo esc_html($counts['faq']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=faq'); ?>" class="button">Verwalten</a>
			</div>

			<div class="po-admin-card">
				<h3>Testimonials</h3>
				<div class="count"><?php echo esc_html($counts['testimonial']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=testimonial'); ?>" class="button">Verwalten</a>
			</div>

			<div class="po-admin-card">
				<h3>Jobs</h3>
				<div class="count"><?php echo esc_html($counts['job']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=job'); ?>" class="button">Verwalten</a>
			</div>

			<div class="po-admin-card">
				<h3>Angebote</h3>
				<div class="count"><?php echo esc_html($counts['angebot']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=angebot'); ?>" class="button">Verwalten</a>
			</div>

			<div class="po-admin-card">
				<h3>Events/Kurse</h3>
				<div class="count"><?php echo esc_html($counts['event']); ?></div>
				<a href="<?php echo admin_url('edit.php?post_type=event'); ?>" class="button">Verwalten</a>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Schul-Vorlagen für Footer
 */
function parkourone_get_school_presets() {
	return [
		'' => ['label' => '— Vorlage wählen —'],
		'schweiz' => [
			'label' => 'Schweiz (Münsingen)',
			'company_name' => 'ParkourONE Schweiz',
			'company_address' => "ParkourONE GmbH\nSüdstrasse 16, SPOT 101\n3110 Münsingen",
			'phone' => '+41 31 371 74 90',
			'phone_hours' => "Mo, Di & Do\n09:00 – 12:00 Uhr\n13:30 – 16:00 Uhr",
			'email' => 'schweiz@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_ch',
			'zentrale_name' => '',
			'zentrale_url' => '',
		],
		'berlin' => [
			'label' => 'Berlin',
			'company_name' => 'ParkourONE Berlin',
			'company_address' => "Scheffler & Gessinger GbR\nDietzinger Straße 25\n13156 Berlin",
			'phone' => '+49 30 48 49 42 40',
			'phone_hours' => "Montag: 09:00 – 10:00 Uhr, 13:00 – 15:30 Uhr\nDi. – Do.: 09:00 – 12:00 Uhr, 13:00 – 16:30 Uhr",
			'email' => 'berlin@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_berlin',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
		'hannover' => [
			'label' => 'Hannover',
			'company_name' => 'ParkourONE Hannover',
			'company_address' => "Stamm & Kaiser GbR\nBernhard-Caspar-Straße 20\n30453 Hannover",
			'phone' => '+49 176 54 22 10 70',
			'phone_hours' => '',
			'email' => 'hannover@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_hannover',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
		'muenster' => [
			'label' => 'Münster',
			'company_name' => 'ParkourONE Münster',
			'company_address' => "Fabian Schubert\nParkourONE Münster",
			'phone' => '+49 176 84 84 33 00',
			'phone_hours' => '',
			'email' => 'muenster@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_muenster',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
		'dresden' => [
			'label' => 'Dresden',
			'company_name' => 'ParkourONE Dresden',
			'company_address' => "Dr. Jonas Jung\nRennplatzstraße 47\n01237 Dresden",
			'phone' => '+49 151 412 40 155',
			'phone_hours' => '',
			'email' => 'dresden@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_dresden',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
		'rheinruhr' => [
			'label' => 'Rhein/Ruhr (Krefeld)',
			'company_name' => 'ParkourONE Rhein/Ruhr',
			'company_address' => "Deniz Bozkurtan\nAugustastrasse 22\n47829 Krefeld",
			'phone' => '+49 157 525 18 774',
			'phone_hours' => "Mo – Fr: 10:00 Uhr – 15:30 Uhr",
			'email' => 'deniz@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_rheinruhr',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
		'augsburg' => [
			'label' => 'Augsburg',
			'company_name' => 'ParkourONE Augsburg',
			'company_address' => "Michael Thümmler\nAmmerseestraße 24\n86163 Augsburg",
			'phone' => '+49 17 05 85 60 09',
			'phone_hours' => '',
			'email' => 'augsburg@parkourone.com',
			'social_instagram' => 'https://instagram.com/parkourone_augsburg',
			'zentrale_name' => 'ParkourONE Schweiz',
			'zentrale_url' => 'https://parkourone.com',
		],
	];
}

/**
 * Menü & Footer Seite
 */
function parkourone_menu_footer_page() {
	$presets = parkourone_get_school_presets();

	// Menü-Links speichern
	if (isset($_POST['parkourone_menu_save']) && check_admin_referer('parkourone_menu_nonce')) {
		$menu_links = [];
		if (!empty($_POST['menu_link_name']) && is_array($_POST['menu_link_name'])) {
			foreach ($_POST['menu_link_name'] as $i => $name) {
				if (!empty($name)) {
					$menu_links[] = [
						'name' => sanitize_text_field($name),
						'url' => sanitize_text_field($_POST['menu_link_url'][$i] ?? '')
					];
				}
			}
		}
		update_option('parkourone_menu_links', $menu_links);
		echo '<div class="notice notice-success"><p>Menü-Links gespeichert!</p></div>';
	}

	// Footer speichern
	if (isset($_POST['parkourone_footer_save']) && check_admin_referer('parkourone_footer_nonce')) {
		$footer_options = [
			'company_name' => sanitize_text_field($_POST['footer_company_name'] ?? ''),
			'company_address' => sanitize_textarea_field($_POST['footer_company_address'] ?? ''),
			'phone' => sanitize_text_field($_POST['footer_phone'] ?? ''),
			'phone_hours' => sanitize_textarea_field($_POST['footer_phone_hours'] ?? ''),
			'email' => sanitize_email($_POST['footer_email'] ?? ''),
			'contact_form_url' => esc_url_raw($_POST['footer_contact_form_url'] ?? ''),
			'social_instagram' => esc_url_raw($_POST['footer_social_instagram'] ?? ''),
			'social_youtube' => esc_url_raw($_POST['footer_social_youtube'] ?? ''),
			'social_podcast' => esc_url_raw($_POST['footer_social_podcast'] ?? ''),
			'newsletter_headline' => sanitize_text_field($_POST['footer_newsletter_headline'] ?? ''),
			'newsletter_text' => sanitize_text_field($_POST['footer_newsletter_text'] ?? ''),
			'newsletter_embed' => current_user_can('unfiltered_html')
				? wp_unslash($_POST['footer_newsletter_embed'] ?? '')
				: wp_kses_post(wp_unslash($_POST['footer_newsletter_embed'] ?? '')),
			'mailerlite_api_key' => sanitize_text_field($_POST['footer_mailerlite_api_key'] ?? ''),
			// Standorte + Copyright Jahr werden automatisch generiert
		];

		update_option('parkourone_footer', $footer_options);
		echo '<div class="notice notice-success"><p>Footer-Einstellungen gespeichert!</p></div>';
	}

	// Menü-Links laden
	$menu_links = get_option('parkourone_menu_links', []);
	if (empty($menu_links)) {
		// Standard-Links als Fallback
		$menu_links = [
			['name' => 'Über uns', 'url' => '/ueber-uns/'],
			['name' => 'Angebote', 'url' => '/angebote/'],
			['name' => 'Team', 'url' => '/team/'],
			['name' => 'Kontakt', 'url' => '/kontakt/'],
		];
	}

	// Aktuelle Werte laden
	$options = get_option('parkourone_footer', []);
	$defaults = [
		'company_name' => '',
		'company_address' => '',
		'phone' => '',
		'phone_hours' => '',
		'email' => '',
		'contact_form_url' => '',
		'social_instagram' => '',
		'social_youtube' => '',
		'social_podcast' => '',
		'newsletter_headline' => '',
		'newsletter_text' => '',
		'newsletter_embed' => '',
		'mailerlite_api_key' => '',
		// Automatisch: Standorte, Zentrale, Copyright Jahr (siehe footer/render.php)
	];
	$options = wp_parse_args($options, $defaults);
	?>
	<div class="wrap">
		<h1>Menü & Footer</h1>

		<style>
			.po-admin-tabs { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 20px; }
			.po-admin-tab { padding: 12px 20px; background: #f0f0f1; border: 1px solid #c3c4c7; border-bottom: none; margin-bottom: -1px; cursor: pointer; text-decoration: none; color: #1d2327; }
			.po-admin-tab.active { background: #fff; border-bottom-color: #fff; font-weight: 600; }
			.po-admin-tab:hover { background: #fff; }
			.po-admin-panel { display: none; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none; }
			.po-admin-panel.active { display: block; }
			.po-form-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
			.po-form-section:last-child { border-bottom: none; margin-bottom: 0; }
			.po-form-section h3 { margin: 0 0 15px; font-size: 14px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; }
			.po-form-row { display: grid; grid-template-columns: 200px 1fr; gap: 10px; margin-bottom: 15px; align-items: start; }
			.po-form-row label { font-weight: 500; padding-top: 8px; }
			.po-form-row input[type="text"],
			.po-form-row input[type="email"],
			.po-form-row input[type="url"],
			.po-form-row textarea { width: 100%; max-width: 500px; }
			.po-form-row textarea { min-height: 80px; }
			.po-standorte-list { display: flex; flex-direction: column; gap: 10px; }
			.po-standort-row { display: flex; gap: 10px; align-items: center; }
			.po-standort-row input { flex: 1; max-width: 240px; }
			.po-standort-row .button { flex-shrink: 0; }
			.po-menu-preview { background: #1d1d1f; color: #fff; padding: 20px; border-radius: 8px; }
			.po-menu-preview ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 20px; flex-wrap: wrap; }
			.po-menu-preview li a { color: #fff; text-decoration: none; }
			.po-footer-preview { background: #1d1d1f; color: #fff; padding: 30px; border-radius: 8px; margin-top: 20px; }
			.po-preset-section { background: #f0f6fc; margin: -20px -20px 20px; padding: 20px; border-bottom: 2px solid #2271b1; }
			.po-preset-select { min-width: 250px; }
			.po-menu-links-list { display: flex; flex-direction: column; gap: 10px; }
			.po-menu-link-row { display: flex; gap: 10px; align-items: center; }
			.po-menu-link-row input { flex: 1; max-width: 280px; }
			.po-menu-link-row .button { flex-shrink: 0; }
		</style>

		<div class="po-admin-tabs">
			<a href="#menu3" class="po-admin-tab active" data-tab="menu3">Menü (3. Spalte)</a>
			<a href="#footer" class="po-admin-tab" data-tab="footer">Footer-Einstellungen</a>
			<a href="#preview" class="po-admin-tab" data-tab="preview">Vorschau</a>
		</div>

		<!-- Menü 3. Spalte -->
		<div class="po-admin-panel active" id="panel-menu3">
			<h2>Menü - 3. Spalte</h2>
			<p>Das Hauptmenü hat 3 Spalten:</p>
			<ul style="margin-bottom: 20px;">
				<li><strong>Spalte 1:</strong> Stundenplan + Altersgruppen (automatisch aus Events)</li>
				<li><strong>Spalte 2:</strong> Standorte (automatisch aus Event-Kategorien)</li>
				<li><strong>Spalte 3:</strong> Diese Links hier (manuell)</li>
			</ul>

			<form method="post">
				<?php wp_nonce_field('parkourone_menu_nonce'); ?>

				<div class="po-form-section">
					<h3>Links für Spalte 3</h3>
					<p class="description" style="margin-bottom: 15px;">Füge hier Seiten wie "Über uns", "Angebote", "Team", "Kontakt" hinzu.</p>

					<div class="po-menu-links-list" id="menu-links-list">
						<?php if (!empty($menu_links)): ?>
							<?php foreach ($menu_links as $link): ?>
								<div class="po-menu-link-row">
									<input type="text" name="menu_link_name[]" value="<?php echo esc_attr($link['name']); ?>" placeholder="Link-Text (z.B. Über uns)">
									<input type="text" name="menu_link_url[]" value="<?php echo esc_attr($link['url']); ?>" placeholder="URL (z.B. /ueber-uns/)">
									<button type="button" class="button po-remove-menu-link">×</button>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<div class="po-menu-link-row">
								<input type="text" name="menu_link_name[]" placeholder="Link-Text (z.B. Über uns)">
								<input type="text" name="menu_link_url[]" placeholder="URL (z.B. /ueber-uns/)">
								<button type="button" class="button po-remove-menu-link">×</button>
							</div>
						<?php endif; ?>
					</div>
					<button type="button" class="button" id="add-menu-link" style="margin-top: 10px;">+ Link hinzufügen</button>
				</div>

				<p>
					<button type="submit" name="parkourone_menu_save" class="button button-primary button-large">Menü speichern</button>
				</p>
			</form>
		</div>

		<!-- Footer Einstellungen -->
		<div class="po-admin-panel" id="panel-footer">
			<form method="post">
				<?php wp_nonce_field('parkourone_footer_nonce'); ?>

				<div class="po-form-section po-preset-section">
					<h3>Schul-Vorlage</h3>
					<div class="po-form-row">
						<label>Vorlage laden</label>
						<div>
							<select id="school-preset" class="po-preset-select">
								<?php foreach ($presets as $key => $preset): ?>
									<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($preset['label']); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" id="load-preset" class="button" style="margin-left: 10px;">Vorlage laden</button>
							<p class="description" style="margin-top: 8px;">Wähle eine Schule aus und klicke "Vorlage laden" um die Felder automatisch auszufüllen.</p>
						</div>
					</div>
				</div>

				<div class="po-form-section">
					<h3>Unternehmen</h3>
					<div class="po-form-row">
						<label>Firmenname</label>
						<input type="text" name="footer_company_name" value="<?php echo esc_attr($options['company_name']); ?>" placeholder="ParkourONE Berlin">
					</div>
					<div class="po-form-row">
						<label>Adresse</label>
						<textarea name="footer_company_address" placeholder="Strasse Nr.&#10;PLZ Stadt"><?php echo esc_textarea($options['company_address']); ?></textarea>
					</div>
				</div>

				<div class="po-form-section">
					<h3>Kontakt</h3>
					<div class="po-form-row">
						<label>Telefon</label>
						<input type="text" name="footer_phone" value="<?php echo esc_attr($options['phone']); ?>" placeholder="+49 30 123456">
					</div>
					<div class="po-form-row">
						<label>Telefonzeiten</label>
						<textarea name="footer_phone_hours" placeholder="Mo-Fr: 9-17 Uhr"><?php echo esc_textarea($options['phone_hours']); ?></textarea>
					</div>
					<div class="po-form-row">
						<label>E-Mail</label>
						<input type="email" name="footer_email" value="<?php echo esc_attr($options['email']); ?>" placeholder="info@parkourone.com">
					</div>
					<div class="po-form-row">
						<label>Kontaktformular URL</label>
						<input type="url" name="footer_contact_form_url" value="<?php echo esc_attr($options['contact_form_url']); ?>" placeholder="/kontakt">
					</div>
				</div>

				<div class="po-form-section">
					<h3>Social Media</h3>
					<div class="po-form-row">
						<label>Instagram</label>
						<input type="url" name="footer_social_instagram" value="<?php echo esc_attr($options['social_instagram']); ?>" placeholder="https://instagram.com/...">
					</div>
					<div class="po-form-row">
						<label>YouTube</label>
						<input type="url" name="footer_social_youtube" value="<?php echo esc_attr($options['social_youtube']); ?>" placeholder="https://youtube.com/...">
					</div>
					<div class="po-form-row">
						<label>Podcast</label>
						<input type="url" name="footer_social_podcast" value="<?php echo esc_attr($options['social_podcast']); ?>" placeholder="https://...">
					</div>
				</div>

				<div class="po-form-section">
					<h3>Standorte</h3>
					<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">
						<strong>Automatisch generiert!</strong>
						<p style="margin: 10px 0 0;">Die Standorte werden automatisch angezeigt. Der aktuelle Standort (basierend auf der URL/Subdomain) wird dabei ausgeblendet.</p>
						<p style="margin: 10px 0 0; font-size: 13px; color: #646970;"><strong>Alle Standorte:</strong> Schweiz, Berlin, Hannover, Münster, Dresden, Rhein/Ruhr, Augsburg</p>
					</div>
				</div>

				<div class="po-form-section">
					<h3>Newsletter</h3>
					<div class="po-form-row">
						<label>Überschrift</label>
						<input type="text" name="footer_newsletter_headline" value="<?php echo esc_attr($options['newsletter_headline']); ?>" placeholder="Newsletter abonnieren">
					</div>
					<div class="po-form-row">
						<label>Text</label>
						<input type="text" name="footer_newsletter_text" value="<?php echo esc_attr($options['newsletter_text']); ?>" placeholder="Bleib auf dem Laufenden!">
					</div>
					<div class="po-form-row">
						<label>MailerLite Embed Code</label>
						<div>
							<textarea name="footer_newsletter_embed" rows="8" style="width: 100%; max-width: 600px; font-family: monospace; font-size: 12px;" placeholder="<!-- MailerLite Embed Code hier einfügen -->"><?php echo esc_textarea($options['newsletter_embed']); ?></textarea>
							<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1; margin-top: 12px;">
								<strong>Anleitung: MailerLite Formular einbinden</strong>
								<ol style="margin: 10px 0 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
									<li>Öffne <a href="https://dashboard.mailerlite.com/forms/embedded" target="_blank">MailerLite</a> und gehe zu <strong>Forms &rarr; Embedded forms</strong></li>
									<li>Klicke auf <strong>&quot;Create embedded form&quot;</strong></li>
									<li>Wähle einen Namen (z.B. &quot;Footer Newsletter&quot;) und eine Subscriber-Gruppe</li>
									<li>Aktiviere unter <strong>Settings</strong> die <strong>&quot;Confirmation checkbox&quot;</strong> (DSGVO-Pflicht)</li>
									<li>Passe den Checkbox-Text an, z.B.: <em>&quot;Ich möchte den ParkourONE Newsletter erhalten.&quot;</em></li>
									<li>Klicke auf <strong>&quot;Save &amp; publish&quot;</strong></li>
									<li>Wähle den Tab <strong>&quot;HTML code&quot;</strong></li>
									<li>Kopiere <strong>beide Code-Blöcke</strong> (JavaScript Snippet + das div darunter) und füge alles hier ein</li>
								</ol>
								<p style="margin: 10px 0 0; font-size: 12px; color: #646970;">
									Das Design wird automatisch an den Footer angepasst &ndash; in MailerLite muss nichts gestyled werden.
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="po-form-section">
					<h3>Automatisch</h3>
					<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">
						<p style="margin: 0;"><strong>Copyright Jahr:</strong> <?php echo date('Y'); ?> (automatisch aktuelles Jahr)</p>
						<p style="margin: 10px 0 0;"><strong>Zentrale:</strong> ParkourONE Academy → parkourone.com</p>
					</div>
				</div>

				<p>
					<button type="submit" name="parkourone_footer_save" class="button button-primary button-large">Speichern</button>
				</p>
			</form>
		</div>

		<!-- Vorschau -->
		<div class="po-admin-panel" id="panel-preview">
			<h2>Menü-Vorschau (3 Spalten)</h2>
			<p>So sieht das Menü mit den aktuellen Einstellungen aus:</p>
			<?php
			// Menü-Vorschau laden
			if (function_exists('parkourone_render_menu_preview_content')) {
				parkourone_render_menu_preview_content();
			} else {
				echo '<p>Menü-Vorschau nicht verfügbar.</p>';
			}
			?>

			<h2 style="margin-top: 40px;">Footer-Vorschau</h2>
			<p>So sieht der Footer mit den aktuellen Einstellungen aus:</p>
			<div class="po-footer-preview">
				<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
					<div>
						<strong><?php echo esc_html($options['company_name'] ?: 'Firmenname'); ?></strong>
						<p style="white-space: pre-line; margin-top: 10px; opacity: 0.8;"><?php echo esc_html($options['company_address'] ?: 'Adresse'); ?></p>
					</div>
					<div>
						<strong>Kontakt</strong>
						<?php if ($options['phone']): ?><p style="margin-top: 10px;"><?php echo esc_html($options['phone']); ?></p><?php endif; ?>
						<?php if ($options['email']): ?><p><?php echo esc_html($options['email']); ?></p><?php endif; ?>
					</div>
					<div>
						<strong>Standorte</strong>
						<p style="margin-top: 5px; font-size: 12px; color: #aaa;">(automatisch generiert)</p>
						<p style="margin-top: 5px;">Schweiz</p>
						<p style="margin-top: 5px;">Berlin</p>
						<p style="margin-top: 5px;">Hannover</p>
						<p style="margin-top: 5px;">...</p>
						<p style="margin-top: 5px; font-size: 11px; opacity: 0.7;">Aktueller Standort wird ausgeblendet</p>
					</div>
					<div>
						<strong><?php echo esc_html($options['newsletter_headline'] ?: 'Newsletter'); ?></strong>
						<p style="margin-top: 10px; opacity: 0.8;"><?php echo esc_html($options['newsletter_text']); ?></p>
					</div>
				</div>
				<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; justify-content: space-between; opacity: 0.7; font-size: 14px;">
					<span>ParkourONE</span>
					<span>Impressum · Datenschutz · Cookies</span>
					<span>© <?php echo esc_html($options['copyright_year'] ?: date('Y')); ?> ParkourONE</span>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Schul-Vorlagen
			var presets = <?php echo json_encode($presets, JSON_UNESCAPED_UNICODE); ?>;

			document.getElementById('load-preset').addEventListener('click', function() {
				var selected = document.getElementById('school-preset').value;
				if (!selected || !presets[selected]) {
					alert('Bitte wähle eine Vorlage aus.');
					return;
				}

				var preset = presets[selected];

				// Felder ausfüllen
				if (preset.company_name !== undefined) {
					document.querySelector('[name="footer_company_name"]').value = preset.company_name;
				}
				if (preset.company_address !== undefined) {
					document.querySelector('[name="footer_company_address"]').value = preset.company_address;
				}
				if (preset.phone !== undefined) {
					document.querySelector('[name="footer_phone"]').value = preset.phone;
				}
				if (preset.phone_hours !== undefined) {
					document.querySelector('[name="footer_phone_hours"]').value = preset.phone_hours;
				}
				if (preset.email !== undefined) {
					document.querySelector('[name="footer_email"]').value = preset.email;
				}
				if (preset.social_instagram !== undefined) {
					document.querySelector('[name="footer_social_instagram"]').value = preset.social_instagram;
				}
				if (preset.zentrale_name !== undefined) {
					document.querySelector('[name="footer_zentrale_name"]').value = preset.zentrale_name;
				}
				if (preset.zentrale_url !== undefined) {
					document.querySelector('[name="footer_zentrale_url"]').value = preset.zentrale_url;
				}

				alert('Vorlage "' + preset.label + '" geladen! Vergiss nicht zu speichern.');
			});

			// Tabs
			document.querySelectorAll('.po-admin-tab').forEach(function(tab) {
				tab.addEventListener('click', function(e) {
					e.preventDefault();
					document.querySelectorAll('.po-admin-tab').forEach(t => t.classList.remove('active'));
					document.querySelectorAll('.po-admin-panel').forEach(p => p.classList.remove('active'));
					this.classList.add('active');
					document.getElementById('panel-' + this.dataset.tab).classList.add('active');
				});
			});

			// Menü-Links hinzufügen
			document.getElementById('add-menu-link').addEventListener('click', function() {
				var row = document.createElement('div');
				row.className = 'po-menu-link-row';
				row.innerHTML = '<input type="text" name="menu_link_name[]" placeholder="Link-Text (z.B. Über uns)">' +
					'<input type="text" name="menu_link_url[]" placeholder="URL (z.B. /ueber-uns/)">' +
					'<button type="button" class="button po-remove-menu-link">×</button>';
				document.getElementById('menu-links-list').appendChild(row);
			});

			// Menü-Links entfernen
			document.getElementById('menu-links-list').addEventListener('click', function(e) {
				if (e.target.classList.contains('po-remove-menu-link')) {
					var rows = this.querySelectorAll('.po-menu-link-row');
					if (rows.length > 1) {
						e.target.closest('.po-menu-link-row').remove();
					}
				}
			});
		});
		</script>
	</div>
	<?php
}

/**
 * Probetraining Steps Seite
 */
function parkourone_probetraining_steps_page() {
	// Speichern
	if (isset($_POST['parkourone_steps_save']) && check_admin_referer('parkourone_steps_nonce')) {
		$steps = [];
		if (!empty($_POST['step_title']) && is_array($_POST['step_title'])) {
			foreach ($_POST['step_title'] as $i => $title) {
				if (!empty($title)) {
					$steps[] = [
						'title' => sanitize_text_field($title),
						'description' => sanitize_text_field($_POST['step_description'][$i] ?? ''),
						'icon' => sanitize_text_field($_POST['step_icon'][$i] ?? 'check'),
					];
				}
			}
		}
		update_option('parkourone_probetraining_steps', $steps);
		echo '<div class="notice notice-success"><p>Probetraining Steps gespeichert!</p></div>';
	}

	// Aktuelle Werte laden
	$steps = get_option('parkourone_probetraining_steps', []);
	if (empty($steps)) {
		$steps = [
			['title' => 'Standort wählen', 'description' => 'Wähle einen Standort aus, an dem du trainieren möchtest.', 'icon' => 'location'],
			['title' => 'Klasse wählen', 'description' => 'Wähle die passende Klasse basierend auf der Altersgruppe aus.', 'icon' => 'users'],
			['title' => 'Termin buchen', 'description' => 'Das Probetraining kostet 15 CHF und ist dafür gedacht, dass du die Gruppendynamik und das Training kennenlernst.', 'icon' => 'calendar'],
			['title' => 'Loslegen', 'description' => 'Nach dem Probetraining kannst du entscheiden, ob du Mitglied werden möchtest.', 'icon' => 'check'],
		];
	}

	$available_icons = ['location', 'users', 'calendar', 'check', 'star', 'heart'];
	?>
	<div class="wrap">
		<h1>Probetraining Steps</h1>
		<p>Diese Schritte werden auf allen Seiten angezeigt, die den Steps-Block <strong>ohne eigene Steps</strong> verwenden.<br>
		Wenn du auf einer einzelnen Seite individuelle Steps brauchst, kannst du sie dort im Block-Editor überschreiben.</p>

		<style>
			.po-steps-admin { max-width: 800px; margin-top: 20px; }
			.po-step-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin-bottom: 16px; position: relative; }
			.po-step-card__header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
			.po-step-card__number { width: 36px; height: 36px; background: #1d1d1f; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
			.po-step-card__fields { display: flex; flex-direction: column; gap: 12px; }
			.po-step-card__fields label { font-weight: 500; display: block; margin-bottom: 4px; }
			.po-step-card__fields input[type="text"],
			.po-step-card__fields textarea { width: 100%; }
			.po-step-card__fields textarea { min-height: 60px; resize: vertical; }
			.po-step-card__fields select { min-width: 150px; }
			.po-step-card__row { display: grid; grid-template-columns: 1fr 150px; gap: 16px; }
			.po-step-card__actions { position: absolute; top: 16px; right: 16px; display: flex; gap: 4px; }
		</style>

		<form method="post">
			<?php wp_nonce_field('parkourone_steps_nonce'); ?>

			<div class="po-steps-admin" id="steps-list">
				<?php foreach ($steps as $i => $step): ?>
				<div class="po-step-card" data-index="<?php echo $i; ?>">
					<div class="po-step-card__actions">
						<button type="button" class="button button-small po-step-up" <?php echo $i === 0 ? 'disabled' : ''; ?> title="Nach oben">&#x25B2;</button>
						<button type="button" class="button button-small po-step-down" <?php echo $i === count($steps) - 1 ? 'disabled' : ''; ?> title="Nach unten">&#x25BC;</button>
						<button type="button" class="button button-small po-step-remove" title="Entfernen" style="color: #a00;">&#x2715;</button>
					</div>
					<div class="po-step-card__header">
						<span class="po-step-card__number"><?php echo $i + 1; ?></span>
						<strong class="po-step-card__title-preview"><?php echo esc_html($step['title']); ?></strong>
					</div>
					<div class="po-step-card__fields">
						<div class="po-step-card__row">
							<div>
								<label>Titel</label>
								<input type="text" name="step_title[]" value="<?php echo esc_attr($step['title']); ?>">
							</div>
							<div>
								<label>Icon</label>
								<select name="step_icon[]">
									<?php foreach ($available_icons as $icon): ?>
									<option value="<?php echo esc_attr($icon); ?>" <?php selected($step['icon'] ?? '', $icon); ?>><?php echo esc_html(ucfirst($icon)); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div>
							<label>Beschreibung</label>
							<textarea name="step_description[]"><?php echo esc_textarea($step['description']); ?></textarea>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button" id="add-step" style="margin-bottom: 20px;">+ Schritt hinzufügen</button>

			<p>
				<button type="submit" name="parkourone_steps_save" class="button button-primary button-large">Steps speichern</button>
			</p>
		</form>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var list = document.getElementById('steps-list');
		var icons = <?php echo json_encode($available_icons); ?>;

		function renumber() {
			var cards = list.querySelectorAll('.po-step-card');
			cards.forEach(function(card, i) {
				card.querySelector('.po-step-card__number').textContent = i + 1;
				card.querySelector('.po-step-up').disabled = (i === 0);
				card.querySelector('.po-step-down').disabled = (i === cards.length - 1);
			});
		}

		document.getElementById('add-step').addEventListener('click', function() {
			var count = list.querySelectorAll('.po-step-card').length;
			var card = document.createElement('div');
			card.className = 'po-step-card';
			card.innerHTML = '<div class="po-step-card__actions">' +
				'<button type="button" class="button button-small po-step-up" title="Nach oben">&#x25B2;</button>' +
				'<button type="button" class="button button-small po-step-down" title="Nach unten" disabled>&#x25BC;</button>' +
				'<button type="button" class="button button-small po-step-remove" title="Entfernen" style="color: #a00;">&#x2715;</button>' +
				'</div>' +
				'<div class="po-step-card__header"><span class="po-step-card__number">' + (count + 1) + '</span><strong class="po-step-card__title-preview">Neuer Schritt</strong></div>' +
				'<div class="po-step-card__fields">' +
				'<div class="po-step-card__row"><div><label>Titel</label><input type="text" name="step_title[]" value=""></div>' +
				'<div><label>Icon</label><select name="step_icon[]">' + icons.map(function(ic) { return '<option value="' + ic + '">' + ic.charAt(0).toUpperCase() + ic.slice(1) + '</option>'; }).join('') + '</select></div></div>' +
				'<div><label>Beschreibung</label><textarea name="step_description[]"></textarea></div></div>';
			list.appendChild(card);
			renumber();
		});

		list.addEventListener('click', function(e) {
			var btn = e.target.closest('button');
			if (!btn) return;
			var card = btn.closest('.po-step-card');

			if (btn.classList.contains('po-step-remove')) {
				if (list.querySelectorAll('.po-step-card').length > 1) {
					card.remove();
					renumber();
				}
			} else if (btn.classList.contains('po-step-up')) {
				var prev = card.previousElementSibling;
				if (prev) { list.insertBefore(card, prev); renumber(); }
			} else if (btn.classList.contains('po-step-down')) {
				var next = card.nextElementSibling;
				if (next) { list.insertBefore(next, card); renumber(); }
			}
		});
	});
	</script>
	<?php
}

/**
 * Holt die globalen Probetraining Steps
 */
function parkourone_get_global_steps() {
	$steps = get_option('parkourone_probetraining_steps', []);
	if (!empty($steps)) {
		return $steps;
	}

	// Hardcoded Fallback falls noch nichts gespeichert
	return [
		['title' => 'Standort wählen', 'description' => 'Wähle einen Standort aus, an dem du trainieren möchtest.', 'icon' => 'location'],
		['title' => 'Klasse wählen', 'description' => 'Wähle die passende Klasse basierend auf der Altersgruppe aus.', 'icon' => 'users'],
		['title' => 'Termin buchen', 'description' => 'Das Probetraining kostet 15 CHF und ist dafür gedacht, dass du die Gruppendynamik und das Training kennenlernst.', 'icon' => 'calendar'],
		['title' => 'Loslegen', 'description' => 'Nach dem Probetraining kannst du entscheiden, ob du Mitglied werden möchtest.', 'icon' => 'check'],
	];
}

/**
 * Fallback-Bilder Seite
 */
function parkourone_fallback_images_page() {
	$categories = ['minis', 'kids', 'juniors', 'adults'];
	$orientations = ['portrait', 'landscape'];
	?>
	<div class="wrap">
		<h1>Fallback-Bilder</h1>
		<p>Diese Bilder werden automatisch verwendet, wenn ein Event/Kurs kein eigenes Bild hat.</p>

		<style>
			.po-fallback-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 15px; }
			.po-fallback-item { position: relative; aspect-ratio: 3/4; border-radius: 8px; overflow: hidden; background: #f0f0f1; }
			.po-fallback-item.landscape { aspect-ratio: 16/10; }
			.po-fallback-item img { width: 100%; height: 100%; object-fit: cover; }
			.po-fallback-item span { position: absolute; bottom: 0; left: 0; right: 0; padding: 5px; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; text-align: center; }
			.po-category-section { margin-bottom: 40px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 8px; }
			.po-category-section h2 { margin-top: 0; text-transform: capitalize; }
			.po-orientation-group { margin-bottom: 20px; }
			.po-orientation-group h4 { margin-bottom: 10px; color: #646970; }
		</style>

		<?php foreach ($categories as $category): ?>
		<div class="po-category-section">
			<h2><?php echo esc_html(ucfirst($category)); ?></h2>

			<?php foreach ($orientations as $orientation): ?>
			<div class="po-orientation-group">
				<h4><?php echo $orientation === 'portrait' ? 'Portrait (3:4)' : 'Landscape (16:10)'; ?></h4>
				<?php
				$dir = get_template_directory() . '/assets/images/fallback/' . $orientation . '/' . $category;
				$url_base = get_template_directory_uri() . '/assets/images/fallback/' . $orientation . '/' . $category;

				if (is_dir($dir)) {
					$images = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
					if (!empty($images)): ?>
					<div class="po-fallback-grid">
						<?php foreach ($images as $image):
							$filename = basename($image);
							?>
						<div class="po-fallback-item <?php echo esc_attr($orientation); ?>">
							<img src="<?php echo esc_url($url_base . '/' . $filename); ?>" alt="<?php echo esc_attr($filename); ?>">
							<span><?php echo esc_html($filename); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<p style="color: #666;">Keine Bilder vorhanden.</p>
					<?php endif;
				} else { ?>
					<p style="color: #666;">Ordner nicht vorhanden: <code><?php echo esc_html($orientation . '/' . $category); ?></code></p>
				<?php } ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>

		<div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-radius: 4px;">
			<strong>Bilder hinzufügen:</strong><br>
			Lade neue Bilder per FTP/SFTP in folgende Ordner hoch:<br>
			<code>/wp-content/themes/parkourone-theme/assets/images/fallback/[portrait|landscape]/[minis|kids|juniors|adults]/</code>
		</div>
	</div>
	<?php
}

/**
 * Theme Updates Seite (Wrapper für GitHub Updater)
 */
function parkourone_theme_updates_page() {
	// Verwende die existierende Render-Funktion vom GitHub Updater
	if (class_exists('ParkourONE_GitHub_Updater')) {
		$updater = new ParkourONE_GitHub_Updater();
		$updater->render_admin_page();
	} else {
		echo '<div class="wrap"><h1>Theme Updates</h1><p>GitHub Updater nicht verfügbar.</p></div>';
	}
}

/**
 * Highlight das aktuelle Menü korrekt für CPTs
 */
function parkourone_fix_admin_menu_highlight($parent_file) {
	global $current_screen;

	$cpts = ['coach', 'faq', 'testimonial', 'job', 'angebot'];

	if (in_array($current_screen->post_type, $cpts)) {
		return 'parkourone';
	}

	return $parent_file;
}
add_filter('parent_file', 'parkourone_fix_admin_menu_highlight');

/**
 * Submenu Highlighting für CPTs
 */
function parkourone_fix_submenu_highlight($submenu_file) {
	global $current_screen;

	$cpts = ['coach', 'faq', 'testimonial', 'job', 'angebot'];

	if (in_array($current_screen->post_type, $cpts)) {
		return 'edit.php?post_type=' . $current_screen->post_type;
	}

	return $submenu_file;
}
add_filter('submenu_file', 'parkourone_fix_submenu_highlight');
