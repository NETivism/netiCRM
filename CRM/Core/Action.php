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
 * The core concept of the system is an action performed on an object. Typically this will be a "data model" object
 * as specified in the API specs. We attempt to keep the number and type of actions consistent
 * and similar across all objects (thus providing both reuse and standards)
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_Action {

  /**
   * Different possible actions are defined here. Keep in sync with the
   * constant from CRM_Core_Form for various modes.
   *
   * @var int
   */
  public const
    NONE = 0,
    ADD = 1,
    UPDATE = 2,
    VIEW = 4,
    DELETE = 8,
    BROWSE = 16,
    ENABLE = 32,
    DISABLE = 64,
    EXPORT = 128,
    BASIC = 256,
    ADVANCED = 512,
    PREVIEW = 1024,
    FOLLOWUP = 2048,
    MAP = 4096,
    PROFILE = 8192,
    COPY = 16384,
    RENEW = 32768,
    DETACH = 65536,
    REVERT = 131072,
    CLOSE = 262144,
    REOPEN = 524288,
    MAX_ACTION = 1048575;

  //make sure MAX_ACTION = 2^n - 1 ( n = total number of actions )

  /**
   * Map the action names to the relevant constant. We perform
   * bit manipulation operations so we can perform multiple
   * actions on the same object if needed.
   *
   * @var array Tuple of variable name to action constant.
   */
  public static $_names = [
    'add' => self::ADD,
    'update' => self::UPDATE,
    'view' => self::VIEW,
    'delete' => self::DELETE,
    'browse' => self::BROWSE,
    'enable' => self::ENABLE,
    'disable' => self::DISABLE,
    'export' => self::EXPORT,
    'preview' => self::PREVIEW,
    'map' => self::MAP,
    'copy' => self::COPY,
    'profile' => self::PROFILE,
    'renew' => self::RENEW,
    'detach' => self::DETACH,
    'revert' => self::REVERT,
    'close' => self::CLOSE,
    'reopen' => self::REOPEN,
  ];

  /**
   * The flipped version of the names array, initialized when used.
   *
   * @var array|null
   */
  public static $_description;

  /**
   * Translates a string of actions separated by '|' into a bitmask.
   *
   * @param string|null $str The action string to be resolved.
   *
   * @return int The action mask corresponding to the input string.
   */
  public static function resolve($str) {
    $action = 0;
    if ($str) {
      $items = explode('|', $str);
      $action = self::map($items);
    }
    return $action;
  }

  /**
   * Given a string or an array of strings, determine the bitmask
   * for this set of actions.
   *
   * @param string|array $item Either a single string or an array of strings.
   *
   * @return int The action mask corresponding to the input args.
   */
  public static function map($item) {
    $mask = 0;

    if (is_array($item)) {
      foreach ($item as $it) {
        $mask |= self::mapItem($it);
      }
      return $mask;
    }
    else {
      return self::mapItem($item);
    }
  }

  /**
   * Given a string determine the bitmask for this specific string.
   *
   * @param string $item The input action to process.
   *
   * @return int The action mask corresponding to the input string.
   */
  public static function mapItem($item) {
    $mask = CRM_Utils_Array::value(trim($item), self::$_names);
    return $mask ? $mask : 0;
  }

  /**
   * Given an action mask, find the corresponding description.
   *
   * @param int $mask The action mask.
   *
   * @return string The corresponding action description.
   */
  public static function description($mask) {
    if (!isset($_description)) {
      self::$_description = array_flip(self::$_names);
    }

    return CRM_Utils_Array::value($mask, self::$_description, 'NO DESCRIPTION SET');
  }

  /**
   * Given a set of links and a mask, return the html action string for
   * the links associated with the mask.
   *
   * @param array $links The set of link items.
   * @param int|null $mask The mask to be used. A null mask means all items.
   * @param array $values The array of values for parameter substitution in the link items.
   * @param string $extraULName Enclose extra links in this UL.
   * @param bool $enclosedAllInSingleUL Force to enclose all links in single UL.
   *
   * @return string|null The html string.
   */
  public static function formLink(
    &$links,
    $mask,
    $values,
    $extraULName = 'more',
    $enclosedAllInSingleUL = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    if (empty($links)) {
      return NULL;
    }

    $url = [];

    $firstLink = TRUE;
    foreach ($links as $m => $link) {
      if (!$mask || ($mask & $m)) {
        $extra = NULL;
        if (isset($link['extra'])) {
          $extra = self::replace(CRM_Utils_Array::value('extra', $link, ''), $values);
        }

        $frontend = FALSE;
        if (isset($link['fe'])) {
          $frontend = TRUE;
        }

        $urlPath = NULL;
        if (CRM_Utils_Array::value('qs', $link) && !CRM_Utils_System::isNull($link['qs'])) {
          $urlPath = CRM_Utils_System::url(
            self::replace($link['url'], $values),
            self::replace($link['qs'], $values),
            TRUE,
            NULL,
            TRUE,
            $frontend
          );
        }
        else {
          $urlPath = CRM_Utils_Array::value('url', $link);
        }

        $classes = 'action-item';
        if ($firstLink) {
          $firstLink = FALSE;
          $classes .= " action-item-first";
        }
        if (isset($link['ref'])) {
          $classes .= ' ' . strtolower($link['ref']);
        }

        //get the user specified classes in.
        if (isset($link['class'])) {
          $className = $link['class'];
          if (is_array($className)) {
            $className = CRM_Utils_Array::implode(' ', $className);
          }
          $classes .= ' ' . strtolower($className);
        }

        $linkClasses = 'class = "' . $classes . '"';

        if ($urlPath) {
          if ($frontend) {
            $extra .= "target=_blank";
          }
          $url[] = sprintf(
            '<a href="%s" %s title="%s"' . $extra . '>%s</a>',
            $urlPath,
            $linkClasses,
            CRM_Utils_Array::value('title', $link),
            $link['name']
          );
        }
        else {
          $url[] = sprintf(
            '<a title="%s"  %s ' . $extra . '>%s</a>',
            CRM_Utils_Array::value('title', $link),
            $linkClasses,
            $link['name']
          );
        }
      }
    }

    $result = '';
    $mainLinks = $url;
    $extraLinksName = strtolower($extraULName);
    $tsExtraULName = ts($extraULName);
    if ($enclosedAllInSingleUL) {
      $allLinks = '';
      CRM_Utils_String::append($allLinks, '</li><li>', $mainLinks);
      $allLinks = "$tsExtraULName <ul id='panel_{$extraLinksName}_xx' class='panel'><li>{$allLinks}</li></ul>";
      $result = "<span class='btn-slide' id={$extraLinksName}_xx>{$allLinks}</span>";
    }
    else {
      $extra = '';
      $extraLinks = array_splice($url, 3);
      if (count($extraLinks) > 1) {
        $mainLinks = array_slice($url, 0, 3);
        CRM_Utils_String::append($extra, '</li><li>', $extraLinks);
        $extra = "$tsExtraULName <ul id='panel_{$extraLinksName}_xx' class='panel'><li>{$extra}</li></ul>";
      }
      $resultLinks = '';
      CRM_Utils_String::append($resultLinks, '', $mainLinks);
      if ($extra) {
        $result = "<span>{$resultLinks}</span><span class='btn-slide' id={$extraLinksName}_xx>{$extra}</span>";
      }
      else {
        $result = "<span>{$resultLinks}</span>";
      }
    }

    return $result;
  }

  /**
   * Given a string and an array of values, substitute the real values
   * in the placeholder in the string in the CiviCRM format (%%key%%).
   *
   * @param string $str The string to be replaced.
   * @param array $values The array of values for parameter substitution in the string.
   *
   * @return string The substituted string.
   */
  public static function &replace(&$str, &$values) {
    foreach ($values as $n => $v) {
      $str = str_replace("%%$n%%", $v, $str);
    }
    return $str;
  }

  /**
   * Get the mask for a set of permissions (view, edit or null).
   *
   * @param array|null $permissions The permissions array.
   *
   * @return int|null The mask for the above permissions.
   */
  public static function mask($permissions) {
    $mask = NULL;
    if (!is_array($permissions) || CRM_Utils_System::isNull($permissions)) {
      return $mask;
    }
    //changed structure since we are handling delete separately - CRM-4418
    if (in_array(CRM_Core_Permission::VIEW, $permissions)) {
      $mask |= self::VIEW | self::EXPORT | self::BASIC | self::ADVANCED | self::BROWSE | self::MAP | self::PROFILE;
    }
    if (in_array(CRM_Core_Permission::DELETE, $permissions)) {
      $mask |= self::DELETE;
    }
    if (in_array(CRM_Core_Permission::EDIT, $permissions)) {
      //make sure we make self::MAX_ACTION = 2^n - 1
      //if we add more actions; ( n = total number of actions )
      $mask |= (self::MAX_ACTION & ~self::DELETE);
    }

    return $mask;
  }
}
