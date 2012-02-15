function flickr_auth_toggle_perms(perms, redir){

	var auth_url = abs_root_url + 'account/flickr/auth?perms=' + perms;

	if (redir){
		auth_url += '&redir=' + encodeURIComponent(redir);
	}

	var html = '<div id="modal_flickr_auth">';

	html += '<p>... <a href="' + auth_url + '">Okay, get started</a></p>';

	html += '<div id="modal_close"><a href="#" onclick="$.modal.close(); return false;">cancel</a></div>';
	html += '</div>';

	$.modal(html);
}
