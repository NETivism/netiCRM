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
 * Business object for managing price sets
 *
 */
class CRM_Price_BAO_Set extends CRM_Price_DAO_Set {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a price set object
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Price_DAO_Set object
   * @access public
   * @static
   */
  static function create(&$params) {
    $priceSetBAO = new CRM_Price_BAO_Set();
    $priceSetBAO->copyValues($params);
    if (defined('CIVICRM_EVENT_PRICE_SET_DOMAIN_ID') && CIVICRM_EVENT_PRICE_SET_DOMAIN_ID) {
      $priceSetBAO->domain_id = CRM_Core_Config::domainID();
    }
    return $priceSetBAO->save();
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
   * @return object CRM_Price_DAO_Set object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_Set', $params, $defaults);
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
  static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_Set', $id, 'is_active', $isActive);
  }

  /**
   * Get the price set title.
   *
   * @param int $id   id of price set
   *
   * @return string   title
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $id, 'title');
  }

  /**
   * Return a list of all forms which use this price set.
   *
   * @param int  $id id of price set
   *
   * @return array
   */
  public static function &getUsedBy($id, $onlyTable = FALSE) {
    $usedBy = $forms = $tables = [];
    $queryString = "
SELECT   entity_table, entity_id 
FROM     civicrm_price_set_entity
WHERE    price_set_id = %1";
    $params = [1 => [$id, 'Integer']];
    $crmFormDAO = CRM_Core_DAO::executeQuery($queryString, $params);

    while ($crmFormDAO->fetch()) {
      $forms[$crmFormDAO->entity_table][] = $crmFormDAO->entity_id;
      $tables[] = $crmFormDAO->entity_table;
    }

    if ($onlyTable == TRUE) {
      return $tables;
    }
    if (empty($forms)) {
      return $usedBy;
    }
    foreach ($forms as $table => $entities) {
      switch ($table) {
        case 'civicrm_event':
          $ids = CRM_Utils_Array::implode(',', $entities);
          $queryString = "SELECT ce.id as id, ce.title as title, ce.is_public as isPublic, ce.start_date as startDate, ce.end_date as endDate, civicrm_option_value.label as eventType
FROM       civicrm_event ce
LEFT JOIN  civicrm_option_value ON  
           ( ce.event_type_id = civicrm_option_value.value )
LEFT JOIN  civicrm_option_group ON 
           ( civicrm_option_group.id = civicrm_option_value.option_group_id )
WHERE     
	       civicrm_option_group.name = 'event_type' AND
           ( ce.is_template IS NULL OR ce.is_template = 0) AND 
           ce.id IN ($ids);";
          $crmDAO = CRM_Core_DAO::executeQuery($queryString);
          while ($crmDAO->fetch()) {
            $usedBy[$table][$crmDAO->id]['title'] = $crmDAO->title;
            $usedBy[$table][$crmDAO->id]['eventType'] = $crmDAO->eventType;
            $usedBy[$table][$crmDAO->id]['startDate'] = $crmDAO->startDate;
            $usedBy[$table][$crmDAO->id]['endDate'] = $crmDAO->endDate;
            $usedBy[$table][$crmDAO->id]['isPublic'] = $crmDAO->isPublic;
          }
          break;

        case 'civicrm_contribution_page':
          $ids = CRM_Utils_Array::implode(',', $entities);
          $queryString = "SELECT cp.id as id, cp.title as title, cp.start_date as startDate, cp.end_date as endDate,ct.name as type
FROM      civicrm_contribution_page cp, civicrm_contribution_type ct
WHERE     ct.id = cp.contribution_type_id AND 
          cp.id IN ($ids);";
          $crmDAO = CRM_Core_DAO::executeQuery($queryString);
          while ($crmDAO->fetch()) {
            $usedBy[$table][$crmDAO->id]['title'] = $crmDAO->title;
            $usedBy[$table][$crmDAO->id]['type'] = $crmDAO->type;
            $usedBy[$table][$crmDAO->id]['startDate'] = $crmDAO->startDate;
            $usedBy[$table][$crmDAO->id]['endDate'] = $crmDAO->endDate;
          }
          break;

        case 'civicrm_contribution':
          $usedBy[$table] = 1;
          break;

        default:
          CRM_Core_Error::fatal("$table is not supported in PriceSet::usedBy()");
          break;
      }
    }

    return $usedBy;
  }

