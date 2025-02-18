/**
 * jquery.select2.selectAll.js
 * 
 * A Select2 extension that adds "Select All" and "Unselect All" functionality to Select2 dropdowns.
 *
 * @description
 * This extension creates a custom adapter for Select2 that adds "Select All" and "Unselect All"
 * buttons to the dropdown of multi-select Select2 instances. These buttons allow users to quickly
 * select or unselect all options in the dropdown.
 *
 * @usage
 * 1. Include this file after loading jQuery and Select2.
 * 2. Initialize Select2 with the custom adapter:
 *  $('#your-select-element').select2({
 *    dropdownAdapter: $.fn.select2.amd.require('select2/selectAllAdapter')
 *  });
 *
 * @dependencies
 * - jQuery (https://jquery.com)
 * - Select2 (https://select2.org)
 *
 * @source
 * This script is based on the code from:
 * https://jsfiddle.net/beaver71/tjvjytp3/
 * Modified and packaged as a reusable extension.
 * The original code was written for Select2 version 4.0.5. When adapting it for the latest version
 * (4.1.0-rc.0), some modifications were necessary due to changes in Select2's internal structure
 * and API. The custom adapter code has been updated to be compatible with version 4.1.0-rc.0,
 * addressing issues that arose from differences in how Select2 handles custom adapters in the
 * newer version. These changes ensure that the "Select All" and "Unselect All" functionality
 * works correctly with the latest Select2 release while maintaining backwards compatibility
 * where possible.
 */

(function ($) {
  'use strict';

  $.fn.select2.amd.define('select2/selectAllAdapter', [
    'select2/utils',
    'select2/dropdown',
    'select2/dropdown/attachBody'
  ], function (Utils, Dropdown, AttachBody) {

  function SelectAll() { }
  SelectAll.prototype.render = function (decorated) {
    var self = this,
      translations = self.options.get('translations'),
      $rendered = decorated.call(this),
      $selectAll = $(
        '<button class="btn" type="button" style="margin-left:6px;">' +
        '<i class="zmdi zmdi-check-square"></i> ' + translations.get('selectAll') +
        '</button>'
      ),
      $unselectAll = $(
        '<button class="btn" type="button" style="margin-left:6px;">' +
        '<i class="zmdi zmdi-square-o"></i> ' + translations.get('unselectAll') +
        '</button>'
      ),
      $btnContainer = $('<div class="select2-selectall-actions" style="margin-top:3px; text-align:center;">').append($selectAll).append($unselectAll);

    if (!this.$element.prop("multiple")) {
      // This isn't a multi-select, don't add the buttons
      return $rendered;
    }
    $rendered.find('.select2-dropdown').prepend($btnContainer);
    $selectAll.on('click', function (e) {
      e.preventDefault();
      var $options = self.$element.find('option');
      var values = [];

      $options.each(function() {
        if (!this.disabled) {
          values.push(this.value);
        }
      });

      self.$element.val(values);
      self.$element.trigger('change');
      self.trigger('close');
    });
    $unselectAll.on('click', function (e) {
      e.preventDefault();
      self.$element.val([]);
      self.$element.trigger('change');
      self.trigger('close');
    });
    return $rendered;
  };

  return Utils.Decorate(
    Utils.Decorate(
      Dropdown,
      AttachBody
    ),
    SelectAll
  );
  });
})(jQuery);