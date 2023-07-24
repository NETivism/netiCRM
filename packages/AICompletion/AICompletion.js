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
        PROCESS_CLASS = 'is-processing',
        FINISH_CLASS = 'is-finished',
        EXPAND_CLASS = 'is-expanded',
        COPY_CLASS = 'is-copied',
        MFP_ACTIVE_CLASS = 'mfp-is-active',
        TIMEOUT = 10;

  /**
   * ============================
   * Private variables
   * ============================
   */

  var defaultData = {},
    templateListData = {
      savedTemplates: [],
      communityRecommendations: []
    },
    ts = {},
    endpoint = {
      getTemplateList: '/civicrm/ajax/ai/get-template-list',
      getTemplate: '/civicrm/ajax/ai/get-template',
      setTemplate: '/civicrm/ajax/ai/set-template',
      chat: '/civicrm/ajax/ai/chat',
      setShare: '/civicrm/ajax/ai/set-share',
    },
    chatData = {
      messages: []
    },
    colon = ':',
    errorMessage,
    errorMessageDefault;

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

  var sendAjaxRequest = function(url, method, data, successCallback, errorCallback) {
    $.ajax({
      url: url,
      method: method,
      data: JSON.stringify(data),
      contentType: 'application/json',
      timeout: TIMEOUT,
      success: function(response) {
        successCallback(response);
      },
      error: function(xhr, status, error) {
        if (status === "timeout") {
          console.error('AJAX request timed out');
        }
        else {
          console.error('AJAX request failed:', status, error);
        }

        errorCallback(xhr, status, error);
      }
    });
  }

  var countCharacters = function(text) {
    return Array.from(text).length;
  }

  var fallbackCopyTextToClipboard = function(text, $copyBtn) {
    let textArea = document.createElement("textarea");
    textArea.value = text;

    // Make the textarea not appear on the user's screen
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.width = '0';
    textArea.style.height = '0';
    textArea.style.opacity = '0';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      let successful = document.execCommand('copy'),
          msg = successful ? 'successful' : 'unsuccessful';
      console.log('Fallback: Copying text command was ' + msg);

      if (successful && $copyBtn.length) {
        toggleCopyClass($copyBtn);
      }
    } catch (error) {
      console.error('Fallback: Oops, unable to copy', error);
    }

    document.body.removeChild(textArea);
  }

  var toggleCopyClass = function($copyBtn) {
    if ($copyBtn.length) {
      $copyBtn.addClass(COPY_CLASS);

      setTimeout(function() {
        $copyBtn.removeClass(COPY_CLASS);
      }, 3000);
    }
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

      if ($container.find('.usage-used').length) {
        $container.find('.usage-used').text(defaultData.usage.used);
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

    contentScrollBottom: function(delay = 250) {
      setTimeout(function() {
        $(".netiaic-chat").scrollTop(function() { return this.scrollHeight; });
      }, delay);
    },

    promptContentCounterUpdate: function($elem) {
      if ($elem.length) {
        let textLength = countCharacters($elem.val()),
            $section = $elem.closest('.crm-section'),
            $desc = $elem.next('.description'),
            $current = $desc.find('.current'),
            limitMax = 1500;

        $current.text(textLength);

        if (textLength > limitMax) {
          if (!$section.hasClass(ERROR_CLASS)) {
            $section.addClass(ERROR_CLASS);
          }

          if (!$desc.hasClass(ERROR_CLASS)) {
            $desc.addClass(ERROR_CLASS);
          }
        }
        else {
          $section.removeClass(ERROR_CLASS);
          $desc.removeClass(ERROR_CLASS);
        }
      }
    },

    getTemplateList: function(data = {}) {
      let output = `<p>${ts['There are currently no templates available.']}</p>`;

      for (let key in templateListData) {
        if (key == 'savedTemplates') {
          sendAjaxRequest(endpoint.getTemplateList, 'POST', data, function(response) {
            if (response.status == 'success' || response.status == 1) {
              if (response.data) { // TODO: check data
                templateListData[key] = response.data;
                output = `<div class="template-list">`;

                for (let i in templateListData[key]) {
                  let data = templateListData[key][i],
                      prompt = JSON.parse(data.prompt);
                  console.log(prompt);
                  output += `<div class="template-item" data-ai-role="${data.ai_role}" data-tone-style="${data.tone_style}" data-context="${data.context}">
                      <div class="inner">
                        <div class="ai-role"><span class="label">${ts['Copywriting Role']}</span>${colon}${data.ai_role}</div>
                        <div class="tone-style"><span class="label">${ts['Tone Style']}</span>${colon}${data.tone_style}</div>
                        <div class="context"><span class="label">${ts['Content Summary']}</span>${colon}${data.context}</div>
                        <div class="actions">
                          <button type="button" class="apply-btn btn">${ts['Apply Template']}</button>
                        </div>
                      </div>
                    </div>`;
                }

                output += `</div>`;
              }
            }

            $(`#use-other-templates-tabs .modal-tabs-panel[data-type="${key}"]`).html(output);

            $('.template-list').on('click', '.apply-btn', function() {
              let $templateItem = $(this).closest('.template-item'),
                  templateData = {
                    role: $templateItem.attr("data-ai-role"),
                    tone: $templateItem.attr("data-tone-style"),
                    content: $templateItem.attr("data-context")
                  };

              if (!AICompletion.prototype.formIsEmpty()) {
                if (confirm(ts['Warning! Applying this template will clear your current settings. Proceed with the application?'])) {
                  AICompletion.prototype.applyTemplateToForm({ data: templateData });
                  AICompletion.prototype.modal.close();
                }
              }
              else {
                AICompletion.prototype.applyTemplateToForm({ data: templateData });
                AICompletion.prototype.modal.close();
              }
            });
          }, function(xhr, status, error) {
            if (status === "timeout") {
              errorMessage = `<p class="error">${ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.']}</p>`;
              $(`#use-other-templates-tabs .modal-tabs-panel[data-type="${key}"]`).html(errorMessage);
            }
          });
        }

        if (key == 'communityRecommendations') {
          $(`#use-other-templates-tabs .modal-tabs-panel[data-type="${key}"]`).html(output);
        }
      }
    },

    setTemplate: function() {
      let $container = AICompletion.prototype.container,
          modal = AICompletion.prototype.modal;

      $container.on('click', '.msg-tools .save-btn:not([disabled])', function(event) {
        event.preventDefault();

        let $saveBtn = $(this),
            $userMsg = $saveBtn.closest('.msg'),
            userMsgID = $userMsg.attr('id'),
            aiMsgID = $userMsg.attr('data-ref-id'),
            $aiMsg = $userMsg.next('.msg'),
            aicompletionID = $aiMsg.data('aicompletion-id'),
            modalTitle = ts['Save prompt as shared template'],
            modalCallbacks = {},
            modalContent = `<div class="set-tpl-form" data-id="${userMsgID}">
              <div class="desc"><p>${ts['Once saved as a shared template, you can reuse this template for editing. Please enter a template title to identify the purpose of the template. If you need to edit a shared template, please go to the template management interface to edit.']}</p></div>
                <div class="netiaic-save-tpl-title-section crm-section crm-text-section form-item">
                  <div class="label"><label for="set-tpl-title">${ts['Title']}</label></div>
                  <div class="edit-value content">
                    <div class="crm-form-elem crm-form-textfield">
                      <input name="set-tpl-title" type="text" id="set-tpl-title" class="form-text">
                    </div>
                  </div>
                </div>
                <div class="form-actions">
                  <button id="set-tpl-submit" type="button" class="set-tpl-submit form-submit">${ts['Save']}</button>
                </div>
              </div>`;

        if (aicompletionID) {
          modalCallbacks.open = function() {
            $('.set-tpl-form').on('click', '.set-tpl-submit:not([disabled])', function(event) {
              event.preventDefault();

              let $submit = $(this),
                  $container = $submit.closest('.set-tpl-form'),
                  $tplTitle = $container.find('.form-text[name="set-tpl-title"]'),
                  tplTitle = $tplTitle.val(),
                  data = {
                    id: aicompletionID,
                    is_template: '1',
                    template_title: tplTitle
                  };

              sendAjaxRequest(endpoint.setTemplate, 'POST', data, function(response) {
                if (response.status == 'success' || response.status == 1) {
                  $tplTitle.prop('readonly', true);
                  $submit.text(ts['Saved']).prop('disabled', true);
                  $saveBtn.html(`<i class="zmdi zmdi-check"></i>${ts['Saved']}`).prop('disabled', true);
                }
              });
            });
          }
        }

        modal.open(modalContent, modalTitle, modalCallbacks);
      });
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

    formValidate: function() {
      let $container = AICompletion.prototype.container,
          $formContainer = $container.find('.netiaic-form-container'),
          $formSubmit = $container.find('.netiaic-form-submit');

      if ($formContainer.find('.is-error').length) {
        return false;
      }

      return true;
    },

    setSelectOption: function($selectElement, value) {
      if ($selectElement.find(`option[value="${value}"]`).length) {
        $selectElement.val(value).trigger('change');
      }
      else {
        // [Create option if not exists](https://select2.org/programmatic-control/add-select-clear-items#create-if-not-exists)
        let optionData = {
          id: value,
          text: value
        };

        let newOption = new Option(optionData.text, optionData.id, true, true);
        $selectElement.append(newOption).trigger('change');
      }
    },

    applyTemplateToForm: function({ data = {} } = {}) {
      let $container = AICompletion.prototype.container,
          $roleSelect = $container.find('.netiaic-prompt-role-select'),
          $toneSelect = $container.find('.netiaic-prompt-tone-select'),
          $content = $container.find('.netiaic-prompt-content-textarea');

      if (Object.keys(data).length === 0) {
        data = { role: null, tone: null, content: null };
      }

      AICompletion.prototype.setSelectOption($roleSelect, data.role);
      AICompletion.prototype.setSelectOption($toneSelect, data.tone);
      $content.val(data.content);
      AICompletion.prototype.promptContentCounterUpdate($content);
    },

    useTemplates: function() {
      let $container = AICompletion.prototype.container,
          modal = AICompletion.prototype.modal;

      $container.on('click', '.use-default-template', function(event) {
        event.preventDefault();
        let templateData = defaultData.templates_default[0];
        if (templateData.content.match(new RegExp('^'+ts['Organization intro']))) {
          templateData.content = templateData.content.replace(new RegExp('^'+ts['Organization intro']+'.*'), ts['Organization intro']+': '+defaultData.org_intro+"\n");
        }

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
            <div id="use-other-templates-tabs-1" class="modal-tabs-panel ${PROCESS_CLASS}" data-type="savedTemplates"><p>${ts['Loading...']}</p></div>
            <div id="use-other-templates-tabs-2" class="modal-tabs-panel ${PROCESS_CLASS}" data-type="communityRecommendations"><p>${ts['Loading...']}</p></div>
            </div>
          </div>`;

        modalCallbacks.open = function() {
          AICompletion.prototype.getTemplateList();
          if (typeof $.ui !== 'undefined' && $.ui.tabs !== 'undefined') {
            $('#use-other-templates-tabs').tabs({
              collapsible: true
            });
          }
        }

        modal.open(modalContent, modalTitle, modalCallbacks);
      });
    },

    createMessage: function(id, refID, data, type, mode) {
      let $container = AICompletion.prototype.container,
          $submit = $container.find('.netiaic-form-submit'),
          msg = '',
          output = '';

      if (!$container.find('.msg[id="' + id + '"]').length) {
        if (type == 'user') {
          if (isObject(data)) {
            if (data.role) {
              msg += `${ts['Copywriting Role']}: ${data.role}\n`;
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

          output = `<div id="${id}" data-ref-id="${refID}" class="user-msg msg is-finished">
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
          output = `<div id="${id}" data-ref-id="${refID}" class="ai-msg msg">
            <div class="msg-avatar"><i class="zmdi zmdi-mood"></i></div>
            <div class="msg-content">${msg}</div>
            <ul class='msg-tools'>
              <li><button type="button" title="${ts['Copy']}" class="copy-btn handle-btn"><i class="zmdi zmdi-copy"></i> ${ts['Copy']}</button></li>
            </ul>
            <div class="msg-tip"><i class="zmdi zmdi-info-outline"></i>${ts['Remember to verify AI-generated text before using it.']}</div>
            </div>`;
        }

        if (output.trim != '') {
          $container.find('.netiaic-chat > .inner').append(output);
          AICompletion.prototype.contentScrollBottom();

          if (mode == 'error') {
            $container.find('.msg[id="' + id + '"]').addClass('error-msg');

            if ($submit.hasClass(ACTIVE_CLASS)) {
              $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
            }

            if (!$submit.hasClass(SENT_CLASS)) {
              $submit.addClass(SENT_CLASS).find('.text').text(ts['Try Again']);
            }
          }
        }
      }
      else {
        if (mode == 'stream') {
          $container.find('.msg[id="' + id + '"] .msg-content').append(data);
          AICompletion.prototype.contentScrollBottom(1000);
        }
      }
    },

    setShare: function() {
      let $container = AICompletion.prototype.container,
      modal = AICompletion.prototype.modal;

      $container.on('click', '.msg-tools .recommend-btn:not([disabled])', function(event) {
        event.preventDefault();

        let $shareBtn = $(this),
            $userMsg = $shareBtn.closest('.msg'),
            userMsgID = $userMsg.attr('id'),
            aiMsgID = $userMsg.attr('data-ref-id'),
            $aiMsg = $userMsg.next('.msg'),
            aicompletionID = $aiMsg.data('aicompletion-id'),
            modalTitle = ts['Recommend a Template to Other Organizations'],
            modalCallbacks = {},
            modalContent = `<div class="share-tpl-form" data-id="${userMsgID}">
              <div class="desc">
              <p>${ts['Upon clicking \'Recommend\', we\'ll proceed with the following verification steps:']}</p>
              <ol>
              <li>${ts['The netiCRM team will ensure the prompt does not contain any personal data and test its function to guarantee the privacy safety for you and other organizations.']}</li>
              <li>${ts['Due to the above, the results of your sharing will not appear immediately. We will schedule periodic updates and publications.']}</li>
              <li>${ts['Once published, you can view your shared template in the \'Community Recommended\' templates, which will also be marked with your organization\'s name.']}<br><img src="/sites/all/modules/civicrm/packages/AICompletion/images/example--share-tpl-screenshot@2x.jpg" alt=""></li>
              </ol>
              <p>${ts['Thank you for being willing to share your templates with the community, thereby benefiting all netiCRM users.']}</p>
              </div>
              <div class="form-actions">
              <button id="share-tpl-submit" type="button" class="share-tpl-submit form-submit">${ts['Recommend']}</button>
              </div>
              </div>`;

        if (aicompletionID) {
          modalCallbacks.open = function() {
            $('.share-tpl-form').on('click', '.share-tpl-submit:not([disabled])', function(event) {
              event.preventDefault();

              let $submit = $(this),
                  $container = $submit.closest('.share-tpl-form'),
                  data = {
                    id: aicompletionID,
                    is_share_with_others: '1',
                  };

              sendAjaxRequest(endpoint.setShare, 'POST', data, function(response) {
                if (response.status == 'success' || response.status == 1) {
                  $submit.text(ts['Recommended']).prop('disabled', true);
                  $shareBtn.html(`<i class="zmdi zmdi-check"></i>${ts['Recommended']}`).prop('disabled', true);
                }
              });
            });
          }
        }

        modal.open(modalContent, modalTitle, modalCallbacks);
      });
    },

    usageUpdate: function() {
      let $container = AICompletion.prototype.container,
          $usageUsed = $container.find('.usage-used');

      if ($usageUsed.length) {
        let usageUsed= parseInt($usageUsed.text(), 10);
        usageUsed++;
        $usageUsed.text(usageUsed);
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
        dropdownAutoWidth: true,
        width: '100%',
        tags: true // Select2 can dynamically create new options from text input by the user in the search box
      });

      $promptContent.on('focus', function() {
        let inputText = $(this).val();

        if (inputText === '') {
          $promptContentCommand.addClass(ACTIVE_CLASS);
        }

        if (this.scrollHeight > this.clientHeight && !$(this).hasClass(EXPAND_CLASS)) {
          $(this).addClass(EXPAND_CLASS);
        }
      });

      $promptContent.on('blur', function() {
        if ($(this).hasClass(EXPAND_CLASS)) {
          $(this).removeClass(EXPAND_CLASS);
        }

        setTimeout(function() {
          $promptContentCommand.removeClass(ACTIVE_CLASS);
        }, 300);
      });

      $promptContent.on('input', function() {
        let inputText = $(this).val();

        $promptContentCommand.toggleClass(ACTIVE_CLASS, inputText === '');

        if (this.scrollHeight > this.clientHeight) {
          if (!$(this).hasClass(EXPAND_CLASS)) {
            $(this).addClass(EXPAND_CLASS);
          }
        }
        else {
          $(this).removeClass(EXPAND_CLASS);
        }

        AICompletion.prototype.promptContentCounterUpdate($(this));
      });

      $promptContentCommand.find('[data-name="org_intro"] .netiaic-command-item-desc').html(defaultData.org_intro);
      $promptContentCommand.on('click', '.get-org-intro, .netiaic-command-item-desc', function(event) {
        event.preventDefault();

        if ($promptContent.val() === '') {
          $promptContent.val(defaultData.org_intro);
          AICompletion.prototype.promptContentCounterUpdate($promptContent);
        }
      });

      $promptContentCommand.on('hover', function() {
        $promptContentCommand.addClass(ACTIVE_CLASS);
      });

      $submit.on('click', function(event) {
        event.preventDefault();

        if (AICompletion.prototype.formValidate()) {
          AICompletion.prototype.formSubmit();
        }
      });
    },

    formSubmit: function() {
      let userMsgID = 'user-msg-' + renderID(),
          aiMsgID = 'ai-msg-' + renderID(),
          $container = AICompletion.prototype.container,
          $submit = $container.find('.netiaic-form-submit'),
          $aiMsg,
          formData = {
            role: $container.find('.netiaic-prompt-role-select').val(),
            tone: $container.find('.netiaic-prompt-tone-select').val(),
            content: $container.find('.netiaic-prompt-content-textarea').val(),
            sourceUrl: window.location.href,
            sourceUrlPath: window.location.pathname,
            sourceUrlQuery: window.location.search
          };

      if (!$submit.hasClass(ACTIVE_CLASS)) {
        $submit.addClass(ACTIVE_CLASS).prop('disabled', true);
      }

      // Create user's message
      AICompletion.prototype.createMessage(userMsgID, aiMsgID, formData, 'user');

      // Create AI's message
      let fetchPromise = fetch(endpoint.chat, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      let timeoutPromise = new Promise((_, reject) => {
        let timeoutId = setTimeout(() => {
          clearTimeout(timeoutId);
          reject(new Error("Network request timed out."));
        }, TIMEOUT);
      });

      Promise.race([fetchPromise, timeoutPromise])
      .then(function(response) {
        if (response.ok) {
          // Determine the data type based on the Content-Type of the response
          if (response.headers.get('Content-Type').includes('application/json')) {
            return response.json(); // Parse as JSON
          } else if (response.headers.get('Content-Type').includes('text/html')) {
            return response.text(); // Parse as plain text
          } else {
            throw new Error('Unknown response data type');
          }
        } else {
          throw new Error('Network request error');
        }
      })
      .then(function(result) {
        var evtSource = new EventSource(endpoint.chat + '?token=' + result.data.token + '&id=' + result.data.id, {
          withCredentials: false,
        });

        evtSource.onmessage = (event) => {
          try {
            let eventData = JSON.parse(event.data);
            if (typeof eventData !== "undefined") {
              if (($aiMsg && $aiMsg.length) && (eventData.hasOwnProperty('is_finished') || eventData.hasOwnProperty('is_error'))) {
                evtSource.close();

                if ($submit.hasClass(ACTIVE_CLASS)) {
                  $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
                }

                if (!$submit.hasClass(SENT_CLASS)) {
                  $submit.addClass(SENT_CLASS).find('.text').text(ts['Try Again']);
                }

                if (eventData.is_finished) {
                  if (!$aiMsg.hasClass(FINISH_CLASS)) {
                    $aiMsg.addClass(FINISH_CLASS);
                  }
                }

                if (eventData.is_error) {
                  if (!$aiMsg.hasClass(ERROR_CLASS)) {
                    $aiMsg.addClass(ERROR_CLASS);
                  }
                }

                var msgCopyHandle = function(event) {
                  event.preventDefault();
                  let $copyBtn = $(this),
                      $aiMsg = $copyBtn.closest('.msg'),
                      $msgContent = $aiMsg.find('.msg-content'),
                      copyText = '';

                  if ($msgContent.length) {
                    copyText = $msgContent.html().replace(/<br>/g, '\n');
                  }

                  copyText = copyText.trim();
                  if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(copyText).then(function() {
                      toggleCopyClass($copyBtn);
                    }, function(error) {
                      console.error('Failed to copy text to clipboard:', error);
                    });
                  } else {
                    console.warn('Clipboard API is not supported in this browser, fallback to execCommand.');
                    fallbackCopyTextToClipboard(copyText, $copyBtn);
                  }
                }

                $aiMsg.on('click', '.copy-btn', msgCopyHandle);
              }
              else {
                if (eventData.hasOwnProperty('message')) {
                  let message = eventData.message.replace(/\n/g, '<br>');
                  AICompletion.prototype.createMessage(aiMsgID, userMsgID, message, 'ai', 'stream');
                  $aiMsg = $container.find('.msg[id="' + aiMsgID + '"]');

                  if (eventData.hasOwnProperty('id')) {
                    $aiMsg.data('aicompletion-id', eventData.id);
                  }

                  // Update usage
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
            }
          } catch (error) {
            console.error('JSON Parse Error:', error.message);
            AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessageDefault, 'ai', 'error');
          }
        };

        evtSource.onerror = function(event) {
          console.error("EventSource encountered an error: ", event);
          evtSource.close();
          AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessageDefault, 'ai', 'error');
        };
      })
      .catch(function(error) {
        // TODO: Error handling
        console.error("Encountered an error: ", error);
        if (error.message.includes('timed out')) {
          errorMessage = ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.'];
        }

        AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessage, 'ai', 'error');
      });
    },

    getDefaultData: function() {
      if (typeof window.AICompletion.default === 'object') {
        return window.AICompletion.default;
      }
      return {};
    },

    init: function() {
      var $container = $(this.element);

      defaultData = this.getDefaultData();
      AICompletion.prototype.container = $container;

      // Get translation string comparison table
      ts = window.AICompletion.translation;
      colon = window.AICompletion.language == 'zh_TW' ? '：' : ':';
      errorMessageDefault = ts['We\'re sorry, our service is currently experiencing some issues. Please try again later. If the problem persists, please contact our customer service team.'];
      errorMessage = errorMessageDefault;

      // TODO: For development and testing only, need to be removed afterwards
      this.devTestUse();

      // Implement and install main features and functions
      this.modal.init();
      this.formUiOperation();
      this.useTemplates();
      this.setTemplate();
      this.setShare();

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
