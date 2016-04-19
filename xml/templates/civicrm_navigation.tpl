-- +--------------------------------------------------------------------+
-- | CiviCRM version 3.3                                                |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2010                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
-- Navigation Menu, Preferences and Mail Settings

SELECT @domainID := id FROM civicrm_domain where name = 'Default Domain Name';

-- Initial default state of system preferences
{literal}
INSERT INTO 
     civicrm_preferences(domain_id, contact_id, is_domain, contact_view_options, contact_edit_options, advanced_search_options, user_dashboard_options, address_options, address_format, mailing_format, display_name_format, sort_name_format, address_standardization_provider, address_standardization_userid, address_standardization_url, editor_id, mailing_backend, contact_autocomplete_options )
VALUES 
     (@domainID,NULL,1,'123456789101113','1234567891011','1234567891011121315161718','1234578','123456891011','{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}','{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}','{contact.individual_prefix}{ }{contact.first_name}{ }{contact.last_name}{ }{contact.individual_suffix}','{contact.last_name}{, }{contact.first_name}',NULL,NULL,NULL,2,'a:1:{s:15:"outBound_option";s:1:"3";}','12');
{/literal}

-- mail settings 

INSERT INTO civicrm_mail_settings (domain_id, name, is_default, domain) VALUES (@domainID, 'default', true, 'FIXME.ORG');

-- activity and case dashlets 

INSERT INTO `civicrm_dashboard` 
    ( `domain_id`, `label`, `url`, `content`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `created_date`, `is_fullscreen`, `is_reserved`) 
    VALUES 
    ( @domainID, '{ts escape="sql"}Activities{/ts}', 'civicrm/dashlet/activity&reset=1&snippet=4', NULL, 'access CiviCRM', NULL, 0, 0, 1, 1, NULL, 1, 1),
    ( @domainID, '{ts escape="sql"}My Cases{/ts}', 'civicrm/dashlet/myCases&reset=1&snippet=4', NULL, 'access my cases and activities', NULL , 0, 0, 1, 2, NULL, 1, 1),
    ( @domainID, '{ts escape="sql"}All Cases{/ts}', 'civicrm/dashlet/allCases&reset=1&snippet=4', NULL, 'access all cases and activities', NULL , 0, 0, 1, 3, NULL, 1, 1);

-- navigation 

INSERT INTO civicrm_navigation
 ( domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
 ( @domainID, '{ts escape="sql" skip="true"}Homepage{/ts}', 'Home', 'civicrm/dashboard&reset=1', NULL, '', NULL, 1, NULL, 0);
 
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    (  @domainID, NULL, '{ts escape="sql" skip="true"}Search{/ts}',  'Search...',    NULL, '',  NULL, '1', NULL, 1 );

SET @searchlastID:=LAST_INSERT_ID();
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/contact/search&reset=1',                          '{ts escape="sql" skip="true"}Find Contacts{/ts}',      'Find Contacts', NULL, '',                      @searchlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/contact/search/advanced&reset=1',                 '{ts escape="sql" skip="true"}Advanced Search{/ts}',    'Advanced Search', NULL, '', @searchlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/contact/search/custom&csid=15&reset=1',           '{ts escape="sql" skip="true"}Full-text Search{/ts}',   'Full-text Search', NULL, '',                   @searchlastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/contact/search/builder&reset=1',                  '{ts escape="sql" skip="true"}Search Builder{/ts}',     'Search Builder', NULL, '',                     @searchlastID, '1', '1',  4 ), 
    ( @domainID, 'civicrm/case/search&reset=1',                             '{ts escape="sql" skip="true"}Find Cases{/ts}',         'Find Cases', 'access my cases and activities,access all cases and activities', 'OR',            @searchlastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/contribute/search&reset=1',                       '{ts escape="sql" skip="true"}Find Contributions{/ts}', 'Find Contributions', 'access CiviContribute', '',  @searchlastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/mailing&reset=1',                                 '{ts escape="sql" skip="true"}Find Mailings{/ts}',      'Find Mailings', 'access CiviMail', '',         @searchlastID, '1', NULL, 7 ), 
    ( @domainID, 'civicrm/member/search&reset=1',                           '{ts escape="sql" skip="true"}Find Members{/ts}',       'Find Members', 'access CiviMember', '',        @searchlastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/event/search&reset=1',                            '{ts escape="sql" skip="true"}Find Participants{/ts}',  'Find Participants',  'access CiviEvent', '',   @searchlastID, '1', NULL, 9 ), 
    ( @domainID, 'civicrm/pledge/search&reset=1',                           '{ts escape="sql" skip="true"}Find Pledges{/ts}',       'Find Pledges', 'access CiviPledge', '',        @searchlastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/activity/search&reset=1',                         '{ts escape="sql" skip="true"}Find Activities{/ts}',    'Find Activities', NULL,  '',                   @searchlastID, '1', '1',  11 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/contact/search/custom/list&reset=1',              '{ts escape="sql" skip="true"}Custom Searches...{/ts}', 'Custom Searches...', NULL, '',                 @searchlastID, '1', NULL, 12 );

SET @customSearchlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/contact/search/custom&reset=1&csid=8',            '{ts escape="sql" skip="true"}Activity Search{/ts}',                  'Activity Search',                  NULL, '', @customSearchlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/contact/search/custom&reset=1&csid=11',           '{ts escape="sql" skip="true"}Contacts by Date Added{/ts}',           'Contacts by Date Added',           NULL, '', @customSearchlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/contact/search/custom&reset=1&csid=2',            '{ts escape="sql" skip="true"}Contributors by Aggregate Totals{/ts}', 'Contributors by Aggregate Totals', NULL, '', @customSearchlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/contact/search/custom&reset=1&csid=6',            '{ts escape="sql" skip="true"}Proximity Search{/ts}',                 'Proximity Search',                 NULL, '', @customSearchlastID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}Contacts{/ts}', 'Contacts', NULL, '', NULL, '1', NULL, 3 );

SET @contactlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES        
    ( @domainID, 'civicrm/contact/add&reset=1&ct=Individual',       '{ts escape="sql" skip="true"}New Individual{/ts}',         'New Individual',       'add contacts',     '',             @contactlastID, '1', NULL,  1 ),
    ( @domainID, 'civicrm/contact/add&reset=1&ct=Household',        '{ts escape="sql" skip="true"}New Household{/ts}',          'New Household',        'add contacts',     '',             @contactlastID, '1', NULL,  2 ),
       ( @domainID, 'civicrm/contact/add&reset=1&ct=Organization',  '{ts escape="sql" skip="true"}New Organization{/ts}',       'New Organization',     'add contacts',     '',             @contactlastID, '1', 1,     3 );


INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/activity&reset=1&action=add&context=standalone',  '{ts escape="sql" skip="true"}New Activity{/ts}',           'New Activity',         NULL,               '',             @contactlastID, '1', NULL,  4 ), 
    ( @domainID, 'civicrm/activity/add&atype=3&action=add&reset=1&context=standalone', '{ts escape="sql" skip="true"}New Email{/ts}',   'New Email',            NULL,               '',             @contactlastID, '1', '1',   5 ), 
    ( @domainID, 'civicrm/import/contact&reset=1',                          '{ts escape="sql" skip="true"}Import Contacts{/ts}',        'Import Contacts',      'import contacts',  '',             @contactlastID, '1', NULL,  6 ), 
    ( @domainID, 'civicrm/import/activity&reset=1',                         '{ts escape="sql" skip="true"}Import Activities{/ts}',      'Import Activities',    'import contacts',  '',             @contactlastID, '1', '1',   7 ), 
    ( @domainID, 'civicrm/group/add&reset=1',                               '{ts escape="sql" skip="true"}New Group{/ts}',              'New Group',            'edit groups',      '',             @contactlastID, '1', NULL,  8 ), 
    ( @domainID, 'civicrm/group&reset=1',                                   '{ts escape="sql" skip="true"}Manage Groups{/ts}',          'Manage Groups',        'access CiviCRM',   '',             @contactlastID, '1', '1',   9 ), 
    ( @domainID, 'civicrm/admin/tag&reset=1&action=add',                    '{ts escape="sql" skip="true"}New Tag{/ts}',                'New Tag',              'administer CiviCRM', '',           @contactlastID, '1', NULL, 10 ), 
    ( @domainID, 'civicrm/admin/tag&reset=1',                               '{ts escape="sql" skip="true"}Manage Tags (Categories){/ts}', 'Manage Tags (Categories)', 'administer CiviCRM', '',     @contactlastID, '1','1', 11 ),
    ( @domainID, 'civicrm/contact/deduperules&reset=1',  '{ts escape="sql" skip="true"}Find and Merge Duplicate Contacts{/ts}', 'Find and Merge Duplicate Contacts', 'administer dedupe rules,merge duplicate contacts', 'OR', @contactlastID, '1', NULL, 12 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}Contributions{/ts}', 'Contributions', 'access CiviContribute', '',      NULL,           '1', NULL,  4 );

SET @contributionlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/contribute&reset=1',                              '{ts escape="sql" skip="true"}Dashboard{/ts}',              'Dashboard',              'access CiviContribute', '', @contributionlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/contribute/add&reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Contribution{/ts}',  'New Contribution',       'access CiviContribute,edit contributions', 'AND', @contributionlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/contribute/search&reset=1',                       '{ts escape="sql" skip="true"}Find Contributions{/ts}',     'Find Contributions',     'access CiviContribute', '', @contributionlastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/contribute/import&reset=1',                       '{ts escape="sql" skip="true"}Import Contributions{/ts}',   'Import Contributions',   'access CiviContribute,edit contributions', 'AND', @contributionlastID, '1', '1',  4 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID,NULL, '{ts escape="sql" skip="true"}Pledges{/ts}',  'Pledges', 'access CiviPledge', '', @contributionlastID, '1',  1,   5 );
    
SET @pledgelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/pledge&reset=1',                                  '{ts escape="sql" skip="true"}Dashboard{/ts}',                  'Dashboard',                 'access CiviPledge',  '',  @pledgelastID,       '1', NULL, 1 ), 
    ( @domainID, 'civicrm/pledge/add&reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Pledge{/ts}',                'New Pledge',                'access CiviPledge,edit pledges',  'AND',  @pledgelastID,       '1', NULL, 2 ),
    ( @domainID, 'civicrm/pledge/search&reset=1',                           '{ts escape="sql" skip="true"}Find Pledges{/ts}',               'Find Pledges',              'access CiviPledge',  '',  @pledgelastID,       '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/contribute/add&reset=1&action=add',             '{ts escape="sql" skip="true"}New Contribution Page{/ts}',      'New Contribution Page',     'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/contribute&reset=1',                        '{ts escape="sql" skip="true"}Manage Contribution Pages{/ts}',  'Manage Contribution Pages', 'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', '1',  7 ), 
    ( @domainID, 'civicrm/admin/pcp&reset=1',                               '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/admin/contribute/managePremiums&reset=1',         '{ts escape="sql" skip="true"}Premiums (Thank-you Gifts){/ts}', 'Premiums',                  'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', 1,    9 ),
    ( @domainID, 'civicrm/admin/price&reset=1&action=add',                  '{ts escape="sql" skip="true"}New Price Set{/ts}',              'New Price Set',             'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/admin/price&reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',          'Manage Price Sets',         'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', NULL, 11 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Events{/ts}',  'Events', 'access CiviEvent', '', NULL, '1', NULL, 5 );

SET @eventlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/event&reset=1',                                   '{ts escape="sql" skip="true"}Dashboard{/ts}',          'CiviEvent Dashboard',  'access CiviEvent', '',    @eventlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/participant/add&reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}Register Event Participant{/ts}', 'Register Event Participant', 'access CiviEvent,edit event participants', 'AND', @eventlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/event/search&reset=1',                            '{ts escape="sql" skip="true"}Find Participants{/ts}',  'Find Participants',    'access CiviEvent', '',    @eventlastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/event/import&reset=1',                            '{ts escape="sql" skip="true"}Import Participants{/ts}','Import Participants',  'access CiviEvent,edit event participants', 'AND',    @eventlastID, '1', '1',  4 ), 
    ( @domainID, 'civicrm/event/add&reset=1&action=add',                    '{ts escape="sql" skip="true"}New Event{/ts}',          'New Event',            'access CiviEvent,administer CiviCRM', 'AND',    @eventlastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/event/manage&reset=1',                            '{ts escape="sql" skip="true"}Manage Events{/ts}',      'Manage Events',        'access CiviEvent,administer CiviCRM', 'AND',    @eventlastID, '1', 1, 6 ), 
    ( @domainID, 'civicrm/admin/eventTemplate&reset=1',                     '{ts escape="sql" skip="true"}Event Templates{/ts}',    'Event Templates',      'access CiviEvent,administer CiviCRM', 'AND',    @eventlastID, '1', 1, 7 ), 
    ( @domainID, 'civicrm/admin/price&reset=1&action=add',                  '{ts escape="sql" skip="true"}New Price Set{/ts}',      'New Price Set',        'access CiviEvent,administer CiviCRM', 'AND',    @eventlastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/admin/price&reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',  'Manage Price Sets',    'access CiviEvent,administer CiviCRM', 'AND',    @eventlastID, '1', NULL, 9 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Mailings{/ts}', 'Mailings', 'access CiviMail', '', NULL, '1', NULL, 6 );

SET @mailinglastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/mailing/send&reset=1',                            '{ts escape="sql" skip="true"}New Mailing{/ts}', 'New Mailing',                                          'access CiviMail', '', @mailinglastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/mailing/browse/unscheduled&reset=1&scheduled=false', '{ts escape="sql" skip="true"}Draft and Unscheduled Mailings{/ts}', 'Draft and Unscheduled Mailings', 'access CiviMail', '', @mailinglastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/mailing/browse/scheduled&reset=1&scheduled=true', '{ts escape="sql" skip="true"}Scheduled and Sent Mailings{/ts}', 'Scheduled and Sent Mailings',          'access CiviMail', '', @mailinglastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/mailing/browse/archived&reset=1',                 '{ts escape="sql" skip="true"}Archived Mailings{/ts}', 'Archived Mailings',                              'access CiviMail', '', @mailinglastID, '1', 1,    4 ), 
    ( @domainID, 'civicrm/admin/component&reset=1',                         '{ts escape="sql" skip="true"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', 'access CiviMail,administer CiviCRM', 'AND', @mailinglastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/messageTemplates&reset=1',                  '{ts escape="sql" skip="true"}Message Templates{/ts}', 'Message Templates',                 'administer CiviCRM', '', @mailinglastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/options/from_email&group=from_email_address&reset=1', '{ts escape="sql" skip="true"}From Email Addresses{/ts}', 'From Email Addresses', 'administer CiviCRM', '', @mailinglastID, '1', NULL, 7 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Memberships{/ts}', 'Memberships', 'access CiviMember', '', NULL, '1', NULL, 7 );

SET @memberlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/member&reset=1',                              '{ts escape="sql" skip="true"}Dashboard{/ts}',           'Dashboard',       'access CiviMember', '', @memberlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/member/add&reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Membership{/ts}', 'New Membership',  'access CiviMember,edit memberships', 'AND', @memberlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/member/search&reset=1',                       '{ts escape="sql" skip="true"}Find Members{/ts}',        'Find Members',    'access CiviMember', '', @memberlastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/member/import&reset=1',                       '{ts escape="sql" skip="true"}Import Memberships{/ts}',      'Import Members',  'access CiviMember,edit memberships', 'AND', @memberlastID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Other{/ts}', 'Other', 'access CiviGrant,administer CiviCase,access my cases and activities,access all cases and activities,administer CiviCampaign,manage campaign,reserve campaign contacts,release campaign contacts,interview campaign contacts,gotv campaign contacts', 'OR', NULL, '1', NULL, 9 );
    
SET @otherlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Cases{/ts}', 'Cases', 'access my cases and activities,access all cases and activities', 'OR', @otherlastID, '1', NULL, 1 );

SET @caselastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/case&reset=1',        '{ts escape="sql" skip="true"}Dashboard{/ts}', 'Dashboard', 'access my cases and activities,access all cases and activities', 'OR',       @caselastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/case/add&reset=1&action=add&atype=13&context=standalone', '{ts escape="sql" skip="true"}New Case{/ts}', 'New Case', 'add contacts,access all cases and activities', 'AND', @caselastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/case/search&reset=1', '{ts escape="sql" skip="true"}Find Cases{/ts}', 'Find Cases', 'access my cases and activities,access all cases and activities', 'OR',     @caselastID, '1', 1, 3 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Grants{/ts}', 'Grants', 'access CiviGrant', '', @otherlastID, '1', NULL, 2 );

