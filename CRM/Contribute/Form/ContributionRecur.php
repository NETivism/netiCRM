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

require_once 'CRM/Core/Form.php';

/**
 * This class generates form components generic to recurring contributions
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Core_Form {

  /**
   * The recurring contribution id, used when editing the recurring contribution
   *
   * @var int
   */
  protected $_id;
  protected $_online;

  /**
   * the id of the contact associated with this recurring contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  function preProcess() {
    $this->_id = $this->get('id');
    $this->_contactID = $this->get('cid');

    $query = "SELECT c.payment_processor_id FROM civicrm_contribution c WHERE c.contribution_recur_id = %1 AND c.payment_processor_id IS NOT NULL && c.payment_processor_id > 0";
    $sqlParams = array(1 => array($this->_id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    if($dao->N){
      $this->_online = TRUE;
    }
    else{
      $this->_online = FALSE;
    }
    $dao->free();

    $hideFields = NULL;
    $processorId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'processor_id');
    if (!empty($processorId)) {
      $processorName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $processorId, 'payment_processor_type');
    }
    $this->assign('payment_type', $processorName);
    $this->set('payment_type', $processorName);
    $isTest = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'is_test');
    if (!empty($processorId)) {
      $test = $isTest ? 'test':'live';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($processorId, $test);
      $payment = &CRM_Core_Payment::singleton($test, $paymentProcessor);
      $paymentClass = get_class($payment);
    }
    if (empty($paymentClass)) {
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_id, 'id', 'contribution_recur_id');
      if (!empty($contributionId)) {
        $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);
        // Get payment processor
      }
    }
    if (!empty($paymentClass)) {
      if (!empty($paymentClass::$_hideFields)) {
        $hideFields = $paymentClass::$_hideFields;
      }
    }
    $this->assign('hide_fields', $hideFields);

    // custom data need entityID for template
    if ($this->_id) {
      $this->assign('entityID', $this->_id);
    }

    // when custom data is included in this page
    if ($_GET['type'] == 'ContributionRecur' || CRM_Utils_Array::value("hidden_custom", $_POST)) {
      $this->set('type', 'ContributionRecur');
      $this->set('entityId', $this->_id);
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
    }
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
    $defaults = array();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (isset($this->_id)) {
        $params['id'] = $this->_id;
        CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionRecur', $params, $defaults);
      }

      if (CRM_Utils_Array::value('create_date', $defaults)) {
        list($defaults['create_date'],
          $defaults['create_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['create_date'], 'activityDateTime');
      }

      if (CRM_Utils_Array::value('start_date', $defaults)) {
        list($defaults['start_date'],
          $defaults['start_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['start_date'], 'activityDateTime');
      }

      if (CRM_Utils_Array::value('modified_date', $defaults)) {
        list($defaults['modified_date'],
          $defaults['modified_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['modified_date'], 'activityDateTime');
      }

      if (CRM_Utils_Array::value('cancel_date', $defaults)) {
        list($defaults['cancel_date'],
          $defaults['cancel_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['cancel_date'], 'activityDateTime');
      }

      if (CRM_Utils_Array::value('end_date', $defaults)) {
        list($defaults['end_date'],
          $defaults['end_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['end_date'], 'activityDateTime');
      }

      $customFieldDefaults = CRM_Custom_Form_CustomData::setDefaultValues($this);
      $defaults += $customFieldDefaults;

    }
    return $defaults;
  }

  /**
   * Function to actually build the components of the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($_GET['type'] == 'ContributionRecur') {
      $this->assign('cftype', 'ContributionRecur');
      $form = CRM_Custom_Form_CustomData::buildQuickForm($this);
      return $form;
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'ContributionRecur');

    // define the fields
    $field = array(
      'id' => ts('Recurring Contribution ID'),
      'amount' => ts('Amount'),
      'currency' => ts('Currency'),
      'frequency_interval' => ts('Frequency Interval'),
      'installments' => ts('Installments'),
      'frequency_unit' => ts('Frequency Unit'),
      'create_date' => ts('Create date'),
      'start_date' => ts('Start date'),
      'end_date' => ts('End date'),
      'modified_date' => ts('Modified Date'),
      'cancel_date' => ts('Cancel Date'),
      'processor_id' => ts('Payment Processor'),
      'external_id' => ts('External Payment ID'),
      'is_test' => ts('Is Test'),
      'cycle_day' => ts('Cycle Day'),
      'next_sched_contribution' => ts('Next Sched Contribution'),
      'auto_renew' => ts('Auto Renew'),
      'contribution_status_id' => ts('Contribution Status Id'),
      'trxn_id' => ts('Transaction ID'),
    );

    // Get payment processor or Payment Processor DAO to get $activeFields
    $activeFields = NULL;
    $processorId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'processor_id');
    $isTest = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'is_test');
    if (!empty($processorId)) {
      $test = $isTest ? 'test':'live';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($processorId, $test);
      $payment = &CRM_Core_Payment::singleton($test, $paymentProcessor);
      $paymentClass = get_class($payment);
    }
    if (empty($paymentClass)) {
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_id, 'id', 'contribution_recur_id');
      if (!empty($contributionId)) {
        $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);
        // Get payment processor
      }
    }
    if (!empty($paymentClass)) {
      if (!empty($paymentClass::$_editableFields)) {
        $activeFields = $paymentClass::$_editableFields;
      }
      else if(method_exists($paymentClass, 'getEditableFields')) {
        $activeFields = $paymentClass::getEditableFields($paymentProcessor, $this);
      }
    }

    foreach ($field as $name => $label) {
      if (substr($name, -5) == '_date') {
        $this->addDateTime($name, $label, FALSE, array('formatType' => 'activityDateTime'));
      }
      else if( $name == 'contribution_status_id') {
        $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
        $statusId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'contribution_status_id');
        $ele = $this->add('select', 'contribution_status_id', $label, $statuses, FALSE, array(
          'data-origin-status' => $statusId,
        ));
      }
      else if (in_array($name, array('installments', 'cycle_day', 'amount'))) {
        if ($name == 'cycle_day') {
          $attr = array('max' => 28, 'min' => 1);
        }
        else {
          $attr = array('min' => 0);
        }
        if ($name == 'amount') {
          $amount = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_id, 'amount');
          $attr['data-origin-amount'] = number_format($amount);
        }
        $ele = $this->add('number', $name, $label, $attr);
      }
      else {
        $ele = $this->add('text', $name, $label, array('size' => 20));
      }

      if (empty($activeFields) || !in_array($name, $activeFields) ) {
        if (substr($name, -5) == '_date') {
          $ele = $this->getElement($name);
          $ele->freeze();
          $ele = $this->getElement($name.'_time');
          $ele->freeze();
        }
        else {
          $ele->freeze();
        }
      }
    }

    if (in_array('note_title', $activeFields)) {
      $this->add('text', 'note_title', ts('Note Title'), array(
        'readonly' => 'readonly',
        'size' => 60,
      ));
    }
    if (in_array('note_body', $activeFields)) {
      $this->add('textarea', 'note_body', ts('Note Text'), array(
        'rows' => "4",
        'cols' => "60",
        'placeholder' => ts("Enter text here")."...",
      ));
    }

    // define the buttons
    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    //retrieve custom field information
    require_once 'CRM/Core/BAO/CustomGroup.php';
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("ContributionRecur", $this, $this->_id, 0);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);

    if (!empty($paymentClass) && method_exists($paymentClass, 'postBuildForm')) {
      $paymentClass::postBuildForm($this);
    }
  }

  /**
   * This function is called after the user submits the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    // if this is an update of an existing recurring contribution, pass the ID
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $params['create_date'] = CRM_Utils_Date::processDate($params['create_date'],
      $params['create_date_time'],
      TRUE
    );
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'],
      $params['start_date_time'],
      TRUE
    );
    $params['modified_date'] = CRM_Utils_Date::processDate($params['modified_date'],
      $params['modified_date_time'],
      TRUE
    );
    $params['cancel_date'] = CRM_Utils_Date::processDate($params['cancel_date'],
      $params['cancel_date_time'],
      TRUE
    );
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'],
      $params['end_date_time'],
      TRUE
    );

    // refs #17486. Date format should be YmdHis.
    foreach (array('create_date', 'start_date', 'modified_date', 'cancel_date', 'end_date', 'next_sched_contribution', 'failure_retry_date', 'last_execute_date') as $idx) {
      if(preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $params[$idx])){
        $params[$idx] = preg_replace('/-| |:/', '', $params[$idx]);
      }
    }

    $recur = array();
    $ids = array('id' => $this->_id);
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionRecur', $ids, $recur);
    $isUpdate = FALSE;
    if (!empty($recur) && !empty($recur['processor_id'])) {

      /**
       * Prepare payment object.
       */
      $mode = $recur['is_test'] ? 'test' : 'live';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($recur['processor_id'], $mode);
      $paymentClass = &CRM_Core_Payment::singleton($mode, $paymentProcessor, $this);
      if (!empty($paymentClass::$_editableFields)) {
        $activeFields = $paymentClass::$_editableFields;
      }
      else if(method_exists($paymentClass, 'getEditableFields')) {
        $activeFields = $paymentClass::getEditableFields($paymentProcessor, $this);
      }
      if (method_exists($paymentClass, 'doUpdateRecur') && !empty($activeFields)) {
        // For Payment which has doUpdateRecur and _editableFields, Like Spgateway.
        foreach ($activeFields as $field) {
          if ($recur[$field] != $params[$field]) {
            $requestParams[$field] = $params[$field];
          }
        }
        if (!empty($requestParams)) {
          $requestParams['contribution_recur_id'] = $this->_id;
          $requestParams['trxn_id'] = $params['trxn_id'];
          // if need debug, can add second params "1" the follow function.
          $config = CRM_Core_Config::singleton();
          $resultParams = $paymentClass->doUpdateRecur($requestParams, $config->debug);
          CRM_Core_Error::debug('ContributionRecur_PostProcess_resultParams', $resultParams);
          if ($resultParams['msg']) {
            
            CRM_Core_Session::setStatus($resultParams['msg']);
          }
          if ($resultParams['is_error']) {
            CRM_Core_Session::setStatus(ts('There are no any change.'));
          }
          else {
            $isUpdate = TRUE;

            /*
             * Compare doUpdateRecur result and edit params. 
             */
            if (!empty($resultParams['next_sched_contribution'])) {
              $params['next_sched_contribution'] = $resultParams['next_sched_contribution'];
              unset($resultParams['next_sched_contribution']);
            }
            foreach ($resultParams as $field => $value) {
              if (!empty($value) && in_array($field, $activeFields) && $params[$field] != $value) {
                if ($field == 'note_title' && !empty($params[$field])) {
                  $params[$field] .= $value;
                }
                else if ($field == 'note_body' && !empty($params[$field])) {
                  $params[$field] .= "\n" . $value;
                }
                else {
                  $params[$field] = $value;
                }
              }
            }
          }
        }

        //end of function
      } // Payment has doUpdateRecur function
      else {
        // For payment which has no doUpdateRecur function, Like TapPay.
        $isUpdate = TRUE;
      } // Payment has no doUpdateRecur function
    } // $recur has 'process_id'

    $customFields = CRM_Core_BAO_CustomField::getFields('ContributionRecur');
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params, $customFields, $this->_id, 'ContributionRecur');

    if (!empty($params['custom'])) {
      $isUpdate = TRUE;
    }

    if ($isUpdate) {
      // If there has update.
      // Update contribution recur
      
      $ids = array();
      require_once 'CRM/Contribute/BAO/ContributionRecur.php';
      CRM_Contribute_BAO_ContributionRecur::add($params, $ids);
      CRM_Core_Session::setStatus(ts('Your recurring contribution has been saved.'));
    
      CRM_Contribute_BAO_ContributionRecur::addNote($this->_id, $params['note_title'], $params['note_body']);
    }
    $urlParams = http_build_query(array(
      'reset' => 1,
      'id' => $this->_id,
      'cid' => $this->_contactID,
    ));
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contributionrecur', $urlParams));
  }
}

