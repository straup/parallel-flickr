<?php

	#
	# $Id$
	#

	#################################################################

        # http://code.flickr.com/blog/2010/02/08/ticket-servers-distributed-unique-primary-keys-on-the-cheap/

	#################################################################

        function dbtickets_create($len=32){

                $table = 'Tickets' . intval($len);

		# As in an instance of flamework that has no access to
		# its mysql config files and/or the ability to set up
		# a dedicated DB server for tickets.

		if ($GLOBALS['cfg']['db_enable_poormans_ticketing']){

			# ALTER TABLE tbl_name AUTO_INCREMENT = (n)
			# how the fuck do you set the offset from the SQL CLI ?

			$rsp = db_tickets_write("SET @@auto_increment_increment=2");

			if (! $rsp['ok']){
				log_error("Failed to set auto_increment_increment for {$table}");
				return null;
			}

			$rsp = db_tickets_write("SET @@auto_increment_offset=1");

			if (! $rsp['ok']){
				log_error("Failed to set auto_increment_offset for {$table}");
				return null;
			}
		}

		$rsp = db_tickets_write("REPLACE INTO {$table} (stub) VALUES ('a')");

		if ((! $rsp['ok']) || (! $rsp['insert_id'])){
			log_error("Failed to replace into {$table}");
			return null;
		}

		return $rsp['insert_id'];
        }

	#################################################################
?>