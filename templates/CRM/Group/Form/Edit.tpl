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
{* this template is used for adding/editing group (name and description only)  *}
<div class="crm-block crm-form-block crm-group-form-block">
    <div id="help">
	{if $action eq 2}
	    {capture assign=crmURL}{crmURL p="civicrm/group/search" q="reset=1&force=1&context=smog&gid=`$group.id`"}{/capture}
	    {ts 1=$crmURL}You can edit the Name and Description for this group here. Click <a href='%1'>Contacts in this Group</a> to view, add or remove contacts in this group.{/ts}
	{else}
	    {ts}Enter a unique name and a description for your new group here. Then click 'Continue' to find contacts to add to your new group.{/ts}
	{/if}
    </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr class="crm-group-form-block-title">
	    <td class="label">{$form.title.label}</td>
            <td>{$form.title.html|crmReplace:class:huge}
                {if $group.saved_search_id}&nbsp;({ts}Smart Group{/ts}){/if}
            </td>
        </tr>
	
        <tr class="crm-group-form-block-description">
	    <td class="label">{$form.description.label}</td>
	    <td>{$form.description.html}<br />
		<span class="description">{ts}Group description is displayed when groups are listed in Profiles and Mailing List Subscribe forms.{/ts}</span>
            </td>
        </tr>

	{if $form.group_type}
	    <tr class="crm-group-form-block-group_type">
		<td class="label">{$form.group_type.label}</td>
		<td>{$form.group_type.html} {help id="id-group-type" file="CRM/Group/Page/Group.hlp"}</td>
	    </tr>
	{/if}
    
  {if $form.remote_group_id}
	<tr class="crm-group-form-block-remote_group_id">
		<td class="label">{$form.remote_group_id.label}</td>
		<td>
      <div>
      {$form.remote_group_id.html}
      {if $smart_marketing_sync}
        <input class="form-submit default smart-marketing-button" name="sync_{$smart_marketing_vendor}" value="{ts}Manually Synchronize{/ts}" type="button" id="sync-{$smart_marketing_vendor}">
        <div id="smart-marketing-sync-confirm" class="hide-block">
          {ts}The automated marketing journey does not start immediately and it needs to follow the external tool to schedule.{/ts}
        </div>
      {/if}
      </div>
      <div class="description">
        <div>{ts}After binding a group, the system will lock this group, preventing any edits.{/ts} {docURL page="Smart Marketing Group"}</div>
        <div>{ts}The will be synchronized at a fixed time every day.{/ts}</div>
        <div>{ts}If there is an immediate need, you can click "Manual Sync".{/ts}</div>
      </div>
