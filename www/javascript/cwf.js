/* contacts who've faved (cwf) */

/* TO DO: put all of this in to the barest amount of closure-fu
 * so that it's all scoped locally to cwf stuff...
 */

var photos = new Array();
var thumbs = new Array();
var images = new Array();

var count_photos = 0;
var count_updates = 0;

var can_fave = 0;

var idx = 0;

function cwf_init(faves){
    
	photos = faves;
	count_photos = photos.length;

	cwf_init_layout();
	cwf_init_shortcuts();

	var hash_photo = location.hash;
	var hash_idx = null;

	if (hash_photo){
		hash_photo = parseInt(hash_photo.substring(1, hash_photo.length));
	}

	for (var i=0; i < count_photos; i++){
		thumbs.push(photos[i][6]);
		images.push(photos[i][7]);

		/*
		  this is not ideal because the user may actually be trying
		  to fave the same photo later down the list but for now it
		  will do; the point is that if they've gotten this far they
		  actually have a write token now so this step will only happen
		  once; still, not ideal; see also below in cwf_show_photo()
		  (20120216/straup)
		*/

		if ((hash_photo) && (! hash_idx) && (photos[i][0] == hash_photo)){
			hash_idx = i;
		}
	}

	if (hash_idx){
		idx = hash_idx;
	}

	$.backstretch(thumbs[idx]);

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

		if (! count_photos){

			var d = new Date();
			d = String(d);

			var msg = "Last checked at " + d + ", still nothing yet.";
			$("#last_check").html(msg);

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
			data: {	'method': 'flickr.photos.friends.faves'	},
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

		var left = function(){
			cwf_show_previous_photo("overflow");
		};

		var right = function(){
			cwf_show_next_photo("overflow");
		};

		var up = function(){
			cwf_show_photo(0);
		};

		var down = function(){
			cwf_show_photo((photos.length - 1));
		};

	
		$(document).keydown(function(e){

		if (e.keyCode == 37){
			left();
		}

		else if (e.keyCode == 38){
			up();
		}

		else if (e.keyCode == 39){
			right();
		}

		else if (e.keyCode == 40){
			down();
		}

		else if (e.keyCode == 80){
			cwf_toggle_pixel_mode(e.shiftKey);
		}

		else {
			/* console.log(e); */
		}
	});

	// http://www.netcu.de/jquery-touchwipe-iphone-ipad-library

	$(document).touchwipe({
		wipeLeft: right,
		wipeRight: left,
		wipeUp: up,
		wipeDown: down,
		min_move_x: 20,
		min_move_y: 20,
		preventDefaultEvents: true
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
			success: cwf_check_photos_callback,
			error: function(e){
				console.log(e);
				cwf_schedule_check_photos(older_than);
			}
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

	/* console.log('pre: p:' + count_photos + ' u:' + count_updates); */

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

		/* this is buggy (20120216/straup) */

		idx += count_photos;
		count_updates += count_photos;

		var msg = "<a href=\"#\" onclick=\"cwf_show_photo(0);return false;\">";

		/* console.log('post: p:' + count_photos + ' u:' + count_updates); */
		
		if (count_updates > 1){
			msg += "there are " + count_updates + " new faves";
		}

		else {
			msg += "there are new faves";
		}

		msg += "</a>";

		$("#cwf_updates").html(msg);

		$("#cwf_photo_idx").html(idx + 1);
		$("#cwf_count_photos").html(photos.length);
	}
}

function cwf_next_photo(allow_overflow){

	if (allow_overflow){
		return (idx < (photos.length - 1)) ? idx + 1 : 0;
	}

	return (idx < (photos.length - 1)) ? idx + 1 : -1;
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

	/*
	  maybe maybe but it produces URLs that are ugly as sin; also
	  not sure how to integrate it with the auth token juggling
	  code above (in init) and in photo.favorites.js which only
	  expects a photo id. TBD... (20120216/straup)
	*/

	/*
	var photo_id = photos[index][0];
	var faved_by = photos[index][4];

	var href = location.href.split("#");
	location.href = href[0] + "#" + faved_by + "/" + photo_id;
	*/

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
	var is_faved = photo[8];

	var num = index + 1;

	title = title + ", by " + taken_by_name;

	msg = "<a href=\"http://www.flickr.com/photos/" + taken_by_nsid + "/" + photo_id + "/\" target=\"_flickr\" title=\"" + title + "\">";
	msg += "<img src=\"" + img + "\" /></a><br >";

	msg += "<div id=\"cwf_about_text\">";
	msg += "<a href=\"http://www.flickr.com/photos/" + faved_by_nsid + "/faves/\" target=\"_flickr\">" + faved_by_name + "</a>";
	//msg += " <span style=\"font-size:1.2em;font-weight:700;\">☆</span>  ";
	msg += " <span>" + symbols_faved + "</span>  ";
	msg += "<a href=\"http://www.flickr.com/photos/" + taken_by_nsid + "/\" target=\"_flickr\">" + taken_by_name + "</a><br />";

	msg += "no. <span id=\"cwf_photo_idx\">" + num + "</span> of <span id=\"cwf_count_photos\">" + photos.length + "</span> faves";

	if (num > 1){
		msg += " / <a href=\"#\" onclick=\"cwf_show_next_photo();return false;\" title=\"before, keyboard shortcut: ⇦\">before</a>";
	}

	if (num < count_photos){
		msg += " / <a href=\"#\" onclick=\"cwf_show_previous_photo();return false;\" title=\"after, keyboard short: ⇨\">after</a>";
	}

	// TO DO: is photo faved?
	// TO DO: generic fave js functions not bound to cwf stuff?
	// TO DO: does user have write token?
	// TO DO: if no auth token redirect to this specific photo...

	msg += ' / ' + photo_favorites_generate_html(photo_id, is_faved);

	msg += "<div id=\"cwf_updates\"></div>";
	msg += "</div>";

	$("#cwf_about").html("");

	$.backstretch(thumb);
	$("#cwf_about").html(msg);
}

function cwf_toggle_pixel_mode(do_fullscreen){

	var a = $("#cwf_about");
	var f = $("#footer");

	if (a.css("display") == "none"){
		a.show();
		f.show();

		if (screenfull){
			screenfull.exit();
		}
	}

	else {
		a.hide();
		f.hide();

		if ((do_fullscreen) && (screenfull)){
			screenfull.request();
		}
	}
}
