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
 * This is our base form. It is part of the Form/Controller/StateMachine
 * trifecta. Each form is associated with a specific state in the state
 * machine. Each form can also operate in various modes
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_Form extends HTML_QuickForm_Page {

  /**
   * Override public variable for HTML_QuickForm_Page
   *
   * @var      CRM_Core_Controller
   * @access   public
   */
  public $controller = NULL;

  /**
   * The state object that this form belongs to
   * @var object
   */
  protected $_state;

  /**
   * The name of this form
   * @var string
   */
  protected $_name;

  /**
   * The title of this form
   * @var string
   */
  protected $_title = NULL;

  /**
   * The options passed into this form
   * @var mixed
   */
  protected $_options = NULL;

  /**
   * The mode of operation for this form
   * @var int
   */
  protected $_action;

  /**
   * the renderer used for this form
   *
   * @var object
   */
  protected $_renderer;

  /**
   * cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  protected static $_template;

  /**
   * The count of submissions in the same form
   * @var int
   */
  protected $_submissionCount;

  /**
   * constants for attributes for various form elements
   * attempt to standardize on the number of variations that we
   * use of the below form elements
   *
   * @var const string
   */
  public const ATTR_SPACING = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

  /**
   * All checkboxes are defined with a common prefix. This allows us to
   * have the same javascript to check / clear all the checkboxes etc
   * If u have multiple groups of checkboxes, you will need to give them different
   * ids to avoid potential name collision
   *
   * @var const string / int
   */
  public const CB_PREFIX = 'mark_x_', CB_PREFIY = 'mark_y_', CB_PREFIZ = 'mark_z_', CB_PREFIX_LEN = 7;

  /**
   * Constructor for the basic form page.
   *
   * We should not use QuickForm directly. This class provides a lot
   * of default convenient functions, rules and buttons.
   *
   * @param CRM_Core_State|null $state State associated with this form.
   * @param int $action The mode the form is operating in (e.g., CRM_Core_Action::NONE).
   * @param string $method The type of http method used (GET/POST).
   * @param string|null $name The name of the form if different from class name.
   */
  public function __construct(
    $state = NULL,
    $action = CRM_Core_Action::NONE,
    $method = 'post',
    $name = NULL
  ) {

    if ($name) {
      $this->_name = $name;
    }
    else {
      $this->_name = CRM_Utils_String::getClassName(CRM_Utils_System::getClassName($this));
    }

    parent::__construct($this->_name, $method);

    $this->_state = $state;
    $this->_action = (int) $action;

    $this->registerRules();

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
    }
  }

  public static function generateID() {
  }

  /**
   * Register all the standard rules that most forms potentially use.
   */
  public function registerRules() {
    static $rules = ['title', 'longTitle', 'variable', 'qfVariable',
      'phone', 'integer', 'query',
      'url', 'wikiURL',
      'domain', 'numberOfDigit',
      'date', 'currentDate',
      'asciiFile', 'htmlFile', 'utf8File', 'imageFile',
      'objectExists', 'optionExists', 'postalCode', 'money', 'positiveInteger',
      'xssString', 'fileExists', 'autocomplete', 'validContact', 'alphanumeric'
    ];

    foreach ($rules as $rule) {
      $this->registerRule($rule, 'callback', $rule, 'CRM_Utils_Rule');
    }
  }

  /**
   * Simple easy to use wrapper around addElement. Deal with
   * simple validation rules.
   *
   * @param string $type The type of html element to be added.
   * @param string $name The name of the html element.
   * @param string $label The display label for the html element.
   * @param mixed $attributes Attributes used for this element.
   * @param bool $required Whether this is a required field.
   * @param mixed $javascript JavaScript associated with the element.
   *
   * @return \HTML_QuickForm_element The created html element.
   */
  public function &add(
    $type,
    $name,
    $label = '',
    $attributes = '',
    $required = FALSE,
    $javascript = NULL
  ) {
    $element = $this->addElement($type, $name, $label, $attributes, $javascript);
    if (HTML_QuickForm::isError($element)) {
      CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
    }

    if ($required) {
      $error = $this->addRule($name, ts('%1 is a required field.', [1 => $label]), 'required');
      if (HTML_QuickForm::isError($error)) {
        CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
      }
    }

    return $element;
  }

  /**
   * This function is called before buildForm. Any pre-processing that
   * needs to be done for buildForm should be done here.
   *
   * This is a virtual function and should be redefined if needed.
   */
  public function preProcess() {
  }

  /**
   * This function is called after the form is validated. Any
   * processing of form state etc should be done in this function.
   * Typically all processing associated with a form should be done
   * here and relevant state should be stored in the session.
   *
   * This is a virtual function and should be redefined if needed.
   */
  public function postProcess() {
  }

  /**
   * This function is just a wrapper, so that we can call all the hook functions.
   */
  public function mainProcess() {
    // Process blob images from CKeditor fields before data persistence
    // Convert temporary blob URLs to permanent file storage
    $blobImagesProcessResult = $this->processBlobImages();

    CRM_Utils_Hook::preSave(get_class($this), $this);

    // before postProcess, count submission at form object
    if (empty($this->_submissionCount)) {
      $this->_submissionCount = $this->get('submissionCount');
    }
    if ($this->_preventMultipleSubmission) {
      $this->_submissionCount++;
      $this->set('submissionCount', $this->_submissionCount);
      $this->preventMultipleSubmission();
    }

    // everything fine.
    $this->postProcess();
    CRM_Utils_Hook::postProcess(get_class($this), $this);
  }

  /**
   * This virtual function is used to build the form. It replaces the
   * buildForm associated with QuickForm_Page. This allows us to put
   * preProcess in front of the actual form building routine.
   */
  public function buildQuickForm() {
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements.
   *
   * @return array reference to the array of default values
   */
  public function setDefaultValues() {
  }

  /**
   * This is a virtual function that adds group and global rules to
   * the form. Keeping it distinct from the form to keep code small
   * and localized in the form building code.
   */
  public function addRules() {
  }

  public function validate() {
    $error = parent::validate();

    $hookErrors = CRM_Utils_Hook::validate(
      get_class($this),
      $this->_submitValues,
      $this->_submitFiles,
      $this
    );
    if ($hookErrors !== TRUE && is_array($hookErrors) && !empty($hookErrors)) {
      $this->_errors += $hookErrors;
    }

    return (0 == count($this->_errors));
  }

  /**
   * Core function that builds the form. We redefine this function
   * here and expect all CRM forms to build their form in the function
   * buildQuickForm.
   *
   */
  public function buildForm() {
    $this->_formBuilt = TRUE;

    $initPage = $this->get('initPage');
    if (empty($initPage)) {
      $this->set('initPage', $_SERVER['REQUEST_URI']);
    }

    $this->preProcess();

    // call the preprocess hook
    CRM_Utils_Hook::preProcess(get_class($this), $this);

    $this->assign('translatePermission', CRM_Core_Permission::check('translate CiviCRM'));

    if ($this->controller->_key &&
      $this->controller->_print != CRM_Core_Smarty::PRINT_NOFORM
    ) {
      $this->addElement('hidden', 'qfKey', $this->controller->_key);
      $this->assign('qfKey', $this->controller->_key);
    }

    // #16953, hack for page based form session
    global $pageKey;
    if (!empty($pageKey)) {
      $this->addElement('hidden', 'pageKey', $pageKey);
      $this->assign('pageKey', $pageKey);
      $session = CRM_Core_Session::singleton();
      $session->set('qfKey', $this->controller->_key, $pageKey);
    }

    $this->buildQuickForm();

    $defaults = $this->setDefaultValues();
    unset($defaults['qfKey']);
    unset($defaults['pageKey']);

    if (!empty($defaults)) {
      $this->setDefaults($defaults);
    }

    // call the form hook
    // also call the hook function so any modules can set thier own custom defaults
    // the user can do both the form and set default values with this hook
    CRM_Utils_Hook::buildForm(
      get_class($this),
      $this
    );

    $this->addRules();
  }

  public function addButton($buttonType, $label, $attributes = NULL) {
    $attributes = !empty($attributes) ? $attributes : [];
    if (!empty($attributes['class'])) {
      $attributes['class'] .= ' form-submit';
    }
    else {
      $attributes['class'] = 'form-submit';
    }
    $buttonName = $this->getButtonName($buttonType);
    return $this->add('submit', $buttonName, $label, $attributes);
  }

  /**
   * Add default Next / Back buttons
   *
   * @param array   array of associative arrays in the order in which the buttons should be
   *                displayed. The associate array has 3 fields: 'type', 'name' and 'isDefault'
   *                The base form class will define a bunch of static arrays for commonly used
   *                formats
   *
   * @return void
   *
   * @access public
   *
   */
  public function addButtons($params) {
    $prevnext = [];
    $spacing = [];
    foreach ($params as $button) {
      $js = CRM_Utils_Array::value('js', $button);
      $isDefault = CRM_Utils_Array::value('isDefault', $button, FALSE);
      if ($isDefault) {
        $attrs = ['class' => 'form-submit default'];
      }
      else {
        $attrs = ['class' => 'form-submit'];
      }

      if ($js) {
        $attrs = array_merge($js, $attrs);
      }

      if ($button['type'] === 'reset') {
        $prevnext[] = &$this->createElement($button['type'], 'reset', $button['name'], $attrs);
      }
      else {
        if (CRM_Utils_Array::value('subName', $button)) {
          $buttonName = $this->getButtonName($button['type'], $button['subName']);
        }
        else {
          $buttonName = $this->getButtonName($button['type']);
        }

        if (in_array($button['type'], ['next', 'upload']) && $button['name'] === 'Save') {
          $attrs = array_merge($attrs, (['accesskey' => 'S']));
        }
        $prevnext[] = $this->createElement('submit', $buttonName, $button['name'], $attrs);
      }
      if (CRM_Utils_Array::value('isDefault', $button)) {
        $this->setDefaultAction($button['type']);
      }

      // if button type is upload, set the enctype
      if ($button['type'] == 'upload') {
        $this->updateAttributes(['enctype' => 'multipart/form-data']);
        $this->setMaxFileSize();
      }

      // hack - addGroup uses an array to express variable spacing, read from the last element
      $spacing[] = CRM_Utils_Array::value('spacing', $button, self::ATTR_SPACING);
    }
    return $this->addGroup($prevnext, 'buttons', '', $spacing, FALSE);
  }

  /**
   * getter function for Name
   *
   * @return string The form name.
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * getter function for State
   *
   * @return \CRM_Core_State The state object.
   */
  public function &getState() {
    return $this->_state;
  }

  /**
   * getter function for StateType
   *
   * @return int The state type.
   */
  public function getStateType() {
    return $this->_state->getType();
  }

  /**
   * getter function for title. Should be over-ridden by derived class.
   *
   * @return string The form title.
   */
  public function getTitle() {
    return $this->_title ? $this->_title : ts('ERROR: Title is not Set');
  }

  /**
   * setter function for title.
   *
   * @param string $title The title of the form.
   */
  public function setTitle($title) {
    $this->_title = $title;
  }

  /**
   * Setter function for options.
   *
   * @param mixed $options The options for the form.
   */
  public function setOptions($options) {
    $this->_options = $options;
  }

  /**
   * getter function for link.
   *
   * @return string The link URL.
   */
  public function getLink() {
    $config = CRM_Core_Config::singleton();
    return CRM_Utils_System::url(
      $_GET[$config->userFrameworkURLVar],
      '_qf_' . $this->_name . '_display=true'
    );
  }

  /**
   * boolean function to determine if this is a one form page.
   *
   * @return bool True if simple form.
   */
  public function isSimpleForm() {
    return $this->_state->getType() & (CRM_Core_State::START | CRM_Core_State::FINISH);
  }

  /**
   * getter function for Form Action.
   *
   * @return string The form action URL.
   */
  public function getFormAction() {
    return $this->_attributes['action'];
  }

  /**
   * setter function for Form Action.
   *
   * @param string $action The form action URL.
   */
  public function setFormAction($action) {
    $this->_attributes['action'] = $action;
  }

  /**
   * render form and return contents.
   *
   * @return array The form contents as an associative array.
   */
  public function toSmarty() {
    $renderer = &$this->getRenderer();
    $this->accept($renderer);
    $content = $renderer->toArray();
    $content['formName'] = $this->getName();
    return $content;
  }

  /**
   * getter function for renderer. If renderer is not set
   * create one and initialize it.
   *
   * @return \CRM_Core_Form_Renderer The renderer object.
   */
  public function &getRenderer() {
    if (!isset($this->_renderer)) {
      $this->_renderer = &CRM_Core_Form_Renderer::singleton();
    }
    return $this->_renderer;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string The template file name.
   */
  public function getTemplateFileName() {

    $ext = new CRM_Core_Extensions();
    if ($ext->isExtensionClass(CRM_Utils_System::getClassName($this))) {
      $filename = $ext->getTemplateName(CRM_Utils_System::getClassName($this));
      $tplname = $ext->getTemplatePath(CRM_Utils_System::getClassName($this)) . DIRECTORY_SEPARATOR . $filename;
    }
    else {
      $tplname = str_replace(
        '_',
        DIRECTORY_SEPARATOR,
        CRM_Utils_System::getClassName($this)
      ) . '.tpl';
    }
    return $tplname;
  }

  /**
   * A wrapper for getTemplateFileName that includes calling the hook to
   * prevent us from having to copy & paste the logic of calling the hook
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'form', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * Error reporting mechanism.
   *
   * @param string $message Error Message.
   * @param int|null $code Error Code.
   * @param \CRM_Core_DAO|null $dao A data access object on which we perform a rollback if non-empty.
   */
  public function error($message, $code = NULL, $dao = NULL) {
    if ($dao) {
      $dao->query('ROLLBACK');
    }

    $error = &CRM_Core_Error::singleton();

    $error->push($code, $message);
  }

  /**
   * Store the variable with the value in the form scope.
   *
   * @param string $name Name of the variable.
   * @param mixed $value Value of the variable.
   */
  public function set($name, $value) {
    $this->controller->set($name, $value);
  }

  /**
   * Get the variable from the form scope.
   *
   * @param string $name Name of the variable.
   *
   * @return mixed The value of the variable.
   */
  public function get($name) {
    return $this->controller->get($name);
  }

  /**
   * getter for action.
   *
   * @return int The form action mode.
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * setter for action.
   *
   * @param int $action The mode we want to set the form.
   */
  public function setAction($action) {
    $this->_action = $action;
  }

  /**
   * assign value to name in template.
   *
   * @param array|string $var Name of variable or associative array of variables.
   * @param mixed $value Value of variable.
   */
  public function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /**
   * assign value to name in template by reference.
   *
   * @param string $var Name of variable.
   * @param mixed $value Value of variable.
   */
  public function assign_by_ref($var, &$value) {
    self::$_template->assign_by_ref($var, $value);
  }

  /**
   * check if variables assigned.
   *
   * @param string $var Name of variable.
   * @param mixed $value Value of variable, if value will check equality.
   */
  public function isAssigned($var, $value = NULL) {
    self::$_template->isAssigned($var, $value);
  }

  /**
   * Add a text field to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param mixed $attributes Attributes for the field.
   * @param bool $required Whether the field is required.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addTextfield($name, $label, $attributes = NULL, $required = FALSE) {
    return $this->add('text', $name, $label, $attributes, $required);
  }

  /**
   * Add a checkbox to the form.
   *
   * @param string $name The name of the checkbox.
   * @param string $label The label for the checkbox.
   * @param mixed $attributes Attributes for the checkbox.
   * @param bool $required Whether the checkbox is required.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addCbx($name, $label, $attributes = NULL, $required = FALSE) {
    return $this->add('checkbox', $name, $label, $attributes, $required);
  }

  /**
   * Add a radio button group to the form.
   *
   * @param string $name The name of the radio group.
   * @param string $title The title for the radio group.
   * @param array $values The values for the radio buttons.
   * @param mixed $attributes Attributes for the radio buttons.
   * @param string|null $separator Separator between radio buttons.
   * @param bool $required Whether the group is required.
   *
   * @return \HTML_QuickForm_group The created element group.
   */
  public function addRadio($name, $title, &$values, $attributes = NULL, $separator = NULL, $required = FALSE) {
    $options = [];
    $attributes = $attributes ? $attributes : [];
    $allowClear = !empty($attributes['allowClear']) ? TRUE : FALSE;
    unset($attributes['allowClear']);

    foreach ($values as $key => $var) {
      $options[] = &$this->createElement('radio', NULL, NULL, $var, $key, $attributes);
    }
    $group = &$this->addGroup($options, $name, $title, $separator);
    if ($required) {
      $this->addRule($name, ts('%1 is a required field.', [1 => $title]), 'required');
    }
    if ($allowClear) {
      $group->setAttribute('allowClear', TRUE);
    }
    return $group;
  }

  /**
   * Add a Yes/No radio group to the form.
   *
   * @param string $id The name of the group.
   * @param string $title The title for the group.
   * @param bool|null $dontKnow Whether to include a "Don't Know" option.
   * @param bool|null $required Whether the group is required.
   * @param mixed $attribute Attributes for the radio buttons.
   *
   * @return \HTML_QuickForm_group The created element group.
   */
  public function addYesNo($id, $title, $dontKnow = NULL, $required = NULL, $attribute = NULL) {
    $choice = [];
    $choice[] = &$this->createElement('radio', NULL, '11', ts('Yes'), '1', $attribute);
    $choice[] = &$this->createElement('radio', NULL, '11', ts('No'), '0', $attribute);
    if ($dontKnow) {
      $choice[] = &$this->createElement('radio', NULL, '22', ts("Don't Know"), '2', $attribute);
    }
    $group = $this->addGroup($choice, $id, $title);

    if ($required) {
      $this->addRule($id, ts('%1 is a required field.', [1 => $title]), 'required');
    }
    return $group;
  }

  /**
   * Add a checkbox group to the form.
   *
   * @param string $id The name of the group.
   * @param string $title The title for the group.
   * @param array $values The values for the checkboxes.
   * @param bool|null $other Whether to include an "Other" text field.
   * @param mixed $attributes Attributes for the checkboxes.
   * @param bool|null $required Whether the group is required.
   * @param string|null $javascriptMethod JavaScript method for the checkboxes.
   * @param string $separator Separator between checkboxes.
   * @param bool $flipValues Whether to flip keys and values.
   *
   * @return \HTML_QuickForm_group The created element group.
   */
  public function addCheckBox(
    $id,
    $title,
    $values,
    $other = NULL,
    $attributes = NULL,
    $required = NULL,
    $javascriptMethod = NULL,
    $separator = '<br />',
    $flipValues = FALSE
  ) {
    $options = [];

    if ($javascriptMethod) {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = &$this->createElement('checkbox', $var, NULL, $key, $javascriptMethod);
        }
        else {
          $options[] = &$this->createElement('checkbox', $key, NULL, $var, $javascriptMethod);
        }
      }
    }
    else {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = $this->createElement('checkbox', $var, NULL, $key);
        }
        else {
          $options[] = $this->createElement('checkbox', $key, NULL, $var);
        }
      }
    }

    $ele = $this->addGroup($options, $id, $title, $separator);

    if ($other) {
      $this->addElement('text', $id . '_other', ts('Other'), $attributes[$id . '_other']);
    }

    if ($required) {
      $this->addRule(
        $id,
        ts('%1 is a required field.', [1 => $title]),
        'required'
      );
    }
    return $ele;
  }

  /**
   * Reset the values of the form in the controller container.
   */
  public function resetValues() {
    $data = &$this->controller->container();
    $data['values'][$this->_name] = [];
  }

  /**
   * simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit.
   *
   * @param string $title Title of the main button.
   * @param string $nextType Button type for the next button.
   * @param string $backType Button type for the back button.
   * @param bool $submitOnce If true, add javascript to next button submit which prevents it from being clicked more than once.
   *
   * @return \HTML_QuickForm_group The created button group.
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $buttons = [];
    if ($backType != NULL) {
      $buttons[] = ['type' => $backType,
        'name' => ts('Previous'),
      ];
    }
    if ($nextType != NULL) {
      $nextButton = ['type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ];
      if ($submitOnce) {
        $nextButton['js'] = ['data' => 'submit-once'];
      }
      $buttons[] = $nextButton;
    }
    return $this->addButtons($buttons);
  }

  /**
   * Add a date range field to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param string $dateFormat The date format type.
   * @param bool $required Whether the field is required.
   */
  public function addDateRange($name, $label = 'From', $dateFormat = 'searchDate', $required = FALSE) {
    $this->addDate($name . '_from', ts($label), $required, ['formatType' => $dateFormat]);
    $this->addDate($name . '_to', ts('To'), $required, ['formatType' => $dateFormat]);
  }

  /**
   * Add a select field with values from an option group.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param string|null $prefix Prefix for the field name.
   * @param bool|null $required Whether the field is required.
   * @param mixed $extra Extra attributes for the field.
   * @param string $select The default select label.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addSelectByOption($name, $label, $prefix = NULL, $required = NULL, $extra = NULL, $select = '- select -') {
    if ($prefix) {
      $ele = $this->addElement(
        'select',
        $name . '_id' . $prefix,
        $label,
        ['' => $select] + CRM_Core_OptionGroup::values($name),
        $extra
      );
      if ($required) {
        $this->addRule($name . '_id' . $prefix, ts('Please select %1', [1 => $label]), 'required');
      }
      return $ele;
    }
    else {
      $ele = $this->addElement(
        'select',
        $name . '_id',
        $label,
        ['' => $select] + CRM_Core_OptionGroup::values($name),
        $extra
      );
      if ($required) {
        $this->addRule($name . '_id', ts('Please select %1', [1 => $label]), 'required');
      }
      return $ele;
    }
  }

  /**
   * Add a textarea to the form.
   *
   * @param string $name The name of the textarea.
   * @param string $label The label for the textarea.
   * @param mixed $attributes Attributes for the textarea.
   * @param bool|null $required Whether the textarea is required.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addTextarea($name, $label, $attributes = NULL, $required = NULL) {
    return $this->add('textarea', $name, $label, $attributes, $required);
  }

  /**
   * Add a WYSIWYG editor to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param mixed $attributes Attributes for the field.
   * @param bool $forceTextarea Whether to force a plain textarea.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addWysiwyg($name, $label, $attributes, $forceTextarea = FALSE) {
    // 1. Get configuration option for editor (tinymce, ckeditor, pure textarea)
    // 2. Based on the option, initialise proper editor

    $editor = strtolower(CRM_Utils_Array::value(
      CRM_Core_BAO_Preferences::value('editor_id'),
      CRM_Core_PseudoConstant::wysiwygEditor()
    ));
    if (!$editor || $forceTextarea) {
      $editor = 'textarea';
    }
    // The 'CKEditor4' label (ref #45339) maps to the 'ckeditor' element type
    // registered in packages/HTML/QuickForm.php; engine remains unchanged.
    if ($editor == 'ckeditor4') {
      $editor = 'ckeditor';
    }
    if ($editor == 'joomla default editor') {
      $editor = 'joomlaeditor';
    }

    $ele = $this->addElement($editor, $name, $label, $attributes);
    $this->assign('editor', $editor);
    return $ele;
  }

  /**
   * Add a country select field to the form.
   *
   * @param string $id The name of the field.
   * @param string $title The title for the field.
   * @param bool|null $required Whether the field is required.
   * @param mixed $extra Extra attributes for the field.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addCountry($id, $title, $required = NULL, $extra = NULL) {
    $ele = $this->addElement(
      'select',
      $id,
      $title,
      ['' => ts('- select -')] + CRM_Core_PseudoConstant::country(),
      $extra
    );
    if ($required) {
      $this->addRule($id, ts('Please select %1', [1 => $title]), 'required');
    }
    return $ele;
  }

  /**
   * Add a select field to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param array $options The options for the select field.
   * @param mixed $attributes Attributes for the field.
   * @param bool|null $required Whether the field is required.
   * @param mixed $others Other parameters.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addSelect($name, $label, $options, $attributes = NULL, $required = NULL, $others = NULL) {
    $ele = $this->addElement('select', $name, $label, $options, $attributes);

    if ($required) {
      $this->addRule($name, ts('Please select %1', [1 => $label]), 'required');
    }
    return $ele;
  }

  /**
   * Add a number field to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param mixed $attributes Attributes for the field.
   * @param bool|null $required Whether the field is required.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addNumber($name, $label, $attributes = NULL, $required = NULL) {
    return $this->add('number', $name, $label, $attributes, $required);
  }

  /**
   * Build an address block.
   *
   * @param int $locationId The location ID.
   * @param string $title The title for the address block.
   * @param string $phone The phone number.
   * @param string|null $alternatePhone The alternate phone number.
   * @param bool|null $addressRequired Whether address is required.
   * @param bool|null $phoneRequired Whether phone is required.
   * @param bool|null $altPhoneRequired Whether alternate phone is required.
   * @param string|null $locationName The name of the location field.
   */
  public function buildAddressBlock(
    $locationId,
    $title,
    $phone,
    $alternatePhone = NULL,
    $addressRequired = NULL,
    $phoneRequired = NULL,
    $altPhoneRequired = NULL,
    $locationName = NULL
  ) {
    if (!$locationName) {
      $locationName = "location";
    }

    $config = CRM_Core_Config::singleton();
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $location[$locationId]['address']['street_address'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][street_address]",
      $title,
      $attributes['street_address']
    );
    if ($addressRequired) {
      $this->addRule("{$locationName}[$locationId][address][street_address]", ts("Please enter the Street Address for %1.", [1 => $title]), 'required');
    }

    $location[$locationId]['address']['supplemental_address_1'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][supplemental_address_1]",
      ts('Additional Address 1'),
      $attributes['supplemental_address_1']
    );
    $location[$locationId]['address']['supplemental_address_2'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][supplemental_address_2]",
      ts('Additional Address 2'),
      $attributes['supplemental_address_2']
    );

    $location[$locationId]['address']['city'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][city]",
      ts('City'),
      $attributes['city']
    );
    if ($addressRequired) {
      $this->addRule("{$locationName}[$locationId][address][city]", ts("Please enter the City for %1.", [1 => $title]), 'required');
    }

    $location[$locationId]['address']['postal_code'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][postal_code]",
      ts('Zip / Postal Code'),
      $attributes['postal_code']
    );
    if ($addressRequired) {
      $this->addRule("{$locationName}[$locationId][address][postal_code]", ts("Please enter the Zip/Postal Code for %1.", [1 => $title]), 'required');
    }

    $location[$locationId]['address']['postal_code_suffix'] = $this->addElement(
      'text',
      "{$locationName}[$locationId][address][postal_code_suffix]",
      ts('Add-on Code'),
      ['size' => 4, 'maxlength' => 12]
    );
    $this->addRule("{$locationName}[$locationId][address][postal_code_suffix]", ts('Zip-Plus not valid.'), 'positiveInteger');

    if ($config->includeCounty) {
      $location[$locationId]['address']['county_id'] = $this->addElement(
        'select',
        "{$locationName}[$locationId][address][county_id]",
        ts('County'),
        ['' => ts('- select -')] + CRM_Core_PseudoConstant::county()
      );
    }

    $location[$locationId]['address']['state_province_id'] = $this->addElement(
      'select',
      "{$locationName}[$locationId][address][state_province_id]",
      ts('State / Province'),
      ['' => ts('- select -')] + CRM_Core_PseudoConstant::stateProvince()
    );

    $location[$locationId]['address']['country_id'] = $this->addElement(
      'select',
      "{$locationName}[$locationId][address][country_id]",
      ts('Country'),
      ['' => ts('- select -')] + CRM_Core_PseudoConstant::country()
    );
    if ($addressRequired) {
      $this->addRule("{$locationName}[$locationId][address][country_id]", ts("Please select the Country for %1.", [1 => $title]), 'required');
    }

    if ($phone) {
      $location[$locationId]['phone'][1]['phone'] = $this->addElement(
        'text',
        "{$locationName}[$locationId][phone][1][phone]",
        $phone,
        CRM_Core_DAO::getAttribute(
          'CRM_Core_DAO_Phone',
          'phone'
        )
      );
      if ($phoneRequired) {
        $this->addRule("{$locationName}[$locationId][phone][1][phone]", ts('Please enter a value for %1', [1 => $phone]), 'required');
      }
      $this->addRule("{$locationName}[$locationId][phone][1][phone]", ts('Please enter a valid number for %1', [1 => $phone]), 'phone');
    }

    if ($alternatePhone) {
      $location[$locationId]['phone'][2]['phone'] = $this->addElement(
        'text',
        "{$locationName}[$locationId][phone][2][phone]",
        $alternatePhone,
        CRM_Core_DAO::getAttribute(
          'CRM_Core_DAO_Phone',
          'phone'
        )
      );
      if ($alternatePhoneRequired) {
        $this->addRule("{$locationName}[$locationId][phone][2][phone]", ts('Please enter a value for %1', [1 => $alternatePhone]), 'required');
      }
      $this->addRule("{$locationName}[$locationId][phone][2][phone]", ts('Please enter a valid number for %1', [1 => $alternatePhone]), 'phone');
    }
  }

  /**
   * Get the root title of the form.
   *
   * @return string|null The root title.
   */
  public function getRootTitle() {
    return NULL;
  }

  /**
   * Get the complete title of the form.
   *
   * @return string The complete title.
   */
  public function getCompleteTitle() {
    return $this->getRootTitle() . $this->getTitle();
  }

  /**
   * Get the template object.
   *
   * @return \CRM_Core_Smarty The template object.
   */
  public static function &getTemplate() {
    return self::$_template;
  }

  /**
   * Add an upload element to the form.
   *
   * @param string|array $elementName The name of the upload element.
   */
  public function addUploadElement($elementName) {
    $uploadNames = $this->get('uploadNames');
    if (!$uploadNames) {
      $uploadNames = [];
    }
    if (is_array($elementName)) {
      foreach ($elementName as $name) {
        if (!in_array($name, $uploadNames)) {
          $uploadNames[] = $name;
        }
      }
    }
    else {
      if (!in_array($elementName, $uploadNames)) {
        $uploadNames[] = $elementName;
      }
    }
    $this->set('uploadNames', $uploadNames);

    $config = CRM_Core_Config::singleton();
    if (!empty($uploadNames)) {
      $this->controller->addUploadAction($config->customFileUploadDir, $uploadNames);
    }
  }

  /**
   * Get the button type (next or upload).
   *
   * @return string The button type.
   */
  public function buttonType() {
    $uploadNames = $this->get('uploadNames');
    $buttonType = (is_array($uploadNames) && !empty($uploadNames)) ? 'upload' : 'next';
    $this->assign('buttonType', $buttonType);
    return $buttonType;
  }

  /**
   * Get a variable from the form.
   *
   * @param string $name The name of the variable.
   *
   * @return mixed The value of the variable.
   */
  public function getVar($name) {
    return $this->$name ?? NULL;
  }

  /**
   * Set a variable on the form.
   *
   * @param string $name The name of the variable.
   * @param mixed $value The value of the variable.
   */
  public function setVar($name, $value) {
    $this->$name = $value;
  }

  /**
   * Function to add a date element.
   *
   * @param string $name Name of the element.
   * @param string $label Label of the element.
   * @param bool $required True if required.
   * @param array|null $attributes Key/value pair of attributes.
   */
  public function addDate($name, $label, $required = FALSE, $attributes = NULL) {
    if (CRM_Utils_Array::value('formatType', $attributes)) {
      // get actual format
      $params = ['name' => $attributes['formatType']];
      $values = [];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);

      if ($values['date_format']) {
        $attributes['format'] = $values['date_format'];
      }

      if (CRM_Utils_Array::value('time_format', $values)) {
        $attributes['timeFormat'] = $values['time_format'];
      }
      $attributes['startOffset'] = $values['start'];
      $attributes['endOffset'] = $values['end'];
    }

    $config = CRM_Core_Config::singleton();
    if (!CRM_Utils_Array::value('format', $attributes)) {
      $attributes['format'] = $config->dateInputFormat;
    }

    if (!isset($attributes['startOffset'])) {
      $attributes['startOffset'] = 10;
    }

    if (!isset($attributes['endOffset'])) {
      $attributes['endOffset'] = 10;
    }

    $attributes['readonly'] = TRUE;

    $this->add('text', $name, $label, $attributes);

    if (CRM_Utils_Array::value('addTime', $attributes) ||
      CRM_Utils_Array::value('timeFormat', $attributes)
    ) {

      if (!isset($attributes['timeFormat'])) {
        $timeFormat = $config->timeInputFormat;
      }
      else {
        $timeFormat = $attributes['timeFormat'];
      }

      // 1 - 12 hours and 2 - 24 hours, but for jquery widget it is 0 and 1 respectively
      if ($timeFormat) {
        $show24Hours = TRUE;
        if ($timeFormat == 1) {
          $show24Hours = FALSE;
        }

        //CRM-6664 -we are having time element name
        //in either flat string or an array format.
        $elementName = $name . '_time';
        if (substr($name, -1) == ']') {
          $elementName = substr($name, 0, strlen($name) - 1) . '_time]';
        }

        $this->add('text', $elementName, ts('Time'), ['timeFormat' => $show24Hours]);
      }
    }

    if ($required) {
      $this->addRule($name, ts('Please select %1', [1 => $label]), 'required');
      if (CRM_Utils_Array::value('addTime', $attributes)) {
        $this->addRule($elementName, ts('Please select Time'), 'required');
      }
    }
  }

  /**
   * Function that will add date and time.
   *
   * @param string $name Name of the element.
   * @param string $label Label of the element.
   * @param bool $required True if required.
   * @param array|null $attributes Key/value pair of attributes.
   */
  public function addDateTime($name, $label, $required = FALSE, $attributes = NULL) {
    $addTime = ['addTime' => TRUE];
    if (is_array($attributes)) {
      $attributes = array_merge($attributes, $addTime);
    }
    else {
      $attributes = $addTime;
    }

    $this->addDate($name, $label, $required, $attributes);
  }

  /**
   * add a currency and money element to the form.
   *
   * @param string $name Name of the field.
   * @param string $label Label for the field.
   * @param bool $required Whether the field is required.
   * @param mixed $attributes Attributes for the field.
   * @param bool $addCurrency Whether to add a currency selector.
   * @param string $currencyName Name for the currency selector.
   * @param string|null $defaultCurrency Default currency code.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addMoney(
    $name,
    $label,
    $required = FALSE,
    $attributes = NULL,
    $addCurrency = TRUE,
    $currencyName = 'currency',
    $defaultCurrency = NULL
  ) {
    $element = $this->add('text', $name, $label, $attributes, $required);
    $this->addRule($name, ts('Please enter a valid amount.'), 'money');

    if ($addCurrency) {
      $this->addCurrency($currencyName, NULL, TRUE, $defaultCurrency);
    }

    return $element;
  }

  /**
   * add currency element to the form.
   *
   * @param string $name Name of the selector.
   * @param string|null $label Label for the selector.
   * @param bool $required Whether the selector is required.
   * @param string|null $defaultCurrency Default currency code.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addCurrency(
    $name = 'currency',
    $label = NULL,
    $required = TRUE,
    $defaultCurrency = NULL
  ) {

    $currencies = CRM_Core_OptionGroup::values('currencies_enabled');
    if (!$required) {
      $currencies = [ '' => ts('- select -') ] + $currencies;
    }
    $ele = $this->add('select', $name, $label, $currencies, $required);
    if (!$defaultCurrency) {
      $config = CRM_Core_Config::singleton();
      $defaultCurrency = $config->defaultCurrency;
    }
    $this->setDefaults([$name => $defaultCurrency]);
    return $ele;
  }

  /**
   * Add a rule to check for required fields.
   *
   * @param array $errors The errors array.
   * @param array $fields The fields array.
   * @param array $files The files array.
   */
  public function addFieldRequiredRule(&$errors, $fields, $files) {
    // Files : Write in $this->_submitFiles['custom_4']['name']
    // if is no Files : $this->_submitFiles['custom_4']['error'] == 4
    // or $this->_submitFiles['custom_4']['name'] is null.
    foreach ($this->_fields as $name => $fld) {
      if ($fld['is_required']) {
        $data_type = $fld['data_type'] ?? '';
        if (CRM_Utils_System::isNull(CRM_Utils_Array::value($name, $fields) && $data_type != 'File')) {
          $errors[$name] = ts('%1 is a required field.', [1 => $fld['title']]);
        }

        if (empty($files[$name]['name']) && ($data_type == 'File' || $name == 'image_URL')) {
          if ($this->_action == 1) {
            // profile : create
            $errors[$name] = ts('%1 is a required field.', [1 => $fld['title']]);
          }
          else {
            if ($data_type == 'File') {
              $customFieldID = CRM_Core_BAO_CustomField::getKeyID($name);
              $file = CRM_Core_BAO_CustomField::getFileURL($this->_id, $customFieldID);
              $file = $file['file_id'] ?? FALSE;
            }
            if ($name == 'image_URL') {
              $file  = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_id, 'image_URL');
            }
            if (empty($file)) {
              $errors[$name] = ts('%1 is a required field.', [1 => $fld['title']]);
            }
          }
        }
      }
    }
  }

  /**
   * Prevent multiple submissions of the same form.
   */
  public function preventMultipleSubmission() {
    if ($this->_submissionCount > 1) {
      $message = ts('This message indicate that your have submitted this form before. You stop here because we need to prevent your double submissions.');
      $initPage = $this->get('initPage');
      if ($initPage) {
        $message .= '<div align="right">&raquo; '. '<a href="'.$initPage.'">'.ts('Correct page to add another submission').'</a></div>';
      }
      CRM_Core_Error::fatal($message);
    }
  }

  /**
   * Add a file upload element to the form.
   *
   * @param string $name The name of the field.
   * @param string $label The label for the field.
   * @param mixed $attributes Attributes for the field.
   * @param bool $required Whether the field is required.
   * @param mixed $javascript JavaScript for the field.
   *
   * @return \HTML_QuickForm_element The created element.
   */
  public function addFile($name, $label = '', $attributes = '', $required = FALSE, $javascript = NULL) {
    $element = &$this->addElement('file', $name, $label, $attributes, $javascript);
    if (HTML_QuickForm::isError($element)) {
      CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
    }

    if ($required) {
      $error = $this->addRule($name, ts('%1 is a required field.', [1 => $label]), 'required');
      if (HTML_QuickForm::isError($error)) {
        CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
      }
    }

    return $element;
  }

  /**
   * Extract ID and additional ID from a checkbox prefix.
   *
   * @param string $value The checkbox value string.
   *
   * @return array Array of [id, additionalID].
   */
  public static function cbExtract($value) {
    $id = $additionalID = NULL;
    if (substr($value, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
      $val = substr($value, CRM_Core_Form::CB_PREFIX_LEN);
      if (strstr($val, '_')) {
        list($id, $additionalID) = explode('_', $val, 2);
      }
      else {
        if (is_numeric($val)) {
          $id = $val;
        }
      }
    }
    return [$id, $additionalID];
  }

  /**
   * Process blob images from CKeditor fields before data persistence
   * Convert temporary blob URLs to permanent file storage and update form elements
   *
   * @return array Result of blob image processing
   */
  private function processBlobImages() {
    $result = CRM_Utils_Image::processBlobImagesInContent(
      $this->_submitValues,
      $this->_elements
    );

    // Update form elements with modified content using setValue
    if (!empty($result['processed_fields'])) {
      foreach ($result['processed_fields'] as $fieldName) {
        $updatedContent = $this->_submitValues[$fieldName];

        try {
          // Find the corresponding form element and update its value
          $element = $this->getElement($fieldName);

          if ($element && method_exists($element, 'setValue')) {
            // Use setValue to maintain form consistency
            $element->setValue($updatedContent);
          }
          if (isset($element->_submitValues[$fieldName])) {
            $element->_submitValues[$fieldName] = $updatedContent;
          }
          $this->controller->setValue($fieldName, $updatedContent);
        }
        catch (Exception $e) {
          $result['errors'][] = "Failed to update form element '{$fieldName}': " . $e->getMessage();
        }
      }
    }

    return $result;
  }
}
