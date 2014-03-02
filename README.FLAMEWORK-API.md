flamework-api
==

_This file has been copied from the [flamework-api](https://github.com/straup/flamework-api) repository and is included
for informational purposes. This is the code that parallel-flickr uses but the
specifics detailed below may not always be the same._

These are drop-in libraries for adding an API endpoint to a Flamework
project.

It includes libraries and webpages for dispatching API requests and responses as
well as creating and managing API keys.

There is also support for authenticated API methods using cookies (not really
useful for third-party things) and OAuth2 access tokens (which means you've got
a site that uses SSL).

You can either install all of the files manually or you can the `bin/setup.sh`
script which will install most of the relevant bits automatically and make a
note of the stuff you'll see need to do yourself.

# .htaccess

You will need to copy the contents of the [flamework-api .htaccess
file](./www/.htaccess) in your projects's .htaccess file. Take a look at it and
be sure to notice all the stuff that's been commented out at the top.

It's a lot of hoop-jumping to separate API calls (api.example.com/rest) from all
the other user-level administrative pages (example.com/api/methods) and to make
sure things that need to be done over SSL are (like OAuth2). By default it's all
commented out because what do I know about your webserver is configured. So
spend a couple minutes looking at all this stuff and thinking about it and
adjusting accordingly. Also: remember _all the security around OAuth2_ is
predicated around the use of SSL.

Otherwise all the URLs defined in the .htaccess file are discussed below.

# config.php

## Basics

### $GLOBALS['cfg']['enable_feature_api'] = 1;

A boolean flag indicating whether or not the API is available for use.

### $GLOBALS['cfg']['enable_feature_api_documentation'] = 1;

A boolean flag indicating whether or not documentation for API methods is
publicly available.

### $GLOBALS['cfg']['enable_feature_api_logging'] = 1;

A boolean flag indicating whether or not to log API requests.

Currently these are just written to the Apache error logs. Eventually it might
be a configurable thing that allows to store things in Redis, etc. But right now
it's not.

### $GLOBALS['cfg']['enable_feature_api_throttling'] = 0;

This currently doesn't actually do anything. But, you know, throttling! (See
above inre: logging.)

### $GLOBALS['cfg']['api_abs_root_url'] = '';

The fully-qualified URL of the API endpoint.

_You should leave this blank as it will be set automatically in api_config_init()_

### $GLOBALS['cfg']['site_abs_root_url'] = '';

The fully-qualified URL of the site that exposes the API endpoint. These may not
be the same. See the `api_subdomain` parameter below.

_You should leave this blank as it will be set automatically in api_config_init()_

### $GLOBALS['cfg']['api_subdomain'] = '';

Optionally define a sub-domain for your API. For example **api**.my-website.com

### $GLOBALS['cfg']['api_endpoint'] = 'rest/';

Where clients using the API should send requests. Note that this is really
defined (controlled) in the .htaccess file but is included here in order to
generate API documentation.

### $GLOBALS['cfg']['api_require_ssl'] = 1;

A boolean flag indicating whether or not the API requires SSL. This is used to
generate `api_abs_root_url` and `site_abs_root_url`.

## API keys

### $GLOBALS['cfg']['enable_feature_api_require_keys'] = 0;

Irrelevant if you are using OAuth2 (below) otherwise a boolean flag indicating
whether API methods must be called with a registered API key.

### $GLOBALS['cfg']['enable_feature_api_register_keys'] = 1;

A boolean flag indicating whether or not to allow users to create new API keys.

## Delegated authentication

### $GLOBALS['cfg']['enable_feature_api_delegated_auth'] = 1;

A boolean flag indicating whether or not to allow users (and applications) to
create new authentication (access) tokens.

### $GLOBALS['cfg']['api_auth_type'] = 'oauth2';

Currently supported auth types are `oauth2` and `cookies`.

### $GLOBALS['cfg']['enable_feature_api_authenticate_self'] = 1;

A boolean flag indicating whether or not to allow users to magically create both
API keys and auth (access) tokens for themselves without all the usual
hoop-jumping of delegated auth.

# OAuth2

### $GLOBALS['cfg']['api_oauth2_require_authentication_header'] = 0;

An optional flag to require that people pass OAuth2 access tokens around in the
HTTP Authentication header.

### $GLOBALS['cfg']['api_oauth2_allow_get_parameters'] = 0;

Allow people to make OAuth2 requests using the HTTP GET method. There are couple
of things you need to be aware of if you enable this:

* You can (and should) still control whether or not a given method must be
  called using a different HTTP method. See below for details.

* You should not enable this unless you're using HTTPS. You really shouldn't be
  using OAuth2 at all without HTTPS but I can't control that (and by the time
  that the code here might notice the disconnect it will be too late).

## Site keys (and tokens)

Site keys are special API keys that are not bound to any user and are created
with an explicit time to live (ttl). They are retrieved (or created) when the
web site is loaded and checked to make sure they don't need to automatically
expired.

