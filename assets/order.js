DOM.onready(function() {

	var table = DOM.getFirstElement("table")
	var rows = DOM.select("tbody tr").map(function(tr, position) {
		return tr;
	});

	if (!table || rows.length == 0) return;
	
	if (document.getElementsByTagName("form")[0].getAttribute("action").indexOf("/publish/") == -1) {
		return false;
	}
	
	var href = DOM.select("h1 a")[0].getAttribute("href");
	if (href.substr(href.length - 1, href.length) != "/") href += "/";
	var save = new Request(href + "symphony/extension/order_entries/save/", function(request) {
		DOM.removeClass("busy", table);
	});
	
	var h2 = DOM.getFirstElement("h2");
	h2.innerHTML += " (drag to reorder)";

	Orderable.implement(rows, function(row) {
		
		DOM.addClass("busy", table);
		var order_entry_field = document.getElementById("order_number_field");
		save.post(DOM.select("input", table).map(serialise).join("&") + "&field=" + order_entry_field.innerHTML);
		
		// find the Order Field column index
		var columns = DOM.select("thead th", table);
		var column_index = 0;
		for(var i=0; i<columns.length; i++) {
			var a = DOM.select("a.active", columns[i])[0];
			if (a) {
				column_index = i;
			}
		}
		
		var rows = DOM.select("tbody tr");
		for(var i=0; i < rows.length; i++) {
			var r = rows[i];
			var columns = DOM.select("td", r);
			for(var j=0; j<columns.length; j++) {
				if (j == column_index) {
					columns[j].innerHTML = i + 1;
				}
			}
			if (r != row) {
				DOM.removeClass("selected", r);
			}
		}
				
	});

	function serialise(input, position) {
		return input.name + "=" + (position + 1);
	}
	
	function getEntryId(row) {
		var input = DOM.select("td.toggle input", row)[0];
		return parseInt(input.name.replace(/items\[/,''));
	}
	
});