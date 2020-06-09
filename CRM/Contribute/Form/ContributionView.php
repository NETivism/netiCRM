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

require_once 'CRM/Core/Form.php';

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
    $values = $ids = array();
    $params = array('id' => $id);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);
    $compId = CRM_Utils_Request::retrieve('compId', 'String', $this);
    $this->assign('context', $context);
    $this->assign('compContext', $compContext);
    $this->assign('compId', $compId);

    require_once 'CRM/Contribute/BAO/Contribution.php';
    CRM_Contribute_BAO_Contribution::getValues($params, $values, $ids);

    $instrument_options = CRM_Core_OptionGroup::values('payment_instrument', FALSE);
    $no_expire_date = array(ts('Convenient Store'), ts('Convenient Store code'), ts('ATM'));
    $instrument = $instrument_options[$values['payment_instrument_id']];
    if($values['payment_instrument_id'] != 1 && $instrument != 'Web ATM'){
      $this->assign('has_expire_date', TRUE);
      $this->assign('expire_date', $values['expire_date']);
    }

    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($id);
    if (method_exists($paymentClass, 'getRecordDetail')) {
      $recordDetail = $paymentClass::getRecordDetail($id);
      $this->assign('record_detail', $recordDetail);
    }

    if (method_exists($paymentClass, 'getSyncDataUrl')) {
      $syncUrl = $paymentClass::getSyncDataUrl($id);
      $this->assign('sync_url', $syncUrl);
    }

    $softParams = array('contribution_id' => $values['contribution_id']);
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
      $params = array(1 => array($values['honor_contact_id'], 'Integer'));
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
      $params = array(1 => array($values['contribution_recur_id'], 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $values["recur_installments"] = empty($dao->installments) ? ts("no limit") : $dao->installments;
        $frequency_unit = CRM_Core_OptionGroup::values('recur_frequency_units');
        $values["recur_frequency_unit"] = $frequency_unit[$dao->frequency_unit];
        $values["recur_frequency_interval"] = $dao->frequency_interval;
        $values["recur_info_url"] = CRM_Utils_System::url('civicrm/contact/view/contributionrecur', "reset=1&id={$values['contribution_recur_id']}&cid={$values['contact_id']}");
      }
    }
    $track = CRM_Core_BAO_Track::getTrack('civicrm_contribution', $id);
    if (!empty($track)) {
      $this->assign('track', $track);
    }

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Contribution', $this, $id, 0, $values['contribution_type_id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);

    $premiumId = NULL;
    if ($id) {
      require_once 'CRM/Contribute/DAO/ContributionProduct.php';
      $dao = new CRM_Contribute_DAO_ContributionProduct();
      $dao->contribution_id = $id;
      if ($dao->find(TRUE)) {
        $premiumId = $dao->id;
        $productID = $dao->product_id;
      }
    }

    if ($premiumId) {
      require_once 'CRM/Contribute/DAO/Product.php';
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $productID;
      $productDAO->find(TRUE);

      $this->assign('premium', $productDAO->name);
      $this->assign('option', $dao->product_option);
      $this->assign('fulfilled', $dao->fulfilled_date);
    }

    // Get Note
    $noteValue = CRM_Core_BAO_Note::getNote($values['id'], 'civicrm_contribution');
    // FIXME need to use civicrm format
    if (function_exists('_filter_autop')) {
      foreach ($noteValue as $v) {
        $values['note'][] = _filter_autop($v);
      }
    }
    else {
      $values['note'] = array_values($noteValue);
    }

    // show billing address location details, if exists
    if (CRM_Utils_Array::value('address_id', $values)) {
      $addressParams = array('id' => CRM_Utils_Array::value('address_id', $values));
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

    require_once 'CRM/Price/BAO/Set.php';
    $lineItems = array();
    if ($id && CRM_Price_BAO_Set::getFor('civicrm_contribution', $id)) {
      require_once 'CRM/Price/BAO/LineItem.php';
      $lineItems[] = CRM_Price_BAO_LineItem::getLineItems($id, 'contribution');
    }
    $this->assign('lineItem', empty($lineItems) ? FALSE : $lineItems);
    $values['totalAmount'] = $values['total_amount'];

    if ($values['contribution_status_id'] == 1 && CRM_Contribute_BAO_ContributionType::deductible($values['contribution_type_id'])) {
      $this->assign('is_print_receipt', 1);
    }

    // assign values to the template
    $this->assign($values);

    // get detail about membership payment, contribution page, or event
    $details = CRM_Contribute_BAO_Contribution::getComponentDetails(array($id));
    if (!empty($details[$id])) {
      $this->assign('details', $details[$id]);
    }

    // add viewed contribution to recent items list
    require_once 'CRM/Utils/Recent.php';
    require_once 'CRM/Utils/Money.php';
    require_once 'CRM/Contact/BAO/Contact.php';
    $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $values['contact_id'], 'display_name');
    $this->assign('displayName', $displayName);

    $title = $displayName . ' - (' . CRM_Utils_Money::format($values['total_amount']) . ' ' . ' - ' . $values['contribution_type'] . ')';

    $recentOther = array();
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
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array('type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }
}

