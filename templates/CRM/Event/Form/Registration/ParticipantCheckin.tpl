<div class="crm-block crm-block-rwd crm-event-participant_checkin-form-block">
  <div class="event-info-content">
    <div class="flex-general">
      <label class="label"><i class="zmdi zmdi-account-box"></i> {ts}Participant{/ts}:</label>
      <span class="content">
        <a href="{crmURL p="civicrm/contact/view/participant" q="reset=1&id=`$participant_id`&cid=`$contact_id`&action=view"}" target="_blank">{$display_name}</a>
      </span>
    </div>
    <div class="flex-general">
      <label class="label"><i class="zmdi zmdi-label"></i> {ts}Event{/ts}:</label>
      <span class="content">
        {$event.title}
      </span>
    </div>
    <div class="flex-general">
      <label class="label"><i class="zmdi zmdi-alert-polygon"></i> {ts}Status{/ts}:</label>
      <span class="content">
        {if $status}
          <span>{$status}</span>
        {else}
        <span><del>{$status_before}</del></span><i class="zmdi zmdi-long-arrow-right"></i><span><strong>{$status_after}</strong></span>
        {/if}
      </span>
    </div>
    <div class="flex-general">
      <label class="label">
        <i class="zmdi zmdi-time"></i> 
        {if $pending}
          {ts}Register Date{/ts}:
        {elseif $attended}
          {ts}Check-in Date{/ts}:
        {else}
          {ts}Current Day{/ts}:
        {/if}
      </label>
      <span class="content">
        {if $pending}
          {$participant.register_date|crmDate}
        {elseif $attended}
          {$checkin_date|crmDate}
        {else}
          {$current_date|crmDate}
        {/if}
      </span>
    </div>
    <div id="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
      {if $status_message}
      <div class="description">
          {if $pending}
            <div class="messages warning">
            {$status_message}
            </div>
          {elseif $attended}
            <div>
            {$status_message}
            </div>
          {else}
            {$status_message}
          {/if}
      </div>
      {/if}
    </div>
  </div><!-Event Info Content-->
</div>
