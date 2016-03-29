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
require_once 'CRM/Contribute/PseudoConstant.php';
class CRM_Contact_Form_Search_Custom_PriceSetContribution extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_price_set_id = NULL;

  protected $_tableName = NULL;

  protected $_cstatus = NULL; function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_price_set_id = CRM_Utils_Array::value('price_set_id', $this->_formValues);
    $this->setColumns();

    if ($this->_price_set_id) {
      $this->buildTempTable();
      $this->fillTable();
    }
    $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
  }

  function __destruct() {
    if ($this->_eventID) {
      $sql = "DROP TEMPORARY TABLE {$this->_tableName}";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
  }

  function buildTempTable() {
    $randomNum = md5($_GET['qfKey'] . $this->_price_set_id);
    $this->_tableName = "civicrm_temp_custom_{$randomNum}";
    $sql = "
CREATE TEMPORARY TABLE IF NOT EXISTS {$this->_tableName} (
  id int unsigned NOT NULL AUTO_INCREMENT,
  entity_table varchar(64) NOT NULL,
  entity_id int unsigned NOT NULL,
";

    foreach ($this->_columns as $dontCare => $fieldName) {
      if (in_array($fieldName, array('eneity_table',
            'entity_id',
          ))) {
        continue;
      }
      $sql .= "{$fieldName} varchar(32) default '',\n";
    }

    $sql .= "
PRIMARY KEY ( id ),
UNIQUE INDEX unique_entity ( entity_table, entity_id )
) ENGINE=HEAP DEFAULT CHARSET=utf8
";

    CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
  }

  function fillTable() {
    static $filled;
    if ($filled) {
      return;
    }
    $sql = "
SELECT l.price_field_value_id as price_field_value_id, 
       l.qty,
       l.entity_table,
       l.entity_id
FROM   civicrm_line_item l, civicrm_price_set_entity e
WHERE e.price_set_id = $this->_price_set_id AND
      l.entity_table = e.entity_table AND
      l.entity_id = e.entity_id
ORDER BY l.entity_table, l.entity_id ASC
";

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    // first store all the information by option value id
    $rows = array();
    while ($dao->fetch()) {
      $uniq = $dao->entity_table . "-" . $dao->entity_id;
      $rows[$uniq][] = "price_field_{$dao->price_field_value_id} = {$dao->qty}";
    }

    foreach (array_keys($rows) as $entity) {
      if (is_array($rows[$entity])) {
        $values = implode(',', $rows[$entity]);
        list($entity_table, $entity_id) = explode('-', $entity);
      }
      $values .= ", entity_table = '{$entity_table}', entity_id = $entity_id";
      $sql = "REPLACE INTO {$this->_tableName} SET $values";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM $this->_tableName");
    while ($dao->fetch()) {
      // contact id and total amount
      $sql = "SELECT contact_id, total_amount, contribution_status_id FROM $dao->entity_table WHERE id = $dao->entity_id";
      $data = CRM_Core_DAO::executeQuery($sql);
      $data->fetch();

      // email
      if ($data->contact_id) {
        $sql = "SELECT email FROM civicrm_email WHERE contact_id = {$data->contact_id} ORDER BY is_primary DESC";
        $email = CRM_Core_DAO::singleValueQuery($sql);

        $sql = "SELECT phone FROM civicrm_phone WHERE contact_id = {$data->contact_id} ORDER BY is_primary DESC";
        $phone = CRM_Core_DAO::singleValueQuery($sql);

        $sql = "SELECT a.postal_code, b.name as state_province, a.city, a.street_address FROM civicrm_address a INNER JOIN civicrm_state_province b ON a.state_province_id = b.id WHERE a.contact_id = {$data->contact_id} ORDER BY a.is_primary DESC";
        $addr = CRM_Core_DAO::executeQuery($sql);
        $addr->fetch();
        $addr->state_province = ts($addr->state_province);

        $sql = "UPDATE {$this->_tableName} 
              SET contact_id = {$data->contact_id},
              contribution_status_id = {$data->contribution_status_id},
              email = '{$email}',
              phone = '{$phone}',
              total_amount = '{$data->total_amount}',
              zip = '{$addr->postal_code}',
              county = '{$addr->state_province}',
              city = '{$addr->city}',
              address = '{$addr->street_address}'
              WHERE entity_id = {$dao->entity_id} AND entity_table = '{$dao->entity_table}'";
        CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
      }
    }
    $filled = TRUE;
  }

  function priceSetDAO($price_set_id = NULL) {

    // get all the events that have a price set associated with it
    $sql = "
SELECT e.id    as id,
       e.title as title,
       p.price_set_id as price_set_id
FROM   civicrm_price_set      e,
       civicrm_price_set_entity  p

WHERE  p.price_set_id = e.id
";

    $params = array();
    if ($price_set_id) {
      $params[1] = array($price_set_id, 'Integer');
      $sql .= " AND e.id = $price_set_id";
    }

    $dao = CRM_Core_DAO::executeQuery($sql,
      $params
    );
    return $dao;
  }

  function buildForm(&$form) {
    CRM_Core_OptionValue::getValues(array('name' => 'custom_search'), $custom_search);
    foreach ($custom_search as $c) {
      if ($c['value'] == $_GET['csid']) {
        $this->setTitle($c['description']);
        break;
      }
    }
    $dao = $this->priceSetDAO();

    $price_set = array();
    while ($dao->fetch()) {
      $price_set[$dao->id] = $dao->title;
    }

    if (empty($price_set)) {
      CRM_Core_Error::fatal(ts('There are no Price Sets'));
    }

    $form->add('select',
      'price_set_id',
      ts('Price Set'),
      $price_set,
      TRUE
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Price Set Export') . ' - '.ts('Contribution Page'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('price_set_id'));
  }

  function setColumns() {
    $this->_columns = array(
      ts('Contribution ID') => 'entity_id',
      ts('Contribution Status') => 'contribution_status_id',
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'display_name',
      ts('Postal Code') => 'zip',
      ts('State/Province') => 'county',
      ts('City') => 'city',
      ts('Address') => 'address',
      ts('Phone') => 'phone',
      ts('Email') => 'email',
      ts('Total Amount') => 'total_amount',
    );

    if (!$this->_price_set_id) {
      return;
    }

    // for the selected event, find the price set and all the columns associated with it.
    // create a column for each field and option group within it
    $dao = $this->priceSetDAO($this->_formValues['price_set_id']);

    if ($dao->fetch() &&
      !$dao->price_set_id
    ) {
      CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
    }

    // get all the fields and all the option values associated with it
    require_once 'CRM/Price/BAO/Set.php';
    $priceSet = CRM_Price_BAO_Set::getSetDetail($dao->price_set_id);
    if (is_array($priceSet[$dao->price_set_id])) {
      foreach ($priceSet[$dao->price_set_id]['fields'] as $key => $value) {
        if (is_array($value['options'])) {
          foreach ($value['options'] as $oKey => $oValue) {
            $columnHeader = CRM_Utils_Array::value('label', $value);
            if (CRM_Utils_Array::value('html_type', $value) != 'Text') {
              $columnHeader .= ' - ' . $oValue['label'];
            }

            $this->_columns[$columnHeader] = "price_field_{$oValue['id']}";
          }
        }
      }
    }
  }

  function summary() {
    return NULL;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    $selectClause = "
contact_a.id             as contact_id  ,
contact_a.display_name   as display_name";

    foreach ($this->_columns as $dontCare => $fieldName) {
      if (in_array($fieldName, array('contact_id',
            'display_name',
          ))) {
        continue;
      }
      $selectClause .= ",\ntempTable.{$fieldName} as {$fieldName}";
    }

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  function from() {
    return "FROM civicrm_contact contact_a INNER JOIN {$this->_tableName} tempTable ON contact_a.id = tempTable.contact_id";
  }

  function where($includeContactIDs = FALSE) {
    return ' ( 1 ) ';
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function setDefaultValues() {
    return array();
  }

  function alterRow(&$row) {
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    $action = array(
      '<a href="'.CRM_Utils_System::url('civicrm/contact/view/contribution', "reset=1&id={$row['entity_id']}&cid={$row['contact_id']}&action=view").'" class="action-item" target="_blank">'.ts('View').'</a>',
      '<a href="'.CRM_Utils_System::url('civicrm/contact/view/contribution', "reset=1&id={$row['entity_id']}&cid={$row['contact_id']}&action=update").'" class="action-item" target="_blank">'.ts('Edit').'</a>',
    );                                      
    if(isset($row['action'])){
      $row['action'] = implode('<br>', $action);
    }
  } 

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Export Price Set Info for a Contribution Page'));
    }
  }
}

