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
 * Choose include / exclude groups and mailings
 *
 */
class CRM_Mailing_Form_Group extends CRM_Contact_Form_Task {

  public $_searchBasedMailing;
  public $_resultSelectOption;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    if (CRM_Core_BAO_MailSettings::defaultDomain() == "FIXME.ORG") {
      CRM_Core_Session::setStatus(ts('The <a href="%1">default mailbox</a> has not been configured. You will find <a href="%2">more info in our online user and administrator guide.</a>', [1 => CRM_Utils_System::url('civicrm/admin/mailSettings', 'reset=1'), 2 => "http://book.civicrm.org/user/basic-setup/email-system-configuration"]));
      return;
    }
    //when user come from search context.

    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
    if ($this->_searchBasedMailing) {
      $searchParams = $this->controller->exportValues();
      if ($this->controller->get('context') == 'smog' && !empty($this->controller->get('gid'))) {
        $this->set('smog', $this->controller->get('gid'));
      }
      // number of records that were selected - All or Few.
      $this->_resultSelectOption = $searchParams['radio_ts'];
      if (CRM_Utils_Array::value('task', $searchParams) == 20) {
        parent::preProcess();
      }
    }

    // use previous context unless mailing is not schedule, CRM-4290
    $session = CRM_Core_Session::singleton();
    if (strpos($session->readUserContext(), 'civicrm/mailing') === FALSE) {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing', 'reset=1'));
    }
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
    $continue = CRM_Utils_Request::retrieve('continue', 'String', $this, FALSE, NULL);
    $reschedule = CRM_Utils_Request::retrieve('reschedule', 'Integer', $this, FALSE, NULL);

    // check that the user has permission to access mailing id

    CRM_Mailing_BAO_Mailing::checkPermission($mailingID);

    $defaults = [];
    $defaults['dedupe_email'] = 1;

