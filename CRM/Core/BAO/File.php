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
 * BAO object for crm_log table
 */
class CRM_Core_BAO_File extends CRM_Core_DAO_File {
  const PUBLIC_ENTITY_TABLE = 'civicrm_pcp';

  function path($fileID, $entityID, $entityTable = NULL, $quest = FALSE) {

    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if ($entityTable) {
      $entityFileDAO->entity_table = $entityTable;
    }
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->file_id = $fileID;

    if ($entityFileDAO->find(TRUE)) {
      $fileDAO = new CRM_Core_DAO_File();
      $fileDAO->id = $fileID;
      if ($fileDAO->find(TRUE)) {
        $config = CRM_Core_Config::singleton();
        if ($quest) {
          if ($quest == '1') {
            // to make quest part work as before
            $path = $config->customFileUploadDir . 'Student' . DIRECTORY_SEPARATOR . $entityID . DIRECTORY_SEPARATOR . $fileDAO->uri;
          }
          else {
            $path = $config->customFileUploadDir . $quest . DIRECTORY_SEPARATOR . $entityID . DIRECTORY_SEPARATOR . $fileDAO->uri;
          }
        }
        else {
          $path = $config->customFileUploadDir . $fileDAO->uri;
        }

        if (file_exists($path) && is_readable($path)) {
          return array($path, $fileDAO->mime_type, $entityFileDAO->entity_table);
        }
      }
    }

    return array(NULL, NULL, NULL);
  }

  function url($fileID, $entityID, $entityTable = NULL, $quest = FALSE) {
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if ($entityTable) {
      $entityFileDAO->entity_table = $entityTable;
    }
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->file_id = $fileID;

    if ($entityFileDAO->find(TRUE)) {
      // display url only when file section is public
      $publicFileSection = explode(',', CRM_Core_BAO_File::PUBLIC_ENTITY_TABLE);
      if (in_array($entityFileDAO->entity_table, $publicFileSection) || substr($entityFileDAO->entity_table, 0,13) == 'civicrm_value') {
        $fileDAO = new CRM_Core_DAO_File();
        $fileDAO->id = $fileID;
        if ($fileDAO->find(TRUE)) {
          $config = CRM_Core_Config::singleton();
          if ($quest) {
            if ($quest == '1') {
              // to make quest part work as before
              $path = $config->customFileUploadURL. 'Student' . DIRECTORY_SEPARATOR . $entityID . DIRECTORY_SEPARATOR . $fileDAO->uri;
            }
            else {
              $path = $config->customFileUploadURL . $quest . DIRECTORY_SEPARATOR . $entityID . DIRECTORY_SEPARATOR . $fileDAO->uri;
            }
          }
          else {
            $path = $config->customFileUploadURL . $fileDAO->uri;
          }

          return array($path, $fileDAO->mime_type, $entityFileDAO->entity_table);
        }
      }
    }

    return array(NULL, NULL, NULL);
  }


  public function filePostProcess($data, $fileID,
    $entityTable, $entityID,
    $entitySubtype, $overwrite = TRUE,
    $fileParams = NULL,
    $uploadName = 'uploadFile',
    $mimeType
  ) {

    $config = &CRM_Core_Config::singleton();

    $path = explode('/', $data);
    $filename = $path[count($path) - 1];

    // rename this file to go into the secure directory
    if ($entitySubtype) {
      $directoryName = $config->customFileUploadDir . $entitySubtype . DIRECTORY_SEPARATOR . $entityID;
    }
    else {
      $directoryName = $config->customFileUploadDir;
    }

    CRM_Utils_File::createDir($directoryName);

    if (!rename($data, $directoryName . DIRECTORY_SEPARATOR . $filename)) {
      CRM_Core_Error::fatal(ts('Could not move custom file to custom upload directory'));
      return;
    }

    // to get id's
    if ($overwrite && $fileID) {
      list($sql, $params) = self::sql($entityTable, $entityID, $fileID);
    }
    else {
      list($sql, $params) = self::sql($entityTable, $entityID, 0);
    }

    $dao = &CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();

    if (!$mimeType) {
      CRM_Core_Error::fatal();
    }

    $fileDAO = new CRM_Core_DAO_File();
    if (isset($dao->cfID) &&
      $dao->cfID
    ) {
      $fileDAO->id = $dao->cfID;
      unlink($directoryName . DIRECTORY_SEPARATOR . $dao->uri);
    }

    if (!empty($fileParams)) {
      $fileDAO->copyValues($fileParams);
    }

    $fileDAO->uri = $filename;
    $fileDAO->mime_type = $mimeType;
    $fileDAO->file_type_id = $fileID;
    $fileDAO->upload_date = date('Ymdhis');
    $fileDAO->save();

    // need to add/update civicrm_entity_file
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if (isset($dao->cefID) &&
      $dao->cefID
    ) {
      $entityFileDAO->id = $dao->cefID;
    }
    $entityFileDAO->entity_table = $entityTable;
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->file_id = $fileDAO->id;
    $entityFileDAO->save();
  }

