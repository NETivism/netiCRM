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
{* Base template for Open Case. May be used for other special activity types at some point ..
   Note: 1. We will include all the activity fields here however each activity type file may build (via php) only those required by them. 
         2. Each activity type file can include its case fields in its own template, so that they will be included during activity edit.
*}
{if $action neq 8 && $action neq 32768}
<div class="html-adjust">{$form.buttons.html}</div>
{/if}

<fieldset><legend>{if $action eq 8}{ts}Delete Case{/ts}{elseif $action eq 32768}{ts}Restore Case{/ts}{else}{$activityType}{/if}</legend>
<table class="form-layout">
{if $action eq 8 or $action eq 32768 } 
      <div class="messages status"> 
        <dl> 
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt> 
          <dd> 
          {if $action eq 8}
            {ts}Click Delete to move this case and all associated activities to the Trash.{/ts} 
          {else}
            {ts}Click Restore to retrieve this case and all associated activities from the Trash.{/ts} 
          {/if}
          </dd> 
       </dl> 
      </div> 
{else}
{if $clientName}
    <tr><td class="label font-size12pt">{ts}Client{/ts}</td><td class="font-size12pt bold view-value">{$clientName}</td></tr>
{elseif !$clientName and $action eq 1} 
    <tr class="form-layout-compressed" border="0">			      
        {if $context eq 'standalone'}
            {include file="CRM/Contact/Form/NewContact.tpl"}
        {/if}
    </tr>
{/if}
{* activity fields *}
{if $form.medium_id.html and $form.activity_location.html}
    <tr>
        <td class="label">{$form.medium_id.label}</td>
        <td class="view-value">{$form.medium_id.html}&nbsp;&nbsp;&nbsp;{$form.activity_location.label} &nbsp;{$form.activity_location.html}</td>
    </tr> 
{/if}

{if $form.activity_details.html}
    <tr>
        <td class="label">{$form.activity_details.label}{help id="id-details" file="CRM/Case/Form/Case.hlp"}</td>
        <td class="view-value">{$form.activity_details.html|crmReplace:class:huge40}</td>
    </tr>
{/if}

{* custom data group *}
{if $groupTree}
    <tr>
       <td colspan="2">{include file="CRM/Custom/Form/CustomData.tpl"}</td>
    </tr>    
{/if}

{if $form.activity_subject.html}
    <tr><td class="label">{$form.activity_subject.label}{help id="id-activity_subject" file="CRM/Case/Form/Case.hlp"}</td><td>{$form.activity_subject.html}</td></tr>
{/if}

{* inject activity type-specific form fields *}
{if $activityTypeFile}
    {include file="CRM/Case/Form/Activity/$activityTypeFile.tpl"}
{/if}

{if $form.duration.html}
    <tr>
      <td class="label">{$form.duration.label}</td>
      <td class="view-value">
        {$form.duration.html}
         <span class="description">{ts}Total time spent on this activity (in minutes).{/ts}
      </td>
    </tr> 
{/if}


{/if}	

</table>
</fieldset>
<div class="html-adjust">{$form.buttons.html}</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