<script>{literal}
cj(function($){
  $('.crm-group-form-block-remote_group_id').hide();
  if ($('.crm-group-form-block-remote_group_id input[type=hidden][name=remote_group_id]').length) {
    $('.crm-group-form-block-remote_group_id').show();
  }
  $('input[name*=group_type]').each(function(){
    if($(this).data('filter') && $(this).data('filter').match('Smart-Marketing')) {
      if ($(this).prop("checked")) {
        $('.crm-group-form-block-remote_group_id').show();
      }
      $(this).click(function(){
        if ($(this).prop("checked")) {
          $('.crm-group-form-block-remote_group_id').show();
        }
        else {
          $('.crm-group-form-block-remote_group_id').hide();
        }
      });
    }
  });
  $('.smart-marketing-button').click(function(e){
    e.preventDefault();
    let dialogCls = "smart-marketing-sync-confirm-box";
    $("#smart-marketing-sync-confirm").dialog({
      title: "{/literal}{ts}Manually Synchronize{/ts}{literal}",
      autoOpen: false,
      modal: true,
      dialogClass: dialogCls,
      open: function(event, ui ) {
        let isSynced = $(this).dialog("option", 'synced');
        if (isSynced) {
          $('.'+dialogCls).find('.ui-dialog-buttonset button').eq(0).attr('disabled', true).addClass('ui-state-disabled');
        }
      },
      buttons: {
        "{/literal}{ts}Sync Now{/ts}{literal}": function() {
          $(this).dialog("option", 'synced', true);
          $('.'+dialogCls).find('.ui-dialog-buttonset button').eq(0).attr("disabled", true).addClass("ui-state-disabled");
          let dataURL = "{/literal}{crmURL p='civicrm/ajax/addContactToRemote' q='snippet=5'}{literal}";
          let groupId = "{/literal}{$group.id}{literal}";
          let runningStr= "{/literal}{ts}Running{/ts}{literal}";
          $('#smart-marketing-sync-confirm').html(runningStr+'<i class="zmdi zmdi-rotate-right zmdi-hc-spin"></i>');
          $.ajax({
            url: dataURL,
            type: "POST",
            data: {"group_id":groupId},
            dataType: "json",
            success: function(data) {
              if (data.success) {
                $('#smart-marketing-sync-confirm').html(data.message);
              }
              else {
                $('#smart-marketing-sync-confirm').html('<i class="zmdi zmdi-refresh-sync-alert"></i> '+data.message);
              }
            }
          });
          return true;
        },
        "{/literal}{ts}Close{/ts}{literal}": function() {
          $(this).dialog("close");
          return false;
        }
      }
    });
    cj("#smart-marketing-sync-confirm").dialog('open');
  });
});
{/literal}</script>
    </td>
	</tr>
  {/if}
  <tr class="crm-group-form-block-visibility">
	    <td class="label">{$form.visibility.label}</td>
	    <td>
        {$form.visibility.html|crmReplace:class:huge}
        <div class="description">
          {ts}Select 'User and User Admin Only' if joining this group is controlled by authorized CiviCRM users only. If you want to allow contacts to join and remove themselves from this group via the Registration and Account Profile forms, select 'Public Pages'.{/ts}
          </div>
      </td>
	</tr>
	
	<tr>
	    <td colspan=2>{include file="CRM/Custom/Form/CustomData.tpl"}</td>
	</tr> 
    </table>

    {if $parent_groups|@count > 0 or $form.parents.html}
	<h3>{ts}Parent Groups{/ts} {help id="id-group-parent" file="CRM/Group/Page/Group.hlp"}</h3>
        {if $parent_groups|@count > 0}
	    <table class="form-layout-compressed">
		<tr>
		    <td><label>{ts}Remove Parent?{/ts}</label></td>
		</tr>
		{foreach from=$parent_groups item=cgroup key=group_id}
		    {assign var="element_name" value="remove_parent_group_"|cat:$group_id}
		    <tr>
			<td>&nbsp;&nbsp;{$form.$element_name.html}&nbsp;{$form.$element_name.label}</td>
		    </tr>
		{/foreach}
	    </table>
	    <br />
        {/if}
        <table class="form-layout-compressed">
	    <tr class="crm-group-form-block-parents">
	        <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.parents.label}</td>
	        <td>{$form.parents.html|crmReplace:class:huge}</td>
	    </tr>
	</table>
    {/if}

    {if $form.organization}
	<h3>{ts}Associated Organization{/ts} {help id="id-group-organization" file="CRM/Group/Page/Group.hlp"}</h3>
	        <table class="form-layout-compressed">
		    <tr class="crm-group-form-block-organization">
		        <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.organization.label}</td>
			<td>{$form.organization.html|crmReplace:class:huge}
			    <div id="organization_address" style="font-size:10px"></div>
			</td>
		    </tr>
		</table>
    {/if} 
	
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

    {capture assign=subsUrl}{crmURL p="civicrm/mailing/subscribe" q="reset=1"}{/capture}
    <div id="dialog-confirm-groupname" title="{ts}Confirm public group name{/ts}" style="display:none;">
      <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span>{ts 1=$subsUrl 2="[[placeholder]]"}The public group will be displayed on the <a href="%1" target="_blank">mailing list subscription</a> page for the general public to browse. Please check if this group name "%2" is correct.{/ts}</p>
      <p>{ts}Are you sure you want to continue?{/ts}</p>
    </div>
    <div id="dialog-confirm-disablesubs" title="{ts}Removing the last public group{/ts}" style="display:none;">
      <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span>{ts 1="$subsUrl"}The group you selected is the last public newsletter group. After removal, the <a href="%1" target="_blank">mailing list subscription</a> function will be disabled.{/ts}</p>
      <p>{ts}Are you sure you want to continue?{/ts}</p>
    </div>
    {if $action neq 1}
	<div class="action-link-button">
	    <a href="{$crmURL}">&raquo; {ts}Contacts in this Group{/ts}</a>
	    {if $group.saved_search_id} 
	        <br />
		{if !$group.custom_search_class}
		    {if $group.mapping_id}
			<a href="{crmURL p="civicrm/contact/search/builder" q="reset=1&force=1&ssID=`$group.saved_search_id`"}">&raquo; {ts}Edit Smart Group Criteria{/ts}</a>
		    {else}
			<a href="{crmURL p="civicrm/contact/search/advanced" q="reset=1&force=1&ssID=`$group.saved_search_id`"}">&raquo; {ts}Edit Smart Group Criteria{/ts}</a>
		    {/if}
		{/if}
		
	    {/if}
	</div>
    {/if}
