
{include file="CRM/Event/Form/ManageEvent/Navigator.tpl"}


<div class="flex-general">
  {capture assign=eventInfoURL}{crmURL a=true p='civicrm/event/info' q="reset=1&id=`$id`" fe='true'}{/capture}
  <a href="{$eventInfoURL}" target="_blank">
    &raquo; {ts}Event Info{/ts}
  </a>
  <textarea name="url_to_copy" class="url_to_copy" cols="45" rows="1" onclick="this.select(); document.execCommand('copy');" data-url-original="{$eventInfoURL}">{if $shorten_info}{$shorten_info}{else}{$eventInfoURL}{/if}</textarea>
  <span>
    <a href="#" class="button url-copy" onclick="document.querySelector('textarea[name=url_to_copy]').select(); document.execCommand('copy'); return false;"><i class="zmdi zmdi-link"></i> {ts}Copy{/ts}</a>
  </span>
  <span>
    <a href="#" class="button url-shorten" data-url-shorten="url_to_copy" data-page-id="{$id}" data-page-type="civicrm_event.info">
      <i class="zmdi zmdi-share"></i> {ts}Shorten URL{/ts}
    </a>
  </span>
</div>

{if $event.is_online_registration}
<div class="flex-general">
  {capture assign=liveURL}{crmURL a=true p='civicrm/event/register' q="reset=1&id=`$id`" fe='true'}{/capture}
  <a href="{$liveURL}" target="_blank">
    &raquo; {ts}Online Registration{/ts}
  </a>
  <textarea name="url_to_copy2" class="url_to_copy" cols="45" rows="1" onclick="this.select(); document.execCommand('copy');" data-url-original="{$liveURL}">{if $shorten_register}{$shorten_register}{else}{$liveURL}{/if}</textarea>
  <span>
    <a href="#" class="button url-copy" onclick="document.querySelector('textarea[name=url_to_copy2]').select(); document.execCommand('copy'); return false;" ><i class="zmdi zmdi-link"></i> {ts}Copy{/ts}</a>
  </span>
  <span>
    <a href="#" class="button url-shorten" data-url-shorten="url_to_copy2" data-page-id="{$id}" data-page-type="civicrm_event.register">
      <i class="zmdi zmdi-share"></i> {ts}Shorten URL{/ts}
    </a>
  </span>
</div>

<div class="flex-general">
  <a class="crm-event-test" href="{crmURL p='civicrm/event/register' q="reset=1&action=preview&id=`$id`"}" target="_blank">
    <i class="zmdi zmdi-alert-octagon"></i>
    {ts}Online Registration (Test-drive){/ts}
  </a>
</div>
  {if $event.participant_listing_id}
  <div class="flex-general">
    <a class="crm-participant-listing" href="{crmURL p='civicrm/event/participant' q="reset=1&id=`$id`"}" target="_blank">
      <i class="zmdi zmdi-assignment-account"></i>
      {ts}Public Participant Listing{/ts}
    </a>
  </div>
  {/if}
{/if}

<div class="flex-general">
  <a class="crm-traffic-source" href="{crmURL p='civicrm/track/report' q="reset=1&ptype=civicrm_event&pid=`$id`"}" target="_blank">
    <i class="zmdi zmdi-chart"></i>
    {ts}Traffic Source{/ts}
  </a>
</div>
{include file="CRM/common/ShortenURL.tpl"}