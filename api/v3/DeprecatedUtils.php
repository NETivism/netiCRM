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



/*
 * These functions have been deprecated out of API v3 Utils folder as they are not part of the
 * API. Calling API functions directly is not supported & these functions are not called by any
 * part of the API so are not really part of the api
 *
 */



require_once 'api/v3/utils.php';

// @codeCoverageIgnoreStart 
// this doesn't belong to the API

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Event_BAO_Participant:create()
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_api3_deprecated_participant_formatted_param($params, &$values, $create = FALSE) {
  $fields = CRM_Event_DAO_Participant::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      $values[$key] = $value;
      $type = $customFields[$customFieldID]['html_type'];
      if ($type == 'CheckBox' || $type == 'Multi-Select') {
        $mulValues    = explode(',', $value);
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        $values[$key] = array();
        foreach ($mulValues as $v1) {
          foreach ($customOption as $customValueID => $customLabel) {
            $customValue = $customLabel['value'];
            if ((strtolower(trim($customLabel['label'])) == strtolower(trim($v1))) ||
              (strtolower(trim($customValue)) == strtolower(trim($v1)))
            ) {
              if ($type == 'CheckBox') {
                $values[$key][$customValue] = 1;
              }
              else {
                $values[$key][] = $customValue;
              }
            }
          }
        }
      }
      elseif ($type == 'Select' || $type == 'Radio') {
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        foreach ($customOption as $customFldID => $customValue) {
          $val   = CRM_Utils_Array::value('value', $customValue);
          $label = CRM_Utils_Array::value('label', $customValue);
          $label = strtolower($label);
          $value = strtolower(trim($value));
          if (($value == $label) || ($value == strtolower($val))) {
            $values[$key] = $val;
          }
        }
      }
    }

    switch ($key) {
      case 'participant_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        $values['contact_id'] = $values['participant_contact_id'];
        unset($values['participant_contact_id']);
        break;

      case 'participant_register_date':
        if (!CRM_Utils_Rule::dateTime($value)) {
          return civicrm_api3_create_error("$key not a valid date: $value");
        }
        break;

      case 'event_title':
        $id = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $value, 'id', 'title');
        $values['event_id'] = $id;
        break;

      case 'event_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("Event ID is not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_event WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_api3_create_error("Invalid Event ID: There is no event record with event_id = $value.");
        }
        break;

      case 'participant_status_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("Event Status ID is not valid: $value");
        }
        break;

      case 'participant_status':
        $status = CRM_Event_PseudoConstant::participantStatus();
        $values['participant_status_id'] = CRM_Utils_Array::key($value, $status);;
        break;

      case 'participant_role_id':
      case 'participant_role':
        $role = CRM_Event_PseudoConstant::participantRole();
        $participantRoles = explode(",", $value);
        foreach ($participantRoles as $k => $v) {
          $v = trim($v);
          if ($key == 'participant_role') {
            $participantRoles[$k] = CRM_Utils_Array::key($v, $role);
          }
          else {
            $participantRoles[$k] = $v;
          }
        }
        require_once 'CRM/Core/DAO.php';
        $values['role_id'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $participantRoles);
        unset($values[$key]);
        break;

      default:
        break;
    }
  }

  if (CRM_Utils_Array::arrayKeyExists('participant_note', $params)) {
    $values['participant_note'] = $params['participant_note'];
  }

  if ($create) {
    // CRM_Event_BAO_Participant::create() handles register_date,
    // status_id and source. So, if $values contains
    // participant_register_date, participant_status_id or participant_source,
    // convert it to register_date, status_id or source
    $changes = array(
      'participant_register_date' => 'register_date',
      'participant_source' => 'source',
      'participant_status_id' => 'status_id',
      'participant_role_id' => 'role_id',
      'participant_fee_level' => 'fee_level',
      'participant_fee_amount' => 'fee_amount',
      'participant_id' => 'id',
    );

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
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
function _civicrm_api3_deprecated_formatted_param($params, &$values, $create = FALSE) {
  // copy all the contribution fields as is

  $fields = CRM_Contribute_DAO_Contribution::fields();

  _civicrm_api3_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      $values[$key] = $value;
      $type = $customFields[$customFieldID]['html_type'];
      if ($type == 'CheckBox' || $type == 'Multi-Select') {
        $mulValues    = explode(',', $value);
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        $values[$key] = array();
        foreach ($mulValues as $v1) {
          foreach ($customOption as $customValueID => $customLabel) {
            $customValue = $customLabel['value'];
            if ((strtolower($customLabel['label']) == strtolower(trim($v1))) ||
              (strtolower($customValue) == strtolower(trim($v1)))
            ) {
              if ($type == 'CheckBox') {
                $values[$key][$customValue] = 1;
              }
              else {
                $values[$key][] = $customValue;
              }
            }
          }
        }
      }
      elseif ($type == 'Select' || $type == 'Radio' ||
        ($type == 'Autocomplete-Select' &&
          $customFields[$customFieldID]['data_type'] == 'String'
        )
      ) {
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        foreach ($customOption as $customFldID => $customValue) {
          $val   = CRM_Utils_Array::value('value', $customValue);
          $label = CRM_Utils_Array::value('label', $customValue);
          $label = strtolower($label);
          $value = strtolower(trim($value));
          if (($value == $label) || ($value == strtolower($val))) {
            $values[$key] = $val;
          }
        }
      }
    }

    switch ($key) {
      case 'contribution_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }

        $values['contact_id'] = $values['contribution_contact_id'];
        unset($values['contribution_contact_id']);
        break;

      case 'contact_type':
        //import contribution record according to select contact type
        require_once 'CRM/Contact/DAO/Contact.php';
        $contactType = new CRM_Contact_DAO_Contact();
        //when insert mode check contact id or external identifire
        if ($params['contribution_contact_id'] || $params['external_identifier']) {
          if ($params['contribution_contact_id']) {
            $contactType->id = $params['contribution_contact_id'];
          }
          elseif ($params['external_identifier']) {
            $contactType->external_identifier = $params['external_identifier'];
          }
          if ($contactType->find(TRUE)) {
            if ($params['contact_type'] != $contactType->contact_type) {
              return civicrm_api3_create_error("Contact Type is wrong: $contactType->contact_type");
            }
          }
        }
        elseif ($params['contribution_id'] || $params['trxn_id'] || $params['invoice_id']) {
          //when update mode check contribution id or trxn id or
          //invoice id
          $contactId = new CRM_Contribute_DAO_Contribution();
          if ($params['contribution_id']) {
            $contactId->id = $params['contribution_id'];
          }
          elseif ($params['trxn_id']) {
            $contactId->trxn_id = $params['trxn_id'];
          }
          elseif ($params['invoice_id']) {
            $contactId->invoice_id = $params['invoice_id'];
          }
          if ($contactId->find(TRUE)) {
            $contactType->id = $contactId->contact_id;
            if ($contactType->find(TRUE)) {
              if ($params['contact_type'] != $contactType->contact_type) {
                return civicrm_api3_create_error("Contact Type is wrong: $contactType->contact_type");
              }
            }
          }
        }
        break;

      case 'receive_date':
      case 'cancel_date':
      case 'receipt_date':
      case 'thankyou_date':
        if (!CRM_Utils_Rule::dateTime($value)) {
          return civicrm_api3_create_error("$key not a valid date: $value");
        }
        break;

      case 'non_deductible_amount':
      case 'total_amount':
      case 'fee_amount':
      case 'net_amount':
        if (!CRM_Utils_Rule::money($value)) {
          return civicrm_api3_create_error("$key not a valid amount: $value");
        }
        break;

      case 'currency':
        if (!CRM_Utils_Rule::currencyCode($value)) {
          return civicrm_api3_create_error("currency not a valid code: $value");
        }
        break;

      case 'contribution_type':
        require_once 'CRM/Contribute/PseudoConstant.php';
        $contriTypes = CRM_Contribute_PseudoConstant::contributionType();
        foreach ($contriTypes as $val => $type) {
          if (strtolower($value) == strtolower($type)) {
            $values['contribution_type_id'] = $val;
            break;
          }
        }
        if (!CRM_Utils_Array::value('contribution_type_id', $values)) {
          return civicrm_api3_create_error("Contribution Type is not valid: $value");
        }
        break;

      case 'payment_instrument':
        require_once 'CRM/Core/OptionGroup.php';
        $values['payment_instrument_id'] = CRM_Core_OptionGroup::getValue('payment_instrument', $value);
        if (!CRM_Utils_Array::value('payment_instrument_id', $values)) {
          return civicrm_api3_create_error("Payment Instrument is not valid: $value");
        }
        break;

      case 'contribution_status_id':
        require_once 'CRM/Core/OptionGroup.php';
        if (!$values['contribution_status_id'] = CRM_Core_OptionGroup::getValue('contribution_status', $value)) {
          return civicrm_api3_create_error("Contribution Status is not valid: $value");
        }
        break;

      case 'honor_type_id':
        require_once 'CRM/Core/OptionGroup.php';
        $values['honor_type_id'] = CRM_Core_OptionGroup::getValue('honor_type', $value);
        if (!CRM_Utils_Array::value('honor_type_id', $values)) {
          return civicrm_api3_create_error("Honor Type is not valid: $value");
        }
        break;

      case 'soft_credit':
        //import contribution record according to select contact type

        // validate contact id and external identifier.
        $contactId = CRM_Utils_Array::value('contact_id', $params['soft_credit']);
        $externalId = CRM_Utils_Array::value('external_identifier', $params['soft_credit']);
        if ($contactId || $externalId) {
          require_once 'CRM/Contact/DAO/Contact.php';
          $contact = new CRM_Contact_DAO_Contact();
          $contact->id = $contactId;
          $contact->external_identifier = $externalId;

          $errorMsg = NULL;
          if (!$contact->find(TRUE)) {
            $errorMsg = ts("No match found for specified Soft Credit contact data. Row was skipped.");
          }

          if ($errorMsg) {
            return civicrm_api3_create_error($errorMsg, 'soft_credit');
          }

          // finally get soft credit contact id.
          $values['soft_credit_to'] = $contact->id;
        }
        else {
          // get the contact id from dupicate contact rule, if more than one contact is returned
          // we should return error, since current interface allows only one-one mapping

          $softParams = $params['soft_credit'];
          $softParams['contact_type'] = $params['contact_type'];

          $error = _civicrm_api3_deprecated_duplicate_formatted_contact($softParams);

          if (isset($error['error_message']['params'][0])) {
            $matchedIDs = explode(',', $error['error_message']['params'][0]);

            // check if only one contact is found
            if (count($matchedIDs) > 1) {
              return civicrm_api3_create_error($error['error_message']['message'], 'soft_credit');
            }
            else {
              $values['soft_credit_to'] = $matchedIDs[0];
            }
          }
          else {
            return civicrm_api3_create_error('No match found for specified Soft Credit contact data. Row was skipped.', 'soft_credit');
          }
        }
        break;

      case 'pledge_payment':
      case 'pledge_id':

        //giving respect to pledge_payment flag.
        if (!CRM_Utils_Array::value('pledge_payment', $params)) {
          continue;
        }

        //get total amount of from import fields
        $totalAmount = CRM_Utils_Array::value('total_amount', $params);

        $onDuplicate = CRM_Utils_Array::value('onDuplicate', $params);

        //we need to get contact id $contributionContactID to
        //retrieve pledge details as well as to validate pledge ID

        //first need to check for update mode
        if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE &&
          ($params['contribution_id'] || $params['trxn_id'] || $params['invoice_id'])
        ) {
          $contribution = new CRM_Contribute_DAO_Contribution();
          if ($params['contribution_id']) {
            $contribution->id = $params['contribution_id'];
          }
          elseif ($params['trxn_id']) {
            $contribution->trxn_id = $params['trxn_id'];
          }
          elseif ($params['invoice_id']) {
            $contribution->invoice_id = $params['invoice_id'];
          }

          if ($contribution->find(TRUE)) {
            $contributionContactID = $contribution->contact_id;
            if (!$totalAmount) {
              $totalAmount = $contribution->total_amount;
            }
          }
          else {
            return civicrm_api3_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
          }
        }
        else {
          // first get the contact id for given contribution record.
          if (CRM_Utils_Array::value('contribution_contact_id', $params)) {
            $contributionContactID = $params['contribution_contact_id'];
          }
          elseif (CRM_Utils_Array::value('external_identifier', $params)) {
            require_once 'CRM/Contact/DAO/Contact.php';
            $contact = new CRM_Contact_DAO_Contact();
            $contact->external_identifier = $params['external_identifier'];
            if ($contact->find(TRUE)) {
              $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $contact->id;
            }
            else {
              return civicrm_api3_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
            }
          }
          else {
            // we  need to get contribution contact using de dupe
            $error = _civicrm_api3_deprecated_check_contact_dedupe($params);

            if (isset($error['error_message']['params'][0])) {
              $matchedIDs = explode(',', $error['error_message']['params'][0]);

              // check if only one contact is found
              if (count($matchedIDs) > 1) {
                return civicrm_api3_create_error($error['error_message']['message'], 'pledge_payment');
              }
              else {
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $matchedIDs[0];
              }
            }
            else {
              return civicrm_api3_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
            }
          }
        }

        if (CRM_Utils_Array::value('pledge_id', $params)) {
          if (CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $contributionContactID) {
            return civicrm_api3_create_error('Invalid Pledge ID provided. Contribution row was skipped.', 'pledge_payment');
          }
          $values['pledge_id'] = $params['pledge_id'];
        }
        else {
          //check if there are any pledge related to this contact, with payments pending or in progress
          require_once 'CRM/Pledge/BAO/Pledge.php';
          $pledgeDetails = CRM_Pledge_BAO_Pledge::getContactPledges($contributionContactID);

          if (empty($pledgeDetails)) {
            return civicrm_api3_create_error('No open pledges found for this contact. Contribution row was skipped.', 'pledge_payment');
          }
          elseif (count($pledgeDetails) > 1) {
            return civicrm_api3_create_error('This contact has more than one open pledge. Unable to determine which pledge to apply the contribution to. Contribution row was skipped.', 'pledge_payment');
          }

          // this mean we have only one pending / in progress pledge
          $values['pledge_id'] = $pledgeDetails[0];
        }

        //we need to check if oldest payment amount equal to contribution amount
        require_once 'CRM/Pledge/BAO/PledgePayment.php';
        $pledgePaymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($values['pledge_id']);

        if ($pledgePaymentDetails['amount'] == $totalAmount) {
          $values['pledge_payment_id'] = $pledgePaymentDetails['id'];
        }
        else {
          return civicrm_api3_create_error('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.', 'pledge_payment');
        }
        break;

      default:
        break;
    }
  }

  if (CRM_Utils_Array::arrayKeyExists('note', $params)) {
    $values['note'] = $params['note'];
  }

  if ($create) {
    // CRM_Contribute_BAO_Contribution::add() handles contribution_source
    // So, if $values contains contribution_source, convert it to source
    $changes = array('contribution_source' => 'source');

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 *  Function to check duplicate contacts based on de-deupe parameters
 */
function _civicrm_api3_deprecated_check_contact_dedupe($params) {
  static $cIndieFields = NULL;
  static $defaultLocationId = NULL;

  $contactType = $params['contact_type'];
  if ($cIndieFields == NULL) {
    require_once 'CRM/Contact/BAO/Contact.php';
    $cTempIndieFields = CRM_Contact_BAO_Contact::importableFields($contactType);
    $cIndieFields = $cTempIndieFields;

    require_once "CRM/Core/BAO/LocationType.php";
    $defaultLocation = CRM_Core_BAO_LocationType::getDefault();

    //set the value to default location id else set to 1
    if (!$defaultLocationId = (int)$defaultLocation->id) {
      $defaultLocationId = 1;
    }
  }

  require_once 'CRM/Contact/BAO/Query.php';
  $locationFields = CRM_Contact_BAO_Query::$_locationSpecificFields;

  $contactFormatted = array();
  foreach ($params as $key => $field) {
    if ($field == NULL || $field === '') {
      continue;
    }
    if (is_array($field)) {
      foreach ($field as $value) {
        $break = FALSE;
        if (is_array($value)) {
          foreach ($value as $name => $testForEmpty) {
            if ($name !== 'phone_type' &&
              ($testForEmpty === '' || $testForEmpty == NULL)
            ) {
              $break = TRUE;
              break;
            }
          }
        }
        else {
          $break = TRUE;
        }
        if (!$break) {
          _civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
        }
      }
      continue;
    }

    $value = array($key => $field);

    // check if location related field, then we need to add primary location type
    if (in_array($key, $locationFields)) {
      $value['location_type_id'] = $defaultLocationId;
    }
    elseif (CRM_Utils_Array::arrayKeyExists($key, $cIndieFields)) {
      $value['contact_type'] = $contactType;
    }

    _civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
  }

  $contactFormatted['contact_type'] = $contactType;

  return _civicrm_api3_deprecated_duplicate_formatted_contact($contactFormatted);
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Activity_BAO_Activity::create()
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_api3_deprecated_activity_formatted_param(&$params, &$values, $create = FALSE) {
  // copy all the activity fields as is
  $fields = CRM_Activity_DAO_Activity::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      $values[$key] = $value;
      $type = $customFields[$customFieldID]['html_type'];
      if ($type == 'CheckBox' || $type == 'Multi-Select') {
        $mulValues    = explode(',', $value);
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        $values[$key] = array();
        foreach ($mulValues as $v1) {
          foreach ($customOption as $customValueID => $customLabel) {
            $customValue = $customLabel['value'];
            if ((strtolower(trim($customLabel['label'])) == strtolower(trim($v1))) ||
              (strtolower(trim($customValue)) == strtolower(trim($v1)))
            ) {
              if ($type == 'CheckBox') {
                $values[$key][$customValue] = 1;
              }
              else {
                $values[$key][] = $customValue;
              }
            }
          }
        }
      }
      elseif ($type == 'Select' || $type == 'Radio') {
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        foreach ($customOption as $customFldID => $customValue) {
          $val   = CRM_Utils_Array::value('value', $customValue);
          $label = CRM_Utils_Array::value('label', $customValue);
          $label = strtolower($label);
          $value = strtolower(trim($value));
          if (($value == $label) || ($value == strtolower($val))) {
            $values[$key] = $val;
          }
        }
      }
    }

    if ($key == 'target_contact_id') {
      if (!CRM_Utils_Rule::integer($value)) {
        return civicrm_api3_create_error("contact_id not valid: $value");
      }
      $contactID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value");
      if (!$contactID) {
        return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
      }
    }
  }
  return NULL;
}

