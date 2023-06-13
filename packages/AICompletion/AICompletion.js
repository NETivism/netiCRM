(function($) {
  'use strict';

  /**
   * ============================
   * Private static constants
   * ============================
   */

  const INIT_CLASS = 'is-initialized',
        ACTIVE_CLASS = 'is-active',
        MFP_ACTIVE_CLASS = 'mfp-is-active';

  /**
   * ============================
   * Private variables
   * ============================
   */

  var defaultData = {},
      ts = {}, // TODO: need to get translation string comparison table
      endpoint = { // TODO: Need to change to real endpoint, refer to CRM/AI/Page/AJAX.php
        getTemplateList: '/api/getTemplateList',
        getTemplate: '/api/getTemplate',
        setTemplate: '/api/setTemplate',
        chat: '/api/chat',
        setShare: '/api/setShare',
        devel: '/openai-stream/stream.php',
      };

  // Default configuration options
  var defaultOptions = {};

  /**
   * ============================
   * Private functions
   * ============================
   */

  var renderID = function(str, len) {
    var str = typeof str !== "undefined" ? str : "";
    var len = typeof len !== "undefined" ? len : 10;
    var allow = "abcdefghijklmnopqrstuvwxyz0123456789";
    var output = "";

    if (str) {
      output = str + "-";
    }

    for (var i = 0; i < len; i++) {
      output += allow.charAt(Math.floor(Math.random() * allow.length));
    }

    if (output) {
      return output;
    }
  }

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
          let modalHtml = `<div class="netiaic-modal mfp-hide">
            <div class="inner">
              <div class="netiaic-modal-header">
                <div class="netiaic-modal-title"></div>
                <button type="button" class="netiaic-modal-close"><i class="zmdi zmdi-close"></i></button>
              </div>
              <div class="netiaic-modal-content"></div>
            </div>
          </div>`;

          $container.append(modalHtml);
        }

        $container.find('.netiaic-modal').on('click', '.netiaic-modal-close', function() {
          modal.close();
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

    getTemplateList: function(page) {
      sendAjaxRequest(endpoint.getTemplateList, 'POST', { page: page }, function(response) {
        // TODO: Process the list of templates according to the data returned by the API
      });
    },

    setTemplate: function() {
      // TODO: Create a modal dialog for saving a Template based on the functional requirements
    },

    formIsEmpty: function() {
      let $container = AICompletion.prototype.container,
          roleSelectData = $container.find('.netiaic-prompt-role-select').select2('data'),
          toneSelectData = $container.find('.netiaic-prompt-tone-select').select2('data'),
          hasRoleSelected = roleSelectData.length > 0 && roleSelectData[0].id !== "",
          hasToneSelected = toneSelectData.length > 0 && toneSelectData[0].id !== "",
          hasContent = $container.find('.netiaic-prompt-content-textarea').val().trim() !== '';

      if (hasRoleSelected || hasToneSelected || hasContent) {
        return false;
      }

      return true;
    },

    applyTemplateToForm: function({ data = {} } = {}) {
      let $container = AICompletion.prototype.container,
          $roleSelect = $container.find('.netiaic-prompt-role-select'),
          $toneSelect = $container.find('.netiaic-prompt-tone-select'),
          $content = $container.find('.netiaic-prompt-content-textarea');

      if (Object.keys(data).length === 0) {
        data = { role: null, tone: null, content: null };
      }

      $roleSelect.val(data.role).trigger('change');
      $toneSelect.val(data.tone).trigger('change');
      $content.val(data.content);
    },

    useTemplates: function() {
      // TODO: Create a modal dialog and related elements based on the functional requirements
      let $container = AICompletion.prototype.container,
          modal = AICompletion.prototype.modal;

      $container.on('click', '.use-default-template', function(e) {
        e.preventDefault();
        let templateData = defaultData.templates_default[0];

        if (!AICompletion.prototype.formIsEmpty()) {
          if (confirm(ts['Warning! Applying this template will clear your current settings. Proceed with the application?'])) {
            AICompletion.prototype.applyTemplateToForm({ data: templateData });
          }
        }
        else {
          AICompletion.prototype.applyTemplateToForm({ data: templateData });
        }
      });

      $container.on('click', '.use-other-templates', function(e) {
        e.preventDefault();
        let modalTitle = ts['AI-generated Text Templates'],
            modalCallbacks = {},
            modalContent = `<div id="use-other-templates-tabs" class="modal-tabs">
            <ul class="modal-tabs-menu">
              <li><a href="#use-other-templates-tabs-1">${ts['Saved Templates']}</a></li>
              <li><a href="#use-other-templates-tabs-2">${ts['Community Recommendations']}</a></li>
            </ul>
            <div class="modal-tabs-panels">
              <div id="use-other-templates-tabs-1" class="modal-tabs-panel">
              </div>
              <div id="use-other-templates-tabs-2" class="modal-tabs-panel">
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
    },

    createResponse: function(id, data, mode) {
      let $container = AICompletion.prototype.container,
          output = '';

      if (!$container.find('.response[data-id="' + id + '"]').length) {
        output = `<div data-id="${id}" class="response msg">
          <div class="msg-avatar"><i class="zmdi zmdi-mood"></i></div>
          <div class="msg-content speech-bubble speech-bubble-left">${data}</div>
          </div>`;
        $container.find('.netiaic-chat > .inner').append(output);
      }
      else {
        if (mode == 'stream') {
          $container.find('.response[data-id="' + id + '"] .msg-content').append(data);
        }
      }
    },

    sendPrompt: function() {
      // TODO: Get prompt data from the form
      let responseID = "response-" + renderID(),
          evtSource = new EventSource(endpoint.devel, {
            withCredentials: false,
          });

      evtSource.onmessage = (event) => {
        if (event.data === '[DONE]' || event.data === '[ERR]') {
          evtSource.close();
        }
        else {
          let json = JSON.parse(event.data);

          if (typeof json !== undefined && json.message.length) {
            AICompletion.prototype.createResponse(responseID, json.message.replace(/\n/g, '<br>'), 'stream');
          }
        }
      };
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

    formUI: function() {
      let $container = AICompletion.prototype.container,
          $promptContent = $container.find(".netiaic-prompt-content-textarea"),
          $promptContentCommand = $container.find(".netiaic-prompt-content-command");

      // Populate the select dropdowns with options from defaultData.filters
      for (let selectName in defaultData.filters) {
        if ($container.find(`select[name="netiaic-prompt-${selectName}"]`).length) {
          defaultData.filters[selectName].forEach(option => {
            $container.find(`select[name="netiaic-prompt-${selectName}"]`).append(`<option value="${option}">${option}</option>`);
          });
        }
      }

      // Initialize the select dropdowns with Select2 plugin
      $container.find('.form-select').select2({
        allowClear: true,
        dropdownAutoWidth: true
      });

      $promptContent.on('focus', function() {
        let inputText = $(this).val();

        if (inputText === '') {
          $promptContentCommand.addClass(ACTIVE_CLASS);
        }
      });

      $promptContent.on('blur', function() {
        setTimeout(function() {
          $promptContentCommand.removeClass(ACTIVE_CLASS);
        }, 300);
      });

      $promptContent.on('input', function() {
        let inputText = $(this).val();
        $promptContentCommand.toggleClass(ACTIVE_CLASS, inputText === '');
      });

      $promptContentCommand.find('[data-name="org_info"] .netiaic-command-item-desc').html(defaultData.org_info);
      $promptContentCommand.on('click', '.get-org-info', function(e) {
        e.preventDefault;

        if ($promptContent.val() === '') {
          $promptContent.val(defaultData.org_info);
        }
      });

      $promptContentCommand.on('hover', function() {
        $promptContentCommand.addClass(ACTIVE_CLASS);
      });

      $('.netiaic-form-submit').on('click', function(e) {
        e.preventDefault;
        AICompletion.prototype.formSubmit();
      });
    },

    formSubmit: function() {
      // TODO: need to handle related events after the form is sent
      AICompletion.prototype.sendPrompt();
    },

    getDefaultData: function() {
      var data = {};

      // The test data is only used for development and needs to call the real endpoint
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
            'role': '募款專家',
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
      ts = window.AICompletion.translation;
      this.formUI();
      this.modal.init();
      this.useTemplates();
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
