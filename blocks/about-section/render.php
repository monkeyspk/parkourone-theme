<?php
$subheadline = $attributes['subheadline'] ?? 'ABOUT';
$headline = $attributes['headline'] ?? 'parkourONE';
$text = $attributes['text'] ?? '';
$ctaText = $attributes['ctaText'] ?? '';
$ctaUrl = $attributes['ctaUrl'] ?? '#';
$mediaType = $attributes['mediaType'] ?? 'video';
$imageUrl = $attributes['imageUrl'] ?? '';
$videoUrl = $attributes['videoUrl'] ?? '';
$videoType = $attributes['videoType'] ?? 'embed';
$mediaRight = $attributes['mediaRight'] ?? true;
$bgColor = $attributes['backgroundColor'] ?? '#f5f5f7';

// Fallback-Bild wenn kein Media gesetzt (Portrait für About-Section)
$fallback_image = parkourone_get_fallback_image('juniors', 'portrait');

$class = 'po-about';
if ($mediaRight) {
	$class .= ' po-about--media-right';
}

// Parse video embed URL for YouTube/Vimeo
if (!function_exists('parkourone_get_embed_url')) {
	function parkourone_get_embed_url($url) {
		// YouTube
		if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
			return 'https://www.youtube.com/embed/' . $matches[1];
		}
		// Vimeo
		if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
			return 'https://player.vimeo.com/video/' . $matches[1];
		}
		return $url;
	}
}
?>
<section class="<?php echo esc_attr($class); ?> alignfull" style="background-color: <?php echo esc_attr($bgColor); ?>">
	<div class="po-about__inner">
	<div class="po-about__text">
		<?php if ($subheadline): ?>
			<span class="po-about__subheadline"><?php echo esc_html($subheadline); ?></span>
		<?php endif; ?>
		<?php if ($headline): ?>
			<h2 class="po-about__headline"><?php echo wp_kses_post($headline); ?></h2>
		<?php endif; ?>
		<?php if ($text): ?>
			<div class="po-about__content"><?php echo wp_kses_post($text); ?></div>
		<?php endif; ?>
		<?php if ($ctaText): ?>
			<a href="<?php echo esc_url($ctaUrl); ?>" class="po-about__cta"><?php echo esc_html($ctaText); ?></a>
		<?php endif; ?>
	</div>
	<div class="po-about__media">
		<?php if ($mediaType === 'video' && $videoUrl): ?>
			<?php if ($videoType === 'embed'): ?>
				<div class="po-about__video-wrapper">
					<iframe
						src="<?php echo esc_url(parkourone_get_embed_url($videoUrl)); ?>"
						frameborder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
						allowfullscreen
					></iframe>
				</div>
			<?php else: ?>
				<video controls class="po-about__video">
					<source src="<?php echo esc_url($videoUrl); ?>" type="video/mp4">
				</video>
			<?php endif; ?>
		<?php elseif ($mediaType === 'video' && !$videoUrl): ?>
			<!-- Video ohne URL: Fallback-Bild anzeigen -->
			<img src="<?php echo esc_url($fallback_image); ?>" alt="ParkourONE Training" loading="lazy" class="po-about__image">
		<?php elseif ($mediaType === 'image' && $imageUrl): ?>
			<img src="<?php echo esc_url($imageUrl); ?>" alt="" loading="lazy" class="po-about__image">
		<?php elseif ($mediaType === 'image' && !$imageUrl): ?>
			<!-- Bild ohne URL: Fallback-Bild anzeigen -->
			<img src="<?php echo esc_url($fallback_image); ?>" alt="ParkourONE Training" loading="lazy" class="po-about__image">
		<?php endif; ?>
	</div>
	</div>
</section>
