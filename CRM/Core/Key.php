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
 * Prevent PHP 5.5 broken
 */
if(!function_exists('hash_equals')) {
  function hash_equals($str1, $str2) {
    if(strlen($str1) != strlen($str2)) {
      return false;
    } else {
      $res = $str1 ^ $str2;
      $ret = 0;
      for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
      return !$ret;
    }
  }
}
class CRM_Core_Key {
  /**
   * The length of the randomly-generated, per-session signing key.
   *
   * Expressed as number of bytes. (Ex: 128 bits = 16 bytes)
   *
   * @var int
   */
  const PRIVATE_KEY_LENGTH = 16;

  /**
   * @var string
   * @see hash_hmac_algos()
   */
  const HASH_ALGO = 'sha256';

  /**
   * The length of a generated signature/digest (expressed in hex digits).
   * @var int
   */
  const HASH_LENGTH = 64;
  
  static $_key = NULL;

  static $_sessionID = NULL;

  /**
   * Generate a private key per session and store in session
   *
   * @return string private key for this session
   * @static
   * @access private
   */
  static function privateKey() {

    if (!self::$_key) {
      $session = CRM_Core_Session::singleton();
      self::$_key = $session->get('qfPrivateKey');
      if (!self::$_key) {
        self::$_key = base64_encode(random_bytes(self::PRIVATE_KEY_LENGTH));
        $session->set('qfPrivateKey', self::$_key);
      }
    }
    return self::$_key;
  }

  static function sessionID() {
    if (!self::$_sessionID) {
      $session = CRM_Core_Session::singleton();
      self::$_sessionID = $session->get('qfSessionID');
      if (!self::$_sessionID) {
        self::$_sessionID = CRM_Utils_System::getSessionID();
        $session->set('qfSessionID', self::$_sessionID);
      }
    }
    return self::$_sessionID;
  }

  /**
   * Generate a form key based on form name, the current user session
   * and a private key. Modelled after drupal's form API
   *
   * @param string  $value       name of the form
   * @paeam boolean $addSequence should we add a unique sequence number to the end of the key
   *
   * @return string       valid formID
   * @static
   * @acess public
   */
  static function get($name, $addSequence = FALSE) {
    $key = self::sign($name);

    if ($addSequence) {
      // now generate a random number between 1 and 100K and add it to the key
      // so that we can have forms in mutiple tabs etc
      $key = $key . '_' . mt_rand(1, 10000);
    }
    return $key;
  }

  /**
   * Validate a form key based on the form name
   *
   * @param string $formKey
   * @param string $name
   *
   * @return string $formKey if valid, else null
   * @static
   * @acess public
   */
  static function validate($key, $name, $addSequence = FALSE) {
    if (!is_string($key)) {
      return NULL;
    }

    if ($addSequence) {
      list($k, $t) = explode('_', $key);
      if ($t < 1 || $t > 10000) {
        return NULL;
      }
    }
    else {
      $k = $key;
    }

    if (!hash_equals($k, self::sign($name))) {
      return NULL;
    }
    return $key;
  }

  static function valid($key) {
    // a valid key is a 32 digit hex number
    // followed by an optional _ and a number between 1 and 10000
    if (strpos('_', $key) !== FALSE) {
      list($hash, $seq) = explode('_', $key);

      // ensure seq is between 1 and 10000
      if (!is_numeric($seq) ||
        $seq < 1 ||
        $seq > 10000
      ) {
        return FALSE;
      }
    }
    else {
      $hash = $key;
    }

    // ensure that hash is a 32 digit hex number
    return preg_match('#[0-9a-f]{' . self::HASH_LENGTH . '}#i', $hash) ? TRUE : FALSE;
  }

  /**
  * @param string $name
  *   The name of the form
  * @return string
  *   A signed digest of $name, computed with the per-session private key
  */
  private static function sign($name) {
    $privateKey = self::privateKey();
    $sessionID = self::sessionID();
    $delim = chr(0);
    if (strpos($sessionID, $delim) !== FALSE || strpos($name, $delim) !== FALSE) {
      throw new \RuntimeException("Failed to generate signature. Malformed session-id or form-name.");
    }

    return hash_hmac(self::HASH_ALGO, $sessionID . $delim . $name, $privateKey);
  }
}

