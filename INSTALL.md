# Installation

## System requirement

Tested and supported environment:
- PHP 5.6, PHP 7.3, PHP 7.4, PHP 8.1
- Mariadb 10.3+

## Installation with Drupal 7.x

1\. First, install Drupal 7.
(Please refer install guide of Drupal 7)

2\. Clone repository to modules folder of Drupal.
(You can use following command under sites/all/modules folder.)
```
git clone git@github.com:NETivism/netiCRM.git civicrm
```
3\. Checkout the branch to master.
(You can use following command under civicrm folder.)
```
git checkout master
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
git checkout 7.x-master
cd drupal/
git checkout 7.x-master
```
6\. Go to the modules configuration page. You should see NetiCRM is available. Enable it and Press "Submit" button.

7\. Complete!!
