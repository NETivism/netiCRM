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
{capture assign=crmURL}{crmURL p='civicrm/admin/messageTemplates/add' q="action=add&reset=1"}{/capture}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/MessageTemplates.tpl"}
   
{elseif $action eq 4}
  {* View a system default workflow template *}

  <div id="help">
    {ts}You are viewing the default message template for this system workflow.{/ts} {help id="id-view_system_default"}
  </div>

  <fieldset>
  <div class="crm-section msg_title-section">
    <div class="bold">{$form.msg_title.value}</div>
  </div>
  <div class="crm-section msg_subject-section">
  <h3 class="header-dark">{$form.msg_subject.label}</h3>
    <div class="text">
      <textarea name="msg-subject" id="msg_subject" style="height: 6em; width: 45em;">{$form.msg_subject.value}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_subject.select(); return false;' class='button'><span>Select Subject</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>
  
  <div class="crm-section msg_txt-section">
  <h3 class="header-dark">{$form.msg_text.label}</h3>
    <div class="text">
      <textarea class="huge" name='msg_text' id='msg_text'>{$form.msg_text.value|htmlentities}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_text.select(); return false;' class='button'><span>Select Text Message</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>

  <div class="crm-section msg_html-section">
  <h3 class="header-dark">{$form.msg_html.label}</h3>
    <div class='text'>
      <textarea class="huge" name='msg_html' id='msg_html'>{$form.msg_html.value|htmlentities}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_html.select(); return false;' class='button'><span>Select HTML Message</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>
  
  <div id="crm-submit-buttons">{$form.buttons.html}</div>
  </fieldset>
{/if}

{if $rows and $action ne 2 and $action ne 4}

  <div id='mainTabContainer'>
    <ul>
      <li id='tab_user'>    <a href='#user'     title='{ts}User-driven Messages{/ts}'>    {ts}User-driven Messages{/ts}    </a></li>
      <li id='tab_workflow'><a href='#workflow' title='{ts}System Workflow Messages{/ts}'>{ts}System Workflow Messages{/ts}</a></li>
    </ul>
  
    {* create two selector tabs, first being the ‘user’ one, the second being the ‘workflow’ one *}
    {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    {foreach from=$rows item=template_row key=type}
      <div id="{if $type eq 'userTemplates'}user{else}workflow{/if}" class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
          <div class="help">
          {if $type eq 'userTemplates'}
            {ts}User-driven message templates allow you to save and re-use messages with layouts. They are useful if you need to send similar emails to contacts on a recurring basis. You can also use them in CiviMail Mailings and they are required for CiviMember membership renewal reminders.{/ts} {help id="id-intro"}
          {else}
            {ts}System workflow message templates are used to generate the emails sent to consituents and administrators for contribution receipts, event confirmations and many other workflows. You can customize the style and wording of these messages here.{/ts} {help id="id-system-workflow"}
          {/if}
          </div>
        <div>
          <p></p>
            {if !empty( $template_row) }
              <table class="display">
                <thead>
                  <tr>
                    <th class="sortable">{if $type eq 'userTemplates'}{ts}Message Title{/ts}{else}{ts}Workflow{/ts}{/if}</th>
                    {if $type eq 'userTemplates'}
                      <th>{ts}Message Subject{/ts}</th>
                      <th>{ts}Enabled?{/ts}</th>
                    {/if}
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                {foreach from=$template_row item=row}
                    <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
                      <td>
                        {$row.msg_title}
                        {if $row.workflow}
                          <div class="description">debug: {$row.workflow.groupName}-{$row.workflow.valueName}</div>
                        {/if}
                      </td>
                      {if $type eq 'userTemplates'}
                        <td>{$row.msg_subject}</td>
                        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                      {/if}
                      <td>{$row.action|replace:'xx':$row.id}</td>
                    </tr>
                {/foreach}
                </tbody>
              </table>
              {/if}

            {if $action ne 1 and $action ne 2 and $type eq 'userTemplates'}
              <div class="action-link-button">
                <a href="{crmURL p='civicrm/admin/messageTemplates/add' q="action=add&reset=1"}" id="newMessageTemplates" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Message Template{/ts}</span></a>
              </div>
              <div class="spacer"></div>
            {/if}
            
            {if empty( $template_row) }
                <div class="messages status">
                    &nbsp;
                    {ts 1=$crmURL}There are no User-driven Message Templates entered. You can <a href='%1'>add one</a>.{/ts}
                </div>
            {/if}
         </div>
      </div>
    {/foreach}
  </div>

  <script type='text/javascript'>
    var selectedTab = 'user';
    {if $selectedChild}selectedTab = '{$selectedChild}';{/if}
    {literal}
      cj( function() {
        var stateObj = {"tabs": 1};
        var tabIndex = cj('#tab_' + selectedTab).prevAll().length
        cj("#mainTabContainer").tabs( {
          selected: tabIndex,
          activate: function(){
            var historyObj = {"tab":tabIndex};
            var currentTab = cj("#mainTabContainer .ui-tabs-active a").prop("href");
            history.replaceState(stateObj, "", currentTab);
          }
        } );
      });
    {/literal}
  </script>

{elseif $action ne 1 and $action ne 2 and $action ne 4 and $action ne 8}
  <div class="messages status">
      {ts 1=$crmURL}There are no Message Templates entered. You can <a href='%1'>add one</a>.{/ts}
  </div>
{/if}
