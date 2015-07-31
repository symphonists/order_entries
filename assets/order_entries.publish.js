(function($, Symphony) {
	'use strict';

	Symphony.Language.add({
		'drag to reorder': false,
		'An error occured while saving the new sort order. Please try again.': false
	});

	Symphony.Extensions.OrderEntries = function() {
		var table, fieldId,	direction, oldSorting, newSorting, startValue, filters;

		var init = function() {
			table = Symphony.Elements.contents.find('table');
			fieldId = table.attr('data-order-entries-id');
			direction = table.attr('data-order-entries-direction');
			filters = Symphony.Context.get('env').filters;

			// convert filters into a query string
			if (filters){
				filters = {"filters":filters};
				filters = '&' + $.param(filters)
			} else {
				filters = '';
			}

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
			if (table.find('.order-entries-item').length > 0){
				startValue = parseInt(table.find('.order-entries-item').eq(0).text(),10);
			} else {
				startValue = parseInt(table.find('tbody tr').eq(0).data('order'),10);				
			}
			var assumedStartValue = Symphony.Context.get('env').pagination['max-rows'] * (Symphony.Context.get('env').pagination['current'] - 1) + 1;
			if (startValue == 0 || direction == 'asc' && startValue < assumedStartValue) {
				startValue = assumedStartValue;
			}
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
					data: newSorting + '&field=' + fieldId + filters + '&' + Symphony.Utilities.getXSRF(true),
					success: function() {
						oldSorting = newSorting;

					// Update indexes
						var items = table.find('tbody tr');
						items.each(function(index) {
							if(direction == 'asc') {
								$(this).data('order',index + startValue);
								$(this).find('.order-entries-item').text(index + startValue);
							}
							else {
								var largest = startValue;
								if ( items.length > largest ) largest = items.length;
								$(this).data('order',largest - index);
								$(this).find('.order-entries-item').text(largest - index);
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
			var items = table.find('input[id^="entry"]'),
				states;

			states = items.map(function(index) {
				if(direction == 'asc') {
					return this.name + '=' + (index + startValue);
				}
				else {
					var largest = startValue;
					if ( items.length > largest ) largest = items.length;
					return this.name + '=' + (largest - index);
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
