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
 * This class generates form components for relationship
 *
 */
class CRM_Contribute_Form_Task extends CRM_Core_Form {

  /**
   * the task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * The array that holds all the contribution ids
   *
   * @var array
   */
  protected $_contributionIds;

  /**
   * The array that holds all the contact ids
   *
   * @var array
   */
  public $_contactIds;

  /**
   * build all the data structures needed to build the form
   *
   * @param
   *
   * @return void
   * @access public
   */
  function preProcess() {
    self::preProcessCommon($this);
  }

  static function preProcessCommon(&$form, $useTable = FALSE) {
    $form->_contributionIds = [];

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $contributeTasks = CRM_Contribute_Task::tasks();
    $form->assign('taskName', $contributeTasks[$form->_task]);

    $ids = [];
    $rowCount = $form->get('rowCount');
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($name);
        if (!empty($contactID)) {
          $ids[] = $contactID;
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_CONTRIBUTE
      );

      $sortOrder = $form->controller->get('sortOrder');

      if ( $form->get( CRM_Utils_Sort::SORT_ID  ) ) {
        $sortID = CRM_Utils_Sort::sortIDValue( $form->get( CRM_Utils_Sort::SORT_ID  ),$form->get( CRM_Utils_Sort::SORT_DIRECTION ) );
        $form->_sort = new CRM_Utils_Sort($sortOrder, $sortID);
      }


      // separate query to prevent memory peak
      $jobSize = 50000;
      if (is_numeric($rowCount) && $rowCount / $jobSize > 1) {
        $jobs = $rowCount / $jobSize;
        for($i = 0; $i < $jobs; $i++) {
          $offset = $i*$jobSize;
          $result = $query->searchQuery($offset, $jobSize, $form->_sort);
          while ($result->fetch()) {
            $ids[] = $result->contribution_id;
          }
          $result->free();
        }
      }
      else {
        $result = $query->searchQuery(0, 0, $form->_sort);
        while ($result->fetch()) {
          $ids[] = $result->contribution_id;
        }
      }
      $result->free();
      $form->assign('totalSelectedContributions', $form->get('rowCount'));
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_contribution.id IN ( ' . CRM_Utils_Array::implode(',', $ids) . ' ) ';

      $form->assign('totalSelectedContributions', count($ids));
    }

    $form->_contributionIds = $form->_componentIds = $ids;

    //set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);

    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName == 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
          $urlParams
        ));
    }
  }

  /**
   * Given the contribution id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = &CRM_Core_DAO::getContactIDsFromComponent($this->_contributionIds,
      'civicrm_contribution'
    );
  }

  /**
   * simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title title of the main button
   * @param string $type  button type for the form after processing
   *
   * @return void
   * @access public
   */
  function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = null) {
    $this->addButtons([
        ['type' => $nextType,
          'name' => $title,
          'isDefault' => TRUE,
        ],
        ['type' => $backType,
          'name' => ts('Cancel'),
        ],
      ]
    );
  }
}

