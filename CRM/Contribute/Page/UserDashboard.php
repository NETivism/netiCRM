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


class CRM_Contribute_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * This function is called when action is browse
   *
   * return null
   * @access public
   */
  function listContribution() {
    $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_Search', ts('Contributions'), NULL);
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 12);
    $controller->set('cid', $this->_contactId);
    $controller->set('context', 'user');
    $controller->set('force', 1);
    $controller->process();
    $controller->run();

    //add honor block

    $params = [];
    $params = CRM_Contribute_BAO_Contribution::getHonorContacts($this->_contactId);

    if (!empty($params)) {
      // assign vars to templates
      $this->assign('honorRows', $params);
      $this->assign('honor', TRUE);
    }




    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->contact_id = $this->_contactId;
    $recur->is_test = 0;
    $recur->find();

    $config = CRM_Core_Config::singleton();

    $recurStatus = CRM_Contribute_PseudoConstant::contributionStatus();



    $recurRow = [];
    $recurIDs = [];
    while ($recur->fetch()) {
      $mode = $recur->is_test ? 'test' : 'live';
      $paymentProcessor = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessor($recur->id, $mode);
      if (!$paymentProcessor) {
        continue;
      }

      // note that we are passing a CRM_Core_Page object ($this) as if it were a form here:
      $paymentObject = &CRM_Core_Payment::singleton($mode, $paymentProcessor, $this);

      _civicrm_api3_object_to_array($recur, $values);
      $values['cancelSubscriptionUrl'] = $paymentObject->cancelSubscriptionURL();
      $values['recur_status'] = $recurStatus[$values['contribution_status_id']];
      $values['installments'] = empty($values['installments']) ? ts('Until expires') : $values['installments'];
      $recurRow[$values['id']] = $values;
      $recurIDs[] = $values['id'];

      //reset $paymentObject for checking other paymenet processor
      //recurring url
      $paymentObject = NULL;
    }
    if (is_array($recurIDs) && !empty($recurIDs)) {
      $getCount = CRM_Contribute_BAO_ContributionRecur::getCount($recurIDs);
      foreach ($getCount as $key => $val) {
        $recurRow[$key]['completed'] = $val;
        $recurRow[$key]['link'] = CRM_Utils_System::url('civicrm/contribute/search', "reset=1&force=1&recur=$key");
      }
    }
    $this->assign('recurRows', $recurRow);

    if (!empty($recurRow)) {
      $this->assign('recur', TRUE);
    }
    else {
      $this->assign('recur', FALSE);
    }
  }

  /**
   * This function is the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    parent::preProcess();
    $this->listContribution();
  }
}

