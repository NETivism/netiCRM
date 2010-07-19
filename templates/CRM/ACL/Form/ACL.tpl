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
{* this template is used for adding/editing ACL  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New ACL{/ts}{elseif $action eq 2}{ts}Edit ACL{/ts}{else}{ts}Delete ACL{/ts}{/if}</legend>

{if $action eq 8}
  <div class="messages status">
    <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>    
        {ts}WARNING: Delete will remove this permission from the specified ACL Role.{/ts} {ts}Do you want to continue?{/ts}
      </dd>
    </dl>
  </div>
{else}
  <dl>
    <dt>{$form.object_type.label}</dt><dd>{$form.object_type.html}</dd>
    <dt class="{$form.object_type.name}">&nbsp;</dt><dd class="description">{ts}Select the type of data this ACL operates on.{/ts}</dd>
    {if $config->userFramework EQ 'Drupal'}
        <dt>&nbsp;</dt><dd class="description">{ts}IMPORTANT: The Drupal permissions for 'access all custom data' and 'profile listings and forms' override and disable specific ACL settings for custom field groups and profiles respectively. Do not enable those Drupal permissions for a Drupal role if you want to use CiviCRM ACL's to control access.{/ts}</dd>
    {/if}
  </dl>
  <div id="id-group-acl">
    <dl>
    <dt>{$form.group_id.label}</dt><dd>{$form.group_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select a specific group of contacts, OR apply this permission to ALL groups.{/ts}</dd>
    </dl>
  </div>
  <div id="id-profile-acl">
    <dl>
    <dt>{$form.uf_group_id.label}</dt><dd>{$form.uf_group_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select a specific profile, OR apply this permission to ALL profiles.{/ts}</dd>
    </dl>
    <div class="status message">{ts}NOTE: For Profile ACLs, the 'View' and 'Edit' operations currently do the same thing. Either option grants users access to profile-based create, edit, view and listings screens. Neither option grants access to administration of profiles.{/ts}</div>
  </div>
  <div id="id-custom-acl">
    <dl>
    <dt>{$form.custom_group_id.label}</dt><dd>{$form.custom_group_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select a specific group of custom fields, OR apply this permission to ALL custom fields.{/ts}</dd>
    </dl>
    <div class="status message">{ts}NOTE: For Custom Data ACLs, the 'View' and 'Edit' operations currently do the same thing. Either option grants the right to view AND / OR edit custom data fields (in all groups, or in a specific custom data group). Neither option grants access to administration of custom data fields.{/ts}</div>
  </div>
  <div id="id-event-acl">
    <dl>
    <dt>{$form.event_id.label}</dt><dd>{$form.event_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select an event, OR apply this permission to ALL events.{/ts}</dt>
    </dl>
    <div class="status message">{ts}NOTE: For Event ACLs, the 'View' operation allows access to the event information screen. "Edit" allows users to register for the event if online registration is enabled.{/ts}<br /> 
    {if $config->userFramework EQ 'Drupal'}
    {ts}Please remember that Drupal's "register for events" permission overrides CiviCRM's control over event information access.{/ts}
    {/if}
    </div>
  </div>
  <dl>
    <dt>{$form.operation.label}</dt><dd>{$form.operation.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}What type of operation (action) is being permitted?{/ts}</dd>
    <dt>{$form.entity_id.label}</dt><dd>{$form.entity_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select a Role to assign (grant) this permission to. Select the special role 'Everyone' if you want to grant this permission to ALL users. 'Everyone' includes anonymous (i.e. not logged in) users. Select the special role 'Authenticated' if you want to grant it to any logged in user.{/ts}</dd>
    <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter a descriptive name for this permission (e.g. 'Edit Advisory Board Contacts').{/ts}</dd>
    <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
  </dl>
{/if}
  <dl> 
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl> 
</fieldset>
</div>

{include file="CRM/common/showHide.tpl"}
{literal}
<script type="text/javascript">
 showObjectSelect( );
 function showObjectSelect( ) {
    var ot = document.getElementsByName('object_type');
    for (var i = 0; i < ot.length; i++) {
        if ( ot[i].checked ) {
            switch(ot[i].value) {
                case "1":
                    show('id-group-acl');
                    hide('id-profile-acl');
                    hide('id-custom-acl');
                    hide('id-event-acl');
                    break;
                case "2":
                    hide('id-group-acl');
                    show('id-profile-acl');
                    hide('id-custom-acl');
                    hide('id-event-acl');
                    break;
                case "3":
                    hide('id-group-acl');
                    hide('id-profile-acl');
                    show('id-custom-acl');
                    hide('id-event-acl');
                    break;
                case "4":
                    hide('id-group-acl');
                    hide('id-profile-acl');
                    hide('id-custom-acl');
                    show('id-event-acl');
                    break;
            }
        }
    }
 return;
}
</script>
{/literal}
