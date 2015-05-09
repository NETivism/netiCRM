Before testing, you need install following package:

- phpunit
- casperjs

# Unit Test

```
cd civicrm
mysql -udbuser -p -e "CREATE DATABASE civicrm_tests_dev CHARACTER SET utf8 COLLATE utf8_unicode_ci";
mysql -udbuser -p civicrm_tests_dev < sql/civicrm.mysql
mysql -udbuser -p civicrm_tests_dev < sql/civicrm_generated.mysql
export CIVICRM_TEST_DSN=mysql://dbuser:password@localhost/civicrm_tests_dev

cd tests/phpunit
phpunit --colors api_v3_ContactTest
phpunit --colors api_v3_AllTests
```

# Casperjs

Make sure you have running website
```
export RUNPORT=80

cd tests/casperjs
casperjs test googletest.js
casperjs test pagespages.js

```
