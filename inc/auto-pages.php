<?php
/**
 * ParkourONE Auto-Pages System
 * Automatische Generierung von Stadt- und Kategorie-Seiten
 * SEO & AI Search optimiert
 */

defined('ABSPATH') || exit;

// =====================================================
// Ticket #6 & #7: Block-Persistenz bei Seiten-Updates
// Manuell hochgeladene Bilder und individuelle Blöcke erhalten
// =====================================================

/**
 * Prüft ob ein Block individuell angepasst wurde
 * Ticket #6: Bilder mit mediaId > 0 sind manuell hochgeladen
 * Ticket #7: Blöcke mit isCustom=true oder geändertem Content
 */
function parkourone_is_block_customized($block) {
	if (empty($block['blockName'])) {
		return false;
	}

	$attrs = $block['attrs'] ?? [];

	// Ticket #6: Prüfen ob manuell hochgeladenes Bild vorhanden
	if (!empty($attrs['mediaId']) && intval($attrs['mediaId']) > 0) {
		return true;
	}

	// Ticket #7: Explizit als custom markiert
	if (!empty($attrs['isCustom']) && $attrs['isCustom'] === true) {
		return true;
	}

	// Prüfen ob manuelle Bild-URLs gesetzt wurden (nicht Fallback/Random)
	$image_attrs = ['imageUrl', 'backgroundImageUrl', 'heroImage'];
	foreach ($image_attrs as $attr) {
		if (!empty($attrs[$attr]) && strpos($attrs[$attr], 'wp-content/uploads') !== false) {
			// Ist eine hochgeladene Datei, nicht ein Fallback
			return true;
		}
	}

	return false;
}

/**
 * Findet einen Block im Array anhand von Typ und ungefährer Position
 */
function parkourone_find_matching_block($block, $blocks_array, $position_hint = 0) {
	$block_name = $block['blockName'] ?? '';
	if (empty($block_name)) {
		return null;
	}

	// Erst versuchen, Block an gleicher Position zu finden
	if (isset($blocks_array[$position_hint]) && ($blocks_array[$position_hint]['blockName'] ?? '') === $block_name) {
		return $blocks_array[$position_hint];
	}

	// Sonst nach erstem Block gleichen Typs suchen
	foreach ($blocks_array as $existing_block) {
		if (($existing_block['blockName'] ?? '') === $block_name) {
			return $existing_block;
		}
	}

	return null;
}

/**
 * Merged bestehenden Seiten-Content mit neuem Template-Content
 * Erhält individuell angepasste Blöcke (Ticket #6 & #7)
 */
function parkourone_merge_page_content($existing_content, $new_template_content) {
	// Wenn keine bestehende Seite, einfach neuen Content zurückgeben
	if (empty($existing_content)) {
		return $new_template_content;
	}

	$existing_blocks = parse_blocks($existing_content);
	$new_blocks = parse_blocks($new_template_content);

	// Wenn keine Blöcke geparst werden können, neuen Content verwenden
	if (empty($existing_blocks) || empty($new_blocks)) {
		return $new_template_content;
	}

	$merged_blocks = [];
	$used_existing_indices = [];

	foreach ($new_blocks as $index => $new_block) {
		// Leere Blöcke (Whitespace) übernehmen
		if (empty($new_block['blockName'])) {
			$merged_blocks[] = $new_block;
			continue;
		}

		// Entsprechenden bestehenden Block finden
		$existing_block = parkourone_find_matching_block($new_block, $existing_blocks, $index);

		if ($existing_block && parkourone_is_block_customized($existing_block)) {
			// Ticket #6 & #7: Behalte den customized Block
			$merged_blocks[] = $existing_block;

			// Markiere als verwendet
			foreach ($existing_blocks as $idx => $eb) {
				if ($eb === $existing_block && !in_array($idx, $used_existing_indices)) {
					$used_existing_indices[] = $idx;
					break;
				}
			}
		} else {
			// Verwende den neuen Template-Block
			$merged_blocks[] = $new_block;
		}
	}

	// Zusätzliche customized Blöcke am Ende anhängen, die nicht im Template sind
	foreach ($existing_blocks as $idx => $existing_block) {
		if (!in_array($idx, $used_existing_indices) && parkourone_is_block_customized($existing_block)) {
			$merged_blocks[] = $existing_block;
		}
	}

	return serialize_blocks($merged_blocks);
}

/**
 * Registriert das isCustom Attribut für alle ParkourONE Blöcke
 */
function parkourone_register_custom_block_attributes() {
	// Alle registrierten Blöcke durchgehen
	$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

	foreach ($registered_blocks as $block_name => $block_type) {
		// Nur ParkourONE Blöcke
		if (strpos($block_name, 'parkourone/') === 0) {
			// isCustom Attribut hinzufügen wenn nicht vorhanden
			if (!isset($block_type->attributes['isCustom'])) {
				$block_type->attributes['isCustom'] = [
					'type' => 'boolean',
					'default' => false
				];
			}
		}
	}
}
add_action('init', 'parkourone_register_custom_block_attributes', 999);

// =====================================================
// Automatische Standort-Erkennung aus Subdomain
// =====================================================

function parkourone_get_site_location() {
	$host = parse_url(home_url(), PHP_URL_HOST);

	// Subdomain extrahieren (z.B. "berlin" aus "berlin.parkourone.com")
	$parts = explode('.', $host);

	// Mapping Subdomain → Anzeigename
	$location_names = [
		'berlin' => 'Berlin',
		'schweiz' => 'Schweiz',
		'augsburg' => 'Augsburg',
		'dresden' => 'Dresden',
		'duisburg' => 'Duisburg',
		'hannover' => 'Hannover',
		'düsseldorf' => 'Düsseldorf',
		'muenster' => 'Münster',
		'munster' => 'Münster',
		'zürich' => 'Zürich',
		'zurich' => 'Zürich',
		'bern' => 'Bern',
		'basel' => 'Basel',
		'localhost' => 'Berlin', // Fallback für lokale Entwicklung
		'new' => 'Berlin', // Fallback für new.parkourone.com
	];

	$subdomain = strtolower($parts[0]);

	// Prüfen ob Subdomain im Mapping existiert
	if (isset($location_names[$subdomain])) {
		return [
			'slug' => $subdomain,
			'name' => $location_names[$subdomain],
			'detected' => true
		];
	}

	// Fallback: Ersten Teil als Standort verwenden
	return [
		'slug' => $subdomain,
		'name' => ucfirst($subdomain),
		'detected' => false
	];
}

/**
 * Probetraining-Preis basierend auf Standort
 * Gibt formatierten Preis mit Währung zurück
 */
function parkourone_get_probetraining_price() {
	$site_location = parkourone_get_site_location();
	$slug = $site_location['slug'];

	// Schweizer Standorte: CHF 25
	$swiss_locations = ['schweiz', 'zürich', 'zurich', 'bern', 'basel'];
	if (in_array($slug, $swiss_locations)) {
		return 'CHF 25';
	}

	// Deutsche Standorte: € 15 (Standard)
	// Hier können bei Bedarf Ausnahmen hinzugefügt werden
	// z.B. 'muenchen' => '€ 20'
	return '€ 15';
}

// =====================================================
// SEO-optimierte Texte für Zielgruppen
// =====================================================

function parkourone_get_seo_content($type, $term_slug = '', $city = '') {
	$content = [
		// Zielgruppen-Texte
		'minis' => [
			'title' => 'Parkour für Minis (4-6 Jahre)',
			'hero_subtitle' => 'Spielerisch die Welt entdecken - erste Bewegungserfahrungen für die Kleinsten',
			'intro_headline' => 'Parkour für die Kleinsten: Bewegung, die Spass macht',
			'intro_text' => 'In unseren Mini-Klassen entdecken Kinder zwischen 4 und 6 Jahren spielerisch die Grundlagen der Bewegung. Durch altersgerechte Übungen entwickeln sie Körperbewusstsein, Koordination und Selbstvertrauen - ohne Leistungsdruck, mit viel Freude.',
			'benefits' => [
				'Spielerische Bewegungsförderung ohne Wettkampf',
				'Entwicklung von Koordination und Gleichgewicht',
				'Stärkung des Selbstvertrauens',
				'Kleine Gruppen mit maximal 10 Kindern',
				'Ausgebildete Coaches mit Erfahrung im Kindertraining'
			],
			'meta_description' => 'Parkour für Kinder ab 4 Jahren. Spielerisches Bewegungstraining für Minis in kleinen Gruppen. Jetzt Probetraining buchen!'
		],
		'kids' => [
			'title' => 'Parkour für Kids (6-12 Jahre)',
			'hero_subtitle' => 'Hindernisse überwinden, Grenzen erweitern - Parkour für Kinder',
			'intro_headline' => 'Parkour Kids: Wo Bewegung zum Abenteuer wird',
			'intro_text' => 'Unsere Kids-Klassen sind der perfekte Einstieg in die Welt des Parkour. Kinder zwischen 6 und 12 Jahren lernen grundlegende Techniken wie Rollen, Springen und Klettern - immer mit Fokus auf Sicherheit und individuellem Fortschritt.',
			'benefits' => [
				'Altersgerechte Parkour-Techniken lernen',
				'Körperliche und mentale Stärke entwickeln',
				'Respektvoller Umgang in der Gruppe',
				'Outdoor-Training in der Natur',
				'Regelmässige Erfolgserlebnisse'
			],
			'meta_description' => 'Parkour Training für Kinder von 6-12 Jahren. Sichere Techniken, qualifizierte Coaches, kleine Gruppen. Probetraining jetzt buchen!'
		],
		'juniors' => [
			'title' => 'Parkour für Juniors (12-18 Jahre)',
			'hero_subtitle' => 'Pushe deine Grenzen - Parkour für Jugendliche',
			'intro_headline' => 'Juniors Parkour: Dein Weg zur Bewegungsfreiheit',
			'intro_text' => 'In den Juniors-Klassen trainieren Jugendliche zwischen 12 und 18 Jahren fortgeschrittene Parkour-Techniken. Hier geht es um mehr als Sport: Wir fördern Selbstständigkeit, Kreativität und den respektvollen Umgang miteinander.',
			'benefits' => [
				'Fortgeschrittene Techniken und Bewegungsfluss',
				'Training nach dem TRuST-Konzept',
				'Mentale Stärke und Fokus entwickeln',
				'Community und Gleichgesinnte finden',
				'Vorbereitung auf Adults-Klassen'
			],
			'meta_description' => 'Parkour für Jugendliche 12-18 Jahre. Fortgeschrittene Techniken, TRuST-Methode, starke Community. Jetzt Probetraining!'
		],
		'adults' => [
			'title' => 'Parkour für Erwachsene (18+)',
			'hero_subtitle' => 'Es ist nie zu spät anzufangen - Parkour für Erwachsene jeden Alters',
			'intro_headline' => 'Adults Parkour: Entdecke dein Bewegungspotenzial',
			'intro_text' => 'Parkour ist für jeden Körper und jedes Alter geeignet. In unseren Erwachsenen-Klassen trainieren Anfänger und Fortgeschrittene gemeinsam - jeder in seinem eigenen Tempo. Erlebe, wie sich deine Bewegungsfreiheit Woche für Woche erweitert.',
			'benefits' => [
				'Für Anfänger und Fortgeschrittene geeignet',
				'Individuelles Tempo, kein Wettkampf',
				'Ganzkörpertraining an der frischen Luft',
				'Stressabbau und mentale Klarheit',
				'Starke Community ab 18 Jahren'
			],
			'meta_description' => 'Parkour für Erwachsene - für Anfänger und Fortgeschrittene. Outdoor-Training, individuelle Betreuung. Probetraining buchen!'
		],
		'women' => [
			'title' => 'Parkour für Frauen',
			'hero_subtitle' => 'Von Frauen, für Frauen - Parkour in geschütztem Rahmen',
			'intro_headline' => 'Women\'s Parkour: Dein Safe Space für Bewegung',
			'intro_text' => 'Unsere Frauen-Klassen bieten einen geschützten Raum, um Parkour zu entdecken und zu trainieren. Geleitet von erfahrenen Trainerinnen, fokussieren wir uns auf deine individuellen Ziele - ob Anfängerin oder Fortgeschrittene.',
			'benefits' => [
				'Training in reinen Frauengruppen',
				'Erfahrene weibliche Coaches',
				'Geschützter, wertschätzender Rahmen',
				'Alle Levels willkommen',
				'Empowerment durch Bewegung'
			],
			'meta_description' => 'Parkour nur für Frauen. Geschützter Rahmen, weibliche Coaches, alle Levels. Jetzt Probetraining in deiner Nähe buchen!'
		],
		'original' => [
			'title' => 'Original Parkour Klassen',
			'hero_subtitle' => 'Die klassische Parkour-Erfahrung - gemischte Gruppen, alle Levels',
			'intro_headline' => 'Original Parkour: Zurück zu den Wurzeln',
			'intro_text' => 'In unseren Original-Klassen trainieren alle gemeinsam - unabhängig von Alter oder Geschlecht. Hier erlebst du Parkour in seiner ursprünglichen Form: Eine Gemeinschaft, die sich gegenseitig unterstützt und inspiriert.',
			'benefits' => [
				'Klassisches Parkour-Training',
				'Gemischte Altersgruppen ab 12 Jahren',
				'Von der Community lernen',
				'Vielfältige Perspektiven und Stile',
				'Der Spirit des ursprünglichen Parkour'
			],
			'meta_description' => 'Original Parkour Klassen für alle ab 12 Jahren. Gemischte Gruppen, Community-Spirit, authentisches Training!'
		]
	];

	// Stadt-spezifische Anpassungen
	if ($city && isset($content[$type])) {
		$city_name = parkourone_get_city_display_name($city);
		$content[$type]['title'] = str_replace('Parkour', "Parkour in {$city_name}", $content[$type]['title']);
		$content[$type]['meta_description'] = str_replace('Probetraining', "Probetraining in {$city_name}", $content[$type]['meta_description']);
	}

	return $content[$type] ?? null;
}

