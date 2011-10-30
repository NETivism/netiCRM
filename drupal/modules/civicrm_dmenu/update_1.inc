<?php
global $menu_links;
$menu_links['navigation'] = array (
  0 => 
  array (
    'path' => 'civicrm',
    'query' => NULL,
    'title' => 'CiviCRM',
    'menu' => 'navigation',
    'weight' => '0',
    'module' => 'menu',
    'hidden' => '1',
  ),
  1 => 
  array (
    'path' => 'civicrm/dashboard',
    'query' => NULL,
    'title' => 'CiviCRM',
    'menu' => 'navigation',
    'weight' => '-50',
    'module' => 'menu',
    'hidden' => '1',
  ),
  2 => 
  array (
    'path' => 'http://neticrm.tw/support',
    'query' => NULL,
    'title' => '線上支援',
    'menu' => 'navigation',
    'weight' => '-44',
    'module' => 'menu',
    'hidden' => '0',
  ),
  3 => 
  array (
    'path' => 'admin',
    'query' => NULL,
    'title' => '網站管理',
    'menu' => 'navigation',
    'weight' => '-45',
    'module' => 'menu',
    'hidden' => '0',
    'children' => 
    array (
      0 => 
      array (
        'path' => 'admin/content',
        'query' => NULL,
        'title' => 'Content management',
        'menu' => 'navigation',
        'weight' => '-10',
        'module' => 'menu',
        'hidden' => '0',
        'children' => 
        array (
          0 => 
          array (
            'path' => 'admin/content/node',
            'query' => NULL,
            'title' => 'Content',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
          1 => 
          array (
            'path' => 'admin/content/types',
            'query' => NULL,
            'title' => 'Content types',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
          2 => 
          array (
            'path' => 'admin/content/node-settings',
            'query' => NULL,
            'title' => 'Post settings',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
            'children' => 
            array (
              0 => 
              array (
                'path' => 'admin/content/node-settings/rebuild',
                'query' => NULL,
                'title' => 'Rebuild permissions',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
            ),
          ),
          3 => 
          array (
            'path' => 'admin/content/rss-publishing',
            'query' => NULL,
            'title' => 'RSS publishing',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
          4 => 
          array (
            'path' => 'admin/content/taxonomy',
            'query' => NULL,
            'title' => 'Taxonomy',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
            'children' => 
            array (
              0 => 
              array (
                'path' => 'admin/content/taxonomy/%',
                'query' => NULL,
                'title' => 'List terms',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
              1 => 
              array (
                'path' => 'admin/content/taxonomy/edit/term',
                'query' => NULL,
                'title' => 'Edit term',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
              2 => 
              array (
                'path' => 'admin/content/taxonomy/edit/vocabulary/%',
                'query' => NULL,
                'title' => 'Edit vocabulary',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
            ),
          ),
          5 => 
          array (
            'path' => 'admin/content/node-type/page',
            'query' => NULL,
            'title' => '頁面',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
        ),
      ),
      1 => 
      array (
        'path' => 'admin/reports',
        'query' => NULL,
        'title' => 'Reports',
        'menu' => 'navigation',
        'weight' => '5',
        'module' => 'menu',
        'hidden' => '0',
        'children' => 
        array (
          0 => 
          array (
            'path' => 'admin/reports/status',
            'query' => NULL,
            'title' => 'Status report',
            'menu' => 'navigation',
            'weight' => '10',
            'module' => 'menu',
            'hidden' => '0',
            'children' => 
            array (
              0 => 
              array (
                'path' => 'admin/reports/status/php',
                'query' => NULL,
                'title' => 'PHP',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
              1 => 
              array (
                'path' => 'admin/reports/status/run-cron',
                'query' => NULL,
                'title' => 'Run cron',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
              2 => 
              array (
                'path' => 'admin/reports/status/sql',
                'query' => NULL,
                'title' => 'SQL',
                'menu' => 'navigation',
                'weight' => '0',
                'module' => 'menu',
                'hidden' => '0',
              ),
            ),
          ),
          1 => 
          array (
            'path' => 'admin/reports/dblog',
            'query' => NULL,
            'title' => 'Recent log entries',
            'menu' => 'navigation',
            'weight' => '-1',
            'module' => 'menu',
            'hidden' => '0',
          ),
          2 => 
          array (
            'path' => 'admin/reports/access-denied',
            'query' => NULL,
            'title' => 'Top \'access denied\' errors',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
          3 => 
          array (
            'path' => 'admin/reports/page-not-found',
            'query' => NULL,
            'title' => 'Top \'page not found\' errors',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
          4 => 
          array (
            'path' => 'admin/reports/event/%',
            'query' => NULL,
            'title' => 'Details',
            'menu' => 'navigation',
            'weight' => '0',
            'module' => 'menu',
            'hidden' => '0',
          ),
        ),
      ),
      2 => 
      array (
        'path' => 'admin/build',
        'query' => NULL,
        'title' => 'Site building',
        'menu' => 'navigation',
        'weight' => '-10',
        'module' => 'menu',
        'hidden' => '0',
        'children' => 
        array (
          0 => 
          array (
            'path' => 'civicrm/admin',
            'query' => NULL,
            'title' => '支持者系統管理',
            'description' => '進階設定、管理支持者系統的欄位選項等',
            'menu' => 'navigation',
            'weight' => '-50',
            'module' => 'menu',
            'hidden' => '0',
            'children' => 
            array (
              0 => 
              array (
                'path' => 'civicrm/admin',
                'query' => NULL,
                'title' => '設定',
                'menu' => 'navigation',
                'weight' => '-50',
                'module' => 'menu',
                'hidden' => '0',
              ),
            ),
          ),
        ),
      ),
      3 => 
      array (
        'path' => 'admin/user',
        'query' => NULL,
        'title' => 'User management',
        'menu' => 'navigation',
        'weight' => '0',
        'module' => 'menu',
        'hidden' => '0',
        'children' => 
        array (
          0 => 
          array (
            'path' => 'civicrm/admin/access',
            'query' => 'reset=1',
            'title' => '存取控制',
            'description' => '管理支持者系統的進階權限控制',
            'menu' => 'navigation',
            'weight' => '1',
            'module' => 'menu',
            'hidden' => '0',
          ),
          1 => 
          array (
            'path' => 'civicrm/admin/synchUser',
            'query' => 'reset=1',
            'title' => '同步使用者到聯絡人',
            'description' => '將現有已註冊的使用者，同步成為聯絡人，重複的聯絡人將合併（以Email判斷）',
            'menu' => 'navigation',
            'weight' => '2',
            'module' => 'menu',
            'hidden' => '0',
          ),
        ),
      ),
    ),
  ),
);

$menu_links['primary-links'] =array (
  0 => 
  array (
    'path' => 'civicrm/contact/search',
    'query' => 'reset=1',
    'title' => '聯絡人',
    'menu' => 'primary-links',
    'weight' => '-49',
    'module' => 'menu',
    'hidden' => '0',
    'expanded' => 1,
    'children' => 
    array (
      0 => 
      array (
        'path' => 'admin/user/user',
        'title' => '新增/管理網站使用者',
        'menu' => 'primary-links',
        'weight' => '-51',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/contact/add',
        'query' => 'reset=1&ct=Household',
        'title' => '新增家庭',
        'menu' => 'primary-links',
        'weight' => '-49',
        'module' => 'menu',
        'hidden' => '0',
      ),
      2 => 
      array (
        'path' => 'civicrm/contact/add',
        'query' => 'reset=1&ct=Individual',
        'title' => '新增個人',
        'menu' => 'primary-links',
        'weight' => '-50',
        'module' => 'menu',
        'hidden' => '0',
      ),
      3 => 
      array (
        'path' => 'civicrm/contact/add',
        'query' => 'reset=1&ct=Organization',
        'title' => '新增組織',
        'menu' => 'primary-links',
        'weight' => '-48',
        'module' => 'menu',
        'hidden' => '0',
      ),
      4 => 
      array (
        'path' => 'civicrm/activity',
        'query' => 'reset=1&action=add&context=standalone',
        'title' => '新增任務',
        'menu' => 'primary-links',
        'weight' => '-47',
        'module' => 'menu',
        'hidden' => '0',
      ),
      5 => 
      array (
        'path' => 'civicrm/activity/add',
        'query' => 'atype=3&action=add&reset=1&context=standalone',
        'title' => '新增 Email',
        'menu' => 'primary-links',
        'weight' => '-46',
        'module' => 'menu',
        'hidden' => '0',
      ),
      6 => 
      array (
        'path' => 'civicrm/import/contact',
        'query' => 'reset=1',
        'title' => '匯入聯絡人資料',
        'menu' => 'primary-links',
        'weight' => '-45',
        'module' => 'menu',
        'hidden' => '0',
      ),
      7 => 
      array (
        'path' => 'civicrm/import/activity',
        'query' => 'reset=1',
        'title' => '匯入任務記錄',
        'menu' => 'primary-links',
        'weight' => '-44',
        'module' => 'menu',
        'hidden' => '0',
      ),
      8 => 
      array (
        'path' => 'civicrm/group/add',
        'query' => 'reset=1',
        'title' => '新增群組',
        'menu' => 'primary-links',
        'weight' => '-43',
        'module' => 'menu',
        'hidden' => '0',
      ),
      9 => 
      array (
        'path' => 'civicrm/group',
        'query' => 'reset=1',
        'title' => '管理群組',
        'menu' => 'primary-links',
        'weight' => '-42',
        'module' => 'menu',
        'hidden' => '0',
      ),
      10 => 
      array (
        'path' => 'civicrm/admin/tag',
        'query' => 'reset=1&action=add',
        'title' => '新增Tag',
        'menu' => 'primary-links',
        'weight' => '-41',
        'module' => 'menu',
        'hidden' => '0',
      ),
      11 => 
      array (
        'path' => 'civicrm/admin/tag',
        'query' => 'reset=1',
        'title' => '管理標籤（分類）',
        'menu' => 'primary-links',
        'weight' => '-40',
        'module' => 'menu',
        'hidden' => '0',
      ),
      12 => 
      array (
        'path' => 'civicrm/contact/deduperules',
        'query' => 'reset=1',
        'title' => '合併重複聯絡人',
        'menu' => 'primary-links',
        'weight' => '-38',
        'module' => 'menu',
        'hidden' => '0',
      ),
      13 => 
      array (
        'path' => 'civicrm/admin/reltype',
        'query' => 'reset=1',
        'title' => '新增/管理關係類型',
        'menu' => 'primary-links',
        'weight' => '-39',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
  1 => 
  array (
    'path' => 'civicrm/contribute',
    'query' => 'reset=1',
    'title' => '捐款',
    'menu' => 'primary-links',
    'weight' => '-48',
    'module' => 'menu',
    'hidden' => '0',
    'expanded' => 1,
    'children' => 
    array (
      0 => 
      array (
        'path' => 'civicrm/admin/options/payment_instrument',
        'query' => 'group=payment_instrument&reset=1',
        'title' => '新增/管理付款工具',
        'menu' => 'primary-links',
        'weight' => '-36',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/contribute/add',
        'query' => 'reset=1&action=add&context=standalone',
        'title' => '新增捐款',
        'menu' => 'primary-links',
        'weight' => '-50',
        'module' => 'menu',
        'hidden' => '0',
      ),
      2 => 
      array (
        'path' => 'civicrm/contribute/search',
        'query' => 'reset=1',
        'title' => '搜尋捐款',
        'menu' => 'primary-links',
        'weight' => '-49',
        'module' => 'menu',
        'hidden' => '0',
      ),
      3 => 
      array (
        'path' => 'civicrm/contribute/import',
        'query' => 'reset=1',
        'title' => '匯入捐款資料',
        'menu' => 'primary-links',
        'weight' => '-48',
        'module' => 'menu',
        'hidden' => '0',
      ),
      4 => 
      array (
        'path' => 'civicrm/pledge',
        'query' => 'reset=1',
        'title' => '認捐總覽',
        'menu' => 'primary-links',
        'weight' => '-47',
        'module' => 'menu',
        'hidden' => '0',
      ),
      5 => 
      array (
        'path' => 'civicrm/pledge/add',
        'query' => 'reset=1&action=add&context=standalone',
        'title' => '新增認捐',
        'menu' => 'primary-links',
        'weight' => '-46',
        'module' => 'menu',
        'hidden' => '0',
      ),
      6 => 
      array (
        'path' => 'civicrm/pledge/search',
        'query' => 'reset=1',
        'title' => '搜尋認捐',
        'menu' => 'primary-links',
        'weight' => '-45',
        'module' => 'menu',
        'hidden' => '0',
      ),
      7 => 
      array (
        'path' => 'civicrm/admin/contribute/add',
        'query' => 'reset=1&action=add',
        'title' => '新增募款頁',
        'menu' => 'primary-links',
        'weight' => '-43',
        'module' => 'menu',
        'hidden' => '0',
      ),
      8 => 
      array (
        'path' => 'civicrm/admin/contribute',
        'query' => 'reset=1',
        'title' => '管理募款頁',
        'menu' => 'primary-links',
        'weight' => '-42',
        'module' => 'menu',
        'hidden' => '0',
      ),
      9 => 
      array (
        'path' => 'civicrm/admin/pcp',
        'query' => 'reset=1',
        'title' => '個人募款頁',
        'menu' => 'primary-links',
        'weight' => '-41',
        'module' => 'menu',
        'hidden' => '0',
      ),
      10 => 
      array (
        'path' => 'civicrm/admin/contribute/managePremiums',
        'query' => 'reset=1',
        'title' => '感謝禮 (感謝禮品)',
        'menu' => 'primary-links',
        'weight' => '-40',
        'module' => 'menu',
        'hidden' => '0',
      ),
      11 => 
      array (
        'path' => 'civicrm/admin/price',
        'query' => 'reset=1&action=add',
        'title' => '新增價格表',
        'menu' => 'primary-links',
        'weight' => '-39',
        'module' => 'menu',
        'hidden' => '0',
      ),
      12 => 
      array (
        'path' => 'civicrm/admin/price',
        'query' => 'reset=1',
        'title' => '管理價格表',
        'menu' => 'primary-links',
        'weight' => '-38',
        'module' => 'menu',
        'hidden' => '0',
      ),
      13 => 
      array (
        'path' => 'civicrm/admin/contribute/contributionType',
        'query' => 'reset=1',
        'title' => '新增/管理捐款類型',
        'menu' => 'primary-links',
        'weight' => '-37',
        'module' => 'menu',
        'hidden' => '0',
      ),
      14 => 
      array (
        'path' => 'civicrm/admin/options/accept_creditcard',
        'query' => 'group=accept_creditcard&reset=1',
        'title' => '新增/管理可用的信用卡',
        'menu' => 'primary-links',
        'weight' => '-35',
        'module' => 'menu',
        'hidden' => '0',
      ),
      15 => 
      array (
        'path' => 'civicrm/admin/uf/group',
        'query' => 'reset=1',
        'title' => '新增/管理資料表單',
        'menu' => 'navigation',
        'weight' => '20',
        'module' => 'menu',
        'hidden' => '0',
      ),
      16 => 
      array (
        'path' => 'civicrm/admin/custom/group',
        'query' => 'reset=1',
        'title' => '新增/管理自訂欄位',
        'menu' => 'navigation',
        'weight' => '20',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
  2 => 
  array (
    'path' => 'civicrm/event',
    'query' => 'reset=1',
    'title' => '活動',
    'menu' => 'primary-links',
    'weight' => '-47',
    'module' => 'menu',
    'hidden' => '0',
    'expanded' => 1,
    'children' => 
    array (
      0 => 
      array (
        'path' => 'civicrm/admin/options/participant_listing',
        'query' => 'group=participant_listing&reset=1',
        'title' => '參加者列表範本',
        'menu' => 'primary-links',
        'weight' => '-42',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/admin/options/event_type',
        'query' => 'group=event_type&reset=1',
        'title' => '新增/管理活動類型',
        'menu' => 'primary-links',
        'weight' => '-41',
        'module' => 'menu',
        'hidden' => '0',
      ),
      2 => 
      array (
        'path' => 'civicrm/admin/participant_status',
        'query' => 'reset=1',
        'title' => '新增/管理參加者狀態',
        'menu' => 'primary-links',
        'weight' => '-40',
        'module' => 'menu',
        'hidden' => '0',
      ),
      3 => 
      array (
        'path' => 'civicrm/admin/options/participant_role',
        'query' => 'group=participant_role&reset=1',
        'title' => '新增/管理參加者角色',
        'menu' => 'primary-links',
        'weight' => '-39',
        'module' => 'menu',
        'hidden' => '0',
      ),
      4 => 
      array (
        'path' => 'civicrm/participant/add',
        'query' => 'reset=1&action=add&context=standalone',
        'title' => '登記活動參加者',
        'menu' => 'primary-links',
        'weight' => '-50',
        'module' => 'menu',
        'hidden' => '0',
      ),
      5 => 
      array (
        'path' => 'civicrm/event/search',
        'query' => 'reset=1',
        'title' => '搜尋活動參加者',
        'menu' => 'primary-links',
        'weight' => '-49',
        'module' => 'menu',
        'hidden' => '0',
      ),
      6 => 
      array (
        'path' => 'civicrm/event/import',
        'query' => 'reset=1',
        'title' => '匯入參加者',
        'menu' => 'primary-links',
        'weight' => '-48',
        'module' => 'menu',
        'hidden' => '0',
      ),
      7 => 
      array (
        'path' => 'civicrm/event/add',
        'query' => 'reset=1&action=add',
        'title' => '新增活動',
        'menu' => 'primary-links',
        'weight' => '-47',
        'module' => 'menu',
        'hidden' => '0',
      ),
      8 => 
      array (
        'path' => 'civicrm/event/manage',
        'query' => 'reset=1',
        'title' => '管理活動',
        'menu' => 'primary-links',
        'weight' => '-46',
        'module' => 'menu',
        'hidden' => '0',
      ),
      9 => 
      array (
        'path' => 'civicrm/admin/eventTemplate',
        'query' => 'reset=1',
        'title' => '活動範本',
        'menu' => 'primary-links',
        'weight' => '-45',
        'module' => 'menu',
        'hidden' => '0',
      ),
      10 => 
      array (
        'path' => 'civicrm/admin/price',
        'query' => 'reset=1&action=add',
        'title' => '新增價格表',
        'menu' => 'primary-links',
        'weight' => '-44',
        'module' => 'menu',
        'hidden' => '0',
      ),
      11 => 
      array (
        'path' => 'civicrm/admin/price',
        'query' => 'reset=1',
        'title' => '管理價格表',
        'menu' => 'primary-links',
        'weight' => '-43',
        'module' => 'menu',
        'hidden' => '0',
      ),
      12 => 
      array (
        'path' => 'civicrm/admin/uf/group',
        'query' => 'reset=1',
        'title' => '新增/管理資料表單',
        'menu' => 'navigation',
        'weight' => '20',
        'module' => 'menu',
        'hidden' => '0',
      ),
      13 => 
      array (
        'path' => 'civicrm/admin/custom/group',
        'query' => 'reset=1',
        'title' => '新增/管理自訂欄位',
        'menu' => 'navigation',
        'weight' => '20',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
  3 => 
  array (
    'path' => 'civicrm/mailing/browse',
    'query' => 'reset=1&scheduled=true',
    'title' => '電子報',
    'menu' => 'primary-links',
    'weight' => '-46',
    'module' => 'menu',
    'expanded' => 1,
    'hidden' => '0',
    'children' => 
    array (
      0 => 
      array (
        'path' => 'civicrm/mailing/send',
        'query' => 'reset=1',
        'title' => '新增電子報/郵件',
        'menu' => 'primary-links',
        'weight' => '-50',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/mailing/browse/unscheduled',
        'query' => 'reset=1&scheduled=false',
        'title' => '草稿與未排程的電子報',
        'menu' => 'primary-links',
        'weight' => '-49',
        'module' => 'menu',
        'hidden' => '0',
      ),
      2 => 
      array (
        'path' => 'civicrm/mailing/browse/scheduled',
        'query' => 'reset=1&scheduled=true',
        'title' => '已排程與已寄送的電子報',
        'menu' => 'primary-links',
        'weight' => '-48',
        'module' => 'menu',
        'hidden' => '0',
      ),
      3 => 
      array (
        'path' => 'civicrm/mailing/browse/archived',
        'query' => 'reset=1',
        'title' => '電子報存檔',
        'menu' => 'primary-links',
        'weight' => '-47',
        'module' => 'menu',
        'hidden' => '0',
      ),
      4 => 
      array (
        'path' => 'civicrm/admin/component',
        'query' => 'reset=1',
        'title' => '頁首、頁尾、自動回覆訊息',
        'menu' => 'primary-links',
        'weight' => '-46',
        'module' => 'menu',
        'hidden' => '0',
      ),
      5 => 
      array (
        'path' => 'civicrm/admin/messageTemplates',
        'query' => 'reset=1',
        'title' => '訊息範本',
        'menu' => 'primary-links',
        'weight' => '-45',
        'module' => 'menu',
        'hidden' => '0',
      ),
      6 => 
      array (
        'path' => 'civicrm/admin/options/from_email',
        'query' => 'group=from_email_address&reset=1',
        'title' => 'Email寄件者',
        'menu' => 'primary-links',
        'weight' => '-44',
        'module' => 'menu',
        'hidden' => '0',
      ),
      7 => 
      array (
        'path' => 'civicrm/admin/options/email_greeting',
        'query' => 'group=email_greeting&reset=1',
        'title' => 'Email問候語',
        'menu' => 'primary-links',
        'weight' => '-43',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
  4 => 
  array (
    'path' => 'civicrm/member',
    'query' => 'reset=1',
    'title' => '會員',
    'menu' => 'primary-links',
    'weight' => '-45',
    'module' => 'menu',
    'hidden' => '0',
    'expanded' => 1,
    'children' => 
    array (
      0 => 
      array (
        'path' => 'civicrm/member/add',
        'query' => 'reset=1&action=add&context=standalone',
        'title' => '新增會員資料',
        'menu' => 'primary-links',
        'weight' => '-50',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/member/search',
        'query' => 'reset=1',
        'title' => '搜尋會員',
        'menu' => 'primary-links',
        'weight' => '-49',
        'module' => 'menu',
        'hidden' => '0',
      ),
      2 => 
      array (
        'path' => 'civicrm/member/import',
        'query' => 'reset=1',
        'title' => '匯入會員',
        'menu' => 'primary-links',
        'weight' => '-48',
        'module' => 'menu',
        'hidden' => '0',
      ),
      3 => 
      array (
        'path' => 'civicrm/admin/member/membershipType',
        'query' => 'reset=1',
        'title' => '新增/編輯會員類型',
        'menu' => 'primary-links',
        'weight' => '-47',
        'module' => 'menu',
        'hidden' => '0',
      ),
      4 => 
      array (
        'path' => 'civicrm/admin/member/membershipStatus',
        'query' => 'reset=1',
        'title' => '新增/編輯會員狀態',
        'menu' => 'primary-links',
        'weight' => '0',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
  5 => 
  array (
    'path' => 'civicrm/report/list',
    'query' => 'reset=1',
    'title' => '報表',
    'menu' => 'primary-links',
    'weight' => '-44',
    'module' => 'menu',
    'hidden' => '0',
    'expanded' => 1,
    'children' => 
    array (
      0 => 
      array (
        'path' => 'civicrm/admin/report/template/list',
        'query' => 'reset=1',
        'title' => '從範本新增報表',
        'menu' => 'primary-links',
        'weight' => '2',
        'module' => 'menu',
        'hidden' => '0',
      ),
      1 => 
      array (
        'path' => 'civicrm/admin/report/options/report_template&reset=1',
        'query' => NULL,
        'title' => '報表範本管理',
        'menu' => 'primary-links',
        'weight' => '0',
        'module' => 'menu',
        'hidden' => '0',
      ),
    ),
  ),
);
 
$menu_links['menu-footer'] =array (
  0 => 
  array (
    'path' => 'http://netivism.com.tw/about',
    'query' => NULL,
    'title' => '關於網絡行動科技',
    'menu' => 'menu-footer',
    'weight' => '2',
    'module' => 'menu',
    'hidden' => '0',
  ),
  1 => 
  array (
    'path' => 'http://civicrm.tw/civicrm/what-is-civicrm',
    'query' => NULL,
    'title' => '關於CiviCRM',
    'menu' => 'menu-footer',
    'weight' => '3',
    'module' => 'menu',
    'hidden' => '0',
  ),
);

$menu_links['menu-search'] = array (
  0 => 
  array (
    'path' => 'civicrm/contact/search/builder',
    'query' => 'reset=1',
    'title' => '自定搜尋',
    'menu' => 'menu-search',
    'weight' => '4',
    'module' => 'menu',
    'hidden' => '0',
  ),
  1 => 
  array (
    'path' => 'civicrm/contact/search/custom',
    'query' => 'csid=15&reset=1',
    'title' => '全文搜尋',
    'menu' => 'menu-search',
    'weight' => '3',
    'module' => 'menu',
    'hidden' => '0',
  ),
  2 => 
  array (
    'path' => 'civicrm/contact/search/advanced',
    'query' => 'reset=1',
    'title' => '進階搜尋',
    'menu' => 'menu-search',
    'weight' => '2',
    'module' => 'menu',
    'hidden' => '0',
  ),
);
