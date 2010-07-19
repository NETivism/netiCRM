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
	<table class="form-layout">
		<tr>
            <td class="font-size12pt">{$form.sort_name.label} {help id='id-advanced-intro'}</td>
            <td>{$form.sort_name.html}
                <div class="description font-italic">
                    {ts}Complete OR partial Contact Name.{/ts}
                </div>
                {$form.email.html}
                <div class="description font-italic">
                    {ts}Complete OR partial Email Address.{/ts}
                </div>
            </td>
            <td>
                {$form.uf_group_id.label} {$form.uf_group_id.html}
                <br /><br />
                <div class="form-item">
                    {if $form.uf_user}{$form.uf_user.label} {$form.uf_user.html}
                    &nbsp; <a href="#" title="unselect" onclick="unselectRadio('uf_user', 'Advanced'); return false;" >unselect</a>

                    <div class="description font-italic">
                        {ts 1=$config->userFramework}Does the contact have a %1 Account?{/ts}
                    </div>
                    {/if}
                </div>
            </td>
            <td class="label">{$form.buttons.html}</td>       
        </tr>
		<tr>
{if $form.contact_type}
            <td><label>{ts}Contact Type(s){/ts}</label><br />
                {$form.contact_type.html}
            </td>
{else}
            <td>&nbsp;</td>
{/if}
{if $form.group}
            {* Choose regular or 'tall' listing-box class for Group select box based on # of groups. *}
            {if $form.group|@count GT 8}
                {assign var="boxClass" value="listing-box-tall"}
            {else}
                {assign var="boxClass" value="listing-box"}
            {/if}
            <td><label>{ts}Group(s){/ts}</label>
                <div class="{$boxClass}">
                    {foreach from=$form.group item="group_val"}
                    <div class="{cycle values="even-row,odd-row"}">
                    {$group_val.html}
                    </div>
                    {/foreach}
                </div>
            </td>
{else}
            <td>&nbsp;</td>
{/if}

{if $form.tag}
            <td colspan="2"><label>{ts}Tag(s){/ts}</label>
                <div id="Tag" class="listing-box">
                    {foreach from=$form.tag item="tag_val"} 
                      <div class="{cycle values="odd-row,even-row"}">
                      {$tag_val.html}
                      </div>
                    {/foreach}
                </div>
            </td>
{else}
            <td colspan="2">&nbsp;</td>
{/if}
	    </tr>
        <tr>
            <td colspan="2">
                {$form.privacy.label}<br />
                {$form.privacy.html} {help id="id-privacy"}
            </td>
            <td colspan="2">
                {$form.preferred_communication_method.label}<br />
                {$form.preferred_communication_method.html}
            </td>
        </tr>
        <tr>
            <td>{$form.contact_source.label}</td>
            <td colspan="3">{$form.contact_source.html}</td>
        </tr>
    </table>