Normally there's no reason for your code to see (or use) site keys. Instead your
code might check a site "token", as in an access token used for delegated
authentication. Site tokens are minted when the page loads and bind the current
site API key with the current user.

That means a separate site token is created for each user and a shared site
token (with limited permissions) is created for logged-out users. Like site keys
the tokens are given a finite time to live (ttl) which is defined below.

All of this allows your website to use its own API the same way every other
application does without having to force users jump through the usual
authentication dance.

### $GLOBALS['cfg']['enable_feature_api_site_keys'] = 1;

A boolean flag controlling whether site keys are enabled.

### $GLOBALS['cfg']['enable_feature_api_site_tokens'] = 1;

A boolean flag controlling whether site tokens are enabled. Note that site
tokens won't work unless site keys are enabled.

### $GLOBALS['cfg']['api_site_keys_ttl'] = 28800;

The time to live for site keys. Default is 28800 seconds, or 8 hours.

### $GLOBALS['cfg']['api_site_tokens_ttl'] = 28000;

The time to live for site tokens for logged-out users. Default is 28800 seconds,
or 8 hours. 

### $GLOBALS['cfg']['api_site_tokens_user_ttl'] = 3600;

The time to live for site tokens for logged-in users. Default is 3600 seconds,
or 1 hours.

Here's the annoying part.

In order for this to work you'll need to modify the code in `init.php` for this
to work. The relevant code is included below and in the
`www/include/init.php.site-keys` file.

