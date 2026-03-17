<?php
/**
 * Factory class for creating Contact DAO and BAO instances based on class name mappings
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Contact_DAO_Factory {

  public static $_classes = [
    'Address' => 'data',
    'Contact' => 'data',
    'Email' => 'data',
    'Household' => 'data',
    'IM' => 'data',
    'Individual' => 'data',
    'Location' => 'data',
    'LocationType' => 'data',
    'Organization' => 'data',
    'Phone' => 'data',
    'Relationship' => 'data',
  ];

  public static $_prefix = [
    'business' => 'CRM/Contact/BAO/',
    'data' => 'CRM/Contact/DAO/',
  ];

  public static $_suffix = '.php';

  public static $_preCall = [
    'singleton' => '',
    'business' => 'new',
    'data' => 'new',
  ];

  public static $_extCall = [
    'singleton' => '::singleton',
    'business' => '',
    'data' => '',
  ];

  public static function &create($className) {
    $type = CRM_Utils_Array::value($className, self::$_classes);
    if (!$type) {
      return CRM_Core_DAO_Factory::create($className);
    }

    $file = self::$_prefix[$type] . $className;
    $class = str_replace('/', '_', $file);

    require_once($file . self::$_suffix);
    if ($type == 'singleton') {
      $newObj = $class::singleton();
    }
    else {
      $newObj = new $class();
    }

    return $newObj;
  }
}
