#!/bin/sh

IMA=$1

# http://snowulf.com/archives/540-Truly-non-interactive-unattended-apt-get-install.html
export DEBIAN_FRONTEND=noninteractive

OPTS='-y -q=2 --force-yes'
INSTALL='apt-get '${OPTS}' install'

# I have no idea why this is sometimes necessary
# It's really annoying...
FIX_DPKG='dpkg --configure -a'

apt-get update
apt-get ${OPTS} upgrade

# this assumes you've already installed git-core because
# otherwise you wouldn't be reading this...

${INSTALL} sysstat
${INSTALL} htop
${INSTALL} mysql-server

${INSTALL} php5
${INSTALL} php5-mysql
${INSTALL} php5-curl
${INSTALL} php5-mcrypt

/usr/bin/pecl install mailparse
echo 'extension=mailparse.so' > /etc/php5/apache2/conf.d/mailparse.ini

ln -s  /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/
/etc/init.d/apache2 restart
