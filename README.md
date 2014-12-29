netiCRM [![Build Status](https://travis-ci.org/NETivism/netiCRM.svg?branch=develop)](https://travis-ci.org/NETivism/netiCRM)
==============

1. Why netiCRM
--------------
CiviCRM is a constituent relationship management system for non-profit organization.
Read more at README.txt or http://civicrm.org for more infomation.

netiCRM derivative from CiviCRM since CiviCRM 3.3, we create another branch because the non-profit user in Taiwan need better support of multi-language environment, such as Chinese and Asia character support, translations, and stability of CiviCRM.


2. Who build this
--------------
netiCRM builded by NETivism from Taiwan.
NETivism also support CiviCRM community in Taiwan on http://CiviCRM.tw .


3. License
--------------
License is under AGPL-3.0. See agpl-3.0.txt.


4. Bug report
--------------
Please file an issue on github.
https://github.com/NETivism/netiCRM/issues


5. Installation
--------------
1. First, install Drupal 7.
(Please refer install guide of Drupal 7)
2. Clone repository to modules folder of Drupal.
(You can use following command under all/modules folder.)
```
git clone git@github.com:NETivism/netiCRM.git civicrm
```
3. Checkout the branch to 2.0-dev.
(You can use following command under civicrm folder.)
```
git checkout 2.0-dev
```
4. Enable submodules and update them.
(You can use following command under civicrm folder.)
```
git submodule init
git submodule update
```
5. Checkout submodules to the branch for Drupal 7.
(You can use following command under civicrm folder.)
```
cd neticrm/
git checkout 7.x-develop
cd drupal/
git checkout 7.x-develop
```
6. Go to the modules configuration page. You should see NetiCRM is available. Enable it and Press "Submit" button.
7. Complete!!


6. User Guide
--------------
See https://neticrm.tw/resources


7. Developer Resources
--------------
For netiCRM, you can read document:
https://neticrm.tw/about/developer


8. Translation
--------------
We will merge string into CiviCRM regularly.
For now if you would like to join translation project, you may see this public translation page:
https://www.transifex.com/projects/p/neticrm/


9. When to use CiviCRM/ not netiCRM
-----------------------------------
- Function of CiviCRM is more completed on the functions. Move forward more quickly, more developers.
- Function of netiCRM is less function, but bug are also less especially on Event, Contribution, Membership compoment.
- For world-wild usage, you should consider use CiviCRM not netiCRM.
- More info of CiviCRM you can watch github organization CiviCRM http://github.com/civicrm/ .

