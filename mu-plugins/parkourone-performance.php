<?php
/**
 * ParkourONE Performance Optimierung
 *
 * MU-Plugin: Laeuft vor allen anderen Plugins und Themes.
 * Entfernt unnoetige Scripts/Styles, bereinigt den HTML-Head
 * und optimiert die Ladezeit.
 *
 * Wird automatisch vom Theme installiert.
 * Version wird geprueft — bei Theme-Update wird die Datei aktualisiert.
 *
 * @package ParkourONE
 * @since 1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

define('PO_PERFORMANCE_VERSION', '1.0.0');


// ==========================================================================
// 1. WORDPRESS HEAD AUFRÄUMEN
//    Entfernt Meta-Tags und Links die kein Besucher braucht.
// ==========================================================================

// WordPress-Version aus dem HTML entfernen (Sicherheit)
remove_action('wp_head', 'wp_generator');

// Windows Live Writer Manifest (veraltet)
remove_action('wp_head', 'wlwmanifest_link');

// Really Simple Discovery / XML-RPC Endpoint
remove_action('wp_head', 'rsd_link');

// Shortlink <link rel="shortlink">
remove_action('wp_head', 'wp_shortlink_wp_head');

// REST API Link im Head (API ist trotzdem erreichbar)
remove_action('wp_head', 'rest_output_link_wp_head');

// oEmbed Discovery Links
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');

// RSS Feed Links (kein Blog, keine Feeds noetig)
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

// Angrenzende Posts (rel="prev/next") - nicht noetig
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

// WordPress Emoji Scripts und Styles entfernen
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

// Emoji aus TinyMCE entfernen
add_filter('tiny_mce_plugins', function ($plugins) {
	return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : $plugins;
});

// Emoji DNS-Prefetch entfernen
add_filter('wp_resource_hints', function ($hints, $relation_type) {
	if ($relation_type === 'dns-prefetch') {
		$hints = array_filter($hints, function ($hint) {
			return strpos($hint, 's.w.org') === false;
		});
	}
	return $hints;
}, 10, 2);


// ==========================================================================
// 2. UNNOETIGE SCRIPTS & STYLES ENTFERNEN
//    Plugins laden oft den gesamten Block-Editor im Frontend.
// ==========================================================================

add_action('wp_enqueue_scripts', function () {
	if (is_admin()) {
		return;
	}

	// -------------------------------------------------------
	// WordPress Block-Editor Scripts (nur im Admin noetig)
	// Spart ca. 700 KiB
	// -------------------------------------------------------
	$editor_scripts = [
		'wp-blocks',
		'wp-block-editor',
		'wp-editor',
		'wp-edit-post',
		'wp-block-library',
		'wp-block-serialization-default-parser',
		'wp-server-side-render',
		'wp-rich-text',
		'wp-components',
		'wp-compose',
		'wp-core-data',
		'wp-data',
		'wp-notices',
		'wp-patterns',
		'wp-preferences',
		'wp-preferences-persistence',
		'wp-commands',
		'wp-viewport',
		'wp-keyboard-shortcuts',
		'wp-media-utils',
		'wp-style-engine',
		'wp-plugins',
		'wp-private-apis',
		'wp-wordcount',
		'wp-autop',
		'wp-shortcode',
		'wp-token-list',
		'wp-blob',
		'wp-html-entities',
		'wp-is-shallow-equal',
		'wp-deprecated',
		'wp-warning',
		'wp-priority-queue',
		'wp-redux-routine',
		'wp-date',
	];

	foreach ($editor_scripts as $handle) {
		wp_dequeue_script($handle);
		wp_deregister_script($handle);
	}

	// React/ReactDOM (nur fuer Block-Editor noetig)
	wp_dequeue_script('react');
	wp_dequeue_script('react-dom');
	wp_dequeue_script('react-jsx-runtime');
	wp_deregister_script('react');
	wp_deregister_script('react-dom');
	wp_deregister_script('react-jsx-runtime');

	// Moment.js (18 KiB, Dependency von wp-date)
	wp_dequeue_script('moment');
	wp_deregister_script('moment');

	// Editor Styles
	wp_dequeue_style('wp-block-editor');
	wp_dequeue_style('wp-editor');
	wp_dequeue_style('wp-edit-post');
	wp_dequeue_style('wp-components');
	wp_dequeue_style('wp-commands');

	// -------------------------------------------------------
	// Easy Timetable Plugin
	// Theme hat eigenen Stundenplan-Block, Plugin unnoetig
	// Spart ca. 100+ KiB
	// -------------------------------------------------------
	$easy_timetable = [
		'html2canvas', 'html2canvas-svg', 'html2canvas-js', 'html2canvas-svg-js',
		'tooltipster', 'tooltipster-bundle',
		'jquery-injectcss',
		'easy-timetable-public',
	];
	foreach ($easy_timetable as $handle) {
		wp_dequeue_script($handle);
	}

	$easy_timetable_styles = [
		'easy-timetable-public', 'easy-timetable',
		'tooltipster', 'tooltipster-bundle', 'tooltipster-shadow', 'tooltipster-borderless',
		'uikit', 'uikit-min',
	];
	foreach ($easy_timetable_styles as $handle) {
		wp_dequeue_style($handle);
	}

	// -------------------------------------------------------
	// Usercentrics (uc-block)
	// Theme hat eigenes Consent-System
	// -------------------------------------------------------
	wp_dequeue_script('uc-block');
	wp_dequeue_style('uc-block');
	wp_dequeue_script('usercentrics');
	wp_dequeue_style('usercentrics');

	// -------------------------------------------------------
	// MailerLite CSS nur laden wenn Shortcode auf der Seite
	// -------------------------------------------------------
	$content = get_the_content() ?? '';
	if (!has_shortcode($content, 'mailerlite_form')) {
		wp_dequeue_style('mailerlite-forms');
		wp_dequeue_style('mailerlite_forms');
	}

	// -------------------------------------------------------
	// WordPress Embed Script (oEmbed)
	// Nur laden wenn tatsaechlich Embeds vorhanden
	// -------------------------------------------------------
	if (!has_shortcode($content, 'embed')) {
		wp_dequeue_script('wp-embed');
	}

}, 999);


// ==========================================================================
// 3. JQUERY MIGRATE ENTFERNEN
//    Kompatibilitaetsschicht fuer veraltete jQuery-APIs (5 KiB)
// ==========================================================================

add_action('wp_default_scripts', function ($scripts) {
	if (is_admin()) {
		return;
	}

	if (isset($scripts->registered['jquery'])) {
		$scripts->registered['jquery']->deps = array_diff(
			$scripts->registered['jquery']->deps,
			['jquery-migrate']
		);
	}
});


// ==========================================================================
// 4. HEARTBEAT API AUF FRONTEND DEAKTIVIEREN
//    Heartbeat pollt alle 15-60 Sekunden — nur im Admin noetig
// ==========================================================================

add_action('init', function () {
	if (!is_admin()) {
		wp_deregister_script('heartbeat');
	}
});


// ==========================================================================
// 5. GLOBALE STYLES / SVG OPTIMIEREN
//    WordPress gibt grosse Inline-SVGs fuer Duotone-Filter aus
// ==========================================================================

remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');


// ==========================================================================
// 6. SELF-PINGBACKS DEAKTIVIEREN
//    WordPress pingt sich selbst an bei internen Links
// ==========================================================================

add_action('pre_ping', function (&$links) {
	$home = home_url();
	foreach ($links as $i => $link) {
		if (strpos($link, $home) === 0) {
			unset($links[$i]);
		}
	}
});
