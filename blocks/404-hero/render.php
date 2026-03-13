<?php
/**
 * 404 Hero Block — Parkour-styled error page hero.
 * Reuses po-hero CSS classes for consistent design.
 */

// Random fallback image from Parkour categories
$fallback_categories = ['adults', 'kids', 'juniors'];
$random_category = $fallback_categories[array_rand($fallback_categories)];

$desktopImage = parkourone_get_fallback_image($random_category, 'landscape')
	?: (get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg');
$mobileImage = parkourone_get_fallback_image($random_category, 'portrait')
	?: (get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg');
?>

<section class="po-hero po-hero--centered po-404-hero">
	<picture class="po-hero__bg-picture" style="display:block;position:absolute;inset:0;z-index:0;">
		<source media="(min-width: 768px)" srcset="<?php echo esc_url($desktopImage); ?>">
		<img src="<?php echo esc_url($mobileImage); ?>" alt="" class="po-hero__bg-img"
			 style="display:block;width:100%;height:100%;object-fit:cover;object-position:center;"
			 loading="eager" decoding="async" width="1920" height="1080">
	</picture>

	<div class="po-hero__overlay" style="background: rgba(0, 0, 0, 0.55)"></div>

	<div class="po-hero__content">
		<span class="po-hero__eyebrow">Seite nicht gefunden</span>

		<h1 class="po-hero__headline">
			<span class="po-hero__highlight">404</span>
		</h1>

		<p class="po-hero__subtext">
			Diese Seite existiert nicht mehr — aber dein nächstes Training wartet!
		</p>

		<div class="po-hero__buttons">
			<a href="<?php echo esc_url(home_url('/')); ?>" class="po-hero__button po-hero__button--primary">
				Zur Startseite
			</a>
			<a href="#stundenplan" class="po-hero__button po-hero__button--secondary">
				Stundenplan ansehen
			</a>
		</div>
	</div>

	<div class="po-hero__scroll-hint">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M12 5v14M5 12l7 7 7-7"/>
		</svg>
	</div>
</section>
