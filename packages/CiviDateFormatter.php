<?php
/**
 * Locale-aware POSIX date formatting for PHP 7.3 and later.
 *
 * Independent of CiviCRM bootstrap so bundled packages can use it directly.
 * Unix uses LC_TIME data from libc; platforms without nl_langinfo require
 * ext-intl for non-C locales. ICU locale data can differ from libc data.
 */
class CiviDateFormatter {

  public static function strftime($format, $timestamp = NULL, $gmt = FALSE) {
    if ($timestamp === NULL) {
      $timestamp = func_num_args() > 1 && PHP_VERSION_ID < 80000 ? 0 : time();
    }
    $date = (new DateTimeImmutable('@' . (int) $timestamp))
      ->setTimezone(new DateTimeZone($gmt ? 'GMT' : date_default_timezone_get()));
    return self::format((string) $format, $date);
  }

  private static function format($format, DateTimeImmutable $date, $depth = 0) {
    if ($depth > 8) {
      throw new InvalidArgumentException('Recursive LC_TIME date format');
    }
    if ($format === '') {
      return FALSE;
    }
    // Consume escaped percent signs together, before recognizing directives.
    return preg_replace_callback('/%%|%([_0^#-]*)([0-9]*)([EO]?)([%a-zA-Z])/', function($match) use ($date, $depth) {
      if ($match[0] === '%%') {
        return '%';
      }
      list(, $flags, $width, $modifier, $token) = $match;
      if (strpos('aAbBcCdDeFgGhHIjklmMnPprRsStTuUVwWxXyYzZ%', $token) === FALSE) {
        return $match[0];
      }
      $value = self::modifiedToken($token, $modifier, $date, $depth);
      if (strpos($flags, '^') !== FALSE) {
        $value = strtoupper($value);
      }
      if (strpos($flags, '#') !== FALSE) {
        $value = in_array($token, ['p', 'P', 'Z']) ? strtolower($value) : strtoupper($value);
      }
      $numeric = strpos('CdegGHIjklmMSuUVwWyY', $token) !== FALSE;
      if ($numeric && strpos($flags, '-') !== FALSE) {
        $value = (string) (int) $value;
      }
      elseif ($width !== '' || ($numeric && strpbrk($flags, '_0') !== FALSE)) {
        $size = $width === '' ? strlen($value) : min((int) $width, 4096);
        $pad = strpos($flags, '_') !== FALSE ? ' ' : ($numeric ? '0' : ' ');
        if (strpos($flags, '0') !== FALSE) {
          $pad = '0';
        }
        if ($numeric) {
          $value = (string) (int) $value;
        }
        $value = str_pad($value, $size, $pad, STR_PAD_LEFT);
      }
      return $value;
    }, $format);
  }

  private static function modifiedToken($token, $modifier, DateTimeImmutable $date, $depth) {
    if ($modifier === 'E') {
      $formats = ['c' => 'ERA_D_T_FMT', 'x' => 'ERA_D_FMT', 'X' => 'ERA_T_FMT'];
      if (isset($formats[$token])) {
        $format = self::info($formats[$token]);
        if ($format !== '') {
          return self::format($format, $date, $depth + 1);
        }
      }
      if (in_array($token, ['C', 'y', 'Y'])) {
        foreach (explode(';', self::info('ERA')) as $era) {
          $parts = explode(':', $era, 6);
          if (count($parts) !== 6) {
            continue;
          }
          list($direction, $offset, $start, $end, $name, $format) = $parts;
          $start = str_replace('/', '-', $start);
          $end = str_replace('/', '-', $end);
          $day = $date->format('Y-m-d');
          if (($end === '+*' && $day >= $start) ||
              ($end === '-*' && $day <= $start) ||
              ($end !== '+*' && $end !== '-*' && $day >= min($start, $end) && $day <= max($start, $end))) {
            $year = (int) $offset + ($direction === '+' ? 1 : -1) *
              ($end === '-*' ? -1 : 1) *
              ((int) $date->format('Y') - (int) substr($start, 0, 4));
            if ($token === 'C') {
              return $name;
            }
            if ($token === 'y') {
              return sprintf('%02d', $year);
            }
            return self::format(str_replace(['%EC', '%Ey'], [$name, sprintf('%02d', $year)], $format), $date, $depth + 1);
          }
        }
      }
    }
    $value = self::token($token, $date, $depth);
    if ($modifier === 'O' && ctype_digit(trim($value))) {
      $digits = explode(';', self::info('ALT_DIGITS'));
      $number = (int) $value;
      if (isset($digits[$number]) && $digits[$number] !== '') {
        return $digits[$number];
      }
    }
    return $value;
  }

