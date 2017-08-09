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
{if !$form.is_online_registration.value}
  <div id="help">
  {ts}If you want to provide an Online Registration page for this event, check the first box below and then complete the fields on this form.{/ts} 
  </div>
{/if}
{include file="CRM/Event/Form/ManageEvent/Tab.tpl"}
{assign var=eventID value=$id}
<div class="crm-block crm-form-block crm-event-manage-registration-form-block">
<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="top"}
</div>

    <div id="register">
     <table class="form-layout">
         <tr class="crm-event-manage-registration-form-block-is_online_registration">
            <td class="label">{$form.is_online_registration.label}</td>
            <td>{$form.is_online_registration.html}
            <span class="description">{ts}Enable or disable online registration for this event.{/ts}</span>
            </td>
         </tr>
     </table>
    </div>
    <div class="spacer"></div>
    <div id="registration_blocks">
      <table class="form-layout-compressed">
        <tr class="crm-event-manage-registration-form-block-registration_link_text">
            <td scope="row" class="label" width="20%">{$form.registration_link_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='registration_link_text' id=$eventID}{/if}</td>
            <td>{$form.registration_link_text.html} {help id="id-link_text"}</td>
        </tr>
       {if !$isTemplate}
        <tr class="crm-event-manage-registration-form-block-registration_start_date">  
           <td scope="row" class="label" width="20%">{$form.registration_start_date.label}</td>
           <td>{include file="CRM/common/jcalendar.tpl" elementName=registration_start_date}</td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-registration_end_date">
           <td scope="row" class="label" width="20%">{$form.registration_end_date.label}</td>
           <td>{include file="CRM/common/jcalendar.tpl" elementName=registration_end_date}</td>
        </tr>
       {/if}
        <tr class="crm-event-manage-registration-form-block-requires_approval">
          {if $form.requires_approval}
            <td scope="row" class="label" width="20%">{$form.requires_approval.label}</td>
            <td>{$form.requires_approval.html} <br />
              <span class="description">{ts}Check this box to require administrative approval for all the participants who self-register, prior to being able to complete the registration process. Participants will be placed in 'Awaiting Approval' status. You can review and approve participants from 'Find Participants' - select the 'Change Participant Status' task. Approved participants will move to 'Pending from approval' status, and will be sent an email with a link to complete their registration (including paying event fees - if any). {/ts}</span>
            </td>
          {/if}
        </tr>
        <tr id="id-approval-text" class="crm-event-manage-registration-form-block-approval_req_text">
          {if $form.approval_req_text}
            <td scope="row" class="label" width="20%">{$form.approval_req_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='approval_req_text' id=$eventID}{/if}</td>
            <td>{$form.approval_req_text.html}</td>
          {/if}
        </tr>
        <tr class="crm-event-manage-registration-form-block-expiration_time">
            <td scope="row" class="label" width="20%">{$form.expiration_time.label}</td>
            <td>{$form.expiration_time.html|crmReplace:class:four} {help id="id-expiration_time"}</td>
        </tr>
      </table>

    {*Form Block*}
    <div class="crm-accordion-wrapper crm-accordion-closed" id="">
      <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>{ts}Include Profiles{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
        <tr class='crm-event-manage-registration-form--block-create-new-profile'>
            <td class="label"></td>
            <td><a href="{crmURL p='civicrm/admin/uf/group/add' q='reset=1&action=add'}" target="_blank"><i class="zmdi zmdi-plus-circle"></i> {ts}Add Profile{/ts}</a></td>
        </tr>
         <tr class="crm-event-manage-registration-form-block-custom_pre_id">
            <td scope="row" class="label" width="20%">{$form.custom_pre_id.label}</td>
            <td>{$form.custom_pre_id.html} <span class="profile-links"></span></span><br />
            <span class="description">{ts}Include additional fields on this registration form by configuring and selecting a CiviCRM Profile to be included at the top of the page (immediately after the introductory message).{/ts}{help id="event-profile"}</span></td>
         </tr>
         <tr class="crm-event-manage-registration-form-block-custom_post_id">
            <td scope="row" class="label" width="20%">{$form.custom_post_id.label}</td>
            <td>{$form.custom_post_id.html} <span class="profile-links"></span><br />
            <span class="description">{ts}Include additional fields on this registration form by configuring and selecting a CiviCRM Profile to be included at the bottom of the page.{/ts}</span></td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-is_multiple_registrations">
            <td scope="row" class="label" width="20%">{$form.is_multiple_registrations.label}</td>
            <td>{$form.is_multiple_registrations.html} {help id="id-allow_multiple"}</td>
        </tr>
        <tr id="allow_same_emails" class="crm-event-manage-registration-form-block-allow_same_participant_emails">
            <td scope="row" class="label" width="20%">{$form.allow_same_participant_emails.label}</td>
            <td>{$form.allow_same_participant_emails.html} {help id="id-allow_same_email"}</td>
        </tr>
        <tr id="additional_profile_pre" class="crm-event-manage-registration-form-block-additional_custom_pre_id">
            <td scope="row" class="label" width="20%">{$form.additional_custom_pre_id.label}</td>
            <td>{$form.additional_custom_pre_id.html} <span class="profile-links"></span><br />
              <span class="description">{ts}Change this if you want to use a different profile for additional participants.{/ts}</span></td>
            </td>
        </tr>
        <tr id="additional_profile_post" class="crm-event-manage-registration-form-block-additional_custom_post_id">
             <td scope="row" class="label" width="20%">{$form.additional_custom_post_id.label}</td>
             <td>{$form.additional_custom_post_id.html} <span class="profile-links"></span><br />
                <span class="description">{ts}Change this if you want to use a different profile for additional participants.{/ts}</span>
             </td>
        </tr>
        </table>
      </div>
    </div>  

    {*Registration Block*}
    <div class="crm-accordion-wrapper crm-accordion-closed" id="registration">
      <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>{ts}Registration Screen{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
         <tr class="crm-event-manage-registration-form-block-intro_text">
            <td scope="row" class="label" width="20%">{$form.intro_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='intro_text' id=$eventID}{/if}</td>
            <td>{$form.intro_text.html}
            <div class="description">{ts}Introductory message / instructions for online event registration page (may include HTML formatting tags).{/ts}</div>
            </td>
         </tr>
         <tr class="crm-event-manage-registration-form-block-footer_text">
            <td scope="row" class="label" width="20%">{$form.footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='footer_text' id=$eventID}{/if}</td>
            <td>{$form.footer_text.html}
            <div class="description">{ts}Optional footer text for registration screen.{/ts}</div></td>
         </tr>
        </table>
      </div>
    </div>  

    {*Confirmation Block*}
    <div class="crm-accordion-wrapper crm-accordion-closed" id="confirm_show">
      <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>{ts}Confirmation Screen{/ts}
      </div>
      <div class="crm-accordion-body">
         <table class= "form-layout-compressed">
           <tr class="crm-event-manage-registration-form-block-confirm_title">
              <td scope="row" class="label" width="20%">{$form.confirm_title.label} <span class="marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_title' id=$eventID}{/if}</td>
              <td>{$form.confirm_title.html}<br />
                  <span class="description">{ts}Page title for screen where user reviews and confirms their registration information.{/ts}</span>
              </td>
           </tr>
           <tr class="crm-event-manage-registration-form-block-confirm_text">
              <td scope="row" class="label" width="20%">{$form.confirm_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_text' id=$eventID}{/if}</td>
              <td>{$form.confirm_text.html}
                  <div class="description">{ts}Optional instructions / message for Confirmation screen.{/ts}</div> 
              </td>
           </tr>
           <tr class="crm-event-manage-registration-form-block-confirm_footer_text">
              <td scope="row" class="label" width="20%">{$form.confirm_footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_footer_text' id=$eventID}{/if}</td>
              <td>{$form.confirm_footer_text.html}
                 <div class="description">{ts}Optional page footer text for Confirmation screen.{/ts}</div>
              </td>
           </tr>
         </table>
      </div>
    </div>  

    {*ThankYou Block*}
    <div class="crm-accordion-wrapper crm-accordion-closed" id="">
      <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>{ts}Thank-you Screen{/ts}
      </div>
      <div class="crm-accordion-body">
         <table class= "form-layout-compressed">
           <tr class="crm-event-manage-registration-form-block-confirm_thankyou_title">           
              <td scope="row" class="label" width="20%">{$form.thankyou_title.label} <span class="marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_title' id=$eventID}{/if}</td>
              <td>{$form.thankyou_title.html}
                <div class="description">{ts}Page title for registration Thank-you screen.{/ts}</div>
            </td>
            </tr>
            <tr class="crm-event-manage-registration-form-block-confirm_thankyou_text">
              <td scope="row" class="label" width="20%">{$form.thankyou_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_text' id=$eventID}{/if}</td>
              <td>{$form.thankyou_text.html}
                 <div class="description">{ts}Optional message for Thank-you screen (may include HTML formatting).{/ts}</div>
              </td>
            </tr>
            <tr class="crm-event-manage-registration-form-block-confirm_thankyou_footer_text">
              <td scope="row" class="label" width="20%">{$form.thankyou_footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_footer_text' id=$eventID}{/if}</td>
              <td>{$form.thankyou_footer_text.html}
                  <div class="description">{ts}Optional footer text for Thank-you screen (often used to include links to other pages/activities on your site).{/ts}</div>
              </td>
            </tr>
         </table>
      </div>
    </div>  

    {* Confirmation Email Block *}
    <div class="crm-accordion-wrapper crm-accordion-closed" id="">
      <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div>{ts}Confirmation Email{/ts}
      </div>
      <div class="crm-accordion-body">
          <table class= "form-layout-compressed">
            <tr class="crm-event-manage-registration-form-block-is_email_confirm"> 
              <td scope="row" class="label" width="20%">{$form.is_email_confirm.label}</td>
              <td>{$form.is_email_confirm.html}<br />
                  <span class="description">{ts}Do you want a registration confirmation email sent automatically to the user? This email includes event date(s), location and contact information. For paid events, this email is also a receipt for their payment.{/ts}</span>
              </td>
            </tr>
          </table>
          <div id="confirmEmail">
           <table class="form-layout-compressed">
             <tr class="crm-event-manage-registration-form-block-confirm_email_text">
               <td scope="row" class="label" width="20%">{$form.confirm_email_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_email_text' id=$eventID}{/if}</td>
               <td>{$form.confirm_email_text.html}<br />
                   <span class="description">{ts}Additional message or instructions to include in confirmation email.{/ts}</span>
               </td>
             </tr>
             <tr class="crm-event-manage-registration-form-block-confirm_from_name">
               <td scope="row" class="label" width="20%">{$form.confirm_from_name.label} <span class="marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_from_name' id=$eventID}{/if}</td>
               <td>{$form.confirm_from_name.html}<br />
                   <span class="description">{ts}FROM name for email.{/ts}</span>
               </td>
             </tr>
             <tr class="crm-event-manage-registration-form-block-confirm_from_email">
               <td scope="row" class="label" width="20%">{$form.confirm_from_email.label} <span class="marker">*</span></td>
               <td>{$form.confirm_from_email.html}
                   {include file="CRM/common/defaultFrom.tpl"}
                   <br />
                   <span class="description">{ts}FROM email address (this must be a valid email account with your SMTP email service provider).{/ts}<br>
                   {ts}Most of mail providers apply DMARC, that means if you use free email address as mail sender, the mail will be blocked by destination inbox.{/ts}<br>
                   {ts 1=`$mail_providers`}Do not use free mail address as mail sender. (eg. %1){/ts}
                   </span>
               </td>
             </tr>
             <tr class="crm-event-manage-registration-form-block-cc_confirm">
               <td scope="row" class="label" width="20%">{$form.cc_confirm.label}</td>
               <td>{$form.cc_confirm.html}<br />
                    <span class="description">{ts}You can notify event organizers of each online registration by specifying one or more email addresses to receive a carbon copy (cc). Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}</span>
               </td>
             </tr>
             <tr class="crm-event-manage-registration-form-block-bcc_confirm">
               <td scope="row" class="label" width="20%">{$form.bcc_confirm.label}</td>
               <td>{$form.bcc_confirm.html}<br />
                  <span class="description">{ts}You may specify one or more email addresses to receive a blind carbon copy (bcc) of the confirmation email. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}</span>
               </td>
             </tr>
             <tr class="crm-event-manage-registration-form-block-allow_cancel_by_link">
                <td class="label">{$form.allow_cancel_by_link.label}</td>
                <td>{$form.allow_cancel_by_link.html}
                <span class="description">{ts}Links only displays on free event.{/ts}</span>
                </td>
             </tr>
           </table>
      </div>
    </div>  

    </div> {*end of div registration_blocks*}
    </div>
 <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
 </div>
</div> <!-- crm-event-manage-registration-form-block -->

{include file="CRM/common/showHideByFieldValue.tpl" 
trigger_field_id    ="is_online_registration"
trigger_value       ="" 
target_element_id   ="registration_blocks" 
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{include file="CRM/common/showHideByFieldValue.tpl" 
trigger_field_id    ="is_email_confirm"
trigger_value       =""
target_element_id   ="confirmEmail" 
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{if $form.requires_approval}
  {include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="requires_approval"
    trigger_value       =""
    target_element_id   ="id-approval-text" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
  }
{/if}

{*include profile link function*}
{include file="CRM/common/buildProfileLink.tpl"}

<script type="text/javascript">
    {literal}
    cj().crmaccordions(); 

    cj("#is_multiple_registrations").change( function( ) {
        if ( !cj(this).attr( 'checked') ) {
            cj("#additional_custom_pre_id").val('');
            cj("#additional_custom_post_id").val('');
            cj("#allow_same_participant_emails").removeAttr("checked");
        }
    });

    //show edit profile field links
    // show edit for main profile
    cj('select[id^="custom_p"]').live( 'change',  function( event ) {
        buildLinks( cj(this), cj(this).val());
    });
    
    // make sure we set edit links for main contact profile when form loads
    cj('select[id^="custom_p"]').each( function(e) {
        buildLinks( cj(this), cj(this).val()); 
    });

    //show edit profile field links in additional participant
    cj('select[id^="additional_custom_p"]').live( 'change',  function( event ) {
        buildLinks( cj(this), cj(this).val());
    });

    // make sure we set edit links for additional profile  when form loads
    cj('select[id^="additional_custom_p"]').each( function(e) {
        buildLinks( cj(this), cj(this).val()); 
    });

    cj('#registration_blocks>table').append(cj('<tr class="crm-event-manage-registration-form-block-expiration_day"><td scope="row" class="label" width="20%"><label for="expiration_day">{/literal}{ts}Pending participant expiration (days){/ts}{literal}</label></td><td><input name="expiration_day" type="text" id="expiration_day" class="four"><div class="helpicon">&nbsp;<span id="id-expiration_time_help" style="display:none"><div class="crm-help">{/literal}{ts}Time limit <strong>in days</strong> for confirming/finishing registration by participants with any of the pending statuses. Enter 0 (or leave empty) to disable this feature.{/ts}{literal}</div></span></div></td></tr>'));
    cj('#expiration_day').keyup(function(){
      var val = cj('#expiration_day').val();
      hour = val * 24 ;
      cj('#expiration_time').val(hour);
    })
    cj('#expiration_day').val(Math.floor(cj('#expiration_time').val()/24));
    cj('.crm-event-manage-registration-form-block-expiration_time').hide();

    {/literal}
</script>
{include file="CRM/common/formNavigate.tpl"}
