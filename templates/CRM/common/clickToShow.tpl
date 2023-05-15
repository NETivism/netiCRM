<script>{literal}
cj(document).ready(function($){
  $('.click-to-show').each(function(){
    let info = $(this).find('.click-to-show-info');
    info.hide();
    $(this).find('.click-to-show-trigger').click(function(e){
      e.preventDefault();
      info.show();
      $(this).hide();
    });
  });
});
{/literal}</script>