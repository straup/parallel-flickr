// TO DO: pass down / read from PHP geo context map

var photo_geo_ctx_map = {
	0: 'in a place that doesn\'t matter (not defined)',
	1: 'indoors',
	2: 'outdoors'
};


function photo_geo_edit_meta(photo_id){

	var html = '<div id="modal_geo">';
	html += '<div id="photo_geo_status"></div>';

	html += '<div id="photo_geo_context">';
	html += _photo_geo_context_generate_html();
	html += '</div>';

	html += '<div id="photo_geo_corrections">';
	html += _photo_geo_corrections_generate_html();
	html += '</div>';

	html += '<div id="photo_geo_close"><a href="#" onclick="$.modal.close(); return false;">close</a></div>';
	html += '</div>';

	// http://www.ericmmartin.com/projects/simplemodal/

	$.modal(html);

	// not thrilled with this...

	$("#photo_geo_context_update").submit(_photo_geo_context_update_onsubmit);
	$("#photo_geo_corrections_fetch").click(_photo_geo_corrections_fetch_onclick);
}

function _photo_geo_context_generate_html(){

	var old_ctx = $("#edit_geo").attr("geo:context");

	var html = '';
	html += '<h3>Edit the geo context for this photo</h3>';

	html += '<form id="photo_geo_context_update">';
	html += 'This photo was taken ';

	html += '<select id="new_geocontext">';

	for (i in photo_geo_ctx_map){
		html += '<option value="' + i + '"';

		if (i == old_ctx){
			html += ' selected="true" disabled="true"';
		}

		html += '>' + photo_geo_ctx_map[i] + '</option>';
	}

	html += '</select>';
	html += '&#160;';
	html += '<input type="submit" value="UPDATE" />';
	html += '</form>';

	return html;
}

function _photo_geo_corrections_generate_html(){

	var selector = "#geo_placename";

	if (places_is_enabled){
		selector += " a";
	}

	var old_placename = $(selector).html();

	var html = '';

	html += '<h3>Edit the place name for this photo</h3>';

	html += '<p>Flickr thinks this photo was taken in <q>';
	html += old_placename;
	html += '</q>. <a href="#" id="photo_geo_corrections_fetch">Fetch the list of alternate place names.</a>';
	html += '</p>';

	return html;
}

function _photo_geo_context_update_onsubmit(){

	var old_ctx = $("#edit_geo").attr("geo:context");

	var new_ctx = $("#new_geocontext");
	new_ctx = new_ctx.val();

	if (new_ctx == old_ctx){
		alert("Hey! There's nothing to update!");
		return;
	}

	var method = 'flickr.photos.geo.setContext';

	var args = {
		'photo_id': photo_id,
		'context': new_ctx
	};

	parallel_flickr_api_call(method, args, _photo_geo_set_context_onsuccess);

	$("#photo_geo_context").hide();

	_photo_geo_status_show("Poking the Flickr API...");

	return false;
}

function _photo_geo_set_context_onsuccess(rsp){

	if (rsp['stat'] != 'ok'){
		_photo_geo_flickr_error(rsp);
		return;
	}

	$("#edit_geo").attr("geo:context", rsp['context']);

	var str_ctx = photo_geo_ctx_map[rsp['context']];

	var taken = '';

	if (rsp['context'] != 0){
		var woeid = $("#edit_geo").attr("geo:woeid");
		var url = places_url + woeid + '/' + str_ctx + '/';
		taken = 'It was taken <a href="' + url + '">' + str_ctx + '</a>';
	}

	$("#geo_context").html(taken);

	var msg = '<p>Success! The geo context for this photo has been updated and is now <strong>' + str_ctx + '</strong>.</p>';
	_photo_geo_status_show(msg);

	setTimeout(function(){

		_photo_geo_status_hide();

		$("#photo_geo_context").html(_photo_geo_context_generate_html);
		$("#photo_geo_context").show();

		$("#photo_geo_context_update").submit(_photo_geo_context_update_onsubmit);
	}, 2500);
}

function _photo_geo_corrections_fetch_onclick(){

	// get placetype here based on zoom level...
	placetype = 'neighbourhood';

	$("#photo_geo_corrections_fetch").hide();

	return _photo_geo_corrections_for_placetype(placetype);
}

function _photo_geo_corrections_for_placetype(placetype){

	var method = 'flickr.photos.geo.possibleCorrections';

	var args = {
		'photo_id': photo_id,
		'place_type': placetype
	};

	parallel_flickr_api_call(method, args, _photo_geo_possible_corrections_onsuccess);

	_photo_geo_status_show("Fetching alternate place names...");
}

