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
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-event-thankyou-form-block">
    {* Don't use "normal" thank-you message for Waitlist and Approval Required registrations - since it will probably not make sense for those situations. dgg *}
    {if $event.thankyou_text AND (not $isOnWaitlist AND not $isRequireApproval)} 
        <div id="intro_text" class="crm-section event_thankyou_text-section">
            <p>
            {$event.thankyou_text}
            </p>
        </div>
    {/if}
    
    {* Show link to Tell a Friend (CRM-2153) *}
    {if $friendText}
        <div id="tell-a-friend" class="crm-section tell_friend_link-section">
            <a href="{$friendURL}" title="{$friendText}" class="button"><span>&raquo; {$friendText}</span></a>
       </div><br /><br />
    {/if}  

    <div class="crmdata-contribution" style="display:none">{$contribution_id}</div>
    <div class="crmdata-event-type" style="display:none">{$event.event_type_id}</div>
    <div {if $payment_result_type eq 4}class="messages error"{else}id="help"{/if}>
        {if $isOnWaitlist}
            <p>
                <span class="bold">{ts}You have been added to the WAIT LIST for this event.{/ts}</span>
                {ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
             </p> 
        {elseif $isRequireApproval}
            <p>
                <span class="bold">{ts}Your registration has been submitted.{/ts}
                {ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</span>
            </p>
        {elseif $payment_result_type eq 1 and $paidEvent}
          <h3>{ts}Congratulations! Your payment has been completed!{/ts}</h3>
          {if $is_email_confirm}
            <div>
            {if $is_email_confirm}
               <p>{ts}You will receive an email acknowledgement of this payment.{/ts}</p>
            {/if}
            </div>
          {/if}
        {elseif $payment_result_type eq 4 and $paidEvent}
          <h3>{ts}Payment failed.{/ts}</h3>
          {ts}We were unalbe to process your payment. Your will not been charged in this transaction.{/ts}
          {ts}Possible reason{/ts}:
          <ul>
          {if $payment_result_message}
            <li>{$payment_result_message}</li>
          {else}
            <li>{ts}Network or system error. Please try again a minutes later, if you still can't success, please contact us for further assistance.{/ts}</li>
          {/if}
          </ul>
        {elseif $is_pay_later and $paidEvent}
          <h3>{ts}Keep supporting it. Payment has not been completed yet with entire process.{/ts}</h3>
            <div class="bold">{$pay_later_receipt}</div>
            {if $is_email_confirm}
                <p>{ts 1=$email}An email with event details has been sent to %1.{/ts}</p>
            {/if}
        {elseif $contributeMode EQ 'notify' and $paidEvent}
            <p>{ts 1=$paymentProcessor.name}Your registration payment has been submitted to %1 for processing. Please print this page for your records.{/ts}</p>
            {if $is_email_confirm}
                <p>{ts 1=$email}A registration confirmation email will be sent to %1 once the transaction is processed successfully.{/ts}</p>
            {/if}
        {else}
            <p class="msg-register-success">{ts}Your registration has been processed successfully. Please print this page for your records.{/ts}</p>
            {if $is_email_confirm}
                <p>{ts 1=$email}A registration confirmation email has also been sent to %1{/ts}</p>
            {/if}
        {/if}
    </div>
    <div class="spacer"></div>

    <div class="crm-group event_info-group">
        <div class="header-dark">
            {ts}Event Information{/ts}
        </div>
        <div class="display-block">
            {include file="CRM/Event/Form/Registration/EventInfoBlock.tpl" context="ThankYou"}
        </div>
    </div>
    
    {if $paidEvent}
        <div class="crm-group event_fees-group">
            <div class="header-dark">
                {$event.fee_label}
            </div>
            {if $lineItem}
                {include file="CRM/Price/Page/LineItem.tpl" context="Event"}
            {elseif $amount || $amount == 0}
	            <div class="crm-section no-label amount-item-section">
                    {foreach from= $finalAmount item=amount key=level}  
            			<div class="content">
            			    {$amount.amount|crmMoney}&nbsp;&nbsp;{$amount.label}
            			</div>
            			<div class="clear"></div>
                    {/foreach}
                </div>
                {if $couponDescription && !$usedOptionsDiscount}
                    <div class="crm-section no-label discount-section">
                        <div class="content">{$couponDescription}:&nbsp;&nbsp;-{$totalDiscount|crmMoney}</div>
                        <div class="clear"></div>
                    </div>
                {/if}   
                {if $totalAmount}
        			<div class="crm-section no-label total-amount-section">
                		<div class="content bold">
                            {ts}Event Total{/ts}:&nbsp;&nbsp;{$totalAmount|crmMoney}
                            <span class="crmdata-amount" style="display:none">{$totalAmount}</span>
                        </div>
                		<div class="clear"></div>
                	</div>
                    {if $hookDiscount.message}
                        <div class="crm-section hookDiscount-section">
                            <em>({$hookDiscount.message})</em>
                        </div>
                    {/if}
                {/if}	
            {/if}
            {if $receive_date}
                <div class="crm-section no-label receive_date-section">
                    <div class="content bold">{ts}Transaction Date{/ts}: {$receive_date|crmDate}</div>
                	<div class="clear"></div>
                </div>
            {/if}
            {if $trxn_id}
                <div class="crm-section no-label trxn_id-section">
                    <div class="content bold">{ts}Transaction #{/ts}: <strong class="crmdata-trxn-id">{$trxn_id}</strong></div>
            		<div class="clear"></div>
            	</div>
            {/if}
            {if $payment_instrument}
              <div class="crm-section no-label trxn_id-section">
                <div class="content bold">{ts}Payment Instrument{/ts}: <span class="crmdata-instrument">{$payment_instrument}</span></div>
              </div>
            {/if}
        </div>
    
    {elseif $participantInfo}
        <div class="crm-group participantInfo-group">
            <div class="header-dark">
                {ts}Additional Participant Email(s){/ts}
            </div>
            <div class="crm-section no-label participant_info-section">
                <div class="content">
                    {foreach from=$participantInfo  item=mail key=no}  
                        <strong>{$mail}</strong><br />	
                    {/foreach}
                </div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}

    <div class="crm-group registered_email-group">
        <div class="header-dark">
            {ts}Registered Email{/ts}
        </div>
        <div class="crm-section no-label registered_email-section">
            <div class="content">
                {$email}
            </div>
    		<div class="clear"></div>
		</div>
    </div>
    
    {if $event.participant_role neq 'Attendee' and $defaultRole}
        <div class="crm-group participant_role-group">
            <div class="header-dark">
                {ts}Participant Role{/ts}
            </div>
            <div class="crm-section no-label participant_role-section">
                <div class="content">
                    {$event.participant_role}
                </div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}

    {if $customPreGroup}
        {foreach from=$customPreGroup item=field key=customName}
            {if $field.groupTitle}
                {assign var=groupTitlePre  value=$field.groupTitle} 
            {/if}
        {/foreach}
    	<div class="crm-group {$groupTitlePre}-group">
            <div class="header-dark">
              {ts 1=1}Participant Information - Participant %1{/ts}
            </div>
            <fieldset class="label-left">
                <legend>{$groupTitlePre}</legend>
                {include file="CRM/UF/Form/Block.tpl" fields=$customPreGroup}
            </fieldset>
        </div>
    {/if}

    {if $customPostGroup}
        {foreach from=$customPostGroup item=field key=customName}
            {if $field.groupTitle}
                {assign var=groupTitlePost  value=$field.groupTitle} 
            {/if}
        {/foreach}
    	<div class="crm-group {$groupTitlePost}-group">
            <fieldset class="label-left">  
                <legend>{$groupTitlePost}</legend>
                {include file="CRM/UF/Form/Block.tpl" fields=$customPostGroup}
            </fieldset>
        </div>
    {/if}

    {*display Additional Participant Info*}
    {if $customProfile}
        {foreach from=$customProfile item=value key=customName}
            <div class="crm-group participant_info-group">
                <div class="header-dark">
                    {ts 1=$customName+1}Participant Information - Participant %1{/ts}	
                </div>
                {foreach from=$value item=val key=field}
                    {if $field eq 'additionalCustomPre' or $field eq 'additionalCustomPost' }
                        {if $field eq 'additionalCustomPre' }
                            <fieldset class="label-left"><legend>{$value.additionalCustomPre_grouptitle}</legend>
                        {else}
                            <fieldset class="label-left"><legend>{$value.additionalCustomPost_grouptitle}</legend>
                        {/if}
                        {foreach from=$val item=v key=f}
                        <div class="crm-section {$field}-section">
                          <div class="label">{$f}</div>
                          <div class="content">{$v}</div>
                        </div>
                        {/foreach}
                        </fieldset>
                    {/if}
                <div>
            {/foreach}
            <div class="spacer"></div>  
        {/foreach}
    {/if}

    {if $contributeMode ne 'notify' and $paidEvent and ! $is_pay_later and ! $isAmountzero and !$isOnWaitlist and !$isRequireApproval}   
        <div class="crm-group billing_name_address-group">
            <div class="header-dark">
                {ts}Billing Name and Address{/ts}
            </div>
        	<div class="crm-section no-label billing_name-section">
        		<div class="content">{$billingName}</div>
        		<div class="clear"></div>
        	</div>
        	<div class="crm-section no-label billing_address-section">
        		<div class="content">{$address|nl2br}</div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}

    {if $contributeMode eq 'direct' and $paidEvent and ! $is_pay_later and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
        <div class="crm-group credit_card-group">
            <div class="header-dark">
                {ts}Credit Card Information{/ts}
            </div>
            <div class="crm-section no-label credit_card_details-section">
                <div class="content">{$credit_card_type}</div>
        		<div class="content">{$credit_card_number}</div>
        		<div class="content">{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}</div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}

    {if $event.thankyou_footer_text}
        <div id="footer_text" class="crm-section event_thankyou_footer-section">
            <p>{$event.thankyou_footer_text}</p>
        </div>
    {/if}
    
    <div class="action-link section event_info_link-section">
        <a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.id`"}">&raquo; {ts 1=$event.event_title}Back to "%1" event information{/ts}</a>
    </div>

    {if $event.is_public }
        {include file="CRM/Event/Page/iCalLinks.tpl"}
    {/if} 

</div>
