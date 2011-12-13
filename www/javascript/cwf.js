/* contacts who've faved (cwf) */

/* TO DO: put all of this in to the barest amount of closure-fu
 * so that it's all scoped locally to cwf stuff...
 */

var photos = new Array();
var thumbs = new Array();
var images = new Array();

var count_photos = 0;
var count_updates = 0;

var idx = 0;

function cwf_init(faves){
    
	photos = faves;
	count_photos = photos.length;

	cwf_init_layout();
	cwf_init_shortcuts();

	for (var i=0; i < count_photos; i++){
		thumbs.push(photos[i][6]);
		images.push(photos[i][7]);
	}

	$.backstretch(thumbs[0]);

	$({}).imageLoader({
		images: thumbs,
		async: true
	});

	$({}).imageLoader({
		images: [ images.shift() ],
		async: false,
		complete: function(){
			cwf_show_photo(idx);
		}
	});

	var last_check = parseInt(new Date() / 1000);
	cwf_schedule_check_photos(last_check);
}

function cwf_init_subscription(){

	var delay = (1000 * 60) * 1;

	var callback = function(rsp){

		rsp = JSON.parse(rsp);

		var new_photos = rsp['photos'];
		var count_photos = new_photos.length;

		// TO DO: status indicator if there are no photos...

		if (! count_photos){
			cwf_init_subscription(delay);
			return;
		}

		var faves = new Array();

		for (var i = 0; i < count_photos; i++){

			var ph = new_photos[i];

			faves.push([
				ph['photo_id'],
				ph['title'],
				ph['owner'],
				ph['ownername'],
				ph['faved_by_nsid'],
				ph['faved_by'],
				ph['thumb_url'],
				ph['display_url']
			]);
		}

		cwf_init(faves);
		return;
	};

	setTimeout(function(){

		$.ajax({
			url: '/api',
			data: {
				'method': 'flickr.photos.friends.faves',
			},
			success: callback
		});

	}, delay);
}

function cwf_init_layout(){

	$("#content").hide();
	$("#main").css("background-color", "transparent");
	$("#footer").css("opacity", ".75");
	$("#main").append("<div id=\"cwf_about\"></div>");
}

function cwf_init_shortcuts(){
    
	$(document).keypress(function(e){

		if (e.keyCode == 37){
			cwf_show_previous_photo("overflow");
		}

		else if (e.keyCode == 39){
			cwf_show_next_photo("overflow");
		}

		else {}
	});

}
function cwf_schedule_check_photos(older_than){

	var delay = (60 * 1000) * 1;

	setTimeout(function(){

		$.ajax({
			url: '/api',
			data: {
				'method': 'flickr.photos.friends.faves',
				'older_than': older_than
			},
			success: cwf_check_photos_callback
		});

	}, delay);
}

function cwf_check_photos_callback(rsp){

	try {
		_cwf_check_photos_callback(rsp);
	}

	catch(e){
		console.log(e);
		console.log(rsp);
	}

	last_check = parseInt(new Date() / 1000);
	cwf_schedule_check_photos(last_check);
}

function _cwf_check_photos_callback(rsp){

	rsp = JSON.parse(rsp);

	var new_photos = rsp['photos'];
	var count_photos = new_photos.length;

	if (count_photos){

		new_photos.reverse();
		var preload = [];

		for (var i = 0; i < count_photos; i++){
			var ph = new_photos[i];

			preload.push(ph['display_url']);
			preload.push(ph['thumb_url']);

			photos.unshift([
				ph['photo_id'],
				ph['title'],
				ph['owner'],
				ph['ownername'],
				ph['faved_by_nsid'],
				ph['faved_by'],
				ph['thumb_url'],
				ph['display_url']
			]);
		}

		$({}).imageLoader({
			images: preload,
			async: true
		});

		idx += count_photos;
		count_updates += count_photos;

		var msg = "<a href=\"#\" onclick=\"cwf_show_photo(0);return false;\">";

		if (count_updates > 1){
			msg += "there are " + count_updates + " new faves";
		}

		else {
			msg += "there are new faves";
		}

		msg += "</a>";

		$("#cwf_updates").html(msg);
	}
}

function cwf_next_photo(allow_overflow){

	if (allow_overflow){
		return (idx < (photos.length - 1)) ? idx + 1 : 0;
	}

	return (idx < (photos.length - 1)) ? idx + 1 : -1
}

function cwf_previous_photo(allow_overflow){

	if (allow_overflow){
		return (idx > 0) ? idx - 1 : count_photos - 1;
	}

	return (idx > 0) ? idx - 1 : -1;
}

function cwf_show_previous_photo(overflow){
	var prev = cwf_previous_photo(overflow);
	cwf_show_photo(prev);
}

function cwf_show_next_photo(overflow){
	var next = cwf_next_photo(overflow);
	cwf_show_photo(next);
}

function cwf_show_photo(index){

	// unsure about this...
	count_updates = 0;

	idx = index;

	var preload = new Array();

	for (var i=0; i < 2; i++){

		if (! images.length){
			break;
		}

		preload.push(images.shift());
		preload.push(images.unshift());
	}

	if (preload.length){
		$({}).imageLoader({
			images: preload,
			async: true
		});
	}

	var photo = photos[index];
	var photo_id = photo[0];
	var title = photo[1];
	var taken_by_nsid = photo[2];
	var taken_by_name = photo[3];
	var faved_by_nsid = photo[4];
	var faved_by_name = photo[5];
	var thumb = photo[6];
	var img = photo[7];

	var num = index + 1;

	title = title + ", by " + taken_by_name;

	msg = "<a href=\"http://www.flickr.com/photos/" + taken_by_nsid + "/" + photo_id + "/\" target=\"_flickr\" title=\"" + title + "\">";
	msg += "<img src=\"" + img + "\" /></a><br >";

	msg += "<div id=\"cwf_about_text\">";
	msg += "<a href=\"http://www.flickr.com/photos/" + faved_by_nsid + "/faves/\" target=\"_flickr\">" + faved_by_name + "</a>";
	msg += " <span style=\"font-size:1.2em;font-weight:700;\">☆</span>  ";
	msg += "<a href=\"http://www.flickr.com/photos/" + taken_by_nsid + "/\" target=\"_flickr\">" + taken_by_name + "</a><br />";

	msg += "no. " + num + " of " + count_photos + " faves";

	if (num > 1){
		msg += " / <a href=\"#\" onclick=\"cwf_show_next_photo();return false;\">before</a>";
	}

	if (num < count_photos){
		msg += " / <a href=\"#\" onclick=\"cwf_show_previous_photo();return false;\">after</a>";
	}

	msg += "<div id=\"cwf_updates\"></div>";
	msg += "</div>";

	$("#cwf_about").html("");

	$.backstretch(thumb);
	$("#cwf_about").html(msg);
}
