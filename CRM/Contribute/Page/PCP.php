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

require_once 'CRM/Contribute/BAO/PCP.php';
require_once 'CRM/Core/Page/Basic.php';

/**
 * Page for displaying list of contribution types
 */
class CRM_Contribute_Page_PCP extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Contribute_BAO_PCP';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Campaign Page ?');

      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contribute/pcp/info',
          'qs' => 'action=update&reset=1&id=%%id%%&context=standalone&key=%%qfKey%%',
          'title' => ts('Edit Personal Campaign Page'),
          'fe' => TRUE,
        ),
        CRM_Core_Action::RENEW => array(
          'name' => ts('Approve'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=renew&id=%%id%%',
          'title' => ts('Approve Personal Campaign Page'),
        ),
        CRM_Core_Action::REVERT => array(
          'name' => ts('Reject'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=revert&id=%%id%%',
          'title' => ts('Reject Personal Campaign Page'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete Personal Campaign Page'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @param
   *
   * @return void
   * @access public
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE,
      'browse'
    );
    if ($action & CRM_Core_Action::REVERT) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
      CRM_Contribute_BAO_PCP::setIsActive($id, 0);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
    }
    elseif ($action & CRM_Core_Action::RENEW) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
      CRM_Contribute_BAO_PCP::setIsActive($id, 1);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_PCP_PCP',
        'Personal Campaign Page',
        CRM_Core_Action::DELETE
      );
      //$this->setContext( $id, $action );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }

    // finally browse
    $this->browse();

    // parent run
    parent::run();
  }

  /**
   * Browse all custom data groups.
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    $pcpSummary = $params = array();

    $pcpSummary = $this->get("pcpSummary");
    if (empty($pcpSummary)) {
      $status = CRM_Contribute_PseudoConstant::pcpstatus();
      $contribution_page = CRM_Contribute_PseudoConstant::contributionPage();
      $whereClause = array();

      if ($status_id = CRM_Utils_Request::retrieve('status_id', 'Positive', $this)) {
        $whereClause[] = 'cp.status_id = %1';
        $params['1'] = array($status_id, 'Integer');
      }

      if ($contribution_page_id = CRM_Utils_Request::retrieve('contribution_page_id', 'Positive', $this)) {
        $whereClause[] = 'cp.contribution_page_id = %2';
        $params['2'] = array($contribution_page_id, 'Integer');
      }

      if ($contact_id = CRM_Utils_Request::retrieve('contact_id', 'Positive', $this)) {
        $whereClause[] = 'cp.contact_id = %3';
        $params['3'] = array($contact_id, 'Integer');
      }

      if ($title = $this->get("title")) {
        $whereClause[] = 'cp.title LIKE %4';
        $params['4'] = array("%".$title."%", 'String');
      }

      if (!empty($whereClause)) {
        $whereClause = implode(" AND ", $whereClause);
      }
      else {
        $whereClause = " 1 ";
      }

      $query = "
          SELECT cp.id as id, contact_id , status_id, cp.title as title, contribution_page_id, start_date, end_date, cp.is_active as active
          FROM civicrm_pcp cp INNER JOIN civicrm_contribution_page cpp ON cpp.id = cp.contribution_page_id
          WHERE " . $whereClause . " ORDER BY status_id ASC";

      $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contribute_DAO_PCP');

      $allowToDelete = CRM_Core_Permission::check('delete in CiviContribute');
      $approvedId = CRM_Core_OptionGroup::getValue('pcp_status', 'Approved', 'name');
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      while ($dao->fetch()) {

        $pcpSummary[$dao->id] = array();
        $action = array_sum(array_keys($this->links()));

        CRM_Core_DAO::storeValues($dao, $pcpSummary[$dao->id]);

        require_once 'CRM/Contact/BAO/Contact.php';
        $contact = CRM_Contact_BAO_Contact::getDisplayAndImage($dao->contact_id);

        $class = '';

        if ($dao->active != 1 || $dao->status_id == 3) {
          $class = 'disabled';
        }

        switch ($dao->status_id) {
          case 2:
            $action -= CRM_Core_Action::RENEW;
            break;

          case 3:
            $action -= CRM_Core_Action::REVERT;
            break;
        }

        if (!$allowToDelete) {
          $action -= CRM_Core_Action::DELETE;
        }

        $pcpSummary[$dao->id]['id'] = $dao->id;
        $pcpSummary[$dao->id]['start_date'] = $dao->start_date;
        $pcpSummary[$dao->id]['end_date'] = $dao->end_date;
        $pcpSummary[$dao->id]['supporter'] = $contact['0'];
        $pcpSummary[$dao->id]['supporter_id'] = $dao->contact_id;
        $pcpSummary[$dao->id]['status_id'] = $status[$dao->status_id];
        $pcpSummary[$dao->id]['contribution_page_id'] = $dao->contribution_page_id;
        $pcpSummary[$dao->id]['contribution_page_title'] = $contribution_page[$dao->contribution_page_id];
        $pcpSummary[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
          array('id' => $dao->id, 'qfKey' => $qfKey)
        );
        $pcpSummary[$dao->id]['class'] = $class;
      }
      $this->set("pcpSummary", $pcpSummary);
    }

    $this->search();
    if ($pcpSummary) {
      $this->assign('rows', $pcpSummary);
    }
    // Let template know if user has run a search or not
    if ($this->get('whereClause')) {
      $this->assign('isSearch', 1);
    }
    else {
      $this->assign('isSearch', 0);
    }
  }

  function search() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Contribute_Form_PCP_PCP', ts('Search Campaign Pages'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Contribute_Form_PCP_PCP';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return ts('Personal Campaign Page');
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/pcp';
  }

}

