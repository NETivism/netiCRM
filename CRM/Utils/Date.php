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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Date utilities for formatting, converting, and calculating dates.
 */
class CRM_Utils_Date {

  /**
   * Format a date by padding it with leading '0'.
   *
   * Accepts either a numeric date string (8 or 14 digits) or an associative
   * array with keys 'Y', 'M'/'m'/'F', 'd', and optional time keys
   * 'H'/'h', 'i', 's', 'A'/'a'.
   *
   * @param array|int|string $date Date array with keys like 'Y', 'M', 'd', or a numeric date string
   * @param string $separator The separator to use when formatting the date
   * @param string|int $invalidDate Value to return if the date is invalid
   *
   * @return string|int Formatted date string, or $invalidDate on failure
   */
  public static function format($date, $separator = '', $invalidDate = 0) {
    if (is_numeric($date) &&
      (strlen(strval($date)) == 8 || strlen(strval($date)) == 14)
    ) {
      return $date;
    }

    if (!is_array($date) ||
      CRM_Utils_System::isNull($date) ||
      empty($date['Y'])
    ) {
      return $invalidDate;
    }

    $date['Y'] = (int ) $date['Y'];
    if ($date['Y'] < 1000 || $date['Y'] > 2999) {
      return $invalidDate;
    }

    if (CRM_Utils_Array::arrayKeyExists('m', $date)) {
      $date['M'] = $date['m'];
    }
    elseif (CRM_Utils_Array::arrayKeyExists('F', $date)) {
      $date['M'] = $date['F'];
    }

    if (CRM_Utils_Array::value('M', $date)) {
      $date['M'] = (int ) $date['M'];
      if ($date['M'] < 1 || $date['M'] > 12) {
        return $invalidDate;
      }
    }
    else {
      $date['M'] = 1;
    }

    if (CRM_Utils_Array::value('d', $date)) {
      $date['d'] = (int ) $date['d'];
    }
    else {
      $date['d'] = 1;
    }

    if (!checkdate($date['M'], $date['d'], $date['Y'])) {
      return $invalidDate;
    }

    $date['M'] = sprintf('%02d', $date['M']);
    $date['d'] = sprintf('%02d', $date['d']);

    $time = '';
    if (CRM_Utils_Array::value('H', $date) != NULL ||
      CRM_Utils_Array::value('h', $date) != NULL ||
      CRM_Utils_Array::value('i', $date) != NULL ||
      CRM_Utils_Array::value('s', $date) != NULL
    ) {
      // we have time too..
      if (CRM_Utils_Array::value('h', $date)) {
        if (CRM_Utils_Array::value('A', $date) == 'PM' or CRM_Utils_Array::value('a', $date) == 'pm') {
          if ($date['h'] != 12) {
            $date['h'] = $date['h'] + 12;
          }
        }
        if ((CRM_Utils_Array::value('A', $date) == 'AM' or CRM_Utils_Array::value('a', $date) == 'am') &&
          CRM_Utils_Array::value('h', $date) == 12
        ) {
          $date['h'] = '00';
        }

        $date['h'] = (int ) $date['h'];
      }
      else {
        $date['h'] = 0;
      }

      // in 24-hour format the hour is under the 'H' key
      if (CRM_Utils_Array::value('H', $date)) {
        $date['H'] = (int) $date['H'];
      }
      else {
        $date['H'] = 0;
      }

      if (CRM_Utils_Array::value('i', $date)) {
        $date['i'] = (int ) $date['i'];
      }
      else {
        $date['i'] = 0;
      }

      if ($date['h'] == 0 && $date['H'] != 0) {
        $date['h'] = $date['H'];
      }

      if (CRM_Utils_Array::value('s', $date)) {
        $date['s'] = (int ) $date['s'];
      }
      else {
        $date['s'] = 0;
      }

      $date['h'] = sprintf('%02d', $date['h']);
      $date['i'] = sprintf('%02d', $date['i']);
      $date['s'] = sprintf('%02d', $date['s']);

      if ($separator) {
        $time = '&nbsp;';
      }
      $time .= $date['h'] . $separator . $date['i'] . $separator . $date['s'];
    }

    return $date['Y'] . $separator . $date['M'] . $separator . $date['d'] . $time;
  }