// =====================================================
// Stadt-Erkennung und Gruppierung
// =====================================================

function parkourone_get_cities_from_locations() {
	$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');
	if (!$ortschaft_parent) return [];

	$locations = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false
	]);

	$cities = [];
	$city_mappings = [
		'berlin' => ['berlin-mitte', 'berlin-pankow', 'berlin-friedrichshain', 'berlin-kreuzberg', 'berlin-prenzlauer-berg', 'berlin-köpenick', 'berlin-tiergarten'],
		'zürich' => ['zürich', 'zurich', 'winterthur'],
		'bern' => ['bern', 'thun', 'köniz'],
		'basel' => ['basel'],
	];

	foreach ($locations as $location) {
		$slug = $location->slug;
		$name = $location->name;

		// Versuche Stadt aus dem Namen zu extrahieren
		$city_key = null;

		// Prüfe bekannte Mappings
		foreach ($city_mappings as $city => $slugs) {
			foreach ($slugs as $city_slug) {
				if (strpos($slug, $city_slug) !== false || strpos(strtolower($name), $city) !== false) {
					$city_key = $city;
					break 2;
				}
			}
		}

		// Fallback: Erster Teil des Namens vor Leerzeichen
		if (!$city_key) {
			$parts = explode(' ', $name);
			$city_key = sanitize_title($parts[0]);
		}

		if (!isset($cities[$city_key])) {
			$cities[$city_key] = [
				'name' => ucfirst($city_key),
				'slug' => $city_key,
				'locations' => []
			];
		}

		$cities[$city_key]['locations'][] = [
			'term_id' => $location->term_id,
			'name' => $location->name,
			'slug' => $location->slug
		];
	}

	return $cities;
}

function parkourone_get_city_display_name($city_slug) {
	$names = [
		'berlin' => 'Berlin',
		'zürich' => 'Zürich',
		'zurich' => 'Zürich',
		'bern' => 'Bern',
		'basel' => 'Basel',
		'brig' => 'Brig',
		'thun' => 'Thun'
	];
	return $names[$city_slug] ?? ucfirst($city_slug);
}

// =====================================================
// Kategorie-Seiten (Zielgruppen) abrufen
// =====================================================

function parkourone_get_target_groups() {
	$alter_parent = get_term_by('slug', 'alter', 'event_category');
	if (!$alter_parent) return [];

	$categories = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $alter_parent->term_id,
		'hide_empty' => false
	]);

	$groups = [];
	foreach ($categories as $cat) {
		$seo = parkourone_get_seo_content($cat->slug);
		$groups[] = [
			'term_id' => $cat->term_id,
			'name' => $cat->name,
			'slug' => $cat->slug,
			'seo' => $seo
		];
	}

	return $groups;
}

// =====================================================
// Admin-Seite: Auto-Generator Wizard
// =====================================================

function parkourone_add_auto_pages_menu() {
	add_submenu_page(
		'themes.php',
		'Seiten Auto-Generator',
		'Seiten Generator',
		'manage_options',
		'parkourone-auto-pages',
		'parkourone_auto_pages_admin_page'
	);
}
add_action('admin_menu', 'parkourone_add_auto_pages_menu');

// =====================================================
// Prüfen ob Seiten veraltet sind (ohne neue Features)
// =====================================================

function parkourone_check_outdated_pages($template_pages, $cities, $target_groups) {
	$outdated = [];
	$count = 0;

	// Verschiedene Seiten-Typen haben unterschiedliche Block-Anforderungen
	$required_blocks_template = ['parkourone/faq'];
	$required_blocks_city = ['parkourone/faq', 'parkourone/steps-carousel'];
	$required_blocks_category = ['parkourone/faq', 'parkourone/klassen-slider'];

	// Template-Seiten prüfen
	foreach ($template_pages as $template) {
		if ($template['exists'] && $template['page_id']) {
			$content = get_post_field('post_content', $template['page_id']);
			$is_outdated = false;
			foreach ($required_blocks_template as $block) {
				if (strpos($content, $block) === false) {
					$is_outdated = true;
					break;
				}
			}
			if ($is_outdated) {
				$outdated['templates'][] = $template['slug'];
				$count++;
			}
		}
	}

	// Stadt-Seiten prüfen
	foreach ($cities as $city_slug => $city) {
		$page = get_page_by_path($city_slug);
		if ($page) {
			$content = get_post_field('post_content', $page->ID);
			$has_meta = get_post_meta($page->ID, '_parkourone_auto_generated', true);
			$is_outdated = !$has_meta;
			if (!$is_outdated) {
				foreach ($required_blocks_city as $block) {
					if (strpos($content, $block) === false) {
						$is_outdated = true;
						break;
					}
				}
			}
			if ($is_outdated) {
				$outdated['cities'][] = $city_slug;
				$count++;
			}
		}
	}

	// Zielgruppen-Seiten prüfen
	foreach ($target_groups as $group) {
		$page = get_page_by_path($group['slug']);
		if ($page) {
			$content = get_post_field('post_content', $page->ID);
			$has_meta = get_post_meta($page->ID, '_parkourone_auto_generated', true);
			$is_outdated = !$has_meta;
			if (!$is_outdated) {
				foreach ($required_blocks_category as $block) {
					if (strpos($content, $block) === false) {
						$is_outdated = true;
						break;
					}
				}
			}
			if ($is_outdated) {
				$outdated['categories'][] = $group['slug'];
				$count++;
			}
		}
	}

	return [
		'has_outdated' => $count > 0,
		'count' => $count,
		'pages' => $outdated
	];
}

// =====================================================
// SEO Status Berechnung für Admin-Übersicht
// =====================================================

function parkourone_get_seo_status($template_pages, $cities, $target_groups) {
	// Zähle erstellte Seiten
	$templates_created = 0;
	foreach ($template_pages as $template) {
		if ($template['exists']) $templates_created++;
	}

	$cities_created = 0;
	foreach ($cities as $city_slug => $city) {
		if (get_page_by_path($city_slug)) $cities_created++;
	}

	$categories_created = 0;
	foreach ($target_groups as $group) {
		if (get_page_by_path($group['slug'])) $categories_created++;
	}

	// FAQ Count
	$faq_count = wp_count_posts('faq');
	$faqs_count = $faq_count->publish ?? 0;

	// Mindestens eine Seite erstellt?
	$has_any_page = ($templates_created + $cities_created + $categories_created) > 0;
	$has_faqs = $faqs_count > 0;

	// Alle Seiten erstellt?
	$all_templates = $templates_created >= count($template_pages);
	$all_cities = empty($cities) || $cities_created >= count($cities);
	$all_categories = empty($target_groups) || $categories_created >= count($target_groups);

	// SEO Features Status
	$status = [
		'pages' => [
			'templates' => $templates_created,
			'cities' => $cities_created,
			'categories' => $categories_created,
			'faqs' => $faqs_count
		],
		'meta_tags' => [
			['label' => 'Meta Title (dynamisch)', 'active' => $has_any_page],
			['label' => 'Meta Description', 'active' => $has_any_page],
			['label' => 'Meta Keywords', 'active' => $has_any_page],
			['label' => 'Canonical URL', 'active' => $has_any_page],
			['label' => 'Robots Meta', 'active' => $has_any_page],
		],
		'structured_data' => [
			['label' => 'SportsActivityLocation Schema', 'active' => $has_any_page],
			['label' => 'FAQPage Schema', 'active' => $has_faqs],
			['label' => 'Organization Schema', 'active' => $has_any_page],
			['label' => 'JSON-LD Markup', 'active' => $has_any_page || $has_faqs],
		],
		'social' => [
			['label' => 'Open Graph Tags (Facebook)', 'active' => $has_any_page],
			['label' => 'Twitter Cards', 'active' => $has_any_page],
			['label' => 'OG Image Support', 'active' => $has_any_page],
			['label' => 'Social Share Preview', 'active' => $has_any_page],
		],
		'content' => [
			['label' => 'SEO-optimierte Texte', 'active' => $has_any_page],
			['label' => 'Keyword-Integration', 'active' => $has_any_page],
			['label' => 'Standort-spezifische Inhalte', 'active' => $cities_created > 0],
			['label' => 'Zielgruppen-spezifische Inhalte', 'active' => $categories_created > 0],
			['label' => 'FAQ-Inhalte für LLMs', 'active' => $has_faqs],
		],
	];

	// Zähle aktive Features
	$completed = 0;
	$total = 0;
	foreach (['meta_tags', 'structured_data', 'social', 'content'] as $category) {
		foreach ($status[$category] as $item) {
			$total++;
			if ($item['active']) $completed++;
		}
	}

	$status['completed_count'] = $completed;
	$status['total_count'] = $total;
	$status['all_complete'] = $completed === $total && $has_any_page && $has_faqs;

	return $status;
}

