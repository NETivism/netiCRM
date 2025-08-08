#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`
LANGUAGE='zh_TW'
MAJOR_VERSION='8.1'

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
  do_merge $TAG 7.x
  do_merge $TAG 10.x

  cd $CIVICRMPATH/drupal
  echo -e "\n###### netiCRM-drupal ######\n"
  do_merge $TAG 7.x
  do_merge $TAG 10.x

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
  git checkout ${VERSION_PREFIX}hotfix
  git pull
  if [ "$2" = "7.x" ] || [ -z "$2" ]; then
    if [ -z "$2" ]; then
      echo "Release note of $TAG:" > /tmp/release-note.txt
      git log $(git describe --tags --abbrev=0)..HEAD --pretty=format:"%h %s" >> /tmp/release-note.txt
      git tag -a $TAG -F /tmp/release-note.txt
    else
      git tag -a $TAG -m "Release $TAG"
    fi
  fi
  git checkout ${VERSION_PREFIX}master
  git fetch --all
  git reset --hard origin/${VERSION_PREFIX}master
  git merge ${VERSION_PREFIX}hotfix -m "Release merge."
  git commit
}
echo "Fetch latest translation ..."
if which tx > /dev/null; then
  cd $CIVICRMPATH/tool/scripts && ./pull-translation.sh
fi

echo "Git status checking ..."
cd $CIVICRMPATH
NEED_COMMIT=`git status --porcelain | grep "^ M"`

if [ -n "$NEED_COMMIT" ]; then
  git status --porcelain | grep "^ M"
  echo -e "\nPlease commit your code manually, abort.\n"
  exit 1;
else
  # do merge jobs
  echo -e "\nProcessing merge ...\n"
  neticrm_merge

  # commit civicrm-version.txt to latest tag
  git_tag=$(git describe --tags)
  IFS='-' read -ra parts <<< "$git_tag"
  git_tag="${parts[0]}+${parts[1]}"
  git_revision=$(git rev-parse --short HEAD)
  git_revision=$(echo $git_revision | tr -d '[:space:]')
  cd $CIVICRMPATH
  echo "${git_tag} (${git_revision})" > civicrm-version.txt
  git commit civicrm-version.txt -m "Update to lastest version tag of git"
  echo -e "\nDone!"
  cd $CIVICRMPATH && git status --porcelain
  echo -e "You can use command below for release this:\n  git push --tags\n"
fi
