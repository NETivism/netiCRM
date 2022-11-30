{if !$nowrapper}<script type="text/javascript"> {/if}
{literal}
cj(document).ready(function(){
  cj('{/literal}{$selector}{literal}').select2({
    "allowClear": true,
    "dropdownAutoWidth": true,
    {/literal}{if $select_width}"width": "{$select_width}",{/if}{literal}
    "placeholder": "{/literal}{ts}-- Select --{/ts}{literal}",
    "language": "{/literal}{if $config->lcMessages}{$config->lcMessages|replace:'_':'-'}{else}en{/if}{literal}"
  });
  cj(document).on('select2:open', () => {
    var inputField = document.querySelector('input.select2-search__field');
    if (inputField) {
      inputField.placeholder='{/literal}{ts}Input search keywords{/ts}{literal}';
      setTimeout(() => {
        inputField.focus();
      }, 100);
    }
  });
});
{/literal}
{if !$nowrapper}</script>{/if}
{if $config->lcMessages eq 'zh_TW'}
  {* this will compitable with drupal 6-7-9 *}
  {* parameter library will use library name pree-defined in civicrm.module *}
  {js src=packages/jquery/plugins/jquery.select2.zh-TW.js library=civicrm/civicrm-js-zh-tw group=999 weight=998}{/js}
{/if}