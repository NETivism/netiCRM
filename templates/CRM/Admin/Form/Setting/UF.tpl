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
<div id="help">
    {ts}These settings define the CMS variables that are used with CiviCRM.{/ts}
</div>
<div class="form-item">
<fieldset><legend>{ts}CMS Settings{/ts}</legend>

        <dl>
            <dt>{$form.userFrameworkVersion.label}</dt><dd>{$form.userFrameworkVersion.html}</dd>
            <dt>{$form.userFrameworkUsersTableName.label}</dt><dd>{$form.userFrameworkUsersTableName.html}</dd>
        </dl>
        <dl>
            <dt></dt><dd>{$form.buttons.html}</dd>
        </dl>
<div class="spacer"></div>
</fieldset>
</div>

{if $tablePrefixes}
<div class="form-item">
<fieldset>
    <legend>{ts}Views integration settings{/ts}</legend>
    <div>{ts}To enable CiviCRM Views integration, add the following to the site <code>settings.php</code> file:{/ts}</div>
    <pre>{$tablePrefixes}</pre>
</fieldset>
</div>
{/if}
