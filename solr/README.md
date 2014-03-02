# Solr

Add search to parallel-flickr using Solr (4.x).

## Set up

## Starting

Ensure that "parallel-flickr" is listed as a core in your `solr.xml` file, like this:

	<cores>
		<core name="parallel-flickr" instanceDir="${parallel-flickr.solr.home}" />
	</cores>

And then run:

	$> java -Dparallel-flickr.solr.home=/usr/local/parallel-flickr/solr -jar start.jar


## To do:

* A pretty fierce stop word list for indexing

## See also:

* https://lucene.apache.org/solr/
