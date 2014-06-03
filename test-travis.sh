#!/bin/bash

# casperjs
if [ !-d "casperjs" ]; then
  git clone git://github.com/n1k0/casperjs.git
  cd casperjs
  export PATH=$PATH:`pwd`/bin
  cd -
fi

# clear
cd ..
ln -s $(readlink -e $(cd -)) civicrm
mysql -ujimmy -pjimmy -e "drop database netivism_neticrm_build"
rm -Rf netivism_neticrm_build
kill $(ps aux | grep '/usr/bin/php' | awk '{print $2}')

# start
mysql -ujimmy -pjimmy -e 'create database netivism_neticrm_build CHARACTER SET utf8 COLLATE utf8_general_ci'
drush --yes core-quick-drupal --core=drupal-6.x --no-server --db-url=mysql://jimmy:jimmy@127.0.0.1/netivism_neticrm_build --account-pass=123456 --site-name=netiCRM --enable=simpletest,transliteration netivism_neticrm_build
ln -s $(readlink -e $(cd -)) netivism_neticrm_build/drupal-6.x/sites/all/modules/civicrm

# drupal
cd netivism_neticrm_build/drupal-6.x
chmod -R 777 sites/default/files
drush --yes pm-download simpletest
patch -p0 < sites/all/modules/simpletest/D6-core-simpletest.patch
drush --yes pm-enable civicrm simpletest
drush runserver 127.0.0.1:8000 &
until netstat -an 2>/dev/null | grep '8000.*LISTEN'; do true; done
drush test-run 'Travis-CI Drupal Module Example' --uri=http://127.0.0.1:8000
cd -

casperjs civicrm/test-casper.js
