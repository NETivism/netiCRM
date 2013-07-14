{literal}
<script type="text/javascript"> 
cj(document).ready(function(){
  cj('{/literal}{$selector}{literal}').chosen({
    "search_contains": true,
    "placeholder_text": "{/literal}{ts}-- Select --{/ts}{literal}",
    "no_results_text": "{/literal}{ts}No matches found.{/ts}{literal}"
  });
});
</script>
{/literal}
