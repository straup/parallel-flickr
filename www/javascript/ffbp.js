/* flickr for busy people (ffbp) */

/* 
 * TO DO: put all of this in to the barest amount of closure-fu
 * so that it's all scoped locally to cwf stuff...
 */

var lazyload = [];

function ffbp_init(images){

	var preload = [];

	for (nsid in images){

		var count_photos = images[nsid].length;

		for (var i=0; i < count_photos; i++){

			var thumb = images[nsid][i][0];
			var photo = images[nsid][i][1];

			preload.push(thumb);

			if (i < 5){
				preload.push(photo);
			}

			else {
				lazyload.push(photo);
			}
		}
	}

	$({}).imageLoader({
		images: preload,
		async: true
	});

	if (lazyload.length){
		setTimeout(ffbp_lazyload_photos, 15000);
	}
}

function ffbp_lazyload_photos(){

	var preload = [];

	for (var i=0; i < 2; i++){
		preload.push(lazyload.shift());
		preload.push(lazyload.unshift());

		if (! lazyload.length){
			break;
		}
	}

	if (! preload.length){
		return;
	}

	// console.log("lazy load another " + preload.length + " photos");

	$({}).imageLoader({
		images: preload,
		async: true
	});

	if (lazyload.length){
		setTimeout(ffbp_lazyload_photos, 20000);
	}

}

function ffbp_draw_photos(nsid){

	var count_photos = images[nsid].length;

	var html = '';

	// these need to be be inserted one at a time rather than as a single
	// monolithic div

	for (var i=0; i < count_photos; i++){

		var thumb = images[nsid][i][0];
		var photo = images[nsid][i][1];

		var img = "<img src=\"" + thumb + "\" height=\"48\" width=\"48\" />";

		// fix me: meta colours...
		html += "<div style=\"float:left; margin-right:12px; margin-bottom:15px; border: 3px solid #000;\">";
		html += "<a href=\"" + photo + "\">" + img + "</a>";
		html += "</div>";
	}

	var photowell = "#photowell_" + nsid;

	$(photowell).html(html);
	$(photowell).show();
	$(photowell +" a").lightBox();
}
