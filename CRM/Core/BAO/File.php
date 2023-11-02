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

  static function path($fileID, $entityID, $entityTable = NULL, $quest = FALSE) {

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

  static function url($fileID, $entityID, $entityTable = NULL, $quest = FALSE) {
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


  public static function filePostProcess($data, $fileID,
    $entityTable, $entityID,
    $entitySubtype, $overwrite = TRUE,
    $fileParams = NULL,
    $uploadName = 'uploadFile',
    $mimeType = NULL
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

  public static function del($fileID, $entityID, $fieldID) {
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
      CRM_Core_Error::fatal("Can't find entity file data.");
    }

    $fileDAO = new CRM_Core_DAO_File();
    $fileDAO->id = $fileID;
    if ($fileDAO->find(TRUE)) {
      $config = &CRM_Core_Config::singleton();
      unlink($config->customFileUploadDir . DIRECTORY_SEPARATOR . $fileDAO->uri);
      $fileDAO->delete();
    }
    else {
      CRM_Core_Error::fatal("Can't find file DAO data.");
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
  public static function deleteEntityFile($entityTable, $entityID) {
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
      $cefIDs = CRM_Utils_Array::implode(',', $cefIDs);
      $sql = "DELETE FROM civicrm_entity_file where id IN ( $cefIDs )";
      CRM_Core_DAO::executeQuery($sql);
    }

    if (!empty($cfIDs)) {
      $cfIDs = CRM_Utils_Array::implode(',', $cfIDs);
      $sql = "DELETE FROM civicrm_file where id IN ( $cfIDs )";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  public static function getEntity($fileID) {
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    $entityFileDAO->file_id = $fileID;

    if ($entityFileDAO->find(TRUE)) {
      $return = array();
      CRM_Core_DAO::storeValues($entityFileDAO, $return);
      return $return;
    }
  }

  /**
   * get all the files and associated object associated with this
   * combination
   */
  public static function &getEntityFile($entityTable, $entityID) {
    static $entityFiles;
    $config = CRM_Core_Config::singleton();
    if (!empty($entityFiles[$entityTable][$entityID])) {
      return $entityFiles[$entityTable][$entityID];
    }
    else {
      $entityFiles[$entityTable][$entityID] = array();
    }

    list($sql, $params) = self::sql($entityTable, $entityID, NULL);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $fileHash = self::generateFileHash($dao->entity_id, $dao->cfID);
      $result['fileID'] = $dao->cfID;
      $result['entityID'] = $dao->cefID;
      $result['mime_type'] = $dao->mime_type;
      $result['fileName'] = $dao->uri;
      $result['cleanName'] = CRM_Utils_File::cleanFileName($dao->uri);
      $result['fullPath'] = rtrim($config->customFileUploadDir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $dao->uri;
      $result['url'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$dao->cfID}&eid={$entityID}&fcs=$fileHash");
      $result['url_real'] = rtrim($config->customFileUploadURL, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . urlencode($dao->uri);
      $result['href'] = "<a href=\"{$result['url']}\" target=\"_blank\">{$result['cleanName']}</a>";
      if (strstr($dao->mime_type, 'image')) {
        $imginfo = getimagesize($result['fullPath']);
        if (!empty($imginfo[0])) {
          $result['img'] = '<img src="'.$result['url'].'" '.$imginfo[3].' >';
          $result['img_real'] = '<img src="'.$result['url_real'].'" '.$imginfo[3].' >';
        }
      }
      $entityFiles[$entityTable][$entityID][$dao->cfID] = $result;
    }
    return $entityFiles[$entityTable][$entityID];
  }

  public static function sql($entityTable, $entityID, $fileID = NULL) {
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
      $form->addRule("attachFile[]", ts('File size should be less than %1 MByte(s)', array(1 => $maxFileSize)), 'maxfilesize', $maxFileSize * 1024 * 1024);
      $form->addRule('attachFile[]', ts('Image could not be uploaded due to invalid type extension.'), 'imageFile', '2000x2000');
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
      return CRM_Utils_Array::implode(' ', $currentAttachmentURL);
    }
    return NULL;
  }

  static function formatAttachment(&$formValues,
    &$params,
    $entityTable,
    $entityID = NULL,
    $maxAttachments = 0
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
        if (!empty($params["attachFile_$i"]['location']) && file_exists($params["attachFile_$i"]['location'])) {
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

  /**
   * Generates an access-token for downloading a specific file.
   *
   * @param int $entityId entity id the file is attached to
   * @param int $fileId file ID
   * @return string
   */
  public static function generateFileHash($entityId = NULL, $fileId = NULL, $genTs = NULL, $life = NULL) {
    // Use multiple (but stable) inputs for hash information.
    $siteKey = defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '';
    if (!$siteKey) {
      throw new \CRM_Core_Exception("Cannot generate file access token. Please set CIVICRM_SITE_KEY.");
    }

    // Trim 8 chars off the string, make it slightly easier to find
    // but reveals less information from the hash.
    if (!$genTs) {
      $genTs = CRM_REQUEST_TIME;
    }
    if (!$life) {
      $life = 24; // 1 day
    }
    // Trim 8 chars off the string, make it slightly easier to find
    // but reveals less information from the hash.
    $cs = hash_hmac('sha256', "entity={$entityId}&file={$fileId}&life={$life}", $siteKey);
    return "{$cs}_{$genTs}_{$life}";
    return substr(md5("{$siteKey}_{$entityId}_{$fileId}"), 8);
  }

  /**
   * Validate a file access token.
   *
   * @param string $hash
   * @param int $entityId Entity Id the file is attached to
   * @param int $fileId File Id
   * @return bool
   */
  public static function validateFileHash($hash, $entityId, $fileId) {
    $input = CRM_Utils_System::explode('_', $hash, 3);
    $inputTs = CRM_Utils_Array::value(1, $input);
    $inputLF = CRM_Utils_Array::value(2, $input);
    $testHash = CRM_Core_BAO_File::generateFileHash($entityId, $fileId, $inputTs, $inputLF);

    $success = FALSE;
    if(strlen($testHash) != strlen($hash)) {
      $success = FALSE;
    }
    else {
      $result = $testHash ^ $hash;
      $check = 0;
      for($i = strlen($result) - 1; $i >= 0; $i--) { 
        $check |= ord($result[$i]);
      }
      $success = !$check;
    }
    if ($success) {
      $now = CRM_REQUEST_TIME;
      if ($inputTs + ($inputLF * 60 * 60) >= $now) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Clear temporary upload dir
   * 
   * @param int $afterDays clear files that exists after n days.
   *
   * @return void
   */
  public static function clearUploadDir($afterDays = 7) {
    $config = CRM_Core_Config::singleton();
    if (is_dir($config->uploadDir) && trim($config->uploadDir) !== trim($config->customFileUploadDir) && trim($config->uploadDir) != trim($config->imageUploadDir)) {
      foreach (new DirectoryIterator($config->uploadDir) as $fileInfo) {
        if ($fileInfo->isDot()) {
          continue;
        }
        if ($fileInfo->isFile() && time() - $fileInfo->getMTime() >= $afterDays*24*60*60) {
          unlink($fileInfo->getRealPath());
        }
      }
    }
  }
}

