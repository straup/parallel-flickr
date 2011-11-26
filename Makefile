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

clean:
	rm -f ./TODO.txt
