{if $confirm}
<div class="messages status">
  <label>{$display_name} ({$email})</label> {ts}has been successfully unsubscribed.{/ts}
</div>
{else}
<div align="center">
  {if $groupExist}
  <div class="messages status">
    {ts 1=$display_name 2=$email} %1 (%2){/ts}<br/>
    {ts}Are you sure you want to be unsubscribed this mailing list?{/ts}<br/>
  </div>
  {include file='CRM/common/ReCAPTCHA.tpl'}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  {else}
  <div class="messages status">
    {ts 1=$display_name 2=$email} %1 (%2){/ts}<br/>
    {ts}Sorry you are not on the mailing list. Probably you are already unsubscribed.{/ts}<br/>
  </div>
  {/if}
</div>
{/if}
