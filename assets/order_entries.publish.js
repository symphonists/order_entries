(function($, Symphony) {
	'use strict';

	Symphony.Language.add({
		'drag to reorder': false,
		'An error occured while saving the new sort order. Please try again.': false
	});

	Symphony.Extensions.OrderEntries = function() {
		var table, fieldId,	direction, oldSorting, newSorting;

		var init = function() {
			table = Symphony.Elements.contents.find('table');
			fieldId = table.attr('data-order-entries-id');
			direction = table.attr('data-order-entries-direction');

			// Add help
			Symphony.Elements.breadcrumbs.append('<p class="inactive"><span>– ' + Symphony.Language.get('drag to reorder') + '</span></p>');

			// Force manual sorting
			if(table.is('[data-order-entries-force]')) {
				table.find('th:not(.field-order_entries)').each(disableSortingModes);
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
					type: 'GET',
					url: Symphony.Context.get('symphony') + '/extension/order_entries/save/',
					data: newSorting + '&field=' + fieldId + '&' + Symphony.Utilities.getXSRF(true),
					success: function() {
						oldSorting = newSorting;

						// Update indexes
						var items = table.find('.order-entries-item');
						items.each(function(index) {
							if(direction == 'asc') {
								$(this).text(index + 1);
							}
							else {
								$(this).text(items.length - index);
							}
						});
					},
					error: function() {
						Symphony.Elements.header.find('div.notifier').trigger('attach.notify', [
							Symphony.Language.get('An error occured while saving the new sort order. Please try again.'),
							'reorder error'
						]);
					}
				});
			}
		};

		var getState = function() {
			var items = table.find('input'),
				states;

			states = items.map(function(index) {
				if(direction == 'asc') {
					return this.name + '=' + (index + 1);
				}
				else {
					return this.name + '=' + (items.length - index);
				}
			}).get().join('&');

			return states;
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
