netiCRM 
==============

You can also read this in [繁體中文](./README.zh-tw.md).

<img src="https://img.shields.io/github/last-commit/NETivism/netiCRM">
<img alt="GitHub commit activity" src="https://img.shields.io/github/commit-activity/m/NETivism/netiCRM"> 

[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/NETivism/netiCRM/actions?query=branch%3Amaster+) **Master**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=hotfix)](https://github.com/NETivism/netiCRM/actions?query=branch%3Ahotfix) **Hotfix**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/NETivism/netiCRM/actions?query=branch%3Adevelop++) **Develop**


### Why Forked from CiviCRM
CiviCRM is a constituent relationship management system for non-profit organizations developed by civicrm.org in 2005. In any case, you should check the original [CiviCRM](http://civicrm.org) project first instead of this one.

Back in 2011, CiviCRM still use SVN for version control. But 3.3 to 3.4 have a lot of changes which made CiviCRM better. Since then, CiviCRM has grown very quickly and has many amazing functions developed and released. At that time, we understand the user base made CiviCRM grow too quick for us. And we don't have enough resources to test every function for NPOs in Taiwan.

We started "netiCRM" service in 2011 and forked from CiviCRM since version 3.3. "netiCRM" is a SaaS service in mind. This means we have a lot of infrastructure issues got to resolve(mailing, deployment, membership ... etc.). We had a small user base at that time. Because of lacking resources for building multi-language SaaS software, we decide to separate our development process and forked the original CiviCRM. Focus on delivering a stable application for NPOs and writing documents (in Traditional Chinese).

In the past 10+ years, "netiCRM" project also accomplish something in Taiwan:
- Nearly 300 NPOs daily uses.
- Improve a lot of functions. eg. referrer tracking report, billing method in Taiwan, QRCode event registration ... etc.
- Improve security. We hired a pro ethic hacking company for penetration tests.
- Upgrade to Drupal 9 (which we only can maintain Drupal-based CRM).
- Keep netiCRM open. **Open source of course**. And also has [online documentation](https://neticrm.tw/online-learning) licensed in CC, online videos.

### Who Build This
[netiCRM](https://neticrm.tw) made by [NETivism Co., Ltd](https://netivism.com.tw) in Taiwan.

### Roadmap and Vision of netiCRM
For the last decade, we were focus on development. Sometimes we forgot to share our knowledge and vision with the community. But we can do better in these areas:
- **Security** - online payment and personal data storage is big deal.
- **Accessibility** - software for NPOs should also be for more users.
- **Automation** - NPOs in these days have more and more workload. Automation of application may help them much more in facing the changing world.
- **Community** - community is the key to survival in the Open Source area, and CiviCRM is a good example. We could do better to cooperate with the community.
- **Scalability** - more and more large NPOs using netiCRM. We will try to improve performance and scalability in the core.

You can also check [milestones](https://github.com/NETivism/netiCRM/milestones) which will address our main focus by quarter.

## Release cycle

The version style is [semantic versioning](https://en.wikipedia.org/wiki/Software_versioning#Semantic_versioning) without leading v.
```
x.y.z
```

We have automated script release weekly base on the "hotfix" branch which will change the last digit "z".
We also have a highly active development "develop" branch for monthly or quarterly releases. It may change the second digit "y".
When major change. Such as supporting new PHP / drupal versions or backward in-compatibility releases. We will change the version number first digit "x".

## Security policy

We take the security of this project very seriously. If you believe you have found a security vulnerability, contact us through the [Security Policy](https://github.com/netivism/netiCRM/security/policy) page.

## Supported Versions

Only support latest release version.

### License

netiCRM is licensed under AGPL-3.0. See AGPL-3.0.txt.
Third-party packages licenses, please check packages/LICENSE

### How to Install

Follow steps in [INSTALL.md](./INSTALL.md).

### How to Contribute

Check [CONTRIBUTE.md](./CONTRIBUTE.md) for more information.
