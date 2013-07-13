# parallel-flickr

* [Gentle Introduction](#gentle-introduction)

* [Installation](#installation)

  * [The Short Version](#the-short-version)

  * [The Long, Slightly Hand Holding, Version](#the-long-slightly-hand-holding-version)

  * [The Here-Be-Dragons Locally Hosted Version](#the-here-be-dragons-locally-hosted-version)

* [Permissions](#permissions)

  * [Flickr Auth tokens](#flickr-auth-tokens)
  
  * [Poor man's god auth](#poor-mans-god-auth)

* [Backing up photos](#backing-up-photos)

  * [Backing up photos manually](#backing-up-photos)
  
  * [Automagic backing up of your photos](#automagic-backing-up-of-your-photos-using-the-flickr-push-feeds)

  * [Controlling who can backup their photos](#controlling-who-can-backup-their-photos)
  
* [Storage options](#storage-options)

  * [Using the local file system](#using-the-local-file-system-for-storing-photos-and-metadata-files)

  * [Using Amazon's S3 service](#using-amazons-s3-service-for-storing-photos-and-metadata-files)
  
  * [Using the "storagemaster" service](#using-the-storagemaster-service-for-storing-photos-and-metadata-files)

* [Fancy stuff](#fancy-stuff)
  
  * [Solr](#solr)
  
  * [Uploads](#uploads)
  
    * [Upload - notifications](#upload---notifications)
	
    * [Upload - send to Flickr](#upload---send-to-flickr)
	
    * [Upload - send to Parallel-Flickr](#upload---send-to-parallel-flickr)
	
    * [Upload by the API](#upload-by-the-api)
	  
    * [Upload by email](#upload-by-email)
  
  * [Filt(e)ring uploads](#filtering-uploads)
  
  * [API](#api)
  
* [Experimental stuff](#experimental-stuff)

* [TO DO](#to-do)

## Gentle Introduction

parallel-flickr is a tool for backing up your Flickr photos and generating a
database backed website that honours the viewing permissions you've chosen on
Flickr. It uses the [Flickr API](http://www.flickr.com/services/api) as a
single-sign-on provider, for user accounts, and to archive the photos,
favourites and contact list from your Flickr account.

parallel-flickr is **not** a replacement for Flickr. It is an effort to
investigate – in working code – what it means to create an archive of a service
as a living, breathing "shadow" copy rather than a snapshot frozen in time.
 
parallel-flickr is still a work in progress. It is more than an idle research
project. It is working code that I use every day. On the other hand it is also
not a full-time gig so I work on it during the mornings and the margins of the
day so it is not pretty or particularly classy, yet.

_It almost certainly still contains bugs, some of them might be a bit
stupid. That includes this documentation which aims to be basically completely
but is not yet completely complete._

There is still not a one-button installation process and configuring parallel-flickr
might seem a bit daunting for that reason. This is far from ideal and there is
lots of room for improvement in the future. Suggestions and gentle cluebats are
welcome and encouraged.

The longer versions are contained in a variety of blog posts and talks that I've
done about parallel-flickr:

* [Parallel Flickr](http://www.aaronland.info/weblog/2011/10/14/pixelspace/#parallel-flickr),
  October 2011
  
* [A talk about Parallel Flickr at the Personal Digital Archiving conference](http://www.aaronland.info/weblog/2012/02/14/incentivize/#pda2012),
  February 2012 

## Installation

### The Short Version

parallel-flickr is built on top of [Flamework](https://github.com/exflickr/flamework) which means it's nothing more than a vanilla Apache + PHP + MySQL application. You can run it as a dedicated virtual host or as a subdirectory of an existing host.

You will need to make a copy of the [config.php.example](https://github.com/straup/parallel-flickr/blob/master/www/include/config.php.example) file and name it `config.php`. You will need to update this new file and add the various specifics for databases and third-party APIs.

	# You will need valid Flickr API credentials
	# See also: http://www.flickr.com/services/apps/create/apply/

	$GLOBALS['cfg']['flickr_api_key'] = '';
	$GLOBALS['cfg']['flickr_api_secret'] = '';	

	$GLOBALS['cfg']['flickr_api_perms'] = 'read';

	# You will need to setup a MySQL database and plug in the specifics
	# here: https://github.com/straup/parallel-flickr/blob/master/schema
	# See also: https://github.com/straup/flamework-tools/blob/master/bin/setup-db.sh

	$GLOBALS['cfg']['db_main'] = array(
		'host' => 'localhost',
		'name' => 'parallel-flickr',
		'user' => 'parallel-flickr',
		'pass' => '',
		'auto_connect' => 1,
	);

	# You will need to set up secrets for the various parts of the site
	# that need to be encrypted. Don't leave these empty. Really.
	# You can create new secrets by typing `make secret`.
	# See also: https://github.com/straup/parallel-flickr/blob/master/bin/generate_secret.php

	$GLOBALS['cfg']['crypto_cookie_secret'] = '';
	$GLOBALS['cfg']['crypto_crumb_secret'] = '';
	$GLOBALS['cfg']['crypto_password_secret'] = '';

	# If you don't have memcache installed (or don't even know what that means)
	# just leave this blank. Otherwise change the 'cache_remote_engine' to
	# 'memcache'.

	$GLOBALS['cfg']['cache_remote_engine'] = '';
	$GLOBALS['cfg']['memcache_host'] = 'localhost';
	$GLOBALS['cfg']['memcache_port'] = '11211';

	# This is only relevant if are running parallel-flickr on a machine where you
	# can not make the www/templates_c folder writeable by the web server. If that's
	# the case set this to 0 but understand that you'll need to pre-compile all
	# of your templates before they can be used by the site.
	# See also: https://github.com/straup/parallel-flickr/blob/master/bin/compile-templates.php

	$GLOBALS['cfg']['smarty_compile'] = 1;

### The Long, Slightly Hand Holding, Version

Get the [code from GitHub](https://github.com/straup/parallel-flickr).

Decide on whether you'll host this on a sub-domain (something along the lines of `parallel-flickr.example.com`) or in a subdirectory (maybe something like `www.example.com/parallel-flickr`).

This rest of this section will assume the following:

* That you'll be hosting on a subdomain called *parallel-flickr* on a domain called *example.com*, or, to put it another way `parallel-flickr.example.com`. Just mentally substitute your domain and sub-domain when reading, and physically substitute your domain and sub-domain during the installation process. Unless you actually own the example.com.
* That you'll be using Flickr for reverse-geocoding and not an instance of the `reverse-geoplanet` web-service.
* That you want the URL for parallel-flickr to be `parallel-flickr.example.com` and not `parallel-flickr.example.com/www`
* That you want parallel-flickr to be on a public facing web service. You *can* install it on a local machine that isn't publicly accessible but to do this needs some careful copying-and-pasting of database settings from a public facing machine to your local, private machine. See the *Here-Be-Dragons Locally Hosted Version Below* if you want to get your hands dirty.
* That `<root>` is the path on your webserver where your web server has been configured to find the sub-domain.
* That you have shell access (probably via SSH) to your web server.

Register with Flickr - go to http://www.flickr.com/services/apps/create/apply/

* Apply for a non-commercial key
* Set the *App name* to `parallel-flickr` (or something that's meaningful to you)
* Set the *App description* to something meaningful, such as *An instance of https://github.com/straup/parallel-flickr*
* Tick both boxes!
* Note the *API key* and *API secret* the registration process gives you (it's a good idea to save this in a new browser window or tab so you can copy-and-paste)

Now ... upload the code, plus all sub-directories to your web-server; don't forget the (hidden) `.htaccess` file in the root of the code's distribution.

Copy `<root>/www/include/config.php.example` to `<root>/www/include/config.php` and edit this new file.

Copy-and-paste your Flickr Key into the section of the config file that looks like ...

	$GLOBALS['cfg']['flickr_api_key'] = 'my-flickr-key-copied-in-here';
	$GLOBALS['cfg']['flickr_api_secret'] = 'my-flickr-secret-copied-in-here';	

	$GLOBALS['cfg']['flickr_api_perms'] = 'read';
	
Set up your database name, database user and database password. Copy and paste these into ...

	$GLOBALS['cfg']['db_main'] = array(
		'host' => 'localhost',
		'name' => 'my-database-name',
		'user' => 'my-database-user',
		'pass' => 'my-database-users-password',
		'auto_connect' => 0,
	);

Setup your encryption secrets secrets. SSH to your host and run `php <root>/bin/generate_secret.php`, 3 times. Copy and paste each secret into 

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'first-secret-here';
	$GLOBALS['cfg']['crypto_crumb_secret'] = 'second-secret-here';
	$GLOBALS['cfg']['crypto_password_secret'] = 'third-secret-here';

(If you don't have shell access to your web-server, you can run this command from the shell on a local machine)

Create the database tables. Load `<root>/schema/db_main.schema`, `<root>/schema/db_tickets.schema` and `<root>/schema/db_users.schema` into the database. You can do this either via phpMyAdmin and the import option or via `mysql` on the shell's command line

Browse to http://parallel-flickr.example.com

If you get errors in your Apache error log such as ...

	www/.htaccess: Invalid command 'php_value', perhaps misspelled or defined by a module not included in the server configuration

... then your host is probably running PHP as a CGI and not as a module so you'll want to comment out any line in `<root>/www/.htaccess` that starts with `php_value` or `php_flag` and put these values into a new file, `<root>/www/php.ini`, without the leading `php_value` or `php_flag`.

Click on *sign in w/ flickr* and authenticate with Flickr.

You might want to put this command in a cron job, if your web host allows this.

That's it. Or should be. If I've forgotten something please let me know or submit a pull request.

### The Here-Be-Dragons Locally Hosted Version

If you really want to hack and play around with parallel-flickr, it's best to do
this on a private, locally hosted machine, like your laptop or your desktop
machine. But as a starting point you need to have followed the installation
instructions above as you *need* to have a public facing installation first and
then clone this to your local machine. The reason for this is that you need to
authenticate with Flickr, Flickr uses FlickrAuth or OAuth to authenticate and OAuth authentication needs a publicly accessible web server to authenticate *with*. With that said, roll up your sleeves, grab a cup of your caffeinated beverage of choice and follow along.

This rest of this section will assume the following:

* That you're running [MAMP](http://mamp.info/en/index.html) on a Mac. MAMP is a nice convenient way to run MySQL, Apache and PHP on a Mac. There's also a Windows version called [WAMP](http://www.wampserver.com/en/). Or most Linux distros come with all of this installed. YMMV so you may need to change some paths and file names.
* That you'll set up a local host name called `parallel-flickr`
* That your MAMP installation is running Apache on port 8888 and MySQL on port 8889.

So ... firstly create a local host name by adding `parallel-flickr` to your `/etc/hosts` file, which will look something like this ...

	127.0.0.1	localhost parallel-flickr

On some operating systems, this file is re-read each time your browser is re-started. On Mac OS X, you'll also need to flush and reload the machine's DNS cache ...

	$ sudo /usr/bin/dscacheutil -flushcache

Now create a new virtual host on your machine. Edit `/Applications/MAMP/conf/apache/httpd-vhosts.conf` and append the magic incantation ...

	<VirtualHost *:8888>
		ServerName parallel-flickr:8888
		DocumentRoot "/Applications/MAMP/htdocs/parallel-flickr/www"
	</VirtualHost>

Restart Apache in MAMP. Create `/Applications/MAMP/htdocs/parallel-flickr/www`. Browse to `http://parallel-flickr:8888`. Check you get an empty directory listing of / to ensure your virtual host is configured and working correctly.

Now download your working, public, parallel-flickr install (to ensure any customisations, including configuration, you've made are preserved) from your public facing webserver.

Export your parallel-flickr database from your public facing installation, either via phpMyAdmin / export or via the `mysqldump command` from the shell's command line.

Create a new local database to hold the, err, data.

Import your parallel-flickr database to your local installation, either via phpMyAdmin / import or via the `mysqldump` command from the shell's command line.

Edit your local copy of parallel-flickr's configuration file at `/Applications/MAMP/htdocs/parallel-flickr/www/include/config.php` to point to your new local database.

	$GLOBALS['cfg']['db_main'] = array(
		'host' => 'localhost',
		'name' => 'parallel-flickr',
		'user' => 'root',
		'pass' => 'root',
		'auto_connect' => 0,
		);

Still in the local parallel-flickr configuration file, set the `environment` config value to be `localhost`:

	$GLOBALS['cfg']['environment'] = 'localhost';

Now browse back to `http://parallel-flickr:8888`. You should be asked to *sign in w/ flickr*. Don't. You'll be redirected to the Flickr site to authenticate and this will fail as your local install isn't publicly accessible.

## Permissions

### Flickr Auth tokens

Aside from annoying-ness of Unix user permissions required to manage your
storage options depending on how you've set up parallel-flickr you may need
ensure that you have a Flickr API auth token with suitable permissions.

By default, parallel-flickr requests a Flickr API auth token with nothing more
than `read` permissions. If all you're doing is archiving your Flickr account
then you shouldn't need permission to any of the "write" methods in the Flickr
API.

That said there are parts of the site that allow you to update your photos on
Flickr . That might means favouriting a photo, updating geo data and so on. At
the moment most of these features are disabled but they do exist.

Tokens are upgraded (or downgraded) by sending users to a special account page
which will take care of sending them to Flickr and updating their account. That
page is:

	http://parallel-flickr.example.com/account/flickr/auth?perms=PERMISSIONS

In order to use this functionality you must also ensure that the
`enable_feature_flickr_api_change_perms` feature flag is enabled.

	$GLOBALS['cfg']['enable_feature_flickr_api_change_perms'] = 0;

### Poor Man's God Auth

_Note: The specifics of this implementation may change over the course of the
next few months but the basic approach will remain the same (20130630/straup)_

Poor man's god auth is nothing more than a series of checks to restrict access
to certain parts of the website to a list of logged in users and the "roles"
they have assigned. It works but it would be a mistake to consider it "secure". 

To enable poor man's god auth you will need to add the following settings to
your config file.

	$GLOBALS['cfg']['auth_enable_poormans_god_auth'] = 1;

	/*
	poormans god auth is keyed off a user's (parallel-flickr)
	user ID, that is the primary key in the `db_main:Users`
	table
	*/

	$GLOBALS['cfg']['auth_poormans_god_auth'] = array(
	
		100 => array(
			'roles' => array( 'admin' ),
		),
	);

Currently the only role that parallel-flickr uses is "admin".

## Backing up photos

### Backing up photos manually

By default backing up your photos is done using a number of
`backup_SOMETHING.php` scripts located in the [bin](./bin) directory and run
manually from the command line.

The scripts are:

* **backup_contacts.php** – fetch and store the list of people (and relationship
    type) that you've made a contact on Flickr. This list is used to determine
    whether another logged-in user (visiting your instance parallel-flickr) can
    view photos that aren't already public.

* **backup_photos.php** – fetch and store your photos. In addition to
    downloading the original photo, this will cache some of the smaller versions
    created by Flickr as well as the contents of the corresponding
    `flickr.photos.getInfo` API method.

* **backup_faves.php** – fetch and store photos you've faved. Like the
    `backup_photos.php` script this will grab smaller versions (as well as the
    original, Flickr permissions permitting) and metadata.

It is helpful to set these various bin/backup_* scripts to run via cron. According to your level of faving, uploading, and contacts fiddling, you may have your own requirements for often you want to run the various backup scripts.

Here's a once-a-day example, which works for a moderate level of activity:

    0 3 * * * php /full/path/to/parallel-flickr/bin/backup_contacts.php
    15 3 * * * php /full/path/to/parallel-flickr/bin/backup_faves.php
    30 3 * * * php /full/path/to/parallel-flickr/bin/backup_photos.php

### Automagic backing up of your photos (using the Flickr PuSH feeds)

parallel-flickr can also be configured to archive the photos for registered users
using the [real-time photo update PuSH feeds](http://code.flickr.com/blog/2011/06/30/dont-be-so-pushy/)
from Flickr.

By default this functionality is disabled  default because in order to use it
you need to ensure that the directory specified in the
`$GLOBALS['cfg']['flickrstatic_path']` config variable is writeable by the web
server _or_ you need to run "storagemaster" storage provider, described below.

_Another way to deal with the probem of permissions is just to use Amazon's S3 service to store your photos, described below._

To enable the PuSH features you'll need to update the following settings in your
config file: 

	$GLOBALS['cfg']['enable_feature_flickr_push'] = 1;
	$GLOBALS['cfg']['enable_feature_flickr_push_backups'] = 1;	
	$GLOBALS['cfg']['flickr_push_enable_registrations'] = 1;
	
The easiest way to enable PuSH backups is to go to the Flickr backups account
page (on your version of parallel-flickr). That is:

	http://parallel-flickr.example.com/account/flickr/backups/

If you've never setup backups before and the `flickr_push` configs described
above have been enabled then PuSH backups will be enabled at the same time that
backups (for your photos, your faves, etc.) are registered.

Not all backup types are valid PuSH backup types (like your contact list, for
example).

If you have enabled [poor man's god auth](#poor-mans-god-auth) then there is
also a "god" page that will list all the PuSH subscription registered for user
backups and other features at this URL:  

	http://parallel-flickr.example.com/god/push/subscriptions/

From here you can create or delete individual PuSH feeds, although the tools are
still feature incomplete. Specifically, it is not yet possible to register new
feeds with arguments (like a tag or a user ID).

Additionally, subscriptions to specific PuSH feeds enables additional
functionality in parallel-flickr. These subscriptions can be configured using
the following flags:

* **flickr_push_enable_photos_friends** – display recent uploads by your
    contacts in a
    [flickr for busy people](http://flickrforbusypeople.appspot.com) -like
    interface. It's not very good but it works. For example:

	http://parallel-flickr.example.com/photos/friends/
	
	$GLOBALS['cfg']['flickr_push_enable_photos_friends'] = 1;

* **flickr_push_enable_recent_activity** – display photos from your contacts
    with some kind of recent activity (tagged, geotagged, commented on, etc.)
    This is a purely experimental interface so all the usual caveats apply. For
    example:

	http://parallel-flickr.example.com/photos/friends/activity/
	
	$GLOBALS['cfg']['flickr_push_enable_recent_activity'] = 1;
	
If you want to be notified by Flickr when a PuSH notification fails or is
deleted make sure you assign the following configuration varable:

	$GLOBALS['cfg']['flickr_push_notification_email'] = 'you@example.com';

### Controlling who can backup their photos

Controlling who can backup their photos using your copy of parallel-flickr is
controlled by three configuration variables. The first is self-explanatory in
that it dictates whether backups are enabled at all:

	$GLOBALS['cfg']['enable_feature_backups'] = 1;

The next two variable decided whether people can register to have their photos
backed up and whether they can do so without an invitation code:

	$GLOBALS['cfg']['enable_feature_backups_registration'] = 1;
	$GLOBALS['cfg']['enable_feature_backups_registration_uninvited'] = 0;

If you want to require invitation codes you'll need to make sure you generate an
encryption secret for the invite cookie. This is done by running `php
<root>/bin/generate_secret.php` like you did during the initial setup process.

	$GLOBALS['cfg']['enable_feature_invite_codes'] = 1;
	$GLOBALS['cfg']['crypto_invite_secret'] = 'invite-secret-here';

If you have enabled [poor man's god auth](#poor-mans-god-auth) then there is
also a "god" page that will list all the past and pending invites this URL:  

	http://parallel-flickr.example.com/god/invites/
	
## Storage options

Internally parallel-flickr uses an abstraction layer for storing (and
retrieving) files. This allows for a variety of storage "providers" to be used
with parallel-flickr. As of this writing they are:

* **fs** - files are read from and written to the local file system. This is the default provider.

* **s3** - files are read from and written to the Amazon Web Service (AWS) S3 service. You will need
	to include your AWS credentials in the `config.php` file in order for this
	provider to work.
	
* **storagemaster** - files are read from and written to a "storagemaster" daemon
    which is configured to run on a high-numbered port on the same machine as
    parallel-flickr itself. By default files are read from and written to the
    local file system with the important distinction that the storagemaster
    daemon itself is running as the `www-data` user (or whatever user account
    the Apache web server is using). Storagemaster details are discussed below.

### Using the local file system for storing photos (and metadata files)

For example:

	$GLOBALS['cfg']['storage_provider'] = 'fs';

	$GLOBALS['cfg']['flickr_static_path'] = "/path/to/parallel-flickr-files/";
	$GLOBALS['cfg']['flickr_static_url'] = "static/";

Unless you are only ever going to backup your Flickr data manually using the
command line tools you will probably need to ensure that the
`/path/to/parallel-flickr-files/` directory can be written to by the same user
account that runs Apache (typically `www-data`) web server.

For example if you enable PuSH backups (discussed above) or uploads (discussed
below) it won't be _you_ trying to save your photos to disk but rather the web
server.

For this reason, if you are running a system where both the command line tools
are being run (say from a cron job) and other automated systems are backing up
your data you want to consider using the `s3` or `storagemaster` providers.

### Using Amazon's S3 service for storing photos (and metadata files)

For example:

	$GLOBALS['cfg']['storage_provider'] = 's3';
	
	$GLOBALS['cfg']['amazon_s3_access_key'] = 'YER_AWS_ACCESS_KEY';
	$GLOBALS['cfg']['amazon_s3_secret_key'] = 'YER_AWS_SECRET_KEY';
	$GLOBALS['cfg']['amazon_s3_bucket_name'] = 'A_NAME_LIKE_MY_FLICKR_PHOTOS';

parallel-flickr is able to store your photos and metadata files using Amazon's
S3 storage service.

Setting up an Amazon account and getting an Amazon Web Services (AWS) API key
and secret are out of scope for this document (there are lots of good howtos on
Amazon's own site and the Internet at large) but once you do it's easily to
configure parallel-flickr to use S3.

### Using the "storagemaster" service for storing photos (and metadata files)

For example:

	$GLOBALS['cfg']['storage_provider'] = 'storagemaster';
	
	$GLOBALS['cfg']['storage_storagemaster_host'] = '127.0.0.1';
	$GLOBALS['cfg']['storage_storagemaster_port'] = '9999';

	$GLOBALS['cfg']['flickr_static_path'] = "/path/to/parallel-flickr-files/";
	$GLOBALS['cfg']['flickr_static_url'] = "static/";

Storagemaster is a very simple socket-based daemon meant to run on a
high-numbered port on the same machine as parallel-flickr itself. That's not a
requirement but the important thing to remember is that it is **not** meant to
generally accessible on the public internet because it has no security controls
of its own.

Storagemaster exposes a deliberately simple interface for manipulating files:
EXISTS, GET, PUT and DELETE.

By default files are read from and written to the local file system with the
important distinction that the storagemaster daemon itself is running
[setuid](https://en.wikipedia.org/wiki/Setuid) as the `www-data` user (or
whatever user account the Apache web server is using). 

The storagemaster daemon is written in Python and located in the
[storagemaster](./storagemaster) directory which is included with
parallel-flickr. It can be run from the command line as follows:

	$> python storagemaster.py --root /path/to/parallel-flickr-static

It can also be configured to run automatically (and in background-mode) using
the Unix `init.d` system. An example init.d file is included which you will need
to configure yourself. As follows:

	$> cd /path/to/parallel-flickr/storagemaster/init.d/storagemaster.sh.example	
	$> cp storagemaster.sh.example storagemaster.sh
	
Now edit the `storagemaster.sh` file to point to the correct root directory and
any other details specific to your setup. Once you're done you'll need to copy
the file in to the `/etc/init.d` folder and register it with the operating
system:

	$> sudo ln -s /path/to/parallel-flickr/storagemaster/init.d/storagemaster.sh /etc/init.d/
	$> sudo update-rc.d storagemaster.sh defaults
	$> sudo /etc/init.d/storagemaster.sh start

If you want or need to run the storagemaster in debug mode you can do this
instead:

	$> sudo /etc/init.d/storagemaster.sh debug	

## Fancy stuff

### Solr

parallel-flickr is designed to run using nothing more complicated than a
standard LAMP stack. However if you are running parallel-flickr on a machine
you control or one that allows you to install (long-running) Java applications
there are a bunch of bonus features that you can take advantage of by enabling
support for Solr.

Setting up and configuring Solr is outside the scope of this document but
everything you need is in the [solr](./solr) directory. In your config file you
will need make sure the following configuration variables are set:
	
	$GLOBALS['cfg']['enable_feature_solr'] = 1;
	$GLOBALS['cfg']['solr_endpoint'] = 'http://localhost:YOUR-SOLR-PORT/solr/parallel-flickr/';

By default, a number of features in parallel-flickr become available on the site
once Solr is available. They are:

* **places** – a gazetteer style interface for all the places you've geotagged photos.

	http://parallel-flickr.example.com/photos/me/places/
	
	$GLOBALS['cfg']['enable_feature_places'] = 1;

* **cameras** – a gazetteer style interface for all the cameras you've taken
    photos with.

	http://parallel-flickr.example.com/photos/me/cameras/
		
	$GLOBALS['cfg']['enable_feature_cameras'] = 1;

* **archives** – a very (very) rudimentary interface for viewing your photos by
    the dates (bucketed by day, month and year)  they were taken or uploaded.

	http://parallel-flickr.example.com/photos/me/archives/
	
	$GLOBALS['cfg']['enable_feature_archives'] = 1;

### Uploads
	
If enabled parallel-flickr can be made to upload photos directly to Flickr
either from the website itself or using an upload by email handler, discussed
below, by enabling the following configuration variable:

	$GLOBALS['cfg']['enable_feature_uploads'] = 1;	
		
	$GLOBALS['cfg']['uploads_default_send_to'] = '';
	$GLOBALS['cfg']['uploads_default_notifications'] = '';
	$GLOBALS['cfg']['uploads_default_tags'] = 'uploaded:by=parallel-flickr';

#### Upload - notifications

Feature flags to control global configurations for upload notifications (to
third party services).

	$GLOBALS['cfg']['enable_feature_uploads_flickr_notifications'] = 0;

### Upload - send to Flickr

Allow photos uploads to be sent diretly to Flickr and optionally pre-archive
them in parallel-flickr as they are uploaded - you will need to configure your
storage provider accordingly in order to be able to write photos to disk (see below) 

	$GLOBALS['cfg']['enable_feature_uploads_flickr'] = 0;
	$GLOBALS['cfg']['enable_feature_uploads_flickr_archive'] = 0;

#### Upload - send to Parallel-Flickr

Allow photos uploads to be imported directly in to parallel-flickr only
optionally sending them on to Flickr.

You will absolutely need to configure your storage provider (see below) to allow
you to write files to disk. You will also need to ensure that you users have a
Flickr auth token with 'delete' permissions in order to do the dbtickets_flickr
trick – careful readers will also note that this means ensuring that the
_dbtickets_flickr feature flag be enabled. 

TO DO: user interface glue and prompts for ensuring that a user has a valid 'delete' auth token from Flickr

	$GLOBALS['cfg']['enable_feature_uploads_parallel_flickr'] = 0;

#### Upload by the API

This flag allows you to control whether or not uploads can be done using the web
and/or the API. Because the website just uses the API for uploads you could, for
example, disable uploads from the web but continue to allow other clients to
upload stuff. Why? I don't know and don't need to. But you can. Note these flags
do not affect upload by email settings (below).

	$GLOBALS['cfg']['enable_feature_uploads_by_web'] = 1;	
	$GLOBALS['cfg']['enable_feature_uploads_by_api'] = 1;

(Normally) photos can be uploaded from website on both the desktop and mobile
phones that have camera support in HTML forms (mobile Safari, for example) from
the following URL:

	http://parallel-flickr.example.com/photos/upload/

#### Upload by email 

	# 1048576 is 1MB; web based upload sizes are controled by the
	# 'upload_max_filesize' directive in www/.htaccess

	$GLOBALS['cfg']['enable_feature_uploads_by_email'] = 1;
	$GLOBALS['cfg']['uploads_by_email_hostname'] = 'upload.parallel-flickr.example.com';
	$GLOBALS['cfg']['uploads_by_email_maxbytes'] = 1048576 * 5;	

Upload by email is handled by the
[bin/upload_by_email.php](bin/upload_by_email.php) script which accepts a single
email message as its input. The email message is parsed for a magic email address
matching a registered user and one or more images.

Magic email addresses are generated automatically and viewable here:

	http://parallel-flickr.example.com/account/uploadbyemail

Permissions and other photo properties are assigned by using a short-hand
notation in the email message's Subject: header. The short-hand is:

* **p:**PERMISSIONS – assign the viewing permissions for this photo. Valid
    options are: **p**-ublic; **pr**-ivate; **fr**-iend; **fa**-mily; **ff** for
    friends and family. Defaults to private.

* **g:**GEO-PERMISSIONS – assign the viewing permissions for this photo. Valid
    options are: **p**-ublic-; **pr**-ivate; **c**-ontact; **fr**-iend; **fa**-mily;
    **ff** for friends and family. Defaults to private.

* **f:**FILTR – apply a `filtr` filter to the upload. Filters are
    discussed below. The list of valid filters is determined using the
    `filtr_valid_filtrs` configuration value. Defaults to none.  

* **s:**SEND-TO – where to upload your photo to first. Valid options are **fl**-ickr only, assuming that some other
    part of your parallel-flickr installation will achive the photo; or **pf** to upload the
    photo _only_ to parallel-flickr. A default value can be set using the
    `$GLOBALS['cfg']['uploads_default_send_to'] variable.

* **n:**NOTIFIY - A comma-separated list of services to notify when you've
    uploaded. Valid options area **fl**-lick which will be sent a highly
    stylized preview image with a link back to the original photo on
    parallel-flickr. Support for Twitter is on the TO DO list.

For example:

	Subject: p:ff g:ff n:fl This is the rest of the subject (and will be used as the photo title)
	From: Aaron Straup Cope <aaron@example.com>
	Date: Sat, 25 May 2013 16:07:06 -0400
	To: Aaron Straup Cope <Wl6m3DSdtj3VougEtoDm.woTaY1y44Bp0@upload.example.com>

As of this writing it is not possible to assign tags or when uploading by
email. This is not a feature. It just hasn't happened yet.

The upload by email script is invoked using a Postfix alias that routes all
email send to a defined host (for example:
_anything_@upload.example.com). Setting up and configuring Postfix is outside
the scope of this document but there are notes and sample configuration files in
the [postfix](./postfix] directory included with parallel-flickr.

If you enable uploads by email and are using Postfix to deliver mail you _must_
also use the "storagemaster" storage provider (discussed above). Specifically,
the documentation for Postfix says: 

"For security reasons, deliveries to command and file destinations are performed
with the rights of the alias database owner."

Which means that the upload by email PHP handler will always be run as the
`nobody` user account. Giving the `nobody` user account write permissions on
your static photos directory is pretty much a terrible idea and granting the
nobody user account extra powers (using something like the `/etc/sudoers` file
is even worse.

So, storagemaster.

### Filt(e)ring uploads

Photos can be filtered before being uploaded using the [filtr](https://github.com/straup/filtr) program which
is included with the standard parallel-flickr distribution. Set up and
configuration of filtr itself is outside the scope of this document at the
moment but the list of stuff required is
[over here](https://github.com/straup/filtr#dependencies). 

Filtering can be enabled by setting the following configuration variable:

	$GLOBALS['cfg']['enable_feature_uploads_filtr'] = 1;

You can control the list of available filters with the following configuration
variable:

	$GLOBALS['cfg']['filtr_valid_filtrs'] = array(
		'dazd',
		'dthr',
		'postr',
		'pxl',
		'pxldthr',
		'rockstr',
	);

### API

parallel-flickr has always had an API that was only intended for "internal" use
since it relies on a user's cookies for doing authentication.

More recently the work done on the [flamework-api](https://github.com/cooperhewitt/flamework-api/) libraries to support a
public OAuth2 API has been added to the codebase.

It still needs to be properly documented here but all the code is part of
parallel-flickr and available for playing with if you're so inclined.

## Experimental stuff

### Uploading to parallel-flickr (but not necessarily Flickr)

What? Yes. You can now upload photos directly in to parallel-flickr but not
(necessarily) Flickr which is kinds of turns everything on its head. As such
this should still be considered an experimental. It works but all the
implications are still being teased out so buyer beware and all that.

This is how it works:

* You upload a photo to parallel-flickr

* parallel-flickr receives the photo and then stores it in a safe place _before_
  importing it in to its database

* parallel-flickr uploads a small place-holder image to your Flickr account that
  is not visible to anyone and makes a note of the photo ID
  
* parallel-flickr then turns around and deletes the photo on Flickr and assigns
  the photo ID (of the deleted photo) to the photo you first uploaded

* parallel-flickr will, unless told otherwise, generate a small preview image of
  the photo and upload it to Flickr with a link back to the actual photo on
  parallel-flickr

So, yes, we've entered a bit a of a parallel mirror-world. At this stage
parallel-flickr is no longer a perfect of replica of Flickr itself. If you try
to view the photo ID of photo you've uploaded on Flickr it won't be there
because that photo has already been deleted. 

But it also means that photo IDs in Flickr and one or more instances of
parallel-flickr remain unique and two or more instances of parallel-flickr could
be merged without having to worry about ID conflicts.

The full set of feature flags and configuration variables is still being worked
out. Aside from the usual config flags associated with photo uploaded you'll
need to ensure that the following configuration variables are set:

        $GLOBALS['cfg']['enable_feature_dbtickets_flickr'] = 1;

Also, you'll need to make sure that you have a Flickr API auth token with
`delete` permissions. All of this needs to be automated or equivalent.

## TO DO:

In no particular order (patches are welcome):

* Make sure video files are actually being fetched properly

* Dets, galleries, groups

* People tagging (http://www.flickr.com/services/api/flickr.photos.people.getList.html)

* Dates and timezones... sad face

* Photo deletion

* Account deletion

* Context-specific URLs (e.g. in-faves or in-WOEID)

* Search – tags, titles, description, etc.

* Better layout, tested in more than just Firefox

* Send to Internet Archive (http://www.archive.org/help/abouts3.txt)

See also: [TODO.txt](https://github.com/straup/parallel-flickr/blob/master/TODO.txt)

## Notes

### A note about (Github) branches:

If you look carefully you may see that there are a lot branches for
parallel-flickr in my Github repository. These are there purely (and only) for
my working purposes.

You're welcome to poke at them obviously but the rule of thumb is: If it's in
"master" then it should work, modulo any outstanding bugs. If it's in any other
branch then all the usual caveats apply, your mileage may vary and we offer no
guarantees or refunds.

### Shout-outs

Most of the documentation for doing a basic installation of parallel-flickr is a
straight-up clone of @vicchi 's
[documentation for privatesquare](https://github.com/straup/privatesquare/blob/master/README.md).

## See also:

* [flamework](https://github.com/straup/flamework)

* [flamework-flickrapp](https://github.com/straup/flamework-flickrapp)

* [flamework-api](https://github.com/straup/flamework-api)

* [flamework-invitecodes](https://github.com/straup/flamework-invitecodes)

* [flamework-tools](https://github.com/straup/flamework-tools)
