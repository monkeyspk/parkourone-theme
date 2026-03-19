<?php
$bg_color = $attributes['backgroundColor'] ?? '';
$padding = $attributes['paddingSize'] ?? 'medium';
$max_width = $attributes['maxWidth'] ?? 'default';
$show_headline = $attributes['showHeadline'] ?? false;
$headline = $attributes['headline'] ?? '';

$classes = ['po-section', 'po-section--pad-' . esc_attr($padding), 'po-section--width-' . esc_attr($max_width)];
$style = $bg_color ? 'background-color: ' . esc_attr($bg_color) . ';' : '';

// Dark text on dark backgrounds
$dark_bgs = ['#1d1d1f', '#000000', '#0066cc', '#ff3b30'];
if (in_array($bg_color, $dark_bgs)) {
    $classes[] = 'po-section--dark';
}
?>
<section<?php if (!empty($attributes['anchor'])) echo ' id="' . esc_attr($attributes['anchor']) . '"'; ?> class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php echo $style ? ' style="' . $style . '"' : ''; ?>>
    <div class="po-section__inner">
        <?php if ($show_headline && $headline): ?>
            <h2 class="po-section__headline"><?php echo wp_kses_post($headline); ?></h2>
        <?php endif; ?>
        <div class="po-section__content">
            <?php echo $content; ?>
        </div>
    </div>
</section>
