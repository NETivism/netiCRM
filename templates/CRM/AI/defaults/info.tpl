  "org_intro": "{/literal}{if $org_intro}{ts}Organization intro{/ts}:{$org_intro|escape:javascript}{/if}{literal}",
  "sort_name": "{/literal}{$sort_name|escape:javascript}{literal}",
  "usage": {
    "max": {/literal}{if $usage.max}{$usage.max}{else}100{/if}{literal},
    "used": {/literal}{if $usage.used}{$usage.used}{else}0{/if}{literal},
    "percent": {/literal}{if $usage.percent}{$usage.percent}{else}0{/if}{literal}
  }{/literal}
