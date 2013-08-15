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
<tr> 
    <td><label>{ts}Membership Type(s){/ts}</label><br />
                   {$form.member_membership_type_id.html}
    </td>
    <td><label>{ts}Membership Status{/ts}</label><br />
                   {$form.member_status_id.html}
    </td>
</tr>

<tr>
    <td>
     {$form.member_source.label}
     <br />{$form.member_source.html}
    </td>
    <td>
     {$form.member_is_primary.html} {help id="id-member_is_primary" file="CRM/Member/Form/Search.hlp"}<br />
     {$form.member_pay_later.html}&nbsp;{$form.member_pay_later.label}<br />
     {$form.member_test.html}&nbsp;{$form.member_test.label}
    </td> 
</tr>
<tr> 
    <td> 
     {$form.member_join_date_low.label} 
     <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_join_date_low}
    </td>
    <td> 
     {$form.member_join_date_high.label} <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_join_date_high}
    </td> 
</tr> 
<tr> 
    <td> 
     {$form.member_start_date_low.label} 
     <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_start_date_low}
    </td>
    <td>
     {$form.member_start_date_high.label}
     <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_start_date_high}
    </td> 
</tr> 
<tr> 
    <td>  
     {$form.member_end_date_low.label} 
     <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_end_date_low}
    </td>
    <td> 
     {$form.member_end_date_high.label}
     <br />
     {include file="CRM/common/jcalendar.tpl" elementName=member_end_date_high}
    </td> 
</tr> 
{if $membershipGroupTree}
<tr>
    <td colspan="4">
    {include file="CRM/Custom/Form/Search.tpl" groupTree=$membershipGroupTree showHideLinks=false}
    </td>
</tr>
{/if}

{include file="CRM/common/chosen.tpl" selector='select[name^=member_membership_type_id], select[name^=member_status_id]'}
