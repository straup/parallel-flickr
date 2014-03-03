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
