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
 * This class contains function for Open Id
 */
class CRM_Core_BAO_OpenID extends CRM_Core_DAO_OpenID {

  /**
   * takes an associative array and adds OpenID
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return object       CRM_Core_BAO_OpenID object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params) {
    $openId = new CRM_Core_DAO_OpenID();

    // normalize the OpenID URL
    $params['openid'] = self::normalizeURL($params['openid']);

    $openId->copyValues($params);

    return $openId->save();
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock   input parameters to find object
   *
   * @return mixed
   * @access public
   * @static
   */
  static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('openid', $entityBlock);
  }

  /**
   * Returns whether or not this OpenID is allowed to login
   *
   * @param  string  $identity_url the OpenID to check
   *
   * @return boolean
   * @access public
   * @static
   */
  static function isAllowedToLogin($identity_url) {
    $openId = new CRM_Core_DAO_OpenID();
    $openId->openid = $identity_url;
    if ($openId->find(TRUE)) {
      return $openId->allowed_to_login == 1;
    }
    return FALSE;
  }

  /**
   * Get all the openids for a specified contact_id, with the primary openid being first
   *
   * @param int $id the contact id
   *
   * @return array  the array of openid's
   * @access public
   * @static
   */
  static function allOpenIDs($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_openid.openid, civicrm_location_type.name as locationType, civicrm_openid.is_primary as is_primary, 
civicrm_openid.allowed_to_login as allowed_to_login, civicrm_openid.id as openid_id, 
civicrm_openid.location_type_id as locationTypeId
FROM      civicrm_contact
LEFT JOIN civicrm_openid ON ( civicrm_openid.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_openid.location_type_id = civicrm_location_type.id )
WHERE
  civicrm_contact.id = %1
ORDER BY
  civicrm_openid.is_primary DESC,  openid_id ASC ";
    $params = [1 => [$id, 'Integer']];

    $openids = $values = [];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = ['locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->openid_id,
        'openid' => $dao->openid,
        'locationTypeId' => $dao->locationTypeId,
        'allowed_to_login' => $dao->allowed_to_login,
      ];

      if ($updateBlankLocInfo) {
        $openids[$count++] = $values;
      }
      else {
        $openids[$dao->openid_id] = $values;
      }
    }
    return $openids;
  }

  static function valueExists(&$params) {
    // do nothing
  }

  static function normalizeURL($url) {
    $parsed = parse_url($url);

    if (!$parsed) {
      return null;
    }

    if (isset($parsed['scheme']) &&
      isset($parsed['host'])) {
      $scheme = strtolower($parsed['scheme']);
      if (!in_array($scheme, ['http', 'https'])) {
        return null;
      }
    } else {
      $url = 'http://' . $url;
    }

    $normalized = self::_normalizeURL($url);
    if ($normalized === null) {
      return null;
    }

    $parts = explode("#", $url, 2);
    if (count($parts) == 1) {
      list($defragged, $frag) = [$parts[0], ""];
    } else {
      list($defragged, $frag) = $parts;
    }
    return $defragged;
  }

  static function _normalizeURL($uri) {
    $uri_matches = [];
    preg_match('&^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?&', $uri, $uri_matches);

    if (count($uri_matches) < 9) {
      for ($i = count($uri_matches); $i <= 9; $i++) {
        $uri_matches[] = '';
      }
    }

    $illegal_matches = [];
    preg_match("/([^-A-Za-z0-9:\/\?#\[\]@\!\$&'\(\)\*\+,;=\._~\%])/", $uri, $illegal_matches);
    if ($illegal_matches) {
      return null;
    }

    $scheme = $uri_matches[2];
    if ($scheme) {
      $scheme = strtolower($scheme);
    }

    $scheme = $uri_matches[2];
    if ($scheme === '') {
      // No scheme specified
      return null;
    }

    $scheme = strtolower($scheme);
    if (!in_array($scheme, ['http', 'https'])) {
      // Not an absolute HTTP or HTTPS URI
      return null;
    }

    $authority = $uri_matches[4];
    if ($authority === '') {
      // Not an absolute URI
      return null;
    }

    $authority_matches = [];
    preg_match('/^([^@]*@)?([^:]*)(:.*)?/', $authority, $authority_matches);
    if (count($authority_matches) === 0) {
      // URI does not have a valid authority
      return null;
    }

    if (count($authority_matches) < 4) {
      for ($i = count($authority_matches); $i <= 4; $i++) {
        $authority_matches[] = '';
      }
    }

    list($_whole, $userinfo, $host, $port) = $authority_matches;

    if ($userinfo === null) {
      $userinfo = '';
    }

    if (strpos($host, '%') !== -1) {
      $host = strtolower($host);
      $host = preg_replace_callback('/%([0-9A-Fa-f]{2})/', function($mo) {
        return chr(intval($mo[1], 16));
      }, $host);
    } else {
      $host = strtolower($host);
    }

    if ($port) {
        if (($port == ':') ||
            ($scheme == 'http' && $port == ':80') ||
            ($scheme == 'https' && $port == ':443')) {
            $port = '';
        }
    } else {
        $port = '';
    }

    $authority = $userinfo . $host . $port;

    $path = $uri_matches[5];
    $path = preg_replace_callback('/%([0-9A-Fa-f]{2})/', function($mo) {
      $_unreserved = [];
      for ($i = 0; $i < 256; $i++) {
        $_unreserved[$i] = false;
      }

      for ($i = ord('A'); $i <= ord('Z'); $i++) {
        $_unreserved[$i] = true;
      }

      for ($i = ord('0'); $i <= ord('9'); $i++) {
        $_unreserved[$i] = true;
      }

      for ($i = ord('a'); $i <= ord('z'); $i++) {
        $_unreserved[$i] = true;
      }

      $_unreserved[ord('-')] = true;
      $_unreserved[ord('.')] = true;
      $_unreserved[ord('_')] = true;
      $_unreserved[ord('~')] = true;

      $i = intval($mo[1], 16);
      if ($_unreserved[$i]) {
        return chr($i);
      } else {
        return strtoupper($mo[0]);
      }

      return $mo[0];
    }, $path);

    $result_segments = [];
    while ($path) {
      if (strpos($path, '../') === 0) {
        $path = substr($path, 3);
      } else if (strpos($path, './') === 0) {
        $path = substr($path, 2);
      } else if (strpos($path, '/./') === 0) {
        $path = substr($path, 2);
      } else if ($path == '/.') {
        $path = '/';
      } else if (strpos($path, '/../') === 0) {
        $path = substr($path, 3);
        if ($result_segments) {
          array_pop($result_segments);
        }
      } else if ($path == '/..') {
        $path = '/';
        if ($result_segments) {
          array_pop($result_segments);
        }
      } else if (($path == '..') || ($path == '.')) {
        $path = '';
      } else {
        $i = 0;
        if ($path[0] == '/') {
          $i = 1;
        }
        $i = strpos($path, '/', $i);
        if ($i === false) {
          $i = strlen($path);
        }
        $result_segments[] = substr($path, 0, $i);
        $path = substr($path, $i);
      }
    }

    if (!$path) {
      $path = '/';
    }

    $query = $uri_matches[6];
    if ($query === null) {
      $query = '';
    }

    $fragment = $uri_matches[8];
    if ($fragment === null) {
      $fragment = '';
    }

    return $scheme . '://' . $authority . $path . $query . $fragment;
  }
}

