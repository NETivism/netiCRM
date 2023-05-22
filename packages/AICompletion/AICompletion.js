(function($) {
  'use strict';

  // Endpoint
  // TODO: Need to change to real endpoint, refer to CRM/AI/Page/AJAX.php
  var endpoint = {
    getTemplateList: '/api/getTemplateList',
    getDefaultTemplate: '/api/getDefaultTemplate',
    getTemplate: '/api/getTemplate',
    setTemplate: '/api/setTemplate',
    getOrganizationIntro: '/api/getOrganizationIntro',
    setOrganizationIntro: '/api/setOrganizationIntro',
    chat: '/api/chat',
    quota: '/api/quota',
    setShare: '/api/setShare'
  }

  // Default configuration options
  var defaultOptions = {};

  // Plugin constructor function
  $.fn.AICompletion = function(options) {
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

      // Initialize
      function init() {
        // TODO: Add any necessary initialization tasks here

        // Call the initialization function
        init();
      }
    });
  };
})(jQuery);