function parkourone_auto_pages_admin_page() {
	$cities = parkourone_get_cities_from_locations();
	$target_groups = parkourone_get_target_groups();
	$template_pages = parkourone_get_template_pages();
	$site_location = parkourone_get_site_location();

	// Single-City-Site Erkennung:
	// Wenn die Site-Location (z.B. "berlin") mit der einzigen Stadt übereinstimmt,
	// zeigen wir stattdessen die einzelnen Ortschaften an
	$is_single_city_site = false;
	$ortschaften = [];

	if (count($cities) === 1) {
		$city_keys = array_keys($cities);
		$only_city = $city_keys[0];

		// Prüfen ob Site-Location mit der Stadt übereinstimmt
		if ($site_location['slug'] === $only_city ||
			stripos($site_location['slug'], $only_city) !== false ||
			stripos($only_city, $site_location['slug']) !== false) {
			$is_single_city_site = true;
			$ortschaften = $cities[$only_city]['locations'] ?? [];
		}
	}

	// SEO Status berechnen
	$seo_status = parkourone_get_seo_status($template_pages, $cities, $target_groups);

	// Erfolgsmeldungen anzeigen
	settings_errors('po_auto_pages');
	?>
	<div class="wrap">
		<h1>ParkourONE Seiten Generator</h1>

		<!-- Erkannter Standort -->
		<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
			<span style="font-size: 2rem;">📍</span>
			<div>
				<strong style="font-size: 1.1rem;">Erkannter Standort: <?php echo esc_html($site_location['name']); ?></strong>
				<br>
				<small style="opacity: 0.9;">Alle generierten Seiten werden automatisch für <strong>"Parkour <?php echo esc_html($site_location['name']); ?>"</strong> SEO-optimiert.</small>
			</div>
		</div>

		<!-- Header-Variante Auswahl -->
		<div style="background: #fff; padding: 1.25rem 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
			<div style="display: flex; align-items: center; gap: 0.5rem;">
				<span style="font-size: 1.5rem;">🎨</span>
				<strong>Header-Stil für neue Seiten:</strong>
			</div>
			<select id="po-header-variant" name="header_variant" style="padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #ddd; font-size: 14px; min-width: 280px;">
				<option value="split" selected>Split – Text links, Bild rechts</option>
				<option value="centered">Centered – Grosser Text zentriert</option>
				<option value="fullscreen">Fullscreen – Vollbild mit Overlay</option>
			</select>
			<small style="color: #666; flex-basis: 100%;">Dieser Stil wird für alle neu erstellten Stadt- und Kategorie-Seiten verwendet. Du kannst den Stil später im Block-Editor ändern.</small>
		</div>

		<!-- SEO & LLM Status Übersicht -->
		<div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem;">
			<h2 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
				<span style="font-size: 1.5rem;">🔍</span> SEO & LLM Optimierung
				<?php if ($seo_status['all_complete']): ?>
					<span style="background: #00a32a; color: #fff; font-size: 12px; padding: 3px 10px; border-radius: 20px; margin-left: 10px;">Komplett</span>
				<?php else: ?>
					<span style="background: #dba617; color: #fff; font-size: 12px; padding: 3px 10px; border-radius: 20px; margin-left: 10px;"><?php echo $seo_status['completed_count']; ?>/<?php echo $seo_status['total_count']; ?></span>
				<?php endif; ?>
			</h2>
			<p style="color: #666; margin-bottom: 1rem;">Diese Features werden automatisch aktiviert, sobald du die entsprechenden Seiten erstellst.</p>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
				<!-- Meta Tags -->
				<div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem;">
					<h4 style="margin: 0 0 0.75rem 0; font-size: 14px; color: #1d2327;">Meta Tags</h4>
					<?php foreach ($seo_status['meta_tags'] as $item): ?>
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 13px;">
							<?php if ($item['active']): ?>
								<span style="color: #00a32a; font-size: 16px;">✓</span>
							<?php else: ?>
								<span style="color: #ccc; font-size: 16px;">○</span>
							<?php endif; ?>
							<span style="color: <?php echo $item['active'] ? '#1d2327' : '#999'; ?>;"><?php echo esc_html($item['label']); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Structured Data -->
				<div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem;">
					<h4 style="margin: 0 0 0.75rem 0; font-size: 14px; color: #1d2327;">Structured Data (Schema.org)</h4>
					<?php foreach ($seo_status['structured_data'] as $item): ?>
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 13px;">
							<?php if ($item['active']): ?>
								<span style="color: #00a32a; font-size: 16px;">✓</span>
							<?php else: ?>
								<span style="color: #ccc; font-size: 16px;">○</span>
							<?php endif; ?>
							<span style="color: <?php echo $item['active'] ? '#1d2327' : '#999'; ?>;"><?php echo esc_html($item['label']); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Social & Sharing -->
				<div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem;">
					<h4 style="margin: 0 0 0.75rem 0; font-size: 14px; color: #1d2327;">Social Media & Sharing</h4>
					<?php foreach ($seo_status['social'] as $item): ?>
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 13px;">
							<?php if ($item['active']): ?>
								<span style="color: #00a32a; font-size: 16px;">✓</span>
							<?php else: ?>
								<span style="color: #ccc; font-size: 16px;">○</span>
							<?php endif; ?>
							<span style="color: <?php echo $item['active'] ? '#1d2327' : '#999'; ?>;"><?php echo esc_html($item['label']); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Content -->
				<div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem;">
					<h4 style="margin: 0 0 0.75rem 0; font-size: 14px; color: #1d2327;">SEO Content</h4>
					<?php foreach ($seo_status['content'] as $item): ?>
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 13px;">
							<?php if ($item['active']): ?>
								<span style="color: #00a32a; font-size: 16px;">✓</span>
							<?php else: ?>
								<span style="color: #ccc; font-size: 16px;">○</span>
							<?php endif; ?>
							<span style="color: <?php echo $item['active'] ? '#1d2327' : '#999'; ?>;"><?php echo esc_html($item['label']); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Seiten-Statistik -->
			<div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
				<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
					<div>
						<span style="font-size: 24px; font-weight: 600; color: #1d2327;"><?php echo $seo_status['pages']['templates']; ?></span>
						<span style="color: #666; font-size: 13px;"> / <?php echo count($template_pages); ?> Template-Seiten</span>
					</div>
					<div>
						<span style="font-size: 24px; font-weight: 600; color: #1d2327;"><?php echo $seo_status['pages']['cities']; ?></span>
						<span style="color: #666; font-size: 13px;"> / <?php echo count($cities); ?> Stadt-Seiten</span>
					</div>
					<div>
						<span style="font-size: 24px; font-weight: 600; color: #1d2327;"><?php echo $seo_status['pages']['categories']; ?></span>
						<span style="color: #666; font-size: 13px;"> / <?php echo count($target_groups); ?> Zielgruppen-Seiten</span>
					</div>
					<div>
						<span style="font-size: 24px; font-weight: 600; color: #1d2327;"><?php echo $seo_status['pages']['faqs']; ?></span>
						<span style="color: #666; font-size: 13px;"> FAQs importiert</span>
					</div>
				</div>
			</div>
		</div>

		<p>Erstelle vorgefertigte Seiten basierend auf Templates oder generiere dynamische Seiten aus deinen Event-Kategorien.</p>

		<!-- WARNUNG: Seiten aktualisieren -->
		<?php
		$outdated_pages = parkourone_check_outdated_pages($template_pages, $cities, $target_groups);
		if ($outdated_pages['has_outdated']): ?>
		<div style="background: #fff8e5; border-left: 4px solid #dba617; padding: 1rem 1.5rem; border-radius: 0 8px 8px 0; margin-bottom: 1.5rem;">
			<h3 style="margin: 0 0 0.5rem 0; color: #1d2327; display: flex; align-items: center; gap: 0.5rem;">
				<span>⚠️</span> Seiten-Update empfohlen
			</h3>
			<p style="margin: 0 0 1rem 0; color: #666;">
				<strong><?php echo $outdated_pages['count']; ?> Seiten</strong> wurden vor den neuesten Features erstellt und enthalten möglicherweise nicht:
			</p>
			<ul style="margin: 0 0 1rem 1.5rem; color: #666;">
				<li><strong>Text-Reveal Block</strong> – Apple-Style Scroll-Animation</li>
				<li><strong>Steps-Carousel Block</strong> – Swipe-Cards für Probetraining</li>
				<li><strong>FAQ-Block</strong> mit verbesserten Animationen</li>
				<li>Native SEO Meta Tags (Title, Description, Open Graph)</li>
			</ul>
			<p style="margin: 0; font-size: 13px; color: #666;">
				<strong>Tipp:</strong> Aktiviere unten "Bestehende Seiten überschreiben" und wähle die Seiten aus, die du aktualisieren möchtest.
			</p>
		</div>
		<?php endif; ?>

		<!-- SEITEN-VORLAGEN -->
		<div class="po-auto-pages-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 2rem;">
			<h2 style="margin-top: 0;">📄 Seiten-Vorlagen</h2>
			<p>Erstelle fertige Seiten aus den vorgefertigten Templates. Diese können nach der Erstellung im Gutenberg-Editor angepasst werden.</p>

			<form method="post" action="">
				<?php wp_nonce_field('po_generate_template_pages', 'po_template_nonce'); ?>

				<!-- Überschreiben Option -->
				<div style="background: #f6f7f7; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
					<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
						<input type="checkbox" name="overwrite_templates" id="overwrite-templates" value="1">
						<span><strong>Bestehende Seiten überschreiben</strong></span>
					</label>
					<p style="margin: 0.5rem 0 0 26px; font-size: 12px; color: #666;">
						Aktivieren, um bereits existierende Seiten mit der neuesten Version zu ersetzen. Die alten Inhalte werden überschrieben!
					</p>
				</div>

				<table class="widefat" style="margin: 1rem 0;">
					<thead>
						<tr>
							<th style="width: 30px;"><input type="checkbox" id="select-all-templates"></th>
							<th>Seite</th>
							<th>Beschreibung</th>
							<th>Enthaltene Blöcke</th>
							<th>Status</th>
							<th>Links</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($template_pages as $template): ?>
							<tr>
								<td><input type="checkbox" name="templates[]" value="<?php echo esc_attr($template['slug']); ?>" class="template-checkbox" data-exists="<?php echo $template['exists'] ? '1' : '0'; ?>" <?php echo $template['exists'] ? 'disabled' : ''; ?>></td>
								<td><strong><?php echo esc_html($template['title']); ?></strong></td>
								<td><?php echo esc_html($template['description']); ?></td>
								<td><small style="color: #666;"><?php echo esc_html($template['blocks']); ?></small></td>
								<td>
									<?php if ($template['exists']): ?>
										<?php if (isset($template['is_outdated']) && $template['is_outdated']): ?>
											<span style="color: #dba617;">⚠️ Update verfügbar</span>
										<?php else: ?>
											<span style="color: green;">✓ Existiert</span>
										<?php endif; ?>
									<?php else: ?>
										<span style="color: #999;">Nicht erstellt</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($template['exists'] && $template['page_id']): ?>
										<a href="<?php echo get_permalink($template['page_id']); ?>" target="_blank" style="text-decoration: none; margin-right: 8px;" title="Seite ansehen">👁️</a>
										<a href="<?php echo get_edit_post_link($template['page_id']); ?>" style="text-decoration: none;" title="Seite bearbeiten">✏️</a>
									<?php else: ?>
										<span style="color: #ccc;">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<button type="submit" name="generate_template_pages" class="button button-primary button-hero">Ausgewählte Seiten erstellen</button>
			</form>
		</div>

		<div class="po-auto-pages-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">

			<!-- Stadt-Seiten oder Ortschaft-Seiten (je nach Site-Typ) -->
			<div class="po-auto-pages-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<?php if ($is_single_city_site): ?>
					<!-- Single-City-Site: Zeige Ortschaften statt Städte -->
					<h2 style="margin-top: 0;">📍 Ortschaft-Seiten</h2>
					<p>Erkannte Ortschaften in <?php echo esc_html($site_location['name']); ?>:</p>

					<?php if (empty($ortschaften)): ?>
						<p><em>Keine Ortschaften gefunden. Bitte erstelle zuerst Events mit Standort-Kategorien unter "Ortschaft".</em></p>
					<?php else: ?>
						<form method="post" action="">
							<?php wp_nonce_field('po_generate_location_pages', 'po_location_nonce'); ?>
							<input type="hidden" name="header_variant" class="po-header-variant-field" value="split">

							<!-- Überschreiben Option -->
							<div style="background: #f6f7f7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
								<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
									<input type="checkbox" name="overwrite_locations" id="overwrite-locations" value="1">
									<span><strong>Bestehende überschreiben</strong></span>
								</label>
								<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-top: 8px;">
									<input type="checkbox" name="force_locations" id="force-locations" value="1">
									<span><strong>Force (komplett neu)</strong> - Ignoriert manuelle Anpassungen</span>
								</label>
							</div>

							<table class="widefat" style="margin: 1rem 0;">
								<thead>
									<tr>
										<th style="width: 30px;"><input type="checkbox" id="select-all-locations"></th>
										<th>Ortschaft</th>
										<th>Slug</th>
										<th>Status</th>
										<th>Links</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($ortschaften as $location):
										$page_exists = get_page_by_path($location['slug']);
									?>
										<tr>
											<td><input type="checkbox" name="locations[]" value="<?php echo esc_attr($location['slug']); ?>" class="location-checkbox" data-exists="<?php echo $page_exists ? '1' : '0'; ?>" <?php echo $page_exists ? 'disabled' : ''; ?>></td>
											<td><strong><?php echo esc_html($location['name']); ?></strong></td>
											<td><code><?php echo esc_html($location['slug']); ?></code></td>
											<td>
												<?php if ($page_exists): ?>
													<span style="color: green;">✓</span>
												<?php else: ?>
													<span style="color: #999;">—</span>
												<?php endif; ?>
											</td>
											<td>
												<?php if ($page_exists): ?>
													<a href="<?php echo get_permalink($page_exists->ID); ?>" target="_blank" style="text-decoration: none; margin-right: 8px;" title="Seite ansehen">👁️</a>
													<a href="<?php echo get_edit_post_link($page_exists->ID); ?>" style="text-decoration: none;" title="Seite bearbeiten">✏️</a>
												<?php else: ?>
													<span style="color: #ccc;">—</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<button type="submit" name="generate_location_pages" class="button button-primary">Ortschaft-Seiten erstellen</button>
						</form>
					<?php endif; ?>

				<?php else: ?>
					<!-- Multi-City-Site: Zeige Städte wie bisher -->
					<h2 style="margin-top: 0;">🏙️ Stadt-Seiten</h2>
					<p>Erkannte Städte aus deinen Standorten:</p>

					<?php if (empty($cities)): ?>
						<p><em>Keine Standorte gefunden. Bitte erstelle zuerst Events mit Standort-Kategorien.</em></p>
					<?php else: ?>
						<form method="post" action="">
							<?php wp_nonce_field('po_generate_city_pages', 'po_city_nonce'); ?>
							<input type="hidden" name="header_variant" class="po-header-variant-field" value="split">

							<!-- Überschreiben Option -->
							<div style="background: #f6f7f7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
								<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
									<input type="checkbox" name="overwrite_cities" id="overwrite-cities" value="1">
									<span><strong>Bestehende überschreiben</strong></span>
								</label>
								<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-top: 8px;">
									<input type="checkbox" name="force_cities" id="force-cities" value="1">
									<span><strong>Force (komplett neu)</strong> - Ignoriert manuelle Anpassungen</span>
								</label>
							</div>

							<table class="widefat" style="margin: 1rem 0;">
								<thead>
									<tr>
										<th style="width: 30px;"><input type="checkbox" id="select-all-cities"></th>
										<th>Stadt</th>
										<th>Standorte</th>
										<th>Status</th>
										<th>Links</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($cities as $city_slug => $city):
										$page_exists = get_page_by_path($city_slug);
										$is_outdated = isset($outdated_pages['pages']['cities']) && in_array($city_slug, $outdated_pages['pages']['cities']);
									?>
										<tr>
											<td><input type="checkbox" name="cities[]" value="<?php echo esc_attr($city_slug); ?>" class="city-checkbox" data-exists="<?php echo $page_exists ? '1' : '0'; ?>" <?php echo $page_exists ? 'disabled' : ''; ?>></td>
											<td><strong><?php echo esc_html($city['name']); ?></strong></td>
											<td><?php echo count($city['locations']); ?></td>
											<td>
												<?php if ($page_exists): ?>
													<?php if ($is_outdated): ?>
														<span style="color: #dba617;">⚠️ Update</span>
													<?php else: ?>
														<span style="color: green;">✓</span>
													<?php endif; ?>
												<?php else: ?>
													<span style="color: #999;">—</span>
												<?php endif; ?>
											</td>
											<td>
												<?php if ($page_exists): ?>
													<a href="<?php echo get_permalink($page_exists->ID); ?>" target="_blank" style="text-decoration: none; margin-right: 8px;" title="Seite ansehen">👁️</a>
													<a href="<?php echo get_edit_post_link($page_exists->ID); ?>" style="text-decoration: none;" title="Seite bearbeiten">✏️</a>
												<?php else: ?>
													<span style="color: #ccc;">—</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<button type="submit" name="generate_city_pages" class="button button-primary">Stadt-Seiten erstellen</button>
						</form>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<!-- Kategorie-Seiten -->
			<div class="po-auto-pages-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;">👥 Zielgruppen-Seiten</h2>
				<p>Erkannte Zielgruppen aus deinen Alters-Kategorien:</p>

				<?php if (empty($target_groups)): ?>
					<p><em>Keine Altersgruppen gefunden. Bitte erstelle zuerst die Kategorie "Alter" mit Unterkategorien.</em></p>
				<?php else: ?>
					<form method="post" action="">
						<?php wp_nonce_field('po_generate_category_pages', 'po_category_nonce'); ?>
						<input type="hidden" name="header_variant" class="po-header-variant-field" value="split">

						<!-- Überschreiben Option -->
						<div style="background: #f6f7f7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
							<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
								<input type="checkbox" name="overwrite_categories" id="overwrite-categories" value="1">
								<span><strong>Bestehende ueberschreiben</strong></span>
							</label>
							<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-top: 8px;">
								<input type="checkbox" name="force_categories" id="force-categories" value="1">
								<span><strong>Force (komplett neu)</strong> - Ignoriert manuelle Anpassungen</span>
							</label>
						</div>

						<table class="widefat" style="margin: 1rem 0;">
							<thead>
								<tr>
									<th style="width: 30px;"><input type="checkbox" id="select-all-categories"></th>
									<th>Zielgruppe</th>
									<th>SEO</th>
									<th>Status</th>
									<th>Links</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($target_groups as $group):
									$page_exists = get_page_by_path($group['slug']);
									$is_outdated = isset($outdated_pages['pages']['categories']) && in_array($group['slug'], $outdated_pages['pages']['categories']);
								?>
									<tr>
										<td><input type="checkbox" name="categories[]" value="<?php echo esc_attr($group['slug']); ?>" class="category-checkbox" data-exists="<?php echo $page_exists ? '1' : '0'; ?>" <?php echo $page_exists ? 'disabled' : ''; ?>></td>
										<td><strong><?php echo esc_html($group['name']); ?></strong></td>
										<td><?php echo $group['seo'] ? '<span style="color: green;">✓</span>' : '<span style="color: orange;">⚠</span>'; ?></td>
										<td>
											<?php if ($page_exists): ?>
												<?php if ($is_outdated): ?>
													<span style="color: #dba617;">⚠️</span>
												<?php else: ?>
													<span style="color: green;">✓</span>
												<?php endif; ?>
											<?php else: ?>
												<span style="color: #999;">—</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($page_exists): ?>
												<a href="<?php echo get_permalink($page_exists->ID); ?>" target="_blank" style="text-decoration: none; margin-right: 8px;" title="Seite ansehen">👁️</a>
												<a href="<?php echo get_edit_post_link($page_exists->ID); ?>" style="text-decoration: none;" title="Seite bearbeiten">✏️</a>
											<?php else: ?>
												<span style="color: #ccc;">—</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<button type="submit" name="generate_category_pages" class="button button-primary">Zielgruppen-Seiten erstellen</button>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Header-Variante Sync zu Hidden Fields
			$('#po-header-variant').on('change', function() {
				$('.po-header-variant-field').val(this.value);
			});

			// Select All Handlers
			$('#select-all-templates').on('change', function() {
				$('.template-checkbox:not(:disabled)').prop('checked', this.checked);
			});
			$('#select-all-cities').on('change', function() {
				$('.city-checkbox:not(:disabled)').prop('checked', this.checked);
			});
			$('#select-all-locations').on('change', function() {
				$('.location-checkbox:not(:disabled)').prop('checked', this.checked);
			});
			$('#select-all-categories').on('change', function() {
				$('.category-checkbox:not(:disabled)').prop('checked', this.checked);
			});

			// Überschreiben Toggle - Templates
			$('#overwrite-templates').on('change', function() {
				var overwrite = this.checked;
				$('.template-checkbox').each(function() {
					if ($(this).data('exists') === 1) {
						$(this).prop('disabled', !overwrite);
						if (!overwrite) $(this).prop('checked', false);
					}
				});
			});

			// Überschreiben Toggle - Cities
			$('#overwrite-cities').on('change', function() {
				var overwrite = this.checked;
				$('.city-checkbox').each(function() {
					if ($(this).data('exists') === 1) {
						$(this).prop('disabled', !overwrite);
						if (!overwrite) $(this).prop('checked', false);
					}
				});
			});

			// Überschreiben Toggle - Locations (für Single-City-Sites)
			$('#overwrite-locations').on('change', function() {
				var overwrite = this.checked;
				$('.location-checkbox').each(function() {
					if ($(this).data('exists') === 1) {
						$(this).prop('disabled', !overwrite);
						if (!overwrite) $(this).prop('checked', false);
					}
				});
			});

			// Überschreiben Toggle - Categories
			$('#overwrite-categories').on('change', function() {
				var overwrite = this.checked;
				$('.category-checkbox').each(function() {
					if ($(this).data('exists') === 1) {
						$(this).prop('disabled', !overwrite);
						if (!overwrite) $(this).prop('checked', false);
					}
				});
			});
		});
		</script>

		<!-- Erklärung: Seiten überschreiben -->
		<div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 1.5rem; border-radius: 0 8px 8px 0; margin-top: 2rem;">
			<h3 style="margin: 0 0 1rem 0; color: #1d2327; display: flex; align-items: center; gap: 0.5rem;">
				<span>💡</span> Was passiert beim Überschreiben?
			</h3>
			<p style="margin: 0 0 1rem 0; color: #333;">
				Wenn du bestehende Seiten überschreibst, werden deine <strong>manuellen Anpassungen geschützt</strong>:
			</p>
			<ul style="margin: 0 0 1rem 1.5rem; color: #333; line-height: 1.7;">
				<li><strong>Bilder bleiben erhalten:</strong> Blöcke mit manuell hochgeladenen Bildern (über die Mediathek) werden nicht überschrieben.</li>
				<li><strong>Markierte Blöcke bleiben:</strong> Blöcke, die du im Editor als "Angepasst" markiert hast (isCustom: true), werden beibehalten.</li>
				<li><strong>Neue Features kommen hinzu:</strong> Neue Template-Blöcke (z.B. verbesserte FAQs, Testimonials) werden ergänzt.</li>
			</ul>
			<p style="margin: 0; font-size: 13px; color: #666;">
				<strong>Tipp für den Editor:</strong> Um einen Block dauerhaft vor dem Überschreiben zu schützen, öffne die Block-Einstellungen (rechte Sidebar) und aktiviere die Option "Block als angepasst markieren". Alternativ lade einfach ein eigenes Bild hoch – Blöcke mit Mediathek-Bildern werden automatisch erkannt und geschützt.
			</p>
		</div>
	</div>
	<?php
}

