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

Using Amazon's S3 service for storing photos (and metadata files)
--

parallel-flickr is able to store your photos and metadata files using Amazon's
S3 storage service.

Setting up an Amazon account and getting an Amazon Web Services (AWS) API key
and secret are out of scope for this document (there are lots of good howtos on
Amazon's own site and the Internet at large) but once you do it's easily to
configure parallel-flickr to use S3. Specifically, you just need to add the
following settings to `config.php` file:

	$GLOBALS['cfg']['enable_feature_storage_s3'] = 1;

	$GLOBALS['cfg']['amazon_s3_access_key'] = 'YER_AWS_ACCESS_KEY';
	$GLOBALS['cfg']['amazon_s3_secret_key'] = 'YER_AWS_SECRET_KEY';
	$GLOBALS['cfg']['amazon_s3_bucket_name'] = 'A_NAME_LIKE_MY_FLICKR_PHOTOS';

Automagic backing up of your photos (using the Flickr PuSH feeds)
--

parallel-flickr can also be configured to archive the photos for registered users
using the [real-time photo update PuSH feeds](http://code.flickr.com/blog/2011/06/30/dont-be-so-pushy/)
from Flickr.

By default this functionality is disabled  default because in order to use it
you need to ensure that the directory specified in the
$GLOBALS['cfg']['flickrstatic_path'] config variable is writeable by the web
server. _Another way to deal with the probem of permissions is just to use Amazon's S3
service to store your photos, described above._

To enable the PuSH features you'll need to update the following settings in your
config file: 

	$GLOBALS['cfg']['enable_feature_flickr_push'] = 1;
	$GLOBALS['cfg']['enable_feature_flickr_push_backups'] = 1;	

The easiest way to enable PuSH backups is to go to the Flickr backups account
page (on your version of parallel-flickr). That is:

	http://your-website.com/account/flickr/backups/

If you've never setup backups before and the `flickr_push` configs described
above have been enabled then PuSH backups will be enabled at the same time that
backups (for your photos, your faves, etc.) are registered.

Not all backup types are valid PuSH backup types (like your contact list, for
example).

If you have enabled "poor man's god auth"
[in the config file](https://github.com/straup/parallel-flickr/blob/master/www/include/config.php.example) 
 (described above) then there is also a "god" page that will list all the PuSH subscription
registered for user backups and other features at this URL:

	http://your-website.com/god/push/subscriptions/

From here you can create or delete individual PuSH feeds, although the tools are
still feature incomplete. Specifically, it is not yet possible to register new
feeds with arguments (like a tag or a user ID).

Poor Man's God Auth
--

Poor man's god auth is nothing more than a series of checks to restrict access
to certain parts of the website to a list of logged in users and the "roles"
they have assigned. It works but it would be a mistake to consider it "secure". 

To enable poor man's god auth you will need to add the following settings to
your config file.

	$GLOBALS['cfg']['auth_enable_poormans_god_auth'] = 1;

	$GLOBALS['cfg']['auth_poormans_god_auth'] = array(

		# poormans god auth is keyed off a user's (parallel-flickr)
		# user ID, that is the primary key in the `db_main:Users`
		# table

		100 => array(
		 	'roles' => array( 'admin' ),
		),
	);

Currently the only role that parallel-flickr uses is "admin".

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
