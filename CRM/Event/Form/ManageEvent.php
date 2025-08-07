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
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_ManageEvent extends CRM_Core_Form {

  public $_doneUrl;
  /**
   * the id of the event we are proceessing
   *
   * @var int
   * @protected
   */
  public $_id;

  /**
   * is this the first page?
   *
   * @var boolean
   * @access protected
   */
  protected $_first = FALSE;

  /**
   * are we in single form mode or wizard mode?
   *
   * @var boolean
   * @access protected
   */
  protected $_single;

  protected $_action;

  /**
   * are we actually managing an event template?
   * @var boolean
   */
  protected $_isTemplate = FALSE;

  /**
   * pre-populate fields based on this template event_id
   * @var integer
   */
  protected $_templateId;

  protected $_cancelURL = NULL;

  protected $_eventInfo = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $config = CRM_Core_Config::singleton();
    if (in_array("CiviEvent", $config->enableComponents)) {
      $this->assign('CiviEvent', TRUE);
    }

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add', 'REQUEST');

    $this->assign('action', $this->_action);

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($this->_id) {
      $this->assign('eventId', $this->_id);
      $this->add('hidden', 'id', $this->_id);
      $this->_single = TRUE;

      $params = ['id' => $this->_id];

      CRM_Event_BAO_Event::retrieve($params, $eventInfo);
      $this->_eventInfo = $eventInfo;

      // its an update mode, do a permission check

      if (!CRM_Event_BAO_Event::checkPermission($this->_id, CRM_Core_Permission::EDIT)) {
         return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
      }

      $participantListingID = CRM_Utils_Array::value('participant_listing_id', $eventInfo);
      //CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event', $this->_id, 'participant_listing_id' );
      if ($participantListingID) {
        $participantListingURL = CRM_Utils_System::url('civicrm/event/participant',
          "reset=1&id={$this->_id}",
          TRUE, NULL, TRUE, TRUE
        );
        $this->assign('participantListingURL', $participantListingURL);
      }

      $this->assign('isOnlineRegistration', CRM_Utils_Array::value('is_online_registration', $eventInfo));

      $this->assign('id', $this->_id);
    }

    // figure out whether we’re handling an event or an event template
    if ($this->_id) {
      $this->_isTemplate = CRM_Utils_Array::value('is_template', $eventInfo);
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
    }

    $this->assign('isTemplate', $this->_isTemplate);

    if ($this->_id) {
      if ($this->_isTemplate) {
        $title = CRM_Utils_Array::value('template_title', $eventInfo);
        CRM_Utils_System::setTitle($title.' - '.ts('Edit Event Template'));
      }
      else {
        $title = CRM_Utils_Array::value('title', $eventInfo);
        CRM_Utils_System::setTitle("$title - ".ts('Configure Event'));
      }
      $this->assign('title', $title);
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      if ($this->_isTemplate) {
        $title = ts('New Event Template');
        CRM_Utils_System::setTitle($title);
      }
      else {
        $title = ts('New Event');
        CRM_Utils_System::setTitle($title);
      }
      $this->assign('title', $title);
    }


    $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1', 'label');
    $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0', 'label');
    $findParticipants['statusCounted'] = CRM_Utils_Array::implode(', ', array_values($statusTypes));
    $findParticipants['statusNotCounted'] = CRM_Utils_Array::implode(', ', array_values($statusTypesPending));
    $this->assign('findParticipants', $findParticipants);

    $this->_templateId = (int) CRM_Utils_Request::retrieve('template_id', 'Integer', $this);

    // also set up tabs

    CRM_Event_Form_ManageEvent_TabHeader::build($this);

    // Set Done button URL and breadcrumb. Templates go back to Manage Templates,
    // otherwise go to Manage Event for new event or ManageEventEdit if event if exists.
    $breadCrumb = [];
    if (!$this->_isTemplate) {
      if ($this->_id) {
        $this->_doneUrl = CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          "action=update&reset=1&id={$this->_id}"
        );
        if (!empty($eventInfo['event_title'])) {
          $breadCrumb = [
            [
              'title' => $eventInfo['event_title'],
              'url' => CRM_Utils_System::url('civicrm/event/search', 'reset=1&force=1&event=' . $this->_id),
            ],
          ];
        }
      }
      else {
        $this->_doneUrl = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
        $breadCrumb = [['title' => ts('Manage Events'),
            'url' => $this->_doneUrl,
          ]];
      }
    }
    else {
      $this->_doneUrl = CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1');
      $breadCrumb = [['title' => ts('Manage Event Templates'),
          'url' => $this->_doneUrl,
        ]];
    }
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];

      CRM_Event_BAO_Event::retrieve($params, $defaults);
    }
    elseif ($this->_templateId) {
      $params = ['id' => $this->_templateId];

      CRM_Event_BAO_Event::retrieve($params, $defaults);
      $defaults['is_template'] = $this->_isTemplate;
      $defaults['template_id'] = $defaults['id'];
      unset($defaults['id']);
      unset($defaults['start_date']);
      unset($defaults['end_date']);
      unset($defaults['registration_start_date']);
      unset($defaults['registration_end_date']);
    }
    else {
      $defaults['is_active'] = 1;
      $defaults['style'] = 'Inline';
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $className = CRM_Utils_System::getClassName($this);
    $session = &CRM_Core_Session::singleton();

    $this->_cancelURL = CRM_Utils_Array::value('cancelURL', $_POST);

    if (!$this->_cancelURL) {
      if ($this->_isTemplate) {
        $this->_cancelURL = CRM_Utils_System::url('civicrm/admin/eventTemplate',
          'reset=1'
        );
      }
      else {
        $this->_cancelURL = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
      }
    }

    if ($this->_cancelURL) {
      $this->addElement('hidden', 'cancelURL', $this->_cancelURL);
    }

    $buttons = [];
    if ($this->_single) {
      // make this form an upload since we dont know if the custom data injected dynamically
      // is of type file etc $uploadNames = $this->get( 'uploadNames' );
      $buttons = [
        ['type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        ['type' => 'upload',
          'name' => ts('Save and Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'done',
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ];
      $this->addButtons($buttons);
    }
    else {
      $buttons = [];
      if (!$this->_first) {
        $buttons[] = ['type' => 'back',
          'name' => ts('<< Previous'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        ];
      }
      $js = ['data' => 'click-once'];
      $buttons[] = ['type' => 'upload',
        'name' => ts('Continue >>'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
        'js' => $js,
      ];
      $buttons[] = ['type' => 'cancel',
        'name' => ts('Cancel'),
      ];

      $this->addButtons($buttons);
    }
    $session->replaceUserContext($this->_cancelURL);
    $this->add('hidden', 'is_template', $this->_isTemplate);
  }

  function endPostProcess() {
    // make submit buttons keep the current working tab opened.
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);
      if ($className == 'EventInfo') {
        $subPage = 'eventInfo';
      }
      elseif ($className == 'Event') {
        $subPage = 'friend';
      }
      else {
        $subPage = strtolower($className);
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.", [1 => ($subPage == 'friend') ? 'Friend' : $className]));

      if ($this->controller->getButtonName('submit') == "_qf_{$className}_upload_done") {
        if ($this->_isTemplate) {
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1'));
        }
        else {
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/event/manage', 'reset=1'));
        }
      }
      else {
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url("civicrm/event/manage/{$subPage}", "action=update&reset=1&id={$this->_id}"));
      }

      // we need to call the hook manually here since we redirect and never
      // go back to CRM/Core/Form.php
      CRM_Utils_Hook::postProcess(get_class($this), $this);
    }
  }

  function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/Event/Form/ManageEvent/{$this->_id}/{$this->_name}.tpl";
      $template = &CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
    /*
        if ( $this->controller->getPrint( ) == CRM_Core_Smarty::PRINT_NOFORM ||
             $this->getVar( '_id' ) <= 0 ||
             ( $this->_action & CRM_Core_Action::DELETE ) ) {
            return parent::getTemplateFileName( );
        } else {
            return 'CRM/Event/Form/ManageEvent/Tab.tpl';
        }
    */
  }
}

