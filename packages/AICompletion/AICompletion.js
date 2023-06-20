(function($) {
  'use strict';

  /**
   * ============================
   * Private static constants
   * ============================
   */

  const INIT_CLASS = 'is-initialized',
        ACTIVE_CLASS = 'is-active',
        ERROR_CLASS = 'is-error',
        SENT_CLASS = 'is-sent',
        FINISH_CLASS = 'is-finished',
        COPY_CLASS = 'is-copied',
        MFP_ACTIVE_CLASS = 'mfp-is-active';

  /**
   * ============================
   * Private variables
   * ============================
   */

  var defaultData = {},
      ts = {},
      endpoint = { // TODO: Need to change to real endpoint, refer to CRM/AI/Page/AJAX.php
        getTemplateList: '/api/getTemplateList',
        getTemplate: '/api/getTemplate',
        setTemplate: '/api/setTemplate',
        chat: '/api/chat',
        setShare: '/api/setShare',
        devel: '/openai-stream/stream.php',
      },
      chatData = {
        messages: []
      };

  // Default configuration options
  var defaultOptions = {};

  /**
   * ============================
   * Private functions
   * ============================
   */

  var isObject = function(variable) {
    return typeof variable === 'object' && variable !== null;
  }

  var renderID = function(str, len) {
    var str = typeof str !== "undefined" ? str : "",
        len = typeof len !== "undefined" ? len : 10,
        allow = "abcdefghijklmnopqrstuvwxyz0123456789",
        output = "";

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
  }

  // Plugin methods and functionality
  AICompletion.prototype = {
    constructor: AICompletion,
    container: null,

    // TODO: For development and testing only, need to be removed afterwards
    devTestUse: function() {
      let $container = AICompletion.prototype.container;

      if ($container.find('.usage-max').length) {
        $container.find('.usage-max').text(defaultData.usage.max);
      }

      if ($container.find('.usage-current').length) {
        $container.find('.usage-current').text(defaultData.usage.current);
      }
    },

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

      $container.on('click', '.use-default-template', function(event) {
        event.preventDefault();
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

      $container.on('click', '.use-other-templates', function(event) {
        event.preventDefault();
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

    createMessage: function(id, data, type, mode) {
      let $container = AICompletion.prototype.container,
          msg = '',
          output = '';

      if (!$container.find('.msg[id="' + id + '"]').length) {
        if (type == 'user') {
          if (isObject(data)) {
            if (data.role) {
              msg += `${ts['Role']}: ${data.role}\n`;
            }

            if (data.tone) {
              msg += `${ts['Tone Style']}: ${data.tone}\n\n`;
            }

            if (data.content) {
              msg += `${ts['Content']}: ${data.content}\n`;
            }

            msg = msg.replace(/\n/g, '<br>');
          }
          else {
            msg = data;
          }

          output = `<div id="${id}" class="user-msg msg is-finished">
            <div class="msg-avatar"></div>
            <div class="msg-content">${msg}</div>
            <ul class='msg-tools'>
              <li><button type="button" title="${ts['Save As New Template']}" class="save-btn handle-btn"><i class="zmdi zmdi-file-plus"></i> ${ts['Save As New Template']}</button></li>
              <li><button type="button" title="${ts['Recommend']}" class="recommend-btn handle-btn"><i class="zmdi zmdi-accounts-alt"></i> ${ts['Recommend']}</button></li>
            </ul>
            </div>`;
        }

        if (type == 'ai') {
          msg = data;
          output = `<div id="${id}" class="ai-msg msg">
            <div class="msg-avatar"><i class="zmdi zmdi-mood"></i></div>
            <div class="msg-content">${msg}</div>
            <ul class='msg-tools'>
              <li><button type="button" title="${ts['Copy']}" class="copy-btn handle-btn"><i class="zmdi zmdi-copy"></i> ${ts['Copy']}</button></li>
            </ul>
            </div>`;
        }

        if (output.trim != '') {
          $container.find('.netiaic-chat > .inner').append(output);
        }
      }
      else {
        if (mode == 'stream') {
          $container.find('.msg[id="' + id + '"] .msg-content').append(data);
        }
      }
    },

    recommendTemplate: function() {
      // TODO: Create a modal dialog for recommending Template based on the functional requirements
      let data = {};

      sendAjaxRequest(endpoint.setShare, 'POST', data, function(response) {
        // TODO: Process and display the result based on the API response
      });
    },

    usageUpdate: function() {
      let $container = AICompletion.prototype.container,
          $usageCurrent = $container.find('.usage-current');

      if ($usageCurrent.length) {
        let usageCurrent = parseInt($usageCurrent.text(), 10);
        usageCurrent++;
        $usageCurrent.text(usageCurrent);
      }
    },

    formUiOperation: function() {
      let $container = AICompletion.prototype.container,
          $promptContent = $container.find('.netiaic-prompt-content-textarea'),
          $promptContentCommand = $container.find('.netiaic-prompt-content-command'),
          $submit = $container.find('.netiaic-form-submit');

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
      $promptContentCommand.on('click', '.get-org-info', function(event) {
        event.preventDefault;

        if ($promptContent.val() === '') {
          $promptContent.val(defaultData.org_info);
        }
      });

      $promptContentCommand.on('hover', function() {
        $promptContentCommand.addClass(ACTIVE_CLASS);
      });

      $submit.on('click', function(event) {
        event.preventDefault;
        AICompletion.prototype.formSubmit();
      });
    },

    formSubmit: function() {
      let userMsgID = 'user-msg-' + renderID(),
          aiMsgID = 'ai-msg-' + renderID(),
          evtSource = new EventSource(endpoint.devel, { // TODO: Need to be replaced with a real endpoint
            withCredentials: false,
          }),
          $container = AICompletion.prototype.container,
          $submit = $container.find('.netiaic-form-submit'),
          $aiMsg,
          formData = {
            role: $container.find('.netiaic-prompt-role-select').val(),
            tone: $container.find('.netiaic-prompt-tone-select').val(),
            content: $container.find('.netiaic-prompt-content-textarea').val()
          };

      if (!$submit.hasClass(ACTIVE_CLASS)) {
        $submit.addClass(ACTIVE_CLASS).prop('disabled', true);
      }

      // Create user's message
      AICompletion.prototype.createMessage(userMsgID, formData, 'user');

      // Create AI's message
      evtSource.onmessage = (event) => {
        if (event.data === '[DONE]' || event.data === '[ERR]') {
          evtSource.close();

          if ($submit.hasClass(ACTIVE_CLASS)) {
            $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
          }

          if (!$submit.hasClass(SENT_CLASS)) {
            $submit.addClass(SENT_CLASS).find('.text').text(ts['Try Again']);
          }

          if (event.data === '[DONE]') {
            if (!$aiMsg.hasClass(FINISH_CLASS)) {
              $aiMsg.addClass(FINISH_CLASS);
            }
          }

          if (event.data === '[ERR]') {
            if (!$aiMsg.hasClass(ERROR_CLASS)) {
              $aiMsg.addClass(ERROR_CLASS);
            }
          }

          if ($aiMsg.length) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              try {
                $aiMsg.on('click', '.copy-btn', function(event) {
                  event.preventDefault();
                  let $copyBtn = $(this),
                      $aiMsg = $copyBtn.closest('.msg'),
                      $msgContent = $aiMsg.find('.msg-content'),
                      copyText = '';

                  if ($msgContent.length) {
                    copyText = $msgContent.html().replace(/<br>/g, '\n');
                  }

                  copyText = copyText.trim();
                  navigator.clipboard.writeText(copyText);
                  $copyBtn.addClass(COPY_CLASS);

                  setTimeout(function() {
                    $copyBtn.removeClass(COPY_CLASS);
                  }, 3000);
                });
              } catch (error) {
                console.error('Failed to copy text to clipboard:', error);
              }
            } else {
              console.error('Clipboard API is not supported in this browser.');
            }
          }
        }
        else {
          let json = JSON.parse(event.data);

          if (typeof json !== "undefined" && json.message.length) {
            let message = json.message.replace(/\n/g, '<br>');
            AICompletion.prototype.createMessage(aiMsgID, message, 'ai', 'stream');
            $aiMsg = $container.find('.msg[id="' + aiMsgID + '"]');

            if (chatData.messages.hasOwnProperty(aiMsgID)) {
              if (!chatData.messages[aiMsgID].used) {
                AICompletion.prototype.usageUpdate();
              }
            }
            else {
              chatData.messages[aiMsgID] = {
                used: true
              }

              AICompletion.prototype.usageUpdate();
            }
          }
        }
      };
    },

    getDefaultData: function() {
      var data = {};

      // The test data is only used for development and needs to call the real endpoint
      data = {
        'org_info': '本組織成立於 19xx 年，致力於環境保育與自然生態導覽，為了這塊土地的...',
        'usage': {
          'max': 1000,
          'current': 3
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
            'content': '拿出你的傘、拿出你的畫筆，還有一份豪華的環境藝術計畫！從2006年開始舉辦的這個計畫...\n\n第二段拿出你的傘、拿出你的畫筆，還有一份豪華的環境藝術計畫！從2006年開始舉辦的這個計畫'
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

      // Get translation string comparison table
      ts = window.AICompletion.translation;

      // TODO: For development and testing only, need to be removed afterwards
      this.devTestUse();

      // Implement and install main features and functions
      this.modal.init();
      this.formUiOperation();
      this.useTemplates();

      // Finally, add class to mark the initialization
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
