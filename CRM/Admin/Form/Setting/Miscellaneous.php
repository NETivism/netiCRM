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
 * This class generates form components for Miscellaneous
 *
 */
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Miscellaneous'));

    // FIXME: for now, disable logging for multilingual sites
    $domain = new CRM_Core_DAO_Domain;
    $domain->find(TRUE);

    $this->addYesNo('doNotAttachPDFReceipt', ts('Do not attach PDF copy to receipts'));

    $this->addElement('text', 'maxAttachments', ts('Maximum Attachments'),
      ['size' => 2, 'maxlength' => 8]
    );
    $this->addElement('text', 'maxFileSize', ts('Maximum File Size'),
      ['size' => 2, 'maxlength' => 8]
    );
    $this->addElement('text', 'recaptchaPublicKey', ts('Public Key'),
      ['size' => 64, 'maxlength' => 64]
    );
    $this->addElement('text', 'recaptchaPrivateKey', ts('Private Key'),
      ['size' => 64, 'maxlength' => 64]
    );

    if (CRM_Core_Permission::check('administer neticrm')) {
      $attribs = $domain->locales ? ['disabled' => 'disabled'] : NULL;
      $this->addYesNo('logging', ts('Logging'), NULL, NULL, $attribs);
      $this->addRule('maxAttachments', ts('Value should be a positive number'), 'positiveInteger');
      $this->addRule('maxFileSize', ts('Value should be a positive number'), 'positiveInteger');
      $this->addElement('text', 'dashboardCacheTimeout', ts('Dashboard cache timeout'),
        ['size' => 3, 'maxlength' => 5]
      );
      $this->addElement('text', 'wkhtmltopdfPath', ts('Path to wkhtmltopdf executable'),
        ['size' => 64, 'maxlength' => 256]
      );
      $this->addYesNo('versionCheck', ts('Version Check & Statistics Reporting'));
      $this->assign('admin', TRUE);
      $this->addTextfield('docURLBase', ts('Documentation URL Base Path'));

      // Refs #38829, Add Path to qpdf executable field.
      $this->addElement('text', 'qpdfPath', ts('Path to qpdf executable'),
        ['size' => 64, 'maxlength' => 256]
      );
    }
    else {
      $this->assign('admin', FALSE);
    }

    parent::buildQuickForm();
  }

  public function postProcess() {
    parent::postProcess();

    // handle logging
    // FIXME: do it only if the setting changed

    $values = $this->exportValues();
    $logging = new CRM_Logging_Schema;
    $values['logging'] ? $logging->enableLogging() : $logging->disableLogging();
  }
}