SET @grantlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES        
    ( @domainID, 'civicrm/grant&reset=1',           '{ts escape="sql" skip="true"}Dashboard{/ts}', 'Dashboard', 'access CiviGrant', '',       @grantlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/grant/add&reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Grant{/ts}', 'New Grant', 'access CiviGrant,edit grants', 'AND', @grantlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/grant/search&reset=1',    '{ts escape="sql" skip="true"}Find Grants{/ts}', 'Find Grants', 'access CiviGrant', '',   @grantlastID, '1', 1, 3 );


INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Campaigns{/ts}', 'Campaigns', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign,gotv campaign contacts', 'OR', @otherlastID, '1', NULL, 3 );

SET @campaignlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/campaign&reset=1',        '{ts escape="sql" skip="true"}Dashboard{/ts}', 'Dashboard', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 1 );
SET @campaigndashboardlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/campaign&reset=1&subPage=survey',        '{ts escape="sql" skip="true"}Surveys{/ts}', 'Survey Dashboard', 'manage campaign,administer CiviCampaign', 'OR', @campaigndashboardlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/campaign&reset=1&subPage=petition',        '{ts escape="sql" skip="true"}Petitions{/ts}', 'Petition Dashboard', 'manage campaign,administer CiviCampaign', 'OR', @campaigndashboardlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/campaign&reset=1&subPage=campaign',        '{ts escape="sql" skip="true"}Campaigns{/ts}', 'Campaign Dashboard', 'manage campaign,administer CiviCampaign', 'OR', @campaigndashboardlastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/campaign/add&reset=1',        '{ts escape="sql" skip="true"}New Campaign{/ts}', 'New Campaign', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/survey/add&reset=1',        '{ts escape="sql" skip="true"}New Survey{/ts}', 'New Survey', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/petition/add&reset=1',        '{ts escape="sql" skip="true"}New Petition{/ts}', 'New Petition', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=reserve', '{ts escape="sql" skip="true"}Reserve Respondents{/ts}', 'Reserve Respondents', 'administer CiviCampaign,manage campaign,reserve campaign contacts', 'OR', @campaignlastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=interview', '{ts escape="sql" skip="true"}Interview Respondents{/ts}', 'Interview Respondents', 'administer CiviCampaign,manage campaign,interview campaign contacts', 'OR', @campaignlastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=release', '{ts escape="sql" skip="true"}Release Respondents{/ts}', 'Release Respondents', 'administer CiviCampaign,manage campaign,release campaign contacts', 'OR', @campaignlastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/campaign/gotv&reset=1', '{ts escape="sql" skip="true"}GOTV (Voter Tracking){/ts}', 'Voter Listing', 'administer CiviCampaign,manage campaign,release campaign contacts,gotv campaign contacts', 'OR', @campaignlastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/campaign/vote&reset=1', '{ts escape="sql" skip="true"}Conduct Survey{/ts}', 'Conduct Survey', 'administer CiviCampaign,manage campaign,reserve campaign contacts,interview campaign contacts', 'OR', @campaignlastID, '1', NULL, 9 );

    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Administer{/ts}', 'Administer', 'administer CiviCRM', '', NULL, '1', NULL, 10 );

SET @adminlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin&reset=1', '{ts escape="sql" skip="true"}Administration Console{/ts}', 'Administration Console', 'administer CiviCRM', '', @adminlastID, '1', NULL, 1 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Customize{/ts}', 'Customize', 'administer CiviCRM', '', @adminlastID, '1', NULL, 2 );

SET @CustomizelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES            
    ( @domainID, 'civicrm/admin/custom/group&reset=1',      '{ts escape="sql" skip="true"}Custom Data{/ts}',     'Custom Data',     'administer CiviCRM', '', @CustomizelastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/uf/group&reset=1',          '{ts escape="sql" skip="true"}CiviCRM Profile{/ts}', 'CiviCRM Profile', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/menu&reset=1',              '{ts escape="sql" skip="true"}Navigation Menu{/ts}', 'Navigation Menu', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/options/custom_search&reset=1&group=custom_search', '{ts escape="sql" skip="true"}Manage Custom Searches{/ts}', 'Manage Custom Searches', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/price&reset=1',             '{ts escape="sql" skip="true"}Price Sets{/ts}',        'Price Sets',        'administer CiviCRM', '', @CustomizelastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/extensions&reset=1',        '{ts escape="sql" skip="true"}Manage CiviCRM Extensions{/ts}', 'Manage CiviCRM Extensions', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 6 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Configure{/ts}', 'Configure', 'administer CiviCRM', '', @adminlastID, '1', NULL, 3 );

SET @configurelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/configtask&reset=1',                    '{ts escape="sql" skip="true"}Configuration Checklist{/ts}', 'Configuration Checklist', 'administer CiviCRM', '', @configurelastID, '1', NULL, 1 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/admin/setting&reset=1',                       '{ts escape="sql" skip="true"}Global Settings{/ts}',         'Global Settings',         'administer CiviCRM', '', @configurelastID, '1', NULL, 2 );

