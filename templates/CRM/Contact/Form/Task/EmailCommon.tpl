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
{*common template for compose mail*}
<table class="form-layout-compressed">
    <tr>
	    <td class="label">{$form.template.label}</td>
	    <td>{$form.template.html}</td>
    </tr>
</table>
<div class="accordion ui-accordion ui-widget ui-helper-reset">
    <span class="helpIcon" id="helphtml">
	<a href="#" onClick="return showToken('Html', 2);" style="float:left;">{$form.token2.label}</a>
	{help id="id-token-html" file="CRM/Contact/Form/Task/Email.hlp"}
	<div id='tokenHtml' style="display:none">
	    <input style="border:1px solid #999999;" type="text" id="filter2" size="20" name="filter2" onkeyup="filter(this, 2)"/><br />
	    <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
	    {$form.token2.html}
	</div>
    </span>
    <div class="messageHelp">{help id="id-message-text" file="CRM/Contact/Form/Task/Email.hlp"}</div>
    <h3 class="head"> 
	<span class="ui-icon ui-icon-triangle-1-e" id='html'></span><a href="#">{ts}HTML Format{/ts}</a>
    </h3>
    <div class='html'>
	{if $editor EQ 'textarea'}
	    <div class="help description">{ts}NOTE: If you are composing HTML-formatted messages, you may want to enable a Rich Text (WYSIWYG) editor (Administer &raquo; Configure &raquo; Global Settings &raquo; Site Preferences).{/ts}</div>
	{/if}
	{$form.html_message.html}<br />
    </div>
    <span class="helpIcon" id="helptext" style="display:none;">
	<a href="#" onClick="return showToken('Text', 1);" style="float:left;">{$form.token1.label}</a>
	{help id="id-token-text" file="CRM/Contact/Form/Task/Email.hlp"}
	<div id='tokenText' style="display:none">
	    <input  style="border:1px solid #999999;" type="text" id="filter1" size="20" name="filter1" onkeyup="filter(this, 1)"/><br />
	    <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
	    {$form.token1.html}
	</div>
    </span>
    <h3 class="head"><span class="ui-icon ui-icon-triangle-1-e" id='text'></span><a href="#">{ts}Plain-Text Format{/ts}</a></h3>
    <div class='text'>
	{$form.text_message.html}<br />
    </div>
</div>
<div class="spacer" style="height:1em;"></div>
<div id="editMessageDetails" class="section">
    <div id="updateDetails" class="section" >
	{$form.updateTemplate.html}&nbsp;{$form.updateTemplate.label}
    </div>
    <div class="section">
	{$form.saveTemplate.html}&nbsp;{$form.saveTemplate.label}
    </div>
</div>

<div id="saveDetails" class="section">
   <div class="label">{$form.saveTemplateName.label}</div>
   <div class="content">{$form.saveTemplateName.html|crmReplace:class:huge}</div>
</div>

{if ! $noAttach}
    {include file="CRM/Form/attachment.tpl"}
{/if}

{include file="CRM/Mailing/Form/InsertTokens.tpl"}
