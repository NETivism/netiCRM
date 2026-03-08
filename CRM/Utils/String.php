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
 * Utility class for string manipulation, encoding detection,
 * transliteration, masking, and URL handling.
 */
class CRM_Utils_String {
  public const COMMA = ",", SEMICOLON = ";", SPACE = " ", TAB = "\t", LINEFEED = "\n", CARRIAGELINE = "\r\n", LINECARRIAGE = "\n\r", CARRIAGERETURN = "\r", MASK = '*';

  /**
   * List of all letters and numbers
   */
  public const ALPHANUMERIC = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

  /**
   * Allowed HTML Tags
   */
  public const ALLOWED_TAGS = [
    'div[style]','b','strong','i','em','s','a[href|title]',
    'ul','ol','li','p[style]','blockquote','br','span[style]',
    'img[width|height|alt|src|style]','figure[class|style]',
    'figcaption','table[border|cellpadding|cellspacing|style]',
    'thead','tbody','tr','td[style]','th[style]','hr',
    'iframe[allow|allowfullscreen|frameborder|src|height|title|width]',
  ];

  /**
   * Convert a display name into a safe variable name for use in forms or code.
   *
   * If the munged title does not pass validation, returns a truncated MD5 hash instead.
   *
   * @param string $title the display name to convert
   * @param int $maxLength maximum length of the resulting variable name
   *
   * @return string the variable-safe name
   */
  public static function titleToVar($title, $maxLength = 31) {
    $variable = self::munge($title, '_', $maxLength);

    if (CRM_Utils_Rule::title($variable, $maxLength)) {
      return $variable;
    }

    // if longer than the maxLength lets just return a substr of the
    // md5 to prevent errors downstream
    return substr(md5($title), 0, $maxLength);
  }

  /**
   * Replace all non-alphanumeric characters and spaces with a replacement character.
   *
   * For strings containing non-ASCII characters (e.g. CJK), transliteration
   * is applied before munging.
   *
   * @param string $name the string to be munged
   * @param string $char the replacement character for non-valid characters
   * @param int $len maximum length of the result (0 for unlimited)
   *
   * @return string the munged string
   */
  public static function munge($name, $char = '_', $len = 63) {
    // replace all white space and non-alpha numeric with $char
    $mungedName = '';
    $name = preg_replace('/[-.]+/', $char, $name);

    // dirty way to detect non-english character
    preg_match('/[^0-9a-z-_]+/i', $name, $matches);

    // any chinese appear, should go transliteration (to prevent duplication)
    if (!empty($matches) && trim($matches[0])) {
      $mungedName = self::transliteration($name);
    }

    if (empty($mungedName)) {
      $mungedName = preg_replace('/\s+|\W+|[-_]+/', $char, trim($name));
    }
    $mungedName = preg_replace('/[-.]+/', $char, $mungedName); // prevent transliteration convert dash

    if ($len) {
      // lets keep variable names short
      return substr($mungedName, 0, $len);
    }
    else {
      return $mungedName;
    }
  }

  /**
   * Replace the last N characters of a variable name with a random string.
   *
   * @param string $name the original variable name
   * @param int $len the number of trailing characters to replace with random characters
   *
   * @return string the randomized variable name
   */
  public static function rename($name, $len = 4) {
    $rand = substr(uniqid(), 0, $len);
    return substr_replace($name, $rand, -$len, $len);
  }

  /**
   * Return the last segment of a delimited string.
   *
   * Useful for extracting a class name from a fully qualified name
   * (e.g. 'CRM_Core_Form' returns 'Form').
   *
   * @param string $string the input string
   * @param string $char the delimiter character
   *
   * @return string|null the last segment, or NULL if the input is an array
   */
  public static function getClassName($string, $char = '_') {
    if (!is_array($string)) {
      $names = explode($char, $string);
    }
    if (is_array($names)) {
      return array_pop($names);
    }
  }

