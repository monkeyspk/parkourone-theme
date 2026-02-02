<?php
/**
 * PO Image Block - Render
 * Ticket #2: Basis Building Blocks
 */
$media_id = $attributes['mediaId'] ?? 0;
$media_url = $attributes['mediaUrl'] ?? '';
$alt = $attributes['alt'] ?? '';
$caption = $attributes['caption'] ?? '';
$link_url = $attributes['linkUrl'] ?? '';
$link_target = $attributes['linkTarget'] ?? '_self';
$size_slug = $attributes['sizeSlug'] ?? 'large';
$border_radius = $attributes['borderRadius'] ?? 0;

if (empty($media_url) && empty($media_id)) {
	return;
}

// Bild-URL aus Media Library holen falls ID vorhanden
if ($media_id && empty($media_url)) {
	$media_url = wp_get_attachment_image_url($media_id, $size_slug);
}

$img_style = $border_radius > 0 ? 'border-radius: ' . intval($border_radius) . 'px;' : '';

$img_html = sprintf(
	'<img src="%s" alt="%s" class="po-image__img" style="%s" loading="lazy">',
	esc_url($media_url),
	esc_attr($alt),
	esc_attr($img_style)
);

// Mit Link umschliessen wenn vorhanden
if (!empty($link_url)) {
	$rel = $link_target === '_blank' ? ' rel="noopener noreferrer"' : '';
	$img_html = sprintf(
		'<a href="%s" target="%s"%s class="po-image__link">%s</a>',
		esc_url($link_url),
		esc_attr($link_target),
		$rel,
		$img_html
	);
}
?>
<figure class="po-image">
	<?php echo $img_html; ?>
	<?php if (!empty($caption)): ?>
		<figcaption class="po-image__caption"><?php echo wp_kses_post($caption); ?></figcaption>
	<?php endif; ?>
</figure>
