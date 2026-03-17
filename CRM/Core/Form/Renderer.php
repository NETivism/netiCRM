<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * customize the output to meet our specific requirements
 */
class CRM_Core_Form_Renderer extends HTML_QuickForm_Renderer_ArraySmarty {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * the converter from array size to css class
   *
   * @var array
   * @static
   */
  public static $_sizeMapper = [
    2 => 'two',
    4 => 'four',
    8 => 'eight',
    12 => 'twelve',
    20 => 'medium',
    30 => 'big',
    45 => 'huge',
  ];

  /**
   * Class constructor.
   */
  public function __construct() {
    $template = CRM_Core_Smarty::singleton();
    parent::__construct($template);
  }

  /**
   * Static instance provider (Singleton pattern).
   *
   * @return CRM_Core_Form_Renderer
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Form_Renderer();
    }
    return self::$_singleton;
  }

  /**
   * Represent an element as an array for Smarty.
   *
   * Adds CiviCRM-specific enhancements to make the HTML compliant with CSS standards.
   *
   * @param HTML_QuickForm_element &$element the form element object
   * @param bool $required whether the element is required
   * @param string|null $error error message associated with the element
   *
   * @return array associative array representing the element
   */
  public function _elementToArray(&$element, $required, $error) {
    self::updateAttributes($element, $required, $error);

    $el = parent::_elementToArray($element, $required, $error);

    // add label html
    if (!empty($el['label'])) {
      $id = $element->getAttribute('id');
      if (!empty($id)) {
        $el['label'] = '<label for="' . $id . '">' . $el['label'] . '</label>';
      }
      else {
        $el['label'] = "<label>{$el['label']}</label>";
      }
    }
    // Active form elements
    if (empty($el['frozen'])) {
      if ($element->getType() == 'group' && $element->getAttribute('allowClear')) {
        $this->appendUnselectButton($el, $element);
      }
    }

    return $el;
  }

  /**
   * Update the attributes of an element for better styling.
   *
   * Adds IDs and CSS classes based on element type and validation status.
   *
   * @param HTML_QuickForm_element &$element the form element object
   * @param bool $required whether the element is required
   * @param string|null $error error message
   *
   * @return void
   */
  public static function updateAttributes(&$element, $required, $error) {
    // lets create an id for all input elements, so we can generate nice label tags
    // to make it nice and clean, we'll just use the elementName if it is non null
    $attributes = [];
    if (!$element->getAttribute('id')) {
      $name = $element->getAttribute('name');
      if ($name) {
        $attributes['id'] = str_replace(
          [']', '['],
          ['', '_'],
          $name
        );
      }
    }

    $class = $element->getAttribute('class');
    $type = $element->getType();
    if (empty($class)) {
      $class = 'form-' . $type;

      if ($type == 'text') {
        $size = $element->getAttribute('size');
        if (!empty($size)) {
          if (CRM_Utils_Array::arrayKeyExists($size, self::$_sizeMapper)) {
            $class = $class . ' ' . self::$_sizeMapper[$size];
          }
        }
      }
    }

    if ($required) {
      $class .= ' required';
    }

    if ($error) {
      $class .= ' error';
    }

    $attributes['class'] = $class;
    $element->updateAttributes($attributes);
  }

  /**
   * Append an "unselect" link to radio group elements.
   *
   * @param array &$el element array for Smarty
   * @param HTML_QuickForm_element $field the form element object
   *
   * @return void
   */
  public function appendUnselectButton(&$el, $field) {
    // Initially hide if not needed
    // Note: visibility:hidden prevents layout jumping around unlike display:none
    $display = $field->getValue() !== NULL ? '' : ' style="visibility:hidden;"';
    $el['html'] .= '<span class="crm-clear-link"><a href="#" title="unselect" onclick="unselectRadio(\''.$el['name'].'\'); return false;">'.ts('unselect').'</a></span>';
  }
}
// end CRM_Core_Form_Renderer
