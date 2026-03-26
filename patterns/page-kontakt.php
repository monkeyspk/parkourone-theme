<?php
/**
 * Title: Kontakt
 * Slug: parkourone/page-kontakt
 * Categories: parkourone-seiten
 * Description: Kontaktseite mit Kontaktformular
 * Keywords: kontakt, formular, anfrage, kontaktformular
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 */

// Fixes Bild für Kontakt-Banner aus Theme-Assets
$kontakt_img = esc_url(get_template_directory_uri() . '/assets/images/fallback/landscape/adults/IMG_8566-1.jpg');
?>

<!-- wp:parkourone/page-header <?php echo json_encode([
	'variant' => 'banner',
	'title' => 'Kontakt',
	'titleAccent' => 'none',
	'description' => '',
	'ctaText' => '',
	'ctaUrl' => '',
	'image' => $kontakt_img,
	'overlayOpacity' => 40,
	'align' => 'full',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> /-->

<!-- wp:parkourone/inquiry-form {"headline":"","subtext":"","formType":"kontakt","showLocation":false,"showParticipants":false,"showDates":false,"showProjectLength":false,"showClassCount":false,"submitText":"Nachricht senden","backgroundColor":"white","align":"full"} /-->
