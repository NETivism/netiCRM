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
 * A simple base class for objects that need to implement the selector api
 * interface. This class provides common functionality with regard to actions
 * and display names
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Core_Selector_Base {

  /**
   * the sort order which is computed from the columnHeaders
   *
   * @var array
   */
  protected $_order;

  /**
   * The permission mask for this selector
   *
   * @var string
   */
  protected $_permission = NULL;

  /**
   * The qfKey of the underlying search
   *
   * @var string
   */
  protected $_key;

  /**
   * Get an attribute for an action that matches the selector criteria.
   *
   * @param int $match the action to match against
   * @param string $attribute the attribute name to return (e.g., 'name', 'link', 'title')
   *
   * @return string|null the attribute value if found
   */
  public function getActionAttribute($match, $attribute = 'name') {
    $links = &$this->links();

    foreach ($link as $action => $item) {
      if ($match & $action) {
        return $item[$attribute];
      }
    }
    return NULL;
  }

  /**
   * Return a reference to the links array.
   *
   * This is a virtual function that must be redefined in inherited classes.
   *
   * @return array|null links definitions
   */
  public static function &links() {
    return NULL;
  }

  /**
   * Get the template filename derived from the class name.
   *
   * @param int|null $action the action being performed
   *
   * @return string template filename
   */
  public function getTemplateFileName($action = NULL) {
    return (str_replace('_', DIRECTORY_SEPARATOR, CRM_Utils_System::getClassName($this)) . ".tpl");
  }

  /**
   * Compute and return the sort order array for the given action.
   *
   * @param int $action the action being performed
   *
   * @return array elements that can be sorted along with their properties
   */
  public function &getSortOrder($action) {
    $columnHeaders = &$this->getColumnHeaders(NULL);

    if (!isset($this->_order)) {
      $this->_order = [];
      $start = 2;
      $firstElementNotFound = TRUE;
      if (!empty($columnHeaders)) {
        foreach ($columnHeaders as $k => $header) {
          $header = &$columnHeaders[$k];
          if (CRM_Utils_Array::arrayKeyExists('sort', $header)) {
            if ($firstElementNotFound && $header['direction'] != CRM_Utils_Sort::DONTCARE) {
              $this->_order[1] = &$header;
              $firstElementNotFound = FALSE;
            }
            else {
              $this->_order[$start++] = &$header;
            }
          }
          unset($header);
        }
      }
      if ($firstElementNotFound) {
        // CRM_Core_Error::fatal( "Could not find a valid sort directional element" );
      }
    }
    return $this->_order;
  }

  /**
   * Set the permission mask for this selector.
   *
   * @param string $permission permission string
   *
   * @return void
   */
  public function setPermission($permission) {
    $this->_permission = $permission;
  }

  /**
   * Get the plain language description (QILL) of the search criteria.
   *
   * @return string|null QILL description
   */
  public function getQill() {
    return NULL;
  }

  public function getSummary() {
    return NULL;
  }

  public function setKey($key) {
    $this->_key = $key;
  }

  public function getKey() {
    return $this->_key;
  }
}
