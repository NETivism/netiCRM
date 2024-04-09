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

require_once 'CRM/Core/Form.php';

/**
 * Administer Personal Campaign Pages - Search form
 */
class CRM_Contribute_Form_PCP_PCP extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      //check permission for action.
      if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
        return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
      }

      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $this->_id, 'title');
      $this->assign('title', $this->_title);
      parent::preProcess();
    }

    if (!$this->_action) {
      $this->_action = CRM_Utils_Array::value('action', $_GET);
      $this->_id = CRM_Utils_Array::value('id', $_GET);
    }
    else {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }

    $session = CRM_Core_Session::singleton();
    $context = $session->popUserContext();
    $userID = $session->get('userID');

    //do not allow destructive actions without permissions
    if (!$userID) {
      CRM_Utils_System::permissionDenied();
    }
    $isManager = $isOwner = FALSE;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $isManager = TRUE;
    }
    if ($userID && $this->_id && (CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $this->_id, 'contact_id') == $userID)) {
      $isOwner = TRUE;
    }
    if (($isOwner || $isManager) && $this->_id) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $this->_id, 'title');
      $statusId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $this->_id, 'status_id');
      $statusApprovedId = intval(CRM_Core_OptionGroup::getValue('pcp_status', 'Approved', 'name'));
      if ($isManager && ($this->_action == CRM_Core_Action::DELETE || $this->_action == 'delete')) {
        CRM_Contribute_BAO_PCP::deleteById($this->_id);
        CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been deleted.", array(1 => $this->_title)));
      }
      elseif ($isManager || ($isOwner && $statusId == $statusApprovedId)) {
        switch ($this->_action) {
          case CRM_Core_Action::DISABLE:
          case 'disable':
            CRM_Contribute_BAO_PCP::setDisable($this->_id, '0');
            CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been disabled.", array(1 => $this->_title)));
            break;

          case CRM_Core_Action::ENABLE:
          case 'enable':
            CRM_Contribute_BAO_PCP::setDisable($this->_id, '1');
            CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been enabled.", array(1 => $this->_title)));
            break;
        }
      }
      if ($context) {
        CRM_Utils_System::redirect($context);
      }
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = array();
    if (!empty($_REQUEST['contribution_page_id'])) {
      $defaults['contribution_page_id'] = $_REQUEST['contribution_page_id'];
    }
    if (!empty($_REQUEST['status_id'])) {
      $defaults['status_id'] = $_REQUEST['status_id'];
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array('type' => 'next',
            'name' => ts('Delete Campaign'),
            'isDefault' => TRUE,
          ),
          array('type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      require_once 'CRM/Contribute/PseudoConstant.php';
      $status = array_merge(
        array('' => ts('- select -')),
        CRM_Contribute_PseudoConstant::pcpstatus()
      );
      $contribution_page = array_merge(
        array(ts('- select -')),
        CRM_Contribute_PseudoConstant::contributionPage()
      );
      $dao = CRM_Core_DAO::executeQuery("SELECT p.contact_id, c.sort_name, c.external_identifier FROM civicrm_pcp p INNER JOIN civicrm_contact c ON p.contact_id = c.id GROUP BY p.contact_id");
      $contacts = array(ts('- select -'));
      while($dao->fetch()) {
        $exid = '';
        if ($dao->external_identifier) {
          $exid = ' - '.$dao->external_identifier;
        }
        $contacts[$dao->contact_id] = $dao->sort_name." ($dao->contact_id{$exid})";
      }

      $this->addSelect('status_id', ts('Status'), $status);
      $this->addSelect('contribution_page_id', ts('Belonging Main Contribution Page'), $contribution_page);
      $this->addSelect('contact_id', ts('Created by'), $contacts);
      $this->add('text', 'title', ts('Page Title'));
      $this->addButtons(array(
          array(
            'type' => 'refresh',
            'name' => ts('Search'),
            'spacing' => ' ',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'refresh',
            'name' => ts('Reset'),
            'spacing' => ' ',
            'isDefault' => FALSE,
          ),
        )
      );
      parent::buildQuickForm();
    }
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
  static function formRule($fields, $files, $form) {}

  /**
   * Process the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      require_once 'CRM/Contribute/BAO/PCP.php';
      CRM_Contribute_BAO_PCP::deleteById($this->_id);
      CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been deleted.", array(1 => $this->_title)));
    }
    else {
      $buttonName = $this->_submitValues['_qf_PCP_refresh'];
      if ($buttonName == ts("Search")) {
        $params = $this->controller->exportValues($this->_name);
        $parent = $this->controller->getParent();

        if (!empty($params) && is_object($parent)) {
          // clear result
          $parent->set("pcpSummary", array());
          $fields = array('status_id', 'contribution_page_id', 'contact_id', 'title');
          foreach ($fields as $field) {
            if (isset($params[$field]) && !CRM_Utils_System::isNull($params[$field])) {
              $parent->set($field, $params[$field]);
            }
            else {
              $parent->set($field, NULL);
            }
          }
        }
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/pcp", "reset=1"));
      }
    }
  }
}

