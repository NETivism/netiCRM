{if !$nowrapper}<script type="text/javascript"> {/if}
{literal}
(function ($) {
$(document).ready(function(){
  let $elements = $('{/literal}{$selector}{literal}');
  $elements.each(function() {
    let $this = $(this);
    let options = {
      "allowClear": true,
      "dropdownAutoWidth": true,
      {/literal}{if $select_width}"width": "{$select_width}",{/if}{literal}
      "placeholder": "{/literal}{ts}-- Select --{/ts}{literal}",
      "language": "{/literal}{if $config->lcMessages}{$config->lcMessages|replace:'_':'-'}{else}en{/if}{literal}",
      "selectAllButton": {/literal}{if $select_all_button}true{else}false{/if}{literal}
    };

    if ($this.is('select[multiple]')) {
      options.dropdownAdapter = $.fn.select2.amd.require('select2/selectAllAdapter');
    }

    $this.select2(options);
  });

  $(document).on('select2:open select2:close', (e) => {
    let thisSelect = e.target,
        $thisSelect = $(thisSelect),
        $thisSelect2Container = $thisSelect.next('.select2-container').length ? $thisSelect.next('.select2-container') : $thisSelect.closest('.crm-form-elem').find('.select2-container'),
        $inputField = $thisSelect2Container.find('.select2-search__field');

    if ($inputField.length) {
      let placeholderText = e.type == 'select2:open' ? '{/literal}{ts}Input search keywords{/ts}{literal}' : '{/literal}{ts}-- Select --{/ts}{literal}';

      $inputField.attr('placeholder', placeholderText);

      if (e.type == 'select2:open') {
        setTimeout(() => {
          $inputField.focus();
        }, 100);
      }
    }
  });
});
})(cj);
{/literal}
{if !$nowrapper}</script>{/if}
{if $config->lcMessages eq 'zh_TW'}
  {* this will compitable with drupal 6-7-9 *}
  {* parameter library will use library name pree-defined in civicrm.module *}
  {js src=packages/jquery/plugins/jquery.select2.zh-TW.js library=civicrm/civicrm-js-zh-tw group=999 weight=998}{/js}
{/if}