  /**
   * Delete the price set
   *
   * @param int $id Price Set id
   *
   * @return boolean false if fields exist for this set, true if the
   * set could be deleted
   *
   * @access public
   * @static
   */
  public static function deleteSet($id) {
    // remove from all inactive forms
    $usedBy = &self::getUsedBy($id);
    if (isset($usedBy['civicrm_event'])) {

      foreach ($usedBy['civicrm_event'] as $eventId => $unused) {
        $eventDAO = new CRM_Event_DAO_Event();
        $eventDAO->id = $eventId;
        $eventDAO->find();
        while ($eventDAO->fetch()) {
          self::removeFrom('civicrm_event', $eventDAO->id);
        }
      }
    }

    // delete price fields
    $priceField = new CRM_Price_DAO_Field();
    $priceField->price_set_id = $id;
    $priceField->find();
    while ($priceField->fetch()) {
      // delete options first
      CRM_Price_BAO_Field::deleteField($priceField->id);
    }

    $set = new CRM_Price_DAO_Set();
    $set->id = $id;
    return $set->delete();
  }

  /**
   * Link the price set with the specified table and id
   *
   * @param string $entityTable
   * @param integer $entityId
   * @param integer $priceSetId
   *
   * @return bool
   */
  public static function addTo($entityTable, $entityId, $priceSetId) {
    // verify that the price set exists
    $dao = new CRM_Price_DAO_Set();
    $dao->id = $priceSetId;
    if (!$dao->find()) {
      return FALSE;
    }
    unset($dao);


    $dao = new CRM_Price_DAO_SetEntity();
    // find if this already exists
    $dao->entity_id = $entityId;
    $dao->entity_table = $entityTable;
    $dao->find(TRUE);

    // add or update price_set_id
    $dao->price_set_id = $priceSetId;
    return $dao->save();
  }

  /**
   * Delete price set for the given entity and id
   *
   * @param string $entityTable
   * @param integer $entityId
   */
  public static function removeFrom($entityTable, $entityId) {

    $dao = new CRM_Price_DAO_SetEntity();
    $dao->entity_table = $entityTable;
    $dao->entity_id = $entityId;
    return $dao->delete();
  }

  /**
   * Find a price_set_id associatied with the given table and id
   *
   * @param string $entityTable
   * @param integer $entityId
   *
   * @return integer|false price_set_id, or false if none found
   */
  public static function getFor($entityTable, $entityId) {
    if (!$entityTable || !$entityId) {
      return FALSE;
    }


    $dao = new CRM_Price_DAO_SetEntity();
    $dao->entity_table = $entityTable;
    $dao->entity_id = $entityId;
    $dao->find(TRUE);
    return (isset($dao->price_set_id)) ? $dao->price_set_id : FALSE;
  }

  /**
   * Find a price_set_id associatied with the given option value or  field ID
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *                      array may contain either option id or
   *                      price field id
   *
   * @return price set id on success, null  otherwise
   * @static
   * @access public
   */
  public static function getSetId(&$params) {
    $fid = NULL;


    if ($oid = CRM_Utils_Array::value('oid', $params)) {

      $fieldValue = new CRM_Price_DAO_FieldValue();
      $fieldValue->id = $oid;
      if ($fieldValue->find(TRUE)) {
        $fid = $fieldValue->price_field_id;
      }
    }
    else {
      $fid = CRM_Utils_Array::value('fid', $params);
    }

    if (isset($fid)) {
      return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Field', $fid, 'price_set_id');
    }

    return NULL;
  }

