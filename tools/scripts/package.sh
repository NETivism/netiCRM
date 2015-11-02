VERSION=$1
OUT="/tmp/release"
FILE="neticrm-${VERSION}.tgz"

if [ -z "$1" ]; then
  echo "Usage: $0 2.0.0"  
  exit 1;
fi

if [ ! -d $OUT ]; then
  mkdir -p $OUT
fi

if [ -d $OUT/civicrm ]; then
  read -p "Directory $OUT/civicrm exists. Do you want to remove and clone lastest project?  [Y/n]" -n 1 REPLY
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "\nUsing exists directory to packaging...\n"
  else
    rm -Rf $OUT/civicrm
    cd $OUT
    echo "Download neticrm $VERSION"
    git clone https://github.com/NETivism/netiCRM.git civicrm
  fi
fi

if [ -d $OUT/civicrm ]; then
  echo -e "Checking out $VERSION"
  cd $OUT/civicrm
  git checkout $VERSION
  git submodule init && git submodule update
  rm -Rf xml
  rm -Rf tests/
  rm -Rf tools/
  rm -Rf l10n/bin
  rm -Rf l10n/pot
  find . -type d -exec chmod 755 {} \;
  find . -type f -exec chmod 644 {} \;
  cd $OUT
fi

cd $OUT
echo "Packaging neticrm $VERSION"
tar -zcf $FILE civicrm --exclude .git --exclude .git* --exclude .travis.yml
rm -Rf $OUT/civicrm
echo -e "Release package at:\n$OUT/$FILE"