Go and look for the following:

	if ($this_is_webpage){
	        login_check_login();

And then add:

		# API site key/token stuff

		if (features_is_enabled("api")){

			loadlib("api");

			if (features_is_enabled(array("api_site_keys", "api_site_tokens"))){

				loadlib("api_keys");
				loadlib("api_oauth2_access_tokens");

				$token = api_oauth2_access_tokens_fetch_site_token($GLOBALS['cfg']['user']);
				$GLOBALS['smarty']->assign_by_ref("site_token", $token['access_token']);
			}
		}

A few things to notice:

* The part where we're adding the site keys/tokens code _after_ we call
 `login_check_login`

* The part where we're checking that the API itself is enabled.

* The part where OAuth2 tokens are hard-coded. That will need to be revisited if
  another delegated auth token system is supported. Right not it's the only game
  so...

* The part where the token is being assigned to a Smarty variable. How your code
uses that variable is left up to you. Specifically if you're calling the API you
will need to grab the Smarty variable `site_token` and pass it along with each
request.

Finally, having to modify `init.php` really sucks. It works but it's neither
great nor elegant. Any suggestions for making this better are welcome.

## Pagination

### $GLOBALS['cfg']['api_per_page_default'] = 100;

The default number of results to return, per page, for things that are
paginated.

### $GLOBALS['cfg']['api_per_page_max'] = 500;

The maximum number of results to return, per page, for things that are
paginated.

# API methods

API methods, and related specifics, are defined as a dictionary where the keys
are method names and the values are the method details.

For example:

	$GLOBALS['cfg']['api']['methods'] => array(
		"api.spec.methods" => array(
			"description" => "Return the list of available API response methods.",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_spec"
			"parameters" => array(
				array("name" => "method_class", "description" => "Only return methods contained by this method class", "required" => 0),
			),
			"errors" => array(
				array("code" => "404", "description" => "Method class not found"),
				array("code" => "418", "description" => "I'm a teapot"),
			),
			"notes" => array(
				"You are beautiful",
			),
		)
	)

Valid method details include:

### description

A short blurb describing the method.

### documented

A boolean flag indicating whether or not to include this method in the online
(and API based) documentation.

### enabled

A boolean flag indicating whether or not this method can be called.

### library

The name of the library to find the actual function that a method name
corresponds to. All things being equal this is what will be invoked.

### parameters

An optional list of dictionaries containing parameter definitions for the method. Valid keys for each dictionary are:

* **name** - the name of the parameter

* **description** - a short text describing the requirements and context for the parameter

* **required** - a boolean flag indicating whether or not the parameter is required

### errors

An optional list of dictionaries containing error definitions for the method. Valid keys for each dictionary are:

* **code** - the numeric code for the error response

* **description** – a short text describing the reasons or context in which the error was triggered

Error codes are left to the discretion of individual developers.

### notes

An optional list of notes that are each blobs of text.

### request_method

If present then the API dispatching code will ensure that the HTTP method used
to invoke the (API) method matches.

### requires_auth

A boolean flag indicating whether or not a method requires an authorization
token to be passed (and tested).

_This is not necessary to declare if you are using OAuth2._

### requires_crumb

A boolean flag indicating whether or a method requires that a valid (Flamework)
crumb ba passed (and validated).

### requires_blessing

A boolean flag indicating whether or a method requires that access to the method
be restricted (or "blessed") by API key, access token or host.

API method "blessings" are discussed in detail below.

# API method "defintions"

The `$GLOBALS['cfg']['api_method_definitions']` config variable is a little
piece of syntatic sugar and helper code to keep the growing number of API
methods out of the main config.

This allows to load API methods defined (described above) in separate PHP files
whose naming convention is:

	FLAMEWORK_INCLUDE_DIR . "/config_api_methods.php";

Where the **methods** part is used to denote a particular group of method
definitions. For example:

	$GLOBALS['cfg']['api_method_definitions'] = array(
		'methods',
	);

See the included `config_api_methods.php` for an example of this setup.

# "Blessed" API methods

"Blessed" API methods are those methods with one or more access controls on
them. Access controls are always based on API keys and may also be further
locked down by API access token, host (the remote address of the client calling
the API method) and by Flamework "environment".

API blessings are defined in `$GLOBALS['cfg']['api']['blessings']` setting. For
example:

	$GLOBALS['cfg']['api']['blessings'] => array(
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
	);

Each definition is keyed (no pun intended) by an API key whose value is a
dictionary containing some or all of the following properties:

### methods

A list of dictionaries (or hashes) that define the API methods that this key has
been blessed to access.

Each dictionary is keyed by a fully qualified method name whose value is an
array that may be empty or contain one or more of the following properties:
hosts, tokens, environments (described in detail below).

### method_classes

A list of dictionaries (or hashes) that define a group of API methods that this
key has been blessed to access.

Each dictionary is keyed by one or more leading parts of a method name, or a
"class". For example if you wanted to grant access to all the methods in the
`foo.bar` class of methods (`foo.bar.baz`, `foo.bar.helloworld` and so one) to
an API key you would say: 

	'foo.bar' => array()

The value for each key (class) is an array that may be empty or contain one or
more of the following properties: hosts, tokens, environments (described in
detail below).

### hosts

A list of IP addresses (in addition to a specific API key) allowed to access a
given API method.

If defined in the scope of an API key these restrictions will apply to all child
method and method class definitions. Note if this key is defined as a empty list
no requests will be granted access to its corresponding API method.

### tokens

A list of API access tokens (in addition to a specific API key) allowed to
access a given API method.

If defined in the scope of an API key these restrictions will apply to all child
method and method class definitions. Note if this key is defined as a empty list
no requests will be granted access to its corresponding API method.

### environments

A list of Flamework "environment" names (in addition to a specific API key)
allowed to access a given API method. Which means this API method will only be
accessible on a list of servers that self-identify with this name.

Environment names are defined in the `$GLOBALS['cfg']['environment']` setting.

If defined in the scope of an API key these restrictions will apply to all child
method and method class definitions. Note if this key is defined as a empty list
no requests will be granted access to its corresponding API method.

# URLs

These are the URLs/endpoints that will be added to your project if you install flamework-api.

Take a look at the .htaccess file and pay close attention to all this stuff
that's been commented out at the top. It's a lot of hoop-jumping to separate API
calls (api.example.com/rest) from all the other user-level administrative pages
(example.com/api/methods) and to make sure things that need to be done over SSL
are (like OAuth2).

By default it's all commented out because what do I know about your webserver is
configured. So spend a couple minutes looking at all this stuff and thinking
about it and adjusting accordingly.

Also: Remember that _all the security_ around OAuth2 is predicated around the use
of SSL.

### api/

A simple landing page for the API with pointers to documentation about methods
and delegated authentication.

### api/methods/

The list of public (enabled and documented) methods for the API.

### api/methods/SOME_METHOD_NAME/

Documentation and examples for individual API methods.

### api/keys/

The list of API keys registered by a (logged in) user.

### api/keys/register/

Create a new API key.

### api/keys/API_KEY/

Review or update an existing API key.

### api/keys/API_KEY/tokens/

The list of OAuth2 access tokens associated with a given API key.

### api/oauth2/

A simple landing page for the OAuth2 webpages with pointers descriptions 
and pointers.

### api/oauth2/authenticate/

The standard OAuth2 authenticate a user / authorize an application webpage.

### api/oauth2/authenticate/like-magic/

A non-standard helper OAuth2 webpage to allow (logged in) users to create
themselves both an API key and a corresponding access token from a single page
by "clicking a button".

### api/oauth2/authenticate/access_token/

The standard OAuth2 echange a (temporary) grant token for a (more permanent)
access token endpoint. This is meant for robots.

### api/oauth2/tokens/

A list of OAuth2 access tokens for a (logged in) user.

### api/oauth2/tokens/API_KEY/

Review of update an existing OAuth2 access token. (Note how we are passing
around the API key in URLs and not the actual access token.)

### rest/

This is the actual API dispatch/endpoint. Code points here.

# To do

* A good web-based API explorer

* Admin pages for viewing API keys and tokens
 
See also
--

* [flamework](https://github.com/straup/flamework)

* [flamework-tools](https://github.com/straup/flamework)
