<div class="messages status">
  <div class="icon inform-icon"></div>
  {if $eventFullText}
    {$eventFullText} <br />
  {else}
    {ts}Until to the last step, your registeration is not complete and we won't reserve any spaces for your registeration.{/ts}
  {/if}
</div>
{if $eventFullText}
  <p><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.id`"}">&raquo; {ts 1=$event.event_title}Back to "%1" event information{/ts}</a></p>
{literal}
<script type="text/javascript">
  cj(document).ready(function(){
    cj('.crm-block').css('opacity', '.3');
  });
</script>
{/literal}
{else}
  {include file="CRM/common/WizardHeader.tpl"}
  {* moved to single tpl since need to show for all step *}
  {if $requireApprovalMsg || $waitlistMsg}
    <div id = "id-waitlist-approval-msg" class="messages status">
      {if $requireApprovalMsg}
        <div id="id-req-approval-msg">{$requireApprovalMsg}</div>
      {/if}
      {if $waitlistMsg}
        <div id="id-waitlist-msg">{$waitlistMsg}</div>
      {/if} 
    </div>
  {/if}
{/if}
