{if $modalImage}
  <img src="{$modalImage.url}" height="{$modalImage.thumb.width}" width="{$modalImage.thumb.height}" class="simplemodal" />
{/if}
{literal}
<script>
cj(document).ready(function($){
  // image enlarge
  $("img.simplemodal").css("cursor", "pointer");
  $("img.simplemodal").click(function(){
    var html = $(this)[0].outerHTML;
    var $html = $(html);
    $html.attr("width", "100%").removeAttr("height").removeAttr("class");
    html = $html[0].outerHTML;
    $.modal(html, {minWidth:"50%", minHeight:"50%", maxWidth:"80%", maxHeight:"90%"});
  });
});
</script>
{/literal}
