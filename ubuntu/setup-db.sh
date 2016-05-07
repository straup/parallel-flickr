#!/bin/sh

WHOAMI=`python -c 'import os, sys; print os.path.realpath(sys.argv[1])' $0`
UBUNTU=`dirname $WHOAMI`

PROJECT=`dirname $UBUNTU`
SECRETS="${PROJECT}/www/include/secrets.php"

DBNAME=$1
USERNAME=$2

if [ "${DBNAME}" = "" ]
then
    echo "missing database name"
    exit 1
fi

if [ "${USERNAME}" = "" ]
then
    echo "missing database username"
    exit 1
fi

MYSQL=`which mysql`

if [ "${MYSQL}" = "" ]
then

    sudo apt-get update
    sudo apt-get -y upgrade
    sudo apt-get install mysql-server
fi

# We probably don't care about any errors...
PHP='php -d display_errors=off -q'

PASSWORD=`${PHP} ${PROJECT}/bin/generate_secret.php`

if [ -f /tmp/${DBNAME}.sql ]
then
    rm /tmp/${DBNAME}.sql
    touch /tmp/${DBNAME}.sql
fi

echo "DROP DATABASE IF EXISTS ${DBNAME};" >> /tmp/${DBNAME}.sql;
echo "CREATE DATABASE ${DBNAME};" >> /tmp/${DBNAME}.sql
# echo "DROP user '${USERNAME}'@'localhost';" >> /tmp/${DBNAME}.sql
echo "CREATE user '${USERNAME}'@'localhost' IDENTIFIED BY '${PASSWORD}';" >> /tmp/${DBNAME}.sql
echo "GRANT SELECT,UPDATE,DELETE,INSERT ON ${DBNAME}.* TO '${USERNAME}'@'localhost' IDENTIFIED BY '${PASSWORD}';" >> /tmp/${DBNAME}.sql
echo "FLUSH PRIVILEGES;" >> /tmp/${DBNAME}.sql

echo "USE ${DBNAME};" >> /tmp/${DBNAME}.sql;

for f in `ls -a ${PROJECT}/schema/*.schema`
do
	echo "" >> /tmp/${DBNAME}.sql
	cat $f >> /tmp/${DBNAME}.sql
done

mysql -u root -p < /tmp/${DBNAME}.sql

unlink /tmp/${DBNAME}.sql

perl -p -i -e "s/GLOBALS\['cfg'\]\['db_main'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_main'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['db_users'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_users'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['db_poormans_slaves'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_poormans_slaves'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};

# please to update db name and db user name in config.php...
# (20160316/thisisaaronland)

exit 0
