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

{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {if $action eq 1024}
        {ts}This is a preview of this product as it will appear on your Contributions page(s).{/ts}
    {else}
        {ts}Use this form to select a premium to be offered on this Online Contribution Page.{/ts}
    {/if}
</div>
 
<div class="form-item">
    <fieldset><legend>{if $action eq 2 }{ts}Add Products to This Page{/ts} {elseif $action eq 1024}{ts}Preview{/ts}{else} {ts}Remove Products from this Page{/ts}{/if}</legend>
   {if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts}Are you sure you want to remove this premium product from this Contribution page?{/ts}
          </dd>
       </dl>
      </div>
  {elseif $action eq 1024}
     {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl"}
  {else}
    <dl>
    <dt>{$form.product_id.label}</dt><dd>{$form.product_id.html}</dd>
    {capture assign=mngPremURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
    <dt>&nbsp;</dt><dd class="description">{ts 1=$mngPremURL}Pick a premium to include on this Contribution Page. Use <a href='%1'>Manage Premiums</a> to create or enable additional premium choices for your site.{/ts}</dd>
    <dt>{$form.weight.label}</dt><dd>{$form.weight.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Weight controls the order that premiums are displayed on the Contribution Page.{/ts}</dd>
    </dl>
  {/if}	
    </fieldset>
</div>

{if $action ne 4}
<div id="crm-submit-buttons">
    {$form.buttons.html}
</div>
{else}
    <div id="crm-done-button">
        {$form.done.html}
    </div>
{/if} {* $action ne view *}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
