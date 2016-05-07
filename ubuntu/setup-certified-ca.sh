#!/bin/sh

if [ $(id -u) != 0 ]; then
     echo "Please be root to do this..."
     exit 1
fi

WHOAMI=`python -c 'import os, sys; print os.path.realpath(sys.argv[1])' $0`

UBUNTU=`dirname $WHOAMI`
PROJECT=`dirname $UBUNTU`

PROJECT_NAME=`basename ${PROJECT}`

CERTIFIED="${PROJECT}/certified"
DB="${CERTIFIED}/db"

if [ ! -d ${CERTIFIED} ]
then
    ${UBUNTU}/setup-certified.sh
fi

if [ -d ${DB} ]
then
    echo "${DB} already exists"
    exit 1
fi

mkdir ${DB}
chmod 700 ${DB}
chown root ${DB}

certified-ca --bits 4096 --db ${DB} C="XN" ST="XN" L="Null Island" O="${PROJECT_NAME}" CN="${PROJECT_NAME} CA" > ${DB}/ca.crt

exit 0
