parallel-flickr
==

parallel-flickr is a tool for backing up your Flickr photos and generating a database backed website that honours the viewing permissions you've chosen on Flickr. parallel-flickr is **not** a replacement for Flickr.

parallel-flickr is still a work in progress. It ain't pretty or classy yet but it works. It still needs to be documented properly.

To do:
--

* write files to S3 (see also: [flamework-aws](https://github.com/straup/flamework-aws))

* make sure video files are actually being fetched properly

* API hooks (see also: [flamework-api](https://github.com/straup/flamework-api))

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
