(function($) {

	$(document).ready(function() {
		
		var table = $('table:first');
	    var rows = $('tbody tr', table);

	    if (!table || rows.length == 0 || !$('#order_number_field').text()) return;

	    if ($('form:first').attr('action').indexOf('/publish/') == -1) {
	        return false;
	    }

	    $('h2:first *:first').before(' (drag to reorder) ');

	    $(table).addClass('order-entries');

	    // Sortable lists - copied from Symphony's admin.js, because it's not accessible for us (too bad it wasn't made as Symphony.movable :(.
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
	                b = target.offset().top,
	                prev_offset = (target.prev().length) ? target.prev().offset().top : 0;

	            movable.target = target;
	            movable.min    = Math.min(b, a + (prev_offset || -Infinity));
	            movable.max    = Math.max(a + b, b + (target.next().height() ||  Infinity));
	        }
	    };


	    // Based on code from Symphony's admin.js
	    $('.order-entries tr').live('mousedown', function(e) {
	        if (!/^(?:h4|td)$/i.test(e.target.nodeName)) {
	            return true;
	        }

	        movable.update($(this).addClass('movable'));
	        movable.delta = 0;

	        $(document).mousemove(movable.move);
	        $(document).mouseup(movable.drop);

	        return false;
	    });
		
		var column_index = $('thead th a.active[href*="sort='+$('#order_number_field').text()+'&"]', table).parent().prevAll().length;
		
		if ($('#order_number_field').hasClass('yes')) {
			$('.order-entries thead th').each(function(i) {
				var text = $(this).text();
				$(this).html(text);
			});
		}

	    $('table.order-entries').live('reorder', function() {
	        var t = $(this).addClass('busy');
	        $.ajax({
	            type: 'POST',
	            url: Symphony.WEBSITE + '/symphony/extension/order_entries/save/',
	            data: $('input', this).map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&') + '&field=' + $('#order_number_field').text(),
	            complete: function(x) {
	                if (x.status === 200) {
	                    Symphony.Message.clear('reorder');

	                    // find the Order Field column index
	                    $('tbody tr td:nth-child('+(column_index+1)+')', table).each(function(i, element) {
							$(this).removeClass('inactive');
							var text_node = element.childNodes[0];
							text_node.nodeValue = i + 1;
	                    });

	                    // deselect rows
	                    $('tr.selected td:first', table).trigger($.Event('click'));

	                } else {
	                    Symphony.Message.post(Symphony.Language.REORDER_ERROR, 'reorder error');
	                }
	                t.removeClass('busy');
	            }
	        });
	    });
		
	});

})(jQuery.noConflict());