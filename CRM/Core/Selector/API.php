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
 * This interface defines the set of functions a class needs to implement
 * to use the CRM/Selector object.
 *
 * Using this interface allows us to standardize on multiple things including
 * list display, pagination, sorting and export in multiple formats (CSV is
 * supported right now, XML support will be added as and when needed
 *
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
interface CRM_Core_Selector_API {

  /**
   * Add various key => value pairs to the params array for paging.
   *
   * @param int $action the action being performed
   * @param array &$params the array that the pagerParams will be inserted into
   *
   * @return void
   */
  public function getPagerParams($action, &$params);

  /**
   * Get the sort order array for the given action.
   *
   * @param int $action the action being performed
   *
   * @return array the elements that can be sorted along with their properties
   */
  public function &getSortOrder($action);

  /**
   * Get the column headers as an array of tuples: (name, sortName).
   *
   * @param int|null $action the action being performed
   * @param string|null $type result set type (e.g., 'web', 'email', 'csv')
   *
   * @return array the column headers to be displayed
   */
  public function &getColumnHeaders($action = NULL, $type = NULL);

  /**
   * Get the total number of rows for this action.
   *
   * @param int $action the action being performed
   *
   * @return int total number of rows
   */
  public function getTotalCount($action);

  /**
   * Get all the rows for the given offset and rowCount.
   *
   * @param int $action the action being performed
   * @param int $offset the row number to start from
   * @param int $rowCount the number of rows to return
   * @param string|CRM_Utils_Sort $sort sort order definition
   * @param string|null $type result set type (e.g., 'web', 'email', 'csv')
   *
   * @return array array of rows
   */
  public function &getRows($action, $offset, $rowCount, $sort, $type = NULL);

  /**
   * Get the template (.tpl) filename for this selector.
   *
   * @param int|null $action the action being performed
   *
   * @return string template filename
   */
  public function getTemplateFileName($action = NULL);

  /**
   * Get the filename for the exported file.
   *
   * @param string $type the type of export required (e.g., 'csv', 'xml')
   *
   * @return string filename
   */
  public function getExportFileName($type = 'csv');
}
