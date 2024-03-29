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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Core/Config.php';
class CRM_Core_Extensions_Payment {

  public function __construct($ext) {
    $this->ext = $ext;
    $this->paymentProcessorTypes = $this->_getAllPaymentProcessorTypes('class_name');
  }

  public function install() {
    if (CRM_Utils_Array::arrayKeyExists($this->ext->key, $this->paymentProcessorTypes)) {
      CRM_Core_Error::fatal('This payment processor type is already installed.');
    }

    $ppByName = $this->_getAllPaymentProcessorTypes('name');
    if (CRM_Utils_Array::arrayKeyExists($this->ext->name, $ppByName)) {
      CRM_Core_Error::fatal('This payment processor type already exists.');
    }


    $dao = new CRM_Core_DAO_PaymentProcessorType();

    $dao->is_active = 1;
    $dao->class_name = trim($this->ext->key);
    $dao->title = trim($this->ext->name) . ' (' . trim($this->ext->key) . ')';
    $dao->name = trim($this->ext->name);
    $dao->description = trim($this->ext->description);

    $dao->user_name_label = trim($this->ext->typeInfo['userNameLabel']);
    $dao->password_label = trim($this->ext->typeInfo['passwordLabel']);
    $dao->signature_label = trim($this->ext->typeInfo['signatureLabel']);
    $dao->subject_label = trim($this->ext->typeInfo['subjectLabel']);
    $dao->url_site_default = trim($this->ext->typeInfo['urlSiteDefault']);
    $dao->url_api_default = trim($this->ext->typeInfo['urlApiDefault']);
    $dao->url_recur_default = trim($this->ext->typeInfo['urlRecurDefault']);
    $dao->url_site_test_default = trim($this->ext->typeInfo['urlSiteTestDefault']);
    $dao->url_api_test_default = trim($this->ext->typeInfo['urlApiTestDefault']);
    $dao->url_recur_test_default = trim($this->ext->typeInfo['urlRecurTestDefault']);
    $dao->url_button_default = trim($this->ext->typeInfo['urlButtonDefault']);
    $dao->url_button_test_default = trim($this->ext->typeInfo['urlButtonTestDefault']);

    require_once 'CRM/Core/Payment.php';
    switch (trim($this->ext->typeInfo['billingMode'])) {
      case 'form':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_FORM;
        break;

      case 'button':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_BUTTON;
        break;

      case 'notify':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_NOTIFY;
        break;

      default:
        CRM_Core_Error::fatal('Billing mode in info file has wrong value.');
    }

    $dao->is_recur = trim($this->ext->typeInfo['isRecur']);
    $dao->payment_type = trim($this->ext->typeInfo['paymentType']);

    $dao->save();
  }

  /**
   * undocumented function
   *
   * @return void
   **/
  public function uninstall() {
    if (!CRM_Utils_Array::arrayKeyExists($this->ext->key, $this->paymentProcessorTypes)) {
      CRM_Core_Error::fatal('This payment processor type is not registered.');
    }

    require_once 'CRM/Core/PseudoConstant.php';
    $paymentProcessors = CRM_Core_PseudoConstant::paymentProcessor(TRUE);

    require_once "CRM/Core/DAO/PaymentProcessor.php";
    foreach ($paymentProcessors as $id => $name) {
      $dao = new CRM_Core_DAO_PaymentProcessor();
      $dao->id = $id;
      $dao->find();
      while ($dao->fetch()) {
        if ($dao->payment_processor_type == $this->ext->name) {
          CRM_Core_Error::fatal('Cannot uninstall this extension - there is at least one payment processor using payment processor type provided by it.');
        }
      }
    }

    require_once "CRM/Core/BAO/PaymentProcessorType.php";
    CRM_Core_BAO_PaymentProcessorType::del($this->paymentProcessorTypes[$this->ext->key]);
  }

  public function disable() {
    require_once "CRM/Core/BAO/PaymentProcessorType.php";
    CRM_Core_BAO_PaymentProcessorType::setIsActive($this->paymentProcessorTypes[$this->ext->key], 0);
  }

  public function enable() {
    require_once "CRM/Core/BAO/PaymentProcessorType.php";
    CRM_Core_BAO_PaymentProcessorType::setIsActive($this->paymentProcessorTypes[$this->ext->key], 1);
  }

  private function _getAllPaymentProcessorTypes($attr) {
    $ppt = array();
    require_once "CRM/Core/DAO/PaymentProcessorType.php";
    require_once "CRM/Core/DAO.php";
    $dao = new CRM_Core_DAO_PaymentProcessorType();
    $dao->find();
    while ($dao->fetch()) {
      $ppt[$dao->$attr] = $dao->id;
    }
    return $ppt;
  }
}

