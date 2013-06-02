function parallel_flickr_api_call(method, args, onsuccess, onerroe){
    
	var endpoint = parallel_flickr_api_endpoint();

	args['method'] = method;

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
	return '/api/rest/';
}
