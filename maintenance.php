<?php
/**
 * Maintenance Page with integrated Probetraining booking
 * Included via template_redirect — WordPress is fully loaded.
 */

// ===== Event-Daten für Buchungsbereich laden =====
$mnt_events = [];
$mnt_modal_data = [];
$mnt_has_wc = class_exists('WooCommerce');

if ($mnt_has_wc) {
	$mnt_age_colors = [
		'minis' => '#ff9500', 'kids' => '#34c759', 'juniors' => '#007aff',
		'adults' => '#5856d6', 'seniors' => '#af52de', 'masters' => '#ff2d55', 'women' => '#ff375f'
	];
	$mnt_mood_texts = [
		'minis'   => 'Erste Bewegungserfahrungen in spielerischer Atmosphäre.',
		'kids'    => 'Spielerisch Bewegungstalente entdecken: Klettern, Springen, Balancieren.',
		'juniors' => 'Von den Basics bis zu fortgeschrittenen Moves.',
		'adults'  => 'Den eigenen Körper neu entdecken und Techniken verfeinern.',
		'women'   => 'In entspannter Atmosphäre unter Frauen trainieren.',
		'seniors' => 'Koordination erhalten, Fitness aufbauen, beweglich bleiben.',
		'masters' => 'Erfahrung trifft Bewegung — aktiv bleiben in jedem Alter.',
	];
	$mnt_month_names = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
	$mnt_day_short = ['So','Mo','Di','Mi','Do','Fr','Sa'];

	$mnt_format_date = function($dk) use ($mnt_day_short, $mnt_month_names) {
		$ts = strtotime($dk);
		$today = date('Y-m-d');
		$tomorrow = date('Y-m-d', strtotime('+1 day'));
		$prefix = ($dk === $today) ? 'Heute, ' : (($dk === $tomorrow) ? 'Morgen, ' : '');
		return $prefix . $mnt_day_short[date('w', $ts)] . '. ' . date('j', $ts) . '. ' . $mnt_month_names[date('n', $ts) - 1];
	};

	$mnt_query = new WP_Query([
		'post_type' => 'event',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	]);

	$mnt_today = strtotime('today');

	if ($mnt_query->have_posts()) {
		while ($mnt_query->have_posts()) {
			$mnt_query->the_post();
			$eid = get_the_ID();
			$edates = get_post_meta($eid, '_event_dates', true);
			if (!is_array($edates) || empty($edates)) continue;

			$st = get_post_meta($eid, '_event_start_time', true);
			$et = get_post_meta($eid, '_event_end_time', true);
			$coach = get_post_meta($eid, '_event_headcoach', true);
			$venue = get_post_meta($eid, '_event_venue', true);
			$coach_img = function_exists('parkourone_get_coach_display_image_by_name')
				? parkourone_get_coach_display_image_by_name($coach, '80x80', get_post_meta($eid, '_event_headcoach_image_url', true))
				: get_post_meta($eid, '_event_headcoach_image_url', true);

			$terms = wp_get_post_terms($eid, 'event_category', ['fields' => 'all']);
			$age_slug = '';
			$offer_slug = '';
			foreach ($terms as $term) {
				if ($term->parent) {
					$parent = get_term($term->parent, 'event_category');
					if ($parent && !is_wp_error($parent)) {
						if ($parent->slug === 'alter') $age_slug = $term->slug;
						if ($parent->slug === 'angebot') $offer_slug = $term->slug;
					}
				}
			}
			if ($offer_slug === 'ferienkurs') continue;

			$color = $mnt_age_colors[$age_slug] ?? '#2997ff';

			if (!isset($mnt_modal_data[$eid])) {
				$mnt_modal_data[$eid] = [
					'event_id' => $eid,
					'title' => get_the_title(),
					'start_time' => $st,
					'end_time' => $et,
					'headcoach' => $coach,
					'venue' => $venue,
					'age_slug' => $age_slug,
					'color' => $color,
					'dropdown_info' => get_post_meta($eid, '_event_dropdown_info', true),
				];
			}

			// WC product stock per date
			$prods = get_posts([
				'post_type' => 'product',
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_query' => [['key' => '_event_id', 'value' => $eid]],
				'fields' => 'ids',
			]);
			$stock_by_date = [];
			foreach ($prods as $pid) {
				$pdate = get_post_meta($pid, '_event_date', true);
				if ($pdate) {
					$wcp = wc_get_product($pid);
					if ($wcp) {
						$stock_by_date[$pdate] = $wcp->is_in_stock() ? max(0, (int) $wcp->get_stock_quantity()) : 0;
					}
				}
			}

			foreach ($edates as $de) {
				if (empty($de['date'])) continue;
				$ds = str_replace('.', '-', $de['date']);
				$parts = explode('-', $ds);
				if (count($parts) !== 3) continue;
				$day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
				$month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
				$year = $parts[2];
				$ts = strtotime("$year-$month-$day");
				if (!$ts || $ts < $mnt_today) continue;

				$stock = isset($stock_by_date[$de['date']]) ? $stock_by_date[$de['date']] : -1;

				$mnt_events[] = [
					'event_id' => $eid,
					'title' => get_the_title(),
					'start_time' => $st,
					'end_time' => $et,
					'headcoach' => $coach,
					'headcoach_image' => $coach_img,
					'venue' => $de['venue'] ?? $venue,
					'age_slug' => $age_slug,
					'color' => $color,
					'stock' => $stock,
					'date_key' => "$year-$month-$day",
					'timestamp' => $ts,
				];
			}
		}
		wp_reset_postdata();
	}

	// Availability per event (for soldout styling)
	$mnt_availability = [];
	foreach ($mnt_events as $ev) {
		if ($ev['stock'] > 0) $mnt_availability[$ev['event_id']] = true;
	}

	// Sort by date, then time
	usort($mnt_events, function($a, $b) {
		return strcmp($a['date_key'], $b['date_key']) ?: strcmp($a['start_time'], $b['start_time']);
	});

	// Limit display to first 10
	$mnt_events = array_slice($mnt_events, 0, 10);
}

