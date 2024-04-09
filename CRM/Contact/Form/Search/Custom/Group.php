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

require_once 'CRM/Contact/Form/Search/Custom/Base.php';
require_once 'CRM/Contact/BAO/SavedSearch.php';
class CRM_Contact_Form_Search_Custom_Group extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;

  protected $_tableName = NULL;

  protected $_where = ' (1) '; function __construct(&$formValues) {
    $this->_formValues = $formValues;
    $this->_columns = array(ts('Contact Id') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Group Name') => 'gname',
      ts('Tag Name') => 'tname',
    );

    $this->_includeGroups = CRM_Utils_Array::value('includeGroups', $this->_formValues, array());
    $this->_excludeGroups = CRM_Utils_Array::value('excludeGroups', $this->_formValues, array());
    $this->_includeTags = CRM_Utils_Array::value('includeTags', $this->_formValues, array());
    $this->_excludeTags = CRM_Utils_Array::value('excludeTags', $this->_formValues, array());

    //define variables
    $this->_allSearch = FALSE;
    $this->_groups = FALSE;
    $this->_tags = FALSE;
    $this->_andOr = $this->_formValues['andOr'];


    //make easy to check conditions for groups and tags are
    //selected or it is empty search
    if (empty($this->_includeGroups) && empty($this->_excludeGroups) &&
      empty($this->_includeTags) && empty($this->_excludeTags)
    ) {
      //empty search
      $this->_allSearch = TRUE;
    }

    if (!empty($this->_includeGroups) || !empty($this->_excludeGroups)) {
      //group(s) selected
      $this->_groups = TRUE;
    }

    if (!empty($this->_includeTags) || !empty($this->_excludeTags)) {
      //tag(s) selected
      $this->_tags = TRUE;
    }
  }

  function __destruct() {
    // mysql drops the tables when connectiomn is terminated
    // cannot drop tables here, since the search might be used
    // in other parts after the object is destroyed
  }

  function buildForm(&$form) {

    $groups = &CRM_Core_PseudoConstant::group();

    $tags = &CRM_Core_PseudoConstant::tag();
    if (count($groups) == 0 || count($tags) == 0) {
      CRM_Core_Session::setStatus(ts("Atleast one Group and Tag must be present, for Custom Group / Tag search."));
      $url = CRM_Utils_System::url('civicrm/contact/search/custom/list', 'reset=1');
      CRM_Utils_System::redirect($url);
    }

    $inG = &$form->addElement('select', 'includeGroups',
      ts('Include Group(s)') . ' ', $groups,
      array('size' => 5,
        'style' => 'width:400px',
        'multiple' => 'multiple',
      )
    );

    $outG = &$form->addElement('select', 'excludeGroups',
      ts('Exclude Group(s)') . ' ', $groups,
      array('size' => 5,
        'style' => 'width:400px',
        'multiple' => 'multiple',
      )
    );
    $andOr = &$form->addElement('checkbox', 'andOr', 'Search with tag (check for AND, uncheck For OR)', NULL,
      array('checked' => 'checked')
    );

    $int = &$form->addElement('select', 'includeTags',
      ts('Include Tag(s)') . ' ', $tags,
      array('size' => 5,
        'style' => 'width:400px',
        'multiple' => 'multiple',
      )
    );

    $outt = &$form->addElement('select', 'excludeTags',
      ts('Exclude Tag(s)') . ' ', $tags,
      array('size' => 5,
        'style' => 'width:400px',
        'multiple' => 'multiple',
      )
    );

    $defaults = array(
      'andOr' => empty($this->_andOr) ? 0 : 1,
    );
    $form->setDefaults($defaults);

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('includeGroups', 'excludeGroups', 'andOr', 'includeTags', 'excludeTags'));
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "DISTINCT(contact_a.id)  as contact_id";
    }
    else {
      $selectClause = "DISTINCT(contact_a.id)  as contact_id,
                         contact_a.contact_type as contact_type,
                         contact_a.sort_name    as sort_name";

      //distinguish column according to user selection
      if ($this->_includeGroups && (!$this->_includeTags)) {
        unset($this->_columns['Tag Name']);
        $selectClause .= ", GROUP_CONCAT(DISTINCT group_names ORDER BY group_names ASC ) as gname";
      }
      elseif ($this->_includeTags && (!$this->_includeGroups)) {
        unset($this->_columns['Group Name']);
        $selectClause .= ", GROUP_CONCAT(DISTINCT tag_names  ORDER BY tag_names ASC ) as tname";
      }
      else {
        if (!empty($this->_includeTags) && !empty($this->_includeGroups)) {
          $selectClause .= ", GROUP_CONCAT(DISTINCT group_names ORDER BY group_names ASC ) as gname , GROUP_CONCAT(DISTINCT tag_names ORDER BY tag_names ASC ) as tname";
        }
      }
    }

    $from = $this->from();

    $where = $this->where($includeContactIDs);

    $sql = " SELECT $selectClause $from WHERE  $where ";
    if (!$justIDs && !$this->_allSearch) {
      $sql .= " GROUP BY contact_id ";
    }

    // Define ORDER BY for query in $sort, with default value
    if (!$justIDs) {
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= " ORDER BY " . trim($sort->orderBy());
        }
      }
      else {
        $sql .= " ORDER BY contact_id ASC";
      }
    }

    if ($offset >= 0 && $rowcount > 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    return $sql;
  }

  function from() {

    //define table name
    $randomNum = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_{$randomNum}";

    //block for Group search
    $smartGroup = array();
    if ($this->_groups || $this->_allSearch) {
      require_once 'CRM/Contact/DAO/Group.php';
      $group = new CRM_Contact_DAO_Group();
      $group->is_active = 1;
      $group->find();
      while ($group->fetch()) {
        $allGroups[] = $group->id;
        if ($group->saved_search_id) {
          $smartGroup[$group->saved_search_id] = $group->id;
        }
      }
      $includedGroups = CRM_Utils_Array::implode(',', $allGroups);

      if (!empty($this->_includeGroups)) {
        $iGroups = CRM_Utils_Array::implode(',', $this->_includeGroups);
      }
      else {
        //if no group selected search for all groups
        $iGroups = NULL;
      }
      if (is_array($this->_excludeGroups)) {
        $xGroups = CRM_Utils_Array::implode(',', $this->_excludeGroups);
      }
      else {
        $xGroups = 0;
      }

      $sql = "CREATE TEMPORARY TABLE Xg_{$this->_tableName} ( contact_id int primary key) ENGINE=HEAP";
      CRM_Core_DAO::executeQuery($sql);

      //used only when exclude group is selected
      if (!empty($xGroups)) {
        $excludeGroup = "INSERT INTO  Xg_{$this->_tableName} ( contact_id )
                  SELECT  DISTINCT civicrm_group_contact.contact_id
                  FROM civicrm_group_contact, civicrm_contact                    
                  WHERE 
                     civicrm_contact.id = civicrm_group_contact.contact_id AND 
                     civicrm_group_contact.status = 'Added' AND
                     civicrm_group_contact.group_id IN( {$xGroups})";

        CRM_Core_DAO::executeQuery($excludeGroup);

        //search for smart group contacts
        foreach ($this->_excludeGroups as $keys => $values) {
          if (in_array($values, $smartGroup)) {
            $contactIdField = "contact_a.id";
            $ssId = CRM_Utils_Array::key($values, $smartGroup);

            $smartSql = CRM_Contact_BAO_SavedSearch::contactIDsSQL($ssId);
            if (strstr($smartSql, "contact_a.contact_id")) {
              $contactIdField = "contact_a.contact_id";
            }

            $smartSql = $smartSql . " AND $contactIdField NOT IN (
                              SELECT contact_id FROM civicrm_group_contact 
                              WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

            $smartGroupQuery = " INSERT IGNORE INTO Xg_{$this->_tableName}(contact_id) $smartSql";

            CRM_Core_DAO::executeQuery($smartGroupQuery);
          }
        }
      }


      $sql = "CREATE TEMPORARY TABLE Ig_{$this->_tableName} ( id int PRIMARY KEY AUTO_INCREMENT,
                                                                   contact_id int,
                                                                   group_names varchar(64)) ENGINE=HEAP";

      CRM_Core_DAO::executeQuery($sql);

      if ($iGroups) {
        $includeGroup = "INSERT INTO Ig_{$this->_tableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, civicrm_group.title as group_name
                 FROM                civicrm_contact
                    INNER JOIN       civicrm_group_contact
                            ON       civicrm_group_contact.contact_id = civicrm_contact.id
                    LEFT JOIN        civicrm_group
                            ON       civicrm_group_contact.group_id = civicrm_group.id";
      }
      else {
        $includeGroup = "INSERT INTO Ig_{$this->_tableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, ''
                 FROM                civicrm_contact";
      }


      //used only when exclude group is selected
      $includeGroup .= " LEFT JOIN Xg_{$this->_tableName} ON civicrm_contact.id = Xg_{$this->_tableName}.contact_id";

      if ($iGroups) {
        $includeGroup .= " WHERE           
                                     civicrm_group_contact.status = 'Added'  AND
                                     civicrm_group_contact.group_id IN($iGroups)";
      }
      else {
        $includeGroup .= " WHERE ( 1 ) ";
      }

      //used only when exclude group is selected
      $includeGroup .= " AND  Xg_{$this->_tableName}.contact_id IS null";

      CRM_Core_DAO::executeQuery($includeGroup);

      //search for smart group contacts

      foreach ($this->_includeGroups as $keys => $values) {
        if (in_array($values, $smartGroup)) {

          $contactIdField = "contact_a.id";
          $ssId = CRM_Utils_Array::key($values, $smartGroup);

          $smartSql = CRM_Contact_BAO_SavedSearch::contactIDsSQL($ssId);
          if (strstr($smartSql, "contact_a.contact_id")) {
            $contactIdField = "contact_a.contact_id";
          }

          $smartSql .= " AND $contactIdField NOT IN (
                              SELECT contact_id FROM civicrm_group_contact
                              WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

          //used only when exclude group is selected
          if ($xGroups != 0) {
            $smartSql .= " AND $contactIdField NOT IN (SELECT contact_id FROM  Xg_{$this->_tableName})";
          }

          $smartGroupQuery = " INSERT IGNORE INTO Ig_{$this->_tableName}(contact_id) 
                                     $smartSql";

          CRM_Core_DAO::executeQuery($smartGroupQuery);
          $insertGroupNameQuery = "UPDATE IGNORE Ig_{$this->_tableName}
                                         SET group_names = (SELECT title FROM civicrm_group
                                                            WHERE civicrm_group.id = $values)
                                         WHERE Ig_{$this->_tableName}.contact_id IS NOT NULL 
                                         AND Ig_{$this->_tableName}.group_names IS NULL";
          CRM_Core_DAO::executeQuery($insertGroupNameQuery);
        }
      }
    }
    //group contact search end here;

    //block for Tags search
    if ($this->_tags || $this->_allSearch) {
      //find all tags
      require_once 'CRM/Core/DAO/Tag.php';
      $tag = new CRM_Core_DAO_Tag();
      $tag->is_active = 1;
      $tag->find();
      while ($tag->fetch()) {
        $allTags[] = $tag->id;
      }
      $includedTags = CRM_Utils_Array::implode(',', $allTags);

      if (!empty($this->_includeTags)) {
        $iTags = CRM_Utils_Array::implode(',', $this->_includeTags);
      }
      else {
        //if no group selected search for all groups
        $iTags = NULL;
      }
      if (is_array($this->_excludeTags)) {
        $xTags = CRM_Utils_Array::implode(',', $this->_excludeTags);
      }
      else {
        $xTags = 0;
      }

      $sql = "CREATE TEMPORARY TABLE Xt_{$this->_tableName} ( contact_id int primary key) ENGINE=HEAP";
      CRM_Core_DAO::executeQuery($sql);

      //used only when exclude tag is selected
      if (!empty($xTags)) {
        $excludeTag = "INSERT INTO  Xt_{$this->_tableName} ( contact_id )
                  SELECT  DISTINCT civicrm_entity_tag.entity_id
                  FROM civicrm_entity_tag, civicrm_contact                    
                  WHERE 
                     civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                     civicrm_contact.id = civicrm_entity_tag.entity_id AND 
                     civicrm_entity_tag.tag_id IN( {$xTags})";

        CRM_Core_DAO::executeQuery($excludeTag);
      }

      $sql = "CREATE TEMPORARY TABLE It_{$this->_tableName} ( id int PRIMARY KEY AUTO_INCREMENT,
                                                               contact_id int,
                                                               tag_names varchar(64)) ENGINE=HEAP";

      CRM_Core_DAO::executeQuery($sql);

      if ($iTags) {
        $includeTag = "INSERT INTO It_{$this->_tableName} (contact_id, tag_names)
                 SELECT              civicrm_contact.id as contact_id, civicrm_tag.name as tag_name
                 FROM                civicrm_contact
                    INNER JOIN       civicrm_entity_tag
                            ON       ( civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                                       civicrm_entity_tag.entity_id = civicrm_contact.id )
                    LEFT JOIN        civicrm_tag
                            ON       civicrm_entity_tag.tag_id = civicrm_tag.id";
      }
      else {
        $includeTag = "INSERT INTO It_{$this->_tableName} (contact_id, tag_names)
                 SELECT              civicrm_contact.id as contact_id, ''
                 FROM                civicrm_contact";
      }

      //used only when exclude tag is selected
      $includeTag .= " LEFT JOIN Xt_{$this->_tableName} ON civicrm_contact.id = Xt_{$this->_tableName}.contact_id";
      if ($iTags) {
        $includeTag .= " WHERE   civicrm_entity_tag.tag_id IN($iTags)";
      }
      else {
        $includeTag .= " WHERE ( 1 ) ";
      }

      //used only when exclude tag is selected
      $includeTag .= " AND  Xt_{$this->_tableName}.contact_id IS null";

      CRM_Core_DAO::executeQuery($includeTag);
    }

    $from = " FROM civicrm_contact contact_a";

    /*
     * check the situation and set booleans
     */

    if ($iGroups != 0) {
      $iG = TRUE;
    }
    else {
      $iG = FALSE;
    }
    if ($iTags != 0) {
      $iT = TRUE;
    }
    else {
      $iT = FALSE;
    }

    // force exclude group and tag true. because we have deleted contacts
    if ($this->_groups) {
      $xG = TRUE;
    }
    if ($this->_tags) {
      $xT = TRUE;
    }

    if (!$this->_groups || !$this->_tags) {
      $this->_andOr = 1;
    }

    // add deleted contact into exclude table
    $sql = "CREATE TEMPORARY TABLE Xd_{$this->_tableName} ( contact_id int primary key) ENGINE=HEAP";
    CRM_Core_DAO::executeQuery($sql);
    $deletedContact = "REPLACE INTO  Xd_{$this->_tableName} ( contact_id ) SELECT id FROM civicrm_contact WHERE is_deleted = 1";
    CRM_Core_DAO::executeQuery($deletedContact);

    /*
         * Set from statement depending on array sel
         */

    if ($iG && $iT && $xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " INNER JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL )
                    AND contact_a.id NOT IN(SELECT contact_id FROM Xg_{$this->_tableName})
                    AND contact_a.id NOT IN(SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable3 ON (contact_a.id = temptable3.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable4 ON (contact_a.id = temptable4.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL OR
                    temptable3.contact_id IS NOT NULL OR temptable4.contact_id IS NOT NULL)";
      }
    }
    if ($iG && $iT && $xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " INNER JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL )
                    AND contact_a.id NOT IN(SELECT contact_id FROM Xg_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable3 ON (contact_a.id = temptable3.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL OR
                    temptable3.contact_id IS NOT NULL)";
      }
    }
    if ($iG && $iT && !$xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " INNER JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL )
                    AND contact_a.id NOT IN(SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable3 ON (contact_a.id = temptable3.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL OR
                    temptable3.contact_id IS NOT NULL)";
      }
    }
    if ($iG && $iT && !$xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " INNER JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL )";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if ($iG && !$iT && $xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xg_{$this->_tableName}) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable3 ON (contact_a.id = temptable3.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL OR
                    temptable3.contact_id IS NOT NULL)";
      }
    }
    if ($iG && !$iT && $xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xg_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if ($iG && !$iT && !$xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if ($iG && !$iT && !$xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL)";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && $iT && $xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xg_{$this->_tableName}) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable3 ON (contact_a.id = temptable3.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL OR
                    temptable3.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && $iT && $xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xg_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && $iT && !$xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL) AND contact_a.id NOT IN(
                    SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && $iT && !$xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL)";
      }
      else {
        $from .= " LEFT JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && !$iT && $xG && $xT) {
      if ($this->_andOr == 1) {
        $this->_where = "contact_a.id NOT IN(SELECT contact_id FROM Xg_{$this->_tableName})
                    AND contact_a.id NOT IN(SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && !$iT && !$xG && $xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN It_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "contact_a.id NOT IN(SELECT contact_id FROM Xt_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Xt_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN It_{$this->_tableName} temptable2 ON (contact_a.id = temptable2.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL OR temptable2.contact_id IS NOT NULL)";
      }
    }
    if (!$iG && !$iT && $xG && !$xT) {
      if ($this->_andOr == 1) {
        $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "contact_a.id NOT IN(SELECT contact_id FROM Xg_{$this->_tableName})";
      }
      else {
        $from .= " LEFT JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $from .= " LEFT JOIN Xg_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
        $this->_where = "( temptable1.contact_id IS NOT NULL)";
      }
    }

    $from .= " LEFT JOIN civicrm_email ON ( contact_a.id = civicrm_email.contact_id AND ( civicrm_email.is_primary = 1 OR civicrm_email.is_bulkmail = 1 ) )";
    $from .= " LEFT JOIN Xd_{$this->_tableName} deleted ON (contact_a.id = deleted.contact_id)";
    if ($this->_where) {
      $this->_where .= " AND ( deleted.contact_id IS NULL) ";
    }
    else {
      $this->_where = " ( deleted.contact_id IS NULL) ";
    }

    return $from;
  }

  function where($includeContactIDs = FALSE) {

    if ($includeContactIDs) {
      $contactIDs = array();

      foreach ($this->_formValues as $id => $value) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
        if ($value && !empty($contactID)) {
          $contactIDs[] = $contactID;
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ( $contactIDs )";
      }
      $where = "{$this->_where} AND " . CRM_Utils_Array::implode(' AND ', $clauses);
    }
    else {
      $where = $this->_where;
    }

    return $where;
  }

  /* 
     * Functions below generally don't need to be modified
     */
  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function &columns() {
    return $this->_columns;
  }

  function summary() {
    return NULL;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }
}

