<?php
/**
 * Title: Startseite
 * Slug: parkourone/page-startseite
 * Categories: parkourone-seiten
 * Description: SEO-optimierte Startseiten-Vorlage - passt sich automatisch an den Standort an
 * Keywords: startseite, homepage, hero, vollständig
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 */

// Standort automatisch erkennen
$site_location = function_exists('parkourone_get_site_location') ? parkourone_get_site_location() : ['name' => 'ParkourONE', 'slug' => '', 'detected' => false];
$site_name = $site_location['name'];
// Nur bei erkannten Standorten (im Mapping) Stadt-spezifische Texte verwenden
$is_city_site = !empty($site_location['detected']) && !empty($site_location['slug']) && !in_array($site_location['slug'], ['parkourone', 'www', 'new', 'staging', 'dev', 'test', 'localhost']);

// Standorte die "in der" statt "in" benötigen (Länder mit Artikel)
$locations_with_article = ['schweiz', 'türkei', 'ukraine', 'slowakei', 'mongolei'];
$needs_article = in_array($site_location['slug'], $locations_with_article);
$in_location = $needs_article ? "in der {$site_name}" : "in {$site_name}";

// SEO-optimierte Texte basierend auf Standort
if ($is_city_site) {
	$hero_eyebrow = "ParkourONE {$site_name}";
	$hero_headline = "Parkour {$in_location}";
	$hero_subtext = "Professionelles Parkour-Training für alle Altersgruppen {$in_location}. Erfahrene Coaches, sichere Umgebung, starke Community.";
	$klassen_headline = "Parkour-Klassen {$in_location}";
	$faq_headline = "Häufige Fragen zu Parkour {$in_location}";
} else {
	$hero_eyebrow = "Parkour für alle";
	$hero_headline = "Stärke deinen Körper, schärfe deinen Geist";
	$hero_subtext = "Entdecke Parkour bei ParkourONE – professionelles Training für alle Altersgruppen.";
	$klassen_headline = "Unsere Klassen";
	$faq_headline = "Häufige Fragen zu Parkour";
}

// JSON für komplexe Attribute
$hero_stats = json_encode([
	['number' => '1500', 'suffix' => '+', 'label' => 'Schüler:innen'],
	['number' => '25', 'suffix' => '', 'label' => 'Jahre Erfahrung'],
	['number' => '7', 'suffix' => '', 'label' => 'Standorte']
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);

$testimonials = json_encode([
	['text' => 'Parkour hat mein Leben verändert. Ich habe nicht nur körperliche Stärke gewonnen, sondern auch mentale Stärke und ein neues Selbstvertrauen.', 'author' => 'Sarah M.', 'role' => 'Schülerin seit 2019', 'imageUrl' => ''],
	['text' => 'Die Community bei ParkourONE ist unglaublich. Hier unterstützt jeder jeden – vom Anfänger bis zum Profi.', 'author' => 'Max K.', 'role' => 'Schüler seit 2021', 'imageUrl' => '']
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);

// Pattern Content ausgeben
echo '<!-- wp:parkourone/hero {"eyebrow":"' . esc_attr($hero_eyebrow) . '","headline":"' . esc_attr($hero_headline) . '","subtext":"' . esc_attr($hero_subtext) . '","buttonText":"Jetzt starten","buttonUrl":"#stundenplan","videoUrl":"https://www.youtube.com/watch?v=kb5XFFvQjYs","videoButtonText":"Film ansehen","stats":' . $hero_stats . ',"overlayOpacity":55,"align":"full"} /-->';
?>

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/zielgruppen-grid {"headline":"Was ist dein nächster Sprung?","subtext":"Wähle deine Altersgruppe und finde den passenden Kurs","anchor":"zielgruppen","align":"wide"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/usp-slider {"headline":"Warum ParkourONE?","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php echo '<!-- wp:parkourone/testimonial-highlight {"quotes":' . $testimonials . ',"layout":"double","style":"light","align":"full"} /-->'; ?>

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/steps-carousel {"headline":"So startest du","subheadline":"In 4 einfachen Schritten zum ersten Training","ageCategory":"default","backgroundColor":"white","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php echo '<!-- wp:parkourone/klassen-slider {"headline":"' . esc_attr($klassen_headline) . '","filterMode":"both","hideIfEmpty":true,"align":"full"} /-->'; ?>

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/about-section {"subheadline":"ABOUT","headline":"ParkourONE","text":"Bei ParkourONE glauben wir an das Recht auf persönliches Wohlbefinden und Wohlstand sowie an die Kraft der Gemeinschaft. Unter dem Motto 'ONE for all – all for ONE' möchten wir dich unterstützen, deine Träume zu verwirklichen. Durch Parkour möchten wir unsere Schüler_innen inspirieren, fördern und herausfordern.","ctaText":"Unser Team","ctaUrl":"/team/","mediaType":"image","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/stundenplan {"headline":"Stundenplan","buttonText":"Probetraining buchen","anchor":"stundenplan"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/angebote-karussell {"headline":"Angebote & Workshops","subtext":"Entdecke unser vielfältiges Programm","align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php echo '<!-- wp:parkourone/faq {"headline":"' . esc_attr($faq_headline) . '","category":"allgemein","limit":10,"backgroundColor":"light","align":"full"} /-->'; ?>

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
