(function($) {
  'use strict';

  /**
   * ============================
   * Private static constants
   * ============================
   */

  const INIT_CLASS = 'is-initialized',
        MFP_ACTIVE_CLASS = 'mfp-is-active';

  /**
   * ============================
   * Private variables
   * ============================
   */

  // Endpoint
  var endpoint = {
    getTemplateList: '/api/getTemplateList',
    getTemplate: '/api/getTemplate',
    setTemplate: '/api/setTemplate',
    chat: '/api/chat',
    setShare: '/api/setShare'
  };

  // Default configuration options
  var defaultOptions = {},
      defaultData = {};

  /**
   * ============================
   * Private functions
   * ============================
   */

  var sendAjaxRequest = function(url, method, data, callback) {
    $.ajax({
      url: url,
      method: method,
      data: JSON.stringify(data),
      contentType: 'application/json',
      success: function(response) {
        callback(response);
      },
      error: function(xhr, status, error) {
        console.error('AJAX request failed:', status, error);
      }
    });
  }

  // Plugin constructor function
  var AICompletion = function(element, options) {
    this.element = element;
    this.settings = $.extend({}, defaultOptions, options);
    this.init();
  };

  // Plugin methods and functionality
  AICompletion.prototype = {
    constructor: AICompletion,
    container: null,

    modal: {
      initialized: false,
      init: function() {
        let $container = AICompletion.prototype.container,
            modal = AICompletion.prototype.modal;

        if (!$container.find('.netiaic-modal').length) {
          let modal = `<div class="netiaic-modal mfp-hide">
            <div class="inner">
              <div class="netiaic-modal-header">
                <div class="netiaic-modal-title"></div>
                <button type="button" class="netiaic-modal-close"><i class="zmdi zmdi-close"></i></button>
              </div>
              <div class="netiaic-modal-content"></div>
            </div>
          </div>`;

          $container.append(modal);
        }

        $container.find('.netiaic-modal').on('click', '.netiaic-modal-close', function() {
          modal.close();
        });

        $container.on('click', '.use-other-templates', function(e) {
          e.preventDefault();
          let modalTitle = '電子報生成範本',
              modalCallbacks = {},
              modalContent = `<div id="use-other-templates-tabs" class="modal-tabs">
              <ul class="modal-tabs-menu">
                <li><a href="#use-other-templates-tabs-1">紀錄範本</a></li>
                <li><a href="#use-other-templates-tabs-2">社群推薦</a></li>
              </ul>
              <div class="modal-tabs-panels">
                <div id="use-other-templates-tabs-1" class="modal-tabs-panel">
                  <p>紀錄範本的清單</p>
                </div>
                <div id="use-other-templates-tabs-2" class="modal-tabs-panel">
                  <p>社群推薦的範本清單</p>
                </div>
              </div>
            </div>`;

          modalCallbacks.open = function() {
            if (typeof $.ui !== 'undefined' && $.ui.tabs !== 'undefined') {
              $('#use-other-templates-tabs').tabs({
                collapsible: true
              });
            }
          }

          modal.open(modalContent, modalTitle, modalCallbacks);
        });


        $container.find('.netiaic-modal').addClass(INIT_CLASS);
        this.initialized = true;
      },
      open: function(content, title, callbacks) {
        $.magnificPopup.open({
          items: {
            src: '.netiaic-modal'
          },
          type: 'inline',
          mainClass: 'mfp-netiaic-modal',
          preloader: true,
          showCloseBtn: false,
          callbacks: {
            open: function() {
              if (!content) content = '';
              if (!title) title = '';

              $('body').addClass(MFP_ACTIVE_CLASS);
              $('.netiaic-modal-content').html(content);
              $('.netiaic-modal-title').html(title);

              if (callbacks && typeof callbacks === 'object' && typeof callbacks.open === 'function') {
                callbacks.open();
              }
            },
            close: function() {
              $('body').removeClass(MFP_ACTIVE_CLASS);
            },
          }
        });
      },
      close: function() {
        $.magnificPopup.close();
      }
    },

    getDefaultTemplate: function() {
      sendAjaxRequest(endpoint.getDefaultTemplate, 'POST', null, function(response) {
        // TODO: Process and fill the data according to the API response
      });
    },

    applyTemplate: function(templateData) {
      // TODO: Apply the template data to the form
    },

    setTemplate: function() {
      // TODO: Create a modal dialog for saving a Template based on the functional requirements
    },

    showAllTemplates: function() {
      // TODO: Create a modal dialog and related elements based on the functional requirements
    },

    getTemplateList: function(page) {
      sendAjaxRequest(endpoint.getTemplateList, 'GET', { page: page }, function(response) {
        // TODO: Process the list of templates according to the data returned by the API
      });
    },

    getOrganizationIntro: function() {
      sendAjaxRequest(endpoint.getOrganizationIntro, 'POST', null, function(response) {
        // TODO: Process and fill the data according to the API response
      });
    },

    sendPrompt: function() {
      var data = {};
      // TODO: Get prompt data from the form

      sendAjaxRequest(endpoint.chat, 'POST', data, function(response) {
        // TODO: Process and display the result based on the API response
      });
    },

    answerResponse: function(answer) {
      // TODO: Display the AI response on the interface
    },

    recommendTemplate: function() {
      // TODO: Create a modal dialog for recommending Template based on the functional requirements
      var data = {};

      sendAjaxRequest(endpoint.setShare, 'POST', data, function(response) {
        // TODO: Process and display the result based on the API response
      });
    },

    checkQuotaUsage: function() {
      sendAjaxRequest(endpoint.quota, 'POST', null, function(response) {
        // TODO: Process the quota usage information based on the API response
      });
    },

    renderSelects: function() {
      // TODO: Render the selects based on the default data
      for (const selectName in defaultData.filters) {
        if ($(`select[name="netiaic-prompt-${selectName}"]`).length) {
          defaultData.filters[selectName].forEach(option => {
            $(`select[name="netiaic-prompt-${selectName}"]`).append(`<option value="${option}">${option}</option>`);
          });
        }
      }

      $(".netiaic-form-container .form-select").select2({
        "allowClear": true,
        "dropdownAutoWidth": true
      });
    },

    getDefaultData: function() {
      var data = {};

      // Test data
      data = {
        'org_info': '本組織成立於 19xx 年，致力於環境保育與自然生態導覽，為了這塊土地的...',
        'usage': {
          'max': 10,
          'used': 3
        },
        'templates_default': [
          {
            'id': '1',
            'created': 1677649420,
            'changed': 1685592993,
            'contact_id': '20',
            'title': '範本A',
            'type': '預設範本',
            'role': '募款專員',
            'tone': '幽默',
            'content': '拿出你的傘、拿出你的畫筆，還有一份豪華的環境藝術計畫！從2006年開始舉辦的這個計畫...'
          }
        ],
        "filters": {
          "role": [
            "募款專家",
            "活動達人",
            "電子報行銷大師"
          ],
          "tone": [
            "放鬆",
            "深情",
            "幽默",
            "細膩",
            "熱情"
          ]
        }
      }

      return data;
    },

    init: function() {
      var $container = $(this.element);
      defaultData = this.getDefaultData();

      AICompletion.prototype.container = $container;
      this.renderSelects();
      this.modal.init();
      $container.addClass(INIT_CLASS);
    }
  };

  // Plugin definition
  $.fn.AICompletion = function(options) {
    return this.each(function() {
      var instance = $.data(this, 'aicompletion');
      if (!instance) {
        instance = new AICompletion(this, options);
        $.data(this, 'aicompletion', instance);
      }
    });
  };
})(jQuery);
