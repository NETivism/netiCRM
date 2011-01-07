#!/usr/bin/env bash

CALLEDPATH=`dirname $0`
if [ -d $CALLEDPATH/../xml ]; then
  cd $CALLEDPATH/../xml
  "$PHP5PATH"php GenCode.php $SCHEMA
fi
