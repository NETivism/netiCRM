<?php

/**
 * This is an evil, evil work-around for CRM-11043. It is used to
 * temporarily change the error-handling behavior and then automatically
 * restore it -- that protocol is an improvement over the current protocol
 * (in which random bits of code will change the global error handler
 * setting and then forget to change it back).  This class and all
 * references to it should be removed in 4.3/4.4 (when we adopt
 * exception-based error handling).
 *
 * To ensure that new errors arising during execution of the current
 * function are immediately fatal, use:
 *
 * To ensure that they throw exceptions, use:
 *
 * @code
 * $errorScope = CRM_Core_TemporaryErrorScope::useException();
 * @endcode
 *
 * Note that relying on this is a code-smell: it can be
 * safe to temporarily switch to exception
 */
class CRM_Core_TemporaryErrorScope {
  public static $oldFrames;

  /**
   * Temporarily switch to exception-based error handling.
   *
   * @return CRM_Core_TemporaryErrorScope
   */
  public static function useException() {
    $newFrame = [
      '_PEAR_default_error_mode' => PEAR_ERROR_CALLBACK,
      '_PEAR_default_error_options' => ['CRM_Core_Error', 'exceptionHandler'],
      'modeException' => 1,
    ];
    return new CRM_Core_TemporaryErrorScope($newFrame);
  }

  /**
   * Class constructor.
   *
   * @param array $newFrame The new error handling settings.
   */
  public function __construct($newFrame) {
    self::$oldFrames[] = self::getActive();
    self::setActive($newFrame);
  }

  /**
   * Class destructor. Restores previous error handling settings.
   */
  public function __destruct() {
    $oldFrame = array_pop(self::$oldFrames);
    self::setActive($oldFrame);
  }

  /**
   * Read the active error-handler settings.
   *
   * @return array
   */
  public static function getActive() {
    return [
      '_PEAR_default_error_mode' => $GLOBALS['_PEAR_default_error_mode'],
      '_PEAR_default_error_options' => $GLOBALS['_PEAR_default_error_options'],
      'modeException' => CRM_Core_Error::$modeException,
    ];
  }

  /**
   * Set the active error-handler settings.
   *
   * @param array $frame The error handling settings to apply.
   */
  public static function setActive($frame) {
    $GLOBALS['_PEAR_default_error_mode'] = $frame['_PEAR_default_error_mode'];
    $GLOBALS['_PEAR_default_error_options'] = $frame['_PEAR_default_error_options'];
    CRM_Core_Error::$modeException = $frame['modeException'];
  }
}
