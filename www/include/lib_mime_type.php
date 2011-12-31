<?php
    
    $GLOBALS['_mime_type_extensions'] = array(
    	'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
    	'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
    	'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
    	'zip' => 'application/zip', 'gz' => 'application/x-gzip',
    	'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
    	'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
    	'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
    	'css' => 'text/css', 'js' => 'text/javascript',
    	'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
    	'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
    	'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
    	'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
    );
    
    function mime_type_identify($file) {
        $ext = strtolower(pathInfo($file, PATHINFO_EXTENSION));
        return isset($GLOBALS['_mime_type_extensions'][$ext]) ? $GLOBALS['_mime_type_extensions'][$ext] : 'binary/octet-stream';
    }

?>