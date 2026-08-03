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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * This class generates form components for Contribution Type management.
 */
class CRM_Contribute_Form_ContributionType extends CRM_Contribute_Form {

  /**
   * Build the quick form components.
   *
   * Adds fields for contribution type name, description, accounting code,
   * tax rate, deductibility, and tax receipt settings.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->addFormRule([get_class($this), 'formRule']);

    $nameAttr = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'name') ?: [];
    $accountingCodeAttr = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'accounting_code') ?: [];
    $hasReceiptsLocked = FALSE;

    if ($this->_action == CRM_Core_Action::UPDATE && $this->_id) {
      $isReserved = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType', $this->_id, 'is_reserved');
      if (!$isReserved && CRM_Contribute_BAO_ContributionType::hasReceiptsIssued($this->_id)) {
        $hasReceiptsLocked = TRUE;
        $nameAttr = array_merge($nameAttr, ['readonly' => 'readonly']);
        $accountingCodeAttr = array_merge($accountingCodeAttr, ['readonly' => 'readonly']);
      }
    }
    $this->assign('hasReceiptsLocked', $hasReceiptsLocked);

    $this->add('text', 'name', ts('Name'), $nameAttr, TRUE);
    $this->addRule('name', ts('A contribution type with this name already exists. Please select another name.'), 'objectExists', ['CRM_Contribute_DAO_ContributionType', $this->_id]);

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'description'));
    $this->add('text', 'accounting_code', ts('Accounting Code'), $accountingCodeAttr);
    $this->add('text', 'tax_rate', ts('Tax Rate'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'tax_rate'));

    $this->add('checkbox', 'is_deductible', ts('Tax-deductible?'));
    $taxReceiptType = [
      '0' => ts('None'),
      '-1' => ts('Tax free'),
      '1' => ts('Normal tax or zero tax'),
    ];
    $this->addRadio('is_taxreceipt', ts('Tax Receipt Type'), $taxReceiptType);
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    if ($this->_action == CRM_Core_Action::UPDATE) {
      if (CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType', $this->_id, 'is_reserved')) {
        $this->freeze(['name', 'description', 'is_active']);
      }
      else {
        $usedPages = CRM_Contribute_BAO_ContributionType::getUsedPagesAndEvents($this->_id);
        $hasActivePages = FALSE;
        foreach ($usedPages as $page) {
          if ($page['is_active']) {
            $hasActivePages = TRUE;
            break;
          }
        }
        if ($hasActivePages) {
          $this->freeze(['is_active']);
          $this->assign('isActivePageLocked', TRUE);
        }
      }
    }
  }

  /**
   * Global form rule.
   *
   * The accounting code may be substituted into the receipt prefix through the
   * '!acc' token, and the receipt id generator appends its own '-' separator
   * afterwards. A hyphen inside the accounting code therefore blurs the
   * prefix/serial boundary that lastReceiptID() relies on. Refs #46448, #44975.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return bool|array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if (!empty($fields['accounting_code']) && strpos($fields['accounting_code'], '-') !== FALSE) {
      $errors['accounting_code'] = ts('Accounting Code cannot contain hyphen (-). Please remove it before saving.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   * Handles deletion of a contribution type or adding/updating a record based
   * on the submitted values.
   *
   * @return void
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contribute_BAO_ContributionType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected contribution type has been deleted.'));
    }
    else {

      $params = $ids = [];
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['contributionType'] = $this->_id;
      }

      $contributionType = CRM_Contribute_BAO_ContributionType::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The contribution type \'%1\' has been saved.', [1 => $contributionType->name]));
    }
  }
}
