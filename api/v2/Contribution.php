<?php
// $Id: Contribution.php 44883 2013-01-16 05:47:23Z yashodha $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * File for the CiviCRM APIv2 Contribution functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Contribute
 *
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Contribution.php 44883 2013-01-16 05:47:23Z yashodha $
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Contribute/PseudoConstant.php';

/**
 * Add or update a contribution
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        contribution_id of created or updated record
 * @static void
 * @access public
 */
function &civicrm_contribution_create(&$params) {
  _civicrm_initialize();

  $error = _civicrm_contribute_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = [];

  require_once 'CRM/Contribute/BAO/Contribution.php';
  $error = _civicrm_contribute_format_params($params, $values);
  if (civicrm_error($error)) {
    return $error;
  }

  $values['contact_id'] = $params['contact_id'];
  $values['source'] = CRM_Utils_Array::value('source', $params);
  $values['skipRecentView'] = TRUE;
  $ids = [];
  if (CRM_Utils_Array::value('id', $params)) {
    $ids['contribution'] = $params['id'];
  }
  $contribution = CRM_Contribute_BAO_Contribution::create($values, $ids);
  if (is_a($contribution, 'CRM_Core_Error')) {
    return civicrm_create_error(ts($contribution->_errors[0]['message']));
  }

  _civicrm_object_to_array($contribution, $contributeArray);

  return $contributeArray;
}
/*
 * Deprecated wrapper function
 * @deprecated
 */
function civicrm_contribution_add(&$params) {
  $result = civicrm_contribution_create($params);
  return $result;
}

/**
 * Retrieve a specific contribution, given a set of input params
 * If more than one contribution exists, return an error, unless
 * the client has requested to return the first found contact
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_contribution_get(&$params) {
  _civicrm_initialize();

  $values = [];
  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  $contributions = &civicrm_contribution_search($params);
  if (civicrm_error($contributions)) {
    return $contributions;
  }

  if (count($contributions) != 1 &&
    !$params['returnFirst']
  ) {
    return civicrm_create_error(ts('%1 contributions matching input params', [1 => count($contributions)]),
      $contributions
    );
  }

  $contributions = array_values($contributions);
  return $contributions[0];
}

/**
 * Delete a contribution
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 */
function civicrm_contribution_delete(&$params) {
  _civicrm_initialize();
  $contributionID = CRM_Utils_Array::value('contribution_id', $params);
  if (!$contributionID) {
    return civicrm_create_error(ts('Could not find contribution_id in input parameters'));
  }

  require_once 'CRM/Contribute/BAO/Contribution.php';
  if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionID)) {
    return civicrm_create_success();
  }
  else {
    return civicrm_create_error(ts('Could not delete contribution'));
  }
}

/**
 * Retrieve a set of contributions, given a set of input params
 *
 * @param  array   $params           (reference ) input parameters
 * @param array    $returnProperties Which properties should be included in the
 *                                   returned Contribution object. If NULL, the default
 *                                   set of properties will be included.
 *
 * @return array (reference )        array of contributions, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_contribution_search(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  $inputParams      = [];
  $returnProperties = [];
  $otherVars        = ['sort', 'offset', 'rowCount'];

  $sort     = NULL;
  $offset   = 0;
  $rowCount = 25;
  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $returnProperties[substr($n, 7)] = $v;
    }
    elseif (in_array($n, $otherVars)) {
      $$n = $v;
    }
    else {
      $inputParams[$n] = $v;
    }
  }

  // add is_test to the clause if not present
  if (!CRM_Utils_Array::arrayKeyExists('contribution_test', $inputParams)) {
    $inputParams['contribution_test'] = 0;
  }

  require_once 'CRM/Contribute/BAO/Query.php';
  require_once 'CRM/Contact/BAO/Query.php';
  if (empty($returnProperties)) {
    $returnProperties = CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
  }

  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);

  $query = new CRM_Contact_BAO_Query($newParams, $returnProperties, NULL);
  list($select, $from, $where, $having) = $query->query();

  $sql = "$select $from $where $having";

  if (!empty($sort)) {
    $sql .= " ORDER BY $sort ";
  }
  $sql .= " LIMIT $offset, $rowCount ";
  $dao = CRM_Core_DAO::executeQuery($sql);

  $contribution = [];
  while ($dao->fetch()) {
    $contribution[$dao->contribution_id] = $query->store($dao);
  }
  $dao->free();

  return $contribution;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 * @deprecated
 */
