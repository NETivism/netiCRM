<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */



/**
 * Main page for viewing Recurring Contributions.
 *
 */
class CRM_Contribute_Page_ContributionRecur extends CRM_Core_Page {

  public $_id;
  public $_action;
  /**
   * @var array<string, mixed>
   */
  public $_values;
  static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * View details of a recurring contribution
   *
   * @return void
   * @access public
   */
  function view() {


    $status = CRM_Contribute_Pseudoconstant::contributionStatus();

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $this->_id;
    if ($recur->find(TRUE)) {
      $config = CRM_Core_Config::singleton();
      $values = array();
      CRM_Core_DAO::storeValues($recur, $values);
      // if there is a payment processor ID, get the name of the payment processor
      if (!empty($values['payment_processor_id'])) {
        $values['payment_processor'] = CRM_Core_DAO::getFieldValue(
          'CRM_Core_DAO_PaymentProcessor',
          $values['payment_processor_id'],
          'name'
        );
      }
      $values['contribution_status'] = $status[$values['contribution_status_id']];
      $this->assign('recur', $values);

      // ach
      $ach = CRM_Contribute_BAO_TaiwanACH::getValue($recur->id);
      if (!empty($ach)) {
        $stampStatus = CRM_Contribute_PseudoConstant::taiwanACHStampVerification();
        $ach['stamp_verification_label'] = $stampStatus[$ach['stamp_verification']];
        if ($ach['bank_code']) {
          $ach['bank_code'] = CRM_Contribute_PseudoConstant::taiwanACH($ach['bank_code']);
        }
        if ($ach['stamp_verification'] == 2) {
          $ach['stamp_verification_reason'] = $ach['data']['verification_failed_reason'];
        }
        $this->assign('ach', $ach);
      }

      // #29618, donate url, for security reason, only available for 7 days
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->contribution_recur_id = $recur->id;
      $contribution->contact_id = $recur->contact_id;
      if (!empty($config->recurringCopySetting) && $config->recurringCopySetting == 'latest') {
        $contribution->orderBy("created_date DESC");
        $contribution->find(TRUE);
      }
      else {
        $contribution->orderBy("id ASC");
        $contribution->find(TRUE);
      }
      if ($contribution->id && $contribution->contribution_page_id) {
        $pageIsActive = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contribution->contribution_page_id, 'is_active');
        $isIndividual = CRM_Contact_BAO_Contact::getContactType($recur->contact_id);
        if ($pageIsActive && $isIndividual == 'Individual') {
          $cid = $recur->contact_id;
          $oid = $contribution->id;
          $pageId = $contribution->contribution_page_id;
          $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($recur->contact_id);
          $donateAgain = CRM_Utils_System::url('civicrm/contribute/transact', "reset=1&id=$pageId&cid=$cid&oid=$oid&cs=$cs", TRUE);
          $this->assign('donateAgain', $donateAgain);
          $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
          if ($providersCount) {
            $phones = CRM_Core_BAO_Phone::allPhones($cid, FALSE, ts('Mobile'));
            if (count($phones)) {
              $this->assign("sendSMS", TRUE);
            }
          }
        }
      }

      // log
      $noteDetail = CRM_Core_BAO_Note::getNoteDetail($this->_id, 'civicrm_contribution_recur');
      $notes = array();
      foreach ($noteDetail as $note) {
        if (!empty($note['modified_date'])) {
          $notes[$note['modified_date']] = $note;
        }
      }

      $logDAO = new CRM_Core_DAO_Log();
      $logDAO->entity_table = 'civicrm_contribution_recur';
      $logDAO->entity_id = $recur->id;
      $logDAO->orderBy('id desc');
      $logDAO->find();

      $statuses = CRM_Contribute_PseudoConstant::contributionStatus();

      while ($logDAO->fetch()) {
        $displayName = '';
        if (!empty($logDAO->modified_id)) {
          list($displayName, $ignore) = CRM_Contact_Page_View::getContactDetails($logDAO->modified_id);
        }
        $data = unserialize($logDAO->data);
        $log = array(
          'modified_id' => $logDAO->modified_id,
          'modified_date' => $logDAO->modified_date,
          'modified_name' => $displayName,
        );

        if (is_array($data) && !empty($data['before']) && !empty($data['after'])) {
          $before = $data['before'];
          $after = $data['after'];
          if ($before['amount'] == $after['amount']) {
            $log['amount'] = $after['amount'];
          }
          else {
            $log['before_amount'] = $before['amount'];
            $log['after_amount'] = $after['amount'];
          }

          if ($before['contribution_status_id'] == $after['contribution_status_id']) {
            $log['contribution_status'] = $statuses[$after['contribution_status_id']];
          }
          else {
            $log['before_contribution_status'] = $statuses[$before['contribution_status_id']];
            $log['after_contribution_status'] = $statuses[$after['contribution_status_id']];
          }
          $diff = self::_diff_contribute_recur_log($data);
          $log['other_diff'] = CRM_Utils_Array::implode('<br/>', $diff);
        }

        if ($notes[$log['modified_date']]) {
          $note = $notes[$log['modified_date']];
          $log['note_subject'] = $note['subject'];
          $log['note'] = $note['note'];
        }

        $logs[] = $log;
      }
      $logDAO->free();
      $this->assign('logs', $logs);

      // Recurring Contributions
      $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_Search', ts('Contributions'), CRM_Core_Action::BROWSE);
      $controller->setEmbedded(TRUE);
      $controller->reset();
      $controller->set('cid', $recur->contact_id);
      $controller->set('id', NULL);
      $controller->set('recur', $recur->id);
      $controller->set('force', 1);
      $controller->set('test', $recur->is_test);
      $controller->set('context', 'recur');
      $controller->set('compContext', 'contributionrecur');
      $controller->process();
      $controller->run();

      // get newest contribution ID and payment processor.
      $sql = "SELECT id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY id DESC LIMIT 1";
      $params = array( 1 => array($recur->id, 'Positive'));
      $contributionId = CRM_Core_DAO::singleValueQuery($sql, $params);
      $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);

