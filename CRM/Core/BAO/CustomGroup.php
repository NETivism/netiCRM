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
 * Business object for managing custom data groups
 *
 */
class CRM_Core_BAO_CustomGroup extends CRM_Core_DAO_CustomGroup {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a custom group object
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Core_DAO_CustomGroup object
   * @access public
   * @static
   */
  static function create(&$params) {
    // create custom group dao, populate fields and then save.
    $group = new CRM_Core_DAO_CustomGroup();
    $group->title = $params['title'];

    if (isset($params['name'])) {
      $group->name = $params['name'];
    }
    else {
      $maxLength = CRM_Core_DAO::getAttribute('CRM_Core_DAO_CustomGroup', 'name');
      $group->name = CRM_Utils_String::titleToVar($params['title'],
        CRM_Utils_Array::value('maxlength', $maxLength)
      );
    }
    if (in_array($params['extends'][0],
        ['ParticipantRole',
          'ParticipantEventName',
          'ParticipantEventType',
        ]
      )) {
      $group->extends = 'Participant';
    }
    else {
      $group->extends = $params['extends'][0];
    }

    $group->extends_entity_column_id = $params['extends_entity_column_id'] === 'null' ? $params['extends_entity_column_id'] : NULL;
    if ($params['extends'][0] == 'ParticipantRole' ||
      $params['extends'][0] == 'ParticipantEventName' ||
      $params['extends'][0] == 'ParticipantEventType'
    ) {
      $group->extends_entity_column_id = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $params['extends'][0], 'value', 'name');
    }

    //this is format when form get submit.
    $extendsChildType = CRM_Utils_Array::value(1, $params['extends']);
    //lets allow user to pass direct child type value, CRM-6893
    if (CRM_Utils_Array::value('extends_entity_column_value', $params)) {
      $extendsChildType = $params['extends_entity_column_value'];
    }

    if ($extendsChildType === 'null') {
      $extendsChildType = 'null';
    }
    elseif (!CRM_Utils_System::isNull($extendsChildType)) {
      $extendsChildType = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $extendsChildType);
      if (CRM_Utils_Array::value(0, $params['extends']) == 'Relationship') {
        $extendsChildType = str_replace(['_a_b', '_b_a'], ['', ''], $extendsChildType);
      }
      if (substr($extendsChildType, 0, 1) != CRM_Core_DAO::VALUE_SEPARATOR) {
        $extendsChildType = CRM_Core_DAO::VALUE_SEPARATOR . $extendsChildType . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }
    else {
      $extendsChildType = 'null';
    }
    $group->extends_entity_column_value = $extendsChildType;

    if (isset($params['id'])) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $params['id'], 'weight', 'id');
    }
    else {
      $oldWeight = 0;
    }

    $group->weight = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_CustomGroup', $oldWeight, CRM_Utils_Array::value('weight', $params, FALSE));
    $fields = ['style', 'collapse_display', 'collapse_adv_display', 'help_pre', 'help_post', 'is_active', 'is_multiple'];
    foreach ($fields as $field) {
      $group->$field = CRM_Utils_Array::value($field, $params, FALSE);
    }
    $group->max_multiple = isset($params['is_multiple']) ? (isset($params['max_multiple']) &&
      $params['max_multiple'] >= '0'
    ) ? $params['max_multiple'] : 'null' : 'null';

    $tableName = NULL;
    if (isset($params['id'])) {
      $group->id = $params['id'];
      //check whether custom group was changed from single-valued to multiple-valued
      $isMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
        $params['id'],
        'is_multiple'
      );

      if (($params['is_multiple'] != $isMultiple) && (CRM_Utils_Array::value('is_multiple', $params) || $isMultiple)) {
        $oldTableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
          $params['id'],
          'table_name'
        );
      }
    }
    else {
      $group->created_id = CRM_Utils_Array::value('created_id', $params);
      $group->created_date = CRM_Utils_Array::value('created_date', $params);



      // lets create the table associated with the group and save it
      $tableName = $group->table_name = "civicrm_value_" . strtolower(CRM_Utils_String::munge($group->title, '_', 32));

      // we do this only once, so name never changes
      $group->name = CRM_Utils_String::munge($params['title'], '_', 64);
    }

    // enclose the below in a transaction

    $transaction = new CRM_Core_Transaction();

    $group->save();
    if ($tableName) {
      // now append group id to table name, this prevent any name conflicts
      // like CRM-2742
      $tableName .= "_{$group->id}";
      $group->table_name = $tableName;
      CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomGroup',
        $group->id,
        'table_name',
        $tableName
      );

      // now create the table associated with this group
      self::createTable($group);
    }
    elseif ($oldTableName) {

      CRM_Core_BAO_SchemaHandler::changeUniqueToIndex($oldTableName, CRM_Utils_Array::value('is_multiple', $params));
    }
    if (CRM_Utils_Array::value('overrideFKConstraint', $params) == 1) {
      $table = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
        $params['id'],
        'table_name'
      );

      CRM_Core_BAO_SchemaHandler::changeFKConstraint($table, self::mapTableName($params['extends'][0]));
    }
    $transaction->commit();
    CRM_Utils_System::flushCache();


    if ($tableName) {
      CRM_Utils_Hook::post('create', 'CustomGroup', $group->id, $group);
    }
    else {
      CRM_Utils_Hook::post('edit', 'CustomGroup', $group->id, $group);
    }

    return $group;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_CustomGroup object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomGroup', $params, $defaults);
  }

  /**
   * update the is_active flag in the db
   *
   * @param  int      $id         id of the database record
   * @param  boolean  $is_active  value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   * @access public
   */
  static function setIsActive($id, $is_active) {
    // reset the cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields');


    if ($is_active) {
      //CRM_Core_BAO_UFField::setUFFieldStatus($id, $is_active);
    }
    else {
      CRM_Core_BAO_UFField::setUFFieldStatus($id, $is_active);
    }
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomGroup', $id, 'is_active', $is_active);
  }

  /**
   * Get custom groups/fields for type of entity.
   *
   * An array containing all custom groups and their custom fields is returned.
   *
   * @param string $entityType - of the contact whose contact type is needed
   * @param null $deprecated   - deprecated
   * @param int    $entityId   - optional - id of entity if we need to populate the tree with custom values.
   * @param int    $groupId    - optional group id (if we need it for a single group only)
   *                           - if groupId is 0 it gets for inline groups only
   *                           - if groupId is -1 we get for all groups
   * @param array $subTypes    - array that subtypes
   * @param string $subName    - subname that use for this group
   * @param bool $fromCache    - use cache or not
   *
   * @return array $groupTree  - array consisting of all groups and fields and optionally populated with custom data values.
   *
   * @access public
   *
   * @static
   *
   */
  public static function &getTree($entityType,
    $deprecated = NULL,
    $entityID = NULL,
    $groupID = NULL,
    $subTypes = [],
    $subName = NULL,
    $fromCache = TRUE
  ) {
    if ($entityID) {
      $entityID = CRM_Utils_Type::escape($entityID, 'Integer');
    }
    if (!is_array($subTypes)) {
      if (empty($subTypes)) {
        $subTypes = [];
      }
      else {
        if(strpos($subTypes, CRM_Core_DAO::VALUE_SEPARATOR) !== -1) {
          $subTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($subTypes, CRM_Core_DAO::VALUE_SEPARATOR));
        }
        else{
          $subTypes = explode(',', $subTypes);
        }
      }
    }


    // create a new tree
    $groupTree = [];
    $strWhere = $orderBy = '';

    // using tableData to build the queryString
    $tableData = [
      'civicrm_custom_field' =>
      ['id',
        'label',
        'column_name',
        'data_type',
        'html_type',
        'default_value',
        'attributes',
        'is_required',
        'is_view',
        'help_pre',
        'help_post',
        'options_per_line',
        'start_date_years',
        'end_date_years',
        'date_format',
        'time_format',
        'option_group_id',
      ],
      'civicrm_custom_group' =>
      ['id',
        'name',
        'table_name',
        'title',
        'help_pre',
        'help_post',
        'collapse_display',
        'is_multiple',
        'extends',
        'extends_entity_column_id',
        'extends_entity_column_value',
        'max_multiple',
      ],
    ];

    // create select
    $select = [];
    foreach ($tableData as $tableName => $tableColumn) {
      foreach ($tableColumn as $columnName) {
        $alias = $tableName . "_" . $columnName;
        $select[] = "{$tableName}.{$columnName} as {$tableName}_{$columnName}";
      }
    }
    $strSelect = "SELECT " . CRM_Utils_Array::implode(', ', $select);

    // from, where, order by
    $strFrom = "
