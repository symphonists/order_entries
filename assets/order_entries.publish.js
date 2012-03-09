Symphony.Language.add({
	'drag to reorder': false,
	'Reordering was unsuccessful.': false,
	'Entry order saved.': false
});

OrderEntries = {
	
	table: null,
	config: null,
	h2: null,
	column_index: null,
	
	init: function() {
		var self = this;
		
		this.table = jQuery('#contents > form > table');
		if(!this.table.find('tbody tr').length) return;
		
		this.config = Symphony.Context.get('order-entries');
		
		jQuery('#breadcrumbs').append('<p class="inactive"><span>(' + Symphony.Language.get('drag to reorder') + ')</span></p>');
		this.column_index = this.table.find('thead th a.active[href*="sort=' + this.config.id + '&"]').parent().prevAll().length;
		
		// disable sorting of other columns by removing the anchors
		if(this.config['force-sort'] == 'yes') {
			this.table.find('thead th').each(function() {
				// don't touch the order entries field, leave sortable
				if(jQuery(this).find('a[href*="sort=' + self.config.id + '&"]').length) return;
				// get the plain text of the cell
				var text = jQuery(this).text();
				// replace contents with plain text
				jQuery(this).html(text);
			});
		}
		
		// Orderable tables
		this.table.symphonyOrderable({
			items: 'tr',
			handles: 'td'
		});

		// Don't start ordering while clicking on links
		this.table.find('a').mousedown(function(event) {
			event.stopPropagation();
		});
		
		// unbind any previous ordering (Symphony's default callbacks)
		this.table.unbind('orderstart');
		this.table.unbind('orderstop');

		// Store current sort order
		this.table.on('orderstart.orderable', function() {
			old_sorting = self.table.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');
		});

		// Process sort order
		this.table.on('orderstop.orderable', function() {
			self.table.addClass('busy');

			// Get new sort order
			var new_sorting = self.table.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');

			// Store new sort order
			if(new_sorting != old_sorting) {

				// Update items
				self.table.trigger('orderchange');

				// Send request
				jQuery.ajax({
					type: 'POST',
					url: Symphony.Context.get('root') + '/symphony/extension/order_entries/save/',
					data: jQuery('input', this).map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&') + '&field=' + self.config.id,
					success: function() {
						// Symphony.Message.clear('reorder');
						// Symphony.Message.post(Symphony.Language.get('Entry order saved.'), 'reorder success');
					},
					error: function() {
						//Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'reorder error');
					},
					complete: function() {
						self.table.removeClass('busy').find('tr').removeClass('selected');
						old_sorting = null;
						
	                    // find the Order Field column index
	                    self.table.find('tbody tr td:nth-child(' + (self.column_index + 1) + ')').each(function(i, element) {
							jQuery(this).find('.order').text(i + 1);
	                    });
						
					}
				});
			}
			else {
				self.table.removeClass('busy');
			}

		});
		
	}
	
};

jQuery(document).ready(function() {
	OrderEntries.init();
});