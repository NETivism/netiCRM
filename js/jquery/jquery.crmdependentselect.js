(function($) {
$.fn.crmDependentSelect = function() {
  $(".crm-container").on("focus", "select:not(.depended)", function() {
    $(this).addClass("depended");

    $(".crm-container select[data-parent]").each(function() {
      $(this).addClass("depended");
      var $child = $(this),
          parentId = $(this).attr("data-parent"),
          isParentCustom = $(this).attr("data-parent-custom");

      if (parentId) {
        parentId = isParentCustom != 0 ? "custom_" + parentId : parentId;
        var $parent = $(".crm-container select[name^=" + parentId + "]"),
            parentVal = $parent.val();

        $child.find("option:not([data-parent-filter='" + parentVal + "']").hide();
        $child.find("option[value='']").show();

        if ($parent.length) {
          $parent.change(function() {
            parentVal = $(this).val();
            $child.find("option").show();
            $child.find("option[value='']").prop("selected", true);
            $child.find("option:not([data-parent-filter='" + parentVal + "']").hide();
          });
        }
      }
    });
  });
}
})(jQuery);
