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
class CRM_Contribute_Form_AdditionalInfo {

  /**
   * Function to build the form for Premium Information.
   *
   * @access public
   *
   * @return None
   */
  static function buildPremium(&$form) {
    //premium section
    $form->add('hidden', 'hidden_Premium', 1);
    require_once 'CRM/Contribute/DAO/Product.php';
    $sel1 = $sel2 = array();

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->find();
    $min_amount = array();
    $sel1[0] = ts('- select -');
    while ($dao->fetch()) {
      $sel1[$dao->id] = $dao->name . " ( " . $dao->sku . " )";
      if ($dao->calculate_mode == 'first') {
        $min_contribution = min($dao->min_contribution, $dao->min_contribution_recur);
      }
      else {
        // condition: $dao->calculate_mode == 'cumulative'
        $min_contribution = $dao->min_contribution;
      }
      $min_amount[$dao->id] = $min_contribution;
      $options = explode(',', $dao->options);
      foreach ($options as $k => $v) {
        $options[$k] = trim($v);
      }
      if ($options[0] != '') {
        $sel2[$dao->id] = $options;
      }
      $form->assign('premiums', TRUE);
    }
    // Display Item if it's selected even if it disabled. refs #28171
    if (!empty($form->_id) && get_class($form) == 'CRM_Contribute_Form_Contribution') {
      $selectedProductId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionProduct', $form->_id, 'product_id', 'contribution_id' );
      if (!empty($selectedProductId) && empty($sel1[$selectedProductId])) {
        $dao = new CRM_Contribute_DAO_Product();
        $dao->id = $selectedProductId;
        $dao->find(TRUE);
        $sel1[$dao->id] = $dao->name . " ( " . $dao->sku . " ) ( ".ts('Disable')." )";
        if ($dao->calculate_mode == 'first') {
          $min_contribution = min($dao->min_contribution, $dao->min_contribution_recur);
        }
        else {
          // condition: $dao->calculate_mode == 'cumulative';
          $min_contribution = $dao->min_contribution;
        }
        $min_amount[$dao->id] = $min_contribution;
        $options = explode(',', $dao->options);
        foreach ($options as $k => $v) {
          $options[$k] = trim($v);
        }
        if ($options[0] != '') {
          $sel2[$dao->id] = $options;
        }
        $form->assign('premiums', TRUE);
      }
    }
    $form->_options = $sel2;
    $form->assign('mincontribution', $min_amount);
    $sel = &$form->addElement('hierselect', "product_name", ts('Premium'), 'onclick="showMinContrib();"');
    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $form->getName();

    for ($k = 1; $k < 2; $k++) {
      if (!isset($defaults['product_name'][$k]) || (!$defaults['product_name'][$k])) {
        $js .= "{$formName}['product_name[$k]'].style.display = 'none';\n";
      }
    }

    $sel->setOptions(array($sel1, $sel2));
    $js .= "</script>\n";
    $form->assign('initHideBoxes', $js);

    $form->addDate('fulfilled_date', ts('Fulfilled'), FALSE, array('formatType' => 'activityDate'));
    $form->addElement('text', 'min_amount', ts('Minimum Contribution Amount'));
  }

