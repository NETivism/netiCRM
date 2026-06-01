<?php
/*
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Ideal Solution, LLC (http://www.idealso.com)
 *
 */

/**
 * Dummy payment processor implementation for testing and development
 *
 * @author Marshal Newrock <marshal@idealso.com>
 * $Id: Dummy.php 30063 2010-10-06 10:33:02Z ashwini $
 * @package CiviCRM_PaymentProcessor
 */

/* NOTE:
 * When looking up response codes in the Authorize.Net API, they
 * begin at one, so always delete one from the "Position in Response"
 */

class CRM_Core_Payment_Dummy extends CRM_Core_Payment {
  /**
   * @var mixed
   */
  public $_processorName;
  public const CHARSET = 'iso-8859-1';

  protected static $_mode = NULL;

  protected static $_params = [];

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * Class constructor.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Dummy Processor');
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   * @param CRM_Core_Form|null &$paymentForm payment form object
   *
   * @return CRM_Core_Payment_Dummy
   */
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Dummy($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Submit a payment using Advanced Integration Method (AIM).
   *
   * @param array &$params associative array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array
   */
  public function doDirectPayment(&$params) {
    // Invoke hook_civicrm_paymentProcessor
    // In Dummy's case, there is no translation of parameters into
    // the back-end's canonical set of parameters.  But if a processor
    // does this, it needs to invoke this hook after it has done translation,
    // but before it actually starts talking to its proprietary back-end.

    // no translation in Dummy processor
    $cookedParams = $params;
    CRM_Utils_Hook::alterPaymentProcessorParams(
      $this,
      $params,
      $cookedParams
    );
    //end of hook invokation

    if ($this->_mode == 'test') {
      $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'test\\_%'";
      $p = [];
      $trxn_id = strval(CRM_Core_DAO::singleValueQuery($query, $p));
      $trxn_id = str_replace('test_', '', $trxn_id);
      $trxn_id = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('test_%08d', $trxn_id);
    }
    else {
      $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'live_%'";
      $p = [];
      $trxn_id = strval(CRM_Core_DAO::singleValueQuery($query, $p));
      $trxn_id = str_replace('live_', '', $trxn_id);
      $trxn_id = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('live_%08d', $trxn_id);
    }
    $params['gross_amount'] = $params['amount'];
    return $params;
  }

  /**
   * Push an error to the error object.
   *
   * @param int|null $errorCode error code
   * @param string|null $errorMessage error message
   *
   * @return CRM_Core_Error error object
   */
  public function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = &CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9001, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * Check if the processor has the right configuration values.
   *
   * @return string|null error message if any, else NULL
   */
  public function checkConfig() {
    return NULL;
  }
}
