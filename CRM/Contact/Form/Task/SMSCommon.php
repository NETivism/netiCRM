<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.7                                                |
   +--------------------------------------------------------------------+
   | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class provides the common functionality for sending sms to one or a group of contact ids.
 */
class CRM_Contact_Form_Task_SMSCommon {
  const RECIEVED_SMS_ACTIVITY_SUBJECT = "SMS Received";

  public $_contactDetails = array();

  /**
   * Pre process the provider.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcessProvider(&$form) {
    $form->_single = FALSE;
    $className = CRM_Utils_System::getClassName($form);

    if (property_exists($form, '_context') &&
      $form->_context != 'search' &&
      $className == 'CRM_Contact_Form_Task_SMS'
    ) {
      $form->_single = TRUE;
    }

    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();

    if (!$providersCount) {
      return CRM_Core_Error::statusBounce(ts('There are no SMS providers configured, or no SMS providers are set active'));
    }
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->assign('SMSTask', TRUE);
    $toArray = array();
    $suppressedSms = 0;
    if (empty($form->_contactIds)) {
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $form, TRUE);
      $form->_contactIds = array($cid);
    }


    if (!empty($form->_contactIds)) {
      $queryParams = array();
      $returnProperties = array(
        'sort_name' => 1,
        'do_not_sms' => 1,
        'is_deceased' => 1,
        'display_name' => 1,
      );
      $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties);
      foreach ($form->_contactIds as $key => $contactId) {
        $queryParams[] = array(
          CRM_Core_Form::CB_PREFIX.$contactId, '=', 1, 0, 0,
        );
      }
      $numberofContacts = count($form->_contactIds);
      $details = $query->apiQuery($queryParams, $returnProperties, NULL, NULL, 0, $numberofContacts, TRUE, TRUE);
      $form->_contactDetails = &$details[0];

      //to check if the phone type is "Mobile"
      $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
      $mobileTypeId = $phoneTypes['Mobile'];
      $mobilePhoneQuery = "
SELECT civicrm_phone.contact_id, civicrm_phone.phone, civicrm_phone.id as phone_id, civicrm_phone.phone_type_id
FROM civicrm_contact
LEFT JOIN civicrm_phone ON ( civicrm_contact.id = civicrm_phone.contact_id )
WHERE civicrm_contact.id IN (%1) AND civicrm_phone.phone_type_id = %2 GROUP BY civicrm_phone.contact_id
ORDER BY civicrm_phone.is_primary DESC, phone_id ASC";
      $mobilePhoneResult = CRM_Core_DAO::executeQuery($mobilePhoneQuery, array(
        1 => array(CRM_Utils_Array::implode(',', $form->_contactIds), 'CommaSeperatedIntegers'),
        2 => array($mobileTypeId, 'Integer'),
      ));
      while ($mobilePhoneResult->fetch()) {
        if (!empty(trim($mobilePhoneResult->phone))) {
          $contactId = $mobilePhoneResult->contact_id;
          $form->_contactDetails[$contactId]['phone_id'] = $mobilePhoneResult->phone_id;
          $form->_contactDetails[$contactId]['phone'] = trim($mobilePhoneResult->phone);
          $form->_contactDetails[$contactId]['phone_type_id'] = $mobilePhoneResult->phone_type_id;
        }
      }

      foreach ($form->_contactIds as $contactId) {
        if (empty($form->_contactDetails[$contactId]['phone']) || $form->_contactDetails[$contactId]['do_not_sms'] || $form->_contactDetails[$contactId]['is_deceased']) {
          $suppressedSms++;
          unset($form->_contactDetails[$contactId]);
        }
        else {
          $toArray[] = array(
            'text' => '"' . $form->_contactDetails[$contactId]['sort_name'] . '" (' .$form->_contactDetails[$contactId]['phone'] . ')',
            'id' => $contactId.'::'.$form->_contactDetails[$contactId]['phone'],
          );
        }
      }

      $toArrayIdPhone = array();
      if (count($toArray) == 1) {
        $defaults['to'] = $toArray[0]['text'];
      }
      elseif (count($toArray) > 500) {
        $defaults['to'] = ts('We will send messages to %1 contacts.', array(1 => count($form->_contactIds) - $suppressedSms));
      }
      else {
        foreach ($toArray as $key => $value) {
          $toArrayIdPhone[] = $value['id'];
        }
        $toDefault = CRM_Utils_Array::implode(', ', $toArrayIdPhone);
        $defaults['to'] = $toDefault;
      }
      $form->setDefaults($defaults);

      if (empty($toArray)) {
        return CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
      }
    }
    else {
      return CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
    }

    //activity related variables
    if (isset($invalidActivity)) {
      $form->assign('invalidActivity', $invalidActivity);
    }
    if (isset($extendTargetContacts)) {
      $form->assign('extendTargetContacts', $extendTargetContacts);
    }

    $form->assign('toContact', json_encode($toArray));
    $form->assign('suppressedSms', $suppressedSms);
    $form->assign('totalSelectedContacts', count($form->_contactIds));
    $form->assign('estimatedSms', count($form->_contactIds) - $suppressedSms);

    // add form elements
    CRM_Mailing_BAO_Mailing::commonCompose($form);
    $token = &$form->getElement('token1');
    $token->_attributes['onclick'] = "tokenReplText(this);maxLengthMessage();maxCharInfoDisplay();";

    $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');
    $providerSelect = array();
    foreach ($providers as $provider) {
      if (!empty($provider['is_active'])) {
        $providerSelect[$provider['id']] = $provider['title'];
      }
    }
    $form->add('select', 'sms_provider_id', ts('From'), $providerSelect, TRUE);

    $to = $form->add('text', 'to', ts('To'), array('class' => 'huge'));
    $to->freeze();
    $form->add('text', 'activity_subject', ts('Name The SMS'), array('class' => 'huge'), TRUE);

    if ($form->_single) {
      // also fix the user context stack
      if ($form->_context) {
        $url = CRM_Utils_System::url('civicrm/contact/search', 'reset=1');
      }
      else {
        $contactId = reset($form->_contactIds);
        $url = CRM_Utils_System::url('civicrm/contact/view', 'cid='.$contactId.'&selectedChild=activity');
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
      $form->addDefaultButtons(ts('Send SMS'), 'upload', 'cancel');
    }
    else {
      $form->addDefaultButtons(ts('Send SMS'), 'upload');
    }

    $form->addFormRule(array('CRM_Contact_Form_Task_SMSCommon', 'formRule'), $form);
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $dontCare
   * @param object $self
   *   Additional values form 'this'.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $dontCare, $self) {
    $errors = array();

    $template = CRM_Core_Smarty::singleton();

    if (empty($fields['sms_text_message'])) {
      $errors['sms_text_message'] = ts('Please provide Text message.');
    }
    else {
      if (!empty($fields['sms_text_message'])) {
        $forceSend = $self->get('force_send');
        if (!$forceSend) {
          $messageCheck = CRM_Utils_Array::value('sms_text_message', $fields);
          $messageCheck = str_replace("\r\n", "\n", $messageCheck);
          if (preg_match('/(\{[^\}]+\})/u', $messageCheck)) {
            $errors['sms_text_message'] = ts("Since you have used tokens. The word count may be wrong.");
            $self->set('force_send', TRUE);
            $self->set('has_token', TRUE);
          }
          if(preg_match ("/[\x{4e00}-\x{9fa5}]/u", $messageCheck)){
            if ($messageCheck && (mb_strlen($messageCheck) > CRM_SMS_Provider::MAX_ZH_SMS_CHAR)) {
              $errors['sms_text_message'] .= ts("You can configure the SMS message body up to %1 characters", array(1 => CRM_SMS_Provider::MAX_ZH_SMS_CHAR));
              $self->set('force_send', TRUE);
            }
          }
          else {
            if ($messageCheck && (strlen($messageCheck) > CRM_SMS_Provider::MAX_SMS_CHAR)) {
              $errors['sms_text_message'] .= ts("You can configure the SMS message body up to %1 characters", array(1 => CRM_SMS_Provider::MAX_SMS_CHAR));
              $self->set('force_send', TRUE);
            }
          }
        }
      }
    }

    //Added for CRM-1393
    if (!empty($fields['SMSsaveTemplate']) && empty($fields['SMSsaveTemplateName'])) {
      $errors['SMSsaveTemplateName'] = ts("Enter name to save message template");
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   */
  public static function postProcess(&$form) {

    // check and ensure that
    $thisValues = $form->controller->exportValues($form->getName());

    $fromSmsProviderId = $thisValues['sms_provider_id'];
    $thisValues['sms_text_message'] = htmlspecialchars_decode($thisValues['sms_text_message']);

    // process message template
    if (!empty($thisValues['SMSsaveTemplate']) || !empty($thisValues['SMSupdateTemplate'])) {
      $messageTemplate = array(
        'msg_text' => $thisValues['sms_text_message'],
        'is_active' => TRUE,
        'is_sms' => TRUE,
      );

      if (!empty($thisValues['SMSsaveTemplate'])) {
        $messageTemplate['msg_title'] = $thisValues['SMSsaveTemplateName'];
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }

      if (!empty($thisValues['SMStemplate']) && !empty($thisValues['SMSupdateTemplate'])) {
        $messageTemplate['id'] = $thisValues['SMStemplate'];
        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }
    }

    // format contact details array to handle multiple sms from same contact
    $formattedContactDetails = array();
    $tempPhones = array();

    foreach ($form->_contactIds as $contactId) {
      $phone = $form->_contactDetails[$contactId]['phone'];

      if ($phone) {
        $phoneKey = "{$contactId}::{$phone}";
        if (!in_array($phoneKey, $tempPhones)) {
          $tempPhones[] = $phoneKey;
          if (!empty($form->_contactDetails[$contactId])) {
            $formattedContactDetails[] = $form->_contactDetails[$contactId];
          }
        }
      }
    }

    // $smsParams carries all the arguments provided on form (or via hooks), to the provider->send() method
    // this gives flexibity to the users / implementors to add their own args via hooks specific to their sms providers
    $smsParams = $thisValues;
    unset($smsParams['sms_text_message']);
    $smsParams['provider_id'] = $fromSmsProviderId;
    $contactIds = array_keys($form->_contactDetails);

    if ($form->get('force_send')) {
      $form->set('force_send', FALSE);
    }

    $providerObj = CRM_SMS_Provider::singleton(array('provider_id' => $smsParams['provider_id']));
    if (!empty($providerObj->_bulkMode)) {
      // start batch
      $config = CRM_Core_Config::singleton();
      $batch = new CRM_Batch_BAO_Batch();
      $batchParams = array(
        'label' => ts('SMS').': '.date('YmdHis'),
        'startCallback' => NULL,
        'startCallbackArgs' => NULL,
        'processCallback' => array('CRM_Contact_Form_Task_SMSCommon', 'batchSend'),
        'processCallbackArgs' => array($formattedContactDetails, $thisValues, $smsParams, $contactIds),
        'finishCallback' => NULL,
        'finishCallbackArgs' => NULL,
        'actionPermission' => '',
        'total' => count($formattedContactDetails),
        'processed' => 0,
      );
      $batch->start($batchParams);

      // redirect to notice page
      CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
    }
    else {
      $sendResult = CRM_Activity_BAO_Activity::sendSMS($formattedContactDetails,
        $thisValues,
        $smsParams,
        $contactIds
      );

      $smsNotSent = count($sendResult['activityIds']) - $sendResult['sent'];
      CRM_Core_Session::setStatus(ts('One message was sent successfully.', array(
        'count' => $sendResult['sent'],
        'plural' => '%count messages were sent successfully.',
      )));
      CRM_Core_Session::setStatus(ts('One Message Not Sent', array(
        'count' => $smsNotSent,
        'plural' => '%count Messages Not Sent',
      )));
    }
  }

  public static function batchSend($contactDetails, $activityParams, $smsParams, $contactIds) {
    global $civicrm_batch;

    if ($civicrm_batch) {
      $offset = 0;
      $providerObj = CRM_SMS_Provider::singleton(array('provider_id' => $smsParams['provider_id']));
      $batchLimit = $providerObj->_bulkLimit;
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'];
      }
      $contactDetails = array_slice($contactDetails, $offset, $batchLimit);
      $contactIds = array_slice($contactIds, $offset, $batchLimit);
      CRM_Activity_BAO_Activity::sendSMS(
        $contactDetails,
        $activityParams,
        $smsParams,
        $contactIds
      );
      $civicrm_batch->data['processed'] += count($contactDetails);

      if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
        $civicrm_batch->data['processed'] = $civicrm_batch->data['total'];
        $civicrm_batch->data['isCompleted'] = TRUE;
      }
    }
  }
}
