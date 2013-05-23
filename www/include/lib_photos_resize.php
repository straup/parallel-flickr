<?php

	#################################################################

	# Where $sz is the maximum dimension on either side

	function photos_resize($src, $dest, $sz, $more=array()){

		$im = imagecreatefromjpeg($src);
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

	# the end
