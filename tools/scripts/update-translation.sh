#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`
LANGUAGE='zh_TW'

# auto fetch transifex translations
if [ -d $CALLEDPATH/../../l10n/$LANGUAGE ]; then
  if [ ! -d $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES ]; then
    mkdir $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES
  fi
  echo "Fetch lastest translation from transifex:"
  read -p "  Enter transifex username (empty to skip): " TRANSIFEXUSER
  read -p "  Enter transifex password (empty to skip): " TRANSIFEXPASS
  cd $CALLEDPATH/../../l10n/$LANGUAGE/
  if [ -n "$TRANSIFEXUSER" ] && [ -n "$TRANSIFEXPASS" ]; then
    curl -L --user $TRANSIFEXUSER:$TRANSIFEXPASS -X GET "https://www.transifex.com/api/2/project/neticrm/resource/neticrmpot/translation/$LANGUAGE/?mode=default&file" -o civicrm.po
  fi
  msgfmt $CALLEDPATH/../../l10n/$LANGUAGE/civicrm.po -o $CALLEDPATH/../../l10n/$LANGUAGE/LC_MESSAGES/civicrm.mo
fi
