# parallel-flickr

_This is a straight-up clone of @vicchi 's documentation for privatesquare and
which still need to be updated to reflect the parallel-flickr-iness of
parallel-flickr (20130626/straup)_

## Gentle Introduction
privatesquare is a simple web application to record and manage a private database of [Foursquare](http://foursquare.com) check-ins.

It uses the Foursquare API as a single-sign-on provider, for user accounts, and to query for nearby locations (using your web browser's built-in geolocation support).

Check-ins can be sent on to Foursquare (and again re-broadcast to Twitter, etc, or to your followers or just *"off the grid"*) but the important part is: *They don't have to be.*

Meanwhile, [here's a blog post](http://nearfuturelaboratory.com/2012/01/22/privatesquare/).

## Installation - The Short Version

privatesquare is built on top of [Flamework](https://github.com/exflickr/flamework) which means it's nothing more than a vanilla Apache + PHP + MySQL application. You can run it as a dedicated virtual host or as a subdirectory of an existing host.

You will need to make a copy of the [config.php.example](https://github.com/straup/privatesquare/blob/master/www/include/config.php.example) file and name it `config.php`. You will need to update this new file and add the various specifics for databases and third-party APIs.

	# You will need valid foursquare OAuth credentials
	# See also: https://foursquare.com/oauth/register

	$GLOBALS['cfg']['foursquare_oauth_key'] = '';
	$GLOBALS['cfg']['foursquare_oauth_secret'] = '';
	
	# Don't change this. If you do update the main .htaccess
	# file accordingly.
	
	$GLOBALS['cfg']['foursquare_oauth_callback'] = 'auth/';

	# You will need a valid Flickr API key *or* access to a running
	# instance of the 'reverse-geoplanet' web service. By default
	# all the code that runs the reverse geocoder is included with
	# privatesquare (hence the requirement for an API key)
	# See also: https://github.com/straup/reverse-geoplanet
	# See also: http://www.flickr.com/services/apps/create/apply/

	$GLOBALS['cfg']['reverse_geoplanet_remote_endpoint'] = '';
	$GLOBALS['cfg']['flickr_api_key'] = '';

	# You will need to setup a MySQL database and plug in the specifics
	# here: https://github.com/straup/privatesquare/blob/master/schema
	# See also: https://github.com/straup/flamework-tools/blob/master/bin/setup-db.sh

	$GLOBALS['cfg']['db_main'] = array(
		'host' => 'localhost',
		'name' => 'privatesquare',
		'user' => 'privatesquare',
		'pass' => '',
		'auto_connect' => 1,
	);

	# You will need to set up secrets for the various parts of the site
	# that need to be encrypted. Don't leave these empty. Really.
	# You can create new secrets by typing `make secret`.
	# See also: https://github.com/straup/privatesquare/blob/master/bin/generate_secret.php

	$GLOBALS['cfg']['crypto_cookie_secret'] = '';
	$GLOBALS['cfg']['crypto_crumb_secret'] = '';
	$GLOBALS['cfg']['crypto_password_secret'] = '';

	# If you don't have memcache installed (or don't even know what that means)
	# just leave this blank. Otherwise change the 'cache_remote_engine' to
	# 'memcache'.

	$GLOBALS['cfg']['cache_remote_engine'] = '';
	$GLOBALS['cfg']['memcache_host'] = 'localhost';
	$GLOBALS['cfg']['memcache_port'] = '11211';

	# This is only relevant if are running privatesquare on a machine where you
	# can not make the www/templates_c folder writeable by the web server. If that's
	# the case set this to 0 but understand that you'll need to pre-compile all
	# of your templates before they can be used by the site.
	# See also: https://github.com/straup/privatesquare/blob/master/bin/compile-templates.php

	$GLOBALS['cfg']['smarty_compile'] = 1;

## Installation - The Long, Slightly Hand Holding, Version

Get the [code from GitHub](https://github.com/straup/privatesquare).

Decide on whether you'll host this on a sub-domain (something along the lines of `privatesquare.example.com`) or in a subdirectory (maybe something like `www.example.com/privatesquare`).

This rest of this section will assume the following:

* That you'll be hosting on a subdomain called *privatesquare* on a domain called *example.com*, or, to put it another way `privatesquare.example.com`. Just mentally substitute your domain and sub-domain when reading, and physically substitute your domain and sub-domain during the installation process. Unless you actually own the example.com.
* That you'll be using Flickr for reverse-geocoding and not an instance of the `reverse-geoplanet` web-service.
* That you want the URL for privatesquare to be `privatesquare.example.com` and not `privatesquare.example.com/www`
* That you want privatesquare to be on a public facing web service. You *can* install it on a local machine that isn't publicly accessible but to do this needs some careful copying-and-pasting of database settings from a public facing machine to your local, private machine. See the *Here-Be-Dragons Locally Hosted Version Below* if you want to get your hands dirty.
* That `<root>` is the path on your webserver where your web server has been configured to find the sub-domain.
* That you have shell access (probably via SSH) to your web server.

Register with Foursquare - go to https://foursquare.com/oauth/register

* Set the *Application name* to `privatesquare` (or something that means something to you)
* Set the *Application web site* to `http://privatesquare.example.com`
* Set the *Callback URL* to `http://privatesquare.example.com/auth`
* Note the *Client id* and *Client secret* the registration process gives you (it's a good idea to save this in a new browser window or tab so you can copy-and-paste)

Register with Flickr - go to http://www.flickr.com/services/apps/create/apply/

* Apply for a non-commercial key
* Set the *App name* to `privatesquare` (or something that's meaningful to you)
* Set the *App description* to something meaningful, such as *An instance of https://github.com/straup/privatesquare*
* Tick both boxes!
* Note the *key* that the registration process gives you.

Now ... upload the code, plus all sub-directories to your web-server; don't forget the (hidden) `.htaccess` file in the root of the code's distribution.

Copy `<root>/www/include/config.php.example` to `<root>/www/include/config.php` and edit this new file.

Copy-and-paste your Foursquare `Client id` and `Client secret` into the section of the config file that looks like ...

	$GLOBALS['cfg']['foursquare_oauth_key'] = 'my-foursquare-key-copied-in-here';
	$GLOBALS['cfg']['foursquare_oauth_secret'] = 'my-foursquare-secret-copied-in-here';

Copy-and-paste your Flickr Key into the section of the config file that looks like ...

	$GLOBALS['cfg']['flickr_api_key'] = 'my-flickr-key-copied-in-here';

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

Browse to http://privatesquare.example.com

If you get errors in your Apache error log such as ...

	www/.htaccess: Invalid command 'php_value', perhaps misspelled or defined by a module not included in the server configuration

... then your host is probably running PHP as a CGI and not as a module so you'll want to comment out any line in `<root>/www/.htaccess` that starts with `php_value` or `php_flag` and put these values into a new file, `<root>/www/php.ini`, without the leading `php_value` or `php_flag`.

Click on *sign in w/ 4sq* and authenticate with Foursquare.

Browse to http://privatesquare.example.com/account. Select your Foursquare synchronisation options. If you want to sync with 4sq you'll need to run the sync script ...

	$ php <root>/bin/sync-foursquare.php
	
... this is sole the part of the process where you'll need shell access; there's currently no way to do this via the browser.

You might want to put this command in a cron job, if your web host allows this.

That's it. Or should be. If I've forgotten something please let me know or submit a pull request.

## Installation - The Here-Be-Dragons Locally Hosted Version

If you really want to hack and play around with privatesquare, it's best to do this on a private, locally hosted machine, like your laptop or your desktop machine. But as a starting point you need to have followed the installation instructions above as you *need* to have a public facing installation first and then clone this to your local machine. The reason for this is that you need to authenticate with Foursquare, Foursquare uses OAuth to authenticate and OAuth authentication needs a publicly accessible web server to authenticate *with*. With that said, roll up your sleeves, grab a cup of your caffeinated beverage of choice and follow along.

This rest of this section will assume the following:

* That you're running [MAMP](http://mamp.info/en/index.html) on a Mac. MAMP is a nice convenient way to run MySQL, Apache and PHP on a Mac. There's also a Windows version called [WAMP](http://www.wampserver.com/en/). Or most Linux distros come with all of this installed. YMMV so you may need to change some paths and file names.
* That you'll set up a local host name called `privatesquare`
* That your MAMP installation is running Apache on port 8888 and MySQL on port 8889.

So ... firstly create a local host name by adding `privatesquare` to your `/etc/hosts` file, which will look something like this ...

	127.0.0.1	localhost localps

On some operating systems, this file is re-read each time your browser is re-started. On Mac OS X, you'll also need to flush and reload the machine's DNS cache ...

	$ sudo /usr/bin/dscacheutil -flushcache

Now create a new virtual host on your machine. Edit `/Applications/MAMP/conf/apache/httpd-vhosts.conf` and append the magic incantation ...

	<VirtualHost *:8888>
		ServerName localps:8888
		DocumentRoot "/Applications/MAMP/htdocs/ps.vicchi.org/www"
	</VirtualHost>

Restart Apache in MAMP. Create `/Applications/MAMP/htdocs/privatesquare/www`. Browse to `http://privatesquare:8888`. Check you get an empty directory listing of / to ensure your virtual host is configured and working correctly.

Now download your working, public, privatesquare install (to ensure any customisations, including configuration, you've made are preserved) from your public facing webserver.

Export your privatesquare database from your public facing installation, either via phpMyAdmin / export or via the `mysqldump command` from the shell's command line.

Create a new local database to hold the, err, data.

Import your privatesquare database to your local installation, either via phpMyAdmin / import or via the `mysqldump` command from the shell's command line.

Edit your local copy of privatesquare's configuration file at `/Applications/MAMP/htdocs/privatesquare/www/include/config.php` to point to your new local database.

	$GLOBALS['cfg']['db_main'] = array(
		'host' => 'localhost',
		'name' => 'privatesquare',
		'user' => 'root',
		'pass' => 'root',
		'auto_connect' => 0,
		);

Still in the local privatesquare configuration file, set the `environment` config value to be `localhost`:

	$GLOBALS['cfg']['environment'] = 'localhost';

Now browse back to `http://privatesquare:8888`. You should be asked to *sign in w/ 4sq*. Don't. You'll be redirected to the Foursquare site to authenticate and this will fail as your local install isn't publicly accessible.

Copy `/Applications/MAMP/htdocs/privatesquare/bin/spoof-login-cookie.php` to `/Applications/MAMP/htdocs/privatesquare/www`.

Edit `/Applications/MAMP/htdocs/ps.vicchi.org/bin/spoof-login-cookie.php`. *Yes*, this is fugly and hacky. *Yes*, I know it is. Change the `$username` variable to contain your Foursquare username (take a look in the `users` table in your cloned database to see what yours is). Save your changes.

Browse to `http://privatesquare:8888/spoof-login-cookie.php`

You should now see a message saying *"All done; now click here"*. Click *there*.

You should be signed in and good to go. You'll probably want to remove the copy of `spoof-login-cookie.php` if you have OCD, but it's a local machine and if someone can access this on a browser running on your machine then this is the least of your problems.

If you want to run `<root>/bin/sync-foursquare.php` you'll need to make your your shell environment is set up to find the correct binaries and libraries. For MAMP this means putting the following into your `.bash_profile`.
	
	PATH=/Applications/MAMP/Library/bin:/Applications/MAMP/bin/php/php5.3.6/bin:$PATH
	export $PATH

## Configuring fancy stuff – Artisanal Integers

By default privatesquare generates its own internal check in IDs using a local ticket server. One side effect of this approach is that it makes it difficult to reliably merge two (or more) separate instances of privatesquare because its likely that each instance will have issued the same ID for different check ins.

It is possible to configure privatesquare to use an artisanal integer provider to ensure that all your check ins are assigned globally unique IDs. Please note that creating these IDs involves calling these services when you check in and this will add a little extra time and overhead to every check in. 
	
To enable the use of artisanal integers please ensure that the following variables are set (and enabled) in your `config.php` file:

	# Use an artisanal integer provider to generate local privatesquare database IDs

	$GLOBALS['cfg']['enable_feature_artisanal_integers'] = 1;

	# Possible values are: mission; brooklyn; london
	# If empty the code will default to a random provider

	$GLOBALS['cfg']['artisanal_integers_provider'] = '';

The currently supported artisanal integer providers are: [Mission Integers](http://www.missionintegers.com/), [Brooklyn Integers](http://www.brooklynintegers.com/) and [London Integers](http://www.londonintegers.com/). For a very very (very) long and thorough discussion of artisanal integers [you should read this blog post](http://www.aaronland.info/weblog/2012/12/01/coffee-and-wifi#timepixels).

## Configuring fancy stuff – Checking in offline

Support for checking in offline is a subset of the `deferred checkins`
feature.

A deferred check-in might happen when the foursquare servers are busted, like when everyone is at SXSW and trying to check-in at the same time. If that
happens privatesquare will know _where_ you are in geographic space (latitude
and longitude) and will prompt you to record the name of the venue you're trying
to check-in to.

That name of the venue, along with the latitude and longitude and the date, will
be stored as a "pending" check-in in your browser's local storage database
allowing you to complete the check-in when the foursquare servers are happy
again.

If you're offline, though, your browser won't even know where you are. So instead
of just prompting you for the name of the venue you're at privatesquare asks you
for both the name of the venue _and_ the city you're in. [Like this.](http://www.flickr.com/photos/straup/8292903436/)

Once you're back online your check-in will be waiting for you in the same
"pending" bin as deferred check-ins (specifically the "pending" link in nav
menu). Instead of asking foursquare for a venue named (x) near a given latitude and
longitude privatesquare will ask for venues named (x) in the city you told it
about. This means that you will probably be presented with _a lot_ more
venues to choose from but if the alternative is not being able to check-in at
all that seems like a reasonable compromise. [Like this.](https://www.flickr.com/photos/straup/8292070035/)

_At some point in the future privatesquare might keep a local cache of all the
cities you're checked in from and try to be clever about auto-filling that field but for the
time being you'll need to add that information by hand._

As with deferred checkins you can only indicate that "you are here". Check-ins
are _not_ passed along to foursquare since there's no way to check-in to the
past with foursquare. That's a perfectly legitimate choice for them to
make. I've been tossing around the possibility of working around that
constraint by adding a mention about the past-iness of a deferred or offline
check-in in the notes field, but it's not something I've done yet.

To enable offline check-ins make sure the following flags are enabled in your `config.php` file:

	$GLOBALS['cfg']['enable_feature_deferred_checkins'] = 1;
	$GLOBALS['cfg']['enable_feature_offline_appcache'] = 1;

Offline check-ins are built using the [HTML5 Application
Cache](https://developer.mozilla.org/en-US/docs/HTML/Using_the_application_cache)
(sometimes just referred to as "appcache") which is a
temperamental beast, at best. Jake Archibald's article [Application Cache is a
Douchebag](http://www.alistapart.com/articles/application-cache-is-a-douchebag/)
is pretty much much the definitive word on the subject.

In addition to the vagueries of appcache itself there are also individual
browser caches and sometimes even network proxy caches thrown in to the
mix. Rarely do they place nicely with one another. By the time this
functionality will have been checked in to the `master` branch (and you are
reading this) it is all code that I will have been using on a regular basis in
the network-challenged subways of New York City for several weeks. So it
_should_ work.

Barring any really stupid bugs, though, there are still plenty of reasons why it
might not work which is why the feature is disabled by default. Please keep that
in mind if you do enable it and [let me
know](https://github.com/straup/privatesquare/issues) if and where it doesn't
work for you.

As of this writing the only part of privatesquare that works in offline mode is
the ability to check-in. History pages, the Atlas of Desire and all the other
good stuff is absolutely _not_ available for viewing in offline mode since it
would quickly turn in to a poison soup of caching issues and other gotchas that
life is too short to bother dealing with.

## See also

* [flamework](https://github.com/exflickr/flamework)
