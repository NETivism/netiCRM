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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_BAO_PaymentProcessorType extends CRM_Core_DAO_PaymentProcessorType {

  /**
   * static holder for the default payment processor
   */
  public static $_defaultPaymentProcessorType = NULL;

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve a payment processor type record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_PaymentProcessorType|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $paymentProcessorType = new CRM_Core_DAO_PaymentProcessorType();
    $paymentProcessorType->copyValues($params);
    if ($paymentProcessorType->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessorType, $defaults);
      return $paymentProcessorType;
    }
    return NULL;
  }

  /**
   * Update the is_active flag for a payment processor type in the database.
   *
   * @param int $id ID of the database record
   * @param bool $is_active value to set for the is_active field
   *
   * @return CRM_Core_DAO_PaymentProcessorType|null updated DAO object
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_PaymentProcessorType', $id, 'is_active', $is_active);
  }

  /**
   * Retrieve the default payment processor type.
   *
   * @return CRM_Core_DAO_PaymentProcessorType the default payment processor type object
   */
  public static function &getDefault() {
    if (self::$_defaultPaymentProcessorType == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultPaymentProcessorType = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessorType;
  }

  /**
   * Delete a payment processor type if no processors are associated with it.
   *
   * @param int $paymentProcessorTypeId ID of the processor type to delete
   *
   * @return void
   */
  public static function del($paymentProcessorTypeId) {
    $query = "SELECT pp.id processor_id  
                  FROM civicrm_payment_processor pp, civicrm_payment_processor_type ppt
                  WHERE pp.payment_processor_type = ppt.name AND ppt.id = %1";

    $params = [1 => [$paymentProcessorTypeId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      CRM_Core_Session::setStatus(ts('There is a Payment Processor associated with selected Payment Processor type, hence it can not be deleted.'));
      return;
    }

    $paymentProcessorType = new CRM_Core_DAO_PaymentProcessorType();
    $paymentProcessorType->id = $paymentProcessorTypeId;
    $paymentProcessorType->delete();
    CRM_Core_Session::setStatus(ts('Selected Payment Processor type has been deleted.'));
  }
}
