// http://raphaeljs.com/github/dots.html
// TO DO: redraw on window resize...

$(function () {

	var data = [];
        var axisx = [];
	var axisy = [];
	var table = $("#for-chart");

	table.hide();

	$("tbody td", table).each(function (i){
		data.push(parseFloat($(this).text(), 10));
	});

	$("tbody th", table).each(function (){
		axisy.push($(this).text());
	});

	$("tfoot th", table).each(function () {
		axisx.push($(this).text());
	});

	var width = window.innerWidth * .9;
	var height = window.innerHeight * .8;

	var label_offset = 30;
	var leftgutter = label_offset * 2;
	var bottomgutter = 25;

	var r = Raphael("chart", width, height);

	var txt = {"font": '10px', stroke: "none", fill: "#666"};

	var X = (width - leftgutter) / axisx.length;
	var Y = (height - bottomgutter) / axisy.length;
	var color = $("#chart").css("color");

	var max = Math.round(X / 2) - 1;
      
	for (var i = 0, ii = axisx.length; i < ii; i++){
		r.text(leftgutter + X * (i + .5), height - 6, axisx[i]).attr(txt);
	}

	for (var i = 0, ii = axisy.length; i < ii; i++){
		r.text(label_offset, Y * (i + .5), axisy[i]).attr(txt);
	}

	var whatisthis = 35;     
	var o = 0;

	// please finish indenting me correctly...

	for (var i = 0, ii = axisy.length; i < ii; i++){

		for (var j = 0, jj = axisx.length; j < jj; j++){

			var R = data[o] && Math.min(Math.round(Math.sqrt(data[o] / Math.PI) * 4), max);

            if (R) {
                (function (dx, dy, R, value) {
                    var color = "hsb(" + [(1 - R / max) * .5, 1, .75] + ")";
                    var dt = r.circle(dx + whatisthis + R, dy + 10, R).attr({stroke: "none", fill: color});
                    if (R < 6) {
                        var bg = r.circle(dx + whatisthis + R, dy + 10, 6).attr({stroke: "none", fill: "#000", opacity: .4}).hide();
                    }
                    var lbl = r.text(dx + whatisthis + R, dy + 10, data[o])
                            .attr({"font": '10px', stroke: "none", fill: "#fff"}).hide();
                    var dot = r.circle(dx + whatisthis + R, dy + 10, max).attr({stroke: "none", fill: "#000", opacity: 0});
                    dot[0].onmouseover = function () {
                        if (bg) {
                            bg.show();
                        } else {
                            var clr = Raphael.rgb2hsb(color);
                            clr.b = .5;
                            dt.attr("fill", Raphael.hsb2rgb(clr).hex);
                        }
                        lbl.show();
                    };
                    dot[0].onmouseout = function () {
                        if (bg) {
                            bg.hide();
                        } else {
                            dt.attr("fill", color);
                        }
                        lbl.hide();
                    };
                })(leftgutter + X * (j + .5) - whatisthis - R, Y * (i + .5) - 10, R, data[o]);
            }
            o++;
        }
    }
});