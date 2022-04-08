netiCRM 
==============

<img src="https://img.shields.io/github/last-commit/NETivism/netiCRM">
<img alt="GitHub commit activity" src="https://img.shields.io/github/commit-activity/m/NETivism/netiCRM"> 

[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/NETivism/netiCRM/actions?query=branch%3Amaster+) **Master**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=hotfix)](https://github.com/NETivism/netiCRM/actions?query=branch%3Ahotfix) **Hotfix**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/NETivism/netiCRM/actions?query=branch%3Adevelop++) **Develop**

### Why Forked from CiviCRM
CiviCRM is a constituent relationship management system for non-profit organization developed by civicrm.org from 2005. In any case, you should check original [CiviCRM](http://civicrm.org) project first instead of this one.

Back to 2011, CiviCRM still use SVN for version control. But 3.3 to 3.4 have a lots of changes which made CiviCRM better. Since then, CiviCRM grown very quickly, and have many amazing functions developed and releases. At that time, we understand the user base make CiviCRM grown too quick for us. And we don't have enough resources to test every functions for NPOs in Taiwan.

We started "netiCRM" service from 2011 and forked from CiviCRM since version 3.3. "netiCRM" is a SaaS service in mind. Which means we have a lots of infrastructure issues got to resolve(mailing, deploy, membership ... etc.). We had small user base that time. Because of lacking resources for building multi-language SaaS software, we decide to separate our development process and forked original CiviCRM. Focus on deliver stable application for NPOs and writing document (in Traditional Chinese).

In past 10+ years, "netiCRM" project also accomplish something in Taiwan:
- Nearly 300 NPOs daily uses.
- Improve a lots of functions. eg. referrer tracking report, billing method in Taiwan, QRCode event registration ... etc.
- Improve security. We hired pro ethic hacking company for penetration test.
- Upgrade to Drupal 9 (which we only have capacity to maintain Drupal based CRM).
- Keep netiCRM open. **Open source of course**. And also has [online documentation](https://neticrm.tw/online-learning) licensed in CC, online videos.

### Who Build This
[netiCRM](https://neticrm.tw) made by [NETivism Co., Ltd](https://netivism.com.tw) in Taiwan.

### Roadmap and Vision of netiCRM
For last decade, we were focus on development. Sometimes we forgot share our knowledge and vision to community. But we can do better on these area:
- **Security** - online payment and personal data storage is big deal.
- **Accessibility** - software for NPOs should also for more users.
- **Automation** - NPOs in these day have more and more workload. Automation by application may help them much more on facing the changing world.
- **Community** - community is the key to survive in Open Source area, and CiviCRM is good example. We could do better to cooperate with community.
- **Scalability** - more and more large NPOs using netiCRM. We will try to improve performance and scalability in core.

You can also check [milestones](https://github.com/NETivism/netiCRM/milestones) which will address our main focus by quarter.

## Release cycle

The version style is [semantic versioning](https://en.wikipedia.org/wiki/Software_versioning#Semantic_versioning) without leading v.
```
x.y.z
```

We have automatic script release weekly base on "hotfix" branch which will change last digit "z".
We also have highly active development "develop" branch for monthly or quarterly release. It may change second digit "y".
When major change. Such as support new php / drupal version or backward in-compatibility release. We will change version number first digit "x".

## Security policy

We take security of this project very seriously. If you believe you have found a security vulnerability, contact us through [Security Policy](https://github.com/netivism/netiCRM/security/policy) page.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 3.3.x   | :white_check_mark: |
| < 3.3   | :x:                |

### License

License is under AGPL-3.0. See agpl-3.0.txt.
Third-party packages licenses, please check packages/LICENSE

### How to Install

Follow steps in [INSTALL.md](./INSTALL.md).

### How to Contribute

Check [CONTRIBUTE.md](./CONTRIBUTE.md) for more information.