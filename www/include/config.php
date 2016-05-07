<?

	$GLOBALS['cfg'] = array();

	# Things you may want to change in a hurry

	$GLOBALS['cfg']['site_name'] = '🐼  | parallel-flickr';
	$GLOBALS['cfg']['environment'] = 'prod';

	$GLOBALS['cfg']['site_disabled'] = 0;
	$GLOBALS['cfg']['site_disabled_retry_after'] = 0;	# seconds; if set will return HTTP Retry-After header

	#
	# Things you need to tweak before you get started
	#

	# Database configs
	# See also: https://github.com/straup/parallel-flickr/blob/master/bin/generate_secret.php

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'localhost',
		'name'	=> 'flickr',
		'user'	=> 'flickr',
		'pass'	=> 'TWEAK ME',
		'auto_connect' => 1,
	);

	# Flickr configs
	# http://www.flickr.com/services/apps/create/apply/

	# Seriously Flickr, fuck you...
	# http://code.flickr.net/2012/01/13/farewell-flickrauth/

	$GLOBALS['cfg']['flickr_api_use_oauth_bridge'] = 0;
	$GLOBALS['cfg']['flickr_oauth_key'] = '';
	$GLOBALS['cfg']['flickr_oauth_secret'] = '';	

	$GLOBALS['cfg']['flickr_api_key'] = 'TWEAK ME';
	$GLOBALS['cfg']['flickr_api_secret'] = 'TWEAK ME';

	# See also: YOUR-PARALLEL-FLICKR/account/flickr/auth

	$GLOBALS['cfg']['flickr_api_perms'] = 'read';
	$GLOBALS['cfg']['enable_feature_flickr_api_change_perms'] = 0;

	# Use Flickr as a ticketing server!
	# See also: parallel-flickr:bin/dbtickets_flickr_token_dance.php

	$GLOBALS['cfg']['enable_feature_dbtickets_flickr'] = 0;
        $GLOBALS['cfg']['dbtickets_flickr_user_id'] = 0;

	# Basic cookie/crypto configs
	# See also: https://github.com/straup/parallel-flickr/blob/master/bin/generate_secret.php

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'TWEAK ME';
	$GLOBALS['cfg']['crypto_password_secret'] = 'TWEAK ME';
	$GLOBALS['cfg']['crypto_crumb_secret'] = 'TWEAK ME';

	#
	# Things you may want to tweak (backups, registrations, invite codes)
	#

	# Flags to indicate whether Flickr should be polled for new photos to backup;
	# whether users can register themselves as backup users; whether registerting
	# also requires a valid invite code. Note that in order to use any of the
	# god pages you will also need to enable and configure the poorman's god auth
	# settings below.

	# Backups - by default anyone who knows where your copy of
	# parallel-flickr is (on the Internet) could register to have
	# their photos (and likes) backed up. If you don't want to
	# let anyone else backup their photos then you should disable
	# the 'backups_enable_registration' flag. If you want
	# to limit who can register take a look at the invite code
	# flags below.

	$GLOBALS['cfg']['enable_feature_backups'] = 1;
	$GLOBALS['cfg']['enable_feature_backups_registration'] = 1;
	$GLOBALS['cfg']['enable_feature_backups_registration_uninvited'] = 0;

	# API URLs and endpoints

	$GLOBALS['cfg']['api_abs_root_url'] = '';	# leave blank - set in api_config_init()
	$GLOBALS['cfg']['site_abs_root_url'] = '';	# leave blank - set in api_config_init()

	$GLOBALS['cfg']['api_subdomain'] = '';
	$GLOBALS['cfg']['api_endpoint'] = 'api/rest/';

	$GLOBALS['cfg']['api_require_ssl'] = 1;

	# API site keys
	
	$GLOBALS['cfg']['enable_feature_api_site_keys'] = 1;
	$GLOBALS['cfg']['enable_feature_api_site_tokens'] = 1;

	$GLOBALS['cfg']['api_site_keys_ttl'] = 28800;		# 8 hours
	$GLOBALS['cfg']['api_site_tokens_ttl'] = 28000;		# 8 hours
	$GLOBALS['cfg']['api_site_tokens_user_ttl'] = 3600;	# 1 hour

	# API pagination

	$GLOBALS['cfg']['api_per_page_default'] = 100;
	$GLOBALS['cfg']['api_per_page_max'] = 500;

	# The actual API config

	$GLOBALS['cfg']['api'] = array(

		'formats' => array( 'json' ),
		'default_format' => 'json',

		# We're defining methods using the method_definitions
		# hooks defined below to minimize the clutter in the
		# main config file, aka this one (20130308/straup)
		'methods' => array(),

		# We are NOT doing the same for blessed API keys since
		# it's expected that their number will be small and
		# manageable (20130308/straup)

		'blessings' => array(
			'xxx-apikey' => array(
				'hosts' => array('127.0.0.1'),
				# 'tokens' => array(),
				# 'environments' => array(),
				'methods' => array(
					'foo.bar.baz' => array(
						'environments' => array('sd-931')
					)
				),
				'method_classes' => array(
					'foo.bar' => array(
						# see above
					)
				),
			),
		),
	);

	# Load api methods defined in separate PHP files whose naming
	# convention is FLAMEWORK_INCLUDE_DIR . "/config_api_{$definition}.php";
	#
	# IMPORTANT: This is syntactic sugar and helper code to keep the growing
	# number of API methods out of the main config. Stuff is loaded in to
	# memory in lib_api_config:api_config_init (20130308/straup)

	$GLOBALS['cfg']['api_method_definitions'] = array(
		'methods',
		'methods_flickr',
		'methods_parallel_flickr',
	);

	# Uploads
	# Allow parallel flickr to upload photos which may or may not be
	# routed on to Flickr.

	# Valid options for _default_send_to are: flickr; parallel-flickr
	# Valid options for _default_notifications are: flickr

	$GLOBALS['cfg']['enable_feature_uploads'] = 0;

	$GLOBALS['cfg']['uploads_default_send_to'] = '';
	$GLOBALS['cfg']['uploads_default_notifications'] = '';
	$GLOBALS['cfg']['uploads_default_tags'] = 'uploaded:by=parallel-flickr';

	# Uploads - notifications

	# Tell Flickr (with a tiny stylized preview image) when you've uploaded
	# a photo to parallel-flickr. TO DO: twitter (20130706/straup)

	$GLOBALS['cfg']['enable_feature_uploads_flickr_notifications'] = 0;

	# Uploads - send to Flickr

	# Allow photos to be sent to Flickr and optionally pre-archive them
	# in parallel-flickr as they are uploaded - you will need to configure
	# your storage provider accordingly (in order to be able to write photos
	# to disk) 

	$GLOBALS['cfg']['enable_feature_uploads_flickr'] = 0;
	$GLOBALS['cfg']['enable_feature_uploads_flickr_archive'] = 0;

	# Uploads - send to Parallel-Flickr

	# You will absolutely need to configure your storage provider to allow you
	# to write files to disk. You will also need to ensure that you users have
	# a Flickr auth token with 'delete' permissions in order to do the
	# dbtickets_flickr trick – careful readers will also note that this means
	# ensuring that the _dbtickets_flickr feature flag be enabled.

	# TO DO: user interface glue and prompts for ensuring that a user has a valid
	# 'delete' auth token from Flickr

	$GLOBALS['cfg']['enable_feature_uploads_parallel_flickr'] = 0;

	# Uploads (by API (which also means the website))
	# See also: http://parallel-flickr/photos/upload/

	$GLOBALS['cfg']['enable_feature_uploads_by_api'] = 0;

	# Uploads (OAuth Echo Upload)
	# Think: custom image backend for Twitter clients like tweetbot.
	# This URL is handy to get the twitter user_id: http://www.idfromuser.com/
	# Also, some day this should be a user settings page. Some day.

	$GLOBALS['cfg']['enable_feature_oauth_upload'] = 0;

	$GLOBALS['cfg']['oauth_upload_user_mapping'] = array(
		// twitter user_id => p-flickr user_id
	);

	# Uploads (by email)

	# Note: setting up and configuring a mail server is left to
	# the individual. The bin/upload_by_email.php script expects
	# to be handd a MIME compatible email message on STDIN; see
	# also sample postfix config files in the postfix directory

	# 1048576 is 1MB; web based upload sizes are controled by the
	# 'upload_max_filesize' directive in www/.htaccess

	$GLOBALS['cfg']['enable_feature_uploads_by_email'] = 0;
	$GLOBALS['cfg']['uploads_by_email_hostname'] = '';
	$GLOBALS['cfg']['uploads_by_email_maxbytes'] = 1048576 * 5;

	# Uploads filt(e)ring

	$GLOBALS['cfg']['enable_feature_uploads_filtr'] = 0;

	$GLOBALS['cfg']['filtr_valid_filtrs'] = array(
		'dazd',
		'postr',
		'postcrd',
		'pxl',
		'rockstr'
	);

	# Invite codes – these are used to limit who can register
	# to have their photos backed up. You'll need to do a
	# few things to enable this:

	# 1) enable the feature flags below for invite codes and
	#    god auth (which is explained later)

	# 2) generate a new secret for encrypting invite cookies
	#    parallel-flickr/bin/generate_secret.php

	# 3) set up poorman's 'god auth' – basically this is just
	#    restricting access to a list of logged in user using
	#    cookies; it works but I wouldn't call it "secure"

	# Once that's done you can manage or create new invites
	# here:

	# $GLOBALS['cfg']['abs_root_url']/god/invites/
	# $GLOBALS['cfg']['abs_root_url']/god/invites/generate/

	# In addition, if a user tries to go to the backup page
	# they've got stopped by an invite code wall which will
	# allow them to request an invite code but you'll still
	# need to send it manually (by pressing a button on the
	# god page).

	$GLOBALS['cfg']['enable_feature_invite_codes'] = 1;
	$GLOBALS['cfg']['crypto_invite_secret'] = '';

	#
	# Things you may want to tweak (poorman's god auth)
	#

	$GLOBALS['cfg']['auth_enable_poormans_god_auth'] = 0;

	$GLOBALS['cfg']['auth_poormans_god_auth'] = array(

		# poormans god auth is keyed off a user's UID
		#
		# xxx => array(
		# 	'roles' => array( 'admin' ),
		# ),
	);

	#
	# Things you may want to tweak (storage)
	#

	# By default, parallel-flickr stores files on the local file system. It is possible
	# to store your photos (and metadata) using Amazon's S3 service but you'll need to
	# enable/configure it below.

	# Storage providers. Valid options are:
	# 'fs' which will store stuff to the local filesystem
	# 's3' which will store stuff to S3
	# 'storagemaster' which will store stuff to the local filesystem - see details below

	$GLOBALS['cfg']['storage_provider'] = 'fs';

	# The 'flickr_static_url/path' flags needs to match the Alias directive
	# in your Apache config. Not that the 'flickr_static_url' config is a
	# relative path; relative to the root URL which is assigned separately.
	# See also: https://github.com/straup/parallel-flickr/blob/master/apache/parallel-flickr.conf.example

	$GLOBALS['cfg']['flickr_static_path'] = "TWEAK ME";
	$GLOBALS['cfg']['flickr_static_url'] = "static/";

	# If you want to store your photos (and metadata) using Amazon's S3
	# service you'll need to enable this feature flag and fill in the
	# 'amazon_s3_*' flags below.

	$GLOBALS['cfg']['amazon_s3_access_key'] = 'TWEAK ME';
	$GLOBALS['cfg']['amazon_s3_secret_key'] = 'TWEAK ME';
	$GLOBALS['cfg']['amazon_s3_bucket_name'] = 'TWEAK ME';

	# Storagemaster. See also:
	# parallel-flickr/storagemaster/bin/storagemaster.py
	# parallel-flickr/storagemaster/init.d/storagemaster.sh

	$GLOBALS['cfg']['storage_storagemaster_host'] = '127.0.0.1';
	$GLOBALS['cfg']['storage_storagemaster_port'] = '9999';


	#
	# Things you may want to tweak (maps)
	#

	$GLOBALS['cfg']['enable_feature_slippymaps'] = 1;
	$GLOBALS['cfg']['slippymap_provider'] = 'toner';	# assumes canned htmapl provider strings
								# see also: http://htmapl.com/examples/providers.html

	#
	# Things you may want to tweak (Flickr PuSH feeds)
	#

	$GLOBALS['cfg']['enable_feature_flickr_push'] = 1;
	$GLOBALS['cfg']['flickr_push_enable_registrations'] = 1;
	$GLOBALS['cfg']['flickr_push_enable_photos_friends'] = 1;

	# this one doesn't seem to work anymore - sad face (20130625/straup)
	# $GLOBALS['cfg']['flickr_push_enable_photos_friends_faves'] = 0;

	$GLOBALS['cfg']['flickr_push_enable_photos_recent_activity'] = 0;
	$GLOBALS['cfg']['flickr_push_notification_email'] = '';	
	$GLOBALS['cfg']['flickr_push_ignore_key'] = '';

	# This is off by default because it requires that you ensure that
	# the directory specified in $GLOBALS['cfg']['flickr_static_path']
	# is both:
	# 
	# Writeable by the web server and that whichever users who may run
	# the bin/backup_*.php scripts are in a shared (unix) group with
	# the web server user account.
	#
	# If you are already storing your photos using Amazon's S3 service
	# (see above) then you don't need to worry about these unix-specific
	# permissions issues.

	$GLOBALS['cfg']['enable_feature_flickr_push_backups'] = 0;

	#
	# Things you may want to tweak (Solr)
	#

	# Solr is used for search and fancy features like places and cameras
	# https://github.com/straup/parallel-flickr/tree/master/solr/
	# https://lucene.apache.org/solr/#intro

	$GLOBALS['cfg']['enable_feature_solr'] = 0;
	$GLOBALS['cfg']['solr_endpoint'] = 'http://localhost:9999/solr/parallel-flickr/';

	# Hey look! Things that require Solr.

	$GLOBALS['cfg']['enable_feature_places'] = 0;
	$GLOBALS['cfg']['enable_feature_cameras'] = 0;
	$GLOBALS['cfg']['enable_feature_archives'] = 0;

	$GLOBALS['cfg']['places_prefetch_data'] = 1;

	#
	# Things you may want to tweak (misc. and silly stuff)
	#

	$GLOBALS['cfg']['enable_feature_sharkify'] = 1;		# http://www.iamcal.com/sharkify/

	$GLOBALS['cfg']['enable_keyboard_browse'] = 1;

	$GLOBALS['cfg']['enable_feature_path_alias_redirects'] = 0;

	#
	# Things you probably don't need to worry about
	#

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 0;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	# See this? It assumes everything is running on a single
	# (read: not federated) database.

	$GLOBALS['cfg']['db_enable_poormans_federation'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 1;

	$GLOBALS['cfg']['smarty_template_dir'] = realpath(dirname(__FILE__) . '/../templates/');
	$GLOBALS['cfg']['smarty_compile_dir'] = realpath(dirname(__FILE__) . '/../templates_c/');

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	$GLOBALS['cfg']['auth_cookie_secure'] = 0;		# see also: http://github.com/blog/737-sidejack-prevention
	$GLOBALS['cfg']['auth_cookie_httponly'] = 0;

	$GLOBALS['cfg']['crumb_ttl_default'] = 300;		# seconds

	$GLOBALS['cfg']['rewrite_static_urls'] = array(
		# '/foo' => '/bar/',
	);

	$GLOBALS['cfg']['email_from_name']	= 'flamework app';
	$GLOBALS['cfg']['email_from_email']	= 'admin@ourapp.com';
	$GLOBALS['cfg']['auto_email_args']	= '-fadmin@ourapp.com';

	$GLOBALS['cfg']['user'] = null;

	# This is only relevant if are running parallel-flickr on a machine where you
	# can not make the www/templates_c folder writeable by the web server. If that's
	# the case set this to 0 but understand that you'll need to pre-compile all
	# of your templates before they can be used by the site.
	# See also: https://github.com/straup/parallel-flickr/blob/master/bin/compile-templates.php

	$GLOBALS['cfg']['smarty_compile'] = 1;

	# Do not always compile all the things all the time. Unless you know you're in to
	# that kind of thing. One important thing to note about this setting is that you
	# will need to reenabled it at least once (and load the template in question) if
	# you've got a template that calls a non-standard function. For example, something
	# like: {$foo|@bar_all_the_things}

	$GLOBALS['cfg']['smarty_force_compile'] = 0;

	$GLOBALS['cfg']['http_timeout'] = 3;

	$GLOBALS['cfg']['check_notices'] = 1;

	$GLOBALS['cfg']['db_profiling'] = 0;

	$GLOBALS['cfg']['pagination_assign_smarty_variable'] = 1;
	$GLOBALS['cfg']['pagination_per_page'] = 10;
	$GLOBALS['cfg']['pagination_spill'] = 2;
	$GLOBALS['cfg']['pagination_style'] = 'pretty';

	#
	# enable this flag to show a full call chain (instead of just the
	# immediate caller) in database query log messages and embedded in
	# the actual SQL sent to the server.
	#

	$GLOBALS['cfg']['db_full_callstack'] = 0;
?>