  /**
   * Function to build the form for Additional Details.
   *
   * @access public
   *
   * @return None
   */
  static function buildAdditionalDetail(&$form) {
    //Additional information section
    $form->add('hidden', 'hidden_AdditionalDetail', 1);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    $form->addDateTime('thankyou_date', ts('Thank-you Sent'), FALSE, array('formatType' => 'activityDateTime'));

    // add various amounts
    $element = &$form->add('text', 'non_deductible_amount', ts('Non-deductible Amount'),
      $attributes['non_deductible_amount']
    );
    $form->addRule('non_deductible_amount', ts('Please enter a valid amount.'), 'money');

    if ($form->_online) {
      $element->freeze();
    }
    $element = &$form->add('text', 'fee_amount', ts('Transaction Fee Amount'),
      $attributes['fee_amount']
    );
    $form->addRule('fee_amount', ts('Please enter a valid amount.'), 'money');
    if ($form->_online) {
      $element->freeze();
    }
    $element = &$form->add('text', 'net_amount', ts('Net Amount'),
      $attributes['net_amount']
    );
    $form->addRule('net_amount', ts('Please enter a valid amount.'), 'money');
    if ($form->_online) {
      $element->freeze();
    }
    $element = &$form->add('text', 'invoice_id', ts('Invoice ID'),
      $attributes['invoice_id']
    );
    if ($form->_online) {
      // $element->freeze( );
    }
    else {
      $form->addRule('invoice_id',
        ts('This Invoice ID already exists in the database.'),
        'objectExists',
        array('CRM_Contribute_DAO_Contribution', $form->_id, 'invoice_id')
      );
    }

    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    foreach($pages as $pageId => $pageName) {
      $pages[$pageId] .= " (ID: $pageId)";
    }
    $form->add('select', 'contribution_page_id',
      ts('Contribution Page'),
      array('' => ts('- select -')) +
      $pages
    );

    $form->add('textarea', 'note', ts('Notes'), array("rows" => 4, "cols" => 60));
  }

  /**
   * Function to build the form for Honoree Information.
   *
   * @access public
   *
   * @return None
   */
  static function buildHonoree(&$form) {
    //Honoree section
    $form->add('hidden', 'hidden_Honoree', 1);
    $honor = CRM_Core_PseudoConstant::honor();
    $extraOption = array('onclick' => "return enableHonorType();");
    foreach ($honor as $key => $var) {
      $honorTypes[$key] = $form->createElement('radio', NULL, NULL, $var, $key, $extraOption);
    }
    $form->addGroup($honorTypes, 'honor_type_id', NULL);
    $form->add('select', 'honor_prefix_id', ts('Prefix'), array('' => ts('- prefix -')) + CRM_Core_PseudoConstant::individualPrefix());
    $form->add('text', 'honor_first_name', ts('First Name'));
    $form->add('text', 'honor_last_name', ts('Last Name'));
    $form->add('text', 'honor_email', ts('Email'));
    $form->addRule("honor_email", ts('Email is not valid.'), 'email');
  }

  /**
   * Function to build the form for PaymentReminders Information.
   *
   * @access public
   *
   * @return None
   */
  static function buildPaymentReminders(&$form) {
    //PaymentReminders section
    $form->add('hidden', 'hidden_PaymentReminders', 1);
    $form->add('text', 'initial_reminder_day', ts('Send Initial Reminder'), array('size' => 3));
    $form->addRule('initial_reminder_day', ts('Please enter a valid reminder day.'), 'positiveInteger');
    $form->add('text', 'max_reminders', ts('Send up to'), array('size' => 3));
    $form->addRule('max_reminders', ts('Please enter a valid No. of reminders.'), 'positiveInteger');
    $form->add('text', 'additional_reminder_day', ts('Send additional reminders'), array('size' => 3));
    $form->addRule('additional_reminder_day', ts('Please enter a valid additional reminder day.'), 'positiveInteger');
  }

  /**
   * Function to process the Premium Information
   *
   * @access public
   *
   * @return None
   */
  static function processPremium(&$params, $contributionID, $premiumID = NULL, &$options = NULL) {
    require_once 'CRM/Contribute/DAO/ContributionProduct.php';
    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $contributionID;
    $dao->product_id = $params['product_name'][0];
    $dao->fulfilled_date = CRM_Utils_Date::processDate($params['fulfilled_date'], NULL, TRUE);
    if (CRM_Utils_Array::value($params['product_name'][0], $options)) {
      $dao->product_option = $options[$params['product_name'][0]][$params['product_name'][1]];
    }
    if ($premiumID) {
      $premoumDAO = new CRM_Contribute_DAO_ContributionProduct();
      $premoumDAO->id = $premiumID;
      $premoumDAO->find(TRUE);
      if ($premoumDAO->product_id == $params['product_name'][0]) {
        $dao->id = $premiumID;
        $premium = $dao->save();
      }
      else {
        $premoumDAO->delete();
        $premium = $dao->save();
      }
    }
    else {
      $premium = $dao->save();
    }
  }

