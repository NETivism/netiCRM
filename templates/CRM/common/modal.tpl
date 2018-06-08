{if $modalImage}
  <img src="{$modalImage.url}" height="{$modalImage.thumb.width}" width="{$modalImage.thumb.height}" class="jquery-modal" />
{/if}
{literal}
<script>
cj(document).ready(function($){
  // image enlarge
  $("img.jquery-modal").css("cursor", "pointer");
  $("img.jquery-modal").click(function(e){
    e.preventDefault();
    var $img = $(this).clone();
    var width = $img[0].naturalWidth;
    var height = $img[0].naturalHeight;
    $img.on($.modal.OPEN, function(){
      if (width < height) {
        if (height > $(window).height()) {
          $img.css({"width":"auto", "max-width":"unset", "max-height":"80vh"});
        } 
        else {
          $img.css({"width":"auto", "max-width":"unset", "max-height":height+"px"});
        }
      }
      else {
        if (width > $(window).width()*0.7) {
          $img.css({"width":"auto", "max-width":"70vw", "max-height":"unset"});
        }
        else {
          $img.css({"width":"auto", "max-width":width + "px", "max-height":"unset"});
        }
      }
    });
    $img.on($.modal.AFTER_CLOSE, function(){
      $img.remove();
    });
    $img.modal();
    return false;
  });
});
</script>
{/literal}
