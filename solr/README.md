parallel-flickr-solr
==

Add search to parallel-flickr using Solr (3.4 or higher).

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

* _start.jar_ is the thing that spins up Solr

* _start.jar_ is going to spin up a web server using Jetty on port 9999; you can
  change the port number in parallel-flickr/solr/etc/jetty.xml

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

	<?xml version="1.0" encoding="UTF-8" ?>

	<solr persistent="false">

		<cores adminPath="/admin/cores">
			<core name="parallel-flickr" instanceDir="${solr.solr.cores}/parallel-flickr" />
		</cores>
	</solr>

* Then, _start.jar_ will look for a directory in
  ${solr.solr.cores}/parallel-flickr called "conf" which contains a bunch of
  config files specific to the parallel-flickr index (or "core"). There are two
  you care about right now: _schema.xml_ and _solrconfig.xml_.
  
* The first contains information about what gets indexed. The second contains
  information about how that index is stored and queried. It is also where you
  tell Solr _where_ to store the index on disk. By default that is:
  
	<dataDir>${solr.solr.cores}/parallel-flickr/data</dataDir>  

To do:
--

* A pretty fierce stop word list for indexing

See also:
--

* [Solr](https://lucene.apache.org/solr/)
