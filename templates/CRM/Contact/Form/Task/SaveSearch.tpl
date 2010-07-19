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
<div class='spacer'></div>
<fieldset>
    <legend>{ts}Smart Group{/ts}</legend>
    {if $qill[0]}
        <div id="search-status">
            <ul>
                {foreach from=$qill[0] item=criteria}
                    <li>{$criteria}</li>
                {/foreach}
            </ul>
            <br />
        </div>
    {/if}
    <div class="form-item">
        <dl class="html-adjust">
            <dt>{$form.title.label}</dt><dd>{$form.title.html}</dd>
            <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
            {if $form.group_type}
                <dt>{$form.group_type.label}</dt><dd>{$form.group_type.html}</dd>
            {/if}
            <dt></dt><dd>{$form.buttons.html}</dd>
        </dl>
    </div>
</fieldset>
