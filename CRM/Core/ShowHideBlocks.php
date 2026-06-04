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
 * Manages show/hide toggle behavior for collapsible form sections
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Core_ShowHideBlocks {

  /**
   * The icons prefixed to block show and hide links.
   *
   * @var string
   */
  public static $_showIcon;
  public static $_hideIcon;

  /**
   * The array of ids of blocks that will be shown
   *
   * @var array
   */
  protected $_show;

  /**
   * The array of ids of blocks that will be hidden
   *
   * @var array
   */
  protected $_hide;

  /**
   * Class constructor.
   *
   * @param array|null $show Initial value of show array.
   * @param array|null $hide Initial value of hide array.
   */
  public function __construct($show = NULL, $hide = NULL) {
    if (!empty($show)) {
      $this->_show = $show;
    }
    else {
      $this->_show = [];
    }

    if (!empty($hide)) {
      $this->_hide = $hide;
    }
    else {
      $this->_hide = [];
    }
  }

  /**
   * Load icon vars used in hide and show links.
   */
  public static function setIcons() {
    if (!isset(self::$_showIcon)) {
      $config = CRM_Core_Config::singleton();
      self::$_showIcon = '<img src="' . $config->resourceBase . 'i/TreePlus.gif" class="action-icon" alt="' . ts('show field or section') . '"/>';
      self::$_hideIcon = '<img src="' . $config->resourceBase . 'i/TreeMinus.gif" class="action-icon" alt="' . ts('hide field or section') . '"/>';
    }
  }

  /**
   * Add the values from this class to the template.
   */
  public function addToTemplate() {
    $hide = $show = '';

    $first = TRUE;
    foreach (array_keys($this->_hide) as $h) {
      if (!$first) {
        $hide .= ',';
      }
      $hide .= "'$h'";
      $first = FALSE;
    }

    $first = TRUE;
    foreach (array_keys($this->_show) as $s) {
      if (!$first) {
        $show .= ',';
      }
      $show .= "'$s'";
      $first = FALSE;
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('hideBlocks', $hide);
    $template->assign_by_ref('showBlocks', $show);
  }

  /**
   * Add a value to the show array.
   *
   * @param string $name ID to be added.
   */
  public function addShow($name) {
    $this->_show[$name] = 1;
    if (CRM_Utils_Array::arrayKeyExists($name, $this->_hide)) {
      unset($this->_hide[$name]);
    }
  }

  /**
   * Add a value to the hide array.
   *
   * @param string $name ID to be added.
   */
  public function addHide($name) {
    $this->_hide[$name] = 1;
    if (CRM_Utils_Array::arrayKeyExists($name, $this->_show)) {
      unset($this->_show[$name]);
    }
  }

  /**
   * Create a well-formatted HTML link from the smaller pieces.
   *
   * @param string $name Name of the link.
   * @param string $href The href attribute.
   * @param string $text The link text.
   * @param string $js JavaScript code.
   *
   * @return string The formatted HTML link.
   */
  public static function linkHtml($name, $href, $text, $js) {
    return '<a name="' . $name . '" id="' . $name . '" href="' . $href . '" ' . $js . ">$text</a>";
  }

  /**
   * Create links that we can use in the form.
   *
   * @param CRM_Core_Form $form The form object.
   * @param string $prefix The attribute that we are referencing.
   * @param string $showLinkText The text to be shown for the show link.
   * @param string $hideLinkText The text to be shown for the hide link.
   * @param bool $assign Whether to assign the values to the form.
   *
   * @return array|void
   */
  public static function links(&$form, $prefix, $showLinkText, $hideLinkText, $assign = TRUE) {
    $showCode = "show('id_{$prefix}'); hide('id_{$prefix}_show');";
    $hideCode = "hide('id_{$prefix}'); show('id_{$prefix}_show'); return false;";

    self::setIcons();
    $values = [];
    $values['show'] = self::linkHtml("{$prefix}_show", "#{$prefix}_hide", self::$_showIcon . $showLinkText, "onclick=\"$showCode\"");
    $values['hide'] = self::linkHtml("{$prefix}_hide", "#{$prefix}", self::$_hideIcon . $hideLinkText, "onclick=\"$hideCode\"");

    if ($assign) {
      $form->assign($prefix, $values);
    }
    else {
      return $values;
    }
  }

  /**
   * Create HTML link elements that we can use in the form.
   *
   * @param CRM_Core_Form $form The form object.
   * @param int $index The current index of the element being processed.
   * @param int $maxIndex The max number of elements that will be processed.
   * @param string $prefix The attribute that we are referencing.
   * @param string $showLinkText The text to be shown for the show link.
   * @param string $hideLinkText The text to be shown for the hide link.
   * @param string|null $elementType The element type.
   * @param string|null $hideLink The hide block string.
   */
  public function linksForArray(&$form, $index, $maxIndex, $prefix, $showLinkText, $hideLinkText, $elementType = NULL, $hideLink = NULL) {
    $showHidePrefix = str_replace(["]", "["], ["", "_"], $prefix);
    $showHidePrefix = "id_" . $showHidePrefix;
    if ($index == $maxIndex) {
      $showCode = $hideCode = "return false;";
    }
    else {
      $next = $index + 1;
      if ($elementType) {
        $showCode = "show('{$prefix}_{$next}_show','table-row'); return false;";
        if ($hideLink) {
          $hideCode = $hideLink;
        }
        else {
          $hideCode = "hide('{$prefix}_{$next}_show','table-row'); hide('{$prefix}_{$next}'); return false;";
        }
      }
      else {
        $showCode = "show('{$showHidePrefix}_{$next}_show'); return false;";
        $hideCode = "hide('{$showHidePrefix}_{$next}_show'); hide('{$showHidePrefix}_{$next}'); return false;";
      }
    }

    self::setIcons();
    if ($elementType) {
      $form->addElement(
        'link',
        "{$prefix}[{$index}][show]",
        NULL,
        "#{$prefix}_{$index}",
        self::$_showIcon . $showLinkText,
        ['onclick' => "hide('{$prefix}_{$index}_show'); show('{$prefix}_{$index}','table-row');" . $showCode]
      );
      $form->addElement(
        'link',
        "{$prefix}[{$index}][hide]",
        NULL,
        "#{$prefix}_{$index}",
        self::$_hideIcon . $hideLinkText,
        ['onclick' => "hide('{$prefix}_{$index}'); show('{$prefix}_{$index}_show');" . $hideCode]
      );
    }
    else {
      $form->addElement(
        'link',
        "{$prefix}[{$index}][show]",
        NULL,
        "#{$prefix}_{$index}",
        self::$_showIcon . $showLinkText,
        ['onclick' => "hide('{$showHidePrefix}_{$index}_show'); show('{$showHidePrefix}_{$index}');" . $showCode]
      );
      $form->addElement(
        'link',
        "{$prefix}[{$index}][hide]",
        NULL,
        "#{$prefix}_{$index}",
        self::$_hideIcon . $hideLinkText,
        ['onclick' => "hide('{$showHidePrefix}_{$index}'); show('{$showHidePrefix}_{$index}_show');" . $hideCode]
      );
    }
  }
}
