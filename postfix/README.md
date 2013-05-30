Configuring (local) uploads by email
==

Set up an A record for the domain you're running parallel-flickr. You may want
to set up something like `photos.YOUR-DOMAIN` to account for the fact that the
default set up creates a catch-all address for upload-by-email handlers.

First ensure that your domain is listed in `/etc/postfix/main.cf`. Like this:

	virtual_alias_domains = photos.YOU-DOMAIN
	virtual_alias_maps = hash:/etc/postfix/virtual
	
See that second line? It's important. In your `/etc/postfix/virtual` file add
the following:

	# replace example.com with your domain
	example.com anything
	@example.com upload@localhost

And then in `/etc/aliases` add the following:

	upload: "| /usr/bin/php -q /path/to/parallel-flickr/bin/upload_by_email.php"

See what's going on? Anything sent to your domain will be forwarded to the
virtual `upload` user on your current machine. That's where the aliases filed
kicks in. It says: Send the contents of messages (delivered to user `upload`) to
the parallel-flickr upload-by-email handlers.

Writing files to disk
--

This is where it gets a bit complicated if you're running parallel-flickr in
'local' mode (where you are not actually sending photos to Flickr
first). Specifically, the documentation for Postfix says:

"For security reasons, deliveries to command and file destinations are performed
with the rights of the alias database owner."

So no matter who you define there, it's going to run as default_privs user
because the alias db itself is owned by root. That means your choices are:

1) modify your pipe to run under sudo, and give nobody sudo privs to run it as
the required user, NOPASSWD

2) define a top-level alias that delivers to your desired user, and set up an
.forward file in that user's home directory that actually executes the pipe

3) [Use storagemaster](https://github.com/straup/parallel-flickr/tree/one-by-one/storagemaster)

See also
--

* http://www.debian-administration.org/articles/243
