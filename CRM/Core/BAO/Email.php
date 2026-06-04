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
 * This class contains functions for email handling
 */
class CRM_Core_BAO_Email extends CRM_Core_DAO_Email {

  /**
   * Low-level logic to add or update an email record.
   *
   * Normalizes the email address to lowercase before saving.
   *
   * @param array &$params associative array of name/value pairs
   *
   * @return CRM_Core_DAO_Email the created/updated email object
   */
  public static function add(&$params) {
    $email = new CRM_Core_DAO_Email();
    $email->copyValues($params);

    // lower case email field to optimize queries
    $email->email = mb_strtolower($email->email, 'UTF-8');
    self::holdEmail($email);

    return $email->save();
  }

  /**
   * Business logic to create or update an email record.
   *
   * Handles primary email logic, bulkmail uniqueness per contact,
   * and triggers hooks.
   *
   * @param array $params associative array of email data
   *
   * @return CRM_Core_DAO_Email the created/updated email object
   */
  public static function create(&$params) {
    CRM_Core_BAO_Block::handlePrimary($params, 'CRM_Core_BAO_Email');

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Email', CRM_Utils_Array::value('id', $params), $params);

    // since we're setting bulkmail for 1 of this contact's emails, first reset all their emails to is_bulkmail false
    // (only 1 email address can have is_bulkmail = true)
    if ($params['is_bulkmail'] == 1 && !empty($params['contact_id'])) {
      $sql = "
UPDATE civicrm_email 
SET is_bulkmail = 0
WHERE 
contact_id = {$params['contact_id']}";
      if (!empty($params['id']) && intval($params['id'])) {
        $sql .= " AND id <> {$params['id']}";
      }
      CRM_Core_DAO::executeQuery($sql);
    }

    $email = self::add($params);

    if (!empty($email->is_primary)) {
      // update the UF user email if that has changed
      CRM_Core_BAO_UFMatch::updateUFName($email->contact_id);
    }

    CRM_Utils_Hook::post($hook, 'Email', $email->id, $email);
    return $email;
  }

  /**
   * Fetch email values based on entity criteria.
   *
   * @param array $entityBlock associative array containing 'contact_id' or 'entity_table'/'entity_id'
   *
   * @return array|null array of email data arrays
   */
  public static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('email', $entityBlock);
  }

  /**
   * Get all emails for a specified contact, ordered by primary email first.
   *
   * @param int $id the contact ID
   * @param bool $updateBlankLocInfo if TRUE, return indexed sequentially; otherwise by ID
   *
   * @return array array of email details
   */
  public static function allEmails($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT email, civicrm_location_type.name as locationType, civicrm_email.is_primary as is_primary, civicrm_email.on_hold as on_hold,
civicrm_email.id as email_id, civicrm_email.location_type_id as locationTypeId, civicrm_email.is_bulkmail
FROM      civicrm_contact
LEFT JOIN civicrm_email ON ( civicrm_email.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_email.location_type_id = civicrm_location_type.id )
WHERE
  civicrm_contact.id = %1
ORDER BY
  civicrm_email.is_primary DESC, email_id ASC ";
    $params = [1 => [$id, 'Integer']];

    $emails = $values = [];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = ['locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'on_hold' => $dao->on_hold,
        'id' => $dao->email_id,
        'email' => $dao->email,
        'locationTypeId' => $dao->locationTypeId,
        'is_bulkmail' => $dao->is_bulkmail,
      ];

      if ($updateBlankLocInfo) {
        $emails[$count++] = $values;
      }
      else {
        $emails[$dao->email_id] = $values;
      }
    }
    return $emails;
  }

  /**
   * Get all emails for a specified entity via its location block.
   *
   * @param array &$entityElements array containing 'entity_id' and 'entity_table'
   *
   * @return array|null array of email details
   */
  public static function allEntityEmails(&$entityElements) {
    if (empty($entityElements)) {
      return NULL;
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = " SELECT email, ltype.name as locationType, e.is_primary as is_primary, e.on_hold as on_hold,e.id as email_id, e.location_type_id as locationTypeId 
FROM civicrm_loc_block loc, civicrm_email e, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   e.id IN (loc.email_id, loc.email_2_id)
AND   ltype.id = e.location_type_id
ORDER BY e.is_primary DESC, email_id ASC ";

    $params = [1 => [$entityId, 'Integer']];

    $emails = [];
    $dao = &CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $emails[$dao->email_id] = ['locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'on_hold' => $dao->on_hold,
        'id' => $dao->email_id,
        'email' => $dao->email,
        'locationTypeId' => $dao->locationTypeId,
      ];
    }

    return $emails;
  }

  /**
   * Set or reset the 'on_hold' status for an email and record dates.
   *
   * @param CRM_Core_DAO_Email &$email email object (passed by reference)
   *
   * @return void
   */
  public static function holdEmail(&$email) {
    //check for update mode
    if ($email->id) {
      //get hold date
      $holdDate = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $email->id, 'hold_date');

      //get reset date
      $resetDate = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $email->id, 'reset_date');

      //set hold date only if it is not set and e
      if (($email->on_hold != 'null') && !$holdDate && $email->on_hold) {
        $email->hold_date = date('YmdHis');
        $email->reset_date = '';
      }
      elseif ($holdDate && ($email->on_hold == 'null') && !$resetDate) {
        //set reset date only if it is not set and if hold date is set
        $email->on_hold = FALSE;
        $email->hold_date = '';
        $email->reset_date = date('YmdHis');
      }
    }
    else {
      if (($email->on_hold != 'null') && $email->on_hold) {
        $email->hold_date = date('YmdHis');
      }
    }
  }

  /**
   * Check if an email value already exists for a contact and set the 'id' parameter.
   *
   * Only performs lookup if 'id' is not provided and email/contact_id are present.
   *
   * @param array &$params associative array of email fields (passed by reference)
   *
   * @return void
   */
  public static function valueExists(&$params) {
    if (empty($params['id']) && !empty($params['email']) && is_string($params['email']) && !empty($params['contact_id'])) {
      $check = preg_replace('/[^a-zA-Z0-9.@-]/', '', $params['email']);
      $params['id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_email WHERE REGEXP_REPLACE(email, '[^a-zA-Z0-9.@-]', '') LIKE %1 AND contact_id = %2", [
        1 => [$check, 'String'],
        2 => [$params['contact_id'], 'Integer']
      ]);
    }
  }
}
