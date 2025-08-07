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
 * form for thank-you / success page - 3rd step of online contribution process
 */
class CRM_Contribute_Form_Contribution_ThankYou extends CRM_Contribute_Form_ContributionBase {

  public $_lineItem;
  public $_paymentInstrument;
  public $_separateMembershipPayment;
  /**
   * @var mixed[]
   */
  public $_submitValues;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    $this->_params = $this->get('params');
    $this->_lineItem = $this->get('lineItem');
    $this->_paymentInstrument = $this->get('paymentInstrument');
    $is_deductible = $this->get('is_deductible');
    $this->assign('is_deductible', $is_deductible);
    $this->assign('thankyou_title', $this->_values['thankyou_title']);
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
    $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
    $this->assign('max_reminders', CRM_Utils_Array::value('max_reminders', $this->_values));
    $this->assign('initial_reminder_day', CRM_Utils_Array::value('initial_reminder_day', $this->_values));
    $this->assign('contribution_type_id', $this->_values['contribution_type_id']);
    $this->assign_by_ref('contributionPage', $this->_values);

    $instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    if($this->_params['payment_instrument_id']){
      $this->assign('payment_instrument', $instruments[$this->_params['payment_instrument_id']]);
    }
    CRM_Utils_System::setTitle($this->_values['thankyou_title']);
    if ($this->_contributionID) {
      $this->assign('contribution_id', $this->_contributionID);
      $params['id'] = $this->_contributionID;
      $paymentResultStatus = CRM_Contribute_BAO_Contribution_Utils::paymentResultType($this, $params);

      // refs #29618, record one-time donate again link used
      if ($paymentResultStatus == 1 && $this->get('originalId')){
        $cs = $this->get('cs');
        $dao = new CRM_Core_DAO_Sequence();
        $dao->name = 'DA_'.$cs;
        if ($dao->find(TRUE)) {
          $dao->timestamp = microtime(true);
          $dao->value = $this->_contributionID;
          $dao->update();
        }
        else {
          $dao->timestamp = microtime(true);
          $dao->value = $this->_contributionID;
          $dao->insert();
        }
      }
      // do_not_notify check
      $contributionContactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_contributionID, 'contact_id');
      if (!empty($contributionContactId)) {
        $detail = CRM_Contact_BAO_Contact::getContactDetails($contributionContactId);
        if (!empty($detail[5])) {
          CRM_Core_Error::debug_log_message("Skipped email notify contribution_thankyou for contact {$contributionContactId} due to do_not_notify marked");
          $this->assign('do_not_notify', TRUE);
        }
      }
    }

    // add dataLayer for gtm
    if (!$this->get('dataLayerAdded')) {
      if(CRM_Utils_Array::value('trxn_id', $this->_params)) {
        $transactionId = $this->_params['trxn_id'];
      }
      else {
        $transactionId = ts('Contribution ID').'-'.$this->_contributionID;
      }
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $transactionId = 'test-'.$transactionId;
      }
      $this->assign('transaction_id', $transactionId);
      $this->assign('product_id', ts('Contribution Page').'-'.$this->_values['id']);

      if (!empty($this->_params['currencyID'])) {
        $this->assign('currency_id', $this->_params['currencyID']);
      }
      else {
        // should not use here, but in case that currencyID don't have value.
        $currencyID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_contributionID, 'currency');
        $this->assign('currency_id', $currencyID);
      }
      $this->assign('product_name', $this->_values['title']);
      if ($this->_params['is_recur']) {
        $this->assign('product_category', ts('Recurring Contribution'));
      }
      else {
        $this->assign('product_category', ts('Non-recurring Contribution'));
      }
      $this->assign('product_quantity', 1);
      $membershipAmount = $this->get('membership_amount');
      if ($membershipAmount) {
        $this->assign('product_amount', $this->_params['amount']+$membershipAmount);
        $this->assign('total_amount', $this->_params['amount']+$membershipAmount);
      }
      else {
        $this->assign('product_amount', $this->_params['amount']);
        $this->assign('total_amount', $this->_params['amount']);
      }
      $this->assign('dataLayerType', 'purchase');
      $smarty = CRM_Core_Smarty::singleton();
      $dataLayer = $smarty->fetch('CRM/common/DataLayer.tpl');
      if ($paymentResultStatus == 4) {
        $this->assign('dataLayerType', 'refund');
        $dataLayer .= $smarty->fetch('CRM/common/DataLayer.tpl');
      }
      if (!empty($dataLayer)) {
        $obj = [
          'type' => 'markup',
          'markup' => $dataLayer."\n",
        ];
        CRM_Utils_System::addHTMLHead($obj);
        $this->set('dataLayerAdded', true);
      }
    }
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();
    $productID = $this->get('productID');
    $option = $this->get('option');
    $membershipTypeID = $this->get('membershipTypeID');
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    // refs #34646, hide the noreply email when user use default email on page.
    global $civicrm_conf;
    if (!empty($this->_values['receipt_from_email']) && !empty($civicrm_conf['mailing_noreply_domain'])) {
      if (preg_match($civicrm_conf['mailing_noreply_domain'], $this->_values['receipt_from_email'])) {
        $this->assign('display_recurring_email', FALSE);
      }
      else {
        $this->assign('display_recurring_email', TRUE);
      }
    }

    if ($productID) {

      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, FALSE, $productID, $option);
    }

    $this->assign('lineItem', $this->_lineItem);
    $this->assign('priceSetID', $this->_priceSetId);
    $params = $this->_params;

    $honor_block_is_active = $this->get('honor_block_is_active');
    if ($honor_block_is_active &&
      ((!empty($params["honor_first_name"]) && !empty($params["honor_last_name"])) ||
        (!empty($params["honor_email"]))
      )
    ) {
      $this->assign('honor_block_is_active', $honor_block_is_active);
      $this->assign('honor_block_title', CRM_Utils_Array::value('honor_block_title', $this->_values));


      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $this->assign('honor_type', $honor[$params["honor_type_id"]]);
      $this->assign('honor_prefix', ($params["honor_prefix_id"]) ? $prefix[$params["honor_prefix_id"]] : ' ');
      $this->assign('honor_first_name', $params["honor_first_name"]);
      $this->assign('honor_last_name', $params["honor_last_name"]);
      $this->assign('honor_email', $params["honor_email"]);
    }
    //pcp elements
    if ($this->_pcpId) {
      $this->assign('pcpBlock', TRUE);
      foreach (['pcp_display_in_roll', 'pcp_is_anonymous', 'pcp_roll_nickname', 'pcp_personal_note'] as $val) {
        if (CRM_Utils_Array::value($val, $this->_params)) {
          $this->assign($val, $this->_params[$val]);
        }
      }
    }

    if ($membershipTypeID) {
      $memberTrxnId= $this->get('membership_trx_id');
      $membershipAmount = $this->get('membership_amount');
      $renewalMode = $this->get('renewal_mode');
      $this->assign('membership_trx_id', $memberTrxnId);
      $this->assign('membership_amount', $membershipAmount);
      $this->assign('renewal_mode', $renewalMode);

      CRM_Member_BAO_Membership::buildMembershipBlock($this,
        $this->_id,
        FALSE,
        $membershipTypeID,
        TRUE, NULL,
        $this->_membershipContactID
      );
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign("is_separate_payment", $this->_separateMembershipPayment);

    $this->buildCustom($this->_values['custom_pre_id'], 'customPreGroup', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPostGroup', TRUE);

    $this->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $this->_params));
    $this->assign('receive_date', CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params)));

    $defaults = [];
    $options = [];
    $fields = [];

    $removeCustomFieldTypes = ['Contribution'];
    foreach ($this->_fields as $name => $dontCare) {
      $fields[$name] = 1;
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    $contact = $this->_params = $this->controller->exportValues('Main');

    foreach ($fields as $name => $dontCare) {
      if (isset($contact[$name])) {
        if (!strstr($name, 'country') && !strstr($name, 'city') && !strstr($name, 'state_province') && $this->_fields[$name]['html_type'] === 'Text' && $this->get('csContactID')) {
          $defaults[$name] = CRM_Utils_String::mask($contact[$name]);
        }
        else {
          $defaults[$name] = $contact[$name];
        }
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
          if (!empty($this->_uploadedFiles[$name])) {
            if (!empty($contact[$name]['name'])) {
              $defaults[$name] = ts('File uploaded');
            }
          }
        }
        elseif ($name == 'image_URL') {
          if (!empty($this->_uploadedFiles[$name])) {
            if (!empty($contact[$name]['name'])) {
              $defaults[$name] = ts('File uploaded');
            }
          }
        }
        elseif (in_array($name, ['addressee', 'email_greeting', 'postal_greeting'])
          && CRM_Utils_Array::value($name . '_custom', $contact)
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
        elseif (substr($name, 0, 3) == 'im-') {
          $defaults[$name . '-provider_id'] = $contact[$name . '-provider_id'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);
    $this->setDefaults($defaults);

    $values['entity_id'] = $this->_id;
    $values['entity_table'] = 'civicrm_contribution_page';

    CRM_Friend_BAO_Friend::retrieve($values, $data);
    $tellAFriend = FALSE;
    if ($this->_pcpId) {
      if ($this->_pcpBlock['is_tellfriend_enabled']) {
        $this->assign('friendText', ts('Tell a Friend'));
        $subUrl = "eid={$this->_pcpId}&blockId={$this->_pcpBlock['id']}&page=pcp";
        $tellAFriend = TRUE;
      }
    }
    elseif (CRM_Utils_Array::value('is_active', $data)) {
      $friendText = $data['title'];
      $this->assign('friendText', $friendText);
      $subUrl = "eid={$this->_id}&page=contribution";
      $tellAFriend = TRUE;
    }

    if ($tellAFriend) {
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $url = CRM_Utils_System::url("civicrm/friend",
          "reset=1&action=preview&{$subUrl}"
        );
      }
      else {
        $url = CRM_Utils_System::url("civicrm/friend",
          "reset=1&{$subUrl}"
        );
      }
      $this->assign('friendURL', $url);
    }

    $this->freeze();
    // can we blow away the session now to prevent hackery
  }
}