/**
 * This function adds the contact variable in $values to the
 * parameter list $params.  For most cases, $values should have length 1.  If
 * the variable being added is a child of Location, a location_type_id must
 * also be included.  If it is a child of phone, a phone_type must be included.
 *
 * @param array  $values    The variable(s) to be added
 * @param array  $params    The structured parameter list
 *
 * @return bool|CRM_Utils_Error
 * @access public
 */
function _civicrm_api3_deprecated_add_formatted_param(&$values, &$params) {
  /* Crawl through the possible classes:
     * Contact
     *      Individual
     *      Household
     *      Organization
     *          Location
     *              Address
     *              Email
     *              Phone
     *              IM
     *      Note
     *      Custom
     */



  /* Cache the various object fields */


  static $fields = NULL;

  if ($fields == NULL) {
    $fields = array();
  }

  //first add core contact values since for other Civi modules they are not added
  require_once 'CRM/Contact/BAO/Contact.php';
  $contactFields = CRM_Contact_DAO_Contact::fields();
  _civicrm_api3_store_values($contactFields, $values, $params);

  if (isset($values['contact_type'])) {
    /* we're an individual/household/org property */



    $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

    _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
    return TRUE;
  }

  if (isset($values['individual_prefix'])) {
    if (CRM_Utils_Array::value('prefix_id', $params)) {
      $prefixes         = array();
      $prefixes         = CRM_Core_PseudoConstant::individualPrefix();
      $params['prefix'] = $prefixes[$params['prefix_id']];
    }
    else {
      $params['prefix'] = $values['individual_prefix'];
    }
    return TRUE;
  }

  if (isset($values['individual_suffix'])) {
    if (CRM_Utils_Array::value('suffix_id', $params)) {
      $suffixes         = array();
      $suffixes         = CRM_Core_PseudoConstant::individualSuffix();
      $params['suffix'] = $suffixes[$params['suffix_id']];
    }
    else {
      $params['suffix'] = $values['individual_suffix'];
    }
    return TRUE;
  }

  //CRM-4575
  if (isset($values['email_greeting'])) {
    if (CRM_Utils_Array::value('email_greeting_id', $params)) {
      $emailGreetings = array();
      $emailGreetingFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'email_greeting',
      );
      $emailGreetings = CRM_Core_PseudoConstant::greeting($emailGreetingFilter);
      $params['email_greeting'] = $emailGreetings[$params['email_greeting_id']];
    }
    else {
      $params['email_greeting'] = $values['email_greeting'];
    }

    return TRUE;
  }

  if (isset($values['postal_greeting'])) {
    if (CRM_Utils_Array::value('postal_greeting_id', $params)) {
      $postalGreetings = array();
      $postalGreetingFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'postal_greeting',
      );
      $postalGreetings = CRM_Core_PseudoConstant::greeting($postalGreetingFilter);
      $params['postal_greeting'] = $postalGreetings[$params['postal_greeting_id']];
    }
    else {
      $params['postal_greeting'] = $values['postal_greeting'];
    }
    return TRUE;
  }

  if (isset($values['addressee'])) {
    if (CRM_Utils_Array::value('addressee_id', $params)) {
      $addressee = array();
      $addresseeFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'addressee',
      );
      $addressee = CRM_Core_PseudoConstant::addressee($addresseeFilter);
      $params['addressee'] = $addressee[$params['addressee_id']];
    }
    else {
      $params['addressee'] = $values['addressee'];
    }
    return TRUE;
  }

  if (isset($values['gender'])) {
    if (CRM_Utils_Array::value('gender_id', $params)) {
      $genders          = array();
      $genders          = CRM_Core_PseudoConstant::gender();
      $params['gender'] = $genders[$params['gender_id']];
    }
    else {
      $params['gender'] = $values['gender'];
    }
    return TRUE;
  }

  if (isset($values['preferred_communication_method'])) {
    $comm      = array();
    $preffComm = array();
    $pcm       = array();
    $pcm       = array_change_key_case(array_flip(CRM_Core_PseudoConstant::pcm()), CASE_LOWER);

    $preffComm = explode(',', $values['preferred_communication_method']);
    foreach ($preffComm as $v) {
      $v = strtolower(trim($v));
      if (CRM_Utils_Array::arrayKeyExists($v, $pcm)) {
        $comm[$pcm[$v]] = 1;
      }
    }

    $params['preferred_communication_method'] = $comm;
    return TRUE;
  }

  //format the website params.
  if (CRM_Utils_Array::value('url', $values)) {
    static $websiteFields;
    if (!is_array($websiteFields)) {
      require_once 'CRM/Core/DAO/Website.php';
      $websiteFields = CRM_Core_DAO_Website::fields();
    }
    if (!CRM_Utils_Array::arrayKeyExists('website', $params) ||
      !is_array($params['website'])
    ) {
      $params['website'] = array();
    }

    $websiteCount = count($params['website']);
    _civicrm_api3_store_values($websiteFields, $values,
      $params['website'][++$websiteCount]
    );

    return TRUE;
  }

  // get the formatted location blocks into params - w/ 3.0 format, CRM-4605
  if (CRM_Utils_Array::value('location_type_id', $values)) {
    _civicrm_api3_deprecated_add_formatted_location_blocks($values, $params);
    return TRUE;
  }

  if (isset($values['note'])) {
    /* add a note field */


    if (!isset($params['note'])) {
      $params['note'] = array();
    }
    $noteBlock = count($params['note']) + 1;

    $params['note'][$noteBlock] = array();
    if (!isset($fields['Note'])) {
      $fields['Note'] = CRM_Core_DAO_Note::fields();
    }

    // get the current logged in civicrm user
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID) {
      $values['contact_id'] = $userID;
    }

    _civicrm_api3_store_values($fields['Note'], $values, $params['note'][$noteBlock]);

    return TRUE;
  }

  /* Check for custom field values */


  if (!CRM_Utils_Array::value('custom', $fields)) {
    $fields['custom'] = &CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values),
      FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE
    );
  }

  foreach ($values as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      /* check if it's a valid custom field id */


      if (!CRM_Utils_Array::arrayKeyExists($customFieldID, $fields['custom'])) {
        return civicrm_api3_create_error('Invalid custom field ID');
      }
      else {
        $params[$key] = $value;
      }
    }
  }
}

