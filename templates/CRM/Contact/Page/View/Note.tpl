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
<div class="view-content">
{if $action eq 4}{* when action is view  *}
    {if $notes}
        <fieldset>
          <legend>{ts}View Note{/ts}</legend>
          <table class="view-layout">
            <tr><td class="label">{ts}Subject{/ts}</td><td>{$note.subject}</td></tr>
            <tr><td class="label">{ts}Date:{/ts}</td><td>{$note.modified_date|crmDate}</td></tr>
            <tr><td class="label"></td><td>{$note.note|nl2br}</td></tr>
            <tr><td></td><td><input type="button" name='cancel' value="{ts}Done{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=note'}';"/></td></tr>        
          </table>
        </fieldset>
        {/if}
{elseif $action eq 1 or $action eq 2} {* action is add or update *}
    <p></p>
    <fieldset><legend>{if $action eq 1}{ts}New Note{/ts}{else}{ts}Edit Note{/ts}{/if}</legend>
    <div class="form-item">
        {$form.subject.label} {$form.subject.html} 
        <br/><br/>
        <label for="note">{$form.note.html}</label>
        <br/>
        {$form.buttons.html}
    </div>
    </fieldset>
    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}
{/if}
{if ($action eq 8)}
<fieldset><legend>{ts}Delete Note{/ts}</legend>
<div class=status>{ts 1=$notes.$id.note}Are you sure you want to delete the note '%1'?{/ts}</div>
<dl><dt></dt><dd>{$form.buttons.html}</dd></dl>
</fieldset>

{/if}

{if $permission EQ 'edit' AND ($action eq 16 or $action eq 4 or $action eq 8)}
   <div class="action-link">
	 <a accesskey="N" href="{crmURL p='civicrm/contact/view/note' q="cid=`$contactId`&action=add"}" class="button"><span>&raquo; {ts}New Note{/ts}</span></a>
   </div>
   <div class="clear"></div>
{/if}

{if $notes}
    {* show browse table for any action *}
<div id="notes">
    {strip}
    {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
        <thead>
        <tr>
	        <th>{ts}Note{/ts}</th>
	        <th>{ts}Subject{/ts}</th>
	        <th>{ts}Date{/ts}</th>
	        <th>{ts}Created By{/ts}</th>
	        <th></th>
        </tr>
        </thead>
        {foreach from=$notes item=note}
        <tr id="cnote_{$note.id}" class="{cycle values="odd-row,even-row"}">
            <td>
                {$note.note|nl2br|mb_truncate:80:"...":true}
                {* Include '(more)' link to view entire note if it has been truncated *}
                {assign var="noteSize" value=$note.note|count_characters:true}
                {if $noteSize GT 80}
		        <a href="{crmURL p='civicrm/contact/view/note' q="action=view&selectedChild=note&reset=1&cid=`$contactId`&id=`$note.id`"}">{ts}(more){/ts}</a>
                {/if}
            </td>
            <td>{$note.subject}</td>
            <td>{$note.modified_date|crmDate}</td>
            <td>
                <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$note.contact_id`"}">{$note.createdBy}</a>
            </td>
            <td class="nowrap">{$note.action}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}
 </div>

{elseif ! ($action eq 1)}
   <div class="messages status">
    <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
        {capture assign=crmURL}{crmURL p='civicrm/contact/view/note' q="cid=`$contactId`&action=add"}{/capture}
        <dd>{ts 1=$crmURL}There are no Notes for this contact. You can <a accesskey="N" href='%1'>add one</a>.{/ts}</dd>
    </dl>
   </div>
{/if}
</div>
