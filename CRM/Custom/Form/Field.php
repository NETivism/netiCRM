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
 * form to process actions on the field aspect of Custom
 */
class CRM_Custom_Form_Field extends CRM_Core_Form {

  /**
   * Constants for number of options for data types of multiple option.
   */
  CONST NUM_OPTION = 11;

  /**
   * the custom group id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_gid;

  /**
   * The field id, used when editing the field
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * The default custom data/input types, when editing the field
   *
   * @var array
   * @access protected
   */
  protected $_defaultDataType;

  /**
   * array of custom field values if update mode
   */
  protected $_values;

  /**
   * Array for valid combinations of data_type & html_type
   *
   * @var array
   * @static
   */
  private static $_dataTypeValues = NULL;
  private static $_dataTypeKeys = NULL;

  private static $_dataToHTML = [
    ['Text' => 'Text', 'Select' => 'Select',
      'Radio' => 'Radio', 'CheckBox' => 'CheckBox',
      'Multi-Select' => 'Multi-Select',
      'AdvMulti-Select' => 'AdvMulti-Select',
      'Autocomplete-Select' => 'Autocomplete-Select',
    ],
    ['Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'],
    ['Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'],
    ['Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'],
    ['TextArea' => 'TextArea', 'RichTextEditor' => 'RichTextEditor'],
    ['Date' => 'Select Date'],
    ['Radio' => 'Radio'],
    ['StateProvince' => 'Select State/Province', 'Multi-Select' => 'Multi-Select State/Province'],
    ['Country' => 'Select Country', 'Multi-Select' => 'Multi-Select Country'],
    ['File' => 'File'],
    ['Link' => 'Link'],
    ['ContactReference' => 'Autocomplete-Select'],
  ];

  private static $_dataToLabels = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    if (!(self::$_dataTypeKeys)) {
      self::$_dataTypeKeys = array_keys(CRM_Core_BAO_CustomField::dataType());
      self::$_dataTypeValues = array_values(CRM_Core_BAO_CustomField::dataType());
    }

