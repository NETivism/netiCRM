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

class CRM_Import_DataSource_SQL extends CRM_Import_DataSource {

  /**
   * Get info about this data source.
   *
   * @return array
   */
  public function getInfo() {
    return [
      'title' => ts('SQL Query'),
      'permissions' => 'import SQL datasource',
    ];
  }

  /**
   * Pre-process form.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function preProcess(&$form) {
  }

  /**
   * Build the form.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_SQL');
    $form->add('textarea', 'sqlQuery', ts('Specify SQL Query'), 'rows=10 cols=45', TRUE);
    $form->addFormRule(['CRM_Import_DataSource_SQL', 'formRule'], $form);
  }

  /**
   * Form rule for validation.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return mixed
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    // poor man's query validation (case-insensitive regex matching on word boundaries)
    $forbidden = ['ALTER', 'CREATE', 'DELETE', 'DESCRIBE', 'DROP', 'SHOW', 'UPDATE', 'information_schema'];
    foreach ($forbidden as $pattern) {
      if (preg_match("/\\b$pattern\\b/i", $fields['sqlQuery'])) {
        $errors['sqlQuery'] = ts('The query contains the forbidden %1 command.', [1 => $pattern]);
      }
    }

    return $errors ? $errors : TRUE;
  }

  /**
   * Process the form.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param object $db
   *
   * @return void
   */
  public static function postProcess(&$form, &$params, &$db) {
    $importJob = new CRM_Import_ImportJob(
      CRM_Utils_Array::value('import_table_name', $params),
      $params['sqlQuery'],
      TRUE
    );
    $tableName = $importJob->getTableName();
    $form->set('importTableName', $tableName);

    $fields = parent::prepareImportTable($tableName);
    $form->set('primaryKeyName', $fields['primaryKeyName']);
    $form->set('statusFieldName', $fields['statusFieldName']);
  }
}
