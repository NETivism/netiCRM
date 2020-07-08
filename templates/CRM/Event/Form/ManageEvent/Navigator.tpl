{if $id}
    <div class="crm-actions-ribbon crm-event-manage-tab-actions-ribbon">
    	<ul id="actions">
      <li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-event-nav-link"><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`"}" class="button"><i class="zmdi zmdi-chart"></i>{ts}Participant Count{/ts}</a></div>
        </div>
      </li>
      <li>
        <div class="action-wrapper">
    	    <div class="action-link-button" id="crm-event-nav-link"><a class="button" href="{crmURL p='civicrm/participant/add' q="reset=1&action=add&context=standalone&eid=`$id`"}" target="_blank"><i class="zmdi zmdi-account-add"></i>{ts}Register New Participant{/ts}</a></div>
        </div>
      </li>
      <li>
        <div class="action-wrapper action-link-button">
    	    <div class="button" id="crm-eventsetting-link">{ts}Configure{/ts}<i class="zmdi zmdi-arrow-right-top zmdi-hc-rotate-90"></i></div>
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
    	    <div class="action-link-button" id="crm-event-nav-link"><a class="button" href="{crmURL p='civicrm/event/links' q="reset=1&id=`$id`"}"><i class="zmdi zmdi-link"></i>{ts}Event Links{/ts}</a></div>
        </div>
      </li>
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
