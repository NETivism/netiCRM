REPLACE INTO 
  `civicrm_preferences` (`id`, `domain_id`, `contact_id`, `is_domain`, `contact_view_options`, `contact_edit_options`, `advanced_search_options`, `user_dashboard_options`, `address_options`, `address_format`, `mailing_format`, `display_name_format`, `sort_name_format`, `address_standardization_provider`, `address_standardization_userid`, `address_standardization_url`, `editor_id`, `mailing_backend`, `navigation`, `contact_autocomplete_options`) 
VALUES
  (1, 1, NULL, 1, '123456789101113', '1234567891011', '12345678910121315161718', '1234578', '145681011', '{contact.postal_code}{ }{contact.state_province_name}{contact.city}{contact.street_address}{contact.supplemental_address_1}{contact.supplemental_address_2}', '{contact.postal_code}{ }{contact.state_province_name}{contact.city}{contact.street_address}{contact.supplemental_address_1}{contact.supplemental_address_2}', '{contact.last_name}{contact.first_name}{ }{contact.individual_prefix}', '{contact.last_name}{contact.first_name}', NULL, NULL, NULL, 2, 'a:1:{s:15:"outBound_option";s:1:"3";}', NULL, '12');

REPLACE INTO 
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES
-- email greeting.
  (41, '親愛的 {contact.last_name}{contact.first_name}',                                                 1, '親愛的 {contact.last_name}{contact.first_name}',                                                 NULL,    1, 1, 1, NULL, 0, 0, 1, NULL, NULL),
  (41, '親愛的 {contact.last_name}{contact.first_name}{contact.individual_prefix}', 2, '親愛的 {contact.last_name}{contact.first_name}{contact.individual_prefix}', NULL,    1, 0, 2, NULL, 0, 0, 1, NULL, NULL),
  (41, '親愛的 {contact.last_name}{contact.individual_prefix}',                      3, '親愛的 {contact.last_name}{contact.individual_prefix}',                      NULL,    1, 0, 3, NULL, 0, 0, 1, NULL, NULL),
  (41, 'Customized',                                                                4, 'Customized',                                                                NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL),
  (41, '親愛的 {contact.household_name}',                                             5, '親愛的 {contact.household_name}',                                             NULL,    2, 1, 5, NULL, 0, 0, 1, NULL, NULL),
-- postal greeting.
  (42, '親愛的 {contact.last_name}{contact.first_name}',                                                 1, '親愛的 {contact.last_name}{contact.first_name}',                                                 NULL,    1, 1, 1, NULL, 0, 0, 1, NULL, NULL),
  (42, '親愛的 {contact.last_name}{contact.first_name}{contact.individual_prefix}', 2, '親愛的 {contact.last_name}{contact.first_name}{contact.individual_prefix}', NULL,    1, 0, 2, NULL, 0, 0, 1, NULL, NULL),
  (42, '親愛的 {contact.last_name}{contact.individual_prefix}',                      3, '親愛的 {contact.last_name}{contact.individual_prefix}',                      NULL,    1, 0, 3, NULL, 0, 0, 1, NULL, NULL),
  (42, 'Customized',                                                                4, 'Customized',                                                                NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL),
  (42, '親愛的 {contact.household_name}',                                             5, '親愛的 {contact.household_name}',                                             NULL,    2, 1, 5, NULL, 0, 0, 1, NULL, NULL),
-- addressee
  (43, '{contact.last_name}{contact.first_name}{ }{contact.individual_prefix}{ }{contact.individual_suffix}',          '1', '{contact.last_name}{contact.first_name}{ }{contact.individual_prefix}{ }{contact.individual_suffix}',         NULL ,   '1', '1', '1', NULL , '0', '0', '1', NULL , NULL),
  (43, '{contact.household_name}',    '2', '{contact.household_name}',    NULL ,   '2', '1', '2', NULL , '0', '0', '1', NULL , NULL),
  (43, '{contact.organization_name}', '3', '{contact.organization_name}', NULL ,   '3', '1', '3', NULL , '0', '0', '1', NULL , NULL),
  (43, 'Customized',                  '4', 'Customized',                  NULL ,    0 , '0', '4', NULL , '0', '1', '1', NULL , NULL);

-- report translation
REPLACE INTO 
  `civicrm_report_instance` (`id`, `domain_id`, `title`, `report_id`, `name`, `args`, `description`, `permission`, `form_values`, `is_active`, `email_subject`, `email_to`, `email_cc`, `header`, `footer`, `navigation_id`) 
