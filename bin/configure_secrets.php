#!/bin/sh

PROJECT=$1
SECRETS=${PROJECT}/www/include/secrets.php

if [ ! -f ${SECRETS} ]
then

    touch ${SECRETS}
    echo "<?php" >> ${SECRETS}
    echo ""
    
    echo "	\$GLOBALS['cfg']['crypto_cookie_secret'] = '';" >> ${SECRETS}
    echo "	\$GLOBALS['cfg']['crypto_password_secret'] = '';" >> ${SECRETS}
    echo "	\$GLOBALS['cfg']['crypto_crumb_secret'] = '';" >> ${SECRETS}
    echo "	\$GLOBALS['cfg']['db_main']['pass'] = '';" >> ${SECRETS}
    echo "	\$GLOBALS['cfg']['db_users']['pass'] = '';" >> ${SECRETS}
    echo "	\$GLOBALS['cfg']['db_poormans_slaves']['pass'] = '';" >> ${SECRETS}

    echo "" >> ${SECRETS}
fi

# We probably don't care about any errors...
PHP='php -d display_errors=off -q'

COOKIE_SECRET=`${PHP} ${PROJECT}/bin/generate_secret.php`
CRUMB_SECRET=`${PHP} ${PROJECT}/bin/generate_secret.php`
PASSWORD_SECRET=`${PHP} ${PROJECT}/bin/generate_secret.php`

# If someone can figure out the nightmare of escaping all this stuff
# for sed I would gladly accept the patches (20120523/straup)

perl -p -i -e "s/GLOBALS\['cfg'\]\['crypto_cookie_secret'\] = ''/GLOBALS\['cfg'\]\['crypto_cookie_secret'\] = '${COOKIE_SECRET}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['crypto_crumb_secret'\] = ''/GLOBALS\['cfg'\]\['crypto_crumb_secret'\] = '${CRUMB_SECRET}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['crypto_password_secret'\] = ''/GLOBALS\['cfg'\]\['crypto_password_secret'\] = '${PASSWORD_SECRET}'/" ${SECRETS};
