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
class CRM_Utils_ReCAPTCHA {

  protected $_captcha = NULL;

  protected $_name = NULL;

  protected $_url = NULL;

  protected $_phrase = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * singleton function used to manage this object
   *
   * @param string the key to permit session scope's
   *
   * @return object
   * @static
   *
   */
  static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_ReCAPTCHA();
    }
    return self::$_singleton;
  }

  function __construct() {
    $require_path = 'packages/recaptcha/src/ReCaptcha';
    if(function_exists('module_exists')){
      if(module_exists('recaptcha')){
        $possible_path = drupal_get_path('module', 'recaptcha').'/recaptcha-php/src/ReCaptcha';
        if(is_file($possible_path.'/ReCaptcha.php')){
          $require_path = $possible_path;
        }
      }
    }
    require_once $require_path . '/ReCaptcha.php';
    require_once $require_path . '/RequestMethod.php';
    require_once $require_path . '/RequestParameters.php';
    require_once $require_path . '/Response.php';
    require_once $require_path . '/RequestMethod/Post.php';
  }

  /**
   * Add element to form
   *
   */
  function add(&$form) {
    $config = CRM_Core_Config::singleton();
    $html = self::getHTML($config->recaptchaPublicKey);

    $form->assign('recaptchaHTML', $html);
    $form->add(
      'textarea',
      'g-recaptcha-response',
      'ReCaptcha',
      NULL,
      TRUE
    );
    $form->registerRule('recaptcha', 'callback', 'validate', 'CRM_Utils_ReCAPTCHA');
    $form->addRule(
      'g-recaptcha-response',
      ts('Input text must match the phrase in the image. Please review the image and re-enter matching text.'),
      'recaptcha',
      $form
    );
  }

  static function validate($value, $form) {
    $config = CRM_Core_Config::singleton();

    $resp = self::checkAnswer($config->recaptchaPrivateKey, $_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if ($resp->isSuccess()) {
      return TRUE;
    }
    return FALSE; 
  }

  static function getHTML($pubkey){
    $output = '<div class="g-recaptcha" data-sitekey="'.$pubkey.'"></div>'; 
    $output .= '<script src="//www.google.com/recaptcha/api.js"></script>';
    return $output;
  }

  static function checkAnswer($key, $response, $ip){
    $recaptcha = new \ReCaptcha\ReCaptcha($key);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    return $resp;
  }

}

