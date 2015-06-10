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

/**
 * @file
 * API for event export in iCalendar format
 * as outlined in Internet Calendaring and
 * Scheduling Core Object Specification
 *
 */
class CRM_Utils_ICalendar {

  /**
   * Escape text elements for safe ICalendar use
   *
   * @param $text Text to escape
   *
   * @return  Escaped text
   *
   */
  static function formatText($text) {
    $text = htmlspecialchars_decode($text);
    $text = str_replace(array('&nbsp;', '&nbsp\;'), '', $text);
    $text = str_replace("\"", "DQUOTE", $text);
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace(array("<br>", "<br />", "</p>"), "\\n ", $text);
    $text = strip_tags($text);
    $text = str_replace(",", "\,", $text);
    $text = str_replace(";", "\;", $text);
    $text = preg_replace('/\s+/u', '', $text);
    $text = implode("\n ", CRM_Utils_ICalendar::mb_str_split($text, 20));
    return $text;
  }

  /**
   * Escape date elements for safe ICalendar use
   *
   * @param $date Date to escape
   *
   * @return  Escaped date
   *
   */
  static function formatDate($date, $gdata = FALSE) {

    if ($gdata) {
      return date("Y-m-d\TH:i:s.000",
        strtotime($date)
      );
    }
    else {
      return date("Ymd\THis",
        strtotime($date)
      );
    }
  }

  /**
   *
   * Send the ICalendar to the browser with the specified content type
   * - 'text/calendar' : used for downloaded ics file
   * - 'text/plain'    : used for iCal formatted feed
   * - 'text/xml'      : used for gData or rss formatted feeds
   *
   * @access public
   *
   * @param string $content_type
   *
   * @param string $filename The file name (for downloads)
   *
   * @param string $disposition How the file should be sent ('attachment' for downloads)
   *
   * @param string $charset The character set to use, defaults to
   * 'us-ascii'.
   *
   * @param string $calendar The calendar data to be published.
   *
   * @return void
   *
   */
  function send($calendar, $content_type = 'text/calendar', $charset = 'us-ascii', $fileName = NULL, $disposition = NULL) {
    require_once 'CRM/Core/Config.php';
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

  function mb_str_split($str, $split_len = 1) {
    if (!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1) {
      return FALSE;
    }

    $len = mb_strlen($str, 'UTF-8');
    if ($len <= $split_len) {
      return array($str);
    }

    preg_match_all('/.{' . $split_len . '}|[^\x00]{1,' . $split_len . '}$/us', $str, $ar);
    return $ar[0];
  }
}

