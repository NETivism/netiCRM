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
 * Utility methods for generating iCalendar (ICS) formatted event data
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * @file
 * API for event export in iCalendar format
 * as outlined in Internet Calendaring and
 * Scheduling Core Object Specification
 *
 */
class CRM_Utils_ICalendar {

  /**
   * Escape text elements for safe ICalendar use.
   *
   * Decodes HTML entities, strips tags, escapes special characters
   * (commas, semicolons, backslashes), converts line breaks, and
   * splits long multibyte strings for RFC 5545 line folding.
   *
   * @param string $text The text to escape.
   *
   * @return string The escaped and folded text.
   */
  public static function formatText($text) {
    $text = html_entity_decode($text, ENT_QUOTES);
    $text = str_replace(['&nbsp;', '&nbsp\;'], '', $text);
    $text = str_replace("\"", "DQUOTE", $text);
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace(["\n", "\r"], "", $text);
    $text = str_replace(["<br>", "<br />", "</p>"], '\n ', $text);
    $text = strip_tags($text);
    $text = str_replace(",", "\,", $text);
    $text = str_replace(";", "\;", $text);
    $text = CRM_Utils_Array::implode("\n ", CRM_Utils_ICalendar::mb_str_split($text, 20));
    return $text;
  }

  /**
   * Format HTML content for ICalendar ALTREP use.
   *
   * Decodes HTML entities, removes newlines/carriage returns,
   * and wraps the content in a basic HTML document structure.
   *
   * @param string $html The HTML content to format.
   *
   * @return string The formatted HTML wrapped in html/body tags.
   */
  public static function formatHTML($html) {
    $html = html_entity_decode($html, ENT_QUOTES);
    $html = preg_replace("/\r|\n/", "", $html);
    return '<html><body>'.$html.'</body></html>';
  }

  /**
   * Format a date string for ICalendar or GData use.
   *
   * Converts a date string to ICalendar format (YYYYMMDDTHHmmss)
   * or GData format (YYYY-MM-DDTHH:mm:ss.000) depending on the flag.
   *
   * @param string $date The date string to format (any strtotime-parseable value).
   * @param bool $gdata If TRUE, use GData format; otherwise use standard ICalendar format.
   *
   * @return string The formatted date string.
   */
  public static function formatDate($date, $gdata = FALSE) {

    if ($gdata) {
      return date(
        "Y-m-d\TH:i:s.000",
        strtotime($date)
      );
    }
    else {
      return date(
        "Ymd\THis",
        strtotime($date)
      );
    }
  }

  /**
   * Send the ICalendar data to the browser with the specified content type.
   *
   * Supported content types:
   * - 'text/calendar': used for downloaded .ics files
   * - 'text/plain': used for iCal formatted feeds
   * - 'text/xml': used for GData or RSS formatted feeds
   *
   * @param string $calendar The calendar data to be published.
   * @param string $content_type The MIME content type for the response.
   * @param string $charset The character set to use.
   * @param string|null $fileName The file name for downloads.
   * @param string|null $disposition How the file should be sent (e.g. 'attachment' for downloads).
   *
   * @return void
   */
  public static function send($calendar, $content_type = 'text/calendar', $charset = 'us-ascii', $fileName = NULL, $disposition = NULL) {

    $config = CRM_Core_Config::singleton();
    $lang = $config->lcMessages;
    header("Content-Language: $lang");
    header("Content-Type: $content_type; charset=$charset;");
    //header( "Content-Type: $content_type" );

    if ($content_type == 'text/calendar') {
      header('Content-Length: ' . strlen($calendar));
      header("Content-Disposition: $disposition; filename=\"$fileName\"");
    }

    echo $calendar;
  }

  /**
   * Split a multibyte string into chunks of a given length.
   *
   * @param string $str The multibyte string to split.
   * @param int $split_len The maximum number of multibyte characters per chunk.
   *
   * @return string[]|false An array of string chunks, or FALSE if split_len is invalid.
   */
  public static function mb_str_split($str, $split_len = 1) {
    if (!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1) {
      return FALSE;
    }

    $len = mb_strlen($str, 'UTF-8');
    if ($len <= $split_len) {
      return [$str];
    }

    preg_match_all('/.{' . $split_len . '}|[^\x00]{1,' . $split_len . '}$/us', $str, $ar);
    return $ar[0];
  }
}
