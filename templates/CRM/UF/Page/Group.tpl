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
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8 or $action eq 64 or $action eq 16384}
    {* Add or edit Profile Group form *}
    {include file="CRM/UF/Form/Group.tpl"}
{elseif $action eq 1024}
    {* Preview Profile Group form *}	
    {include file="CRM/UF/Form/Preview.tpl"}
{elseif $action eq 8192}
    {* Display HTML Form Snippet Code *}
    <form name="html_code" action="{crmURL p='civicrm/admin/uf/group' q="action=profile&gid=$gid"}">
    <div>
      <h2>{ts}Link{/ts}</h2>
      <input name="link_url" value="{crmURL p='civicrm/profile/create' q="gid=$gid&reset=1" a=true}" class="huge"> 
      <a href="#" onclick="html_code.link_url.select(); document.execCommand('copy'); return false;" class="button"><i class="zmdi zmdi-copy"></i><span>{ts}Copy{/ts} {ts}Link{/ts}</span></a> 
      <a href="{crmURL p='civicrm/profile/create' q="gid=`$gid`&reset=1" a=true}" class="button" target="_blank"><i class="zmdi zmdi-link"></i><span>{ts}Use (create mode){/ts}</span></a> 
    </div>

    <div>
      <h2>{ts}HTML Form Snippet{/ts}</h2>
      <div>
        <table><tr>
          <td><textarea rows="7" style="width:26em;" name="profile" id="profile" onfocus="this.select();">{$profile}</textarea></td>
          <td>
            {ts}The HTML code below will display a form consisting of the active fields in this Profile. You can copy this HTML code and paste it into any block or page on ANY website where you want to collect contact information.{/ts}<br>
            <a href="#" onclick="html_code.profile.select(); document.execCommand('copy'); return false;" class="button"><i class="zmdi zmdi-copy"></i><span>{ts}Copy{/ts}</span></a> 
          </td>
        </tr></table>
      </div>
    </div>
    <div class="action-link-button">
        <a href="{crmURL p='civicrm/admin/uf/group/field' q="reset=1&action=browse&gid=$gid"}"><i class="zmdi zmdi-arrow-left"></i>{ts}Back to Profile Listings{/ts}</a>
    </div>
    </form>

{else}
    <div id="help">
        {ts}CiviCRM Profile(s) allow you to aggregate groups of fields and include them in your site as input forms, contact display pages, and search and listings features. They provide a powerful set of tools for you to collect information from constituents and selectively share contact information.{/ts} {help id='profile_overview'}
    </div>

    {if NOT ($action eq 1 or $action eq 2)}
    <div class="crm-submit-buttons">
        <a href="{crmURL p='civicrm/admin/uf/group/add' q="action=add&reset=1"}" id="newCiviCRMProfile-top" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Profile{/ts}</span></a>
    </div>
    {/if}
    {if $rows}
    <div id='mainTabContainer'>
        <ul>
            <li id='tab_user'>    <a href='#user-profiles'     title='{ts}User-defined Profile{/ts}'>{ts}User-defined Profiles{/ts}</a></li>
            <li id='tab_reserved'><a href='#reserved-profiles' title='{ts}Reserved Profiles{/ts}'>{ts}Reserved Profiles{/ts}</a></li>
        </ul>

        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisable.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <div id="user-profiles">
           <div class="crm-content-block">
           <table class="display">
             <thead>
              <tr>
                <th id="sortable">{ts}Profile Title{/ts}</th>
                <th>{ts}Type{/ts}</th>
                <th>{ts}ID{/ts}</th>
                <th id="nosort">{ts}Used For{/ts}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            {foreach from=$rows item=row}
            {if !$row.is_reserved }
              <tr id="UFGroup-{$row.id}"class="crm-entity {$row.class}{if NOT $row.is_active} disabled{/if}">
                <td><span class="crmf-title crm-editable">{$row.title}</span></td>
                <td>{$row.group_type}</td>
                <td>{$row.id}</td>
                <td>{$row.module}</td>
                <td>{$row.action|replace:'xx':$row.id}</td>
              </tr>
            {/if}
            {/foreach}
            </tbody>
            </table>

            {if NOT ($action eq 1 or $action eq 2)}
            <div class="crm-submit-buttons">
                <a href="{crmURL p='civicrm/admin/uf/group/add' q='action=add&reset=1'}" id="newCiviCRMProfile-bottom" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Profile{/ts}</span></a>
            </div>
            {/if}
            </div>
        </div>{* user profile*}

        <div id="reserved-profiles">
        <div class="crm-content-block">
            <table class="display">
             <thead>
              <tr>
                <th id="sortable">{ts}Profile Title{/ts}</th>
                <th>{ts}Type{/ts}</th>
                <th>{ts}ID{/ts}</th>
                <th id="nosort">{ts}Used For{/ts}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            {foreach from=$rows item=row}
            {if $row.is_reserved}
              <tr id="row_{$row.id}"class="{$row.class}{if NOT $row.is_active} disabled{/if}">
                <td>{$row.title}</td>
                <td>{$row.group_type}</td>
                <td>{$row.id}</td>
                <td>{$row.module}</td>
                <td>{$row.action|replace:'xx':$row.id}</td>
              </tr>
            {/if}
            {/foreach}
            </tbody>
            </table>

            {if NOT ($action eq 1 or $action eq 2)}
            <div class="crm-submit-buttons">
                <a href="{crmURL p='civicrm/admin/uf/group/add' q='action=add&reset=1'}" id="newCiviCRMProfile-bottom" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Profile{/ts}</span></a>
            </div>
            {/if}
            </div>
        </div>{* reserved profile*}

  </div> {* maincontainer*}
  <script type='text/javascript'>
    var selectedTab = 'user-profiles';
    {if $selectedChild}selectedTab = '{$selectedChild}';{/if}
    {literal}
      cj( function() {
        var tabIndex = cj('#tab_' + selectedTab).prevAll().length
        cj("#mainTabContainer").tabs( {selected: tabIndex} );
      });
    {/literal}
  </script>
    {else}
    {if $action ne 1} {* When we are adding an item, we should not display this message *}
       <div class="messages status">
          &nbsp;
         {capture assign=crmURL}{crmURL p='civicrm/admin/uf/group/add' q='action=add&reset=1'}{/capture}{ts 1=$crmURL}No CiviCRM Profiles have been created yet. You can <a href='%1'>add one now</a>.{/ts}
       </div>
    {/if}
    {/if}
{/if}
