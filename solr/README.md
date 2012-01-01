parallel-flickr-solr
==

Add search to parallel-flickr using Solr (3.4).

**This works but needs better documentation** (read: you'll need to be familiar with Solr if you want to do anything in the meantime.)

Quick start
--

From inside the parallel-flickr/solr directory type:

	java -Dsolr.solr.home=. -Dsolr.solr.cores=. -jar start.jar

This will start a Solr endpoint for parallel-flickr on port 9999. You can query
it by typing:

	curl http://localhost:9999/solr/parallel-flickr/select?q=*:*
	
What's going on here?
--

* _start.jar_ is the thing that starts Solr

* _start.jar_ is going to spin up a web server using Jetty on port 9999; you can
  change the port number in [parallel-flickr/solr/etc/jetty.xml](https://github.com/straup/parallel-flickr/blob/master/solr/etc/jetty.xml).

* _start.jar_ is going to look for a file called _solr.xml_ in the
  _solr.solr.home_ directory. Its presence will indicate that Solr is being run
  in "multicore" mode. If you don't know what that means don't worry other than
  to know that the _solr.xml_ file is where Solr will look for details about
  what to load next.

* See the _solr.solr.cores_ flag we're passing? That's going to be referenced
  from a bunch of files that have or are about to be loaded. Solr doesn't try to
  be overly clever about where to look for things so it's best just to be
  explicit.
  
* The _solr.xml_ file looks like this:

	&lt;?xml version="1.0" encoding="UTF-8" ?&gt;

	&lt;solr persistent="false"&gt;
		&lt;cores adminPath="/admin/cores"&gt;
			&lt;core name="parallel-flickr" instanceDir="${solr.solr.cores}/parallel-flickr" /&gt;
		&lt;/cores&gt;
	&lt;/solr&gt;

* If you look carefully you'll see there isn't a default _solr.xml_ file,
  because it is explicitly prevented from being checked in to git for security
  and privacy reasons. You will need to copy the
  [solr.xml.example](https://github.com/straup/parallel-flickr/blob/master/solr/solr.xml.example)
  file instead.

* Then, _start.jar_ will look for a directory in
  ${solr.solr.cores}/parallel-flickr called "conf" which contains a bunch of
  config files specific to the parallel-flickr index (or "core"). There are two
  you care about right now: [schema.xml](https://github.com/straup/parallel-flickr/blob/master/solr/parallel-flickr/conf/solrconfig.xml) and [solrconfig.xml](https://github.com/straup/parallel-flickr/blob/master/solr/parallel-flickr/conf/solrconfig.xml).
  
* The first contains information about what gets indexed. The second contains
  information about how that index is stored and queried. It is also where you
  tell Solr _where_ to store the index on disk. By default that is:
  
	&lt;dataDir&gt;${solr.solr.cores}/parallel-flickr/data&lt;/dataDir&gt;  

* In order to use Solr you'll need to enable it in your [config file](https://github.com/straup/parallel-flickr/blob/master/www/include/config.php.example) with the following configs:
 
	$GLOBALS['cfg']['enable_feature_solr'] = 1;

	$GLOBALS['cfg']['solr_endpoint'] = 'http://localhost:9999/solr/parallel-flickr/';

* To index (or re-index) exsiting data that you've imported from Flickr you will need to run the [backfill_solr_index_photos.php](https://github.com/straup/parallel-flickr/blob/master/bin/backfill_solr_index_photos.php) script, like this:

	$> php -q ./bin/backfill_solr_index_photos.php

* _start.jar_ will launch Solr as a "foreground" application. If you want to run
  it as a proper "background" service take a look at the
  [init.d/solr.sh](https://github.com/straup/parallel-flickr/blob/master/solr/init.d/solr.sh) file.

Important
--

Solr doesn't have any kind of built-in authorization or authentication model so
you should be careful not to run it on a port that is accessible to the public
Internet. If you do and a bad person discovers it they will be able to freely
read and write to your Solr database.

To do:
--

* A pretty fierce stop word list for indexing

See also:
--

* [Solr](https://lucene.apache.org/solr/)