$mnt_has_events = !empty($mnt_events);
$mnt_checkout_url = $mnt_has_wc ? wc_get_checkout_url() : '/checkout/';
$mnt_rest_url = rest_url('parkourone/v1/add-to-cart');
$mnt_nonce = wp_create_nonce('wp_rest');
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Wir sind gleich zurück | ParkourONE</title>
	<meta name="robots" content="noindex, nofollow">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			background: #0a0a0a;
			color: #fff;
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			overflow-x: hidden;
			position: relative;
		}

		body.po-no-scroll { overflow: hidden; }

		/* Background */
		.bg-image {
			position: fixed;
			inset: 0;
			background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/fallback/landscape/adults/1T2A6249.jpg');
			background-size: cover;
			background-position: center;
			z-index: -2;
		}
		.bg-overlay {
			position: fixed;
			inset: 0;
			background: linear-gradient(to bottom, rgba(10,10,10,0.7) 0%, rgba(10,10,10,0.85) 50%, rgba(10,10,10,0.95) 100%);
			z-index: -1;
		}
		.bg-grid {
			position: fixed;
			inset: 0;
			background-image: linear-gradient(rgba(41,151,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(41,151,255,0.03) 1px, transparent 1px);
			background-size: 60px 60px;
			animation: gridMove 20s linear infinite;
		}
		@keyframes gridMove { 0% { transform: translate(0,0); } 100% { transform: translate(60px,60px); } }

		.orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: orbFloat 8s ease-in-out infinite; }
		.orb-1 { width: 400px; height: 400px; background: #2997ff; top: -100px; right: -100px; }
		.orb-2 { width: 300px; height: 300px; background: #5856d6; bottom: -50px; left: -50px; animation-delay: -4s; }
		.orb-3 { width: 200px; height: 200px; background: #2997ff; top: 50%; left: 50%; animation-delay: -2s; }
		@keyframes orbFloat { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(30px,-30px) scale(1.1); } }

		/* Content */
		.content {
			position: relative;
			z-index: 1;
			text-align: center;
			padding: 40px 24px;
			max-width: 600px;
			width: 100%;
		}

		/* Logo */
		.logo { margin-bottom: 48px; }
		.logo svg { height: 40px; width: auto; }

		/* Icon */
		.icon {
			width: 80px; height: 80px; margin: 0 auto 32px;
			background: rgba(41,151,255,0.1); border-radius: 50%;
			display: flex; align-items: center; justify-content: center;
			animation: pulse 2s ease-in-out infinite;
		}
		.icon svg { width: 40px; height: 40px; color: #2997ff; }
		@keyframes pulse {
			0%,100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(41,151,255,0.4); }
			50% { transform: scale(1.05); box-shadow: 0 0 40px 10px rgba(41,151,255,0.2); }
		}

		/* Typography */
		.eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 0.2em; text-transform: uppercase; color: #2997ff; margin-bottom: 16px; }
		h1 { font-size: clamp(32px,8vw,56px); font-weight: 700; letter-spacing: -0.03em; line-height: 1.1; margin-bottom: 24px; }
		.highlight { color: #2997ff; text-shadow: 0 0 30px rgba(41,151,255,0.5); }
		.description { font-size: 18px; line-height: 1.6; color: rgba(255,255,255,0.7); margin-bottom: 32px; }

		/* ============================
		   Booking Section
		   ============================ */
		.booking-section {
			margin-bottom: 40px;
			text-align: left;
		}
		.booking-section h2 {
			font-size: 20px;
			font-weight: 600;
			text-align: center;
			margin-bottom: 6px;
			letter-spacing: -0.01em;
		}
		.booking-intro {
			font-size: 14px;
			color: rgba(255,255,255,0.5);
			text-align: center;
			margin-bottom: 16px;
		}
		.booking-list {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		/* Event Card */
		.booking-card {
			display: flex;
			align-items: center;
			gap: 12px;
			width: 100%;
			padding: 14px 16px;
			background: rgba(255,255,255,0.06);
			border: 1px solid rgba(255,255,255,0.08);
			border-radius: 14px;
			cursor: pointer;
			text-align: left;
			font-family: inherit;
			color: #fff;
			transition: background 0.15s, transform 0.15s;
		}
		.booking-card:hover { background: rgba(255,255,255,0.1); transform: translateY(-1px); }
		.booking-card:active { transform: translateY(0); }
		.booking-card.is-soldout { opacity: 0.45; pointer-events: none; }
		.booking-card-img {
			width: 44px; height: 44px; border-radius: 50%; object-fit: cover;
			flex-shrink: 0; border: 2px solid rgba(255,255,255,0.1);
		}
		.booking-card-body { flex: 1; display: flex; flex-direction: column; gap: 1px; min-width: 0; }
		.booking-card-date { font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.03em; }
		.booking-card-time { font-size: 13px; color: rgba(255,255,255,0.65); }
		.booking-card-title { font-size: 15px; font-weight: 600; line-height: 1.3; }
		.booking-card-meta { display: flex; gap: 8px; margin-top: 1px; }
		.booking-card-coach { font-size: 12px; color: rgba(255,255,255,0.5); }
		.booking-card-venue { font-size: 12px; color: rgba(255,255,255,0.35); }
		.booking-card-venue::before { content: "·"; margin-right: 4px; }
		.booking-card-stock { font-size: 11px; font-weight: 500; color: #34c759; margin-top: 1px; }
		.booking-card-stock--low { color: #ff9500; }
		.booking-card-stock--none { color: #8e8e93; }
		.booking-card-stock--alt { color: #2997ff; }
		.booking-card-arrow {
			width: 18px; height: 18px; flex-shrink: 0;
			color: rgba(255,255,255,0.25); transition: color 0.15s;
		}
		.booking-card:hover .booking-card-arrow { color: rgba(255,255,255,0.5); }

		/* Fallback CTA when no events */
		.cta-section { margin-bottom: 40px; text-align: center; }
		.cta-text { font-size: 15px; color: rgba(255,255,255,0.6); margin-bottom: 16px; }
		.cta-btn {
			display: inline-block; padding: 14px 32px;
			background: #2997ff; color: #fff; font-size: 16px; font-weight: 600;
			text-decoration: none; border-radius: 12px; transition: all 0.3s;
		}
		.cta-btn:hover { background: #0a84ff; transform: translateY(-2px); box-shadow: 0 8px 30px rgba(41,151,255,0.4); }

		/* ============================
		   Modal Overlay
		   ============================ */
		.po-overlay {
			position: fixed; inset: 0; z-index: 999999;
			display: flex; align-items: center; justify-content: center;
			padding: 24px;
			visibility: hidden; pointer-events: none;
		}
		.po-overlay.is-active { visibility: visible; pointer-events: auto; }
		.po-overlay__backdrop {
			position: fixed; inset: 0;
			background: rgba(0,0,0,0.6);
			backdrop-filter: blur(20px) saturate(150%);
			-webkit-backdrop-filter: blur(20px) saturate(150%);
			opacity: 0; transition: opacity 0.25s;
		}
		.po-overlay.is-active .po-overlay__backdrop { opacity: 1; }
		.po-overlay__panel {
			position: relative; width: 100%; max-width: 480px;
			max-height: calc(100vh - 48px); overflow-y: auto;
			background: #fff; border-radius: 20px;
			padding: 40px 32px 32px;
			box-shadow: 0 25px 80px rgba(0,0,0,0.4);
			opacity: 0; transform: scale(0.92) translateY(24px);
			transition: opacity 0.35s ease, transform 0.4s ease;
			color: #1d1d1f;
		}
		.po-overlay.is-active .po-overlay__panel { opacity: 1; transform: scale(1) translateY(0); }
		.po-overlay__close {
			position: absolute; top: 16px; right: 16px;
			width: 30px; height: 30px; padding: 0;
			border: none; background: none; cursor: pointer;
			transition: transform 0.2s;
		}
		.po-overlay__close:hover { transform: scale(1.1); }
		.po-overlay__close svg { width: 100%; height: 100%; }

		/* ============================
		   Steps (multi-slide modal)
		   ============================ */
		.po-steps { position: relative; overflow: hidden; min-height: 280px; }
		.po-steps__track { position: relative; }
		.po-steps__slide {
			position: absolute; top: 0; left: 0; width: 100%;
			opacity: 0; transform: translateX(60px);
			transition: transform 0.4s cubic-bezier(0.4,0,0.2,1), opacity 0.3s;
			pointer-events: none;
		}
		.po-steps__slide.is-active { position: relative; opacity: 1; transform: translateX(0); pointer-events: auto; }
		.po-steps__slide.is-prev { opacity: 0; transform: translateX(-60px); }

		.po-steps__header { text-align: center; margin-bottom: 24px; }
		.po-steps__eyebrow {
			display: block; font-size: 13px; font-weight: 500;
			text-transform: uppercase; letter-spacing: 0.05em;
			color: #86868b; margin-bottom: 8px;
		}
		.po-steps__heading {
			font-size: 26px; font-weight: 600; letter-spacing: -0.02em;
			color: #1d1d1f; margin: 0; line-height: 1.2;
		}
		.po-steps__subheading { font-size: 15px; color: #86868b; margin: 8px 0 0; }

		.po-steps__meta { display: flex; flex-direction: column; gap: 12px; margin: 0 0 20px; }
		.po-steps__meta-item { display: flex; align-items: center; gap: 12px; justify-content: center; }
		.po-steps__meta-item dt { flex-shrink: 0; width: 20px; height: 20px; color: #86868b; }
		.po-steps__meta-item dt svg { width: 100%; height: 100%; }
		.po-steps__meta-item dd { margin: 0; font-size: 15px; color: #1d1d1f; }

		.po-steps__description {
			font-size: 15px; line-height: 1.6; color: #86868b;
			text-align: center; margin: 0 0 20px;
		}
		.po-steps__info-notice {
			background: rgba(0,115,170,0.08); border-left: 3px solid #0073aa;
			padding: 12px 16px; margin: 0 0 20px; font-size: 14px;
			line-height: 1.6; color: #1d1d1f; border-radius: 0 8px 8px 0; text-align: left;
		}
		.po-steps__price {
			display: flex; justify-content: space-between; align-items: center;
			padding: 14px 18px; background: #f5f5f7; border-radius: 12px; margin-bottom: 18px;
		}
		.po-steps__price-label { font-size: 14px; color: #86868b; }
		.po-steps__price-value { font-size: 22px; font-weight: 700; color: #0066cc; }

		.po-steps__cta {
			display: block; width: 100%; padding: 16px 24px;
			background: #0066cc; color: #fff; font-size: 16px; font-weight: 600;
			text-align: center; text-decoration: none;
			border: none; border-radius: 14px; cursor: pointer;
			transition: background 0.2s, transform 0.15s;
		}
		.po-steps__cta:hover { background: #0055b3; }
		.po-steps__cta:active { transform: scale(0.98); }
		.po-steps__cta:disabled { background: #999; cursor: not-allowed; transform: none; }

		.po-steps__dates { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
		.po-steps__date {
			display: flex; justify-content: space-between; align-items: center;
			padding: 14px 16px; background: #f5f5f7; border: 2px solid transparent;
			border-radius: 14px; cursor: pointer; text-align: left;
			transition: background 0.2s, transform 0.15s; font-family: inherit;
		}
		.po-steps__date:hover { background: #eaeaea; transform: translateY(-1px); }
		.po-steps__date-text { font-size: 15px; font-weight: 500; color: #1d1d1f; }
		.po-steps__date-stock { font-size: 13px; color: #86868b; }

		.po-steps__back-link {
			display: block; width: 100%; padding: 12px;
			background: none; border: none; font-size: 14px; font-family: inherit;
			color: #0066cc; text-align: center; cursor: pointer;
			transition: opacity 0.2s;
		}
		.po-steps__back-link:hover { opacity: 0.7; }

		.po-steps__form { margin-bottom: 16px; }
		.po-steps__field { margin-bottom: 16px; }
		.po-steps__field label {
			display: block; font-size: 14px; font-weight: 500;
			color: #1d1d1f; margin-bottom: 6px;
		}
		.po-steps__field input {
			width: 100%; padding: 12px 14px; font-size: 16px;
			border: 1px solid #d1d1d6; border-radius: 12px;
			background: #fff; font-family: inherit;
			transition: border-color 0.2s, box-shadow 0.2s;
			-webkit-appearance: none;
		}
		.po-steps__field input:focus {
			outline: none; border-color: #0066cc;
			box-shadow: 0 0 0 4px rgba(0,102,204,0.12);
		}

		.po-steps__success { text-align: center; padding: 30px 20px; }
		.po-steps__success-icon {
			width: 64px; height: 64px; margin: 0 auto 20px;
			background: #34c759; border-radius: 50%;
			display: flex; align-items: center; justify-content: center;
		}
		.po-steps__success-icon svg { width: 32px; height: 32px; color: #fff; }
		.po-steps__checkout-link {
			display: block; width: 100%; padding: 16px 24px;
			background: #0066cc; color: #fff; font-size: 16px; font-weight: 600;
			text-align: center; text-decoration: none;
			border-radius: 14px; margin-top: 20px;
			transition: background 0.2s;
		}
		.po-steps__checkout-link:hover { background: #0055b3; }

		/* Progress Bar */
		.progress-container { width: 100%; max-width: 300px; margin: 0 auto 24px; }
		.progress-bar { height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
		.progress-fill { height: 100%; width: 60%; background: linear-gradient(90deg,#2997ff,#5856d6); border-radius: 2px; animation: progressPulse 2s ease-in-out infinite; }
		@keyframes progressPulse { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }
		.progress-text { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 12px; }

		/* Social Links */
		.social { display: flex; gap: 16px; justify-content: center; margin-top: 48px; }
		.social a {
			width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;
			background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
			border-radius: 50%; color: rgba(255,255,255,0.6); text-decoration: none; transition: all 0.3s;
		}
		.social a:hover { background: rgba(41,151,255,0.2); border-color: #2997ff; color: #fff; transform: translateY(-2px); }
		.social svg { width: 20px; height: 20px; }

		/* Footer */
		.footer { position: relative; margin-top: 40px; text-align: center; font-size: 13px; color: rgba(255,255,255,0.3); padding-bottom: 24px; }

		@media (max-width: 480px) {
			.description { font-size: 16px; }
			.social { gap: 12px; }
			.po-overlay { padding: 12px; }
			.po-overlay__panel { padding: 32px 20px 24px; }
		}
	</style>
</head>
<body>
	<!-- Background -->
	<div class="bg-image"></div>
	<div class="bg-overlay"></div>
	<div class="bg-grid"></div>
	<div class="orb orb-1"></div>
	<div class="orb orb-2"></div>
	<div class="orb orb-3"></div>

	<!-- Content -->
	<div class="content">
		<div class="logo">
			<svg viewBox="0 0 200 40" fill="currentColor">
				<text x="0" y="30" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="28" font-weight="700">ParkourONE</text>
			</svg>
		</div>

		<div class="icon">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
			</svg>
		</div>

		<span class="eyebrow">Update in Arbeit</span>
		<h1>Wir sind <span class="highlight">gleich</span> zurück</h1>
		<p class="description">
			Wir arbeiten gerade an etwas Grossartigem.
			Unsere neue Website ist bald bereit für den nächsten Sprung.
		</p>

		<?php if ($mnt_has_events): ?>
		<!-- Booking Section -->
		<div class="booking-section">
			<h2>Probetraining buchen</h2>
			<p class="booking-intro">Du kannst auch jetzt ein Probetraining buchen!</p>
			<div class="booking-list">
				<?php foreach ($mnt_events as $ev):
					$time_text = $ev['start_time'];
					if ($ev['end_time']) $time_text .= ' – ' . $ev['end_time'];
					$modal_id = 'mnt-modal-' . $ev['event_id'];
					$date_label = $mnt_format_date($ev['date_key']);
					$stock = $ev['stock'];
					$has_other = !empty($mnt_availability[$ev['event_id']]);
					$is_soldout = ($stock === 0) && !$has_other;
					$stock_html = '';
					if ($stock === 0 && $has_other) {
						$stock_html = '<span class="booking-card-stock booking-card-stock--alt">Weitere Termine verfügbar</span>';
					} elseif ($stock === 0) {
						$stock_html = '<span class="booking-card-stock booking-card-stock--none">Ausgebucht</span>';
					} elseif ($stock > 0 && $stock <= 3) {
						$stock_html = '<span class="booking-card-stock booking-card-stock--low">' . $stock . ($stock === 1 ? ' Platz' : ' Plätze') . '</span>';
					} elseif ($stock > 3) {
						$stock_html = '<span class="booking-card-stock">' . $stock . ' Plätze</span>';
					}
				?>
				<button type="button"
					class="booking-card<?php echo $is_soldout ? ' is-soldout' : ''; ?>"
					data-modal-target="<?php echo esc_attr($modal_id); ?>">
					<?php if (!empty($ev['headcoach_image'])): ?>
						<img src="<?php echo esc_url($ev['headcoach_image']); ?>" alt="<?php echo esc_attr($ev['headcoach']); ?>" class="booking-card-img" loading="lazy">
					<?php endif; ?>
					<div class="booking-card-body">
						<span class="booking-card-date"><?php echo esc_html($date_label); ?></span>
						<span class="booking-card-time"><?php echo esc_html($time_text); ?> Uhr</span>
						<span class="booking-card-title" style="color: <?php echo esc_attr($ev['color']); ?>"><?php echo esc_html($ev['title']); ?></span>
						<div class="booking-card-meta">
							<?php if ($ev['headcoach']): ?>
							<span class="booking-card-coach"><?php echo esc_html($ev['headcoach']); ?></span>
							<?php endif; ?>
							<?php if ($ev['venue']): ?>
							<span class="booking-card-venue"><?php echo esc_html($ev['venue']); ?></span>
							<?php endif; ?>
						</div>
						<?php echo $stock_html; ?>
					</div>
					<svg class="booking-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php else: ?>
		<!-- Fallback: Contact CTA -->
		<div class="cta-section">
			<p class="cta-text">Du möchtest ein Probetraining buchen? Schreib uns!</p>
			<a href="mailto:info@parkourone.com" class="cta-btn">E-Mail schreiben</a>
		</div>
		<?php endif; ?>

		<!-- Progress -->
		<div class="progress-container">
			<div class="progress-bar">
				<div class="progress-fill"></div>
			</div>
			<p class="progress-text">Setup läuft...</p>
		</div>

		<!-- Social Links -->
		<div class="social">
			<a href="https://instagram.com/parkourone" target="_blank" rel="noopener" aria-label="Instagram">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
					<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
					<line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
				</svg>
			</a>
			<a href="https://youtube.com/parkourone" target="_blank" rel="noopener" aria-label="YouTube">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/>
					<polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
				</svg>
			</a>
			<a href="mailto:info@parkourone.com" aria-label="E-Mail">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
					<polyline points="22,6 12,13 2,6"/>
				</svg>
			</a>
		</div>
	</div>

	<!-- Footer -->
	<footer class="footer">
		&copy; <?php echo date('Y'); ?> ParkourONE
	</footer>

	<?php // ======== Modals (1 pro Event-ID) ======== ?>
	<?php if ($mnt_has_events): ?>
	<?php foreach ($mnt_modal_data as $event_id => $ev):
		$modal_id = 'mnt-modal-' . $event_id;
		$available_dates = function_exists('parkourone_get_available_dates_for_event')
			? parkourone_get_available_dates_for_event($event_id) : [];
		$category = $ev['age_slug'] ?? '';
		$time_text = $ev['start_time'] ? $ev['start_time'] . ($ev['end_time'] ? ' – ' . $ev['end_time'] . ' Uhr' : ' Uhr') : '';
	?>
	<div class="po-overlay" id="<?php echo esc_attr($modal_id); ?>" aria-hidden="true" role="dialog" aria-modal="true">
		<div class="po-overlay__backdrop"></div>
		<div class="po-overlay__panel">
			<button class="po-overlay__close" aria-label="Schliessen">
				<svg viewBox="0 0 24 24" fill="none">
					<circle cx="12" cy="12" r="12" fill="#1d1d1f"/>
					<path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>

			<div class="po-steps" data-step="0">
				<div class="po-steps__track">

					<!-- Slide 0: Overview -->
					<div class="po-steps__slide is-active" data-slide="0">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Probetraining</span>
							<h2 class="po-steps__heading"><?php echo esc_html($ev['title']); ?></h2>
						</header>
						<dl class="po-steps__meta">
							<?php if ($ev['start_time']): ?>
							<div class="po-steps__meta-item">
								<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></dt>
								<dd><?php echo esc_html($time_text); ?></dd>
							</div>
							<?php endif; ?>
							<?php if (!empty($ev['venue'])): ?>
							<div class="po-steps__meta-item">
								<dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></dt>
								<dd><?php echo esc_html($ev['venue']); ?></dd>
							</div>
							<?php endif; ?>
						</dl>
						<?php if ($category && isset($mnt_mood_texts[$category])): ?>
						<p class="po-steps__description"><?php echo esc_html($mnt_mood_texts[$category]); ?></p>
						<?php endif; ?>
						<?php if (!empty($ev['dropdown_info'])): ?>
						<div class="po-steps__info-notice"><?php echo wp_kses_post($ev['dropdown_info']); ?></div>
						<?php endif; ?>
						<?php if (!empty($available_dates) && !empty($available_dates[0]['price'])): ?>
						<div class="po-steps__price">
							<span class="po-steps__price-label">Probetraining</span>
							<span class="po-steps__price-value"><?php echo wp_kses_post($available_dates[0]['price']); ?></span>
						</div>
						<?php endif; ?>
						<button type="button" class="po-steps__cta po-steps__next">Jetzt buchen</button>
					</div>

					<!-- Slide 1: Date selection -->
					<div class="po-steps__slide is-next" data-slide="1">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Schritt 1 von 2</span>
							<h2 class="po-steps__heading">Termin wählen</h2>
							<p class="po-steps__subheading"><?php echo esc_html($ev['title']); ?></p>
						</header>
						<?php if (!empty($available_dates)): ?>
						<div class="po-steps__dates">
							<?php foreach ($available_dates as $date): ?>
							<button type="button" class="po-steps__date po-steps__next" data-product-id="<?php echo esc_attr($date['product_id']); ?>" data-date-text="<?php echo esc_attr($date['date_formatted']); ?>">
								<span class="po-steps__date-text"><?php echo esc_html($date['date_formatted']); ?></span>
								<span class="po-steps__date-stock"><?php echo esc_html($date['stock']); ?> <?php echo $date['stock'] === 1 ? 'Platz' : 'Plätze'; ?> frei</span>
							</button>
							<?php endforeach; ?>
						</div>
						<?php else: ?>
						<div style="text-align:center;padding:30px 20px;background:#f5f5f7;border-radius:14px;margin-bottom:24px;">
							<p style="font-size:15px;color:#86868b;">Aktuell keine Termine verfügbar.</p>
						</div>
						<?php endif; ?>
						<button type="button" class="po-steps__back-link">&larr; Zurück</button>
					</div>

					<!-- Slide 2: Form -->
					<div class="po-steps__slide is-next" data-slide="2">
						<header class="po-steps__header">
							<span class="po-steps__eyebrow">Schritt 2 von 2</span>
							<h2 class="po-steps__heading">Wer nimmt teil?</h2>
							<p class="po-steps__subheading po-steps__selected-date"></p>
						</header>
						<form class="po-steps__form">
							<input type="hidden" name="product_id" value="">
							<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
							<div class="po-steps__field">
								<label for="vorname-<?php echo esc_attr($modal_id); ?>">Vorname</label>
								<input type="text" id="vorname-<?php echo esc_attr($modal_id); ?>" name="vorname" required autocomplete="given-name">
							</div>
							<div class="po-steps__field">
								<label for="name-<?php echo esc_attr($modal_id); ?>">Nachname</label>
								<input type="text" id="name-<?php echo esc_attr($modal_id); ?>" name="name" required autocomplete="family-name">
							</div>
							<div class="po-steps__field">
								<label for="geb-<?php echo esc_attr($modal_id); ?>">Geburtsdatum</label>
								<input type="date" id="geb-<?php echo esc_attr($modal_id); ?>" name="geburtsdatum" required>
							</div>
							<button type="submit" class="po-steps__cta">Zum Warenkorb hinzufügen</button>
						</form>
						<button type="button" class="po-steps__back-link">&larr; Anderer Termin</button>
					</div>

					<!-- Slide 3: Success -->
					<div class="po-steps__slide is-next" data-slide="3">
						<div class="po-steps__success">
							<div class="po-steps__success-icon">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
							</div>
							<h2 class="po-steps__heading">Hinzugefügt!</h2>
							<p class="po-steps__subheading"><?php echo esc_html($ev['title']); ?></p>
							<p class="po-steps__selected-date-confirm" style="font-size:14px;color:#86868b;margin-top:4px;"></p>
							<a href="<?php echo esc_url($mnt_checkout_url); ?>" class="po-steps__checkout-link">Zur Kasse</a>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<script>
	(function() {
		'use strict';

		var restUrl = <?php echo wp_json_encode($mnt_rest_url); ?>;
		var nonce = <?php echo wp_json_encode($mnt_nonce); ?>;

		// Step navigation
		function goToStep(steps, step) {
			var slides = steps.querySelectorAll('.po-steps__slide');
			slides.forEach(function(slide, i) {
				slide.classList.remove('is-active', 'is-prev', 'is-next');
				if (i < step) slide.classList.add('is-prev');
				else if (i === step) slide.classList.add('is-active');
				else slide.classList.add('is-next');
			});
			steps.setAttribute('data-step', step);
			steps.closest('.po-overlay__panel').scrollTop = 0;
		}

		// Modal open
		document.querySelectorAll('[data-modal-target]').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var modal = document.getElementById(btn.getAttribute('data-modal-target'));
				if (!modal) return;
				modal.classList.add('is-active');
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('po-no-scroll');
			});
		});

		// Modal close
		function closeModal(modal) {
			modal.classList.remove('is-active');
			modal.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('po-no-scroll');
			var steps = modal.querySelector('.po-steps');
			if (steps) setTimeout(function() { goToStep(steps, 0); }, 300);
			var form = modal.querySelector('.po-steps__form');
			if (form) form.reset();
		}

		document.querySelectorAll('.po-overlay__close').forEach(function(btn) {
			btn.addEventListener('click', function() { closeModal(btn.closest('.po-overlay')); });
		});
		document.querySelectorAll('.po-overlay__backdrop').forEach(function(el) {
			el.addEventListener('click', function() { closeModal(el.closest('.po-overlay')); });
		});
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				var active = document.querySelector('.po-overlay.is-active');
				if (active) closeModal(active);
			}
		});

		// Next step
		document.querySelectorAll('.po-steps__next').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var steps = btn.closest('.po-steps');
				var current = parseInt(steps.getAttribute('data-step')) || 0;

				// Date selection: store product_id
				if (btn.classList.contains('po-steps__date')) {
					var pid = btn.getAttribute('data-product-id');
					var dt = btn.getAttribute('data-date-text');
					steps.querySelector('[name="product_id"]').value = pid;
					var sd = steps.querySelector('.po-steps__selected-date');
					if (sd) sd.textContent = dt;
					var sc = steps.querySelector('.po-steps__selected-date-confirm');
					if (sc) sc.textContent = dt;
				}

				goToStep(steps, current + 1);
			});
		});

		// Back step
		document.querySelectorAll('.po-steps__back-link').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				var steps = btn.closest('.po-steps');
				var current = parseInt(steps.getAttribute('data-step')) || 0;
				if (current > 0) goToStep(steps, current - 1);
			});
		});

		// Form submit
		document.querySelectorAll('.po-steps__form').forEach(function(form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				var submitBtn = form.querySelector('[type="submit"]');
				var steps = form.closest('.po-steps');

				var data = {
					product_id: form.querySelector('[name="product_id"]').value,
					event_id: form.querySelector('[name="event_id"]').value,
					vorname: form.querySelector('[name="vorname"]').value,
					name: form.querySelector('[name="name"]').value,
					geburtsdatum: form.querySelector('[name="geburtsdatum"]').value
				};

				submitBtn.disabled = true;
				submitBtn.textContent = 'Wird hinzugefügt...';

				fetch(restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
					credentials: 'same-origin',
					body: JSON.stringify(data)
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (res.success) {
						goToStep(steps, 3);
						form.reset();
					} else {
						alert(res.data && res.data.message ? res.data.message : 'Ein Fehler ist aufgetreten.');
					}
					submitBtn.disabled = false;
					submitBtn.textContent = 'Zum Warenkorb hinzufügen';
				})
				.catch(function() {
					alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
					submitBtn.disabled = false;
					submitBtn.textContent = 'Zum Warenkorb hinzufügen';
				});
			});
		});
	})();
	</script>
	<?php endif; ?>
</body>
</html>
