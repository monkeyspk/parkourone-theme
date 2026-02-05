<?php
/**
 * Title: Probetraining buchen
 * Slug: parkourone/page-probetraining-buchen
 * Categories: parkourone-seiten
 * Description: Buchungsseite für Probetraining mit Steps, Event-Liste und FAQ
 * Keywords: probetraining, buchen, booking, events
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 */

// Site-Standort für dynamische Inhalte
$site_location = function_exists('parkourone_get_site_location') ? parkourone_get_site_location() : ['name' => 'ParkourONE', 'slug' => 'parkourone'];
$site_name = $site_location['name'];
$probetraining_price = function_exists('parkourone_get_probetraining_price') ? parkourone_get_probetraining_price() : '€15';
?>
<!-- wp:parkourone/steps-carousel {"headline":"So buchst du dein Probetraining","subheadline":"In 4 einfachen Schritten zum ersten Training","steps":[{"title":"Klasse wählen","description":"Wähle unten die passende Klasse für dein Alter und Level aus. Wir haben Kurse für Minis (4-5), Kids (6-12), Juniors (12-18) und Adults (18+).","icon":"users"},{"title":"Termin aussuchen","description":"Finde einen Termin, der in deinen Zeitplan passt. Unsere Kurse finden mehrmals pro Woche statt.","icon":"calendar"},{"title":"Anmelden","description":"Das Probetraining kostet <?php echo esc_attr($probetraining_price); ?>. Bei Anmeldung wird dieser Betrag mit dem ersten Monatsbeitrag verrechnet.","icon":"check"},{"title":"Loslegen","description":"Komm in bequemer Sportkleidung vorbei. Unsere Coaches zeigen dir alles – keine Vorkenntnisse nötig!","icon":"location"}],"backgroundColor":"light","align":"full"} /-->

<!-- wp:spacer {"height":"80px"} -->
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:0;padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">

<!-- wp:heading {"textAlign":"center","level":2,"style":{"spacing":{"margin":{"bottom":"1rem"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:1rem">Wähle dein Probetraining</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"2rem"}}}} -->
<p class="has-text-align-center" style="margin-bottom:2rem">Filtere nach Altersgruppe, Standort oder Wochentag und finde den perfekten Kurs für dich.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[event_list show_filter="true"]
<!-- /wp:shortcode -->

</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:parkourone/faq {"headline":"Häufige Fragen zum Probetraining","category":"probetraining","limit":20,"backgroundColor":"light","align":"full"} /-->