function _photo_geo_possible_corrections_onsuccess(rsp){

	_photo_geo_status_hide();

	if (rsp['stat'] != 'ok'){
		$("#photo_geo_status").html("Ack!");
		return;
	}

	var current_woeid = $("#edit_geo").attr("geo:woeid");

	var current_options = $("#new_woeid option");

	if (current_options.length){

		// delete the last option
		$("#new_woeid option:last-child").remove();

		var count = rsp['places'].length;

		if (! count){
			alert("Hrm. There are no more places to choose from");
			return;
		}

		var html = '<option />';

		for (var i=0; i < count; i++){
			var pl = rsp['places'][i];

			html += '<option value="' + pl['woeid'] + '"';
			html += '>' + pl['name'] + '</option>';		
		}

		if (rsp['parent_place_type']){
			html += '<option value="-1" geo:placetype="' + rsp['parent_place_type'] + '">–– fetch more place names ––</option>';
		}

		$("#new_woeid").append(html);
	}

	else {

		var html = '<form id="photo_geo_corrections_update">';

		html += 'It was really taken in ';
		html += '<select id="new_woeid">';
		html += '<option />';

		var count = rsp['places'].length;

		for (var i=0; i < count; i++){
			var pl = rsp['places'][i];

			html += '<option value="' + pl['woeid'] + '"';

			if (pl['woeid'] == current_woeid){
				html += ' disabled="true"';
			}

			html += '>' + pl['name'] + '</option>';		
		}

		if (rsp['parent_place_type']){
			html += '<option value="-1" geo:placetype="' + rsp['parent_place_type'] + '">–– fetch more place names ––</option>';
		}

		html += '</select>';
		html += '&#160;&#160;';
		html += '<input type="submit" value="UPDATE" />';
		html += '</form>';

		$("#photo_geo_corrections").append(html);
		$("#photo_geo_corrections_update").submit(_photo_geo_corrections_update_onsubmit);
	}

}

function _photo_geo_corrections_update_onsubmit(){

	var new_woeid = $("#new_woeid");
	new_woeid = new_woeid.val();

	if (new_woeid == ''){
		alert("Hey! You didn't choose anything.");
		return false;
	}

	/* 
	if (new_woeid == 0){
		// remove all woe ids; do not remove lat,lon
	}
	*/

	if (new_woeid == -1){
		var opt = $("#new_woeid option:last-child");
		var parent_placetype = opt.attr("geo:placetype");
		_photo_geo_corrections_for_placetype(parent_placetype);
		return false;
	}

	var method = 'flickr.photos.geo.correctLocation';

	var args = {
		'photo_id': photo_id,
		'woeid': new_woeid
	};

	parallel_flickr_api_call(method, args, _photo_geo_correct_location_onsuccess);

	$("#photo_geo_corrections").hide();

	_photo_geo_status_show("Poking the Flickr API...");
	return false;
}

function _photo_geo_correct_location_onsuccess(rsp){

	_photo_geo_status_hide();

	if (rsp['stat'] != 'ok'){
		_photo_geo_flickr_error(rsp);
		return;
	}

	var new_woeid = rsp['woeid'];
	var new_placename = rsp['place']['name'];

	$("#edit_geo").attr("geo:woeid", rsp['woeid']);

	if (new_woeid == 0){
		placename = 'a place with no name';
	}

	else if (places_is_enabled){
		var new_placeurl = places_url + new_woeid + '/';
		placename = '<a href="' + new_placeurl + '">' + new_placename + '</a>';
	}

	else {
		placename = new_placename;
	}

	$("#geo_placename").html(placename);

	$("#photo_geo_status").html("Okay! The place name for your photo has been updated. It is now " + new_placename);
	$("#photo_geo_status").show();

	setTimeout(function(){

		_photo_geo_status_hide();

		$("#photo_geo_corrections").html(_photo_geo_corrections_generate_html);
		$("#photo_geo_corrections").show();

		$("#photo_geo_corrections_fetch").click(_photo_geo_corrections_fetch_onclick);
	}, 2500);
}

function _photo_geo_flickr_error(rsp){
	_photo_geo_status_show("Ack! There was a problem calling the Flickr API.");
	return;
}

function _photo_geo_status_show(msg){
	$("#photo_geo_status").html(msg);
	$("#photo_geo_status").show();
}

function _photo_geo_status_hide(){
	$("#photo_geo_status").html("");
	$("#photo_geo_status").hide();
}
