{if $id}
    <div class="crm-actions-ribbon crm-event-manage-tab-actions-ribbon">
    	<ul id="actions">
      <li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-event-nav-link"><span><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`"}">{ts}Participant Count{/ts}<div class="icon dashboard-icon"></div></a></span></div>
        </div>
      </li>
      <li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-event-nav-link"><span><a href="{crmURL p='civicrm/participant/add' q="reset=1&action=add&context=standalone&eid=`$id`"}">{ts}Register New Participant{/ts}<div class="icon add-icon"></div></a></span></div>
        </div>
      </li>
      <li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-eventsetting-link"><span>{ts}Configure{/ts}<div class="icon dropdown-icon"></div></div>
          <div class="action-link-result ac_results" id="crm-eventsetting-list">
            <div class="action-link-result-inner crm-eventsetting-list-inner">
              <ul>
              <li><a href="{crmURL p='civicrm/event/manage/eventInfo' q="reset=1&action=update&id=`$id`"}">{ts}Info and Settings{/ts}</a></li>
              <li><a href="{crmURL p='civicrm/event/manage/location' q="reset=1&action=update&id=`$id`"}">{ts}Event Location{/ts}</a></li>
              <li><a href="{crmURL p='civicrm/event/manage/fee' q="reset=1&action=update&id=`$id`"}">{ts}Fees{/ts}</a></li>
              <li><a href="{crmURL p='civicrm/event/manage/registration' q="reset=1&action=update&id=`$id`"}">{ts}Online Registration{/ts}</a></li>
              <li><a href="{crmURL p='civicrm/event/manage/friend' q="reset=1&action=update&id=`$id`"}">{ts}Tell a Friend{/ts}</a></li>
              </ul>
            </div>
          </div>
        </div>
      </li>
    	<li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-event-links-link"><span><div class="icon dropdown-icon"></div>{ts}Event Links{/ts}</span></div>
    	    <div class="action-link-result ac_results" id="crm-event-links-list">
    	      <div class="action-link-result-inner crm-event-links-list-inner">
              <ul>
                <li><a class="crm-event-info" href="{crmURL p='civicrm/event/info' q="reset=1&id=`$id`" fe='true'}" target="_blank">{ts}Event Info{/ts}</a></li>
                {if $isOnlineRegistration}
                <li><a class="crm-event-test" href="{crmURL p='civicrm/event/register' q="reset=1&action=preview&id=`$id`"}">{ts}Online Registration (Test-drive){/ts}</a></li>
                <li><a class="crm-event-live" href="{crmURL p='civicrm/event/register' q="reset=1&id=`$id`" fe='true'}" target="_blank">{ts}Online Registration (Live){/ts}</a></li>
                {if $participantListingURL}
                <li><a class="crm-participant-listing" href="{$participantListingURL}">{ts}Public Participant Listing{/ts}</a></li>
                {/if}
                {/if}
              </ul>
    	      </div>
    	    </div>
        </div>
      </li>
      <!--
    	<li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-participant-link"><span>{ts}Find Participants{/ts}<div class="icon dropdown-icon"></div></div>
          <div class="action-link-result ac_results" id="crm-participant-list">
            <div class="action-link-result-inner crm-participant-list-inner">
              <ul>
              <li><a class="crm-participant-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`&status=true"}">{ts}Counted{/ts}</a></li>
              <li><a class="crm-participant-not-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`&status=false"}">{ts}Not Counted{/ts}</a></li>
              </ul>
            </div>
          </div>
        </div>
      </li>
      -->
    	</ul>
    	<div class="clear"></div>
    </div>
  {literal}
  <script>

  cj('body').click(function() {
    cj('.action-link-result').hide();
  });

  cj('#crm-event-links-link,#crm-participant-link,#crm-eventsetting-link').click(function(event) {
    cj('.ac_results').hide();
    cj(this).next().toggle();
    event.stopPropagation();
  });

  </script>
  {/literal}
{/if}
