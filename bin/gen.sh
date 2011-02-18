#!/usr/bin/env bash

CALLEDPATH=`dirname $0`
if [ -d $CALLEDPATH/../xml ]; then
  cd $CALLEDPATH/../xml
  php GenCode.php schema/Schema.xml 3.3.1 drupal $1 
fi
