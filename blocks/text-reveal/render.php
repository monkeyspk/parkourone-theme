<?php
$text = $attributes['text'] ?? '';
$text_size = $attributes['textSize'] ?? 'large';
$text_align = $attributes['textAlign'] ?? 'center';
$align = $attributes['align'] ?? 'wide';

if (empty($text)) return;

// Text in Wörter aufteilen
$clean_text = wp_strip_all_tags($text);
$words = preg_split('/\s+/', $clean_text);

static $po_text_reveal_instance = 0; $po_text_reveal_instance++;
$anchor = $attributes['anchor'] ?? '';$unique_id = 'text-reveal-' . $po_text_reveal_instance;

$align_class = '';
if ($align === 'wide') $align_class = 'alignwide';
if ($align === 'full') $align_class = 'alignfull';
?>

<style>
.po-text-reveal {
	padding: 80px 24px;
	background: #fff;
}
.po-text-reveal__container {
	max-width: 1000px;
	margin: 0 auto;
}
.po-text-reveal__text {
	margin: 0;
	line-height: 1.3;
	letter-spacing: -0.02em;
	font-weight: 600;
}
.po-text-reveal--medium .po-text-reveal__text { font-size: clamp(24px, 4vw, 32px); }
.po-text-reveal--large .po-text-reveal__text { font-size: clamp(32px, 5vw, 48px); }
.po-text-reveal--xlarge .po-text-reveal__text { font-size: clamp(40px, 6vw, 64px); }
.po-text-reveal--align-left .po-text-reveal__text { text-align: left; }
.po-text-reveal--align-center .po-text-reveal__text { text-align: center; }
.po-text-reveal--align-right .po-text-reveal__text { text-align: right; }
.po-text-reveal .po-text-reveal__word {
	display: inline;
	color: rgba(29, 29, 31, 0.15) !important;
	transition: color 0.3s ease-out;
}
.po-text-reveal .po-text-reveal__word.is-visible {
	color: #1d1d1f !important;
}
@media (max-width: 768px) {
	.po-text-reveal { padding: 60px 20px; }
}
</style>

<section class="po-text-reveal po-text-reveal--<?php echo esc_attr($text_size); ?> po-text-reveal--align-<?php echo esc_attr($text_align); ?> <?php echo esc_attr($align_class); ?>" id="<?php echo esc_attr($anchor ?: $unique_id); ?>">
	<div class="po-text-reveal__container">
		<p class="po-text-reveal__text">
			<?php foreach ($words as $index => $word): ?>
				<span class="po-text-reveal__word"><?php echo esc_html($word); ?></span><?php echo ' '; ?>
			<?php endforeach; ?>
		</p>
	</div>
</section>

