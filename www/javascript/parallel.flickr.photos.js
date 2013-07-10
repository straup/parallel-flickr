function parallel_flickr_photos_delete(photo_id, onsuccess, onerror){
    
	var method = 'parallel.flickr.photos.delete';

	var args = {
		'id': photo_id
	};

	parallel_flickr_api_call(method, args, onsuccess, onerror)
}
