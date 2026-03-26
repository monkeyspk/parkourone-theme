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
$kontakt_image = get_template_directory_uri() . '/assets/images/fallback/landscape/adults/1T2A6286.webp';
?>

<!-- wp:parkourone/page-header {"variant":"banner","title":"Kontakt","description":"","ctaText":"","ctaUrl":"","image":"<?php echo esc_url($kontakt_image); ?>","overlayOpacity":40,"align":"full"} /-->

<!-- wp:parkourone/inquiry-form {"headline":"","subtext":"","formType":"kontakt","showLocation":false,"showParticipants":false,"showDates":false,"showProjectLength":false,"showClassCount":false,"submitText":"Nachricht senden","backgroundColor":"white","align":"full"} /-->