  private static function token($token, DateTimeImmutable $date, $depth) {
    switch ($token) {
      case '%':
        return '%';

      case 'a':
        $weekday = (int) $date->format('w');
        return self::localeValue('ABDAY_' . ($weekday + 1), $date->format('D'));

      case 'A':
        $weekday = (int) $date->format('w');
        return self::localeValue('DAY_' . ($weekday + 1), $date->format('l'));

      case 'b':
      case 'h':
        $month = (int) $date->format('n');
        return self::localeValue('ABMON_' . $month, $date->format('M'));

      case 'B':
        $month = (int) $date->format('n');
        return self::localeValue('MON_' . $month, $date->format('F'));

      case 'c':
        return self::format(
          self::localeValue('D_T_FMT', '%a %b %e %H:%M:%S %Y'),
          $date, $depth + 1
        );

      case 'C':
        return sprintf('%02d', (int) floor(((int) $date->format('Y')) / 100));

      case 'd':
        return $date->format('d');

      case 'D':
        return $date->format('m/d/y');

      case 'e':
        return sprintf('%2d', (int) $date->format('j'));

      case 'F':
        return $date->format('Y-m-d');

      case 'g':
        return substr($date->format('o'), -2);

      case 'G':
        return $date->format('o');

      case 'H':
        return $date->format('H');

      case 'I':
        return $date->format('h');

      case 'j':
        return sprintf('%03d', ((int) $date->format('z')) + 1);

      case 'k':
        return sprintf('%2d', (int) $date->format('G'));

      case 'l':
        return sprintf('%2d', (int) $date->format('g'));

      case 'm':
        return $date->format('m');

      case 'M':
        return $date->format('i');

      case 'n':
        return "\n";

      case 'p':
        return self::localeValue(
          ((int) $date->format('G')) < 12 ? 'AM_STR' : 'PM_STR',
          $date->format('A')
        );

      case 'P':
        $period = self::token('p', $date, $depth);
        return strtolower($period);

      case 'r':
        return self::format(
          self::localeValue('T_FMT_AMPM', '%I:%M:%S %p'),
          $date, $depth + 1
        );

      case 'R':
        return $date->format('H:i');

      case 's':
        return $date->format('U');

      case 'S':
        return $date->format('s');

      case 't':
        return "\t";

      case 'T':
        return $date->format('H:i:s');

      case 'u':
        return $date->format('N');

      case 'U':
        $dayOfYear = (int) $date->format('z');
        $weekday = (int) $date->format('w');
        return sprintf('%02d', (int) floor(($dayOfYear + 7 - $weekday) / 7));

      case 'V':
        return $date->format('W');

      case 'w':
        return $date->format('w');

      case 'W':
        $dayOfYear = (int) $date->format('z');
        $weekday = (int) $date->format('w');
        $mondayBasedWeekday = ($weekday + 6) % 7;
        return sprintf('%02d', (int) floor(($dayOfYear + 7 - $mondayBasedWeekday) / 7));

      case 'x':
        return self::format(
          self::localeValue('D_FMT', '%m/%d/%y'),
          $date, $depth + 1
        );

      case 'X':
        return self::format(
          self::localeValue('T_FMT', '%H:%M:%S'),
          $date, $depth + 1
        );

      case 'y':
        return $date->format('y');

      case 'Y':
        return $date->format('Y');

      case 'z':
        return $date->format('O');

      case 'Z':
        return $date->format('T');

      default:
        return '%' . $token;
    }
  }


  private static function info($name) {
    // PHP exposes only the first NUL-terminated ERA / ALT_DIGITS entry.
    // Use complete reference data for the bundled locales.
    if (in_array($name, ['ERA', 'ALT_DIGITS']) || !function_exists('nl_langinfo')) {
      $value = self::bundledLocaleValue($name);
      if ($value !== NULL) {
        return $value;
      }
    }
    if (function_exists('nl_langinfo') && defined($name)) {
      $value = nl_langinfo(constant($name));
      return $value === FALSE ? '' : $value;
    }
    return '';
  }

