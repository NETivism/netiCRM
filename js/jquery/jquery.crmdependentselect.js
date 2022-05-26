(function($) {
$.fn.crmDependentSelect = function() {
  $(".crm-container").on("focus", "select:not(.depended)", function(){
    $(this).addClass("depended");
    $(".crm-container select[data-parent]").each(function(){
      $(this).addClass("depended");
      var $child = $(this),
          parentId = $(this).attr('data-parent'),
          isParentCustom = $(this).attr('data-parent-custom');

      if (parentId) {
        parentId = isParentCustom != 0 ? 'custom_' + parentId : parentId;
        var $parent = $(".crm-container select[name^="+parentId+"]");
        $child.find('option:not(:checked)').hide();
        $child.find('option[value=""]').show();
        $parent.change(function(){
          var currentSelection = $parent.val();
          $child.find('option').show();
          $child.find('option[value=""]').prop("selected", true);
          $child.find('option[data-parent-filter!="'+currentSelection+'"]').hide();
        });
      }
    });
  });
}
})(jQuery);
