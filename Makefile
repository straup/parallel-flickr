it: clean
so: all

all: todo js css templates

todo:
	touch TODO.txt
	echo "# This file was generated automatically by grep-ing for 'TO DO' in the source code." > ./TODO.txt
	echo "# This file is meant as a pointer to the actual details in the files themselves." >> TODO.txt
	echo "# This file was created "`date` >> TODO.txt
	echo "" >> TODO.txt
	grep -n -r -e "TO DO" www >> TODO.txt
	grep -n -r -e "TO DO" bin >> TODO.txt

js:

	# these needs to be cleaned up per the google compiler's whinging...	
	# java -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/htmapl.js > www/javascript/htmapl.min.js
	# java -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/modestmaps.markers.js > www/javascript/modestmaps.markers.min.js

	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/parallel.flickr.api.js > www/javascript/parallel.flickr.api.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/cwf.js > www/javascript/cwf.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/ffbp.js > www/javascript/ffbp.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/photo.geo.js > www/javascript/photo.geo.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/flickr.auth.js > www/javascript/flickr.auth.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/photo.favorites.js > www/javascript/photo.favorites.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/sharkify.js > www/javascript/sharkify.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/jquery.lightbox.ffbp.js > www/javascript/jquery.lightbox.ffbp.min.js

	# java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/backstretch.js --js www/jquery-ui-1.8.16.custom.js --js jquery.imageloader.js --js www/javascript/simplemodal.js --js www/javascript/screenfull.js --js www/javascript/jquery.touchwipe.1.1.1.js  --js www/javascript/jquery.ios-shake.js > www/javascript/cwf.jquery.bundle.min.js

	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/cwf.js --js www/javascript/photo.favorites.js --js www/javascript/flickr.auth.js  > www/javascript/cwf.bundle.min.js

css:
	cat www/css/parallel-flickr-main.source.css www/css/parallel-flickr-pagination.source.css www/css/parallel-flickr-photos*.source.css | java -jar lib/yuicompressor/yuicompressor-2.4.7.jar --type css -o www/css/parallel-flickr.min.css

	cat www/css/parallel-flickr-admin.source.css | java -jar -Xmx64m -jar lib/yuicompressor/yuicompressor-2.4.7.jar --type css -o www/css/parallel-flickr-admin.min.css

	cat www/css/parallel-flickr-api.source.css | java -jar -Xmx64m -jar lib/yuicompressor/yuicompressor-2.4.7.jar --type css -o www/css/parallel-flickr-api.min.css

templates:
	php -q ./bin/compile-templates.php

secret:
	php -q ./bin/generate_secret.php

clean:
	rm -f ./TODO.txt
