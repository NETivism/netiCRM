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
        PROCESS_CLASS = 'is-processing',
        FINISH_CLASS = 'is-finished',
        EXPAND_CLASS = 'is-expanded',
        COPY_CLASS = 'is-copied',
        SHOW_CLASS = 'is-show',
        HIDE_CLASS = 'is-hide',
        MFP_ACTIVE_CLASS = 'mfp-is-active',
        STATE_INITIAL_CLASS = 'is-state-initial',
        STATE_ACTIVE_CLASS = 'is-state-active',
        OPEN_CLASS = 'is-open',
        SELECTED_CLASS = 'is-selected',
        EMPTY_CLASS = 'is-empty',
        FILTER_PILLS_CLASS = 'has-filter-pills',
        FILTER_EVENT_NS = '.netiaic-filter',
        DROPPED_CLASS = 'is-dropped',
        TURN_LIMIT_CODE = 'CONVERSATION_TURN_LIMIT',
        MENU_MIN_HEIGHT = 120,
        MENU_MAX_HEIGHT = 280,
        TITLE_MAX_LENGTH = 20,
        HTTP_FORBIDDEN = 403,
        TIMEOUT = 30000;

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
    endpoint = {},
    chatData = {
      messages: [],
      // Multi turn conversation, refs #46672. Stays null for a brand new
      // conversation and is never sent as a key while it is null: the backend
      // reads a missing key as "start a new conversation", null fails validation.
      conversationId: null,
      state: 'initial',
      // The stream in flight, so a new conversation can cut it off instead of
      // letting the previous reply land in the fresh thread.
      stream: null,
      // Role and tone as they went out with the last turn, so the pills can say
      // "carried over" while they have not been touched since.
      lastFilters: {
        role: null,
        tone: null
      }
    },
    colon = ':',
    debug = false,
    component,
    errorMessage,
    errorMessageDefault,
    promptPlaceholderDefault = '';

  // Default configuration options
  var defaultOptions = {
    debug: false
  };

  /**
   * ============================
   * Private functions
   * ============================
   */

  var debugLog = function(message, obj) {
    if (debug && window.console && window.console.log) {
      console.log('AICompletion:', message, obj || '');
    }
  };

  var isObject = function(variable) {
    return typeof variable === 'object' && variable !== null;
  }

  var isEmpty = function(value) {
    return value === undefined || value === null || String(value).trim() === "";
  }

  // Role and tone can hold anything the user typed, and they end up inside both
  // text and attribute positions of the pill menu markup. refs #46672
  var escapeHtml = function(value) {
    return String(value === undefined || value === null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  var getFirstCharacter = function(input) {
    try {
      if (typeof input !== 'string') {
        throw new Error('Input must be a string');
      }

      if (input.length === 0) {
        return '';
      }

      for (let character of input) {
        return character;
      }
    } catch (error) {
      console.error(error.message);
      return '';
    }
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
          console.error('AJAX request failed:', status, error, xhr.responseJSON.message);
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

    debug = this.settings.debug === "1" ? true : false;

    if (debug) {
      $("html").addClass("is-debug");
    }

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
      open: function(content, title, callbacks, classes = 'mfp-netiaic-modal') {
        $.magnificPopup.open({
          items: {
            src: '.netiaic-modal'
          },
          type: 'inline',
          mainClass: classes,
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
            // Not next(): the textarea shares a row with the submit button since
            // the input area was rebuilt. refs #46672
            $desc = $elem.closest('.crm-form-elem').find('.description'),
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

      if (component) {
        data['component'] = component;
      }

      const fetchTemplates = function(key, data, output) {
        sendAjaxRequest(endpoint.getTemplateList, 'POST', data, (response) => {
          handleSuccessResponse(key, response, output);
        }, (xhr, status, error) => {
          handleErrorResponse(key, xhr, status, error);
        });
      };

      const handleSuccessResponse = function(key, response, output) {
        if (response.status == 'success' || response.status == 1) {
          if (response.data) {
            templateListData[key] = key === 'savedTemplates' ? response.data : response.data['templates'];
            output += `<div class="template-list">`;

            for (let i in templateListData[key]) {
              let tplData = templateListData[key][i],
                  role = tplData.ai_role ? tplData.ai_role : tplData.role,
                  tone = tplData.tone_style ? tplData.tone_style : tplData.tone,
                  context = tplData.context ? tplData.context : tplData.content;

              let templateItemHtml = `
                <div class="template-item" data-ai-role="${role}" data-tone-style="${tone}" data-context="${context}">
                  <div class="inner">
                    <div class="ai-role"><span class="label">${ts['Copywriting Role']}</span>${colon}${role}</div>
                    <div class="tone-style"><span class="label">${ts['Tone Style']}</span>${colon}${tone}</div>
                    <div class="context"><span class="label">${ts['Content Summary']}</span>${colon}${context}</div>
                    <div class="actions">
                      <button type="button" class="apply-btn btn">${ts['Apply Template']}</button>
                    </div>
                  </div>
                </div>`;

              if (key === 'communityRecommendations') {
                templateItemHtml = templateItemHtml.replace('<div class="actions">', `<div class="org"><span class="label">${ts['The organization sharing this template']}</span>${colon}${tplData.org}</div><div class="actions">`);
              }

              output += templateItemHtml;
            }

            output += `</div>`;
          }
        }

        let $currentPanel = $(`#use-other-templates-tabs .modal-tabs-panel[data-type="${key}"]`);
        $currentPanel.html(output);
        bindTemplateListEvents($currentPanel);
      };

      const handleErrorResponse = function(key, xhr, status, error) {
        if (status == 'timeout') {
          errorMessage = `<p class="error">${ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.']}</p>`;
        } else if (xhr.responseJSON.message == 'Failed to retrieve template list.') {
          errorMessage = `<p>${ts['There are currently no templates available.']}</p>`;
        } else {
          errorMessage = `<p class="error">${errorMessageDefault}</p>`;
        }

        $(`#use-other-templates-tabs .modal-tabs-panel[data-type="${key}"]`).html(errorMessage);
      };

      const bindTemplateListEvents = function($panel) {
        $panel.find('.template-list').on('click', '.apply-btn', function() {
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

        let buffer;
        $panel.find('.keyword-filter').on('input', function() {
          clearTimeout(buffer);
          let $panel = $(this).closest('.modal-tabs-panel'),
              $list = $panel.find('.template-list'),
              keyword = $(this).val().toLowerCase();

          buffer = setTimeout(function() {
            let hasResult = false;
            $list.find('.template-item').each(function() {
                var $item = $(this);
                var aiRole = $item.data('ai-role').toLowerCase();
                var toneStyle = $item.data('tone-style').toLowerCase();
                var context = $item.data('context').toLowerCase();

                if (aiRole.includes(keyword) || toneStyle.includes(keyword) || context.includes(keyword)) {
                  $item.addClass(SHOW_CLASS).removeClass(HIDE_CLASS);
                  hasResult = true;
                } else {
                  $item.addClass(HIDE_CLASS).removeClass(SHOW_CLASS);
                }
            });

            if (!hasResult) {
              $list.next('.no-result').remove();
              $list.after(`<div class="no-result">${ts['No matches found.']}</div>`);
            } else {
              $list.next('.no-result').remove();
            }
          }, 300);
        });
      };

      for (let key in templateListData) {
        output = `<div class="template-filters crm-container">
        <div class="keyword-filter-item filter-item form-item">
          <i class="zmdi zmdi-search"></i>
          <div class="crm-form-elem crm-form-textfield">
            <input class="keyword-filter form-text huge" type="search" placeholder="${ts['Input search keywords']}">
          </div>
        </div>
        </div>`;

        if (['savedTemplates', 'communityRecommendations'].includes(key)) {
          if (key === 'communityRecommendations') {
            data['is_share_with_others'] = 1;
          }
          fetchTemplates(key, data, output);
        }
      }
    },

    setTemplate: function() {
      let $container = AICompletion.prototype.container,
          modal = AICompletion.prototype.modal;

      $container.on('click', '.msg-tools .save-btn:not([disabled])', function(event) {
        event.preventDefault();

        // The button now lives in the AI reply, whose data-ref-id points back at
        // the request it answered. refs #46672
        let $saveBtn = $(this),
            $aiMsg = $saveBtn.closest('.msg'),
            aiMsgID = $aiMsg.attr('id'),
            userMsgID = $aiMsg.attr('data-ref-id'),
            aicompletionID = $aiMsg.data('aicompletion-id'),
            modalTitle = ts['Save prompt as shared template'],
            modalCallbacks = {},
            modalClasses = 'mfp-netiaic-modal mfp-netiaic-modal-mini mfp-netiaic-set-tpl',
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
                  <button id="set-tpl-submit" type="button" class="set-tpl-submit form-submit-primary form-submit">${ts['Save']}</button>
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

              $container.find('.error').remove();
              sendAjaxRequest(endpoint.setTemplate, 'POST', data, function(response) {
                if (response.status == 'success' || response.status == 1) {
                  $tplTitle.prop('readonly', true);
                  $submit.text(ts['Saved']).prop('disabled', true);
                  $saveBtn.html(`<i class="zmdi zmdi-check"></i>${ts['Saved']}`).prop('disabled', true);
                  setTimeout(function() {
                    modal.close();
                  }, 800);
                }
              }, function(xhr, status, error) {
                if (status == 'timeout') {
                  errorMessage = `<p class="error">${ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.']}</p>`;
                  $tplTitle.prop('readonly', false);
                  $submit.text(ts['Save']).prop('disabled', false).after(errorMessage);
                }
              });
            });
          }
        }

        modal.open(modalContent, modalTitle, modalCallbacks, modalClasses);
      });
    },

    formIsEmpty: function() {
      // The selects are read directly now that the pill menu replaced select2,
      // refs #46672.
      let $container = AICompletion.prototype.container,
          hasRoleSelected = !isEmpty($container.find('.netiaic-prompt-role-select').val()),
          hasToneSelected = !isEmpty($container.find('.netiaic-prompt-tone-select').val()),
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
        // Create the option if it does not exist yet, which is how a value from
        // a template outside the configured list gets in.
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
        if (templateData.content.match(new RegExp('^'+ts['Organization intro'])) && defaultData.org_intro.length > 0) {
          templateData.content = templateData.content.replace(new RegExp('^'+ts['Organization intro']+'.*'), ts['Organization intro']+': '+defaultData.org_intro);
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
            modalClasses = 'mfp-netiaic-modal mfp-netiaic-modal-with-tabs mfp-netiaic-use-tpl',
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

        modal.open(modalContent, modalTitle, modalCallbacks, modalClasses);
      });
    },

    createMessage: function(id, refID, data, type, mode) {
      let $container = AICompletion.prototype.container,
          $submit = $container.find('.netiaic-form-submit'),
          msg = '',
          output = '';

      if (!$container.find('.msg[id="' + id + '"]').length) {
        if (type == 'user') {
          let firstCharacter = getFirstCharacter(defaultData.sort_name);

          if (isObject(data)) {
            if (isEmpty(data.role) && isEmpty(data.tone) && isEmpty(data.content)) {
              msg = '(n/a)';
            }
            else {
              // AC-4 layout: role/tone get their own styled group and only show
              // up again once they differ from the previous turn - a turn that
              // just carries them over stays quiet. No "Content" label, the
              // text speaks for itself. refs #46672
              if ((data.role || data.tone) && !AICompletion.prototype.isFiltersCarriedOver(data.role, data.tone)) {
                msg += '<div class="msg-filters">';

                if (data.role) {
                  msg += `<span class="ai-role"><span class="label">${ts['Copywriting Role']}</span>${colon}${escapeHtml(data.role)}</span>`;
                }

                if (data.tone) {
                  msg += `<span class="tone-style"><span class="label">${ts['Tone Style']}</span>${colon}${escapeHtml(data.tone)}</span>`;
                }

                msg += '</div>';
              }

              if (data.content) {
                msg += `<div class="msg-text">${escapeHtml(data.content).replace(/\n/g, '<br>')}</div>`;
              }
            }
          }
          else {
            msg = data;
          }

          output = `<div id="${id}" data-ref-id="${refID}" class="user-msg msg is-finished">
            <div class="msg-avatar">${firstCharacter}</div>
            <div class="msg-content">${msg}</div>
            </div>`;
        }

        if (type == 'ai') {
          msg = data;
          // AC-7: saving and recommending belong under the reply they produced,
          // not under the request. refs #46672
          output = `<div id="${id}" data-ref-id="${refID}" class="ai-msg msg">
            <div class="msg-avatar"><i class="zmdi zmdi-mood"></i></div>
            <div class="msg-content">${msg}</div>
            <ul class='msg-tools'>
              <li><button type="button" title="${ts['Copy']}" class="copy-btn handle-btn"><i class="zmdi zmdi-copy"></i> ${ts['Copy']}</button></li>
              <li><button type="button" title="${ts['Save As New Template']}" class="save-btn handle-btn"><i class="zmdi zmdi-file-plus"></i> ${ts['Save As New Template']}</button></li>
              <li><button type="button" title="${ts['Recommend']}" class="recommend-btn handle-btn"><i class="zmdi zmdi-accounts-alt"></i> ${ts['Recommend']}</button></li>
            </ul>
            <div class="msg-tip"><i class="zmdi zmdi-info-outline"></i>${ts['Remember to verify AI-generated text before using it.']}</div>
            </div>`;
        }

        if (output.trim != '') {
          $container.find('.netiaic-chat > .inner').append(output);
          AICompletion.prototype.contentScrollBottom();

          if (mode == 'error') {
            $container.find('.msg[id="' + id + '"]').addClass('error-msg');

            // AC-7: no "try again" button, a new attempt is a normal submit or
            // a follow up message.
            if ($submit.hasClass(ACTIVE_CLASS)) {
              $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
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

        // Same move as setTemplate(): the button sits in the AI reply now.
        let $shareBtn = $(this),
            $aiMsg = $shareBtn.closest('.msg'),
            aiMsgID = $aiMsg.attr('id'),
            userMsgID = $aiMsg.attr('data-ref-id'),
            aicompletionID = $aiMsg.data('aicompletion-id'),
            modalTitle = ts['Recommend a Template to Other Organizations'],
            modalCallbacks = {},
            modalClasses = 'mfp-netiaic-modal mfp-netiaic-modal-mini mfp-netiaic-share-tpl',
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
              <button id="share-tpl-submit" type="button" class="share-tpl-submit form-submit form-submit-primary">${ts['Recommend']}</button>
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

              $container.find('.error').remove();
              sendAjaxRequest(endpoint.setShare, 'POST', data, function(response) {
                if (response.status == 'success' || response.status == 1) {
                  $submit.text(ts['Recommended']).prop('disabled', true);
                  $shareBtn.html(`<i class="zmdi zmdi-check"></i>${ts['Recommended']}`).prop('disabled', true);
                  setTimeout(function() {
                    modal.close();
                  }, 800);
                }
              }, function(xhr, status, error) {
                if (status == 'timeout') {
                  errorMessage = `<p class="error">${ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.']}</p>`;
                  $submit.text(ts['Recommend']).prop('disabled', false).after(errorMessage);
                }
              });
            });
          }
        }

        modal.open(modalContent, modalTitle, modalCallbacks, modalClasses);
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

    setChatState: function(state) {
      let $container = AICompletion.prototype.container,
          $promptContent = $container.find('.netiaic-prompt-content-textarea'),
          isActive = state === 'active';

      chatData.state = isActive ? 'active' : 'initial';
      $container.toggleClass(STATE_ACTIVE_CLASS, isActive).toggleClass(STATE_INITIAL_CLASS, !isActive);

      // Follow up mode hints at what can be typed, the template links are hidden
      // by CSS through the state class above (AC-7).
      $promptContent.attr('placeholder', isActive
        ? ts['Enter a follow-up request, for example: make it shorter, or use a livelier tone.']
        : promptPlaceholderDefault);

      AICompletion.prototype.updateInheritedLabel();
    },

    setConversationTitle: function(text) {
      let $title = AICompletion.prototype.container.find('.netiaic-conversation-title'),
          title = isEmpty(text) ? '' : String(text).trim();

      // The title lives on the client for now. Phase 4 stores it server side and
      // only the body of this function changes, callers stay as they are.
      if (countCharacters(title) > TITLE_MAX_LENGTH) {
        title = Array.from(title).slice(0, TITLE_MAX_LENGTH).join('') + '…';
      }

      $title.text(title === '' ? ts['New conversation'] : title);
    },

    resetConversation: function() {
      let $container = AICompletion.prototype.container,
          $promptContent = $container.find('.netiaic-prompt-content-textarea'),
          $submit = $container.find('.netiaic-form-submit');

      // A reply still streaming belongs to the conversation being left behind.
      if (chatData.stream) {
        chatData.stream.close();
        chatData.stream = null;
        $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
      }

      // Keep the greeting, drop every exchange so no old context is carried over.
      $container.find('.netiaic-chat > .inner .msg').not('#ai-msg-welcome').remove();
      chatData.conversationId = null;
      chatData.messages = [];

      // Role and tone are settings rather than context, they stay as they are,
      // but they are no longer carried over from a turn of the old conversation.
      chatData.lastFilters = { role: null, tone: null };
      AICompletion.prototype.closeFilterMenus();

      $promptContent.val('');
      AICompletion.prototype.promptContentCounterUpdate($promptContent);
      AICompletion.prototype.setConversationTitle('');
      AICompletion.prototype.setChatState('initial');
    },

    confirmNewConversation: function() {
      let modal = AICompletion.prototype.modal,
          modalTitle = ts['New conversation'],
          modalCallbacks = {},
          modalClasses = 'mfp-netiaic-modal mfp-netiaic-modal-mini mfp-netiaic-new-conversation',
          modalContent = `<div class="new-conversation-form">
            <div class="desc"><p>${ts['After opening a new conversation, you will no longer see the current one. Continue?']}</p></div>
            <div class="form-actions">
              <button type="button" class="new-conversation-cancel form-submit">${ts['Cancel']}</button>
              <button type="button" class="new-conversation-confirm form-submit form-submit-primary">${ts['Confirm']}</button>
            </div>
          </div>`;

      modalCallbacks.open = function() {
        $('.new-conversation-form').on('click', '.new-conversation-confirm', function(event) {
          event.preventDefault();
          AICompletion.prototype.resetConversation();
          modal.close();
        });

        $('.new-conversation-form').on('click', '.new-conversation-cancel', function(event) {
          event.preventDefault();
          modal.close();
        });
      }

      modal.open(modalContent, modalTitle, modalCallbacks, modalClasses);
    },

    handleChatError: function(status, body, aiMsgID, userMsgID) {
      let message = errorMessageDefault;

      // The turn limit and the ownership check both need their own wording, a
      // generic failure message would leave the user with no way forward.
      if (isObject(body) && body.error_code === TURN_LIMIT_CODE) {
        message = ts['This conversation has reached its length limit. Please start a new conversation.'];
      }
      else if (status === HTTP_FORBIDDEN) {
        message = ts['This conversation is not available. Please start a new conversation.'];
      }

      AICompletion.prototype.createMessage(aiMsgID, userMsgID, message, 'ai', 'error');
    },

    getFilterSelect: function(type) {
      return AICompletion.prototype.container.find(`.netiaic-prompt-${type}-select`);
    },

    // Description and icon for one option, keyed by its value in
    // templates/CRM/AI/defaults/filters*.tpl. A site with its own copy of that
    // file has no such table, and then the option is drawn with its text alone.
    getFilterMeta: function(type, value) {
      let options = defaultData.filter_options;

      if (isObject(options) && isObject(options[type]) && isObject(options[type][value])) {
        return options[type][value];
      }

      return null;
    },

    // AC-4: role and tone are pills with a popup menu. The select stays the only
    // data source, this only draws it, so applying a template or any other
    // caller of setSelectOption() lands here through the change event.
    renderFilterDropdown: function($dropdown) {
      let type = $dropdown.attr('data-filter'),
          $select = AICompletion.prototype.getFilterSelect(type),
          selected = $select.val(),
          hasSelected = !isEmpty(selected),
          label = type === 'role' ? ts['Copywriting Role'] : ts['Tone Style'],
          pillIcon = type === 'role' ? 'zmdi-account' : 'zmdi-palette',
          placeholder = $select.attr('data-placeholder'),
          optionsHtml = '';

      $select.find('option').each(function() {
        let value = $(this).attr('value');

        if (isEmpty(value)) {
          // The empty option is offered as "not specified" further down.
          return;
        }

        let meta = AICompletion.prototype.getFilterMeta(type, value),
            icon = meta && meta.icon ? meta.icon : 'zmdi-label',
            desc = meta && meta.desc ? `<span class="desc">${escapeHtml(meta.desc)}</span>` : '',
            isCurrent = hasSelected && value === selected;

        optionsHtml += `<li><button type="button" class="netiaic-filter-option${isCurrent ? ' ' + SELECTED_CLASS : ''}" data-value="${escapeHtml(value)}">
          <span class="icon"><i class="zmdi ${escapeHtml(icon)}"></i></span>
          <span class="body"><span class="title">${escapeHtml(value)}</span>${desc}</span>
          <i class="zmdi zmdi-check"></i>
          </button></li>`;
      });

      $dropdown.html(`<button type="button" class="netiaic-filter-pill">
        <i class="zmdi ${pillIcon}"></i>
        <span class="text">${escapeHtml(hasSelected ? selected : label)}</span>
        <i class="zmdi zmdi-chevron-down"></i>
        </button>
        <div class="netiaic-filter-menu">
        <div class="netiaic-filter-menu-title">${label}</div>
        <ul class="netiaic-filter-option-list">${optionsHtml}</ul>
        <ul class="netiaic-filter-option-list netiaic-filter-extra">
        <li><button type="button" class="netiaic-filter-option${hasSelected ? '' : ' ' + SELECTED_CLASS}" data-value="">
          <span class="icon"><i class="zmdi zmdi-minus-circle-outline"></i></span>
          <span class="body"><span class="title">${ts['Not specified']}</span></span>
          <i class="zmdi zmdi-check"></i>
          </button></li>
        <li class="netiaic-filter-custom">
          <button type="button" class="netiaic-filter-custom-toggle">
            <span class="icon"><i class="zmdi zmdi-plus"></i></span>
            <span class="body"><span class="title">${ts['Custom...']}</span></span>
          </button>
          <div class="netiaic-filter-custom-form">
            <input type="text" class="netiaic-filter-custom-input form-text" placeholder="${escapeHtml(placeholder)}">
            <button type="button" class="netiaic-filter-custom-submit form-submit form-submit-primary">${ts['Save']}</button>
          </div>
        </li>
        </ul>
        </div>`);

      $dropdown.toggleClass(EMPTY_CLASS, !hasSelected);
    },

    // The menu opens upwards, since the input area sits at the bottom of the
    // panel. In a short panel, or on a phone with the keyboard up, there may not
    // be room: then it is capped to what is available, or dropped below the pill
    // when that side has more space.
    positionFilterMenu: function($dropdown) {
      let $menu = $dropdown.find('.netiaic-filter-menu'),
          row = $dropdown.closest('.netiaic-prompt-filters').get(0);

      if (!$menu.length || !row || !row.getBoundingClientRect) {
        return;
      }

      let rect = row.getBoundingClientRect(),
          spaceAbove = rect.top - 8,
          spaceBelow = (window.innerHeight || document.documentElement.clientHeight) - rect.bottom - 8,
          openUp = spaceAbove >= MENU_MIN_HEIGHT || spaceAbove >= spaceBelow,
          space = openUp ? spaceAbove : spaceBelow;

      $dropdown.toggleClass(DROPPED_CLASS, !openUp);
      $menu.css('max-height', Math.max(MENU_MIN_HEIGHT, Math.min(MENU_MAX_HEIGHT, space)) + 'px');
    },

    toggleFilterMenu: function($dropdown) {
      let wasOpen = $dropdown.hasClass(OPEN_CLASS);

      AICompletion.prototype.closeFilterMenus();

      if (!wasOpen) {
        $dropdown.addClass(OPEN_CLASS);
        AICompletion.prototype.positionFilterMenu($dropdown);

        let menu = $dropdown.find('.netiaic-filter-menu').get(0);
        if (menu && menu.scrollIntoView) {
          menu.scrollIntoView({ block: 'nearest' });
        }
      }
    },

    closeFilterMenus: function() {
      let $container = AICompletion.prototype.container;

      if (!$container || !$container.length) {
        return;
      }

      $container.find('.netiaic-filter-dropdown').removeClass(`${OPEN_CLASS} ${DROPPED_CLASS}`);
      $container.find('.netiaic-filter-custom').removeClass(ACTIVE_CLASS);
    },

    applyCustomFilter: function($dropdown) {
      let value = $dropdown.find('.netiaic-filter-custom-input').val();

      if (isEmpty(value)) {
        return;
      }

      // Same path a value coming from a template takes, so a typed value and an
      // applied one end up in exactly the same state.
      AICompletion.prototype.setSelectOption(
        AICompletion.prototype.getFilterSelect($dropdown.attr('data-filter')),
        value.trim()
      );
      AICompletion.prototype.closeFilterMenus();
    },

    // True while role/tone are still the ones the last turn went out with, i.e.
    // nothing has been changed since. Shared by the "carried over" pill label
    // and the user message bubble (createMessage()), so both agree on the same
    // turn. refs #46672
    isFiltersCarriedOver: function(role, tone) {
      return chatData.state === 'active'
        && role === chatData.lastFilters.role
        && tone === chatData.lastFilters.tone;
    },

    // Shown while the settings are still the ones the last turn went out with.
    updateInheritedLabel: function() {
      let $container = AICompletion.prototype.container,
          isUnchanged = AICompletion.prototype.isFiltersCarriedOver(
            $container.find('.netiaic-prompt-role-select').val(),
            $container.find('.netiaic-prompt-tone-select').val()
          );

      $container.find('.netiaic-filter-inherited').toggleClass(ACTIVE_CLASS, isUnchanged);
    },

    filterUiOperation: function() {
      let $container = AICompletion.prototype.container;

      $container.find('.netiaic-filter-dropdown').each(function() {
        let $dropdown = $(this);

        AICompletion.prototype.renderFilterDropdown($dropdown);

        AICompletion.prototype.getFilterSelect($dropdown.attr('data-filter')).on('change', function() {
          AICompletion.prototype.renderFilterDropdown($dropdown);
          AICompletion.prototype.updateInheritedLabel();
        });
      });

      // Same shape as the image generator dropdowns (AIImageGeneration.js): the
      // toggle and the menu stop the event, a click anywhere else closes.
      $container.on('click', '.netiaic-filter-pill', function(event) {
        event.preventDefault();
        event.stopPropagation();
        AICompletion.prototype.toggleFilterMenu($(this).closest('.netiaic-filter-dropdown'));
      });

      $container.on('click', '.netiaic-filter-menu', function(event) {
        event.stopPropagation();
      });

      // iOS does not bubble click from elements that are not interactive, so the
      // document handler below would never run on a phone. Stopping touchstart
      // here keeps a tap on the pill from closing the menu it just opened.
      $container.on('touchstart', '.netiaic-filter-pill, .netiaic-filter-menu', function(event) {
        event.stopPropagation();
      });

      $(document).off(FILTER_EVENT_NS).on(`click${FILTER_EVENT_NS} touchstart${FILTER_EVENT_NS}`, function() {
        AICompletion.prototype.closeFilterMenus();
      });

      $container.on('click', '.netiaic-filter-option', function(event) {
        event.preventDefault();
        let $option = $(this),
            $dropdown = $option.closest('.netiaic-filter-dropdown');

        AICompletion.prototype.getFilterSelect($dropdown.attr('data-filter'))
          .val($option.attr('data-value'))
          .trigger('change');
        AICompletion.prototype.closeFilterMenus();
      });

      $container.on('click', '.netiaic-filter-custom-toggle', function(event) {
        event.preventDefault();
        let $custom = $(this).closest('.netiaic-filter-custom');

        $custom.addClass(ACTIVE_CLASS);
        $custom.find('.netiaic-filter-custom-input').focus();
      });

      $container.on('click', '.netiaic-filter-custom-submit', function(event) {
        event.preventDefault();
        AICompletion.prototype.applyCustomFilter($(this).closest('.netiaic-filter-dropdown'));
      });

      $container.on('keydown', '.netiaic-filter-custom-input', function(event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          AICompletion.prototype.applyCustomFilter($(this).closest('.netiaic-filter-dropdown'));
        }
      });

      // Only now are the pills usable, so only now are the selects hidden: a
      // browser still running the cached previous script never gets this class
      // and keeps the select boxes it knows how to drive. refs #46672
      $container.addClass(FILTER_PILLS_CLASS);
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

      // The pill menu replaced select2 on these two selects, refs #46672. Free
      // text is still accepted, through the "custom" row of the menu.
      AICompletion.prototype.filterUiOperation();

      // Enhance select2 search field focus styling
      // Kept as is: this is a document level handler that also reaches the
      // select2 widgets of other components on the same page.
      $(document).on('select2:open', function(e) {
        let selectId = e.target.id,
            $select2Element = $(e.target),
            selectedOptions = $select2Element.val();

        if (!selectedOptions && selectedOptions.length == 0) {
          $(".select2-results__options[id='select2-" + selectId + "-results']").each(function(key, elem) {
            let $elem = $(elem);

            if ($elem.length) {
              setTimeout(function() {
                $elem.find('.select2-results__option[aria-selected=true]').attr('aria-selected', false).removeClass('select2-results__option--highlighted');
              }, 200); // TODO: timeout is workaournd
            }
          });

          $(".select2-search__field[aria-controls='select2-" + selectId + "-results']").each(function(key, elem) {
            setTimeout(function() { elem.focus(); } , 300); // TODO: timeout is workaournd
          });
        }
      });

      $promptContent.on('focus', function() {
        let inputText = $(this).val();

        if (inputText === '') {
          $promptContentCommand.addClass(ACTIVE_CLASS);
        }

        if (this.scrollHeight > this.clientHeight && !$(this).hasClass(EXPAND_CLASS)) {
          $(this).addClass(EXPAND_CLASS);
          $container.addClass(`form-${EXPAND_CLASS}`);
        }
      });

      $promptContent.on('blur', function() {
        let $field = $(this);

        // Add delay to expansion collapse to ensure other form elements can be clicked
        if ($field.hasClass(EXPAND_CLASS)) {
          setTimeout(function() {
            $field.removeClass(EXPAND_CLASS);
            $container.removeClass(`form-${EXPAND_CLASS}`);
          }, 200);
        }

        setTimeout(function() {
          $promptContentCommand.removeClass(ACTIVE_CLASS);
        }, 500);
      });

      $promptContent.on('input', function() {
        let inputText = $(this).val();

        $promptContentCommand.toggleClass(ACTIVE_CLASS, inputText === '');

        if (this.scrollHeight >= this.clientHeight) {
          if (!$(this).hasClass(EXPAND_CLASS)) {
            $(this).addClass(EXPAND_CLASS);
            $container.addClass(`form-${EXPAND_CLASS}`);
          }
        }
        else {
          $(this).removeClass(EXPAND_CLASS);
          $container.removeClass(`form-${EXPAND_CLASS}`);
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

      // AC-5: the confirmation is a modal, not window.confirm.
      $container.on('click', '.netiaic-new-conversation', function(event) {
        event.preventDefault();
        AICompletion.prototype.confirmNewConversation();
      });
    },

    formSubmit: function() {
      let userMsgID = 'user-msg-' + renderID(),
          aiMsgID = 'ai-msg-' + renderID(),
          $container = AICompletion.prototype.container,
          $submit = $container.find('.netiaic-form-submit'),
          $userMsg,
          $aiMsg,
          formData = {
            role: $container.find('.netiaic-prompt-role-select').val(),
            tone: $container.find('.netiaic-prompt-tone-select').val(),
            content: $container.find('.netiaic-prompt-content-textarea').val(),
            sourceUrl: window.location.href,
            sourceUrlPath: window.location.pathname,
            sourceUrlQuery: window.location.search
          },
          isEmptyPrompt = isEmpty(formData.role) && isEmpty(formData.tone) && isEmpty(formData.content) ? true : false,
          userMessage = isEmptyPrompt ? '(n/a)' : formData;

      // Follow up turn. A missing key is what tells the backend to start a new
      // conversation, so the key is only added once we really have an id.
      if (chatData.conversationId) {
        formData.conversation_id = chatData.conversationId;
      }

      if (!$submit.hasClass(ACTIVE_CLASS)) {
        $submit.addClass(ACTIVE_CLASS).prop('disabled', true);
      }

      // Create user's message
      AICompletion.prototype.createMessage(userMsgID, aiMsgID, userMessage, 'user');

      // Create AI's message
      if (isEmptyPrompt) {
        errorMessage = ts['Please enter the %1 copy you would like AI to generate.'];
        AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessage, 'ai', 'error');
      }
      else {
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
            // The turn limit (422) and the ownership check (403) answer with a
            // body that tells them apart from a generic failure, so read it
            // before deciding what to show. refs #46672
            return response.json().catch(function() {
              return {};
            }).then(function(body) {
              AICompletion.prototype.handleChatError(response.status, body, aiMsgID, userMsgID);
              return null;
            });
          }
        })
        .then(function(result) {
          if (result === null) {
            // Already reported by handleChatError().
            return;
          }

          // Purely additive: a backend without multi turn support does not send
          // this key, and then nothing is ever sent back either.
          if (result.data && result.data.conversation_id) {
            chatData.conversationId = result.data.conversation_id;
          }

          // What this turn went out with, so the pills can say "carried over"
          // until one of them is touched again.
          chatData.lastFilters = { role: formData.role, tone: formData.tone };

          if (chatData.state !== 'active') {
            AICompletion.prototype.setConversationTitle(formData.content);
            AICompletion.prototype.setChatState('active');
          }
          else {
            AICompletion.prototype.updateInheritedLabel();
          }

          // Clear the box so a follow up starts empty, role and tone carry over.
          let $promptContent = $container.find('.netiaic-prompt-content-textarea');
          $promptContent.val('');
          AICompletion.prototype.promptContentCounterUpdate($promptContent);

          var evtSource = new EventSource(endpoint.chat + '?token=' + result.data.token + '&id=' + result.data.id, {
            withCredentials: false,
          });

          chatData.stream = evtSource;

          evtSource.onmessage = (event) => {
            try {
              let eventData = JSON.parse(event.data);
              if (typeof eventData !== "undefined") {
                if (($aiMsg && $aiMsg.length) && (eventData.hasOwnProperty('is_finished') || eventData.hasOwnProperty('is_error'))) {
                  evtSource.close();
                  chatData.stream = null;

                  // AC-7: the button goes back to plain submit, ready for the
                  // next follow up.
                  if ($submit.hasClass(ACTIVE_CLASS)) {
                    $submit.removeClass(ACTIVE_CLASS).prop('disabled', false);
                  }

                  if (eventData.is_finished) {
                    if (!$aiMsg.hasClass(FINISH_CLASS)) {
                      $aiMsg.addClass(FINISH_CLASS);
                    }
                  }

                  if (eventData.is_error) {
                    if (!$aiMsg.hasClass(ERROR_CLASS)) {
                      $aiMsg.addClass(ERROR_CLASS);
                      throw new Error(`${eventData.message} (onmessage error)`);
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

                    if (eventData.hasOwnProperty('is_error')) {
                      let msgID = 'ai-msg-' + renderID();

                      if (eventData.message.includes('timed out')) {
                        errorMessage = ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.'];
                        AICompletion.prototype.createMessage(msgID, '', errorMessage, 'ai', 'error');
                      }
                      else {
                        AICompletion.prototype.createMessage(msgID, '', errorMessageDefault, 'ai', 'error');
                      }

                      console.error(message);
                    }
                    else {
                      AICompletion.prototype.createMessage(aiMsgID, userMsgID, message, 'ai', 'stream');
                      $aiMsg = $container.find('.msg[id="' + aiMsgID + '"]');
                      $userMsg = $container.find('.msg[id="' + userMsgID + '"]');

                      if (eventData.hasOwnProperty('id')) {
                        $aiMsg.data('aicompletion-id', eventData.id);

                        if (!$userMsg.hasClass(`ai-msg-${FINISH_CLASS}`)) {
                          $userMsg.addClass(`ai-msg-${FINISH_CLASS}`);
                        }
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
              }
            } catch (error) {
              if (error.message.includes('onmessage error')) {
                let msgID = 'ai-msg-' + renderID();

                if (error.message.includes('timed out')) {
                  errorMessage = ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.'];
                  AICompletion.prototype.createMessage(msgID, '', errorMessage, 'ai', 'error');
                }
                else {
                  AICompletion.prototype.createMessage(msgID, '', errorMessageDefault, 'ai', 'error');
                }
              }
              else {
                AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessageDefault, 'ai', 'error');
              }

              console.error(error.message);
            }
          };

          evtSource.onerror = function(event) {
            console.error("EventSource encountered an error: ", event);
            evtSource.close();
            chatData.stream = null;
          };
        })
        .catch(function(error) {
          console.error("Encountered an error: ", error);
          if (error.message.includes('timed out')) {
            errorMessage = ts['Our service is currently busy, please try again later. If needed, please contact our customer service team.'];
          }

          AICompletion.prototype.createMessage(aiMsgID, userMsgID, errorMessage, 'ai', 'error');
        });
      }
    },

    getDefaultData: function() {
      if (typeof window.AICompletion.default === 'object') {
        return window.AICompletion.default;
      }
    },

    init: function() {
      var $container = $(this.element);

      defaultData = this.getDefaultData();
      endpoint = window.AICompletion.endpoint;
      component = window.AICompletion.component;
      AICompletion.prototype.container = $container;

      // Get translation string comparison table
      ts = window.AICompletion.translation;
      colon = window.AICompletion.language == 'zh_TW' ? '：' : ':';
      errorMessageDefault = ts['We\'re sorry, our service is currently experiencing some issues. Please try again later. If the problem persists, please contact our customer service team.'];
      errorMessage = errorMessageDefault;

      // Follow up mode swaps the placeholder, so keep the original around to be
      // able to restore exactly this text when a new conversation starts.
      promptPlaceholderDefault = $container.find('.netiaic-prompt-content-textarea').attr('placeholder') || '';

      // TODO: For development and testing only, need to be removed afterwards
      this.devTestUse();

      // Implement and install main features and functions
      this.modal.init();
      this.formUiOperation();
      this.useTemplates();
      this.setTemplate();
      this.setShare();

      // No conversation yet: template links visible, no header. refs #46672
      this.setChatState('initial');

      // Finally, add class to mark the initialization
      $container.addClass(INIT_CLASS);
      debugLog('AICompletion initialized on element:', this.element);
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
