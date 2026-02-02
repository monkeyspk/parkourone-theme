<?php
/**
 * PO Icon Block - Render
 * Ticket #2: Basis Building Blocks
 */
$icon = $attributes['icon'] ?? 'star';
$size = $attributes['size'] ?? 48;
$color = $attributes['color'] ?? '#667eea';
$link_url = $attributes['linkUrl'] ?? '';
$link_target = $attributes['linkTarget'] ?? '_self';
$align = $attributes['align'] ?? 'center';

// SVG Paths (Lucide-style)
$icon_paths = [
	'star' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
	'heart' => 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z',
	'user' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
	'users' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M9 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
	'calendar' => 'M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM16 2v4M8 2v4M3 10h18',
	'clock' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 6v6l4 2',
	'map-pin' => 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0zM12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
	'phone' => 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z',
	'mail' => 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zM22 6l-10 7L2 6',
	'check' => 'M20 6L9 17l-5-5',
	'check-circle' => 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3',
	'arrow-right' => 'M5 12h14M12 5l7 7-7 7',
	'arrow-left' => 'M19 12H5M12 19l-7-7 7-7',
	'external-link' => 'M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3',
	'download' => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
	'play' => 'M5 3l14 9-14 9V3z',
	'target' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
	'award' => 'M12 15l-2 5 2-1 2 1-2-5zM8.21 13.89L7 23l5-3 5 3-1.21-9.12M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17l4-2 4 2v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7z',
	'zap' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
	'trending-up' => 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
	'shield' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
	'smile' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01',
	'home' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
	'info' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 16v-4M12 8h.01',
	'help-circle' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01'
];

$path = $icon_paths[$icon] ?? $icon_paths['star'];

$svg = sprintf(
	'<svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="po-icon__svg"><path d="%s"/></svg>',
	intval($size),
	intval($size),
	esc_attr($color),
	esc_attr($path)
);

// Mit Link umschliessen wenn vorhanden
if (!empty($link_url)) {
	$rel = $link_target === '_blank' ? ' rel="noopener noreferrer"' : '';
	$svg = sprintf(
		'<a href="%s" target="%s"%s class="po-icon__link">%s</a>',
		esc_url($link_url),
		esc_attr($link_target),
		$rel,
		$svg
	);
}
?>
<div class="po-icon" style="text-align: <?php echo esc_attr($align); ?>">
	<?php echo $svg; ?>
</div>
