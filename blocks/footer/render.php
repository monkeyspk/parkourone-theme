<?php
// Footer-Optionen aus der Datenbank laden (Backend-Einstellungen haben Priorität)
$footer_options = get_option('parkourone_footer', []);

// Hilfsfunktion: Backend-Einstellungen > Block-Attribute > Default
// Backend hat Priorität, damit zentrale Einstellungen nicht von leeren Block-Defaults überschrieben werden
function po_footer_value($options_key, $attribute_value, $footer_options, $default = '') {
	// 1. Backend-Einstellungen (wenn nicht leer)
	if (!empty($footer_options[$options_key])) {
		return $footer_options[$options_key];
	}
	// 2. Block-Attribute (wenn nicht leer)
	if (!empty($attribute_value)) {
		return $attribute_value;
	}
	// 3. Default
	return $default;
}

$companyName = po_footer_value('company_name', $attributes['companyName'] ?? '', $footer_options, 'ParkourONE');
$companyAddress = po_footer_value('company_address', $attributes['companyAddress'] ?? '', $footer_options);
$socialInstagram = po_footer_value('social_instagram', $attributes['socialInstagram'] ?? '', $footer_options);
$socialYoutube = po_footer_value('social_youtube', $attributes['socialYoutube'] ?? '', $footer_options);
$socialPodcast = po_footer_value('social_podcast', $attributes['socialPodcast'] ?? '', $footer_options);
$phone = po_footer_value('phone', $attributes['phone'] ?? '', $footer_options);
$email = po_footer_value('email', $attributes['email'] ?? '', $footer_options);
$contactFormUrl = po_footer_value('contact_form_url', $attributes['contactFormUrl'] ?? '', $footer_options);
$phoneHours = po_footer_value('phone_hours', $attributes['phoneHours'] ?? '', $footer_options);
$zentraleName = po_footer_value('zentrale_name', $attributes['zentraleName'] ?? '', $footer_options);
$zentraleUrl = po_footer_value('zentrale_url', $attributes['zentraleUrl'] ?? '', $footer_options);
$newsletterHeadline = po_footer_value('newsletter_headline', $attributes['newsletterHeadline'] ?? '', $footer_options);
$newsletterText = po_footer_value('newsletter_text', $attributes['newsletterText'] ?? '', $footer_options);
$copyrightYear = po_footer_value('copyright_year', $attributes['copyrightYear'] ?? '', $footer_options, date('Y'));

// Standorte: Backend hat Priorität, sonst Block-Attribute
$standorte = !empty($footer_options['standorte']) ? $footer_options['standorte'] : ($attributes['standorte'] ?? []);

// Legal Pages - automatisch verlinken wenn vorhanden
$impressumUrl = $attributes['impressumUrl'] ?? '';
$datenschutzUrl = $attributes['datenschutzUrl'] ?? '';
$cookiesUrl = $attributes['cookiesUrl'] ?? '';

// Fallback zu automatisch erstellten Seiten
if (empty($impressumUrl) || $impressumUrl === '#') {
	$impressum_page = get_page_by_path('impressum');
	$impressumUrl = $impressum_page ? get_permalink($impressum_page) : '/impressum/';
}
if (empty($datenschutzUrl) || $datenschutzUrl === '#') {
	$datenschutz_page = get_page_by_path('datenschutz');
	$datenschutzUrl = $datenschutz_page ? get_permalink($datenschutz_page) : '/datenschutz/';
}
if (empty($cookiesUrl) || $cookiesUrl === '#') {
	// Cookies-Info ist Teil der Datenschutzseite (Abschnitt 4)
	$cookiesUrl = $datenschutzUrl . '#cookies';
}
?>
<footer class="po-footer alignfull">
	<div class="po-footer__main">
		<div class="po-footer__col">
			<strong><?php echo esc_html($companyName); ?></strong>
			<p style="white-space: pre-line;"><?php echo esc_html($companyAddress); ?></p>
			<div class="po-footer__social">
				<span>Follow Us:</span>
				<?php if ($socialInstagram && $socialInstagram !== '#'): ?>
					<a href="<?php echo esc_url($socialInstagram); ?>" aria-label="Instagram" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
					</a>
				<?php endif; ?>
				<?php if ($socialYoutube && $socialYoutube !== '#'): ?>
					<a href="<?php echo esc_url($socialYoutube); ?>" aria-label="YouTube" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
					</a>
				<?php endif; ?>
				<?php if ($socialPodcast && $socialPodcast !== '#'): ?>
					<a href="<?php echo esc_url($socialPodcast); ?>" aria-label="Podcast" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.5 17.5h3v-7h-3v7zm1.5-9c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5z"/></svg>
					</a>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="po-footer__col">
			<strong>Kontaktiere Uns</strong>
			<?php if ($phone): ?>
				<p><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p>
			<?php endif; ?>
			<?php if ($email): ?>
				<p><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
			<?php endif; ?>
			<?php if ($contactFormUrl && $contactFormUrl !== '#'): ?>
				<p><a href="<?php echo esc_url($contactFormUrl); ?>">Kontaktformular</a></p>
			<?php endif; ?>
			<?php if ($phoneHours): ?>
				<strong style="margin-top: 1rem;">Telefonzeiten:</strong>
				<p class="po-footer__hours"><?php echo nl2br(esc_html($phoneHours)); ?></p>
			<?php endif; ?>
		</div>
		
		<div class="po-footer__col">
			<strong>Standorte</strong>
			<div class="po-footer__standorte">
				<?php foreach ($standorte as $s): ?>
					<a href="<?php echo esc_url($s['url'] ?? '#'); ?>"><?php echo esc_html($s['name']); ?></a>
				<?php endforeach; ?>
			</div>
			<?php if ($zentraleName): ?>
				<div class="po-footer__zentrale">
					<strong>Zentrale</strong>
					<a href="<?php echo esc_url($zentraleUrl); ?>"><?php echo esc_html($zentraleName); ?></a>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="po-footer__col">
			<?php if ($newsletterHeadline): ?>
				<strong><?php echo esc_html($newsletterHeadline); ?></strong>
			<?php endif; ?>
			<?php if ($newsletterText): ?>
				<p><?php echo esc_html($newsletterText); ?></p>
			<?php endif; ?>
			<form class="po-footer__newsletter-form" action="#" method="post">
				<input type="email" class="po-footer__newsletter-input" placeholder="E-Mail Adresse" required>
				<button type="submit" class="po-footer__newsletter-btn">Jetzt eintragen</button>
			</form>
		</div>
	</div>
	
	<div class="po-footer__bottom">
		<div class="po-footer__logo">
			<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/admin-logo.png'); ?>" alt="ParkourONE">
		</div>
		<div class="po-footer__legal">
			<a href="<?php echo esc_url($impressumUrl); ?>">Impressum</a>
			<a href="<?php echo esc_url($datenschutzUrl); ?>">Datenschutz</a>
			<a href="<?php echo esc_url($cookiesUrl); ?>">Cookies</a>
		</div>
		<div class="po-footer__copyright">
			© <?php echo esc_html($copyrightYear); ?> ParkourONE All rights reserved.
		</div>
	</div>
</footer>
