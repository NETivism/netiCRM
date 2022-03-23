# Installation


1\. First, install Drupal 7.
(Please refer install guide of Drupal 7)

2\. Clone repository to modules folder of Drupal.
(You can use following command under all/modules folder.)
```
git clone git@github.com:NETivism/netiCRM.git civicrm
```
3\. Checkout the branch to 2.0-dev.
(You can use following command under civicrm folder.)
```
git checkout 2.0-dev
```
4\. Enable submodules and update them.
(You can use following command under civicrm folder.)
```
git submodule init
git submodule update
```
5\. Checkout submodules to the branch for Drupal 7.
(You can use following command under civicrm folder.)
```
cd neticrm/
git checkout 7.x-develop
cd drupal/
git checkout 7.x-develop
```
6\. Go to the modules configuration page. You should see NetiCRM is available. Enable it and Press "Submit" button.

7\. Complete!!