<?php
/**
 * Factory class for creating Core DAO instances such as Domain, Country, and StateProvince
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_DAO_Factory {

  public static $_classes = [
    'Domain' => 'data',
    'Country' => 'singleton',
    'County' => 'singleton',
    'StateProvince' => 'singleton',
    'GeoCoord' => 'singleton',
    'IMProvider' => 'singleton',
    'MobileProvider' => 'singleton',
  ];

  public static $_prefix = [
    'business' => 'CRM/Core/BAO/',
    'data' => 'CRM/Core/DAO/',
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
      CRM_Core_Error::fatal("class $className not found");
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
