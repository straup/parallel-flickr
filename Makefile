it: clean
so: all

all: todo js templates

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
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/punchcard.js > www/javascript/punchcard.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/cwf.js > www/javascript/cwf.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/ffbp.js > www/javascript/ffbp.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/jquery.lightbox.ffbp.js > www/javascript/jquery.lightbox.ffbp.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/photo.geo.js > www/javascript/photo.geo.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/flickr.auth.js > www/javascript/flickr.auth.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/photo.favorites.js > www/javascript/photo.favorites.min.js
	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar --js www/javascript/sharkify.js > www/javascript/sharkify.min.js

templates:
	php -q ./bin/compile-templates.php

secret:
	php -q ./bin/generate_secret.php

clean:
	rm -f ./TODO.txt
