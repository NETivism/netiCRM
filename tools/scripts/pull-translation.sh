#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`
LANGUAGE='zh_TW'

cd $CIVICRMPATH
tx pull -f
if [ -d $CALLEDPATH/../../l10n/$LANGUAGE ]; then
  if [ ! -d $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES ]; then
    mkdir $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES
  fi
  msgfmt $CALLEDPATH/../../l10n/$LANGUAGE/civicrm.po -o $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES/civicrm.mo
fi
