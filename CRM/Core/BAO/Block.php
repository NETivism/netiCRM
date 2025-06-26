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
 * add static functions to include some common functionality
 * used across location sub object BAO classes
 *
 */
class CRM_Core_BAO_Block {

  /**
   * Fields that are required for a valid block
   */
  static $requiredBlockFields = [
    'email' => ['email'],
    'phone' => ['phone'],
    'im' => ['name'],
    'openid' => ['openid'],
  ];

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Object $block         typically a Phone|Email|IM|OpenID object
   * @param string $blockName     name of the above object
   * @param array  $params        input parameters to find object
   * @param array  $values        output values of the object
   *
   * @return array of $block objects.
   * @access public
   * @static
   */
  static function &getValues($blockName, $params) {
    if (empty($params)) {
      return NULL;
    }
    $BAOString = 'CRM_Core_BAO_' . $blockName;
    $block = new $BAOString( );

    $blocks = [];
    if (!isset($params['entity_table'])) {
      $block->contact_id = $params['contact_id'];
      if (!$block->contact_id) {
        CRM_Core_Error::fatal();
      }
      $blocks = self::retrieveBlock($block, $blockName);
    }
    else {
      $blockIds = self::getBlockIds($blockName, NULL, $params);

      if (empty($blockIds)) {
        return $blocks;
      }

      $count = 1;
      foreach ($blockIds as $blockId) {
        $block = new $BAOString( );
        $block->id = $blockId['id'];
        $getBlocks = self::retrieveBlock($block, $blockName);
        $blocks[$count++] = array_pop($getBlocks);
      }
    }

    return $blocks;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Object $block         typically a Phone|Email|IM|OpenID object
   * @param string $blockName     name of the above object
   * @param array  $values        output values of the object
   *
   * @return array of $block objects.
   * @access public
   * @static
   */
  static function retrieveBlock(&$block, $blockName) {
    // we first get the primary location due to the order by clause
    $block->orderBy('is_primary desc, id');
    $block->find();

    $count = 1;
    $blocks = [];
    while ($block->fetch()) {
      CRM_Core_DAO::storeValues($block, $blocks[$count]);
      //unset is_primary after first block. Due to some bug in earlier version
      //there might be more than one primary blocks, hence unset is_primary other than first
      if ($count > 1) {
        unset($blocks[$count]['is_primary']);
      }
      $count++;
    }

    return $blocks;
  }

  /**
   * check if the current block object has any valid data
   *
   * @param array  $blockFields   array of fields that are of interest for this object
   * @param array  $params        associated array of submitted fields
   *
   * @return boolean              true if the block has data, otherwise false
   * @access public
   * @static
   */
  static function dataExists($blockFields, &$params) {
    foreach ($blockFields as $field) {
      if (CRM_Utils_System::isNull($params[$field])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * check if the current block exits
   *
   * @param string  $blockName   bloack name
   * @param array   $params      associated array of submitted fields
   *
   * @return boolean             true if the block exits, otherwise false
   * @access public
   * @static
   */
  static function blockExists($blockName, &$params) {
    // return if no data present
    if (!CRM_Utils_Array::value($blockName, $params) || !is_array($params[$blockName])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * check if block value exists
   *
   * @param string  $blockName   bloack name that in self::requiredBlockFields key
   * @param array   $blockValue associated array of data without id
   *   ```
   *     array('phone_type_id' => 2, 'contact_id' => 1234, 'phone' => '123123123')
   *   ```
   *
   * @return boolean             true if the block exits, otherwise false
   * @access public
   * @static
   */
  static function blockValueExists($blockName, &$blockValue) {
    $require = self::$requiredBlockFields[$blockName];
    if (empty($require)) {
      return FALSE;
    }
    if (empty($blockValue[$require[0]])) {
      return FALSE;
    }
    // we won't check exists when id provided
    if (!empty($blockValue['id'])) {
      return FALSE;
    }
    // we won't check exists when contact_id not provided
    if (empty($blockValue['contact_id'])) {
      return FALSE;
    }
    $name = ucfirst($blockName);
    $baoString = 'CRM_Core_BAO_' . $name;
    $baoString::valueExists($blockValue);
    if (!empty($blockValue['id'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get all block ids for a contact
   *
   * @param string $blockName block name
   * @param int    $contactId contact id
   *
   * @return array $contactBlockIds formatted array of block ids
   *
   * @access public
   * @static
   */
  static function getBlockIds($blockName, $contactId = NULL, $entityElements = NULL, $updateBlankLocInfo = FALSE) {
    $allBlocks = [];
    $name = ucfirst($blockName);
    $baoString = 'CRM_Core_BAO_' . $name;
    if ($contactId) {
      //@todo a cleverer way to do this would be to use the same fn name on each
      // BAO rather than constructing the fn
      // it would also be easier to grep for
      // e.g $bao = new $baoString;
      // $bao->getAllBlocks()
      $baoFunction = 'all' . $name . 's';
      $allBlocks = $baoString::$baoFunction( $contactId, $updateBlankLocInfo );
    }
    elseif (!empty($entityElements) && $blockName != 'openid') {
      $baoFunction = 'allEntity' . $name . 's';
      $allBlocks = $baoString::$baoFunction( $entityElements );
    }

    return $allBlocks;
  }

  /**
   * takes an associative array and creates a block
   *
   * @param string $blockName      block name
   * @param array  $params         (reference ) an assoc array of name/value pairs
   * @param array  $requiredFields fields that's are required in a block
   *
   * @return object       CRM_Core_BAO_Block object on success, null otherwise
   * @access public
   * @static
   */
  static function create($blockName, &$params, $entity = NULL) {
    if (!self::blockExists($blockName, $params)) {
      return NULL;
    }

    $name = ucfirst($blockName);
    $contactId = NULL;
    $isPrimary = $isBilling = TRUE;
    $entityElements = $blocks = [];

    if ($entity) {
      $entityElements = ['entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      ];
    }
    else {
      $contactId = $params['contact_id'];
    }

    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);

    //get existsing block ids.
    $blockIds = self::getBlockIds($blockName, $contactId, $entityElements, $updateBlankLocInfo);

    //lets allow user to update block w/ the help of id, CRM-6170
    $resetPrimaryId = NULL;
    foreach ($params[$blockName] as $count => $value) {
      $blockId = CRM_Utils_Array::value('id', $value);
      if ($blockId) {
        if (is_array($blockIds) && CRM_Utils_Array::arrayKeyExists($blockId, $blockIds)) {
          unset($blockIds[$blockId]);
        }
        else {
          unset($value['id']);
        }
      }
      //lets allow to update primary w/ more cleanly.
      if (!$resetPrimaryId && CRM_Utils_Array::value('is_primary', $value)) {
        if (is_array($blockIds)) {
          foreach ($blockIds as $blockId => $blockValue) {
            if (CRM_Utils_Array::value('is_primary', $blockValue)) {
              $resetPrimaryId = $blockId;
              break;
            }
          }
        }
        if ($resetPrimaryId) {
          $baoString = 'CRM_Core_BAO_' . $blockName;
          $block = new $baoString( );
          $block->selectAdd();
          $block->selectAdd("id, is_primary");
          $block->id = $resetPrimaryId;
          if ($block->find(TRUE)) {
            $block->is_primary = FALSE;
            $block->save();
          }
          $block->free();
        }
      }
    }

    foreach ($params[$blockName] as $count => $value) {
      if (!is_array($value)) {
        continue;
      }
      $contactFields = [
        'contact_id' => $contactId,
        'location_type_id' => $value['location_type_id'],
      ];

      //check for update
      if (!CRM_Utils_Array::value('id', $value) && is_array($blockIds) && !empty($blockIds)) {
        foreach ($blockIds as $blockId => $blockValue) {
          if ($updateBlankLocInfo) {
            if (CRM_Utils_Array::value($count, $blockIds)) {
              $value['id'] = $blockIds[$count]['id'];
              unset($blockIds[$count]);
            }
          }
          else if($blockName == 'phone') {
            if ($blockValue['locationTypeId'] == $value['location_type_id'] && $blockValue['phone_type_id'] == $value['phone_type_id'] && empty($value['append'])) {
              $value['id'] = $blockValue['id'];
              unset($blockIds[$blockId]);
              break;
            }
            elseif(!empty($value['append'])) {
              $value['contact_id'] = $contactId;
              self::blockValueExists($blockName, $value);
            }
          }
          else if($blockName == 'im') {
            if ($blockValue['locationTypeId'] == $value['location_type_id'] && $blockValue['provider_id'] == $value['provider_id'] && empty($value['append'])) {
              $value['id'] = $blockValue['id'];
              unset($blockIds[$blockId]);
              break;
            }
            elseif(!empty($value['append'])) {
              $value['contact_id'] = $contactId;
              self::blockValueExists($blockName, $value);
            }
          }
          else {
            if ($blockValue['locationTypeId'] == $value['location_type_id'] && empty($value['append'])) {
              //assigned id as first come first serve basis
              $value['id'] = $blockValue['id'];
              unset($blockIds[$blockId]);
              break;
            }
            elseif(!empty($value['append'])) {
              $value['contact_id'] = $contactId;
              self::blockValueExists($blockName, $value);
            }
          }
        }
      }

      $dataExists = self::dataExists(self::$requiredBlockFields[$blockName], $value);

      // Note there could be cases when block info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (CRM_Utils_Array::value('id', $value) && !$dataExists && $updateBlankLocInfo) {
        //delete the existing record
        self::blockDelete($name, ['id' => $value['id']]);
        continue;
      }
      elseif (!$dataExists) {
        continue;
      }

      if ($isPrimary && CRM_Utils_Array::value('is_primary', $value)) {
        $contactFields['is_primary'] = $value['is_primary'];
        $isPrimary = FALSE;
      }
      else {
        $contactFields['is_primary'] = 0;
      }

      if ($isBilling && CRM_Utils_Array::value('is_billing', $value)) {
        $contactFields['is_billing'] = $value['is_billing'];
        $isBilling = FALSE;
      }
      else {
        $contactFields['is_billing'] = 0;
      }

      $blockFields = array_merge($value, $contactFields);
      $baoString = 'CRM_Core_BAO_' . $name;
      if (method_exists($baoString, 'create')) {
        $blocks[] = $baoString::create( $blockFields );
      }
      else {
        $blocks[] = $baoString::add( $blockFields );
      }
    }

    // we need to delete blocks that were deleted during update
    if ($updateBlankLocInfo && !empty($blockIds)) {
      foreach ($blockIds as $deleteBlock) {
        if (!CRM_Utils_Array::value('id', $deleteBlock)) {
          continue;
        }
        self::blockDelete($name, ['id' => $deleteBlock['id']]);
      }
    }

    return $blocks;
  }

  /**
   * Function to delete block
   *
   * @param  string $blockName       block name
   * @param  int    $params          associates array
   *
   * @return void
   * @static
   */
  static function blockDelete($blockName, $params) {
    $baoString = 'CRM_Core_DAO_' . $blockName;
    $block = new $baoString( );

    $block->copyValues($params);

    $block->delete();
  }

  /**
   * Handling for is_primary.
   * 
   * $params is_primary could be
   *  #  1 - find other entries with is_primary = 1 &  reset them to 0
   *  #  0 - make sure at least one entry is set to 1
   *            - if no other entry is 1 change to 1
   *            - if one other entry exists change that to 1
   *            - if more than one other entry exists change first one to 1
   *  #  empty - same as 0 as once we have checked first step
   *             we know if it should be 1 or 0
   *
   *  if $params['id'] is set $params['contact_id'] may need to be retrieved
   *
   * @param array $params
   * @param $class
   *
   * @throws API_Exception
   */
  public static function handlePrimary(&$params, $class) {
    $is_primary = $params['is_primary'] ?? NULL;
    if (isset($params['id']) && CRM_Utils_System::isNull($is_primary)) {
      // if id is set & is_primary isn't we can assume no change)
      return;
    }
    $table = CRM_Core_DAO_AllCoreTables::getTableForClass($class);
    if (!$table) {
      throw new API_Exception("Failed to locate table for class [$class]");
    }

    // contact_id in params might be empty or the string 'null' so cast to integer
    $contactId = (int) ($params['contact_id'] ?? 0);
    // If id is set & we haven't been passed a contact_id, retrieve it
    if (!empty($params['id']) && !isset($params['contact_id'])) {
      $entity = new $class();
      $entity->id = $params['id'];
      $entity->find(TRUE);
      $contactId = $entity->contact_id;
    }
    // If entity is not associated with contact, concept of is_primary not relevant
    if (!$contactId) {
      return;
    }

    // if params is_primary then set all others to not be primary & exit out
    // if is_primary = 1
    if (!empty($params['is_primary'])) {
      $sql = "UPDATE $table SET is_primary = 0 WHERE contact_id = %1";
      $sqlParams = [1 => [$contactId, 'Integer']];
      // we don't want to create unnecessary entries in the log_ tables so exclude the one we are working on
      if (!empty($params['id'])) {
        $sql .= " AND id <> %2";
        $sqlParams[2] = [$params['id'], 'Integer'];
      }
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
      return;
    }

    //Check what other emails exist for the contact
    $existingEntities = new $class();
    $existingEntities->contact_id = $contactId;
    $existingEntities->orderBy('is_primary DESC');
    if (!$existingEntities->find(TRUE) || (!empty($params['id']) && $existingEntities->id == $params['id'])) {
      // ie. if  no others is set to be primary then this has to be primary set to 1 so change
      $params['is_primary'] = 1;
      return;
    }
    else {
      /*
       * If the only existing email is the one we are editing then we must set
       * is_primary to 1
       * @see https://issues.civicrm.org/jira/browse/CRM-10451
       */
      if ($existingEntities->N == 1 && $existingEntities->id == CRM_Utils_Array::value('id', $params)) {
        $params['is_primary'] = 1;
        return;
      }

      if ($existingEntities->is_primary == 1) {
        return;
      }
      // so at this point we are only dealing with ones explicity setting is_primary to 0
      // since we have reverse sorted by email we can either set the first one to
      // primary or return if is already is
      $existingEntities->is_primary = 1;
      $existingEntities->save();
    }
  }
}

