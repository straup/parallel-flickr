#!/bin/sh

${RECIPES}/postcrd.sh

echo "[${FILTER}] recompose"
${COMPOSITE} -compose multiply ${INPUT} ${NEW} ${NEW}

mv -f ${NEW} ${OUTPUT}

if [ ${DO_REPORT} -gt 0 ] 
then
    echo "[${FILTER}] generate report ${REPORT}"
    ${MONTAGE} -geometry ${W_REPORT}x${H_REPORT}+5+5 -tile 2x2 ${INPUT} ${MASK} ${LOMO} ${OUTPUT} ${REPORT}
fi
