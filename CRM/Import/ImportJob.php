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
 | Version 3, 19 November 2009.                                       |
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

require_once 'CRM/Core/DAO.php';

/**
 * This class acts like a psuedo-BAO for transient import job tables
 */
class CRM_Import_ImportJob {

  protected $_tableName;
  protected $_primaryKeyName;
  protected $_statusFieldName;

  protected $_doGeocodeAddress;
  protected $_invalidRowCount;
  protected $_conflictRowCount;
  protected $_onDuplicate;

  public $_newGroupName;
  public $_newGroupDesc;
  public $_groups;
  public $_allGroups;
  public $_newTagName;
  public $_newTagDesc;
  public $_tag;
  public $_allTags;

  protected $_mapper;
  protected $_mapperKeys;
  protected $_mapperLocTypes;
  protected $_mapperPhoneTypes;
  protected $_mapperImProviders;
  protected $_mapperWebsiteTypes;
  protected $_mapperRelated;
  protected $_mapperRelatedContactType;
  protected $_mapperRelatedContactDetails;
  protected $_mapperRelatedContactLocType;
  protected $_mapperRelatedContactPhoneType;
  protected $_mapperRelatedContactImProvider;
  protected $_mapperRelatedContactWebsiteType;
  protected $_mapFields;

  protected $_parser;

  protected $_groupAdditions;
  protected $_tagAdditions;

  public function __construct($tableName = NULL, $createSql = NULL, $createTable = FALSE) {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    if ($createTable) {
      if (!$createSql) {
        CRM_Core_Error::fatal('Either an existing table name or an SQL query to build one are required');
      }

      // FIXME: we should regen this table's name if it exists rather than drop it
      if (!$tableName) {
        $tableName = 'civicrm_import_job_' . md5(uniqid(rand(), TRUE));
      }
      $db->query("DROP TABLE IF EXISTS $tableName");
      $db->query("CREATE TABLE $tableName ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci $createSql");
    }

    if (!$tableName) {
      CRM_Core_Error::fatal('Import Table is required.');
    }

    $this->_tableName = $tableName;

    //initialize the properties.
    $properties = array('mapperKeys',
      'mapperRelated',
      'mapperLocTypes',
      'mapperPhoneTypes',
      'mapperImProviders',
      'mapperWebsiteTypes',
      'mapperRelatedContactType',
      'mapperRelatedContactDetails',
      'mapperRelatedContactLocType',
      'mapperRelatedContactPhoneType',
      'mapperRelatedContactImProvider',
      'mapperRelatedContactWebsiteType',
    );
    foreach ($properties as $property) {
      $this->{"_$property"} = array();
    }
    $this->_groupAdditions = array();
    $this->_tagAdditions = array();
  }

  public function getTableName() {
    return $this->_tableName;
  }

