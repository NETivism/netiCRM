/**
 * @file
 * A JavaScript file for the module.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal, window, document) {
  'use strict';

  String.prototype.customReplaceAll = function(search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
  };

  window.imceCustom = function(file, imceWindow) {
    //console.log(file);
    console.log(imceWindow);
    if (moduleNeticrmNewsletter.nc.current) {
      var $target = $("#" + moduleNeticrmNewsletter.nc.current);

      if ($target.length) {
        var $btn = $target.find(".nc-img-insert"),
            $img = $target.find(".nc-img");

        if ($img.length) {
          $img.remove();
        }

        var img = "<img class='nc-img' src='" + file.url + "' alt='" + file.name + "' />";
        $target.append(img);

        if ($btn.text() == "請選擇圖片") {
          $btn.text("圖片變更");
        }
      }
    }

    imceWindow.close();
  }


  window.moduleNeticrmNewsletter = {
    path: window.location.pathname,
    qs: window.location.search,
    hash: window.location.hash,
    viewport: {
      width: $(window).width(),
      height: $(window).height()
    },
    resizeTimer: null,
    nc: {
      current: ""
    },

    getViewport: function() {
      moduleNeticrmNewsletter.viewport.width = $(window).width();
      moduleNeticrmNewsletter.viewport.height = $(window).height();
    },

    getURLParameter: function(name) {
      return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
    },

    objToQueryString: function(obj) {
      var queryString = Object.keys(obj).map(function(key) {
          return key + '=' + obj[key]
      }).join('&');

      return queryString;
    },

    consoleObj: function(obj) {
      if (typeof obj === "object") {
        console.log(JSON.parse(JSON.stringify(obj)));
      }
    },

    updateUrlWithoutRefresh: function(url, pageTitle, state) {
      var url = typeof url !== "undefined" ? url : "",
          pageTitle = typeof pageTitle !== "undefined" ? url : null,
          state = typeof state !== "undefined" && typeof state === "object" ? state : null;
      
      if (url) {
        if (history.pushState) {
          history.pushState(state, pageTitle, url);
        }
        else {
          location.hash = url;
        }
      }   
    },

    xeditable: function() {
      console.log("wow");
      if (moduleNeticrmNewsletter.path == '/civicrm/mailing/send') {
        var testHTML = "<div class='x-editable-playground'>" +
          "<div class='items'>" +
          "<div class='item'>" +
          "<a href='#' class='js-editable' id='t1' data-type='text' data-pk='1' data-title='Enter username'>X-editable text</a>" +
          "</div>" +
          "<div class='item'>" +
          "<a href='#' class='js-editable' id='t2' data-type='textarea' data-pk='2' data-placeholder='請輸入...' data-title='Enter comments'>X-editable textarea\nis new line.</a>" +
          "</div>" +
          "<div class='item'>" +
          "<div class='js-editable' id='t3' data-type='xquill' data-pk='3' data-placeholder='請輸入...' data-title='Enter comments'>X-editable custom input: quill! Hello <b>Quill Editor</b>!!</div>" +
          "</div>" +
          "</div>" +
          "</div>";

        if ($(".demo").length) {
          //$("#compose_id").before(testHTML);
          $.fn.editable.defaults.mode = 'inline';
          $('.js-editable').editable();
        }
      }
    },

    xeditableImce: function() {
      if (moduleNeticrmNewsletter.path == '/civicrm/event/add') {
          var appFields = {}, appWindow = (top.appiFrm||window).opener || parent;
          console.log(appWindow);

        $(".crm-container").before("<div class='test-container'></div>");
        $(".test-container").append("<button class='btn js-imce-ckeditor' type='button'>插入圖片到CKEditor</button>");
        $(".test-container").append("<div id='nc-img-uwiEIq28' class='nc-img-container'><button class='nc-img-insert btn js-imce-insert' type='button'>請選擇圖片</button></div>");
        $(".nc-img-container").on("click", ".js-imce-insert", function() {
          var $this = $(this),
              $container = $this.closest(".nc-img-container"),
              ncID = $container.attr("id");

          moduleNeticrmNewsletter.nc.current = ncID;
          //var win = window.open('index.php?q=imce&app=t|sendto@moduleNeticrmNewsletter', 'testimce', 'width=640, height=480');
          //var win = window.open('index.php?q=imce&app=ckeditor|sendto@ckeditor_setFile|&CKEditorFuncNum=1', 'testimce', 'width=640, height=480');
          //var win = window.open('index.php?q=imce&app=t|sendto@ckeditor_setFile|&CKEditorFuncNum=1', 'testimce', 'width=640, height=480');
          var win = window.open('/imce&app=nc|sendto@imceCustom', 'nc_imce', 'width=640, height=480');
        });

        $(".page").on("click", ".js-imce-ckeditor", function() {
          var win = window.open('/imce&app=ckeditor|sendto@ckeditor_setFile|&CKEditorFuncNum=1', 'nc_imce', 'width=640, height=480');
        });

        // x-editable
        /*
        $(".test-container").append("<a href='#' class='js-editable' id='t1' data-type='text' data-pk='1' data-title='Enter username'>superuser</a>");
        $(".test-container").append("<button class='js-editable' id='t2' data-type='xeimce' data-pk='2' data-title='x-editable: imce'>請選擇圖片（x-editable）</button>");
        $.fn.editable.defaults.mode = 'inline';
        $('.js-editable').editable();
        */
      }
    },

    sortableInit: function(selector) {
      var el = document.getElementById(selector);
      var option = {
        // group:"child",
        animation: 150,
        handle: ".handle-drag"
        /*
        onMove: function (evt, originalEvent) {
          console.log("onMove");
          console.log(evt);
          if (evt.related.dataset.sortable == "false") {
            console.log("y");
            console.log(sortable);
            sortable.option("disabled", true);
            sortable.options.disabled = true;
            console.log(sortable);
          }
          else {
            console.log("n");
            sortable.option("disabled", false);
          }
          // Example: http://jsbin.com/tuyafe/1/edit?js,output
          //console.log("evt.dragged"); // dragged HTMLElement
          //console.log(evt.dragged); // dragged HTMLElement
          //evt.draggedRect; // TextRectangle {left, top, right и bottom}
          //console.log("evt.related"); // HTMLElement on which have guided
          //console.log(evt.related); // HTMLElement on which have guided
          //evt.relatedRect; // TextRectangle
          //originalEvent.clientY; // mouse position
          // return false; — for cancel
        },
        */
        /*
        onEnd: function (evt) {
          console.log("onEnd");
          console.log(evt);
          
          var itemEl = evt.item;  // dragged HTMLElement
          evt.to;    // target list
          evt.from;  // previous list
          evt.oldIndex;  // element's old index within old parent
          evt.newIndex;  // element's new index within new parent
        },
        */
        /*
        onSort: function (evt) {
          console.log("onSort");
          order = sortable.toArray();
          console.log(order);
        }
        */
      };
      var sortable = Sortable.create(el, option);
      var order = sortable.toArray();
      console.log(order);
    },

    nmDEMO: function() {
      moduleNeticrmNewsletter.xeditable();
      
      // nm panel
      $(".nm-setting-panels").on("click", ".nm-setting-panels-trigger", function() {
        var $panels = $(".nm-setting-panels");
        if ($panels.hasClass("is-opened")) {
          $panels.removeClass("is-opened");
          $("body").removeClass("nme-panel-is-opened");
        }
        else {
          $panels.addClass("is-opened");
          $("body").addClass("nme-panel-is-opened");
        }
      });
      /*
      var el = document.getElementById('items');
      var sortable = Sortable.create(el);

      var example1 = document.getElementById('example1');
      new Sortable(example1, {
        animation: 150,
        ghostClass: 'blue-background-class'
      });
      */

      var example2 = document.getElementById('nm-blocks');
      new Sortable(example2, {
        animation: 150,
        ghostClass: 'blue-background-class',
        draggable: ".nm-block",
        dragClass: "handle-drag"
      });
    },

    rwdEvent: function(vw) {
      var $body = $("body");
      var $header = $("#header");

      if (vw > 1199) {
      }
      else {
      }
    },

    windowResize: function() {
      // console.log("window resize");
      // console.log(moduleNeticrmNewsletter.viewport);
      moduleNeticrmNewsletter.getViewport();
      moduleNeticrmNewsletter.rwdEvent(moduleNeticrmNewsletter.viewport.width);
    },

    init: function() {
      //moduleNeticrmNewsletter.rwdEvent(moduleNeticrmNewsletter.viewport.width);
      console.log("moduleNeticrmNewsletter init");
      moduleNeticrmNewsletter.nmDEMO();
    }
  };

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.newsletterCustom = {
    attach: function (context, settings) {
      // Do your stuff!
      // document ready
      $(document).ready(function() {
        moduleNeticrmNewsletter.init();
      });

      // window resize
      $(window).resize(function() {
        clearTimeout(moduleNeticrmNewsletter.resizeTimer);
        moduleNeticrmNewsletter.resizeTimer = setTimeout(moduleNeticrmNewsletter.windowResize, 250);
      });

      // window load
      $(window).load(function() {
        // moduleNeticrmNewsletter.addthisCount();
      });
    },
    detach: function (context, settings, trigger) {
      // Undo something.
    }
  };

})(jQuery, Drupal, this, this.document);