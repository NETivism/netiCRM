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
<div class="crm-block crm-form-block crm-csp-form-block">
<div id="help">
    {ts}Default values will be supplied for these Content Security Policy the first time you access CiviCRM - based on the CIVICRM_TEMPLATE_COMPILEDIR specified in civicrm.settings.php. If you need to modify the defaults, make sure that your web server has write access to the directories.{/ts}
</div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
           <table class="form-layout">
            <tr class="crm-csp-form-block-CSP">
                <td class="label">{$form.customCSP.label}</td>
                <td>{$form.customCSP.html|crmReplace:class:'huge40'}<br />
                    <span class="description">{ts}CiviCRM Content Security Policy.{/ts}
                </td>
            </tr>
            <tr class="crm-csp-form-block-excludeCSP">
                <td class="label">{$form.customCSPExcludePath.label}</td>
                <td>{$form.customCSPExcludePath.html|crmReplace:class:'huge40'}<br />
                    <span class="description">{ts}Specify pages by using their paths. Enter one path per line.{/ts}
                </td>
            </tr>
        </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
