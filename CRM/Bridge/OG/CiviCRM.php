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
class CRM_Bridge_OG_CiviCRM {

  static function group($groupID, $group, $op) {
    if ($op == 'add') {
      self::groupAdd($groupID, $group);
    }
    else {
      self::groupDelete($groupID, $group);
    }
  }

  static function groupAdd($groupID, $group) {
    require_once 'CRM/Bridge/OG/Utils.php';
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID, FALSE);

    $node = new stdClass();
    if ($ogID) {
      $node->nid = $ogID;
    }

    global $user;
    $node->uid = $user->uid;
    $node->title = $group->title;
    $node->type = 'og';
    $node->status = 1;

    // set the og values
    $node->og_description = $group->description;
    $node->og_selective = OF_OPEN;
    $node->og_register = 0;
    $node->og_directory = 1;

    node_save($node);

    // also change the source field of the group
    CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Group',
      $groupID,
      'source',
      CRM_Bridge_OG_Utils::ogSyncName($node->nid)
    );
  }

  static function groupDelete($groupID, $group) {
    require_once 'CRM/Bridge/OG/Utils.php';
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID, FALSE);
    if (!$ogID) {
      return;
    }

    node_delete($ogID);
  }

  static function groupContact($groupID, $contactIDs, $op) {
    require_once 'CRM/Bridge/OG/Utils.php';
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID, FALSE);
    if (!$ogID) {
      return;
    }

    require_once 'api/v2/UFGroup.php';
    foreach ($contactIDs as $contactID) {
      $drupalID = civicrm_uf_id_get($contactID);
      if ($drupalID) {
        if ($op == 'add') {
          og_save_subscription($ogID, $drupalID, array('is_active' => 1));
        }
        else {
          og_delete_subscription($ogID, $drupalID);
        }
      }
    }
  }
}

