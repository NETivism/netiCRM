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
{* not sure how to define the below in Smarty, so doing it in PHP instead *}
{php}
  $ogNames = array(
    'case'         => ts('Message Template Workflow for Cases',          array('escape' => 'sql')),
    'contribution' => ts('Message Template Workflow for Contributions',  array('escape' => 'sql')),
    'event'        => ts('Message Template Workflow for Events',         array('escape' => 'sql')),
    'friend'       => ts('Message Template Workflow for Tell-a-Friend',  array('escape' => 'sql')),
    'membership'   => ts('Message Template Workflow for Memberships',    array('escape' => 'sql')),
    'meta'         => ts('Message Template Workflow for Meta Templates', array('escape' => 'sql')),
    'pledge'       => ts('Message Template Workflow for Pledges',        array('escape' => 'sql')),
    'uf'           => ts('Message Template Workflow for Profiles',       array('escape' => 'sql')),
    'petition'     => ts('Message Template Workflow for Petition',       array('escape' => 'sql')),
  );
  $ovNames = array(
    'case' => array(
      'case_activity' => ts('Cases - Send Copy of an Activity', array('escape' => 'sql')),
    ),
    'contribution' => array(
      'contribution_dupalert'         => ts('Contributions - Duplicate Organization Alert',                   array('escape' => 'sql')),
      'contribution_offline_receipt'  => ts('Contributions - Receipt (off-line)',                             array('escape' => 'sql')),
      'contribution_online_receipt'   => ts('Contributions - Receipt (on-line)',                              array('escape' => 'sql')),
      'contribution_recurring_notify' => ts('Contributions - Recurring Start and End Notification',           array('escape' => 'sql')),
      'contribution_invoice_notify'   => ts('Contributions - Invoice Notification',                           array('escape' => 'sql')),
      'pcp_notify'                    => ts('Personal Campaign Pages - Admin Notification',                   array('escape' => 'sql')),
      'pcp_status_change'             => ts('Personal Campaign Pages - Supporter Status Change Notification', array('escape' => 'sql')),
      'pcp_supporter_notify'          => ts('Personal Campaign Pages - Supporter Welcome',                    array('escape' => 'sql')),
      'contribution_recur_fail_notify'=> ts('Contributions - Recurring Failed Notification', array('escape' => 'sql')),
    ),
    'event' => array(
      'event_offline_receipt' => ts('Events - Registration Confirmation and Receipt (off-line)', array('escape' => 'sql')),
      'event_online_receipt'  => ts('Events - Registration Confirmation and Receipt (on-line)',  array('escape' => 'sql')),
      'participant_cancelled' => ts('Events - Registration Cancellation Notice',                 array('escape' => 'sql')),
      'participant_confirm'   => ts('Events - Registration Confirmation Invite',                 array('escape' => 'sql')),
      'participant_expired'   => ts('Events - Pending Registration Expiration Notice',           array('escape' => 'sql')),
    ),
    'friend' => array(
      'friend' => ts('Tell-a-Friend Email', array('escape' => 'sql')),
    ),
    'membership' => array(
      'membership_offline_receipt' => ts('Memberships - Signup and Renewal Receipts (off-line)', array('escape' => 'sql')),
      'membership_online_receipt'  => ts('Memberships - Receipt (on-line)',                      array('escape' => 'sql')),
    ),
    'meta' => array(
      'test_preview' => ts('Test-drive - Receipt Header', array('escape' => 'sql')),
      'batch_complete_notification' => ts('Batch Complete Notification', array('escape' => 'sql')),
    ),
    'pledge' => array(
      'pledge_acknowledge' => ts('Pledges - Acknowledgement',  array('escape' => 'sql')),
      'pledge_reminder'    => ts('Pledges - Payment Reminder', array('escape' => 'sql')),
    ),
    'uf' => array(
      'uf_notify' => ts('Profiles - Admin Notification', array('escape' => 'sql')),
    ),
    'petition' => array(
      'petition_sign' => ts('Petition - signature added', array('escape' => 'sql')),
      'petition_confirmation_needed' => ts('Petition - need verification', array('escape' => 'sql')),
    ),
  );
  $this->assign('ogNames',  $ogNames);
  $this->assign('ovNames',  $ovNames);
{/php}

INSERT INTO civicrm_option_group
  (name,                         {localize field='label'}label{/localize},            {localize field='description'}description{/localize},      is_reserved, is_active) VALUES
{foreach from=$ogNames key=name item=description name=for_groups}
    ('msg_tpl_workflow_{$name}', {localize}'{$description}'{/localize},               {localize}'{$description}'{/localize},                     0,           1) {if $smarty.foreach.for_groups.last};{else},
{/if}
{/foreach}

{foreach from=$ogNames key=name item=description}
  SELECT @tpl_ogid_{$name} := MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$name}';
{/foreach}

INSERT INTO civicrm_option_value
  (option_group_id,        name,       {localize field='label'}label{/localize},   value,                                  weight) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=label name=for_values}
      (@tpl_ogid_{$gName}, '{$vName}', {localize}'{$label}'{/localize},            {$smarty.foreach.for_values.iteration}, {$smarty.foreach.for_values.iteration}) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},
{/if}
{/foreach}
{/foreach}

{foreach from=$ovNames key=gName item=ovs}
{foreach from=$ovs key=vName item=label}
    SELECT @tpl_ovid_{$vName} := MAX(id) FROM civicrm_option_value WHERE option_group_id = @tpl_ogid_{$gName} AND name = '{$vName}';
{/foreach}
{/foreach}

INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=title name=for_values}
      {fetch assign=subject file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_subject.tpl"}
      {fetch assign=text    file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_text.tpl"}
      {fetch assign=html    file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_html.tpl"}
      ('{$title}', '{$subject|escape:"quotes"|regex_replace:"/[\r\t\n]/":""}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 1,          0),
      ('{$title}', '{$subject|escape:"quotes"|regex_replace:"/[\r\t\n]/":""}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 0,          1) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}
