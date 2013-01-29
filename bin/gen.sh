#!/usr/bin/env bash
SCHEMA=schema/Schema.xml

CALLEDPATH=`dirname $0`
LANGUAGE='zh_TW'
if [ -d $CALLEDPATH/../l10n/$LANGUAGE ]; then
  if [ ! -d $CALLEDPATH/../l10n/$LANGUAGE/LC_MESSAGES ]; then
    mkdir $CALLEDPATH/../l10n/$LANGUAGE/LC_MESSAGES
  fi
  msgfmt $CALLEDPATH/../l10n/$LANGUAGE/civicrm.po -o $CALLEDPATH/../l10n/$LANGUAGE/LC_MESSAGES/civicrm.mo
fi

if [ ! -d $CALLEDPATH/../drupal/translations ]; then
  mkdir $CALLEDPATH/../drupal/translations
fi
cp -f $CALLEDPATH/../l10n/$LANGUAGE/civicrm.po $CALLEDPATH/../drupal/translations/zh-hant.po

if [ -d $CALLEDPATH/../xml ]; then
  cd $CALLEDPATH/../xml
  php GenCode.php $SCHEMA
fi

