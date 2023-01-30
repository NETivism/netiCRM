netiCRM 
==============

You can also read this in [English](./README.md).

<img src="https://img.shields.io/github/last-commit/NETivism/netiCRM">
<img alt="GitHub commit activity" src="https://img.shields.io/github/commit-activity/m/NETivism/netiCRM"> 

[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/NETivism/netiCRM/actions?query=branch%3Amaster+) **Master**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=hotfix)](https://github.com/NETivism/netiCRM/actions?query=branch%3Ahotfix) **Hotfix**
[![Build Status](https://github.com/NETivism/netiCRM/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/NETivism/netiCRM/actions?query=branch%3Adevelop++) **Develop**

### 為什麼從 CiviCRM 分支？
2005年 civicrm.org 推出了 CiviCRM ——專為非營利組織設計的支持者關係管理系統，在大多情況下，您應該先參考 [CiviCRM](http://civicrm.org) 專案而非此專案，但本專案還是持續維護中。
 
回到 2011 年，CiviCRM 仍在使用 SVN 來進行版本控制，版本 3.3 更新到 3.4 時做了大幅改善，從那之後 CiviCRM 開發的迭代進展非常快，也新增了不少功能。那時候的我們面對這樣的成長速度，還沒有足夠資源去測試所有新發展都適合臺灣的 NPO。所以 2011 年時我們決定用版本 3.3 的 CiviCRM 來作為 netiCRM 的服務根基。
 
netiCRM 是一個雲端軟體即服務（SaaS），所以有很多系統的基礎問題需要解決，比如寄信、佈署、訂閱會員等，那時我們用戶很少，也沒有資源可以做跨國、多種語言的 SaaS 軟體，所以我們決定先把開發流程分開，導入原始的 CiviCRM，專注於提供 NPO 穩定的系統，還有撰寫繁體中文的使用手冊。

過去十幾年來，netiCRM 在台灣有以下成果：
- 超過300間 NPO 使用
- 改善多種功能，例如流量來源追蹤、符合台灣法規的公版收據、QRCode 活動簽到等
- 強化資安，並請專業白帽駭客公司做反滲透測試
- 後台升級至 Drupal 9（目前僅支援 Drupal 為基礎的 CRM）
- 維持開源精神，例如 [線上文件](https://neticrm.tw/online-learning) （創用 CC 授權）、線上影片


### 誰創建了 netiCRM？
[netiCRM](https://neticrm.tw) 由網絡行動科技股份有限公司 [NETivism Co., Ltd](https://netivism.com.tw) 提供。


### netiCRM 的未來藍圖
過去十年我們都專注在開發，接下來我們希望在 Github 社群分享更多我們的知識和願景！
- **資安**：我們很重視線上金流、個人資料貯存的安全性
- **無障礙**：NPO專屬的軟體應該可以為了更多族群的使用者設計
- **自動化**：NPO工作量愈來愈多，系統自動化能幫助NPO將更多精力聚焦在改善世界
- **社群**：社群是幫助開源軟體生存很重要的關鍵，CiviCRM 就是很好的例子，我們也將精進社群間的合作
- **可擴充性**：愈來愈多大型NPO開始使用 netiCRM，我們也會試著加強系統性能和擴充能力
 
有興趣的話歡迎看看我們每季度的里程碑 [milestones](https://github.com/NETivism/netiCRM/milestones) 。


## 版本發布週期
我們遵循 [semantic versioning](https://en.wikipedia.org/wiki/Software_versioning#Semantic_versioning)，舉例來說，如果版本編號為：
```
x.y.z
```
則每個字母代表不同程度的版本更新，數字遞增規則如下：
x：大型修改，像是新的php、drupal版本，或向下不相容的更新。
y：每月或每季的功能開發更新（"develop" branch）。
z：每週例行發布的修復功能更新（"hotfix" branch）。


## 資安政策
我們非常認真看待資安防護，如果你發現任何關於 netiCRM 的漏洞，請不吝在資安頁面 [Security Policy](https://github.com/netivism/netiCRM/security/policy) 聯繫我們。


### 如何安裝？
可參考安裝文件： [INSTALL.md](./INSTALL.md)


### 你能為 netiCRM 做些什麼？
如果你想協助 netiCRM 變得更好，可以參考這份文件： [CONTRIBUTE.md](./CONTRIBUTE.md)，謝謝你的付出！
