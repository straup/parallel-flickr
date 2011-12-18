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

			if (i < 5){
				preload.push(images[nsid][i]);
			}

			else {
				lazyload.push(images[nsid][i]);
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

	for (var i=0; i < count_photos; i++){

		var src = images[nsid][i];
		var img = "<img src=\"" + src + "\" />";

		html += "<a href=\"" + src + "\">" + img + "</a>";
	}

	$("#photowell").html(html);
	$("#photowell").lightBox();
}
