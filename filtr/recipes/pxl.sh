#!/bin/bash

echo "[${FILTER}] triangulize ${INPUT} (${OUTPUT})"

# to do: check that INPUT is a jpeg before setting -f
# to do: adjust -t based on the size of the photo...

${PYTHON} ${UTILS}/triangulizor.py -t 24 -f JPEG ${INPUT} ${OUTPUT}

