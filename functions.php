<?php
defined('ABSPATH') || exit;

// Includes
require_once get_template_directory() . '/inc/angebote-cpt.php';
require_once get_template_directory() . '/inc/testimonials-cpt.php';
require_once get_template_directory() . '/inc/faq-cpt.php';
require_once get_template_directory() . '/inc/auto-pages.php';
require_once get_template_directory() . '/inc/event-images.php';

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
 * Admin-Seite: Menü-Vorschau
 */
function parkourone_menu_preview_page() {
    add_submenu_page(
        'themes.php',
        'Menü-Vorschau',
        'Menü-Vorschau',
        'edit_theme_options',
        'po-menu-preview',
        'parkourone_menu_preview_render'
    );
}
add_action('admin_menu', 'parkourone_menu_preview_page');

function parkourone_menu_preview_render() {
    ?>
    <div class="wrap">
        <h1>Menü-Vorschau</h1>
        <p>So sieht das Hauptmenü im Frontend aus:</p>

        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 40px; margin-top: 20px; max-width: 1000px;">
            <style>
                .po-preview .po-menu__columns {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 3rem;
                }
                .po-preview .po-menu__column {
                    min-width: 180px;
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
                .po-preview .po-menu__column:nth-child(1)::before {
                    content: 'Spalte 1 (automatisch)';
                }
                .po-preview .po-menu__column:nth-child(2)::before {
                    content: 'Spalte 2 (automatisch)';
                }
                .po-preview .po-menu__column--manual::before {
                    content: 'Spalte 3 (manuell)' !important;
                    color: #2271b1;
                }
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
                .po-preview .po-menu__empty {
                    color: #666;
                }
            </style>
            <div class="po-preview">
                <?php echo parkourone_render_main_menu(); ?>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-radius: 4px; max-width: 1000px;">
            <strong>Hinweis:</strong> Spalte 1 und 2 werden automatisch aus den Event-Kategorien generiert.
            Spalte 3 kannst du unter <a href="<?php echo admin_url('nav-menus.php'); ?>">Design → Menüs</a> bearbeiten.
        </div>

        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url('nav-menus.php'); ?>" class="button button-primary">Spalte 3 bearbeiten</a>
        </p>
    </div>
    <?php
}

/**
 * Rendert das Hauptmenü für das Fullscreen Overlay
 *
 * Spalte 1: Stundenplan (fix) + Altersgruppen (dynamisch)
 * Spalte 2: Standorte (dynamisch)
 * Spalte 3: Manuelles Menü aus WordPress (Über uns, Kontakt, etc.)
 */
function parkourone_render_main_menu() {
    $output = '<nav class="po-menu__columns">';

    // ========================================
    // SPALTE 1: Stundenplan + Altersgruppen
    // ========================================
    $output .= '<div class="po-menu__column">';
    $output .= '<ul class="po-menu__list">';

    // Stundenplan immer oben
    $output .= '<li class="po-menu__item">';
    $output .= '<a href="' . esc_url(home_url('/stundenplan/')) . '" class="po-menu__link po-menu__link--highlight">Stundenplan</a>';
    $output .= '</li>';

    // Altersgruppen dynamisch aus event_category
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
                // Schöner Name: "kids" → "Parkour für Kids"
                $display_name = parkourone_get_age_display_name($gruppe->slug, $gruppe->name);
                $url = home_url('/parkour-' . $gruppe->slug . '/');

                $output .= '<li class="po-menu__item">';
                $output .= '<a href="' . esc_url($url) . '" class="po-menu__link">' . esc_html($display_name) . '</a>';
                $output .= '</li>';
            }
        }
    }

    $output .= '</ul>';
    $output .= '</div>';

    // ========================================
    // SPALTE 2: Standorte
    // ========================================
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
            $output .= '<div class="po-menu__column">';
            $output .= '<ul class="po-menu__list">';

            foreach ($standorte as $standort) {
                $url = home_url('/standort-' . $standort->slug . '/');

                $output .= '<li class="po-menu__item">';
                $output .= '<a href="' . esc_url($url) . '" class="po-menu__link">' . esc_html($standort->name) . '</a>';
                $output .= '</li>';
            }

            $output .= '</ul>';
            $output .= '</div>';
        }
    }

    // ========================================
    // SPALTE 3: Manuelles WordPress Menü
    // ========================================
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
 */
