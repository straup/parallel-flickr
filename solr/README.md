# Solr

Add search to parallel-flickr using Solr (4.x).

## Set up

Unlike earlier versions of Parallel Flickr which came with a local copy of Solr you will need to download and install Solr yourself and then point to the parallel-flickr Solr core (this folder).

A proper "getting started with Solr" section needs to be written for this document but in the meantime these are good places to start:

* http://www.solrtutorial.com/solr-in-5-minutes.html#Install
* http://kevindoran1.blogspot.com/2013/02/solr-tutorial.html

Once you've got Solr running ensure that "parallel-flickr" is listed as a core in your `solr.xml` file, like this:

	<cores>
		<core name="parallel-flickr" instanceDir="${parallel-flickr.solr.home}" />
	</cores>

And then start Solr as you normally would but be sure specify the `parallel-flickr.solr.home` property, for example:

	$> java -Dparallel-flickr.solr.home=/usr/local/parallel-flickr/solr -jar start.jar

## To do:

* A pretty fierce stop word list for indexing

## See also:

* https://lucene.apache.org/solr/
