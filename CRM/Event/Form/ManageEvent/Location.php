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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Event/Form/ManageEvent.php';
require_once 'CRM/Event/BAO/Event.php';
require_once 'CRM/Contact/Form/Location.php';
require_once 'CRM/Core/SelectValues.php';

/**
 * This class generates form components for processing Event Location
 * civicrm_event_page.
 */
class CRM_Event_Form_ManageEvent_Location extends CRM_Event_Form_ManageEvent {

  /**
   * how many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  CONST LOCATION_BLOCKS = 1;

  /**
   * the variable, for storing the location array
   *
   * @var array
   */
  protected $_locationIds = array();

  /**
   * the variable, for storing location block id with event
   *
   * @var int
   */
  protected $_oldLocBlockId = 0;

  /**
   * get the db values for this form
   *
   */
  public $_values = array();

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $blockCount = CRM_Utils_Request::retrieve('count', 'Positive', CRM_Core_DAO::$_nullObject);
    if(empty($blockCount)){
      // for ajax , don't do extra things. refs #20631
      parent::preProcess();
    }


    $this->_values = $this->get('values');
    if ($this->_id && empty($this->_values)) {

      //get location values.
      $params = array('entity_id' => $this->_id,
        'entity_table' => 'civicrm_event',
      );
      require_once 'CRM/Core/BAO/Location.php';
      $this->_values = CRM_Core_BAO_Location::getValues($params);

      //get event values.
      $params = array('id' => $this->_id);
      CRM_Event_BAO_Event::retrieve($params, $this->_values);
      $this->set('values', $this->_values);
    }

    //location blocks.
    CRM_Contact_Form_Location::preProcess($this);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = $this->_values;

    if (CRM_Utils_Array::value('loc_block_id', $defaults)) {
      $defaults['loc_event_id'] = $defaults['loc_block_id'];
      $countLocUsed = CRM_Event_BAO_Event::countEventsUsingLocBlockId($defaults['loc_block_id']);
      if ($countLocUsed > 1) {
        $this->assign('locUsed', TRUE);
      }
    }

    $config = CRM_Core_Config::singleton();
    if (!isset($defaults['address'][1]['country_id'])) {
      $defaults['address'][1]['country_id'] = $config->defaultContactCountry;
    }

    require_once 'CRM/Contact/Form/Edit/Address.php';
    if (!empty($defaults['address'])) {
      foreach ($defaults['address'] as $key => $value) {
        CRM_Contact_Form_Edit_Address::fixStateSelect($this,
          "address[$key][country_id]",
          "address[$key][state_province_id]",
          CRM_Utils_Array::value('country_id', $value,
            $config->defaultContactCountry
          )
        );
      }
    }
    $defaults['location_option'] = $this->_oldLocBlockId ? 2 : 1;

    return $defaults;
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(array('CRM_Event_Form_ManageEvent_Location', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = array();
    // check for state/country mapping
    CRM_Contact_Form_Edit_Address::formRule($fields, $errors);

    return empty($errors) ? TRUE : $errors;
  }

  /**
   *  function to build location block
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    //load form for child blocks
    if ($this->_addBlockName) {
      require_once (str_replace('_', DIRECTORY_SEPARATOR, "CRM_Contact_Form_Edit_" . $this->_addBlockName) . ".php");
      return eval('CRM_Contact_Form_Edit_' . $this->_addBlockName . '::buildQuickForm( $this );');
    }

    $this->applyFilter('__ALL__', 'trim');

    //build location blocks.
    CRM_Contact_Form_Location::buildQuickForm($this);

    //fix for CRM-1971
    $this->assign('action', $this->_action);

    if ($this->_id) {
      $this->_oldLocBlockId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
        $this->_id, 'loc_block_id'
      );
    }

    // get the list of location blocks being used by other events
    $locationEvents = CRM_Event_BAO_Event::getLocationEvents();

    // remove duplicates and make sure that the duplicate entry with key as
    // loc_block_id of this event (this->_id) is preserved
    if (CRM_Utils_Array::value($this->_oldLocBlockId, $locationEvents)) {
      $possibleDuplicate = $locationEvents[$this->_oldLocBlockId];
      $locationEvents = array_flip(array_unique($locationEvents));
      if (CRM_Utils_Array::value($possibleDuplicate, $locationEvents)) {
        $locationEvents[$possibleDuplicate] = $this->_oldLocBlockId;
      }
      $locationEvents = array_flip($locationEvents);
    }
    else {
      $locationEvents = array_unique($locationEvents);
    }

    $events = array();
    if (!empty($locationEvents)) {
      $this->assign('locEvents', TRUE);
      $optionTypes = array('1' => ts('Create new location'),
        '2' => ts('Use existing location'),
      );

      $this->addRadio('location_option', ts("Choose Location"), $optionTypes,
        array('onclick' => "showLocFields();"), '<br/>', FALSE
      );

      if (!isset($locationEvents[$this->_oldLocBlockId]) || (!$this->_oldLocBlockId)) {
        $locationEvents = array('' => ts('- select -')) + $locationEvents;
      }
      $this->add('select', 'loc_event_id', ts('Use Location'), $locationEvents);
    }
    $this->addElement('advcheckbox', 'is_show_location', ts('Show Location?'));
    parent::buildQuickForm();
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->exportValues();
    $deleteOldBlock = FALSE;

    // if 'use existing location' option is selected -
    if (($params['location_option'] == 2) &&
      CRM_Utils_Array::value('loc_event_id', $params) &&
      ($params['loc_event_id'] != $this->_oldLocBlockId)
    ) {
      // if new selected loc is different from old loc, update the loc_block_id
      // so that loc update would affect the selected loc and not the old one.
      $deleteOldBlock = TRUE;
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $this->_id,
        'loc_block_id', $params['loc_event_id']
      );
    }

    // if 'create new loc' option is selected, set the loc_block_id for this event to null
    // so that an update would result in creating a new loc.
    if ($this->_oldLocBlockId && ($params['location_option'] == 1)) {
      $deleteOldBlock = TRUE;
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $this->_id,
        'loc_block_id', 'null'
      );
    }

    // if 'create new loc' optioin is selected OR selected new loc is different
    // from old one, go ahead and delete the old loc provided thats not being
    // used by any other event
    if ($this->_oldLocBlockId && $deleteOldBlock) {
      CRM_Event_BAO_Event::deleteEventLocBlock($this->_oldLocBlockId, $this->_id);
    }

    // get ready with location block params
    $params['entity_table'] = 'civicrm_event';
    $params['entity_id'] = $this->_id;

    require_once 'CRM/Core/BAO/LocationType.php';
    $defaultLocationType = &CRM_Core_BAO_LocationType::getDefault();
    foreach (array('address', 'phone', 'email') as $block) {
      if (!CRM_Utils_Array::value($block, $params) || !is_array($params[$block])) {
        continue;
      }
      foreach ($params[$block] as $count => & $values) {
        if ($count == 1) {
          $values['is_primary'] = 1;
        }
        $values['location_type_id'] = ($defaultLocationType->id) ? $defaultLocationType->id : 1;
      }
    }

    // create/update event location
    require_once 'CRM/Core/BAO/Location.php';
    $location = CRM_Core_BAO_Location::create($params, TRUE, 'event');
    $params['loc_block_id'] = $location['id'];

    // finally update event params
    $params['id'] = $this->_id;
    require_once 'CRM/Event/BAO/Event.php';
    CRM_Event_BAO_Event::add($params);

    parent::endPostProcess();
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Event Location');
  }
}