/**
 * This function format location blocks w/ v3.0 format.
 *
 * @param array  $values    The variable(s) to be added
 * @param array  $params    The structured parameter list
 *
 * @return bool
 * @access public
 */
function _civicrm_api3_deprecated_add_formatted_location_blocks(&$values, &$params) {
  static $fields = NULL;
  if ($fields == NULL) {
    $fields = array();
  }

  foreach (array(
    'Phone', 'Email', 'IM', 'OpenID') as $block) {
    $name = strtolower($block);
    if (!CRM_Utils_Array::arrayKeyExists($name, $values)) {
      continue;
    }

    // block present in value array.
    if (!CRM_Utils_Array::arrayKeyExists($name, $params) || !is_array($params[$name])) {
      $params[$name] = array();
    }

    if (!CRM_Utils_Array::arrayKeyExists($block, $fields)) {
      $daoName = 'CRM_Core_DAO_' . $block;
      $fields[$block] =& $daoName::fields( );
    }

    $blockCnt = count($params[$name]);

    // copy value to dao field name.
    if ($name == 'im') {
      $values['name'] = $values[$name];
    }

    _civicrm_api3_store_values($fields[$block], $values,
      $params[$name][++$blockCnt]
    );

    if (!CRM_Utils_Array::value('id', $params) && ($blockCnt == 1)) {
      $params[$name][$blockCnt]['is_primary'] = TRUE;
    }

    // we only process single block at a time.
    return TRUE;
  }

  // handle address fields.
  if (!CRM_Utils_Array::arrayKeyExists('address', $params) || !is_array($params['address'])) {
    $params['address'] = array();
  }

  $addressCnt = 1;
  foreach ($params['address'] as $cnt => $addressBlock) {
    if (CRM_Utils_Array::value('location_type_id', $values) ==
      CRM_Utils_Array::value('location_type_id', $addressBlock)
    ) {
      $addressCnt = $cnt;
      break;
    }
    $addressCnt++;
  }

  if (!CRM_Utils_Array::arrayKeyExists('Address', $fields)) {
    require_once 'CRM/Core/DAO/Address.php';
    $fields['Address'] = CRM_Core_DAO_Address::fields();
  }
  _civicrm_api3_store_values($fields['Address'], $values, $params['address'][$addressCnt]);

  $addressFields = array(
    'county', 'country', 'state_province',
    'supplemental_address_1', 'supplemental_address_2',
    'StateProvince.name',
  );

  foreach ($addressFields as $field) {
    if (CRM_Utils_Array::arrayKeyExists($field, $values)) {
      if (!CRM_Utils_Array::arrayKeyExists('address', $params)) {
        $params['address'] = array();
      }
      $params['address'][$addressCnt][$field] = $values[$field];
    }
  }

  if ($addressCnt == 1) {

    $params['address'][$addressCnt]['is_primary'] = TRUE;
  }

  return TRUE;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function _civicrm_api3_deprecated_duplicate_formatted_contact($params) {
  $id = CRM_Utils_Array::value('id', $params);
  $externalId = CRM_Utils_Array::value('external_identifier', $params);
  if ($id || $externalId) {
    $contact = new CRM_Contact_DAO_Contact();

    $contact->id = $id;
    $contact->external_identifier = $externalId;

    if ($contact->find(TRUE)) {
      if ($params['contact_type'] != $contact->contact_type) {
        return civicrm_api3_create_error("Mismatched contact IDs OR Mismatched contact Types");
      }

      $error = CRM_Core_Error::createError("Found matching contacts: $contact->id",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $contact->id
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  else {
    require_once 'CRM/Dedupe/Finder.php';
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);
    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $params['contact_type'], 'Strict');

    if (!empty($ids)) {
      $ids = CRM_Utils_Array::implode(',', $ids);
      $error = CRM_Core_Error::createError("Found matching contacts: $ids",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $ids
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  return civicrm_api3_create_success(TRUE);
}

/**
 * Validate a formatted contact parameter list.
 *
 * @param array $params  Structured parameter list (as in crm_format_params)
 *
 * @return bool|CRM_Core_Error
 * @access public
 */
function _civicrm_api3_deprecated_validate_formatted_contact(&$params) {
  /* Look for offending email addresses */


  if (CRM_Utils_Array::arrayKeyExists('email', $params)) {
    foreach ($params['email'] as $count => $values) {
      if (!is_array($values)) {
        continue;
      }
      if ($email = CRM_Utils_Array::value('email', $values)) {
        //validate each email
        if (!CRM_Utils_Rule::email($email)) {
          return civicrm_api3_create_error('No valid email address');
        }

        //check for loc type id.
        if (!CRM_Utils_Array::value('location_type_id', $values)) {
          return civicrm_api3_create_error('Location Type Id missing.');
        }
      }
    }
  }

  /* Validate custom data fields */


  if (CRM_Utils_Array::arrayKeyExists('custom', $params) && is_array($params['custom'])) {
    foreach ($params['custom'] as $key => $custom) {
      if (is_array($custom)) {
        foreach ($custom as $fieldId => $value) {
          $valid = CRM_Core_BAO_CustomValue::typecheck(CRM_Utils_Array::value('type', $value),
            CRM_Utils_Array::value('value', $value)
          );
          if (!$valid) {
            return civicrm_api3_create_error('Invalid value for custom field \'' .
              CRM_Utils_Array::value('name', $custom) . '\''
            );
          }
          if (CRM_Utils_Array::value('type', $custom) == 'Date') {
            $params['custom'][$key][$fieldId]['value'] = str_replace('-', '', $params['custom'][$key][$fieldId]['value']);
          }
        }
      }
    }
  }

  return civicrm_api3_create_success(TRUE);
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Member_BAO_Membership:create()
 *
 * @return array|error
 * @access public
 */
function _civicrm_api3_deprecated_membership_format_params($params, &$values, $create = FALSE) {

  $fields = CRM_Member_DAO_Membership::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields( 'Membership');
  
  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

       //Handling Custom Data
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
          $values[$key] = $value;
          $type = $customFields[$customFieldID]['html_type'];
          if( $type == 'CheckBox' || $type == 'Multi-Select' || $type == 'AdvMulti-Select') {
                $mulValues = explode( ',' , $value );
                $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, true);
                $values[$key] = array();
                foreach( $mulValues as $v1 ) {
                    foreach($customOption as $customValueID => $customLabel) {
                        $customValue = $customLabel['value'];
                        if (( strtolower($customLabel['label']) == strtolower(trim($v1)) ) ||
                            ( strtolower($customValue) == strtolower(trim($v1)) )) {
                            if ( $type == 'CheckBox' ) {
                                $values[$key][$customValue] = 1;
                            } else {
                                $values[$key][] = $customValue;
                            }
                        }
                    }
                }
          }
      }
     
    switch ($key) {
      case 'membership_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_api3_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        $values['contact_id'] = $values['membership_contact_id'];
        unset($values['membership_contact_id']);
        break;

      case 'membership_type_id':
        if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipType())) {
          return civicrm_api3_create_error('Invalid Membership Type Id');
        }
        $values[$key] = $value;
        break;

      case 'membership_type':
        $membershipTypeId = CRM_Utils_Array::key(ucfirst($value),
          CRM_Member_PseudoConstant::membershipType()
        );
        if ($membershipTypeId) {
          if (CRM_Utils_Array::value('membership_type_id', $values) &&
            $membershipTypeId != $values['membership_type_id']
          ) {
            return civicrm_api3_create_error('Mismatched membership Type and Membership Type Id');
          }
        }
        else {
          return civicrm_api3_create_error('Invalid Membership Type');
        }
        $values['membership_type_id'] = $membershipTypeId;
        break;

      case 'status_id':
        if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipStatus())) {
          return civicrm_api3_create_error('Invalid Membership Status Id');
        }
        $values[$key] = $value;
        break;

      case 'membership_status':
        $membershipStatusId = CRM_Utils_Array::key(ucfirst($value),
          CRM_Member_PseudoConstant::membershipStatus()
        );
        if ($membershipStatusId) {
          if (CRM_Utils_Array::value('status_id', $values) &&
            $membershipStatusId != $values['status_id']
          ) {
            return civicrm_api3_create_error('Mismatched membership Status and Membership Status Id');
          }
        }
        else {
          return civicrm_api3_create_error('Invalid Membership Status');
        }
        $values['status_id'] = $membershipStatusId;
        break;

      default:
        break;
    }
  }

  _civicrm_api3_custom_format_params($params, $values, 'Membership');


  if ($create) {
    // CRM_Member_BAO_Membership::create() handles membership_start_date,
    // membership_end_date and membership_source. So, if $values contains
    // membership_start_date, membership_end_date  or membership_source,
    // convert it to start_date, end_date or source
    $changes = array(
      'membership_start_date' => 'start_date',
      'membership_end_date' => 'end_date',
      'membership_source' => 'source',
    );

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 * @deprecated - this is part of the import parser not the API & needs to be moved on out
 *
 * @param <type> $params
 * @param <type> $onDuplicate
 *
 * @return <type>
 */
function _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate) {
  require_once 'CRM/Event/Import/Parser.php';
  if ($onDuplicate != CRM_Event_Import_Parser::DUPLICATE_NOCHECK) {
    CRM_Core_Error::reset();
    $error = _civicrm_api3_deprecated_participant_check_params($params, TRUE);
    if (civicrm_error($error)) {
      return $error;
    }
  }
  require_once "api/v3/Participant.php";
  return civicrm_api3_participant_create($params);
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function _civicrm_api3_deprecated_participant_check_params($params, $checkDuplicate = FALSE) {

  //check if participant id is valid or not
  if (CRM_Utils_Array::value('id', $params)) {
    $participant = new CRM_Event_BAO_Participant();
    $participant->id = $params['id'];
    if (!$participant->find(TRUE)) {
      return civicrm_api3_create_error(ts('Participant  id is not valid'));
    }
  }
  require_once 'CRM/Contact/BAO/Contact.php';
  //check if contact id is valid or not
  if (CRM_Utils_Array::value('contact_id', $params)) {
    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $params['contact_id'];
    if (!$contact->find(TRUE)) {
      return civicrm_api3_create_error(ts('Contact id is not valid'));
    }
  }

  //check that event id is not an template
  if (CRM_Utils_Array::value('event_id', $params)) {
    $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'is_template');
    if (!empty($isTemplate)) {
      return civicrm_api3_create_error(ts('Event templates are not meant to be registered'));
    }
  }

  $result = array();
  if ($checkDuplicate) {
    if (CRM_Event_BAO_Participant::checkDuplicate($params, $result)) {
      $participantID = array_pop($result);

      $error = CRM_Core_Error::createError("Found matching participant record.",
        CRM_Core_Error::DUPLICATE_PARTICIPANT,
        'Fatal', $participantID
      );

      return civicrm_api3_create_error($error->pop(),
        array(
          'contactID' => $params['contact_id'],
          'participantID' => $participantID,
        )
      );
    }
  }
  return TRUE;
}

