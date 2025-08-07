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
class CRM_Pledge_BAO_Query {
  public $_qill;
  static function &getFields() {

    $fields = CRM_Pledge_BAO_Pledge::exportableFields();
    return $fields;
  }

  /**
   * build select for Pledge
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    if (($query->_mode & CRM_Contact_BAO_Query::MODE_PLEDGE) ||
      CRM_Utils_Array::value('pledge_id', $query->_returnProperties)
    ) {
      $query->_select['pledge_id'] = "civicrm_pledge.id as pledge_id";
      $query->_element['pledge_id'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    //add pledge select
    if (CRM_Utils_Array::value('pledge_amount', $query->_returnProperties)) {
      $query->_select['pledge_amount'] = "civicrm_pledge.amount as pledge_amount";
      $query->_element['pledge_amount'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_create_date', $query->_returnProperties)) {
      $query->_select['pledge_create_date'] = "civicrm_pledge.create_date as pledge_create_date";
      $query->_element['pledge_create_date'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_status_id', $query->_returnProperties)) {
      $query->_select['pledge_status_id'] = "pledge_status.value as pledge_status_id";
      $query->_element['pledge_status'] = 1;
      $query->_tables['pledge_status'] = $query->_whereTables['pledge_status'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_status', $query->_returnProperties)) {
      $query->_select['pledge_status'] = "pledge_status.label as pledge_status";
      $query->_element['pledge_status'] = 1;
      $query->_tables['pledge_status'] = $query->_whereTables['pledge_status'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_total_paid', $query->_returnProperties)) {
      $query->_select['pledge_total_paid'] = " (SELECT sum(civicrm_pledge_payment.actual_amount) FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id = 1 ) as pledge_total_paid";
      $query->_element['pledge_total_paid'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_next_pay_date', $query->_returnProperties)) {
      $query->_select['pledge_next_pay_date'] = " (SELECT civicrm_pledge_payment.scheduled_date FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id IN ( 2, 6 ) ORDER BY civicrm_pledge_payment.scheduled_date ASC LIMIT 0, 1) as pledge_next_pay_date";
      $query->_element['pledge_next_pay_date'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_next_pay_amount', $query->_returnProperties)) {
      $query->_select['pledge_next_pay_amount'] = " (SELECT civicrm_pledge_payment.scheduled_amount FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id = 2 ORDER BY civicrm_pledge_payment.scheduled_date ASC LIMIT 0, 1) as pledge_next_pay_amount";
      $query->_element['pledge_next_pay_amount'] = 1;

      $query->_select['pledge_outstanding_amount'] = " (SELECT sum(civicrm_pledge_payment.scheduled_amount) FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id = 6 ) as pledge_outstanding_amount";
      $query->_element['pledge_outstanding_amount'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_contribution_page_id', $query->_returnProperties)) {
      $query->_select['pledge_contribution_page_id'] = "civicrm_pledge.contribution_page_id as pledge_contribution_page_id";
      $query->_element['pledge_contribution_page_id'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_id', $query->_returnProperties)) {
      $query->_select['pledge_payment_id'] = "civicrm_pledge_payment.id as pledge_payment_id";
      $query->_element['pledge_payment_id'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_scheduled_amount', $query->_returnProperties)) {
      $query->_select['pledge_payment_scheduled_amount'] = "civicrm_pledge_payment.scheduled_amount as pledge_payment_scheduled_amount";
      $query->_element['pledge_payment_scheduled_amount'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_scheduled_date', $query->_returnProperties)) {
      $query->_select['pledge_payment_scheduled_date'] = "civicrm_pledge_payment.scheduled_date as pledge_payment_scheduled_date";
      $query->_element['pledge_payment_scheduled_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_paid_amount', $query->_returnProperties)) {
      $query->_select['pledge_payment_paid_amount'] = "civicrm_pledge_payment.actual_amount as pledge_payment_paid_amount";
      $query->_element['pledge_payment_paid_amount'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_paid_date', $query->_returnProperties)) {
      $query->_select['pledge_payment_paid_date'] = "payment_contribution.receive_date as pledge_payment_paid_date";
      $query->_element['pledge_payment_paid_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
      $query->_tables['payment_contribution'] = $query->_whereTables['payment_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_reminder_date', $query->_returnProperties)) {
      $query->_select['pledge_payment_reminder_date'] = "civicrm_pledge_payment.reminder_date as pledge_payment_reminder_date";
      $query->_element['pledge_payment_reminder_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_reminder_count', $query->_returnProperties)) {
      $query->_select['pledge_payment_reminder_count'] = "civicrm_pledge_payment.reminder_count as pledge_payment_reminder_count";
      $query->_element['pledge_payment_reminder_count'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_status_id', $query->_returnProperties)) {
      $query->_select['pledge_payment_status_id'] = "payment_status.name as pledge_payment_status_id";
      $query->_element['pledge_payment_status_id'] = 1;
      $query->_tables['payment_status'] = $query->_whereTables['payment_status'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_payment_status', $query->_returnProperties)) {
      $query->_select['pledge_payment_status'] = "payment_status.label as pledge_payment_status";
      $query->_element['pledge_payment_status'] = 1;
      $query->_tables['payment_status'] = $query->_whereTables['payment_status'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_frequency_interval', $query->_returnProperties)) {
      $query->_select['pledge_frequency_interval'] = "civicrm_pledge.frequency_interval as pledge_frequency_interval";
      $query->_element['pledge_frequency_interval'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_frequency_unit', $query->_returnProperties)) {
      $query->_select['pledge_frequency_unit'] = "civicrm_pledge.frequency_unit as pledge_frequency_unit";
      $query->_element['pledge_frequency_unit'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (CRM_Utils_Array::value('pledge_is_test', $query->_returnProperties)) {
      $query->_select['pledge_is_test'] = "civicrm_pledge.is_test as pledge_is_test";
      $query->_element['pledge_is_test'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }
  }

  static function where(&$query) {
    $isTest = FALSE;
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 7) == 'pledge_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        if ($query->_params[$id][0] == 'pledge_test') {
          $isTest = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }

    if ($grouping !== NULL &&
      !$isTest
    ) {
      $values = ['pledge_test', '=', 0, $grouping, 0];
      self::whereClauseSingle($values, $query);
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    switch ($name) {
      case 'pledge_create_date_low':
      case 'pledge_create_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_create_date', 'create_date', 'Pledge Made'
        );
      case 'pledge_start_date_low':
      case 'pledge_start_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_start_date', 'start_date', 'Pledge Start Date'
        );
        return;

      case 'pledge_end_date_low':
      case 'pledge_end_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_end_date', 'end_date', 'Pledge End Date'
        );
        return;

      case 'pledge_payment_date_low':
      case 'pledge_payment_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge_payment', 'pledge_payment_date', 'scheduled_date', 'Payment Scheduled'
        );
        return;

      case 'pledge_amount':
      case 'pledge_amount_low':
      case 'pledge_amount_high':
        // process min/max amount
        $query->numberRangeBuilder($values,
          'civicrm_pledge', 'pledge_amount', 'amount', 'Pledge Amount'
        );
        return;

      case 'pledge_status_id':
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$k] = $k;
            }
          }

          $status = CRM_Utils_Array::implode(',', $val);

          if (count($val) > 1) {
            $op = 'IN';
            $status = "({$status})";
          }
        }
        else {
          $op = '=';
          $status = $value;
        }


        $statusValues = CRM_Core_OptionGroup::values("contribution_status");

        $names = [];
        if (is_array($val)) {
          foreach ($val as $id => $dontCare) {
            $names[] = $statusValues[$id];
          }
        }
        else {
          $names[] = $statusValues[$value];
        }

        $query->_qill[$grouping][] = ts('Pledge Status %1', [1 => $op]) . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $names);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.status_id",
          $op,
          $status,
          "Integer"
        );
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_payment_status_id':
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$k] = $k;
            }
          }

          $status = CRM_Utils_Array::implode(',', $val);

          if (count($val) > 1) {
            $op = 'IN';
            $status = "({$status})";
          }
        }
        else {
          $op = '=';
          $status = $value;
        }


        $statusValues = CRM_Core_OptionGroup::values("contribution_status");

        $names = [];
        if (is_array($val)) {
          foreach ($val as $id => $dontCare) {
            $names[] = $statusValues[$id];
          }
        }
        else {
          $names[] = $statusValues[$value];
        }

        $query->_qill[$grouping][] = ts('Pledge Payment Status %1', [1 => $op]) . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $names);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge_payment.status_id",
          $op,
          $status,
          "Integer"
        );
        $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
        return;

      case 'pledge_test':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.is_test",
          $op,
          $value,
          "Integer"
        );
        if ($value) {
          $query->_qill[$grouping][] = ts("Find Test Pledges");
        }
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_contribution_type_id':

        $type = CRM_Contribute_PseudoConstant::contributionType($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.contribution_type_id",
          $op,
          $value,
          "Integer"
        );
        $query->_qill[$grouping][] = ts('Contribution Type - %1', [1 => $type]);
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_contribution_page_id':

        $page = CRM_Contribute_PseudoConstant::contributionPage($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.contribution_page_id",
          $op,
          $value,
          "Integer"
        );
        $query->_qill[$grouping][] = ts('Contribution Page - %1', [1 => $page]);
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_in_honor_of':
        $name = trim($value);
        $newName = str_replace(',', " ", $name);
        $pieces = explode(' ', $newName);
        foreach ($pieces as $piece) {
          $value = mb_strtolower(CRM_Core_DAO::escapeString(trim($piece)), 'UTf-8');
          $value = "'%$value%'";
          $sub[] = " ( pledge_contact_b.sort_name LIKE $value )";
        }

        $query->_where[$grouping][] = ' ( ' . CRM_Utils_Array::implode('  OR ', $sub) . ' ) ';
        $query->_qill[$grouping][] = ts('Honor name like - \'%1\'', [1 => $name]);
        $query->_tables['pledge_contact_b'] = $query->_whereTables['pledge_contact_b'] = 1;
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_id':
        $query->_where[$grouping][] = "civicrm_pledge.id $op $value";
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_frequency_interval':
        $query->_where[$grouping][] = "civicrm_pledge.frequency_interval $op $value";
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_frequency_unit':
        $query->_where[$grouping][] = "civicrm_pledge.frequency_unit $op $value";
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;

    switch ($name) {
      case 'civicrm_pledge':
        $from = " $side JOIN civicrm_pledge  ON civicrm_pledge.contact_id = contact_a.id ";
        break;

      case 'pledge_status':
        $from .= " $side JOIN civicrm_option_group option_group_pledge_status ON (option_group_pledge_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value pledge_status ON (civicrm_pledge.status_id = pledge_status.value AND option_group_pledge_status.id = pledge_status.option_group_id ) ";
        break;

      case 'pledge_contribution_type':
        $from .= " $side JOIN civicrm_contribution_type ON civicrm_pledge.contribution_type_id = civicrm_contribution_type.id ";
        break;

      case 'pledge_contact_b':
        $from .= " $side JOIN civicrm_contact pledge_contact_b ON (civicrm_pledge.honor_contact_id = pledge_contact_b.id )";
        break;

      case 'civicrm_pledge_payment':
        $from .= " $side JOIN civicrm_pledge_payment  ON civicrm_pledge_payment.pledge_id = civicrm_pledge.id ";
        break;

      case 'payment_contribution':
        $from .= " $side JOIN civicrm_contribution payment_contribution ON civicrm_pledge_payment.contribution_id  = payment_contribution.id ";
        break;

      case 'payment_status':
        $from .= " $side JOIN civicrm_option_group option_group_payment_status ON (option_group_payment_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value payment_status ON (civicrm_pledge_payment.status_id = payment_status.value AND option_group_payment_status.id = payment_status.option_group_id ) ";
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
   * Ideally this function should include fields that are displayed in the selector
   */
  static function defaultReturnProperties($mode) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $properties = [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'pledge_id' => 1,
        'pledge_amount' => 1,
        'pledge_total_paid' => 1,
        'pledge_create_date' => 1,
        'pledge_next_pay_date' => 1,
        'pledge_next_pay_amount' => 1,
        'pledge_status' => 1,
        'pledge_status_id' => 1,
        'pledge_is_test' => 1,
        'pledge_contribution_page_id' => 1,
        'pledge_frequency_interval' => 1,
        'pledge_frequency_unit' => 1,
      ];
    }
    return $properties;
  }

  /**
   * This includes any extra fields that might need for export etc
   */
  static function extraReturnProperties($mode) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $properties = [
        'pledge_balance_amount' => 1,
        'pledge_payment_id' => 1,
        'pledge_payment_scheduled_amount' => 1,
        'pledge_payment_scheduled_date' => 1,
        'pledge_payment_paid_amount' => 1,
        'pledge_payment_paid_date' => 1,
        'pledge_payment_reminder_date' => 1,
        'pledge_payment_reminder_count' => 1,
        'pledge_payment_status_id' => 1,
        'pledge_payment_status' => 1,
      ];

      // also get all the custom pledge properties

      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Pledge');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }
    return $properties;
  }

  static function buildSearchForm(&$form) {
    // pledge related dates
    $form->addDate('pledge_start_date_low', ts('Payments Start Date - From'), FALSE, ['formatType' => 'searchDate']);
    $form->addDate('pledge_start_date_high', ts('To'), FALSE, ['formatType' => 'searchDate']);

    $form->addDate('pledge_end_date_low', ts('Payments Ended Date - From'), FALSE, ['formatType' => 'searchDate']);
    $form->addDate('pledge_end_date_high', ts('To'), FALSE, ['formatType' => 'searchDate']);

    $form->addDate('pledge_create_date_low', ts('Pledge Made - From'), FALSE, ['formatType' => 'searchDate']);
    $form->addDate('pledge_create_date_high', ts('To'), FALSE, ['formatType' => 'searchDate']);

    // pledge payment related dates
    $form->addDate('pledge_payment_date_low', ts('Payment Scheduled - From'), FALSE, ['formatType' => 'searchDate']);
    $form->addDate('pledge_payment_date_high', ts('To'), FALSE, ['formatType' => 'searchDate']);

    $form->addElement('checkbox', 'pledge_test', ts('Find Test Pledges?'));


    $form->add('text', 'pledge_amount_low', ts('From'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('pledge_amount_low', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('9.99', ' ')]), 'money');

    $form->add('text', 'pledge_amount_high', ts('To'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('pledge_amount_high', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');


    $statusValues = CRM_Contribute_PseudoConstant::contributionStatus();

    // Remove status values that are only used for recurring contributions for now (Failed and In Progress).
    unset($statusValues['4']);

    foreach ($statusValues as $key => $val) {
      $status[] = $form->createElement('advcheckbox', $key, NULL, $val);
    }

    $form->addGroup($status, 'pledge_status_id', ts('Pledge Status'));

    //unset in progress for payment
    unset($statusValues['5']);

    foreach ($statusValues as $key => $val) {
      $paymentStatus[] = $form->createElement('advcheckbox', $key, NULL, $val);
    }

    $form->addGroup($paymentStatus, 'pledge_payment_status_id', ts('Pledge Payment Status'));


    $form->add('select', 'pledge_contribution_type_id',
      ts('Contribution Type'),
      ['' => ts('- select -')] +
      CRM_Contribute_PseudoConstant::contributionType()
    );

    $form->add('select', 'pledge_contribution_page_id',
      ts('Contribution Page'),
      ['' => ts('- select -')] +
      CRM_Contribute_PseudoConstant::contributionPage()
    );

    //add fields for honor search
    $form->addElement('text', 'pledge_in_honor_of', ts("In Honor Of"));

    //add fields for pledge frequency
    $form->add('text', 'pledge_frequency_interval', ts("Every"), ['size' => 8, 'maxlength' => 8]);

    $frequencies = CRM_Core_OptionGroup::values('recur_frequency_units');
    foreach ($frequencies as $val => $label) {
      $freqUnitsDisplay["'{$val}'"] = ts('%1(s)', [1 => $label]);
    }

    $form->add('select', 'pledge_frequency_unit',
      ts('Pledge Frequency'),
      ['' => ts('- select -')] + $freqUnitsDisplay
    );

    // add all the custom  searchable fields

    $pledge = ['Pledge'];
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $pledge);
    if ($groupDetails) {

      $form->assign('pledgeGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }

    $form->assign('validCiviPledge', TRUE);
  }

  static function searchAction(&$row, $id) {}

  static function tableNames(&$tables) {
    //add status table
    if (CRM_Utils_Array::value('pledge_status', $tables) ||
      CRM_Utils_Array::value('civicrm_pledge_payment', $tables)
    ) {
      $tables = array_merge(['civicrm_pledge' => 1], $tables);
    }
  }
}

