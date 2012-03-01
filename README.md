parallel-flickr 🐼
==
  
parallel-flickr is a tool for backing up your Flickr photos and generating a database backed website that honours the viewing permissions you've chosen on Flickr. parallel-flickr is **not** a replacement for Flickr.

parallel-flickr is still a work in progress. It ain't pretty or classy yet but it works. **It almost certainly still contains bugs, some of them really stupid.**

It still needs to be documented properly.

In the meantime, [here's a blog post](http://www.aaronland.info/weblog/2011/10/14/pixelspace/#parallel-flickr) and [some screenshots](http://www.flickr.com/photos/straup/tags/parallelflickr/).

Installing parallel-flickr
--

_These instructions are incomplete. They'll probably work but I have tested them from scratch yet. The following assumes you're running on a brand new vanilla Ubuntu machine._

* First, some basic OS-level setup:

	$> sudo apt-get install git-core

	$> git clone git://github.com/straup/parallel-flickr

	$> cd parallel-flickr

	$> sudo sh ./ubuntu/install.sh

	$> sudo chown -R www-data www/templates_c

	TO DO: apache configs
  see: apache/parallel-flickr.conf.example

  $> cd schema

  CHANGE flickr DB password in SETUP.md

  $> mysql -u root -p < SETUP.md

  $> cat *schema | mysql -u root -p flickr

* Now set up the application config file (grep for TWEAK ME):

	$> cp www/include/config.php.example www/include/config.php

	TO DO: updating the config file, see also: https://github.com/straup/flamework-tools/blob/master/bin/make-project.sh 

* That's it.

Backing up photos
--
After setting up everything above, and setting your API key callback to "http://YOURDOMAINNAME.com/auth/", visit /account/backups/. This will
create your backup user account and then from here you can run the various backup scripts inside of the bin/ directory. 

Keeping up to date
--
It is helpful to set these various bin/backup_* scripts to run via cron. According to your level of faving, uploading, and contacts fiddling, you may have your own requirements for often you want to run the various backup scripts.

Here's my a once-a-day example, which works for a moderate level of activity:

    0 3 * * * php /full/path/to/parallel-flickr/bin/backup_contacts.php
    15 3 * * * php /full/path/to/parallel-flickr/bin/backup_faves.php
    30 3 * * * php /full/path/to/parallel-flickr/bin/backup_photos.php

TO DO:
--

* make sure video files are actually being fetched properly

* sets, galleries, groups

* people tagging (http://www.flickr.com/services/api/flickr.photos.people.getList.html)

* uploads (and then re-uploading to Flickr

* dates and timezones... sad face

* photo deletion

* account deletion

* context-specific URLs (e.g. in-faves or in-WOEID)

* display metadata

* search

* better layout, tested in more than just Firefox

* send to Internet Archive (http://www.archive.org/help/abouts3.txt)

See also: [TODO.txt](https://github.com/straup/parallel-flickr/blob/master/TODO.txt)

To note:
--

* password reminders are disabled, only because I don't have a mail server set up

A note about (Github) branches:
--

If you look carefully you may see that there are a lot branches for
parallel-flickr in my Github repository. These are there purely (and only) for
my working purposes.

You're welcome to poke at them obviously but the rule of thumb is: If it's in
"master" then it should work, modulo any outstanding bugs. If it's in any other
branch then all the usual caveats apply, your mileage may vary and we offer no
guarantees or refunds.

See also:
--

* [flamework](https://github.com/straup/flamework)

* [flamework-flickrapp](https://github.com/straup/flamework-flickrapp)

* [flamework-api](https://github.com/straup/flamework-api)

* [flamework-invitecodes](https://github.com/straup/flamework-invitecodes)

* [flamework-tools](https://github.com/straup/flamework-tools)
