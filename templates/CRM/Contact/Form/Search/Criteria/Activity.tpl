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
<div id="activity" class="form-item">
    <table class="form-layout">
        <tr>
            <td>
                {$form.activity_type_id.label}<br />
                {$form.activity_type_id.html|crmReplace:class:big}
            </td>
            <td >
                {$form.activity_date_low.label|replace:'-':'<br />'}<br/>
				{include file="CRM/common/jcalendar.tpl" elementName=activity_date_low} 
			</td>
			<td><br />
				{$form.activity_date_high.label}<br />
				{include file="CRM/common/jcalendar.tpl" elementName=activity_date_high}
            </td>
        </tr>
        <tr>
            <td>
		        {$form.activity_role.label}&nbsp;(<a href="#" title="unselect" onclick="unselectRadio('activity_role', 'Advanced'); return false;" >unselect</a>)<br />
                {$form.activity_role.html}
            </td>
            <td colspan="2"><br /><br />
				{$form.activity_target_name.html}<br />
                <span class="description font-italic">{ts}Complete OR partial Contact Name.{/ts}</span><br /><br />
				{$form.activity_test.label} &nbsp; {$form.activity_test.html} 
            </td>
        </tr>
        <tr>
             <td>
                {$form.activity_subject.label}<br />
                {$form.activity_subject.html|crmReplace:class:big} 
             </td>
	         <td colspan="2">
                {$form.activity_status.label}<br />
                {$form.activity_status.html} 
             </td>
        </tr>
        {if $activityGroupTree}
        <tr>
	         <td colspan="3">
	          {include file="CRM/Custom/Form/Search.tpl" groupTree=$activityGroupTree showHideLinks=false}
             </td>
        </tr>
        {/if}
    </table>
</div>