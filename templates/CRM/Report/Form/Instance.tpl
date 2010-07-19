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
<table class="report-layout">
    <tr>
	<th>{ts}General Settings{/ts}</th>
    </tr>
</table>
<table class="report-layout">
    <tr>
        <td class="report-label" width="20%">{$form.title.label} {help id="id-report_title" file="CRM/Report/Form/Settings.hlp"}</td>
        <td >{$form.title.html}</td>
    </tr>
    <tr>
        <td class="report-label" width="20%">{$form.description.label}</td>
        <td>{$form.description.html}</td>
    </tr>
    <tr>
        <td class="report-label" width="20%">{$form.report_header.label}{help id="id-report_header" file="CRM/Report/Form/Settings.hlp"}</td>
        <td>{$form.report_header.html}</td>
    </tr>
    <tr>
        <td class="report-label" width="20%">{$form.report_footer.label}</td>
        <td>{$form.report_footer.html}</td>
    </tr>
</table>
<br/>

<table class="report-layout">
    <tr>
	<th>{ts}Email Delivery Settings{/ts} {help id="id-email_settings" file="CRM/Report/Form/Settings.hlp"}</th>
    </tr>
</table>
<table class="report-layout">
    <tr>
        <td class="report-label" width="20%">{$form.email_subject.label}</td>
        <td>{$form.email_subject.html|crmReplace:class:huge}</td>
    </tr>
    <tr>
        <td class="report-label">{$form.email_to.label}</td>
        <td>{$form.email_to.html|crmReplace:class:huge}</td>
    </tr>
    <tr>
        <td class="report-label">{$form.email_cc.label}</td>
        <td>{$form.email_cc.html|crmReplace:class:huge}</td>
    </tr> 
</table>
<br/>

<table class="report-layout">
    <tr>
	<th>{ts}Other Settings{/ts}</th>
    </tr>
</table>
<table class="report-layout">
    <tr>
	<td class="report-label">{$form.is_navigation.label}</td>
        <td>{$form.is_navigation.html}<br />
            <span class="description">{ts}All report instances are automatically included in the Report Listing page. Check this box to also add this report to the navigation menu.{/ts}</span>
        </td>
    </tr>
    <tr id="navigation_menu">
	<td class="report-label">{$form.parent_id.label} {help id="id-parent" file="CRM/Admin/Form/Navigation.hlp"}</td>
	<td>{$form.parent_id.html|crmReplace:class:huge}</td>
    </tr>
    {if $config->userFramework neq 'Joomla'}
        <tr>
            <td class="report-label" width="20%">{$form.permission.label} {help id="id-report_perms" file="CRM/Report/Form/Settings.hlp"}</td>
            <td>{$form.permission.html|crmReplace:class:huge}</td>
        </tr>
    {/if}
    <tr>
	    <td class="report-label">{$form.addToDashboard.label} {help id="id-dash_avail" file="CRM/Report/Form/Settings.hlp"}</td>
        <td>{$form.addToDashboard.html}
            <span class="description">{ts}Users with appropriate permissions can add this report to their dashboard.{/ts}</span>
        </td>
    </tr>
</table>

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_navigation"
    trigger_value       =""
    target_element_id   ="navigation_menu" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{if $is_navigation}
 <script type="text/javascript">
     document.getElementById('is_navigation').checked = true;
     showHideByValue('is_navigation','','navigation_menu','table-row','radio',false);
 </script>
{/if}