FROM     civicrm_custom_group
LEFT JOIN civicrm_custom_field ON (civicrm_custom_field.custom_group_id = civicrm_custom_group.id)
";

    // if entity is either individual, organization or household pls get custom groups for 'contact' too.
    if ($entityType == "Individual" || $entityType == 'Organization' || $entityType == 'Household') {
      $in = "'$entityType', 'Contact'";
    }
    elseif (strpos($entityType, "'") !== FALSE) {
      // this allows the calling function to send in multiple entity types
      $in = $entityType;
    }
    else {
      // quote it
      $in = "'$entityType'";
    }

    if (!empty($subTypes)) {
      foreach ($subTypes as $key => $subType) {
        $validatedSubType = self::validateSubTypeByEntity($entityType, $subType);
        if ($validatedSubType) {
          $subTypeClauses[] = self::whereListHas("civicrm_custom_group.extends_entity_column_value", $validatedSubType);
        }
      }
      if(!empty($subTypeClauses)) {
        $subTypeClause = '(' .  CRM_Utils_Array::implode(' OR ', $subTypeClauses) . ')';
        if (!$onlySubType) {
          $subTypeClause = '(' . $subTypeClause . '  OR civicrm_custom_group.extends_entity_column_value IS NULL )';
        }

        $strWhere = "
   WHERE civicrm_custom_group.is_active = 1
     AND civicrm_custom_field.is_active = 1
     AND civicrm_custom_group.extends IN ($in)
     AND $subTypeClause
   ";
        if ($subName) {
          $strWhere .= " AND civicrm_custom_group.extends_entity_column_id = {$subName} ";
        }
      }
    }

    if(empty($strWhere)) {
      $strWhere = "
WHERE civicrm_custom_group.is_active = 1
  AND civicrm_custom_field.is_active = 1
  AND civicrm_custom_group.extends IN ($in)
  AND civicrm_custom_group.extends_entity_column_value IS NULL
  AND civicrm_custom_group.extends_entity_column_id IS NULL
";
    }

    $params = [];
    if ($groupID > 0) {
      // since we want a specific group id we add it to the where clause
      $strWhere .= " AND civicrm_custom_group.id = %1";
      $params[1] = [$groupID, 'Integer'];
    }
    elseif (!$groupID) {
      // since groupID is false we need to show all Inline groups
      $strWhere .= " AND civicrm_custom_group.style = 'Inline'";
    }


    // ensure that the user has access to these custom groups
    $strWhere .= " AND " . CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
      'civicrm_custom_group.'
    );

    $orderBy = "
ORDER BY civicrm_custom_group.weight,
         civicrm_custom_group.title,
         civicrm_custom_field.weight,
         civicrm_custom_field.label
