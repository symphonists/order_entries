(function($, Symphony) {
	'use strict';

	Symphony.Language.add({
		'drag to reorder': false,
		'An error occured while saving the new sort order. Please try again.': false
	});

	Symphony.Extensions.OrderEntries = function() {
		var table, tableHead,
			fieldId, oldSorting, newSorting;

		var init = function() {
			table = Symphony.Elements.contents.find('table');
			tableHead = table.find('thead');

			// Get sorting field id
			fieldId = tableHead.find('th.field-order_entries').attr('id').split('-')[1];

			// Add help
			Symphony.Elements.breadcrumbs.append('<p class="inactive"><span>– ' + Symphony.Language.get('drag to reorder') + '</span></p>');

			// Force manual sorting
			if(tableHead.has('[data-manual-sorting]').length) {
				tableHead.find('th:not(.field-order_entries)').each(disableSortingModes);
			}

			// Enable sorting
			table.symphonyOrderable({
				items: 'tr',
				handles: 'td'
			});

			// Process sort order
			oldSorting = getState();
			table.on('orderstop.orderable', processState);
		};

		var disableSortingModes = function() {
			var header = $(this),
				text = header.text();

			// Remove sorting links
			header.html(text);
		};

		var processState = function() {
			newSorting = getState();

			// Store sort order
			if(oldSorting != newSorting) {
				$.ajax({
					type: 'POST',
					url: Symphony.Context.get('symphony') + '/extension/order_entries/save/',
					data: newSorting + '&field=' + fieldId + '&' + Symphony.Utilities.getXSRF(true),
					success: function() {
						oldSorting = newSorting;

						// Update indexes
						table.find('.order-entries-item').each(function(index) {
							$(this).text(index + 1)
						});
					},
					error: function(a, b, c) {
						console.log(a, b, c);

						Symphony.Elements.header.find('div.notifier').trigger('attach.notify', [
							Symphony.Language.get('An error occured while saving the new sort order. Please try again.'),
							'reorder error'
						]);
					}
				});
			}
		};

		var getState = function() {
			return table.find('input').map(function(e, i) {
				return this.name + '=' + (e + 1);
			}).get().join('&');
		};

		// API
		return {
			init: init
		};
	}();

	$(document).on('ready.orderentries', function() {
		Symphony.Extensions.OrderEntries.init();
	});

})(window.jQuery, window.Symphony);
