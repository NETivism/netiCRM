#!/bin/bash

[ "$1" == "" ] && echo 'source dir missing'     && exit 1
test ! -e "$1" && echo 'source does not exist'  && exit 1
test ! -d "$1" && echo 'source not a directory' && exit 1

[ "$2" == "" ] && echo 'target dir missing'     && exit 1
test ! -e "$2" && echo 'target does not exist'  && exit 1
test ! -d "$2" && echo 'target not a directory' && exit 1

root="$1"
potdir="$2"

header=`tempfile`
now=`date +'%F %R%z'`
sed "s/NOW/$now/" bin/header > $header

# $componets are the dirs with strings generating per-component civicrm-$component.po files
components=`ls -1 $root/CRM $root/templates/CRM | grep -v :$ | grep -v ^$ | grep -viFf bin/basedirs | sort -u | xargs | tr [A-Z] [a-z]`

# build the three XML-originating files
echo ' * building civcrm-menu.pot'
cp $header $potdir/civicrm-menu.pot
grep -h '<title>' $root/CRM/*/xml/Menu/*.xml | cut -b13- | cut -d'<' -f1 | sort | uniq | tail --lines=+2 | while read entry; do echo -e "msgctxt \"menu\"\nmsgid \"$entry\"\nmsgstr \"\"\n"; done >> $potdir/civicrm-menu.pot
echo ' * building countries.pot'
cp $header $potdir/countries.pot
grep ^INSERT $root/xml/templates/civicrm_country.tpl     | cut -d\" -f4                                  | while read entry; do echo -e "msgctxt \"country\"\nmsgid \"$entry\"\nmsgstr \"\"\n"; done >> $potdir/countries.pot
echo ' * building provinces.pot'
cp $header $potdir/provinces.pot
grep '^(' $root/xml/templates/civicrm_state_province.tpl | cut -d\" -f4                                  | while read entry; do echo -e "msgctxt \"province\"\nmsgid \"$entry\"\nmsgstr \"\"\n"; done >> $potdir/provinces.pot

# make sure none of the province names repeat
# FIXME: add country name to context and skip this to do the Right Thing™
msguniq $potdir/provinces.pot | sponge $potdir/provinces.pot

# create base POT file
echo ' * building civicrm-base.pot'
cp $header $potdir/civicrm-base.pot
`dirname $0`/extractor.php base $root >> $potdir/civicrm-base.pot

# create component POT files
for comp in $components; do
  echo ' * building civicrm-'$comp'.pot'
  cp $header $potdir/civicrm-$comp.pot
  `dirname $0`/extractor.php $comp $root >> $potdir/civicrm-$comp.pot
  # drop strings already present in civicrm-base.pot
  common=`tempfile`
  msgcomm $potdir/civicrm-$comp.pot $potdir/civicrm-base.pot > $common
  msgcomm -u $potdir/civicrm-$comp.pot $common | sponge $potdir/civicrm-$comp.pot
  rm $common
done

# create civicrm-components.pot with strings common to all components (but not base)
paths=''
for comp in $components; do
  paths="$paths $potdir/civicrm-$comp.pot"
done
echo ' * building civicrm-components.pot'
msgcomm $paths > $potdir/civicrm-components.pot

# drop strings in civicrm-components.pot from component POT files
for comp in $components; do
  common=`tempfile`
  msgcomm $potdir/civicrm-$comp.pot $potdir/civicrm-components.pot > $common
  msgcomm -u $potdir/civicrm-$comp.pot $common | sponge $potdir/civicrm-$comp.pot
  rm $common
done

# drop empty files
find $potdir -empty -exec rm {} \;

# create drupal-civicrm.pot
echo ' * building drupal-civicrm.pot'
cp $header $potdir/drupal-civicrm.pot
find $root/drupal -name '*.inc' -or -name '*.install' -or -name '*.module' -or -name '*.php' | xargs `dirname $0`/php-extractor.php $root $root/CRM/Core/Permission.php >> $potdir/drupal-civicrm.pot

rm $header
