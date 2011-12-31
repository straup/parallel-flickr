// TO DO: clean up markup and semantic groupings of the various options...

function photo_geo_edit_meta(photo_id){

	var old_ctx = $("#edit_geo").attr("geo:context");

	// TO DO: pass down / read from PHP geo context map

	var ctx_map = {
		0: 'in a place that doesn\'t matter (not defined)',
		1: 'indoors',
		2: 'outdoors'
	};

	var html = '<div id="modal_geo">';
	html += '<div id="photo_geo_status"></div>';
	html += '<form id="photo_geo_update_context">';

	html += '<h3>Edit the geo context for this photo</h3>';
	html += 'This photo was taken ';

	html += '<select id="new_geocontext">';

	for (i in ctx_map){
		html += '<option value="' + i + '"';

		if (i == old_ctx){
			html += ' selected="true" disabled="true"';
		}

		html += '>' + ctx_map[i] + '</option>';
	}

	html += '</select>';
	html += '&#160;';
	html += '<input type="submit" value="UPDATE" />';
	html += '</form>';

	// corrections

	// FIX ME: make me less brittle...
	var old_placename = $("#geo_placename a").html();

	html += '<div id="photo_geo_corrections">';
	html += '<h3>Edit the place name for this photo</h3>';
	html += '<p>Flickr thinks this photo was taken in <q>';
	html += old_placename;
	html += '</q>. <a href="#" id="photo_geo_corrections_fetch">Fetch the list of alternate place names.</a>';
	html += '</p>';

	html += '</div>';

	html += '</div>';
	html += '<div id="photo_geo_close"><a href="#" onclick="$.modal.close(); return false;">close</a></div>';

	// http://www.ericmmartin.com/projects/simplemodal/

	$.modal(html);

	$("#photo_geo_corrections_fetch").click(function(){

		var _onsuccess = function(rsp){

			$("#photo_geo_status").html("");

			if (rsp['stat'] != 'ok'){
				$("#photo_geo_status").html("Ack!");
				return;
			}

			var current_woeid = $("#edit_geo").attr("geo:woeid");

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

			html += '</select>';
			html += '&#160;&#160;';
			html += '<input type="submit" value="UPDATE" />';
			html += '</form>';

			$("#photo_geo_corrections").append(html);

			$("#photo_geo_corrections_update").submit(function(){

				var new_woeid = $("#new_woeid");
				new_woeid = new_woeid.val();

				var _onsuccess = function(rsp){
					$("#photo_geo_status").html(rsp['stat']);
					console.log(rsp);
					// update place name in display
					// auto close window ?
				};

				var args = {
					'method': 'flickr.photos.geo.correctLocation',
					'photo_id': photo_id,
					'woeid': new_woeid
				};

				$.ajax({
					'url' : '/api/',
					'type': 'POST',
					'data': args,
					'success': _onsuccess
				});

				$("#photo_geo_corrections_update").hide();
				$("#photo_geo_status").html("Poking the Flickr API...");

				return false;
			});

		};

		var args = {
			'method': 'flickr.photos.geo.possibleCorrections', 
			'photo_id': photo_id,
			'place_type': 'neighbourhood'
		};

		$.ajax({
			'url': '/api/',
			'type': 'GET',
			'data': args,
			'success': _onsuccess
		});

		$("#photo_geo_corrections_fetch").hide();
		$("#photo_geo_status").html("Fetching alternate place names...");
	});

	$("#photo_geo_update_context").submit(function(){

		var new_ctx = $("#new_geocontext");
		new_ctx = new_ctx.val();

		if (new_ctx == old_ctx){
			alert("Hey! There's nothing to update!");
			return;
		}

		var args = {
			'method': 'flickr.photos.geo.setContext',
			'photo_id': photo_id,
			'context': new_ctx
		};

		var _onsuccess = function(rsp){

			if (rsp['stat'] != 'ok'){
				$("#photo_geo_status").html("Ack! There was a problem calling the Flickr API.");
				return;
			}

			$("#edit_geo").attr("geo:context", rsp['context']);

			var str_ctx = ctx_map[rsp['context']];

			var taken = '';

			if (rsp['context'] != 0){

				var woeid = $("#edit_geo").attr("geo:woeid");
				var url = places_url + woeid + '/' + str_ctx + '/';

				taken = 'It was taken <a href="' + url + '">' + str_ctx + '</a>';
			}

			$("#geo_context").html(taken);

			var msg = '<p>Success! The geo context for this photo has been updated and is now <strong>' + str_ctx + '</strong>.</p>';
			$("#photo_geo_status").html(msg);
		};

		$.ajax({
			'url': '/api/',
			'type': 'POST',
			'data': args,
			'success': _onsuccess
		});

		$("#photo_geo_update_context").hide();
		$("#photo_geo_status").html("Poking the Flickr API...");

		return false;
	});

}
