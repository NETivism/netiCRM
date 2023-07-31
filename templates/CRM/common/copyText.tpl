{literal}
<script>
(function ($) {
$(document).ready(function(){
  $('input[type=text][data=copy-text], textarea[data=copy-text]').each(function(){
    let $textarea = $(this);
    $(this).wrap('<div class="copy-text-widget">');
    let $widget = $(this).closest('div.copy-text-widget');
    $(this).wrap('<div class="copy-text-widget-inner" style="position: relative; width: min-content;">');
    $(this).before('<span data="copy-text-button"><i class="zmdi zmdi-copy"></i>{/literal}{ts}Copy{/ts}{literal}</span>');
    let $button = $widget.find('[data=copy-text-button]');
    $button.css({
      "position":"absolute",
      "top":"0",
      "right":"0",
      "opacity":".3",
      "background":"rgba(255,255,255, 0.9)",
      "padding":"3px",
      "border-radius": "0 0 0 10px",
      "border":"1px solid #777",
      "border-width":"0 0 1px 1px",
      "z-index":"10",
      "cursor":"pointer"
    });
    $textarea.hover(function(){
      $button.css("opacity","1");
    }, function(){
      $button.css("opacity",".3");
    });
    $button.hover(function(){
      $button.css("opacity","1");
    }, function(){
      $button.css("opacity",".3");
    });
    $button.click(function(){
      $textarea.select();
      document.execCommand("copy");
      $button.html('<i class="zmdi zmdi-check-circle"></i>{/literal}{ts}Copied{/ts}{literal}');
      window.setTimeout(function(){
        $button.html('<i class="zmdi zmdi-copy"></i>{/literal}{ts}Copy{/ts}{literal}');
      }, 2500);
    });
  });
});
})(cj);
</script>
{/literal}