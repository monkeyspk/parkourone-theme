<?php
/**
 * ParkourONE Share Button Helper
 * Renders a share button for server-rendered templates.
 */

if (!function_exists('parkourone_share_button')) {
	function parkourone_share_button($url, $title, $text = '', $small = false) {
		$cls = 'po-share-btn' . ($small ? ' po-share-btn--sm' : '');
		$label = $small ? '' : '<span class="po-share-btn__label">Teilen</span>';
		$icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';

		return '<button type="button" class="' . esc_attr($cls) . '" '
			. 'data-share-url="' . esc_attr($url) . '" '
			. 'data-share-title="' . esc_attr($title) . '" '
			. 'data-share-text="' . esc_attr($text) . '" '
			. 'aria-label="Teilen">'
			. $icon . $label
			. '</button>';
	}
}
