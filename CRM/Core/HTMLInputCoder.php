<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * This class captures the encoding practices of CRM-5667 in a reusable
 * fashion.  In this design, all submitted values are partially HTML-encoded
 * before saving to the database.  If a DB reader needs to output in
 * non-HTML medium, then it should undo the partial HTML encoding.
 *
 * This class should be short-lived -- 4.3 should introduce an alternative
 * escaping scheme and consequently remove HTMLInputCoder.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

require_once 'api/Wrapper.php';

class CRM_Core_HTMLInputCoder implements API_Wrapper {
  private static $skipFields = NULL;

  /**
   * @var CRM_Core_HTMLInputCoder
   */
  private static $_singleton = NULL;

  /**
   *
   * @return CRM_Core_HTMLInputCoder
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_HTMLInputCoder();
    }
    return self::$_singleton;
  }

  /**
   *
   * @return array<string> list of field names
   */
  public static function getSkipFields() {
    if (self::$skipFields === NULL) {
      self::$skipFields = [
        'widget_code',
        'html_message',
        'body_html',
        'msg_html',
        'description',
        'intro',
        'thankyou_text',
        'tf_thankyou_text',
        'intro_text',
        'page_text',
        'body_text',
        'footer_text',
        'thankyou_footer',
        'thankyou_footer_text',
        'new_text',
        'renewal_text',
        'help_pre',
        'help_post',
        'confirm_title',
        'confirm_text',
        'confirm_footer_text',
        'confirm_email_text',
        'event_full_text',
        'waitlist_text',
        'approval_req_text',
        'report_header',
        'report_footer',
        'cc_id',
        'bcc_id',
        'premiums_intro_text',
        'honor_block_text',
        'pay_later_receipt',
        // This is needed for FROM Email Address configuration. dgg
        'label',
        // This is needed for navigation items urls
        'url',
        'details',
        // message templates’ text versions
        'msg_text',
        // (send an) email to contact’s and CiviMail’s text version
        'text_message',
        // data i/p of persistent table
        'data',
        // CRM-6673
        'sqlQuery',
        'pcp_title',
        'pcp_intro_text',
      ];
    }
    return self::$skipFields;
  }

  /**
   *
   * @param string $fldName
   *
   * @return bool TRUE if encoding should be skipped for this field
   */
  public static function isSkippedField($fldName) {
    $skipFields = self::getSkipFields();
    return (
      // should be skipped...
      in_array($fldName, $skipFields)
      or
      // is multilingual and after cutting off _xx_YY should be skipped (CRM-7230)…
      (preg_match('/_[a-z][a-z]_[A-Z][A-Z]$/', $fldName) and in_array(substr($fldName, 0, -6), $skipFields))
    );
  }

  /**
   * This function is going to filter the
   * submitted values across XSS vulnerability.
   *
   * @param array|string $values
   * @param bool $castToString If TRUE, all scalars will be filtered (and therefore cast to strings)
   *    If FALSE, then non-string values will be preserved
   */
  public static function encodeInput(&$values, $castToString = TRUE) {
    if (is_array($values)) {
      foreach ($values as & $value) {
        self::encodeInput($value);
      }
    }
    elseif ($castToString || is_string($values)) {
      $values = str_replace(['<', '>'], ['&lt;', '&gt;'], $values);
    }
  }

  public static function decodeOutput(&$values, $castToString = TRUE) {
    if (is_array($values)) {
      foreach ($values as & $value) {
        self::decodeOutput($value);
      }
    }
    elseif ($castToString || is_string($values)) {
      $values = str_replace(['&lt;', '&gt;'], ['<', '>'], $values);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function fromApiInput($apiRequest) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, ['get', 'create'])) {
      // note: 'getsingle', 'replace', 'update', and chaining all build on top of 'get'/'create'
      foreach ($apiRequest['params'] as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!self::isApiControlField($key) && !self::isSkippedField($key)) {
          self::encodeInput($apiRequest['params'][$key], FALSE);
        }
      }
    }
    elseif ($apiRequest['version'] == 3 && $lowerAction == 'setvalue') {
      if (isset($apiRequest['params']['field']) && isset($apiRequest['params']['value'])) {
        if (!self::isSkippedField($apiRequest['params']['field'])) {
          self::encodeInput($apiRequest['params']['value'], FALSE);
        }
      }
    }
    return $apiRequest;
  }

  /**
   * {@inheritDoc}
   */
  public function toApiOutput($apiRequest, $result) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, ['get', 'create', 'setvalue'])) {
      foreach ($result as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!self::isApiControlField($key) && !self::isSkippedField($key)) {
          self::decodeOutput($result[$key], FALSE);
        }
      }
    }
    // setvalue?
    return $result;
  }

  /**
   *
   * @return bool
   */
  protected function isApiControlField($key) {
    return (FALSE !== strpos($key, '.'));
  }
}

