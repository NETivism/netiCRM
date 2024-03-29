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
<h3>{$activityTypeName}</h3>
<div class="crm-block crm-content-block crm-activity-view-block">
      {if $activityTypeDescription}
        <div id="help">{$activityTypeDescription}</div>
      {/if}
      <table class="crm-info-panel">
        {if $values.is_test}
        <tr>
            <td class="label">{ts}Is Test{/ts}</td><td class="view-value">{ts}Yes{/ts}</td>
        </tr>
        {/if}
        {if $values.status}
        <tr>
            <td class="label">{ts}Activity Status{/ts}</td><td class="view-value">{$values.status}</td>
        </tr>
        {/if}
        <tr>
            <td class="label">{ts}Added By{/ts}</td><td class="view-value">{$values.source_contact}</td>
        </tr>
       {if $values.target_contact_value} 
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value">{$values.target_contact_value}</td>
           </tr>
       {/if}
       {if $values.mailingId}
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value"><a href="{$values.mailingId}" title="{ts}View Mailing Report{/ts}">&raquo;{ts}Mailing Report{/ts}</a></td>
           </tr>
       {/if} 
        <tr>
            <td class="label">{ts}Subject{/ts}</td><td class="view-value">{$values.subject}</td>
        </tr>  
        <tr>
            <td class="label">{ts}Date and Time{/ts}</td><td class="view-value">{$values.activity_date_time|crmDate }</td>
        </tr> 
        {if $values.mailingId}
            <tr>
                <td class="label">{ts}Details{/ts}</td>
                <td class="view-value report">
                    
                    <fieldset>
                    <legend>{ts}Content / Components{/ts}</legend>
                    {strip}
                    <table class="form-layout-compressed">
                      {if $mailingReport.mailing.body_text}
                          <tr>
                              <td class="label nowrap">{ts}Text Message{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.body_text|mb_truncate:30|escape|nl2br}
                                  <br />
                                  <strong><a href='{$textViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
                              </td>
                          </tr>
                      {/if}

                      {if $mailingReport.mailing.body_html}
                          <tr>
                              <td class="label nowrap">{ts}HTML Message{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.body_html|mb_truncate:30|escape|nl2br}
                                  <br/>                         
                                  <strong><a href='{$htmlViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
                              </td>
                          </tr>
                      {/if}

                      {if $mailingReport.mailing.attachment}
                          <tr>
                              <td class="label nowrap">{ts}Attachments{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.attachment}
                              </td>
                              </tr>
                      {/if}
                      
                    </table>
                    {/strip}
                    </fieldset>
                </td>
            </tr>  
        {else}
             <tr>
                 <td class="label">{ts}Details{/ts}</td><td class="view-value report">{$values.details|crmStripAlternatives|nl2br}</td>
             </tr>
        {/if}  
{if $values.attachment}
        <tr>
            <td class="label">{ts}Attachment(s){/ts}</td><td class="view-value report">{$values.attachment}</td>
        </tr>  
{/if}
     </table>
      {if $mailing_events}
      <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
        <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>
          {ts}Email Tracking{/ts}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr>
              <th class="twelve">{ts}Action{/ts}</th>
              <th class="twelve">{ts}Date{/ts}</th>
              <th>{ts}Details{/ts}</th>
            </tr>
            {foreach from=$mailing_events item=mailing_event_row}
              <tr>
                <td>{$mailing_event_row.action}</td>
                <td>{$mailing_event_row.time|crmDate}</td>
                <td>
                  {if $mailing_event_row.detail}
                    {$mailing_event_row.detail|nl2br}
                  {else}
                    {ts}None{/ts}
                  {/if}
                </td>
              </tr>
            {/foreach}
            <tr>
            </tr>
          </table>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->
      {/if}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>  
 
