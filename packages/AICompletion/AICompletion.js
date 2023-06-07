(function($) {
  'use strict';

  /**
   * ============================
   * Private static constants
   * ============================
   */

  const INIT_CLASS = 'is-initialized';

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

      setTimeout(function() {
        $(".netiaic-form-container .form-select").select2({
          "allowClear": true,
          "dropdownAutoWidth": true
        });
      }, 3000);
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

      this.renderSelects();
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
