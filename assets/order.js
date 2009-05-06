(function($) {

	var movable = {
		move: function(e) {
			var t,
			    n,
			    y = e.pageY;

			if (y < movable.min) {
				t = movable.target.prev();
				for (;;) {
					movable.delta--;
					n = t.prev();
					if (n.length === 0 || y >= (movable.min -= n.height())) {
						movable.target.insertBefore(t);
						break;
					}
					t = n;
				}
			} else if (y > movable.max) {
				t = movable.target.next();
				for (;;) {
					movable.delta++;
					n = t.next();
					if (n.length === 0 || y <= (movable.max += n.height())) {
						movable.target.insertAfter(t);
						break;
					}
					t = n;
				}
			} else {
				return;
			}

			movable.update(movable.target);
			movable.target.parent().children().each(function(i) { $(this).toggleClass('odd', i % 2 === 0); });
		},
		drop: function() {
			$(document).unbind('mousemove', movable.move);
			$(document).unbind('mouseup', movable.drop);

			movable.target.removeClass('movable');

			if (movable.delta) {
				movable.target.trigger($.Event('reorder'));
			}
		},
		update: function(target) {
			var a = target.height(),
			    b = target.offset().top;

			movable.target = target;
			movable.min    = Math.min(b, a + (target.prev().offset().top || -Infinity));
			movable.max    = Math.max(a + b, b + (target.next().height() ||  Infinity));
		}
	};
	

	$(document).ready(function() {
		
		var h2 = $("h2:first");
		var h2_text = h2.html();
		h2.html(h2_text + " (drag to reorder)");

		$('table tr').live('mousedown', function(e) {
			if (!/^(?:h4|td)$/i.test(e.target.nodeName)) {
				return true;
			}

			movable.update($(this).addClass('movable'));
			movable.delta = 0;

			$(document).mousemove(movable.move);
			$(document).mouseup(movable.drop);

			return false;
		});

		$('table.selectable').live('reorder', function() {
			var t = $(this).addClass('busy');

			var column_index = 0;
			t.find('thead th').each(function(i) {
				var a = $("a.active", this);
				if (a.length) {
					column_index = i;
				}
			});			

			t.find("tbody tr").each(function(i) {
				$(this).find('td:eq(' + column_index + ')').text(i + 1);
			});
			
			$.ajax({
				type: 'POST',
				url: Symphony.WEBSITE + '/symphony/extension/order_entries/save/',
				data: $('input', this).map(function(i) { return this.name + '=' + (i + 1); }).get().join('&') + '&field=' + $('#order_number_field').text(),
				complete: function(x) {
					if (x.status === 200) {
						Symphony.Message.clear('reorder');
					} else {
						Symphony.Message.post(Symphony.Language.REORDER_ERROR, 'reorder error');
					}
					t.removeClass('busy');
				}
			});
		});

	});
		
})(jQuery.noConflict());