# Contribute this project
## Translate
We only maintain Traditional Chinese translations. Follow the CiviCRM community experience, we also build a [Transifex Project here](https://www.transifex.com/projects/p/neticrm/).

Welcome to join us by "request button" right top of Transifex project page. Please address who you are and NPO belongs to. We will review your request.
Keep in mind, the modification will effect a lots of users in daily uses. We may reject your contribution if not appropriate.

We also merge string into CiviCRM regularly. Your contribution may also benefit to original CiviCRM project.

## Bug Report
Please file an issue on github in English.
https://github.com/NETivism/netiCRM/issues
## Development

Stability is our top priority. If you would like to add bunch of features by pull request. This project may not merge your effort.

Although the consideration is saying strictly. We are very welcome that you can contribute some ideas by drafting a pull request or feature request. 

### 1. Fixes bugs

As above saying, we are working hard on solving issues of this project. Especially bugs.

You can help us to achieve this by follow steps:
1. Register your github account
2. Fork this project to your account
3. Under your personal forked project, create a new branch base on "hotfix" branch. The naming of your branch should have issue number on [issue tracking list here](https://github.com/NETivism/netiCRM/issues). eg. number 23 issue, your branch name should be "23-mimeissuefix".
4. Writing your code.
5. Drafting a pull request.  [Tutorial here](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/about-pull-requests#draft-pull-requests)
6. Once we reviewed your code, we will ask you submit a normal pull request. Which we already reviewed and should be merge very quickly.

Beware, submit a formal pull request means you agree your code will license under AGPL v3.0. It's the same as this project.

### 2. Testing or CI

We integrated headless browser testing and unit testing for some of the workflow.
We are currently migrate our CI testing library from phantomjs based to chromium base. Will update detail process after the migration.

#### 2.1 How to record CI testing script

*TBD*

#### 2.2 How to submit your testing script

*TBD*

## 3. Building development environment

In theory, you can install Drupal, then [install civicrm as module](./INSTALL.md) as well.
But for better testing all the functions of netiCRM and support continuous integration, we have docker image to help this.

This is an quick guide for running a development environment listen on port 8888.
**You should never run this image as production.**
```bash
#!/bin/bash
WORKDIR=`pwd`

docker run -d \
  --name neticrm-ci-php7 \
  -p 8888:8080 \
  -v /etc/localtime:/etc/localtime:ro \
  -v $WORKDIR/container/init.sh:/init.sh \
  -v $WORKDIR/civicrm:/mnt/neticrm-7/civicrm \
  -e "TZ=Asia/Taipei" \
  -e "RUNPORT=8080" \
  -e "DRUPAL_ROOT=/var/www/html" \
  -e "CIVICRM_TEST_DSN=mysqli://root@localhost/neticrmci" \
  netivism/neticrm-ci:drone-php7

docker exec neticrm-ci-php7 /init.sh
```
**You should never run this image as production.** This docker image is for CI testing and development only. 

### 3.1 Better understand the environment

*TBD*
