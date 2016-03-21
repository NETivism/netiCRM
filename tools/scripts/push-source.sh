#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`

cd $CIVICRMPATH
cd tx pull -f
tx push -s