  /**
   * Append a value (or array of values) to a string using a delimiter.
   *
   * Handles the case where the target string is initially empty,
   * avoiding a leading delimiter.
   *
   * @param string $str the string to be appended to (passed by reference)
   * @param string $delim the delimiter to insert between segments
   * @param string|string[] $name the string or array of strings to append
   *
   * @return void
   */
  public static function append(&$str, $delim, $name) {
    if (empty($name)) {
      return;
    }

    if (is_array($name)) {
      foreach ($name as $n) {
        if (empty($n)) {
          continue;
        }
        if (empty($str)) {
          $str = $n;
        }
        else {
          $str .= $delim . $n;
        }
      }
    }
    else {
      if (empty($str)) {
        $str = $name;
      }
      else {
        $str .= $delim . $name;
      }
    }
  }

  /**
   * Determine if a string is composed only of ASCII (or optionally UTF-8) characters.
   *
   * @param string $str the input string to check
   * @param bool $utf8 if TRUE, also accept valid UTF-8 strings
   *
   * @return bool TRUE if the string is ASCII (or valid UTF-8 when allowed)
   */
  public static function isAscii($str, $utf8 = TRUE) {
    if (!function_exists('mb_detect_encoding')) {
      // eliminate all white space from the string
      $str = preg_replace('/\s+/', '', $str);
      /* FIXME:  This is a pretty brutal hack to make utf8 and 8859-1 work.
             */

      /* match low- or high-ascii characters */

      if (preg_match('/[\x00-\x20]|[\x7F-\xFF]/', $str)) {
        // || // low ascii characters
        // high ascii characters
        //  preg_match( '/[\x7F-\xFF]/', $str ) ) {
        if ($utf8) {
          /* if we did match, try for utf-8, or iso8859-1 */

          return self::isUtf8($str);
        }
        else {
          return FALSE;
        }
      }
      return TRUE;
    }
    else {
      $order = ['ASCII'];
      if ($utf8) {
        $order[] = 'UTF-8';
      }
      $enc = mb_detect_encoding($str, $order, TRUE);
      return ($enc == 'ASCII' || $enc == 'UTF-8');
    }
  }

  /**
   * Determine string replacements for redaction based on regular expressions.
   *
   * Matches the input string against the given regex rules and builds a
   * mapping of matched strings to their redacted replacement values.
   *
   * @param string $str the input string to scan for matches
   * @param array $regexRules associative array of regex patterns to replacement prefixes
   *
   * @return array associative array mapping matched strings to redacted replacements
   */
  public static function regex($str, $regexRules) {
    //redact the regular expressions
    if (!empty($regexRules) && isset($str)) {
      static $matches, $totalMatches, $match = [];
      foreach ($regexRules as $pattern => $replacement) {
        preg_match_all($pattern, $str, $matches);
        if (!empty($matches[0])) {
          if (empty($totalMatches)) {
            $totalMatches = $matches[0];
          }
          else {
            $totalMatches = array_merge($totalMatches, $matches[0]);
          }
          $match = array_flip($totalMatches);
        }
      }
    }

    if (!empty($match)) {
      foreach ($match as $matchKey => &$dontCare) {
        foreach ($regexRules as $pattern => $replacement) {
          if (preg_match($pattern, $matchKey)) {
            $dontCare = $replacement . substr(md5($matchKey), 0, 5);
            break;
          }
        }
      }
      return $match;
    }
    return CRM_Core_DAO::$_nullArray;
  }

  /**
   * Apply string-based redaction rules to the input text.
   *
   * Performs case-insensitive replacement of matched strings.
   *
   * @param string $str the input string to redact
   * @param array $stringRules associative array mapping strings to their redacted replacements
   *
   * @return string the redacted string
   */
  public static function redaction($str, $stringRules) {
    //redact the strings
    if (!empty($stringRules)) {
      foreach ($stringRules as $match => $replace) {
        $str = str_ireplace($match, $replace, $str);
      }
    }

    //return the redacted output
    return $str;
  }

