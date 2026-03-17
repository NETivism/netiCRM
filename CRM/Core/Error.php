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
 * Start of the Error framework. We should check out and inherit from
 * PEAR_ErrorStack and use that framework
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Exception extends PEAR_Exception {
  // Redefine the exception so message isn't optional
  public function __construct($message = NULL, $code = 0, ?Exception$previous = NULL) {
    parent::__construct($message, $code, $previous);
  }
}

class CRM_Core_Error extends PEAR_ErrorStack {

  /**
   * status code of various types of errors, use http status code(200~5xx) to indicate http status code response
   * use others to indicate other error
   * @var const
   */
  public const NO_ERROR = 200, FATAL_ERROR = 500, DATABASE_ERROR = 500, STATUS_BOUNCE = 303, DUPLICATE_CONTACT = 8001, DUPLICATE_CONTRIBUTION = 8002, DUPLICATE_PARTICIPANT = 8003;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * If modeException == true, errors are raised as exception instead of returning civicrm_errors
   * @static
   */
  public static $modeException = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * This function is not explicitly declared static to be compatible with PEAR_ErrorStack.
   *
   * @param string|null $package
   * @param callable|bool $msgCallback
   * @param callable|bool $contextCallback
   * @param bool $throwPEAR_Error
   * @param string $stackClass
   *
   * @return CRM_Core_Error
   */
  public static function &singleton($package = NULL, $msgCallback = FALSE, $contextCallback = FALSE, $throwPEAR_Error = FALSE, $stackClass = 'PEAR_ErrorStack') {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Error('CiviCRM');
    }
    return self::$_singleton;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('CiviCRM');

    $log = CRM_Core_Config::getLog();
    $this->setLogger($log);

