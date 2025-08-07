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
 * This class generates form components for Payment-Instrument
 *
 */
class CRM_Contribute_Form_ContributionView extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $id = $this->get('id');
    $this->assign('id', $id);
    $values = $ids = [];
    $params = ['id' => $id];
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);
    $compId = CRM_Utils_Request::retrieve('compId', 'String', $this);
    $this->assign('context', $context);
    $this->assign('compContext', $compContext);
    $this->assign('compId', $compId);


    CRM_Contribute_BAO_Contribution::getValues($params, $values, $ids);

    $instrument_options = CRM_Core_OptionGroup::values('payment_instrument', FALSE);
    $no_expire_date = [ts('Convenient Store'), ts('Convenient Store code'), ts('ATM')];
    $instrument = $instrument_options[$values['payment_instrument_id']];
    if ($instrument == ts('Check')) {
      $this->assign('payment_instrument_name', 'Check');
    }
    if($values['payment_instrument_id'] != 1 && $instrument != 'Web ATM'){
      $this->assign('has_expire_date', TRUE);
      $this->assign('expire_date', $values['expire_date']);
    }

    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($id);
    if (!empty($paymentClass) && method_exists($paymentClass, 'getRecordDetail')) {
      $recordDetail = $paymentClass::getRecordDetail($id);
      $this->assign('record_detail', $recordDetail);
    }

    if (!empty($paymentClass) && method_exists($paymentClass, 'getSyncDataUrl')) {
      $syncUrl = $paymentClass::getSyncDataUrl($id, $this);
      $this->assign('sync_url', $syncUrl);
      $syncDataHint = $this->get('sync_data_hint');
      if (!empty($syncDataHint)) {
        $this->assign('sync_data_hint', $syncDataHint);
      }
    }

    $softParams = ['contribution_id' => $values['contribution_id']];
    if ($softContribution = CRM_Contribute_BAO_Contribution::getSoftContribution($softParams, TRUE)) {
      $values = array_merge($values, $softContribution);
    }
    CRM_Contribute_BAO_Contribution::resolveDefaults($values);
    $taxTypes = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_taxreceipt');
    if (!empty($taxTypes[$values['contribution_type_id']])) {
      $taxReceiptImplements = CRM_Utils_Hook::availableHooks('civicrm_validateTaxReceipt');
      $taxReceiptImplements = count($taxReceiptImplements);
      if (!empty($taxReceiptImplements)) {
        $values['is_taxreceipt'] = 1;
      }
    }

    if (CRM_Utils_Array::value('contribution_page_id', $values)) {
      $contribPages = CRM_Contribute_PseudoConstant::contributionPage();
      $values["contribution_page_title"] = CRM_Utils_Array::value(CRM_Utils_Array::value('contribution_page_id', $values), $contribPages);
    }

    if (CRM_Utils_Array::value('honor_contact_id', $values)) {
      $sql = "SELECT display_name FROM civicrm_contact WHERE id = %1";
      $params = [1 => [$values['honor_contact_id'], 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$values[honor_contact_id]");
        $values["honor_display"] = "<A href = $url>" . $dao->display_name . "</A>";
      }
      $honor = CRM_Core_PseudoConstant::honor();
      $values['honor_type'] = $honor[$values['honor_type_id']];
    }

    if (CRM_Utils_Array::value('contribution_recur_id', $values)) {
      $sql = "SELECT  installments, frequency_interval, frequency_unit FROM civicrm_contribution_recur WHERE id = %1";
      $params = [1 => [$values['contribution_recur_id'], 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $values["recur_installments"] = empty($dao->installments) ? ts("no limit") : $dao->installments;
        $frequency_unit = CRM_Core_OptionGroup::values('recur_frequency_units');
        $values["recur_frequency_unit"] = $frequency_unit[$dao->frequency_unit];
        $values["recur_frequency_interval"] = $dao->frequency_interval;
        $values["recur_info_url"] = CRM_Utils_System::url('civicrm/contact/view/contributionrecur', "reset=1&id={$values['contribution_recur_id']}&cid={$values['contact_id']}");
      }
    }
    if (CRM_Utils_Array::value('amount_level', $values)) {
      CRM_Event_BAO_Participant::fixEventLevel($values['amount_level']);
      $values['amount_level'] = str_replace(', ', '<br>', $values['amount_level']);
    }
    $track = CRM_Core_BAO_Track::getTrack('civicrm_contribution', $id);
    if (!empty($track)) {
      $this->assign('track', $track);
    }

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Contribution', $this, $id, 0, $values['contribution_type_id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);

    $premiumId = NULL;
    if ($id) {

      $dao = new CRM_Contribute_DAO_ContributionProduct();
      $dao->contribution_id = $id;
      if ($dao->find(TRUE)) {
        $premiumId = $dao->id;
        $productID = $dao->product_id;
      }
    }

    if ($premiumId) {

      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $productID;
      $productDAO->find(TRUE);

      $this->assign('premium', $productDAO->name);
      $this->assign('option', $dao->product_option);
      $this->assign('fulfilled', $dao->fulfilled_date);
    }

    // Get Note
    $noteValue = CRM_Core_BAO_Note::getNote($values['id'], 'civicrm_contribution');
    foreach ($noteValue as $v) {
      $values['note'][] = nl2br($v);
    }

    // show billing address location details, if exists
    if (CRM_Utils_Array::value('address_id', $values)) {
      $addressParams = ['id' => CRM_Utils_Array::value('address_id', $values)];
      $addressDetails = CRM_Core_BAO_Address::getValues($addressParams, FALSE, 'id');
      $addressDetails = array_values($addressDetails);
      $values['billing_address'] = $addressDetails[0]['display'];
    }

    //get soft credit record if exists.
    if ($softContribution = CRM_Contribute_BAO_Contribution::getSoftContribution($softParams)) {

      $softContribution['softCreditToName'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $softContribution['soft_credit_to'], 'display_name'
      );
      //hack to avoid dispalyName conflict
      //for viewing softcredit record.
      $softContribution['displayName'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $values['contact_id'], 'display_name'
      );
      $values = array_merge($values, $softContribution);
    }


    $lineItems = [];
    if ($id && CRM_Price_BAO_Set::getFor('civicrm_contribution', $id)) {

      $lineItems[] = CRM_Price_BAO_LineItem::getLineItems($id, 'contribution');
    }
    $this->assign('lineItem', empty($lineItems) ? FALSE : $lineItems);
    $values['totalAmount'] = $values['total_amount'];

    // assign values to the template
    $this->assign($values);

    $deductibleTypes = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_deductible');
    if (in_array($values['contribution_type'], $deductibleTypes)) {
      $this->assign('isdeductible', 1);
    }

    // get detail about membership payment, contribution page, or event
    $details = CRM_Contribute_BAO_Contribution::getComponentDetails([$id]);
    if (!empty($details[$id])) {
      $this->assign('details', $details[$id]);
    }

    // add viewed contribution to recent items list



    $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $values['contact_id'], 'display_name');
    $this->assign('displayName', $displayName);

    $title = $displayName . ' - (' . CRM_Utils_Money::format($values['total_amount']) . ' ' . ' - ' . $values['contribution_type'] . ')';

    $recentOther = [];
    if (CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/contribution',
        "action=update&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    $pdfTypes = CRM_Contribute_Form_Task_PDF::getPrintingTypes();
    $this->assign('pdfTypes', $pdfTypes);

    CRM_Utils_Recent::add($title,
      $url,
      $values['id'],
      'Contribution',
      $values['contact_id'],
      NULL,
      $recentOther
    );

    // receipt sending activity
    $sortID = NULL;
    $activityTypes = ['Email Receipt', 'Print Contribution Receipts'];
    foreach ($activityTypes as $typeName) {
      $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', $typeName, 'name');
      if (!empty($activityTypeId)) {
        $activityTypeIds[] = $activityTypeId;
      }
    }
    if (!empty($activityTypeIds)) {
      $filter = [
        'activity_record_id' => $values['id'], // source_record_id
        'activity_type_id' => $activityTypeIds,
        'activity_test' => $values['is_test'],
      ];
      $queryParams = CRM_Contact_BAO_Query::convertFormValues($filter);
      $selector = new CRM_Activity_Selector_Search($queryParams, $this->_action);
      $controller2 = new CRM_Core_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $sortID,
        CRM_Core_Action::VIEW,
        $this,
        CRM_Core_Selector_Controller::TRANSFER
      );
      $controller2->setEmbedded(TRUE);
      $controller2->run();
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons([
        ['type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ]
    );
  }
}

