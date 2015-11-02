#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`
LANGUAGE='zh_TW'
MAJOR_VERSION='2.0'

neticrm_merge(){
  TAG=`git tag | grep "^$MAJOR_VERSION" | awk -F "." '{print $3}' | sort -nr | head -n 1`
  if [ -z $TAG ]; then
    TAG="${MAJOR_VERSION}.0"
  else
    TAG=$((TAG+1))
    TAG="${MAJOR_VERSION}.${TAG}"
  fi
  cd $CIVICRMPATH/neticrm
  echo -e "\n###### netiCRM-neticrm ######\n"
  do_merge $TAG 6.x
  do_merge $TAG 7.x

  cd $CIVICRMPATH/drupal
  echo -e "\n###### netiCRM-drupal ######\n"
  do_merge $TAG 6.x
  do_merge $TAG 7.x

  echo -e "\n###### netiCRM ######\n"
  cd $CIVICRMPATH
  do_merge $TAG
}

do_merge(){
  TAG=$1
  if [ -n "$2" ]; then
    VERSION_PREFIX=${2}-
  else
    VERSION_PREFIX=""
  fi
  git checkout ${VERSION_PREFIX}develop
  git checkout ${VERSION_PREFIX}master
  git merge ${VERSION_PREFIX}develop -m "Release merge."
  git commit
  git tag -a $TAG -m "Release $TAG"
  git checkout ${VERSION_PREFIX}develop
}

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

# gen code for new translations 
read -p "  Generate civicrm DAO code(y/empty to skip): " GENCODE
if [ -d $CALLEDPATH/../../xml ] && [ -n "$GENCODE" ]; then
  cd $CALLEDPATH/../../xml
  php GenCode.php
fi

# verify if we have anything to commit
echo -e "Done.\n\n"

echo "Git status checking ..."
cd $CIVICRMPATH
git commit civicrm-version.txt -m "Update to lastest version tag of git"
NEED_COMMIT=`git status --porcelain | grep "^ M"`

if [ -n "$NEED_COMMIT" ]; then
  git status --porcelain | grep "^ M"
  echo -e "\nPlease commit your code manually, abort.\n"
  exit 1;
else
  # do merge jobs
  echo "We will begin merge process."
  read -p "Are you sure?  [Y/n]" -n 1 REPLY
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "\nUser abort."
    exit 1
  else
    echo -e "\nProcessing merge ...\n"
    neticrm_merge
    echo -e "\nDone!"
    cd $CIVICRMPATH && git status --porcelain && git tag
    echo -e "You can use command below for release this:\n  git push --tags\n"
  fi
fi