    // PEAR<=1.9.0 does not declare "static" properly.
    if (!is_callable(['PEAR', '__callStatic'])) {
      $this->setDefaultCallback([$this, 'handlePES']);
    }
    else {
      PEAR_ErrorStack::setDefaultCallback([$this, 'handlePES']);
    }
  }

  /**
   * Get error messages from the error stack.
   *
   * @param CRM_Core_Error|PEAR_ErrorStack $error The error object.
   * @param string $separator The separator between messages.
   *
   * @return string|null The concatenated error messages.
   */
  public static function getMessages(&$error, $separator = '<br />') {
    if (is_a($error, 'CRM_Core_Error')) {
      $errors = $error->getErrors();
      $message = [];
      foreach ($errors as $e) {
        $message[] = $e['code'] . ':' . $e['message'];
      }
      $message = CRM_Utils_Array::implode($separator, $message);
      return $message;
    }
    return NULL;
  }

  /**
   * Display a session error message, typically for payment failures.
   *
   * @param CRM_Core_Error|PEAR_ErrorStack $error The error object.
   * @param string $separator The separator for detail messages.
   */
  public static function displaySessionError(&$error, $separator = '<br />') {
    $message = ts('Payment failed.').' '.ts('We were unable to process your payment. You will not be charged in this transaction.');
    $detail = self::getMessages($error, $separator);
    if (!empty($detail)) {
      $message .= $separator.ts("Payment Processor Error message") . ": $detail";
    }
    else {
      $message .= ' '.ts("Network or system error. Please try again a minutes later, if you still can't success, please contact us for further assistance.");
    }
    CRM_Core_Error::debug_var('payment_processor_failed', $message);
    CRM_Core_Error::backtrace('backtrace', TRUE);
    CRM_Core_Session::setStatus($message, TRUE, 'error');
  }

  /**
   * Main callback method for centralized error processing.
   *
   * Handles errors from PEAR modules like DB and DB_DataObject.
   *
   * @param PEAR_Error $pearError The error object to process.
   *
   * @throws CRM_Core_Exception
   */
  public static function handle($pearError) {
    $config = CRM_Core_Config::singleton();

    $error = self::getErrorDetails($pearError);

    $backtrace = CRM_Core_Error::backtrace('backtrace', FALSE);
    CRM_Core_Error::debug_var('db_error', $error);
    CRM_Core_Error::debug_var('backtrace', $backtrace);

    if (!$config->initialized) {
      // No config yet — cannot render template
      echo "Sorry. A non-recoverable error has occurred.";
      CRM_Utils_System::civiExit(1);
    }

    $vars = [
      'type' => 'data-error',
      'message' => NULL,
      'suppress' => FALSE,
    ];
    $content = self::output($config->fatalErrorTemplate, $vars);

    self::abend();
    throw new CRM_Core_Exception(
      'We experienced an unexpected database error.',
      CRM_Core_Error::DATABASE_ERROR,
      [
        'content' => $content,
        'object' => $pearError,
      ]
    );
  }

  /**
   * Handle errors raised using the PEAR Error Stack.
   *
   * @param array $pearError The error details.
   *
   * @return int PEAR_ERRORSTACK_PUSH
   */
  public static function handlePES($pearError) {
    return PEAR_ERRORSTACK_PUSH;
  }

  /**
   * Display an error page with an error message describing what happened.
   *
   * @param string|null $message The error message.
   * @param string|null $status The HTTP status error code if any, default is 500.
   * @param string|bool|null $suppress Suppress error message with given string or boolean.
   *
   * @throws CRM_Core_Exception
   */
  public static function fatal($message = NULL, $status = NULL, $suppress = NULL) {
    $config = CRM_Core_Config::singleton();
    $vars = [];
    if ($config->fatalErrorHandler && class_exists($config->fatalErrorHandler)) {
      $class = $config->fatalErrorHandler;
      // the call has been successfully handled
      self::abend();
      throw new $class($message, CRM_Core_Error::FATAL_ERROR);
      return;
    }

    CRM_Core_Error::debug_var('fatal_error', $message);
    CRM_Core_Error::backtrace('backtrace', TRUE);
    $vars['type'] = 'internal-error';
    $vars['message'] = $message;
    if ($suppress) {
      $vars['suppress'] = $suppress;
    }
    else {
      $vars['suppress'] = FALSE;
    }

    $content = self::output($config->fatalErrorTemplate, $vars);
    self::abend();
    throw new CRM_Core_Exception($message, CRM_Core_Error::FATAL_ERROR, ['content' => $content]);
  }

  /**
   * Handles fatal errors without requiring an initialized configuration object.
   *
   * This function is useful in cases where using the regular fatal() function
   * would cause a circular dependency.
   *
   * @param string|null $message The error message to be displayed.
   */
  public static function fatalWithoutInitialized($message = NULL) {
    http_response_code(self::FATAL_ERROR);
    echo $message;
    exit;
  }

  /**
   * Display a timeout message.
   *
   * @param string $message The message to display.
   */
  public static function timeout($message) {
    $vars = [
      'message' => $message,
    ];
    self::output('CRM/common/timeout.tpl', $vars);
    CRM_Utils_System::civiExit();
  }

  /**
   * Outputs pre-formatted debug information.
   *
   * Flushes the buffers so we can interrupt a potential POST/redirect.
   *
   * @param string $name Name of debug section.
   * @param mixed $variable Variable to be traced.
   * @param bool $print Whether to echo the output.
   * @param bool $html Whether to generate a HTML-escaped output.
   *
   * @return string The generated output.
   */
  public static function debug($name, $variable = NULL, $print = FALSE, $html = TRUE) {
    $error = &self::singleton();
    $out = self::debug_var($name, $variable);

    if ($variable === NULL) {
      $variable = $name;
      $name = NULL;
    }

    $prefix = NULL;
    if ($html) {
      $out = htmlspecialchars($out);
      if ($name) {
        $prefix = "<p>$name</p>";
      }
      $out = "{$prefix}<p><pre>$out</pre></p><p></p>";
    }
    else {
      if ($name) {
        $prefix = "$name:\n";
      }
      $out = "{$prefix}$out\n";
    }
    if ($print) {
      echo $out;
    }

    return $out;
  }

  /**
   * Debug a variable and log the output.
   *
   * @param string $variable_name The name of the variable.
   * @param mixed $variable The variable to be traced.
   * @param bool $print Whether to use print_r instead of var_dump.
   * @param bool $log Whether to log the output.
   * @param string $comp Component name for log file suffix.
   */
  public static function debug_var(
    $variable_name,
    $variable = NULL,
    $print = FALSE,
    $log = TRUE,
    $comp = ''
  ) {
    // check if variable is set
    if ($variable === NULL) {
      $out = (string) $variable_name;
    }
    else {
      if ($print) {
        $out = print_r($variable, TRUE);
        $out = "\$$variable_name = $out";
      }
      else {
        // use var_dump
        ob_start();
        var_dump($variable);
        $dump = ob_get_contents();
        ob_end_clean();
        $out = "\$$variable_name = ".str_replace("=>\n", "=>", $dump);
      }
      // reset if it is an array
      if (is_array($variable)) {
        reset($variable);
      }
    }
    if ($log) {
      self::debug_log_message($out, FALSE, $comp);
    }
    return;
  }

  /**
   * Display the error message on terminal or log file.
   *
   * @param string $message Message to be output.
   * @param bool $out Whether to echo the message.
   * @param string $comp Component name for log file suffix.
   */
  public static function debug_log_message($message, $out = FALSE, $comp = '') {
    $config = CRM_Core_Config::singleton();

    if ($comp) {
      $comp = $comp . '.';
    }

    $fileName = "{$config->configAndLogDir}CiviCRM." . $comp . md5($config->dsn . $config->userFrameworkResourceURL) . '.log';

    // Roll log file monthly or if greater than 256M
    // note that PHP file functions have a limit of 2G and hence
    // the alternative was introduce :)
    if (file_exists($fileName)) {
      $fileTime = date("Ym", filemtime($fileName));
      $fileSize = filesize($fileName);
      if (($fileTime < date('Ym')) ||
        ($fileSize > 256 * 1024 * 1024) ||
        ($fileSize < 0)
      ) {
        rename($fileName, $fileName . '.' . date('Ymdhi', mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
      }
    }

    $file_log = Log::singleton('file', $fileName, CRM_Utils_System::ipAddress(), ['timeFormat' => '%Y-%m-%dT%H:%M:%S%z']);
    $file_log->log($message);
    if ($out) {
      echo $message;
    }
    $file_log->close();
  }

  /**
   * Generate a backtrace message and optionally log it.
   *
   * @param string $msg Optional header for the backtrace.
   * @param bool $log Whether to log the backtrace.
   *
   * @return string The backtrace message.
   */
  public static function backtrace($msg = 'backtrace', $log = TRUE) {
    $backTrace = debug_backtrace();

    $msgs = [];
    foreach ($backTrace as $trace) {
      $msgs[] = CRM_Utils_Array::implode(
        ', ',
        [CRM_Utils_Array::value('file', $trace),
          CRM_Utils_Array::value('function', $trace),
          CRM_Utils_Array::value('line', $trace),
        ]
      );
    }

    $message = "\n".CRM_Utils_Array::implode("\n", $msgs);
    if ($log) {
      CRM_Core_Error::debug_var($msg, $message, FALSE, TRUE);
    }
    return $message;
  }

  /**
   * Push an error onto the stack.
   *
   * @param string $message The error message.
   * @param int $code The error code.
   * @param string $level The error level (e.g., 'Fatal').
   * @param mixed $params Extra parameters for the error.
   *
   * @return CRM_Core_Error The error stack object.
   */
  public static function createError($message, $code = 8000, $level = 'Fatal', $params = NULL) {
    $error = &CRM_Core_Error::singleton();
    $error->push($code, $level, [$params], $message);
    return $error;
  }

  /**
   * Set a status message in the session, then bounce back to the referrer or a specific URL.
   *
   * @param string $message The status message to set.
   * @param string|bool|null $redirect The URL to redirect to. If false, no redirect occurs.
   *
   * @throws CRM_Core_Exception
   */
  public static function statusBounce($message, $redirect = NULL) {
    $session = CRM_Core_Session::singleton();
    $session->setStatus($message, $append = TRUE, 'warning');
    if ($redirect !== FALSE) {
      if (!$redirect) {
        $redirect = $session->readUserContext();
        if (!$redirect) {
          $redirect = CRM_Utils_System::url(); // front page
        }
      }
    }
    throw new CRM_Core_Exception($message, self::STATUS_BOUNCE, [
      'redirect' => $redirect
    ]);
    // shouldn't goes here
  }

  /**
   * Function to reset the error stack.
   */
  public static function reset() {
    $error = &self::singleton();
    $error->_errors = [];
    $error->_errorsByLevel = [];
  }

  /**
   * Set the PEAR error mode to ignore exceptions.
   *
   * @param callable|null $callback The callback handler.
   */
  public static function ignoreException($callback = NULL) {
    if (!$callback) {
      $callback = ['CRM_Core_Error', 'nullHandler'];
    }

    $GLOBALS['_PEAR_default_error_mode'] = PEAR_ERROR_CALLBACK;
    $GLOBALS['_PEAR_default_error_options'] = $callback;
  }

  /**
   * Generic exception handler.
   *
   * @param PEAR_Error $pearError
   *
   * @throws PEAR_Exception
   */
  public static function exceptionHandler($pearError) {
    CRM_Core_Error::debug_var('fatal_error', self::getErrorDetails($pearError));
    CRM_Core_Error::backtrace('backtrace', TRUE);
    throw new PEAR_Exception($pearError->getMessage(), $pearError);
  }

  /**
   * Get details from a PEAR_Error object.
   *
   * @param PEAR_Error $pearError The error object.
   *
   * @return array<string, mixed> The error details.
   */
  public static function getErrorDetails($pearError) {
    // create the error array
    $error = [];
    $error['callback'] = $pearError->getCallback();
    $error['code'] = $pearError->getCode();
    $error['message'] = $pearError->getMessage();
    $error['mode'] = $pearError->getMode();
    $error['type'] = $pearError->getType();
    $error['user_info'] = $pearError->getUserInfo();

    return $error;
  }

  /**
   * Error handler to quietly catch errors.
   *
   * @param mixed $obj The error object or message.
   *
   * @return mixed The input object.
   */
  public static function nullHandler($obj) {
    if (is_a($obj, 'PEAR_Error')) {
      $message = $obj->getMessage();
      self::debug_var('null_error', $message);
    }
    elseif (is_string($obj)) {
      self::debug_var('null_error', $obj);
    }
    else {
      self::debug_var('null_error', 'Triggered nullHandler here, unknown error');
    }
    self::backtrace('backtrace', TRUE);
    return $obj;
  }

  /**
   * (Re)set the default callback method for PEAR errors.
   *
   * @param callable|null $callback
   */
  public static function setCallback($callback = NULL) {
    if (!$callback) {
      $callback = ['CRM_Core_Error', 'handle'];
    }
    $GLOBALS['_PEAR_default_error_mode'] = PEAR_ERROR_CALLBACK;
    $GLOBALS['_PEAR_default_error_options'] = $callback;
  }

  /**
   * Create an error array for CiviCRM API v3.
   *
   * @param string $msg The error message.
   * @param mixed $data Extra error data.
   *
   * @return array|never The error array or throws an exception if modeException is set.
   */
  public static function &createAPIError($msg, $data = NULL) {
    if (self::$modeException) {
      throw new Exception($msg, $data);
    }

    $values = [];

    $values['is_error'] = 1;
    $values['error_message'] = $msg;
    if (isset($data) && is_array($data)) {
      $values = array_merge($values, $data);
    }
    elseif (is_string($data)) {
      $values['error_data'] = $data;
    }
    return $values;
  }

  /**
   * Create a success array for CiviCRM API v3.
   *
   * @param mixed $result The result data.
   *
   * @return array<string, mixed> The success array.
   */
  public static function &createAPISuccess($result = 1) {
    $values = [];

    $values['is_error'] = 0;
    $values['result'] = $result;
    return $values;
  }

  /**
   * Display an error when the site directory or server has moved.
   *
   * @param string $file The file path that could not be written.
   */
  public static function movedSiteError($file) {
    $url = CRM_Utils_System::url(
      'civicrm/admin/setting/updateConfigBackend',
      'reset=1',
      TRUE
    );
    echo "We could not write $file. Have you moved your site directory or server?<p>";
    echo "Please fix the setting by running the <a href=\"$url\">update config script</a>";
    exit();
  }

  /**
   * Terminate execution abnormally and force rollback if transaction is active.
   */
  protected static function abend() {
    CRM_Core_Transaction::forceRollbackIfEnabled();
  }

  /**
   * Generate output of fatal template.
   *
   * @param string $tplFile The template file to use.
   * @param array $vars Template variables.
   *
   * @return string The rendered template content or JSON string.
   */
  protected static function output($tplFile, $vars) {
    $template = CRM_Core_Smarty::singleton();
    $template->assign('tplFile', $tplFile);
    $template->assign($vars);
    if (isset($_GET['snippet']) && $_GET['snippet']) {
      if ($_GET['snippet'] == CRM_Core_Smarty::PRINT_SNIPPET ||
        $_GET['snippet'] == CRM_Core_Smarty::PRINT_NOFORM) {
        $content = $vars['message'];
        $json = [
          'status' => '-1',
          'error' => "$content",
        ];
        return json_encode($json);
      }
      else {
        return $template->fetch('CRM/common/print.tpl');
      }
    }
    else {
      $config = CRM_Core_Config::singleton();
      $tplCommon = 'CRM/common/' . strtolower($config->userFramework) . '.tpl';
      return $template->fetch($tplCommon);
    }
    return '';
  }

  /**
   * Purge old log files.
   */
  public static function purge() {
    $config = CRM_Core_Config::singleton();
    $dir1 = $config->configAndLogDir;
    $dir2 = str_replace("smartycli", "smartyfpm-fcgi", $dir1);
    foreach ([$dir1, $dir2] as $dir) {
      $filename = "{$dir}CiviCRM.*.log";
      $files = glob($filename.'*');
      if (!empty($files)) {
        foreach ($files as $f) {
          if ($f != $filename && filemtime($f) < strtotime('-5 month')) {
            unlink($f);
          }
        }
      }
    }
  }

  /**
   * Output database profiling information if enabled.
   */
  public static function debugDatabaseProfiling() {
    if (CRM_Core_Config::singleton()->debugDatabaseProfiling) {
      $profiles = CRM_Core_DAO::getProfiles();
      if (!empty($profiles)) {
        $smarty = CRM_Core_Smarty::singleton();
        $smarty->assign('query_profiling', $profiles);
        $debug = "This page exit by debug purpose at ".__CLASS__.'::'.__FUNCTION__.' line '.__LINE__.'<br>';
        $debug = $smarty->fetch('CRM/common/queryProfiling.tpl');
        self::debug('Database Profiling', $debug);
      }
    }
  }
}

PEAR_ErrorStack::singleton('CRM', FALSE, NULL, 'CRM_Core_Error');
