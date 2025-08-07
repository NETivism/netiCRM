<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This is base class for all ajax calls
 */
class CRM_Core_Page_AJAX {

  /**
   * function to call generic ajax forms
   *
   * @static
   * @access public
   */
  static function run() {
    $className = CRM_Utils_Type::escape($_REQUEST['class_name'], 'String');
    $type = '';
    if (CRM_Utils_Array::value('type', $_POST)) {
      $type = CRM_Utils_Type::escape($_POST['type'], 'String');
    }

    if (!$className) {
      CRM_Core_Error::fatal(ts('Invalid className: %1', [1 => $className]));
    }

    $fnName = NULL;
    if (isset($_REQUEST['fn_name'])) {
      $fnName = CRM_Utils_Type::escape($_REQUEST['fn_name'], 'String');
    }

    if (!self::checkAuthz($type, $className, $fnName)) {
      CRM_Utils_System::civiExit();
    }

    switch ($type) {
      case 'method':
        call_user_func([$className, $fnName]);
        break;

      case 'page':
      case 'class':
      case '':
        // FIXME: This is done to maintain current wire protocol, but it might be
        // simpler to just require different 'types' for pages and forms
        if (preg_match('/^CRM_[a-zA-Z0-9]+_Page_Inline_/', $className)) {
          $page = new $className;
          $page->run();
        }
        else {
          $wrapper = new CRM_Utils_Wrapper();
          $wrapper->run($className);
        }
        break;

      default:
        CRM_Core_Error::debug_log_message('Unsupported inline request type: ' . var_export($type, TRUE));
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * function to change is_quick_config priceSet to complex
   *
   * @static
   * @access public
   */
  static function setIsQuickConfig() {
    if (!$id = CRM_Utils_Array::value('id', $_GET)) {
      return FALSE;
    }
    $priceSetId = CRM_Price_BAO_Set::getFor($_GET['context'], $id);
    if ($priceSetId) {
      $result = CRM_Price_BAO_Set::setIsQuickConfig($priceSetId, 0);
    }
    if (!$result) {
      $priceSetId = NULL;
    }
    echo json_encode($priceSetId);

    CRM_Utils_System::civiExit();
  }

  /**
   * Determine whether the request is for a valid class/method name.
   *
   * @param string $type 'method'|'class'|''
   * @param string $className 'Class_Name'
   * @param string $fnName method name
   */
  static function checkAuthz($type, $className, $fnName = NULL) {
    switch ($type) {
      case 'method':
        if (!preg_match('/^CRM_[a-zA-Z0-9]+_Page_AJAX$/', $className)) {
          return FALSE;
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $fnName)) {
          return FALSE;
        }

        // ensure that function exists
        return method_exists($className, $fnName);

      case 'page':
      case 'class':
      case '':
        if (!preg_match('/^CRM_[a-zA-Z0-9]+_(Page|Form)_Inline_[a-zA-Z0-9]+$/', $className)) {
          return FALSE;
        }
        return class_exists($className);

      default:
        return FALSE;
    }
  }
  /**
   * Guards against CSRF by validating the request method appears to be an ajax request
   */
  public static function validateAjaxRequestMethod() {
    if (!CRM_Utils_REST::isWebServiceRequest()) {
      http_response_code(400);
      CRM_Core_Error::debug_log_message('SECURITY ALERT: Ajax requests can only be issued by javascript clients.');
      CRM_Core_Error::debug_var('ajax_request_info', [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '',
          'reason' => 'CSRF suspected',
      ]);
      throw new CRM_Core_Exception('SECURITY ALERT: Ajax requests can only be issued by javascript clients.');
    }
  }
}

