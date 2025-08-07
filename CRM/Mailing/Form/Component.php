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
 * This class generates form components for Location Type
 *
 */
class CRM_Mailing_Form_Component extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  protected $_id;

  /**
   * The name of the BAO object for this form
   *
   * @var string
   */
  protected $_BAOName; function preProcess() {
    $this->_id = $this->get('id');
    $this->_BAOName = $this->get('BAOName');
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $this->add('text', 'name', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Component', 'name'), TRUE
    );
    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', ['CRM_Mailing_DAO_Component', $this->_id]);

    $this->add('select', 'component_type', ts('Component Type'), CRM_Core_SelectValues::mailingComponents());

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Component', 'subject'),
      TRUE
    );

    $isRequired = TRUE;
    if (!empty($this->_id)) {
      $componentType = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Component', $this->_id, 'component_type');
      $isRequired = ($componentType != 'Header');
    }
    $this->add('textarea', 'body_text', ts('Body - TEXT Format'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Component', 'body_text'),
      $isRequired
    );
    $this->addWysiwyg('body_html',
    ts('Body - HTML Format'),
    [
      'cols' => '80',
      'rows' => '8',
      ]
    );

    $this->add('checkbox', 'is_default', ts('Default?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Mailing_Form_Component', 'dataRule']);

    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = [];
    $params = [];

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve( $params, $defaults );
    }
    $defaults['is_active'] = 1;

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $ids = [];

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $ids['id'] = $this->_id;
    }


    CRM_Mailing_BAO_Component::add($params, $ids);
  }
  //end of function

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function dataRule($params, $files, $options) {
    if ($params['component_type'] == 'Header' || $params['component_type'] == 'Footer') {
      $InvalidTokens = [];
    }
    else {
      $InvalidTokens = ['action.forward' => ts("This token can only be used in send mailing context (body, header, footer)..")];
    }
    $errors = [];
    foreach ([
        'text', 'html',
      ] as $type) {
      $dataErrors = [];
      foreach ($InvalidTokens as $token => $desc) {
        if ($params['body_' . $type]) {
          if (preg_match('/' . preg_quote('{' . $token . '}') . '/', $params['body_' . $type])) {
            $dataErrors[] = '<li>' . ts('This message is having a invalid token - %1: %2', [
                1 => $token, 2 => $desc,
              ]) . '</li>';
          }
        }
      }
      if (!empty($dataErrors)) {
        $errors['body_' . $type] = ts('The following errors were detected in %1 message:', [
            1 => $type,
          ]) . '<ul>' . CRM_Utils_Array::implode('', $dataErrors) . '</ul><br /><a href="' . CRM_Utils_System::docURL2('CiviMail Action Tokens', TRUE) . '">' . ts('More information on tokens...') . '</a>';
      }
    }
    if (!CRM_Utils_Array::value('body_html', $params)) {
      $errors['body_html'] = ts('body_html is a required field.');
    }
    if (!CRM_Utils_Array::value('body_text', $params)) {
      $errors['body_text'] = ts('body_text is a required field.');
    }

    return empty($errors) ? TRUE : $errors;
  }
}

