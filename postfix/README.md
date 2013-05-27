Configuring (local) uploads by email
--

"For security reasons, deliveries to command and file destinations are performed
with the rights of the alias database owner."

so no matter who you define there, it's going to run as default_privs user
because the alias db itself is owned by root 

so choices:

1) modify your pipe to run under sudo, and give nobody sudo privs to run it as
the required user, NOPASSWD

2) define a top-level alias that delivers to your desired user, and set up an
.forward file in that user's home directory that actually executes the pipe

3) [Use storagemaster](https://github.com/straup/parallel-flickr/tree/one-by-one/storagemaster)
