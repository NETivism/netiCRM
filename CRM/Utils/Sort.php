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
 * Base class to provide generic sort functionality for tabular data.
 *
 * Inspired by Drupal's tablesort.inc. Since CRM_Utils_Pager and this class
 * share similar patterns, method names should be kept consistent when
 * introducing additional functionality.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */
class CRM_Utils_Sort {

  /**
   * Sort direction constants.
   *
   * @var int
   */
  public const ASCENDING = 1, DESCENDING = 2, DONTCARE = 4,

    /**
     * GET/POST parameter names for sort state.
     *
     * @var string
     */
    SORT_ID = 'crmSID', SORT_DIRECTION = 'crmSortDirection', SORT_ORDER = 'crmSortOrder';

  /**
   * Name of the sort instance, used to isolate session variables.
   *
   * @var string
   */
  protected $_name;

  /**
   * Array of sortable column definitions, keyed by weight/index.
   *
   * Each entry contains 'name' (column), 'direction' (int), and 'title' (label).
   *
   * @var array<int, array{name: string, direction: int, title: string}>
   */
  public $_vars;

  /**
   * Base URL used for generating sort links in table headers.
   *
   * @var string
   */
  protected $_link;

  /**
   * Name of the URL query parameter used for the sort identifier.
   *
   * @var string
   */
  protected $_urlVar;

  /**
   * Index of the column currently being sorted on.
   *
   * @var int
   */
  protected $_currentSortID;

  /**
   * Current sort direction (ASCENDING, DESCENDING, or DONTCARE).
   *
   * @var int
   */
  protected $_currentSortDirection;

  /**
   * Generated sort link HTML for each sortable column, keyed by column name.
   *
   * @var array<string, array{link: string}>
   */
  public $_response;

  /**
   * Constructor.
   *
   * Initializes sortable columns and determines the current sort state
   * from the request or the given default sort order.
   *
   * @param array       $vars             Array of sortable column definitions, each
   *                                      containing 'sort' (column name), 'name'
   *                                      (display title), and optionally 'direction'.
   * @param string|null $defaultSortOrder  Default sort identifier (e.g., '1_u') to
   *                                      use when none is provided via GET parameters.
   */
  public function __construct(&$vars, $defaultSortOrder = NULL) {
    $this->_vars = [];
    $this->_response = [];

    foreach ($vars as $weight => $value) {
      $this->_vars[$weight] = [
        'name' => $value['sort'],
        'direction' => CRM_Utils_Array::value('direction', $value),
        'title' => $value['name'],
      ];
    }

    $this->_currentSortID = 1;
    if (isset($this->_vars[$this->_currentSortID])) {
      $this->_currentSortDirection = $this->_vars[$this->_currentSortID]['direction'];
    }
    $this->_urlVar = self::SORT_ID;
    $this->_link = CRM_Utils_System::makeUrl($this->_urlVar);

    $this->initialize($defaultSortOrder);
  }

  /**
   * Build and return the SQL ORDER BY clause based on the current sort state.
   *
   * @return string The ORDER BY clause (e.g., 'column_name asc'), or empty string
   *                if the current sort ID is not valid.
   */
  public function orderBy() {
    if (!CRM_Utils_Array::value($this->_currentSortID, $this->_vars)) {
      return '';
    }

    if ($this->_vars[$this->_currentSortID]['direction'] == self::ASCENDING ||
      $this->_vars[$this->_currentSortID]['direction'] == self::DONTCARE
    ) {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return $this->_vars[$this->_currentSortID]['name'] . ' asc';
    }
    else {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return $this->_vars[$this->_currentSortID]['name'] . ' desc';
    }
  }

  /**
   * Create the sort ID string to be used as a GET parameter value.
   *
   * @param int $index The column index.
   * @param int $dir   The sort direction constant (ASCENDING or DESCENDING).
   *
   * @return string The sort ID string (e.g., '2_d' or '1_u').
   */
  public static function sortIDValue($index, $dir) {
    return ($dir == self::DESCENDING) ? $index . '_d' : $index . '_u';
  }

  /**
   * Initialize the current sort ID and direction from the GET request.
   *
   * Parses the sort parameter (e.g., '2_d') from $_GET to determine
   * which column and direction to sort by. Falls back to the given
   * default sort order if no GET parameter is present.
   *
   * @param string|null $defaultSortOrder The default sort identifier (e.g., '1_u').
   *
   * @return void
   */
  public function initSortID($defaultSortOrder) {
    $url = CRM_Utils_Array::value(self::SORT_ID, $_GET, $defaultSortOrder);

    if (empty($url)) {
      return;
    }

    list($current, $direction) = explode('_', $url);

    // if current is wierd and does not exist in the vars array, skip
    if (!CRM_Utils_Array::arrayKeyExists($current, $this->_vars)) {
      return;
    }

    if ($direction == 'u') {
      $direction = self::ASCENDING;
    }
    elseif ($direction == 'd') {
      $direction = self::DESCENDING;
    }
    else {
      $direction = self::DONTCARE;
    }

    $this->_currentSortID = $current;
    $this->_currentSortDirection = $direction;
    $this->_vars[$current]['direction'] = $direction;
  }

  /**
   * Initialize sort state and build the response array of sort links.
   *
   * Calls initSortID() then generates HTML links for each sortable column
   * with appropriate CSS classes (sorting_asc, sorting_desc, sorting).
   *
   * @param string|null $defaultSortOrder The default sort identifier (e.g., '1_u').
   *
   * @return void
   */
  public function initialize($defaultSortOrder) {
    $this->initSortID($defaultSortOrder);

    $this->_response = [];

    $current = $this->_currentSortID;
    foreach ($this->_vars as $index => $item) {
      $name = $item['name'];
      $this->_response[$name] = [];

      $newDirection = ($item['direction'] == self::ASCENDING) ? self::DESCENDING : self::ASCENDING;

      if ($current == $index) {
        if ($item['direction'] == self::ASCENDING) {
          $class = 'sorting_asc';
        }
        else {
          $class = 'sorting_desc';
        }
      }
      else {
        $class = 'sorting';
      }

      $this->_response[$name]['link'] = '<a href="' . $this->_link . $this->sortIDValue($index, $newDirection) . '" class="' . $class . '">' . $item['title'] . '</a>';
    }
  }

  /**
   * Get the index of the column currently being sorted on.
   *
   * @return int The current sort column index.
   */
  public function getCurrentSortID() {
    return $this->_currentSortID;
  }

  /**
   * Get the current sort direction.
   *
   * @return int The current sort direction constant (ASCENDING, DESCENDING, or DONTCARE).
   */
  public function getCurrentSortDirection() {
    return $this->_currentSortDirection;
  }

  /**
   * Comparison callback function for sorting items by their 'weight' key.
   *
   * Intended for use with usort() or similar array sorting functions.
   *
   * @param array $a First item with a 'weight' key.
   * @param array $b Second item with a 'weight' key.
   *
   * @return int -1 if $a weight is less than or equal to $b weight, 1 otherwise.
   */
  public static function cmpFunc($a, $b) {
    return ($a['weight'] <= $b['weight']) ? -1 : 1;
  }
}
