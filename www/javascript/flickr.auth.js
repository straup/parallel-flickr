function flickr_auth_dialog_request_write_perms(action, redir){

	var auth_url = abs_root_url + 'account/flickr/auth?perms=write';

	if (redir){
		auth_url += '&redir=' + encodeURIComponent(redir);
	}

	var html = '<div id="modal_flickr_auth">';

	html += '<h3>Hey look, a modal dialog!</h3>';

	html += '<p>When you first signed up and authorized this application with Flickr you allowed it access your account with a <q>read</q> only token. ';

	html += '<span style="font-style:italic;">';

	if (action=='fave'){
		html += 'In order to fave another users photos we need to bounce you back through Flickr ';
		html += 'again so that you can authorize us to access your account with a <q>write</q> token. ';
	}

	else if (action=='geo'){
		html += 'In order to be able to edit your photos we need to bounce you back through Flickr ';
		html += 'again so that you can authorize us to access your account with a <q>write</q> token. ';	    
	}

	html += '</span>';

	// other 'actions' go here...

	html += 'You\'ll only have to do this once (or until you revoke the token itself <a href="http://www.flickr.com/services/auth/list.gne">on Flickr</a>). Once you\'ve approved the new token you will be sent back to this webpage.</p>';

	html += '<div class="buttons"><button onclick="location.href=\'' + auth_url + '\'; return false;">Get started</button>&#160;&#160;';
	html += '<button onclick="$.modal.close(); return false;">Nevermind, maybe another time.</button></div>';

	html += '</div>';

	$.modal(html);
}
