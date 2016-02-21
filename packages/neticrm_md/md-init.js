(function($) {

  "use strict";

  /*
  var wlPath = window.location.pathname;
  var wlPathArr = window.location.pathname.split("/");
  var wlSearch = window.location.search;
  var wlPathSearch = wlPath + wlSearch;
  console.log(wlPath);
  console.log(wlSearch);
  console.log(wlPathSearch);
  */

  // Get viewport
  var viewport;
  var getViewport = function() {
    viewport = {
      width  : $(window).width(),
      height : $(window).height()
    };
  }
  getViewport();

  var mdInit = function() {
    $(".crm-form-elem").each(function() {
      if ($(this).hasClass("crm-form-radio")) {
        $(this).addClass("mdl-radio mdl-js-radio mdl-js-ripple-effect");
        $(this).children("input[type='radio']").addClass("mdl-radio__button");
        $(this).children(".elem-label").addClass("mdl-radio__label");
      }

      if ($(this).hasClass("crm-form-checkbox")) {
        $(this).addClass("mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect");
        $(this).children("input[type='checkbox']").addClass("mdl-checkbox__input");
        $(this).children(".elem-label").addClass("mdl-checkbox__label");
      }

      if ($(this).hasClass("crm-form-select")) {
        $(this).addClass("md-elem md-select");

        if ($(this).find("select:not([multiple])").length > 0) {
          $(this).addClass("md-select-single");
        } else {
          $(this).addClass("md-select-multiple");
        }
      }

      if ($(this).hasClass("crm-form-textfield")) {
        $(this).addClass("md-elem md-textfield");
      }

      if ($(this).hasClass("crm-form-textarea")) {
        $(this).addClass("md-elem md-textarea");
        $(this).find("textarea").attr("placeholder", "請在此輸入...");
      }

      if ($(this).find("[readonly]").length > 0) {
        $(this).addClass("md-elem-readonly");
      }
    });

    if ($(".advmultiselect").length > 0) {
      $(".advmultiselect").each(function() {
        $(this).addClass("crm-form-elem crm-form-select md-elem md-select md-select-multiple md-select-multiple-adv");
      });
    }

    if ($("select[id^='twaddr']").length > 0) {
      $("select[id^='twaddr']").wrap("<div class='crm-form-elem crm-form-select md-elem md-select md-select-single'></div>");
    }

    if ($("input.post-code").length > 0) {
      $("input.post-code").wrap("<div class='crm-form-elem crm-form-textfield md-elem md-textfield md-elem-readonly crm-form-post-code'></div>");
    }
  }

  var clearElement = function() {
    if ($(".state_province-1-section").length > 0) {
      $(".state_province-1-section .content .md-elem").each(function() {
        if ($.trim($(this).html()).length == 0) {
          $(this).remove();
        }
      });
    }
  }

  var rwdEvent = function(vw) {
    if (vw >= 768) {
      // desktop
    } else {
      // mobile
    }
  }

  var isFrontend = function() {
    var allow_path = [
      "/civicrm/event/register",
      "/civicrm/event/info",
      "/civicrm/contribute/transact",
    ];
    var result = $.inArray(path, allow_path) != -1 ? true : false;

    return result;
  }

  // Document ready
  $(document).ready(function() {
    if (isFrontend()) {
      mdInit();
    }

    // rwdEvent(viewport.width);
  });

  $(window).load(function() {
    clearElement();
  });

  // Window resize
  var resizeTimer;
  var windowResize = function() {
    getViewport();
    rwdEvent(viewport.width);
  };

  $(window).resize(function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(windowResize, 250);
  });

  $(window).scroll(function() {
    var scroll = $(window).scrollTop();
  });

})(jQuery);