  public function isComplete($dropIfComplete = TRUE) {
    if (!$this->_statusFieldName) {
      CRM_Core_Error::fatal("Could not get name of the import status field");
    }
    $query = "SELECT * FROM $this->_tableName
                  WHERE  $this->_statusFieldName = 'NEW' LIMIT 1";
    $result = CRM_Core_DAO::executeQuery($query);
    if ($result->fetch()) {
      return FALSE;
    }
    if ($dropIfComplete) {
      $query = "DROP TABLE $this->_tableName";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  public function setJobParams(&$params) {
    foreach ($params as $param => $value) {
      eval("\$this->_$param = \$value;");
    }
  }

  public function runImport(&$form, $timeout = 55) {
    $mapper = $this->_mapper;
    $mapperFields = array();
    $phoneTypes = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();
    $locationTypes = CRM_Core_PseudoConstant::locationType();

    //initialize mapper perperty value.
    $mapperPeroperties = array('mapperRelated' => 'mapperRelatedVal',
      'mapperLocTypes' => 'mapperLocTypesVal',
      'mapperPhoneTypes' => 'mapperPhoneTypesVal',
      'mapperImProviders' => 'mapperImProvidersVal',
      'mapperWebsiteTypes' => 'mapperWebsiteTypesVal',
      'mapperRelatedContactType' => 'mapperRelatedContactTypeVal',
      'mapperRelatedContactDetails' => 'mapperRelatedContactDetailsVal',
      'mapperRelatedContactLocType' => 'mapperRelatedContactLocTypeVal',
      'mapperRelatedContactPhoneType' => 'mapperRelatedContactPhoneTypeVal',
      'mapperRelatedContactImProvider' => 'mapperRelatedContactImProviderVal',
      'mapperRelatedContactWebsiteType' => 'mapperRelatedContactWebsiteTypeVal',
    );

    foreach ($mapper as $key => $value) {
      //set respective mapper value to null.
      foreach (array_values($mapperPeroperties) as $perpertyVal)$$perpertyVal = NULL;

      $header = array($this->_mapFields[$fldName]);
      $fldName = CRM_Utils_Array::value(0, $mapper[$key]);
      $selOne = CRM_Utils_Array::value(1, $mapper[$key]);
      $selTwo = CRM_Utils_Array::value(2, $mapper[$key]);
      $selThree = CRM_Utils_Array::value(3, $mapper[$key]);
      $this->_mapperKeys[$key] = $fldName;

      //need to differentiate non location elements.
      if ($selOne && is_numeric($selOne)) {
        if ($fldName == 'url') {
          $header[] = $websiteTypes[$selOne];
          $mapperWebsiteTypesVal = $selOne;
        }
        else {
          $header[] = $locationTypes[$selOne];
          $mapperLocTypesVal = $selOne;
          if ($selTwo && is_numeric($selTwo)) {
            if ($fldName == 'phone') {
              $header[] = $phoneTypes[$selTwo];
              $mapperPhoneTypesVal = $selTwo;
            }
            elseif ($fldName == 'im') {
              $header[] = $imProviders[$selTwo];
              $mapperImProvidersVal = $selTwo;
            }
          }
        }
      }

      list($id, $first, $second) = explode('_', $fldName, 3);
      if (($first == 'a' && $second == 'b') ||
        ($first == 'b' && $second == 'a')
      ) {

        $header[] = ucwords(str_replace("_", " ", $selOne));

        $relationType = new CRM_Contact_DAO_RelationshipType();
        $relationType->id = $id;
        $relationType->find(TRUE);
        $mapperRelatedContactTypeVal = $relationType->{"contact_type_$second"};

        $mapperRelatedVal = $fldName;
        if ($selOne) {
          $mapperRelatedContactDetailsVal = $selOne;
          if ($selTwo) {
            if ($selOne == 'url') {
              $header[] = $websiteTypes[$selTwo];
              $mapperRelatedContactWebsiteTypeVal = $selTwo;
            }
            else {
              $header[] = $locationTypes[$selTwo];
              $mapperRelatedContactLocTypeVal = $selTwo;
              if ($selThree) {
                if ($selOne == 'phone') {
                  $header[] = $phoneTypes[$selThree];
                  $mapperRelatedContactPhoneTypeVal = $selThree;
                }
                elseif ($selOne == 'im') {
                  $header[] = $imProviders[$selThree];
                  $mapperRelatedContactImProviderVal = $selThree;
                }
              }
            }
          }
        }
      }
      $mapperFields[] = implode(' - ', $header);

      //set the respective mapper param array values.
      foreach ($mapperPeroperties as $mapperProKey => $mapperProVal) {
        $this->{"_$mapperProKey"}[$key] = $$mapperProVal;
      }
    }

    require_once 'CRM/Import/Parser/Contact.php';
    $this->_parser = new CRM_Import_Parser_Contact(
      $this->_mapperKeys,
      $this->_mapperLocTypes,
      $this->_mapperPhoneTypes,
      $this->_mapperImProviders,
      $this->_mapperRelated,
      $this->_mapperRelatedContactType,
      $this->_mapperRelatedContactDetails,
      $this->_mapperRelatedContactLocType,
      $this->_mapperRelatedContactPhoneType,
      $this->_mapperRelatedContactImProvider,
      $this->_mapperWebsiteTypes,
      $this->_mapperRelatedContactWebsiteType
    );
    $this->_parser->_job = $this;
    $this->_parser->run($this->_tableName, $mapperFields,
      CRM_Import_Parser::MODE_IMPORT,
      $this->_contactType,
      $this->_primaryKeyName,
      $this->_statusFieldName,
      $this->_onDuplicate,
      $this->_statusID,
      $this->_totalRowCount,
      $this->_doGeocodeAddress,
      CRM_Import_Parser::DEFAULT_TIMEOUT,
      $this->_contactSubType
    );

    $contactIds = $this->_parser->getImportedContacts();

    //get the related contactIds. CRM-2926
    $relatedContactIds = $this->_parser->getRelatedImportedContacts();
    if ($relatedContactIds) {
      $contactIds = array_merge($contactIds, $relatedContactIds);
      if ($form) {
        $form->set('relatedCount', count($relatedContactIds));
      }
    }

    if ($this->_newGroupName || count($this->_groups)) {
      if ($form) {
        $form->set('groupAdditions', $this->_groupAdditions);
      }
    }

    if ($this->_newTagName || count($this->_tag)) {
      if ($form) {
        $form->set('tagAdditions', $this->_tagAdditions);
      }
    }
  }

  public function setFormVariables($form) {
    $this->_parser->set($form, CRM_Import_Parser::MODE_IMPORT);
  }

  public function addImportedContactsToNewGroup($contactIds, $newGroupName, $newGroupDesc) {
    static $newGroupId;

    if ($newGroupName && empty($newGroupId)) {
      /* Create a new group */

      $gParams = array(
        'title' => $newGroupName,
        'description' => $newGroupDesc,
        'is_active' => TRUE,
      );
      $group = CRM_Contact_BAO_Group::create($gParams);
      $newGroupId = $group->id;
      $this->_groupAdditions[$newGroupId]['new'] = TRUE;
      $this->_groups[] = $newGroupId;
    }

    if (is_array($this->_groups)) {
      foreach ($this->_groups as $groupId) {
        $addCount = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
        $totalCount = $addCount[1];
        if ($groupId == $newGroupId) {
          $name = $newGroupName;
          $new = TRUE;
        }
        else {
          $name = $this->_allGroups[$groupId];
          $new = FALSE;
        }
        if (!isset($this->_groupAdditions[$groupId]['name'])) {
          $this->_groupAdditions[$groupId]['name'] = $name;
          $this->_groupAdditions[$groupId]['url'] = CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $groupId);
          $this->_groupAdditions[$groupId]['added'] = 0;
          $this->_groupAdditions[$groupId]['notAdded'] = 0;
        }
        $this->_groupAdditions[$groupId]['added'] += $totalCount;
        $this->_groupAdditions[$groupId]['notAdded'] += $addCount[2];
      }
    }
    return FALSE;
  }

  public function tagImportedContactsWithNewTag($contactIds, $newTagName, $newTagDesc) {
    static $newTagId;

    if ($newTagName && empty($newTagId)) {
      /* Create a new Tag */

      $tagParams = array(
        'name' => $newTagName,
        'title' => $newTagName,
        'description' => $newTagDesc,
        'is_selectable' => TRUE,
        'used_for' => 'civicrm_contact',
      );
      require_once 'CRM/Core/BAO/Tag.php';
      $id = array();
      $addedTag = CRM_Core_BAO_Tag::add($tagParams, $id);
      $newTagId = $addedTag->id;
      $this->_tagAdditions[$newTagId]['new'] = TRUE;
      $this->_tag[$newTagId] = 1;
    }

    //add Tag to Import
    if (is_array($this->_tag)) {
      require_once "CRM/Core/BAO/EntityTag.php";
      foreach ($this->_tag as $tagId => $val) {
        $addTagCount = CRM_Core_BAO_EntityTag::addEntitiesToTag($contactIds, $tagId);
        $totalTagCount = $addTagCount[1];
        if ($tagId == $addedTag->id) {
          $tagName = $newTagName;
          $new = TRUE;
        }
        else {
          $tagName = $this->_allTags[$tagId];
          $new = FALSE;
        }
        if (!isset($this->_tagAdditions[$tagId]['name'])) {
          $this->_tagAdditions[$tagId]['name'] = $tagName;
          $this->_tagAdditions[$tagId]['url'] = CRM_Utils_System::url('civicrm/contact/search', 'reset=1&force=1&tid=' . $tagId);
          $this->_tagAdditions[$tagId]['added'] = 0;
          $this->_tagAdditions[$tagId]['notAdded'] = 0;
        }
        $this->_tagAdditions[$tagId]['added'] += $totalTagCount;
        $this->_tagAdditions[$tagId]['notAdded'] += $addTagCount[2];
      }
    }
    return FALSE;
  }

  public function addContactToGroupTag($contactId, $groups = array(), $tags = array()) {
    static $existsGroups, $existsTag;

    $contactIds = array($contactId);
    if(!empty($groups) && is_array($groups)) {
      foreach ($groups as $groupName) {
        $groupId = 0;
        if ($existsGroups[$groupName]) {
          $groupId = $existsGroups[$groupName];
        }
        else{
          $query = "SELECT id FROM civicrm_group WHERE title LIKE %1";
          $groupId = CRM_Core_DAO::singleValueQuery($query, array(1 => array($groupName, 'String')));
          if (empty($groupId)) {
            $gParams = array(
              'title' => $groupName,
              'description' => '',
              'is_active' => TRUE,
            );
            $group = CRM_Contact_BAO_Group::create($gParams);
            $groupId = $group->id;
          }
        }
        $existsGroups[$groupName] = $groupId;
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
      }
    }

    if(!empty($tags) && is_array($tags)) {
      foreach ($tags as $tagName) {
        if ($existsTag[$tagName]) {
          $tagId = $existsTag[$tagName];
        }
        else {
          $query = "SELECT id FROM civicrm_tag WHERE name LIKE %1";
          $tagId = CRM_Core_DAO::singleValueQuery($query, array(1 => array($tagName, 'String')));
          if (empty($tagId)) {
            $tagParams = array(
              'name' => $tagName,
              'title' => $tagName,
              'description' => '',
              'is_selectable' => TRUE,
              'used_for' => 'civicrm_contact',
            );
            $id = array();
            $tag = CRM_Core_BAO_Tag::add($tagParams, $id);
            $tagId = $tag->id;
          }
        }
        $existsTag[$tagName] = $tagId;
        $addTagCount = CRM_Core_BAO_EntityTag::addEntitiesToTag($contactIds, $tagId);
      }
    }
  }

  public static function getIncompleteImportTables() {
    $dao = new CRM_Core_DAO();
    $database = $dao->database();
    $query = "SELECT   TABLE_NAME FROM INFORMATION_SCHEMA
                  WHERE    TABLE_SCHEMA = ? AND
                           TABLE_NAME LIKE 'civicrm_import_job_%'
                  ORDER BY TABLE_NAME";
    $result = CRM_Core_DAO::executeQuery($query, array($database));
    $incompleteImportTables = array();
    while ($importTable = $result->fetch()) {
      if (!$this->isComplete($importTable)) {
        $incompleteImportTables[] = $importTable;
      }
    }
    return $incompleteImportTables;
  }
}

