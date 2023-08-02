(function($) {
  // TODO: If default values are set, dependencies might have issues
  // TODO: Auto-fill features in some browsers or plugins might set dropdown values before JS execution, possibly causing field misalignment
  $.fn.crmDependentSelect = function() {
    $(".crm-container select[data-parent]").each(function() {
      var $child = $(this),
          parentId = $(this).attr("data-parent"),
          isParentCustom = $(this).attr("data-parent-custom"),
          originalOptions = $child.find('option').clone(); // Store original options

      $(this).addClass("depended");

      if (parentId) {
        parentId = isParentCustom != 0 ? "custom_" + parentId : parentId;
        var $parent = $(".crm-container select[name^=" + parentId + "]"),
            parentVal = $parent.val();

        // Update child options based on parent's value
        function updateChildOptions(parentVal) {
          $child.empty().append(originalOptions.clone().filter(function(index, option) {
            return !$(option).attr('data-parent-filter') || $(option).attr('data-parent-filter') === parentVal;
          }));
        }

        // Initialize child menu
        updateChildOptions(parentVal);

        // Update child menu when parent changes
        $parent.change(function() {
          updateChildOptions($(this).val());
        });
      }
    });

    // TODO: This code snippet might not be necessary; check later
    $(".crm-container").on("focus", "select:not(.depended)", function() {
      $(this).addClass("depended");
    });
  }
})(jQuery);
