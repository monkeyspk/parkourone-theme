<?php
$headline = $attributes['headline'] ?? 'Häufige Fragen';
$category = $attributes['category'] ?? '';
$limit = $attributes['limit'] ?? 6;
$bg_color = $attributes['backgroundColor'] ?? 'white';
$include_general = $attributes['includeGeneral'] ?? true;

// FAQs laden - mit oder ohne allgemeine FAQs
if ($include_general && !empty($category) && function_exists('parkourone_get_page_faqs')) {
	// Kombiniert spezifische Kategorie + allgemein + probetraining
	$faqs = parkourone_get_page_faqs($category, $limit);
} else {
	// Nur spezifische Kategorie (oder alle wenn leer)
	$faqs = function_exists('parkourone_get_faqs') ? parkourone_get_faqs($category, $limit) : [];
}

// Keine FAQs vorhanden
if (empty($faqs)) {
	return;
}

// JSON-LD Schema für LLM-Optimierung generieren
$json_ld_schema = function_exists('parkourone_generate_faq_schema') ? parkourone_generate_faq_schema($faqs) : '';

$section_classes = ['po-faqone', 'po-faqone--bg-' . $bg_color];
if (!empty($attributes['align'])) {
	$section_classes[] = 'align' . $attributes['align'];
}

$unique_id = 'po-faqone-' . uniqid();
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="<?php echo esc_attr($unique_id); ?>">
	<?php if ($headline): ?>
		<h2 class="po-faqone__headline"><?php echo esc_html($headline); ?></h2>
	<?php endif; ?>

	<div class="po-faqone__list" itemscope itemtype="https://schema.org/FAQPage">
		<?php foreach ($faqs as $index => $faq): ?>
			<div class="po-faqone__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
				<button
					type="button"
					class="po-faqone__question"
					id="<?php echo esc_attr($unique_id . '-q-' . $index); ?>"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr($unique_id . '-a-' . $index); ?>"
					itemprop="name"
				>
					<span class="po-faqone__question-text"><?php echo esc_html($faq['question']); ?></span>
					<span class="po-faqone__icon" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="6 9 12 15 18 9"></polyline>
						</svg>
					</span>
				</button>
				<div
					class="po-faqone__answer"
					id="<?php echo esc_attr($unique_id . '-a-' . $index); ?>"
					role="region"
					aria-labelledby="<?php echo esc_attr($unique_id . '-q-' . $index); ?>"
					itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"
				>
					<div class="po-faqone__answer-inner">
						<div class="po-faqone__answer-content" itemprop="text">
							<?php echo wp_kses_post($faq['answer']); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
	// JSON-LD Schema ausgeben (für ChatGPT, Perplexity & andere LLM-Suchen)
	echo $json_ld_schema;
	?>
</section>
