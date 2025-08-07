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
 * Page for displaying list of events
 */
class CRM_Event_Page_ManageEvent extends CRM_Core_Page {

  public $_force;
  /**
   * @var string
   */
  public $_searchResult;
  /**
   * @var string
   */
  public $_event_type_id;
  public $_action;
  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_actionLinks = NULL;

  static $_links = NULL;

  protected $_pager = NULL;

  protected $_sortByCharacter;

  protected $_isTemplate = FALSE;

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $copyExtra = ts('Are you sure you want to make a copy of this Event?');
      $deleteExtra = ts('Are you sure you want to delete this Event?');

      self::$_actionLinks = [
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Event_BAO_Event' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Event'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Event_BAO_Event' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Event'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete Event'),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'reset=1&action=copy&id=%%id%%&key=%%key%%',
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
          'title' => ts('Copy Event'),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0, 'REQUEST'
    );

    // figure out whether we’re handling an event or an event template
    if ($id) {
      $this->_isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'is_template');
    }
    elseif ($action & CRM_Core_Action::ADD) {
      $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
    }

    if (!$this->_isTemplate && $id) {
      $breadCrumb = [['title' => ts('Manage Events'), 'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1')]];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }

    // what action to take ?
    if ($action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_Event_Form_ManageEvent_Delete',
        'Delete Event',
        $action
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $this->copy();
    }

    // finally browse the custom groups
    $this->browse();

    // parent run
    parent::run();
  }

  /**
   * browse all events
   *
   * @return void
   */
  function browse() {
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    $createdId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    if ($this->_sortByCharacter == 1 ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }

    $this->_force = $this->_searchResult = NULL;

    $this->search();


    $params = [];
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_searchResult = CRM_Utils_Request::retrieve('searchResult', 'Boolean', $this);
    $this->_event_type_id = CRM_Utils_Request::retrieve('event_type_id', 'String', $this);

    $params = [];
    $whereClause = $this->whereClause($params, TRUE, $this->_force);
    // because is_template != 1 would be to simple
    $whereClause .= ' AND (is_template = 0 OR is_template IS NULL)';

    $this->pager($whereClause, $params);

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    $name = get_class($this);
    $key = CRM_Core_Key::get($name);
    $this->assign('key', $key);

    // get all custom groups sorted by weight
    $manageEvent = [];

    $query = "
  SELECT *
    FROM civicrm_event
   WHERE $whereClause
ORDER BY start_date desc
   LIMIT $offset, $rowCount";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Event_DAO_Event');
    $permissions = CRM_Event_BAO_Event::checkPermission();
    $eventType = CRM_Event_PseudoConstant::eventType();

    while ($dao->fetch()) {
      if (in_array($dao->id, $permissions[CRM_Core_Permission::VIEW])) {
        $manageEvent[$dao->id] = [];
        CRM_Core_DAO::storeValues($dao, $manageEvent[$dao->id]);

        $manageEvent[$dao->id]['event_type'] = $eventType[$dao->event_type_id];

        // form all action links
        $action = array_sum(array_keys($this->links()));
        $counting = CRM_Event_BAO_Participant::statusEventSeats($dao->id);
        foreach ($counting as $k => $c) {
          $manageEvent[$dao->id][$k] = array_sum($c);
        }
        $manageEvent[$dao->id]['max_participants'] = $dao->max_participants;

        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        if (!in_array($dao->id, $permissions[CRM_Core_Permission::DELETE])) {
          $action -= CRM_Core_Action::DELETE;
        }
        if (!in_array($dao->id, $permissions[CRM_Core_Permission::EDIT])) {
          $action -= CRM_Core_Action::UPDATE;
        }

        $manageEvent[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
          $action,
          [
            'id' => $dao->id,
            'key' => $key
          ],
          ts('Operation'),
          TRUE
        );


        $manageEvent[$dao->id]['friend'] = CRM_Friend_BAO_Friend::getValues($params);
      }
    }
    $this->assign('rows', $manageEvent);


    $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1', 'label');
    $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0', 'label');
    $findParticipants['statusCounted'] = CRM_Utils_Array::implode(', ', array_values($statusTypes));
    $findParticipants['statusNotCounted'] = CRM_Utils_Array::implode(', ', array_values($statusTypesPending));
    $this->assign('findParticipants', $findParticipants);
  }

  /**
   * This function is to make a copy of a Event, including
   * all the fields in the event wizard
   *
   * @return void
   * @access public
   */
  function copy() {
    $key = CRM_Utils_Request::retrieve('key', 'String',
      CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
    );

    $name = get_class($this);
    if (!CRM_Core_Key::validate($key, $name)) {
      return CRM_Core_Error::statusBounce(ts('Sorry, we cannot process this request...'));
    }

    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE, 0, 'GET');

    $urlString = 'civicrm/event/manage';

    $copyEvent = CRM_Event_BAO_Event::copy($id);
    $urlParams = 'reset=1';
    // Redirect to Copied Event Configuration
    if ($copyEvent->id) {
      $urlString = 'civicrm/event/manage/eventInfo';
      $urlParams .= '&action=update&id=' . $copyEvent->id;
    }

    return CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));
  }

  function search() {
    if (isset($this->_action) &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $controller = new CRM_Core_Controller_Simple('CRM_Event_Form_SearchEvent', ts('Search Events'), CRM_Core_Action::ADD);
    $controller->setEmbedded(TRUE);
    $controller->setParent($this);
    $controller->process();
    $controller->run();
  }

  function whereClause(&$params, $sortBy, $force) {
    $values = [];
    $clauses = [];
    $title = $this->get('title');
    $createdId = $this->get('cid');

    if ($createdId) {
      $clauses[] = "(created_id = {$createdId})";
    }

    if ($title) {
      $clauses[] = "title LIKE %1";
      $params[1] = [trim($title), 'String', TRUE];
    }

    $value = $this->get('event_type_id');
    $val = [];
    if ($value) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          if ($v) {
            $val[$k] = $k;
          }
        }
        $type = CRM_Utils_Array::implode(',', $val);
      }
      else {
        $value = explode(',', $value);
        foreach($value as $v) {
            $val[$v] = $v;
        }
        $type = CRM_Utils_Array::implode(',', $val);
      }
      $clauses[] = "event_type_id IN ({$type})";
    }
    return !empty($clauses) ? CRM_Utils_Array::implode(' AND ', $clauses) : '(1)';
  }

  function pager($whereClause, $whereParams) {


    $params['status'] = ts('Event %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count(id)
  FROM civicrm_event
 WHERE $whereClause";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);

    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }
}

