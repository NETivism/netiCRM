#!/bin/bash

files='calendar civicrm-core civicrm-helpfiles civicrm-menu civicrm-modules drupal-civicrm'

branches=`dirname $0`/../branches
rels=`ls -1 $branches | grep '^v.\..$'`
latest=`ls -1 $branches | grep '^v.\..$' | sort | tail -1`
langs=`ls -1 $branches/$latest | grep '^.._..$'`

case "$1" in

  common)
    rel=$2
    echo -n "rebuilding common language files for $rel"
    for lang in $langs; do
      echo -n " $lang"
      msgcat --use-first --no-location $branches/$rel/$lang/civicrm-*.po $branches/$rel/$lang/{calendar,drupal-civicrm}.po > $branches/$rel/$lang.po
      msgattrib --no-obsolete --translated $branches/$rel/$lang.po | sponge $branches/$rel/$lang.po
    done
    echo
    test -x "`which beep`" && beep
  ;;

  monolithic)
    echo -n 'rebuilding monolithic files'
    for lang in $langs; do
      echo -n " $lang"
      find $branches/v?.? -name $lang.po | sort -r | xargs msgcat --use-first --no-location > $branches/$lang.po
      msgattrib --no-obsolete --translated $branches/$lang.po | sponge $branches/$lang.po
    done
    echo
    test -x "`which beep`" && beep
  ;;

  refresh)
    rel=$2
    echo "refreshing PO files for $rel"
    for file in $files; do
      for lang in $langs; do
        echo -n " – $file $lang"
        msgcat --use-first $branches/$rel/$lang/$file.po $branches/$lang.po | sponge $branches/$rel/$lang/$file.po
        msgmerge -U $branches/$rel/$lang/$file.po $branches/$rel/pot/$file.pot
        msgattrib --no-obsolete $branches/$rel/$lang/$file.po | sponge $branches/$rel/$lang/$file.po
      done
    done
    test -x "`which beep`" && beep
  ;;

  compile)
    rel=$2
    echo -n "recompiling MO files for $rel"
    for lang in $langs; do
      echo -n " $lang"
      msgcat --use-first $branches/$rel/$lang/civicrm-*.po $branches/$rel/$lang/{calendar,countries,provinces}.po | msgfmt -o $branches/$rel/$lang/civicrm.mo -
    done
    echo
    test -x "`which beep`" && beep
  ;;

  add-locale)
    rel=$2
    locale=$3
    echo "adding the $locale locale to $rel"
    pots=`for i in $branches/$rel/pot/*.pot; do basename $i .pot; done`
    mkdir $branches/$rel/$locale
    for pot in $pots; do
      msginit -i $branches/$rel/pot/$pot.pot -o $branches/$rel/$locale/$pot.po -l $locale --no-translator
    done
    test -x "`which beep`" && beep
  ;;

  *)
    echo '
      Usage:
      common {release}              – rebuild the common language files for a given release
      monolithic                    – rebuild the monolithic language files
      refresh {release}             – refresh the given release’s PO files (from the monolithic files)
      compile {release}             – compile the given release’s MO files
      add-locale {release} {locale} – add the given locale to the given release
    '
  ;;

esac
