<?php
// $Id$

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
 * File for the CiviCRM APIv3 Contribution functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribute
 *
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Contribution.php 30486 2010-11-02 16:12:09Z shot $
 *
 */

/**
 * Include utility functions
 */
require_once 'CRM/Contribute/BAO/Contribution.php';
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Contribute/PseudoConstant.php';

/**
 * Add or update a contribution
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array  Api result array
 * @static void
 * @access public
 * @example ContributionCreate.php
 * {@getfields Contribution_create}
 */
function civicrm_api3_contribution_create($params) {
  // civicrm_api3_verify_one_mandatory($params, NULL, array('financial_type_id', 'financial_type'));

  $values = [];

  _civicrm_api3_contribute_format_params($params, $values);

  _civicrm_api3_custom_format_params($params, $values, 'Contribution');
  $values["contact_id"] = CRM_Utils_Array::value('contact_id', $params);
  $values["source"] = CRM_Utils_Array::value('source', $params);

  $ids = [];
  if (CRM_Utils_Array::value('id', $params)) {
    $ids['contribution'] = $params['id'];
  }
  $contribution = CRM_Contribute_BAO_Contribution::create($values, $ids);

  if (is_a($contribution, 'CRM_Core_Error')) {
    return civicrm_api3_create_error($contribution->_errors[0]['message']);
  } 
  _civicrm_api3_object_to_array($contribution, $contributeArray[$contribution->id]);

  return civicrm_api3_create_success($contributeArray, $params, 'contribution', 'create', $contribution);
}
/*
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['total_amount']['api.required'] = 1;
  $params['note'] = [
    'name' => 'note',
    'uniqueName' => 'contribution_note',
    'title' => 'note',
    'type' => 2,
    'description' => 'Associated Note in the notes table',
  ];
  $params['soft_credit_to'] = [
    'name' => 'soft_credit_to',
    'title' => 'Soft Credit contact ID',
    'type' => 1,
    'description' => 'ID of Contact to be Soft credited to',
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  // note this is a recommended option but not adding as a default to avoid
  // creating unecessary changes for the dev
    $params['skipRecentView'] = [
    'name' => 'skipRecentView',
    'title' => 'Skip adding to recent view',
    'type' => 1,
    'description' => 'Do not add to recent view (setting this improves performance)',
  ];
}

/**
 * Delete a contribution
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 * {@getfields Contribution_delete}
 * @example ContributionDelete.php
 */
function civicrm_api3_contribution_delete($params) {

  $contributionID = CRM_Utils_Array::value('contribution_id', $params) ? $params['contribution_id'] : $params['id'];
  if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionID)) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error('Could not delete contribution');
  }
}
/*
 * modify metadata. Legacy support for contribution_id
 */
function _civicrm_api3_contribution_delete_spec(&$params) {
  $params['id']['api.aliases'] = ['contribution_id'];
}

/**
 * Retrieve a set of contributions, given a set of input params
 *
 * @param  array $params           input parameters
 *
 * @return array  array of contributions, if error an array with an error id and error message
 * @access public
 * {@getfields Contribution_get}
 * @example ContributionGet.php
 */
function civicrm_api3_contribution_get($params) {
  $mode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
  $additionalOptions = _civicrm_api3_get_options_from_params($params, TRUE);
  $returnProperties = CRM_Contribute_BAO_Query::defaultReturnProperties($mode);

  // filter out deleted contact by default
  if (!isset($params['contact_is_deleted'])) {
    $params['contact_is_deleted'] = 0;
  }
  // Get the contributions based on parameters passed in
  $contributions = _civicrm_api3_get_using_query_object('Contribution', $params, $additionalOptions, NULL, $mode, $returnProperties, TRUE);
  if (!empty($contributions)) {
    foreach ($contributions as $id => $contribution) {
      $soft_params = ['contribution_id' => $id];
      $soft_contribution = CRM_Contribute_BAO_Contribution::getSoftContribution ( $soft_params , true);
      if (!empty($soft_contribution)) {
        $contributions[$id] = array_merge($contributions[$id], $soft_contribution);
      }
    }
  }
  return civicrm_api3_create_success($contributions, $params, 'Contribution', 'get');
}

function civicrm_api3_contribution_getcount($params) {
  $options = [];
  $params['contact_is_deleted'] = 0;
  $count = _civicrm_api3_get_using_query_object('Contribution', $params, $options, 1, CRM_Contact_BAO_Query::MODE_CONTRIBUTE, [], TRUE);
  return (int) $count;
}
/*
 * Adjust Metadata for Get action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_get_spec(&$params) {
  $params['contribution_test']['api.default'] = 0;
  $params['contact_id'] = $params['contribution_contact_id'];
  $params['contact_id']['api.aliases'] = ['contribution_contact_id'];
  unset($params['contribution_contact_id']);
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 * pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 * '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_api3_contribute_format_params($params, &$values, $create = FALSE) {

  _civicrm_api3_filter_fields_for_bao('Contribution', $params, $values);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    switch ($key) {
			case 'financial_type_id' :
				if (! CRM_Utils_Array::value ( $value, CRM_Contribute_PseudoConstant::financialType() )) {
          throw new Exception("Invalid Financial Type Id");
        }
        break;
			case 'financial_type' :
				$contributionTypeId = CRM_Utils_Array::key ( ucfirst ( $value ), CRM_Contribute_PseudoConstant::financialType() );
        if ($contributionTypeId) {
          if (CRM_Utils_Array::value('financial_type_id', $values) && $contributionTypeId != $values['financial_type_id']) {
            throw new Exception("Mismatched Financial Type and Financial Type Id");
          }
					$values ['financial_type_id'] = $contributionTypeId;
        }
        else {
          throw new Exception("Invalid Financial Type");
        }
        break;

      case 'soft_credit_to':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("$key not a valid Id: $value");
        }
        $values['soft_credit_to'] = $value;
        break;

      default:
        break;
    }
  }

  return [];
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
function civicrm_api3_contribution_transact($params) {
  $required = ['amount'];
  foreach ($required as $key) {
    if (!isset($params[$key])) {
      return civicrm_api3_create_error("Missing parameter $key: civicrm_contribute_transact() requires a parameter '$key'.");
    }
  }

  // allow people to omit some values for convenience
  // 'payment_processor_id' => NULL /* we could retrieve the default processor here, but only if it's missing to avoid an extra lookup */
  $defaults = [
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

  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($params['payment_processor_id'], $params['payment_processor_mode']);
  if (civicrm_error($paymentProcessor)) {
    return $paymentProcessor;
  }

  require_once 'CRM/Core/Payment.php';
  $payment = &CRM_Core_Payment::singleton($params['payment_processor_mode'], $paymentProcessor);
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

  $contribution = civicrm_api('contribution', 'create', $params);
  return $contribution['values'];
}
/**
 * Send a contribution confirmation (receipt or invoice)
 * The appropriate online template will be used (the existence of related objects
 * (e.g. memberships ) will affect this selection
 * @param array $params input parameters
 * {@getfields Contribution_sendconfirmation}
 * @return array  Api result array
 * @static void
 * @access public
 *
 */
function civicrm_api3_contribution_sendconfirmation($params) {
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['id'];
  if (! $contribution->find(true)) {
    throw new Exception('Contribution does not exist');
}
  $input = $ids = $cvalues = [];
  $contribution->loadRelatedObjects($input, $ids, FALSE, true);
  $contribution->composeMessageArray($input, $ids, $cvalues, false, false);
}

/*
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_sendconfirmation_spec(&$params) {
  $params['id'] = [
    'api.required' => 1,
    'title' => 'Contribution ID'
  ];

}
