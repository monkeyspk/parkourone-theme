<?php
/**
 * Video Block – Server-Side Render
 *
 * Renders YouTube/Vimeo embeds with privacy-friendly placeholder (iframe loaded on click)
 * or self-hosted HTML5 <video> elements.
 *
 * @package ParkourONE
 */

$video_url    = $attributes['videoUrl'] ?? '';
$video_type   = $attributes['videoType'] ?? 'embed';
$poster_image = $attributes['posterImage'] ?? '';
$autoplay     = !empty($attributes['autoplay']);
$loop         = !empty($attributes['loop']);
$muted        = !empty($attributes['muted']);
$caption      = $attributes['caption'] ?? '';
$aspect_ratio = $attributes['aspectRatio'] ?? '16:9';

// Nothing to render without a URL
if (empty($video_url)) {
	return;
}

/**
 * Extract video platform and ID from URL.
 */
if (!function_exists('po_video_extract_id')):
function po_video_extract_id($url) {
	// YouTube
	if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
		return ['youtube', $m[1]];
	}
	// Vimeo
	if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
		return ['vimeo', $m[1]];
	}
	return [null, null];
}
endif;

// Aspect ratio CSS class
$ratio_map = [
	'16:9' => 'po-video--16x9',
	'4:3'  => 'po-video--4x3',
	'1:1'  => 'po-video--1x1',
	'9:16' => 'po-video--9x16',
];
$ratio_class = $ratio_map[$aspect_ratio] ?? 'po-video--16x9';

$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'po-video ' . $ratio_class,
]);
?>

<figure <?php echo $wrapper_attributes; ?>>
	<div class="po-video__wrapper">
		<?php if ($video_type === 'embed'): ?>
			<?php
			list($platform, $video_id) = po_video_extract_id($video_url);

			if ($platform && $video_id):
				// Build poster URL: use custom poster or auto-generate YouTube thumbnail
				$placeholder_bg = '';
				if ($poster_image) {
					$placeholder_bg = $poster_image;
				} elseif ($platform === 'youtube') {
					$placeholder_bg = 'https://img.youtube.com/vi/' . esc_attr($video_id) . '/maxresdefault.jpg';
				}

				$placeholder_style = $placeholder_bg
					? 'background-image: url(' . esc_url($placeholder_bg) . ');'
					: '';
			?>
				<button type="button"
						class="po-video__placeholder"
						data-platform="<?php echo esc_attr($platform); ?>"
						data-video-id="<?php echo esc_attr($video_id); ?>"
						<?php if ($placeholder_style): ?>style="<?php echo esc_attr($placeholder_style); ?>"<?php endif; ?>
						aria-label="Video abspielen">
					<span class="po-video__play" aria-hidden="true">
						<svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="36" cy="36" r="36" fill="rgba(0,0,0,0.5)"/>
							<polygon points="28,20 56,36 28,52" fill="#fff"/>
						</svg>
					</span>
				</button>
			<?php else: ?>
				<?php /* Unknown embed URL – render as direct iframe fallback */ ?>
				<iframe class="po-video__media"
						src="<?php echo esc_url($video_url); ?>"
						frameborder="0"
						allow="autoplay; fullscreen; picture-in-picture"
						allowfullscreen
						loading="lazy">
				</iframe>
			<?php endif; ?>

		<?php elseif ($video_type === 'file'): ?>
			<video class="po-video__media"
				   <?php if ($poster_image): ?>poster="<?php echo esc_url($poster_image); ?>"<?php endif; ?>
				   <?php if ($autoplay): ?>autoplay<?php endif; ?>
				   <?php if ($loop): ?>loop<?php endif; ?>
				   <?php if ($muted): ?>muted<?php endif; ?>
				   <?php if ($autoplay): ?>playsinline<?php endif; ?>
				   controls
				   preload="metadata">
				<source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
			</video>
		<?php endif; ?>
	</div>

	<?php if ($caption): ?>
		<figcaption class="po-video__caption"><?php echo esc_html($caption); ?></figcaption>
	<?php endif; ?>
</figure>
