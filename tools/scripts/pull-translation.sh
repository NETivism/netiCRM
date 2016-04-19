#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`
LANGUAGE='zh_TW'

cd $CIVICRMPATH
tx pull -f
if [ -d $CIVICRMPATH/l10n/$LANGUAGE ]; then
  if [ ! -d $CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES ]; then
    mkdir $CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES
  fi
  msgfmt $CIVICRMPATH/l10n/$LANGUAGE/civicrm.po -o $CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES/civicrm.mo
fi
