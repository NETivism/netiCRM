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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Contribute/Import/Parser.php';

/**
 * This class summarizes the import results
 */
class CRM_Contribute_Import_Form_Summary extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // set the error message path to display
    $errorFile = $this->assign('errorFile', $this->get('errorFile'));

    $totalRowCount = $this->get('totalRowCount');
    $relatedCount = $this->get('relatedCount');
    $totalRowCount += $relatedCount;
    $this->set('totalRowCount', $totalRowCount);

    $tableName = $this->get('importTableName');
    $prefix = str_replace(CRM_Import_ImportJob::TABLE_PREFIX, CRM_Contribute_Import_Parser::ERROR_FILE_PREFIX, $tableName);
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);

    CRM_Import_Parser::setImportErrorFilenames($qfKey, array('error', 'conflict','soft_credit_error', 'pcp_error', 'pledge_payment_error', 'duplicate', 'no_match'), 'CRM_Contribute_Import_Parser', $prefix, $this);

    $invalidRowCount = $this->get('invalidRowCount');
    $invalidSoftCreditRowCount = $this->get('invalidSoftCreditRowCount');
    $invalidPCPRowCount = $this->get('invalidPCPRowCount');
    $invalidPledgePaymentRowCount = $this->get('invalidPledgePaymentRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $duplicateRowCount = $this->get('duplicateRowCount');
    $onDuplicate = $this->get('onDuplicate');
    $mismatchCount = $this->get('unMatchCount');

    if ($duplicateRowCount <= 0 && !$mismatchCount) {
      $duplicateRowCount = 0;
      $this->set('duplicateRowCount', $duplicateRowCount);
    }

    $this->assign('dupeError', FALSE);

    if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE) {
      $dupeActionString = ts('These records have been updated with the imported data.');
    }
    elseif ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_FILL) {
      $dupeActionString = ts('These records have been filled in with the imported data.');
    }
    else {
      /* Skip by default */
      $dupeActionString = ts('These records have not been imported.');
      $this->assign('dupeError', TRUE);
    }
    $this->set('validRowCount', $totalRowCount - $invalidRowCount -
      $conflictRowCount - $duplicateRowCount - $mismatchCount - $invalidSoftCreditRowCount - $invalidPledgePaymentRowCount - $invalidPCPRowCount
    );
    $this->assign('dupeActionString', $dupeActionString);

    $properties = array('totalRowCount', 'validRowCount', 'invalidRowCount', 'validSoftCreditRowCount', 'validPCPRowCount', 'invalidSoftCreditRowCount', 'invalidPCPRowCount', 'conflictRowCount', 'downloadConflictRecordsUrl', 'downloadErrorRecordsUrl', 'duplicateRowCount', 'downloadDuplicateRecordsUrl', 'downloadMismatchRecordsUrl', 'groupAdditions', 'unMatchCount', 'validPledgePaymentRowCount', 'invalidPledgePaymentRowCount', 'downloadPledgePaymentErrorRecordsUrl', 'downloadSoftCreditErrorRecordsUrl', 'downloadPCPErrorRecordsUrl');
    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array('type' => 'next',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Summary');
  }
}

