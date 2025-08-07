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
 * form to process actions on the field aspect of Price
 */
class CRM_Price_Form_Field extends CRM_Core_Form {

  public $_cdType;
  /**
   * Constants for number of options for data types of multiple option.
   */
  CONST NUM_OPTION = 11;

  /**
   * the custom set id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_sid;

  /**
   * The field id, used when editing the field
   *
   * @var int
   * @access protected
   */
  protected $_fid;

  /**
   * The extended component Id
   *
   * @var array
   * @access protected
   */
  protected $_extendComponentId;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // add custom field support
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }


    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this);
    $url = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$this->_sid}");
    $breadCrumb = [['title' => ts('Price Set Fields'),
        'url' => $url,
      ]];

    $this->_extendComponentId = [];
    $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $this->_sid, 'extends', 'id');
    if ($extendComponentId) {
      $this->_extendComponentId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $extendComponentId);
    }

    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    // when custom data is included in this page
	  $this->assign('customDataType', 'PriceField');
    if ($this->_fid) {
      $this->assign('entityID', $this->_fid);
      $this->assign('customDataSubType', $this->_fid);
    }

    if (CRM_Utils_Array::value("hidden_custom", $_POST)) {
      $this->set('type', 'PriceField');
      $this->set('subType', $this->_fid);
      $this->set('entityId', $this->_fid);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
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
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
    $defaults = [];

    // is it an edit operation ?
    if (isset($this->_fid)) {
      $params = ['id' => $this->_fid];
      $this->assign('id', $this->_fid);
      CRM_Price_BAO_Field::retrieve($params, $defaults);
      $this->_sid = $defaults['price_set_id'];

      // if text, retrieve price
      if ($defaults['html_type'] == 'Text') {
        $valueParams = ['price_field_id' => $this->_fid];


        CRM_Price_BAO_FieldValue::retrieve($valueParams, $fieldValues);
        foreach ($fieldValues as $key => $value) {
          if($key == 'is_active')continue;
          $defaults[$key] = $value;
        }

        // fix the display of the monetary value, CRM-4038

        $defaults['price'] = CRM_Utils_Money::format($defaults['amount'], NULL, '%a');
      }

      if(isset($defaults['max_value']) && $defaults['max_value'] >= 0){
        $defaults['allow_count'] = 1;
      }
    }
    else {
      $defaults['is_active'] = 1;
      for ($i = 1; $i <= self::NUM_OPTION; $i++) {
        $defaults['option_status[' . $i . ']'] = 1;
        $defaults['option_weight[' . $i . ']'] = $i;
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {

      $fieldValues = ['price_set_id' => $this->_sid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Price_DAO_Field', $fieldValues);
      $defaults['options_per_line'] = 1;
      $defaults['is_display_amounts'] = 1;
    }

    if(!empty($defaults['active_on'])){
      list($defaults['active_on'], $defaults['active_on_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('active_on', $defaults), 'activityDateTime');
    }
    if(!empty($defaults['expire_on'])){
      list($defaults['expire_on'], $defaults['expire_on_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('expire_on', $defaults), 'activityDateTime');
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
    // custom data related
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    // label
    $this->add('text', 'label', ts('Field Label'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_Field', 'label'), TRUE);

    // html_type
    $javascript = 'onchange="option_html_type(this.form)";';


    $htmlTypes = CRM_Price_BAO_Field::htmlTypes();

    $sel = $this->add('select', 'html_type', ts('Input Field Type'),
      $htmlTypes, TRUE, $javascript
    );

    // Text box for Participant Count for a field

    $eventComponentId = CRM_Core_Component::getComponentID('CiviEvent');

    $attributes = CRM_Core_DAO::getAttribute('CRM_Price_DAO_FieldValue');

    if (in_array($eventComponentId, $this->_extendComponentId)) {
      $this->add('text', 'count', ts('Participant Count'), $attributes['count']);
      $this->addRule('count', ts('Participant Count should be a positive number'), 'positiveInteger');

      $this->addElement('checkbox', 'allow_count', ts('Allow Changing Count'), NULL, ['onclick' => 'onChangeAllowCount();']);

      $this->addNumber('max_value', ts('Max Participants'), $attributes['max_value']+['min' => 0]);
      $this->addRule('max_value', ts('Please enter a valid Max Participants.'), 'positiveInteger');

      $this->add('textArea', 'description', ts('Description'), $attributes['description']);

      $this->assign('useForEvent', TRUE);
    }
    else {
      $this->assign('useForEvent', FALSE);
    }

    // price (for text inputs)
    $this->add('text', 'price', ts('Price'));
    $this->registerRule('price', 'callback', 'money', 'CRM_Utils_Rule');
    $this->addRule('price', ts('must be a monetary value'), 'money');

    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->freeze('html_type');
    }

    // form fields of Custom Option rows
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
      // label
      $attributes['label']['size'] = 25;
      $this->add('text', 'option_label[' . $i . ']', ts('Label'), $attributes['label']);

      // amount
      $attributes['amount']['size'] = 8;
      $this->add('text', 'option_amount[' . $i . ']', ts('Amount'), $attributes['amount']);
      $this->addRule('option_amount[' . $i . ']', ts('Please enter a valid amount for this field.'), 'money');

      if (in_array($eventComponentId, $this->_extendComponentId)) {
        // count
        $this->addNumber('option_count[' . $i . ']', ts('Participant Count'), ['min' => 0, 'size' => 4]);
        $this->addRule('option_count[' . $i . ']', ts('Please enter a valid Participants Count.'), 'positiveInteger');

        // max_value
        $this->addNumber('option_max_value[' . $i . ']', ts('Max Participants'), ['min' => 0, 'size' => 4]);
        $this->addRule('option_max_value[' . $i . ']', ts('Please enter a valid Max Participants.'), 'positiveInteger');

        // description
        $this->add('textArea', 'option_description[' . $i . ']', ts('Description'), ['rows' => 1, 'cols' => 20]);
      }

      // weight
      $attributes['weight']['size'] = 2;
      $this->add('text', 'option_weight[' . $i . ']', ts('Order'), $attributes['weight']);

      // is member only?
      $this->add('checkbox', 'option_member[' . $i . ']', ts('Member only?'));

      // is active ?
      $this->add('checkbox', 'option_status[' . $i . ']', ts('Active?'));

      $defaultOption[$i] = $this->createElement('radio', NULL, NULL, NULL, $i);

      //for checkbox handling of default option
      $this->add('checkbox', "default_checkbox_option[$i]", NULL);
    }
    //default option selection
    $this->addGroup($defaultOption, 'default_option');
    $_showHide->addToTemplate();

    // is_display_amounts
    $this->add('checkbox', 'is_display_amounts', ts('Display Amount?'));

    // weight
    $this->add('text', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_Field', 'weight'), TRUE);
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // checkbox / radio options per line
    $this->add('text', 'options_per_line', ts('Options Per Line'));
    $this->addRule('options_per_line', ts('must be a numeric value'), 'numeric');

    // help post, mask, attributes, javascript ?
    $this->addWysiwyg('help_post', ts('Field Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_Field', 'help_post')
    );

    // active_on
    $this->addDateTime('active_on', ts('Active On'), false, $date_options);

    // expire_on
    $this->addDateTime('expire_on', ts('Expire On'), false, $date_options);

    // is required ?
    $this->add('checkbox', 'is_required', ts('Required?'));

    // is member??
    $this->add('checkbox', 'is_member', ts('Member only?'));

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // add buttons
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
    // is public?

    $this->add('select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility());

    // add a form rule to check default value
    $this->addFormRule(['CRM_Price_Form_Field', 'formRule'], $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid);
      $this->addElement('button',
        'done',
        ts('Done'),
        ['onclick' => "location.href='$url'"]
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
  static function formRule($fields, $files, $form) {

    // all option fields are of type "money"
    $errors = [];

    /** Check the option values entered
     *  Appropriate values are required for the selected datatype
     *  Incomplete row checking is also required.
     */
    if (($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE) &&
      $fields['html_type'] == 'Text' && $fields['price'] == NULL
    ) {
      $errors['price'] = ts('Price is a required field');
    }
    //avoid the same price field label in Within PriceSet
    $priceFieldLabel = new CRM_Price_DAO_Field();
    $priceFieldLabel->label = $fields['label'];
    $priceFieldLabel->price_set_id = $form->_sid;

    $dupeLabel = FALSE;
    if ($priceFieldLabel->find(TRUE) && $form->_fid != $priceFieldLabel->id) {
      $dupeLabel = TRUE;
    }

    if ($dupeLabel) {
      $errors['label'] = ts('Name already exists in Database.');
    }

    if(($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE) && $fields['allow_count']){
      if(!is_numeric($fields['max_value']) || $fields['max_value'] == ''){
        $errors['max_value'] = ts('must be a numeric value');
      }
      elseif($fields['max_value'] < 0){
        $errors['max_value'] = ts("greater than or equal to '%1'", [1 => 0]);
      }
    }

    if ((is_numeric(CRM_Utils_Array::value('count', $fields)) &&
        CRM_Utils_Array::value('count', $fields) == 0
      ) &&
      (CRM_Utils_Array::value('html_type', $fields) == 'Text')
    ) {
      $errors['count'] = ts('Participant Count must be greater than zero.');
    }

    if ($form->_action & CRM_Core_Action::ADD) {

      if ($fields['html_type'] != 'Text') {
        $countemptyrows = 0;
        $_flagOption = $_rowError = 0;

        $_showHide = new CRM_Core_ShowHideBlocks('', '');

        for ($index = 1; $index <= self::NUM_OPTION; $index++) {

          $noLabel = $noAmount = $noWeight = 1;
          if (!empty($fields['option_label'][$index])) {
            $noLabel = 0;

            $duplicateIndex = CRM_Utils_Array::key($fields['option_label'][$index],
              $fields['option_label']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_label[{$index}]"] = ts('Duplicate label value');
              $_flagOption = 1;
            }
          }

          // allow for 0 value.
          if (!empty($fields['option_amount'][$index]) ||
            strlen($fields['option_amount'][$index]) > 0
          ) {
            $noAmount = 0;
          }

          if (!empty($fields['option_weight'][$index])) {
            $noWeight = 0;

            $duplicateIndex = CRM_Utils_Array::key($fields['option_weight'][$index],
              $fields['option_weight']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_weight[{$index}]"] = ts('Duplicate weight value');
              $_flagOption = 1;
            }
          }

          if ($noLabel && !$noAmount) {
            $errors["option_label[{$index}]"] = ts('Label cannot be empty.');
            $_flagOption = 1;
          }

          if (!$noLabel && $noAmount) {
            $errors["option_amount[{$index}]"] = ts('Amount cannot be empty.');
            $_flagOption = 1;
          }

          if ($noLabel && $noAmount) {
            $countemptyrows++;
            $_emptyRow = 1;
          }
          elseif (!empty($fields['option_max_value'][$index]) &&
            !empty($fields['option_count'][$index]) &&
            ($fields['option_count'][$index] > $fields['option_max_value'][$index])
          ) {
            $errors["option_max_value[{$index}]"] = ts('Participant count can not be greater than max participants.');
            $_flagOption = 1;
          }

          $showBlocks = 'optionField_' . $index;
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
          if ($index == self::NUM_OPTION) {
            $hideBlock = 'additionalOption';
            $_showHide->addHide($hideBlock);
          }

          $_flagOption = $_emptyRow = 0;
        }
        $_showHide->addToTemplate();

        if ($countemptyrows == 11) {
          $errors["option_label[1]"] = $errors["option_amount[1]"] = ts('Label and value cannot be empty.');
          $_flagOption = 1;
        }
      }
      elseif (!empty($fields['max_value']) &&
        !empty($fields['count']) &&
        ($fields['count'] > $fields['max_value'])
      ) {
        $errors["max_value"] = ts('Participant count can not be greater than max participants.');
      }

      // do not process if no option rows were submitted
      if (empty($fields['option_amount']) && empty($fields['option_label'])) {
        return TRUE;
      }

      if (empty($fields['option_name'])) {
        $fields['option_amount'] = [];
      }

      if (empty($fields['option_label'])) {
        $fields['option_label'] = [];
      }
    }

    if(!empty($fields['active_on']) && !empty($fields['expire_on'])){
      $active_on = CRM_Utils_Date::processDate($fields['active_on'], $fields['active_on_time']);
      $expire_on = CRM_Utils_Date::processDate($fields['expire_on'], $fields['expire_on_time']);
      if(strtotime($active_on) >= strtotime($expire_on)){
        $errors["active_on_time"] = ts('Expire time can\'t earlier than or as same as the active time.');
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
    $params = $this->controller->exportValues('Field');

    $params['name'] = CRM_Utils_String::titleToVar($params['label']);
    $params['is_display_amounts'] = CRM_Utils_Array::value('is_display_amounts', $params, FALSE);
    $params['is_required'] = CRM_Utils_Array::value('is_required', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_member'] = CRM_Utils_Array::value('is_member', $params, FALSE);
    $params['active_on'] = CRM_Utils_Date::processDate($params['active_on'], $params['active_on_time']);
    if(empty($params['active_on'])){
      $params['active_on'] = 'NULL';
    }
    $params['expire_on'] = CRM_Utils_Date::processDate($params['expire_on'], $params['expire_on_time']);
    if(empty($params['expire_on'])){
      $params['expire_on'] = 'NULL';
    }
    $params['visibility_id'] = CRM_Utils_Array::value('visibility_id', $params, FALSE);
    $params['count'] = CRM_Utils_Array::value('count', $params, FALSE);

    // need the FKEY - price set id
    $params['price_set_id'] = $this->_sid;


    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $fieldValues = ['price_set_id' => $this->_sid];
      $oldWeight = NULL;
      if ($this->_fid) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Field', $this->_fid, 'weight', 'id');
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_Field', $oldWeight, $params['weight'], $fieldValues);
    }

    // make value <=> name consistency.
    if (isset($params['option_name'])) {
      $params['option_value'] = $params['option_name'];
    }
    $params['is_enter_qty'] = CRM_Utils_Array::value('is_enter_qty', $params, FALSE);

    if ($params['html_type'] == 'Text') {
      // if html type is Text, force is_enter_qty on
      $params['is_enter_qty'] = 1;
      // modify params values as per the option group and option
      // value
      $params['option_amount'] = [1 => $params['price']];
      $params['option_label'] = [1 => $params['label']];
      $params['option_count'] = [1 => $params['count']];
      $params['option_max_value'] = [1 => $params['max_value']];
      $params['option_description'] = [1 => $params['description']];
      $params['option_weight'] = [1 => $params['weight']];
      $params['option_member'] = [1 => $params['is_member']];
      $params['option_is_active'] = [1 => 1];
      unset($params['max_value']);
    }

    $ids = [];

    if ($this->_fid) {
      $ids['id'] = $this->_fid;
    }

    $customFields = CRM_Core_BAO_CustomField::getFields('PriceField');
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params, $customFields, $this->_fid, 'PriceField');
    $priceField = CRM_Price_BAO_Field::create($params, $ids);

    if (!is_a($priceField, 'CRM_Core_Error')) {
      CRM_Core_Session::setStatus(ts('Price Field \'%1\' has been saved.', [1 => $priceField->label]));
    }
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another price set field.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=add&sid=' . $this->_sid));
    }
  }
}

