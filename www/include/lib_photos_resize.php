<?php

	#################################################################

	# Where $sz is the maximum dimension on either side

	# TO DO: account for things that are not JPGs (20130526/straup)

	function photos_resize($src, $dest, $sz, $more=array()){

		$rsp = photos_resize_load($src);

		if (! $rsp['ok']){
			return $rsp;
		}

		$im = $rsp['image'];

		list($w, $h, $type, $attr) = getimagesize($src);

		if ($w > $h){
			$ratio = $sz / $w;
			$width = $sz;
			$height = $h * $ratio;
		}

		else {
			$ratio = $sz / $h;
			$height = $sz;
			$width = $w * $ratio;
		}

		# dumper("W,H: {$w},{$h} W,H:{$width},{$height}");

		$resized = imagecreatetruecolor($width, $height);

		imagecopyresampled($resized, $im, 0, 0, 0, 0, $width, $height, $w, $h);

		imagejpeg($resized, $dest);

		return array(
			'ok' => 1,
		);
	}

	#################################################################

	function photos_resize_load($src){

		$type = mime_content_type($src);

		if (! $type){
			return array('ok' => 0, 'error' => 'unable to determine mime-type');
		}

		if (! preg_match("/^image\/(gif|jpeg|png)$/", $type, $m)){
			return array('ok' => 0, 'error' => 'invalid or unsupported image');
		}

		$ext = $m[1];
		$func = "imagecreatefrom{$ext}";

		$im = call_user_func($func, $src);

		if (! $im){
			return array('ok' => 0, 'error' => 'unable to determine mime-type');
		}

		return array('ok' => 1, 'image' => $im);
	}

	#################################################################

	# the end