/**
 * Ensure that we have the right input parameters for custom data
 *
 * @param array   $params          Associative array of property name/value
 *                                 pairs to insert in new contact.
 * @param string  $csType          contact subtype if exists/passed.
 *
 * @return null on success, error message otherwise
 * @access public
 */
function _civicrm_api3_deprecated_contact_check_custom_params($params, $csType = NULL) {
  empty($csType) ? $onlyParent = TRUE : $onlyParent = FALSE;

  require_once 'CRM/Core/BAO/CustomField.php';
  $customFields = CRM_Core_BAO_CustomField::getFields($params['contact_type'],
    FALSE,
    FALSE,
    $csType,
    NULL,
    $onlyParent,
    FALSE,
    FALSE
  );

  foreach ($params as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      /* check if it's a valid custom field id */


      if (!CRM_Utils_Array::arrayKeyExists($customFieldID, $customFields)) {

        $errorMsg = "Invalid Custom Field Contact Type: {$params['contact_type']}";
        if (!empty($csType)) {
          $errorMsg .= " or Mismatched SubType: " . CRM_Utils_Array::implode(', ', (array)$csType);
        }
        return civicrm_api3_create_error($errorMsg);
      }
    }
  }
}

function _civicrm_api3_deprecated_contact_check_params(&$params, $dupeCheck = TRUE, $dupeErrorArray = FALSE, $requiredCheck = TRUE, $dedupeRuleGroupID = NULL) {
  if (isset($params['id']) && is_numeric($params['id'])) {
    $requiredCheck = FALSE;
  }
  if ($requiredCheck) {
    if (isset($params['id'])) {
      $required = array('Individual', 'Household', 'Organization');
    }
    $required = array(
      'Individual' => array(
        array('first_name', 'last_name'),
        'email',
      ),
      'Household' => array(
        'household_name',
      ),
      'Organization' => array(
        'organization_name',
      ),
    );


    // contact_type has a limited number of valid values
    $fields = CRM_Utils_Array::value($params['contact_type'], $required);
    if ($fields == NULL) {
      return civicrm_api3_create_error("Invalid Contact Type: {$params['contact_type']}");
    }

    if ($csType = CRM_Utils_Array::value('contact_sub_type', $params)) {
      if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($csType, $params['contact_type']))) {
        return civicrm_api3_create_error("Invalid or Mismatched Contact SubType: " . CRM_Utils_Array::implode(', ', (array)$csType));
      }
    }

    if (!CRM_Utils_Array::value('contact_id', $params) && CRM_Utils_Array::value('id', $params)) {
      $valid = FALSE;
      $error = '';
      foreach ($fields as $field) {
        if (is_array($field)) {
          $valid = TRUE;
          foreach ($field as $element) {
            if (!CRM_Utils_Array::value($element, $params)) {
              $valid = FALSE;
              $error .= $element;
              break;
            }
          }
        }
        else {
          if (CRM_Utils_Array::value($field, $params)) {
            $valid = TRUE;
          }
        }
        if ($valid) {
          break;
        }
      }

      if (!$valid) {
        return civicrm_api3_create_error("Required fields not found for {$params['contact_type']} : $error");
      }
    }
  }

  if ($dupeCheck) {
    // check for record already existing
    require_once 'CRM/Dedupe/Finder.php';
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);

    // CRM-6431
    // setting 'check_permission' here means that the dedupe checking will be carried out even if the
    // person does not have permission to carry out de-dupes
    // this is similar to the front end form
    if (isset($params['check_permission'])) {
      $dedupeParams['check_permission'] = $params['check_permission'];
    }

    $ids = CRM_Utils_Array::implode(',', CRM_Dedupe_Finder::dupesByParams($dedupeParams, $params['contact_type'], 'Strict', array(), $dedupeRuleGroupID));

    if ($ids != NULL) {
      if ($dupeErrorArray) {
        $error = CRM_Core_Error::createError("Found matching contacts: $ids",
          CRM_Core_Error::DUPLICATE_CONTACT,
          'Fatal', $ids
        );
        return civicrm_api3_create_error($error->pop());
      }

      return civicrm_api3_create_error("Found matching contacts: $ids");
    }
  }

  //check for organisations with same name
  if (CRM_Utils_Array::value('current_employer', $params)) {
    $organizationParams = array();
    $organizationParams['organization_name'] = $params['current_employer'];

    require_once 'CRM/Dedupe/Finder.php';
    $dedupParams = CRM_Dedupe_Finder::formatParams($organizationParams, 'Organization');

    $dedupParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupParams, 'Organization', 'Fuzzy');

    // check for mismatch employer name and id
    if (CRM_Utils_Array::value('employer_id', $params)
      && !in_array($params['employer_id'], $dupeIds)
    ) {
      return civicrm_api3_create_error('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (!CRM_Utils_Array::value('employer_id', $params)
      && (count($dupeIds) > 1)
    ) {
      return civicrm_api3_create_error('Found more than one Organisation with same Name.');
    }
  }

  return NULL;
}

/**
 *
 * @param <type> $result
 * @param <type> $activityTypeID
 *
 * @return <type> $params
 */
function _civicrm_api3_deprecated_activity_buildmailparams($result, $activityTypeID) {
  // get ready for collecting data about activity to be created
  $params = array();

  $params['activity_type_id'] = $activityTypeID;

  $params['status_id'] = 2;
  $params['source_contact_id'] = $params['assignee_contact_id'] = $result['from']['id'];
  $params['target_contact_id'] = array();
  $keys = array('to', 'cc', 'bcc');
  foreach ($keys as $key) {
    if (is_array($result[$key])) {
      foreach ($result[$key] as $key => $keyValue) {
        if (!empty($keyValue['id'])) {
          $params['target_contact_id'][] = $keyValue['id'];
        }
      }
    }
  }
  $params['subject'] = $result['subject'];
  $params['activity_date_time'] = $result['date'];
  $params['details'] = $result['body'];

  for ($i = 1; $i <= 5; $i++) {
    if (isset($result["attachFile_$i"])) {
      $params["attachFile_$i"] = $result["attachFile_$i"];
    }
  }

  return $params;
}

// @codeCoverageIgnoreEnd
