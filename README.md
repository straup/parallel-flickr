parallel-flickr
==

parallel-flickr is a tool for backing up your Flickr photos and generating a database backed website that honours the viewing permissions you've chosen on Flickr. parallel-flickr is **not** a replacement for Flickr.

parallel-flickr is still a work in progress. It ain't pretty or classy yet but it works. It still needs to be documented properly.

In the meantime, [here's a blog post](http://www.aaronland.info/weblog/2011/10/14/pixelspace/#parallel-flickr).

Installing parallel-flickr
--

_These instructions are incomplete. They'll probably work but I have tested them from scratch yet. The following assumes you're running on a brand new vanilla Ubuntu machine._

* First, some basic OS-level setup:

	$> sudo apt-get install git-core

	$> git clone git@github.com:straup/parallel-flickr.git

	$> cd parallel-flickr

	$> sudo sh ./ubuntu/install.sh

	$> sudo chown -R www-data templaces_c

	TO DO: apache configs

	TO DO: database setup, see also: https://github.com/straup/flamework-tools/blob/master/bin/setup-db.sh

	TO DO: read in schema/*.schema

* Now set up the application config file:

	$> cp www/include/config.php.example www/include/config.php

	TO DO: updating the config file, see also: https://github.com/straup/flamework-tools/blob/master/bin/make-project.sh 

* That's it.

To do:
--

* write files to S3 (see also: [flamework-aws](https://github.com/straup/flamework-aws))

* make sure video files are actually being fetched properly

* API hooks (see also: [flamework-api](https://github.com/straup/flamework-api))

* sets, galleries, groups

* uploads (and then re-uploading to Flickr)

* account deletion

* sync/update contacts

* cron jobs for backups

* display metadata

* permission checks for geo (currently not displayed)

* search (solr?)

* duplicate key errors fetching faves

* better layout, tested in more than just Firefox

To note:
--

* password reminders are disabled, only because I don't have a mail server set up

See also:
--

* [flamework](https://github.com/straup/flamework)

* [flamework-flickrapp](https://github.com/straup/flamework-flickrapp)

* [flamework-api](https://github.com/straup/flamework-api)

* [flamework-invitecodes](https://github.com/straup/flamework-invitecodes)

* [flamework-tools](https://github.com/straup/flamework-tools)
