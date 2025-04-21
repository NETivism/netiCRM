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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */









/**
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Contribute_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   * @static
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   * @static
   */
  static $_properties = array(
    'contact_id',
    'contribution_id',
    'contact_type',
    'sort_name',
    'amount_level',
    'total_amount',
    'contribution_type',
    'contribution_type_id',
    'contribution_source',
    'contribution_referrer_type',
    'payment_instrument',
    'payment_instrument_id',
    'created_date',
    'receive_date',
    'thankyou_date',
    'contribution_status_id',
    'contribution_status',
    'trxn_id',
    'cancel_date',
    'cancel_reason',
    'product_name',
    'product_option',
    'is_test',
    'contribution_recur_id',
    'receipt_date',
    'receipt_id',
    'membership_id',
    'currency',
  );

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * what component context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_compContext = NULL;

  /**
   * queryParams is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   * @access protected
   */
  public $_queryParams;

  /**
   * represent the type of selector
   *
   * @var int
   * @access protected
   */
  protected $_action;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_contributionClause = NULL;

  /**
   * The query object
   *
   * @var string
   */
  protected $_query;

  /**
   * Class constructor
   *
   * @param array $queryParams array of parameters for query
   * @param int   $action - action of search basic or advanced.
   * @param string   $contributionClause if the caller wants to further restrict the search (used in contributions)
   * @param boolean $single are we dealing only with one contact?
   * @param int     $limit  how many contributions do we want returned
   *
   * @return CRM_Contact_Selector
   * @access public
   */
  function __construct(&$queryParams,
    $action = CRM_Core_Action::NONE,
    $contributionClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search',
    $compContext = NULL
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single = $single;
    $this->_limit = $limit;
    if ($context == 'search' && strstr(CRM_Utils_System::currentPath(), 'participant')) {
      $context = 'participant';
    }
    if ($context == 'search' && strstr(CRM_Utils_System::currentPath(), 'membership')) {
      $context = 'membership';
    }
    $this->_context = $context;
    $this->_compContext = $compContext;

    $this->_contributionClause = $contributionClause;

    // type of selector
    $this->_action = $action;

    // refs #32894, custom default properties for performance reason
    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams, self::returnProperties($this->_queryParams), NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CONTRIBUTE
    );

    $this->_query->_distinctComponentClause = " DISTINCT(civicrm_contribution.id)";
  }
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   * @access public
   *
   */
  static function &links($componentId = NULL, $componentAction = NULL, $key = NULL, $compContext = NULL) {
    $extraParams = NULL;
    if ($componentId) {
      $extraParams = "&compId={$componentId}&compAction={$componentAction}";
    }
    if ($compContext) {
      $extraParams .= "&compContext={$compContext}";
    }
    if ($key) {
      $extraParams .= "&key={$key}";
    }

    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=contribute{$extraParams}",
          'title' => ts('View Contribution'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts('Edit Contribution'),
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Receipt'),
          'url' => 'civicrm/contact/view/contribution/receipt',
          'qs' => "reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%",
          'title' => ts('Receipt'),
          'fe' => 1,
        ),
        CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('Tax Receipt'),
          'url' => 'civicrm/contribute/taxreceipt',
          'qs' => "reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%",
          'title' => ts('Receipt'),
          'fe' => 1,
        ),
      );
    }
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['status'] = ts('Contribution') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    if ($this->_limit) {
      $params['rowCount'] = $this->_limit;
    }
    else {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_contributionClause
    );
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_contributionClause
    );
    //CRM_Core_Error::debugDatabaseProfiling();

    // process the result of the query
    $rows = array();



    //CRM-4418 check for view/edit/delete
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit contributions')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviContribute')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    $qfKey = $this->_key;
    $componentId = $componentContext = NULL;
    if ($this->_context != 'contribute' && $this->_context != 'search') {
      $qfKey = CRM_Utils_Request::retrieve('key', 'String', CRM_Core_DAO::$_nullObject);
      $componentId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject);
      $componentAction = CRM_Utils_Request::retrieve('action', 'String', CRM_Core_DAO::$_nullObject);
      $componentContext = CRM_Utils_Request::retrieve('compContext', 'String', CRM_Core_DAO::$_nullObject);

      if (!$componentContext && $this->_compContext) {
        $componentContext = $this->_compContext;
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, NULL, FALSE, 'REQUEST');
      }
    }

    // get all contribution status
    $contributionStatusesName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $taxReceiptTypes = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_taxreceipt');
    $taxReceiptImplements = CRM_Utils_Hook::availableHooks('civicrm_validateTaxReceipt');
    $taxReceiptImplements = count($taxReceiptImplements);

    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType();

    $ids = array();
    while ($result->fetch()) {
      $row = array();
      $ids[] = $result->id;
      // prepare result from pseudo element
      $result->payment_instrument = $paymentInstruments[$result->payment_instrument_id];
      $result->contribution_status = $contributionStatuses[$result->contribution_status_id];
      $result->contribution_type = $contributionTypes[$result->contribution_type_id];

      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (property_exists($result, $property)) {
          $row[$property] = $result->$property;
        }
      }

      // add contribution status name
      $row['id'] = $result->id;
      $row['contribution_status_name'] = CRM_Utils_Array::value($row['contribution_status_id'], $contributionStatusesName);

      if ($result->is_pay_later && CRM_Utils_Array::value('contribution_status_name', $row) == 'Pending') {
        $row['contribution_status_suffix'] = '(' . ts('Pay Later') . ')';
      }
      elseif (CRM_Utils_Array::value('contribution_status_name', $row) == 'Pending') {
        $row['contribution_status_suffix'] = '(' . ts('Incomplete Transaction') . ')';
      }

      if ($row['is_test']) {
        $row['contribution_type'] = $row['contribution_type'] . ' (' . ts('test') . ')';
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->contribution_id;

      $actions = array(
        'id' => $result->contribution_id,
        'cid' => $result->contact_id,
        'cxt' => $this->_context,
      );

      $links = self::links($componentId, $componentAction, $qfKey, $componentContext);

      // receipt only available when receipt id generated.
      $deductible = CRM_Contribute_BAO_ContributionType::deductible($result->contribution_type_id);
      if (empty($result->receipt_id) || empty($result->receipt_date) || $result->contribution_status_id != 1 || !$deductible) {
        unset($links[CRM_Core_Action::PREVIEW]);
      }
      if (empty($taxReceiptTypes[$result->contribution_type_id]) || !$taxReceiptImplements) {
        unset($links[CRM_Core_Action::FOLLOWUP]);
      }
      $row['action'] = CRM_Core_Action::formLink($links, $mask, $actions);

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
        $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );

      if (CRM_Utils_Array::value('amount_level', $row)) {
        CRM_Event_BAO_Participant::fixEventLevel($row['amount_level']);
      }

      if(!empty($row['payment_instrument'])){
        $invoiceLink = CRM_Contribute_BAO_Contribution_Utils::invoiceLink($row['contribution_id'], TRUE);
        if($invoiceLink){
          $row['payment_instrument'] .= '<br>(<a href="'.$invoiceLink.'" target="_blank">'.ts('Invoice').'</a>)';
        }
      }

      $rows[] = $row;
    }
    if(!empty($ids)){
      $details = CRM_Contribute_BAO_Contribution::getComponentDetails($ids);
      $premiums = self::getContributionPremiums($ids);
      $referrers = self::getContributionReferrers($ids);
      foreach($rows as $k => $r){
        if (!empty($details[$r['id']])){
          $rows[$k]['ids'] = $details[$r['id']];
        }
        if (!empty($referrers[$r['id']])) {
          $rows[$k]['contribution_referrer_type'] = $referrers[$r['id']];
        }
        if (!empty($premiums[$r['id']])) {
          $rows[$k]['product_name'] = $premiums[$r['id']]['product_name'];
          $rows[$k]['product_option'] = $premiums[$r['id']]['product_option'];
        }
      }
    }
    return $rows;
  }

  /**
   *
   * @return array   $qill         which contains an array of strings
   * @access public
   */

  // the current internationalisation is bad, but should more or less work
  // for most of "European" languages
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = array(
        array(
          'name' => ts('Transaction ID'),
          'sort' => 'trxn_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Receipt ID'),
          'sort' => 'receipt_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Payment Instrument'),
          'sort' => 'payment_instrument_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Amount'),
          'sort' => 'total_amount',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('Contribution Type'),
          'sort' => 'contribution_type_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Source'),
          'sort' => 'contribution_source',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Created Date'),
          'sort' => 'created_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Received'),
          'sort' => 'receive_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        /*
                                          array(
                                                'name'      => ts('Thank-you Sent'),
                                                'sort'      => 'thankyou_date',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          */
        array(
          'name' => ts('Status'),
          'sort' => 'contribution_status_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        /*
                                          array(
                                                'name'      => ts('Premium'),
                                                'sort'      => 'product_name',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          */
        array('desc' => ts('Actions')),
      );

      if (!$this->_single) {
        $pre = array(
          array('desc' => ts('Contact Type')),
          array(
            'name' => '#',
            'title' => ts('Contribution ID'),
            'sort' => 'contribution_id',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Name'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
        );
        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
      else {
        $pre = array(
          array(
            'name' => '#',
            'title' => ts('Contribution ID'),
            'sort' => 'contribution_id',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
        );
        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
    }
    return self::$_columnHeaders;
  }

  function alphabetQuery() {
    return $this->_query->searchQuery(NULL, NULL, NULL, FALSE, FALSE, TRUE);
  }

  function &getQuery() {
    return $this->_query;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviCRM Contribution Search');
  }

  function getSummary() {
    return $this->_query->summaryContribution($this->_context);
  }

  public static function returnProperties($queryParams) {
    $returnProperties = CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE);

    // never used
    unset($returnProperties['accounting_code']);
    unset($returnProperties['contribution_note']);

    // for performance reason, show these values when getRows
    unset($returnProperties['payment_instrument']);
    unset($returnProperties['contribution_status']);
    unset($returnProperties['contribution_type']);

    // do not include queries when no product related search
    $includeProduct = FALSE;
    $includeReferrer = FALSE;
    $includedCustoms = array();
    foreach($queryParams as $query) {
      if ($query[0] === 'product_name') {
        $includeProduct = TRUE;
      }
      if (preg_match('/^(contribution_referrer|contribution_utm|contribution_landing)/', $query[0])) {
        $includeReferrer = TRUE;
      }
      if (preg_match('/^custom_\d+/', $query[0])) {
        $includedCustoms[] = $query[0];
      }
    }
    if (!$includeProduct) {
      unset($returnProperties['product_name']);
      unset($returnProperties['sku']);
      unset($returnProperties['product_option']);
      unset($returnProperties['fulfilled_date']);
    }
    if (!$includeReferrer) {
      unset($returnProperties['contribution_referrer_type']);
    }
    foreach($returnProperties as $field => $return) {
      if (preg_match('/^custom_\d+/', $field)) {
        if (!in_array($field, $includedCustoms)) {
          unset($returnProperties[$field]);
        }
      }
    }

    return $returnProperties;
  }

  public static function getContributionPremiums($ids) {
    $sql = "SELECT cp.contribution_id, cp.product_option, p.name FROM civicrm_contribution_product cp INNER JOIN civicrm_product p ON p.id = cp.product_id WHERE cp.contribution_id IN (%1)";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array(CRM_Utils_Array::implode(',', $ids), 'CommaSeparatedIntegers')
    ));
    $premiums = array();
    while($dao->fetch()) {
      $premiums[$dao->contribution_id]['product_name'] = $dao->name;
      $premiums[$dao->contribution_id]['product_option'] = $dao->product_option;
    }
    return $premiums;
  }

  public static function getContributionReferrers($ids) {
    $params = array(
      'entityTable' => 'civicrm_contribution',
      'entityId' => $ids,
    );
    $selector = new CRM_Track_Selector_Track($params);
    $dao = $selector->getQuery("entity_id, referrer_type", 'GROUP BY entity_table, entity_id');
    $referrer = array();
    while($dao->fetch()){
      $referrer[$dao->entity_id] = $dao->referrer_type;
    }
    return $referrer;
  }
}
//end of class

