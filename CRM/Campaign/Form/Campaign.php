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
 * This class generates form components for processing a campaign
 *
 */
class CRM_Campaign_Form_Campaign extends CRM_Core_Form {

  /**
   * action
   *
   * @var int
   */
  protected $_action;

  /**
   * context
   *
   * @var string
   */
  protected $_context;

  /**
   * the id of the campaign we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_campaignId;

  public function preProcess() {

    if (!CRM_Campaign_BAO_Campaign::accessCampaignDashboard()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    if ($this->_context) {
      $this->assign('context', $this->_context);
    }

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | $this->_action & CRM_Core_Action::DELETE)) {
      $this->_campaignId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        CRM_Utils_System::setTitle(ts('Edit Campaign'));
      }
      else {
        CRM_Utils_System::setTitle(ts('Delete Campaign'));
      }
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));

    $this->assign('action', $this->_action);
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
    $defaults = [];

    // if we are editing
    if (isset($this->_campaignId)) {
      $params = ['id' => $this->_campaignId];

      CRM_Campaign_BAO_Campaign::retrieve($params, $defaults);
    }

    if (isset($defaults['start_date'])) {
      list($defaults['start_date'],
        $defaults['start_date_time']
      ) = CRM_Utils_Date::setDateDefaults($defaults['start_date'],
        'activityDateTime'
      );
    }
    else {
      list($defaults['start_date'],
        $defaults['start_date_time']
      ) = CRM_Utils_Date::setDateDefaults();
    }

    if (isset($defaults['end_date'])) {
      list($defaults['end_date'],
        $defaults['end_date_time']
      ) = CRM_Utils_Date::setDateDefaults($defaults['end_date'],
        'activityDateTime'
      );
    }

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    if (!$this->_campaignId) {
      return $defaults;
    }

    $dao = new CRM_Campaign_DAO_CampaignGroup();

    $campaignGroups = [];
    $dao->campaign_id = $this->_campaignId;
    $dao->find();

    while ($dao->fetch()) {
      $campaignGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
    }

    if (!empty($campaignGroups)) {
      $defaults['includeGroups'] = $campaignGroups['civicrm_group']['Include'];
    }
    return $defaults;
  }

  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {

      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');

    // add comaign title.
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    // add description
    $this->add('textarea', 'description', ts('Description'), $attributes['description']);

    // add campaign start date
    $this->addDateTime('start_date', ts('Start Date'), TRUE, ['formatType' => 'activityDateTime']);

    // add campaign end date
    $this->addDateTime('end_date', ts('End Date'), FALSE, ['formatType' => 'activityDateTime']);

    // add campaign type
    $campaignType = CRM_Campaign_PseudoConstant::campaignType();
    $this->add('select', 'campaign_type_id', ts('Campaign Type'), ['' => ts('- select -')] + $campaignType, TRUE);

    // add campaign status
    $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
    $this->addElement('select', 'status_id', ts('Campaign Status'), ['' => ts('- select -')] + $campaignStatus);

    // add External Identifire Element
    $this->add('text', 'external_identifier', ts('External Id'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'external_identifier'), FALSE
    );

    // add Campaign Parent Id

    $campaigns = CRM_Campaign_BAO_Campaign::getAllCampaign($this->_campaignId);

    if ($campaigns) {
      $this->addElement('select', 'parent_id', ts('Parent Id'),
        ['' => ts('- select Parent -')] + $campaigns
      );
    }

    //get the campaign groups.
    $groups = CRM_Core_PseudoConstant::group('Campaign');

    $inG = &$this->addElement('advmultiselect', 'includeGroups',
      ts('Include Group(s)') . ' ',
      $groups,
      ['size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      ]
    );
    $inG->setButtonAttributes('add', ['value' => ts('Add >>')]);
    $inG->setButtonAttributes('remove', ['value' => ts('<< Remove')]);

    // is this Campaign active
    $this->addElement('checkbox', 'is_active', ts('Is Active?'));

    if ($this->_context == 'dialog') {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
            'js' => ['onclick' => "cj('#campaign-dialog').dialog('close'); return false;"],
          ],
        ]);
    }
    else {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
          ['type' => 'next',
            'name' => ts('Save and New'),
            'subName' => 'new',
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
  }

  /**
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return None
   * @access public
   * @see valid_date
   */

  static function formRule($fields, $files, $errors) {
    $errors = [];

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Form submission of new/edit campaign is processed.
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $session = CRM_Core_Session::singleton();

    $groups = [];
    if (isset($this->_campaignId)) {
      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Campaign::del($this->_campaignId);
        CRM_Core_Session::setStatus(ts(' Campaign has been deleted.'));
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
        return;
      }
      $params['id'] = $this->_campaignId;
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }
    // format params
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');

    if (is_array($params['includeGroups'])) {
      foreach ($params['includeGroups'] as $key => $id) {
        if ($id) {
          $groups['include'][] = $id;
        }
      }
    }
    $params['groups'] = $groups;

    // delete previous includes/excludes, if campaign already existed
    $groupTableName = CRM_Contact_BAO_Group::getTableName();
    $dao = new CRM_Campaign_DAO_CampaignGroup();
    $dao->campaign_id = $this->_campaignId;
    $dao->entity_table = $groupTableName;
    $dao->find();
    while ($dao->fetch()) {
      $dao->delete();
    }


    $result = CRM_Campaign_BAO_Campaign::create($params);

    if ($result) {
      CRM_Core_Session::setStatus(ts('Campaign %1 has been saved.', [1 => $result->title]));
      $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
    }

    if ($this->_context == 'dialog') {
      $returnArray = ['returnSuccess' => TRUE];
      echo json_encode($returnArray);
      CRM_Utils_System::civiExit();
    }

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Campaign.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign/add', 'reset=1&action=add'));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
    }
  }
}



