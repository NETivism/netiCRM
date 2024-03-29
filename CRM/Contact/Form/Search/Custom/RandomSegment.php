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
class CRM_Contact_Form_Search_Custom_RandomSegment extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_debug = 0; function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = array(ts('Contact Id') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Email') => 'email',
    );

    $this->initialize();
  }

  function initialize() {
    $this->_segmentSize = CRM_Utils_Array::value('segmentSize', $this->_formValues);

    $this->_includeGroups = CRM_Utils_Array::value('includeGroups', $this->_formValues);

    $this->_excludeGroups = CRM_Utils_Array::value('excludeGroups', $this->_formValues);

    $this->_allSearch = FALSE;
    $this->_groups = FALSE;

    if (empty($this->_includeGroups) && empty($this->_excludeGroups)) {
      //empty search
      $this->_allSearch = TRUE;
    }

    if (!empty($this->_includeGroups) || !empty($this->_excludeGroups)) {
      //group(s) selected
      $this->_groups = TRUE;
    }
  }

  function buildForm(&$form) {
    $form->add('text',
      'segmentSize',
      ts('Segment Size'),
      TRUE
    );

    $groups = &CRM_Core_PseudoConstant::group();
    $inG = &$form->addElement('advmultiselect', 'includeGroups',
      ts('Include Group(s)') . ' ', $groups,
      array('size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      )
    );

    $outG = &$form->addElement('advmultiselect', 'excludeGroups',
      ts('Exclude Group(s)') . ' ', $groups,
      array('size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      )
    );

    $inG->setButtonAttributes('add', array('value' => ts('Add >>')));
    $outG->setButtonAttributes('add', array('value' => ts('Add >>')));
    $inG->setButtonAttributes('remove', array('value' => ts('<< Remove')));
    $outG->setButtonAttributes('remove', array('value' => ts('<< Remove')));

    $form->setTitle('Create a random segment of contacts');

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('segmentSize', 'includeGroups', 'excludeGroups'));
  }

  function summary() {
    return NULL;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE
  ) {
    $selectClause = "contact_a.id   as contact_id,
                         contact_a.contact_type as contact_type,
                         contact_a.sort_name    as sort_name,
                         civicrm_email.email    as email";

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  function from() {
    //define table name
    $randomNum = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_{$randomNum}";

    //block for Group search
    $smartGroup = array();
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
      $iGroups = $includedGroups;
    }
    if (is_array($this->_excludeGroups)) {
      $xGroups = CRM_Utils_Array::implode(',', $this->_excludeGroups);
    }
    else {
      $xGroups = 0;
    }

    $sql = "DROP TEMPORARY TABLE IF EXISTS Xg_{$this->_tableName}";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "CREATE TEMPORARY TABLE Xg_{$this->_tableName} ( contact_id int primary key) ENGINE=HEAP";
    CRM_Core_DAO::executeQuery($sql);

    //used only when exclude group is selected
    if ($xGroups != 0) {
      $excludeGroup = "INSERT INTO  Xg_{$this->_tableName} ( contact_id )
              SELECT  DISTINCT civicrm_group_contact.contact_id
              FROM civicrm_group_contact
              WHERE 
                 civicrm_group_contact.status = 'Added' AND
                 civicrm_group_contact.group_id IN ( {$xGroups} )";

      CRM_Core_DAO::executeQuery($excludeGroup);

      //search for smart group contacts
      foreach ($this->_excludeGroups as $keys => $values) {
        if (in_array($values, $smartGroup)) {
          $ssId = CRM_Utils_Array::key($values, $smartGroup);

          $smartSql = CRM_Contact_BAO_SavedSearch::contactIDsSQL($ssId);

          $smartSql = $smartSql . " AND contact_a.id NOT IN ( 
                          SELECT contact_id FROM civicrm_group_contact 
                          WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

          $smartGroupQuery = " INSERT IGNORE INTO Xg_{$this->_tableName}(contact_id) $smartSql";

          CRM_Core_DAO::executeQuery($smartGroupQuery);
        }
      }
    }

    $sql = "DROP TEMPORARY TABLE IF EXISTS Ig_{$this->_tableName}";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "CREATE TEMPORARY TABLE Ig_{$this->_tableName}
            ( id int PRIMARY KEY AUTO_INCREMENT,
              contact_id int,
              group_names varchar(64)) ENGINE=HEAP";

    if ($this->_debug > 0) {
      print "-- Include groups query: <pre>";
      print "$sql;";
      print "</pre>";
    }

    CRM_Core_DAO::executeQuery($sql);

    $includeGroup = "INSERT INTO Ig_{$this->_tableName} (contact_id, group_names)
             SELECT      civicrm_group_contact.contact_id, civicrm_group.name as group_name
             FROM        civicrm_group_contact
             LEFT JOIN   civicrm_group
             ON          civicrm_group_contact.group_id = civicrm_group.id";

    //used only when exclude group is selected
    if ($xGroups != 0) {
      $includeGroup .= " LEFT JOIN        Xg_{$this->_tableName}
                                      ON        civicrm_group_contact.contact_id = Xg_{$this->_tableName}.contact_id";
    }
    $includeGroup .= " WHERE           
                                 civicrm_group_contact.status = 'Added'  AND
                                 civicrm_group_contact.group_id IN($iGroups)";

    //used only when exclude group is selected
    if ($xGroups != 0) {
      $includeGroup .= " AND  Xg_{$this->_tableName}.contact_id IS null";
    }

    if ($this->_debug > 0) {
      print "-- Include groups query: <pre>";
      print "$includeGroup;";
      print "</pre>";
    }

    CRM_Core_DAO::executeQuery($includeGroup);

    //search for smart group contacts
    foreach ($this->_includeGroups as $keys => $values) {
      if (in_array($values, $smartGroup)) {

        $ssId = CRM_Utils_Array::key($values, $smartGroup);

        $smartSql = CRM_Contact_BAO_SavedSearch::contactIDsSQL($ssId);

        $smartSql .= " AND contact_a.id NOT IN ( 
                               SELECT contact_id FROM civicrm_group_contact
                               WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

        //used only when exclude group is selected
        if ($xGroups != 0) {
          $smartSql .= " AND contact_a.id NOT IN (SELECT contact_id FROM  Xg_{$this->_tableName})";
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

    $from = "FROM civicrm_contact contact_a";

    $fromTail = "LEFT JOIN civicrm_email ON ( contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 )";

    $fromTail .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";

    // now create a temp table to store the randomized contacts
    $sql = "DROP TEMPORARY TABLE IF EXISTS random_{$this->_tableName}";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "CREATE TEMPORARY TABLE random_{$this->_tableName} ( id int primary key ) ENGINE=HEAP";
    CRM_Core_DAO::executeQuery($sql);

    if (substr($this->_segmentSize, -1) == '%') {
      $countSql = "SELECT DISTINCT contact_a.id $from $fromTail
                         WHERE " . $this->where();
      $dao = CRM_Core_DAO::executeQuery($countSql);
      $totalSize = $dao->N;
      $multiplier = substr($this->_segmentSize, 0, strlen($this->_segmentSize) - 1);
      $multiplier /= 100;
      //CRM_Core_Error::debug( "Total size: $totalSize<br/>Multiplier: $multiplier<br/>");
      $this->_segmentSize = round($totalSize * $multiplier);
    }

    $sql = "INSERT INTO random_{$this->_tableName} ( id )
                SELECT DISTINCT contact_a.id $from $fromTail
                WHERE " . $this->where() . "
                ORDER BY RAND()
                LIMIT {$this->_segmentSize}";
    CRM_Core_DAO::executeQuery($sql);

    $from = "FROM random_{$this->_tableName} random";

    $from .= " INNER JOIN civicrm_contact contact_a ON random.id = contact_a.id";

    $from .= " $fromTail";

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    return '(1)';
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function __destruct() {
    // the temporary tables are dropped automatically
    // so we dont do it here
    // but let mysql clean up
    return;
  }
}

