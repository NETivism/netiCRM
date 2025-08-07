<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Payment_ProcessorForm {
  static function preProcess(&$form, $type = NULL, $mode = NULL) {
    if ($type) {
      $form->_type = $type;
    }
    else {
      $form->_type = CRM_Utils_Request::retrieve('type', 'String', $form);
    }

    if ($form->_type) {
      $form->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($form->_type, $form->_mode);
    }

    $form->set('paymentProcessor', $form->_paymentProcessor);

    //checks after setting $form->_paymentProcessor
    // we do this outside of the above conditional to avoid
    // saving the country/state list in the session (which could be huge)

    if (($form->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_FORM) &&
      CRM_Utils_Array::value('is_monetary', $form->_values)
    ) {
      if ($form->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT) {
        CRM_Core_Payment_Form::setDirectDebitFields($form);
      }
      else {
        CRM_Core_Payment_Form::setCreditCardFields($form);
      }
    }

    $form->assign_by_ref('paymentProcessor', $form->_paymentProcessor);

    // check if this is a paypal auto return and redirect accordingly
    if (CRM_Core_Payment::paypalRedirect($form->_paymentProcessor)) {
      $url = CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_ThankYou_display=1&qfKey={$form->controller->_key}"
      );
      CRM_Utils_System::redirect($url);
    }

    // make sure we have a valid payment class, else abort
    if (CRM_Utils_Array::value('is_monetary', $form->_values) &&
      !$form->_paymentProcessor['class_name'] &&
      !CRM_Utils_Array::value('is_pay_later', $form->_values)
    ) {
      $form->addRule('payment_processor', ts('%1 is a required field.', [1 => ts('Payment Method')]), 'required');
    }

    if (!empty($form->_membershipBlock) &&
      CRM_Utils_Array::value('is_separate_payment', $form->_membershipBlock) &&
      (CRM_Utils_Array::value('class_name', $form->_paymentProcessor) &&
        !(CRM_Utils_Array::value('billing_mode', $form->_paymentProcessor) & CRM_Core_Payment::BILLING_MODE_FORM)
      )
    ) {

       return CRM_Core_Error::statusBounce(ts('This contribution page is configured to support separate contribution and membership payments. This %1 plugin does not currently support multiple simultaneous payments, or the option to "Execute real-time monetary transactions" is disabled. Please contact the site administrator and notify them of this error',
          [1 => $form->_paymentProcessor['payment_processor_type']]
        ));
    }

    $profileAddressFields = $form->get('profileAddressFields');
    if (!empty($profileAddressFields)) {
      $form->assign('profileAddressFields', $profileAddressFields);
    }
  }

  static function buildQuickform(&$form) {
    $form->addElement('hidden', 'hidden_processor', 1);

    if (($form->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
      )) {
      CRM_Core_Payment_Form::buildDirectDebit($form);
    }
    elseif (($form->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD
      )) {
      CRM_Core_Payment_Form::buildCreditCard($form);
    }
  }
}