    if ($mailingID) {
      $mailing = new CRM_Mailing_DAO_Mailing();
      $mailing->id = $mailingID;
      $mailing->addSelectByOption('name', 'campaign_id');
      $mailing->find(TRUE);

      if ($reschedule) {
        $existsJob = [];
        $returnProperties = ['id', 'status', 'start_date'];
        $params = ['mailing_id' => $mailing->id, 'is_test' => 0];
        CRM_Core_DAO::commonRetrieve('CRM_Mailing_DAO_Job', $params, $existsJob, $returnProperties);

        if (!empty($existsJob) && empty($existsJob['start_date']) && $existsJob['status'] === 'Scheduled') {
          CRM_Mailing_BAO_Mailing::delJob($existsJob['id']);
        }

        // refs #34112, gh-37, reset mailing's data and move to draft page when rescheduling.
        if (!is_null($mailing->scheduled_id)) {
          $mailing->scheduled_id = 'null';
        }

        if (!is_null($mailing->scheduled_date)) {
          $mailing->scheduled_date = 'null';
        }

        $mailing->save();
      }

      $defaults['name'] = $mailing->name;
      if ($mailing->name) {
        CRM_Utils_System::setTitle($mailing->name);
      }
      if (!$continue) {
        $defaults['name'] = ts('Copy of %1', [1 => $mailing->name]);
      }
      else {
        // CRM-7590, reuse same mailing ID if we are continuing
        $this->set('mailing_id', $mailingID);
      }

      $defaults['campaign_id'] = $mailing->campaign_id;
      $defaults['dedupe_email'] = $mailing->dedupe_email;


      $dao = new CRM_Mailing_DAO_Group();

      $mailingGroups = [];
      $dao->mailing_id = $mailingID;
      $dao->find();
      while ($dao->fetch()) {
        $mailingGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
      }

      $defaults['includeGroups'] = $mailingGroups['civicrm_group']['Include'];
      $defaults['excludeGroups'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_group']);

      $defaults['includeMailings'] = CRM_Utils_Array::value('Include', $mailingGroups['civicrm_mailing']);
      $defaults['excludeMailings'] = $mailingGroups['civicrm_mailing']['Exclude'];

      $defaults['includeOpened'] = $mailingGroups['civicrm_mailing_event_opened']['Include'];
      $defaults['excludeOpened'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_mailing_event_opened']);

      $defaults['includeClicked'] = $mailingGroups['civicrm_mailing_event_trackable_url_open']['Include'];
      $defaults['excludeClicked'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_mailing_event_trackable_url_open']);
    }

    //when the context is search hide the mailing recipients.

    $showHide = new CRM_Core_ShowHideBlocks();
    $showGroupSelector = TRUE;

    if (!empty($this->_searchBasedMailing)) {
      $showGroupSelector = FALSE;
      $formElements = ['includeGroups', 'excludeGroups', 'includeMailings', 'excludeMailings', 'includeOpened', 'excludeOpened', 'includeClicked', 'excludeClicked'];
      $formValues = $this->controller->exportValues($this->_name);
      foreach ($formElements as $element) {
        if (!empty($formValues[$element])) {
          $showGroupSelector = TRUE;
          break;
        }
      }
    }

    if ($showGroupSelector) {
      $showHide->addShow("id-additional");
      $showHide->addHide("id-additional-show");
    }
    else {
      $showHide->addShow("id-additional-show");
      $showHide->addHide("id-additional");
    }
    $showHide->addToTemplate();

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {


    //get the context
    $context = $this->get('context');
    if (!empty($this->_searchBasedMailing)) {
      $context = 'search';
    }
    $this->assign('context', $context);

    $this->add('text', 'name', ts('Name Your Mailing'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'name'),
      TRUE
    );

    /*
    //CRM-7362 --add campaigns.
    $mailingId = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);

    $campaignId = NULL;
    if ($mailingId) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailingId, 'campaign_id');
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);
*/


    //dedupe on email option
    $this->addElement('checkbox', 'dedupe_email', ts('Remove duplicate emails?'));

    //get the mailing groups.
    $groups = CRM_Core_PseudoConstant::group('Mailing');

    $mailings = CRM_Mailing_PseudoConstant::completed();
    if (!$mailings) {
      $mailings = [];
    }

    // run the groups through a hook so users can trim it if needed

    CRM_Utils_Hook::mailingGroups($this, $groups, $mailings);

    //when the context is search add base group's.
    if (!empty($this->_searchBasedMailing)) {
      //get the static groups
      $staticGroups = CRM_Core_PseudoConstant::staticGroup(FALSE, 'Mailing');
      $this->add('select', 'baseGroup',
        ts('Unsubscription Group'),
        [
          '' => ts('- select -'),
        ] + $staticGroups,
        TRUE
      );
    }

    // group
    $this->addSelect('includeGroups', ts('Include Group(s)'), $groups, ['class' => 'chosen', 'multiple' => 'multiple']);
    $this->addSelect('excludeGroups', ts('Exclude Group(s)'), $groups, ['class' => 'chosen', 'multiple' => 'multiple']);

    // mailing
    $this->addSelect('includeMailings', ts('INCLUDE Recipients of These Mailing(s)'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);
    $this->addSelect('excludeMailings', ts('EXCLUDE Recipients of These Mailing(s)'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);
    
    // open
    $this->addSelect('includeOpened', ts('INCLUDE Recipients who opened these mailing'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);
    $this->addSelect('excludeOpened', ts('EXCLUDE Recipients who opened these mailing'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);

    // clicked1
    $this->addSelect('includeClicked', ts('INCLUDE Recipients who clicked these mailing'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);
    $this->addSelect('excludeClicked', ts('EXCLUDE Recipients who clicked these mailing'), $mailings, ['class' => 'chosen', 'multiple' => 'multiple']);

    $this->addFormRule(['CRM_Mailing_Form_Group', 'formRule']);

    //FIXME : currently we are hiding save an continue later when
    //search base mailing, we should handle it when we fix CRM-3876
    $js = ['data' => 'click-once'];
    $buttons = [
      ['type' => 'next',
        'name' => ts('Next >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
        'js' => $js,
      ],
      [
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
        'js' => $js,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];

    if (!empty($this->_searchBasedMailing)) {
      $buttons = [
        ['type' => 'next',
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
    $this->addButtons($buttons);
    $this->assign('groupCount', count($groups));
    $this->assign('mailingCount', count($mailings));
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    //build hidden smart group. when user want to send  mailing
    //through search contact-> more action -> send Mailing. CRM-3711
    $rules = [];
    if ($this->_searchBasedMailing && $this->_contactIds) {
      $session = CRM_Core_Session::singleton();


      if ($this->_resultSelectOption == 'ts_sel') {
        // create a static grp if only a subset of result set was selected:

        $qfsID = $session->get('qfSessionID');
        $grpTitle = "Hidden Group {$qfsID}";
        $grpID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $grpTitle, 'id', 'title');

        if (!$grpID) {
          $groupParams = [
            'title' => $grpTitle,
            'is_active' => 1,
            'is_hidden' => 1,
            'group_type' => ['2' => 1],
          ];

          $group = CRM_Contact_BAO_Group::create($groupParams);
          $grpID = $group->id;

          CRM_Contact_BAO_GroupContact::addContactsToGroup($this->_contactIds, $group->id);
        }

        // note at this point its a static group
        $smartGroupId = $grpID;
      }
      else {
        //get the hidden smart group id.
        $ssId = $this->get('ssID');
        $hiddenSmartParams = ['group_type' => ['2' => 1],
          'form_values' => $this->get('formValues'),
          'saved_search_id' => $ssId,
          'search_custom_id' => $this->get('customSearchID'),
          'search_context' => $this->get('context'),
        ];

        list($smartGroupId, $savedSearchId) = CRM_Contact_BAO_Group::createHiddenSmartGroup($hiddenSmartParams);

        //set the saved search id.
        if (!$ssId) {
          if ($savedSearchId) {
            $this->set('ssID', $savedSearchId);
          }
          else {
            CRM_Core_Error::fatal();
          }
        }
      }

      //get the base group for this mailing, CRM-3711
      $rules['groups']['base'] = [$values['baseGroup']];
      $rules['groups']['include'][] = $smartGroupId;
    }

    foreach ([
        'name', 'group_id', 'search_id', 'search_args', 'campaign_id', 'dedupe_email',
      ] as $n) {
      if (CRM_Utils_Array::value($n, $values)) {
        $params[$n] = $values[$n];
      }
    }


    $qf_Group_submit = $this->controller->exportValue($this->_name, '_qf_Group_submit');
    $this->set('name', $params['name']);

    foreach($values as $key => $args){
      if(preg_match('/^(include|exclude)/i', $key)){
        $rulekey = str_replace(['include', 'exclude'], '', $key);
        $rulekey = strtolower($rulekey);
        $ruletype = strstr($key, 'include') ? 'include' : 'exclude';
        if (!empty($args) && is_array($args)) {
          foreach($args as $k => $id){
            if (!empty($id)) {
              $rules[$rulekey][$ruletype][] = $id;
            }
          }
        }
      }
    }

    $session = CRM_Core_Session::singleton();
    $params = array_merge($params, $rules);

    if ($this->get('mailing_id')) {
      $ids = [];
      // don't create a new mailing if already exists
      $ids['mailing_id'] = $this->get('mailing_id');
      $tables = [
        'groups' => CRM_Contact_BAO_Group::getTableName(),
        'mailings' =>  CRM_Mailing_BAO_Mailing::getTableName(),
        'opened' =>  CRM_Mailing_Event_BAO_Opened::getTableName(),
        'clicked' =>  CRM_Mailing_Event_BAO_TrackableURLOpen::getTableName(),
      ];
      // delete previous includes/excludes, if mailing already existed

      foreach ($tables as $entity => $table) {
        $mg = new CRM_Mailing_DAO_Group();
        $mg->mailing_id = $ids['mailing_id'];
        $mg->entity_table = $table;
        $mg->find();
        while ($mg->fetch()) {
          $mg->delete();
        }
      }
    }
    else {
      // new mailing, so lets set the created_id
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }


    $mailing = CRM_Mailing_BAO_Mailing::create($params, $ids);
    $this->set('mailing_id', $mailing->id);

    $dedupeEmail = FALSE;
    if (isset($params['dedupe_email'])) {
      $dedupeEmail = $params['dedupe_email'];
    }

    // also compute the recipients and store them in the mailing recipients table
    CRM_Mailing_BAO_Mailing::getRecipients($mailing->id,
      $mailing->id,
      NULL,
      NULL,
      TRUE,
      $dedupeEmail
    );


    $count = CRM_Mailing_BAO_Recipients::mailingSize($mailing->id);
    $this->set('count', $count);
    $this->assign('count', $count);
    foreach($rules as $key => $rule){
      $this->set($key, $rule);
    }

    if ($qf_Group_submit) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      $context = $this->get('context');
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
        return $this->controller->setDestination($url);
      }
      else {
        $status = ts("Your mailing has been saved. Click the 'Continue' action to resume working on it.");
        CRM_Core_Session::setStatus($status);
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        return $this->controller->setDestination($url);
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
    return ts('Select Recipients');
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
  static function formRule($fields) {
    $errors = [];
    if (isset($fields['includeGroups']) &&
      is_array($fields['includeGroups']) &&
      isset($fields['excludeGroups']) &&
      is_array($fields['excludeGroups'])
    ) {
      $checkGroups = [];
      $checkGroups = array_intersect($fields['includeGroups'], $fields['excludeGroups']);
      if (!empty($checkGroups)) {
        $errors['excludeGroups'] = ts('Cannot have same groups in Include Group(s) and Exclude Group(s).');
      }
    }

    if (isset($fields['includeMailings']) &&
      is_array($fields['includeMailings']) &&
      isset($fields['excludeMailings']) &&
      is_array($fields['excludeMailings'])
    ) {
      $checkMailings = [];
      $checkMailings = array_intersect($fields['includeMailings'], $fields['excludeMailings']);
      if (!empty($checkMailings)) {
        $errors['excludeMailings'] = ts('Cannot have same mail in Include mailing(s) and Exclude mailing(s).');
      }
    }

    if (!empty($fields['search_id']) &&
      empty($fields['group_id'])
    ) {
      $errors['group_id'] = ts('You must select a group to filter on');
    }

    if (empty($fields['search_id']) &&
      !empty($fields['group_id'])
    ) {
      $errors['search_id'] = ts('You must select a search to filter');
    }

    return empty($errors) ? TRUE : $errors;
  }
}

