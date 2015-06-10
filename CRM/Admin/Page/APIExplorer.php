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

  function run() {
    if($this->allowVisit()){
      CRM_Utils_System::setTitle(ts('API explorer and generator'));
      $result = civicrm_api('Entity', 'get', array(
        'sequential' => 1,
        'version' => 3,
      ));
      self::$_template->assign('entities', $result);

      $civicrm_path = '/'.drupal_get_path('module', 'civicrm').'/';
      drupal_add_js(array('resourceBase' => $civicrm_path), 'setting');

      $this->assign('admin', user_access("administer CiviCRM"));
    }
    return parent::run();
  }

  function getTemplateFileName() {
    if($this->allowVisit()){
      return 'CRM/Core/AjaxDoc.tpl';
    }
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
    else{
      $pattern = '/dev.*neticrm\.tw/';
      if(preg_match($pattern, $_SERVER['HTTP_HOST'])){
        return TRUE;
      }
    }
    return FALSE;
  }
}
