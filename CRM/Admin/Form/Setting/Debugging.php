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

require_once 'CRM/Admin/Form/Setting.php';

/**
 * This class generates form components for Error Handling and Debugging
 *
 */
class CRM_Admin_Form_Setting_Debugging extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts(' Settings - Debugging and Error Handling '));

    $config = CRM_Core_Config::singleton();

    $this->addYesNo('debug', ts('Enable Debugging'));
    $this->addYesNo('debugDatabaseProfiling', ts('Enable Database Profiling'));
    $this->addYesNo('userFrameworkLogging', ts('Enable Drupal Watchdog Logging'));
    $this->addElement('text', 'fatalErrorHandler', ts('Fatal Error Handler'));

    parent::buildQuickForm();
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function preProcess() {
    // Reference from CRM/Core/Error.php Line.397
    $config = CRM_Core_Config::singleton();
    $fileName = "{$config->configAndLogDir}CiviCRM." . $comp . md5($config->dsn . $config->userFrameworkResourceURL) . '.log';
    $this->assign('logPath',$fileName);
  }
}

