var timeout_geo = null;

// currently this only lets you edit geo context (20111229/straup)

function photo_geo_edit_meta(photo_id){

	if (timeout_geo){
		clearTimeout(timeout_geocontext);
	}

	var old_ctx = $("#edit_geo").attr("geo:context");

	// TO DO: pass down / read from PHP geo context map

	var ctx_map = {
		0: 'in a place that doesn\'t matter (not defined)',
		1: 'indoors',
		2: 'outdoors'
	};

	var html = '<div id="modal_geo">';
	html = '<form id="photo_geo_update_context">';

	html += '<h3>Edit theo geo context for this photo</h3>';
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
	html += '</div>';

	html += '<div id="photo_geo_status"></div>';
	html += '<div id="photo_geo_close"><a href="#" onclick="$.modal.close(); return false;">close</a></div>';

	// http://www.ericmmartin.com/projects/simplemodal/

	$.modal(html);

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
			msg += '<p style="font-style:italic;font-size:small;">This dialog will close itself automagically in a moment...</p>';

			$("#photo_geo_status").html(msg);

			timeout_geocontext = setTimeout(function(){
				$.modal.close();
			}, 1000);
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