function &civicrm_contribution_format_create(&$params) {
  _civicrm_initialize();

  // return error if we have no params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $error = _civicrm_contribute_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }
  $values = [];
  $error = _civicrm_contribute_format_params($params, $values);
  if (civicrm_error($error)) {
    return $error;
  }

  $error = _civicrm_contribute_duplicate_check($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $error = _civicrm_contribute_receiptid_check($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $ids = [];
  CRM_Contribute_BAO_Contribution::resolveDefaults($params, TRUE);

  $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);
  if (is_a($contribution, 'CRM_Core_Error')) {
    return civicrm_create_error(ts($contribution->_errors[0]['message']));
  }
  _civicrm_object_to_array($contribution, $contributeArray);
  return $contributeArray;
}

/**
 * This function ensures that we have the right input contribution parameters
 *
 * We also need to make sure we run all the form rules on the params list
 * to ensure that the params are valid
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contribution.
 *
 * @return bool|CRM_Utils_Error
 * @access private
 */
function _civicrm_contribute_check_params(&$params) {
  static $required = ['contact_id' => NULL,
    'total_amount' => NULL,
    'contribution_type_id' => 'contribution_type',
  ];

  // params should be an array
  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  // cannot create a contribution with empty params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $valid = TRUE;
  $error = '';

  // check params for contribution id during update
  if (CRM_Utils_Array::value('id', $params)) {
    require_once 'CRM/Contribute/BAO/Contribution.php';
    $contributor = new CRM_Contribute_BAO_Contribution();
    $contributor->id = $params['id'];
    if (!$contributor->find(TRUE)) {
      return civicrm_create_error(ts('Contribution id is not valid'));
    }
    // do not check other field during update
    return [];
  }

  foreach ($required as $field => $eitherField) {
    if ($field == 'total_amount') {
      $amount = CRM_Utils_Array::value($field, $params);
      if(empty($amount) && $amount !== 0 && $amount !== "0") {
        $valid = FALSE;
        $error .= $field;
        break;
      }
    }
    elseif (!CRM_Utils_Array::value($field, $params)) {
      if ($eitherField && CRM_Utils_Array::value($eitherField, $params)) {
        continue;
      }
      $valid = FALSE;
      $error .= $field;
      break;
    }
  }

  if (!$valid) {
    return civicrm_create_error("Required fields not found for contribution $error");
  }

  return [];
}

/**
 * Check if there is a contribution with the same trxn_id or invoice_id
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contribution.
 *
 * @return array|CRM_Error
 * @access private
 */
function _civicrm_contribute_duplicate_check(&$params) {
  require_once 'CRM/Contribute/BAO/Contribution.php';
  $duplicates = [];
  $result = CRM_Contribute_BAO_Contribution::checkDuplicate($params, $duplicates);
  if ($result) {
    $d = CRM_Utils_Array::implode(', ', $duplicates);
    $error = CRM_Core_Error::createError("Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice ID. Contribution record ID(s) are: $d", CRM_Core_Error::DUPLICATE_CONTRIBUTION, 'Fatal', $d);
    return civicrm_create_error($error->pop(),
      $d
    );
  }
  else {
    return [];
  }
}

/**
 * Check if there is a contribution with the same receipt_id
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contribution.
 *
 * @return array|CRM_Error
 * @access private
 */
function _civicrm_contribute_receiptid_check(&$params) {
  $duplicates = [];
  $result = CRM_Contribute_BAO_Contribution::checkDuplicateReceipt($params, $duplicates);

  if ($result) {
    $d = CRM_Utils_Array::implode(', ', $duplicates);
    $error = CRM_Core_Error::createError("Duplicate error - existing contribution record(s) have a matching Receipt ID. Contribution record ID(s) are: $d", CRM_Core_Error::DUPLICATE_CONTRIBUTION, 'Fatal', $d);
    return civicrm_create_error($error->pop(),
      $d
    );
  }
  else {
    return [];
  }
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *                            '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_contribute_format_params(&$params, &$values, $create = FALSE) {
  return _civicrm_contribute_formatted_param($params, $values, $create);
}

/**
 * Process a transaction and record it against the contact.
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        contribution of created or updated record (or a civicrm error)
 * @static void
 * @access public
 *
 */
function civicrm_contribute_transact($params) {
  civicrm_initialize();

  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  $values = [];

  require_once 'CRM/Contribute/BAO/Contribution.php';
  $error = _civicrm_contribute_format_params($params, $values);
  if (civicrm_error($error)) {
    return $error;
  }

  $required = [
    'amount',
  ];
  foreach ($required as $key) {
    if (!isset($params[$key])) {
      return civicrm_create_error("Missing parameter $key: civicrm_contribute_transact() requires a parameter '$key'.");
    }
  }

  // allow people to omit some values for convenience
  $defaults = [
    // 'payment_processor_id' => NULL /* we could retrieve the default processor here, but only if it's missing to avoid an extra lookup */
    'payment_processor_mode' => 'live',
  ];
  $params = array_merge($defaults, $params);

  // clean up / adjust some values which
  if (!isset($params['total_amount'])) {
    $params['total_amount'] = $params['amount'];
  }
  if (!isset($params['net_amount'])) {
    $params['net_amount'] = $params['amount'];
  }
  if (!isset($params['receive_date'])) {
    $params['receive_date'] = date('Y-m-d');
  }
  if (!isset($params['invoiceID']) && isset($params['invoice_id'])) {
    $params['invoiceID'] = $params['invoice_id'];
  }

  require_once 'CRM/Financial/BAO/PaymentProcessor.php';
  $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($params['payment_processor_id'],
    $params['payment_processor_mode']
  );
  if (civicrm_error($paymentProcessor)) {
    return $paymentProcessor;
  }

  require_once 'CRM/Core/Payment.php';
  $payment = CRM_Core_Payment::singleton($params['payment_processor_mode'], $paymentProcessor);
  if (civicrm_error($payment)) {
    return $payment;
  }

  $transaction = $payment->doDirectPayment($params);
  if (civicrm_error($transaction)) {
    return $transaction;
  }

  // but actually, $payment->doDirectPayment() doesn't return a
  // CRM_Core_Error by itself
  if (get_class($transaction) == 'CRM_Core_Error') {
    $errs = $transaction->getErrors();
    if (!empty($errs)) {
      $last_error = array_shift($errs);
      return CRM_Core_Error::createApiError($last_error['message']);
    }
  }

  $contribution = civicrm_contribution_add($params);
  return $contribution;
}

