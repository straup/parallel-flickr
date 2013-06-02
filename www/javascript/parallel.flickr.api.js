function parallel_flickr_api_call(method, args, onsuccess, onerroe){
    
	var endpoint = parallel_flickr_api_endpoint();

	args['method'] = method;

	if (! args['access_token']){

	   var site_token = parallel_flickr_api_site_token();

	    if (site_token){
		args['access_token'] = site_token;
	    }
	}

	if (! onerror){

		onerror = function(rsp){		    
                	console.log("there was a problem calling '" + method + "':");
                	console.log(rsp);
                };
        }

        $.ajax({
                'url': endpoint,
                'type': 'POST',
                'data': args,
                'dataType': 'json',
                'success': onsuccess,
                'error': onerror
	});

	// console.log("calling " + args['method']);
}

function parallel_flickr_api_endpoint(){
	return document.body.getAttribute("data-api-endpoint");
}

function parallel_flickr_api_site_token(){
	return document.body.getAttribute("data-api-site-token");
}