";

    // final query string
    $queryString = "$strSelect $strFrom $strWhere $orderBy";

    // lets see if we can retrieve the groupTree from cache
    $cacheString = $queryString;
    if ($groupID > 0) {
      $cacheString .= "_{$groupID}";
    }
    else {
      $cacheString .= "_Inline";
    }
    $cacheKey = "CRM_Core_DAO_CustomGroup_Query " . md5($cacheString);
    $multipleFieldGroupCacheKey = "CRM_Core_DAO_CustomGroup_QueryMultipleFields " . md5($cacheString);
    $cache = CRM_Utils_Cache::singleton();
    $tablesWithEntityData = [];
    if ($fromCache) {
      $groupTree = $cache->get($cacheKey);
      $multipleFieldGroups = $cache->get($multipleFieldGroupCacheKey);
    }
    if (empty($groupTree)) {
      $groupTree = $multipleFieldGroups = [];
      $crmDAO = &CRM_Core_DAO::executeQuery($queryString, $params);

      $customValueTables = [];

      // process records
      while ($crmDAO->fetch()) {
        // get the id's
        $groupID = $crmDAO->civicrm_custom_group_id;
        $fieldId = $crmDAO->civicrm_custom_field_id;

        // create an array for groups if it does not exist
        if (!CRM_Utils_Array::arrayKeyExists($groupID, $groupTree)) {
          $groupTree[$groupID] = [];
          $groupTree[$groupID]['id'] = $groupID;

          // populate the group information
          foreach ($tableData['civicrm_custom_group'] as $fieldName) {
            $fullFieldName = "civicrm_custom_group_$fieldName";
            if ($fieldName == 'id' ||
              is_null($crmDAO->$fullFieldName)
            ) {
              continue;
            }
            // CRM-5507
            // This is an old bit of code - per the CRM number & probably does not work reliably if
            // that one contact sub-type exists.
            if ($fieldName == 'extends_entity_column_value' && !empty($subTypes[0])) {
              $validatedSubType = self::validateSubTypeByEntity($entityType, $subType);
              if($validatedSubType) {
                $groupTree[$groupID]['subtype'] = $validatedSubType;
              }
            }
            $groupTree[$groupID][$fieldName] = $crmDAO->$fullFieldName;
          }
          $groupTree[$groupID]['fields'] = [];

          $customValueTables[$crmDAO->civicrm_custom_group_table_name] = [];
        }

        // add the fields now (note - the query row will always contain a field)
        // we only reset this once, since multiple values come is as multiple rows
        if (!CRM_Utils_Array::arrayKeyExists($fieldId, $groupTree[$groupID]['fields'])) {
          $groupTree[$groupID]['fields'][$fieldId] = [];
        }

        $customValueTables[$crmDAO->civicrm_custom_group_table_name][$crmDAO->civicrm_custom_field_column_name] = 1;
        $groupTree[$groupID]['fields'][$fieldId]['id'] = $fieldId;
        // populate information for a custom field
        foreach ($tableData['civicrm_custom_field'] as $fieldName) {
          $fullFieldName = "civicrm_custom_field_$fieldName";
          if ($fieldName == 'id' ||
            is_null($crmDAO->$fullFieldName)
          ) {
            continue;
          }
          $groupTree[$groupID]['fields'][$fieldId][$fieldName] = $crmDAO->$fullFieldName;
        }
      }
      if (!empty($customValueTables)) {
        $groupTree['info'] = ['tables' => $customValueTables];
      }

      $cache->set($cacheKey, $groupTree);
      $cache->set($multipleFieldGroupCacheKey, $multipleFieldGroups);
    }

    // now that we have all the groups and fields, lets get the values
    // since we need to know the table and field names

    // add info to groupTree
    if (isset($groupTree['info']) && !empty($groupTree['info']) && !empty($groupTree['info']['tables'])) {
      $select = $from = $where = [];
      foreach ($groupTree['info']['tables'] as $table => $fields) {
        $from[] = $table;
        $select[] = "{$table}.id as {$table}_id";
        $select[] = "{$table}.entity_id as {$table}_entity_id";

        foreach ($fields as $column => $dontCare) {
          $select[] = "{$table}.{$column} as {$table}_{$column}";
        }

        if ($entityID) {
          $where[] = "{$table}.entity_id = $entityID";
        }
      }

      $groupTree['info']['select'] = $select;
      $groupTree['info']['from'] = $from;
      $groupTree['info']['where'] = NULL;

      if ($entityID) {
        $groupTree['info']['where'] = $where;
        $select = CRM_Utils_Array::implode(', ', $select);

        // this is a hack to find a table that has some values for this
        // entityID to make the below LEFT JOIN work (CRM-2518)
        $firstTable = NULL;
        foreach ($from as $table) {
          $query = "
SELECT id
FROM   $table
WHERE  entity_id = $entityID
";
          $recordExists = CRM_Core_DAO::singleValueQuery($query);
          if ($recordExists) {
            $firstTable = $table;
            break;
          }
        }

        if ($firstTable) {
          $fromSQL = $firstTable;
          foreach ($from as $table) {
            if ($table != $firstTable) {
              $fromSQL .= "\nLEFT JOIN $table USING (entity_id)";
            }
          }

          $query = "
SELECT $select
  FROM $fromSQL
 WHERE {$firstTable}.entity_id = $entityID
";

          $dao = CRM_Core_DAO::executeQuery($query);

          while ($dao->fetch()) {
            foreach ($groupTree as $groupID => $group) {
              if ($groupID === 'info') {
                continue;
              }
              $table = $groupTree[$groupID]['table_name'];
              foreach ($group['fields'] as $fieldID => $dontCare) {
                $column = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
                $idName = "{$table}_id";
                $fieldName = "{$table}_{$column}";

                $dataType = $groupTree[$groupID]['fields'][$fieldID]['data_type'];
                if ($dataType == 'File') {
                  if (isset($dao->$fieldName)) {

                    $config = CRM_Core_Config::singleton();
                    $fileDAO = new CRM_Core_DAO_File();
                    $fileDAO->id = $dao->$fieldName;

                    if ($fileDAO->find(TRUE)) {
                      $entityIDName = "{$table}_entity_id";
                      $customValue = [];
                      $customValue['id'] = $dao->$idName;
                      $customValue['data'] = $fileDAO->uri;
                      $customValue['fid'] = $fileDAO->id;
                      $fileHash = CRM_Core_BAO_File::generateFileHash($dao->$entityIDName, $fileDAO->id);
                      $customValue['fileURL'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$fileDAO->id}&eid={$dao->$entityIDName}&fcs=$fileHash", FALSE, NULL, FALSE);
                      $customValue['displayURL'] = $customValue['fileURL'];
                      $deleteExtra = ts('Are you sure you want to delete attached file.');
                      $deleteURL = [CRM_Core_Action::DELETE =>
                        [
                          'name' => ts('Delete Attached File'),
                          'url' => 'civicrm/file',
                          'qs' => 'reset=1&id=%%id%%&eid=%%eid%%&fid=%%fid%%&action=delete&stay=1&fcs=%%fcs%%',
                          'extra' =>
                          'onclick = "if (confirm( \'' . $deleteExtra . '\' ) ) this.href+=\'&confirmed=1\'; else return false;"',
                        ],
                      ];
                      $customValue['deleteURL'] = CRM_Core_Action::formLink($deleteURL,
                        CRM_Core_Action::DELETE,
                        [
                          'id' => $fileDAO->id,
                          'eid' => $dao->$entityIDName,
                          'fid' => $fieldID,
                          'fcs' => $fileHash
                        ]
                      );
                      $customValue['fileName'] = basename($fileDAO->uri);
                      $customValue['fileNameClean'] = CRM_Utils_File::cleanFileName(basename($fileDAO->uri));
                      if ($fileDAO->mime_type == "image/jpeg" ||
                        $fileDAO->mime_type == "image/pjpeg" ||
                        $fileDAO->mime_type == "image/gif" ||
                        $fileDAO->mime_type == "image/x-png" ||
                        $fileDAO->mime_type == "image/png"
                      ) {
                        $customImage = CRM_Utils_Image::getImageVars($customValue['fileURL']); 
                        $customValue['image'] = $customImage;
                      }
                    }
                  }
                  else {
                    $customValue = ['id' => $dao->$idName,
                      'data' => '',
                    ];
                  }
                }
                else {
                  $customValue = ['id' => $dao->$idName,
                    'data' => $dao->$fieldName,
                  ];
                }
                if (!CRM_Utils_Array::arrayKeyExists('customValue', $groupTree[$groupID]['fields'][$fieldID])) {
                  $groupTree[$groupID]['fields'][$fieldID]['customValue'] = [];
                }
                if (empty($groupTree[$groupID]['fields'][$fieldID]['customValue'])) {
                  $groupTree[$groupID]['fields'][$fieldID]['customValue'] = [1 => $customValue];
                }
                else {
                  $groupTree[$groupID]['fields'][$fieldID]['customValue'][] = $customValue;
                }
              }
            }
          }
        }
      }
    }

    return $groupTree;
  }


  /**
   * Clean and validate the filter before it is used in a db query.
   *
   * @param string $entityType
   * @param string $subType
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected static function validateSubTypeByEntity($entityType, $subType) {
    $subType = trim($subType, CRM_Core_DAO::VALUE_SEPARATOR);
    if (is_numeric($subType)) {
      return $subType;
    }

    $contactTypes = CRM_Contact_BAO_ContactType::contactTypes();
    if ($entityType != 'Contact' && !in_array($entityType, $contactTypes)) {
      CRM_Core_Error::debug_log_message('Invalid Entity Type - '.$entityType);
      return FALSE;
    }
    $subTypes = CRM_Contact_BAO_ContactType::subTypes($entityType, TRUE);
    if (!in_array($subType, $subTypes)) {
      CRM_Core_Error::debug_log_message('Invalid Sub Type - '.$subType);
      return FALSE;
    }
    return $subType;
  }
  /**
   * Suppose you have a SQL column, $column, which includes a delimited list, and you want
   * a WHERE condition for rows that include $value. Use whereListHas().
   *
   * @param string $column
   * @param string $value
   * @param string $delimiter
   * @return string
   *   SQL condition.
   */
  static private function whereListHas($column, $value, $delimiter = CRM_Core_DAO::VALUE_SEPARATOR) {
    $bareValue = trim($value, $delimiter); // ?
    $escapedValue = CRM_Utils_Type::escape("%{$delimiter}{$bareValue}{$delimiter}%", 'String', FALSE);
    return "($column LIKE \"$escapedValue\")";
  }

  /**
   * Get the group title.
   *
   * @param int $id id of group.
   *
   * @return string title
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $id, 'title');
  }

  /**
   * Get custom group details for a group.
   *
   * An array containing custom group details (including their custom field) is returned.
   *
   * @param int     $groupId    - group id whose details are needed
   * @param boolean $searchable - is this field searchable
   * @param array   $extends    - which table does it extend if any
   *
   * @return array $groupTree - array consisting of all group and field details
   *
   * @access public
   *
   * @static
   *
   */
  public static function &getGroupDetail($groupId = NULL, $searchable = NULL, &$extends = NULL) {
    // create a new tree
    $groupTree = [];
    $select = $from = $where = $orderBy = '';

    $tableData = [];

    // using tableData to build the queryString
    $tableData = [
      'civicrm_custom_field' =>
      ['id',
        'label',
        'data_type',
        'html_type',
        'default_value',
        'attributes',
        'is_required',
        'help_pre',
        'help_post',
        'options_per_line',
        'is_searchable',
        'start_date_years',
        'end_date_years',
        'is_search_range',
        'date_format',
        'time_format',
        'note_columns',
        'note_rows',
        'column_name',
        'is_view',
        'option_group_id',
      ],
      'civicrm_custom_group' =>
      ['id',
        'name',
        'title',
        'help_pre',
        'help_post',
        'collapse_display',
        'collapse_adv_display',
        'extends',
        'extends_entity_column_value',
        'table_name',
      ],
    ];

    // create select
    $select = "SELECT";
    $s = [];
    foreach ($tableData as $tableName => $tableColumn) {
      foreach ($tableColumn as $columnName) {
        $s[] = "{$tableName}.{$columnName} as {$tableName}_{$columnName}";
      }
    }
    $select = 'SELECT ' . CRM_Utils_Array::implode(', ', $s);
    $params = [];
    // from, where, order by
    $from = " FROM civicrm_custom_field, civicrm_custom_group";
    $where = " WHERE civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                            AND civicrm_custom_group.is_active = 1
                            AND civicrm_custom_field.is_active = 1 ";
    if ($groupId) {
      $params[1] = [$groupId, 'Integer'];
      $where .= " AND civicrm_custom_group.id = %1";
    }

    if ($searchable) {
      $where .= " AND civicrm_custom_field.is_searchable = 1";
    }

    if ($extends) {
      $clause = [];
      foreach ($extends as $e) {
        $clause[] = "civicrm_custom_group.extends = '$e'";
      }
      $where .= " AND ( " . CRM_Utils_Array::implode(' OR ', $clause) . " ) ";

      //include case activities customdata if case is enabled
      if (in_array('Activity', $extends)) {
        $extendValues = CRM_Utils_Array::implode(',', array_keys(CRM_Core_PseudoConstant::activityType(TRUE, TRUE)));
        $where .= " AND ( civicrm_custom_group.extends_entity_column_value IS NULL OR REPLACE( civicrm_custom_group.extends_entity_column_value, %2, ' ') IN ($extendValues) ) ";
        $params[2] = [CRM_Core_DAO::VALUE_SEPARATOR, 'String'];
      }
    }


    // ensure that the user has access to these custom groups
    $where .= " AND " . CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
      'civicrm_custom_group.'
    );

    $orderBy = " ORDER BY civicrm_custom_group.weight, civicrm_custom_field.weight";

    // final query string
    $queryString = $select . $from . $where . $orderBy;

    // dummy dao needed
    $crmDAO = &CRM_Core_DAO::executeQuery($queryString, $params);

    // process records
    while ($crmDAO->fetch()) {
      $groupId = $crmDAO->civicrm_custom_group_id;
      $fieldId = $crmDAO->civicrm_custom_field_id;

      // create an array for groups if it does not exist
      if (!CRM_Utils_Array::arrayKeyExists($groupId, $groupTree)) {
        $groupTree[$groupId] = [];
        $groupTree[$groupId]['id'] = $groupId;

        foreach ($tableData['civicrm_custom_group'] as $v) {
          $fullField = "civicrm_custom_group_" . $v;

          if ($v == 'id' || is_null($crmDAO->$fullField)) {
            continue;
          }

          $groupTree[$groupId][$v] = $crmDAO->$fullField;
        }

        $groupTree[$groupId]['fields'] = [];
      }

      // add the fields now (note - the query row will always contain a field)
      $groupTree[$groupId]['fields'][$fieldId] = [];
      $groupTree[$groupId]['fields'][$fieldId]['id'] = $fieldId;

      foreach ($tableData['civicrm_custom_field'] as $v) {
        $fullField = "civicrm_custom_field_" . $v;
        if ($v == 'id' || is_null($crmDAO->$fullField)) {
          continue;
        }
        $groupTree[$groupId]['fields'][$fieldId][$v] = $crmDAO->$fullField;
      }
    }

    return $groupTree;
  }


  public static function &getActiveGroups($entityType, $path, $cidToken = '%%cid%%') {
    // for Group's
    $customGroupDAO = new CRM_Core_DAO_CustomGroup();

    // get only 'Tab' groups
    $customGroupDAO->whereAdd("style = 'Tab'");
    $customGroupDAO->whereAdd("is_active = 1");

    // add whereAdd for entity type
    self::_addWhereAdd($customGroupDAO, $entityType, $cidToken);

    $groups = [];

    $permissionClause = CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW, NULL, TRUE);
    $customGroupDAO->whereAdd($permissionClause);

    // order by weight
    $customGroupDAO->orderBy('weight');
    $customGroupDAO->find();

    // process each group with menu tab
    while ($customGroupDAO->fetch()) {
      $group = [];
      $group['id'] = $customGroupDAO->id;
      $group['path'] = $path;
      $group['title'] = "$customGroupDAO->title";
      $group['query'] = "reset=1&gid={$customGroupDAO->id}&cid={$cidToken}";
      $group['extra'] = ['gid' => $customGroupDAO->id];
      $group['table_name'] = $customGroupDAO->table_name;
      $groups[] = $group;
    }

    return $groups;
  }

  /**
   * Get the table name for the entity type
   * currently if entity type is 'Contact', 'Individual', 'Household', 'Organization'
   * tableName is 'civicrm_contact'
   *
   * @param string $entityType  what entity are we extending here ?
   *
   * @return string $tableName
   *
   * @access private
   * @static
   *
   */
  private static function _getTableName($entityType) {
    $tableName = '';
    switch ($entityType) {
      case 'Contact':
      case 'Individual':
      case 'Household':
      case 'Organization':
        $tableName = 'civicrm_contact';
        break;

      case 'Contribution':
        $tableName = 'civicrm_contribution';
        break;

      case 'Group':
        $tableName = 'civicrm_group';
        break;
      // DRAFTING: Verify if we cannot make it pluggable

      case 'Activity':
        $tableName = 'civicrm_activity';
        break;

      case 'Relationship':
        $tableName = 'civicrm_relationship';
        break;

      case 'Membership':
        $tableName = 'civicrm_membership';
        break;

      case 'Participant':
        $tableName = 'civicrm_participant';
        break;

      case 'Event':
        $tableName = 'civicrm_event';
        break;

      case 'Grant':
        $tableName = 'civicrm_grant';
        break;
      // need to add cases for Location, Address
    }

    return $tableName;
  }

  /**
   * Get a list of custom groups which extend a given entity type.
   * If there are custom-groups which only apply to certain subtypes,
   * those WILL be included.
   *
   * @param string $entityType
   *
   * @return CRM_Core_DAO_CustomGroup
   */
  public static function getAllCustomGroupsByBaseEntity($entityType) {
    $customGroupDAO = new CRM_Core_DAO_CustomGroup();
    self::_addWhereAdd($customGroupDAO, $entityType, NULL, TRUE);
    return $customGroupDAO;
  }

  /**
   * Add the whereAdd clause for the DAO depending on the type of entity
   * the custom group is extending.
   *
   * @param object CRM_Core_DAO_CustomGroup (reference) - Custom Group DAO.
   * @param string $entityType    - what entity are we extending here ?
   *
   * @return void
   *
   * @access private
   * @static
   *
   */
  private static function _addWhereAdd(&$customGroupDAO, $entityType, $entityID = NULL) {
    $addSubtypeClause = FALSE;

    switch ($entityType) {
      case 'Contact':
        // if contact, get all related to contact
        $extendList = "'Contact','Individual','Household','Organization'";
        $customGroupDAO->whereAdd("extends IN ( $extendList )");
        $addSubtypeClause = TRUE;
        break;

      case 'Individual':
      case 'Household':
      case 'Organization':
        // is I/H/O then get I/H/O and contact
        $extendList = "'Contact','$entityType'";
        $customGroupDAO->whereAdd("extends IN ( $extendList )");
        $addSubtypeClause = TRUE;
        break;

      case 'Location':
      case 'Address':
        $customGroupDAO->whereAdd("extends IN ('$entityType')");
        break;
    }

    if ($addSubtypeClause) {

      $csType = is_numeric($entityID) ? CRM_Contact_BAO_Contact::getContactSubType($entityID) : FALSE;

      if ($csType) {
        $csType = CRM_Core_DAO::VALUE_SEPARATOR . $csType . CRM_Core_DAO::VALUE_SEPARATOR;
        $customGroupDAO->whereAdd("( extends_entity_column_value LIKE '%{$csType}%' OR extends_entity_column_value IS NULL )");
      }
      else {
        $customGroupDAO->whereAdd("extends_entity_column_value IS NULL");
      }
    }
  }

  /**
   * Delete the Custom Group.
   *
   * @param $group object   the DAO custom group object
   * @param $force boolean  whether to force the deletion, even if there are custom fields
   *
   * @return boolean   false if field exists for this group, true if group gets deleted.
   *
   * @access public
   * @static
   *
   */
  public static function deleteGroup($group, $force = FALSE) {


    //check wheter this contain any custom fields
    $customField = new CRM_Core_DAO_CustomField();
    $customField->custom_group_id = $group->id;
    $customField->find();

    // return early if there are custom fields and we're not
    // forcing the delete, otherwise delete the fields one by one
    while ($customField->fetch()) {
      if (!$force) {
        return FALSE;
      }
      CRM_Core_BAO_CustomField::deleteField($customField);
    }

    // drop the table associated with this custom group

    CRM_Core_BAO_SchemaHandler::dropTable($group->table_name);

    //delete  custom group
    $group->delete();


    CRM_Utils_Hook::post('delete', 'CustomGroup', $group->id, $group);

    return TRUE;
  }

  static function setDefaults(&$groupTree, &$defaults, $viewMode = FALSE, $inactiveNeeded = FALSE, $action = CRM_Core_Action::NONE) {

    foreach ($groupTree as $id => $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      $groupId = CRM_Utils_Array::value('id', $group);
      foreach ($group['fields'] as $field) {
        if (CRM_Utils_Array::value('element_value', $field) !== NULL) {
          $value = $field['element_value'];
        }
        elseif (CRM_Utils_Array::value('default_value', $field) !== NULL && $action != CRM_Core_Action::UPDATE) {
          $value = $viewMode ? NULL : $field['default_value'];
        }
        else {
          continue;
        }

        $fieldId = $field['id'];
        $elementName = $field['element_name'];
        switch ($field['html_type']) {
          case 'Multi-Select':
          case 'AdvMulti-Select':
          case 'CheckBox':
            $defaults[$elementName] = [];
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($field['id'], $inactiveNeeded);
            if ($viewMode) {
              $checkedData = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($value, 1, -1));
              if (isset($value)) {
                foreach ($customOption as $customValue => $customLabel) {
                  if (in_array($customValue, $checkedData)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$customValue] = 1;
                    }
                    else {
                      $defaults[$elementName][$customValue] = $customValue;
                    }
                  }
                  else {
                    $defaults[$elementName][$customValue] = 0;
                  }
                }
              }
            }
            else {
              if (isset($field['customValue']['data'])) {
                $checkedData = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($field['customValue']['data'], 1, -1));
                foreach ($customOption as $val) {
                  if (in_array($val['value'], $checkedData)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$val['value']] = 1;
                    }
                    else {
                      $defaults[$elementName][$val['value']] = $val['value'];
                    }
                  }
                  else {
                    $defaults[$elementName][$val['value']] = 0;
                  }
                }
              }
              else {
                $checkedValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($value, 1, -1));
                foreach ($customOption as $val) {
                  if (in_array($val['value'], $checkedValue)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$val['value']] = 1;
                    }
                    else {
                      $defaults[$elementName][$val['value']] = $val['value'];
                    }
                  }
                }
              }
            }
            break;

          case 'Select Date':
            if (isset($value)) {
              if (!$field['time_format']) {
                list($defaults[$elementName]) = CRM_Utils_Date::setDateDefaults($value, NULL,
                  $field['date_format']
                );
              }
              else {
                $timeElement = $elementName . '_time';
                if (substr($elementName, -1) == ']') {
                  $timeElement = substr($elementName, 0, $$elementName . length - 1) . '_time]';
                }
                list($defaults[$elementName], $defaults[$timeElement]) = CRM_Utils_Date::setDateDefaults($value, NULL, $field['date_format'], $field['time_format']);
              }
            }
            break;

          case 'Multi-Select Country':
          case 'Multi-Select State/Province':
            if (isset($value)) {
              $checkedValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
              foreach ($checkedValue as $val) {
                if ($val) {
                  $defaults[$elementName][$val] = $val;
                }
              }
            }
            break;

          case 'Select Country':
            if ($value) {
              $defaults[$elementName] = $value;
            }
            else {
              $config = CRM_Core_Config::singleton();
              $defaults[$elementName] = $config->defaultContactCountry;
            }
            break;

          case 'Autocomplete-Select':
            if ($field['data_type'] == "ContactReference") {

              if (is_numeric($value)) {
                $defaults[$elementName . '_id'] = $value;
                $defaults[$elementName] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'sort_name');
              }
            }
            else {
              $label = CRM_Core_BAO_CustomOption::getOptionLabel($field['id'], $value);
              $defaults[$elementName . '_id'] = $value;
              $defaults[$elementName] = $label;
            }
            break;

          default:
            if ($field['data_type'] == "Float") {
              $defaults[$elementName] = (float)$value;
            }
            elseif ($field['data_type'] == 'Money') {

              $defaults[$elementName] = CRM_Utils_Money::format($value, NULL, '%a');
            }
            else {
              $defaults[$elementName] = $value;
            }
        }
      }
    }
  }

  static function postProcess(&$groupTree, &$params, $skipFile = FALSE) {
    // Get the Custom form values and groupTree
    // first reset all checkbox and radio data
    foreach ($groupTree as $groupID => $group) {
      if ($groupID === 'info') {
        continue;
      }
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];

        //added Multi-Select option in the below if-statement
        if ($field['html_type'] == 'CheckBox' || $field['html_type'] == 'Radio' ||
          $field['html_type'] == 'AdvMulti-Select' || $field['html_type'] == 'Multi-Select'
        ) {
          $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = 'NULL';
        }

        $v = CRM_Utils_Array::value('custom_' . $field['id'], $params);

        if (!isset($groupTree[$groupID]['fields'][$fieldId]['customValue'])) {
          // field exists in db so populate value from "form".
          $groupTree[$groupID]['fields'][$fieldId]['customValue'] = [];
        }

        switch ($groupTree[$groupID]['fields'][$fieldId]['html_type']) {

          //added for CheckBox

          case 'CheckBox':
            if (!empty($v)) {
              $customValue = is_array($v) ? array_keys($v) : [];
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $customValue) . CRM_Core_DAO::VALUE_SEPARATOR;
            }
            else {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = NULL;
            }
            break;

          //added for Advanced Multi-Select

          case 'AdvMulti-Select':
            //added for Multi-Select
          case 'Multi-Select':
            if (!empty($v)) {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $v) . CRM_Core_DAO::VALUE_SEPARATOR;
            }
            else {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = NULL;
            }
            break;

          case 'Select Date':
            $date = CRM_Utils_Date::format($v);
            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $date;
            break;

          case 'File':
            if ($skipFile) {
              break;
            }

            //store the file in d/b
            $entityId = explode('=', $groupTree['info']['where'][0]);
            $fileParams = ['upload_date' => date('Ymdhis')];

            if ($groupTree[$groupID]['fields'][$fieldId]['customValue']['fid']) {
              $fileParams['id'] = $groupTree[$groupID]['fields'][$fieldId]['customValue']['fid'];
            }
            if (!empty($v)) {

              $fileParams['uri'] = $v['name'];
              $fileParams['mime_type'] = $v['type'];
              CRM_Core_BAO_File::filePostProcess($v['name'],
                $groupTree[$groupID]['fields'][$fieldId]['customValue']['fid'],
                $groupTree[$groupID]['table_name'],
                trim($entityId[1]),
                FALSE,
                TRUE,
                $fileParams,
                'custom_' . $fieldId,
                $v['type']
              );
            }
            $defaults = [];
            $paramsFile = ['entity_table' => $groupTree[$groupID]['table_name'],
              'entity_id' => $entityId[1],
            ];

            CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_EntityFile',
              $paramsFile,
              $defaults
            );

            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $defaults['file_id'];
            break;

          default:
            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $v;
            break;
        }
      }
    }
  }

  /**
   * generic function to build all the form elements for a specific group tree
   *
   * @param CRM_Core_Form $form      the form object
   * @param array         $groupTree the group tree object
   * @param string        $showName
   * @param string        $hideName
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form,
    &$groupTree,
    $inactiveNeeded = FALSE,
    $groupCount = 1,
    $prefix = ''
  ) {



    $form->assign_by_ref("{$prefix}groupTree", $groupTree);
    $sBlocks = [];
    $hBlocks = [];

    // this is fix for date field
    $form->assign('currentYear', date('Y'));


    foreach ($groupTree as $id => $group) {

      CRM_Core_ShowHideBlocks::links($form, $group['title'], '', '');

      $groupId = CRM_Utils_Array::value('id', $group);
      foreach ($group['fields'] as $field) {
        // skip all view fields
        if (CRM_Utils_Array::value('is_view', $field)) {
          continue;
        }

        $required = CRM_Utils_Array::value('is_required', $field);
        //fix for CRM-1620
        if ($field['data_type'] == 'File') {
          if (isset($field['customValue']['data'])) {
            $required = 0;
          }
        }

        $fieldId = $field['id'];
        $elementName = $field['element_name'];

        CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, $inactiveNeeded, $required);
      }
    }
  }

  /**
   * Function to extract the get params from the url, validate
   * and store it in session
   *
   * @param CRM_Core_Form $form the form object
   * @param string        $type the type of custom group we are using
   *
   * @return void
   * @access public
   * @static
   */
  static function extractGetParams(&$form, $type) {
    // if not GET params return
    if (empty($_GET)) {
      return;
    }

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree($type);
    $customValue = [];
    $htmlType = ['CheckBox', 'Multi-Select', 'AdvMulti-Select', 'Select', 'Radio'];

    foreach ($groupTree as $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $key => $field) {
        $fieldName = 'custom_' . $key;
        $value = CRM_Utils_Request::retrieve($fieldName, 'String',
          $form
        );

        if ($value) {
          if (!in_array($field['html_type'], $htmlType) ||
            $field['data_type'] == 'Boolean'
          ) {
            $valid = CRM_Core_BAO_CustomValue::typecheck($field['data_type'], $value);
          }
          if ($field['html_type'] == 'CheckBox' ||
            $field['html_type'] == 'AdvMulti-Select' ||
            $field['html_type'] == 'Multi-Select'
          ) {
            $value = str_replace("|", ",", $value);
            $mulValues = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($key, TRUE);
            $val = [];
            foreach ($mulValues as $v1) {
              foreach ($customOption as $coID => $coValue) {
                if (strtolower(trim($coValue['label'])) == strtolower(trim($v1))) {
                  $val[$coValue['value']] = 1;
                }
              }
            }
            if (!empty($val)) {
              $value = $val;
              $valid = TRUE;
            }
            else {
              $value = NULL;
            }
          }
          elseif ($field['html_type'] == 'Select' ||
            ($field['html_type'] == 'Radio' &&
              $field['data_type'] != 'Boolean'
            )
          ) {
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($key, TRUE);
            foreach ($customOption as $customID => $coValue) {
              if (strtolower(trim($coValue['label'])) == strtolower(trim($value))) {
                $value = $coValue['value'];
                $valid = TRUE;
              }
            }
          }
          elseif ($field['data_type'] == 'Date') {

            if (!empty($value)) {
              $time = NULL;
              if (CRM_Utils_Array::value('time_format', $field)) {
                $time = CRM_Utils_Request::retrieve($fieldName . '_time', 'String', $form);
              }
              list($value, $time) = CRM_Utils_Date::setDateDefaults($value . ' ' . $time);
              if (CRM_Utils_Array::value('time_format', $field)) {
                $customValue[$fieldName . '_time'] = $time;
              }
            }
            $valid = TRUE;
          }
          if ($valid) {
            $customValue[$fieldName] = $value;
          }
        }
      }
    }

    return $customValue;
  }

  /**
   * Function to check the type of custom field type (eg: Used for Individual, Contribution, etc)
   * this function is used to get the custom fields of a type (eg: Used for Individual, Contribution, etc )
   *
   * @param  int     $customFieldId          custom field id
   * @param  array   $removeCustomFieldTypes remove custom fields of a type eg: array("Individual") ;
   *
   *
   * @return boolean false if it matches else true
   * @static
   * @access public
   */
  static function checkCustomField($customFieldId, &$removeCustomFieldTypes) {
    $query = "SELECT cg.extends as extends
                  FROM civicrm_custom_group as cg, civicrm_custom_field as cf
                  WHERE cg.id = cf.custom_group_id
                    AND cf.id =" . CRM_Utils_Type::escape($customFieldId, 'Integer');

    $extends = CRM_Core_DAO::singleValueQuery($query);

    if (in_array($extends, $removeCustomFieldTypes)) {
      return FALSE;
    }
    return TRUE;
  }

  static function mapTableName($table) {
    switch ($table) {
      case 'Contact':
      case 'Individual':
      case 'Household':
      case 'Organization':
        return 'civicrm_contact';

      case 'Activity':
        return 'civicrm_activity';

      case 'Group':
        return 'civicrm_group';

      case 'Contribution':
        return 'civicrm_contribution';

      case 'ContributionPage':
        return 'civicrm_contribution_page';

      case 'ContributionRecur':
        return 'civicrm_contribution_recur';

      case 'Relationship':
        return 'civicrm_relationship';

      case 'Event':
        return 'civicrm_event';

      case 'Membership':
        return 'civicrm_membership';

      case 'Participant':
      case 'ParticipantEventName':
      case 'ParticipantEventType':
      case 'ParticipantRole':
        return 'civicrm_participant';

      case 'Grant':
        return 'civicrm_grant';

      case 'Pledge':
        return 'civicrm_pledge';

      case 'Address':
        return 'civicrm_address';

      case 'PriceField':
        return 'civicrm_price_field';

      default:
        $query = "
SELECT IF( EXISTS(SELECT name FROM civicrm_contact_type WHERE name like %1), 1, 0 )";
        $qParams = [1 => [$table, 'String']];
        $result = CRM_Core_DAO::singleValueQuery($query, $qParams);

        if ($result) {
          return 'civicrm_contact';
        }
        else {
          CRM_Core_Error::fatal("Selected used for table not exists.");
        }
    }
  }

  static function createTable($group) {
    $params = [
      'name' => $group->table_name,
      'is_multiple' => $group->is_multiple ? 1 : 0,
      'extends_name' => self::mapTableName($group->extends),
    ];


    $tableParams = &CRM_Core_BAO_CustomField::defaultCustomTableSchema($params);


    CRM_Core_BAO_SchemaHandler::createTable($tableParams);
  }

  /**
   * Function returns formatted groupTree, sothat form can be easily build in template
   *
   * @param array  $groupTree associated array
   * @param int    $groupCount group count by default 1, but can varry for multiple value custom data
   * @param object form object
   *
   * @return array $formattedGroupTree
   */
  static function formatGroupTree(&$groupTree, $groupCount, &$form) {
    $formattedGroupTree = [];
    $uploadNames = [];

    foreach ($groupTree as $key => $value) {
      if ($key === 'info') {
        continue;
      }

      // add group information
      $formattedGroupTree[$key]['name'] = CRM_Utils_Array::value('name', $value);
      $formattedGroupTree[$key]['title'] = CRM_Utils_Array::value('title', $value);
      $formattedGroupTree[$key]['help_pre'] = CRM_Utils_Array::value('help_pre', $value);
      $formattedGroupTree[$key]['help_post'] = CRM_Utils_Array::value('help_post', $value);
      $formattedGroupTree[$key]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $value);
      $formattedGroupTree[$key]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $value);

      // this params needed of bulding multiple values
      $formattedGroupTree[$key]['is_multiple'] = CRM_Utils_Array::value('is_multiple', $value);
      $formattedGroupTree[$key]['extends'] = CRM_Utils_Array::value('extends', $value);
      $formattedGroupTree[$key]['extends_entity_column_id'] = CRM_Utils_Array::value('extends_entity_column_id', $value);
      $formattedGroupTree[$key]['extends_entity_column_value'] = CRM_Utils_Array::value('extends_entity_column_value', $value);
      $formattedGroupTree[$key]['subtype'] = CRM_Utils_Array::value('subtype', $value);
      $formattedGroupTree[$key]['max_multiple'] = CRM_Utils_Array::value('max_multiple', $value);

      // add field information
      foreach ($value['fields'] as $k => $properties) {
        $properties['element_name'] = "custom_{$k}_-{$groupCount}";
        if (isset($properties['customValue']) && !CRM_Utils_system::isNull($properties['customValue'])) {
          if (isset($properties['customValue'][$groupCount])) {
            $properties['element_name'] = "custom_{$k}_{$properties['customValue'][$groupCount]['id']}";
            if ($properties['data_type'] == 'File') {
              $properties['element_value'] = $properties['customValue'][$groupCount];
              $uploadNames[] = $properties['element_name'];
            }
            else {
              $properties['element_value'] = $properties['customValue'][$groupCount]['data'];
            }
          }
        }
        unset($properties['customValue']);
        $formattedGroupTree[$key]['fields'][$k] = $properties;
      }
    }

    if ($form) {
      // hack for field type File
      $formUploadNames = $form->get('uploadNames');
      if (is_array($formUploadNames)) {
        $uploadNames = array_unique(array_merge($formUploadNames, $uploadNames));
      }

      $form->set('uploadNames', $uploadNames);
    }

    return $formattedGroupTree;
  }

  /**
   * Build custom data view
   *  @param object  $form page object
   *  @param array   $groupTree associated array
   *  @param boolean $returnCount true if customValue count needs to be returned
   */
  static function buildCustomDataView(&$form, &$groupTree, $returnCount = FALSE, $groupID = NULL, $prefix = NULL) {
    foreach ($groupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }

      foreach ($group['fields'] as $k => $properties) {
        $groupID = $group['id'];
        if (!empty($properties['customValue'])) {
          foreach ($properties['customValue'] as $values) {
            $details[$groupID][$values['id']]['title'] = CRM_Utils_Array::value('title', $group);
            $details[$groupID][$values['id']]['name'] = CRM_Utils_Array::value('name', $group);
            $details[$groupID][$values['id']]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
            $details[$groupID][$values['id']]['help_post'] = CRM_Utils_Array::value('help_post', $group);
            $details[$groupID][$values['id']]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
            $details[$groupID][$values['id']]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
            $details[$groupID][$values['id']]['fields'][$k] = ['field_title' => CRM_Utils_Array::value('label', $properties),
              'field_type' => CRM_Utils_Array::value('html_type',
                $properties
              ),
              'field_data_type' => CRM_Utils_Array::value('data_type',
                $properties
              ),
              'field_value' => self::formatCustomValues($values,
                $properties
              ),
              'options_per_line' => CRM_Utils_Array::value('options_per_line',
                $properties
              ),
            ];
            // also return contact reference contact id if user has view all or edit all contacts perm
            if ((CRM_Core_Permission::check('view all contacts') || CRM_Core_Permission::check('edit all contacts'))
              && $details[$groupID][$values['id']]['fields'][$k]['field_data_type'] == 'ContactReference'
            ) {
              $details[$groupID][$values['id']]['fields'][$k]['contact_ref_id'] = CRM_Utils_Array::value('data', $values);
            }
          }
        }
        else {
          $details[$groupID][0]['title'] = CRM_Utils_Array::value('title', $group);
          $details[$groupID][0]['name'] = CRM_Utils_Array::value('name', $group);
          $details[$groupID][0]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
          $details[$groupID][0]['help_post'] = CRM_Utils_Array::value('help_post', $group);
          $details[$groupID][0]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
          $details[$groupID][0]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
          $details[$groupID][0]['fields'][$k] = ['field_title' => CRM_Utils_Array::value('label', $properties)];
        }
      }
    }

    if ($returnCount) {
      return count($details[$groupID]);
    }
    else {
      $form->assign_by_ref("{$prefix}viewCustomData", $details);
      return $details;
    }
  }

  /**
   * Format custom value according to data, view mode
   *
   * @param array $values associated array of custom values
   * @param array $field associated array
   * @param boolean $dncOptionPerLine true if optionPerLine should not be consider
   *
   */
  static function formatCustomValues(&$values, &$field, $dncOptionPerLine = FALSE) {
    $value = $values['data'];

    //changed isset CRM-4601
    if (CRM_Utils_System::isNull($value)) {
      return;
    }

    $htmlType = CRM_Utils_Array::value('html_type', $field);
    $dataType = CRM_Utils_Array::value('data_type', $field);
    $option_group_id = CRM_Utils_Array::value('option_group_id', $field);
    $timeFormat = CRM_Utils_Array::value('time_format', $field);
    $optionPerLine = CRM_Utils_Array::value('options_per_line', $field);

    $freezeString = "";
    $freezeStringChecked = "";

    switch ($dataType) {
      case 'Date':
        $customTimeFormat = '';
        $customFormat = NULL;
        if ($timeFormat == 1) {
          $customTimeFormat = '%l:%M %P';
        }
        elseif ($timeFormat == 2) {
          $customTimeFormat = '%H:%M';
        }

        $supportableFormats = [
          'mm/dd' => "%B %E%f $customTimeFormat",
          'dd-mm' => "%E%f %B $customTimeFormat",
          'yy' => "%Y $customTimeFormat",
        ];
        if ($format = CRM_Utils_Array::value('date_format', $field)) {
          if (CRM_Utils_Array::arrayKeyExists($format, $supportableFormats)) {
            $customFormat = $supportableFormats["$format"];
          }
        }

        $retValue = CRM_Utils_Date::customFormat($value, $customFormat);
        break;

      case 'Boolean':
        if ($value == '1') {
          $retValue = $freezeStringChecked . ts('Yes') . "\n";
        }
        else {
          $retValue = $freezeStringChecked . ts('No') . "\n";
        }
        break;

      case 'Link':
        if ($value) {
          $retValue = CRM_Utils_System::formatWikiURL($value);
        }
        break;

      case 'File':
        $retValue = $values;
        break;

      case 'ContactReference':
        if (CRM_Utils_Array::value('data', $values)) {
          $retValue = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $values['data'], 'display_name');
        }
        break;

      case 'Memo':
        $retValue = $value;
        break;

      case 'Float':
        if ($htmlType == 'Text') {
          $retValue = (float)$value;
          break;
        }
      case 'Money':
        if ($htmlType == 'Text') {

          $retValue = CRM_Utils_Money::format($value, NULL, '%a');
          break;
        }
      case 'String':
      case 'Int':
        if (in_array($htmlType, ['Text', 'TextArea'])) {
          $retValue = $value;
          break;
        }
      case 'StateProvince':
      case 'Country':
        //added check for Multi-Select in the below if-statement
        $customData[] = $value;

        //form custom data for multiple-valued custom data
        switch ($htmlType) {
          case 'Multi-Select Country':
          case 'Select Country':
            $customData = $value;
            if (!is_array($value)) {
                $customData = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
              }
              $query = "
                    SELECT id as value, name as label  
                    FROM civicrm_country";
              $coDAO = CRM_Core_DAO::executeQuery($query);
              break;

          case 'Select State/Province':
          case 'Multi-Select State/Province':
            $customData = $value;
            if (!is_array($value)) {
              $customData = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            }
            $query = "
                SELECT id as value, name as label  
                FROM civicrm_state_province";
            $coDAO = CRM_Core_DAO::executeQuery($query);
            break;

          case 'Select':
            $customData = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            if ($option_group_id) {
              $query = "
                  SELECT label, value
                  FROM civicrm_option_value
                  WHERE option_group_id = %1
                  ORDER BY weight ASC, label ASC";
              $params = [1 => [$option_group_id, 'Integer']];
              $coDAO = CRM_Core_DAO::executeQuery($query, $params);
            }
            break;

          case 'CheckBox':
          case 'AdvMulti-Select':
          case 'Multi-Select':
            $customData = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          default:
            if ($option_group_id) {
              $query = "
                SELECT label, value
                FROM civicrm_option_value
                WHERE option_group_id = %1
                ORDER BY weight ASC, label ASC";
              $params = [1 => [$option_group_id, 'Integer']];
              $coDAO = CRM_Core_DAO::executeQuery($query, $params);
            }
            break;
        }

        $options = [];

        if (is_object($coDAO)) {
          while ($coDAO->fetch()) {
            $options[$coDAO->value] = ($dataType == 'Country' || $dataType == 'StateProvince') ? ts($coDAO->label) : $coDAO->label;
          }
        }
        else {
          CRM_Core_Error::fatal(ts('You have hit issue CRM-4716. Please post a report with as much detail as possible on the CiviCRM forums. You can truncate civicr_cache to get around this problem'));
        }


        CRM_Utils_Hook::customFieldOptions($field['id'], $options, FALSE);

        $retValue = NULL;
        foreach ($options as $optionValue => $optionLabel) {
          //to show only values that are checked
          if (in_array((string) $optionValue, $customData)) {
            $checked = in_array($optionValue, $customData) ? $freezeStringChecked : $freezeString;
            if (!$optionPerLine || $dncOptionPerLine) {
              if ($retValue) {
                $retValue .= ",&nbsp;";
              }
              $retValue .= $checked . $optionLabel;
            }
            else {
              $retValue[] = $checked . $optionLabel;
            }
          }
        }
        break;
    }

    //special case for option per line formatting
    if ($optionPerLine > 1 && is_array($retValue)) {
      $rowCounter = 0;
      $fieldCounter = 0;
      $displayValues = [];
      $displayString = NULL;
      foreach ($retValue as $val) {
        if ($displayString) {
          $displayString .= ",&nbsp;";
        }

        $displayString .= $val;
        $rowCounter++;
        $fieldCounter++;

        if (($rowCounter == $optionPerLine) || ($fieldCounter == count($retValue))) {
          $displayValues[] = $displayString;
          $displayString = NULL;
          $rowCounter = 0;
        }
      }
      $retValue = $displayValues;
    }

    $retValue = $retValue ?? NULL;
    return $retValue;
  }

  /**
   * Get the custom group titles by custom field ids.
   *
   * @param  array $fieldIds    - array of custom field ids.
   *
   * @return array $groupLabels - array consisting of groups and fields labels with ids.
   * @access public
   */
  static function getGroupTitles($fieldIds) {
    if (!is_array($fieldIds) && empty($fieldIds)) {
      return;
    }

    $groupLabels = [];
    $fIds = "(" . CRM_Utils_Array::implode(',', $fieldIds) . ")";

    $query = "
SELECT  civicrm_custom_group.id as groupID, civicrm_custom_group.title as groupTitle,
civicrm_custom_field.label as fieldLabel, civicrm_custom_field.id as fieldID
FROM  civicrm_custom_group, civicrm_custom_field
WHERE  civicrm_custom_group.id = civicrm_custom_field.custom_group_id
AND  civicrm_custom_field.id IN {$fIds}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $groupLabels[$dao->fieldID] = ['fieldID' => $dao->fieldID,
        'fieldLabel' => $dao->fieldLabel,
        'groupID' => $dao->groupID,
        'groupTitle' => $dao->groupTitle,
      ];
    }

    return $groupLabels;
  }

  static function dropAllTables() {
    $query = "SELECT table_name FROM civicrm_custom_group";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $query = "DROP TABLE IF EXISTS {$dao->table_name}";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
    * Check whether custom group is empty or not.
    *
    * @param   int $gID    - custom group id.
    *
    * @return boolean true if empty otherwise false.
    * @access public
    */
  static function isGroupEmpty($gID) {
    if (!$gID) {
      return;
    }

    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
      $gID,
      'table_name'
    );

    $query = "SELECT count(id) FROM {$tableName} WHERE id IS NOT NULL LIMIT 1";
    $value = CRM_Core_DAO::singleValueQuery($query);

    if (empty($value)) {
      return TRUE;
    }

    return FALSE;
  }

  
  /**
   * Get custom groups/fields for type of entity.
   *
   * An array containing all custom groups and their custom fields is returned.
   *
   * @param string $entityType - of the contact whose contact type is needed
   * @param null $deprecated   - deprecated
   * @param int    $entityId   - optional - id of entity if we need to populate the tree with custom values.
   * @param int    $groupId    - optional group id (if we need it for a single group only)
   *                           - if groupId is 0 it gets for inline groups only
   *                           - if groupId is -1 we get for all groups
   * @param array $subTypes    - array that subtypes
   * @param string $subName    - subname that use for this group
   * @param bool $fromCache    - use cache or not
   *
   * @return array $groupTree  - array consisting of all groups and fields and optionally populated with custom data values.
   *
   * @access public
   *
   * @static
   *
   */
  public static function getTreeWithOptions($entityType, $entityID = NULL, $groupID = NULL, $subTypes = [], $subName = NULL, $fromCache = TRUE) {
    $tree =  self::getTree($entityType, NULL, $entityID, $groupID, $subTypes, $subName, $fromCache);
    foreach($tree as $groupId => &$group) {
      if (is_numeric($groupId) && !empty($group['fields'])) {
        foreach($group['fields'] as $fieldId => &$field) {
          if (!empty($field['option_group_id'])) {
            $field['options'] = CRM_Core_BAO_CustomOption::valuesByID($fieldId, $field['option_group_id']);
          }
          elseif($field['data_type'] == 'Boolean') {
            $field['options'] = [
              0 => ts('No'),
              1 => ts('Yes'),
            ];
          }
          elseif($field['data_type'] == 'Country') {
            $field['options'] = CRM_Core_PseudoConstant::country();
          }
          elseif($field['data_type'] == 'StateProvince') {
            $field['options'] = CRM_Core_PseudoConstant::stateProvince();
          }
        }
      }
    }
    return $tree;
  }

  /**
   * Match label-value of all custom fields in specific type
   * 
   * Limited in select entity type. This will loop all custom fields and trying
   * to match options by custom field data type and html type
   *
   * @param string $entityType entity type that want to filter fields
   * @param array $items item with key=field name, value=option value pair
   * @param array $matches match will result here
   * @return void
   */
  public static function matchFieldValues($entityType, $items, &$matches) {
    $tree = self::getTreeWithOptions($entityType);
    foreach($items as $label => $value) {
      $label = (string) $label;
      foreach($tree as $groupId => $group) {
        if (!is_numeric($groupId)) {
          continue;
        }
        foreach($group['fields'] as $field) {
          if ($label === $field['label']) {
            if ($field['data_type'] == 'Boolean') {
              $val = CRM_Utils_String::strtoboolstr($value);
              if ($val !== FALSE) {
                $matches[0][$label] = 1;
                $matches[1]['custom_'.$field['id']] = $val;
              }
            }
            elseif (!empty($field['options'])) {
              $val = array_search($value, $field['options']);
              if ($val !== FALSE) {
                $matches[0][$label] = 1;
                $matches[1]['custom_'.$field['id']] = $val;
              }
              elseif(CRM_Utils_Array::arrayKeyExists($value, $field['options'])) {
                $matches[0][$label] = 1;
                $matches[1]['custom_'.$field['id']] = $value;
              }
            }
            elseif ($field['data_type'] == 'Memo') {
              $matches[0][$label] = 1;
              $matches[1]['custom_'.$field['id']] = "$value";
            }
            elseif ($field['data_type'] == 'Date') {
              $val = CRM_Utils_Type::validate($value, 'Timestamp');
              if ($val) {
                $matches[0][$label] = 1;
                $matches[1]['custom_'.$field['id']] = $val;
              }
            }
            elseif ($field['data_type'] == 'Link') {
              if(CRM_Utils_Rule::url($value)) {
                $matches[0][$label] = 1;
                $matches[1]['custom_'.$field['id']] = $value;
              }
            }
            elseif ($field['data_type'] == 'ContactReference') {
              if (CRM_Utils_Rule::positiveInteger($value)) {
                $val = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'id', 'id');
                if ($val) {
                  $matches[0][$label] = 1;
                  $matches[1]['custom_'.$field['id']] = $val;
                }
              }
            }
            elseif ($field['html_type'] == 'Text') {
              switch($field['data_type']) {
                case 'String':
                  $matches[0][$label] = 1;
                  $matches[1]['custom_'.$field['id']] = "$value";
                  break;
                case 'Int':
                  if (CRM_Utils_Rule::integer($value)) {
                    $matches[1]['custom_'.$field['id']] = (int) $value;
                  }
                  break;  
                case 'Float':
                  if (CRM_Utils_Rule::numeric($value)) {
                    $matches[0][$label] = 1;
                    $matches[1]['custom_'.$field['id']] = $value;
                  }
                  break;  
                case 'Money':
                  if (CRM_Utils_Rule::money($value)) {
                    $matches[0][$label] = 1;
                    $matches[1]['custom_'.$field['id']] = $value ? $value : 0;
                  }
                  break;
              }
            }
          }
        }
      }
    }
  }
}

