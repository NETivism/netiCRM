(function($) {
  'use strict';

  // Default configuration options
  // TODO: Change to the actual Endpoint later
  var defaultOptions = {
    defaultPromptEndpoint: '/api/getDefaultPrompt',
    sendPromptEndpoint: '/api/sendPrompt',
    savePromptEndpoint: '/api/saveTemplate',
    recommendPromptEndpoint: '/api/recommendPrompt',
    quotaEndpoint: '/api/getQuota',
    quotaLimit: 10
  };

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

      // Load default Prompt
      function loadDefaultPrompt() {
        sendAjaxRequest(settings.defaultPromptEndpoint, 'POST', null, function(response) {
          // Fill the context settings area with default Prompt information
          // TODO: Process and fill the data according to the API response
        });
      }

      // Show more Prompts in a modal dialog
      function showMorePrompt() {
        // TODO: Create a modal dialog and related elements based on the functional requirements
      }

      // Apply a Prompt
      function applyPrompt(promptData) {
        // TODO: Apply the Prompt data to the form in the context settings area
      }

      // Load organization introduction text
      function loadOrganizationIntro() {
        sendAjaxRequest('/api/getOrganizationIntro', 'POST', null, function(response) {
          // Fill the content summary text area with the organization introduction
          // TODO: Process and fill the data according to the API response
        });
      }

      // Edit organization introduction text
      function editOrganizationIntro() {
        // TODO: Create an edit modal dialog and related elements based on the functional requirements
      }

      // Send context content
      function sendPrompt() {
        var endpoint = settings.sendPromptEndpoint;

        // Get information from the context settings area
        var toneStyle = ''; // TODO: Get the tone/style value from the dropdown menu
        var role = ''; // TODO: Get the role value from the dropdown menu
        var summary = ''; // TODO: Get the content summary value from the text area

        // TODO: Conditions must be combined into a prompt string

        sendAjaxRequest(endpoint, 'POST', data, function(response) {
          // Display the response result in the result presentation area
          // TODO: Process and display the result based on the API response
        });
      }

      // Display AI response on the interface
      function answerResponse(answer) {
        // TODO: Display the AI response in the result presentation area
      }

      // Recommend Prompts to other organizations
      function recommendPrompt() {
        // TODO: Create a modal dialog for recommending Prompts based on the functional requirements
      }

      // Save a Prompt
      function savePrompt() {
        // TODO: Create a modal dialog for saving a Prompt based on the functional requirements
      }

      // Check quota usage
      function checkQuotaUsage() {
        var quotaEndpoint = settings.quotaEndpoint;
        var quotaLimit = settings.quotaLimit;

        sendAjaxRequest(quotaEndpoint, 'POST', null, function(response) {
          var quotaUsed = response.quotaUsed;
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