function parkourone_render_manual_menu_column() {
    // Prüfen ob manuelles Menü existiert
    if (!has_nav_menu('main-menu')) {
        // Fallback: Standard-Links
        return parkourone_render_default_menu_column();
    }

    $menu_locations = get_nav_menu_locations();
    $menu_id = $menu_locations['main-menu'] ?? 0;

    if (!$menu_id) {
        return parkourone_render_default_menu_column();
    }

    $menu_items = wp_get_nav_menu_items($menu_id);

    if (!$menu_items || empty($menu_items)) {
        return parkourone_render_default_menu_column();
    }

    $output = '<div class="po-menu__column po-menu__column--manual">';
    $output .= '<ul class="po-menu__list">';

    foreach ($menu_items as $item) {
        // Nur Top-Level Items (keine verschachtelten)
        if ((int) $item->menu_item_parent === 0) {
            $output .= '<li class="po-menu__item">';
            $output .= '<a href="' . esc_url($item->url) . '" class="po-menu__link">' . esc_html($item->title) . '</a>';
            $output .= '</li>';
        }
    }

    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}

/**
 * Fallback Menü-Spalte wenn kein manuelles Menü existiert
 */
function parkourone_render_default_menu_column() {
    $output = '<div class="po-menu__column po-menu__column--manual">';
    $output .= '<ul class="po-menu__list">';

    $default_links = [
        'Über uns' => '/ueber-uns/',
        'Angebote' => '/angebote/',
        'Kontakt' => '/kontakt/',
    ];

    foreach ($default_links as $title => $path) {
        $output .= '<li class="po-menu__item">';
        $output .= '<a href="' . esc_url(home_url($path)) . '" class="po-menu__link">' . esc_html($title) . '</a>';
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

        // FAQ & Footer
        'parkourone/faq',
        'parkourone/footer',

        // Basis Blöcke für Schulleiter (Ticket #2)
        'parkourone/po-text',
        'parkourone/po-image',
        'parkourone/po-icon',
        'parkourone/po-columns',

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
        // Ticket #2: Basis Building Blocks für Schulleiter
        'po-text',
        'po-image',
        'po-icon',
        'po-columns'
    ];

    foreach ($blocks as $block) {
        $block_folder = $blocks_dir . $block;

        if (file_exists($block_folder . '/block.json')) {
            register_block_type($block_folder);
        }
    }
}
add_action('init', 'parkourone_register_blocks');

function parkourone_enqueue_swiper() {
    wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
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
}
add_action('wp_enqueue_scripts', 'parkourone_enqueue_theme_styles');

/**
 * Rendert den Custom Header mit Fullscreen-Menü
 */