  public function delete($fileID, $entityID, $fieldID) {
    // get the table and column name
    list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($fieldID);

    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    $entityFileDAO->file_id = $fileID;
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->entity_table = $tableName;

    if ($entityFileDAO->find(TRUE)) {
      $entityFileDAO->delete();
    }
    else {
      CRM_Core_Error::fatal();
    }

    $fileDAO = new CRM_Core_DAO_File();
    $fileDAO->id = $fileID;
    if ($fileDAO->find(TRUE)) {
      $fileDAO->delete();
    }
    else {
      CRM_Core_Error::fatal();
    }

    // also set the value to null of the table and column
    $query = "UPDATE $tableName SET $columnName = null WHERE $columnName = %1";
    $params = array(1 => array($fileID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * delete all the files and associated object associated with this
   * combination
   */
  public function deleteEntityFile($entityTable, $entityID) {
    if (empty($entityTable) ||
      empty($entityID)
    ) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    list($sql, $params) = self::sql($entityTable, $entityID, NULL);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $cfIDs = array();
    $cefIDs = array();
    while ($dao->fetch()) {
      unlink($config->customFileUploadDir . DIRECTORY_SEPARATOR . $dao->uri);
      $cfIDs[] = $dao->cfID;
      $cefIDs[] = $dao->cefID;
    }

    if (!empty($cefIDs)) {
      $cefIDs = implode(',', $cefIDs);
      $sql = "DELETE FROM civicrm_entity_file where id IN ( $cefIDs )";
      CRM_Core_DAO::executeQuery($sql);
    }

    if (!empty($cfIDs)) {
      $cfIDs = implode(',', $cfIDs);
      $sql = "DELETE FROM civicrm_file where id IN ( $cfIDs )";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * get all the files and associated object associated with this
   * combination
   */
  public function &getEntityFile($entityTable, $entityID) {
    static $entityFiles;
    $config = CRM_Core_Config::singleton();
    if (!empty($entityFiles[$entityTable][$entityID])) {
      return $entityFiles[$entityTable][$entityID];
    }

    list($sql, $params) = self::sql($entityTable, $entityID, NULL);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $result['fileID'] = $dao->cfID;
      $result['entityID'] = $dao->cefID;
      $result['mime_type'] = $dao->mime_type;
      $result['fileName'] = $dao->uri;
      $result['cleanName'] = CRM_Utils_File::cleanFileName($dao->uri);
      $result['fullPath'] = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $dao->uri;
      $result['url'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$dao->cfID}&eid={$entityID}");
      $result['href'] = "<a href=\"{$result['url']}\" target=\"_blank\">{$result['cleanName']}</a>";
      if (strstr($dao->mime_type, 'image')) {
        $result['img'] = '<a href="'.$result['url'].'" target="_blank"><img src="'.$result['url'].'" width="150"></a>';
      }
      $entityFiles[$entityTable][$entityID][$dao->cfID] = $result;
    }
    return $entityFiles[$entityTable][$entityID];
  }

  public function sql($entityTable, $entityID, $fileID = NULL) {
    $sql = "
SELECT    CF.id as cfID,
          CF.uri as uri,
          CF.mime_type as mime_type,
          CEF.id as cefID
FROM      civicrm_file AS CF
LEFT JOIN civicrm_entity_file AS CEF ON ( CEF.file_id = CF.id )
WHERE     CEF.entity_table = %1
AND       CEF.entity_id    = %2";
    $params = array(1 => array($entityTable, 'String'),
      2 => array($entityID, 'Integer'),
    );

    if ($fileID !== NULL) {
      $sql .= " AND CF.file_type_id = %3";
      $params[3] = array($fileID, 'Integer');
    }

    return array($sql, $params);
  }

  static function buildAttachment(&$form, $entityTable, $entityID = NULL, $numAttachments = NULL, $attr = array()) {

    $config = CRM_Core_Config::singleton();

    if (!$numAttachments) {
      $numAttachments = $config->maxAttachments;
    }
    if ($entityID) {
      $currentAttachments = self::getEntityFile($entityTable, $entityID);
      $numAttachments -= count($currentAttachments);
    }
    if ($numAttachments > 0 ) {
      // set default max file size as 2MB
      $maxFileSize = $config->maxFileSize ? $config->maxFileSize : 10;
      $attributes = array(
        'size' => 30,
        'maxlength' => 60,
      );
      if (is_array($attr) && !empty($attr)) {
        $attributes = array_merge($attributes, $attr);
      }

      $form->assign('numAttachments', $numAttachments);
      // add attachments
      $form->addFile("attachFile[]", ts('Attach File'), $attributes);
      $form->setMaxFileSize($maxFileSize * 1024 * 1024);
      $form->assign('maxFileSize', $maxFileSize);
      $form->addRule(
        "attachFile[]",
        ts('File size should be less than %1 MByte(s)', array(1 => $maxFileSize)),
        'maxfilesize',
        $maxFileSize * 1024 * 1024
      );
    }

    $attachmentInfo = self::attachmentInfo($entityTable, $entityID);
    if ($attachmentInfo) {
      $form->add('checkbox', 'is_delete_attachment', ts('Delete Current Attachment(s)'));
      $form->assign('currentAttachmentURL', $attachmentInfo);
    }
    else {
      $form->assign('currentAttachmentURL', NULL);
    }
  }

  static function attachmentInfo($entityTable, $entityID, $separator = '<br />') {
    if (!$entityID) {
      return NULL;
    }

    $currentAttachments = self::getEntityFile($entityTable, $entityID);
    if (!empty($currentAttachments)) {
      $currentAttachmentURL = array();
      foreach ($currentAttachments as $fileID => $attach) {
        if (!empty($attach['img'])) {
          $currentAttachmentURL[] = $attach['img'];
        }
        else {
          $currentAttachmentURL[] = $attach['href'];
        }
      }
      return implode(' ', $currentAttachmentURL);
    }
    return NULL;
  }

  static function formatAttachment(&$formValues,
    &$params,
    $entityTable,
    $entityID = NULL,
    $maxAttachments
  ) {

    // delete current attachments if applicable
    if ($entityID && (CRM_Utils_Array::value('is_delete_attachment', $formValues))) {
      CRM_Core_BAO_File::deleteEntityFile($entityTable, $entityID);
    }

    $config = CRM_Core_Config::singleton();
    $numAttachments = $maxAttachments ? $maxAttachments : $config->maxAttachments;
    if ($entityID) {
      $currentAttachments = self::getEntityFile($entityTable, $entityID);
      $numAttachments -= count($currentAttachments);
    }

    // setup all attachments
    $attachName = "attachFile[]";
    if (isset($formValues[$attachName]) && !empty($formValues[$attachName])) {
      for ($i = 0; $i < $numAttachments; $i++) {
        // ensure file is not empty
        $contents = file_get_contents($formValues[$attachName][$i]['name']);
        if ($contents) {
          $fileParams = array(
            'uri' => $formValues[$attachName][$i]['name'],
            'type' => $formValues[$attachName][$i]['type'],
            'upload_date' => date('Ymdhis'),
            'location' => $formValues[$attachName][$i]['name'],
          );
          $params['attachFile_'.$i] = $fileParams;
        }
      }
    }
  }

  static function processAttachment(&$params,
    $entityTable,
    $entityID,
    $maxAttachments = 0
  ) {
    $config = CRM_Core_Config::singleton();
    $numAttachments = $maxAttachments ? $maxAttachments : $config->maxAttachments;
    

    for ($i = 0; $i < $numAttachments; $i++) {
      if (isset($params["attachFile_$i"]) &&
        is_array($params["attachFile_$i"])
      ) {
        self::filePostProcess($params["attachFile_$i"]['location'],
          NULL,
          $entityTable,
          $entityID,
          NULL,
          TRUE,
          $params["attachFile_$i"],
          "attachFile_$i",
          $params["attachFile_$i"]['type']
        );
      }
    }
  }

  static function uploadNames() {
    $config = CRM_Core_Config::singleton();
    $numAttachments = 3;

    $names = array(
      "uploadFile",
      "attachFile",
      "attachFile[]",
    );
    for ($i = 0; $i < $numAttachments; $i++) {
      $names[] = "attachFile_{$i}";
    }
    return $names;
  }

  /*
     * Function to copy/attach an existing file to a different entity
     * table and id.
     */

  static function copyEntityFile($oldEntityTable, $oldEntityId, $newEntityTable, $newEntityId) {
    $oldEntityFile = new CRM_Core_DAO_EntityFile();
    $oldEntityFile->entity_id = $oldEntityId;
    $oldEntityFile->entity_table = $oldEntityTable;
    $oldEntityFile->find();

    while ($oldEntityFile->fetch()) {
      $newEntityFile = new CRM_Core_DAO_EntityFile();
      $newEntityFile->entity_id = $newEntityId;
      $newEntityFile->entity_table = $newEntityTable;
      $newEntityFile->file_id = $oldEntityFile->file_id;
      $newEntityFile->save();
    }
  }
}

