/**
 * CrmDependentSelect
 *
 * CrmDependentSelect is a lightweight, simple JavaScript plugin that creates a custom, dependent
 * select input element within a container element. The dependencies are managed by data
 * attributes on the select element's options. The plugin also supports an option for debug
 * mode and can be imported as a module in environments that support it.
 *
 * The plugin is initialized by creating a new instance of the CrmDependentSelect constructor, passing
 * the selector of the dependent select element and an optional settings object. The dependent
 * select element is expected to be within an element with the '.crm-container' class and
 * should have options with 'data-parent-filter' attributes corresponding to the parent element's
 * values. The select element itself should have a 'data-parent' attribute with the value being
 * the selector of the parent select element.
 *
 * Example usage:
 *
 *     new crmDependentSelect('.crm-container select[data-parent]', { debug: true });
 *
 * This will initialize the plugin on the '.crm-container select[data-parent]' element with debug mode turned on.
 */

(function(global, factory) {
  'use strict';

  // Prevent global variable conflicts
  if (global.crmDependentSelect) {
    console.warn('crmDependentSelect is already defined. Please check if other scripts use the same global variable name.');
    return;
  }

  // Check if the environment supports module.exports (such as Node.js)
  if (typeof module === 'object' && typeof module.exports === 'object') {
      // Export the factory function as a module
      module.exports = factory();
  } else {
      // Attach the factory function to the global object
      global.crmDependentSelect = factory();
  }
}(this, function() {
  'use strict';

  // Ensure native methods aren't overridden
  const originalForEach = Array.prototype.forEach;

  // Default configuration options for the plugin
  var defaultOptions = {
      debug: false
  };

  // Constructor for the plugin object
  function CrmDependentSelect(element, options) {
    if (typeof element === 'string') {
      this.element = document.querySelector(element);
    } else if (element instanceof HTMLElement) {
      this.element = element;
    } else {
      console.warn('Invalid element parameter: must be a CSS selector or DOM element.');
      return;
    }

    if (!this.element) {
      console.warn('The specified element does not exist.');
      return;
    }

    this.validateElement();

    // Merge user options with default options
    this.settings = Object.assign({}, defaultOptions, options);

    try {
      this.init();
    } catch (e) {
      console.error('An error occurred while initializing crmDependentSelect:', e);
    }
  }

  // Method to get parent information of the element
  CrmDependentSelect.prototype.getParentInfo = function() {
      const parentId = this.element.dataset.parent;
      const isParentCustom = this.element.getAttribute("data-parent-custom");
      const finalParentId = isParentCustom !== '0' ? `custom_${parentId}` : parentId;
      const parent = document.querySelector(`.crm-container select[name^="${finalParentId}"]`);

      return {
          parentId: parentId,
          parent: parent
      };
  };

  // Method to validate the element
  CrmDependentSelect.prototype.validateElement = function() {
      if (!this.element) {
          throw new Error('The selected element does not exist');
      }
      if (!this.element.closest('.crm-container')) {
          throw new Error('The selected element must be inside a ".crm-container"');
      }

      const { parentId, parent } = this.getParentInfo();

      if (!parentId) {
          throw new Error('The selected element must have a "data-parent" attribute');
      }

      if (!parent) {
          throw new Error('The dependent parent element does not exist');
      }
  }

  // Method to initialize the plugin
  CrmDependentSelect.prototype.init = function() {
      // Assign the element to the container property
      this.container = this.element;
      this.setupDependentSelect();
  }

  // Method to setup the dependent select element
  CrmDependentSelect.prototype.setupDependentSelect = function() {
    // Make sure native Array.forEach method is not overridden
    if (Array.prototype.forEach !== originalForEach) {
      throw new Error('Array\'s forEach method seems to be overridden.');
    }

    // Get all original options from the select element
    const originalOptions = Array.from(this.container.querySelectorAll('option'));
    this.container.classList.add("depended");

    const { parent } = this.getParentInfo();

    // Function to update child options based on parent value
    const updateChildOptions = (parentVal) => {
      // Clear all current options
      this.container.innerHTML = '';

      // Add options that either have no parent filter or match the current parent value
      originalOptions.forEach(option => {
        if (!option.getAttribute('data-parent-filter') || option.getAttribute('data-parent-filter') === parentVal) {
          this.container.appendChild(option.cloneNode(true));
        }
      });

      // Reset selection to first option (avoid selecting non-existent options)
      if (this.container.options.length > 0) {
        this.container.selectedIndex = 0;
      }

      // Trigger change event to update dependent child selects
      // Using setTimeout to avoid potential event loop issues
      setTimeout(() => {
        const event = new Event('change', { bubbles: true });
        this.container.dispatchEvent(event);
      }, 0);
    }

    // Initialize child options with current parent value
    updateChildOptions(parent.value);

    // Listen for parent value changes
    parent.addEventListener('change', function() {
      updateChildOptions(parent.value);
    });
  }

  // Return the plugin constructor
  return CrmDependentSelect;
}));
