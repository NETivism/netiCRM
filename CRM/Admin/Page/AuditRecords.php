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
class CRM_Admin_Page_AuditRecords extends CRM_Core_Page {

  function run() {
    $date = date('Ymd000000', strtotime('-30Day'));
    $sql = "SELECT l.*, c.sort_name AS user_contact_name, c_modify.sort_name AS modified_name FROM civicrm_log l LEFT JOIN civicrm_uf_match um ON l.entity_id = um.uf_id LEFT JOIN civicrm_contact c ON um.contact_id = c.id LEFT JOIN civicrm_contact c_modify ON l.modified_id = c_modify.id WHERE entity_table LIKE 'audit.%' AND l.modified_date > %1 ORDER BY l.modified_date DESC";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($date, 'String')));
    while ($dao->fetch()) {
      $auditStatusName = '';
      $userId = '';
      $data = '';
      switch ($dao->entity_table) {
        case 'audit.users.name':
          $auditStatusName = ts('Website User Name Changed');
          $userId = $dao->entity_id;
          $data = ts("User name before changed").': '.$dao->data;
          break;
        case 'audit.users.pass':
          $auditStatusName = ts('Website User Password Changed');
          $userId = $dao->entity_id;
          break;
        case 'audit.users.roles':
          $auditStatusName = ts('Website User Roles Changed');
          $userId = $dao->entity_id;
          $data = ts("Role IDs before changed").': '.$dao->data;
          break;
        case 'audit.users.civicrm':
          $auditStatusName = ts('New CiviCRM Access User');
          $userId = $dao->entity_id;
          break;
        case 'audit.civicrm.export':
          $data = json_decode($dao->data, TRUE);
          $auditStatusName = ts('Export Records').": ".CRM_Export_BAO_Export::getExportName($data['Type']);
          break;
        case 'audit.civicrm.security.option':
          $data = json_decode($dao->data, TRUE);
          $auditStatusName = ts('Export excel file encryption settings option Changed');
          $data = $data['log'];
          break;
        case 'audit.civicrm.security.pwd':
          $auditStatusName = ts('Export excel file encryption settings password Changed');
          break;
        default:
          $auditStatusName = ts('Other');
          break;
      }
      $rows[] = array(
        'audit_type' => $dao->entity_table,
        'state' => $auditStatusName,
        'user_contact_name' => $dao->user_contact_name,
        'user_id' => $userId,
        'time' => $dao->modified_date,
        'modified_id' => $dao->modified_id,
        'modified_name' => $dao->modified_name,
        'data' => $data,
        'entity_id' => $dao->entity_id,
      );
    }
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('rows', $rows);

    return parent::run();
  }
}
