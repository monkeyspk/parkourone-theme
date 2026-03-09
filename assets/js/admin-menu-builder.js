/**
 * ParkourONE Menu Builder - Drag & Drop Admin UI
 */
(function($) {
	'use strict';

	$(function() {
		var $builder = $('#po-menu-builder');
		if (!$builder.length) return;

		// Init sortable on all column dropzones
		$('.po-mb-dropzone').sortable({
			connectWith: '.po-mb-dropzone',
			placeholder: 'po-mb-placeholder',
			handle: '.po-mb-handle',
			tolerance: 'pointer',
			cursor: 'move',
			update: function() {
				serializeMenuBuilder();
			},
			receive: function() {
				serializeMenuBuilder();
			}
		}).disableSelection();

		// Add custom link
		$('#po-mb-add-custom').on('click', function() {
			var label = $('#po-mb-custom-label').val().trim();
			var url = $('#po-mb-custom-url').val().trim();
			if (!label) {
				alert('Bitte einen Link-Text eingeben.');
				return;
			}
			var item = createItem({
				type: 'custom',
				label: label,
				url: url || '#',
				highlight: false
			});
			// Add to first column that has items, or column 3 (index 2) by default
			$('.po-mb-dropzone').eq(2).append(item);
			$('#po-mb-custom-label').val('');
			$('#po-mb-custom-url').val('');
			serializeMenuBuilder();
		});

		// Add auto block
		$('.po-mb-add-auto').on('click', function() {
			var source = $(this).data('source');
			var taxonomy = $(this).data('taxonomy') || 'event_category';
			var label = $(this).data('label');
			var item = createItem({
				type: 'auto_block',
				source: source,
				taxonomy: taxonomy,
				label: label
			});
			// Add to first empty column, or column 1
			var $target = findBestColumn();
			$target.append(item);
			serializeMenuBuilder();
		});

		// Add page
		$('#po-mb-add-page').on('click', function() {
			var $select = $('#po-mb-page-select');
			var pageId = $select.val();
			if (!pageId) {
				alert('Bitte eine Seite auswählen.');
				return;
			}
			var pageTitle = $select.find('option:selected').text();
			var item = createItem({
				type: 'page',
				page_id: pageId,
				label: pageTitle
			});
			$('.po-mb-dropzone').eq(2).append(item);
			serializeMenuBuilder();
		});

		// Remove item
		$builder.on('click', '.po-mb-remove', function() {
			$(this).closest('.po-mb-item').fadeOut(200, function() {
				$(this).remove();
				serializeMenuBuilder();
			});
		});

		// Toggle highlight
		$builder.on('click', '.po-mb-highlight-toggle', function() {
			var $item = $(this).closest('.po-mb-item');
			var current = $item.data('highlight');
			$item.data('highlight', !current);
			$item.toggleClass('po-mb-item--highlight');
			$(this).text(!current ? '★' : '☆');
			$(this).attr('title', !current ? 'Hervorhebung entfernen' : 'Hervorheben');
			serializeMenuBuilder();
		});

		// Edit column title
		$builder.on('blur', '.po-mb-column-title-input', function() {
			serializeMenuBuilder();
		});

		function findBestColumn() {
			var $zones = $('.po-mb-dropzone');
			for (var i = 0; i < $zones.length; i++) {
				if ($zones.eq(i).children('.po-mb-item').length === 0) {
					return $zones.eq(i);
				}
			}
			return $zones.eq(0);
		}

		function createItem(data) {
			var typeLabel = '';
			var displayLabel = data.label || '';
			var extraClass = '';
			var extraButtons = '';

			switch (data.type) {
				case 'custom':
					typeLabel = 'Link';
					displayLabel = data.label + (data.url && data.url !== '#' ? ' → ' + data.url : '');
					extraClass = data.highlight ? ' po-mb-item--highlight' : '';
					extraButtons = '<button type="button" class="po-mb-highlight-toggle" title="' +
						(data.highlight ? 'Hervorhebung entfernen' : 'Hervorheben') + '">' +
						(data.highlight ? '★' : '☆') + '</button>';
					break;
				case 'auto_block':
					typeLabel = 'Auto';
					displayLabel = data.label || data.source;
					extraClass = ' po-mb-item--auto';
					break;
				case 'page':
					typeLabel = 'Seite';
					break;
			}

			var $item = $('<div class="po-mb-item' + extraClass + '">' +
				'<span class="po-mb-handle">⋮⋮</span>' +
				'<span class="po-mb-type">' + typeLabel + '</span>' +
				'<span class="po-mb-label">' + escHtml(displayLabel) + '</span>' +
				extraButtons +
				'<button type="button" class="po-mb-remove" title="Entfernen">×</button>' +
				'</div>');

			$item.data('item-data', data);
			$item.data('highlight', data.highlight || false);

			return $item;
		}

		function escHtml(str) {
			return $('<span>').text(str).html();
		}

		function serializeMenuBuilder() {
			var columns = [];
			$('.po-mb-column').each(function() {
				var title = $(this).find('.po-mb-column-title-input').val() || '';
				var items = [];
				$(this).find('.po-mb-item').each(function() {
					var data = $(this).data('item-data');
					if (!data) return;
					// Update highlight from current state
					if (data.type === 'custom') {
						data.highlight = $(this).data('highlight') || false;
					}
					items.push(data);
				});
				columns.push({
					title: title,
					items: items
				});
			});
			$('#po-mb-data').val(JSON.stringify(columns));
		}

		// Hydrate server-rendered items with data attributes
		$('.po-mb-item[data-item]').each(function() {
			var raw = $(this).attr('data-item');
			try {
				var data = JSON.parse(raw);
				$(this).data('item-data', data);
				$(this).data('highlight', data.highlight || false);
			} catch(e) {}
			$(this).removeAttr('data-item');
		});

		// Initial serialize
		serializeMenuBuilder();

		// Page search filter
		$('#po-mb-page-search').on('input', function() {
			var query = $(this).val().toLowerCase();
			$('#po-mb-page-select option').each(function() {
				if ($(this).val() === '') return; // keep placeholder
				var text = $(this).text().toLowerCase();
				$(this).toggle(text.indexOf(query) !== -1);
			});
		});
	});
})(jQuery);
