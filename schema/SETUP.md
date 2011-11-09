CREATE DATABASE flickr;
CREATE USER 'flickr'@'localhost' IDENTIFIED BY '***';
GRANT SELECT,INSERT,UPDATE, DELETE ON flickr.* TO 'flickr'@'localhost' IDENTIFIED BY '***';
