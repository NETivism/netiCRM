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
 *
 * This class extends the PEAR pager object by substituting standard default pager arguments
 * We also extract the pageId from either the GET variables or the POST variable (since we
 * use a POST to jump to a specific page). At some point we should evaluate if we want
 * to use Pager_Jumping instead. We've changed the format to allow navigation by jumping
 * to a page and also First, Prev CURRENT Next Last
 *
 */

class CRM_Utils_Pager extends Pager_Sliding {

  /**
   * Constants for pager configuration parameters.
   */
  public const ROWCOUNT = 50, PAGE_ID = 'crmPID', PAGE_ID_TOP = 'crmPID', PAGE_ID_BOTTOM = 'crmPID_B', PAGE_ROWCOUNT = 'crmRowCount';

  /**
   * The output of the pager. A name/value array with various keys
   * that an application uses to render the pager display.
   *
   * @var array<string, mixed>
   */
  public $_response;

  /**
   * Construct and initialize the pager with default PEAR pager settings.
   *
   * Accepts a parameter array and assigns default values for PEAR
   * Pager_Sliding. Also computes the status message displaying
   * the current item range (e.g. "1 - 50 of 200").
   *
   * @param array<string, mixed> $params Configuration array with keys:
   *   - 'total' (int): Total count of items to be displayed.
   *   - 'status' (string|null): Status message template with %%StatusMessage%% token.
   *   - 'csvString' (string): Title of the CSV export link.
   *   - 'rowCount' (int): Number of items per page.
   *   - 'buttonTop' (string): Name of the top submit button.
   *   - 'buttonBottom' (string): Name of the bottom submit button.
   *   - 'pageID' (int): Optional initial page number.
   */
  public function __construct($params) {
    if ($params['status'] === NULL) {
      $params['status'] = ts('Contacts %%StatusMessage%%');
    }

    $params['path'] = '';

    $this->initialize($params);

    parent::__construct($params);

    list($offset, $limit) = $this->getOffsetAndRowCount();
    $start = $offset + 1;
    $end = $offset + $limit;
    if ($end > $params['total']) {
      $end = $params['total'];
    }

    if ($params['total'] == 0) {
      $statusMessage = '';
    }
    else {
      $statusMessage = ts('%1 - %2 of %3', [1 => $start, 2 => $end, 3 => $params['total']]);
    }
    $params['status'] = str_replace('%%StatusMessage%%', $statusMessage, $params['status']);

    $this->_response = [
      'first' => $this->_printFirstPage(),
      'back' => str_replace('&nbsp;', '', $this->_getBackLink()),
      'next' => str_replace('&nbsp;', '', $this->_getNextLink()),
      'last' => $this->_printLastPage(),
      'currentPage' => $this->getCurrentPageID(),
      'numPages' => $this->numPages(),
      'csvString' => CRM_Utils_Array::value('csvString', $params),
      'status' => CRM_Utils_Array::value('status', $params),
      'buttonTop' => CRM_Utils_Array::value('buttonTop', $params),
      'buttonBottom' => CRM_Utils_Array::value('buttonBottom', $params),
      'twentyfive' => $this->getPerPageLink(25),
      'fifty' => $this->getPerPageLink(50),
      'onehundred' => $this->getPerPageLink(100),
    ];

    /**
     * A page cannot have two variables with the same form name. Hence in the
     * pager display, we have a form submission at the top with the normal
     * page variable, but a different form element for one at the bottom
     *
     */
    $this->_response['titleTop'] = ts('Page %1 of %2', [1 => '<input size="2" maxlength="5" name="' . self::PAGE_ID . '" type="text" value="' . $this->_response['currentPage'] . '" />', 2 => $this->_response['numPages']]);
    $this->_response['titleBottom'] = ts('Page %1 of %2', [1 => '<input size="2" maxlength="5" name="' . self::PAGE_ID_BOTTOM . '" type="text" value="' . $this->_response['currentPage'] . '" />', 2 => $this->_response['numPages']]);
  }

  /**
   * Assign default pager options to the parameter array.
   *
   * Sets mode, URL variable, navigation labels, page size,
   * and other PEAR Pager_Sliding configuration values.
   *
   * @param array<string, mixed> $params The pager configuration array, modified by reference.
   *
   * @return array<string, mixed> The modified parameter array.
   */
  public function initialize(&$params) {
    /* set the mode for the pager to Sliding */

    $params['mode'] = 'Sliding';

    /* also set the urlVar to be a crm specific get variable */

    $params['urlVar'] = self::PAGE_ID;

    /* set this to a small value, since we dont use this functionality */

    $params['delta'] = 1;

    $params['totalItems'] = $params['total'];
    $params['append'] = TRUE;
    $params['fileName'] = '';
    $params['fixFileName'] = FALSE;
    $params['separator'] = '';
    $params['spacesBeforeSeparator'] = 1;
    $params['spacesAfterSeparator'] = 1;
    $params['extraVars'] = ['force' => 1];
    $params['excludeVars'] = ['reset', 'snippet'];

    // set previous and next text labels
    $params['prevImg'] = ' ' . ts('&lt; Previous');
    $params['nextImg'] = ts('Next &gt;') . ' ';

    // set first and last text fragments
    $params['firstPagePre'] = '';
    $params['firstPageText'] = ' ' . ts('&lt;&lt; First');
    $params['firstPagePost'] = '';

    $params['lastPagePre'] = '';
    $params['lastPageText'] = ts('Last &gt;&gt;') . ' ';
    $params['lastPagePost'] = '';

    if (isset($params['pageID'])) {
      $params['currentPage'] = $this->getPageID($params['pageID'], $params);
    }

    $params['perPage'] = $this->getPageRowCount($params['rowCount']);

    return $params;
  }

