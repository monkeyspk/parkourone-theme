<?php
$token = get_query_var('coach_token');
$coach = $token ? parkourone_verify_coach_token($token) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>Coach-Profil bearbeiten – ParkourONE</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		
		body {
			font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
			background: #fff;
			color: #1d1d1f;
			line-height: 1.5;
			-webkit-font-smoothing: antialiased;
		}
		
		.coach-profile-page {
			max-width: 600px;
			margin: 0 auto;
			padding: 60px 24px 100px;
		}
		
		.coach-profile-logo {
			text-align: center;
			margin-bottom: 48px;
		}
		
		.coach-profile-logo img {
			height: 32px;
			width: auto;
		}
		
		.coach-profile-logo span {
			font-size: 20px;
			font-weight: 700;
			letter-spacing: -0.02em;
		}
		
		.coach-profile-intro {
			text-align: center;
			margin-bottom: 40px;
		}
		
		.coach-profile-intro h1 {
			font-size: 28px;
			font-weight: 600;
			margin: 0 0 16px;
			letter-spacing: -0.02em;
		}
		
		.coach-profile-intro p {
			color: #86868b;
			font-size: 15px;
			line-height: 1.6;
			max-width: 480px;
			margin: 0 auto;
		}
		
		.coach-profile-avatar {
			width: 80px;
			height: 80px;
			border-radius: 50%;
			background: #e5e5e5;
			background-size: cover;
			background-position: center;
			margin: 0 auto 20px;
		}
		
		.coach-profile-form {
			background: #f5f5f7;
			border-radius: 20px;
			padding: 32px;
		}
		
		.coach-profile-section {
			margin-bottom: 40px;
			padding-bottom: 32px;
			border-bottom: 1px solid #d2d2d7;
		}
		
		.coach-profile-section:last-child {
			margin-bottom: 0;
			padding-bottom: 0;
			border-bottom: none;
		}
		
		.coach-profile-section-title {
			font-size: 13px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #86868b;
			margin: 0 0 20px;
		}
		
		.coach-profile-field {
			margin-bottom: 24px;
		}
		
		.coach-profile-field:last-child {
			margin-bottom: 0;
		}
		
		.coach-profile-field label {
			display: block;
			font-size: 14px;
			font-weight: 500;
			color: #1d1d1f;
			margin-bottom: 8px;
		}
		
		.coach-profile-field input,
		.coach-profile-field textarea {
			width: 100%;
			padding: 14px 16px;
			font-size: 16px;
			border: 1px solid #d2d2d7;
			border-radius: 12px;
			background: #fff;
			transition: border-color 0.2s, box-shadow 0.2s;
			font-family: inherit;
		}
		
		.coach-profile-field textarea {
			min-height: 120px;
			resize: vertical;
		}
		
		.coach-profile-field input:focus,
		.coach-profile-field textarea:focus {
			outline: none;
			border-color: #0066cc;
			box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.15);
		}
		
		.coach-profile-field .hint {
			font-size: 13px;
			color: #86868b;
			margin-top: 6px;
		}
		
		.coach-profile-actions {
			display: flex;
			gap: 12px;
			margin-top: 32px;
		}
		
		.coach-profile-submit {
			flex: 1;
			padding: 16px 24px;
			background: #0066cc;
			color: #fff;
			font-size: 16px;
			font-weight: 600;
			border: none;
			border-radius: 14px;
			cursor: pointer;
			transition: background 0.2s;
		}
		
		.coach-profile-submit:hover {
			background: #0055b3;
		}
		
		.coach-profile-submit:disabled {
			background: #999;
			cursor: not-allowed;
		}
		
		.coach-profile-preview-btn {
			padding: 16px 24px;
			background: #e5e5e5;
			color: #1d1d1f;
			font-size: 16px;
			font-weight: 600;
			border: none;
			border-radius: 14px;
			cursor: pointer;
			transition: background 0.2s;
		}
		
		.coach-profile-preview-btn:hover {
			background: #d5d5d5;
		}
		
		.coach-profile-message {
			padding: 16px 20px;
			border-radius: 12px;
			margin-bottom: 24px;
			font-size: 15px;
		}
		
		.coach-profile-message.success {
			background: #d4edda;
			color: #155724;
		}
		
		.coach-profile-message.error {
			background: #f8d7da;
			color: #721c24;
		}
		
		.coach-image-upload {
			margin-bottom: 24px;
		}
		
		.coach-image-upload label {
			display: block;
			font-size: 14px;
			font-weight: 500;
			color: #1d1d1f;
			margin-bottom: 8px;
		}
		
		.coach-image-preview {
			position: relative;
			background: #fff;
			border: 2px dashed #d2d2d7;
			border-radius: 12px;
			overflow: hidden;
			cursor: pointer;
			transition: border-color 0.2s;
		}
		
		.coach-image-preview:hover {
			border-color: #0066cc;
		}
		
		.coach-image-preview.has-image {
			border-style: solid;
			cursor: default;
		}
		
		.coach-image-preview img {
			display: block;
			width: 100%;
			height: auto;
		}
		
		.coach-image-preview .placeholder {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
			color: #86868b;
		}
		
		.coach-image-preview .placeholder svg {
			width: 48px;
			height: 48px;
			margin-bottom: 12px;
			opacity: 0.5;
		}
		
		.coach-image-preview .placeholder span {
			font-size: 14px;
		}
		
		.coach-image-preview.has-image .placeholder {
			display: none;
		}
		
		.coach-image-actions {
			display: flex;
			gap: 8px;
			margin-top: 8px;
		}
		
		.coach-image-actions button {
			flex: 1;
			padding: 10px 16px;
			font-size: 14px;
			font-weight: 500;
			border: none;
			border-radius: 8px;
			cursor: pointer;
			transition: background 0.2s;
		}
		
		.coach-image-actions .btn-change {
			background: #e5e5e5;
			color: #1d1d1f;
		}
		
		.coach-image-actions .btn-change:hover {
			background: #d5d5d5;
		}
		
		.coach-image-actions .btn-delete {
			background: #f8d7da;
			color: #721c24;
		}
		
		.coach-image-actions .btn-delete:hover {
			background: #f1b0b7;
		}
		
		.coach-image-hint {
			font-size: 12px;
			color: #86868b;
			margin-top: 8px;
		}
		
		.cropper-modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.8);
			z-index: 10000;
			display: none;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		
		.cropper-modal-overlay.active {
			display: flex;
		}
		
		.cropper-modal {
			background: #fff;
			border-radius: 16px;
			max-width: 800px;
			width: 100%;
			max-height: 90vh;
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}
		
		.cropper-modal-header {
			padding: 20px 24px;
			border-bottom: 1px solid #e5e5e5;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.cropper-modal-header h3 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
		}
		
		.cropper-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			cursor: pointer;
			color: #86868b;
			padding: 0;
			line-height: 1;
		}
		
		.cropper-modal-body {
			padding: 24px;
			flex: 1;
			overflow: hidden;
		}
		
		.cropper-container-wrapper {
			max-height: 50vh;
			background: #f5f5f7;
			border-radius: 8px;
			overflow: hidden;
		}
		
		.cropper-container-wrapper img {
			display: block;
			max-width: 100%;
		}
		
		.cropper-modal-footer {
			padding: 20px 24px;
			border-top: 1px solid #e5e5e5;
			display: flex;
			gap: 12px;
			justify-content: flex-end;
		}
		
		.cropper-modal-footer button {
			padding: 12px 24px;
			font-size: 15px;
			font-weight: 500;
			border: none;
			border-radius: 10px;
			cursor: pointer;
			transition: background 0.2s;
		}
		
		.cropper-modal-footer .btn-cancel {
			background: #e5e5e5;
			color: #1d1d1f;
		}
		
		.cropper-modal-footer .btn-save {
			background: #0066cc;
			color: #fff;
		}
		
		.cropper-modal-footer .btn-save:hover {
			background: #0055b3;
		}
		
		.cropper-modal-footer .btn-save:disabled {
			background: #999;
			cursor: not-allowed;
		}
		
		/* Preview Modal Styles */
		.preview-modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(255, 255, 255, 0.5);
			backdrop-filter: blur(40px);
			-webkit-backdrop-filter: blur(40px);
			z-index: 9999;
			display: none;
			align-items: flex-start;
			justify-content: center;
			padding: 5vh 24px;
			overflow-y: auto;
		}
		
		.preview-modal-overlay.active {
			display: flex;
		}
		
		.preview-modal {
			background: #fff;
			border-radius: 24px;
			max-width: 900px;
			width: 100%;
			padding: 56px;
			box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
			position: relative;
		}
		
		.preview-modal-close {
			position: absolute;
			top: 20px;
			right: 20px;
			width: 32px;
			height: 32px;
			background: #1d1d1f;
			border: none;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.preview-modal-close svg {
			width: 16px;
			height: 16px;
			stroke: #fff;
			stroke-width: 2;
		}
		
		.preview-coach-header {
			margin-bottom: 32px;
		}
		
		.preview-coach-name {
			font-size: 42px;
			font-weight: 700;
			letter-spacing: -0.03em;
			margin: 0 0 8px;
		}
		
		.preview-coach-meta {
			font-size: 17px;
			color: #86868b;
		}
		
		.preview-coach-hero {
			margin-bottom: 32px;
			border-radius: 16px;
			overflow: hidden;
		}
		
		.preview-coach-hero img {
			width: 100%;
			height: auto;
			display: block;
		}
		
		.preview-coach-facts {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 16px;
			background: #f5f5f7;
			border-radius: 16px;
			padding: 24px;
			margin-bottom: 24px;
		}
		
		.preview-coach-fact dt {
			font-size: 13px;
			font-weight: 500;
			color: #86868b;
			text-transform: uppercase;
			letter-spacing: 0.02em;
			margin-bottom: 4px;
		}
		
		.preview-coach-fact dd {
			font-size: 17px;
			font-weight: 600;
			color: #1d1d1f;
			margin: 0;
		}
		
		.preview-coach-fact.full {
			grid-column: 1 / -1;
		}
		
		.preview-coach-fact.full dd {
			font-weight: 500;
			font-style: italic;
		}
		
		.preview-coach-card {
			background: #f5f5f7;
			border-radius: 20px;
			padding: 32px;
			margin-bottom: 20px;
		}
		
		.preview-coach-card p {
			font-size: 16px;
			line-height: 1.7;
			color: #6e6e73;
			margin: 0;
		}
		
		.preview-coach-card strong {
			color: #1d1d1f;
		}
		
		.preview-coach-card img {
			width: 100%;
			height: auto;
			border-radius: 12px;
			margin-top: 20px;
		}
		
		.preview-empty {
			text-align: center;
			padding: 40px;
			color: #86868b;
			font-style: italic;
		}
		
		@media (max-width: 600px) {
			.coach-profile-page {
				padding: 40px 20px 80px;
			}
			
			.coach-profile-form {
				padding: 24px 20px;
			}
			
			.coach-profile-actions {
				flex-direction: column;
			}
			
			.cropper-modal {
				max-height: 100vh;
				border-radius: 0;
			}
			
			.preview-modal {
				padding: 32px 24px;
				border-radius: 20px;
				margin: 20px 0;
			}
			
			.preview-coach-name {
				font-size: 28px;
			}
			
			.preview-coach-facts {
				grid-template-columns: 1fr;
			}
			
			.preview-coach-fact.full {
				grid-column: 1;
			}
		}
	</style>
</head>
<body>

<div class="coach-profile-page">
	<div class="coach-profile-logo">
		<span>ParkourONE</span>
	</div>
	
	<?php if ($coach): ?>
		<?php
		$api_image = get_post_meta($coach->ID, '_coach_api_image', true);
		$profile_image = get_post_meta($coach->ID, '_coach_profile_image', true);
		$avatar_image = !empty($profile_image) ? $profile_image : $api_image;
		$rolle = get_post_meta($coach->ID, '_coach_rolle', true);
		$standort = get_post_meta($coach->ID, '_coach_standort', true);
		$parkour_seit = get_post_meta($coach->ID, '_coach_parkour_seit', true);
		$po_seit = get_post_meta($coach->ID, '_coach_po_seit', true);
		$leitsatz = get_post_meta($coach->ID, '_coach_leitsatz', true);
		$kurzvorstellung = get_post_meta($coach->ID, '_coach_kurzvorstellung', true);
		$moment = get_post_meta($coach->ID, '_coach_moment', true);
		$hero_bild = get_post_meta($coach->ID, '_coach_hero_bild', true);
		$philosophie_bild = get_post_meta($coach->ID, '_coach_philosophie_bild', true);
		$moment_bild = get_post_meta($coach->ID, '_coach_moment_bild', true);
		$coach_name = $coach->post_title;
		?>
		
		<div class="coach-profile-intro">
			<?php if ($avatar_image): ?>
				<div class="coach-profile-avatar" style="background-image: url('<?php echo esc_url($avatar_image); ?>');"></div>
			<?php endif; ?>
			<h1>Hallo <?php echo esc_html($coach_name); ?></h1>
			<p>Mit diesem Formular kannst du dein Coach-Profil für die ParkourONE Webseite gestalten. Dein Profil hilft Interessierten zu sehen, mit wem sie trainieren werden – zeig ihnen, wer du bist!</p>
		</div>
		
		<div id="coach-message"></div>
		
		<form class="coach-profile-form" id="coach-profile-form">
			<input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
			<input type="hidden" id="coach-name" value="<?php echo esc_attr($coach_name); ?>">
			<input type="hidden" id="coach-api-image" value="<?php echo esc_attr($api_image); ?>">
			
			<div class="coach-profile-section">
				<h2 class="coach-profile-section-title">Steckbrief</h2>
				
				<div class="coach-profile-field">
					<label for="rolle">Deine Rolle</label>
					<input type="text" id="rolle" name="rolle" value="<?php echo esc_attr($rolle); ?>" placeholder="z.B. Head Coach, Coach, Gründer">
				</div>
				
				<div class="coach-profile-field">
					<label for="standort">Standort</label>
					<input type="text" id="standort" name="standort" value="<?php echo esc_attr($standort); ?>" placeholder="z.B. Bern, Zürich">
				</div>
				
				<div class="coach-profile-field">
					<label for="parkour_seit">Parkour seit</label>
					<input type="text" id="parkour_seit" name="parkour_seit" value="<?php echo esc_attr($parkour_seit); ?>" placeholder="z.B. 2015">
				</div>
				
				<div class="coach-profile-field">
					<label for="po_seit">Bei ParkourONE seit</label>
					<input type="text" id="po_seit" name="po_seit" value="<?php echo esc_attr($po_seit); ?>" placeholder="z.B. 2019">
				</div>
				
				<div class="coach-profile-field">
					<label for="leitsatz">Ein Satz, der dir Kraft gibt</label>
					<input type="text" id="leitsatz" name="leitsatz" value="<?php echo esc_attr($leitsatz); ?>" placeholder="z.B. Être fort pour être utile">
				</div>
			</div>
			
			<div class="coach-profile-section">
				<h2 class="coach-profile-section-title">Hero-Bild</h2>
				<p class="hint" style="margin: -12px 0 16px; font-size: 13px; color: #86868b;">Ein Action-Foto von dir beim Training – das erste was Besucher sehen.</p>
				
				<div class="coach-image-upload" data-field="hero_bild" data-ratio="1.778">
					<label>Action-Foto (Querformat 16:9)</label>
					<div class="coach-image-preview <?php echo $hero_bild ? 'has-image' : ''; ?>">
						<?php if ($hero_bild): ?>
							<img src="<?php echo esc_url($hero_bild); ?>" alt="">
						<?php endif; ?>
						<div class="placeholder">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
							<span>Klicken zum Hochladen</span>
						</div>
					</div>
					<div class="coach-image-actions" style="<?php echo $hero_bild ? '' : 'display:none;'; ?>">
						<button type="button" class="btn-change">Ändern</button>
						<button type="button" class="btn-delete">Löschen</button>
					</div>
					<p class="coach-image-hint">Min. 1200 × 675 Pixel, max. 10 MB</p>
					<input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;">
				</div>
			</div>
			
			<div class="coach-profile-section">
				<h2 class="coach-profile-section-title">Meine Geschichte</h2>
				<p class="hint" style="margin: -12px 0 16px; font-size: 13px; color: #86868b;">Wie bist du zu Parkour gekommen? Was bedeutet es dir?</p>
				
				<div class="coach-profile-field">
					<label for="kurzvorstellung">Erzähl deine Geschichte</label>
					<textarea id="kurzvorstellung" name="kurzvorstellung" placeholder="Schreib ein paar Sätze über dich und deinen Weg zu Parkour..."><?php echo esc_textarea($kurzvorstellung); ?></textarea>
				</div>
				
				<div class="coach-image-upload" data-field="philosophie_bild" data-ratio="1.333">
					<label>Bild zur Geschichte (Querformat 4:3)</label>
					<div class="coach-image-preview <?php echo $philosophie_bild ? 'has-image' : ''; ?>">
						<?php if ($philosophie_bild): ?>
							<img src="<?php echo esc_url($philosophie_bild); ?>" alt="">
						<?php endif; ?>
						<div class="placeholder">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
							<span>Klicken zum Hochladen</span>
						</div>
					</div>
					<div class="coach-image-actions" style="<?php echo $philosophie_bild ? '' : 'display:none;'; ?>">
						<button type="button" class="btn-change">Ändern</button>
						<button type="button" class="btn-delete">Löschen</button>
					</div>
					<p class="coach-image-hint">Min. 800 × 600 Pixel, max. 10 MB</p>
					<input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;">
				</div>
			</div>
			
			<div class="coach-profile-section">
				<h2 class="coach-profile-section-title">Prägender Moment</h2>
				<p class="hint" style="margin: -12px 0 16px; font-size: 13px; color: #86868b;">Ein besonderer Parkour-Moment, der dir in Erinnerung geblieben ist.</p>
				
				<div class="coach-profile-field">
					<label for="moment">Dein Moment</label>
					<textarea id="moment" name="moment" placeholder="Beschreib einen Moment, der dich geprägt hat..."><?php echo esc_textarea($moment); ?></textarea>
				</div>
				
				<div class="coach-image-upload" data-field="moment_bild" data-ratio="1.333">
					<label>Bild zum Moment (Querformat 4:3)</label>
					<div class="coach-image-preview <?php echo $moment_bild ? 'has-image' : ''; ?>">
						<?php if ($moment_bild): ?>
							<img src="<?php echo esc_url($moment_bild); ?>" alt="">
						<?php endif; ?>
						<div class="placeholder">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
							<span>Klicken zum Hochladen</span>
						</div>
					</div>
					<div class="coach-image-actions" style="<?php echo $moment_bild ? '' : 'display:none;'; ?>">
						<button type="button" class="btn-change">Ändern</button>
						<button type="button" class="btn-delete">Löschen</button>
					</div>
					<p class="coach-image-hint">Min. 800 × 600 Pixel, max. 10 MB</p>
					<input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;">
				</div>
			</div>
			
			<div class="coach-profile-actions">
				<button type="button" class="coach-profile-preview-btn" id="preview-btn">Vorschau</button>
				<button type="submit" class="coach-profile-submit">Profil speichern</button>
			</div>
		</form>
		
		<!-- Cropper Modal -->
		<div class="cropper-modal-overlay" id="cropper-modal">
			<div class="cropper-modal">
				<div class="cropper-modal-header">
					<h3>Bildausschnitt wählen</h3>
					<button type="button" class="cropper-modal-close">&times;</button>
				</div>
				<div class="cropper-modal-body">
					<div class="cropper-container-wrapper">
						<img id="cropper-image" src="" alt="">
					</div>
				</div>
				<div class="cropper-modal-footer">
					<button type="button" class="btn-cancel">Abbrechen</button>
					<button type="button" class="btn-save">Speichern</button>
				</div>
			</div>
		</div>
		
		<!-- Preview Modal -->
		<div class="preview-modal-overlay" id="preview-modal">
			<div class="preview-modal">
				<button type="button" class="preview-modal-close">
					<svg viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12"/></svg>
				</button>
				<div id="preview-content"></div>
			</div>
		</div>
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
		<script>
		(function() {
			var token = '<?php echo esc_js($token); ?>';
			var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
			var cropper = null;
			var currentUpload = null;
			
			var modal = document.getElementById('cropper-modal');
			var cropperImage = document.getElementById('cropper-image');
			var previewModal = document.getElementById('preview-modal');
			var previewContent = document.getElementById('preview-content');
			
			// Image upload handling
			document.querySelectorAll('.coach-image-upload').forEach(function(upload) {
				var field = upload.dataset.field;
				var ratio = parseFloat(upload.dataset.ratio);
				var preview = upload.querySelector('.coach-image-preview');
				var fileInput = upload.querySelector('input[type="file"]');
				var actions = upload.querySelector('.coach-image-actions');
				var changeBtn = upload.querySelector('.btn-change');
				var deleteBtn = upload.querySelector('.btn-delete');
				
				preview.addEventListener('click', function() {
					if (!preview.classList.contains('has-image')) {
						fileInput.click();
					}
				});
				
				if (changeBtn) {
					changeBtn.addEventListener('click', function() {
						fileInput.click();
					});
				}
				
				if (deleteBtn) {
					deleteBtn.addEventListener('click', function() {
						if (!confirm('Bild wirklich löschen?')) return;
						
						var formData = new FormData();
						formData.append('action', 'coach_image_delete');
						formData.append('token', token);
						formData.append('field', field);
						
						fetch(ajaxUrl, { method: 'POST', body: formData })
							.then(function(r) { return r.json(); })
							.then(function(data) {
								if (data.success) {
									preview.classList.remove('has-image');
									var img = preview.querySelector('img');
									if (img) img.remove();
									actions.style.display = 'none';
								} else {
									alert(data.data.message);
								}
							});
					});
				}
				
				fileInput.addEventListener('change', function(e) {
					var file = e.target.files[0];
					if (!file) return;
					
					if (file.size > 10 * 1024 * 1024) {
						alert('Datei zu gross. Max. 10 MB erlaubt.');
						return;
					}
					
					var reader = new FileReader();
					reader.onload = function(ev) {
						currentUpload = { field: field, ratio: ratio, upload: upload };
						cropperImage.src = ev.target.result;
						modal.classList.add('active');
						
						if (cropper) cropper.destroy();
						
						cropper = new Cropper(cropperImage, {
							aspectRatio: ratio,
							viewMode: 1,
							minCropBoxWidth: 100,
							minCropBoxHeight: 100,
							ready: function() {}
						});
					};
					reader.readAsDataURL(file);
					fileInput.value = '';
				});
			});
			
			modal.querySelector('.cropper-modal-close').addEventListener('click', closeCropperModal);
			modal.querySelector('.btn-cancel').addEventListener('click', closeCropperModal);
			
			modal.querySelector('.btn-save').addEventListener('click', function() {
				if (!cropper || !currentUpload) return;
				
				var btn = this;
				btn.disabled = true;
				btn.textContent = 'Wird gespeichert...';
				
				var canvas = cropper.getCroppedCanvas({
					maxWidth: 1920,
					maxHeight: 1920,
					imageSmoothingQuality: 'high'
				});
				
				var imageData = canvas.toDataURL('image/jpeg', 0.9);
				
				var formData = new FormData();
				formData.append('action', 'coach_image_upload');
				formData.append('token', token);
				formData.append('field', currentUpload.field);
				formData.append('image_data', imageData);
				
				fetch(ajaxUrl, { method: 'POST', body: formData })
					.then(function(r) { return r.json(); })
					.then(function(data) {
						if (data.success) {
							var upload = currentUpload.upload;
							var preview = upload.querySelector('.coach-image-preview');
							var actions = upload.querySelector('.coach-image-actions');
							
							var existingImg = preview.querySelector('img');
							if (existingImg) {
								existingImg.src = data.data.url;
							} else {
								var img = document.createElement('img');
								img.src = data.data.url;
								preview.insertBefore(img, preview.firstChild);
							}
							
							preview.classList.add('has-image');
							actions.style.display = 'flex';
							closeCropperModal();
						} else {
							alert(data.data.message);
						}
						btn.disabled = false;
						btn.textContent = 'Speichern';
					})
					.catch(function() {
						alert('Fehler beim Hochladen.');
						btn.disabled = false;
						btn.textContent = 'Speichern';
					});
			});
			
			function closeCropperModal() {
				modal.classList.remove('active');
				if (cropper) {
					cropper.destroy();
					cropper = null;
				}
				currentUpload = null;
			}
			
			// Preview functionality
			document.getElementById('preview-btn').addEventListener('click', function() {
				var name = document.getElementById('coach-name').value;
				var rolle = document.getElementById('rolle').value;
				var standort = document.getElementById('standort').value;
				var parkour_seit = document.getElementById('parkour_seit').value;
				var po_seit = document.getElementById('po_seit').value;
				var leitsatz = document.getElementById('leitsatz').value;
				var kurzvorstellung = document.getElementById('kurzvorstellung').value;
				var moment = document.getElementById('moment').value;
				
				var heroImg = document.querySelector('[data-field="hero_bild"] .coach-image-preview img');
				var storyImg = document.querySelector('[data-field="philosophie_bild"] .coach-image-preview img');
				var momentImg = document.querySelector('[data-field="moment_bild"] .coach-image-preview img');
				
				var html = '<div class="preview-coach-header">';
				html += '<h2 class="preview-coach-name">' + escapeHtml(name) + '</h2>';
				if (rolle || standort) {
					var meta = [];
					if (rolle) meta.push(rolle);
					if (standort) meta.push(standort);
					html += '<p class="preview-coach-meta">' + escapeHtml(meta.join(' · ')) + '</p>';
				}
				html += '</div>';
				
				if (heroImg) {
					html += '<div class="preview-coach-hero"><img src="' + heroImg.src + '" alt=""></div>';
				}
				
				if (parkour_seit || po_seit || leitsatz) {
					html += '<dl class="preview-coach-facts">';
					if (parkour_seit) {
						html += '<div class="preview-coach-fact"><dt>Parkour seit</dt><dd>' + escapeHtml(parkour_seit) + '</dd></div>';
					}
					if (po_seit) {
						html += '<div class="preview-coach-fact"><dt>Bei ParkourONE seit</dt><dd>' + escapeHtml(po_seit) + '</dd></div>';
					}
					if (leitsatz) {
						html += '<div class="preview-coach-fact full"><dt>Ein Satz, der mir Kraft gibt</dt><dd>«' + escapeHtml(leitsatz) + '»</dd></div>';
					}
					html += '</dl>';
				}
				
				if (kurzvorstellung) {
					html += '<div class="preview-coach-card">';
					html += '<p><strong>Meine Geschichte.</strong> ' + escapeHtml(kurzvorstellung) + '</p>';
					if (storyImg) {
						html += '<img src="' + storyImg.src + '" alt="">';
					}
					html += '</div>';
				}
				
				if (moment) {
					html += '<div class="preview-coach-card">';
					html += '<p><strong>Ein Parkour Moment, der mich geprägt hat.</strong> ' + escapeHtml(moment) + '</p>';
					if (momentImg) {
						html += '<img src="' + momentImg.src + '" alt="">';
					}
					html += '</div>';
				}
				
				if (!heroImg && !kurzvorstellung && !moment && !parkour_seit && !po_seit && !leitsatz) {
					html += '<div class="preview-empty">Fülle die Felder aus um eine Vorschau zu sehen.</div>';
				}
				
				previewContent.innerHTML = html;
				previewModal.classList.add('active');
				document.body.style.overflow = 'hidden';
			});
			
			previewModal.querySelector('.preview-modal-close').addEventListener('click', closePreviewModal);
			previewModal.addEventListener('click', function(e) {
				if (e.target === previewModal) closePreviewModal();
			});
			
			function closePreviewModal() {
				previewModal.classList.remove('active');
				document.body.style.overflow = '';
			}
			
			function escapeHtml(text) {
				var div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
			}
			
			// Form submission
			document.getElementById('coach-profile-form').addEventListener('submit', function(e) {
				e.preventDefault();
				
				var form = this;
				var btn = form.querySelector('.coach-profile-submit');
				var msgEl = document.getElementById('coach-message');
				
				btn.disabled = true;
				btn.textContent = 'Wird gespeichert...';
				
				var formData = new FormData(form);
				formData.append('action', 'coach_profile_save');
				
				fetch(ajaxUrl, { method: 'POST', body: formData })
					.then(function(r) { return r.json(); })
					.then(function(data) {
						if (data.success) {
							msgEl.innerHTML = '<div class="coach-profile-message success">' + data.data.message + '</div>';
							btn.textContent = 'Gespeichert!';
							setTimeout(function() {
								window.location.href = '<?php echo home_url('/mein-coach-profil/'); ?>';
							}, 2000);
						} else {
							msgEl.innerHTML = '<div class="coach-profile-message error">' + data.data.message + '</div>';
							btn.disabled = false;
							btn.textContent = 'Profil speichern';
						}
						window.scrollTo({top: 0, behavior: 'smooth'});
					})
					.catch(function() {
						msgEl.innerHTML = '<div class="coach-profile-message error">Ein Fehler ist aufgetreten.</div>';
						btn.disabled = false;
						btn.textContent = 'Profil speichern';
					});
			});
			
			// ESC key handling
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape') {
					if (modal.classList.contains('active')) closeCropperModal();
					if (previewModal.classList.contains('active')) closePreviewModal();
				}
			});
		})();
		</script>
		
	<?php else: ?>
		<div class="coach-profile-intro">
			<h1>Coach-Profil bearbeiten</h1>
			<p>Gib deine E-Mail-Adresse ein und wir senden dir einen persönlichen Link zum Bearbeiten deines Profils.</p>
		</div>
		
		<div id="coach-message"></div>
		
		<form class="coach-profile-form" id="coach-request-form">
			<div class="coach-profile-field">
				<label for="email">E-Mail-Adresse</label>
				<input type="email" id="email" name="email" required placeholder="deine@email.ch">
				<p class="hint">Verwende die E-Mail-Adresse, die bei ParkourONE hinterlegt ist.</p>
			</div>
			
			<div class="coach-profile-actions">
				<button type="submit" class="coach-profile-submit" style="flex:1;">Link anfordern</button>
			</div>
		</form>
		
		<script>
		document.getElementById('coach-request-form').addEventListener('submit', function(e) {
			e.preventDefault();
			
			var form = this;
			var btn = form.querySelector('.coach-profile-submit');
			var msgEl = document.getElementById('coach-message');
			
			btn.disabled = true;
			btn.textContent = 'Wird gesendet...';
			
			var formData = new FormData(form);
			formData.append('action', 'coach_profile_request');
			
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				body: formData
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (data.success) {
					msgEl.innerHTML = '<div class="coach-profile-message success">' + data.data.message + '</div>';
					form.style.display = 'none';
				} else {
					msgEl.innerHTML = '<div class="coach-profile-message error">' + data.data.message + '</div>';
					btn.disabled = false;
					btn.textContent = 'Link anfordern';
				}
			})
			.catch(function() {
				msgEl.innerHTML = '<div class="coach-profile-message error">Ein Fehler ist aufgetreten.</div>';
				btn.disabled = false;
				btn.textContent = 'Link anfordern';
			});
		});
		</script>
	<?php endif; ?>
</div>

</body>
</html>
