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
 * This class is for displaying alphabetical bar
 *
 */
class CRM_Utils_PagerAToZ {

  /**
   * returns the alphabetic array for sorting by character
   *
   * @param array  $query           The query object
   * @param string $sortByCharacter The character that we are potentially sorting on
   *
   * @return string                 The html formatted string
   * @access public
   * @static
   */
  static function getAToZBar(&$query, $sortByCharacter, $isDAO = FALSE) {
    $AToZBar = self::createLinks($query, $sortByCharacter, $isDAO);
    return $AToZBar;
  }

  /**
   * Function to return the all the static characters
   *
   * @return array $staticAlphabets is a array of static characters
   * @access private
   * @static
   */

  static function getStaticCharacters() {
    $staticAlphabets = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    return $staticAlphabets;
  }

  /**
   * Function to return the all the dynamic characters
   *
   * @return array $dynamicAlphabets is a array of dynamic characters
   * @access private
   * @static
   */
  static function getDynamicCharacters(&$query, $isDAO) {
    if ($isDAO) {
      $result = $query;
    }
    else {
      $result = &$query->alphabetQuery();
    }
    if (!$result) {
      return NULL;
    }

    $dynamicAlphabets = array();
    while ($result->fetch()) {
      $dynamicAlphabets[] = $result->sort_name;
    }
    return $dynamicAlphabets;
  }

  /**
   * create the links
   *
   * @param array  $query          The form values for search
   * @param string $sortByCharacter The character that we are potentially sorting on
   *
   * @return array with links
   * @access private
   * @static
   */
  static function createLinks(&$query, $sortByCharacter, $isDAO) {
    $AToZBar = self::getStaticCharacters();
    $dynamicAlphabets = self::getDynamicCharacters($query, $isDAO);

    if (!$dynamicAlphabets) {
      return NULL;
    }

    $AToZBar = array_merge($AToZBar, $dynamicAlphabets);
    $AToZBar = array_unique($AToZBar);
    //get the current path
    $path = CRM_Utils_System::currentPath();

    if(!empty($query->_formValues)){
      $qfKey = CRM_Utils_Array::value('qfKey', $query->_formValues);
      $aToZBar = array();
      foreach ($AToZBar as $key => $link) {
        if (!$link) {
          continue;
        }

        $element = array();
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
          $element['item'] = sprintf('<a href="%s" %s>%s</a>',
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

      $url = sprintf('<a href="%s">%s</a>',
        CRM_Utils_System::url($path, "force=1&qfKey=$qfKey&sortByCharacter=1"),
        'All'
      );
      $aToZBar[] = array('item' => $url);
      return $aToZBar;
    }
    else{
      return NULL;
    }
  }
}

