<div class="crm-block crm-block-rwd crm-event-participant_checkin-form-block">
  <div class="event-info-content">
    <div class="flex-general">
      <label class="label"><i class="zmdi zmdi-account-box"></i> {ts}Participant{/ts}:</label>
      <span class="content">
        {$display_name}
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
        <span><del>{$status_before}</del></span><i class="zmdi zmdi-long-arrow-right"></i><span><strong>{$status_after}</strong></span>
      </span>
    </div>
    <div class="flex-general">
      <label class="label"><i class="zmdi zmdi-time"></i> {ts}Check-in Date{/ts}:</label>
      <span class="content">
        {$current_date}
      </span>
    </div>
    <div id="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
      {if $status_message}
      <div class="description">
          {$status_message}
      </div>
      {/if}
    </div>
  </div><!-Event Info Contact-->
</div>