<script>{literal}
cj(document).ready(function(){
  var default_from = '{/literal}{$default_from_value}{literal}';
  var target = '{/literal}{$default_from_target}{literal}';
  var link = "default-from-" + target;
  if (default_from.length && target.length){
    if (cj('#'+target).length) {
      cj('#'+link).click(function(e){
        e.preventDefault();
        cj('#'+target).val(default_from);
        cj('#'+target).trigger('focus');
        cj('#'+target).trigger('select');
        return false;
      });
    }
  }
});
{/literal}</script>
<a id="default-from-{$default_from_target}" href="#{$default_from_target}">{ts 1=$default_from_value|escape:'html'}Use default: %1{/ts}</a>