SET @globalSettinglastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/setting/component&reset=1',             '{ts escape="sql" skip="true"}Enable CiviCRM Components{/ts}', 'Enable Components', 'administer CiviCRM', '',   @globalSettinglastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/setting/preferences/display&reset=1',   '{ts escape="sql" skip="true"}Site Preferences (screen and form configuration){/ts}', 'Site Preferences', 'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/setting/path&reset=1',                  '{ts escape="sql" skip="true"}Directories{/ts}',        'Directories',      'administer CiviCRM', '',        @globalSettinglastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/setting/url&reset=1',                   '{ts escape="sql" skip="true"}Resource URLs{/ts}',      'Resource URLs',    'administer CiviCRM', '',      @globalSettinglastID, '1', NULL, 4 ), 
    ( @domainID, 'civicrm/admin/setting/smtp&reset=1',                  '{ts escape="sql" skip="true"}Outbound Email (SMTP/Sendmail){/ts}', 'Outbound Email', 'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/admin/setting/mapping&reset=1',               '{ts escape="sql" skip="true"}Mapping and Geocoding{/ts}', 'Mapping and Geocoding',   'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/paymentProcessor&reset=1',              '{ts escape="sql" skip="true"}Payment Processors{/ts}', 'Payment Processors', 'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 7  ), 
    ( @domainID, 'civicrm/admin/setting/localization&reset=1',          '{ts escape="sql" skip="true"}Localization{/ts}',       'Localization',     'administer CiviCRM', '',       @globalSettinglastID, '1', NULL, 8  ), 
    ( @domainID, 'civicrm/admin/setting/preferences/address&reset=1',   '{ts escape="sql" skip="true"}Address Settings{/ts}',   'Address Settings', 'administer CiviCRM', '',   @globalSettinglastID, '1', NULL, 9  ), 
    ( @domainID, 'civicrm/admin/setting/search&reset=1',                '{ts escape="sql" skip="true"}Search Settings{/ts}',    'Search Settings',  'administer CiviCRM', '',    @globalSettinglastID, '1', NULL, 10 ), 
    ( @domainID, 'civicrm/admin/setting/date&reset=1',                  '{ts escape="sql" skip="true"}Date Formats{/ts}',       'Date Formats',     'administer CiviCRM', '',       @globalSettinglastID, '1', NULL, 11 ), 
    ( @domainID, 'civicrm/admin/setting/uf&reset=1',                    '{ts escape="sql" skip="true"}CMS Integration{/ts}',    'CMS Integration',  'administer CiviCRM', '',    @globalSettinglastID, '1', NULL, 12 ), 
    ( @domainID, 'civicrm/admin/setting/misc&reset=1',                  '{ts escape="sql" skip="true"}Miscellaneous (version check, reCAPTCHA...){/ts}', 'Miscellaneous', 'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 13 ), 
    ( @domainID, 'civicrm/admin/options/safe_file_extension&group=safe_file_extension&reset=1', '{ts escape="sql" skip="true"}Safe File Extensions{/ts}', 'Safe File Extensions', 'administer CiviCRM', '', @globalSettinglastID, '1', NULL, 14 ), 
    ( @domainID, 'civicrm/admin/setting/debug&reset=1',                 '{ts escape="sql" skip="true"}Debugging{/ts}',      'Debugging', 'administer CiviCRM', '',              @globalSettinglastID, '1', NULL, 15 ), 
    
    ( @domainID, 'civicrm/admin/mapping&reset=1',                      '{ts escape="sql" skip="true"}Import/Export Mappings{/ts}', 'Import/Export Mappings',   'administer CiviCRM', '', @configurelastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/messageTemplates&reset=1',             '{ts escape="sql" skip="true"}Message Templates{/ts}',      'Message Templates',        'administer CiviCRM', '', @configurelastID, '1', NULL, 4 ), 
    ( @domainID, 'civicrm/admin/domain&action=update&reset=1',         '{ts escape="sql" skip="true"}Domain Information{/ts}',     'Domain Information',       'administer CiviCRM', '', @configurelastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/admin/options/from_email_address&group=from_email_address&reset=1', '{ts escape="sql" skip="true"}FROM Email Addresses{/ts}', 'FROM Email Addresses',    'administer CiviCRM', '', @configurelastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/setting/updateConfigBackend&reset=1',  '{ts escape="sql" skip="true"}Update Directory Path and URL{/ts}', 'Update Directory Path and URL',         'administer CiviCRM', '', @configurelastID, '1', NULL, 7 );

    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES         
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Manage{/ts}', 'Manage', 'administer CiviCRM', '', @adminlastID, '1', NULL, 4 );

SET @managelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/admin/access&reset=1',       '{ts escape="sql" skip="true"}Access Control{/ts}',                    'Access Control',                    'administer CiviCRM', '', @managelastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/synchUser&reset=1',    '{ts escape="sql" skip="true"}Synchronize Users to Contacts{/ts}',     'Synchronize Users to Contacts',     'administer CiviCRM', '', @managelastID, '1', NULL, 2 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Option Lists{/ts}', 'Option Lists', 'administer CiviCRM', '', @adminlastID, '1', NULL, 5 );
    
SET @optionListlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES         
    ( @domainID, 'civicrm/admin/options/activity_type&reset=1&group=activity_type',                                    '{ts escape="sql" skip="true"}Activity Types{/ts}',         'Activity Types',                           'administer CiviCRM', '',   @optionListlastID, '1', NULL,  1 ), 
    ( @domainID, 'civicrm/admin/reltype&reset=1',                                                                      '{ts escape="sql" skip="true"}Relationship Types{/ts}',     'Relationship Types',                       'administer CiviCRM', '',   @optionListlastID, '1', NULL,  2 ), 
    ( @domainID, 'civicrm/admin/tag&reset=1',                                                                          '{ts escape="sql" skip="true"}Tags (Categories){/ts}',      'Tags (Categories)',                        'administer CiviCRM', '',   @optionListlastID, '1', 1,     3 ), 
    ( @domainID, 'civicrm/admin/options/gender&reset=1&group=gender',                                                  '{ts escape="sql" skip="true"}Gender Options{/ts}',         'Gender Options',                           'administer CiviCRM', '',   @optionListlastID, '1', NULL,  4 ), 
    ( @domainID, 'civicrm/admin/options/individual_prefix&group=individual_prefix&reset=1',                            '{ts escape="sql" skip="true"}Individual Prefixes (Ms, Mr...){/ts}', 'Individual Prefixes (Ms, Mr...)', 'administer CiviCRM', '',   @optionListlastID, '1', NULL,  5 ), 
    ( @domainID, 'civicrm/admin/options/individual_suffix&group=individual_suffix&reset=1',                            '{ts escape="sql" skip="true"}Individual Suffixes (Jr, Sr...){/ts}', 'Individual Suffixes (Jr, Sr...)', 'administer CiviCRM', '',   @optionListlastID, '1', 1,     6 ), 
    ( @domainID, 'civicrm/admin/options/addressee&group=addressee&reset=1',                                            '{ts escape="sql" skip="true"}Addressee Formats{/ts}',      'Addressee Formats',                        'administer CiviCRM', '',   @optionListlastID, '1', NULL,  7 ), 
    ( @domainID, 'civicrm/admin/options/email_greeting&group=email_greeting&reset=1',                                  '{ts escape="sql" skip="true"}Email Greetings{/ts}',        'Email Greetings',                          'administer CiviCRM', '',   @optionListlastID, '1', NULL,  8 ), 
    ( @domainID, 'civicrm/admin/options/postal_greeting&group=postal_greeting&reset=1',                                '{ts escape="sql" skip="true"}Postal Greetings{/ts}',       'Postal Greetings',                         'administer CiviCRM', '',   @optionListlastID, '1', 1,     9 ), 
    ( @domainID, 'civicrm/admin/options/instant_messenger_service&group=instant_messenger_service&reset=1',            '{ts escape="sql" skip="true"}Instant Messenger Services{/ts}',     'Instant Messenger Services',       'administer CiviCRM', '',   @optionListlastID, '1', NULL, 10 ), 
    ( @domainID, 'civicrm/admin/locationType&reset=1',                                                                 '{ts escape="sql" skip="true"}Location Types (Home, Work...){/ts}', 'Location Types (Home, Work...)',   'administer CiviCRM', '',   @optionListlastID, '1', NULL, 11 ), 
    ( @domainID, 'civicrm/admin/options/mobile_provider&group=mobile_provider&reset=1',                                '{ts escape="sql" skip="true"}Mobile Phone Providers{/ts}', 'Mobile Phone Providers',                   'administer CiviCRM', '',   @optionListlastID, '1', NULL, 12 ), 
    ( @domainID, 'civicrm/admin/options/phone_type&group=phone_type&reset=1',                                          '{ts escape="sql" skip="true"}Phone Types{/ts}',            'Phone Types',                              'administer CiviCRM', '',   @optionListlastID, '1', NULL, 13 ),  
    ( @domainID, 'civicrm/admin/options/website_type&group=website_type&reset=1',                                      '{ts escape="sql" skip="true"}Website Types{/ts}',          'Website Types',                            'administer CiviCRM', '',   @optionListlastID, '1', NULL, 14 ),
    ( @domainID, 'civicrm/admin/options/preferred_communication_method&group=preferred_communication_method&reset=1',  '{ts escape="sql" skip="true"}Preferred Communication Methods{/ts}', 'Preferred Communication Methods', 'administer CiviCRM', '',   @optionListlastID, '1', NULL, 15 ),
    ( @domainID, 'civicrm/admin/options/subtype&reset=1',                                                              '{ts escape="sql" skip="true"}Contact Types{/ts}',          'Contact Types',                            'administer CiviCRM', '',   @optionListlastID, '1', NULL, 16 ),
    ( @domainID, 'civicrm/admin/options/wordreplacements&reset=1',                                                     '{ts escape="sql" skip="true"}Word Replacements{/ts}',      'Word Replacements',                        'administer CiviCRM', '',   @optionListlastID, '1', NULL, 17 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}CiviCase{/ts}', 'CiviCase', 'administer CiviCase', NULL, @adminlastID, '1', NULL, 6 );

SET @adminCaselastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/options/case_type&group=case_type&reset=1',            '{ts escape="sql" skip="true"}Case Types{/ts}',      'Case Types',      'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/options/redaction_rule&group=redaction_rule&reset=1',  '{ts escape="sql" skip="true"}Redaction Rules{/ts}', 'Redaction Rules', 'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/options/case_status&group=case_status&reset=1',  '{ts escape="sql" skip="true"}Case Statuses{/ts}', 'Case Statuses', 'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 3 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES 
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}CiviContribute{/ts}', 'CiviContribute', 'access CiviContribute,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 7 );
    
SET @adminContributelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/contribute&reset=1&action=add',            '{ts escape="sql" skip="true"}New Contribution Page{/ts}',      'New Contribution Page',     'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/contribute&reset=1',                       '{ts escape="sql" skip="true"}Manage Contribution Pages{/ts}',  'Manage Contribution Pages', 'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', '1',  7 ), 
    ( @domainID, 'civicrm/admin/pcp&reset=1',                              '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/admin/contribute/managePremiums&reset=1',        '{ts escape="sql" skip="true"}Premiums (Thank-you Gifts){/ts}', 'Premiums',                  'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', 1,    9 ), 
    ( @domainID, 'civicrm/admin/contribute/contributionType&reset=1',      '{ts escape="sql" skip="true"}Contribution Types{/ts}',         'Contribution Types',        'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 10), 
    ( @domainID, 'civicrm/admin/options/payment_instrument&group=payment_instrument&reset=1',  '{ts escape="sql" skip="true"}Payment Instruments{/ts}',    'Payment Instruments',   'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 11 ), 
    ( @domainID, 'civicrm/admin/options/accept_creditcard&group=accept_creditcard&reset=1',    '{ts escape="sql" skip="true"}Accepted Credit Cards{/ts}',  'Accepted Credit Cards', 'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', 1, 12 ),
    ( @domainID, 'civicrm/admin/price&reset=1&action=add',                  '{ts escape="sql" skip="true"}New Price Set{/ts}',              'New Price Set',             'access CiviContribute,administer CiviCRM', 'AND',  @adminContributelastID, '1', NULL, 13 ),
    ( @domainID, 'civicrm/admin/price&reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',          'Manage Price Sets',         'access CiviContribute,administer CiviCRM', 'AND',  @adminContributelastID, '1', NULL, 14 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES         
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviEvent{/ts}', 'CiviEvent', 'access CiviEvent,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 8 );

SET @adminEventlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/event/add&reset=1&action=add',                   '{ts escape="sql" skip="true"}New Event{/ts}',          'New Event',                        'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/event/manage&reset=1',                           '{ts escape="sql" skip="true"}Manage Events{/ts}',      'Manage Events',                    'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    2 ), 
    ( @domainID, 'civicrm/admin/eventTemplate&reset=1',                    '{ts escape="sql" skip="true"}Event Templates{/ts}',    'Event Templates',                  'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    3 ), 
    ( @domainID, 'civicrm/admin/price&reset=1&action=add',                 '{ts escape="sql" skip="true"}New Price Set{/ts}',      'New Price Set',                    'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 4 ), 
    ( @domainID, 'civicrm/admin/price&reset=1',                            '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',  'Manage Price Sets',                'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    5 ),
    ( @domainID, 'civicrm/admin/options/participant_listing&group=participant_listing&reset=1', '{ts escape="sql" skip="true"}Participant Listing Templates{/ts}', 'Participant Listing Templates', 'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/options/event_type&group=event_type&reset=1',  '{ts escape="sql" skip="true"}Event Types{/ts}',    'Event Types',                      'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 7 ), 
    ( @domainID, 'civicrm/admin/participant_status&reset=1',                   '{ts escape="sql" skip="true"}Participant Statuses{/ts}', 'Participant Statuses',       'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/admin/options/participant_role&group=participant_role&reset=1', '{ts escape="sql" skip="true"}Participant Roles{/ts}', 'Participant Roles',  'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 9 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviGrant{/ts}', 'CiviGrant', 'access CiviGrant,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 9 );

SET @adminGrantlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/admin/options/grant_type&group=grant_type&reset=1', '{ts escape="sql" skip="true"}Grant Types{/ts}', 'Grant Types', 'access CiviGrant,administer CiviCRM', 'AND', @adminGrantlastID, '1', NULL, 1 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviMail{/ts}', 'CiviMail', 'access CiviMail,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 10 );

SET @adminMailinglastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/component&reset=1',            '{ts escape="sql" skip="true"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', 'access CiviMail,administer CiviCRM', 'AND', @adminMailinglastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/messageTemplates&reset=1',     '{ts escape="sql" skip="true"}Message Templates{/ts}', 'Message Templates', 'administer CiviCRM', '',   @adminMailinglastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/options/from_email&group=from_email_address&reset=1', '{ts escape="sql" skip="true"}From Email Addresses{/ts}', 'From Email Addresses', 'administer CiviCRM', '', @adminMailinglastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/mailSettings&reset=1',         '{ts escape="sql" skip="true"}Mail Accounts{/ts}', 'Mail Accounts', 'access CiviMail,administer CiviCRM', 'AND',           @adminMailinglastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/mail&reset=1',                 '{ts escape="sql" skip="true"}Mailer Settings{/ts}', 'Mailer Settings', 'access CiviMail,administer CiviCRM', 'AND',           @adminMailinglastID, '1', NULL, 5 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviMember{/ts}', 'CiviMember', 'access CiviMember,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 11 );

SET @adminMemberlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/admin/member/membershipType&reset=1',    '{ts escape="sql" skip="true"}Membership Types{/ts}',        'Membership Types',        'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/member/membershipStatus&reset=1',  '{ts escape="sql" skip="true"}Membership Status Rules{/ts}', 'Membership Status Rules', 'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 2 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL,                                             '{ts escape="sql" skip="true"}CiviReport{/ts}',              'CiviReport',              'access CiviReport,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 12 );

SET @adminReportlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/report/list&reset=1',                            '{ts escape="sql" skip="true"}Reports Listing{/ts}',  'Reports Listing', 'access CiviReport',    '', @adminReportlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/report/template/list&reset=1',             '{ts escape="sql" skip="true"}Create Reports from Templates{/ts}', 'Create Reports from Templates', 'administer Reports', '', @adminReportlastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/report/options/report_template&reset=1',   '{ts escape="sql" skip="true"}Manage Templates{/ts}', 'Manage Templates', 'administer Reports',  '', @adminReportlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/report/register&reset=1',                  '{ts escape="sql" skip="true"}Register Report{/ts}',  'Register Report',  'administer Reports',  '', @adminReportlastID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL,                                             '{ts escape="sql" skip="true"}CiviCampaign{/ts}',              'CiviCampaign',              'administer CiviCampaign,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 13 );

SET @adminCampaignlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, 'civicrm/admin/campaign/surveyType&reset=1',                            '{ts escape="sql" skip="true"}Survey Types{/ts}',  'Survey Types', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/options/campaign_type&group=campaign_type&reset=1',      '{ts escape="sql" skip="true"}Campaign Types{/ts}',  'Campaign Types', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/options/campaign_status&group=campaign_status&reset=1',      '{ts escape="sql" skip="true"}Campaign Status{/ts}',  'Campaign Status', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 3 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Help{/ts}', 'Help', NULL, '',  NULL, '1', NULL, 11);

SET @adminHelplastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'http://netivism.com.tw/support',   '{ts escape="sql" skip="true"}Online Support{/ts}',      'Support',    NULL, 'AND', @adminHelplastID, '1', NULL, 1 ), 
    ( @domainID, 'http://netivism.com.tw/about', '{ts escape="sql" skip="true"}About NETivism{/ts}',   'About NETivism', NULL, 'AND', @adminHelplastID, '1', NULL, 2 ), 
    ( @domainID, 'http://civicrm.org/aboutcivicrm',    '{ts escape="sql" skip="true"}About CiviCRM{/ts}',      'About',            NULL, 'AND', @adminHelplastID, '1', NULL, 3 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Reports{/ts}', 'Reports', 'access CiviReport', '',  NULL, '1', NULL, 8 );

SET @reportlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/report/list&reset=1', '{ts escape="sql" skip="true"}Reports Listing{/ts}', 'Reports Listing', 'access CiviReport', '', @reportlastID, '1', 1,    1 );
    
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/report/template/list&reset=1',  '{ts escape="sql" skip="true"}Create Reports from Templates{/ts}',  'Create Reports from Templates', 'administer Reports',  '', @reportlastID, '1', 1, 2 );
    
-- sample reports with navigation menus

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    (  @domainID, 'Constituent Report (Summary)', 'contact/summary', 'Provides a list of address and telephone information for constituent records in your system.', 'administer CiviCRM', '{literal}a:21:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:14:"street_address";s:1:"1";s:4:"city";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:92:"Provides a list of address and telephone information for constituent records in your system.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Constituent Report (Summary){/ts}',       '{literal}Constituent Report (Summary){/literal}',     'administer CiviCRM',       '',  @reportlastID,  '1', NULL, 3 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Constituent Report (Detail)', 'contact/detail', 'Provides contact-related information on contributions, memberships, events and activities.', 'administer CiviCRM', '{literal}a:19:{s:6:"fields";a:30:{s:12:"display_name";s:1:"1";s:10:"country_id";s:1:"1";s:15:"contribution_id";s:1:"1";s:12:"total_amount";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:13:"membership_id";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:20:"membership_status_id";s:1:"1";s:14:"participant_id";s:1:"1";s:8:"event_id";s:1:"1";s:21:"participant_status_id";s:1:"1";s:7:"role_id";s:1:"1";s:25:"participant_register_date";s:1:"1";s:9:"fee_level";s:1:"1";s:10:"fee_amount";s:1:"1";s:15:"relationship_id";s:1:"1";s:20:"relationship_type_id";s:1:"1";s:12:"contact_id_b";s:1:"1";s:2:"id";s:1:"1";s:16:"activity_type_id";s:1:"1";s:7:"subject";s:1:"1";s:17:"source_contact_id";s:1:"1";s:18:"activity_date_time";s:1:"1";s:18:"activity_status_id";s:1:"1";s:17:"target_contact_id";s:1:"1";s:19:"assignee_contact_id";s:1:"1";}s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:90:"Provides contact-related information on contributions, memberships, events and activities.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Constituent Report (Detail){/ts}',        '{literal}Constituent Report (Detail){/literal}',      'administer CiviCRM',       '',  @reportlastID,  '1', NULL, 4 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Donor Report (Summary)', 'contribute/summary', 'Shows contribution statistics by month / week / year .. country / state .. type.', 'access CiviContribute', '{literal}a:38:{s:6:"fields";a:1:{s:12:"total_amount";s:1:"1";}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:13:"total_sum_min";s:0:"";s:13:"total_sum_max";s:0:"";s:12:"total_sum_op";s:3:"lte";s:15:"total_sum_value";s:0:"";s:15:"total_count_min";s:0:"";s:15:"total_count_max";s:0:"";s:14:"total_count_op";s:3:"lte";s:17:"total_count_value";s:0:"";s:13:"total_avg_min";s:0:"";s:13:"total_avg_max";s:0:"";s:12:"total_avg_op";s:3:"lte";s:15:"total_avg_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"group_bys";a:1:{s:12:"receive_date";s:1:"1";}s:14:"group_bys_freq";a:1:{s:12:"receive_date";s:5:"MONTH";}s:11:"description";s:80:"Shows contribution statistics by month / week / year .. country / state .. type.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";s:6:"charts";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Donor Report (Summary){/ts}',             '{literal}Donor Report (Summary){/literal}',           'access CiviContribute',    '',  @reportlastID,  '1', NULL, 5 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Donor Report (Detail)', 'contribute/detail', 'Lists detailed contribution(s) for one / all contacts. Contribution summary report points to this report for specific details.', 'access CiviContribute', '{literal}a:31:{s:6:"fields";a:6:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:10:"country_id";s:1:"1";s:12:"total_amount";s:1:"1";s:12:"receive_date";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:11:"description";s:126:"Lists detailed contribution(s) for one / all contacts. Contribution summary report points to this report for specific details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Donor Report (Detail){/ts}',              '{literal}Donor Report (Detail){/literal}',            'access CiviContribute',    '',  @reportlastID,  '1', NULL, 6 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Donation Summary Report (Repeat)', 'contribute/repeat', 'Given two date ranges, shows contacts (and their contributions) who contributed in both the date ranges with percentage increase / decrease.', 'access CiviContribute', '{literal}a:26:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:13:"total_amount1";s:1:"1";s:13:"total_amount2";s:1:"1";}s:22:"receive_date1_relative";s:13:"previous.year";s:18:"receive_date1_from";s:0:"";s:16:"receive_date1_to";s:0:"";s:22:"receive_date2_relative";s:9:"this.year";s:18:"receive_date2_from";s:0:"";s:16:"receive_date2_to";s:0:"";s:17:"total_amount1_min";s:0:"";s:17:"total_amount1_max";s:0:"";s:16:"total_amount1_op";s:3:"lte";s:19:"total_amount1_value";s:0:"";s:17:"total_amount2_min";s:0:"";s:17:"total_amount2_max";s:0:"";s:16:"total_amount2_op";s:3:"lte";s:19:"total_amount2_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"group_bys";a:1:{s:2:"id";s:1:"1";}s:11:"description";s:140:"Given two date ranges, shows contacts (and their contributions) who contributed in both the date ranges with percentage increase / decrease.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Donation Summary Report (Repeat){/ts}',   '{literal}Donation Summary Report (Repeat){/literal}', 'access CiviContribute',    '',  @reportlastID,  '1', NULL, 7 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'SYBUNT Report', 'contribute/sybunt', 'Some year(s) but not this year. Provides a list of constituents who donated at some time in the history of your organization but did not donate during the time period you specify.', 'access CiviContribute', '{literal}a:15:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"yid_op";s:2:"eq";s:9:"yid_value";s:4:"2009";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:179:"Some year(s) but not this year. Provides a list of constituents who donated at some time in the history of your organization but did not donate during the time period you specify.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"charts";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}SYBUNT Report{/ts}',                      'SYBUNT Report',                                       'access CiviContribute',    '',  @reportlastID,  '1', NULL, 8 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'LYBUNT Report', 'contribute/lybunt', 'Last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.', 'access CiviContribute', '{literal}a:16:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"yid_op";s:2:"eq";s:9:"yid_value";s:4:"2009";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:157:"Last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:6:"charts";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}LYBUNT Report{/ts}',                      'LYBUNT Report',                                       'access CiviContribute',    '',  @reportlastID,  '1', NULL, 9 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Soft Credit Report', 'contribute/softcredit', 'Soft Credit details.', 'access CiviContribute', '{literal}a:21:{s:6:"fields";a:5:{s:21:"display_name_creditor";s:1:"1";s:24:"display_name_constituent";s:1:"1";s:14:"email_creditor";s:1:"1";s:14:"phone_creditor";s:1:"1";s:12:"total_amount";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:20:"Soft Credit details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Soft Credit Report{/ts}',                 'Soft Credit Report',                                  'access CiviContribute',    '',  @reportlastID,  '1', NULL, 10 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Membership Report (Summary)', 'member/summary', 'Provides a summary of memberships by type and join date.', 'access CiviMember', '{literal}a:18:{s:6:"fields";a:2:{s:18:"membership_type_id";s:1:"1";s:12:"total_amount";s:1:"1";}s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:21:"membership_type_id_op";s:2:"in";s:24:"membership_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:9:"group_bys";a:2:{s:9:"join_date";s:1:"1";s:18:"membership_type_id";s:1:"1";}s:14:"group_bys_freq";a:1:{s:9:"join_date";s:5:"MONTH";}s:11:"description";s:56:"Provides a summary of memberships by type and join date.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),     '{ts escape="sql" skip="true"}Membership Report (Summary){/ts}',        '{literal}Membership Report (Summary){/literal}',      'access CiviMember',        '',  @reportlastID,  '1', NULL, 11 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Membership Report (Detail)', 'member/detail', 'Provides a list of members along with their membership status and membership details (Join Date, Start Date, End Date).', 'access CiviMember', '{literal}a:27:{s:6:"fields";a:5:{s:12:"display_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:119:"Provides a list of members along with their membership status and membership details (Join Date, Start Date, End Date).";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Membership Report (Detail){/ts}',         '{literal}Membership Report (Detail){/literal}',       'access CiviMember',        '',  @reportlastID,  '1', NULL, 12 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Membership Report (Lapsed)', 'member/lapse', 'Provides a list of memberships that lapsed or will lapse before the date you specify.', 'access CiviMember', '{literal}a:15:{s:6:"fields";a:5:{s:12:"display_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";s:10:"country_id";s:1:"1";}s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:85:"Provides a list of memberships that lapsed or will lapse before the date you specify.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Membership Report (Lapsed){/ts}',         '{literal}Membership Report (Lapsed){/literal}',       'access CiviMember',        '',  @reportlastID,  '1', NULL, 13 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Event Participant Report (List)', 'event/participantListing', 'Provides lists of participants for an event.', 'access CiviEvent', '{literal}a:25:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:8:"event_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:1:" ";s:28:"participant_register_date_to";s:1:" ";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:16:"blank_column_end";s:0:"";s:11:"description";s:44:"Provides lists of participants for an event.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:7:"options";N;}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Event Participant Report (List){/ts}',    '{literal}Event Participant Report (List){/literal}',  'access CiviEvent',         '',  @reportlastID,  '1', NULL, 14 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Event Income Report (Summary)', 'event/summary', 'Provides an overview of event income. You can include key information such as event ID, registration, attendance, and income generated to help you determine the success of an event.', 'access CiviEvent', '{literal}a:18:{s:6:"fields";a:2:{s:5:"title";s:1:"1";s:13:"event_type_id";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:16:"event_type_id_op";s:2:"in";s:19:"event_type_id_value";a:0:{}s:25:"event_start_date_relative";s:1:"0";s:21:"event_start_date_from";s:1:" ";s:19:"event_start_date_to";s:1:" ";s:23:"event_end_date_relative";s:1:"0";s:19:"event_end_date_from";s:1:" ";s:17:"event_end_date_to";s:0:"";s:11:"description";s:181:"Provides an overview of event income. You can include key information such as event ID, registration, attendance, and income generated to help you determine the success of an event.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:9:"parent_id";s:3:"174";s:6:"charts";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Event Income Report (Summary){/ts}',      '{literal}Event Income Report (Summary){/literal}',    'access CiviEvent',         '',  @reportlastID,  '1', NULL, 15 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Event Income Report (Detail)', 'event/income', 'Helps you to analyze the income generated by an event. The report can include details by participant type, status and payment method.', 'access CiviEvent', '{literal}a:7:{s:5:"id_op";s:2:"in";s:8:"id_value";N;s:11:"description";s:133:"Helps you to analyze the income generated by an event. The report can include details by participant type, status and payment method.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Event Income Report (Detail){/ts}',       '{literal}Event Income Report (Detail){/literal}',     'access CiviEvent',         '',  @reportlastID,  '1', NULL, 16 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Attendee List', 'event/participantListing', 'Provides lists of event attendees.', 'access CiviEvent', '{literal}a:25:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:14:"participant_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:1:{i:0;s:1:"1";}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:0:"";s:28:"participant_register_date_to";s:0:"";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:16:"blank_column_end";s:1:"1";s:11:"description";s:34:"Provides lists of event attendees.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";s:7:"options";N;}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Attendee List{/ts}',                      'Attendee List',                                       'access CiviEvent',         '',  @reportlastID,  '1', NULL, 17 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Activity Report ', 'activity', 'Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required)', 'administer CiviCRM', '{literal}a:23:{s:6:"fields";a:7:{s:14:"contact_source";s:1:"1";s:16:"contact_assignee";s:1:"1";s:14:"contact_target";s:1:"1";s:16:"activity_type_id";s:1:"1";s:7:"subject";s:1:"1";s:18:"activity_date_time";s:1:"1";s:9:"status_id";s:1:"1";}s:17:"contact_source_op";s:3:"has";s:20:"contact_source_value";s:0:"";s:19:"contact_assignee_op";s:3:"has";s:22:"contact_assignee_value";s:0:"";s:17:"contact_target_op";s:3:"has";s:20:"contact_target_value";s:0:"";s:27:"activity_date_time_relative";s:10:"this.month";s:23:"activity_date_time_from";s:0:"";s:21:"activity_date_time_to";s:0:"";s:10:"subject_op";s:3:"has";s:13:"subject_value";s:0:"";s:19:"activity_type_id_op";s:2:"in";s:22:"activity_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:9:"group_bys";a:1:{s:17:"source_contact_id";s:1:"1";}s:11:"description";s:126:"Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required)";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Activity Report{/ts}',                    'activity',                                            'administer CiviCRM',       '',  @reportlastID,  '1', NULL, 18 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Relationship Report', 'contact/relationship', 'Gives relationship details between two contacts', 'administer CiviCRM', '{literal}a:23:{s:6:"fields";a:4:{s:14:"display_name_a";s:1:"1";s:14:"display_name_b";s:1:"1";s:9:"label_a_b";s:1:"1";s:9:"label_b_a";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:17:"contact_type_a_op";s:2:"in";s:20:"contact_type_a_value";a:0:{}s:17:"contact_type_b_op";s:2:"in";s:20:"contact_type_b_value";a:0:{}s:12:"is_active_op";s:2:"eq";s:15:"is_active_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:47:"Gives relationship details between two contacts";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:18:"administer CiviCRM";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Relationship Report{/ts}',                'Relationship Report',                                 'administer CiviCRM',       '',  @reportlastID,  '1', NULL, 19 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Donation Summary Report (Organization)', 'contribute/organizationSummary', 'Displays a detailed contribution report for Organization relationships with contributors, as to if contribution done was  from an employee of some organization or from that Organization itself.', 'access CiviContribute', '{literal}a:21:{s:6:"fields";a:5:{s:17:"organization_name";s:1:"1";s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:20:"organization_name_op";s:3:"has";s:23:"organization_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"4_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:1:" ";s:15:"receive_date_to";s:1:" ";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:193:"Displays a detailed contribution report for Organization relationships with contributors, as to if contribution done was  from an employee of some organization or from that Organization itself.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Donation Summary Report (Organization){/ts}', '{literal}Donation Summary Report (Organization){/literal}', 'access CiviContribute', '',  @reportlastID,  '1', NULL, 20 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Donation Summary Report (Household)', 'contribute/householdSummary', 'Provides a detailed report for Contributions made by contributors(Or Household itself) who are having a relationship with household (For ex a Contributor is Head of Household for some household or is a member of.)', 'access CiviContribute', '{literal}a:21:{s:6:"fields";a:5:{s:14:"household_name";s:1:"1";s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:17:"household_name_op";s:3:"has";s:20:"household_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"6_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:1:" ";s:15:"receive_date_to";s:1:" ";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:213:"Provides a detailed report for Contributions made by contributors(Or Household itself) who are having a relationship with household (For ex a Contributor is Head of Household for some household or is a member of.)";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Donation Summary Report (Household){/ts}',    '{literal}Donation Summary Report (Household){/literal}',    'access CiviContribute', '',  @reportlastID,  '1', NULL, 21 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Top Donors Report', 'contribute/topDonor', 'Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).', 'access CiviContribute', '{literal}a:20:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";}s:21:"receive_date_relative";s:9:"this.year";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:15:"total_range_min";s:0:"";s:15:"total_range_max";s:0:"";s:14:"total_range_op";s:2:"eq";s:17:"total_range_value";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:148:"Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Top Donors Report{/ts}',                  'Top Donors Report',            'access CiviContribute',   '',  @reportlastID,  '1', NULL, 22 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Pledge Summary Report', 'pledge/summary', 'Updates you with your Pledge Summary (if any) such as your pledge status, next payment date, amount, payment due, total amount paid etc.', 'access CiviPledge', '{literal}a:25:{s:6:"fields";a:4:{s:12:"display_name";s:1:"1";s:10:"country_id";s:1:"1";s:6:"amount";s:1:"1";s:9:"status_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:17:"pledge_amount_min";s:0:"";s:17:"pledge_amount_max";s:0:"";s:16:"pledge_amount_op";s:3:"lte";s:19:"pledge_amount_value";s:0:"";s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:136:"Updates you with your Pledge Summary (if any) such as your pledge status, next payment date, amount, payment due, total amount paid etc.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Pledge Summary Report{/ts}',              'Pledge Summary Report',        'access CiviPledge',       '',  @reportlastID,  '1', NULL, 23 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES    
    ( @domainID, 'Pledged But not Paid Report', 'pledge/pbnp', 'Pledged but not Paid Report', 'access CiviPledge', '{literal}a:15:{s:6:"fields";a:6:{s:12:"display_name";s:1:"1";s:18:"pledge_create_date";s:1:"1";s:6:"amount";s:1:"1";s:14:"scheduled_date";s:1:"1";s:10:"country_id";s:1:"1";s:5:"email";s:1:"1";}s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:27:"Pledged but not Paid Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:9:"parent_id";s:3:"174";s:6:"groups";s:0:"";}{/literal}');
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'),    '{ts escape="sql" skip="true"}Pledged But not Paid Report{/ts}',        'Pledged But not Paid Report',  'access CiviPledge',       '',  @reportlastID,  '1', NULL, 24 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Bookkeeping Transactions Report', 'contribute/bookkeeping', 'Shows Bookkeeping Transactions Report', 'access CiviContribute', '{literal}a:26:{s:6:"fields";a:10:{s:12:"display_name";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:7:"trxn_id";s:1:"1";s:10:"invoice_id";s:1:"1";s:12:"check_number";s:1:"1";s:21:"payment_instrument_id";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:2:"id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:11:"description";s:37:"Shows Bookkeeping Transactions Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;} {/literal}');
 
SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Bookkeeping Transactions Report{/ts}', '{literal}Bookkeeping Transactions Report{/literal}', 'access CiviContribute', '', @reportlastID, '1', NULL, 25 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;


INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Grant Report', 'grant', 'Grant Report', 'access CiviGrant', '{literal}a:37:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:25:"application_received_date";s:1:"1";}s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:12:"gender_id_op";s:2:"in";s:15:"gender_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:11:"description";s:12:"Grant Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Grant Report{/ts}', '{literal}Grant Report{/literal}', 'access CiviGrant', '',@reportlastID, '1', NULL, 26 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Bounce Report', 'Mailing/bounce', 'Bounce Report for mailings', 'access CiviMail', '{literal}a:30:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:19:"bounce_type_name_op";s:2:"eq";s:22:"bounce_type_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:26:"Bounce Report for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Mail Bounce Report{/ts}', '{literal}Mail Bounce Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, 27 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Summary Report', 'Mailing/summary','Summary statistics for mailings','access CiviMail','{literal}a:21:{s:6:"fields";a:1:{s:4:"name";s:1:"1";}s:15:"is_completed_op";s:2:"eq";s:18:"is_completed_value";s:1:"1";s:9:"status_op";s:3:"has";s:12:"status_value";s:8:"Complete";s:11:"is_test_min";s:0:"";s:11:"is_test_max";s:0:"";s:10:"is_test_op";s:3:"lte";s:13:"is_test_value";s:1:"0";s:19:"start_date_relative";s:9:"this.year";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:17:"end_date_relative";s:9:"this.year";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:11:"description";s:31:"Summary statistics for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Mail Summary Report{/ts}', '{literal}Mail Summary Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, 28 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Opened Report', 'Mailing/opened', 'Display contacts who opened emails from a mailing', 'access CiviMail', '{literal}a:28:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:49:"Display contacts who opened emails from a mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Mail Opened Report{/ts}', '{literal}Mail Opened Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, 28 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Clickthrough Report', 'Mailing/clicks', 'Display clicks from each mailing', 'access CiviMail', '{literal}a:28:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:32:"Display clicks from each mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Mail Clickthrough Report{/ts}', '{literal}Mail Clickthrough Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, 29 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, '{ts escape="sql"}Top Participant{/ts}', 'contact/topparticipant', '{ts escape="sql"}Report for list top participants.{/ts}', 'access CiviEvent', '{literal}a:34:{s:6:"fields";a:3:{s:9:"sort_name";s:1:"1";s:2:"id";s:1:"1";s:9:"status_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:21:"participant_count_min";s:0:"";s:21:"participant_count_max";s:0:"";s:20:"participant_count_op";s:3:"gte";s:23:"participant_count_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:0:"";s:28:"participant_register_date_to";s:0:"";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:25:"event_start_date_relative";s:1:"0";s:21:"event_start_date_from";s:0:"";s:19:"event_start_date_to";s:0:"";s:23:"event_end_date_relative";s:1:"0";s:19:"event_end_date_from";s:0:"";s:17:"event_end_date_to";s:0:"";s:11:"description";s:33:"Report for list top participants.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Top Participant{/ts}', '{literal}Top Participant{/literal}', 'access CiviEvent', '',@reportlastID, '1', NULL, 30 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;
