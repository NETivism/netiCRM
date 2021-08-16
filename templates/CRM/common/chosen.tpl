{if !$nowrapper}<script type="text/javascript"> {/if}
{literal}
cj(document).ready(function(){
  cj('{/literal}{$selector}{literal}').select2({
    "dropdownAutoWidth": true,
    {/literal}{if $select_width}"width": "{$select_width}",{/if}{literal}
    "placeholder": "{/literal}{ts}-- Select --{/ts}{literal}",
    "language": "{/literal}{if $config->lcMessages}{$config->lcMessages|replace:'_':'-'}{else}en{/if}{literal}"
  });
});
{/literal}
{if !$nowrapper}</script>{/if}