// =====================================================
// Template-Seiten Konfiguration
// =====================================================

function parkourone_get_template_pages() {
	$templates = [
		[
			'slug' => 'startseite',
			'title' => 'Startseite',
			'description' => 'Komplette Homepage mit Hero, Zielgruppen, Klassen und Testimonials',
			'blocks' => 'Hero, Zielgruppen-Grid, Klassen-Slider, About, Angebote, Testimonials',
			'pattern_file' => 'page-startseite.php',
			'page_slug' => 'startseite',
		],
		[
			'slug' => 'kurse-workshops',
			'title' => 'Kurse & Workshops',
			'description' => 'Übersicht aller Angebote mit Filterfunktion und FAQ',
			'blocks' => 'Page-Header, Angebote-Grid, Testimonials, FAQ',
			'pattern_file' => 'page-kurse-workshops.php',
			'page_slug' => 'kurse-workshops',
		],
		[
			'slug' => 'team',
			'title' => 'Team-Seite',
			'description' => 'Über die Schule, TRUST Education, Team-Grid und Jobs',
			'blocks' => 'Page-Header, About, Stats, TRUST Education, Team-Grid, Jobs, Schulen-Grid',
			'pattern_file' => 'page-team.php',
			'page_slug' => 'team',
		],
		[
			'slug' => 'stundenplan',
			'title' => 'Stundenplan',
			'description' => 'Wochenübersicht aller Trainingszeiten mit Klassen-Selektor',
			'blocks' => 'Page-Header, Stundenplan, Klassen-Selektor, Intro-Section, FAQ',
			'pattern_file' => 'page-stundenplan.php',
			'page_slug' => 'stundenplan',
		],
		[
			'slug' => 'preise',
			'title' => 'Preise & Mitgliedschaft',
			'description' => 'Preisübersicht nach Altersgruppen mit FAQ',
			'blocks' => 'Page-Header, Pricing-Table, Intro-Section, FAQ',
			'pattern_file' => 'page-preise.php',
			'page_slug' => 'preise',
		],
	];

	// Prüfen ob Seiten bereits existieren
	foreach ($templates as &$template) {
		$page = get_page_by_path($template['page_slug']);
		$template['exists'] = !empty($page);
		$template['page_id'] = $page ? $page->ID : null;
	}

	return $templates;
}