  /**
   * Return abbreviated weekday names according to the locale.
   *
   * @return array<int, string> 0-based array with abbreviated weekday names
   */
  public static function &getAbbrWeekdayNames() {
    static $abbrWeekdayNames;
    if (!isset($abbrWeekdayNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      // June 1st, 1970 was a Monday
      CRM_Core_I18n::setLcTime();
      for ($i = 0; $i < 7; $i++) {
        $abbrWeekdayNames[$i] = strftime('%a', mktime(0, 0, 0, 6, $i, 1970));
      }
    }
    return $abbrWeekdayNames;
  }

  /**
   * Return full weekday names according to the locale.
   *
   * @return array<int, string> 0-based array with full weekday names
   */
  public static function &getFullWeekdayNames() {
    static $fullWeekdayNames;
    if (!isset($fullWeekdayNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      // June 1st, 1970 was a Monday
      CRM_Core_I18n::setLcTime();
      for ($i = 0; $i < 7; $i++) {
        $fullWeekdayNames[$i] = strftime('%A', mktime(0, 0, 0, 6, $i, 1970));
      }
    }
    return $fullWeekdayNames;
  }

  /**
   * Return abbreviated month names according to the locale.
   *
   * @param int|false $month Optional month number (1-12) to return a single name
   *
   * @return array<int, string>|string 1-based array with abbreviated month names, or a single month name if $month is provided
   */
  public static function &getAbbrMonthNames($month = FALSE) {
    static $abbrMonthNames;
    if (!isset($abbrMonthNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      CRM_Core_I18n::setLcTime();
      for ($i = 1; $i <= 12; $i++) {
        $abbrMonthNames[$i] = strftime('%b', mktime(0, 0, 0, $i, 10, 1970));
      }
    }
    if ($month) {
      return $abbrMonthNames[$month];
    }
    return $abbrMonthNames;
  }

  /**
   * Return full month names according to the locale.
   *
   * @return array<int, string> 1-based array with full month names
   */
  public static function &getFullMonthNames() {
    static $fullMonthNames;
    if (!isset($fullMonthNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      CRM_Core_I18n::setLcTime();
      for ($i = 1; $i <= 12; $i++) {
        $fullMonthNames[$i] = strftime('%B', mktime(0, 0, 0, $i, 10, 1970));
      }
    }
    return $fullMonthNames;
  }

  /**
   * Convert date string to Unix timestamp
   *
   * If seconds exist in input: Uses original seconds
   * If no seconds specified: Uses 0 by default, or 59 if $useEndOfMinute=true
   *
   * @param string $string Date string (YYYY-MM-DD HH:MM:SS)
   * @param bool $useEndOfMinute If true, sets seconds to 59 when not specified
   * @return int Unix timestamp
   *
   */
  public static function unixTime($string, $useEndOfMinute = FALSE) {
    if (empty($string)) {
      return 0;
    }
    $parsedDate = date_parse($string);

    if (isset($parsedDate['second']) && $parsedDate['second'] > 0) {
      $second = CRM_Utils_Array::value('second', $parsedDate, 0);
    }
    else {
      $second = $useEndOfMinute ? 59 : 0;
    }

    return mktime(
      CRM_Utils_Array::value('hour', $parsedDate),
      CRM_Utils_Array::value('minute', $parsedDate),
      $second,
      CRM_Utils_Array::value('month', $parsedDate),
      CRM_Utils_Array::value('day', $parsedDate),
      CRM_Utils_Array::value('year', $parsedDate)
    );
  }

  /**
   * Create a date and time string in a provided format.
   *
   * Supported format tokens:
   * - %b - abbreviated month name ('Jan'..'Dec')
   * - %B - full month name ('January'..'December')
   * - %d - day of the month, 0-padded ('01'..'31')
   * - %e - day of the month, blank-padded (' 1'..'31')
   * - %E - day of the month, no padding ('1'..'31')
   * - %f - English ordinal suffix for the day ('st', 'nd', 'rd', 'th')
   * - %H - hour in 24-hour format, 0-padded ('00'..'23')
   * - %h - hour in 12-hour format, 0-padded ('01'..'12')
   * - %I - hour in 12-hour format, 0-padded ('01'..'12')
   * - %k - hour in 24-hour format, blank-padded (' 0'..'23')
   * - %l - hour in 12-hour format, blank-padded (' 1'..'12')
   * - %m - month as a decimal number, 0-padded ('01'..'12')
   * - %M - minute, 0-padded ('00'..'59')
   * - %i - minute, 0-padded ('00'..'59')
   * - %p - lowercase ante/post meridiem ('am', 'pm')
   * - %P - uppercase ante/post meridiem ('AM', 'PM')
   * - %A - uppercase ante/post meridiem ('AM', 'PM')
   * - %Y - year as a decimal number including the century ('2005')
   *
   * @param string $dateString Date and time in 'YYYY-MM-DD hh:mm:ss' or 'YYYYMMDDhhmmss' format
   * @param string|null $format The output format using tokens above; defaults to config-based format
   * @param array|null $dateParts An array of date part identifiers (e.g. ['h', 'd', 'm']) to determine default format
   *
   * @return string The formatted date string, or empty string if $dateString is empty
   */
  public static function customFormat($dateString, $format = NULL, $dateParts = NULL) {
    // 1-based (January) month names arrays
    $abbrMonths = self::getAbbrMonthNames();
    $fullMonths = self::getFullMonthNames();

    if (!$format) {
      $config = CRM_Core_Config::singleton();

      if ($dateParts) {
        if (array_intersect(['h', 'H'], $dateParts)) {
          $format = $config->dateformatDatetime;
        }
        elseif (array_intersect(['d', 'j'], $dateParts)) {
          $format = $config->dateformatFull;
        }
        elseif (array_intersect(['m', 'M'], $dateParts)) {
          $format = $config->dateformatPartial;
        }
        else {
          $format = $config->dateformatYear;
        }
      }
      else {
        if (strpos($dateString, '-')) {
          $month = (int) substr($dateString, 5, 2);
          $day = (int) substr($dateString, 8, 2);
        }
        else {
          $month = (int) substr($dateString, 4, 2);
          $day = (int) substr($dateString, 6, 2);
        }

        if (strlen($dateString) > 10) {
          $format = $config->dateformatDatetime;
        }
        elseif ($day > 0) {
          $format = $config->dateformatFull;
        }
        elseif ($month > 0) {
          $format = $config->dateformatPartial;
        }
        else {
          $format = $config->dateformatYear;
        }
      }
    }

    if ($dateString) {
      if (strpos($dateString, '-')) {
        $year = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 5, 2);
        $day = (int) substr($dateString, 8, 2);

        $hour24 = (int) substr($dateString, 11, 2);
        $minute = (int) substr($dateString, 14, 2);
      }
      else {
        $year = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 4, 2);
        $day = (int) substr($dateString, 6, 2);

        $hour24 = (int) substr($dateString, 8, 2);
        $minute = (int) substr($dateString, 10, 2);
      }

      if ($day % 10 == 1 and $day != 11) {

        $suffix = 'st';

      }
      elseif ($day % 10 == 2 and $day != 12) {
        $suffix = 'nd';
      }
      elseif ($day % 10 == 3 and $day != 13) {
        $suffix = 'rd';
      }
      else {
        $suffix = 'th';
      }

      if ($hour24 < 12) {
        if ($hour24 == 00) {
          $hour12 = 12;
        }
        else {
          $hour12 = $hour24;
        }
        $type = 'AM';
      }
      else {
        if ($hour24 == 12) {
          $hour12 = 12;
        }
        else {
          $hour12 = $hour24 - 12;
        }
        $type = 'PM';
      }

      $date = [
        '%b' => CRM_Utils_Array::value($month, $abbrMonths),
        '%B' => CRM_Utils_Array::value($month, $fullMonths),
        '%d' => $day > 9 ? $day : '0' . $day,
        '%e' => $day > 9 ? $day : ' ' . $day,
        '%E' => $day,
        '%f' => $suffix,
        '%H' => $hour24 > 9 ? $hour24 : '0' . $hour24,
        '%h' => $hour12 > 9 ? $hour12 : '0' . $hour12,
        '%I' => $hour12 > 9 ? $hour12 : '0' . $hour12,
        '%k' => $hour24 > 9 ? $hour24 : ' ' . $hour24,
        '%l' => $hour12 > 9 ? $hour12 : ' ' . $hour12,
        '%m' => $month > 9 ? $month : '0' . $month,
        '%M' => $minute > 9 ? $minute : '0' . $minute,
        '%i' => $minute > 9 ? $minute : '0' . $minute,
        '%p' => strtolower($type),
        '%P' => $type,
        '%A' => $type,
        '%Y' => $year,
      ];

      return strtr($format, $date);
    }
    else {
      return '';
    }
  }

  /**
   * Convert a date/datetime from MySQL compact format (YYYYMMDDHHIISS) to ISO format (YYYY-MM-DD HH:II:SS).
   *
   * @param string $mysql Date/datetime in MySQL compact format (e.g. '20231015143000')
   *
   * @return string Date/datetime in ISO format (e.g. '2023-10-15 14:30:00')
   */
  public static function mysqlToIso($mysql) {
    $year = substr($mysql, 0, 4);
    $month = substr($mysql, 4, 2);
    $day = substr($mysql, 6, 2);
    $hour = substr($mysql, 8, 2);
    $minute = substr($mysql, 10, 2);
    $second = substr($mysql, 12, 2);

    $iso = '';
    if ($year) {
      $iso .= "$year";
    }
    if ($month) {
      $iso .= "-$month";
      if ($day) {
        $iso .= "-$day";
      }
    }

    if ($hour) {
      $iso .= " $hour";
      if ($minute) {
        $iso .= ":$minute";
        if ($second) {
          $iso .= ":$second";
        }
      }
    }
    return $iso;
  }

  /**
   * Convert a date/datetime from ISO format (YYYY-MM-DD HH:II:SS) to MySQL compact format (YYYYMMDDHHIISS).
   *
   * @param string $iso Date/datetime in ISO format (e.g. '2023-10-15 14:30:00')
   *
   * @return string Date/datetime in MySQL compact format (e.g. '20231015143000')
   */
  public static function isoToMysql($iso) {
    $dropArray = ['-' => '', ':' => '', ' ' => ''];
    return strtr($iso, $dropArray);
  }

  /**
   * Convert a date value in the given format to the default MySQL compact date format.
   *
   * The converted date is written back into $params[$dateParam].
   *
   * @param array $params Array containing the date value, modified in place
   * @param int $dateType Type of date format (1=YYYY-MM-DD, 2=MM/DD/YY, 4=MM/DD/YYYY, 8=Month DD, YYYY, 16=DD-Mon-YY, 32=DD/MM/YYYY)
   * @param string $dateParam Key name within $params that holds the date value
   *
   * @return bool TRUE on success, FALSE if the date format is invalid
   */
  public static function convertToDefaultDate(&$params, $dateType, $dateParam) {
    $now = getDate();
    $cen = substr(strval($now['year']), 0, 2);
    $prevCen = $cen - 1;

    $value = $time = NULL;
    if (CRM_Utils_Array::value($dateParam, $params)) {
      //suppress hh:mm:ss if it exists
      preg_match("/(\s(([01]\d)|[2][0-3]):([0-5]\d):?([0-5]\d)?)$/", $params[$dateParam], $matches);
      if (!empty($matches[1])) {
        $value = str_replace($matches[0], '', $params[$dateParam]);
        $time = preg_replace('/[^\d]/i', '', $matches[1]);
        if (strlen($time) == 4) {
          $time .= '00';
        }
        if (strlen($time) != 6) {
          return FALSE;
        }
      }
      else {
        $value = $params[$dateParam];
      }
    }

    switch ($dateType) {
      case 1:
        if (!preg_match('/^\d\d\d\d-?(\d|\d\d)-?(\d|\d\d)$/', $value)) {
          return FALSE;
        }
        break;

      case 2:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 4:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 8:
        if (!preg_match('/^[A-Za-z]*.[ \t]?\d\d\,[ \t]?\d\d\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 16:
        if (!preg_match('/^\d\d-[A-Za-z]{3}.*-\d\d$/', $value) && !preg_match('/^\d\d[-\/]\d\d[-\/]\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 32:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d\d\d/', $value)) {
          return FALSE;
        }
        break;
    }

    if ($dateType == 1) {
      $formattedDate = explode("-", $value);
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[0];
        $month = (int) $formattedDate[1];
        $day = (int) $formattedDate[2];
      }
      elseif (count($formattedDate) == 1 && (strlen($value) == 8)) {
        if ($time) {
          $params[$dateParam] = $value.$time;
        }
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    if ($dateType == 2 || $dateType == 4) {
      $formattedDate = explode("/", $value);
      if (count($formattedDate) != 3) {
        $formattedDate = explode("-", $value);
      }
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[2];
        $month = (int) $formattedDate[0];
        $day = (int) $formattedDate[1];
      }
      else {
        return FALSE;
      }
    }
    if ($dateType == 8) {
      $dateArray = explode(' ', $value);
      // ignore comma(,)
      $dateArray[1] = (int) substr($dateArray[1], 0, 2);

      $monthInt = 0;
      $fullMonths = self::getFullMonthNames();
      foreach ($fullMonths as $key => $val) {
        if (strtolower($dateArray[0]) == strtolower($val)) {
          $monthInt = $key;
          break;
        }
      }
      if (!$monthInt) {
        $abbrMonths = self::getAbbrMonthNames();
        foreach ($abbrMonths as $key => $val) {
          if (strtolower(trim($dateArray[0], ".")) == strtolower($val)) {
            $monthInt = $key;
            break;
          }
        }
      }
      $year = (int) $dateArray[2];
      $day = (int) $dateArray[1];
      $month = (int) $monthInt;
    }
    if ($dateType == 16) {
      $dateArray = explode('-', $value);
      if (count($dateArray) != 3) {
        $dateArray = explode('/', $value);
      }

      if (count($dateArray) == 3) {
        $monthInt = 0;
        $fullMonths = self::getFullMonthNames();
        foreach ($fullMonths as $key => $val) {
          if (strtolower($dateArray[1]) == strtolower($val)) {
            $monthInt = $key;
            break;
          }
        }
        if (!$monthInt) {
          $abbrMonths = self::getAbbrMonthNames();
          foreach ($abbrMonths as $key => $val) {
            if (strtolower(trim($dateArray[1], ".")) == strtolower($val)) {
              $monthInt = $key;
              break;
            }
          }
        }
        if (!$monthInt) {
          $monthInt = $dateArray[1];
        }

        $year = (int) $dateArray[2];
        $day = (int) $dateArray[0];
        $month = (int) $monthInt;
      }
      else {
        return FALSE;
      }
    }
    if ($dateType == 32) {
      $formattedDate = explode("/", $value);
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[2];
        $month = (int) $formattedDate[1];
        $day = (int) $formattedDate[0];
      }
      else {
        return FALSE;
      }
    }

    $month = ($month < 10) ? "0" . "$month" : $month;
    $day = ($day < 10) ? "0" . "$day" : $day;

    $year = (int ) $year;
    // simple heuristic to determine what century to use
    // 00 - 20 is always 2000 - 2020
    // 21 - 99 is always 1921 - 1999
    if ($year < 21) {
      $year = (strlen(strval($year)) == 1) ? $cen . '0' . $year : $cen . $year;
    }
    elseif ($year < 100) {
      $year = $prevCen . $year;
    }

    if ($params[$dateParam]) {
      $params[$dateParam] = "$year$month$day";
    }
    //if month is invalid return as error
    if ($month !== '00' && $month <= 12) {
      if ($time) {
        $params[$dateParam] .= $time;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check whether the given date value is non-null and non-empty.
   *
   * @param mixed $date Date value to check (array or string)
   *
   * @return bool TRUE if the date is valid (non-null), FALSE otherwise
   */
  public static function isDate(&$date) {
    if (CRM_Utils_System::isNull($date)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the current date and time in MySQL compact format (YmdHis).
   *
   * @param int|null $timeStamp Optional Unix timestamp; uses current time if NULL
   *
   * @return string Date/time string in 'YmdHis' format (e.g. '20231015143000')
   */
  public static function currentDBDate($timeStamp = NULL) {
    return $timeStamp ? date('YmdHis', $timeStamp) : date('YmdHis');
  }

  /**
   * Check whether the given date is overdue (i.e. in the past).
   *
   * @param string $date Date string in ISO format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
   * @param string|null $now Optional reference date in ISO format; defaults to current date/time
   *
   * @return bool TRUE if the date is in the past, FALSE otherwise
   */
  public static function overdue($date, $now = NULL) {
    $mysqlDate = self::isoToMysql($date);
    if (!$now) {
      $now = self::currentDBDate();
    }
    else {
      $now = self::isoToMysql($now);
    }

    return ($mysqlDate >= $now) ? FALSE : TRUE;
  }

  /**
   * Get today's date or a customized date string.
   *
   * To get the actual today, pass $dayParams as NULL. Otherwise pass
   * day, month, year values as an associative array.
   * Example: $dayParams = ['day' => '25', 'month' => '10', 'year' => '2007'];
   *
   * @param array|null $dayParams Array with 'day', 'month', 'year' keys, or NULL for today
   * @param string $format Expected date format (default is 'Y-m-d')
   *
   * @return string Formatted date string
   */
  public static function getToday($dayParams = NULL, $format = "Y-m-d") {
    if (is_null($dayParams) || empty($dayParams)) {
      $today = date($format);
    }
    else {
      $today = date($format, mktime(
        0,
        0,
        0,
        $dayParams['month'],
        $dayParams['day'],
        $dayParams['year']
      ));
    }

    return $today;
  }

  /**
   * Check whether a given timestamp falls within the specified date range.
   *
   * @param string $startDate Start date for the range in ISO format
   * @param string $endDate End date for the range in ISO format
   * @param int $timestamp Unix timestamp to check; defaults to CRM_REQUEST_TIME
   *
   * @return bool TRUE if the date is within the range, FALSE otherwise
   */
  public static function getRange($startDate, $endDate, $timestamp = CRM_REQUEST_TIME) {
    $today = date("Y-m-d", $timestamp);
    $mysqlStartDate = self::isoToMysql($startDate);
    $mysqlEndDate = self::isoToMysql($endDate);
    $mysqlToday = self::isoToMysql($today);

    if ((isset($mysqlStartDate) && isset($mysqlEndDate)) && (($mysqlToday >= $mysqlStartDate) && ($mysqlToday <= $mysqlEndDate))) {
      return TRUE;
    }
    elseif ((isset($mysqlStartDate) && !isset($mysqlEndDate)) && (($mysqlToday >= $mysqlStartDate))) {
      return TRUE;
    }
    elseif ((!isset($mysqlStartDate) && isset($mysqlEndDate)) && (($mysqlToday <= $mysqlEndDate))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Calculate age in years if greater than one year, otherwise in months.
   *
   * @param string $birthDate Birth date string in a format parseable by customFormat()
   *
   * @return array Associative array with either 'years' or 'months' key, or empty array if birth year is 1902
   */
  public static function calculateAge($birthDate) {
    $results = [];
    $formatedBirthDate = CRM_Utils_Date::customFormat($birthDate, '%Y-%m-%d');

    $bDate = explode('-', $formatedBirthDate);
    $birthYear = $bDate[0];
    $birthMonth = $bDate[1];
    $birthDay = $bDate[2];
    $year_diff = date("Y") - $birthYear;

    // don't calculate age CRM-3143
    if ($birthYear == '1902') {
      return $results;
    }

    switch ($year_diff) {
      case 1:
        $month = (12 - $birthMonth) + date("m");
        if ($month < 12) {
          if (date("d") < $birthDay) {
            $month--;
          }
          $results['months'] = $month;
        }
        elseif ($month == 12 && (date("d") < $birthDay)) {
          $results['months'] = $month - 1;
        }
        else {
          $results['years'] = $year_diff;
        }
        break;

      case 0:
        $month = date("m") - $birthMonth;
        $results['months'] = $month;
        break;

      default:
        $results['years'] = $year_diff;
        if ((date("m") < $birthMonth) || (date("m") == $birthMonth) && (date("d") < $birthDay)) {
          $results['years']--;
        }
    }

    return $results;
  }

  /**
   * Calculate the next date by adding the specified interval to a given date.
   *
   * @param string $unit Frequency unit: 'year', 'month', 'week', 'day', or 'second'
   * @param int $interval Number of units to add (can be negative)
   * @param array|string $date Start date as an associative array with keys 'Y', 'M', 'd', 'H', 'i', 's', or a date string parseable by date_parse()
   * @param bool $dontCareTime If TRUE, omit time components from the result
   *
   * @return array<string, string> Associative array with keys 'Y', 'M', 'd' and optionally 'H', 'i', 's'
   */
  public static function intervalAdd($unit, $interval, $date, $dontCareTime = FALSE) {
    if (is_array($date)) {
      $hour = CRM_Utils_Array::value('H', $date);
      $minute = CRM_Utils_Array::value('i', $date);
      $second = CRM_Utils_Array::value('s', $date);
      $month = CRM_Utils_Array::value('M', $date);
      $day = CRM_Utils_Array::value('d', $date);
      $year = CRM_Utils_Array::value('Y', $date);
    }
    else {
      extract(date_parse($date));
    }
    $date = mktime($hour, $minute, $second, $month, $day, $year);
    switch ($unit) {
      case 'year':
        $date = mktime($hour, $minute, $second, $month, $day, $year + $interval);
        break;

      case 'month':
        $date = mktime($hour, $minute, $second, $month + $interval, $day, $year);
        break;

      case 'week':
        $interval = $interval * 7;
        $date = mktime($hour, $minute, $second, $month, $day + $interval, $year);
        break;

      case 'day':
        $date = mktime($hour, $minute, $second, $month, $day + $interval, $year);
        break;

      case 'second':
        $date = mktime($hour, $minute, $second + $interval, $month, $day, $year);
        break;
    }

    $scheduleDate = explode("-", date("n-j-Y-H-i-s", $date));

    $date = [];
    $date['M'] = $scheduleDate[0];
    $date['d'] = $scheduleDate[1];
    $date['Y'] = $scheduleDate[2];
    if ($dontCareTime == FALSE) {
      $date['H'] = $scheduleDate[3];
      $date['i'] = $scheduleDate[4];
      $date['s'] = $scheduleDate[5];
    }
    return $date;
  }

  /**
   * Check if the given format is valid for birth date and return a supportable format with QF mapping.
   *
   * @param string|null $format Date format string (e.g. 'M Y', 'Y M'); uses birth date config if NULL
   *
   * @return string|array|null The birth date format string, or an array with 'qfMapping' and 'dateParts' keys if supported, or NULL
   */
  public static function checkBirthDateFormat($format = NULL) {
    $birthDateFormat = NULL;
    if (!$format) {
      $birthDateFormat = self::getDateFormat('birth');
    }

    $supportableFormats = [
      'mm/dd' => '%B %E%f',
      'dd-mm' => '%E%f %B',
      'yy-mm' => '%Y %B',
      'M yy' => '%b %Y',
      'yy' => '%Y',
      'dd/mm/yy' => '%E%f %B %Y',
    ];

    if (CRM_Utils_Array::arrayKeyExists($birthDateFormat, $supportableFormats)) {
      $birthDateFormat = ['qfMapping' => $supportableFormats[$birthDateFormat],
        'dateParts' => $formatMapping,
      ];
    }

    return $birthDateFormat;
  }

  /**
   * Resolve a relative time interval into absolute start and end dates.
   *
   * @param string $relativeTerm Relative time frame: 'this', 'previous', 'previous_before', 'previous_2', 'earlier', 'greater', or 'ending'
   * @param string $unit Frequency unit: 'year', 'fiscal_year', 'quarter', 'month', 'week', or 'day'
   *
   * @return array Associative array with 'from' and 'to' keys containing date strings in MySQL compact format, or NULL if unbounded
   */
  public static function relativeToAbsolute($relativeTerm, $unit) {
    $now = getDate();
    $from = $to = $dateRange = [];
    $from['H'] = $from['i'] = $from['s'] = 0;

    switch ($unit) {
      case 'year':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $from['M'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'];
            break;

          case 'previous':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'] - 1;
            break;

          case 'previous_before':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'] - 2;
            break;

          case 'previous_2':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $from['Y'] = $now['year'] - 2;
            $to['Y'] = $now['year'] - 1;
            break;

          case 'earlier':
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $now['year'] - 1;
            unset($from);
            break;

          case 'greater':
            $from['M'] = $from['d'] = 1;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('year', -1, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'fiscal_year':
        $config = CRM_Core_Config::singleton();
        $from['d'] = $config->fiscalYearStart['d'];
        $from['M'] = $config->fiscalYearStart['M'];
        $fYear = self::calculateFiscalYear($from['d'], $from['M']);
        switch ($relativeTerm) {
          case 'this':
            $from['Y'] = $fYear;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $form['d'], $from['Y'] + 1);
            $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));

            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            break;

          case 'previous':
            $from['Y'] = $fYear - 1;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $form['d'], $from['Y'] + 1);
            $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));

            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            break;
        }
        break;

      case 'quarter':
        switch ($relativeTerm) {
          case 'this':

            $quarter = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'];
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, (int)$to['M'], (int)$now['year']);
            break;

          case 'previous':
            $difference = 1;
            $quarter = ceil($now['mon'] / 3);
            $quarter = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, (int)$to['M'], (int)$to['Y']);
            break;

          case 'previous_before':
            $difference = 2;
            $quarter = ceil($now['mon'] / 3);
            $quarter = $quarter - $difference;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
              srst;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, (int)$to['M'], (int)$to['Y']);
            break;

          case 'previous_2':
            $difference = 2;
            $quarter = ceil($now['mon'] / 3);
            $current_quarter = $quarter;
            $quarter = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            switch ($current_quarter) {
              case 1:
                $to['M'] = (4 * $quarter);
                break;

              case 2:
                $to['M'] = (4 * $quarter) + 3;
                break;

              case 3:
                $to['M'] = (4 * $quarter) + 2;
                break;

              case 4:
                $to['M'] = (4 * $quarter) + 1;
                break;
            }
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            if ($to['M'] > 12) {
              $to['M'] = 3 * ($quarter - 3);
              $to['Y'] = $now['year'];
            }
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, (int)$to['M'], (int)$to['Y']);
            break;

          case 'earlier':
            $quarter = ceil($now['mon'] / 3) - 1;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, (int)$to['M'], (int)$to['Y']);
            unset($from);
            break;

          case 'greater':
            $quarter = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('month', -3, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'month':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = 1;
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, $now['mon'], $now['year']);
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            break;

          case 'previous':
            $from['d'] = 1;
            if ($now['mon'] == 1) {
              $from['M'] = $to['M'] = 12;
              $from['Y'] = $to['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $to['M'] = $now['mon'] - 1;
              $from['Y'] = $to['Y'] = $now['year'];
            }
            $to['d'] = cal_days_in_month(CAL_GREGORIAN, $to['M'], $to['Y']);
            break;

          case 'previous_before':
            $from['d'] = 1;
            if ($now['mon'] < 3) {
              $from['M'] = $to['M'] = 10 + $now['mon'];
              $from['Y'] = $to['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $to['M'] = $now['mon'] - 2;
              $from['Y'] = $to['Y'] = $now['year'];
            }

            $to['d'] = cal_days_in_month(CAL_GREGORIAN, $to['M'], $to['Y']);
            break;

          case 'previous_2':
            $from['d'] = 1;
            if ($now['mon'] < 3) {
              $from['M'] = 10 + $now['mon'];
              $from['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $now['mon'] - 2;
              $from['Y'] = $now['year'];
            }

            if ($now['mon'] == 1) {
              $to['M'] = 12;
              $to['Y'] = $now['year'] - 1;
            }
            else {
              $to['M'] = $now['mon'] - 1;
              $to['Y'] = $now['year'];
            }

            $to['d'] = cal_days_in_month(CAL_GREGORIAN, $to['M'], $to['Y']);
            break;

          case 'earlier':
            //before end of past month
            if ($now['mon'] == 1) {
              $to['M'] = 12;
              $to['Y'] = $now['year'] - 1;
            }
            else {
              $to['M'] = $now['mon'] - 1;
              $to['Y'] = $now['year'];
            }

            $to['d'] = cal_days_in_month(CAL_GREGORIAN, $to['M'], $to['Y']);
            unset($from);
            break;

          case 'greater':
            $from['d'] = 1;
            $from['M'] = $now['mon'];
            ;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('month', -1, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'week':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($now['wday']), $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($now['wday']) - 7, $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($now['wday']) - 14, $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_2':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($now['wday']) - 14, $from);
            $to = self::intervalAdd('day', 13, $from);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to = self::intervalAdd('day', -1 * ($now['wday']) - 1, $to);
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($now['wday']), $from);
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('day', -7, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'day':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $to['d'] = $now['mday'];
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            break;

          case 'previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1, $from);
            $to['d'] = $from['d'];
            $to['M'] = $from['M'];
            $to['Y'] = $from['Y'];
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -2, $from);
            $to['d'] = $from['d'];
            $to['M'] = $from['M'];
            $to['Y'] = $from['Y'];
            break;

          case 'previous_2':
            $from['d'] = $to['d'] = $now['mday'];
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            $from = self::intervalAdd('day', -2, $from);
            $to = self::intervalAdd('day', -1, $to);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            ;
            $from['Y'] = $now['year'];
            unset($to);
            break;
        }
        break;
    }

    foreach (['from', 'to'] as $item) {
      if (!empty($$item)) {
        $dateRange[$item] = self::format($$item);
      }
      else {
        $dateRange[$item] = NULL;
      }
    }
    return $dateRange;
  }

  /**
   * Calculate the current fiscal year based on the fiscal start month and day.
   *
   * @param int $fyDate Fiscal year start day of the month
   * @param int $fyMonth Fiscal year start month
   *
   * @return int The current fiscal year as a four-digit integer
   */
  public function calculateFiscalYear($fyDate, $fyMonth) {
    $date = date("Y-m-d");
    $currentYear = date("Y");

    //recalculate the date because month 4::04 make the difference
    $fiscalYear = explode('-', date("Y-m-d", mktime(0, 0, 0, (int)$fyMonth, (int)$fyDate, (int)$currentYear)));
    $fyDate = $fiscalYear[2];
    $fyMonth = $fiscalYear[1];
    $fyStartDate = date("Y-m-d", mktime(0, 0, 0, (int)$fyMonth, (int)$fyDate, (int)$currentYear));

    if ($fyStartDate > $date) {
      $fy = intval(intval($currentYear) - 1);
    }
    else {
      $fy = intval($currentYear);
    }
    return $fy;
  }

  /**
   * Process a date string and optional time string, converting to a specified format.
   *
   * Handles various input date formats including dd/mm/yy, AM/PM time notation,
   * and 24-hour time format.
   *
   * @param string $date Date string to process
   * @param string|null $time Optional time string (e.g. '14:30' or '2:30 PM')
   * @param bool $returnNullString If TRUE, returns the string 'null' when date is empty (for database NULL insertion)
   * @param string $format PHP date() format string for output (default 'YmdHis')
   * @param string|null $inputCustomFormat Custom input format override (e.g. 'dd/mm/yy')
   *
   * @return string|null Formatted date string, 'null' string, or NULL if date is empty
   */
  public static function processDate($date, $time = NULL, $returnNullString = FALSE, $format = 'YmdHis', $inputCustomFormat = NULL) {
    $mysqlDate = NULL;

    if ($returnNullString) {
      $mysqlDate = 'null';
    }

    $config = CRM_Core_Config::singleton();
    $inputFormat = $config->dateInputFormat;

    if (!empty($inputCustomFormat)) {
      $inputFormat = $inputCustomFormat;
    }
    if ($inputFormat == 'dd/mm/yy') {
      $date = str_replace('/', '-', $date);
    }
    if (trim($date)) {
      // Modify when hour is over 12 and use 'pm' (Contains AM, in spite of rarely wrong using.)
      $pregTest = '/(?<pmam>(?:pm)|(?:am))? ?(?<hr>\d{1,2}):(?<min>\d{1,2})(?::(?<sec>\d{1,2}))? ?((?:pm)|(?:am))?$/i';
      if (preg_match($pregTest, $date, $matches)) {
        if (empty($time)) {
          $date = preg_replace($pregTest, '', $date);
          $time = $matches[0];
        }
      }
      if (!empty($time) && preg_match($pregTest, $time, $matches)) {
        if ($matches['hr'] > 12 || !empty($matches['pmam'])) {
          // Ignore PM, AM. Or switch PM, AM to string tail.
          $time = "{$matches['hr']}:{$matches['min']}";
          if ($matches['sec']) {
            $time .= ":{$matches['sec']}";
          }
          if (!($matches['hr'] > 12) && $matches['pmam']) {
            $time .= " {$matches['pmam']}";
          }
        }
      }

      $timeStamp = strtotime($date . ' ' . $time);
      if (empty($timeStamp)) {
        CRM_Core_Session::setStatus(
          ts("%1 has error on format.", [
            1 => ts('Time')
          ]),
          TRUE,
          'error'
        );
      }
      $mysqlDate = date($format, $timeStamp);
    }

    return $mysqlDate;
  }

  /**
   * Convert a MySQL/ISO date string to a date plugin format with separate date and time parts.
   *
   * @param string|null $mysqlDate Date string in MySQL or ISO format; defaults to current date/time if NULL
   * @param string|null $formatType Date preference name (e.g. 'birth', 'activityDate') to look up format
   * @param string|null $format Date format override (e.g. 'mm/dd/yy')
   * @param int|null $timeFormat Time format type (1 = 12-hour, 2 = 24-hour); uses config default if NULL
   *
   * @return array{0: string, 1: string} Array with [0] formatted date string and [1] formatted time string
   */
  public static function setDateDefaults($mysqlDate = NULL, $formatType = NULL, $format = NULL, $timeFormat = NULL) {
    // if date is not passed assume it as today
    if (!$mysqlDate) {
      $mysqlDate = date('Y-m-d G:i:s');
    }

    $config = CRM_Core_Config::singleton();
    if ($formatType) {
      // get actual format
      $params = ['name' => $formatType];
      $values = [];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);

      if ($values['date_format']) {
        $format = $values['date_format'];
      }

      if ($values['time_format']) {
        $timeFormat = $values['time_format'];
      }
    }

    if (!$format) {
      $format = $config->dateInputFormat;
    }

    // get actual format
    $actualPHPFormats = CRM_Core_SelectValues::datePluginToPHPFormats();
    $dateFormat = $actualPHPFormats[$format];

    $date = date($dateFormat, strtotime($mysqlDate));

    if (!$timeFormat) {
      $timeFormat = $config->timeInputFormat;
    }

    $actualTimeFormat = "g:iA";
    $appendZeroLength = 7;
    if ($timeFormat > 1) {
      $actualTimeFormat = "G:i";
      $appendZeroLength = 5;
    }

    $time = date($actualTimeFormat, strtotime($mysqlDate));

    // need to append zero for hours < 10
    if (strlen($time) < $appendZeroLength) {
      $time = '0' . $time;
    }

    return [$date, $time];
  }

  /**
   * Get the date input format for a given format type.
   *
   * Falls back to the global dateInputFormat config if the format type has no specific format.
   *
   * @param string|null $formatType Date preference name (e.g. 'birth', 'activityDate')
   *
   * @return string Date format string (e.g. 'mm/dd/yy', 'yy-mm-dd')
   */
  public static function getDateFormat($formatType = NULL) {
    $format = NULL;
    if ($formatType) {
      $format = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_PreferencesDate',
        $formatType,
        'date_format',
        'name'
      );
    }

    if (!$format) {
      $config = CRM_Core_Config::singleton();
      $format = $config->dateInputFormat;
    }
    return $format;
  }
  /**
   * Get the time in UTC for the current time. You can optionally send an offset from the current time if needed
   *
   * @param $offset int the offset from the current time in seconds
   *
   * @return the time in UTC
   * @static
   * @public
   */
  public static function getUTCTime($offset = 0) {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $time = time() + $offset;
    $now = date('YmdHis', $time);
    date_default_timezone_set($originalTimezone);
    return $now;
  }

  /**
   * Parse date filter
   *
   * @param string $dateFilter Date filter string
   * @return array Array containing start date, end date, total days and total months
   */
  public static function strtodate(string $dateFilter): array {
    $today = new DateTime();
    $startDate = NULL;
    $endDate = NULL;

    // Parse `last/next N days/weeks/months/years to today/yesterday`
    if (preg_match('/^(last|next) (\d+) (days|weeks|months|years) to (today|yesterday)$/', strtolower($dateFilter), $matches)) {
      $direction = $matches[1]; // last or next
      $amount = (int)$matches[2]; // number
      $unit = $matches[3]; // time unit
      $endType = $matches[4]; // today or yesterday

      $endReference = ($endType === 'yesterday') ? (clone $today)->modify('-1 day') : $today;

      if ($direction === 'last') {
        $startDate = (clone $endReference)->modify("-{$amount} {$unit}")->format('Y-m-d');
        $endDate = $endReference->format('Y-m-d');

        // Adjust start date by +1 day
        $startDate = (new DateTime($startDate))->modify('+1 day')->format('Y-m-d');
      }
      else {
        $startDate = $endReference->format('Y-m-d');
        $endDate = (clone $endReference)->modify("+{$amount} {$unit}")->format('Y-m-d');
      }
    }
    // Parse `last/next N days/weeks/months/years` (without `to today`)
    elseif (preg_match('/^(last|next) (\d+) (days|weeks|months|years)$/', strtolower($dateFilter), $matches)) {
      $direction = $matches[1];
      $amount = (int)$matches[2];
      $unit = $matches[3];

      if ($direction === 'last') {
        $startDate = (clone $today)->modify("-{$amount} {$unit}")->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
      }
      else {
        $startDate = $today->format('Y-m-d');
        $endDate = (clone $today)->modify("+{$amount} {$unit}")->format('Y-m-d');
      }
    }
    // Parse `this week`, `last month`, `this year`, `last year`
    elseif (preg_match('/^(this|last) (week|month|year)$/', strtolower($dateFilter), $matches)) {
      $modifier = $matches[1]; // this or last
      $unit = $matches[2]; // week, month, year

      if ($unit === 'week') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('monday this week')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        }
        else {
          $startDate = (clone $today)->modify('monday last week')->format('Y-m-d');
          $endDate = (clone $today)->modify('sunday last week')->format('Y-m-d');
        }
      }
      elseif ($unit === 'month') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('first day of this month')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        }
        else {
          $startDate = (clone $today)->modify('first day of last month')->format('Y-m-d');
          $endDate = (clone $today)->modify('last day of last month')->format('Y-m-d');
        }
      }
      elseif ($unit === 'year') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('first day of January this year')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        }
        else {
          $startDate = (clone $today)->modify('first day of January last year')->format('Y-m-d');
          $endDate = (clone $today)->modify('last day of December last year')->format('Y-m-d');
        }
      }
    }
    // Special fixed values
    elseif ($dateFilter === 'today') {
      $startDate = $today->format('Y-m-d');
      $endDate = $today->format('Y-m-d');
    }
    elseif ($dateFilter === 'yesterday') {
      $startDate = (clone $today)->modify('-1 day')->format('Y-m-d');
      $endDate = $startDate;
    }
    // Parse single day yyyy-mm-dd
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
      $startDate = $dateFilter;
      $endDate = $dateFilter;
    }
    // Parse range yyyy-mm-dd_to_yyyy-mm-dd
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
      list($startDate, $endDate) = explode('_to_', $dateFilter);
    }

    $startDateTime = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $interval = $startDateTime->diff($endDateTime);
    $totalDays = $interval->days + 1;
    $totalMonths = ($interval->y * 12) + $interval->m;
    if ($interval->d > 0) {
      $totalMonths++;
    }

    return [
      'start' => $startDate,
      'end' => $endDate,
      'day' => $totalDays,
      'month' => $totalMonths,
    ];
  }
}
