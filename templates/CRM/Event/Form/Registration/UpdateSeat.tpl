{if $event.max_participants}
{literal} 
<script type="text/javascript">
 var updateSeat = function(){
    cj.ajax({
      type:'GET',
      url:'/civicrm/ajax/eventFull?id={/literal}{$event.id}{literal}',
      dataType:'json'
    }).done(function(r) {
      if(r.seat > 0){
        var msg = '{/literal}{ts 1='@s'}There is only enough space left on this event for %1 participant(s).{/ts}<span style="font-size:10px;">({ts}Update per minute.{/ts})</span>{literal}';
        msg = msg.replace('@s', r.seat);
      }
      else{
        var msg = '{/literal}{ts}This event is currently full.{/ts}{literal}';
      }
      var seat_msg = '<div class="seat-msg" style="opacity:0.5;">' + msg + '</div>';
      cj('.seat-msg').remove();
      cj('.msg-event-full').append(seat_msg);
      cj('.seat-msg').animate({opacity:1}, 1000);
    });
  }
  updateSeat();
  setInterval('updateSeat()', 60000);
</script>
{/literal} 
{/if}
