<?php
/**
 * Simple in-memory array-based cache implementation for use within a single request
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Utils_Cache_Arraycache implements CRM_Utils_Cache_Interface {

  /**
   * The cache storage container, an in memory array by default
   */
  private $_cache;

  /**
   * Constructor
   *
   * @param array   $config  an array of configuration params
   */
  public function __construct($config) {
    $this->_cache = [];
  }

  public function set($key, &$value) {
    $this->_cache[$key] = $value;
  }

  public function get($key) {
    return CRM_Utils_Array::value($key, $this->_cache);
  }

  public function delete($key) {
    unset($this->_cache[$key]);
  }

  public function flush() {
    unset($this->_cache);
    $this->_cache = [];
  }
}
