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
class CRM_Contact_Form_Task extends CRM_Core_Form {

  /**
   * the task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The array that holds all the contact ids
   *
   * @var array
   */
  public $_contactIds;

  /**
   * The array that holds all the contact types
   *
   * @var array
   */
  public $_contactTypes;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The name of the temp table where we store the contact IDs
   *
   * @var string
   */
  protected $_componentTable = NULL;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

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
    $form->_contactIds = [];
    $form->_contactTypes = [];

    // get the submitted values of the search form
    // we'll need to get fv from either search or adv search in the future
    $fragment = 'search';
    if ($form->_action == CRM_Core_Action::ADVANCED) {
      $values = $form->controller->exportValues('Advanced');
      $fragment .= '/advanced';
    }
    elseif ($form->_action == CRM_Core_Action::PROFILE) {
      $values = $form->controller->exportValues('Builder');
      $fragment .= '/builder';
    }
    elseif ($form->_action == CRM_Core_Action::COPY) {
      $values = $form->controller->exportValues('Custom');
      $fragment .= '/custom';
    }
    else {
      $values = $form->controller->exportValues('Basic');
    }

    //set the user context for redirection of task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);

    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);


    $form->_task = CRM_Utils_Array::value('task', $values);
    $crmContactTaskTasks = CRM_Contact_Task::taskTitles();
    $form->assign('taskName', CRM_Utils_Array::value($form->_task, $crmContactTaskTasks));
    $primaryIDName = $form->get('primaryIDName', NULL);
    if ($useTable) {
      $form->_componentTable = CRM_Core_DAO::createTempTableName('civicrm_task_action', FALSE);
      $sql = " DROP TABLE IF EXISTS {$form->_componentTable}";
      CRM_Core_DAO::executeQuery($sql);
      if ($customHeader = $form->get('customHeader')) {
        foreach ($customHeader as $columnName => $val) {
          $customColumns .= ", $columnName varchar(64)";
        }
      }

      if (!empty($primaryIDName)) {
        $sql = "CREATE TABLE {$form->_componentTable} ( id int primary key $customColumns) ENGINE=MyISAM DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";  
      }
      else {
        $sql = "CREATE TABLE {$form->_componentTable} ( contact_id int primary key $customColumns) ENGINE=MyISAM DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      }

      CRM_Core_DAO::executeQuery($sql);
    }

    // all contacts or action = save a search
    if ((CRM_Utils_Array::value('radio_ts', $values) == 'ts_all') ||
      ($form->_task == CRM_Contact_Task::SAVE_SEARCH)
    ) {
      // need to perform action on all contacts
      // fire the query again and get the contact id's + display name
      $sortID = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($form->get(CRM_Utils_Sort::SORT_ID),
          $form->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }

      $selectorName = $form->controller->selectorName();
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $selectorName) . '.php');

      $fv = $form->get('formValues');
      $customClass = $form->get('customSearchClass');

      $returnProperties = CRM_Core_BAO_Mapping::returnProperties($values);
      $selector = new $selectorName($customClass, $fv, NULL, $returnProperties);

      $params = $form->get('queryParams');

      // fix for CRM-5165
      $sortByCharacter = $form->get('sortByCharacter');
      if ($sortByCharacter &&
        $sortByCharacter != 1
      ) {
        $params[] = ['sortByCharacter', '=', $sortByCharacter, 0, 0];
      }

      if ($selectorName == 'CRM_Contact_Selector_Custom') {
        $dao = &$selector->contactAdditionalIDQuery($params, $form->_action, $sortID);
      }
      else {
        $dao = &$selector->contactIDQuery($params, $form->_action, $sortID);
      }

      $form->_contactIds = [];
      if ($useTable) {
        $count = 0;
        $insertString = [];
        while ($dao->fetch()) {
          $count++;
          $insertString[] = " ( {$dao->contact_id} ) ";
          if ($count % 200 == 0) {
            $string = CRM_Utils_Array::implode(',', $insertString);
            $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
            CRM_Core_DAO::executeQuery($sql);
            $insertString = [];
          }
        }
        if (!empty($insertString)) {
          $string = CRM_Utils_Array::implode(',', $insertString);
          $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
          CRM_Core_DAO::executeQuery($sql);
        }
        $dao->free();
      }
      else {
        // CRM-7058
        $alreadySeen = [];
        while ($dao->fetch()) {
          if (!empty($dao->id)) {
            $form->_additionalIds[$dao->id] = $dao->id;
          }
          if (!CRM_Utils_Array::arrayKeyExists($dao->contact_id, $alreadySeen)) {
            $form->_contactIds[] = $dao->contact_id;
            $alreadySeen[$dao->contact_id] = 1;
          }
        }
        $dao->free();
      }
    }
    elseif (CRM_Utils_Array::value('radio_ts', $values) == 'ts_sel') {
      // selected contacts only
      // need to perform action on only selected contacts
      $insertString = [];
      $alreadySeen = [];
      foreach ($values as $name => $value) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($name);
        if (!empty($contactID)) {
          if ($useTable) {
            if (!CRM_Utils_Array::arrayKeyExists($contactID, $alreadySeen)) {
              $insertString[] = " ( {$contactID} ) ";
            }
          }
          else {
            if (!CRM_Utils_Array::arrayKeyExists($contactID, $alreadySeen)) {
              $form->_contactIds[] = $contactID;
            }
            if (!empty($additionalID) && is_numeric($additionalID)) {
              $form->_additionalIds[$additionalID] = $additionalID;
            }
          }
          $alreadySeen[$contactID] = 1;
        }
      }
      if (!empty($insertString)) {
        $string = CRM_Utils_Array::implode(',', $insertString);
        $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
        CRM_Core_DAO::executeQuery($sql);
      }
    }

    //contact type for pick up profiles as per selected contact types with subtypes
    //CRM-5521
    if ($selectedTypes = CRM_Utils_Array::value('contact_type', $values)) {
      if (!is_array($selectedTypes)) {
        $selectedTypes = explode(" ", $selectedTypes);
      }
      foreach ($selectedTypes as $ct => $dontcare) {
        if (strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR) === FALSE) {
          $form->_contactTypes[] = $ct;
        }
        else {
          $separator = strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR);
          $form->_contactTypes[] = substr($ct, $separator + 1);
        }
      }
    }

    if (!empty($form->_contactIds)) {
      $form->_componentClause = ' contact_a.id IN ( ' . CRM_Utils_Array::implode(',', $form->_contactIds) . ' ) ';
      $form->assign('totalSelectedContacts', count($form->_contactIds));

      $form->_componentIds = $form->_contactIds;
    }
  }

  /**
   * This function sets the default values for the form. Relationship that in edit/view action
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }

  /**
   * This function is used to add the rules for form.
   *
   * @return void
   * @access public
   */
  function addRules() {}

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Confirm Action'));
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {}
  //end of function

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

