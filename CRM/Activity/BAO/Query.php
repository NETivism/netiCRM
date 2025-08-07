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
class CRM_Activity_BAO_Query {

  public $_qill;
  /**
   * build select for Case
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    if (CRM_Utils_Array::value('activity_id', $query->_returnProperties)) {
      $query->_select['activity_id'] = "civicrm_activity.id as activity_id";
      $query->_element['activity_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_type_id', $query->_returnProperties)) {
      $query->_select['activity_type_id'] = "activity_type.id as activity_type_id";
      $query->_element['activity_type_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_type'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_type'] = 1;
    }

    if (CRM_Utils_Array::value('activity_type', $query->_returnProperties)) {
      $query->_select['activity_type'] = "activity_type.label as activity_type";
      $query->_element['activity_type'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_type'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_type'] = 1;
    }

    if (CRM_Utils_Array::value('activity_subject', $query->_returnProperties)) {
      $query->_select['activity_subject'] = "civicrm_activity.subject as activity_subject";
      $query->_element['activity_subject'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_date_time', $query->_returnProperties)) {
      $query->_select['activity_date_time'] = "civicrm_activity.activity_date_time as activity_date_time";
      $query->_element['activity_date_time'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_status_id', $query->_returnProperties)) {
      $query->_select['activity_status_id'] = "activity_status.id as activity_status_id";
      $query->_element['activity_status_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_status'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_status'] = 1;
    }

    if (CRM_Utils_Array::value('activity_status', $query->_returnProperties)) {
      $query->_select['activity_status'] = "activity_status.label as activity_status";
      $query->_element['activity_status'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_status'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_status'] = 1;
    }

    if (CRM_Utils_Array::value('activity_duration', $query->_returnProperties)) {
      $query->_select['activity_duration'] = "civicrm_activity.duration as activity_duration";
      $query->_element['activity_duration'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_location', $query->_returnProperties)) {
      $query->_select['activity_location'] = "civicrm_activity.location as activity_location";
      $query->_element['activity_location'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_details', $query->_returnProperties)) {
      $query->_select['activity_details'] = "civicrm_activity.details as activity_details";
      $query->_element['activity_details'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('source_record_id', $query->_returnProperties)) {
      $query->_select['source_record_id'] = "civicrm_activity.source_record_id as source_record_id";
      $query->_element['source_record_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_is_test', $query->_returnProperties)) {
      $query->_select['activity_is_test'] = "civicrm_activity.is_test as activity_is_test";
      $query->_element['activity_is_test'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }
  }

  /**
   * Given a list of conditions in query generate the required
   * where clause
   *
   * @return void
   * @access public
   */
  static function where(&$query) {
    $isTest = FALSE;
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 9) == 'activity_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
        if ($query->_params[$id][0] == 'activity_test') {
          $isTest = TRUE;
        }
      }
    }

    if ($grouping !== NULL &&
      !$isTest
    ) {
      $values = ['activity_test', '=', 0, $grouping, 0];
      self::whereClauseSingle($values, $query);
    }
  }

  /**
   * where clause for a single field
   *
   * @return void
   * @access public
   */
  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $query->_skipDeleteClause = TRUE;
    }

    switch ($name) {
      case 'activity_type_id':
        $types = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
        $clause = [];
        if (is_array($value)) {
          foreach ($value as $id) {
            if (CRM_Utils_Array::arrayKeyExists($id, $types)) {
              $clause[] = "'" . CRM_Utils_Type::escape($types[$id], 'String') . "'";
            }
          }
          $activityTypes = CRM_Utils_Array::implode(',', $value);
        }
        else {
          $clause[] = "'" . CRM_Utils_Type::escape($value, 'String') . "'";
          $activityTypes = $value;
        }
        $query->_where[$grouping][] = ' civicrm_activity.activity_type_id IN (' . $activityTypes . ')';
        $query->_qill[$grouping][] = ts('Activity Type') . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $clause);
        break;

      case 'source_record_id':
      case 'activity_record_id':
        if (!$value) {
          break;
        }
        $value = CRM_Utils_Type::escape($value, 'Integer');
        $query->_where[$grouping][] = " civicrm_activity.source_record_id = $value";
        $query->_qill[$grouping][] = ts('Source Record ID');
        break;

      case 'activity_survey_id':
        if (!$value) {
          break;
        }
        $value = CRM_Utils_Type::escape($value, 'Integer');
        $query->_where[$grouping][] = " source_record_id = $value";
        $query->_qill[$grouping][] = ts('Survey') . ' - ' . CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'title');
        break;

      case 'activity_role':
        CRM_Contact_BAO_Query::$_activityRole = $values[2];

        //for activity target name
        $activityTargetName = $query->getWhereValues('activity_contact_name', $grouping);
        if (!$activityTargetName[2]) {
          $name = NULL;
        }
        else {
          $name = trim($activityTargetName[2]);
          $name = mb_strtolower(CRM_Core_DAO::escapeString($name), 'UTF-8');
        }

        $query->_tables['civicrm_activity_contact'] = $query->_whereTables['civicrm_activity_contact'] = 1;
        $query->_where[$grouping][] = " contact_b.is_deleted = 0 AND contact_b.sort_name LIKE '%{$name}%'";

        if ($values[2] == 1) {
          $query->_where[$grouping][] = " civicrm_activity.source_contact_id = contact_b.id";
          $query->_qill[$grouping][] = ts('Activity created by') . " '$name'";
        }
        elseif ($values[2] == 2) {
          $query->_where[$grouping][] = " civicrm_activity_assignment.activity_id = civicrm_activity.id AND civicrm_activity_assignment.assignee_contact_id = contact_b.id";
          $query->_tables['civicrm_activity_assignment'] = $query->_whereTables['civicrm_activity_assignment'] = 1;
          $query->_qill[$grouping][] = ts('Activity assigned to') . " '$name'";
        }
        elseif ($values[3] == 3) {
          $query->_where[$grouping][] = " civicrm_activity_target.target_contact_id = contact_b.id";
          $query->_qill[$grouping][] = ts('Target Contact') . " '$name'";
        }
        break;

      case 'activity_status':
        $status = CRM_Core_PseudoConstant::activityStatus();
        $clause = [];
        if (is_array($value)) {
          foreach ($value as $v) {
            $clause[] = "'" . CRM_Utils_Type::escape($status[$v], 'String') . "'";
          }
        }
        else {
          $clause[] = "'" . CRM_Utils_Type::escape($value, 'String') . "'";
        }
        $query->_where[$grouping][] = ' civicrm_activity.status_id IN (' . CRM_Utils_Array::implode(',', $value) . ')';
        $query->_qill[$grouping][] = ts('Activity Status') . ' - ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $clause);
        break;

      case 'activity_subject':
        $n = trim($value);
        $value = mb_strtolower(CRM_Core_DAO::escapeString($n), 'UTF-8');
        if ($wildcard) {
          if (strpos($value, '%') !== FALSE) {
            // only add wild card if not there
            $value = "'$value'";
          }
          else {
            $value = "'%$value%'";
          }
          $op = 'LIKE';
        }
        else {
          $value = "'$value'";
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_activity.subject)" : "civicrm_activity.subject";
        $query->_where[$grouping][] = " $wc $op $value";
        $query->_qill[$grouping][] = ts('Subject') . " $op - '$n'";
        break;

      case 'activity_test':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_activity.is_test", $op, $value, "Integer");
        if ($value) {
          $query->_qill[$grouping][] = ts('Find Test Activities');
        }
        break;

      case 'activity_date':
      case 'activity_date_low':
      case 'activity_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_activity', 'activity_date', 'activity_date_time', ts('Activity Date')
        );
        break;

      case 'activity_tags':

        $value = array_keys($value);
        $activityTags = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_activity');

        $names = [];
        $val = [];
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $names[] = $activityTags[$v];
          }
        }
        $query->_where[$grouping][] = "civicrm_activity_tag.tag_id IN (" . CRM_Utils_Array::implode(",", $value) . ")";
        $query->_qill[$grouping][] = ts('Activity Tag %1', [1 => $op]) . ' ' . CRM_Utils_Array::implode(' ' . ts('OR') . ' ', $names);
        $query->_tables['civicrm_activity_tag'] = $query->_whereTables['civicrm_activity_tag'] = 1;
        break;

      case 'parent_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_activity.parent_id", $op, $value, "Integer");
        $query->_qill[$grouping][] = ts('Task Parent') . ' = ' . $value;
        break;
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_activity':
        $from .= " $side JOIN civicrm_activity ON ( civicrm_activity.source_contact_id = contact_a.id AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )";
        break;

      case 'civicrm_activity_contact':

        $activityRole = CRM_Contact_BAO_Query::$_activityRole;
        if ($activityRole == 1) {
          $from .= " $side JOIN civicrm_contact contact_b ON civicrm_activity.source_contact_id = contact_b.id
                           LEFT JOIN civicrm_email email_b ON (contact_b.id = email_b.contact_id AND email_b.is_primary = 1)";
        }
        elseif ($activityRole == 2) {
          $from .= " $side JOIN civicrm_activity_assignment ON civicrm_activity.id = civicrm_activity_assignment.activity_id ";
          $from .= " $side JOIN civicrm_contact contact_b ON civicrm_activity_assignment.assignee_contact_id = contact_b.id
                           LEFT JOIN civicrm_email email_b ON (contact_b.id = email_b.contact_id AND email_b.is_primary = 1)";
        }
        elseif ($activityRole == 3) {
          $from .= " $side JOIN civicrm_activity_target ON civicrm_activity_target.activity_id = civicrm_activity.id ";
          $from .= " $side JOIN civicrm_contact contact_b ON civicrm_activity_target.target_contact_id = contact_b.id
                           LEFT JOIN civicrm_email email_b ON (contact_b.id = email_b.contact_id AND email_b.is_primary = 1)";
        }
        break;

      case 'activity_status':
        $from = " $side JOIN civicrm_option_group option_group_activity_status ON (option_group_activity_status.name = 'activity_status')";
        $from .= " $side JOIN civicrm_option_value activity_status ON (civicrm_activity.status_id = activity_status.value 
                               AND option_group_activity_status.id = activity_status.option_group_id ) ";
        break;

      case 'activity_type':
        $from = " $side JOIN civicrm_option_group option_group_activity_type ON (option_group_activity_type.name = 'activity_type')";
        $from .= " $side JOIN civicrm_option_value activity_type ON (civicrm_activity.activity_type_id = activity_type.value 
                               AND option_group_activity_type.id = activity_type.option_group_id ) ";
        break;

      case 'civicrm_activity_tag':
        $from .= " $side JOIN civicrm_entity_tag as civicrm_activity_tag ON ( civicrm_activity_tag.entity_table = 'civicrm_activity' AND civicrm_activity_tag.entity_id = civicrm_activity.id ) ";
        break;
    }

    return $from;
  }

  /**
   * getter for the qill object
   *
   * @return string
   * @access public
   */
  function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  /**
   * add all the elements shared between case activity search  and advanaced search
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {
    $form->addElement('text', 'activity_contact_name', ts('Contact Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $activityOptions = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    asort($activityOptions);
    $attrmultiple = ['multiple' => 'multiple'];
    $form->addElement('select', 'activity_type_id', ts('Activity Type(s)'), $activityOptions, $attrmultiple);

    $form->addDate('activity_date_low', ts('Activity Dates - From'), FALSE, ['formatType' => 'searchDate']);
    $form->addDate('activity_date_high', ts('To'), FALSE, ['formatType' => 'searchDate']);

    $activityRoles = [1 => ts('Created by'), 2 => ts('Assigned to'), 3 => ts('Target Contact')];
    $form->addRadio('activity_role', NULL, $activityRoles, NULL, '<br />');
    $form->setDefaults(['activity_role' => 1]);

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $form->addElement('select', 'activity_status', ts('Activity Status'), $activityStatus, $attrmultiple);

    $form->setDefaults(['activity_status' => [1, 2]]);
    $form->addElement('text', 'activity_subject', ts('Subject'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
    $form->addElement('checkbox', 'activity_test', ts('Find Test Activities?'));

    $activity_tags = CRM_Core_BAO_Tag::getTags('civicrm_activity');
    if (!empty($activity_tags)) {
      $form->addSelect('activity_tags', ts('Activity Tag(s)'), $activity_tags, $attrmultiple);
    }


    $surveys = ['' => ts('- none -')] + CRM_Campaign_BAO_Survey::getSurveyList();
    $form->add('select', 'activity_survey_id', ts('Survey'), $surveys, FALSE);


    $extends = ['Activity'];
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {

      $form->assign('activityGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, FALSE, TRUE);
        }
      }
    }
  }

  static function addShowHide(&$showHide) {
    $showHide->addHide('caseActivityForm');
    $showHide->addShow('caseActivityForm_show');
  }

  static function defaultReturnProperties($mode) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $properties = [
        'activity_id' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'activity_type' => 1,
        'activity_subject' => 1,
        'activity_date_time' => 1,
        'activity_duration' => 1,
        'activity_location' => 1,
        'activity_details' => 1,
        'activity_status' => 1,
        'source_contact_id' => 1,
        'source_record_id' => 1,
        'activity_is_test' => 1,
      ];

      // also get all the custom activity properties

      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Activity');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }
    return $properties;
  }
}

