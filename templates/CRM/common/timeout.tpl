<div class="messages help">{$message} <a class="timeout-refresh">{ts}Refresh{/ts}</a></div>
<script>{literal}
cj(document).ready(function($){
  $(".timeout-refresh").attr('href', window.location.href);
});
{/literal}</script>
