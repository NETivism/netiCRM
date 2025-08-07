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
 * This class generates form components for processing a survey
 *
 */
class CRM_Campaign_Form_Survey extends CRM_Core_Form {

  /**
   * The id of the object being edited
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * action
   *
   * @var int
   */
  protected $_action;

  /* values
     *
     * @var array
     */

  public $_values;

  /**
   * context
   *
   * @var string
   */
  protected $_context;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  CONST NUM_OPTION = 11;

  public function preProcess() {

    if (!CRM_Campaign_BAO_Campaign::accessCampaignDashboard()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    if ($this->_context) {
      $this->assign('context', $this->_context);
    }

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        CRM_Utils_System::setTitle(ts('Edit Survey'));
      }
      else {
        CRM_Utils_System::setTitle(ts('Delete Survey'));
      }
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
    $session->pushUserContext($url);

    if ($this->_name != 'Petition') {
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey Dashboard'), 'url' => $url]]);
    }

    $this->_values = [];
    if ($this->_surveyId) {
      $this->assign('surveyId', $this->_surveyId);

      $values = $this->get('values');
      // get contact values.
      if (!empty($values)) {
        $this->_values = $values;
      }
      else {
        $params = ['id' => $this->_surveyId];
        CRM_Campaign_BAO_Survey::retrieve($params, $this->_values, TRUE);
        $this->set('values', $this->_values);
      }
    }

    $this->assign('action', $this->_action);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array    array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = $this->_values;

    if ($this->_surveyId) {


      if (CRM_Utils_Array::value('result_id', $defaults) &&
        CRM_Utils_Array::value('recontact_interval', $defaults)
      ) {


        $resultId = $defaults['result_id'];
        $recontactInterval = unserialize($defaults['recontact_interval']);

        unset($defaults['recontact_interval']);
        $defaults['option_group_id'] = $resultId;
      }

      $ufJoinParams = ['entity_table' => 'civicrm_survey',
        'entity_id' => $this->_surveyId,
        'weight' => 1,
      ];

      if ($ufGroupId = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams)) {
        $defaults['profile_id'] = $ufGroupId;
      }
    }

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    // set defaults for weight.
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      $defaults["option_weight[{$i}]"] = $i;
    }

    $defaultSurveys = CRM_Campaign_BAO_Survey::getSurvey(FALSE, FALSE, TRUE);
    if (!isset($defaults['is_default']) && empty($defaultSurveys)) {
      $defaults['is_default'] = 1;
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

      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }





    $this->add('text', 'title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'title'), TRUE);

    $surveyActivityTypes = CRM_Campaign_BAO_Survey::getSurveyActivityType();
    // Activity Type id
    $this->add('select', 'activity_type_id', ts('Activity Type'), ['' => ts('- select -')] + $surveyActivityTypes, TRUE);

    // Campaign id

    $campaigns = CRM_Campaign_BAO_Campaign::getAllCampaign();
    $this->add('select', 'campaign_id', ts('Campaign'), ['' => ts('- select -')] + $campaigns);

    $customProfiles = CRM_Core_BAO_UFGroup::getProfiles(['Activity']);
    // custom group id
    $this->add('select', 'profile_id', ts('Profile'),
      ['' => ts('- select -')] + $customProfiles
    );

    $optionGroups = CRM_Campaign_BAO_Survey::getResultSets();

    if (empty($optionGroups)) {
      $optionTypes = ['1' => ts('Create new response set')];
    }
    else {
      $optionTypes = ['1' => ts('Create a new response set'),
        '2' => ts('Use existing response set'),
      ];
      $this->add('select',
        'option_group_id',
        ts('Select Response Set'),
        ['' => ts('- select -')] + $optionGroups, FALSE,
        ['onChange' => 'loadOptionGroup( )']
      );
    }

    $element = &$this->addRadio('option_type',
      ts('Survey Responses'),
      $optionTypes,
      ['onclick' => "showOptionSelect();"], '<br/>', TRUE
    );

    if (empty($optionGroups) || !CRM_Utils_Array::value('result_id', $this->_values)) {
      $this->setdefaults(['option_type' => 1]);
    }
    elseif (CRM_Utils_Array::value('result_id', $this->_values)) {
      $this->setdefaults(['option_type' => 2,
          'option_group_id' => $this->_values['result_id'],
        ]);
    }

    // form fields of Custom Option rows
    $defaultOption = [];
    $_showHide = new CRM_Core_ShowHideBlocks('', '');

    $optionAttributes = &CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue');
    $optionAttributes['label']['size'] = $optionAttributes['value']['size'] = 25;

    for ($i = 1; $i <= self::NUM_OPTION; $i++) {

      //the show hide blocks
      $showBlocks = 'optionField_' . $i;
      if ($i > 2) {
        $_showHide->addHide($showBlocks);
        if ($i == self::NUM_OPTION) {
          $_showHide->addHide('additionalOption');
        }
      }
      else {
        $_showHide->addShow($showBlocks);
      }

      $this->add('text', 'option_label[' . $i . ']', ts('Label'),
        $optionAttributes['label']
      );

      // value
      $this->add('text', 'option_value[' . $i . ']', ts('Value'),
        $optionAttributes['value']
      );

      // weight
      $this->add('text', "option_weight[$i]", ts('Order'),
        $optionAttributes['weight']
      );

      $this->add('text', 'option_interval[' . $i . ']', ts('Recontact Interval'),
        CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'release_frequency')
      );

      $defaultOption[$i] = $this->createElement('radio', NULL, NULL, NULL, $i);
    }

    //default option selection
    $this->addGroup($defaultOption, 'default_option');

    $_showHide->addToTemplate();

    // script / instructions
    $this->add('textarea', 'instructions', ts('Instructions for interviewers'), ['rows' => 5, 'cols' => 40]);

    // release frequency
    $this->add('text', 'release_frequency', ts('Release frequency'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'release_frequency'));

    $this->addRule('release_frequency', ts('Release Frequency interval should be a positive number.'), 'positiveInteger');

    // max reserved contacts at a time
    $this->add('text', 'default_number_of_contacts', ts('Maximum reserved at one time'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'default_number_of_contacts'));
    $this->addRule('default_number_of_contacts', ts('Maximum reserved at one time should be a positive number'), 'positiveInteger');

    // total reserved per interviewer
    $this->add('text', 'max_number_of_contacts', ts('Total reserved per interviewer'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'max_number_of_contacts'));
    $this->addRule('max_number_of_contacts', ts('Total reserved contacts should be a positive number'), 'positiveInteger');

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // is default ?
    $this->add('checkbox', 'is_default', ts('Default?'));

    // add buttons
    if ($this->_context == 'dialog') {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
            'js' => ['onclick' => "cj('#survey-dialog').dialog('close'); return false;"],
          ],
        ]);
    }
    else {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
          ['type' => 'next',
            'name' => ts('Save and New'),
            'subName' => 'new',
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }

    // add a form rule to check default value
    $this->addFormRule(['CRM_Campaign_Form_Survey', 'formRule'], $this);
  }

  /**
   * global validation rules for the form
   *
   */
  static function formRule($fields, $files, $form) {
    $errors = [];

    if (CRM_Utils_Array::value('option_label', $fields) &&
      CRM_Utils_Array::value('option_value', $fields) &&
      (count(array_filter($fields['option_label'])) == 0) &&
      (count(array_filter($fields['option_value'])) == 0)
    ) {
      $errors['option_label[1]'] = ts('Enter atleast one response option.');
      return $errors;
    }

    if ($fields['option_type'] == 2 &&
      !CRM_Utils_Array::value('option_group_id', $fields)
    ) {
      $errors['option_group_id'] = ts("Please select Survey Response set.");
      return $errors;
    }

    $_flagOption = $_rowError = 0;
    $_showHide = new CRM_Core_ShowHideBlocks('', '');

    //capture duplicate Custom option values
    if (!empty($fields['option_value'])) {
      $countValue = count($fields['option_value']);
      $uniqueCount = count(array_unique($fields['option_value']));

      if ($countValue > $uniqueCount) {
        $start = 1;
        while ($start < self::NUM_OPTION) {
          $nextIndex = $start + 1;

          while ($nextIndex <= self::NUM_OPTION) {
            if ($fields['option_value'][$start] == $fields['option_value'][$nextIndex] &&
              !empty($fields['option_value'][$nextIndex])
            ) {

              $errors['option_value[' . $start . ']'] = ts('Duplicate Option values');
              $errors['option_value[' . $nextIndex . ']'] = ts('Duplicate Option values');
              $_flagOption = 1;
            }
            $nextIndex++;
          }
          $start++;
        }
      }
    }

    //capture duplicate Custom Option label
    if (!empty($fields['option_label'])) {
      $countValue = count($fields['option_label']);
      $uniqueCount = count(array_unique($fields['option_label']));

      if ($countValue > $uniqueCount) {
        $start = 1;
        while ($start < self::NUM_OPTION) {
          $nextIndex = $start + 1;

          while ($nextIndex <= self::NUM_OPTION) {
            if ($fields['option_label'][$start] == $fields['option_label'][$nextIndex] && !empty($fields['option_label'][$nextIndex])) {
              $errors['option_label[' . $start . ']'] = ts('Duplicate Option label');
              $errors['option_label[' . $nextIndex . ']'] = ts('Duplicate Option label');
              $_flagOption = 1;
            }
            $nextIndex++;
          }
          $start++;
        }
      }
    }

    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      if (!$fields['option_label'][$i]) {
        if ($fields['option_value'][$i]) {
          $errors['option_label[' . $i . ']'] = ts('Option label cannot be empty');
          $_flagOption = 1;
        }
        else {
          $_emptyRow = 1;
        }
      }
      elseif (!strlen(trim($fields['option_value'][$i]))) {
        if (!$fields['option_value'][$i]) {
          $errors['option_value[' . $i . ']'] = ts('Option value cannot be empty');
          $_flagOption = 1;
        }
      }

      if (CRM_Utils_Array::value($i, $fields['option_interval']) && !CRM_Utils_Rule::integer($fields['option_interval'][$i])) {
        $_flagOption = 1;
        $errors['option_interval[' . $i . ']'] = ts('Please enter a valid integer.');
      }

      $showBlocks = 'optionField_' . $i;
      if ($_flagOption) {
        $_showHide->addShow($showBlocks);
        $_rowError = 1;
      }

      if (!empty($_emptyRow)) {
        $_showHide->addHide($showBlocks);
      }
      else {
        $_showHide->addShow($showBlocks);
      }

      if ($i == self::NUM_OPTION) {
        $hideBlock = 'additionalOption';
        $_showHide->addHide($hideBlock);
      }

      $_flagOption = $_emptyRow = 0;
    }
    $_showHide->addToTemplate();

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();

    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');




    $updateResultSet = FALSE;
    if ((CRM_Utils_Array::value('option_type', $params) == 2) &&
      CRM_Utils_Array::value('option_group_id', $params)
    ) {
      if ($params['option_group_id'] == CRM_Utils_Array::value('result_id', $this->_values)) {
        $updateResultSet = TRUE;
      }
    }

    if ($this->_surveyId) {

      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Survey::del($this->_surveyId);
        CRM_Core_Session::setStatus(ts(' Survey has been deleted.'));
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey'));
        return;
      }

      $params['id'] = $this->_surveyId;
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, 0);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, 0);

    $recontactInterval = [];


    if ($updateResultSet) {
      $optionValue = new CRM_Core_DAO_OptionValue();
      $optionValue->option_group_id = $this->_values['result_id'];
      $optionValue->delete();

      $params['result_id'] = $this->_values['result_id'];
    }
    else {
      $opGroupName = 'civicrm_survey_' . rand(10, 1000) . '_' . date('YmdHis');

      $optionGroup = new CRM_Core_DAO_OptionGroup();
      $optionGroup->name = $opGroupName;
      $optionGroup->label = $params['title'] . ' Response Set';
      $optionGroup->is_active = 1;
      $optionGroup->save();

      $params['result_id'] = $optionGroup->id;
    }

    foreach ($params['option_value'] as $k => $v) {
      if (strlen(trim($v))) {
        $optionValue = new CRM_Core_DAO_OptionValue();
        $optionValue->option_group_id = $params['result_id'];
        $optionValue->label = $params['option_label'][$k];
        $optionValue->name = CRM_Utils_String::titleToVar($params['option_label'][$k]);
        $optionValue->value = trim($v);
        $optionValue->weight = $params['option_weight'][$k];
        $optionValue->is_active = 1;

        if (CRM_Utils_Array::value('default_option', $params) &&
          $params['default_option'] == $k
        ) {
          $optionValue->is_default = 1;
        }

        $optionValue->save();

        if (CRM_Utils_Array::value($k, $params['option_interval'])) {
          $recontactInterval[$optionValue->label] = $params['option_interval'][$k];
        }
      }
    }

    $params['recontact_interval'] = serialize($recontactInterval);

    $surveyId = CRM_Campaign_BAO_Survey::create($params);

    if (CRM_Utils_Array::value('result_id', $this->_values) && !$updateResultSet) {
      $query = "SELECT COUNT(*) FROM civicrm_survey WHERE result_id = %1";
      $countSurvey = CRM_Core_DAO::singleValueQuery($query, [1 => [$this->_values['result_id'], 'Integer']]);

      // delete option group if no any survey is using it.
      if (!($countSurvey >= 1)) {
        CRM_Core_BAO_OptionGroup::del($this->_values['result_id']);
      }
    }



    // also update the ProfileModule tables
    $ufJoinParams = ['is_active' => 1,
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => $surveyId->id,
    ];

    // first delete all past entries
    if ($this->_surveyId) {
      CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);
    }
    if (CRM_Utils_Array::value('profile_id', $params)) {

      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    if (!is_a($surveyId, 'CRM_Core_Error')) {
      CRM_Core_Session::setStatus(ts('Survey %1 has been saved.', [1 => $params['title']]));
    }

    if ($this->_context == 'dialog') {
      $returnArray = ['returnSuccess' => TRUE];
      echo json_encode($returnArray);
      CRM_Utils_System::civiExit();
    }

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Survey.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add'));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey'));
    }
  }
}

