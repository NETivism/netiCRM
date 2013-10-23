{if $event.max_participants}
{literal} 
<script type="text/javascript">
 var updateSeat = function(){
    cj.ajax({
      type:'GET',
      url:'/civicrm/ajax/eventFull?id=16',
      dataType:'json'
    }).done(function(r) {
      if(r.seat > 0){
        var msg = '{/literal}{ts 1='@s'}There is only enough space left on this event for %1 participant(s).{/ts}<br /><span style="font-size:10px;">({ts}Update per minute.{/ts})</span>{literal}';
        msg = msg.replace('@s', r.seat);
      }
      else{
        var msg = '{/literal}{ts}This event is currently full.{/ts}{literal}';
      }
      var seat_msg = '<div class="messages status float-right left-seat" style="opacity:0.5;">'+msg+'</div>';
      cj('.left-seat').remove();
      if($('#wizard-steps').length){
        cj('#wizard-steps').before(seat_msg);
      }
      else{
        cj('#crm-container').prepend(seat_msg);
      }
      cj('#crm-submit-buttons').before(seat_msg);
      cj('.left-seat').animate({opacity:1}, 1000);
    });
  }
  updateSeat();
  setInterval('updateSeat()', 60000);
</script>
{/literal} 
{/if}