</fieldset>

{literal}
<script type="text/javascript">
cj(document).ready( function($) {
  let lastPublicSubsGroup = "{/literal}{$lastPublicSubsGroup}{literal}";
  $("input[name=_qf_Edit_upload]").on("click", function(e){
    e.preventDefault();
    let thisform = $(this).closest('form');
    if ($("#visibility").val() === 'Public Pages') {
      let groupName = $('input[name=title]').val();
      $('#dialog-confirm-groupname').html($('#dialog-confirm-groupname').html().replace('[[placeholder]]', groupName));
      $("#dialog-confirm-groupname").dialog({
        autoOpen: false,
        resizable: false,
        width:500,
        height:300,
        modal: true,
        buttons: {
          "{/literal}{ts}OK{/ts}{literal}": function() {
            $(this).dialog("close");
            thisform.submit();
            return true;
          },
          "{/literal}{ts}Cancel{/ts}{literal}": function() {
            $(this).dialog("close");
            $("input[name=_qf_Edit_upload]").removeAttr('readonly');
          }
        }
      });
      $('#dialog-confirm-groupname').dialog('open');
    }
    else if(lastPublicSubsGroup && ($("#group_type\\\[2\\\]").prop('checked') === false || $("#visibility").val() !== 'Public Pages')) {
      $("#dialog-confirm-disablesubs").dialog({
        autoOpen: false,
        resizable: false,
        width:500,
        height:300,
        modal: true,
        buttons: {
          "{/literal}{ts}OK{/ts}{literal}": function() {
            $(this).dialog("close");
            thisform.submit();
            return true;
          },
          "{/literal}{ts}Cancel{/ts}{literal}": function() {
            $(this).dialog("close");
            $("input[name=_qf_Edit_upload]").removeAttr('readonly');
          }
        }
      });
      $('#dialog-confirm-disablesubs').dialog('open');
    }
    else {
      thisform.submit();
    }
  });
});
{/literal}{if $organizationID}{literal}
    cj(document).ready( function() { 
	//group organzation default setting
	var dataUrl = "{/literal}{crmURL p='civicrm/ajax/search' h=0 q="org=1&id=$organizationID"}{literal}";
	cj.ajax({ 
	        url     : dataUrl,   
	        async   : false,
	        success : function(html){ 
	                    //fixme for showing address in div
	                    htmlText = html.split( '|' , 2);
	                    htmlDiv = htmlText[0].replace( /::/gi, ' ');
			    cj('#organization').val(htmlText[0]);
	                    cj('div#organization_address').html(htmlDiv);
	                  }
	});
    });
{/literal}{/if}{literal}

var dataUrl = "{/literal}{$groupOrgDataURL}{literal}";
cj('#organization').autocomplete( dataUrl, {
					    width : 250, selectFirst : false, matchContains: true  
					    }).result( function(event, data, formatted) {
                                                       cj( "#organization_id" ).val( data[1] );
                                                       htmlDiv = data[0].replace( /::/gi, ' ');
                                                       cj('div#organization_address').html(htmlDiv);
						      });

</script>
{/literal}
</div>

{include file="CRM/common/chosen.tpl" selector="#parents"}