// =====================================================
// Template-Seite aus Pattern erstellen
// =====================================================

function parkourone_create_template_page($template_slug, $overwrite = false) {
	$templates = parkourone_get_template_pages();
	$template = null;

	foreach ($templates as $t) {
		if ($t['slug'] === $template_slug) {
			$template = $t;
			break;
		}
	}

	if (!$template) {
		return false;
	}

	// Prüfen ob Seite bereits existiert
	$existing_page_id = null;
	if ($template['exists']) {
		if (!$overwrite) {
			return false;
		}
		$existing_page_id = $template['page_id'];
	}

	// Pattern-Datei lesen
	$pattern_path = get_template_directory() . '/patterns/' . $template['pattern_file'];

	if (!file_exists($pattern_path)) {
		error_log('Pattern file not found: ' . $pattern_path);
		return false;
	}

	$pattern_content = file_get_contents($pattern_path);

	// PHP-Header aus Pattern entfernen (alles vor dem ersten <!-- wp:)
	$content_start = strpos($pattern_content, '<!-- wp:');
	if ($content_start !== false) {
		$new_content = substr($pattern_content, $content_start);
	} else {
		$new_content = $pattern_content;
	}

	// Seite erstellen oder aktualisieren
	if ($existing_page_id) {
		// Ticket #6 & #7: Bestehenden Content mit neuem Template mergen
		// Erhält manuell hochgeladene Bilder und customized Blöcke
		$existing_page = get_post($existing_page_id);
		$content = parkourone_merge_page_content($existing_page->post_content, $new_content);

		// Bestehende Seite aktualisieren
		$page_id = wp_update_post([
			'ID' => $existing_page_id,
			'post_content' => $content,
		]);
	} else {
		// Neue Seite erstellen
		$page_id = wp_insert_post([
			'post_title' => $template['title'],
			'post_name' => $template['page_slug'],
			'post_content' => $new_content,
			'post_status' => 'draft',
			'post_type' => 'page',
			'post_author' => get_current_user_id(),
		]);
	}

	if (is_wp_error($page_id)) {
		error_log('Error creating/updating page: ' . $page_id->get_error_message());
		return false;
	}

	// Meta-Daten setzen
	update_post_meta($page_id, '_parkourone_template_page', true);
	update_post_meta($page_id, '_parkourone_template_slug', $template_slug);

	// Falls Startseite, als Homepage setzen (optional)
	if ($template_slug === 'startseite') {
		update_post_meta($page_id, '_parkourone_is_homepage', true);
	}

	return $page_id;
}

// =====================================================
// Seiten-Generierung Handler
// =====================================================

function parkourone_handle_page_generation() {
	// Template-Seiten generieren
	if (isset($_POST['generate_template_pages']) && wp_verify_nonce($_POST['po_template_nonce'], 'po_generate_template_pages')) {
		$templates = $_POST['templates'] ?? [];
		$overwrite = isset($_POST['overwrite_templates']) && $_POST['overwrite_templates'] === '1';
		$created = 0;
		$updated = 0;
		$created_pages = [];

		foreach ($templates as $template_slug) {
			// Prüfen ob Seite existiert
			$existing = get_page_by_path($template_slug);
			$page_id = parkourone_create_template_page($template_slug, $overwrite);
			if ($page_id) {
				if ($existing && $overwrite) {
					$updated++;
				} else {
					$created++;
				}
				$created_pages[] = '<a href="' . get_edit_post_link($page_id) . '">' . get_the_title($page_id) . '</a>';
			}
		}

		if ($created > 0 || $updated > 0) {
			$parts = [];
			if ($created > 0) $parts[] = "{$created} erstellt";
			if ($updated > 0) $parts[] = "{$updated} aktualisiert";
			$message = "Seiten " . implode(', ', $parts) . ": " . implode(', ', $created_pages);
			add_settings_error('po_auto_pages', 'templates_created', $message, 'success');
		}
	}

	// Stadt-Seiten generieren
	if (isset($_POST['generate_city_pages']) && wp_verify_nonce($_POST['po_city_nonce'], 'po_generate_city_pages')) {
		$cities = $_POST['cities'] ?? [];
		$overwrite = isset($_POST['overwrite_cities']) && $_POST['overwrite_cities'] === '1';
		$force = isset($_POST['force_cities']) && $_POST['force_cities'] === '1';
		$header_variant = sanitize_text_field($_POST['header_variant'] ?? 'split');
		$created = 0;
		$updated = 0;

		if (empty($cities)) {
			add_settings_error('po_auto_pages', 'no_cities_selected', 'Bitte waehle mindestens eine Stadt aus. Falls alle bereits existieren, aktiviere "Bestehende ueberschreiben".', 'error');
		} else {
			foreach ($cities as $city_slug) {
				$existing = get_page_by_path($city_slug);
				$result = parkourone_create_city_page($city_slug, $overwrite, $header_variant, $force);
				if ($result) {
					if ($existing && $overwrite) {
						$updated++;
					} else {
						$created++;
					}
				}
			}

			$parts = [];
			if ($created > 0) $parts[] = "{$created} erstellt";
			if ($updated > 0) $parts[] = "{$updated} aktualisiert";
			if (!empty($parts)) {
				add_settings_error('po_auto_pages', 'cities_created', "Stadt-Seiten: " . implode(', ', $parts), 'success');
			}
		}
	}

	// Ortschaft-Seiten generieren (für Single-City-Sites wie berlin.parkourone.com)
	if (isset($_POST['generate_location_pages']) && wp_verify_nonce($_POST['po_location_nonce'], 'po_generate_location_pages')) {
		$locations = $_POST['locations'] ?? [];
		$overwrite = isset($_POST['overwrite_locations']) && $_POST['overwrite_locations'] === '1';
		$force = isset($_POST['force_locations']) && $_POST['force_locations'] === '1';
		$header_variant = sanitize_text_field($_POST['header_variant'] ?? 'split');
		$created = 0;
		$updated = 0;

		if (empty($locations)) {
			add_settings_error('po_auto_pages', 'no_locations_selected', 'Bitte wähle mindestens eine Ortschaft aus. Falls alle bereits existieren, aktiviere "Bestehende überschreiben".', 'error');
		} else {
			foreach ($locations as $location_slug) {
				$existing = get_page_by_path($location_slug);
				$result = parkourone_create_location_page($location_slug, $overwrite, $header_variant, $force);
				if ($result) {
					if ($existing && $overwrite) {
						$updated++;
					} else {
						$created++;
					}
				}
			}

			$parts = [];
			if ($created > 0) $parts[] = "{$created} erstellt";
			if ($updated > 0) $parts[] = "{$updated} aktualisiert";
			if (!empty($parts)) {
				add_settings_error('po_auto_pages', 'locations_created', "Ortschaft-Seiten: " . implode(', ', $parts), 'success');
			}
		}
	}

	// Kategorie-Seiten generieren
	if (isset($_POST['generate_category_pages']) && wp_verify_nonce($_POST['po_category_nonce'], 'po_generate_category_pages')) {
		$categories = $_POST['categories'] ?? [];
		$overwrite = isset($_POST['overwrite_categories']) && $_POST['overwrite_categories'] === '1';
		$force = isset($_POST['force_categories']) && $_POST['force_categories'] === '1';
		$header_variant = sanitize_text_field($_POST['header_variant'] ?? 'split');
		$created = 0;
		$updated = 0;

		if (empty($categories)) {
			add_settings_error('po_auto_pages', 'no_categories_selected', 'Bitte waehle mindestens eine Zielgruppe aus. Falls alle bereits existieren, aktiviere "Bestehende ueberschreiben".', 'error');
		} else {
			foreach ($categories as $cat_slug) {
				$existing = get_page_by_path($cat_slug);
				$result = parkourone_create_category_page($cat_slug, $overwrite, $header_variant, $force);
				if ($result) {
					if ($existing && $overwrite) {
						$updated++;
					} else {
						$created++;
					}
				}
			}

			$parts = [];
			if ($created > 0) $parts[] = "{$created} erstellt";
			if ($updated > 0) $parts[] = "{$updated} aktualisiert";
			if (!empty($parts)) {
				add_settings_error('po_auto_pages', 'categories_created', "Zielgruppen-Seiten: " . implode(', ', $parts), 'success');
			}
		}
	}
}
add_action('admin_init', 'parkourone_handle_page_generation');

