<?php
/**
 * Title: Team-Seite
 * Slug: parkourone/page-team
 * Categories: parkourone-seiten
 * Description: Team-Seite mit Storytelling-Struktur: Menschen im Fokus, emotionaler Einstieg, klarer Flow
 * Keywords: team, trainer, coaches, schule, standort
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 */

// Bilder-URLs
$theme_uri = get_template_directory_uri();
$hero_image = $theme_uri . '/assets/images/fallback/adults/EveryONE_5MP-149-von-152-scaled.jpg';
$testimonial_image = $theme_uri . '/assets/images/fallback/adults/IMG_0716-scaled.jpg';
?>

<!-- wp:parkourone/page-header {"variant":"fullscreen","title":"Die Menschen hinter der Bewegung","description":"Parkour ist mehr als Sport. Es sind die Menschen, die es besonders machen. Lerne unser Team kennen.","image":"<?php echo esc_url($hero_image); ?>","ctaText":"Team kennenlernen","ctaUrl":"#team","ctaSecondaryText":"Offene Stellen","ctaSecondaryUrl":"#jobs","align":"full"} /-->

<!-- wp:parkourone/team-grid {"headline":"Unser Team","intro":"Ob auf dem Trainingsplatz oder hinter den Kulissen - jede:r bei ParkourONE brennt für Bewegung und Menschen.","align":"wide","anchor":"team"} /-->

<!-- wp:parkourone/testimonial-highlight {"quotes":[{"text":"Bei ParkourONE geht es nicht darum, der Beste zu sein. Es geht darum, jeden Tag ein bisschen besser zu werden als gestern. Das versuche ich meinen Schülern zu vermitteln.","author":"Coach","role":"ParkourONE","imageUrl":"<?php echo esc_url($testimonial_image); ?>"}],"layout":"single","style":"dark","align":"full"} /-->

<!-- wp:parkourone/trust-education {"headline":"TRUST Education","intro":"Bei ParkourONE arbeiten wir nach TRUST Education - unserer eigenen pädagogischen Methode. Sie verbindet körperliches Training mit persönlicher Entwicklung.","backgroundColor":"white","align":"full"} /-->

<!-- wp:parkourone/job-cards {"headline":"Werde einer von uns","intro":"Du teilst unsere Leidenschaft? Wir suchen Menschen, die mit uns wachsen wollen.","anchor":"jobs","jobs":[{"title":"Parkour Coach","type":"Teilzeit / Vollzeit","desc":"Du brennst für Parkour und möchtest deine Leidenschaft weitergeben?","fullDescription":"Als Coach bei ParkourONE leitest du Trainings für verschiedene Altersgruppen und hilfst unseren Schüler:innen, über sich hinauszuwachsen. Du wirst Teil eines engagierten Teams, das Parkour nicht nur als Sport, sondern als Lebensphilosophie versteht.","requirements":"Erfahrung im Parkour-Training\nFreude am Unterrichten\nZuverlässigkeit und Teamfähigkeit\nBereitschaft zur Weiterbildung (TRUST-Zertifizierung)","benefits":"Faire Vergütung\nFlexible Arbeitszeiten\nKostenloses Training\nFort- und Weiterbildungen\nEin motiviertes Team","howToApply":"Schick uns eine kurze Vorstellung von dir, deinen Parkour-Hintergrund und warum du bei uns arbeiten möchtest.","contactEmail":"jobs@parkourone.com","ctaText":"Mehr erfahren"},{"title":"Praktikum","type":"3-6 Monate","desc":"Sammle erste Erfahrungen in der Welt des Parkour-Trainings.","fullDescription":"In unserem Praktikum lernst du von erfahrenen Coaches und unterstützt uns bei Training und Organisation. Du bekommst Einblicke in alle Bereiche einer Parkour-Schule.","requirements":"Interesse an Parkour und Bewegung\nMindestalter 18 Jahre\nZuverlässigkeit\nOffenheit für Neues","benefits":"Praktische Erfahrung\nMentoring durch erfahrene Coaches\nKostenloses Training\nMöglichkeit zur Übernahme","howToApply":"Bewirb dich mit einem kurzen Motivationsschreiben und deinem Lebenslauf.","contactEmail":"praktikum@parkourone.com","ctaText":"Mehr erfahren"}],"backgroundColor":"#f5f5f7","align":"full"} /-->

<!-- wp:parkourone/schulen-grid {"headline":"Finde uns in deiner Stadt","intro":"ParkourONE ist an mehreren Standorten vertreten. Entdecke unsere Schulen.","hideCurrentSchool":true,"backgroundColor":"#ffffff","align":"full"} /-->