VALUES
  (1, 1, '支持者報表（概況）', 'contact/summary', NULL, NULL, '在您的系統上，為支持者記錄提供地址和電話訊息組成的名單。', 'administer CiviCRM', 'a:24:{s:6:"fields";a:7:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:14:"street_address";s:1:"1";s:4:"city";s:1:"1";s:11:"postal_code";s:1:"1";s:17:"state_province_id";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:84:"在您的系統上，為支持者記錄提供地址和電話訊息組成的名單。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>支持者報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 196),
  (2, 1, '支持者報表（詳情）', 'contact/detail', NULL, NULL, '提供在捐款、會員、項目和活動有關的聯絡資訊', 'administer CiviCRM', 'a:18:{s:6:"fields";a:29:{s:12:"display_name";s:1:"1";s:15:"contribution_id";s:1:"1";s:12:"total_amount";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:13:"membership_id";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:20:"membership_status_id";s:1:"1";s:14:"participant_id";s:1:"1";s:8:"event_id";s:1:"1";s:21:"participant_status_id";s:1:"1";s:7:"role_id";s:1:"1";s:25:"participant_register_date";s:1:"1";s:9:"fee_level";s:1:"1";s:10:"fee_amount";s:1:"1";s:15:"relationship_id";s:1:"1";s:20:"relationship_type_id";s:1:"1";s:12:"contact_id_b";s:1:"1";s:2:"id";s:1:"1";s:16:"activity_type_id";s:1:"1";s:7:"subject";s:1:"1";s:17:"source_contact_id";s:1:"1";s:18:"activity_date_time";s:1:"1";s:18:"activity_status_id";s:1:"1";s:17:"target_contact_id";s:1:"1";s:19:"assignee_contact_id";s:1:"1";}s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:63:"提供在捐款、會員、項目和活動有關的聯絡資訊";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>支持者報表（詳情）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 197),
  (3, 1, '捐款者報表（概況）', 'contribute/summary', NULL, NULL, '依月 / 星期 / 年 ... 國家 / 州等分類顯示捐款統計資料', 'access CiviContribute', 'a:40:{s:6:"fields";a:1:{s:12:"total_amount";s:1:"1";}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:13:"total_sum_min";s:0:"";s:13:"total_sum_max";s:0:"";s:12:"total_sum_op";s:3:"lte";s:15:"total_sum_value";s:0:"";s:15:"total_count_min";s:0:"";s:15:"total_count_max";s:0:"";s:14:"total_count_op";s:3:"lte";s:17:"total_count_value";s:0:"";s:13:"total_avg_min";s:0:"";s:13:"total_avg_max";s:0:"";s:12:"total_avg_op";s:3:"lte";s:15:"total_avg_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:9:"group_bys";a:1:{s:12:"receive_date";s:1:"1";}s:14:"group_bys_freq";a:1:{s:12:"receive_date";s:5:"MONTH";}s:11:"description";s:71:"依月 / 星期 / 年 ... 國家 / 州等分類顯示捐款統計資料";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>捐款者報表（概況）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 198),
  (4, 1, '捐款者報表（詳情）', 'contribute/detail', NULL, NULL, '列出一個 / 全部聯絡人的詳細捐款資料。捐款總結報表指出這個報表的具體細節。', 'access CiviContribute', 'a:34:{s:6:"fields";a:7:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:10:"country_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"receipt_date";s:1:"1";s:12:"total_amount";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:108:"列出一個 / 全部聯絡人的詳細捐款資料。捐款總結報表指出這個報表的具體細節。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>捐款者報表（詳情）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 199),
  (5, 1, '捐贈摘要報表（重複）', 'contribute/repeat', NULL, NULL, '請給兩個日期範圍，顯示在這段時間裡，聯絡人（與他們的捐款）增加或減少的比率。', 'access CiviContribute', 'a:28:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:13:"total_amount1";s:1:"1";s:13:"total_amount2";s:1:"1";}s:22:"receive_date1_relative";s:13:"previous.year";s:18:"receive_date1_from";s:0:"";s:16:"receive_date1_to";s:0:"";s:22:"receive_date2_relative";s:9:"this.year";s:18:"receive_date2_from";s:0:"";s:16:"receive_date2_to";s:0:"";s:17:"total_amount1_min";s:0:"";s:17:"total_amount1_max";s:0:"";s:16:"total_amount1_op";s:3:"lte";s:19:"total_amount1_value";s:0:"";s:17:"total_amount2_min";s:0:"";s:17:"total_amount2_max";s:0:"";s:16:"total_amount2_op";s:3:"lte";s:19:"total_amount2_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:9:"group_bys";a:1:{s:2:"id";s:1:"1";}s:11:"description";s:114:"請給兩個日期範圍，顯示在這段時間裡，聯絡人（與他們的捐款）增加或減少的比率。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>捐贈摘要報表（重複）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 200),
  (6, 1, '本年未捐款報表 (以過去捐款為基礎)', 'contribute/sybunt', NULL, NULL, '某幾年但不是今年。提供一份曾經捐款給您的組織，但今年您指定的期間內尚未捐款的清單。', 'access CiviContribute', 'a:17:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"yid_op";s:2:"eq";s:9:"yid_value";s:4:"2009";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:123:"某幾年但不是今年。提供一份曾經捐款給您的組織，但今年您指定的期間內尚未捐款的清單。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>本年未捐款報表 (以過去捐款為基礎)</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 201),
  (7, 1, '本年未捐款報表 (與去年捐款為基礎)', 'contribute/lybunt', NULL, NULL, '去年一年但不是今年。提供一個去年有捐款但本年度您指定時間內沒有捐款的清單。', 'access CiviContribute', 'a:17:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"yid_op";s:2:"eq";s:9:"yid_value";s:4:"2009";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:111:"去年一年但不是今年。提供一個去年有捐款但本年度您指定時間內沒有捐款的清單。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>本年未捐款報表 (與去年捐款為基礎)</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 202),
  (8, 1, 'Soft Credit Report', 'contribute/softcredit', NULL, NULL, 'Soft Credit details.', 'access CiviContribute', 'a:21:{s:6:"fields";a:5:{s:21:"display_name_creditor";s:1:"1";s:24:"display_name_constituent";s:1:"1";s:14:"email_creditor";s:1:"1";s:14:"phone_creditor";s:1:"1";s:12:"total_amount";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:20:"Soft Credit details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}', NULL, NULL, NULL, NULL, '', NULL, 203),
  (9, 1, '會員報表（概況）', 'member/summary', NULL, NULL, '提供一個會員摘要，按類型和加入的日期。', 'access CiviMember', 'a:16:{s:6:"fields";a:2:{s:18:"membership_type_id";s:1:"1";s:12:"total_amount";s:1:"1";}s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:9:"group_bys";a:2:{s:9:"join_date";s:1:"1";s:18:"membership_type_id";s:1:"1";}s:14:"group_bys_freq";a:1:{s:9:"join_date";s:5:"MONTH";}s:11:"description";s:57:"提供一個會員摘要，按類型和加入的日期。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>會員報表（概況）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 204),
  (10, 1, '會員報表（詳情）', 'member/detail', NULL, NULL, '提供會員名單以及他們的會員資格和成員詳細資料（加入的日期，開始日期，結束日期）。', 'access CiviMember', 'a:26:{s:6:"fields";a:5:{s:12:"display_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:120:"提供會員名單以及他們的會員資格和成員詳細資料（加入的日期，開始日期，結束日期）。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>會員報表（詳情）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 205),
  (11, 1, '會員報表 (已失效)', 'member/lapse', NULL, NULL, '提供已失敗或在指定日期前失效的會員名單。', 'access CiviMember', 'a:14:{s:6:"fields";a:5:{s:12:"display_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";s:10:"country_id";s:1:"1";}s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:60:"提供已失敗或在指定日期前失效的會員名單。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>會員報表 (已失效)</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 206),
  (12, 1, '活動參加者報表（清單）', 'event/participantListing', NULL, NULL, '提供此項目的參加者名單', 'access CiviEvent', 'a:24:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:8:"event_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:1:" ";s:28:"participant_register_date_to";s:1:" ";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:16:"blank_column_end";s:0:"";s:11:"description";s:33:"提供此項目的參加者名單";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:7:"options";N;s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>活動參加者報表（清單）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 207),
  (13, 1, '活動收入報表（摘要）', 'event/summary', NULL, NULL, '提供了項目收入的概述。您可以包括一些關鍵信息如活動編號、登記、出席和產生的收入來幫助您去確定這個項目是否成功。', 'access CiviEvent', 'a:18:{s:6:"fields";a:2:{s:5:"title";s:1:"1";s:13:"event_type_id";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:16:"event_type_id_op";s:2:"in";s:19:"event_type_id_value";a:0:{}s:25:"event_start_date_relative";s:1:"0";s:21:"event_start_date_from";s:1:" ";s:19:"event_start_date_to";s:1:" ";s:23:"event_end_date_relative";s:1:"0";s:19:"event_end_date_from";s:1:" ";s:17:"event_end_date_to";s:0:"";s:11:"description";s:165:"提供了項目收入的概述。您可以包括一些關鍵信息如活動編號、登記、出席和產生的收入來幫助您去確定這個項目是否成功。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>活動收入報表（摘要）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 208),
  (14, 1, '活動收入報表（詳情）', 'event/income', NULL, NULL, '幫助您去分析項目所帶來的收入。該報表可包括詳細的參加者類型，狀態和付款方式。', 'access CiviEvent', 'a:8:{s:5:"id_op";s:2:"in";s:8:"id_value";N;s:11:"description";s:114:"幫助您去分析項目所帶來的收入。該報表可包括詳細的參加者類型，狀態和付款方式。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>活動收入報表（詳情）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 209),
  (15, 1, '參加者清單', 'event/participantListing', NULL, NULL, '提供活動的參加者清單', 'access CiviEvent', 'a:24:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:14:"participant_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:1:{i:0;s:1:"1";}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:0:"";s:28:"participant_register_date_to";s:0:"";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:16:"blank_column_end";s:1:"1";s:11:"description";s:30:"提供活動的參加者清單";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:7:"options";N;s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>參加者清單</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 210),
  (16, 1, '任務報表', 'activity', NULL, NULL, '提供支持者任務的清單，包括在指定日期範圍內，聯絡人的任務統計。（必填）', 'administer CiviCRM', 'a:23:{s:6:"fields";a:6:{s:14:"contact_source";s:1:"1";s:16:"contact_assignee";s:1:"1";s:14:"contact_target";s:1:"1";s:16:"activity_type_id";s:1:"1";s:18:"activity_date_time";s:1:"1";s:9:"status_id";s:1:"1";}s:17:"contact_source_op";s:3:"has";s:20:"contact_source_value";s:0:"";s:19:"contact_assignee_op";s:3:"has";s:22:"contact_assignee_value";s:0:"";s:17:"contact_target_op";s:3:"has";s:20:"contact_target_value";s:0:"";s:27:"activity_date_time_relative";s:10:"this.month";s:23:"activity_date_time_from";s:0:"";s:21:"activity_date_time_to";s:0:"";s:19:"activity_subject_op";s:3:"has";s:22:"activity_subject_value";s:0:"";s:19:"activity_type_id_op";s:2:"in";s:22:"activity_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:9:"group_bys";a:1:{s:17:"source_contact_id";s:1:"1";}s:11:"description";s:105:"提供支持者任務的清單，包括在指定日期範圍內，聯絡人的任務狀態統計。（必填）";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>任務報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 211),
  (17, 1, '關係報表', 'contact/relationship', NULL, NULL, '提供聯絡人間的關係報表。', 'administer CiviCRM', 'a:27:{s:6:"fields";a:4:{s:14:"display_name_a";s:1:"1";s:14:"display_name_b";s:1:"1";s:9:"label_a_b";s:1:"1";s:9:"label_b_a";s:1:"1";}s:14:"sort_name_a_op";s:3:"has";s:17:"sort_name_a_value";s:0:"";s:14:"sort_name_b_op";s:3:"has";s:17:"sort_name_b_value";s:0:"";s:17:"contact_type_a_op";s:2:"in";s:20:"contact_type_a_value";a:0:{}s:17:"contact_type_b_op";s:2:"in";s:20:"contact_type_b_value";a:0:{}s:12:"is_active_op";s:2:"eq";s:15:"is_active_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:36:"提供聯絡人間的關係報表。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>關係報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 212),
  (18, 1, '捐款概況報表（組織）', 'contribute/organizationSummary', NULL, NULL, '顯示一份詳細的捐款報表，針對與組織有關的捐款者，以及捐款來自組織員工或組織本身。', 'access CiviContribute', 'a:20:{s:6:"fields";a:5:{s:17:"organization_name";s:1:"1";s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:20:"organization_name_op";s:3:"has";s:23:"organization_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"4_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:1:" ";s:15:"receive_date_to";s:1:" ";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:120:"顯示一份詳細的捐款報表，針對與組織有關的捐款者，以及捐款來自組織員工或組織本身。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>捐款概況報表（組織）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 213),
  (19, 1, '捐款概況報表（家庭）', 'contribute/householdSummary', NULL, NULL, '提供一份有家庭資料（或家庭本身）的捐款詳細報表（對前一個捐款者為家戶長的一些家庭與成員）。', 'access CiviContribute', 'a:20:{s:6:"fields";a:5:{s:14:"household_name";s:1:"1";s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:17:"household_name_op";s:3:"has";s:20:"household_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"6_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:1:" ";s:15:"receive_date_to";s:1:" ";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:135:"提供一份有家庭資料（或家庭本身）的捐款詳細報表（對前一個捐款者為家戶長的一些家庭與成員）。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>捐款概況報表（家庭）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 214),
  (20, 1, '最高捐款者名錄報表', 'contribute/topDonor', NULL, NULL, '提供一個您指定期間內捐款最多的人的名單。您可搜尋出您要的數量（例如：前一百名捐款者）。', 'access CiviContribute', 'a:22:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";}s:21:"receive_date_relative";s:9:"this.year";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:15:"total_range_min";s:0:"";s:15:"total_range_max";s:0:"";s:14:"total_range_op";s:2:"eq";s:17:"total_range_value";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:129:"提供一個您指定期間內捐款最多的人的名單。您可搜尋出您要的數量（例如：前一百名捐款者）。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>最高捐款者名錄報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 215),
  (21, 1, '認捐概況報表', 'pledge/summary', NULL, NULL, '認捐概況，如你的認捐狀況，下一個付款日、金額、付款期限，和認捐的總計。', 'access CiviPledge', 'a:25:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:10:"country_id";s:1:"1";s:6:"amount";s:1:"1";s:9:"status_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:17:"pledge_amount_min";s:0:"";s:17:"pledge_amount_max";s:0:"";s:16:"pledge_amount_op";s:3:"lte";s:19:"pledge_amount_value";s:0:"";s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:136:"Updates you with your Pledge Summary (if any) such as your pledge status, next payment date, amount, payment due, total amount paid etc.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}', NULL, NULL, NULL, NULL, '', NULL, 216),
  (22, 1, '認捐但未付款報表', 'pledge/pbnp', NULL, NULL, '認捐但未付款報表', 'access CiviPledge', 'a:15:{s:6:"fields";a:6:{s:12:"display_name";s:1:"1";s:18:"pledge_create_date";s:1:"1";s:6:"amount";s:1:"1";s:14:"scheduled_date";s:1:"1";s:10:"country_id";s:1:"1";s:5:"email";s:1:"1";}s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:24:"認捐但未付款報表";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}', NULL, NULL, NULL, NULL, '', NULL, 217),
  (23, 1, '記帳報表', 'contribute/bookkeeping', NULL, NULL, '顯示記帳表', 'access CiviContribute', 'a:24:{s:6:"fields";a:10:{s:12:"display_name";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:7:"trxn_id";s:1:"1";s:10:"invoice_id";s:1:"1";s:12:"check_number";s:1:"1";s:21:"payment_instrument_id";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:2:"id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:11:"description";s:15:"顯示記帳表";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>記帳報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 218),
  (24, 1, '補助報表', 'grant', NULL, NULL, '補助報表', 'access CiviGrant', 'a:37:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:25:"application_received_date";s:1:"1";}s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:12:"gender_id_op";s:2:"in";s:15:"gender_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:11:"description";s:12:"補助報表";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '', NULL, 219),
                                                  (25, 1, 'Email退信報表', 'Mailing/bounce', NULL, NULL, '電子報或email退信報表。', 'access CiviMail', 'a:21:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:19:"bounce_type_name_op";s:2:"eq";s:22:"bounce_type_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:32:"電子報或email退信報表。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>Email退信報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 220),
  (26, 1, 'Email遞送報表（概況）', 'Mailing/summary', NULL, NULL, '電子報遞送狀態概況報表。', 'access CiviMail', 'a:21:{s:6:"fields";a:1:{s:4:"name";s:1:"1";}s:15:"is_completed_op";s:2:"eq";s:18:"is_completed_value";s:1:"1";s:9:"status_op";s:3:"has";s:12:"status_value";s:8:"Complete";s:11:"is_test_min";s:0:"";s:11:"is_test_max";s:0:"";s:10:"is_test_op";s:3:"lte";s:13:"is_test_value";s:1:"0";s:19:"start_date_relative";s:9:"this.year";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:17:"end_date_relative";s:9:"this.year";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:11:"description";s:36:"電子報遞送狀態概況報表。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>Email遞送報表（概況）</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 221),
                                                      (27, 1, 'Email開信率報表', 'Mailing/opened', NULL, NULL, 'Email開信率概況報表。', 'access CiviMail', 'a:19:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:29:"Email開信率概況報表。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>Email開信率報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 222),
  (28, 1, 'Email點擊流程報表', 'Mailing/clicks', NULL, NULL, '提供每個電子報 / email行銷的概況報表。', 'access CiviMail', 'a:19:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:53:"提供每個電子報 / email行銷的概況報表。";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}', NULL, NULL, NULL, NULL, '<html>\r\n  <head>\r\n    <title>Email點擊流程報表</title>\r\n    <style type="text/css">@import url(http://demo.civicrm.tw/sites/all/modules/civicrm/css/print.css);</style>\r\n  </head>\r\n  <body><div id="crm-container">', '<p><img src="http://demo.civicrm.tw/sites/all/modules/civicrm/i/powered_by.png" /></p></div></body>\r\n</html>\r\n', 223);

-- group / profiles
UPDATE `civicrm_group` SET `title` = '管理員', `description` = '網站最高權限管理員.' WHERE id = 1;
UPDATE  `civicrm_uf_group` SET  `help_post` =  '' WHERE `id` = 2 LIMIT 1 ;
DELETE FROM `civicrm_uf_join` WHERE id = 1 OR id = 2;
REPLACE INTO `civicrm_uf_field` (`id`, `uf_group_id`, `field_name`, `is_active`, `is_view`, `is_required`, `weight`, `help_post`, `help_pre`, `visibility`, `in_selector`, `is_searchable`, `location_type_id`, `phone_type_id`, `label`, `field_type`, `is_reserved`) VALUES
(1, 1, 'first_name', 1, 0, 1, 2, '其他人將無法看見您輸入的資訊，請您放心。', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '名字', 'Individual', 0),
(2, 1, 'last_name', 1, 0, 1, 1, '', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '姓氏', 'Individual', 0),
(3, 1, 'street_address', 1, 0, 0, 7, NULL, NULL, 'User and User Admin Only', 0, 0, 1, NULL, '街道地址（住家）', 'Contact', 0),
(4, 1, 'city', 1, 0, 0, 5, NULL, NULL, 'User and User Admin Only', 0, 0, 1, NULL, '鄉鎮市區（住家）', 'Contact', 0),
(5, 1, 'postal_code', 1, 0, 0, 6, NULL, NULL, 'User and User Admin Only', 0, 0, 1, NULL, '郵遞區號（住家）', 'Contact', 0),
(6, 1, 'country', 0, 0, 0, 3, '你所在的縣市和居住國將與他人共享，以便人們可以找到其他住在他們社區的聯絡人。', NULL, 'User and User Admin Only', 0, 0, 1, NULL, '國家（住家）', 'Contact', 0),
(7, 1, 'state_province', 1, 0, 0, 4, '', NULL, 'User and User Admin Only', 0, 0, 1, NULL, '縣市（住家）', 'Contact', 0),
(8, 2, 'first_name', 1, 0, 1, 2, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '名字 ', 'Individual', 0),
(9, 2, 'last_name', 1, 0, 1, 1, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '姓氏', 'Individual', 0),
(10, 2, 'email', 1, 0, 1, 3, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email位址', 'Contact', 0),
(11, 3, 'participant_status_id', 1, 0, 1, 1, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '參加者狀態', 'Participant', 1),
(12, 4, 'first_name', 1, 0, 1, 2, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '名字 ', 'Individual', 0),
(13, 4, 'last_name', 1, 0, 1, 1, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '姓氏', 'Individual', 0),
(14, 4, 'email', 1, 0, 0, 3, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email位址', 'Contact', 0),
(15, 5, 'organization_name', 1, 0, 1, 2, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '組織名稱', 'Organization', 0),
(16, 5, 'email', 1, 0, 0, 3, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email位址', 'Contact', 0),
(17, 6, 'household_name', 1, 0, 1, 2, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '家庭名稱', 'Household', 0),
(18, 6, 'email', 1, 0, 0, 3, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email位址', 'Contact', 0),
(19, 7, 'phone', 1, 0, 1, 1, '', NULL, 'User and User Admin Only', 0, 0, 1, 1, '住家電話', 'Contact', 0),
(20, 7, 'phone', 1, 0, 1, 2, '', NULL, 'User and User Admin Only', 0, 0, 1, 2, '手機號碼', 'Contact', 0),
(21, 7, 'street_address', 1, 0, 1, 6, '', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '街道地址', 'Contact', 0),
(22, 7, 'city', 1, 0, 1, 4, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '鄉鎮市區', 'Contact', 0),
(23, 7, 'state_province', 1, 0, 1, 3, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '縣市', 'Contact', 0),
(24, 7, 'postal_code', 1, 0, 1, 5, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '郵遞區號', 'Contact', 0),
(25, 7, 'email', 1, 0, 1, 7, '', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email', 'Contact', 0),
(26, 7, 'group', 1, 0, 1, 8, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '群組', 'Contact', 0),
(27, 7, 'tag', 1, 0, 1, 9, '', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '標籤', 'Contact', 0),
(28, 7, 'gender', 1, 0, 1, 10, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '性別', 'Individual', 0),
(29, 7, 'birth_date', 1, 0, 1, 11, '', NULL, 'User and User Admin Only', 0, 0, NULL, NULL, '生日', 'Individual', 0),
(30, 8, 'street_address', 1, 0, 1, 5, NULL, NULL, 'User and User Admin Only', 0, 0, 1, NULL, '街道地址（住家）', 'Contact', 1),
(31, 8, 'city', 1, 0, 1, 3, NULL, NULL, 'User and User Admin Only', 0, 0, 1, NULL, '鄉鎮市區（住家）', 'Contact', 1),
(32, 8, 'postal_code', 1, 0, 0, 4, '', NULL, 'User and User Admin Only', 0, 0, 1, NULL, '郵遞區號', 'Contact', 0),
(33, 8, 'country', 0, 0, 0, 1, NULL, NULL, 'Public Pages and Listings', 0, 1, 1, NULL, '國家（住家）', 'Contact', 0),
(34, 8, 'state_province', 1, 0, 0, 2, '', NULL, 'User and User Admin Only', 0, 0, 1, NULL, '縣市', 'Contact', 0);


-- start for mapping

INSERT INTO 
  `civicrm_mapping` (`name`, `description`, `mapping_type_id`) 
VALUES
  ('[範本]聯絡人基本資料', NULL, 7),
  ('[範本]單位通訊錄', NULL, 7),
  ('[範本]捐款記錄匯出', NULL, 8),
  ('[範本]活動參加者匯出', NULL, 10),
  ('[範本]任務記錄匯出', NULL, 14),
  ('[範本]聯絡人匯入', NULL, 2),
  ('[範本]單位資料匯入', NULL, 2),
  ('[範本]任務記錄匯入', NULL, 3),
  ('[範本]捐款記錄匯入', NULL, 4),
  ('[範本]活動參加者匯入', NULL, 6);

SELECT @export_contact_id := id from civicrm_mapping where name = '[範本]聯絡人基本資料';
SELECT @export_org_id := id from civicrm_mapping where name = '[範本]單位通訊錄';
SELECT @export_contribution_id := id from civicrm_mapping where name = '[範本]捐款記錄匯出';
SELECT @export_participant_id := id from civicrm_mapping where name = '[範本]活動參加者匯出';
SELECT @export_activity_id := id from civicrm_mapping where name = '[範本]任務記錄匯出';
SELECT @import_contact_id := id from civicrm_mapping where name = '[範本]聯絡人匯入';
SELECT @import_org_id := id from civicrm_mapping where name = '[範本]單位資料匯入';
SELECT @import_activity_id := id from civicrm_mapping where name = '[範本]任務記錄匯入';
SELECT @import_contribution_id := id from civicrm_mapping where name = '[範本]捐款記錄匯入';
SELECT @import_participant_id := id from civicrm_mapping where name = '[範本]活動參加者匯入';


INSERT INTO 
  `civicrm_mapping_field` (`mapping_id`, `name`, `contact_type`, `column_number`) 
VALUES
  (@export_contact_id, 'id', 'Individual', 0),
  (@export_contact_id, 'legal_identifier', 'Individual', 1),
  (@export_contact_id, 'external_identifier', 'Individual', 2),
  (@export_contact_id, 'display_name', 'Individual', 3),
  (@export_contact_id, 'last_name', 'Individual', 4),
  (@export_contact_id, 'first_name', 'Individual', 5),
  (@export_contact_id, 'nick_name', 'Individual', 6),
  (@export_contact_id, 'individual_prefix', 'Individual', 7),
  (@export_contact_id, 'gender', 'Individual', 8),
  (@export_contact_id, 'birth_date', 'Individual', 9),
  (@export_contact_id, 'email', 'Individual', 10),
  (@export_contact_id, 'phone', 'Individual', 11),
  (@export_contact_id, 'postal_code', 'Individual', 12),
  (@export_contact_id, 'state_province', 'Individual', 13),
  (@export_contact_id, 'city', 'Individual', 14),
  (@export_contact_id, 'street_address', 'Individual', 15),
  (@export_org_id, 'id', 'Organization', 0),
  (@export_org_id, 'organization_name', 'Organization', 1),
  (@export_org_id, 'sic_code', 'Organization', 2),
  (@export_org_id, 'email', 'Organization', 3),
  (@export_org_id, 'phone', 'Organization', 4),
  (@export_org_id, 'url', 'Organization', 5),
  (@export_org_id, 'postal_code', 'Organization', 6),
  (@export_org_id, 'state_province', 'Organization', 7),
  (@export_org_id, 'city', 'Organization', 8),
  (@export_org_id, 'street_address', 'Organization', 9),
  (@export_contribution_id, 'contribution_id', 'Contribution', 0),
  (@export_contribution_id, 'trxn_id', 'Contribution', 1),
  (@export_contribution_id, 'receive_date', 'Contribution', 2),
  (@export_contribution_id, 'total_amount', 'Contribution', 3),
  (@export_contribution_id, 'contribution_type', 'Contribution', 4),
  (@export_contribution_id, 'contribution_status', 'Contribution', 5),
  (@export_contribution_id, 'contribution_source', 'Contribution', 6),
  (@export_contribution_id, 'custom_11', 'Contribution', 7),
  (@export_contribution_id, 'custom_10', 'Contribution', 8),
  (@export_contribution_id, 'last_name', 'Individual', 9),
  (@export_contribution_id, 'first_name', 'Individual', 10),
  (@export_contribution_id, 'email', 'Individual', 11),
  (@export_contribution_id, 'phone', 'Individual', 12),
  (@export_contribution_id, 'state_province', 'Individual', 13),
  (@export_contribution_id, 'city', 'Individual', 14),
  (@export_contribution_id, 'street_address', 'Individual', 15),
  (@export_contribution_id, 'postal_code', 'Individual', 16),
  (@export_contribution_id, 'id', 'Individual', 17),
  (@export_contribution_id, 'id', 'Individual', 18),
  (@export_participant_id, 'participant_id', 'Participant', 0),
  (@export_participant_id, 'last_name', 'Individual', 1),
  (@export_participant_id, 'first_name', 'Individual', 2),
  (@export_participant_id, 'individual_prefix', 'Individual', 3),
  (@export_participant_id, 'participant_register_date', 'Participant', 4),
  (@export_participant_id, 'participant_status', 'Participant', 5),
  (@export_participant_id, 'participant_role', 'Participant', 6),
  (@export_participant_id, 'event_id', 'Participant', 7),
  (@export_participant_id, 'participant_fee_level', 'Participant', 8),
  (@export_participant_id, 'email', 'Individual', 9),
  (@export_participant_id, 'phone', 'Individual', 10),
  (@export_participant_id, 'id', 'Individual', 11),
  (@export_activity_id, 'activity_id', 'Activity', 0),
  (@export_activity_id, 'activity_type', 'Activity', 1),
  (@export_activity_id, 'activity_status', 'Activity', 2),
  (@export_activity_id, 'activity_date_time', 'Activity', 3),
  (@export_activity_id, 'activity_duration', 'Activity', 4),
  (@export_activity_id, 'activity_subject', 'Activity', 5),
  (@export_activity_id, 'source_contact_id', 'Activity', 6),
  (@export_activity_id, 'id', 'Individual', 7),
  (@import_contact_id, '系統編號', 'Individual', 0),
  (@import_contact_id, '身分證字號', 'Individual', 1),
  (@import_contact_id, '外部編號', 'Individual', 2),
  (@import_contact_id, '- 不要匯入 -', 'Individual', 3),
  (@import_contact_id, '姓氏', 'Individual', 4),
  (@import_contact_id, '名字 ', 'Individual', 5),
  (@import_contact_id, '暱稱', 'Individual', 6),
  (@import_contact_id, '個人稱謂', 'Individual', 7),
  (@import_contact_id, '性別', 'Individual', 8),
  (@import_contact_id, '出生日期', 'Individual', 9),
  (@import_contact_id, 'Email (match to contact)', 'Individual', 10),
  (@import_contact_id, '電話', 'Individual', 11),
  (@import_contact_id, '郵遞區號', 'Individual', 12),
  (@import_contact_id, '縣市', 'Individual', 13),
  (@import_contact_id, '鄉鎮市區', 'Individual', 14),
  (@import_contact_id, '街道地址', 'Individual', 15),
  (@import_org_id, '系統編號', 'Organization', 0),
  (@import_org_id, '單位抬頭 (match to contact)', 'Organization', 1),
  (@import_org_id, 'Sic Code', 'Organization', 2),
  (@import_org_id, 'Email (match to contact)', 'Organization', 3),
  (@import_org_id, '電話', 'Organization', 4),
  (@import_org_id, '網站', 'Organization', 5),
  (@import_org_id, '郵遞區號', 'Organization', 6),
  (@import_org_id, '縣市', 'Organization', 7),
  (@import_org_id, '鄉鎮市區', 'Organization', 8),
  (@import_org_id, '街道地址', 'Organization', 9),
  (@import_activity_id, '- 不要匯入 -', NULL, 0),
  (@import_activity_id, '任務類型顯示名稱', NULL, 1),
  (@import_activity_id, '任務狀態', NULL, 2),
  (@import_activity_id, '任務日期', NULL, 3),
  (@import_activity_id, '時間長短', NULL, 4),
  (@import_activity_id, '主旨', NULL, 5),
  (@import_activity_id, '來源聯絡人', NULL, 6),
  (@import_activity_id, '系統編號(與聯絡人對應)', NULL, 7),
  (@import_contribution_id, '- 不要匯入 -', NULL, 0),
  (@import_contribution_id, '交易編號', NULL, 1),
  (@import_contribution_id, '收到日期', NULL, 2),
  (@import_contribution_id, '總金額', NULL, 3),
  (@import_contribution_id, '捐款類型', NULL, 4),
  (@import_contribution_id, '捐款狀態', NULL, 5),
  (@import_contribution_id, '捐款來源', NULL, 6),
  (@import_contribution_id, '匿名捐款顯示名稱', NULL, 7),
  (@import_contribution_id, '收據寄發方式', NULL, 8),
  (@import_contribution_id, 'Email (與聯絡人對應)', NULL, 9),
  (@import_contribution_id, '系統編號 (與聯絡人對應)', NULL, 10),
  (@import_participant_id, '- 不要匯入 -', NULL, 0),
  (@import_participant_id, '- 不要匯入 -', NULL, 1),
  (@import_participant_id, '- 不要匯入 -', NULL, 2),
  (@import_participant_id, '- 不要匯入 -', NULL, 3),
  (@import_participant_id, '報名日期', NULL, 4),
  (@import_participant_id, '參加者狀態', NULL, 5),
  (@import_participant_id, '參加者身分', NULL, 6),
  (@import_participant_id, '活動名稱', NULL, 7),
  (@import_participant_id, '費用級別', NULL, 8),
  (@import_participant_id, 'Email (match to contact)', NULL, 9),
  (@import_participant_id, '- 不要匯入 -', NULL, 10),
  (@import_participant_id, '系統編號 (match to contact)', NULL, 11);


-- remove custom search at navigation
UPDATE civicrm_navigation SET is_active = 0 WHERE url LIKE 'civicrm/contact/search/custom%csid=8%';
UPDATE civicrm_navigation SET is_active = 0 WHERE url LIKE 'civicrm/contact/search/custom%csid=11%';
UPDATE civicrm_navigation SET is_active = 0 WHERE url LIKE 'civicrm/contact/search/custom%csid=2%';
UPDATE civicrm_navigation SET is_active = 0 WHERE url LIKE 'civicrm/contact/search/custom%csid=6%';
UPDATE civicrm_navigation SET is_active = 0 WHERE url LiKE 'civicrm/contact/search/custom/list%';

-- translate profile group
UPDATE civicrm_uf_group SET  title =  '聯絡人摘要' WHERE  civicrm_uf_group.id =7;
UPDATE civicrm_uf_group SET  title =  '共享地址' WHERE  civicrm_uf_group.id =8;

