{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
{if $groupCount == 0 and $mailingCount == 0}
  <div class="status">
    <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>
        {ts}To send a mailing, you must have a valid group of recipients - either at least one group that's a Mailing List or at least one previous mailing.{/ts}
      </dd>
    </dl>
  </div>
{else}
{include file="CRM/common/WizardHeader.tpl"}

<div class="form-item">
<fieldset>
  <dl>
    <dt>{$form.name.label}</dt><dd>{$form.name.html} {help id="mailing-name"}</dd>
    {if $context EQ 'search'}
    <dt>{$form.baseGroup.label}</dt><dd>{$form.baseGroup.html}</dd>
    {/if}	
  </dl>
</fieldset>

 <div id="id-additional-show" class="section-hidden section-hidden-border" style="clear: both;">
        <a href="#" onclick="hide('id-additional-show'); show('id-additional'); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a><label>{if $context EQ 'search'}{ts}Additional Mailing Recipients{/ts}{else}{ts}Mailing Recipients{/ts}{/if}</label><br />
 </div>

 <div id="id-additional" class="form-item">
  <fieldset>
  <legend><a href="#" onclick="hide('id-additional'); show('id-additional-show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a>{if $context EQ 'search'}{ts}Additional Mailing Recipients{/ts}{else}{ts}Mailing Recipients{/ts}{/if}</legend>
  {strip}

  <table>
  {if $groupCount > 0}
    <tr><th class="label">{$form.includeGroups.label} {help id="include-groups"}</th></tr>
    <tr><td>{$form.includeGroups.html}</td></tr>
    <tr><th class="label">{$form.excludeGroups.label} {help id="exclude-groups"}</th></tr>
    <tr><td>{$form.excludeGroups.html}</td></tr>
  {/if}
  {if $mailingCount > 0}
  <tr><th class="label">{$form.includeMailings.label} {help id="include-mailings"}</th></tr>
  <tr><td>{$form.includeMailings.html}</td></tr>
  <tr><th class="label">{$form.excludeMailings.label} {help id="exclude-mailings"}</th></tr>
  <tr><td>{$form.excludeMailings.html}</td></tr>
  {/if}
  </table>

  {/strip}
  </fieldset>
 </div>

 <dl>
 <dt></dt><dd>{$form.buttons.html}</dd>
 </dl>
</div>
{include file="CRM/common/showHide.tpl"}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

{/if}
