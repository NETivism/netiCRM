{if !$nowrapper}<script type="text/javascript"> {/if}
{literal}
cj(document).ready(function(){
  cj('{/literal}{$selector}{literal}').chosen({
    "search_contains": true,
    "placeholder_text": "{/literal}{ts}-- Select --{/ts}{literal}",
    "no_results_text": "{/literal}{ts}No matches found.{/ts}{literal}"
  });
});
{/literal}
{if !$nowrapper}</script>{/if}