// =====================================================
// Stadt-Seite erstellen
// =====================================================

function parkourone_create_city_page($city_slug, $overwrite = false, $header_variant = 'split', $force = false) {
	$cities = parkourone_get_cities_from_locations();
	if (!isset($cities[$city_slug])) return false;

	// Prüfen ob Seite bereits existiert
	$existing_page = get_page_by_path($city_slug);
	if ($existing_page && !$overwrite) {
		return false;
	}

	$city = $cities[$city_slug];
	$city_name = parkourone_get_city_display_name($city_slug);

	// Block-Content für die Seite (Template)
	$new_content = parkourone_generate_city_page_content($city_slug, $city, $header_variant);

	if ($existing_page && $overwrite) {
		// Force: Komplett neuen Content verwenden (kein Merge)
		// Normal: Bestehenden Content mit neuem Template mergen (erhält customized Blöcke)
		$content = $force ? $new_content : parkourone_merge_page_content($existing_page->post_content, $new_content);

		// Bestehende Seite aktualisieren
		$page_id = wp_update_post([
			'ID' => $existing_page->ID,
			'post_content' => $content,
		]);
		// Meta-Daten aktualisieren
		update_post_meta($existing_page->ID, '_parkourone_city_slug', $city_slug);
		update_post_meta($existing_page->ID, '_parkourone_auto_generated', true);
	} else {
		// Neue Seite erstellen
		$page_id = wp_insert_post([
			'post_title' => "Parkour in {$city_name}",
			'post_name' => $city_slug,
			'post_content' => $new_content,
			'post_status' => 'publish',
			'post_type' => 'page',
			'meta_input' => [
				'_parkourone_city_slug' => $city_slug,
				'_parkourone_auto_generated' => true
			]
		]);
	}

	return $page_id && !is_wp_error($page_id);
}

