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

require_once 'CRM/Contribute/DAO/TaiwanACH.php';
class CRM_Contribute_BAO_TaiwanACH extends CRM_Contribute_DAO_TaiwanACH {

  /**
   * takes an associative array and creates a contribution object
   *
   * the function extract all the params it needs to initialize the create a
   * contribution object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Contribute_BAO_Contribution object
   * @access public
   * @static
   */
  static function add(&$params) {

    // pre-processing hooks
    require_once 'CRM/Utils/Hook.php';
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'TaiwanACH', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'TaiwanACH', NULL, $params);
    }

    if ($params['id']) {
      $oldTaiwanACH = new CRM_Contribute_DAO_TaiwanACH();
      $oldTaiwanACH->id = $params['id'];
      $oldTaiwanACH->find(TRUE);
    }

    $taiwanACH = new CRM_Contribute_DAO_TaiwanACH();
    $taiwanACH->copyValues($params);

    $recurring = new CRM_Contribute_DAO_ContributionRecur();
    $recurParams = array();
    $recurringFields = $recurring->fields();
    foreach ($recurringFields as $field) {
      $fieldName = $field['name'];
      if (isset($params[$fieldName])) {
        $recurParams[$fieldName] = $params[$fieldName];
      }
    }
    $recurParams['create_date'] = date('YmdHis');
    $ids = array();
    if (!empty($taiwanACH->contribution_recur_id)) {
      $recurParams['id'] = $taiwanACH->contribution_recur_id;
    }
    $recurring = CRM_Contribute_BAO_ContributionRecur::add($recurParams, $ids);
    if (empty($taiwanACH->contribution_recur_id)) {
      $taiwanACH->contribution_recur_id = $recurring->id;
    }

    // set currency for CRM-1496
    if (!isset($taiwanACH->currency)) {
      $config = CRM_Core_Config::singleton();
      $taiwanACH->currency = $config->defaultCurrency;
    }

    if (isset($taiwanACH->data) && is_array($taiwanACH->data)) {
      $taiwanACH->data = serialize($taiwanACH->data);
    }

    $result = $taiwanACH->save();

    // create post-processing hooks
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'TaiwanACH', $taiwanACH->id, $taiwanACH);
    }
    else {
      CRM_Utils_Hook::post('create', 'TaiwanACH', $taiwanACH->id, $taiwanACH);
    }

    return $result;
  }

  static function addNote($taiwanACHId, $title, $body = NULL) {
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (empty($userId)) {
      $userId = "NULL";
    }
    $noteParams = array(
      'entity_table'  => 'civicrm_contribution_recur',
      'subject'       => $title,
      'note'          => $body,
      'entity_id'     => $taiwanACHId,
      'contact_id'    => $userId,
      'modified_date' => date('YmdHis'),
    );
    $note = CRM_Core_BAO_Note::add( $noteParams, NULL );
  }

  static function getValue($recurringId) {
    $output = array();

    $taiwanACH = new CRM_Contribute_DAO_TaiwanACH();
    $taiwanACH->contribution_recur_id = $recurringId;
    $taiwanACH->find(TRUE);
    $taiwanACH->data = unserialize($taiwanACH->data);
    $taiwanACHFields = $taiwanACH->fields();
    foreach ($taiwanACHFields as $field) {
      $fieldName = $field['name'];
      $output[$fieldName] = $taiwanACH->$fieldName;
    }

    $recurring = new CRM_Contribute_DAO_ContributionRecur();
    $recurring->id = $recurringId;
    $recurring->find(TRUE);
    $recurringFields = $recurring->fields();
    foreach ($recurringFields as $field) {
      $fieldName = $field['name'];
      if ($fieldName != 'id') {
        $output[$fieldName] = $recurring->$fieldName;
      }
    }

    return $output;
  }

  static function getTaiwanACHDatas($recurringIds = array()) {
    $achDatas = array();
    foreach ($recurringIds as $recurringId) {
      $achDatas[$recurringId] = self::getValue($recurringId);
    }
    return $achDatas;
  }

  static function doExportVerification($recurringIds = array(), $params = array(), $type = 'txt') {
    // Generate Body Table
    $fileName = $params['file_name'];



    // If type != 'txt'
    if ($type == 'txt') {
      // Generate Header
      $date = $params['date'];
      $time = $params['time'];
      $account = CRM_Core_BAO_PaymentProcessor::getPayment();
      $header = array(
        'BOF',
        'ACHP02',
        $date,
        $time,
        $account,
        'V10',
        ' ',
      );
      // Generate Footer
      $total = count($recurringIds);
      $footer = array(
        'EOF',
        $total,
        ' ',
      );

    }


    // Export File
    if ($type == 'txt') {
      self::doExportTXTFile($fileName, $table);
    }
    else {
      self::doExportXSLFile($fileName, $table);
    }
  }

  static function doExportTransaction($recurringIds, $params = array(),$type = 'txt') {
    // Export File

    // Generate Header

    // Generate Body

    // Generate Footer

    // Export file
  }

  static private function doExportTXTFile($fileName, $txt) {
    $config = CRM_Core_Config::singleton();
    $tmpDir = empty($config->uploadDir) ? CIVICRM_TEMPLATE_COMPILEDIR : $config->uploadDir;
    $fileName = CRM_Utils_File::makeFileName($fileName);
    $fileFullPath = $tmpDir.'/'.$fileName;
    file_put_contents($fileFullPath, $txt, FILE_APPEND);
    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename=' . $fileName);
    header('Pragma: no-cache');
    echo file_get_contents($fileFullPath);
    exit;
  }

  static private function doExportXSLFile($fileName, $txt) {

  }
}

