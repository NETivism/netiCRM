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


class CRM_Core_Page_File extends CRM_Core_Page {
  function run() {



    $entityId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $fieldId = CRM_Utils_Request::retrieve('fid', 'Positive', $this, FALSE);
    $fileId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $downloadName = CRM_Utils_String::safeFilename(CRM_Utils_Request::retrieve('download', 'String', $this, FALSE));
    $hash = CRM_Utils_Request::retrieve('fcs', 'Alphanumeric', $this);
    if (!CRM_Core_BAO_File::validateFileHash($hash, $entityId, $fileId)) {
      /** because drupal 6 still have problem of this...
      CRM_Core_Error::fatal('URL for file is not valid');
      */
    }
    $quest = CRM_Utils_Request::retrieve('quest', 'String', $this);
    $action = CRM_Utils_Request::retrieve('action', 'String', $this);


    list($path, $mimeType, $entityTable) = CRM_Core_BAO_File::path($fileId, $entityId, NULL, $quest);
    $publicFileSection = explode(',', CRM_Core_BAO_File::PUBLIC_ENTITY_TABLE);
    if (!in_array($entityTable, $publicFileSection)) {
      if (!CRM_Core_Permission::check('access uploaded files')) {
        CRM_Utils_System::permissionDenied();
        return;
      }
    }

    if (!$path) {
      CRM_Core_Error::fatal('Could not retrieve the file');
    }

    $buffer = file_get_contents($path);
    if ($buffer === FALSE) {
      CRM_Core_Error::fatal('The file is either empty or you do not have permission to retrieve the file');
    }

    if ($action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', CRM_Core_DAO::$_nullObject)) {
        CRM_Core_BAO_File::del($fileId, $entityId, $fieldId);
        CRM_Core_Session::setStatus(ts('The attached file has been deleted.'));
        $stay = CRM_Utils_Request::retrieve('stay', 'Boolean', CRM_Core_DAO::$_nullObject);
        $session = CRM_Core_Session::singleton();
        $toUrl = $session->popUserContext();

        if (!empty($stay) && !empty($_SERVER['HTTP_REFERER'])) {
          $url = parse_url($_SERVER['HTTP_REFERER']);
          if ($url['host'] === $_SERVER['SERVER_NAME']) {
            $toUrl = $_SERVER['HTTP_REFERER'];
          }
        }
        CRM_Utils_System::redirect($toUrl);
      }
      else {
        $wrapper = new CRM_Utils_Wrapper();
        return $wrapper->run('CRM_Custom_Form_DeleteFile', ts('Domain Information Page'), NULL);
      }
    }
    else {
      if ($downloadName) {
        $fileName = $downloadName;
        $fileExt = CRM_Utils_String::safeFilename(basename($path));
        $fileExt = pathinfo($fileExt, PATHINFO_EXTENSION);
        CRM_Utils_System::download($fileName, $mimeType, $buffer, $fileExt);
      }
      else {
        CRM_Utils_System::download(CRM_Utils_File::cleanFileName(basename($path)), $mimeType, $buffer);
      }
    }
  }
}