    //custom group id
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this);

    if ($this->_gid) {
      $url = CRM_Utils_System::url('civicrm/admin/custom/group/field',
        "reset=1&action=browse&gid={$this->_gid}"
      );

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }

    //custom field id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    //get the values form db if update.
    $this->_values = [];
    if ($this->_id) {
      $params = ['id' => $this->_id];
      CRM_Core_BAO_CustomField::retrieve($params, $this->_values);
    }

    if (self::$_dataToLabels == NULL) {
      self::$_dataToLabels = [
        ['Text' => ts('Text'), 'Select' => ts('Select'),
          'Radio' => ts('Radio'), 'CheckBox' => ts('CheckBox'), 'Multi-Select' => ts('Multi-Select'),
          'AdvMulti-Select' => ts('Advanced Multi-Select'),
          'Autocomplete-Select' => ts('Autocomplete Select'),
        ],
        ['Text' => ts('Text'), 'Select' => ts('Select'),
          'Radio' => ts('Radio'),
        ],
        ['Text' => ts('Text'), 'Select' => ts('Select'),
          'Radio' => ts('Radio'),
        ],
        ['Text' => ts('Text'), 'Select' => ts('Select'),
          'Radio' => ts('Radio'),
        ],
        ['TextArea' => ts('TextArea'), 'RichTextEditor' => ts('WYSIWYG Editor')],
        ['Date' => ts('Select Date')],
        ['Radio' => ts('Radio')],
        ['StateProvince' => ts('Select State/Province'), 'Multi-Select' => ts('Multi-Select State/Province')],
        ['Country' => ts('Select Country'), 'Multi-Select' => ts('Multi-Select Country ')],
        ['File' => ts('Select File')],
        ['Link' => ts('Link')],
        ['ContactReference' => ts('Autocomplete Select')],
      ];
    }
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

    if ($this->_id) {
      $this->assign('id', $this->_id);
      $this->_gid = $defaults['custom_group_id'];

      //get the value for state or country
      if ($defaults['data_type'] == 'StateProvince' &&
        $stateId = CRM_Utils_Array::value('default_value', $defaults)
      ) {
        $defaults['default_value'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince', $stateId);
      }
      elseif ($defaults['data_type'] == 'Country' &&
        $countryId = CRM_Utils_Array::value('default_value', $defaults)
      ) {
        $defaults['default_value'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', $countryId);
      }

      if (CRM_Utils_Array::value('data_type', $defaults)) {
        $defaultDataType = array_search($defaults['data_type'],
          self::$_dataTypeKeys
        );
        $defaultHTMLType = array_search($defaults['html_type'],
          self::$_dataToHTML[$defaultDataType]
        );
        $defaults['data_type'] = ['0' => $defaultDataType,
          '1' => $defaultHTMLType,
        ];
        $this->_defaultDataType = $defaults['data_type'];
      }
      if ($defaults['attributes']) {
        if (preg_match('/data-parent=(\d+)/i', $defaults['attributes'], $matches)) {
          $defaults['parent'] = $matches[1];
        }
      }

      $defaults['option_type'] = 2;
    }
    else {
      $defaults['is_active'] = 1;
      $defaults['option_type'] = 1;
    }

    // set defaults for weight.
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      $defaults['option_status[' . $i . ']'] = 1;
      $defaults['option_weight[' . $i . ']'] = $i;
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = ['custom_group_id' => $this->_gid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_CustomField', $fieldValues);

      $defaults['text_length'] = 255;
      $defaults['note_columns'] = 60;
      $defaults['note_rows'] = 4;
      $defaults['is_view'] = 0;
      $defaults['is_searchable'] = 1;
    }

    if (CRM_Utils_Array::value('html_type', $defaults)) {
      $dontShowLink = substr($defaults['html_type'], -14) == 'State/Province' || substr($defaults['html_type'], -7) == 'Country' ? 1 : 0;
    }

    $config = CRM_Core_Config::singleton();
    if(!empty($config->externalMembershipIdFieldId) && $config->externalMembershipIdFieldId == $this->_id){
      $defaults['is_external_membership_id'] = 1;
    }

    if (isset($dontShowLink)) {
      $this->assign('dontShowLink', $dontShowLink);
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
    if ($this->_gid) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'title');
      CRM_Utils_System::setTitle($this->_title . ' - ' . ts('Custom Fields'));
    }

    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    $attributes = &CRM_Core_DAO::getAttribute('CRM_Core_DAO_CustomField');

    // label
    $this->add('text',
      'label',
      ts('Field Label'),
      $attributes['label'],
      TRUE
    );

    $dt = &self::$_dataTypeValues;
    $it = [];
    foreach ($dt as $key => $value) {
      $it[$key] = self::$_dataToLabels[$key];
    }
    $sel = &$this->addElement('hierselect',
      'data_type',
      ts('Data and Input Field Type'),
      'onclick="clearSearchBoxes();custom_option_html_type(this.form)"; onBlur="custom_option_html_type(this.form)";',
      '&nbsp;&nbsp;&nbsp;'
    );
    $sel->setOptions([$dt, $it]);
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->freeze('data_type');

      // only update can choose selection type
      if ($this->_values['html_type'] == 'Select') {
        $parentOptions = ['' => ts('- select Parent -')];
        $params = ['id' => $this->_values['custom_group_id']];
        $customGroup = [];
        CRM_Core_BAO_CustomGroup::retrieve($params, $customGroup);
        $customFields = CRM_Core_BAO_CustomField::getFields($customGroup['extends']);
        foreach($customFields as $fieldId => $field) {
          if ($field['html_type'] == 'Select' && $fieldId != $this->_values['id']) {
            $parentOptions[$fieldId] = $field['label'];
          }
        }
        $this->addSelect('parent', ts('Parent'), $parentOptions);
      }
    }
    $includeFieldIds = NULL;
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $includeFieldIds = $this->_values['id'];
    }
    $optionGroups = CRM_Core_BAO_CustomField::customOptionGroup($includeFieldIds);
    $emptyOptGroup = FALSE;
    if (empty($optionGroups)) {
      $emptyOptGroup = TRUE;
      $optionTypes = ['1' => ts('Create a new set of options')];
    }
    else {
      $optionTypes = ['1' => ts('Create a new set of options'),
        '2' => ts('Reuse an existing set'),
      ];

      $this->add('select',
        'option_group_id',
        ts('Multiple Choice Option Sets'),
        ['' => ts('- select -')] + $optionGroups
      );
    }

    $element = &$this->addRadio('option_type',
      ts('Option Type'),
      $optionTypes,
      ['onclick' => "showOptionSelect();"], '<br/>'
    );

    //if empty option group freeze the option type.
    if ($emptyOptGroup) {
      $element->freeze();
    }

    // form fields of Custom Option rows
    $defaultOption = [];
    $_showHide = new CRM_Core_ShowHideBlocks('', '');
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

      $optionAttributes = &CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue');
      // label
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

      // is active ?
      $this->add('checkbox', "option_status[$i]", ts('Active?'));

      $defaultOption[$i] = $this->createElement('radio', NULL, NULL, NULL, $i);

      //for checkbox handling of default option
      $this->add('checkbox', "default_checkbox_option[$i]", NULL);
    }

    //default option selection
    $this->addGroup($defaultOption, 'default_option');

    $_showHide->addToTemplate();

    // text length for alpha numeric data types
    $ele = $this->add('text',
      'text_length',
      ts('Database field length'),
      $attributes['text_length'],
      FALSE
    );
    $ele->freeze();
    $this->addRule('text_length', ts('Value should be a positive number'), 'integer');

    $this->add('text',
      'start_date_years',
      ts('Dates may be up to'),
      $attributes['start_date_years'],
      FALSE
    );
    $this->add('text',
      'end_date_years',
      ts('Dates may be up to'),
      $attributes['end_date_years'],
      FALSE
    );

    $this->addRule('start_date_years', ts('Value should be a positive number'), 'integer');
    $this->addRule('end_date_years', ts('Value should be a positive number'), 'integer');

    $this->add('select', 'date_format', ts('Date Format'),
      ['' => ts('- select -')] + CRM_Core_SelectValues::getDatePluginInputFormats()
    );

    $this->add('select', 'time_format', ts('Time'),
      ['' => ts('- none -')] + CRM_Core_SelectValues::getTimeFormats()
    );

    // for Note field
    $this->add('text',
      'note_columns',
      ts('Width (columns)') . ' ',
      $attributes['note_columns'],
      FALSE
    );
    $this->add('text',
      'note_rows',
      ts('Height (rows)') . ' ',
      $attributes['note_rows'],
      FALSE
    );

    $this->addRule('note_columns', ts('Value should be a positive number'), 'positiveInteger');
    $this->addRule('note_rows', ts('Value should be a positive number'), 'positiveInteger');

    // weight
    $this->add('text', 'weight', ts('Order'),
      $attributes['weight'],
      TRUE
    );
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // is required ?
    $this->add('checkbox', 'is_required', ts('Required?'));

    // checkbox / radio options per line
    $this->add('text', 'options_per_line', ts('Options Per Line'));
    $this->addRule('options_per_line', ts('must be a numeric value'), 'numeric');

    // default value, help pre, help post, mask, attributes, javascript ?
    $this->add('text', 'default_value', ts('Default Value'),
      $attributes['default_value']
    );
    $this->add('textarea', 'help_pre', ts('Field Pre Help'),
      $attributes['help_pre']
    );
    $this->add('textarea', 'help_post', ts('Field Post Help'),
      $attributes['help_post']
    );
    $this->add('text', 'mask', ts('Mask'),
      $attributes['mask']
    );

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // is active ?
    $this->add('checkbox', 'is_view', ts('View Only?'));

    // is searchable ?
    $this->addElement('checkbox',
      'is_searchable',
      ts('Is this Field Searchable?'),
      NULL, ['onclick' => "showSearchRange(this)"]
    );

    // is searchable by range?
    $searchRange = [];
    $searchRange[] = $this->createElement('radio', NULL, NULL, ts('Yes'), '1');
    $searchRange[] = $this->createElement('radio', NULL, NULL, ts('No'), '0');

    $this->addGroup($searchRange, 'is_search_range', ts('Search by Range?'));

    // add buttons
    $js = ['data' => 'click-once'];
    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
          'js' => $js,
        ],
        ['type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
          'js' => $js,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    // add a form rule to check default value
    $this->addFormRule(['CRM_Custom_Form_Field', 'formRule'], $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $url = CRM_Utils_System::url('civicrm/admin/custom/group/field', 'reset=1&action=browse&gid=' . $this->_gid);
      $this->addElement('button',
        'done',
        ts('Done'),
        ['onclick' => "location.href='$url'"]
      );
    }

    $type = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'extends');
    if($type == 'Membership'){
      $config = CRM_Core_Config::singleton();
      $current_external_membership_id_field_id = $config->externalMembershipIdFieldId;
      if(!empty($current_external_membership_id_field_id) && $current_external_membership_id_field_id != $this->_id){
        $sql = "SELECT f.label AS field_label, g.title AS group_title, f.custom_group_id FROM civicrm_custom_field f INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id WHERE f.id = %1";
        $param = [1 => [$current_external_membership_id_field_id, 'Integer']];
        $dao = CRM_Core_DAO::executeQuery($sql, $param);
        if($dao->fetch()){
          $this->assign('current_external_membership_id_field_title', $dao->field_label);
          $this->assign('current_external_membership_id_group_title', $dao->group_title);
          $this->assign('current_external_membership_id_group_id', $dao->custom_group_id);
        }
      }

      $this->addElement('checkbox',
        'is_external_membership_id',
        ts('External Membership ID Field')
      );
    }
  }

  /**
   * global validation rules for the form
   *
   * @param array  $fields   (referance) posted values of the form
   *
   * @return array    if errors then list of errors to be posted back to the form,
   *                  true otherwise
   * @static
   * @access public
   */
  static function formRule($fields, $files, $self) {
    $default = CRM_Utils_Array::value('default_value', $fields);

    $errors = [];

    //validate field label as well as name.
    $title = $fields['label'];
    $name = CRM_Utils_String::munge($title, '_', 64);
    $query = 'select count(*) from civicrm_custom_field where ( name like %1 OR label like %2 ) and id != %3';
    $fldCnt = CRM_Core_DAO::singleValueQuery($query, [1 => [$name, 'String'],
        2 => [$title, 'String'],
        3 => [(int)$self->_id, 'Integer'],
      ]);
    if ($fldCnt) {
      $errors['label'] = ts('Custom field \'%1\' already exists in Database.', [1 => $title]);
    }

    //checks the given custom field name doesnot start with digit
    if (!empty($title)) {
      // gives the ascii value
      $asciiValue = ord($title[0]);
      if ($asciiValue >= 48 && $asciiValue <= 57) {
        $errors['label'] = ts("Field's Name should not start with digit");
      }
    }

    // ensure that the label is not 'id'
    if (strtolower($title) == 'id') {
      $errors['label'] = ts("You cannot use 'id' as a field label.");
    }

    if (!isset($fields['data_type'][0]) || !isset($fields['data_type'][1])) {
      $errors['_qf_default'] = ts('Please enter valid - Data and Input Field Type.');
    }

    if ($default) {
      $dataType = self::$_dataTypeKeys[$fields['data_type'][0]];
      switch ($dataType) {
        case 'Int':
          if (!CRM_Utils_Rule::integer($default)) {
            $errors['default_value'] = ts('Please enter a valid integer as default value.');
          }
          break;

        case 'Float':
          if (!CRM_Utils_Rule::numeric($default)) {
            $errors['default_value'] = ts('Please enter a valid number as default value.');
          }
          break;

        case 'Money':
          if (!CRM_Utils_Rule::money($default)) {
            $errors['default_value'] = ts('Please enter a valid number value.');
          }
          break;

        case 'Link':
          if (!CRM_Utils_Rule::url($default)) {
            $errors['default_value'] = ts('Please enter a valid link.');
          }
          break;

        case 'Date':
          if (!CRM_Utils_Rule::date($default)) {
            $errors['default_value'] = ts('Please enter a valid date as default value using YYYY-MM-DD format. Example: 2004-12-31.');
          }
          break;

        case 'Boolean':
          if ($default != '1' && $default != '0') {
            $errors['default_value'] = ts('Please enter 1 (for Yes) or 0 (for No) if you want to set a default value.');
          }
          break;

        case 'Country':
          if (!empty($default)) {
            $query = "SELECT count(*) FROM civicrm_country WHERE name = %1 OR iso_code = %1";
            $params = [1 => [$fields['default_value'], 'String']];
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['default_value'] = ts('Invalid default value for country.');
            }
          }
          break;

        case 'StateProvince':
          if (!empty($default)) {
            $query = "
SELECT count(*) 
  FROM civicrm_state_province
 WHERE name = %1
    OR abbreviation = %1";
            $params = [1 => [$fields['default_value'], 'String']];
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['default_value'] = ts('The invalid default value for State/Province data type');
            }
          }
          break;

        case 'ContactReference':
          //FIX ME
          break;
      }
    }

    if (self::$_dataTypeKeys[$fields['data_type'][0]] == 'Date') {
      if (!$fields['date_format']) {
        $errors['date_format'] = ts('Please select a date format.');
      }
    }

    /** Check the option values entered
     *  Appropriate values are required for the selected datatype
     *  Incomplete row checking is also required.
     */
    $_flagOption = $_rowError = 0;
    $_showHide = new CRM_Core_ShowHideBlocks('', '');
    $dataType = self::$_dataTypeKeys[$fields['data_type'][0]];
    if (isset($fields['data_type'][1])) {
      $dataField = $fields['data_type'][1];
    }
    $optionFields = ['Select', 'Multi-Select', 'CheckBox', 'Radio', 'AdvMulti-Select'];

    if ($fields['option_type'] == 1) {
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
              if ($fields['option_label'][$start] == $fields['option_label'][$nextIndex] &&
                !empty($fields['option_label'][$nextIndex])
              ) {
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
        else {
          if (!strlen(trim($fields['option_value'][$i]))) {
            if (!$fields['option_value'][$i]) {
              $errors['option_value[' . $i . ']'] = ts('Option value cannot be empty');
              $_flagOption = 1;
            }
          }
        }

        if ($fields['option_value'][$i] && $dataType != 'String') {
          if ($dataType == 'Int') {
            if (!CRM_Utils_Rule::integer($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid integer.');
            }
          }
          elseif ($dataType == 'Money') {
            if (!CRM_Utils_Rule::money($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid money value.');
            }
          }
          else {
            if (!CRM_Utils_Rule::numeric($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid number.');
            }
          }
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
    }
    elseif (isset($dataField) &&
      in_array($dataField, $optionFields) &&
      !in_array($dataType, ['Boolean', 'Country', 'StateProvince'])
    ) {
      if (!$fields['option_group_id']) {
        $errors['option_group_id'] = ts('You must select a Multiple Choice Option set if you chose Reuse an existing set.');
      }
      else {
        $query = "
SELECT count(*)
FROM   civicrm_custom_field
WHERE  data_type != %1
AND    option_group_id = %2";
        $params = [1 => [self::$_dataTypeKeys[$fields['data_type'][0]],
            'String',
          ],
          2 => [$fields['option_group_id'], 'Integer'],
        ];
        $count = CRM_Core_DAO::singleValueQuery($query, $params);
        if ($count > 0) {
          $errors['option_group_id'] = ts('The data type of the multiple choice option set you\'ve selected does not match the data type assigned to this field.');
        }
      }
    }


    $assignError = new CRM_Core_Page();
    if ($_rowError) {
      $_showHide->addToTemplate();
      $assignError->assign('optionRowError', $_rowError);
    }
    else {
      if (isset($fields['data_type'][1])) {
        switch (self::$_dataToHTML[$fields['data_type'][0]][$fields['data_type'][1]]) {
          case 'Radio':
            $_fieldError = 1;
            $assignError->assign('fieldError', $_fieldError);
            break;

          case 'Checkbox':
            $_fieldError = 1;
            $assignError->assign('fieldError', $_fieldError);
            break;

          case 'Select':
            $_fieldError = 1;
            $assignError->assign('fieldError', $_fieldError);
            break;

          default:
            $_fieldError = 0;
            $assignError->assign('fieldError', $_fieldError);
        }
      }

      for ($idx = 1; $idx <= self::NUM_OPTION; $idx++) {
        $showBlocks = 'optionField_' . $idx;
        if (!empty($fields['option_label'][$idx])) {
          $_showHide->addShow($showBlocks);
        }
        else {
          $_showHide->addHide($showBlocks);
        }
      }
      $_showHide->addToTemplate();
    }

    // we can not set require and view at the same time.
    if (CRM_Utils_Array::value('is_required', $fields) &&
      CRM_Utils_Array::value('is_view', $fields)
    ) {
      $errors['is_view'] = ts('Can not set this field Required and View Only at the same time.');
    }


    if (CRM_Utils_Array::value('is_searchable', $fields)) {
      $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $self->_gid, 'table_name');
      $existsIndexCount = CRM_Core_BAO_SchemaHandler::checkIndexCountByTable($tableName);
      if ($existsIndexCount >= CRM_Core_DAO::MAX_KEYS_PER_TABLE) {
        $errors['is_searchable'] = ts('You can not add more than %1 searchable fields in this custom group.', CRM_Core_DAO::MAX_KEYS_PER_TABLE);
      }
    }

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
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $dataTypeKey = $this->_defaultDataType[0];
      $params['data_type'] = self::$_dataTypeKeys[$this->_defaultDataType[0]];
      $params['html_type'] = self::$_dataToHTML[$this->_defaultDataType[0]][$this->_defaultDataType[1]];
    }
    else {
      $dataTypeKey = $params['data_type'][0];
      $params['html_type'] = self::$_dataToHTML[$params['data_type'][0]][$params['data_type'][1]];
      $params['data_type'] = self::$_dataTypeKeys[$params['data_type'][0]];
    }

    $type = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'extends');

    if($type == 'Membership'){
      $config = CRM_Core_Config::singleton();
      $current_external_membership_id_field_id = $config->externalMembershipIdFieldId;

      if(empty($this->_id)){
        $this_field_id = CRM_Core_DAO::singleValueQuery("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'civicrm_custom_field'");
      }
      else{
        $this_field_id = $this->_id;
      }

      if($current_external_membership_id_field_id != $this_field_id ){
        $add['externalMembershipIdFieldId'] = $this_field_id;
      }
      else{
        if(!$this->controller->exportValues('is_external_membership_id')){
          $add['externalMembershipIdFieldId'] = FALSE;
        }
      }
      CRM_Core_BAO_ConfigSetting::add($add);

      // also delete the CRM_Core_Config key from the database
      $cache = &CRM_Utils_Cache::singleton();
      $cache->delete('CRM_Core_Config');
    }

    //fix for 'is_search_range' field.
    if (in_array($dataTypeKey, [1, 2, 3, 5])) {
      if (!CRM_Utils_Array::value('is_searchable', $params)) {
        $params['is_search_range'] = 0;
      }
    }
    else {
      $params['is_search_range'] = 0;
    }

    // fix for CRM-316
    $oldWeight = NULL;
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $fieldValues = ['custom_group_id' => $this->_gid];
      if ($this->_id) {
        $oldWeight = $this->_values['weight'];
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_CustomField', $oldWeight, $params['weight'], $fieldValues);
    }

    //store the primary key for State/Province or Country as default value.
    if (strlen(trim($params['default_value']))) {
      switch ($params['data_type']) {
        case 'StateProvince':
          $fieldStateProvince = mb_strtolower($params['default_value'], 'UTF-8');
          $query = "
SELECT id
  FROM civicrm_state_province 
 WHERE LOWER(name) = '$fieldStateProvince' 
    OR abbreviation = '$fieldStateProvince'";
          $dao = &CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
          if ($dao->fetch()) {
            $params['default_value'] = $dao->id;
          }
          break;

        case 'Country':
          $fieldCountry = mb_strtolower($params['default_value'], 'UTF-8');
          $query = "
SELECT id
  FROM civicrm_country
 WHERE LOWER(name) = '$fieldCountry' 
    OR iso_code = '$fieldCountry'";
          $dao = &CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
          if ($dao->fetch()) {
            $params['default_value'] = $dao->id;
          }
          break;
      }
    }


    // need the FKEY - custom group id
    $params['custom_group_id'] = $this->_gid;

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
      if ($params['parent']) {
        $params['attributes'] = 'data-parent='.$params['parent'];
        unset($params['parent']);
      }
      else {
        $params['attributes'] = 'null';
      }
    }

    $customField = CRM_Core_BAO_CustomField::create($params);

    // reset the cache

    CRM_Core_BAO_Cache::deleteGroup('contact fields');

    // reset memcache
    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('*CRM_Core_DAO_CustomGroup*');

    CRM_Core_Session::setStatus(ts('Your custom field \'%1\' has been saved.',
        [1 => $customField->label]
      ));

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another custom field.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field/add',
          'reset=1&action=add&gid=' . $this->_gid
        ));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field',
          'reset=1&action=browse&gid=' . $this->_gid
        ));
    }
  }
}

