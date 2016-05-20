{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
{if $groupCount == 0 and $mailingCount == 0}
  <div class="status">
	
        {ts}To send a mailing, you must have a valid group of recipients - either at least one group that's a Mailing List or at least one previous mailing.{/ts}
  </div>
{else}
<div class="crm-block crm-form-block crm-mailing-group-form-block">
{include file="CRM/common/WizardHeader.tpl"}

  <table class="form-layout">
   <tr class="crm-mailing-group-form-block-name"><td class="label">{$form.name.label}</td><td>{$form.name.html} {help id="mailing-name"}</td></tr>   

    {if $context EQ 'search'}
        <tr class="crm-mailing-group-form-block-baseGroup">
            <td class="label">{$form.baseGroup.label}</td>
            <td>{$form.baseGroup.html} {help id="base-group"}</td>
        </tr>
    {/if}
    
    <tr class="crm-mailing-group-form-block-dedupeemail">
        <td class="label">{$form.dedupe_email.label}</td>
        <td>{$form.dedupe_email.html} {help id="dedupe-email"}</td>
    </tr>
  </table>


<div id="id-additional" class="form-item">
<h3>{if $context EQ 'search'}{ts}Additional Mailing Recipients{/ts}{else}{ts}Mailing Recipients{/ts}{/if}</h3>
{if $groupCount > 0}
<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
 <div class="crm-accordion-header">
   <div class="zmdi crm-accordion-pointer"></div>
   {ts}Group{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {strip}
  <table>
    <tr class="crm-mailing-group-form-block-includeGroups"><td class="label">{$form.includeGroups.label} {help id="include-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-includeGroups"><td>{$form.includeGroups.html}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td class="label">{$form.excludeGroups.label} {help id="exclude-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td>{$form.excludeGroups.html}</td></tr>
  </table>
  {/strip}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{/if}{* group *}
{if $mailingCount > 0}
<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
 <div class="crm-accordion-header">
   <div class="zmdi crm-accordion-pointer"></div>
   {ts}Mailing{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {strip}
  <table>
  <tr class="crm-mailing-group-form-block-includeMailings"><td class="label">{$form.includeMailings.label} {help id="include-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-includeMailings"><td>{$form.includeMailings.html}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td class="label">{$form.excludeMailings.label} {help id="exclude-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td>{$form.excludeMailings.html}</td></tr>
  </table>
  {/strip}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
 <div class="crm-accordion-header">
   <div class="zmdi crm-accordion-pointer"></div>
   {ts}Open tracking{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {strip}
  <table>
  <tr class="crm-mailing-group-form-block-includeOpened"><td class="label">{$form.includeOpened.label} {help id="include-opened"}</td></tr>
  <tr class="crm-mailing-group-form-block-includeOpened"><td>{$form.includeOpened.html}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeOpened"><td class="label">{$form.excludeOpened.label} {help id="exclude-opened"}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeOpened"><td>{$form.excludeOpened.html}</td></tr>
  </table>
  {/strip}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
 <div class="crm-accordion-header">
   <div class="zmdi crm-accordion-pointer"></div>
   {ts}Clicked{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {strip}
  <table>
  <tr class="crm-mailing-group-form-block-includeClicked"><td class="label">{$form.includeClicked.label} {help id="include-clicked"}</td></tr>
  <tr class="crm-mailing-group-form-block-includeClicked"><td>{$form.includeClicked.html}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeClicked"><td class="label">{$form.excludeClicked.label} {help id="exclude-clicked"}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeClicked"><td>{$form.excludeClicked.html}</td></tr>
  </table>
  {/strip}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{/if}{*mailing*}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>

{literal}
<script type="text/javascript">
cj(function() {
  cj().crmaccordions(); 
  cj('.advmultiselect select[name$="t\\\[\\\]"]').each(function(){
    if (!cj(this).attr('disabled')) {
      cj(this).parents('.crm-accordion-closed').each(function(){
        cj(this).find('.crm-accordion-header').trigger('click');
      });
    }
  });
});
</script>
{/literal}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
</div>
{/if}
