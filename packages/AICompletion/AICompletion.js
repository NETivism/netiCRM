(function($) {
  'use strict';

  // Endpoint
  // TODO: Need to change to real endpoint, refer to CRM/AI/Page/AJAX.php
  var endpoint = {
    getTemplateList: '/api/getTemplateList',
    getTemplate: '/api/getTemplate',
    setTemplate: '/api/setTemplate',
    chat: '/api/chat',
    setShare: '/api/setShare'
  }

  // Default configuration options
  var defaultOptions = {},
      defaultData = {};

  // Plugin constructor function
  $.fn.AICompletion = function(options) {
    var $container = this;
    var settings = $.extend({}, defaultOptions, options);

    return this.each(function() {
      /**
       * ============================
       * Private functions
       * ============================
       */

      // Send AJAX request
      function sendAjaxRequest(url, method, data, callback) {
        $.ajax({
          url: url,
          method: method,
          data: JSON.stringify(data),
          contentType: 'application/json',
          success: function(response) {
            callback(response);
          },
          error: function(xhr, status, error) {
            // console.error('AJAX request failed:', status, error);
          }
        });
      }

      // Get default Template
      function getDefaultTemplate() {
        sendAjaxRequest(endpoint.getDefaultTemplate, 'POST', null, function(response) {
          // Fill the context settings area with default Template information
          // TODO: Process and fill the data according to the API response
        });
      }

      // Apply a Template
      function applyTemplate(templateData) {
        // TODO: Apply the template data to the form in the context settings area
      }

      // Show all templates in a modal dialog
      function showAllTemplates() {
        // TODO: Create a modal dialog and related elements based on the functional requirements
      }

      // Get template list
      function getTemplateList(page) {
        sendAjaxRequest(endpoint.getTemplateList, 'GET', { page: page }, function(response) {
          // TODO: Process the list of templates according to the data returned by the API
        });
      }

      // Get organization introduction text
      function getOrganizationIntro() {
        sendAjaxRequest(endpoint.getOrganizationIntro, 'POST', null, function(response) {
          // Fill the content summary text area with the organization introduction
          // TODO: Process and fill the data according to the API response
        });
      }

      // Set organization introduction text
      function setOrganizationIntro() {
        // TODO: Create an edit modal dialog and related elements based on the functional requirements
        var data = {};

        sendAjaxRequest(endpoint.setOrganizationIntro, 'POST', data, function(response) {
        });
      }

      // Send prompt content
      function sendPrompt() {
        var data = {};
        var toneStyle = ''; // TODO: Get the tone/style value from the dropdown menu
        var role = ''; // TODO: Get the role value from the dropdown menu
        var summary = ''; // TODO: Get the content summary value from the text area

        // TODO: Conditions must be combined into a prompt string

        sendAjaxRequest(endpoint.chat, 'POST', data, function(response) {
          // Display the response result in the result presentation area
          // TODO: Process and display the result based on the API response
        });
      }

      // Display AI response on the interface
      function answerResponse(answer) {
        // TODO: Display the AI response in the result presentation area
      }

      // Recommend Template to other organizations
      function recommendTemplate() {
        // TODO: Create a modal dialog for recommending Template based on the functional requirements
        var data = {};

        sendAjaxRequest(endpoint.setShare, 'POST', data, function(response) {
          // Display the response result in the result presentation area
          // TODO: Process and display the result based on the API response
        });
      }

      // Save a template
      function setTemplate() {
        // TODO: Create a modal dialog for saving a Template based on the functional requirements
      }

      // Check quota usage
      function checkQuotaUsage() {
        sendAjaxRequest(endpoint.quota, 'POST', null, function(response) {
          var quotaLimit = response.quotaLimit,
              quotaUsed = response.quotaUsed;
          // TODO: Retrieve quota usage information from the API response and return the corresponding message
        });
      }

      function renderSelects() {
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
      }

      // Get default data
      function getDefaultData() {
        let data = {};

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
      }

      // Initialize
      function init() {
        // TODO: Add any necessary initialization tasks here
        defaultData = getDefaultData();
        renderSelects();
        $container.addClass("is-initialized");
      }

      // Call the initialization function
      init();

    });
  };
})(jQuery);
