<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

/**
 * This file is used to build the form configuring mailing details
 */
class CRM_Mailing_Form_Settings extends CRM_Core_Form {

  public $_searchBasedMailing;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //when user come from search context.

    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $count = $this->get('count');
    $this->assign('count', $count);
    $defaults = [];

    $componentFields = [
      'reply_id' => 'Reply',
      'optout_id' => 'OptOut',
      'unsubscribe_id' => 'Unsubscribe',
      'resubscribe_id' => 'Resubscribe',
    ];

    foreach ($componentFields as $componentVar => $componentType) {
      $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
    }

    if ($mailingID) {

      $dao = new CRM_Mailing_DAO_Mailing();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      // override_verp must be flipped, as in 3.2 we reverted
      // its meaning to ‘should CiviMail manage replies?’ – i.e.,
      // ‘should it *not* override Reply-To: with VERP-ed address?’
      $dao->override_verp = 1;
      $dao->forward_replies = 1;
      $dao->storeValues($dao, $defaults);
      if ($dao->name) {
        CRM_Utils_System::setTitle($dao->name);
      }
      $defaults['visibility'] = $dao->visibility;
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addElement('hidden', 'override_verp', 1);
    $this->addElement('hidden', 'forward_replies', 1);

    $this->add('checkbox', 'url_tracking', ts('Track Click-throughs?'));
    $defaults['url_tracking'] = TRUE;

    $this->add('checkbox', 'open_tracking', ts('Track Opens?'));
    $defaults['open_tracking'] = TRUE;

    $options = ['' => ts("-- select --")] + CRM_Core_SelectValues::ufVisibility(TRUE);
    $this->add('select', 'visibility', ts('Mailing Visibility'),
    $options, TRUE
    );

    $this->add('select', 'unsubscribe_id', ts('Unsubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Unsubscribe'), TRUE
    );

    $this->add('select', 'resubscribe_id', ts('Resubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Resubscribe'), TRUE
    );

    $this->add('select', 'optout_id', ts('Opt-out Message'),
      CRM_Mailing_PseudoConstant::component('OptOut'), TRUE
    );

    //FIXME : currently we are hiding save an continue later when
    //search base mailing, we should handle it when we fix CRM-3876
    if ($this->_searchBasedMailing) {
      $buttons = [
        ['type' => 'back',
          'name' => ts('<< Previous'),
        ],
        [
          'type' => 'next',
          'name' => ts('Next >>'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ];
    }
    else {
      $buttons = [
        ['type' => 'back',
          'name' => ts('<< Previous'),
        ],
        [
          'type' => 'next',
          'name' => ts('Next >>'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'submit',
          'name' => ts('Save & Continue Later'),
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ];
    }

    $this->addButtons($buttons);

    $this->setDefaults($defaults);
  }

  public function postProcess() {
    $params = $ids = [];

    $uploadParams = ['reply_id', 'unsubscribe_id', 'optout_id', 'resubscribe_id'];
    $uploadParamsBoolean = ['override_verp', 'forward_replies', 'url_tracking', 'open_tracking', 'auto_responder'];

    $qf_Settings_submit = $this->controller->exportValue($this->_name, '_qf_Settings_submit');

    foreach ($uploadParams as $key) {
      $params[$key] = $this->controller->exportvalue($this->_name, $key);
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    foreach ($uploadParamsBoolean as $key) {
      if ($this->controller->exportvalue($this->_name, $key)) {
        $params[$key] = TRUE;
      }
      else {
        $params[$key] = FALSE;
      }
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    $params['visibility'] = $this->controller->exportvalue($this->_name, 'visibility');

    // override_verp must be flipped, as in 3.2 we reverted
    // its meaning to ‘should CiviMail manage replies?’ – i.e.,
    // ‘should it *not* override Reply-To: with VERP-ed address?’
    $params['override_verp'] = !$params['override_verp'];

    $ids['mailing_id'] = $this->get('mailing_id');

    // update mailing

    CRM_Mailing_BAO_Mailing::create($params, $ids);

    if ($qf_Settings_submit) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      if ($ssID && $this->_searchBasedMailing) {
        if ($this->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($this->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($this->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $context = $this->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }
        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("Your mailing has been saved. You can continue later by clicking the 'Continue' action to resume working on it.<br /> From <a href='%1'>Draft and Unscheduled Mailings</a>.", [1 => $draftURL]);
        CRM_Core_Session::setStatus($status);

        //replace user context to search.
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
        CRM_Utils_System::redirect($url);
      }
      else {
        $status = ts("Your mailing has been saved. Click the 'Continue' action to resume working on it.");
        CRM_Core_Session::setStatus($status);
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        CRM_Utils_System::redirect($url);
      }
    }
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Track and Respond');
  }
}

