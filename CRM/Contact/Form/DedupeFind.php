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
 * This class generates form components for DedupeRules
 *
 */
class CRM_Contact_Form_DedupeFind extends CRM_Admin_Form {

  /**
   * defined defaults
   *
   */

  public $_defaults;

  /**
   * Function to pre processing
   *
   * @return None
   * @access public
   */
  function preProcess() {
    $this->rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE, 0);
    if (CRM_Contact_Page_DedupeFind::dedupeRunning()) {
      $this->assign('is_running_process', TRUE);
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $groupList = CRM_Core_PseudoConstant::group();
    $groupList[''] = ts('- All Contacts -');
    asort($groupList);

    $this->add('select', 'group_id', ts('Select Group'), $groupList);
    $this->addButtons(array(
        array('type' => 'next',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
          'js' => array(
            'data' => 'click-once',
            'data-once-msg' => ts('Form processing. Do not reload page or you will loss your progress.'),
          ),
        ),
        //hack to support cancel button functionality
        array('type' => 'submit',
          'name' => ts('Cancel'),
        ),
      )
    );
    $this->addFormRule(array('CRM_Contact_Form_DedupeFind', 'formRule'), $this);
  }

  static function formRule($fields, $files, $form) {
    $errors = array();
    if ($form->rgid) {
      $dedupeGroupParams = array('id' => $form->rgid);
      $ruleGroup = CRM_Dedupe_BAO_RuleGroup::getDetailsByParams($dedupeGroupParams);
      $ruleGroup = $ruleGroup[$form->rgid];
      if (!$ruleGroup['threshold']) {
        $editUrl = CRM_Utils_System::url('civicrm/contact/deduperules', 'action=update&id='.$ruleGroup['id']);
        $message = ts('Dedupe Rule Group')." - {$ruleGroup['name']} ".ts("Weight Threshold to Consider Contacts 'Matching':").' '.$ruleGroup['threshold'];
        $message .= '<br> &raquo; <a href="'.$editUrl.'">'.ts('Edit Rule').'</a>';
        $errors['qfKey'] = $message;
      }
    }
    return $errors;
  }

  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $values = $this->exportValues();
    if (CRM_Utils_Array::value('_qf_DedupeFind_submit', $_POST)) {
      //used for cancel button
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1'));
      return;
    }
    if ($values['group_id']) {
      $url = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->rgid}&gid={$values['group_id']}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->rgid}");
    }

    CRM_Utils_System::redirect($url);
  }
}

