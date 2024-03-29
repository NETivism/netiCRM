<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of contact Subtypes
 */
class CRM_Admin_Page_APIExplorer extends CRM_Core_Page {
  const PUBLIC_API = 'Activity,Contact,Contribution,ContributionPage,Event,Participant,Membership,Address,Email,Phone,CustomValue,Group,GroupContact,Tag,EntityTag';

  function run() {
    if($this->allowVisit()){
      $config = CRM_Core_Config::singleton();
      CRM_Utils_System::setTitle(ts('API explorer and generator'));
      $publicAPI = explode(',', self::PUBLIC_API);
      self::$_template->assign('entities', $publicAPI);

      $this->assign('admin', CRM_Core_Permission::check("administer CiviCRM") && $config->debug);
    }
    return parent::run();
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/apibrowser';
  }

  function allowVisit() {
    if(defined('CIVICRM_APIEXPLORER_ENABLED') && CIVICRM_APIEXPLORER_ENABLED == 1){
      return TRUE;
    }
    return FALSE;
  }

}
