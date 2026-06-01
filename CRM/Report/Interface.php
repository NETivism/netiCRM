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
 * Interface for report implementations
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
interface CRM_Report_Interface {

  /**
   * The constructor gets the submitted form values.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues);

  /**
   * Builds the quickform for this search.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public function buildForm(&$form);

  /**
   * Count of records that match the current input parameters.
   * Used by pager.
   *
   * @return int
   */
  public function count();

  /**
   * Summary information for the query that can be displayed in the template.
   * This is useful to pass total / sub total information if needed.
   *
   * @return string
   */
  public function summary();

  /**
   * List of contact ids that match the current input parameters.
   * Used by different tasks. Will be also used to optimize the
   * 'all' query below to avoid excessive LEFT JOIN blowup.
   *
   * @param int $offset
   * @param int $rowcount
   * @param string|array $sort
   *
   * @return array
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL);

  /**
   * Retrieve all the values that match the current input parameters.
   * Used by the selector.
   *
   * @param int $offset
   * @param int $rowcount
   * @param string|array $sort
   * @param bool $includeContactIDs
   *
   * @return array
   */
  public function all(
    $offset = 0,
    $rowcount = 0,
    $sort = NULL,
    $includeContactIDs = FALSE
  );

  /**
   * The from clause for the query.
   *
   * @return string
   */
  public function from();

  /**
   * The where clause for the query.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE);

  /**
   * The template FileName to use to display the results.
   *
   * @return string
   */
  public function templateFile();

  /**
   * Returns an array of column headers and field names and sort options.
   *
   * @return array
   */
  public function &columns();
}
