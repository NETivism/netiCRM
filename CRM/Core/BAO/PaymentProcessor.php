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
 * This class contains payment processor related functions.
 */
class CRM_Core_BAO_PaymentProcessor extends CRM_Core_DAO_PaymentProcessor {

  /**
   * static holder for the default payment processor
   */
  static $_defaultPaymentProcessor = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_PaymentProcessor object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $paymentProcessor = new CRM_Core_DAO_PaymentProcessor();
    $paymentProcessor->copyValues($params);
    if ($paymentProcessor->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessor, $defaults);
      return $paymentProcessor;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_PaymentProcessor', $id, 'is_active', $is_active);
  }

  /**
   * retrieve the default payment processor
   *
   * @param NULL
   *
   * @return object           The default payment processor object on success,
   *                          null otherwise
   * @static
   * @access public
   */
  static function &getDefault() {
    if (self::$_defaultPaymentProcessor == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultPaymentProcessor = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessor;
  }

  /**
   * Function  to delete payment processor
   *
   * @param  int  $paymentProcessorId     ID of the processor to be deleted.
   *
   * @access public
   * @static
   */
  static function del($paymentProcessorID) {
    if (!$paymentProcessorID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to delete function'));
    }

    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->id = $paymentProcessorID;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    $testDAO = new CRM_Core_DAO_PaymentProcessor();
    $testDAO->name = $dao->name;
    $testDAO->is_test = 1;
    $testDAO->delete();

    $dao->delete();
  }

  /**
   * Function to get the payment processor details
   *
   * @param  int    $paymentProcessorID payment processor id
   * @param  string $mode               payment mode ie test or live
   *
   * @return array  associated array with payment processor related fields
   * @static
   * @access public
   */
  static function getPayment($paymentProcessorID, $mode) {
    if (!$paymentProcessorID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to getPayment function'));
    }

    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->id = $paymentProcessorID;
    $dao->is_active = 1;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    if ($mode == 'test') {
      $testDAO = new CRM_Core_DAO_PaymentProcessor();
      $testDAO->name = $dao->name;
      $testDAO->is_active = 1;
      $testDAO->is_test = 1;
      if (!$testDAO->find(TRUE)) {
        CRM_Core_Error::fatal(ts('Could not retrieve payment processor details'));
      }
      return self::buildPayment($testDAO);
    }
    else {
      return self::buildPayment($dao);
    }
  }


  static function getPayments($paymentProcessorIDs, $mode) {
    if (!$paymentProcessorIDs) {
      CRM_Core_Error::fatal(ts('Invalid value passed to getPayment function'));
    }
    $paymentDefault = $paymentDAO = [];
    foreach ($paymentProcessorIDs as $paymentProcessorID) {
      $dao = new CRM_Core_DAO_PaymentProcessor();
      $dao->id = $paymentProcessorID;
      $dao->is_active = 1;
      if (!$dao->find(TRUE)) {
        continue;
      }

      if ($mode == 'test') {
        $testDAO = new CRM_Core_DAO_PaymentProcessor();
        $testDAO->name = $dao->name;
        $testDAO->is_active = 1;
        $testDAO->is_test = 1;
        if (!$testDAO->find(TRUE)) {
          CRM_Core_Error::fatal(ts('Could not retrieve payment processor details'));
        }
        if($testDAO->is_default){
          $paymentDefault[$testDAO->id] = self::buildPayment($testDAO);
        }
        else{
          $paymentDAO[$testDAO->id] = self::buildPayment($testDAO);
        }
      }
      else {
        if($dao->is_default){
          $paymentDefault[$dao->id] = self::buildPayment($dao);
        }
        else{
          $paymentDAO[$dao->id] = self::buildPayment($dao);
        }
      }
    }
    $paymentDAO = $paymentDefault + $paymentDAO;
    if (empty($paymentDAO)) {
      return NULL;
    }
    return $paymentDAO;
  }

  static function getPaymentsByType($processorType, $mode) {
    if (!$processorType) {
      return [];
    }

    $paymentDAO = [];

    // Query to get all processors with the specified type (case insensitive)
    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->whereAdd("LOWER(payment_processor_type) = '" . strtolower($processorType) . "'");
    $dao->is_active = 1;
    $dao->find();

    while ($dao->fetch()) {
      if ($mode == 'test' && $dao->is_test) {
        $paymentDAO[$dao->id] = self::buildPayment($dao);
      }
      elseif($mode != 'test' && !$dao->is_test) {
        $paymentDAO[$dao->id] = self::buildPayment($dao);
      }
    }

    // Return default processors first, then others
    $paymentDAO = $paymentDAO;
    return $paymentDAO;
  }

  /**
   * Function to build payment processor details
   *
   * @param object $dao payment processor object
   *
   * @return array  associated array with payment processor related fields
   * @static
   * @access public
   */
  static function buildPayment($dao) {
    $fields = [
      'id', 'name', 'description', 'payment_processor_type', 'user_name', 'password',
      'signature', 'url_site', 'url_api', 'url_recur', 'url_button',
      'subject', 'class_name', 'is_recur', 'is_test', 'billing_mode',
      'payment_type', 'is_default',
    ];
    $result = [];

    // allow class to pass default settings of payment processor
    if ($dao->class_name && strpos($dao->class_name, 'Payment_') === 0) {
      $class = 'CRM_Core_'.$dao->class_name;
      if (method_exists($class, 'buildPaymentDefault')) {
        call_user_func_array([$class, 'buildPaymentDefault'], [&$result, $dao]);
      }
    }
    foreach ($fields as $name) {
      if (!empty($dao->$name)) {
        $result[$name] = $dao->$name;
      }
      else {
        if (empty($result[$name])) {
          $result[$name] = '';
        }
      }
    }
    return $result;
  }
}

