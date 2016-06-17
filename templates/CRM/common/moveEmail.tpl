<script>{literal}
(function($){

$(document).ready(function(){
  var dest = '#' + "{/literal}{$moveEmail}{literal}";
  if($(dest).length){
    $(".crm-container .crm-section.email-section").prependTo(dest);
  }
});

})(cj);
{/literal}</script>
