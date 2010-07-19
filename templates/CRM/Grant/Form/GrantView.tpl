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
{* this template is used for viewing grants *} 
<fieldset>
    <legend>{ts}View Grant{/ts}</legend>
    <table class="view-layout">
        <tr><td class="label">{ts}Name{/ts}</td><td class="bold">{$displayName}</td></tr>    
        <tr><td class="label">{ts}Grant Status{/ts}          </td> <td>{$grantStatus}</td></tr>
        <tr><td class="label">{ts}Grant Type{/ts}            </td> <td>{$grantType}</td></tr>
        <tr><td class="label">{ts}Application Received{/ts}  </td> <td>{$application_received_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Grant Decision{/ts}        </td> <td>{$decision_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Money Transferred{/ts}     </td> <td>{$money_transfer_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Grant Report Due{/ts}      </td> <td>{$grant_due_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Amount Requested{/ts}      </td> <td>{$amount_total|crmMoney}</td></tr>
        <tr><td class="label">{ts}Amount Requested{/ts}<br />
                              {ts}(original currency){/ts}   </td> <td>{$amount_requested|crmMoney}</td></tr>
        <tr><td class="label">{ts}Amount Granted{/ts}        </td> <td>{$amount_granted|crmMoney}</td></tr>
        <tr><td class="label">{ts}Grant Report Received?{/ts}</td> <td>{if $grant_report_received}{ts}Yes{/ts} {else}{ts}No{/ts}{/if}</td></tr>
        <tr><td class="label">{ts}Rationale{/ts}             </td> <td>{$rationale}</td></tr>
        <tr><td class="label">{ts}Notes{/ts}                 </td> <td>{$note}</td></tr>
        {if $attachment}
            <tr><td class="label">{ts}Attachment(s){/ts}</td><td>{$attachment}</td></tr>
        {/if}
    </table>
	<div class="spacer"></div>
    {include file="CRM/Custom/Page/CustomDataView.tpl"} 
    <div class="spacer"></div>  
    <table class="form-layout buttons">
        <tr>
    	    <td>&nbsp;</td>
            <td>
                {$form.buttons.html}
                {if call_user_func(array('CRM_Core_Permission','check'), 'edit grants')}
                    &nbsp;|&nbsp;<a href="{crmURL p='civicrm/contact/view/grant' q="reset=1&id=$id&cid=$contactId&action=update&context=grant"}" accesskey="e">Edit</a>
                {/if}
                {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviGrant')}
                    &nbsp;|&nbsp;<a href="{crmURL p='civicrm/contact/view/grant' q="reset=1&id=$id&cid=$contactId&action=delete&context=grant"}">Delete</a>
                {/if}
            </td>
        </tr>    
    </table>
</fieldset>
    
