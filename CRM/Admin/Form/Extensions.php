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
 * This class generates form components for Extensions
 *
 */
class CRM_Admin_Form_Extensions extends CRM_Admin_Form {

  public $_key;
  /**
   * Function to for pre-processing
   *
   * @return None
   * @access public
   */
  public function preProcess() {
    parent::preProcess();


    $this->_key = CRM_Utils_Request::retrieve('key', 'String',
      $this, FALSE, 0
    );

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1&action=browse');
    $session->pushUserContext($url);
    $this->assign('id', $this->_id);
    $this->assign('key', $this->_key);


    $ext = new CRM_Core_Extensions();
    $extension = $ext->getExtensionsByKey(TRUE);

    $this->assign('extension', get_object_vars($extension[$this->_key]));
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
    $defaults = [];
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Uninstall'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
    else {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Install'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $self    this object.
   *
   * @return true if no errors, else an array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = [];

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

      $ext = new CRM_Core_Extensions();
      $ext->uninstall($this->_id, $this->_key);
      CRM_Core_Session::setStatus(ts('Extension has been uninstalled.'));
    }

    if ($this->_action & CRM_Core_Action::ADD) {

      $ext = new CRM_Core_Extensions();
      $ext->install($this->_id, $this->_key);
      CRM_Core_Session::setStatus(ts('Extension has been installed.'));
    }
  }
}

