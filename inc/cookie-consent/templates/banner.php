<?php
/**
 * Cookie Consent Banner Template
 *
 * @package ParkourONE
 */

if (!defined('ABSPATH')) {
	exit;
}

$consent_manager = PO_Consent_Manager::get_instance();
$categories = $consent_manager->get_category_info();
$services_by_category = $consent_manager->get_services_by_category();
$current_consent = $consent_manager->get_current_consent();

$privacy_url = get_privacy_policy_url() ?: '/datenschutz/';
$imprint_url = '/impressum/';

// DNT/GPC Signal erkennen
$has_dnt = !empty($_SERVER['HTTP_DNT']);
$has_gpc = !empty($_SERVER['HTTP_SEC_GPC']);
$has_privacy_signal = $has_dnt || $has_gpc;
?>

<!-- Cookie Consent Banner -->
<div id="po-consent-banner" class="po-consent-banner" role="dialog" aria-modal="true" aria-labelledby="po-consent-title" <?php echo $consent_manager->should_show_banner() ? '' : 'style="display:none;"'; ?>>
	<div class="po-consent-banner__overlay"></div>

	<div class="po-consent-banner__container">
		<!-- Hauptansicht -->
		<div class="po-consent-banner__main" id="po-consent-main">
			<div class="po-consent-banner__content">
				<h2 id="po-consent-title" class="po-consent-banner__title">Wir respektieren Ihre Privatsphäre</h2>
				<?php if ($has_privacy_signal): ?>
					<p class="po-consent-banner__privacy-signal">
						<strong>
							<?php if ($has_gpc): ?>
								Wir haben Ihr Global Privacy Control (GPC) Signal erkannt.
							<?php else: ?>
								Wir haben Ihr Do Not Track (DNT) Signal erkannt.
							<?php endif; ?>
						</strong>
						Tracking ist standardmäßig deaktiviert. Sie können dies unten anpassen.
					</p>
				<?php endif; ?>
				<p class="po-consent-banner__text">
					Wir verwenden Cookies und ähnliche Technologien, um Ihre Erfahrung zu verbessern.
					Einige sind technisch notwendig, andere helfen uns die Website zu optimieren und Ihnen
					personalisierte Inhalte anzubieten.
				</p>
				<p class="po-consent-banner__links">
					<a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a>
					<span class="po-consent-banner__separator">|</span>
					<a href="<?php echo esc_url($imprint_url); ?>" target="_blank" rel="noopener">Impressum</a>
				</p>
			</div>

			<!-- DSGVO: Alle Buttons müssen gleichwertig dargestellt werden (BGH Planet49) -->
			<div class="po-consent-banner__actions">
				<button type="button" class="po-consent-btn po-consent-btn--primary" data-consent-action="accept-all">
					Alle akzeptieren
				</button>
				<button type="button" class="po-consent-btn po-consent-btn--secondary" data-consent-action="reject-all">
					Nur Notwendige
				</button>
				<button type="button" class="po-consent-btn po-consent-btn--secondary" data-consent-action="show-settings">
					Einstellungen
				</button>
			</div>
		</div>

		<!-- Detailansicht -->
		<div class="po-consent-banner__settings" id="po-consent-settings" style="display:none;">
			<div class="po-consent-banner__header">
				<h2 class="po-consent-banner__title">Cookie-Einstellungen</h2>
				<button type="button" class="po-consent-banner__close" data-consent-action="hide-settings" aria-label="Schließen">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<div class="po-consent-banner__categories">
				<?php foreach ($categories as $cat_id => $category): ?>
					<?php
					$is_required = !empty($category['required']);
					$is_checked = $is_required || ($current_consent && !empty($current_consent['categories'][$cat_id]));
					$services = $services_by_category[$cat_id] ?? [];
					?>
					<div class="po-consent-category" data-category="<?php echo esc_attr($cat_id); ?>">
						<div class="po-consent-category__header">
							<div class="po-consent-category__info">
								<h3 class="po-consent-category__title"><?php echo esc_html($category['name']); ?></h3>
								<p class="po-consent-category__desc"><?php echo esc_html($category['description']); ?></p>
							</div>
							<div class="po-consent-category__toggle">
								<?php if ($is_required): ?>
									<span class="po-consent-category__required">Immer aktiv</span>
								<?php else: ?>
									<label class="po-consent-toggle">
										<input
											type="checkbox"
											name="consent_<?php echo esc_attr($cat_id); ?>"
											value="<?php echo esc_attr($cat_id); ?>"
											<?php checked($is_checked); ?>
											data-consent-category="<?php echo esc_attr($cat_id); ?>"
										>
										<span class="po-consent-toggle__slider"></span>
									</label>
								<?php endif; ?>
							</div>
						</div>

						<?php if (!empty($services)): ?>
							<button type="button" class="po-consent-category__expand" aria-expanded="false">
								<span class="po-consent-category__expand-text"><?php echo count($services); ?> Cookie<?php echo count($services) > 1 ? 's' : ''; ?> anzeigen</span>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="6 9 12 15 18 9"></polyline>
								</svg>
							</button>

							<div class="po-consent-category__services" style="display:none;">
								<?php foreach ($services as $service): ?>
									<div class="po-consent-service">
										<div class="po-consent-service__name"><?php echo esc_html($service['name']); ?></div>
										<div class="po-consent-service__desc"><?php echo esc_html($service['description']); ?></div>
										<?php if (!empty($service['cookies'])): ?>
											<div class="po-consent-service__cookies">
												<strong>Cookies:</strong> <?php echo esc_html(implode(', ', $service['cookies'])); ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($service['cookie_duration'])): ?>
											<div class="po-consent-service__duration">
												<strong>Speicherdauer:</strong> <?php echo esc_html($service['cookie_duration']); ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($service['legal_basis'])): ?>
											<div class="po-consent-service__legal">
												<strong>Rechtsgrundlage:</strong> <?php echo esc_html($service['legal_basis']); ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($service['provider'])): ?>
											<div class="po-consent-service__provider">
												<strong>Anbieter:</strong> <?php echo esc_html($service['provider']); ?>
												<?php if (!empty($service['country']) && $service['country'] !== 'EU'): ?>
													<span class="po-consent-service__country">(<?php echo esc_html($service['country']); ?>)</span>
												<?php endif; ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($service['third_country_transfer'])): ?>
											<div class="po-consent-service__transfer">
												<strong>Drittlandtransfer:</strong> <?php echo esc_html($service['third_country_transfer']); ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($service['privacy_policy_url'])): ?>
											<a href="<?php echo esc_url($service['privacy_policy_url']); ?>" target="_blank" rel="noopener" class="po-consent-service__link">
												Datenschutzerklärung des Anbieters
											</a>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="po-consent-banner__footer">
				<div class="po-consent-banner__actions">
					<button type="button" class="po-consent-btn po-consent-btn--secondary" data-consent-action="reject-all">
						Alle ablehnen
					</button>
					<button type="button" class="po-consent-btn po-consent-btn--primary" data-consent-action="save-selection">
						Auswahl speichern
					</button>
					<button type="button" class="po-consent-btn po-consent-btn--primary" data-consent-action="accept-all">
						Alle akzeptieren
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Consent Settings Trigger (für Footer/Header) -->
<script>
// Event für externe Trigger (z.B. Link im Footer)
document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('[data-open-consent-settings]').forEach(function(el) {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			if (window.POConsentManager) {
				window.POConsentManager.showSettings();
			}
		});
	});
});
</script>
