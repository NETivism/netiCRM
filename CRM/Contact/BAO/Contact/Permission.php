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
class CRM_Contact_BAO_Contact_Permission {
  CONST NUM_CONTACTS_TO_INSERT = 200;

  /**
   * check if the logged in user has permissions for the operation type
   *
   * @param int    $id   contact id
   * @param string $type the type of operation (view|edit)
   *
   * @return boolean true if the user has permission, false otherwise
   * @access public
   * @static
   */
  static function allow($id, $type = CRM_Core_Permission::VIEW) {
    $tables = [];
    $whereTables = [];

    # FIXME: push this somewhere below, to not give this permission so many rights
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'is_deleted');
    if (CRM_Core_Permission::check('access deleted contacts') and $isDeleted) {
      return TRUE;
    }

    //check permission based on relationship, CRM-2963
    if (self::relationship($id)) {
      return TRUE;
    }


    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables);


    $from = CRM_Contact_BAO_Query::getFromClause($whereTables);

    $query = "
SELECT count(DISTINCT contact_a.id) 
       $from
WHERE contact_a.id = %1 AND $permission";
    $params = [1 => [$id, 'Integer']];

    return (CRM_Core_DAO::singleValueQuery($query, $params) > 0) ? TRUE : FALSE;
  }

  /**
   * fill the acl contact cache for this contact id if empty
   *
   * @param int     $id     contact id
   * @param string  $type   the type of operation (view|edit)
   * @param boolean $force  should we force a recompute
   *
   * @return void
   * @access public
   * @static
   */
  static function cache($userID, $type = CRM_Core_Permission::VIEW, $force = FALSE) {
    static $_processed = [];

    if ($type = CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }

    if (!$force) {
      if (CRM_Utils_Array::value($userID, $_processed)) {
        return;
      }

      // run a query to see if the cache is filled
      $sql = "
SELECT count(id)
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause
";
      $params = [1 => [$userID, 'Integer']];
      $count = CRM_Core_DAO::singleValueQuery($sql, $params);
      if ($count > 0) {
        $_processed[$userID] = 1;
        return;
      }
    }

    $tables = [];
    $whereTables = [];


    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID);


    $from = CRM_Contact_BAO_Query::getFromClause($whereTables);

    $query = "
SELECT DISTINCT(contact_a.id) as id
       $from
WHERE $permission
";

    $values = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $values[] = "( {$userID}, {$dao->id}, '{$operation}' )";
    }

    // now store this in the table
    while (!empty($values)) {
      $processed = TRUE;
      $input = array_splice($values, 0, self::NUM_CONTACTS_TO_INSERT);
      $str = CRM_Utils_Array::implode(',', $input);
      $sql = "REPLACE INTO civicrm_acl_contact_cache ( user_id, contact_id, operation ) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_acl_contact_cache WHERE contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)');

    $_processed[$userID] = 1;
    return;
  }

  static function cacheClause($contactAlias = 'contact_a', $contactID = NULL) {
    if (CRM_Core_Permission::check('view all contacts')) {
      if (is_array($contactAlias)) {
        $wheres = [];
        foreach ($contactAlias as $alias) {
          // CRM-6181
          $wheres[] = "$alias.is_deleted = 0";
        }
        return [NULL, '(' . CRM_Utils_Array::implode(' AND ', $wheres) . ')'];
      }
      else {
        // CRM-6181
        return [NULL, "$contactAlias.is_deleted = 0"];
      }
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }
    $contactID = CRM_Utils_Type::escape($contactID, 'Integer');

    self::cache($contactID);

    if (is_array($contactAlias) && !empty($contactAlias)) {
      //More than one contact alias
      $clauses = [];
      foreach ($contactAlias as $k => $alias) {
        $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON {$alias}.id = aclContactCache_{$k}.contact_id AND aclContactCache_{$k}.user_id = $contactID ";
      }

      $fromClause = CRM_Utils_Array::implode(" ", $clauses);
      $whereClase = NULL;
    }
    else {
      $fromClause = " INNER JOIN civicrm_acl_contact_cache aclContactCache ON {$contactAlias}.id = aclContactCache.contact_id ";
      $whereClase = " aclContactCache.user_id = $contactID ";
    }

    return [$fromClause, $whereClase];
  }

  /**
   * Function to get the permission base on its relationship
   *
   * @param int $selectedContactId contact id of selected contact
   * @param int $contactId contact id of the current contact
   *
   * @return booleab true if logged in user has permission to view
   * selected contact record else false
   * @static
   */
  static function relationship($selectedContactID, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    if (!$contactID) {
      $contactID = $session->get('userID');
      if (!$contactID) {
        return FALSE;
      }
    }
    if ($contactID == $selectedContactID) {
      return TRUE;
    }
    else {
      $query = "
SELECT id
FROM   civicrm_relationship
WHERE  (( contact_id_a = %1 AND contact_id_b = %2 AND is_permission_a_b = 1 ) OR
        ( contact_id_a = %2 AND contact_id_b = %1 AND is_permission_b_a = 1 )) AND
       (contact_id_a NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)) AND
       (contact_id_b NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
";
      $params = [1 => [$contactID, 'Integer'],
        2 => [$selectedContactID, 'Integer'],
      ];
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }


  static function validateOnlyChecksum($contactID, &$form) {
    // check if this is of the format cs=XXX

    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($contactID,
        CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)
      )) {
      $message = !empty($form->_invalidChecksumMessage) ? $form->_invalidChecksumMessage : ts('You do not have permission to edit this contact record. Contact the site administrator if you need assistance.');
      $redirect = !empty($form->_invalidChecksumRedirect) ? $form->_invalidChecksumRedirect : NULL;
      return CRM_Core_Error::statusBounce($message, $redirect);
      // does not come here, we redirect in the above statement
    }
    return TRUE;
  }

  static function validateChecksumContact($contactID, &$form) {
    if (!self::allow($contactID, CRM_Core_Permission::EDIT)) {
      // check if this is of the format cs=XXX
      return self::validateOnlyChecksum($contactID, $form);
    }
    return TRUE;
  }
}