function parkourone_render_header() {
    $logo_id = get_theme_mod('custom_logo');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    $site_name = get_bloginfo('name');
    $home_url = home_url('/');
    $probetraining_url = home_url('/probetraining/');

    ?>
    <header class="po-header" id="po-header">
        <div class="po-header__inner">
            <a href="<?php echo esc_url($home_url); ?>" class="po-header__logo">
                <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="po-header__logo-img">
                <?php else : ?>
                    <span class="po-header__logo-text"><?php echo esc_html($site_name); ?></span>
                <?php endif; ?>
            </a>

            <div class="po-header__actions">
                <a href="<?php echo esc_url($probetraining_url); ?>" class="po-header__cta">Probetraining</a>
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

            // Bild über zentrale Funktion holen
            $event_image = function_exists('parkourone_get_event_image')
                ? parkourone_get_event_image($event_id, $age_term)
                : get_the_post_thumbnail_url($event_id, 'medium_large');

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
        // Ticket #2: Basis Building Blocks für Schulleiter
        'po-text',
        'po-image',
        'po-icon',
        'po-columns'
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
    ];

    foreach ($view_script_blocks as $folder) {
        $view_file = $blocks_dir . $folder . '/view.js';
        if (file_exists($view_file)) {
            wp_enqueue_script(
                'parkourone-' . $folder . '-view',
                $blocks_url . $folder . '/view.js',
                [],
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
        
        if ($formatted_date >= $today && $stock_int > 0 && $stock_status === 'instock') {
            $available_dates[] = [
                'product_id' => $product_id,
                'date' => $event_date,
                'date_formatted' => date_i18n('l, j. F Y', strtotime($formatted_date)),
                'stock' => $stock_int
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
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('po_booking_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'parkourone_booking_scripts');

function parkourone_ajax_add_to_cart() {
    check_ajax_referer('po_booking_nonce', 'nonce');
    
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce nicht aktiv']);
    }
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
    $vorname = isset($_POST['vorname']) ? sanitize_text_field($_POST['vorname']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $geburtsdatum = isset($_POST['geburtsdatum']) ? sanitize_text_field($_POST['geburtsdatum']) : '';
    
    if (!$product_id || !$vorname || !$name || !$geburtsdatum) {
        wp_send_json_error(['message' => 'Bitte alle Felder ausfüllen']);
    }
    
    $_POST['event_id'] = $event_id;
    $_POST['event_participant_name'] = [$name];
    $_POST['event_participant_vorname'] = [$vorname];
    $_POST['event_participant_geburtsdatum'] = [$geburtsdatum];
    
    $added = WC()->cart->add_to_cart($product_id, 1);
    
    if ($added) {
        wp_send_json_success([
            'message' => 'Erfolgreich zum Warenkorb hinzugefügt',
            'cart_count' => WC()->cart->get_cart_contents_count()
        ]);
    } else {
        wp_send_json_error(['message' => 'Fehler beim Hinzufügen zum Warenkorb']);
    }
}
add_action('wp_ajax_po_add_to_cart', 'parkourone_ajax_add_to_cart');
add_action('wp_ajax_nopriv_po_add_to_cart', 'parkourone_ajax_add_to_cart');

function parkourone_register_coach_cpt() {
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
		'rewrite' => false
	]);
}
add_action('init', 'parkourone_register_coach_cpt');

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
	
	echo '<p><strong>Quelle:</strong> ' . ($source === 'manual' ? 'Manuell erstellt' : 'API (Academyboard)') . '</p>';
	
	if ($api_image) {
		echo '<p><strong>API-Profilbild:</strong></p>';
		echo '<img src="' . esc_url($api_image) . '" style="max-width:100%;height:auto;border-radius:8px;">';
	}
}

function parkourone_coach_profile_metabox($post) {
	wp_nonce_field('parkourone_coach_save', 'parkourone_coach_nonce');
	
	$fields = [
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
		$name = get_post_meta($event->ID, '_event_headcoach', true);
		$image = get_post_meta($event->ID, '_event_headcoach_image_url', true);
		$email = get_post_meta($event->ID, '_event_headcoach_email', true);
		
		if (!empty($name) && !isset($api_coaches[$name])) {
			$api_coaches[$name] = [
				'image' => $image,
				'email' => $email
			];
		}
	}
	
	foreach ($api_coaches as $name => $data) {
		$existing = get_posts([
			'post_type' => 'coach',
			'title' => $name,
			'posts_per_page' => 1,
			'post_status' => ['publish', 'draft']
		]);
		
		if (empty($existing)) {
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
			$coach_id = $existing[0]->ID;
			update_post_meta($coach_id, '_coach_api_image', $data['image']);
			if (!empty($data['email']) && empty(get_post_meta($coach_id, '_coach_email', true))) {
				update_post_meta($coach_id, '_coach_email', $data['email']);
			}
			
			if ($existing[0]->post_status === 'draft') {
				wp_update_post([
					'ID' => $coach_id,
					'post_status' => 'publish'
				]);
			}
		}
	}
	
	$all_coaches = get_posts([
		'post_type' => 'coach',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	]);
	
	foreach ($all_coaches as $coach) {
		$source = get_post_meta($coach->ID, '_coach_source', true);
		
		if ($source === 'api' && !isset($api_coaches[$coach->post_title])) {
			wp_update_post([
				'ID' => $coach->ID,
				'post_status' => 'draft'
			]);
		}
	}
}

function parkourone_sync_coaches_on_admin_load($screen) {
	if ($screen->post_type === 'coach' && $screen->base === 'edit') {
		parkourone_sync_coaches_from_events();
	}
}
add_action('current_screen', 'parkourone_sync_coaches_on_admin_load');

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
