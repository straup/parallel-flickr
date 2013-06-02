var symbols_faved="★";
var symbols_notfaved="☆";

function photo_favorites_add(photo_id){

	if (! can_fave){

		/* sudo put me in a function? */

		var host = location.host;
		var href = location.href.split("#");

		var redir = href[0];
		redir = redir.replace("http://", "");
		redir = redir.replace(host, "");

		redir += "#" + photo_id;

		flickr_auth_dialog_request_write_perms('fave', redir);
		return;
	}

	var method = 'flickr.favorites.add';

    	var args = {
		'photo_id': photo_id
	};

	var onsuccess = function(rsp){
		var photo_id = rsp['photo_id'];
		photo_favorites_toggle_html(photo_id, 1);
	};

	parallel_flickr_api_call(methods, args, onsuccess);

	photo_favorites_toggle_html_api(photo_id);
}

function photo_favorites_remove(photo_id){

	var method = 'flickr.favorites.remove';

	var args = {
		'photo_id': photo_id
	};

	var onsuccess = function(rsp){
		var photo_id = rsp['photo_id'];
		photo_favorites_toggle_html(photo_id, 0);
	};

	parallel_flickr_api_call(methods, args, onsuccess);

	photo_favorites_toggle_html_api(photo_id);
}

function photo_favorites_generate_html(photo_id, faved){

	var uid = photo_favorites_uid(photo_id);
	var symbol = photo_favorites_symbol(photo_id, faved);
	var onclick = photo_favorites_onclick(photo_id, faved);
	var title = photo_favorites_title(photo_id, faved);
	var classname = photo_favorites_classname(photo_id, faved);

	var html= '<a href="#" id="' + uid + '" onclick="' + onclick + '" title="' + title + '" class="' + classname + '">';
	html += symbol;
	html += '</a>';

	return html;
}

function photo_favorites_toggle_html_api(photo_id){
	var selector = photo_favorites_selector(photo_id);
	var el = $(selector);

	el.html("...");
	el.attr("onclick", "return false;");
	el.attr("title", "talking to the sky");
	el.attr("class", "");
}

function photo_favorites_toggle_html(photo_id, faved){

	var selector = photo_favorites_selector(photo_id);
	var symbol = photo_favorites_symbol(photo_id, faved);
	var onclick = photo_favorites_onclick(photo_id, faved);
	var title = photo_favorites_title(photo_id, faved);
	var classname = photo_favorites_classname(photo_id, faved);

	var el = $(selector);
	el.html(symbol);
	el.attr("onclick", onclick);
	el.attr("title", title);
	el.attr("class", classname);
}

function photo_favorites_symbol(photo_id, faved){
	var symbol = (faved) ? symbols_faved : symbols_notfaved;
	return symbol;
}

function photo_favorites_onclick(photo_id, faved){
	var func = (faved) ? 'photo_favorites_remove' : 'photo_favorites_add';
	return func + "(" + photo_id + "); return false;";
}

function photo_favorites_title(photo_id, faved){
	var title = (faved) ? "remove as favourite" : "add as favourite";
	return title;
}

function photo_favorites_classname(photo_id, faved){
	return (faved) ? "photo_faved" : "photo_notfaved";
}

function photo_favorites_uid(photo_id){
	return "photo_favorites_" + photo_id;
}

function photo_favorites_selector(photo_id){
	return "#" + photo_favorites_uid(photo_id);
}
