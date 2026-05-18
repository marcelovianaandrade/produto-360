/**
 * Produto 360 - Admin JS
 * Gerencia upload, ordenação e configurações de imagens.
 */
(function ($) {
	'use strict';

	$(function () {

		var $list = $('#p360-list');
		var $count = $('#p360-count');
		var $empty = $('.p360-empty');
		var mediaFrame = null;

		// === Sortable (drag-and-drop) ===
		$list.sortable({
			placeholder: 'p360-item ui-sortable-placeholder',
			tolerance: 'pointer',
			update: function () {
				renumber();
			}
		});

		// === Color picker ===
		if ($.fn.wpColorPicker) {
			$('.p360-color-picker').wpColorPicker();
		}

		// === Adicionar imagens via Media Library ===
		$('#p360-select-images').on('click', function (e) {
			e.preventDefault();

			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media({
				title: P360Admin.i18n.selectImages,
				button: { text: P360Admin.i18n.useThese },
				library: { type: 'image' },
				multiple: 'add'
			});

			mediaFrame.on('select', function () {
				var attachments = mediaFrame.state().get('selection').toJSON();
				attachments.forEach(function (att) {
					if (alreadyAdded(att.id)) return;
					appendItem(att);
				});
				renumber();
			});

			mediaFrame.open();
		});

		// === Remover imagem ===
		$list.on('click', '.p360-remove', function (e) {
			e.preventDefault();
			if (!confirm(P360Admin.i18n.confirmRemove)) return;
			$(this).closest('.p360-item').remove();
			renumber();
		});

		// === Ordenar pelo nome do arquivo ===
		$('#p360-sort-name').on('click', function (e) {
			e.preventDefault();

			var $items = $list.find('.p360-item');
			if ($items.length === 0) return;

			// Coleta nomes de arquivo via REST/AJAX-free: usa o src da thumb
			var data = $items.map(function () {
				var $li = $(this);
				var src = $li.find('img').attr('src') || '';
				var name = src.split('/').pop().toLowerCase();
				return { el: this, name: name };
			}).get();

			data.sort(function (a, b) {
				return a.name.localeCompare(b.name, undefined, { numeric: true });
			});

			$list.empty();
			data.forEach(function (d) {
				$list.append(d.el);
			});

			renumber();
		});

		// === Limpar tudo ===
		$('#p360-clear').on('click', function (e) {
			e.preventDefault();
			if (!confirm(P360Admin.i18n.confirmClear)) return;
			$list.empty();
			renumber();
		});

		// === Helpers ===
		function alreadyAdded(id) {
			return $list.find('.p360-item[data-id="' + id + '"]').length > 0;
		}

		function appendItem(att) {
			var thumb = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
			var $li = $(
				'<li class="p360-item" data-id="' + att.id + '">' +
				'<span class="p360-frame"></span>' +
				'<img src="' + thumb + '" alt="" />' +
				'<button type="button" class="p360-remove" aria-label="Remover">×</button>' +
				'<input type="hidden" name="p360_images[]" value="' + att.id + '" />' +
				'</li>'
			);
			$list.append($li);
		}

		function renumber() {
			var items = $list.find('.p360-item');
			items.each(function (i) {
				$(this).find('.p360-frame').text('#' + (i + 1));
			});
			var count = items.length;
			$count.text(count);
			$empty.toggle(count === 0);
		}

	});

})(jQuery);
