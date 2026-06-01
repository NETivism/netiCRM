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
 * This class is for displaying alphabetical bar
 *
 */
class CRM_Utils_PagerAToZ {

  /**
   * Build the A-to-Z navigation bar for alphabetical contact browsing.
   *
   * @param object|CRM_Core_DAO $query           The query object (or a DAO result set when $isDAO is TRUE).
   * @param string              $sortByCharacter  The character currently selected for filtering.
   * @param bool                $isDAO            When TRUE, $query is treated as a DAO result rather than a query object.
   *
   * @return array|null  An array of link element arrays, or NULL if no dynamic characters exist.
   */
  public static function getAToZBar(&$query, $sortByCharacter, $isDAO = FALSE) {
    $AToZBar = self::createLinks($query, $sortByCharacter, $isDAO);
    return $AToZBar;
  }

  /**
   * Return the full static A-to-Z alphabet array.
   *
   * @return array<int, string> An array of uppercase letters A through Z.
   */
  public static function getStaticCharacters() {
    $staticAlphabets = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    return $staticAlphabets;
  }

  /**
   * Return the set of first-letter characters that actually appear in the query results.
   *
   * @param object|CRM_Core_DAO $query  The query object or a DAO result set (when $isDAO is TRUE).
   * @param bool                $isDAO  When TRUE, $query is used directly as a DAO result.
   *
   * @return array|null  An array of uppercase letters present in the data, or NULL if the query returned no results.
   */
  public static function getDynamicCharacters(&$query, $isDAO) {
    if ($isDAO) {
      $result = $query;
    }
    else {
      $result = &$query->alphabetQuery();
    }
    if (!$result) {
      return NULL;
    }

    $dynamicAlphabets = [];
    while ($result->fetch()) {
      $dynamicAlphabets[] = $result->sort_name;
    }
    return $dynamicAlphabets;
  }

  /**
   * Build the array of A-to-Z link elements combining static and dynamic alphabets.
   *
   * @param object $query           The query object providing form values and alphabetQuery().
   * @param string $sortByCharacter The currently active letter filter.
   * @param bool   $isDAO           When TRUE, $query is treated as a DAO result set.
   *
   * @return array|null  An array of element arrays (each with an 'item' HTML string and optional 'class'),
   *                     or NULL if no dynamic alphabets exist or form values are absent.
   */
  public static function createLinks(&$query, $sortByCharacter, $isDAO) {
    $AToZBar = self::getStaticCharacters();
    $dynamicAlphabets = self::getDynamicCharacters($query, $isDAO);

    if (!$dynamicAlphabets) {
      return NULL;
    }

    $AToZBar = array_merge($AToZBar, $dynamicAlphabets);
    $AToZBar = array_unique($AToZBar);
    //get the current path
    $path = CRM_Utils_System::currentPath();

    if (!empty($query->_formValues)) {
      $qfKey = CRM_Utils_Array::value('qfKey', $query->_formValues);
      $aToZBar = [];
      foreach ($AToZBar as $key => $link) {
        if (!$link) {
          continue;
        }

        $element = [];
        if (in_array($link, $dynamicAlphabets)) {
          $klass = '';
          if ($link == $sortByCharacter) {
            $element['class'] = "active";
            $klass = 'class="active"';
          }
          $url = CRM_Utils_System::url($path, "force=1&qfKey=$qfKey&sortByCharacter=");
          // we do it this way since we want the url to be encoded but not the link character
          // since that seems to mess up drupal utf-8 encoding etc
          $url .= $link;
          $element['item'] = sprintf(
            '<a href="%s" %s>%s</a>',
            $url,
            $klass,
            $link
          );
        }
        else {
          $element['item'] = $link;
        }
        $aToZBar[] = $element;
      }

      $url = sprintf(
        '<a href="%s">%s</a>',
        CRM_Utils_System::url($path, "force=1&qfKey=$qfKey&sortByCharacter=1"),
        'All'
      );
      $aToZBar[] = ['item' => $url];
      return $aToZBar;
    }
    else {
      return NULL;
    }
  }
}
