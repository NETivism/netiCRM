{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
  <h3>{ts}Settings{/ts} - {ts}Fields{/ts}</h3>
  <div class="crm-section">
    <div class="label">{$form.recurringSyncExclude.label}</div>
    <div class="content">
      {$form.recurringSyncExclude.html} 
      <div class="description">{ts}When recurring contribution being triggered, new contribution will copy custom fields value from first one. Fields you selected in this option will exclude to synchronize value from old contribution.{/ts}</div>
    </div>
    {include file="CRM/common/chosen.tpl" selector='select[name^=recurringSyncExclude]'}
  </div>
  <div class="crm-section">
    <div class="label">{$form.recurringCopySetting.label}</div>
    <div class="content">
      {$form.recurringCopySetting.html} 
      <div class="description">{ts}When the recurring contribution is triggered, which contribution do you want to copy?{/ts}</div>
    </div>
  </div>
  {if $form.copyContributionTypeSource}
  <div class="crm-section">
    <div class="label">{$form.copyContributionTypeSource.label}</div>
    <div class="content">
      {$form.copyContributionTypeSource.html} 
      <div class="description">{ts}When the recurring contribution is triggered, which source of contribution type do you want to copy?{/ts}</div>
    </div>
  </div>
  {/if}
  {if $form.contribution_page_id}
  <div class="crm-section">
    <div class="label">{$form.contribution_page_id.label}</div>
    <div class="content">
      {$form.contribution_page_id.html}
      <div class="description">{ts}If the linked page is unavailable or the contact has no donation history, the system will redirect to this default contribution page.{/ts}</div>
    </div>
  </div>
  {/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
