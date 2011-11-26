// This is a modified version of
// http://raphaeljs.com/github/dots.html
// 
// TO DO: redraw on window resize...

// hey look! running code!!

(function(){
	var table = $("#punchcard");
	table.hide();
})();

function punchcard_collect_data(){

	var table = $("#punchcard");
	table.hide();

	var data = {
		'values' : [],
		'max': 0,
		'min': 0,
		'labels_x' : [],
		'labels_y' : [],
		'raw_x' : [],
		'raw_y' : []
	};

	$("tbody td", table).each(function (i){
		var el = $(this);
		var value = parseFloat(el.text(), 10);
		data['values'].push(value);
		data['min'] = Math.min(value, data['min']);
		data['max'] = Math.max(value, data['max']);
	});

	$("tbody th", table).each(function (){
		var el = $(this);

		var label = el.text();
		label = label.trim();
		data['labels_y'].push(label);

		var raw = el.attr("data-raw");
		raw = raw.trim();
		data['raw_y'].push(raw);
	});

	$("tfoot th", table).each(function () {
		var el = $(this);
		var label = el.text();
		label = label.trim();
		data['labels_x'].push(label);

		var raw = el.attr("data-raw");
		raw = raw.trim();
		data['raw_x'].push(raw);
	});

	return data;
}

function punchcard_draw(url_template){

	var data = punchcard_collect_data();

	var width = window.innerWidth * .9;
	var height = window.innerHeight * .8;

	var label_offset = 30;
	var leftgutter = label_offset * 2;
	var bottomgutter = 25;

	var r = Raphael("chart", width, height);

	var txt_attrs = {"font": '10px', stroke: "none", fill: "#666"};

	// TO DO: calculate max better than this...

	var X = (width - leftgutter) / data['labels_x'].length;
	var Y = (height - bottomgutter) / data['labels_y'].length;

	var max = Math.round(X / 2) - 1;

	for (var i = 0, ii = data['labels_x'].length; i < ii; i++){
		var x = leftgutter + X * (i + .5);
		var y = height - 6;
		var v = data['labels_x'][i];

		var label = r.text(x, y, v);
		label.attr(txt_attrs);
	}

	for (var i = 0, ii = data['labels_y'].length; i < ii; i++){
		var x = label_offset;
		var y = Y * (i + .5);
		var v = data['labels_y'][i];

		var label = r.text(x, y, v);
		label.attr(txt_attrs);
	}

	var whatisthis = 35;     
	var o = 0;

	for (var i = 0, ii = data['labels_y'].length; i < ii; i++){

		for (var j = 0, jj = data['labels_x'].length; j < jj; j++){

			var value = data['values'][o];
			
			var R = value && Math.min(Math.round(Math.sqrt(value / Math.PI) * 4), max);

			// see this? it's because we're already too deep in to
			// if,else hell to keep going; we increment var o below
			// if var R is true (20111125/straup)

			if (! R){
				o++;
				continue;
			}

			var yyyy = data['raw_x'][j];
			var mm = data['raw_y'][i];

			var url = url_template;
			url = url.replace("{X}", data['raw_x'][j]);
			url = url.replace("{Y}", data['raw_y'][i]);

			var dx = leftgutter + X * (j + .5) - whatisthis - R;
			var dy = Y * (i + .5) - 10;

			(function(dx, dy, R, value, url){

				var color = "hsb(" + [(1 - R / max) * .5, 1, .75] + ")";

				var dt = r.circle(dx + whatisthis + R, dy + 10, R);
				dt.attr({stroke: "none", fill: color});

				if (R < 6){
					var bg = r.circle(dx + whatisthis + R, dy + 10, 6);
					bg.attr({stroke: "none", fill: "#000", opacity: .4});
					bg.hide();
				}

				var lbl = r.text(dx + whatisthis + R, dy + 10, value);
				lbl.attr({"font": '10px', stroke: "none", fill: "#fff"});
				lbl.hide();

				var dot = r.circle(dx + whatisthis + R, dy + 10, max);
				dot.attr({stroke: "none", fill: "#000", opacity: 0});

				dot[0].onmouseover = function (){
					if (bg){
						bg.show();
					}

					else {
						var clr = Raphael.rgb2hsb(color);
						clr.b = .5;
						dt.attr("fill", Raphael.hsb2rgb(clr).hex);
					}

					lbl.show();
				};

				dot[0].onmouseout = function (){
					if (bg){
						bg.hide();
					}

					else {
						dt.attr("fill", color);
					}

					lbl.hide();
				};

				dot[0].onclick = function(){
					location.href = url;
				};

			})(dx, dy, R, value, url);

			o++;
		}
	}
}