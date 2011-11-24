todo:
	touch TODO.md
	echo "# This file was generated automatically by grep-ing for 'TO DO' in the source code." > ./TODO.md
	echo "# This file is meant as a pointer to the actual details in the files themselves." >> TODO.md
	echo "# This file was created "`date` >> TODO.md
	echo "" >> TODO.md
	grep -n -r -e "TO DO" www >> TODO.md
clean:
	rm -f ./TODO.md
