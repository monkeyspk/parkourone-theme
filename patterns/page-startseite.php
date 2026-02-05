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
$site_location = function_exists('parkourone_get_site_location') ? parkourone_get_site_location() : ['name' => 'ParkourONE', 'slug' => ''];
$site_name = $site_location['name'];
$is_city_site = !empty($site_location['slug']) && $site_location['slug'] !== 'parkourone';

// SEO-optimierte Texte basierend auf Standort
if ($is_city_site) {
	$hero_eyebrow = "ParkourONE {$site_name}";
	$hero_headline = "Parkour in {$site_name}";
	$hero_subtext = "Professionelles Parkour-Training für alle Altersgruppen in {$site_name}. Erfahrene Coaches, sichere Umgebung, starke Community.";
	$klassen_headline = "Parkour-Klassen in {$site_name}";
	$faq_headline = "Häufige Fragen zu Parkour in {$site_name}";
	$testimonial_headline = "Das sagen unsere Teilnehmer";
} else {
	$hero_eyebrow = "Parkour für alle";
	$hero_headline = "Stärke deinen Körper, schärfe deinen Geist";
	$hero_subtext = "Entdecke Parkour bei ParkourONE – professionelles Training für alle Altersgruppen.";
	$klassen_headline = "Unsere Klassen";
	$faq_headline = "Häufige Fragen zu Parkour";
	$testimonial_headline = "Das sagen unsere Teilnehmer";
}

// JSON für komplexe Attribute escapen
$hero_stats = json_encode([
	['number' => '1500', 'suffix' => '+', 'label' => 'Schüler:innen'],
	['number' => '25', 'suffix' => '', 'label' => 'Jahre Erfahrung'],
	['number' => '7', 'suffix' => '', 'label' => 'Standorte']
], JSON_UNESCAPED_UNICODE);

$stats_counter = json_encode([
	['number' => '1500', 'suffix' => '+', 'label' => 'Schüler:innen', 'subtext' => 'vertrauen uns'],
	['number' => '25', 'suffix' => '', 'label' => 'Jahre Erfahrung', 'subtext' => 'seit 1999'],
	['number' => '7', 'suffix' => '', 'label' => 'Standorte', 'subtext' => 'in Deutschland & Schweiz']
], JSON_UNESCAPED_UNICODE);

$testimonials = json_encode([
	['text' => 'Parkour hat mein Leben verändert. Ich habe nicht nur körperliche Stärke gewonnen, sondern auch mentale Stärke und ein neues Selbstvertrauen.', 'author' => 'Sarah M.', 'role' => 'Schülerin seit 2019', 'imageUrl' => ''],
	['text' => 'Die Community bei ParkourONE ist unglaublich. Hier unterstützt jeder jeden – vom Anfänger bis zum Profi.', 'author' => 'Max K.', 'role' => 'Schüler seit 2021', 'imageUrl' => '']
], JSON_UNESCAPED_UNICODE);
?>

<!-- wp:parkourone/hero {"eyebrow":"<?php echo esc_attr($hero_eyebrow); ?>","headline":"<?php echo esc_attr($hero_headline); ?>","subtext":"<?php echo esc_attr($hero_subtext); ?>","buttonText":"Jetzt starten","buttonUrl":"#stundenplan","videoUrl":"https://www.youtube.com/watch?v=kb5XFFvQjYs","videoButtonText":"Film ansehen","stats":<?php echo $hero_stats; ?>,"overlayOpacity":55,"align":"full"} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/zielgruppen-grid {"headline":"Was ist dein nächster Sprung?","subtext":"Wähle deine Altersgruppe und finde den passenden Kurs","anchor":"zielgruppen","align":"wide"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/stats-counter {"stats":<?php echo $stats_counter; ?>,"style":"light","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/usp-slider {"headline":"Warum ParkourONE?","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/testimonial-highlight {"quotes":<?php echo $testimonials; ?>,"layout":"double","style":"light","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/steps-carousel {"headline":"So startest du","subheadline":"In 4 einfachen Schritten zum ersten Training","ageCategory":"default","backgroundColor":"white","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/klassen-slider {"headline":"<?php echo esc_attr($klassen_headline); ?>","filterMode":"both","hideIfEmpty":true,"align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/about-section {"subheadline":"ABOUT","headline":"ParkourONE","text":"Bei ParkourONE glauben wir an das Recht auf persönliches Wohlbefinden und Wohlstand sowie an die Kraft der Gemeinschaft. Unter dem Motto 'ONE for all – all for ONE' möchten wir dich unterstützen, deine Träume zu verwirklichen. Durch Parkour möchten wir unsere Schüler_innen inspirieren, fördern und herausfordern.","ctaText":"Mehr erfahren","ctaUrl":"/ueber-uns/","mediaType":"image","align":"full"} /-->

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

<!-- wp:parkourone/faq {"headline":"<?php echo esc_attr($faq_headline); ?>","category":"allgemein","limit":10,"backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
