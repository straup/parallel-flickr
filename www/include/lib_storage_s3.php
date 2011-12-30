<?php

    /*
        I think the easiest thing to do is mirror the local storage directory structure up on S3. That way you just have to 
        swap out the hostname in the URL. Simplicity!
         
        Things we need to be able to do:
        1) Store an image (object?)
        2) Delete an image
        3) Replace/rename (combination of the above 2, I don't care about race conditions)
    */

    loadlib('http');

    function storage_s3_store($keyname, $bytes, $content_type) {
    
        if (!$bytes) {
            return array('ok' => 0, 'rsp' => 'File does not exist');
        }

        $bucket = $GLOBALS['cfg']['amazon_s3_bucket_name'];

        $md5 = base64_encode(md5($bytes, true));
        $date = gmdate('r');

        $fields = array(
            'Date' => $date,
            'Content-MD5' => $md5,
            'Content-Type' => $content_type,
            'Authorization' => storage_s3_create_authorization_header_string('PUT', $md5, $content_type, $date, "/$bucket$keyname"),
        );

        $rsp = http_put($bucket . ".s3.amazonaws.com$keyname", $bytes, $fields);

        return $rsp;
    }

    function storage_s3_delete($fullpath) {
        
        $bucket = $GLOBALS['cfg']['amazon_s3_bucket_name'];
        $date = gmdate('r');

        $fields = array(
            'Date' => $date,
            'Content-Type' => 'text/plain',
            'Authorization' => storage_s3_create_authorization_header_string('DELETE', null, 'text/plain', $date, "/$bucket$fullpath"),
        );

        return http_delete("$bucket.s3.amazonaws.com$fullpath", $fields);
    }

    function storage_s3_create_authorization_header_string($verb, $md5, $type, $date, $resource) {

        $string = "$verb\n$md5\n$type\n$date\n$resource";

        $hmac = base64_encode(hash_hmac('sha1', $string, $GLOBALS['cfg']['amazon_s3_secret_key'], true));
        return "AWS {$GLOBALS['cfg']['amazon_s3_access_key']}:$hmac"; 
    }

