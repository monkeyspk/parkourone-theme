<?php
/**
 * PO Columns Block - Render
 * Ticket #2: Basis Building Blocks
 */
$columns = $attributes['columns'] ?? 2;
$gap = $attributes['gap'] ?? 'medium';
$vertical_align = $attributes['verticalAlign'] ?? 'top';
$bg_color = $attributes['backgroundColor'] ?? '';
$column_content = $attributes['columnContent'] ?? [];

$classes = [
	'po-columns',
	'po-columns--' . intval($columns),
	'po-columns--gap-' . esc_attr($gap),
	'po-columns--valign-' . esc_attr($vertical_align)
];

$style = $bg_color ? 'background-color: ' . esc_attr($bg_color) . ';' : '';
?>
<div class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
	<?php for ($i = 0; $i < $columns; $i++): ?>
		<?php
		$col_data = $column_content[$i] ?? ['content' => '', 'backgroundColor' => ''];
		$col_style = !empty($col_data['backgroundColor']) ? 'background-color: ' . esc_attr($col_data['backgroundColor']) . ';' : '';
		$col_class = 'po-columns__column';
		if (!empty($col_data['backgroundColor'])) {
			$col_class .= ' po-columns__column--has-background';
		}
		?>
		<div class="<?php echo esc_attr($col_class); ?>" <?php echo $col_style ? 'style="' . $col_style . '"' : ''; ?>>
			<?php if (!empty($col_data['content'])): ?>
				<div class="po-columns__content">
					<?php echo wp_kses_post($col_data['content']); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endfor; ?>
</div>
