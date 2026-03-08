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
 * Google reCAPTCHA integration for CiviCRM forms.
 *
 * Provides singleton-based reCAPTCHA v2 widget rendering and server-side
 * response validation using the Google reCAPTCHA PHP library.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */
class CRM_Utils_ReCAPTCHA {

  /**
   * @var mixed
   */
  protected $_captcha = NULL;

  /**
   * @var string|null
   */
  protected $_name = NULL;

  /**
   * @var string|null
   */
  protected $_url = NULL;

  /**
   * @var string|null
   */
  protected $_phrase = NULL;

  /**
   * Singleton instance of this class.
   *
   * @var CRM_Utils_ReCAPTCHA|null
   */
  private static $_singleton = NULL;

  /**
   * Return the singleton instance of this class.
   *
   * @return CRM_Utils_ReCAPTCHA
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_ReCAPTCHA();
    }
    return self::$_singleton;
  }

  /**
   * Constructor. Loads the Google reCAPTCHA PHP library.
   */
  public function __construct() {
    $require_path = 'packages/recaptcha/src/ReCaptcha';
    $config = CRM_Core_Config::singleton();
    if (CRM_Utils_System::moduleExists('recaptcha') && $config->userFramework == 'Drupal') {
      if ($config->userSystem->version < 8) {
        $possible_path = drupal_get_path('module', 'recaptcha').'/recaptcha-php/src/ReCaptcha';
        if (is_file($possible_path.'/ReCaptcha.php')) {
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
   * Add a reCAPTCHA widget and validation rule to a form.
   *
   * @param CRM_Core_Form $form the form object to add the reCAPTCHA element to
   *
   * @return void
   */
  public function add(&$form) {
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

  /**
   * Validate the reCAPTCHA response from the user.
   *
   * Caches the result of a successful validation to avoid duplicate
   * verification on repeated form submissions.
   *
   * @param string $value the reCAPTCHA response value from the form
   * @param CRM_Core_Form $form the form object being validated
   *
   * @return bool TRUE if validation passes, FALSE otherwise
   */
  public static function validate($value, $form) {
    // refs #35022 when recaptcha has twice,recored the previous result.
    static $previousResult;
    $config = CRM_Core_Config::singleton();

    $resp = self::checkAnswer($config->recaptchaPrivateKey, $_POST['g-recaptcha-response'], self::getIp());
    // refs #17773, when submit twice, we will get false but no error codes
    $errors = $resp->getErrorCodes();
    if ($previousResult) {
      return TRUE;
    }
    if ($resp->isSuccess()) {
      $previousResult = TRUE;
      return TRUE;
    }
    elseif (empty($errors)) {
      $previousResult = TRUE;
      return TRUE;
    }
    $previousResult = FALSE;
    return FALSE;
  }

  /**
   * Generate the HTML markup for the reCAPTCHA widget.
   *
   * @param string $pubkey the reCAPTCHA site (public) key
   *
   * @return string HTML containing the reCAPTCHA div and script tag
   */
  public static function getHTML($pubkey) {
    $output = '<div class="g-recaptcha" data-sitekey="'.$pubkey.'"></div>';
    $output .= '<script src="//www.google.com/recaptcha/api.js"></script>';
    return $output;
  }

  /**
   * Verify the reCAPTCHA response with Google's API.
   *
   * @param string $key the reCAPTCHA secret (private) key
   * @param string $response the user's reCAPTCHA response token
   * @param string $ip the client IP address
   *
   * @return \ReCaptcha\Response the verification response object
   */
  public static function checkAnswer($key, $response, $ip) {
    $recaptcha = new \ReCaptcha\ReCaptcha($key);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $ip);
    return $resp;
  }

  /**
   * Get the client's IP address.
   *
   * @return string the client IP address
   */
  public static function getIp() {
    return CRM_Utils_System::ipAddress();
  }
}
