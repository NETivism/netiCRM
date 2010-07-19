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
{literal}
<script type="text/javascript">
function setIntermediate( ) {
	var dataUrl = "{/literal}{$statusUrl}{literal}";
	cj.getJSON( dataUrl, function( response ) {
	   var dataStr = response.toString();
	   var result  = dataStr.split(",");
	   cj("#intermediate").html( result[1] );
	   cj("#importProgressBar").progressBar( result[0] );
	});
}

function pollLoop( ){
	setIntermediate( );
	window.setTimeout( pollLoop, 10*1000 ); // 10 sec
}

function verify( ) {
    if (! confirm('{/literal}{ts}Are you sure you want to Import now{/ts}{literal}?') ) {
        return false;
    }
	
	cj("#id-processing").show( ).dialog({
		modal         : true,
		width         : 350,
		height        : 160,
		resizable     : false,
		bgiframe      : true,
		draggable     : true,
		closeOnEscape : false,
		overlay       : { opacity: 0.5, background: "black" },
		open          : function ( ) {
		    cj("#id-processing").dialog().parents(".ui-dialog").find(".ui-dialog-titlebar").remove();
		}
	});
	
	var imageBase = "{/literal}{$config->resourceBase}{literal}packages/jquery/plugins/images/";
    cj("#importProgressBar").progressBar({
        boxImage:       imageBase + 'progressbar.gif',
        barImage: { 0 : imageBase + 'progressbg_red.gif',
                    20: imageBase + 'progressbg_orange.gif',
                    50: imageBase + 'progressbg_yellow.gif',
                    70: imageBase + 'progressbg_green.gif'
                  }
	}); 
	cj("#importProgressBar").show( );
	pollLoop( );
}
</script>
{/literal}

{* Import Wizard - Step 3 (preview import results prior to actual data loading) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
 
 <div id="help">
    <p>
    {ts}The information below previews the results of importing your data in CiviCRM. Review the totals to ensure that they represent your expected results.{/ts}         
    </p>
    
    {if $invalidRowCount}
        <p class="error">
        {ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl}CiviCRM has detected invalid data or formatting errors in %1 records. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Errors</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}

    {if $conflictRowCount}
        <p class="error">
        {ts 1=$conflictRowCount 2=$downloadConflictRecordsUrl}CiviCRM has detected %1 records with conflicting email addresses within this data file. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Conflicts</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}
    
    <p>{ts}Click 'Import Now' if you are ready to proceed.{/ts}</p>
 </div>

{* Import Progress Bar and Info *}
<div id="id-processing" class="hiddenElement">
	<h3>Importing records...</h3><br />
	<div class="progressBar" id="importProgressBar" style="margin-left:45px;display:none;"></div>
	<div id="intermediate"></div>
	<div id="error_status"></div>
</div>

<div id="preview-info">
 {* Summary Preview (record counts) *}
 <table id="preview-counts" class="report">
    <tr><td class="label">{ts}Total Rows{/ts}</td>
        <td class="data">{$totalRowCount}</td>
        <td class="explanation">{ts}Total number of rows in the imported data.{/ts}</td>
    </tr>
    
    {if $invalidRowCount}
    <tr class="error"><td class="label">{ts}Rows with Errors{/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields (for example, invalid email address formatting). These rows will be skipped (not imported).{/ts}
            {if $invalidRowCount}
                <div class="action-link"><a href="{$downloadErrorRecordsUrl}">&raquo; {ts}Download Errors{/ts}</a></div>
            {/if}
        </td>
    </tr>
    {/if}
    
    {if $conflictRowCount}
    <tr class="error"><td class="label">{ts}Conflicting Rows{/ts}</td>
        <td class="data">{$conflictRowCount}</td>
        <td class="explanation">{ts}Rows with conflicting email addresses within this file. These rows will be skipped (not imported).{/ts}
            {if $conflictRowCount}
                <div class="action-link"><a href="{$downloadConflictRecordsUrl}">&raquo; {ts}Download Conflicts{/ts}</a></div>
            {/if}
        </td>
    </tr>
    {/if}

    <tr>
		<td class="label">{ts}Valid Rows{/ts}</td>
        <td class="data">{$validRowCount}</td>
        <td class="explanation">{ts}Total rows to be imported.{/ts}</td>
    </tr>
 </table>

 {* Table for mapping preview *}
 {include file="CRM/Import/Form/MapTable.tpl"}
 
 {* Group options *}
 {* New Group *}
    <div id="newGroup_show" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('newGroup_show'); show('newGroup'); return false;">&raquo; <label>{ts}Add imported records to a new group{/ts}</label>{*$form.newGroup.label*}</a>
    </div>

    <div id="newGroup" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('newGroup'); show('newGroup_show'); return false;">&raquo; <label>{ts}Add imported records to a new group{/ts}</label></a>
        <div class="form-item">
            <dl>
            <dt class="description">{$form.newGroupName.label}</dt><dd>{$form.newGroupName.html}</dd>
            <dt class="description">{$form.newGroupDesc.label}</dt><dd>{$form.newGroupDesc.html}</dd>
            </dl>
        </div>
    </div>
      {* Existing Group *}
    {if $form.groups}
    <div id="existingGroup_show" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('existingGroup_show'); show('existingGroup'); return false;">&raquo; {$form.groups.label}</a>
    </div>
    {/if}

    <div id="existingGroup" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('existingGroup'); show('existingGroup_show'); return false;">&raquo; {$form.groups.label}</a>
        <div class="form-item">
            <dl>
            <dt></dt><dd>{$form.groups.html}</dd>
            </dl>
        </div>
    </div>

    {* Tag options *}
    {* New Tag *}
    <div id="newTag_show" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('newTag_show'); show('newTag'); return false;">&raquo; <label>{ts}Create a new tag and assign it to imported records{/ts}</label></a>
    </div> 
    <div id="newTag" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('newTag'); show('newTag_show'); return false;">&raquo; <label>{ts}Create a new tag and assign it to imported records{/ts}</label></a>
            <div class="form-item">
				<dl>
				<dt class="description">{$form.newTagName.label}</dt><dd>{$form.newTagName.html}</dd>
				<dt class="description">{$form.newTagDesc.label}</dt><dd>{$form.newTagDesc.html}</dd>
            </dl>
        </div>
    </div>
    {* Existing Tag Imported Contact *}

    <div id="tag_show" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('tag_show'); show('tag'); return false;">&raquo; <label>{ts}Tag imported records{/ts}</label></a>
    </div>

    <div id="tag" class="section-hidden section-hidden-border">
        <a href="#" onclick="hide('tag'); show('tag_show'); return false;">&raquo; <label>{ts}Tag imported records{/ts}</label></a>
        <dl>
            <dt></dt>
			<dd class="listing-box" style="margin-bottom: 0em; width: 15em;">
				{foreach from=$form.tag item="tag_val"} 
					<div>{$tag_val.html}</div>
				{/foreach}
            </dd>
        </dl>
    </div>
</div> {* End of preview-info div. We hide this on form submit. *}

<div id="crm-submit-buttons">
   {$form.buttons.html}
</div>
<script type="text/javascript">
hide('newGroup');
hide('existingGroup');
hide('newTag');
hide('tag');
</script>