      if ((method_exists($paymentClass, 'doRecurTransact') || method_exists($paymentClass, 'doRecurUpdate')) && CRM_Core_Permission::check('edit contributions')) {
        $controllerTransact = new CRM_Core_Controller_Simple('CRM_Contribute_Form_MakingTransaction', NULL, CRM_Core_Action::NONE);
        $controllerTransact->setEmbedded(TRUE);
        $controllerTransact->set('recurId', $recur->id);
        $controllerTransact->set('contributionId', $contributionId);
        $controllerTransact->set('contactId', $recur->contact_id);
        $controllerTransact->process();
        $controllerTransact->run();
      }

      if (method_exists($paymentClass, 'getRecordDetail')) {
        $recordDetail = $paymentClass::getRecordDetail($contributionId);
        $this->assign('record_detail', $recordDetail);
      }

      // Get payment processor
      if (!empty($paymentClass) && !empty($paymentClass::$_hideFields)) {
        $this->assign('hide_fields', $paymentClass::$_hideFields);
      }

      // show 'edit' button depends on permission.
      if (CRM_Core_Permission::check('edit contributions')) {
        $this->assign('is_editable', true);
      }

    }
  }

  /**
   * This function is called when action is update
   *
   * return null
   * @access public
   */
  function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionRecur', 'Create Contribution', $this->_action);
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view',
      'reset=1&selectedChild=contribute&cid=' . $this->_contactId
    );
    $session->pushUserContext($url);

    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);
    $controller->process();

    return $controller->run();
  }

  function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'view');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);
    $this->assign('contributionRecurId', $this->_id);

    // check logged in url permission

    CRM_Contact_Page_View::checkUserPermission($this);

    // set page title
    CRM_Contact_Page_View::setTitle($this->_contactId);
    list($displayName, $ignore) = CRM_Contact_Page_View::getContactDetails($this->_contactId);
    $this->assign('displayName', $displayName);

    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit contributions')) {
      // demote to view since user does not have edit contrib rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
    
    //retrieve custom field information
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("ContributionRecur", $this, $this->_id, 0);
    $this->_values['custom_data_view'] = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & CRM_Core_Action::UPDATE) {
      if (!CRM_Core_Permission::check('edit contributions')) {
         return CRM_Core_Error::statusBounce(ts("You do not have permission to access this page."));
      }
      $this->edit();
    }

    return parent::run();
  }

  static function _diff_contribute_recur_log($data) {
    $allFields = CRM_Contribute_DAO_ContributionRecur::$_fields;
    if (is_string($data)) {
      $log = unserialize($data);
    }
    else {
      $log = $data;
    }
    $before = $log['before'];
    $after = $log['after'];
    foreach ($after as $key => &$value) {
      if (substr($key, -5, 5) == '_date' && !empty($value) && $value != 'null') {
        $after[$key] = date('Y-m-d H:i:s', strtotime($value));
      }
      if ($value == 'null') {
        $value = NULL;
      }
    }
    if (!empty($before) && !empty($after)) {
      $diff = array_diff_assoc($after, $before);
      unset($diff['contribution_status_id']);
      unset($diff['amount']);
      unset($diff['create_date']);
      unset($diff['start_date']);
      unset($diff['modified_date']);
      foreach ($diff as $field => $value) {
        if ($before[$field]) {
          // is hash:
          if (preg_match('/^[a-f0-9]{32}$/', $before[$field])) {
            $before[$field] = null;
          }
          if (empty($value)) {
            $arrayReturnHtml[] = ts('Delete'). $allFields[$field]['title'] . ': <span class="disabled">'.$before[$field].'</span>';
          }
          else {
            $arrayReturnHtml[] = $allFields[$field]['title'] . ': <span class="disabled">'.$before[$field].'</span>→'.$value;
          }
        }

        else {
          $arrayReturnHtml[] = ts('Add'). $allFields[$field]['title'] . ': '.$value;
        }
      }
    }
    return $arrayReturnHtml;
  }
}

