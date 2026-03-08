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
 * Encryption and decryption utilities using the CIVICRM_SITE_KEY.
 *
 * Supports OpenSSL (AES-128-CBC) encryption with backward compatibility
 * for legacy mcrypt-based encryption.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */
class CRM_Utils_Crypt {

  /**
   * OpenSSL cipher algorithm.
   *
   * @var string
   */
  public const ALGORITHM = 'AES-128-CBC';

  /**
   * Version prefix used to identify OpenSSL-encrypted strings.
   *
   * @var string
   */
  public const VER2 = '$O$';

  /**
   * Encrypt a string using OpenSSL AES-128-CBC with the site key.
   *
   * Returns the original string if OpenSSL is not available or
   * CIVICRM_SITE_KEY is not defined.
   *
   * @param string $string the plaintext string to encrypt
   *
   * @return string the encrypted string prefixed with VER2, or the original string on failure
   */
  public static function encrypt($string) {
    if (!self::checkAvailableCrypt('openssl')) {
      return $string;
    }

    if (defined('CIVICRM_SITE_KEY')) {
      $key = CIVICRM_SITE_KEY;
      $ivlen = openssl_cipher_iv_length(self::ALGORITHM);
      $iv = openssl_random_pseudo_bytes($ivlen);
      $encrypted = openssl_encrypt($string, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);
      $string = base64_encode($iv.$encrypted);
      // add special char for indicate it's openssl version
      $string = self::VER2.$string;
    }
    return $string;
  }

  /**
   * Decrypt a string encrypted by this class.
   *
   * Automatically detects whether the string was encrypted with OpenSSL
   * (VER2 prefix) or the legacy mcrypt method, and dispatches accordingly.
   *
   * @param string $string the encrypted string to decrypt
   *
   * @return string the decrypted plaintext, or the original string if decryption is unavailable
   */
  public static function decrypt($string) {
    if (substr($string, 0, strlen(self::VER2)) !== self::VER2 && self::checkAvailableCrypt('mcrypt')) {
      return self::deprecatedDecrypt($string);
    }
    if (!self::checkAvailableCrypt('openssl')) {
      return $string;
    }
    if (defined('CIVICRM_SITE_KEY') && substr($string, 0, strlen(self::VER2)) === self::VER2) {
      $str = substr($string, strlen(self::VER2));
      $key = CIVICRM_SITE_KEY;
      $encrypted = base64_decode($str);
      $ivlen = openssl_cipher_iv_length(self::ALGORITHM);
      $iv = substr($encrypted, 0, $ivlen);
      $encrypted_raw = substr($encrypted, $ivlen);
      $string = openssl_decrypt($encrypted_raw, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);
    }
    return $string;
  }

  /**
   * Encrypt a string using the legacy mcrypt RIJNDAEL-256 algorithm.
   *
   * @deprecated Use encrypt() with OpenSSL instead.
   *
   * @param string $string the plaintext string to encrypt
   *
   * @return string the base64-encoded encrypted string
   */
  public static function deprecatedEncrypt($string) {
    if (!self::checkAvailableCrypt('mcrypt')) {
      return base64_encode($string);
    }
    if (empty($string)) {
      return $string;
    }

    if (defined('CIVICRM_SITE_KEY')) {
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      $iv = mcrypt_create_iv(32, MCRYPT_RAND);
      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = mcrypt_generic($td, $string);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }
    return base64_encode($string);
  }

  /**
   * Decrypt a string encrypted with the legacy mcrypt RIJNDAEL-256 algorithm.
   *
   * @deprecated Use decrypt() with OpenSSL instead.
   *
   * @param string $string the base64-encoded encrypted string
   *
   * @return string the decrypted plaintext
   */
  public static function deprecatedDecrypt($string) {
    if (!self::checkAvailableCrypt('mcrypt')) {
      return base64_decode($string);
    }
    if (empty($string)) {
      return $string;
    }

    $string = base64_decode($string);

    if (defined('CIVICRM_SITE_KEY')) {
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      $iv = mcrypt_create_iv(32, MCRYPT_RAND);
      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = rtrim(mdecrypt_generic($td, $string));
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }

    return $string;
  }

  /**
   * Check whether a given cryptographic extension is available.
   *
   * @param string $type the extension name to check ('openssl' or 'mcrypt')
   *
   * @return bool|null TRUE if the extension is loaded, FALSE if not, NULL if type is unrecognized
   */
  public static function checkAvailableCrypt($type) {
    if ($type == 'openssl') {
      return extension_loaded('openssl');
    }
    if ($type == 'mcrypt') {
      return extension_loaded('mcrypt');
    }
  }
}