  /**
   * Determine the current page number from GET/POST variables.
   *
   * Follows a priority hierarchy: POST overrides GET, and a POST
   * from the top pager overrides a POST from the bottom pager.
   *
   * @param int $defaultPageId The fallback page ID if none is found in request variables.
   * @param array<string, mixed> $params The pager configuration array (passed by reference).
   *
   * @return int The resolved page number to display.
   */
  public function getPageID($defaultPageId, &$params) {
    // POST has higher priority than GET vars
    // else if a value is set that has higher priority and finally the GET var
    $currentPage = $defaultPageId;
    if (!empty($_POST)) {
      if (isset($_POST[CRM_Utils_Array::value('buttonTop', $params)]) && isset($_POST[self::PAGE_ID])) {
        $currentPage = max((int )@$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($_POST[$params['buttonBottom']]) && isset($_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int )@$_POST[self::PAGE_ID_BOTTOM], 1);
      }
      elseif (isset($_POST[self::PAGE_ID])) {
        $currentPage = max((int )@$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int )@$_POST[self::PAGE_ID_BOTTOM]);
      }
    }
    elseif (isset($_GET[self::PAGE_ID])) {
      $currentPage = max((int )@$_GET[self::PAGE_ID], 1);
    }
    return $currentPage;
  }

  /**
   * Get the number of rows to display from GET/POST variables.
   *
   * POST takes priority over GET. Falls back to the provided default.
   *
   * @param int $defaultPageRowCount The default row count if not set in request variables.
   *
   * @return int The row count value to use.
   */
  public function getPageRowCount($defaultPageRowCount = self::ROWCOUNT) {
    // POST has higher priority than GET vars
    if (isset($_POST[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int )@$_POST[self::PAGE_ROWCOUNT], 1);
    }
    elseif (isset($_GET[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int )@$_GET[self::PAGE_ROWCOUNT], 1);
    }
    else {
      $rowCount = $defaultPageRowCount;
    }
    return $rowCount;
  }

  /**
   * Calculate the SQL offset and row count for the current page.
   *
   * @return array{0: int, 1: int} An array of [offset, rowCount].
   */
  public function getOffsetAndRowCount() {
    $pageId = $this->getCurrentPageID();
    if (!$pageId) {
      $pageId = 1;
    }

    $offset = ($pageId - 1) * $this->_perPage;

    return [$offset, $this->_perPage];
  }

  /**
   * Generate an HTML link to change the number of rows displayed per page.
   *
   * If the requested per-page value matches the current value, returns
   * plain text instead of a link.
   *
   * @param int $perPage The number of rows per page for this link.
   *
   * @return string The HTML link or plain text.
   */
  public function getPerPageLink($perPage) {
    if ($perPage != $this->_perPage) {
      $href = CRM_Utils_System::makeURL(self::PAGE_ROWCOUNT) . $perPage;
      $link = sprintf(
        '<a href="%s" %s>%s</a>',
        $href,
        $this->_classString,
        $perPage
      ) . $this->_spacesBefore . $this->_spacesAfter;
    }
    else {
      $link = $this->_spacesBefore . $perPage . $this->_spacesAfter;
    }
    return $link;
  }

  /**
   * Render a pager navigation link using the appropriate HTTP method.
   *
   * Generates an anchor tag for either GET or POST navigation,
   * applying XSS filtering and proper URL construction.
   *
   * @param string $altText Alternative text for the link (used as the title attribute).
   * @param string $linkText The visible text content of the link.
   *
   * @return string The rendered HTML link, or empty string if HTTP method is unsupported.
   */
  public function _renderLink($altText, $linkText) {
    if ($this->_httpMethod == 'GET') {
      $query = [];
      foreach ($this->_linkData as $key => $val) {
        $val = CRM_Utils_String::xssFilter($val);
        $key = CRM_Utils_String::xssFilter($key);
        if ($key === 'q') {
          $path = $val;
        }
        else {
          $query[$key] = $val;
        }
      }
      $query = http_build_query($query);
      $href = CRM_Utils_System::url($path, $query);
      $onclick = '';
      if (CRM_Utils_Array::arrayKeyExists($this->_urlVar, $this->_linkData)) {
        $onclick = str_replace('%d', $this->_linkData[$this->_urlVar], $this->_onclick);
      }
      return sprintf(
        '<a href="%s"%s%s%s%s title="%s">%s</a>',
        htmlentities($this->_url . $href, ENT_COMPAT, 'UTF-8'),
        empty($this->_classString) ? '' : ' '.$this->_classString,
        empty($this->_attributes) ? '' : ' '.$this->_attributes,
        empty($this->_accesskey) ? '' : ' accesskey="'.$this->_linkData[$this->_urlVar].'"',
        empty($onclick) ? '' : ' onclick="'.$onclick.'"',
        $altText,
        $linkText
      );
    }
    elseif ($this->_httpMethod == 'POST') {
      $href = $this->_url;
      if (!empty($_GET)) {
        $href .= '?' . $this->_http_build_query_wrapper($_GET);
      }
      return sprintf(
        "<a href='javascript:void(0)' onclick='%s'%s%s%s title='%s'>%s</a>",
        $this->_generateFormOnClick($href, $this->_linkData),
        empty($this->_classString) ? '' : ' '.$this->_classString,
        empty($this->_attributes) ? '' : ' '.$this->_attributes,
        empty($this->_accesskey) ? '' : ' accesskey=\''.$this->_linkData[$this->_urlVar].'\'',
        $altText,
        $linkText
      );
    }
    return '';
  }
}
