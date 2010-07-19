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
{* CiviCase -  view case screen*}
<div class="form-item">
<fieldset><legend>{ts}Case Summary{/ts}</legend>
    <table class="report">
        <tr>
            <td>
		<table class="form-layout-compressed">
      	       	   <tr><td class="label-left" style="padding: 0px">{ts}Client{/ts}:</td>
		       <td class="label-left" style="padding: 0px">{$displayName}</td>
		   </tr>
	       	   {if $caseRoles.client.phone}
		       <tr><td class="label" style="padding: 0px" ></td>
		       	   <td class="label-left description" style="padding: 0px">{$caseRoles.client.phone}</td>
		       </tr>
		   {/if}
	    	</table>
            </td>
            <td>
                <label>{ts}Case Type{/ts}:</label>&nbsp;{$caseDetails.case_type}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseTypeId`"}" title="Change case type (creates activity record)"><img src="{$config->resourceBase}i/edit.png" border="0"></a>
            </td>
            <td>
                <label>{ts}Status{/ts}:</label>&nbsp;{$caseDetails.case_status}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStatusId`"}" title="Change case status (creates activity record)"><img src="{$config->resourceBase}i/edit.png" border="0"></a>
            </td>
            <td>
                <label>{ts}Start Date{/ts}:</label>&nbsp;{$caseDetails.case_start_date|crmDate}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStartDateId`"}" title="Change case start date (creates activity record)"><img src="{$config->resourceBase}i/edit.png" border="0"></a>
            </td>
            <td>
                <label>{ts}Case ID{/ts}:</label>&nbsp;{$caseID}
            </td>
        </tr>
    </table>
    {if $hookCaseSummary}
      <div id="caseSummary">
      {foreach from=$hookCaseSummary item=val key=div_id}
        <div id="{$div_id}"><label>{$val.label}</label><div class="value">{$val.value}</div></div>
      {/foreach}
      </div>
    {/if}
    <table class="form-layout">
        <tr>
            <td colspan="2">{$form.activity_type_id.label}<br />{$form.activity_type_id.html}&nbsp;<input type="button" accesskey="N" value="Go" name="new_activity" onclick="checkSelection( this );"/></td>
		</tr>
		<tr>
            <td>{$form.timeline_id.label}<br />{$form.timeline_id.html}&nbsp;{$form._qf_CaseView_next.html}</td> 
            <td>{$form.report_id.label}<br />{$form.report_id.html}&nbsp;<input type="button" accesskey="R" value="Go" name="case_report" onclick="checkSelection( this );"/></td> 
        </tr>
    </table>
</fieldset>

<div id="caseRole_show" class="section-hidden section-hidden-border">
  <a href="#" onclick="hide('caseRole_show'); show('caseRole'); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="open section"/></a><label>{ts}Case Roles{/ts}</label><br />
</div>

<div id="caseRole" class="section-shown">
 <fieldset>
  <legend><a href="#" onclick="hide('caseRole'); show('caseRole_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="close section"/></a>{ts}Case Roles{/ts}</legend>

    <div><input type="button" class="form-submit default" onClick="Javascript:addRole()" value="{ts}Add new role{/ts}" /></div>

    <table class="report">
    	<tr class="columnheader">
    		<th>{ts}Case Role{/ts}</th>
    		<th>{ts}Name{/ts}</th>
    	   	<th>{ts}Phone{/ts}</th>
            <th>{ts}Email{/ts}</th>
    		<th>{ts}Actions{/ts}</th>
    	</tr>
		{assign var=rowNumber value = 1}
        {foreach from=$caseRelationships item=row key=relId}
        <tr>
            <td class="label">{$row.relation}</td>
            <td id="relName_{$rowNumber}"><a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$row.cid`"}" title="view contact record">{$row.name}</a></td>
           
            <td id="phone_{$rowNumber}">{$row.phone}</td><td id="email_{$rowNumber}">{if $row.email}<a href="{crmURL p='civicrm/contact/view/activity' q="reset=1&action=add&atype=3&cid=`$row.cid`&caseid=`$caseID`"}" title="{ts}compose and send an email{/ts}"><img src="{$config->resourceBase}i/EnvelopeIn.gif" alt="{ts}compose and send an email{/ts}"/></a>&nbsp;{/if}</td>
          {if $relId neq 'client'}
            <td id ="edit_{$rowNumber}"><img src="{$config->resourceBase}i/edit.png" title="edit case role" onclick="createRelationship( {$row.relation_type}, {$row.cid}, {$relId}, {$rowNumber} );">&nbsp;&nbsp;<a href="{crmURL p='civicrm/contact/view/rel' q="action=delete&reset=1&cid=`$contactID`&id=`$relId`&caseID=`$caseID`"}" onclick = "if (confirm('{ts}Are you sure you want to remove this person from their case role{/ts}?') ) this.href+='&confirmed=1'; else return false;"><img title="remove contact from case role" src="{$config->resourceBase}i/delete.png"/></a></td>
          {else}
            <td></td>
          {/if}
        </tr>
		{assign var=rowNumber value = `$rowNumber+1`}
        {/foreach}

        {foreach from=$caseRoles item=relName key=relTypeID}
         {if $relTypeID neq 'client'} 
           <tr>
               <td class="label">{$relName}</td>
               <td id="relName_{$rowNumber}">(not assigned)</td>
               <td id="phone_{$rowNumber}"></td>
               <td id="email_{$rowNumber}"></td>
               <td id ="edit_{$rowNumber}"><img title="assign contact to case role" src="{$config->resourceBase}i/edit.png" onclick="createRelationship( {$relTypeID}, null, null, {$rowNumber} );"></td>
           </tr>
         {else}
           <tr>
               <td class="label">{$relName.role}</td>
               <td id="relName_{$rowNumber}"><a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$relName.contact_id`"}" title="view contact record">{$relName.sort_name}</a></td>
               <td id="phone_{$rowNumber}">{$relName.phone}</td>
               <td id="email_{$rowNumber}">{if $relName.email}<a href="{crmURL p='civicrm/contact/view/activity' q="reset=1&action=add&atype=3&cid=`$relName.contact_id`&caseid=`$caseID`"}" title="{ts}compose and send an email{/ts}"><img src="{$config->resourceBase}i/EnvelopeIn.gif" alt="{ts}compose and send an email{/ts}"/></a>&nbsp;{/if}</td>
               <td></td>
           </tr> 
         {/if}
		{assign var=rowNumber value = `$rowNumber+1`}
        {/foreach}
    </table>    
 </fieldset>
</div>
<div id="dialog">
     {ts}Begin typing last name of contact.{/ts}<br/>
     <input type="text" id="rel_contact"/>
     <input type="hidden" id="rel_contact_id" value="">
</div>

{literal}
<script type="text/javascript">
show('caseRole_show');
hide('caseRole');

cj("#dialog").hide( );
function createRelationship( relType, contactID, relID, rowNumber ) {
    cj("#dialog").show( );

	cj("#dialog").dialog({
		title: "Assign Case Role",
		modal: true, 
		bgiframe: true,
		overlay: { 
			opacity: 0.5, 
			background: "black" 
		},
		beforeclose: function(event, ui) {
            cj(this).dialog("destroy");
        },

		open:function() {
			/* set defaults if editing */
			cj("#rel_contact").val( "" );
			cj("#rel_contact_id").val( null );
			if ( contactID ) {
				cj("#rel_contact_id").val( contactID );
				cj("#rel_contact").val( cj("#relName_" + rowNumber).text( ) );
			}

			var contactUrl = {/literal}"{crmURL p='civicrm/ajax/contactlist' q='context=caseview' h=0 }"{literal};

			cj("#rel_contact").autocomplete( contactUrl, {
				width: 260,
				selectFirst: false,
	                        matchContains: true 
			});
			
			cj("#rel_contact").focus();
			cj("#rel_contact").result(function(event, data, formatted) {
				cj("input[id=rel_contact_id]").val(data[1]);
			});		    
		},

		buttons: { 
			"Ok": function() { 	    
				if ( ! cj("#rel_contact").val( ) ) {
					alert('{/literal}{ts}Select valid contact from the list{/ts}{literal}.');
					return false;
				}

				var sourceContact = {/literal}"{$contactID}"{literal};
				var caseID        = {/literal}"{$caseID}"{literal};

				var v1 = cj("#rel_contact_id").val( );

				if ( ! v1 ) {
					alert('{/literal}{ts}Select valid contact from the list{/ts}{literal}.');
					return false;
				}

				var postUrl = {/literal}"{crmURL p='civicrm/ajax/relation' h=0 }"{literal};
                cj.post( postUrl, { rel_contact: v1, rel_type: relType, contact_id: sourceContact, rel_id: relID, case_id: caseID },
                    function( data ) {
                        var resourceBase   = {/literal}"{$config->resourceBase}"{literal};

			var html = '';			
			if ( data.cid ) {
                            var contactViewUrl = {/literal}"{crmURL p='civicrm/contact/view' q='action=view&reset=1&cid=' h=0 }"{literal};	
                            var deleteUrl      = {/literal}"{crmURL p='civicrm/contact/view/rel' q="action=delete&reset=1&cid=`$contactID`&caseID=`$caseID`&id=" h=0 }"{literal};	
                            var html = '<a href=' + contactViewUrl + data.cid +' title="view contact record">' +  data.name +'</a>';
                            cj('#relName_' + rowNumber ).html( html );

                           html = '';
                           html = '<img src="' +resourceBase+'i/edit.png" title="edit case role" onclick="createRelationship( ' + relType +','+ data.cid +', ' + data.rel_id +', ' + rowNumber +' );">&nbsp;&nbsp; <a href=' + deleteUrl + data.rel_id +' onclick = "if (confirm(\'{/literal}{ts}Are you sure you want to delete this relationship{/ts}{literal}?\') ) this.href +=\'&confirmed=1\'; else return false;"><img title="remove contact from case role" src="' +resourceBase+'i/delete.png"/></a>';
                           cj('#edit_' + rowNumber ).html( html );

			} else {
			   html = '<img src="' +resourceBase+'i/edit.png" title="edit case role" onclick="createRelationship( ' + relType +','+ data.cid +', ' + data.rel_id +', ' + rowNumber +' );">&nbsp;&nbsp;';

			   var errorMsg = '{/literal}{ts}Please select valid contact{/ts}{literal}.'; 

			   cj('#edit_' + rowNumber ).html( html + errorMsg );
			}

                        html = '';
                        if ( data.phone ) {
                            html = data.phone;
                        }	
                        cj('#phone_' + rowNumber ).html( html );

                        html = '';
                        if ( data.email ) {
                            var activityUrl = {/literal}"{crmURL p='civicrm/contact/view/activity' q="atype=3&action=add&reset=1&caseid=`$caseID`&cid=" h=0 }"{literal};
                            html = '<a href=' + activityUrl + data.cid + '><img src="'+resourceBase+'i/EnvelopeIn.gif" alt="Send Email"/></a>&nbsp;';
                        } 
                        cj('#email_' + rowNumber ).html( html );

                        }, 'json' 
                    );

				cj(this).dialog("close"); 
				cj(this).dialog("destroy"); 
			},

			"Cancel": function() { 
				cj(this).dialog("close"); 
				cj(this).dialog("destroy"); 
			} 
		} 

	});
}

function showHideSearch( ) {
   cj("#searchOptions").toggle( );
   if ( cj("#searchFilter").hasClass('collapsed') ) {
       cj("#searchFilter").removeClass('collapsed');
       cj("#searchFilter").addClass('expanded');
   } else {
       cj("#searchFilter").removeClass('expanded');
       cj("#searchFilter").addClass('collapsed');
   }
}

cj(document).ready(function(){
   cj("#searchOptions").hide( );
   cj("#view-activity").hide( );
});
</script>
{/literal}

<div id="otherRel_show" class="section-hidden section-hidden-border">
  <a href="#" onclick="hide('otherRel_show'); show('otherRel'); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="open section"/></a><label>{ts}Other Relationships{/ts}</label><br />
</div>

<div id="otherRel" class="section-shown">
 <fieldset>
  <legend><a href="#" onclick="hide('otherRel'); show('otherRel_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="close section"/></a>{ts}Other Relationships{/ts}</legend>
  
  {if $clientRelationships}
    <div><input type="button" class="form-submit default" onClick="window.location='{crmURL p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`"}'" value="{ts}Add client relationship{/ts}" /></div>
	
    <table class="report">
    	<tr class="columnheader">
    		<th>{ts}Client Relationship{/ts}</th>
    		<th>{ts}Name{/ts}</th>
    		<th>{ts}Phone{/ts}</th>
    		<th>{ts}Email{/ts}</th>
    	</tr>
        {foreach from=$clientRelationships item=row key=relId}
        <tr>
            <td class="label">{$row.relation}</td>
            <td id="relName_{$rowNumber}"><a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$row.cid`"}" title="view contact record">{$row.name}</a></td>
            <td id="phone_{$rowNumber}">{$row.phone}</td><td id="email_{$rowNumber}">{if $row.email}<a href="{crmURL p='civicrm/contact/view/activity' q="reset=1&action=add&atype=3&cid=`$row.cid`&caseid=`$caseID`"}" title="{ts}compose and send an email{/ts}"><img src="{$config->resourceBase}i/EnvelopeIn.gif" alt="{ts}compose and send an email{/ts}"/></a>&nbsp;{/if}</td>
        </tr>
		{assign var=rowNumber value = `$rowNumber+1`}
        {/foreach}
    </table>
  {else}
    <div class="messages status">
      <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
        <dd>
          {capture assign=crmURL}{crmURL p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`"}{/capture}
          {ts 1=$crmURL}There are no Relationships entered for this client. You can <a accesskey="N" href='%1'>add one</a>.{/ts}
        </dd>
      </dl>
    </div>
  {/if}

  <br />
  
  {if $globalRelationships}
    <div><input type="button" class="form-submit default" onClick="window.location='{crmURL p='civicrm/group/search' q="reset=1&context=amtg&amtgID=`$globalGroupInfo.id`"}'" value="{ts 1=$globalGroupInfo.title}Add members to %1{/ts}" /></div>
	
    <table class="report">
    	<tr class="columnheader">
    		<th>{$globalGroupInfo.title}</th>
    		<th>{ts}Phone{/ts}</th>
    		<th>{ts}Email{/ts}</th>
    	</tr>
        {foreach from=$globalRelationships item=row key=relId}
        <tr>
            <td id="relName_{$rowNumber}"><a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$row.contact_id`"}" title="view contact record">{$row.sort_name}</a></td>
            <td id="phone_{$rowNumber}">{$row.phone}</td><td id="email_{$rowNumber}">{if $row.email}<a href="{crmURL p='civicrm/contact/view/activity' q="reset=1&action=add&atype=3&cid=`$row.contact_id`&caseid=`$caseID`"}" title="{ts}compose and send an email{/ts}"><img src="{$config->resourceBase}i/EnvelopeIn.gif" alt="{ts}compose and send an email{/ts}"/></a>&nbsp;{/if}</td>
        </tr>
		{assign var=rowNumber value = `$rowNumber+1`}
        {/foreach}
    </table>
  {elseif $globalGroupInfo.id}
    <div class="messages status">
      <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
        <dd>          
          {capture assign=crmURL}{crmURL p='civicrm/group/search' q="reset=1&context=amtg&amtgID=`$globalGroupInfo.id`"}{/capture}
          {ts 1=$crmURL 2=$globalGroupInfo.title}The group %2 has no members. You can <a href='%1'>add one</a>.{/ts}
        </dd>
      </dl>
    </div>
  {/if}

 </fieldset>
</div>
{literal}
<script type="text/javascript">
show('otherRel_show');
hide('otherRel');
</script>
{/literal}

<div id="addRoleDialog">
{$form.role_type.label}<br />
{$form.role_type.html}
<br /><br />
    {ts}Begin typing last name of contact.{/ts}<br/>
    <input type="text" id="role_contact"/>
    <input type="hidden" id="role_contact_id" value="">
</div>

{literal}
<script type="text/javascript">

cj("#addRoleDialog").hide( );
function addRole() {
    cj("#addRoleDialog").show( );

	cj("#addRoleDialog").dialog({
		title: "Add Role",
		modal: true,
		bgiframe: true, 
		overlay: { 
			opacity: 0.5, 
			background: "black" 
		},

        beforeclose: function(event, ui) {
            cj(this).dialog("destroy");
        },

		open:function() {
			/* set defaults if editing */
			cj("#role_contact").val( "" );
			cj("#role_contact_id").val( null );

			var contactUrl = {/literal}"{crmURL p='civicrm/ajax/contactlist' q='context=caseview' h=0 }"{literal};

			cj("#role_contact").autocomplete( contactUrl, {
				width: 260,
				selectFirst: false,
				matchContains: true 
			});
			
			cj("#role_contact").focus();
			cj("#role_contact").result(function(event, data, formatted) {
				cj("input[id=role_contact_id]").val(data[1]);
			});		    
		},

		buttons: { 
			"Ok": function() { 	    
				if ( ! cj("#role_contact").val( ) ) {
					alert('{/literal}{ts}Select valid contact from the list{/ts}{literal}.');
					return false;
				}

				var sourceContact = {/literal}"{$contactID}"{literal};
				var caseID        = {/literal}"{$caseID}"{literal};
				var relID         = null;

				var v1 = cj("#role_contact_id").val( );

				if ( ! v1 ) {
					alert('{/literal}{ts}Select valid contact from the list{/ts}{literal}.');
					return false;
				}

				var v2 = cj("#role_type").val();
				if ( ! v2 ) {
					alert('{/literal}{ts}Select valid type from the list{/ts}{literal}.');
					return false;
				}
				
               /* send synchronous request so that disabling any actions for slow servers*/
				var postUrl = {/literal}"{crmURL p='civicrm/ajax/relation' h=0 }"{literal}; 
                var data = 'rel_contact='+ v1 + '&rel_type='+ v2 + '&contact_id='+sourceContact + '&rel_id='+ relID + '&case_id=' + caseID;
                cj.ajax({ type: "POST", url: postUrl, data: data, async: false });
 
				cj(this).dialog("close"); 
				cj(this).dialog("destroy");
				
// Temporary workaround for problems with SSL connections being too
// slow. The relationship doesn't get created because the page reload
// happens before the ajax call.
// In general this reload needs improvement, which is already on the list for phase 2.
var sdate = (new Date()).getTime();
var curDate = sdate;
while(curDate-sdate < 2000) {
curDate = (new Date()).getTime();
}
				window.location.reload(); 
			},

			"Cancel": function() { 
				cj(this).dialog("close"); 
				cj(this).dialog("destroy"); 
			} 
		} 

	});
}

</script>
{/literal}

{*include activity view js file*}
{include file="CRM/common/activityView.tpl"}

<div id="activities_show" class="section-hidden section-hidden-border">
  <a href="#" onclick="hide('activities_show'); show('activities'); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="open section"/></a><label>{ts}Case Activities{/ts}</label><br />

<div id="view-activity">
     <div id="activity-content"></div>
</div>
</div>

<div id="activities" class="section-shown">
<fieldset>
<legend><a href="#" onclick="hide('activities'); show('activities_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="close section"/></a>{ts}Case Activities{/ts}</legend>
  <div><a id="searchFilter" href="javascript:showHideSearch( );" class="collapsed">{ts}Search Filters{/ts}</a></div>
  <table class="no-border form-layout-compressed" id="searchOptions">
    <tr>
        <td colspan="2"><label for="reporter_id">{ts}Reporter/Role{/ts}</label><br />
            {$form.reporter_id.html}
        </td>
        <td><label for="status_id">{$form.status_id.label}</label><br />
            {$form.status_id.html}
        </td>
	<td style="vertical-align: bottom;"><input class="form-submit default" name="_qf_Basic_refresh" value="Search" type="button" onclick="search()"; /></td>
    </tr>
    <tr>
        <td>
	        {$form.activity_date_low.label}<br />
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_low}
        </td>
        <td> 
            {$form.activity_date_high.label}<br /> 
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_high}
        </td>
        <td>
            {$form.activity_type_filter_id.label}<br />
            {$form.activity_type_filter_id.html}
        </td>
    </tr>
    {if $form.activity_deleted}    
    	<tr>
	     <td>
		 {$form.activity_deleted.html}{$form.activity_deleted.label}
	     </td>
	</tr>
	{/if}
  </table>
  <br />

  <table id="activities-selector"  class="nestedActivitySelector" style="display:none"></table>

</fieldset>
</div> <!-- End Activities div -->


{literal}
<script type="text/javascript">
cj(document).ready(function(){

    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/activity' h=0 q='snippet=4&caseID='}{$caseID}"{literal};

    dataUrl = dataUrl + '&cid={/literal}{$contactID}{literal}';

    cj("#activities-selector").flexigrid
    (
        {
            url: dataUrl,
            dataType: 'json',
            colModel : [

            {display: 'Date',    name : 'display_date', width : 124,  sortable : true, align: 'left'},
            {display: 'Subject', name : 'subject',     width : 105, sortable : true, align: 'left'},
            {display: 'Type',    name : 'type',        width : 100,  sortable : true, align: 'left'},
            {display: 'Reporter / Assignee',name : 'reporter',    width : 100,  sortable : true, align: 'left'},
            {display: 'Status',  name : 'status',      width : 65,  sortable : true, align: 'left'},
            {display: '',        name : 'links',       width : 70,  align: 'left'},
            {name : 'class', hide: true, width: 1}  // this col is use for applying css classes
            ],
            usepager: true,
            useRp: true,
            rp: 40,
            showToggleBtn: false,
            width: 680,
            height: 'auto',
            nowrap: false,
            onSuccess:setSelectorClass
        }
    );	
}
);

function search(com)
{   
    var activity_date_low  = cj("#activity_date_low").val();
    var activity_date_high = cj("#activity_date_high").val();

    var activity_deleted = 0;
    if ( cj("#activity_deleted:checked").val() == 1 ) {
        activity_deleted = 1;
    }
    cj('#activities-selector').flexOptions({
	    newp:1, 
		params:[{name:'reporter_id', value: cj("select#reporter_id").val()},
			{name:'status_id', value: cj("select#status_id").val()},
			{name:'activity_type_id', value: cj("select#activity_type_filter_id").val()},
			{name:'activity_date_low', value: activity_date_low},
			{name:'activity_date_high', value: activity_date_high},
			{name:'activity_deleted', value: activity_deleted }
			]
		});
    
    cj("#activities-selector").flexReload(); 
}

function checkSelection( field ) {
    var validationMessage = '';
    var validationField   = '';
    var successAction     = '';

    var fName = field.name;

    switch ( fName )  {
        case '_qf_CaseView_next' :
            validationMessage = 'Please select an activity set from the list.';
            validationField   = 'timeline_id';
            successAction     = "confirm('{/literal}{ts}Are you sure you want to add a set of scheduled activities to this case{/ts}{literal}?');";
            break;

        case 'new_activity' :
            validationMessage = 'Please select an activity type from the list.';
            validationField   = 'activity_type_id';
            successAction     = "window.location='{/literal}{$newActivityUrl}{literal}' + document.getElementById('activity_type_id').value";
            break;

        case 'case_report' :
            validationMessage = 'Please select a report from the list.';
            validationField   = 'report_id';
            successAction     = "window.location='{/literal}{$reportUrl}{literal}' + document.getElementById('report_id').value";
            break;
    }	

    if ( document.getElementById( validationField ).value == '' ) {
        alert( validationMessage );
        return false;
    } else {
        return eval( successAction );
    }
}


function setSelectorClass( ) {
    cj("#activities-selector td:last-child").each( function( ) {
       cj(this).parent().attr( 'class', cj(this).text() );
    });
}
</script>
{/literal}

{literal}
<script type="text/javascript">
    hide('activities_show');
</script>
{/literal}

{$form.buttons.html}
</div>
