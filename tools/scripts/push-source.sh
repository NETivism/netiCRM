#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`

cd $CIVICRMPATH
tx push -s
