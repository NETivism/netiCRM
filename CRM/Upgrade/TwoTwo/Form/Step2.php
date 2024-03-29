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

require_once 'CRM/Upgrade/Form.php';
class CRM_Upgrade_TwoTwo_Form_Step2 extends CRM_Upgrade_Form {
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade step %1.', array(1 => '2'));

    return $this->checkVersion('2.1.101');
  }

  function upgrade() {
    $sqlFile = CRM_Utils_Array::implode(DIRECTORY_SEPARATOR,
      array(dirname(__FILE__), '..', '..',
        'Incremental', 'sql', '2.2.alpha1.mysql',
      )
    );
    $tplFile = "$sqlFile.tpl";

    $isMultilingual = FALSE;
    if (file_exists($tplFile)) {
      $isMultilingual = $this->processLocales($tplFile, '2.2');
    }
    else {
      if (!file_exists($sqlFile)) {
        CRM_Core_Error::fatal("sqlfile - $rev.mysql not found.");
      }
      $this->source($sqlFile);
    }

    if ($isMultilingual) {
      require_once 'CRM/Core/I18n/Schema.php';
      require_once 'CRM/Core/DAO/Domain.php';
      $domain = new CRM_Core_DAO_Domain();
      $domain->find(TRUE);
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, '2.2');
    }

    $this->setVersion('2.1.102');
  }

  function verifyPostDBState(&$errorMessage) {
    // check if civicrm_event_page tables droped
    if (CRM_Core_DAO::checkTableExists('civicrm_event_page')) {
      $errorMessage .= '  civicrm_event_page table is not droped.';
      return FALSE;
    }
    // check fields which MUST be present civicrm_event
    if (!CRM_Core_DAO::checkFieldExists('civicrm_event', 'intro_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_title') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_email_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_email_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_from_name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_from_email') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'cc_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'bcc_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'default_fee_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'default_discount_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_title') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_pay_later') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'pay_later_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'pay_later_receipt') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_multiple_registrations')
    ) {
      $errorMessage .= ' Few important fields were found missing in civicrm_event table.';
      return FALSE;
    }

    $errorMessage = ts('Post-condition failed for upgrade step %1.', array(1 => '2'));

    return $this->checkVersion('2.1.102');
  }

  function getTitle() {
    return ts('CiviCRM 2.2 Upgrade: Step Two (Merge CiviEvent Tables)');
  }

  function getTemplateMessage() {
    return '<p>' . ts('Step Two will merge the table EventPage into Event table in your database.') . '</p>';
  }

  function getButtonTitle() {
    return ts('Upgrade & Continue');
  }
}

