<?php

require_once __DIR__.'/../../functions.php';
class CRM_Core_ClassLoader {
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;
  private static $_composer_classmap = [];
  private static $_include_paths = [];

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
    global $civicrm_root;

    $this->_registered = FALSE;
    if(isset($_ENV['CIVICRM_COMPOSER_DIR'])){
      $composer_classmap = $_ENV['CIVICRM_COMPOSER_DIR'] . '/vendor/composer/autoload_classmap.php';
    }
    elseif(defined('CIVICRM_COMPOSER_DIR')){
      $composer_classmap = CIVICRM_COMPOSER_DIR . '/vendor/composer/autoload_classmap.php';
    }
    else{
      $civicrm_path = rtrim($civicrm_root, '/') .  DIRECTORY_SEPARATOR;
      $composer_classmap = $civicrm_path. '/vendor/composer/autoload_classmap.php';
    }
    if(file_exists($composer_classmap)){
      $this->_composer_classmap = include_once($composer_classmap);
    }
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
    spl_autoload_register([$this, 'loadClass'], TRUE, $prepend);
    $this->_registered = TRUE;

  }

  function loadClass($class) {
    self::$_include_paths = explode(PATH_SEPARATOR, get_include_path());
    if ( FALSE === strpos($class, '\\') ) {
      if(isset($this->_composer_classmap[$class])){
        require $this->_composer_classmap[$class];
        return;
      }
      $file = strtr($class, '_', '/') . '.php';
      foreach (self::$_include_paths as $base_dir) {
        $filename = rtrim($base_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($file, DIRECTORY_SEPARATOR);
        if (file_exists($filename) ){
          require $filename;
          return;
        }
      }
    }
  }
}