  /**
   * Return an associative array of all price sets
   *
   * @param bool   $withInactive        whether or not to include inactive entries
   * @param string $extendComponentName name of the component like 'CiviEvent','CiviContribute'
   *
   * @return array associative array of id => name
   */
  public static function getAssoc($withInactive = FALSE, $extendComponentName = FALSE) {
    $query = "
    SELECT 
       DISTINCT ( price_set_id ) as id, title 
    FROM 
       civicrm_price_field, 
       civicrm_price_set 
    WHERE 
       civicrm_price_set.id = civicrm_price_field.price_set_id ";

    if (!$withInactive) {
      $query .= " AND civicrm_price_set.is_active = 1 ";
    }

    if (defined('CIVICRM_EVENT_PRICE_SET_DOMAIN_ID') && CIVICRM_EVENT_PRICE_SET_DOMAIN_ID) {
      $query .= " AND civicrm_price_set.domain_id = " . CRM_Core_Config::domainID();
    }

    $priceSets = [];

    if ($extendComponentName) {
      $componentId = CRM_Core_Component::getComponentID($extendComponentName);
      if (!$componentId) {
        return $priceSets;
      }
      $query .= " AND civicrm_price_set.extends LIKE '%$componentId%' ";
    }

    $dao = &CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $priceSets[$dao->id] = $dao->title;
    }
    return $priceSets;
  }

  /**
   * Get price set details
   *
   * An array containing price set details (including price fields) is returned
   *
   * @param int $setId - price set id whose details are needed
   *
   * @return array $setTree - array consisting of field details
   */
  public static function getSetDetail($setID, $required = TRUE, $isActive = TRUE) {
    // create a new tree
    $setTree = [];
    $select = $from = $where = $orderBy = '';

    $priceFields = [
      'id',
      'name',
      'label',
      'html_type',
      'is_enter_qty',
      'max_value',
      'help_post',
      'is_display_amounts',
      'options_per_line',
      'is_active',
      'visibility_id',
      'max_value',
      'active_on', 
      'expire_on',
    ];
    if ($required == TRUE) {
      $priceFields[] = 'is_required';
    }

    // create select
    $select = 'SELECT ' . CRM_Utils_Array::implode(',', $priceFields);
    $from = ' FROM civicrm_price_field';

    $params = [];
    $params[1] = [$setID, 'Integer'];
    $where = '
WHERE price_set_id = %1
';
    if ($isActive) {
      $where .= ' AND is_active = 1';
    }

    $orderBy = ' ORDER BY weight';

    $sql = $select . $from . $where . $orderBy;

    $dao = &CRM_Core_DAO::executeQuery($sql, $params);

    $visibility = CRM_Core_PseudoConstant::visibility('name');

    while ($dao->fetch()) {
      $fieldID = $dao->id;

      $setTree[$setID]['fields'][$fieldID] = [];
      $setTree[$setID]['fields'][$fieldID]['id'] = $fieldID;

      foreach ($priceFields as $field) {
        if ($field == 'id' || is_null($dao->$field)) {
          continue;
        }

        if ($field == 'visibility_id') {
          $setTree[$setID]['fields'][$fieldID]['visibility'] = $visibility[$dao->$field];
        }
        $setTree[$setID]['fields'][$fieldID][$field] = $dao->$field;
      }
      

      $inactiveNeeded = FALSE;
      if(!$isActive) {
        // also get in-active options
        $inactiveNeeded = TRUE;
      }
      $setTree[$setID]['fields'][$fieldID]['options'] = CRM_Price_BAO_Field::getOptions($fieldID, $inactiveNeeded);
    }

    // also get the pre and post help from this price set
    $sql = "
SELECT help_pre, help_post
FROM   civicrm_price_set
WHERE  id = %1";
    $dao = &CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      $setTree[$setID]['help_pre'] = $dao->help_pre;
      $setTree[$setID]['help_post'] = $dao->help_post;
    }

    return $setTree;
  }

  static function initSet(&$form, $id, $entityTable = 'civicrm_event') {
    // get price info
    if ($priceSetId = self::getFor($entityTable, $id)) {
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $entityId = $entity = NULL;

        switch ($entityTable) {
          case 'civicrm_event':
            $entity = 'participant';
            if (CRM_Utils_System::getClassName($form) == 'CRM_Event_Form_Participant') {
              $entityId = $form->_id;
            }
            else {
              $entityId = $form->_participantId;
            }
            break;

          case 'civicrm_contribution_page':
          case 'civicrm_contribution':
            $entity = 'contribution';
            $entityId = $form->_id;
            break;
        }

        if ($entityId && $entity) {

          $form->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($entityId, $entity);
        }
        $required = FALSE;
      }
      else {
        $required = TRUE;
      }

      $form->_priceSetId = $priceSetId;
      $priceSet = self::getSetDetail($priceSetId, $required);
      $form->_priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
      $form->_values['fee'] = CRM_Utils_Array::value('fields', $form->_priceSet);

      //get the price set fields participant count.
      if ($entityTable == 'civicrm_event') {


        //get option count info.
        $form->_priceSet['optionsCountTotal'] = self::getPricesetCount($priceSetId);
        if ($form->_priceSet['optionsCountTotal']) {
          $optionsCountDeails = [];
          foreach ($form->_priceSet['fields'] as $field) {
            foreach ($field['options'] as $option) {
              $count = CRM_Utils_Array::value('count', $option, 0);
              $optionsCountDeails['fields'][$field['id']]['options'][$option['id']] = $count;
            }
          }
          $form->_priceSet['optionsCountDetails'] = $optionsCountDeails;
        }

        //get option max value info.
        $optionsMaxValueTotal = 0;
        $optionsMaxValueDetails = [];
        foreach ($form->_priceSet['fields'] as $field) {
          if (isset($field['max_value']) && !empty($field['max_value'])) {
            $optionsMaxValueDetails['fields'][$field['id']]['max_value'] = $field['max_value'];
            $optionsMaxValueTotal += $field['max_value'];
          }
          else {
            foreach ($field['options'] as $option) {
              $maxVal = CRM_Utils_Array::value('max_value', $option, 0);
              $optionsMaxValueDetails['fields'][$field['id']]['options'][$option['id']] = $maxVal;
              $optionsMaxValueTotal += $maxVal;
            }
          }
        }
        $form->_priceSet['optionsMaxValueTotal'] = $optionsMaxValueTotal;
        if ($optionsMaxValueTotal) {
          $form->_priceSet['optionsMaxValueDetails'] = $optionsMaxValueDetails;
        }
      }
      $form->set('priceSetId', $form->_priceSetId);
      $form->set('priceSet', $form->_priceSet);

      return $priceSetId;
    }
    return FALSE;
  }

  static function processAmount(&$fields, &$params, &$lineItem) {
    // using price set
    $totalPrice = 0;
    $radioLevel = $checkboxLevel = $selectLevel = $textLevel = [];


    foreach ($fields as $id => $field) {
      if (empty($params["price_{$id}"]) && $params["price_{$id}"] == NULL) {
        // skip if nothing was submitted for this field
        continue;
      }

      switch ($field['html_type']) {
        case 'Text':
          reset($field['options']);
          $params["price_{$id}"] = [key($field['options']) => $params["price_{$id}"]];
          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
          $totalPrice += $lineItem[key($field['options'])]['line_total'];
          break;

        case 'Radio':
          //special case if user select -none-
          if ($params["price_{$id}"] == 0) {
            break;
          }
          $optionValueId = $params["price_{$id}"];
          $params["price_{$id}"] = [$params["price_{$id}"] => $params["price_{$id}_{$optionValueId}_count"]];
          $optionLabel = $field['options'][$optionValueId]['label'];
          $params['amount_priceset_level_radio'] = [];
          $params['amount_priceset_level_radio'][$optionValueId] = $optionLabel;
          if (isset($radioLevel)) {
            $radioLevel = array_merge($radioLevel,
              array_keys($params['amount_priceset_level_radio'])
            );
          }
          else {
            $radioLevel = array_keys($params['amount_priceset_level_radio']);
          }
          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
          $totalPrice += $lineItem[$optionValueId]['line_total'];
          break;

        case 'Select':
          $params["price_{$id}"] = [$params["price_{$id}"] => 1];
          $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);
          $optionLabel = $field['options'][$optionValueId]['label'];
          $params['amount_priceset_level_select'] = [];
          $params['amount_priceset_level_select'][CRM_Utils_Array::key(1, $params["price_{$id}"])] = $optionLabel;
          if (isset($selectLevel)) {
            $selectLevel = array_merge($selectLevel, array_keys($params['amount_priceset_level_select']));
          }
          else {
            $selectLevel = array_keys($params['amount_priceset_level_select']);
          }
          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
          $totalPrice += $lineItem[$optionValueId]['line_total'];
          break;
        case 'CheckBox':
          $params['amount_priceset_level_checkbox'] = $optionIds = [];
          foreach ($params["price_{$id}"] as $optionId => $option) {
            $optionIds[] = $optionId;
            $optionLabel = $field['options'][$optionId]['label'];
            $params['amount_priceset_level_checkbox']["{$field['options'][$optionId]['id']}"] = $optionLabel;
            if (isset($checkboxLevel)) {
              $checkboxLevel = array_unique(array_merge($checkboxLevel, array_keys($params['amount_priceset_level_checkbox'])));
            }
            else {
              $checkboxLevel = array_keys($params['amount_priceset_level_checkbox']);
            }
            $params["price_{$id}"][$optionId] = $params["price_{$id}_{$optionId}_count"];
          }
          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
          foreach ($optionIds as $optionId) {
            $totalPrice += $lineItem[$optionId]['line_total'];
          }
          break;
      }
    }

    $params['amount_level'] = self::amountLevelText($lineItem);
    $params['amount'] = $totalPrice;
  }

  /**
   * Function to build the price set form.
   *
   * @return None
   * @access public
   */
  static function buildPriceSet(&$form) {
    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId) {
      return;
    }
    $priceSet = self::getSetDetail($priceSetId, TRUE);
    $form->_priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
    $form->assign('priceSet', $form->_priceSet);

    $className = CRM_Utils_System::getClassName($form);

    if ($className == 'CRM_Contribute_Form_Contribution_Main') {
      $feeBlock = &$form->_values['fee'];
    }
    else {
      $feeBlock = &$form->_priceSet['fields'];
    }

    // call the hook.

    CRM_Utils_Hook::buildAmount('contribution', $form, $feeBlock);

    $hasField = false;
    foreach ($feeBlock as $field) {
      if (CRM_Utils_Array::value('visibility', $field) == 'public' || $className == 'CRM_Contribute_Form_Contribution') {
        $options = CRM_Utils_Array::value('options', $field);
        if (!is_array($options)) {
          continue;
        }

        $element = CRM_Price_BAO_Field::addQuickFormElement($form, 'price_' . $field['id'], $field['id'], FALSE, CRM_Utils_Array::value('is_required', $field, FALSE), NULL, $options);
        if(!empty($element)){
          $hasField = TRUE;
        }

      }
    }
    if(!$hasField){
      $status = ts('There are no active field in the price set.');
      $session = CRM_Core_Session::singleton();
      $session->setStatus($status);
    }
  }

  /**
   * Get field ids of a price set
   *
   * @param int id Price Set id
   *
   * @return array of the field ids
   *
   * @access public
   * @static
   */
  public static function getFieldIds($id) {
    $priceField = new CRM_Price_DAO_Field();
    $priceField->price_set_id = $id;
    $priceField->find();
    while ($priceField->fetch()) {
      $var[] = $priceField->id;
    }
    return $var;
  }

  public static function getFields($id) {
    $setTitle = self::getTitle($id);
    $priceField = new CRM_Price_BAO_Field();
    $priceField->price_set_id = $id;
    $priceField->orderBy('weight, label');
    $priceField->find();

    $fields = [];
    while ($priceField->fetch()) {
      $fields[$priceField->id] = $setTitle.": ".$priceField->label." (".ts($priceField->html_type).")";
    }
    return $fields;
  }

  /**
   * This function is to make a copy of a price set, including
   * all the fields
   *
   * @param int $id the price set id to copy
   *
   * @return the copy object
   * @access public
   * @static
   */
  static function copy($id) {
    $maxId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_price_set");

    $title = ts('[Copy id %1]', [1 => $maxId + 1]);
    $fieldsFix = [
      'suffix' => [
        'title' => ' ' . $title,
        'name' => '__Copy_id_' . ($maxId + 1) . '_',
      ]
    ];

    $copy = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_Set', ['id' => $id], NULL, $fieldsFix);

    //copying all the blocks pertaining to the price set
    $copyPriceField = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_Field', ['price_set_id' => $id], ['price_set_id' => $copy->id]);
    if (!empty($copyPriceField)) {
      $price = array_combine(self::getFieldIds($id), self::getFieldIds($copy->id));

      //copy option group and values
      foreach ($price as $originalId => $copyId) {
        CRM_Core_DAO::copyGeneric('CRM_Price_DAO_FieldValue', ['price_field_id' => $originalId], ['price_field_id' => $copyId]);
      }
    }
    $copy->save();


    CRM_Utils_Hook::copy('Set', $copy);
    return $copy;
  }

  /**
   * This function is to check price set permission
   *
   * @param int $sid the price set id
   */
  static function checkPermission($sid) {
    if ($sid && defined('CIVICRM_EVENT_PRICE_SET_DOMAIN_ID') && CIVICRM_EVENT_PRICE_SET_DOMAIN_ID) {
      $domain_id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $sid, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domain_id) {
         return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
      }
    }
    return TRUE;
  }

  /**
   * Get the sum of participant count
   * for all fields of given price set.
   *
   * @param int $sid the price set id
   *
   * @access public
   * @static
   */
  public static function getPricesetCount($sid, $onlyActive = TRUE) {
    static $pricesetFieldCount;
    $count = 0;
    if (!$sid) {
      return $count;
    }

    $where = NULL;
    if ($onlyActive) {
      $where = 'AND  value.is_active = 1 AND field.is_active = 1';
    }

    if (!isset($pricesetFieldCount[$sid])) {
      $sql = "
SELECT  sum(value.count) as totalCount
FROM  civicrm_price_field_value  value
INNER JOIN  civicrm_price_field field ON ( field.id = value.price_field_id )
INNER JOIN  civicrm_price_set pset    ON ( pset.id = field.price_set_id ) 
WHERE  pset.id = %1 $where";

      $count = CRM_Core_DAO::singleValueQuery($sql, [1 => [$sid, 'Positive']]);
      $pricesetFieldCount[$sid] = ($count) ? $count : 0;
    }

    return $pricesetFieldCount[$sid];
  }

  public static function amountLevelText($lineItem) {
    $amount_level = [];
    $totalParticipant = 0;
    if (is_array($lineItem)) {
      foreach ($lineItem as $values) {
        $totalParticipant += $values['participant_count'];
        if ($values['html_type'] == 'Text') {
          $amount_level[] = $values['label'].'x'.$values['qty'].':'.$values['line_total'];
          continue;
        }
        $amount_level[] = $values['field_title'].'-'.$values['label'].'x'.$values['qty'].':'.$values['line_total'];
      }
    }

    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . ts('Participant Count') . ':' . $totalParticipant;
    }

    return CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $amount_level) . $displayParticipantCount . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
  }
}

