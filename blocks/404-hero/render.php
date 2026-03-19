<?php
/**
 * 404 Hero Block — Parkour-styled error page hero.
 * Reuses po-hero CSS classes for consistent design.
 */
$eyebrow = $attributes['eyebrow'] ?? 'Seite nicht gefunden';
$headline = $attributes['headline'] ?? '404';
$subtext = $attributes['subtext'] ?? 'Diese Seite existiert nicht mehr — aber dein nächstes Training wartet!';
$primaryButtonText = $attributes['primaryButtonText'] ?? 'Zur Startseite';
$primaryButtonUrl = $attributes['primaryButtonUrl'] ?? '/';
$secondaryButtonText = $attributes['secondaryButtonText'] ?? 'Stundenplan ansehen';
$secondaryButtonUrl = $attributes['secondaryButtonUrl'] ?? '#stundenplan';
$overlayOpacity = ($attributes['overlayOpacity'] ?? 55) / 100;

// Random fallback image from Parkour categories
$fallback_categories = ['adults', 'kids', 'juniors'];
$random_category = $fallback_categories[array_rand($fallback_categories)];

$desktopImage = parkourone_get_fallback_image($random_category, 'landscape')
    ?: (get_template_directory_uri() . '/assets/images/hero/startseite-desltop.jpg');
$mobileImage = parkourone_get_fallback_image($random_category, 'portrait')
    ?: (get_template_directory_uri() . '/assets/images/hero/mobile-startbild.jpg');

// Use home_url for "/" to get the correct absolute URL
if ($primaryButtonUrl === '/') {
    $primaryButtonUrl = home_url('/');
}
?>

<section class="po-hero po-hero--centered po-404-hero">
    <picture class="po-hero__bg-picture" style="display:block;position:absolute;inset:0;z-index:0;">
        <source media="(min-width: 768px)" srcset="<?php echo esc_url($desktopImage); ?>">
        <img src="<?php echo esc_url($mobileImage); ?>" alt="" class="po-hero__bg-img"
             style="display:block;width:100%;height:100%;object-fit:cover;object-position:center;"
             loading="eager" decoding="async" width="1920" height="1080">
    </picture>

    <div class="po-hero__overlay" style="background: rgba(0, 0, 0, <?php echo esc_attr($overlayOpacity); ?>)"></div>

    <div class="po-hero__content">
        <?php if ($eyebrow): ?>
            <span class="po-hero__eyebrow"><?php echo esc_html($eyebrow); ?></span>
        <?php endif; ?>

        <h1 class="po-hero__headline">
            <span class="po-hero__highlight"><?php echo esc_html($headline); ?></span>
        </h1>

        <?php if ($subtext): ?>
            <p class="po-hero__subtext"><?php echo esc_html($subtext); ?></p>
        <?php endif; ?>

        <div class="po-hero__buttons">
            <?php if ($primaryButtonText): ?>
                <a href="<?php echo esc_url($primaryButtonUrl); ?>" class="po-hero__button po-hero__button--primary">
                    <?php echo esc_html($primaryButtonText); ?>
                </a>
            <?php endif; ?>
            <?php if ($secondaryButtonText): ?>
                <a href="<?php echo esc_url($secondaryButtonUrl); ?>" class="po-hero__button po-hero__button--secondary">
                    <?php echo esc_html($secondaryButtonText); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="po-hero__scroll-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12l7 7 7-7"/>
        </svg>
    </div>
</section>
