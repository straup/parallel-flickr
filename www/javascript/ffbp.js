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

		var img = "<img src=\"" + thumb + "\" height=\"50\" width=\"50\" style=\"border: 3px solid #eee;\"/>";

		// TO DO: link to photo on flickr...

		html = "<div class=\"ffbp_thumb ffbp_" + nsid + "_thumb\">";
		html += "<a href=\"" + photo + "\">" + img + "</a>";
		html += "<div class=\"ffbp_thumb_blurb\"><strong>&nbsp;</strong></div>";
		html += "</div>";

		$("#ffbp_" + nsid).after(html);
	}

	var buddyicons = $(".ffbp_buddyicon");
	var count = buddyicons.length;

	for (var i=0; i < count; i++){
		var el = $(buddyicons[i]);

		if (el.attr("id") == "ffbp_" + nsid){
			continue;
		}

		el.css("opacity", .1);
	}

	$(".ffbp_" + nsid + "_thumb a").lightBox();
}

function ffbp_hide_photos(nsid){
	$(".ffbp_" + nsid + "_thumb").remove();
	$(".ffbp_buddyicon").css("opacity", 1);
	open_nsid = null;
}
