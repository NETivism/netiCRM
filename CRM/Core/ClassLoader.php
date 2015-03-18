<?php
class CRM_Core_ClassLoader {
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * @param bool $force
   *
   * @return object
   */
  static function &singleton($force = FALSE) {
    if ($force || self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_ClassLoader();
    }
    return self::$_singleton;
  }

  /**
   * @var bool TRUE if previously registered
   */
  protected $_registered;

  /**
   * Initializer
   */
  function __construct() {
    $this->_registered = FALSE;
  }

  /**
   * Registers this instance as an autoloader.
   *
   * @param Boolean $prepend Whether to prepend the autoloader or not
   *
   * @api
   */
  function register($prepend = FALSE) {
    if ($this->_registered) {
      return;
    }
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      spl_autoload_register(array($this, 'loadClass'), TRUE, $prepend);
    }
    else {
      // http://www.php.net/manual/de/function.spl-autoload-register.php#107362
      // "when specifying the third parameter (prepend), the function will fail badly in PHP 5.2"
      spl_autoload_register(array($this, 'loadClass'), TRUE);
    }
    $this->_registered = TRUE;

  }

  function loadClass($class) {
    if ( FALSE === strpos($class, '\\') ) {
      $file_by_ver = strtr($class, '_', '/') . '.'.PHP_MAJOR_VERSION.'_'.PHP_MINOR_VERSION.'.php';
      $file = strtr($class, '_', '/') . '.php';
      $include_paths = explode(PATH_SEPARATOR, get_include_path());
      foreach ($include_paths as $base_dir) {
        $file_by_ver = $base_dir.DIRECTORY_SEPARATOR.$file_by_ver;
        $file = $base_dir.DIRECTORY_SEPARATOR.$file;
        if (file_exists($file_by_ver) ){
          require $file_by_ver;
          return;
        }
        elseif (file_exists($file) ){
          require $file;
          return;
        }
      }
    }
  }
}

