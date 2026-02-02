<?php
/**
 * PO Text Block - Render
 * Ticket #2: Basis Building Blocks
 */
$content = $attributes['content'] ?? '';
$font_size = $attributes['fontSize'] ?? 'medium';
$text_align = $attributes['textAlign'] ?? 'left';
$bg_color = $attributes['backgroundColor'] ?? '';
$text_color = $attributes['textColor'] ?? '';

if (empty($content)) {
	return;
}

$classes = ['po-text', 'po-text--' . $font_size];
$styles = [];

if ($text_align && $text_align !== 'left') {
	$styles[] = 'text-align: ' . esc_attr($text_align);
}
if ($bg_color) {
	$styles[] = 'background-color: ' . esc_attr($bg_color);
	$classes[] = 'po-text--has-background';
}
if ($text_color) {
	$styles[] = 'color: ' . esc_attr($text_color);
}

$style_attr = !empty($styles) ? 'style="' . implode('; ', $styles) . '"' : '';
?>
<p class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php echo $style_attr; ?>>
	<?php echo wp_kses_post($content); ?>
</p>
