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
{* Search criteria form elements *}
<fieldset>
    <div class="form-item">
    {if $rows}
        {if $context EQ 'smog'}
            <h3>{ts}Find Members within this Group{/ts}</h3>
        {/if}
    {else}
        {if $context EQ 'smog'}
            <h3>{ts}Find Members within this Group{/ts}</h3>
        {elseif $context EQ 'amtg'}
            <h3>{ts}Find Contacts to Add to this Group{/ts}</h3>
        {/if}
    {/if}

{ if $config->groupTree }
{literal}
<script type="text/javascript">
dojo.require("dojo.parser");
dojo.require("dijit.Dialog");
dojo.require("dojo.data.ItemFileWriteStore");
dojo.require("civicrm.CheckboxTree");
dojo.require("dijit.form.CheckBox"); 

function displayGroupTree( ) {
    // do not recreate if tree is already created
    if ( dijit.byId('checkboxtree') ) {
	return;
    }

    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/groupTree' h=0 }"{literal};
    
    {/literal}
    {if $groupIds}
        dataUrl = dataUrl + '?gids=' + '{$groupIds}'
    {/if}
    {literal}

    var treeStore = new dojo.data.ItemFileWriteStore({url:dataUrl});
    
    var treeModel = new civicrm.tree.CheckboxTreeStoreModel({
	    store: treeStore,
	    query: {type:'rootGroup'},
	    rootId: 'allGroups',
	    rootLabel: 'All Groups',
	    childrenAttrs: ["children"]
	});
    var tree = new civicrm.CheckboxTree({
	    id : "checkboxtree",
	    model: treeModel,
        showRoot: false
	});
    
    var dd = dijit.byId('id-groupPicker');

    var button1 = new dijit.form.Button({label: "Done", type: "submit"});                                                                   
    dd.containerNode.appendChild(button1.domNode);      
    
    dd.containerNode.appendChild(tree.domNode);

    var button2 = new dijit.form.Button({label: "Done", type: "submit"});                                                                   
    dd.containerNode.appendChild(button2.domNode);      

    tree.startup();

};

function getCheckedNodes( ) 
{
    var treeStore = dijit.byId("checkboxtree").model.store ;
    treeStore.fetch({query: {checked:true},queryOptions: {deep:true}, onComplete: setCheckBoxValues});
};         

function setCheckBox( ) 
{
    var groupNames = {/literal}"{$groupNames}"{literal};
    if ( groupNames ) {
	var grp  = document.getElementById('id-group-names');
	grp.innerHTML = groupNames;
    }
};

function setCheckBoxValues(items,request) 
{
    var groupLabel = "" ;
    var groupIds   = "";

    var myTreeStore = dijit.byId("checkboxtree").model.store;

    for (var i = 0; i < items.length; i++){
	var item = items[i];
	groupLabel = groupLabel + myTreeStore.getLabel(item) + "<BR/>" ;
	if ( groupIds != '' ) {
	    groupIds = groupIds + ',';
	}
	groupIds = groupIds + item['id'];
    }

    var grp  = document.getElementById('id-group-names');    
    grp.innerHTML = groupLabel;
    
    var groupId   = document.getElementById('group');
    groupId.value = groupIds;
};                     


dojo.addOnLoad( function( ) {
     setCheckBox( );
});
</script>
{/literal}
{/if}

    {strip}
	<table class="{$form.sort_name.name}">
        <tr>
            <td class="label">{$form.sort_name.label} {$form.sort_name.html}</td>
{if $form.contact_type}
            <td class="label">{$form.contact_type.label} {$form.contact_type.html}</td>
{/if}
{if $form.group}
            <td class="label">
                {if $context EQ 'smog'}
                    {$form.group_contact_status.label}<br />
                {else}
                    {ts}in{/ts} &nbsp;
                {/if}
                {if $context EQ 'smog'}
                    {$form.group_contact_status.html}
                {else}
                    { if $config->groupTree }
                        <a href="#" onclick="dijit.byId('id-groupPicker').show(); displayGroupTree( );">{ts}Select Group(s){/ts}</a>
                        <div class="tundra" style="background-color: #f4eeee;" dojoType="dijit.Dialog" id="id-groupPicker" title="Select Group(s)" execute="getCheckedNodes();">
                        </div><br />
                        <span id="id-group-names"></span>
                    {else}
                        {$form.group.html|crmReplace:class:big}
                    {/if}
                {/if}
            </td>
{/if}
{if $form.tag}
            <td class="label">{$form.tag.label} {$form.tag.html|crmReplace:class:medium}</td>
{/if}
            <td style="vertical-align: bottom;">
                {$form.buttons.html}
            </td>
        </tr>
    </table>
    {/strip}
    </div>
</fieldset>