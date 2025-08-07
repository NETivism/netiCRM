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

class CRM_Utils_Rule {

  static function title($str, $maxLength = 127) {

    // check length etc
    if (empty($str) || strlen($str) > $maxLength) {
      return FALSE;
    }

    // Make sure it include valid characters, alpha numeric and underscores
    if (!preg_match('/^\w[\w\s\'\&\,\$\#\-\.\"\?\!]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  static function longTitle($str) {
    return self::title($str, 255);
  }

  static function variable($str) {
    // check length etc
    if (empty($str) || strlen($str) > 31) {
      return FALSE;
    }

    // make sure it include valid characters, alpha numeric and underscores
    if (!preg_match('/^[\w]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate an acceptable column name for sorting results.
   *
   * @param $str
   *
   * @return bool
   */
  public static function mysqlColumnName($str) {
    // Check not empty.
    if (empty($str)) {
      return FALSE;
    }

    // Ensure it only contains valid characters (alphanumeric and underscores).
   if (!preg_match('/^\w{1,64}(\.\w{1,64})?$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate that a string is ASC or DESC.
   *
   * Empty string should be treated as invalid and ignored => default = ASC.
   *
   * @param $str
   * @return bool
   */
  public static function mysqlOrderByDirection($str) {
    if (!preg_match('/^(asc|desc)$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $str
   *
   * @return bool
   */
  static function qfVariable($str) {
    // check length etc
    //if ( empty( $str ) || strlen( $str ) > 31 ) {
    if (strlen(trim($str)) == 0 || strlen($str) > 31) {
      return FALSE;
    }

    // make sure it include valid characters, alpha numeric and underscores
    // added (. and ,) option (CRM-1336)
    if (!preg_match('/^[\w\s\.\,]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  static function phone($phone) {
    // check length etc
    if (empty($phone) || strlen($phone) > 16) {
      return FALSE;
    }

    // make sure it include valid characters, (, \s and numeric
    if (preg_match('/^[\d\(\)\-\.\s]+$/', $phone)) {
      return TRUE;
    }
    return FALSE;
  }

  static function query($query) {
    // check length etc
    if (empty($query) || strlen($query) < 3 || strlen($query) > 127) {
      return FALSE;
    }

    // make sure it include valid characters, alpha numeric and underscores
    if (!preg_match('/^[\w\s\%\'\&\,\$\#]+$/i', $query)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Only allow http / https scheme
   *
   * @param string $url
   * @param string $checkDomain check url has matching domain name
   * @param bool $checkHTTPS to check if has https
   * @return bool 
   */
  static function url($url, $checkDomain = '', $checkHTTPS = NULL) {
    if (!$url) {
      // If this is required then that should be checked elsewhere - here we are not assuming it is required.
      return TRUE;
    }
    if (preg_match('/^\//', $url)) {
      // allow relative URL's (CRM-15598)
      $scheme = CRM_Utils_System::isSSL() ? 'https' : 'http';
      $url = $scheme.'://' . $_SERVER['HTTP_HOST'] . $url;
    }
    $valid = (bool) filter_var($url, FILTER_VALIDATE_URL);
    if (!$valid) {
      $parts = parse_url($url);
      if (!empty($parts['scheme']) && !empty($parts['host'])) {
        $valid = TRUE;
      }
    }

    if (!in_array(substr($url, 0, 5), ['http:', 'https'])) {
      $valid = FALSE;
    }
    $pureDomain = str_replace('/', '', $checkDomain);
    if (!empty($pureDomain) && !preg_match('@^https?://'.preg_quote($pureDomain).'/@i', $url)) {
      $valid = FALSE;
    }
    if (!empty($checkHTTPS) && !preg_match('@^https://@i', $url)) {
      $valid = FALSE;
    }
    return (bool) $valid;
  }

  static function wikiURL($string) {
    $items = explode(' ', trim($string), 2);
    return self::url($items[0]);
  }

  static function domain($domain) {
    // not perfect, but better than the previous one; see CRM-1502
    if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9\.\-]*[A-Za-z0-9])?$/', $domain)) {
      return FALSE;
    }
    return TRUE;
  }

  static function date($value, $default = NULL) {
    if (is_string($value) &&
      preg_match('/^\d\d\d\d-?\d\d-?\d\d$/', $value)
    ) {
      return $value;
    }
    return $default;
  }

  static function dateTime($value, $default = NULL) {
    $result = $default;
    if (is_string($value) &&
      preg_match('/^\d\d\d\d-?\d\d-?\d\d(\s\d\d:\d\d(:\d\d)?|\d\d\d\d(\d\d)?)?$/', $value)
    ) {
      $result = $value;
    }

    return $result;
  }

  /**
   * check the validity of the date (in qf format)
   * note that only a year is valid, or a mon-year is
   * also valid in addition to day-mon-year. The date
   * specified has to be beyond today. (i.e today or later)
   *
   * @param array $date
   * @param bool  $monthRequired check whether month is mandatory
   *
   * @return bool true if valid date
   * @static
   * @access public
   */
  static function currentDate($date, $monthRequired = TRUE) {
    $config = CRM_Core_Config::singleton();

    $d = CRM_Utils_Array::value('d', $date);
    $m = CRM_Utils_Array::value('M', $date);
    $y = CRM_Utils_Array::value('Y', $date);

    if (!$d && !$m && !$y) {
      return TRUE;
    }

    $day = $mon = 1;
    $year = 0;
    if ($d) {
      $day = $d;
    }
    if ($m) {
      $mon = $m;
    }
    if ($y) {
      $year = $y;
    }

    // if we have day we need mon, and if we have mon we need year
    if (($d && !$m) ||
      ($d && !$y) ||
      ($m && !$y)
    ) {
      return FALSE;
    }

    $result = FALSE;
    if (!empty($day) || !empty($mon) || !empty($year)) {
      $result = checkdate($mon, $day, $year);
    }

    if (!$result) {
      return FALSE;
    }

    // ensure we have month if required
    if ($monthRequired && !$m) {
      return FALSE;
    }

    // now make sure this date is greater that today
    $currentDate = getdate();
    if ($year > $currentDate['year']) {
      return TRUE;
    }
    elseif ($year < $currentDate['year']) {
      return FALSE;
    }

    if ($m) {
      if ($mon > $currentDate['mon']) {
        return TRUE;
      }
      elseif ($mon < $currentDate['mon']) {
        return FALSE;
      }
    }

    if ($d) {
      if ($day > $currentDate['mday']) {
        return TRUE;
      }
      elseif ($day < $currentDate['mday']) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * check the validity of a date or datetime (timestamp)
   * value which is in YYYYMMDD or YYYYMMDDHHMMSS format
   *
   * Uses PHP checkdate() - params are ( int $month, int $day, int $year )
   *
   * @param string $date
   *
   * @return bool true if valid date
   * @static
   * @access public
   */
  static function mysqlDate($date) {
    // allow date to be null
    if ($date == NULL) {
      return TRUE;
    }

    if (checkdate(substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4))) {
      return TRUE;
    }

    return FALSE;
  }

  static function integer($value) {
    if (is_int($value)) {
      return TRUE;
    }

    // CRM-13460
    // ensure number passed is always a string numeral
    if (!is_numeric($value)) {
      return FALSE;
    }

    // note that is_int matches only integer type
    // and not strings which are only integers
    // hence we do this here
    if (preg_match('/^\d+$/', $value)) {
      return TRUE;
    }

    if ($value < 0) {
      $negValue = -1 * $value;
      if (is_int($negValue)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  static function positiveInteger($value) {
    if (is_int($value)) {
      return ($value < 0) ? FALSE : TRUE;
    }

    // CRM-13460
    // ensure number passed is always a string numeral
    if (!is_numeric($value)) {
      return FALSE;
    }

    if (preg_match('/^\d+$/', $value)) {
      return TRUE;
    }

    return FALSE;
  }

  public static function commaSeparatedIntegers($value) {
    foreach (explode(',', $value) as $val) {
      $val = trim($val);
      if (!self::positiveInteger($val)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  static function numeric($value) {
    // lets use a php gatekeeper to ensure this is numeric
    if (!is_numeric($value)) {
      return FALSE;
    }

    return preg_match('/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/', $value) ? TRUE : FALSE;
  }

  /**
   * Test whether $value is alphanumeric.
   *
   * Underscores and dashes are also allowed!
   *
   * This is the type of string you could expect to see in URL parameters
   * like `?mode=live` vs `?mode=test`. This function exists so that we can be
   * strict about what we accept for such values, thus mitigating against
   * potential security issues.
   *
   * @see \CRM_Utils_RuleTest::alphanumericData
   *   for examples of vales that give TRUE/FALSE here
   *
   * @param $value
   *
   * @return bool
   */
  public static function alphanumeric($value) {
    return preg_match('/^[a-zA-Z0-9_-]*$/', $value) ? TRUE : FALSE;
  }

  static function numberOfDigit($value, $noOfDigit) {
    return preg_match('/^\d{' . $noOfDigit . '}$/', $value) ? TRUE : FALSE;
  }

  static function cleanMoney($value) {
    // first remove all white space
    $value = str_replace([' ', "\t", "\n"], '', $value);

    $config = &CRM_Core_Config::singleton();

    if ($config->monetaryThousandSeparator) {
      $mon_thousands_sep = $config->monetaryThousandSeparator;
    }
    else {
      $mon_thousands_sep = ',';
    }

    // ugly fix for CRM-6391: do not drop the thousand separator if
    // it looks like it’s separating decimal part (because a given
    // value undergoes a second cleanMoney() call, for example)
    if ($mon_thousands_sep != '.' or substr($value, -3, 1) != '.') {
      $value = str_replace($mon_thousands_sep, '', $value);
    }

    if ($config->monetaryDecimalPoint) {
      $mon_decimal_point = $config->monetaryDecimalPoint;
    }
    else {
      $mon_decimal_point = '.';
    }
    $value = str_replace($mon_decimal_point, '.', $value);

    return $value;
  }

  static function money($value) {
    $config = CRM_Core_Config::singleton();

    //only edge case when we have a decimal point in the input money
    //field and not defined in the decimal Point in config settings
    if ($config->monetaryDecimalPoint &&
      $config->monetaryDecimalPoint != '.' &&
      substr_count($value, '.')
    ) {
      return FALSE;
    }

    $value = self::cleanMoney($value);

    if (self::integer($value)) {
      return TRUE;
    }

    return preg_match('/(^-?\d+\.\d?\d?$)|(^-?\.\d\d?$)/', $value) ? TRUE : FALSE;
  }

  static function string($value, $maxLength = 0) {
    if (is_string($value) &&
      ($maxLength === 0 || strlen($value) <= $maxLength)
    ) {
      return TRUE;
    }
    return FALSE;
  }

  static function boolean($value) {
    return preg_match(
      '/(^(1|0)$)|(^(Y(es)?|N(o)?)$)|(^(T(rue)?|F(alse)?)$)/i', $value
    ) ? TRUE : FALSE;
  }

  static function email($value, $checkDomain = FALSE) {
    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
  }

  static function emailList($list, $checkDomain = FALSE) {
    $emails = explode(',', $list);
    foreach ($emails as $email) {
      $email = trim($email);
      if (!self::email($email, $checkDomain)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  // allow between 4-6 digits as postal code since india needs 6 and US needs 5 (or
  // if u disregard the first 0, 4 (thanx excel!)
  // FIXME: we need to figure out how to localize such rules
  static function postalCode($value) {
    if (preg_match('/^\d{4,6}(-\d{4})?$/', $value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * see how file rules are written in HTML/QuickForm/file.php
   * Checks to make sure the uploaded file is ascii
   *
   * @param     array     Uploaded file info (from $_FILES)
   * @access    private
   *
   * @return    bool      true if file has been uploaded, false otherwise
   */
  static function asciiFile($elementValue) {
    $valid = TRUE;
    if (is_array($elementValue['tmp_name'])) {
      foreach($elementValue['tmp_name'] as $idx => $tmpName){
        if ((isset($elementValue['error'][$idx]) && $elementValue['error'][$idx] == 0) ||
          (!empty($elementValue['tmp_name'][$idx]) && $elementValue['tmp_name'][$idx] != 'none')
        ) {
          $valid = CRM_Utils_File::isAscii($tmpName);
        }
        if (!$valid) {
          break;
        }
      }
    }
    else {
      if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
        (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
      ) {
        $valid = CRM_Utils_File::isAscii($elementValue['tmp_name']);
      }
    }
    return $valid;
  }

  /**
   * Checks to make sure the uploaded file is in UTF-8, recodes if it's not
   *
   * @param     array     Uploaded file info (from $_FILES)
   * @access    private
   *
   * @return    bool      whether file has been uploaded properly and is now in UTF-8
   */
  static function utf8File($elementValue) {
    $success = FALSE;
    if (is_array($elementValue['tmp_name'])) {
      foreach($elementValue['tmp_name'] as $idx => $tmpName) {
        if ((isset($elementValue['error'][$idx]) && $elementValue['error'][$idx] == 0) ||
          (!empty($elementValue['tmp_name'][$idx]) && $elementValue['tmp_name'][$idx] != 'none')
        ) {

          $success = CRM_Utils_File::isAscii($tmpName);

          // if it's a file, but not UTF-8, let's try and recode it
          // and then make sure it's an UTF-8 file in the end
          if (!$success) {
            $success = CRM_Utils_File::toUtf8($tmpName);
            if ($success) {
              $success = CRM_Utils_File::isAscii($tmpName);
            }
          }
        }
      }
    }
    else {
      if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
        (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
      ) {

        $success = CRM_Utils_File::isAscii($elementValue['tmp_name']);

        // if it's a file, but not UTF-8, let's try and recode it
        // and then make sure it's an UTF-8 file in the end
        if (!$success) {
          $success = CRM_Utils_File::toUtf8($elementValue['tmp_name']);
          if ($success) {
            $success = CRM_Utils_File::isAscii($elementValue['tmp_name']);
          }
        }
      }
    }
    return $success;
  }

  /**
   * see how file rules are written in HTML/QuickForm/file.php
   * Checks to make sure the uploaded file is html
   *
   * @param     array     Uploaded file info (from $_FILES)
   * @access    private
   *
   * @return    bool      true if file has been uploaded, false otherwise
   */
  static function htmlFile($elementValue) {
    if (is_array($elementValue['tmp_name'])) {
      $valid = FALSE;
      foreach($elementValue['tmp_name'] as $idx => $tmpName) {
        if ((isset($elementValue['error'][$idx]) && $elementValue['error'][$idx] == 0) ||
          (!empty($elementValue['tmp_name'][$idx]) && $elementValue['tmp_name'][$idx] != 'none')
        ) {
          $valid = CRM_Utils_File::isHtmlFile($tmpName);
          if (!$valid) {
            break;
          }
        }
      }
      return $valid;
    }
    else {
      if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
        (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
      ) {
        return CRM_Utils_File::isHtmlFile($elementValue['tmp_name']);
      }
    }
    return FALSE;
  }

  /**
   * see how file rules are written in HTML/QuickForm/file.php
   * Checks to make sure the uploaded file is html
   *
   * @param     array     Uploaded file info (from $_FILES)
   *            string    WIDTH HEIGHT QUALITY SKIPVERIFY with x between
   * @access    private
   *
   * @return    bool      true if file has been uploaded, false otherwise
   */
  static function imageFile($elementValue, $format = NULL) {
    if (!empty($format)) {
      list($maxWidth, $maxHeight, $quality, $skip) = explode('x', $format);
    }
    if (empty($maxWidth) || empty($maxHeight)) {
      $maxWidth = 2000;
      $maxHeight = 2000;
    }
    $quality = !empty($quality) ? $quality : 90;
    $skip = !empty($skip) ? TRUE : FALSE;
    $valid = TRUE;
    if (is_array($elementValue['tmp_name'])) {
      foreach($elementValue['tmp_name'] as $idx => $tmpName) {
        if (!empty($tmpName)) {
          list($width, $height) = getimagesize($tmpName);
          if ($width && $height) {
            if ($width > $maxWidth || $height > $maxHeight) {
              $image = new CRM_Utils_Image($tmpName, $tmpName, $quality);
              $resized = $image->scale($maxWidth, $maxHeight);
            }
          }
          else {
            $valid = FALSE;
          }
        }
      }
    }
    else {
      $tmpName = $elementValue['tmp_name'];
      if (!empty($tmpName)) {
        list($width, $height) = getimagesize($tmpName);
        if ($width && $height) {
          if ($width > $maxWidth || $height > $maxHeight) {
            $image = new CRM_Utils_Image($tmpName, $tmpName);
            $resized = $image->scale($maxWidth, $maxHeight);
          }
        }
        else {
          $valid = FALSE;
        }
      }
    }
    if (!empty($skip)) {
      return TRUE;
    }
    else {
      return $valid;
    }
  }

  /**
   * Check if there is a record with the same name in the db
   *
   * @param string $value     the value of the field we are checking
   * @param array  $options   the daoName and fieldName (optional )
   *
   * @return boolean     true if object exists
   * @access public
   * @static
   */
  static function objectExists($value, $options) {
    $name = 'name';
    if (isset($options[2])) {
      $name = $options[2];
    }

    return CRM_Core_DAO::objectExists($value, $options[0], $options[1], CRM_Utils_Array::value(2, $options, $name));
  }

  static function optionExists($value, $options) {

    return CRM_Core_OptionValue::optionExists($value, $options[0], $options[1], $options[2], CRM_Utils_Array::value(3, $options, 'name'));
  }

  static function creditCardNumber($value, $type) {

    return Validate_Finance_CreditCard::number($value, $type);
  }

  static function cvv($value, $type) {


    return Validate_Finance_CreditCard::cvv($value, $type);
  }

  static function currencyCode($value) {
    static $currencyCodes = NULL;
    if (!$currencyCodes) {
      $currencyCodes = CRM_Core_PseudoConstant::currencyCode();
    }
    if (in_array($value, $currencyCodes)) {
      return TRUE;
    }
    return FALSE;
  }

  static function xssString($value) {
    if (is_string($value)) {
      return preg_match('!<(vb)?script[^>]*>.*</(vb)?script.*>!ims',
        $value
      ) ? FALSE : TRUE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Validate json string for xss
   *
   * @param string $value
   *
   * @return bool
   *   False if invalid, true if valid / safe.
   */
  public static function json($value) {
    if (!self::xssString($value)) {
      return FALSE;
    }
    $array = json_decode($value, TRUE);
    if (!$array || !is_array($array)) {
      return FALSE;
    }
    return self::arrayValue($array);
  }

  static function fileExists($path) {
    return file_exists($path);
  }

  static function autocomplete($value, $options) {
    if ($value) {

      $selectOption = &CRM_Core_BAO_CustomOption::valuesByID($options['fieldID'], $options['optionGroupID']);

      if (!in_array($value, $selectOption)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  static function validContact($value, $actualElementValue = NULL) {
    if ($actualElementValue) {
      $value = $actualElementValue;
    }

    return self::positiveInteger($value);
  }

  /**
   * check the validity of the date (in qf format)
   * note that only a year is valid, or a mon-year is
   * also valid in addition to day-mon-year
   *
   * @param array $date
   *
   * @return bool true if valid date
   * @static
   * @access public
   */
  static function qfDate($date) {
    $config = CRM_Core_Config::singleton();

    $d = CRM_Utils_Array::value('d', $date);
    $m = CRM_Utils_Array::value('M', $date);
    $y = CRM_Utils_Array::value('Y', $date);
    if (isset($date['h']) ||
      isset($date['g'])
    ) {
      $m = CRM_Utils_Array::value('M', $date);
    }

    if (!$d && !$m && !$y) {
      return TRUE;
    }

    $day = $mon = 1;
    $year = 0;
    if ($d) {
      $day = $d;
    }
    if ($m) {
      $mon = $m;
    }
    if ($y) {
      $year = $y;
    }

    // if we have day we need mon, and if we have mon we need year
    if (($d && !$m) ||
      ($d && !$y) ||
      ($m && !$y)
    ) {
      return FALSE;
    }

    if (!empty($day) || !empty($mon) || !empty($year)) {
      return checkdate($mon, $day, $year);
    }
    return FALSE;
  }

  static function qfKey($key) {
    return ($key) ? CRM_Core_Key::valid($key) : FALSE;
  }

  /**
   * Validate array recursively checking keys and  values.
   *
   * @param array $array
   * @return bool
   */
  protected static function arrayValue($array) {
    foreach ($array as $key => $item) {
      if (is_array($item)) {
        if (!self::xssString($key) || !self::arrayValue($item)) {
          return FALSE;
        }
      }
      if (!self::xssString($key) || !self::xssString($item)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check ip address
   *
   * This is from symfony HttpFoundation/IpUtils::checkIp
   * In case a subnet is given, it checks if it contains the request IP.
   *
   * @param string $requestIp the ip got to verify
   * @param string $ips addresses or subnet got to match
   *
   * @return bool
   */
  public static function checkIp($requestIp, $ips) {
    if (null === $requestIp) {
      return false;
    }
    if (!is_array($ips)) {
      $ips = [
        $ips,
      ];
    }
    $method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';
    foreach ($ips as $ip) {
      if (self::$method($requestIp, $ip)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Compares two IPv4 addresses
   *
   * This is from symfony HttpFoundation/IpUtils::checkIp4
   * In case a subnet is given, it checks if it contains the request IP.
   *
   * @param string $requestIp the ip got to verify
   * @param string $allowIp IPv4 address or subnet in CIDR notation
   *
   * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
   */
  public static function checkIp4($requestIp, $ip) {
    if (!filter_var($requestIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return false;
    }
    if (false !== strpos($ip, '/')) {
      list($address, $netmask) = explode('/', $ip, 2);
      if ($netmask === '0') {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
      }
      if ($netmask < 0 || $netmask > 32) {
        return false;
      }
    }
    else {
      $address = $ip;
      $netmask = 32;
    }
    return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
  }

  /**
   * Compares two IPv6 addresses
   *
   * This is from symfony HttpFoundation/IpUtils::checkIp6
   * In case a subnet is given, it checks if it contains the request IP.
   *
   * @param string $requestIp the ip got to verify
   * @param string $allowIp IPv4 address or subnet in CIDR notation
   *
   * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
   */
  public static function checkIp6($requestIp, $ip) {
    if (!(extension_loaded('sockets') && defined('AF_INET6') || @inet_pton('::1'))) {
      throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
    }
    if (false !== strpos($ip, '/')) {
      list($address, $netmask) = explode('/', $ip, 2);
      if ($netmask < 1 || $netmask > 128) {
        return false;
      }
    }
    else {
      $address = $ip;
      $netmask = 128;
    }
    $bytesAddr = unpack("n*", inet_pton($address));
    $bytesTest = unpack("n*", inet_pton($requestIp));
    for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; $i++) {
      $left = $netmask - 16 * ($i - 1);
      $left = $left <= 16 ? $left : 16;
      $mask = ~(0xffff >> $left) & 0xffff;
      if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Check Directory Name
   *
   * @param string $name
   * @return bool
   */
  public static function directoryName($name) {
    $dirName = CRM_Utils_File::sanitizeDirectoryName($name);
    if ($dirName == $name) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check File Name
   *
   * @param string $name
   * @return bool
   */
  public static function fileName($name) {
    $fileName = CRM_Utils_File::sanitizeFileName($name);
    if ($fileName == $name) {
      return TRUE;
    }
    return FALSE;
  }
}

