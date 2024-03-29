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

require_once 'CRM/Core/Page.php';

/**
 * Main page for Cases dashlet
 *
 */
class CRM_Dashlet_Page_AllCases extends CRM_Core_Page {

  /**
   * List activities as dashlet
   *
   * @return none
   *
   * @access public
   */
  function run() {
    require_once 'CRM/Case/BAO/Case.php';
    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
       return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    require_once 'CRM/Core/OptionGroup.php';
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $upcoming = CRM_Case_BAO_Case::getCases(TRUE, $userID, 'upcoming');

    if (!empty($upcoming)) {
      $this->assign('AllCases', $upcoming);
    }
    return parent::run();
  }
}

