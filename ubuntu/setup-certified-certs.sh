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

if [ ! -d ${DB} ]
then
    ${UBUNTU}/setup-certified-ca.sh
fi

CSPLIT=`which csplit`
CERTIFIED=`which certified`

PROJECT_KEY="${DB}/${PROJECT_NAME}.key"
PROJECT_CRT="${DB}/${PROJECT_NAME}.crt"
PROJECT_KEY_CRT="${DB}/${PROJECT_NAME}-key-crt.txt"

if [ -f ${PROJECT_KEY} ]
then
    echo "${PROJECT_NAME} key already exists"
    exit 1
fi

if [ -f ${PROJECT_CRT} ]
then
    echo "${PROJECT_NAME} cert already exists"
    exit 1
fi

if [ -f ${PROJECT_CRT}.txt ]
then
    rm ${PROJECT_CRT}.txt
fi 

# See this? This assumes we're running in AWS or on localhost... which is
# inevitably going to be wrong one day (20160131/thisisaaronland)

PUBLIC_IP=`curl -s --connect-timeout 3 http://169.254.169.254/latest/meta-data/public-ipv4`

if [ "${PUBLIC_IP}" = "" ]
then
    PUBLIC_IP='127.0.0.1'
fi

# I don't remember why this bit (with localhost) is necessary
# to be honest... (20160131/thisisaaronland)

LOCAL_CRT="${DB}/certs/localhost.crt"

if [ -f ${LOCAL_CRT} ]
then
    ${CERTIFIED} --revoke --db ${DB} CN="localhost" +"${PUBLIC_IP}"
fi

${CERTIFIED} --bits 4096 --db ${DB} CN="localhost" +"${PUBLIC_IP}" > ${PROJECT_KEY_CRT}

if [ ! -f ${PROJECT_KEY_CRT}  ]
then
    echo "Failed to generate key/certs"
    exit 1
fi

cd ${DB}

${CSPLIT} -k ${PROJECT_KEY_CRT} '/-----END RSA PRIVATE KEY-----/+1'

if [ ! -f ${DB}/xx00 ]
then
    echo "Failed to split ${PROJECT_NAME}-key-crt.txt correctly"
    exit 1
fi

if [ ! -f ${DB}/xx01 ]
then
    echo "Failed to split ${PROJECT_NAME}-key-crt.txt correctly"
    exit 1
fi

mv ${DB}/xx00 ${PROJECT_KEY}
mv ${DB}/xx01 ${PROJECT_CRT}

chown root ${PROJECT_KEY}
chmod 600 ${PROJECT_KEY}

chown root ${PROJECT_CRT}
chmod 600 ${PROJECT_CRT}

cd -

rm ${PROJECT_KEY_CRT}

exit 0
