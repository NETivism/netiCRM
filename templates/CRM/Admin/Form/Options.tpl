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
{* this template is used for adding/editing options *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts 1=$GName}New %1 Option{/ts}{elseif $action eq 8}{ts 1=$GName}Delete %1 Option{/ts}{else}{ts 1=$GName}Edit %1 Option{/ts}{/if}</legend>
	{if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts 1=$GName}WARNING: Deleting this option will result in the loss of all %1 related records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
    {else}
	<dl class="html-adjust" >
        {if $gName eq 'custom_search'}
            <dt>{ts}Custom Search Path{/ts}</dt><dd>{$form.label.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Enter the "class path" for this custom search here.{/ts} {docURL page="Custom Search Components"}</dd>
        {elseif $gName eq 'from_email_address'}
            <dt>{ts}FROM Email Address{/ts} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</dt><dd>{$form.label.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Include double-quotes (&quot;) around the name and angle-brackets (&lt; &gt;) around the email address.<br />EXAMPLE: <em>&quot;Client Services&quot; &lt;clientservices@example.org&gt;</em>{/ts}</dd>
        {elseif $gName eq 'redaction_rule'}
            <dt>{ts}Match Value or Expression{/ts} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</dt><dd>{$form.label.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}A "string value" or regular expression to be redacted (replaced).{/ts}</dd>
        {else}
            <dt>{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</dt><dd>{$form.label.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}The option label is displayed to users.{/ts}</dd>
        {/if}
        {if $gName eq 'custom_search'}
            <dt>{ts}Search Title{/ts}</dt><dd>{$form.description.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This title is displayed to users in the Custom Search listings.{/ts}</dd>
        {else}
            {if $gName eq 'redaction_rule'}
                <dt>{ts}Replacement (prefix){/ts}</dt><dd>{$form.value.html}</dd>
                <dt>&nbsp;</dt><dd class="description">{ts}Matched values are replaced with this prefix plus a unique code. EX: If replacement prefix for &quot;Vancouver&quot; is <em>city_</em>, occurrences will be replaced with <em>city_39121</em>.{/ts}</dd>
            {else}
                <dt>{$form.value.label}</dt><dd>{$form.value.html}</dd>
            {/if}
            {if $form.filter.html} {* Filter property is only exposed for some option groups. *}
                <dt>{$form.filter.label}</dt><dd>{$form.filter.html}</dd>
            {/if} 
            <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
            {if $gName eq 'activity_type'}
                <dt>&nbsp;</dt><dd class="description">{ts}Description is included at the top of the activity edit and view pages for this type of activity.{/ts}</dd>
            {/if}
        {/if}
        {if $gName eq 'participant_status'}
            <dt>{$form.visibility_id.label}</dt><dd>{$form.visibility_id.html}</dd>	
        {/if}
        <dt>{$form.weight.label}</dt><dd>{$form.weight.html}</dd>
        {if $form.component_id.html} {* Component ID is exposed for activity types if CiviCase is enabled. *}
            <dt>{$form.component_id.label}</dt><dd>{$form.component_id.html}</dd>
        {/if}
        <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
        {if $showDefault}
            <dt>{$form.is_default.label}</dt><dd>{$form.is_default.html}</dd>
        {/if}
        {if $showContactFilter}{* contactOptions is exposed for email/postal greeting and addressee types to set filter for contact types *}
            <dt>{$form.contactOptions.label}</dt><dd>{$form.contactOptions.html}</dd>
        {/if}    
    </dl>
    {/if}
    <div class="spacer"></div>   
	<dl><dt></dt><dd>{$form.buttons.html}</dd></dl>
</fieldset>
</div>
