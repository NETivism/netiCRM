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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */



/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration_ThankYou extends CRM_Event_Form_Registration {

  public $_part;
  public $_totalAmount;
  public $_receiveDate;
  public $_trxnId;
  public $_isOnWaitlist;
  /**
   * @var mixed[]
   */
  public $_submitValues;
  public $_usedOptionsDiscount;
  public $_totalDiscount;
  public $_coupon;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $this->_params = $this->get('params');
    $this->_lineItem = $this->get('lineItem');
    $this->_part = $this->get('part');
    $this->_totalAmount = $this->get('totalAmount');
    $this->_receiveDate = $this->get('receiveDate');
    $trxn_id = $this->get('trxnId');
    $this->_trxnId = !empty($trxn_id) ? $trxn_id : ($this->_params['trxn_id'] ?? '');
    $this->_isOnWaitlist = $this->get('isOnWaitlist');
    $finalAmount = $this->get('finalAmount');
    $this->assign('finalAmount', $finalAmount);
    $participantInfo = $this->get('participantInfo');
    $this->assign('part', $this->_part);
    $this->assign('participantInfo', $participantInfo);
    $customGroup = $this->get('customProfile');
    $this->assign('customProfile', $customGroup);
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values['event']));

    $primaryParticipant = $this->get('registerByID');
    if ($primaryParticipant) {
      $this->assign('registerByID', $primaryParticipant);
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $primaryParticipant, 'contribution_id', 'participant_id');
      if ($contributionId) {
        $this->assign('contribution_id', $contributionId);
        $params['id'] = $contributionId;
        $paymentResultStatus = CRM_Contribute_BAO_Contribution_Utils::paymentResultType($this, $params);
      }
    }

    $participantContactID = $this->get('participantContactID');
    if (!empty($participantContactID)) {
      $detail = CRM_Contact_BAO_Contact::getContactDetails($participantContactID);
      if (!empty($detail[5])) {
        CRM_Core_Error::debug_log_message("Skipped email notify contribution_thankyou for contact {$participantContactID} due to do_not_notify marked");
        $this->assign('do_not_notify', TRUE);
      }
    }

    // add dataLayer for gtm
    if (!$this->get('dataLayerAdded')) {
      if ($this->_trxnId) {
        $transactionId = $this->_trxnId;
      }
      else {
        if ($contributionId) {
          $transactionId = ts('Contribution ID').'-'.$contributionId;
        }
        else {
          $transactionId = ts('Participant Id').'-'.$primaryParticipant;
        }
      }
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $transactionId = 'test-'.$transactionId;
      }
      $this->assign('transaction_id', $transactionId);
      if (!empty($this->_params[0]['currencyID'])) {
        $this->assign('currency_id', $this->_params[0]['currencyID']);
      }
      $this->assign('product_id', ts('Event').'-'.$this->_eventId);
      $this->assign('product_name', $this->_values['event']['title']);
      $this->assign('product_category', $this->_values['event']['event_type']);
      $participantCount = self::getParticipantCount($this, $this->_params);
      $this->assign('product_quantity', $participantCount);
      if ($this->_totalAmount) {
        $this->assign('product_amount', $this->_totalAmount);
        $this->assign('total_amount', $this->_totalAmount);
      }
      else {
        $this->assign('product_amount', 0);
        $this->assign('total_amount', 0);
      }
      $this->assign('dataLayerType', 'purchase');
      $smarty = CRM_Core_Smarty::singleton();
      $dataLayer = $smarty->fetch('CRM/common/DataLayer.tpl');
      if (isset($paymentResultStatus) && $paymentResultStatus == 4) {
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
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();
    // change pay later option if needed
    if(isset($this->_params['is_pay_later'])){
      $this->assign('is_pay_later', $this->_params['is_pay_later']);
    }

    $this->buildCustom($this->_values['custom_pre_id'], 'customPreGroup', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPostGroup', TRUE);

    $this->assign('lineItem', $this->_lineItem);
    $this->assign('totalAmount', $this->_totalAmount);
    $hookDiscount = $this->get('hookDiscount');
    if ($hookDiscount) {
      $this->assign('hookDiscount', $hookDiscount);
    }

    $this->assign('receive_date', $this->_receiveDate);
    $this->assign('trxn_id', $this->_trxnId);

    if (CRM_Utils_Array::value('amount', $this->_params[0]) == 0) {
      $this->assign('isAmountzero', 1);
    }
    $this->assign('defaultRole', FALSE);
    if (CRM_Utils_Array::value('defaultRole', $this->_params[0]) == 1) {
      $this->assign('defaultRole', TRUE);
    }
    $defaults = [];
    $fields = [];
    if (!empty($this->_fields)) {
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    foreach ($fields as $name => $dontCare) {
      if (isset($this->_params[0][$name])) {
        $defaults[$name] = $this->_params[0][$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($this->_params[0][$timeField])) {
            $defaults[$timeField] = $this->_params[0][$timeField];
          }
          if (!empty($this->_uploadedFiles[$name])) {
            if (!empty($this->_params[0][$name]['name'])) {
              $defaults[$name] = ts('File uploaded');
            }
          }
        }
        elseif ($name == 'image_URL') {
          if (!empty($this->_uploadedFiles[$name])) {
            if (!empty($this->_params[0][$name]['name']) || !empty($this->_params[0][$name])) {
              $defaults[$name] = ts('File uploaded');
            }
          }
        }
        elseif (in_array($name, ['addressee', 'email_greeting', 'postal_greeting'])
          && CRM_Utils_Array::value($name . '_custom', $this->_params[0])
        ) {
          $defaults[$name . '_custom'] = $this->_params[0][$name . '_custom'];
        }
        elseif (substr($name, 0, 3) == 'im-') {
          $defaults[$name . '-provider_id'] = $this->_params[0][$name . '-provider_id'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);

    $this->setDefaults($defaults);



    $params['entity_id'] = $this->_eventId;
    $params['entity_table'] = 'civicrm_event';

    CRM_Friend_BAO_Friend::retrieve($params, $data);
    if (CRM_Utils_Array::value('is_active', $data)) {
      $friendText = $data['title'];
      $this->assign('friendText', $friendText);
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $url = CRM_Utils_System::url("civicrm/friend",
          "eid={$this->_eventId}&reset=1&action=preview&page=event"
        );
      }
      else {
        $url = CRM_Utils_System::url("civicrm/friend",
          "eid={$this->_eventId}&reset=1&page=event"
        );
      }
      $this->assign('friendURL', $url);
    }

    $this->freeze();

    //lets give meaningful status message, CRM-4320.
    $isOnWaitlist = $isRequireApproval = FALSE;
    if ($this->_isOnWaitlist && !$this->_allowConfirmation) {
      $isOnWaitlist = TRUE;
    }
    if ($this->_requireApproval && !$this->_allowConfirmation) {
      $isRequireApproval = TRUE;
    }
    $this->assign('isOnWaitlist', $isOnWaitlist);
    $this->assign('isRequireApproval', $isRequireApproval);

    // Assign Participant Count to Lineitem Table

    $this->assign('pricesetFieldsCount', CRM_Price_BAO_Set::getPricesetCount($this->_priceSetId));

    $this->assign('usedOptionsDiscount', $this->_usedOptionsDiscount);
    $this->assign('totalDiscount', $this->_totalDiscount);
    $this->assign('couponDescription', $this->_coupon['description']);

    // can we blow away the session now to prevent hackery
    // $this->controller->reset();
    // $session = CRM_Core_Session::singleton();
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {}
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Complete');
  }
}

