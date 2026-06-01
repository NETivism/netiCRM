#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`

# Push translations to Transifex
echo "=== Pushing translations to Transifex ==="
cd $CIVICRMPATH
tx push -t
