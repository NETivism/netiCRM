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
 * This is a dummy class that does nothing at the moment.
 * the template is used primarily for displaying result page
 * of tasks performed on contacts. Contacts are searched/selected
 * and then subjected to Tasks/Actions.
 *
 */
class CRM_Contact_Page_SearchRedirect extends CRM_Core_Page {

  /**
   * Find the path and go to Custom Search
   */
  function __construct() {
    $currentPath = CRM_Utils_System::currentPath();
    $split = explode('/', $currentPath);
    if ($split[0] == 'civicrm' && $split[1] == 'search' && !empty($split[2]) && CRM_Utils_Rule::alphanumeric($split[2])) {
      $custom = $split[2];
      $find = [
        1 => ['CRM_Contact_Form_Search_Custom_'.$custom, 'String']
      ];
      $exists = CRM_Core_DAO::singleValueQuery("SELECT value FROM civicrm_option_value WHERE name LIKE %1", $find);
      if(!empty($exists)){
        $args = $_GET;
        unset($args['q']);
        $args['reset'] = 1;
        $args['csid'] = $exists;
        $url = CRM_Utils_System::url('civicrm/contact/search/custom', http_build_query($args));
        CRM_Utils_System::redirect($url);
      }
    }
  }
}
