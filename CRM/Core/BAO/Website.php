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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * This class contain function for Website handling
 */
class CRM_Core_BAO_Website extends CRM_Core_DAO_Website {

  /**
   * Add or update a website record.
   *
   * @param array &$params associative array of website data
   *
   * @return CRM_Core_DAO_Website the created/updated website object
   */
  public static function add(&$params) {
    $website = new CRM_Core_DAO_Website();
    $website->copyValues($params);
    return $website->save();
  }

  /**
   * Process and synchronize multiple website records for a contact.
   *
   * @param array $params nested array of website parameters
   * @param int $contactID contact ID
   * @param bool $skipDelete whether to delete existing websites not in the parameters
   *
   * @return void|bool FALSE if params are empty
   */
  public static function create(&$params, $contactID, $skipDelete) {
    if (empty($params)) {
      return FALSE;
    }

    $ids = self::allWebsites($contactID);

    foreach ($params as $key => $values) {
      $websiteId = CRM_Utils_Array::value('id', $values);
      if ($websiteId) {
        if (CRM_Utils_Array::arrayKeyExists($websiteId, $ids)) {
          unset($ids[$websiteId]);
        }
        else {
          unset($values['id']);
        }
      }

      if (!CRM_Utils_Array::value('id', $values) &&
        is_array($ids) && !empty($ids)
      ) {
        foreach ($ids as $id => $value) {
          if ($value['website_type_id'] == $values['website_type_id']) {
            $values['id'] = $id;
            unset($ids[$id]);
            break;
          }
        }
      }
      $values['contact_id'] = $contactID;
      self::add($values);
    }

    if ($skipDelete && !empty($ids)) {
      self::del(array_keys($ids));
    }
  }

  /**
   * Delete one or more website records.
   *
   * @param int[] $ids array of website IDs to delete
   *
   * @return void
   */
  public static function del($ids) {
    $query = 'DELETE FROM civicrm_website WHERE id IN ( ' . CRM_Utils_Array::implode(',', $ids) . ')';
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Fetch website values for a specific contact.
   *
   * @param array &$params associative array containing 'contact_id'
   * @param array &$values array to store retrieved values
   *
   * @return array array of website data arrays
   */
  public static function &getValues(&$params, &$values) {
    $websites = [];
    $website = new CRM_Core_DAO_Website();
    $website->contact_id = $params['contact_id'];
    $website->find();

    $count = 1;
    while ($website->fetch()) {
      $values['website'][$count] = [];
      CRM_Core_DAO::storeValues($website, $values['website'][$count]);

      $websites[$count] = $values['website'][$count];
      $count++;
    }

    return $websites;
  }

  /**
   * Get all websites for a specified contact.
   *
   * @param int $id contact ID
   * @param bool $updateBlankLocInfo if TRUE, return indexed sequentially; otherwise by ID
   *
   * @return array array of website details
   */
  public static function allWebsites($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = '
SELECT  id, website_type_id
  FROM  civicrm_website
 WHERE  civicrm_website.contact_id = %1';
    $params = [1 => [$id, 'Integer']];

    $websites = $values = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = ['id' => $dao->id,
        'website_type_id' => $dao->website_type_id,
      ];

      if ($updateBlankLocInfo) {
        $websites[$count++] = $values;
      }
      else {
        $websites[$dao->id] = $values;
      }
    }
    return $websites;
  }
}
