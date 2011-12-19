/* flickr for busy people (ffbp) */

/* 
 * TO DO: put all of this in to the barest amount of closure-fu
 * so that it's all scoped locally to cwf stuff...
 */

var lazyload = [];
var open_nsid = null;

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

	$({}).imageLoader({
		images: preload,
		async: true
	});

	if (lazyload.length){
		setTimeout(ffbp_lazyload_photos, 20000);
	}

}

function ffbp_draw_photos(nsid){

	if ($(".ffbp_" + nsid + "_thumb").length){
		ffbp_hide_photos(nsid);
		return;
	}

	// close any user's photos that may have been expanded

	if (open_nsid){
		ffbp_hide_photos(open_nsid);
	}

	open_nsid = nsid;

	// http://leandrovieira.com/projects/jquery/lightbox/

	var count_photos = images[nsid].length;

	for (var i=0; i < count_photos; i++){

		var thumb = images[nsid][i][0];
		var photo = images[nsid][i][1];

		// TO DO: meta colours...

		var img = "<img src=\"" + thumb + "\" height=\"48\" width=\"48\" style=\"border: 3px solid #000;\"/>";

		// TO DO: link to photo on flickr...

		html = "<div class=\"ffbp_thumb ffbp_" + nsid + "_thumb\">";
		html += "<a href=\"" + photo + "\">" + img + "</a>";
		html += "<div class=\"ffbp_thumb_blurb\"><strong>&nbsp;</strong></div>";
		html += "</div>";

		$("#ffbp_" + nsid).after(html);
	}

	$(".ffbp_" + nsid + "_thumb a").lightBox();
}

function ffbp_hide_photos(nsid){
	$(".ffbp_" + nsid + "_thumb").remove();
	open_nsid = null;
}