  /**
   * Function to process the Note
   *
   * @access public
   *
   * @return None
   */
  static function processNote(&$params, $contactID, $contributionID, $contributionNoteID = NULL) {
    //process note
    require_once 'CRM/Core/BAO/Note.php';
    $noteParams = array('entity_table' => 'civicrm_contribution',
      'note' => $params['note'],
      'entity_id' => $contributionID,
      'contact_id' => $contactID,
    );
    $noteID = array();
    if ($contributionNoteID) {
      $noteID = array("id" => $contributionNoteID);
      $noteParams['note'] = $noteParams['note'] ? $noteParams['note'] : "null";
    }
    CRM_Core_BAO_Note::add($noteParams, $noteID);
  }

  /**
   * Function to process the Common data
   *
   * @access public
   *
   * @return None
   */
  static function postProcessCommon(&$params, &$formatted) {
    $fields = array('non_deductible_amount',
      'total_amount',
      'fee_amount',
      'net_amount',
      'trxn_id',
      'invoice_id',
      'honor_type_id',
      'contribution_page_id',
    );
    foreach ($fields as $f) {
      $formatted[$f] = CRM_Utils_Array::value($f, $params);
    }

    if (CRM_Utils_Array::value('thankyou_date', $params) && !CRM_Utils_System::isNull($params['thankyou_date'])) {
      $formatted['thankyou_date'] = CRM_Utils_Date::processDate($params['thankyou_date'], $params['thankyou_date_time']);
    }
    else {
      $formatted['thankyou_date'] = 'null';
    }

    if (CRM_Utils_Array::value('is_email_receipt', $params) && empty($params['receipt_date'])) {
      $params['receipt_date'] = $formatted['receipt_date'] = date('YmdHis');
    }

    if (CRM_Utils_Array::value('honor_type_id', $params)) {
      require_once 'CRM/Contribute/BAO/Contribution.php';
      if ($params['honorID']) {
        $honorId = CRM_Contribute_BAO_Contribution::createHonorContact($params, $params['honorID']);
      }
      else {
        $honorId = CRM_Contribute_BAO_Contribution::createHonorContact($params);
      }
      $formatted["honor_contact_id"] = $honorId;
    }
    else {
      $formatted["honor_contact_id"] = 'null';
    }

    //special case to handle if all checkboxes are unchecked
    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('contribution_type_id',
        $params
      )
    );
    $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      CRM_Utils_Array::value('id', $params, NULL),
      'Contribution'
    );
  }

  /**
   * Function to send email receipt.
   *
   * @form object  of Contribution form.
   *
   * @param array  $params (reference ) an assoc array of name/value pairs.
   * @$ccContribution boolen,  is it credit card contribution.
   * @access public.
   *
   * @return None.
   */
  static function emailReceipt(&$form, &$params, $ccContribution = FALSE) {
    if (!empty($params['is_attach_receipt'])) {
      $config = CRM_Core_Config::singleton();
      $receiptEmailType = !empty($config->receiptEmailType) ? $config->receiptEmailType : 'copy_only';
      $receiptTask = new CRM_Contribute_Form_Task_PDF();
      $receiptTask->makeReceipt($params['contribution_id'], $receiptEmailType, TRUE);
      $pdfFilePath = $receiptTask->makePDF(False);
      $pdfFileName = strstr($pdfFilePath, 'Receipt');
      $pdfParams =  array(
        'fullPath' => $pdfFilePath,
        'mime_type' => 'application/pdf',
        'cleanName' => $pdfFileName,
      );
    }

    $form->assign('receiptType', 'contribution');
    // Retrieve Contribution Type Name from contribution_type_id
    $params['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
      $params['contribution_type_id']
    );
    if (CRM_Utils_Array::value('payment_instrument_id', $params)) {
      require_once 'CRM/Contribute/PseudoConstant.php';
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $params['paidBy'] = $paymentInstrument[$params['payment_instrument_id']];
    }
    // retrieve individual prefix value for honoree
    if (CRM_Utils_Array::value('hidden_Honoree', $params)) {
      $individualPrefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $params['honor_prefix'] = CRM_Utils_Array::value($params['honor_prefix_id'], $individualPrefix);
      $params["honor_type"] = CRM_Utils_Array::value($params["honor_type_id"], $honor);
    }
    if (CRM_Utils_Array::value('honor_first_name', $params)) {
      $params['honor_first_name'] = CRM_Utils_String::mask($params['honor_first_name']);
    }
    if (CRM_Utils_Array::value('honor_email', $params)) {
      $params['honor_email'] = CRM_Utils_String::mask($params['honor_email']);
    }

    // retrieve premium product name and assigned fulfilled
    // date to template
    if (CRM_Utils_Array::value('hidden_Premium', $params)) {
      if (CRM_Utils_Array::value($params['product_name'][0], $form->_options)) {
        $params['product_option'] = $form->_options[$params['product_name'][0]][$params['product_name'][1]];
      }
      //fix for crm-4584
      if (!empty($params['product_name'])) {
        require_once 'CRM/Contribute/DAO/Product.php';
        $productDAO = new CRM_Contribute_DAO_Product();
        $productDAO->id = $params['product_name'][0];
        $productDAO->find(TRUE);
        $params['product_name'] = $productDAO->name;
        $params['product_sku'] = $productDAO->sku;
      }
      $form->assign('fulfilled_date', CRM_Utils_Date::processDate($params['fulfilled_date']));
    }

    $form->assign('ccContribution', $ccContribution);
    if ($ccContribution) {
      //build the name.
      $name = CRM_Utils_Array::value('billing_first_name', $params);
      if (CRM_Utils_Array::value('billing_middle_name', $params)) {
        $name .= " {$params['billing_middle_name']}";
      }
      $name .= ' ' . CRM_Utils_Array::value('billing_last_name', $params);
      $name = trim($name);
      $form->assign('billingName', $name);

      //assign the address formatted up for display
      $addressParts = array("street_address" => "billing_street_address-{$form->_bltID}",
        "city" => "billing_city-{$form->_bltID}",
        "postal_code" => "billing_postal_code-{$form->_bltID}",
        "state_province" => "state_province-{$form->_bltID}",
        "country" => "country-{$form->_bltID}",
      );

      $addressFields = array();
      foreach ($addressParts as $name => $field) {
        $addressFields[$name] = CRM_Utils_Array::value($field, $params);
      }
      require_once 'CRM/Utils/Address.php';
      $form->assign('address', CRM_Utils_Address::format($addressFields));

      $date = CRM_Utils_Date::format($params['credit_card_exp_date']);
      $date = CRM_Utils_Date::mysqlToIso($date);
      $form->assign('credit_card_type', CRM_Utils_Array::value('credit_card_type', $params));
      $form->assign('credit_card_exp_date', $date);
      $form->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard($params['credit_card_number'])
      );
    }
    else {
      //offline contribution
      //Retrieve the name and email from receipt is to be send
      $params['receipt_from_name'] = $form->userDisplayName;
      $params['receipt_from_email'] = $form->userEmail;
      // assigned various dates to the templates
      $form->assign('receipt_date', CRM_Utils_Date::processDate($params['receipt_date']));
      $form->assign('cancel_date', CRM_Utils_Date::processDate($params['cancel_date']));
      if (CRM_Utils_Array::value('thankyou_date', $params)) {
        $form->assign('thankyou_date', CRM_Utils_Date::processDate($params['thankyou_date']));
      }
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $form->assign('lineItem', empty($form->_lineItems) ? FALSE : $form->_lineItems);
      }
    }

    //handle custom data
    if (!empty($params['contribution_page_id'])) {
      $profiles = array();
      // page profile pre id
      $ufJoinParams = array(
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $params['contribution_page_id'],
        'weight' => 1,
        'module' => 'CiviContribute',
      );
      $profiles['pre']['id'] = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams);
      $ufJoinParams['weight'] = 2;
      $profiles['post']['id'] = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams);
      $customGroup = array();
      foreach($profiles as $idx => $ufGroup) {
        $customFields = $customValues = array();
        if (!empty($ufGroup['id']) && CRM_Core_BAO_UFGroup::filterUFGroups($ufGroup['id'], $params['contact_id'])) {
          $groupTitle = NULL;
          $customFields = CRM_Core_BAO_UFGroup::getFields($ufGroup['id'], FALSE, CRM_Core_Action::VIEW);

          foreach ($customFields as $k => $v) {
            if (!$groupTitle) {
              $groupTitle = $v["groupTitle"];
            }
            // unset all view only profile field
            if ($v['is_view']){
              unset($customFields[$k]);
            }
          }

          $contribParams = array(array('contribution_id', '=', $params['contribution_id'], 0, 0));
          if ($form->_mode == 'test') {
            $contribParams[] = array('contribution_test', '=', 1, 0, 0);
          }
          CRM_Core_BAO_UFGroup::getValues($params['contact_id'], $customFields, $customValues, FALSE, $contribParams, CRM_Core_BAO_UFGroup::MASK_ALL);
          if (!empty(array_filter($customValues))) {
            $customGroup[$groupTitle] = $customValues;
          }
        }
      }
      $form->assign('customGroup', $customGroup);
    }
    // refs #35201, do not add any custom data to offline template when no contribution page specify

    if ($params['receipt_text']) {
      $params['receipt_text'] = CRM_Contribute_BAO_ContributionPage::tokenize($params['contact_id'], $params['receipt_text']); 
    }
    $form->assign_by_ref('formValues', $params);
    require_once 'CRM/Contact/BAO/Contact/Location.php';
    require_once 'CRM/Utils/Mail.php';
    list($contributorDisplayName,
      $contributorEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($params['contact_id']);
    $form->assign('contactID', $params['contact_id']);
    $form->assign('contributionID', $params['contribution_id']);
    $form->assign('currency', $params['currency']);
    $form->assign('receive_date', CRM_Utils_Date::processDate($params['receive_date']));

    if ($params['from_email_address']) {
      $fromEmailAddress = $params['from_email_address'];
    }
    else{
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
      $fromEmailAddress = "$userName <$userEmail>";
    }

    require_once 'CRM/Core/BAO/MessageTemplates.php';
    $templateParams = array(
      'groupName' => 'msg_tpl_workflow_contribution',
      'valueName' => 'contribution_offline_receipt',
      'contactId' => $params['contact_id'],
      'from' => $fromEmailAddress,
      'toName' => $contributorDisplayName,
      'toEmail' => $contributorEmail,
      'isTest' => $form->_mode == 'test',
    );
    if (!empty($params['is_attach_receipt'])) {
      $templateParams['attachments'][] = $pdfParams;
      $templateParams['tplParams']['pdf_receipt'] = 1;
    }
    else {
      $templateParams['PDFFilename'] = 'receipt.pdf';
    }

    $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow($templateParams['groupName'], $templateParams['valueName']);
    $contribParams = array('id' => $params['contribution_id']);
    $contribution = CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_Contribution', $contribParams, CRM_Core_DAO::$_nullArray);
    if (!empty($params['is_attach_receipt']) && !empty(CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name'))) {
      $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($contribution, 'Email Receipt', $workflow['msg_title']);
    }
    else {
      $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($contribution, 'Contribution Notification Email', $workflow['msg_title']);
    }
    $templateParams['activityId'] = $activityId;
    list($sendReceipt, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($templateParams, CRM_Core_DAO::$_nullObject, array(
      0 => array('CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  array($activityId, TRUE)),
      1 => array('CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  array($activityId, FALSE)),
    ));


    return $sendReceipt;
  }

  /**
   * Function to process price set and line items.
   *
   * @access public
   *
   * @return None
   */
  static function processPriceSet($contributionId, $lineItem) {
    if (!$contributionId || !is_array($lineItem)
      || CRM_Utils_System::isNull($lineItem)
    ) {
      return;
    }

    require_once 'CRM/Price/BAO/Set.php';
    require_once 'CRM/Price/BAO/LineItem.php';
    foreach ($lineItem as $priceSetId => $values) {
      if (!$priceSetId) {
        continue;
      }
      foreach ($values as $line) {
        $line['entity_table'] = 'civicrm_contribution';
        $line['entity_id'] = $contributionId;
        CRM_Price_BAO_LineItem::create($line);
      }
      CRM_Price_BAO_Set::addTo('civicrm_contribution', $contributionId, $priceSetId);
    }
  }
}

