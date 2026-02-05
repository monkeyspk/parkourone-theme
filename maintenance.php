<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Wir sind gleich zurück | ParkourONE</title>
	<meta name="robots" content="noindex, nofollow">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			background: #0a0a0a;
			color: #fff;
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			overflow: hidden;
			position: relative;
		}

		/* Background Image */
		.bg-image {
			position: absolute;
			inset: 0;
			background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/fallback/landscape/adults/1T2A6249.jpg');
			background-size: cover;
			background-position: center;
			background-repeat: no-repeat;
			z-index: -2;
		}

		.bg-overlay {
			position: absolute;
			inset: 0;
			background: linear-gradient(
				to bottom,
				rgba(10, 10, 10, 0.7) 0%,
				rgba(10, 10, 10, 0.85) 50%,
				rgba(10, 10, 10, 0.95) 100%
			);
			z-index: -1;
		}

		/* Animated Background Grid */
		.bg-grid {
			position: absolute;
			inset: 0;
			background-image:
				linear-gradient(rgba(41, 151, 255, 0.03) 1px, transparent 1px),
				linear-gradient(90deg, rgba(41, 151, 255, 0.03) 1px, transparent 1px);
			background-size: 60px 60px;
			animation: gridMove 20s linear infinite;
		}

		@keyframes gridMove {
			0% { transform: translate(0, 0); }
			100% { transform: translate(60px, 60px); }
		}

		/* Glowing Orbs */
		.orb {
			position: absolute;
			border-radius: 50%;
			filter: blur(80px);
			opacity: 0.4;
			animation: orbFloat 8s ease-in-out infinite;
		}

		.orb-1 {
			width: 400px;
			height: 400px;
			background: #2997ff;
			top: -100px;
			right: -100px;
			animation-delay: 0s;
		}

		.orb-2 {
			width: 300px;
			height: 300px;
			background: #5856d6;
			bottom: -50px;
			left: -50px;
			animation-delay: -4s;
		}

		.orb-3 {
			width: 200px;
			height: 200px;
			background: #2997ff;
			top: 50%;
			left: 50%;
			animation-delay: -2s;
		}

		@keyframes orbFloat {
			0%, 100% { transform: translate(0, 0) scale(1); }
			50% { transform: translate(30px, -30px) scale(1.1); }
		}

		/* Content */
		.content {
			position: relative;
			z-index: 1;
			text-align: center;
			padding: 40px 24px;
			max-width: 600px;
		}

		/* Logo */
		.logo {
			margin-bottom: 48px;
		}

		.logo svg {
			height: 40px;
			width: auto;
		}

		/* Icon */
		.icon {
			width: 80px;
			height: 80px;
			margin: 0 auto 32px;
			background: rgba(41, 151, 255, 0.1);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			animation: pulse 2s ease-in-out infinite;
		}

		.icon svg {
			width: 40px;
			height: 40px;
			color: #2997ff;
		}

		@keyframes pulse {
			0%, 100% {
				transform: scale(1);
				box-shadow: 0 0 0 0 rgba(41, 151, 255, 0.4);
			}
			50% {
				transform: scale(1.05);
				box-shadow: 0 0 40px 10px rgba(41, 151, 255, 0.2);
			}
		}

		/* Typography */
		.eyebrow {
			font-size: 11px;
			font-weight: 600;
			letter-spacing: 0.2em;
			text-transform: uppercase;
			color: #2997ff;
			margin-bottom: 16px;
		}

		h1 {
			font-size: clamp(32px, 8vw, 56px);
			font-weight: 700;
			letter-spacing: -0.03em;
			line-height: 1.1;
			margin-bottom: 24px;
		}

		.highlight {
			color: #2997ff;
			text-shadow: 0 0 30px rgba(41, 151, 255, 0.5);
		}

		.description {
			font-size: 18px;
			line-height: 1.6;
			color: rgba(255, 255, 255, 0.7);
			margin-bottom: 48px;
		}

		/* Progress Bar */
		.progress-container {
			width: 100%;
			max-width: 300px;
			margin: 0 auto 24px;
		}

		.progress-bar {
			height: 4px;
			background: rgba(255, 255, 255, 0.1);
			border-radius: 2px;
			overflow: hidden;
		}

		.progress-fill {
			height: 100%;
			width: 60%;
			background: linear-gradient(90deg, #2997ff, #5856d6);
			border-radius: 2px;
			animation: progressPulse 2s ease-in-out infinite;
		}

		@keyframes progressPulse {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.6; }
		}

		.progress-text {
			font-size: 13px;
			color: rgba(255, 255, 255, 0.5);
			margin-top: 12px;
		}

		/* Social Links */
		.social {
			display: flex;
			gap: 16px;
			justify-content: center;
			margin-top: 48px;
		}

		.social a {
			width: 44px;
			height: 44px;
			display: flex;
			align-items: center;
			justify-content: center;
			background: rgba(255, 255, 255, 0.05);
			border: 1px solid rgba(255, 255, 255, 0.1);
			border-radius: 50%;
			color: rgba(255, 255, 255, 0.6);
			text-decoration: none;
			transition: all 0.3s ease;
		}

		.social a:hover {
			background: rgba(41, 151, 255, 0.2);
			border-color: #2997ff;
			color: #fff;
			transform: translateY(-2px);
		}

		.social svg {
			width: 20px;
			height: 20px;
		}

		/* Footer */
		.footer {
			position: absolute;
			bottom: 24px;
			left: 0;
			right: 0;
			text-align: center;
			font-size: 13px;
			color: rgba(255, 255, 255, 0.3);
		}

		/* Responsive */
		@media (max-width: 480px) {
			.description {
				font-size: 16px;
			}

			.social {
				gap: 12px;
			}
		}
	</style>
</head>
<body>
	<!-- Background Image -->
	<div class="bg-image"></div>
	<div class="bg-overlay"></div>

	<!-- Background Effects -->
	<div class="bg-grid"></div>
	<div class="orb orb-1"></div>
	<div class="orb orb-2"></div>
	<div class="orb orb-3"></div>

	<!-- Content -->
	<div class="content">
		<!-- Logo -->
		<div class="logo">
			<svg viewBox="0 0 200 40" fill="currentColor">
				<text x="0" y="30" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="28" font-weight="700">ParkourONE</text>
			</svg>
		</div>

		<!-- Animated Icon -->
		<div class="icon">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
			</svg>
		</div>

		<!-- Text -->
		<span class="eyebrow">Update in Arbeit</span>
		<h1>Wir sind <span class="highlight">gleich</span> zurück</h1>
		<p class="description">
			Wir arbeiten gerade an etwas Grossartigem.
			Unsere neue Website ist bald bereit für den nächsten Sprung.
		</p>

		<!-- Progress -->
		<div class="progress-container">
			<div class="progress-bar">
				<div class="progress-fill"></div>
			</div>
			<p class="progress-text">Setup läuft...</p>
		</div>

		<!-- Social Links -->
		<div class="social">
			<a href="https://instagram.com/parkourone" target="_blank" rel="noopener" aria-label="Instagram">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
					<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
					<line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
				</svg>
			</a>
			<a href="https://youtube.com/parkourone" target="_blank" rel="noopener" aria-label="YouTube">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/>
					<polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
				</svg>
			</a>
			<a href="mailto:info@parkourone.com" aria-label="E-Mail">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
					<polyline points="22,6 12,13 2,6"/>
				</svg>
			</a>
		</div>
	</div>

	<!-- Footer -->
	<footer class="footer">
		&copy; <?php echo date('Y'); ?> ParkourONE
	</footer>
</body>
</html>
