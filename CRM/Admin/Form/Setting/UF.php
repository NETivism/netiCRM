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
 * This class generates form components for Site Url
 *
 */
class CRM_Admin_Form_Setting_UF extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $uf = $config->userFramework;

    CRM_Utils_System::setTitle(ts('Settings - %1 Integration',
        [1 => $uf]
      ));

    $ele = $this->addElement('text', 'userSystemVersion', ts('%1 Version', [1 => $uf]));
    $ele->freeze();
    $this->addElement('text', 'userFrameworkUsersTableName', ts('%1 Users Table Name', [1 => $uf]));
    if (CRM_Utils_System::moduleExists('views') && $config->dsn != $config->userFrameworkDSN) {
      $dsnArray = DB::parseDSN($config->dsn);
      $tableNames = CRM_Core_DAO::GetStorageValues(NULL, 0, 'Name');
      $tablePrefixes = '$db_prefix = array(';
      foreach ($tableNames as $tableName => $value) {
        $tablePrefixes .= "\n  '" . str_pad($tableName . "'", 41) . " => '{$dsnArray['database']}.',";
      }
      $tablePrefixes .= "\n);";
      $this->assign('tablePrefixes', $tablePrefixes);
    }

    parent::buildQuickForm();
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = [];
    $params = [];

    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    $defaults['userSystemVersion'] = $version;

    return $defaults;
  }
}