function parkourone_generate_city_page_content($city_slug, $city, $header_variant = 'split') {
	$city_name = parkourone_get_city_display_name($city_slug);
	$location_slugs = array_column($city['locations'], 'slug');
	$location_filter = implode(',', $location_slugs);

	// Theme URI für Bilder
	$theme_uri = get_template_directory_uri();

	// Probetraining-Preis dynamisch
	$probetraining_price = parkourone_get_probetraining_price();

	// Probetraining-Steps für Steps-Carousel (JSON)
	$probetraining_steps = json_encode([
		["title" => "Standort wählen", "description" => "Wähle einen unserer Trainingsstandorte in {$city_name} aus, der für dich am besten erreichbar ist.", "icon" => "location"],
		["title" => "Klasse auswählen", "description" => "Wähle die passende Klasse basierend auf deiner Altersgruppe und deinem Erfahrungslevel.", "icon" => "users"],
		["title" => "Termin buchen", "description" => "Buche online deinen Wunschtermin. Das Probetraining kostet {$probetraining_price}.", "icon" => "calendar"],
		["title" => "Loslegen", "description" => "Nach dem Probetraining kannst du entscheiden, ob du Teil unserer Community werden möchtest.", "icon" => "check"]
	], JSON_UNESCAPED_UNICODE);

	// Header-Variante Attribute für Page-Header Block
	$header_variant = in_array($header_variant, ['centered', 'split', 'fullscreen']) ? $header_variant : 'split';

	// Stats für den Page Header (dynamisch basierend auf Standort-Anzahl)
	$location_count = count($city['locations']);
	$header_stats = json_encode([
		["number" => "15", "label" => "Jahre Erfahrung"],
		["number" => (string)$location_count, "label" => ($location_count === 1 ? "Standort" : "Standorte")],
		["number" => "500", "label" => "Aktive Mitglieder"]
	], JSON_UNESCAPED_UNICODE);

	// Einzelner Slider mit Age-Filter (statt mehrere Slider pro Altersgruppe)
	$sliders_content = "\n<!-- wp:parkourone/klassen-slider {\"headline\":\"Parkour Trainings in {$city_name}\",\"filterMode\":\"age\",\"filterLocation\":\"{$location_filter}\",\"buttonText\":\"Probetraining buchen\",\"hideIfEmpty\":true,\"align\":\"full\"} /-->\n";

	// SEO-optimierte Hero-Headline mit Keyword "Parkour [Stadt/Bezirk]"
	$hero_headline = "Parkour {$city_name} – Training in deiner Nähe";
	$hero_subtext = "Entdecke Parkour-Klassen für alle Altersgruppen in {$city_name}. Professionelle Coaches, sichere Trainingsumgebung und eine starke Community erwarten dich bei ParkourONE.";

	// Apple-Style Text-Reveal Text mit SEO-Keywords
	$text_reveal_text = "Parkour in {$city_name} – mehr als nur Sport. Bei ParkourONE findest du das passende Training für dein Level. Für Anfänger und Fortgeschrittene, von Kids bis Erwachsene. Lerne von erfahrenen Coaches die Grundlagen oder verfeinere deine Technik in einer sicheren Umgebung mit Gleichgesinnten.";

	/**
	 * Block-Reihenfolge für Standorte-Seiten:
	 * 1. Page Header
	 * 2. Steps-Carousel (Probetraining)
	 * 3. Klassen-Slider (nach Altersgruppe)
	 * 4. Testimonials
	 * 5. Warum Parkour + Text-Reveal
	 * 6. FAQ
	 * 7. About-Section
	 */
	$content = <<<BLOCKS
<!-- wp:parkourone/page-header {"variant":"{$header_variant}","title":"Parkour in {$city_name}","titleAccent":"Training in deiner Nähe","description":"{$hero_subtext}","ctaText":"Probetraining buchen","ctaUrl":"/probetraining-buchen/","ctaSecondaryText":"Standorte entdecken","ctaSecondaryUrl":"#standorte","stats":{$header_stats},"align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/steps-carousel {"headline":"So startest du mit Parkour in {$city_name}","subheadline":"In 4 einfachen Schritten zum ersten Training","steps":{$probetraining_steps},"ageCategory":"default","backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

{$sliders_content}
<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/testimonials-slider {"headline":"Das sagen Parkour-Teilnehmer aus {$city_name}","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/text-reveal {"text":"{$text_reveal_text}","textSize":"large","textAlign":"center"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"align":"full","sizeSlug":"full"} -->
<figure class="wp-block-image alignfull size-full"><img src="{$theme_uri}/assets/images/fallback/juniors/grosserpsrung.jpg" alt="Parkour Training in {$city_name}"/></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/faq {"headline":"Häufige Fragen zu Parkour in {$city_name}","category":"standort","backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/about-section {"subheadline":"WARUM PARKOUR?","headline":"Mehr als nur Sport","text":"Parkour in {$city_name} bei ParkourONE bedeutet nicht nur körperliches Training, sondern auch mentale Stärke, Selbstvertrauen und Community. Unsere Coaches begleiten dich auf deinem individuellen Weg – egal ob Anfänger oder Fortgeschrittene.","ctaText":"Mehr über ParkourONE","ctaUrl":"/team/","align":"full"} /-->
BLOCKS;

	return $content;
}

// =====================================================
// Ortschaft-Seite erstellen (für Single-City-Sites)
// =====================================================

function parkourone_create_location_page($location_slug, $overwrite = false, $header_variant = 'split', $force = false) {
	// Ortschaft-Term aus event_category holen
	$location = get_term_by('slug', $location_slug, 'event_category');
	if (!$location) return false;

	// Prüfen ob Seite bereits existiert
	$existing_page = get_page_by_path($location_slug);
	if ($existing_page && !$overwrite) {
		return false;
	}

	$location_name = $location->name;
	$site_location = parkourone_get_site_location();
	$site_name = $site_location['name'];

	// Block-Content für die Seite generieren
	$new_content = parkourone_generate_location_page_content($location_slug, $location_name, $header_variant);

	if ($existing_page && $overwrite) {
		$content = $force ? $new_content : parkourone_merge_page_content($existing_page->post_content, $new_content);

		$page_id = wp_update_post([
			'ID' => $existing_page->ID,
			'post_content' => $content,
		]);
		update_post_meta($existing_page->ID, '_parkourone_location_slug', $location_slug);
		update_post_meta($existing_page->ID, '_parkourone_auto_generated', true);
		update_post_meta($existing_page->ID, '_parkourone_site_location', $site_name);
	} else {
		$page_id = wp_insert_post([
			'post_title' => "Parkour in {$location_name}",
			'post_name' => $location_slug,
			'post_content' => $new_content,
			'post_status' => 'publish',
			'post_type' => 'page',
			'meta_input' => [
				'_parkourone_location_slug' => $location_slug,
				'_parkourone_auto_generated' => true,
				'_parkourone_site_location' => $site_name
			]
		]);
	}

	return $page_id && !is_wp_error($page_id);
}

function parkourone_generate_location_page_content($location_slug, $location_name, $header_variant = 'split') {
	$site_location = parkourone_get_site_location();
	$site_name = $site_location['name'];

	// Theme URI für Bilder
	$theme_uri = get_template_directory_uri();

	// Probetraining-Preis
	$probetraining_price = parkourone_get_probetraining_price();

	// Header-Variante validieren
	$header_variant = in_array($header_variant, ['centered', 'split', 'fullscreen']) ? $header_variant : 'split';

	// Probetraining-Steps
	$probetraining_steps = json_encode([
		["title" => "Klasse auswählen", "description" => "Wähle die passende Klasse basierend auf deiner Altersgruppe und deinem Erfahrungslevel.", "icon" => "users"],
		["title" => "Termin buchen", "description" => "Buche online deinen Wunschtermin. Das Probetraining kostet {$probetraining_price}.", "icon" => "calendar"],
		["title" => "Zur Halle kommen", "description" => "Komm in bequemer Sportkleidung zu unserer Halle in {$location_name}.", "icon" => "location"],
		["title" => "Loslegen", "description" => "Nach dem Probetraining kannst du entscheiden, ob du Teil unserer Community werden möchtest.", "icon" => "check"]
	], JSON_UNESCAPED_UNICODE);

	// Stats
	$header_stats = json_encode([
		["number" => "15", "label" => "Jahre Erfahrung"],
		["number" => "50", "label" => "Kurse/Woche"],
		["number" => "500", "label" => "Aktive Mitglieder"]
	], JSON_UNESCAPED_UNICODE);

	$content = <<<BLOCKS
<!-- wp:parkourone/page-header {"variant":"{$header_variant}","eyebrow":"Parkour in {$location_name}","headline":"Parkour Training in {$location_name}","subtext":"Entdecke professionelles Parkour-Training bei ParkourONE {$site_name}. Kurse für alle Altersgruppen an unserem Standort in {$location_name}.","showStats":true,"stats":{$header_stats},"backgroundType":"image","backgroundImage":"{$theme_uri}/assets/images/fallback/landscape/adults/1T2A6249.jpg","overlayOpacity":60,"align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/zielgruppen-grid {"headline":"Wähle deine Altersgruppe","subtext":"Finde den passenden Kurs für dich in {$location_name}","align":"wide"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/klassen-slider {"headline":"Parkour Kurse in {$location_name}","filterMode":"age","filterLocation":"{$location_slug}","buttonText":"Probetraining buchen","hideIfEmpty":true,"align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/steps-carousel {"headline":"So startest du in {$location_name}","subheadline":"In 4 einfachen Schritten zum ersten Training","steps":{$probetraining_steps},"backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/usp-slider {"headline":"Warum ParkourONE {$location_name}?","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/testimonial-slider {"filterMode":"location","filterValue":"{$location_slug}","headline":"Was unsere Schüler:innen sagen","autoplay":true,"interval":6000,"align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/faq {"headline":"Häufige Fragen zu {$location_name}","category":"standort","backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/about-section {"subheadline":"WARUM PARKOUR?","headline":"Mehr als nur Sport","text":"Parkour in {$location_name} bei ParkourONE {$site_name} bedeutet nicht nur körperliches Training, sondern auch mentale Stärke, Selbstvertrauen und Community. Unsere Coaches begleiten dich auf deinem individuellen Weg – egal ob Anfänger oder Fortgeschrittene.","ctaText":"Mehr über ParkourONE","ctaUrl":"/team/","align":"full"} /-->
BLOCKS;

	return $content;
}

// Altersgruppen in korrekter Reihenfolge abrufen
function parkourone_get_ordered_age_groups() {
	$alter_parent = get_term_by('slug', 'alter', 'event_category');
	if (!$alter_parent) return [];

	$terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $alter_parent->term_id,
		'hide_empty' => false
	]);

	if (is_wp_error($terms)) return [];

	// Reihenfolge definieren (klein nach gross)
	$order = ['minis', 'kids', 'juniors', 'adults', 'women', 'original'];

	$ordered = [];
	foreach ($order as $slug) {
		foreach ($terms as $term) {
			if ($term->slug === $slug) {
				$ordered[] = [
					'slug' => $term->slug,
					'name' => $term->name
				];
				break;
			}
		}
	}

	// Restliche Terms hinzufügen die nicht in der Order-Liste sind
	foreach ($terms as $term) {
		$found = false;
		foreach ($ordered as $o) {
			if ($o['slug'] === $term->slug) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			$ordered[] = [
				'slug' => $term->slug,
				'name' => $term->name
			];
		}
	}

	return $ordered;
}

// =====================================================
// Kategorie-Seite erstellen
// =====================================================

function parkourone_create_category_page($cat_slug, $overwrite = false, $header_variant = 'split', $force = false) {
	// Prüfen ob Seite bereits existiert
	$existing_page = get_page_by_path($cat_slug);
	if ($existing_page && !$overwrite) {
		return false;
	}

	// Site-Standort automatisch erkennen
	$site_location = parkourone_get_site_location();
	$site_name = $site_location['name'];

	$seo = parkourone_get_seo_content($cat_slug);
	$term = get_term_by('slug', $cat_slug, 'event_category');
	$display_name = $term ? $term->name : ucfirst($cat_slug);

	if (!$seo) {
		// Generischer Fallback
		$seo = [
			'title' => "Parkour {$display_name}",
			'hero_subtitle' => "Parkour-Training für {$display_name}",
			'intro_headline' => "Willkommen bei {$display_name}",
			'intro_text' => "Entdecke unsere Parkour-Trainings für {$display_name}.",
			'benefits' => ['Professionelles Training', 'Erfahrene Coaches', 'Kleine Gruppen'],
			'meta_description' => "Parkour für {$display_name}. Jetzt Probetraining buchen!"
		];
	}

	$new_content = parkourone_generate_category_page_content($cat_slug, $seo, $header_variant);

	// SEO-optimierter Seiten-Titel mit Standort
	$page_title = "Parkour {$display_name} {$site_name}";

	if ($existing_page && $overwrite) {
		// Force: Komplett neuen Content verwenden (kein Merge)
		// Normal: Bestehenden Content mit neuem Template mergen (erhält customized Blöcke)
		$content = $force ? $new_content : parkourone_merge_page_content($existing_page->post_content, $new_content);

		// Bestehende Seite aktualisieren
		$page_id = wp_update_post([
			'ID' => $existing_page->ID,
			'post_content' => $content,
		]);
		// Meta-Daten aktualisieren
		update_post_meta($existing_page->ID, '_parkourone_category_slug', $cat_slug);
		update_post_meta($existing_page->ID, '_parkourone_auto_generated', true);
		update_post_meta($existing_page->ID, '_parkourone_site_location', $site_name);
	} else {
		// Neue Seite erstellen
		$page_id = wp_insert_post([
			'post_title' => $page_title,
			'post_name' => $cat_slug,
			'post_content' => $new_content,
			'post_status' => 'publish',
			'post_type' => 'page',
			'meta_input' => [
				'_parkourone_category_slug' => $cat_slug,
				'_parkourone_auto_generated' => true,
				'_parkourone_site_location' => $site_name
			]
		]);
	}

	return $page_id && !is_wp_error($page_id);
}

function parkourone_generate_category_page_content($cat_slug, $seo, $header_variant = 'split') {
	// Site-Standort automatisch erkennen
	$site_location = parkourone_get_site_location();
	$site_name = $site_location['name'];

	// Theme URI für Bilder
	$theme_uri = get_template_directory_uri();

	// Header-Variante validieren
	$header_variant = in_array($header_variant, ['centered', 'split', 'fullscreen']) ? $header_variant : 'split';

	// Probetraining-Preis dynamisch
	$probetraining_price = parkourone_get_probetraining_price();

	// Probetraining-Steps für Steps-Carousel (JSON)
	$probetraining_steps = json_encode([
		["title" => "Standort wählen", "description" => "Wähle einen unserer Trainingsstandorte in {$site_name} aus, der für dich am besten erreichbar ist.", "icon" => "location"],
		["title" => "Klasse auswählen", "description" => "Wähle die passende Klasse basierend auf deiner Altersgruppe und deinem Erfahrungslevel.", "icon" => "users"],
		["title" => "Termin buchen", "description" => "Buche online deinen Wunschtermin. Das Probetraining kostet {$probetraining_price}.", "icon" => "calendar"],
		["title" => "Loslegen", "description" => "Nach dem Probetraining kannst du entscheiden, ob du Teil unserer Community werden möchtest.", "icon" => "check"]
	], JSON_UNESCAPED_UNICODE);

	// Alle Standorte abrufen (fuer Statistiken)
	$locations = parkourone_get_all_locations();

	// Einzelner Slider mit Location-Filter (statt mehrere Slider pro Standort)
	$term = get_term_by('slug', $cat_slug, 'event_category');
	$display_name = $term ? $term->name : ucfirst($cat_slug);

	$sliders_content = "\n<!-- wp:parkourone/klassen-slider {\"headline\":\"Parkour {$display_name} Trainings\",\"filterMode\":\"location\",\"filterAge\":\"{$cat_slug}\",\"buttonText\":\"Probetraining buchen\",\"hideIfEmpty\":true,\"align\":\"full\"} /-->\n";

	// SEO-optimierte Hero-Headline mit Keyword "Parkour [Altersgruppe] [Standort]"
	$hero_headline = "Parkour {$display_name} {$site_name} – Dein Training";
	$hero_subtext = str_replace(
		['Parkour', 'unseren', 'unsere'],
		["Parkour in {$site_name}", 'unseren', 'unsere'],
		$seo['intro_text']
	);

	// Age category für Fallback-Bilder
	$age_category_attr = in_array($cat_slug, ['kids', 'minis']) ? 'kids' : 'adults';

	// Stats für den Page Header
	$location_count = count($locations);
	$category_stats = json_encode([
		["number" => "15", "label" => "Jahre Erfahrung"],
		["number" => (string)$location_count, "label" => ($location_count === 1 ? "Standort" : "Standorte")],
		["number" => "98", "label" => "% Zufriedenheit"]
	], JSON_UNESCAPED_UNICODE);

	// Apple-Style Text-Reveal Text mit SEO-Keywords
	$text_reveal_text = "Parkour {$display_name} in {$site_name} bedeutet mehr als nur Sport. Es ist eine Reise zu dir selbst. Bei ParkourONE trainierst du in einer motivierenden Umgebung mit erfahrenen Coaches. Kleine Gruppen, individuelle Betreuung und eine starke Community warten auf dich.";

	$content = <<<BLOCKS
<!-- wp:parkourone/page-header {"variant":"{$header_variant}","title":"Parkour {$display_name}","titleAccent":"{$site_name}","description":"{$hero_subtext}","ctaText":"Probetraining buchen","ctaUrl":"/probetraining-buchen/","ctaSecondaryText":"Mehr erfahren","ctaSecondaryUrl":"#kurse","stats":{$category_stats},"align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/steps-carousel {"headline":"Parkour {$display_name} Probetraining in {$site_name}","subheadline":"In 4 einfachen Schritten zum ersten Training","steps":{$probetraining_steps},"ageCategory":"{$cat_slug}","backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

{$sliders_content}
<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/testimonials-slider {"headline":"Das sagen Parkour {$display_name} Teilnehmer in {$site_name}","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/text-reveal {"text":"{$text_reveal_text}","textSize":"large","textAlign":"center"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"align":"full","sizeSlug":"full"} -->
<figure class="wp-block-image alignfull size-full"><img src="{$theme_uri}/assets/images/fallback/juniors/grosserpsrung.jpg" alt="Parkour {$display_name} Training in {$site_name}"/></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/faq {"headline":"Häufige Fragen zu Parkour {$display_name}","category":"{$cat_slug}","backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/about-section {"subheadline":"PARKOUR {$display_name} {$site_name}","headline":"Training bei ParkourONE","text":"Bei ParkourONE {$site_name} glauben wir an das Recht auf persönliches Wohlbefinden und die Kraft der Gemeinschaft. Unter dem Motto 'ONE for all – all for ONE' begleiten wir dich auf deinem Parkour-Weg. Unsere {$display_name}-Trainings in {$site_name} sind darauf ausgelegt, dich zu inspirieren, zu fördern und herauszufordern.","ctaText":"Mehr über uns","ctaUrl":"/ueber-uns/","align":"full"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/promo-banner {"headline":"Jetzt Parkour {$display_name} in {$site_name} starten!","subtext":"Buche dein erstes Probetraining und erlebe Parkour {$display_name} hautnah. Keine Vorkenntnisse nötig.","buttonText":"Probetraining buchen","buttonUrl":"/probetraining-buchen/","align":"full"} /-->
BLOCKS;

	return $content;
}

// Alle Standorte abrufen
function parkourone_get_all_locations() {
	$ortschaft_parent = get_term_by('slug', 'ortschaft', 'event_category');
	if (!$ortschaft_parent) return [];

	$terms = get_terms([
		'taxonomy' => 'event_category',
		'parent' => $ortschaft_parent->term_id,
		'hide_empty' => false,
		'orderby' => 'name',
		'order' => 'ASC'
	]);

	if (is_wp_error($terms)) return [];

	$locations = [];
	foreach ($terms as $term) {
		$locations[] = [
			'slug' => $term->slug,
			'name' => $term->name
		];
	}

	return $locations;
}

// =====================================================
// Schema.org Structured Data für SEO
// =====================================================

function parkourone_add_structured_data() {
	if (!is_page()) return;

	global $post;
	$is_auto_page = get_post_meta($post->ID, '_parkourone_auto_generated', true);
	if (!$is_auto_page) return;

	$city_slug = get_post_meta($post->ID, '_parkourone_city_slug', true);
	$cat_slug = get_post_meta($post->ID, '_parkourone_category_slug', true);

	$schema = [
		'@context' => 'https://schema.org',
		'@type' => 'SportsActivityLocation',
		'name' => get_the_title(),
		'description' => get_the_excerpt(),
		'url' => get_permalink(),
		'sport' => 'Parkour',
		'provider' => [
			'@type' => 'Organization',
			'name' => 'ParkourONE',
			'url' => home_url()
		]
	];

	if ($city_slug) {
		$schema['address'] = [
			'@type' => 'PostalAddress',
			'addressLocality' => parkourone_get_city_display_name($city_slug)
		];
	}

	echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'parkourone_add_structured_data');

// =====================================================
// Taxonomy Archive Structured Data
// =====================================================

function parkourone_taxonomy_structured_data($term, $seo_content, $events) {
	$schema = [
		'@context' => 'https://schema.org',
		'@type' => 'CollectionPage',
		'name' => $seo_content['title'],
		'description' => $seo_content['meta_description'] ?? $seo_content['intro_text'],
		'url' => get_term_link($term),
		'isPartOf' => [
			'@type' => 'WebSite',
			'name' => 'ParkourONE',
			'url' => home_url()
		],
		'about' => [
			'@type' => 'SportsActivityLocation',
			'name' => $seo_content['title'],
			'sport' => 'Parkour'
		]
	];

	// Add events as items
	if (!empty($events)) {
		$schema['mainEntity'] = [
			'@type' => 'ItemList',
			'numberOfItems' => count($events),
			'itemListElement' => []
		];

		foreach ($events as $index => $event) {
			$event_id = $event->ID;
			$schema['mainEntity']['itemListElement'][] = [
				'@type' => 'ListItem',
				'position' => $index + 1,
				'item' => [
					'@type' => 'Course',
					'name' => $event->post_title,
					'provider' => [
						'@type' => 'Organization',
						'name' => 'ParkourONE'
					]
				]
			];
		}
	}

	echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

// =====================================================
// Enqueue Taxonomy Archive Styles
// =====================================================

function parkourone_enqueue_taxonomy_styles() {
	if (is_tax('event_category')) {
		wp_enqueue_style(
			'parkourone-taxonomy-archive',
			get_template_directory_uri() . '/assets/css/taxonomy-archive.css',
			[],
			filemtime(get_template_directory() . '/assets/css/taxonomy-archive.css')
		);

		// Also enqueue testimonials styles
		wp_enqueue_style(
			'parkourone-testimonials',
			get_template_directory_uri() . '/blocks/testimonials-slider/style.css',
			[],
			filemtime(get_template_directory() . '/blocks/testimonials-slider/style.css')
		);

		// Enqueue testimonials JS
		wp_enqueue_script(
			'parkourone-testimonials-view',
			get_template_directory_uri() . '/blocks/testimonials-slider/view.js',
			[],
			filemtime(get_template_directory() . '/blocks/testimonials-slider/view.js'),
			true
		);
	}
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_taxonomy_styles');

// =====================================================
// Native SEO Meta Tags (ohne Yoast)
// =====================================================

/**
 * Generiert SEO Meta-Daten für Auto-Seiten
 */
function parkourone_get_page_seo_meta($post_id) {
	$city_slug = get_post_meta($post_id, '_parkourone_city_slug', true);
	$cat_slug = get_post_meta($post_id, '_parkourone_category_slug', true);
	$site_location = parkourone_get_site_location();
	$site_name = $site_location['name'];

	$meta = [
		'title' => '',
		'description' => '',
		'keywords' => '',
		'og_image' => ''
	];

	// Stadt-Seite
	if ($city_slug) {
		$city_name = parkourone_get_city_display_name($city_slug);
		$meta['title'] = "Parkour {$city_name} – Training & Probetraining | ParkourONE";
		$meta['description'] = "Parkour in {$city_name}: Professionelles Training für Kids, Jugendliche & Erwachsene. Jetzt Probetraining buchen bei ParkourONE {$city_name}!";
		$meta['keywords'] = "Parkour {$city_name}, Parkour Training {$city_name}, Parkour lernen {$city_name}";
	}

	// Zielgruppen-Seite
	if ($cat_slug) {
		$term = get_term_by('slug', $cat_slug, 'event_category');
		$display_name = $term ? $term->name : ucfirst($cat_slug);
		$seo = parkourone_get_seo_content($cat_slug);

		$meta['title'] = "Parkour {$display_name} {$site_name} – Training | ParkourONE";
		$meta['description'] = $seo['meta_description'] ?? "Parkour {$display_name} in {$site_name}. Professionelles Training, erfahrene Coaches, sichere Umgebung. Jetzt Probetraining buchen!";
		$meta['keywords'] = "Parkour {$display_name}, Parkour {$display_name} {$site_name}, {$display_name} Parkour Training";
	}

	// Template-Seiten (Startseite, Kurse, Team)
	$template_slug = get_post_meta($post_id, '_parkourone_template_slug', true);
	if ($template_slug && empty($meta['title'])) {
		$template_meta = [
			'startseite' => [
				'title' => "Parkour {$site_name} – Kurse für Kids, Jugendliche & Erwachsene | ParkourONE",
				'description' => "ParkourONE {$site_name}: Professionelles Parkour-Training für alle Altersgruppen. TRUST-zertifizierte Coaches, sichere Umgebung. Jetzt Probetraining buchen!",
				'keywords' => "Parkour {$site_name}, Parkour Training, Parkour lernen, ParkourONE"
			],
			'kurse-workshops' => [
				'title' => "Parkour Kurse & Workshops {$site_name} | ParkourONE",
				'description' => "Parkour Workshops, Ferienkurse & Events in {$site_name}. Für Anfänger & Fortgeschrittene, Kinder & Erwachsene. Jetzt Kurs buchen!",
				'keywords' => "Parkour Workshop {$site_name}, Parkour Ferienkurs, Parkour Event, Parkour Kurs buchen"
			],
			'team' => [
				'title' => "Über ParkourONE {$site_name} – Team & TRUST Education",
				'description' => "Lerne das ParkourONE {$site_name} Team kennen. TRUST-zertifizierte Coaches mit jahrelanger Erfahrung. Entdecke unsere Philosophie und Werte.",
				'keywords' => "ParkourONE Team, TRUST Education, Parkour Coaches {$site_name}, Roger Widmer"
			]
		];

		if (isset($template_meta[$template_slug])) {
			$meta = array_merge($meta, $template_meta[$template_slug]);
		}
	}

	// Fallback für andere Seiten
	if (empty($meta['title'])) {
		$meta['title'] = get_the_title($post_id) . ' | ParkourONE ' . $site_name;
	}

	return $meta;
}

/**
 * Meta Title anpassen (WordPress native)
 */
function parkourone_seo_title($title_parts) {
	if (!is_page()) return $title_parts;

	global $post;
	if (!$post) return $title_parts;

	$is_auto_page = get_post_meta($post->ID, '_parkourone_auto_generated', true);
	$is_template_page = get_post_meta($post->ID, '_parkourone_template_page', true);

	// Nur für Auto-Seiten oder Template-Seiten
	if (!$is_auto_page && !$is_template_page) return $title_parts;

	$meta = parkourone_get_page_seo_meta($post->ID);

	if (!empty($meta['title'])) {
		// Kompletten Title überschreiben
		$title_parts['title'] = $meta['title'];
		// Site-Name entfernen (ist bereits im Title)
		unset($title_parts['site']);
		unset($title_parts['tagline']);
	}

	return $title_parts;
}
add_filter('document_title_parts', 'parkourone_seo_title');

/**
 * Meta Description & Open Graph Tags ausgeben
 */
function parkourone_output_seo_meta_tags() {
	if (!is_page()) return;

	global $post;
	if (!$post) return;

	$is_auto_page = get_post_meta($post->ID, '_parkourone_auto_generated', true);
	$is_template_page = get_post_meta($post->ID, '_parkourone_template_page', true);

	// Nur für Auto-Seiten oder Template-Seiten
	if (!$is_auto_page && !$is_template_page) return;

	$meta = parkourone_get_page_seo_meta($post->ID);
	$site_location = parkourone_get_site_location();

	// Fallback Description
	if (empty($meta['description'])) {
		$meta['description'] = "Parkour Training bei ParkourONE {$site_location['name']}. Professionelle Kurse für alle Altersgruppen. Jetzt Probetraining buchen!";
	}

	// Featured Image oder Fallback für OG Image
	$og_image = '';
	if (has_post_thumbnail($post->ID)) {
		$og_image = get_the_post_thumbnail_url($post->ID, 'large');
	} else {
		// Fallback: Theme-Logo oder Standard-Bild
		$og_image = get_template_directory_uri() . '/assets/images/parkourone-og-default.jpg';
	}

	// Canonical URL
	$canonical = get_permalink($post->ID);

	// Meta Tags ausgeben
	echo "\n<!-- ParkourONE SEO Meta Tags -->\n";

	// Basic Meta
	echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";

	if (!empty($meta['keywords'])) {
		echo '<meta name="keywords" content="' . esc_attr($meta['keywords']) . '">' . "\n";
	}

	// Canonical
	echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";

	// Open Graph (Facebook, LinkedIn, etc.)
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($meta['title'] ?: get_the_title()) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
	echo '<meta property="og:site_name" content="ParkourONE ' . esc_attr($site_location['name']) . '">' . "\n";
	echo '<meta property="og:locale" content="de_DE">' . "\n";

	if ($og_image) {
		echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";
	}

	// Twitter Card
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($meta['title'] ?: get_the_title()) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";

	if ($og_image) {
		echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
	}

	echo "<!-- /ParkourONE SEO Meta Tags -->\n\n";
}
add_action('wp_head', 'parkourone_output_seo_meta_tags', 1);

/**
 * Robots Meta Tag für Auto-Seiten
 */
function parkourone_robots_meta() {
	if (!is_page()) return;

	global $post;
	if (!$post) return;

	$is_auto_page = get_post_meta($post->ID, '_parkourone_auto_generated', true);
	if (!$is_auto_page) return;

	// Auto-Seiten sollen indexiert werden
	echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
}
add_action('wp_head', 'parkourone_robots_meta', 2);