  /**
   * Determine if a string is composed only of UTF-8 characters.
   *
   * @param string $str the input string to check
   *
   * @return bool TRUE if the string is valid UTF-8
   */
  public static function isUtf8($str) {
    if (!function_exists('mb_detect_encoding')) {
      // eliminate all white space from the string
      $str = preg_replace('/\s+/', '', $str);

      /* pattern stolen from the php.net function documentation for
             * utf8decode();
             * comment by JF Sebastian, 30-Mar-2005
             */

      return preg_match('/^([\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|[\xee-\xef][\x80-\xbf]{2}|f0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})*$/', $str);
      // ||
      // iconv('ISO-8859-1', 'UTF-8', $str);
    }
    else {
      $enc = mb_detect_encoding($str, ['UTF-8'], TRUE);
      return ($enc !== FALSE);
    }
  }

  /**
   * Determine if two URLs are equivalent (fuzzy match).
   *
   * Compares the path and the CiviCRM-specific query parameter.
   *
   * @param string $url1 the first URL to be matched
   * @param string $url2 the second URL to be matched against
   *
   * @return bool TRUE if the URLs match
   */
  public static function match($url1, $url2) {
    $url1 = strtolower($url1);
    $url2 = strtolower($url2);

    $url1Str = parse_url($url1);
    $url2Str = parse_url($url2);

    if ($url1Str['path'] == $url2Str['path'] &&
      self::extractURLVarValue(CRM_Utils_Array::value('query', $url1Str)) == self::extractURLVarValue(CRM_Utils_Array::value('query', $url2Str))
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Extract the value of the framework-specific URL variable from a query string.
   *
   * @param string $query the URL query string
   *
   * @return string|null the value of the URL variable, or NULL if not found
   */
  public static function extractURLVarValue($query) {
    $config = CRM_Core_Config::singleton();
    $urlVar = $config->userFrameworkURLVar;

    $params = explode('&', $query);
    foreach ($params as $p) {
      if (strpos($p, '=')) {
        list($k, $v) = explode('=', $p);
        if ($k == $urlVar) {
          return $v;
        }
      }
    }
    return NULL;
  }

  /**
   * Translate a boolean-like string (true/yes/1) to a boolean value.
   *
   * @param string $str the string to be translated
   *
   * @return bool TRUE if the string represents a true value, otherwise FALSE
   */
  public static function strtobool($str) {
    if (preg_match('/^(y(es)?|t(rue)?|1)$/i', $str)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Translate a boolean-like string to '1' or '0', or FALSE if not a boolean-like string.
   *
   * Supports localized 'Yes'/'No' as well as common boolean strings.
   *
   * @param string $str the string to be translated
   *
   * @return string|false '1' for true-like, '0' for false-like, FALSE otherwise
   */
  public static function strtoboolstr($str) {
    $tsstr = ts($str);
    if ($tsstr == ts('Yes')) {
      return '1';
    }
    elseif ($tsstr == ts('No')) {
      return '0';
    }
    elseif (preg_match('/^(y(es)?|t(rue)?|1)$/i', $str)) {
      return '1';
    }
    elseif (preg_match('/^(n(o)?|f(alse)?|0)$/i', $str)) {
      return '0';
    }
    else {
      return FALSE;
    }
  }

  /**
   * Filter a string to prevent XSS attacks.
   *
   * @param string $str the string to be filtered
   * @param bool $decodeFirst whether to URL-decode the string before filtering
   *
   * @return string the filtered string
   */
  public static function xssFilter($str, $decodeFirst = FALSE) {
    if ($decodeFirst) {
      $str = urldecode($str);
    }
    return filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  }

  /**
   * Convert an HTML string into plain text.
   *
   * @param string $html the HTML string to be converted
   *
   * @return string the converted plain text
   */
  public static function htmlToText($html) {
    require_once 'packages/html2text/rcube_html2text.php';
    $converter = new rcube_html2text($html, FALSE, TRUE, 0);
    return $converter->get_text();
  }

  /**
   * Purify HTML content to remove unsafe tags and attributes.
   *
   * @param string $html the HTML content to purify
   * @param string[] $allowedTags list of allowed HTML tags
   *
   * @return string the purified HTML
   */
  public static function htmlPurifier($html, $allowedTags = []) {
    require_once 'packages/IDS/vendors/htmlpurifier/HTMLPurifier.auto.php';
    static $_purifier;
    $hash = md5(CRM_Utils_Array::implode(',', $allowedTags));

    if (!$_purifier[$hash]) {
      $config = CRM_Core_Config::singleton();

      // general setting
      $purifierConfig = HTMLPurifier_Config::createDefault();
      $purifierConfig->set('Cache.SerializerPath', $config->templateCompileDir);
      $purifierConfig->set('HTML.DefinitionID', 'civicrm-htmlpurifier-figure');
      $purifierConfig->set('HTML.DefinitionRev', 2);
      $purifierConfig->set('Output.Newline', "\n");
      $purifierConfig->set('Core.Encoding', 'UTF-8');

      // iframe
      $purifierConfig->set('HTML.SafeIframe', TRUE);
      $purifierConfig->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'); //allow YouTube and Vimeo

      // allowed tags put at the end
      $allowed = implode(', ', $allowedTags);
      $purifierConfig->set('HTML.Allowed', $allowed);

      // def needs after configure
      // fullscreen
      $def = $purifierConfig->getHTMLDefinition();
      $def->addAttribute('iframe', 'allowfullscreen', 'Bool');

      // figure / figcaption
      $def->addElement('figcaption', 'Block', 'Flow', 'Common');
      $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
      $def->addAttribute('figure', 'style', 'Text');

      $_purifier[$hash] = new HTMLPurifier($purifierConfig);
    }
    return $_purifier[$hash]->purify($html);
  }

  /**
   * Extract name components (first, middle, last) from a single string.
   *
   * @param string $string the full name string
   * @param array $params the array to store extracted name components (passed by reference)
   *
   * @return void
   */
  public static function extractName($string, &$params) {
    $name = trim($string);
    if (empty($name)) {
      return;
    }

    $names = explode(' ', $name);
    if (count($names) == 1) {
      $params['first_name'] = $names[0];
    }
    elseif (count($names) == 2) {
      $params['first_name'] = $names[0];
      $params['last_name'] = $names[1];
    }
    else {
      $params['first_name'] = $names[0];
      $params['middle_name'] = $names[1];
      $params['last_name'] = $names[2];
    }
  }

  /**
   * Parse a string of key=value pairs separated by newlines into an associative array.
   *
   * @param string $string the input string to parse
   *
   * @return array associative array of parsed values
   */
  public static function &makeArray($string) {
    $string = trim($string);

    $values = explode("\n", $string);
    $result = [];
    foreach ($values as $value) {
      list($n, $v) = CRM_Utils_System::explode('=', $value, 2);
      if (!empty($v)) {
        $result[trim($n)] = trim($v);
      }
    }
    return $result;
  }

  /**
   * Prepend jQuery and related CSS files to an HTML string.
   *
   * @param string $html the HTML content to prepend files to (passed by reference)
   *
   * @return string the resulting HTML string with jQuery files prepended
   */
  public static function addJqueryFiles(&$html) {
    $config = CRM_Core_Config::singleton();
    $smarty = CRM_Core_Smarty::singleton();
    $buffer = $smarty->fetch('CRM/common/jquery.files.tpl');
    $lines  = preg_split('/\s+/', $buffer);
    $jquery = '';
    $css = '';
    foreach ($lines as $line) {
      if (strpos($line, '.js') !== FALSE) {
        $jquery .= '<script type="text/javascript" src="'.$config->resourceBase.$line.'"></script>'."\n";
      }
      elseif (strpos($line, '.css') !== FALSE) {
        $css .= '<link crossorigin="anonymous" media="all" rel="stylesheet" href="'.$config->resourceBase.$line.'" itemprop="url" />';
      }
    }
    $jquery .= '<script type="text/javascript">var cj = jQuery.noConflict(); $ = cj;</script>'."\n";
    return $css.$jquery.$html;
  }

  /**
   * Extract the first alternative from a string containing multiple alternatives.
   *
   * Used for parsing text with alternatives (e.g., from ezComponents).
   *
   * @param string $full the string containing alternatives
   *
   * @return string the first alternative, or the original string if no alternatives found
   */
  public static function stripAlternatives($full) {
    $matches = [];
    preg_match('/-ALTERNATIVE ITEM 0-(.*?)-ALTERNATIVE ITEM 1-.*-ALTERNATIVE END-/s', $full, $matches);

    if (trim(strip_tags($matches[1])) != '') {
      return $matches[1];
    }
    else {
      return $full;
    }
  }

  /**
   * Strip leading, trailing, and consecutive spaces from a string.
   *
   * @param string $string the input string to be cleaned
   *
   * @return string the cleaned string
   */
  public static function stripSpaces($string) {
    if (empty($string)) {
      return $string;
    }

    $pat = [0 => "/^\s+/",
      1 => "/\s{2,}/",
      2 => "/\s+\$/",
    ];

    $rep = [0 => "",
      1 => " ",
      2 => "",
    ];

    return preg_replace($pat, $rep, $string);
  }

  /**
   * Generate a random string of a specified length.
   *
   * @param int $len the length of the string to generate
   * @param string $alphabet the set of characters to use for generation
   *
   * @return string the generated random string
   */
  public static function createRandom($len, $alphabet = self::ALPHANUMERIC) {
    $alphabetSize = strlen($alphabet);
    $result = '';
    for ($i = 0; $i < $len; $i++) {
      $result .= $alphabet[mt_rand(1, $alphabetSize) - 1];
    }
    return $result;
  }

  /**
   * Sanitize a URL path string by replacing reserved characters.
   *
   * @param string $string the input string to be sanitized
   * @param array|null $search the characters to search for (defaults to a predefined set)
   * @param string|null $replace the character to replace matches with (defaults to '_')
   *
   * @return string the sanitized string
   */
  public static function stripPathChars(
    $string,
    $search = NULL,
    $replace = NULL
  ) {
    static $_searchChars = NULL;
    static $_replaceChar = NULL;

    if (empty($string)) {
      return $string;
    }

    if ($_searchChars == NULL) {
      $_searchChars = [
        '&', ';', ',', '=', '$',
        '"', "'", '\\',
        '<', '>', '(', ')',
        ' ', "\r", "\r\n", "\n", "\t",
      ];
      $_replaceChar = '_';
    }

    if ($search == NULL) {
      $search = $_searchChars;
    }

    if ($replace == NULL) {
      $replace = $_replaceChar;
    }

    return str_replace($search, $replace, $string);
  }

  /**
   * Remove height-related attributes and styles from <img> tags in HTML.
   *
   * @param string $html the HTML content to process
   *
   * @return string the processed HTML
   */
  public static function removeImageHeight($html) {
    $html = preg_replace('/(<img[^>]+)(line-height\s*:[^;]+;)([^>]+>)/i', '$1$3', $html);
    $html = preg_replace('/(<img[^>]+)(min-height\s*:[^;]+;)([^>]+>)/i', '$1$3', $html);
    $html = preg_replace('/(<img[^>]+)(height\s*:[^;]+;)([^>]+>)/i', '$1$3', $html);
    $html = preg_replace('/(<img[^>]+)(height=[\'"][^\'"]+[\'"])([^>]+>)/i', '$1 $3', $html);
    return $html;
  }

  /**
   * Convert a string to a numeric value (int or float) if possible.
   *
   * Handles scientific notation and leading zeros.
   *
   * @param string $str the string to convert
   *
   * @return int|float|string the converted numeric value, or the original string
   */
  public static function toNumber($str) {
    $str = trim($str);
    if (preg_match('/[eE]/', $str)) {
      return $str;
    }
    if (is_numeric($str)) {
      // leading zero and no any other sign
      if (preg_match('/^0\d+$/', $str)) {
        return $str;
      }
      if (filter_var($str, FILTER_VALIDATE_FLOAT)) {
        return (float) $str;
      }
      if (filter_var($str, FILTER_VALIDATE_INT)) {
        return (int) $str;
      }
      if (is_numeric($str)) {
        return (float) $str;
      }
    }
    return $str;
  }

  /**
   * Parse a URL and return its components.
   *
   * Wrapper for PHP's parse_url().
   *
   * @param string $url the URL to parse
   *
   * @return array|false associative array of URL components, or FALSE on failure
   */
  public static function parseUrl($url) {
    return parse_url($url);
  }

  /**
   * Extract UTM parameters from a URL.
   *
   * @param string $url the URL to extract UTM parameters from
   *
   * @return array associative array of UTM parameters
   */
  public static function parseUrlUtm($url) {
    $utms = [];
    $original = CRM_Utils_String::parseUrl($url);
    if (stristr($original['query'], 'utm_')) {
      $query = str_replace('&amp;', '&', $original['query']);
      $get = [];
      parse_str($query, $get);
      foreach ($get as $queryKey => $queryValue) {
        if (stristr($queryKey, 'utm_')) {
          $utms[$queryKey] = $queryValue;
        }
      }
    }
    return $utms;
  }

  /**
   * Build a URL from its components.
   *
   * @param array $parts associative array of URL components (scheme, host, path, etc.)
   *
   * @return string the constructed URL
   */
  public static function buildUrl($parts) {
    return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
      ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
      (isset($parts['user']) ? "{$parts['user']}" : '') .
      (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
      (isset($parts['user']) ? '@' : '') .
      (isset($parts['host']) ? "{$parts['host']}" : '') .
      (isset($parts['port']) ? ":{$parts['port']}" : '') .
      (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '') .
      (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
  }

  /**
   * Convert a string into a safe filename.
   *
   * Removes unsafe characters and replaces spaces with underscores.
   *
   * @param string $str the input string
   *
   * @return string the safe filename
   */
  public static function safeFilename($str) {
    $str = preg_replace("/([^\w\s\d\.\-_~\[\]\(\)]|[\.]{2,})/u", '', $str);
    $str = preg_replace("/\s+/u", '_', $str);
    $str = CRM_Utils_File::sanitizeFileName($str);
    return $str;
  }

  /**
   * Mask a portion of a string with a mask character.
   *
   * @param string $str the string to be masked
   * @param string $mode masking mode ('auto' or 'custom')
   * @param int|null $start starting position for custom masking
   * @param int|null $end end position for custom masking (calculated from end)
   *
   * @return string|null the masked string
   */
  public static function mask($str, $mode = 'auto', $start = NULL, $end = NULL) {
    if (empty($str)) {
      return;
    }
    $length = mb_strlen($str);
    if ($length <= 1) {
      return self::MASK;
    }

    if ($mode == 'custom' && is_int($start) && is_int($end)) {
      $end = abs($end);
      if ($start < 0) {
        $repeat = abs($start) - $end;
      }
      else {
        $repeat = $length - $start - $end;
      }
      $repeat = $repeat < 0 ? 0 : $repeat;
      $str = mb_substr($str, 0, $start) . str_repeat(self::MASK, $repeat) . mb_substr($str, -1 * $end, $end);
    }
    else {
      switch ($length) {
        case 2:
          $str = mb_substr($str, 0, 1) . self::MASK;
          // no break
        case 3:
        case 4:
          $str = mb_substr($str, 0, 1) . str_repeat(self::MASK, $length - 2) . mb_substr($str, -1, 1);
          break;
        default:
          if ($length > 20) {
            $str = mb_substr($str, 0, 1) . str_repeat(self::MASK, 20 - 3) . mb_substr($str, -2, 2);
          }
          else {
            $str = mb_substr($str, 0, 1) . str_repeat(self::MASK, $length - 3) . mb_substr($str, -2, 2);
          }
          break;
      }

    }
    return $str;
  }

  /**
   * Transliterate a UTF-8 string to ASCII.
   *
   * @param string $string the string to transliterate
   * @param string $unknown replacement for unknown characters
   * @param string|null $source_langcode optional source language code
   *
   * @return string the transliterated ASCII string
   */
  public static function transliteration($string, $unknown = '?', $source_langcode = NULL) {
    // ASCII is always valid NFC! If we're only ever given plain ASCII, we can
    // avoid the overhead of initializing the decomposition tables by skipping
    // out early.
    if (!preg_match('/[\x80-\xff]/', $string)) {
      return $string;
    }

    static $tail_bytes;

    if (!isset($tail_bytes)) {
      // Each UTF-8 head byte is followed by a certain number of tail bytes.
      $tail_bytes = [];
      for ($n = 0; $n < 256; $n++) {
        if ($n < 0xc0) {
          $remaining = 0;
        }
        elseif ($n < 0xe0) {
          $remaining = 1;
        }
        elseif ($n < 0xf0) {
          $remaining = 2;
        }
        elseif ($n < 0xf8) {
          $remaining = 3;
        }
        elseif ($n < 0xfc) {
          $remaining = 4;
        }
        elseif ($n < 0xfe) {
          $remaining = 5;
        }
        else {
          $remaining = 0;
        }
        $tail_bytes[chr($n)] = $remaining;
      }
    }

    // Chop the text into pure-ASCII and non-ASCII areas; large ASCII parts can
    // be handled much more quickly. Don't chop up Unicode areas for punctuation,
    // though, that wastes energy.
    preg_match_all('/[\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*/', $string, $matches);

    $result = '';
    foreach ($matches[0] as $str) {
      if ($str[0] < "\x80") {
        // ASCII chunk: guaranteed to be valid UTF-8 and in normal form C, so
        // skip over it.
        $result .= $str;
        continue;
      }

      // We'll have to examine the chunk byte by byte to ensure that it consists
      // of valid UTF-8 sequences, and to see if any of them might not be
      // normalized.
      //
      // Since PHP is not the fastest language on earth, some of this code is a
      // little ugly with inner loop optimizations.

      $head = '';
      $chunk = strlen($str);
      // Counting down is faster. I'm *so* sorry.
      $len = $chunk + 1;

      for ($i = -1; --$len;) {
        $c = $str[++$i];
        if ($remaining = $tail_bytes[$c]) {
          // UTF-8 head byte!
          $sequence = $head = $c;
          do {
            // Look for the defined number of tail bytes...
            if (--$len && ($c = $str[++$i]) >= "\x80" && $c < "\xc0") {
              // Legal tail bytes are nice.
              $sequence .= $c;
            }
            else {
              if ($len == 0) {
                // Premature end of string! Drop a replacement character into
                // output to represent the invalid UTF-8 sequence.
                $result .= $unknown;
                break 2;
              }
              else {
                // Illegal tail byte; abandon the sequence.
                $result .= $unknown;
                // Back up and reprocess this byte; it may itself be a legal
                // ASCII or UTF-8 sequence head.
                --$i;
                ++$len;
                continue 2;
              }
            }
          }
          while (--$remaining);

          $n = ord($head);
          if ($n <= 0xdf) {
            $ord = ($n - 192) * 64 + (ord($sequence[1]) - 128);
          }
          elseif ($n <= 0xef) {
            $ord = ($n - 224) * 4096 + (ord($sequence[1]) - 128) * 64 + (ord($sequence[2]) - 128);
          }
          elseif ($n <= 0xf7) {
            $ord = ($n - 240) * 262144 + (ord($sequence[1]) - 128) * 4096 + (ord($sequence[2]) - 128) * 64 + (ord($sequence[3]) - 128);
          }
          elseif ($n <= 0xfb) {
            $ord = ($n - 248) * 16777216 + (ord($sequence[1]) - 128) * 262144 + (ord($sequence[2]) - 128) * 4096 + (ord($sequence[3]) - 128) * 64 + (ord($sequence[4]) - 128);
          }
          elseif ($n <= 0xfd) {
            $ord = ($n - 252) * 1073741824 + (ord($sequence[1]) - 128) * 16777216 + (ord($sequence[2]) - 128) * 262144 + (ord($sequence[3]) - 128) * 4096 + (ord($sequence[4]) - 128) * 64 + (ord($sequence[5]) - 128);
          }
          $result .= self::transliterationReplace($ord, $unknown, $source_langcode);
          $head = '';
        }
        elseif ($c < "\x80") {
          // ASCII byte.
          $result .= $c;
          $head = '';
        }
        elseif ($c < "\xc0") {
          // Illegal tail bytes.
          if ($head == '') {
            $result .= $unknown;
          }
        }
        else {
          // Miscellaneous freaks.
          $result .= $unknown;
          $head = '';
        }
      }
    }

    // Replace whitespace.
    $result = str_replace(' ', '_', $result);
    // Remove remaining unsafe characters.
    $result = preg_replace('![^0-9A-Za-z_.-]!', '', $result);
    // Remove multiple consecutive non-alphabetical characters.
    $result = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $result);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $result = strtolower($result);
    // prevent illegal db name
    $result = trim($result, '_');
    return $result;
  }

  /**
   * Replace a character code with its transliterated equivalent.
   *
   * @param int $ord the character code
   * @param string $unknown replacement for unknown characters
   * @param string|null $langcode the language code for language-specific mapping
   *
   * @return string the transliterated character
   */
  private static function transliterationReplace($ord, $unknown = '?', $langcode = NULL) {
    static $map = [];

    if (!isset($langcode)) {
      $langcode = CRM_Utils_System::getUFLocale();
    }

    $bank = $ord >> 8;

    if (!isset($map[$bank][$langcode])) {
      global $civicrm_root;
      $civicrm_path = rtrim($civicrm_root, '/');
      $file = $civicrm_path.'/packages/transliteration/'.sprintf('x%02x', $bank) . '.php';
      if (file_exists($file)) {
        include $file;
        if ($langcode != 'en' && isset($variant[$langcode])) {
          // Merge in language specific mappings.
          $map[$bank][$langcode] = $variant[$langcode] + $base;
        }
        else {
          $map[$bank][$langcode] = $base;
        }
      }
      else {
        $map[$bank][$langcode] = [];
      }
    }

    $ord = $ord & 255;

    return $map[$bank][$langcode][$ord] ?? $unknown;
  }

  /**
   * Truncate a UTF-8 string to a specified length.
   *
   * @param string $utf8String the string to truncate
   * @param int $length the maximum length
   *
   * @return string the truncated string
   */
  public function truncate($utf8String, $length) {
    if (extension_loaded('mbstring')) {
      return mb_substr($utf8String, 0, $length, 'UTF-8');
    }
    else {
      $slen = strlen($utf8String);
      if ($slen <= $length) {
        return $utf8String;
      }
      if (ord($utf8String[$length]) < 0x80 || ord($utf8String[$length]) >= 0xc0) {
        return substr($utf8String, 0, $length);
      }
      while (--$length >= 0 && ord($utf8String[$length]) >= 0x80 && ord($utf8String[$length]) < 0xc0) {
      }
      return substr($utf8String, 0, $length);
    }
  }
}
