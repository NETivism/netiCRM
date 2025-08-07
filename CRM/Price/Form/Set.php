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
 * form to process actions on Price Sets
 */
class CRM_Price_Form_Set extends CRM_Core_Form {

  /**
   * the set id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_sid;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // current set id
    $this->_sid = $this->get('sid');

    // setting title for html page
    $title = ts('New Price Set');
    if ($this->_sid) {
      $title = CRM_Price_BAO_Set::getTitle($this->_sid);
    }
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $title = ts('Edit %1', [1 => $title]);
    }
    elseif ($this->_action & CRM_Core_Action::VIEW) {
      $title = ts('Preview %1', [1 => $title]);
    }
    CRM_Utils_System::setTitle($title);

    $url = CRM_Utils_System::url('civicrm/admin/price', 'reset=1');
    $breadCrumb = [['title' => ts('Price Sets'),
        'url' => $url,
      ]];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $options) {
    $errors = [];

    //checks the given price set doesnot start with digit
    $title = $fields['title'];
    // gives the ascii value
    $asciiValue = ord($title[0]);
    if ($asciiValue >= 48 && $asciiValue <= 57) {
      $errors['title'] = ts("Set's Name should not start with digit");
    }
    return empty($errors) ? TRUE : $errors;
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
    $this->applyFilter('__ALL__', 'trim');

    $this->assign('sid', $this->_sid);

    // title
    $this->add('text', 'title', ts('Set Name'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_Set', 'title'), TRUE);
    $this->addRule('title', ts('Name already exists in Database.'),
      'objectExists', ['CRM_Price_DAO_Set', $this->_sid, 'title']
    );

    $priceSetUsedTables = $extends = [];
    if ($this->_action == CRM_Core_Action::UPDATE && $this->_sid) {
      $priceSetUsedTables = CRM_Price_BAO_Set::getUsedBy($this->_sid, TRUE);
    }



    $config = CRM_Core_Config::singleton();
    $components = ['CiviEvent' => ['title' => ts('Event'),
        'extend' => CRM_Core_Component::getComponentID('CiviEvent'),
        'tables' => ['civicrm_event',
          'civicrm_participant',
        ],
      ],
      'CiviContribute' => ['title' => ts('Contribution'),
        'extend' => CRM_Core_Component::getComponentID('CiviContribute'),
        'tables' => ['civicrm_contribution',
          'civicrm_contribution_page',
        ],
      ],
    ];
    foreach ($components as $compName => $compValues) {
      // take only enabled components.
      if (!in_array($compName, $config->enableComponents)) {
        continue;
      }
      $option = $this->createElement('checkbox', $compValues['extend'], NULL, $compValues['title']);

      //if price set is used than freeze it.
      if (!empty($priceSetUsedTables)) {
        foreach ($compValues['tables'] as $table) {
          if (in_array($table, $priceSetUsedTables)) {
            $option->freeze();
            break;
          }
        }
      }
      $extends[] = $option;
    }

    if (CRM_Utils_System::isNull($extends)) {
      $this->assign('extends', FALSE);
    }
    else {
      $this->assign('extends', TRUE);
    }

    $this->addGroup($extends, 'extends', ts('Used For'), '&nbsp;', TRUE);

    $this->addRule('extends', ts('%1 is a required field.', [1 => ts('Used For')]), 'required');

    // help text
    $this->add('textarea', 'help_pre', ts('Pre-form Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_Set', 'help_pre')
    );
    $this->add('textarea', 'help_post', ts('Post-form Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_Set', 'help_post')
    );

    // is this set active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Price Set active?'));

    // If price set is being used, freeze the is_active field
    if (!empty($priceSetUsedTables)) {
      $this->getElement('is_active')->freeze();
    }

    $js = ['data' => 'click-once'];
    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
          'js' => $js,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    $this->addFormRule(['CRM_Price_Form_Set', 'formRule']);

    // views are implemented as frozen form
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      //$this->addElement('button', 'done', ts('Done'), array('onclick' => "location.href='civicrm/admin/price?reset=1&action=browse'"));
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = ['is_active' => TRUE];
    if ($this->_sid) {
      $params = ['id' => $this->_sid];
      CRM_Price_BAO_Set::retrieve($params, $defaults);
      $extends = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['extends']);
      unset($defaults['extends']);
      foreach ($extends as $compId) $defaults['extends'][$compId] = 1;
    }

    return $defaults;
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
    // get the submitted form values.
    $params = $this->controller->exportValues('Set');
    $nameLength = CRM_Core_DAO::getAttribute('CRM_Price_DAO_Set', 'name');
    $params['name'] = CRM_Utils_String::titleToVar($params['title'], CRM_Utils_Array::value('maxlength', $nameLength));
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

    $compIds = [];
    $extends = CRM_Utils_Array::value('extends', $params);
    if (is_array($extends)) {
      foreach ($extends as $compId => $selected) if ($selected) {   $compIds[] = $compId; }
    }
    $params['extends'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $compIds);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_sid;
    }

    $set = CRM_Price_BAO_Set::create($params);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      CRM_Core_Session::setStatus(ts('The Set \'%1\' has been saved.', [1 => $set->title]));
    }
    else {
      $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=add&sid=' . $set->id);
      CRM_Core_Session::setStatus(ts('Your Set \'%1\' has been added. You can add fields to this set now.',
          [1 => $set->title]
        ));
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }
  }
}

