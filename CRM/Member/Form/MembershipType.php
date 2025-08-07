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
 * This class generates form components for Membership Type
 *
 */
class CRM_Member_Form_MembershipType extends CRM_Member_Form {

  /**
   * max number of contacts we will display for membership-organisation
   */
  CONST MAX_CONTACTS = 50;

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults = &parent::setDefaultValues();

    //finding default weight to be put
    if (!isset($defaults['weight']) || (!$defaults['weight'])) {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Member_DAO_MembershipType');
    }
    //setting default relationshipType
    if (isset($defaults['relationship_type_id'])) {
      //$defaults['relationship_type_id'] = $defaults['relationship_type_id'].'_a_b';
      // Set values for relation type select box
      $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['relationship_type_id']);
      $relDirections = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['relationship_direction']);
      $defaults['relationship_type_id'] = [];
      foreach ($relTypeIds as $key => $value) {
        $defaults['relationship_type_id'][] = $value . '_' . $relDirections[$key];
      }
    }

    $config = CRM_Core_Config::singleton();
    //setting default fixed_period_start_day & fixed_period_rollover_day
    $periods = ['fixed_period_start_day', 'fixed_period_rollover_day'];
    foreach ($periods as $per) {
      if (isset($defaults[$per])) {
        $dat = $defaults[$per];
        $dat = ($dat < 999) ? '0' . $dat : $dat;
        $defaults[$per] = [];
        $defaults[$per]['M'] = substr($dat, 0, 2);
        $defaults[$per]['d'] = substr($dat, 2, 3);
      }
    }

    if (isset($defaults['renewal_reminder_day'])) {
      $this->assign("origin_reminder_day", $defaults['renewal_reminder_day']);
      $this->set('origin_reminder_day', $defaults['renewal_reminder_day']);
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
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'name'), TRUE);

    $this->addRule('name', ts('A membership type with this name already exists. Please select another name.'),
      'objectExists', ['CRM_Member_DAO_MembershipType', $this->_id]
    );
    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'description')
    );
    $this->add('text', 'minimum_fee', ts('Minimum Fee'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'minimum_fee')
    );
    $this->addRule('minimum_fee', ts('Please enter a monetary value for the Minimum Fee.'), 'money');

    $this->addElement('select', 'duration_unit', ts('Duration') . ' ',
      CRM_Core_SelectValues::unitList('duration'), ['onchange' => 'showHidePeriodSettings()']
    );
    //period type
    $this->addElement('select', 'period_type', ts('Period Type'),
      CRM_Core_SelectValues::periodType(), ['onchange' => 'showHidePeriodSettings()']
    );

    $this->add('text', 'duration_interval', ts('Duration Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'duration_interval')
    );

    $memberOrg = &$this->add('text', 'member_org', ts('Membership Organization'), 'size=30 maxlength=120');
    //start day
    $this->add('date', 'fixed_period_start_day', ts('Fixed Period Start Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );

    //rollover day
    $this->add('date', 'fixed_period_rollover_day', ts('Fixed Period Rollover Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );

    // required in form rule
    $this->add('hidden', 'action', $this->_action);


    $this->add('select', 'contribution_type_id', ts('Contribution Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionType()
    );


    $relTypeInd = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    if (is_array($relTypeInd)) {
      asort($relTypeInd);
    }
    $memberRel = &$this->add('select', 'relationship_type_id', ts('Relationship Type'), ['' => ts('- select -')] + $relTypeInd);
    $memberRel->setMultiple(TRUE);

    $this->add('select', 'visibility', ts('Visibility'), CRM_Core_SelectValues::memberVisibility());
    $this->add('text', 'weight', ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'weight')
    );
    $this->add('checkbox', 'is_active', ts('Enabled?'));


    $msgTemplates = CRM_Core_BAO_MessageTemplates::getMessageTemplates(FALSE);
    if (!empty($msgTemplates)) {
      $reminderMsg = $this->add('select', 'renewal_msg_id', ts('Renewal Reminder Message'), ['' => ts('- select -')] + $msgTemplates);
    }
    else {
      $this->assign('noMsgTemplates', TRUE);
    }
    $reminderDay = &$this->addNumber(
      'renewal_reminder_day',
      ts('Renewal Reminder Day'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType',
        'renewal_reminder_day'
      )
    );

    $searchRows = $this->get('searchRows');
    $searchCount = $this->get('searchCount');
    $searchDone = $this->get('searchDone');

    if ($searchRows) {
      $checkBoxes = [];
      $chekFlag = 0;
      foreach ($searchRows as $id => $row) {
        $checked = '';
        if (!$chekFlag) {
          $checked = ['checked' => NULL];
          $chekFlag++;
        }

        $checkBoxes[$id] = $this->createElement('radio', NULL, NULL, NULL, $id, $checked);
      }

      $this->addGroup($checkBoxes, 'contact_check');
      $this->assign('searchRows', $searchRows);
    }

    $this->assign('searchCount', $searchCount);
    $this->assign('searchDone', $searchDone);

    if ($searchDone) {
      $searchBtn = ts('Search Again');
    }
    elseif ($this->_action & CRM_Core_Action::UPDATE) {
      $searchBtn = ts('Change');
    }
    else {
      $searchBtn = ts('Search');
    }
    $membershipRecords = FALSE;
    if ($this->_action & CRM_Core_Action::UPDATE) {

      $membershipType = new CRM_Member_BAO_Membership();
      $membershipType->membership_type_id = $this->_id;
      if ($membershipType->find(TRUE)) {
        $membershipRecords = TRUE;
        $memberRel->freeze();
      }
      $memberOrg->freeze();
      if ($searchDone) {
        $memberOrg->unfreeze();
      }
    }

    if (($this->_action & CRM_Core_Action::UPDATE) && $reminderDay) {
      $renewMessage = [];
      $returnProperties = ['renewal_msg_id', 'renewal_reminder_day'];
      CRM_Core_DAO::commonRetrieveAll('CRM_Member_DAO_MembershipType', 'id', $this->_id, $renewMessage, $returnProperties);
      if (CRM_Utils_Array::value('renewal_msg_id', $renewMessage[$this->_id]) &&
        CRM_Utils_Array::value('renewal_reminder_day', $renewMessage[$this->_id]) &&
        $membershipRecords
      ) {
        $reminderMsg = $this->add('select', 'renewal_msg_id', ts('Renewal Reminder Message'),
          ['' => ts('- select -')] + $msgTemplates
        );
      }
    }

    $this->addElement('submit', $this->getButtonName('refresh'), $searchBtn, ['class' => 'form-submit']);

    $this->addFormRule(['CRM_Member_Form_MembershipType', 'formRule']);

    $this->assign('membershipTypeId', $this->_id);

    // add current member count data
    if ($this->_id) {
      $memberCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_membership WHERE membership_type_id = %1 AND is_test = 0", [
        1 => [$this->_id, 'Integer']
      ]);
      $this->assign('memberCount', $memberCount);
      $this->set('memberCount', $memberCount);
    }
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params) {

    $errors = [];
    if (!isset($params['_qf_MembershipType_refresh']) || !$params['_qf_MembershipType_refresh']) {
      if (!$params['name']) {
        $errors['name'] = ts('Please enter a membership type name.');
      }

      if (!CRM_Utils_Array::value('contact_check', $params) && $params['action'] != CRM_Core_Action::UPDATE) {
        $errors['member_org'] = ts('Please select the membership organization');
      }

      if (empty($params['contribution_type_id'])) {
        $errors['contribution_type_id'] = ts('Please enter a contribution type.');
      }

      if (($params['minimum_fee'] > 0) && !$params['contribution_type_id']) {
        $errors['contribution_type_id'] = ts('Please enter the contribution type.');
      }

      if (empty($params['duration_unit'])) {
        $errors['duration_unit'] = ts('Please enter a duration unit.');
      }

      if (empty($params['duration_interval']) and $params['duration_unit'] != 'lifetime') {
        $errors['duration_interval'] = ts('Please enter a duration interval.');
      }

      if (empty($params['period_type'])) {
        $errors['period_type'] = ts('Please select a period type.');
      }

      if ($params['period_type'] == 'fixed' &&
        $params['duration_unit'] == 'day'
      ) {
        $errors['period_type'] = ts('Period type should be Rolling when duration unit is Day');
      }

      $config = CRM_Core_Config::singleton();
      if (($params['period_type'] == 'fixed') &&
        ($params['duration_unit'] == 'year')
      ) {
        $periods = ['fixed_period_start_day', 'fixed_period_rollover_day'];
        foreach ($periods as $period) {
          $month = $params[$period]['M'];
          $date = $params[$period]['d'];
          if (!$month || !$date) {
            switch ($period) {
              case 'fixed_period_start_day':
                $errors[$period] = ts('Please enter a valid fixed period start day');
                break;

              case 'fixed_period_rollover_day':
                $errors[$period] = ts('Please enter a valid fixed period rollover day');
                break;
            }
          }
        }
      }
    }

    if ($params['fixed_period_start_day'] && !empty($params['fixed_period_start_day'])) {
      $params['fixed_period_start_day']['Y'] = date('Y');
      if (!CRM_Utils_Rule::qfDate($params['fixed_period_start_day'])) {
        $errors['fixed_period_start_day'] = ts('Please enter valid Fixed Period Start Day');
      }
    }

    if ($params['fixed_period_rollover_day'] && !empty($params['fixed_period_rollover_day'])) {
      $params['fixed_period_rollover_day']['Y'] = date('Y');
      if (!CRM_Utils_Rule::qfDate($params['fixed_period_rollover_day'])) {
        $errors['fixed_period_rollover_day'] = ts('Please enter valid Fixed Period Rollover Day');
      }
    }

    $renewalReminderDay = CRM_Utils_Array::value('renewal_reminder_day', $params);
    $renewalMsgId = CRM_Utils_Array::value('renewal_msg_id', $params);
    if (!((strlen($renewalReminderDay) && $renewalMsgId) || (!strlen($renewalReminderDay) && !$renewalMsgId))) {
      if (!$renewalReminderDay) {
        $errors['renewal_reminder_day'] = ts('Please enter renewal reminder days.');
      }
      elseif (!$renewalMsgId) {
        $errors['renewal_msg_id'] = ts('Please select renewal message.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Utils_Weight::delWeight('CRM_Member_DAO_MembershipType', $this->_id);
      CRM_Member_BAO_MembershipType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected membership type has been deleted.'));
    }
    else {
      $buttonName = $this->controller->getButtonName();
      $submitted = $this->controller->exportValues($this->_name);

      $this->set('searchDone', 0);
      if ($buttonName == '_qf_MembershipType_refresh') {
        $this->search($submitted);
        $this->set('searchDone', 1);
        return;
      }

      $fields = ['name',
        'weight',
        'is_active',
        'member_org',
        'visibility',
        'period_type',
        'minimum_fee',
        'description',
        'auto_renew',
        'autorenewal_msg_id',
        'duration_unit',
        'renewal_msg_id',
        'duration_interval',
        'renewal_reminder_day',
        'contribution_type_id',
        'fixed_period_start_day',
        'fixed_period_rollover_day',
      ];

      $params = $ids = [];
      foreach ($fields as $fld) {
        $params[$fld] = CRM_Utils_Array::value($fld, $submitted, 'NULL');
      }

      //clean money.
      if ($params['minimum_fee']) {
        $params['minimum_fee'] = CRM_Utils_Rule::cleanMoney($params['minimum_fee']);
      }

      $hasRelTypeVal = FALSE;
      if (!CRM_Utils_System::isNull($submitted['relationship_type_id'])) {
        // To insert relation ids and directions with value separator
        $relTypeDirs = $submitted['relationship_type_id'];
        $relIds = $relDirection = [];
        foreach ($relTypeDirs as $key => $value) {
          $relationId = explode('_', $value);
          if (count($relationId) == 3 &&
            is_numeric($relationId[0])
          ) {
            $relIds[] = $relationId[0];
            $relDirection[] = $relationId[1] . '_' . $relationId[2];
          }
        }
        if (!empty($relIds)) {
          $hasRelTypeVal = TRUE;

          $params['relationship_type_id'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $relIds);
          $params['relationship_direction'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $relDirection);
        }
      }
      if (!$hasRelTypeVal) {
        $params['relationship_type_id'] = $params['relationship_direction'] = 'NULL';
      }

      if ($params['duration_unit'] == 'lifetime' &&
        empty($params['duration_interval'])
      ) {
        $params['duration_interval'] = 1;
      }

      $config = CRM_Core_Config::singleton();
      $periods = ['fixed_period_start_day', 'fixed_period_rollover_day'];
      foreach ($periods as $per) {
        if (CRM_Utils_Array::value('M', $params[$per]) &&
          CRM_Utils_Array::value('d', $params[$per])
        ) {
          $mon = $params[$per]['M'];
          $dat = $params[$per]['d'];
          $mon = ($mon < 9) ? '0' . $mon : $mon;
          $dat = ($dat < 9) ? '0' . $dat : $dat;
          $params[$per] = $mon . $dat;
        }
        else {
          $params[$per] = 'NULL';
        }
      }
      $oldWeight = NULL;
      $ids['memberOfContact'] = CRM_Utils_Array::value('contact_check', $submitted);

      if ($this->_id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $this->_id, 'weight', 'id'
        );
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Member_DAO_MembershipType',
        $oldWeight, $params['weight']
      );

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['membershipType'] = $this->_id;
      }

      $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The membership type \'%1\' has been saved.', [1 => $membershipType->name]));

      $originReminder = $this->get('origin_reminder_day');
      $memberCount = $this->get('memberCount');
      if ( (!empty($originReminder) || $originReminder == 0) && $originReminder != $params['renewal_reminder_day'] && $memberCount > 0 && $this->_id) {
        $dao = CRM_Core_DAO::executeQuery("SELECT id, end_date, reminder_date FROM civicrm_membership WHERE membership_type_id = %1 AND end_date IS NOT NULL", [
          1 => [$this->_id, 'Integer']
        ]);
        while($dao->fetch()) {
          if ($params['renewal_reminder_day'] != '' && !empty($dao->reminder_date)) {
            // we should trust reminder date exists means no notification sent recently
            $reminderDate = CRM_Member_BAO_MembershipType::calcReminderDate($dao->end_date, $params['renewal_reminder_day']);
            if (empty($reminderDate)) {
              $reminderDate = 'null';
            }
            CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $dao->id, 'reminder_date', $reminderDate);
          }
          elseif ($params['renewal_reminder_day'] != '' && empty($dao->reminder_date)) {
            // prevent duplicate reminder sent
            // only set reminder date when no notification sent in recent 30 days
            $reminderDate = CRM_Member_BAO_MembershipType::calcReminderDate($dao->end_date, $params['renewal_reminder_day']);
            if (!empty($reminderDate)) {
              $reminderActivityTypId = CRM_Core_OptionGroup::getValue('activity_type', 'Membership Renewal Reminder', 'name');
              $lastRemindSentDate = CRM_Core_DAO::singleValueQuery("SELECT MAX(activity_date_time) FROM civicrm_activity WHERE source_record_id = %1 AND activity_type_id = %2 GROUP BY source_record_id", [
                1 => [$dao->id, 'Integer'],
                2 => [$reminderActivityTypId, 'Integer'],
              ]);
              if (empty($lastRemindSentDate)) {
                CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $dao->id, 'reminder_date', $reminderDate);
              }
              else if ($lastRemindSentDate && (CRM_REQUEST_TIME - strtotime($lastRemindSentDate)) / 86400 > 30) {
                CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $dao->id, 'reminder_date', $reminderDate);
              }
            }
          }
          else {
            CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $dao->id, 'reminder_date', 'null');
          }
        }
        CRM_Core_Session::setStatus(ts('Members in this type alreay have new reminder date.'));
      }
      $session = CRM_Core_Session::singleton();
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/member/membershipType',
            'action=add&reset=1'
          ));
      }
    }
  }

  /**
   * This function is to get the result of the search for membership organisation.
   *
   * @param  array $params  This contains elements for search criteria
   *
   * @access public
   *
   * @return None
   *
   */
  function search(&$params) {
    //max records that will be listed
    $searchValues = [];
    if (!empty($params['member_org'])) {
      $searchValues[] = ['sort_name', 'LIKE', $params['member_org'], 0, 1];
    }
    $searchValues[] = ['contact_type', '=', 'organization', 0, 0];

    // get the count of contact

    $contactBAO = new CRM_Contact_BAO_Contact();
    $query = new CRM_Contact_BAO_Query($searchValues);
    $searchCount = $query->searchQuery(0, 0, NULL, TRUE);
    $this->set('searchCount', $searchCount);
    if ($searchCount <= self::MAX_CONTACTS) {
      // get the result of the search
      $result = $query->searchQuery(0, self::MAX_CONTACTS, NULL);

      $config = CRM_Core_Config::singleton();
      $searchRows = [];

      while ($result->fetch()) {
        $contactID = $result->contact_id;

        $searchRows[$contactID]['id'] = $contactID;
        $searchRows[$contactID]['name'] = $result->sort_name;
        $searchRows[$contactID]['city'] = $result->city;
        $searchRows[$contactID]['state'] = $result->state_province;
        $searchRows[$contactID]['email'] = $result->email;
        $searchRows[$contactID]['phone'] = $result->phone;

        $contact_type = '<img src="' . $config->resourceBase . 'i/contact_';

        $contact_type .= 'org.gif" alt="' . ts('Organization') . '" height="16" width="18" />';

        $searchRows[$contactID]['type'] = $contact_type;
      }
      $this->set('searchRows', $searchRows);
    }
    else {
      // resetting the session variables if many records are found
      $this->set('searchRows', NULL);
    }
  }
}

