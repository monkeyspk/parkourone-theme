<?php
/**
 * Page Header Block - 3 Varianten
 * Centered | Split | Fullscreen
 */

$variant = $attributes['variant'] ?? 'centered';
$title = $attributes['title'] ?? 'Parkour Training';
$title_accent = $attributes['titleAccent'] ?? '';
$description = $attributes['description'] ?? '';
$image = $attributes['image'] ?? '';
$image_alt = $attributes['imageAlt'] ?? '';
$cta_text = $attributes['ctaText'] ?? 'Probetraining buchen';
$cta_url = $attributes['ctaUrl'] ?? '#probetraining';
$cta_secondary_text = $attributes['ctaSecondaryText'] ?? '';
$cta_secondary_url = $attributes['ctaSecondaryUrl'] ?? '';
$stats = $attributes['stats'] ?? [];
$accent_color = $attributes['accentColor'] ?? '#0066cc';
$overlay_opacity = $attributes['overlayOpacity'] ?? 50;
$rotation = $attributes['imageRotation'] ?? 2;

// Fallback-Bild
if (empty($image)) {
	$image = get_template_directory_uri() . '/assets/images/fallback/juniors/G1A2050-scaled.jpg';
}

// CTA Arrow SVG
$arrow_svg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
?>

<?php if ($variant === 'centered'): ?>
<!-- ========== VARIANTE A: CENTERED ========== -->
<section class="po-ph po-ph--centered alignfull" style="--accent-color: <?php echo esc_attr($accent_color); ?>">
	<div class="po-ph__container po-ph__container--centered">
		<div class="po-ph__content po-ph__content--centered">
			<h1 class="po-ph__title po-ph__title--centered">
				<?php echo wp_kses_post($title); ?>
				<?php if ($title_accent): ?>
					<span class="po-ph__title-accent"><?php echo wp_kses_post($title_accent); ?></span>
				<?php endif; ?>
			</h1>

			<?php if ($description): ?>
				<p class="po-ph__description po-ph__description--centered"><?php echo wp_kses_post($description); ?></p>
			<?php endif; ?>

			<?php if ($cta_text || $cta_secondary_text): ?>
			<div class="po-ph__actions po-ph__actions--centered">
				<?php if ($cta_text && $cta_url): ?>
					<a href="<?php echo esc_url($cta_url); ?>" class="po-ph__cta po-ph__cta--primary">
						<?php echo esc_html($cta_text); ?>
					</a>
				<?php endif; ?>

				<?php if ($cta_secondary_text && $cta_secondary_url): ?>
					<a href="<?php echo esc_url($cta_secondary_url); ?>" class="po-ph__cta po-ph__cta--secondary">
						<?php echo esc_html($cta_secondary_text); ?>
						<?php echo $arrow_svg; ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php if (!empty($stats)): ?>
		<div class="po-ph__stats">
			<?php foreach ($stats as $stat): ?>
				<div class="po-ph__stat">
					<span class="po-ph__stat-number" data-target="<?php echo esc_attr($stat['number'] ?? 0); ?>">0</span>
					<span class="po-ph__stat-label"><?php echo esc_html($stat['label'] ?? ''); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</section>

<?php elseif ($variant === 'split'): ?>
<!-- ========== VARIANTE B: SPLIT ========== -->
<section class="po-ph po-ph--split alignfull" style="--accent-color: <?php echo esc_attr($accent_color); ?>">
	<div class="po-ph__container po-ph__container--split">
		<div class="po-ph__content po-ph__content--split">
			<h1 class="po-ph__title po-ph__title--split">
				<?php echo wp_kses_post($title); ?>
				<?php if ($title_accent): ?>
					<span class="po-ph__title-accent"><?php echo wp_kses_post($title_accent); ?></span>
				<?php endif; ?>
			</h1>

			<?php if ($description): ?>
				<p class="po-ph__description"><?php echo wp_kses_post($description); ?></p>
			<?php endif; ?>

			<?php if ($cta_text || $cta_secondary_text): ?>
			<div class="po-ph__actions">
				<?php if ($cta_text && $cta_url): ?>
					<a href="<?php echo esc_url($cta_url); ?>" class="po-ph__cta po-ph__cta--primary">
						<?php echo esc_html($cta_text); ?>
					</a>
				<?php endif; ?>

				<?php if ($cta_secondary_text && $cta_secondary_url): ?>
					<a href="<?php echo esc_url($cta_secondary_url); ?>" class="po-ph__cta po-ph__cta--secondary">
						<?php echo esc_html($cta_secondary_text); ?>
						<?php echo $arrow_svg; ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="po-ph__visual po-ph__visual--split">
			<div class="po-ph__image-wrapper po-ph__image-wrapper--split" style="--rotation: <?php echo esc_attr($rotation); ?>deg">
				<img
					src="<?php echo esc_url($image); ?>"
					alt="<?php echo esc_attr($image_alt); ?>"
					class="po-ph__image"
					loading="eager"
				>
			</div>
		</div>
	</div>
</section>

<?php else: ?>
<!-- ========== VARIANTE C: FULLSCREEN ========== -->
<section class="po-ph po-ph--fullscreen alignfull" style="--overlay-opacity: <?php echo esc_attr($overlay_opacity / 100); ?>; --accent-color: <?php echo esc_attr($accent_color); ?>">
	<div class="po-ph__background">
		<img
			src="<?php echo esc_url($image); ?>"
			alt="<?php echo esc_attr($image_alt); ?>"
			class="po-ph__bg-image"
			loading="eager"
		>
		<div class="po-ph__overlay"></div>
	</div>

	<div class="po-ph__container po-ph__container--fullscreen">
		<div class="po-ph__content po-ph__content--fullscreen">
			<h1 class="po-ph__title po-ph__title--fullscreen">
				<?php echo wp_kses_post($title); ?>
				<?php if ($title_accent): ?>
					<span class="po-ph__title-accent"><?php echo wp_kses_post($title_accent); ?></span>
				<?php endif; ?>
			</h1>

			<?php if ($description): ?>
				<p class="po-ph__description po-ph__description--fullscreen"><?php echo wp_kses_post($description); ?></p>
			<?php endif; ?>

			<?php if ($cta_text || $cta_secondary_text): ?>
			<div class="po-ph__actions po-ph__actions--fullscreen">
				<?php if ($cta_text && $cta_url): ?>
					<a href="<?php echo esc_url($cta_url); ?>" class="po-ph__cta po-ph__cta--primary po-ph__cta--light">
						<?php echo esc_html($cta_text); ?>
					</a>
				<?php endif; ?>

				<?php if ($cta_secondary_text && $cta_secondary_url): ?>
					<a href="<?php echo esc_url($cta_secondary_url); ?>" class="po-ph__cta po-ph__cta--secondary po-ph__cta--light">
						<?php echo esc_html($cta_secondary_text); ?>
						<?php echo $arrow_svg; ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>