  private static function bundledLocaleValue($name) {
    static $locales;
    if ($locales === NULL) {
      $locales = require __DIR__ . '/CiviDateFormatter/locales.php';
    }
    $locale = setlocale(LC_TIME, 0);
    $parts = explode('.', $locale, 2);
    $aliases = ['English_United States' => 'en_US', 'Chinese_Taiwan' => 'zh_TW',
      'Chinese (Traditional)_Taiwan' => 'zh_TW', 'Japanese_Japan' => 'ja_JP'];
    $key = $aliases[$parts[0]] ?? str_replace('-', '_', $parts[0]);
    if (!isset($locales[$key])) {
      return NULL;
    }
    $data = $locales[$key];
    if (preg_match('/^(ABDAY|DAY|ABMON|MON)_([0-9]+)$/', $name, $match)) {
      $values = explode(';', $data[strtolower($match[1])]);
      $value = $values[(int) $match[2] - 1];
    }
    elseif ($name === 'AM_STR' || $name === 'PM_STR') {
      $value = explode(';', $data['am_pm'])[$name === 'AM_STR' ? 0 : 1];
    }
    else {
      $value = $data[strtolower($name)] ?? NULL;
    }
    if ($value === NULL || $value === '') {
      return $value;
    }
    $charset = self::infoCharset($parts);
    if (!in_array(strtolower($charset), ['utf8', 'utf-8', '65001'])) {
      if (!function_exists('iconv')) {
        throw new RuntimeException('LC_TIME character conversion requires iconv');
      }
      $value = iconv('UTF-8', $charset, $value);
      if ($value === FALSE) {
        throw new RuntimeException('Unable to encode LC_TIME locale ' . $locale);
      }
    }
    return $value;
  }

  private static function infoCharset($parts) {
    if (isset($parts[1])) {
      return ctype_digit($parts[1]) ? 'CP' . $parts[1] : $parts[1];
    }
    // CODESET describes LC_CTYPE, not LC_TIME, so it must not be used here.
    return 'UTF-8';
  }

  private static function localeValue($name, $fallback) {
    $value = self::info($name);
    if ($value !== '') {
      return $value;
    }
    if (function_exists('nl_langinfo')) {
      return $fallback;
    }
    $locale = setlocale(LC_TIME, 0);
    if (in_array(strtolower($locale), ['c', 'posix', 'c.utf8', 'c.utf-8'])) {
      return $fallback;
    }
    if (!class_exists('IntlDateFormatter')) {
      throw new RuntimeException('Non-C LC_TIME formatting requires nl_langinfo or ext-intl');
    }
    // Windows locale names may use English language/territory names.
    $localeParts = explode('.', $locale, 2);
    $icuLocale = str_replace('-', '_', $localeParts[0]);
    if (preg_match('/^[A-Za-z]{3,}_/', $icuLocale)) {
      foreach (ResourceBundle::getLocales('') as $candidate) {
        $display = Locale::getDisplayLanguage($candidate, 'en') . '_' . Locale::getDisplayRegion($candidate, 'en');
        if (strcasecmp($display, $icuLocale) === 0) {
          $icuLocale = $candidate;
          break;
        }
      }
    }
    $patterns = ['AM_STR' => 'a', 'PM_STR' => 'a'];
    $stamp = gmmktime($name === 'PM_STR' ? 13 : 1, 0, 0, 1, 1, 2023);
    if (preg_match('/^(ABDAY|DAY|ABMON|MON)_([0-9]+)$/', $name, $match)) {
      $patterns[$name] = ['ABDAY' => 'EEE', 'DAY' => 'EEEE', 'ABMON' => 'MMM', 'MON' => 'MMMM'][$match[1]];
      $stamp = strpos($match[1], 'DAY') !== FALSE
        ? gmmktime(0, 0, 0, 1, (int) $match[2], 2023)
        : gmmktime(0, 0, 0, (int) $match[2], 1, 2023);
    }
    if (!isset($patterns[$name])) {
      return $fallback;
    }
    $formatter = new IntlDateFormatter($icuLocale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', IntlDateFormatter::GREGORIAN, $patterns[$name]);
    $value = $formatter->format($stamp);
    if ($value === FALSE || $value === '') {
      throw new RuntimeException('Unable to format LC_TIME locale ' . $locale);
    }
    if (isset($localeParts[1]) && !in_array(strtolower($localeParts[1]), ['utf8', 'utf-8', '65001'])) {
      $charset = ctype_digit($localeParts[1]) ? 'CP' . $localeParts[1] : $localeParts[1];
      if (!function_exists('iconv')) {
        throw new RuntimeException('LC_TIME character conversion requires iconv');
      }
      $value = iconv('UTF-8', $charset, $value);
      if ($value === FALSE) {
        throw new RuntimeException('Unable to encode LC_TIME locale ' . $locale);
      }
    }
    return $value;
  }
}
