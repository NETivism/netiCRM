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
class CRM_Contribute_BAO_Query {

  /**
   * static field for all the export/import contribution fields
   *
   * @var array
   * @static
   */
  static $_contributionFields = NULL;

  /**
   * Function get the import/export fields for contribution
   *
   * @return array self::$_contributionFields  associative array of contribution fields
   * @static
   */
  static function &getFields() {
    if (!self::$_contributionFields) {
      self::$_contributionFields = array();

      require_once 'CRM/Contribute/BAO/Contribution.php';
      $fields = &CRM_Contribute_BAO_Contribution::exportableFields();

      unset($fields['contribution_contact_id']);

      self::$_contributionFields = $fields;
    }
    return self::$_contributionFields;
  }

  /**
   * if contributions are involved, add the specific contribute fields
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    // if contribute mode add contribution id
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $query->_select['contribution_id'] = "civicrm_contribution.id as contribution_id";
      $query->_element['contribution_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
      $query->_groupByComponentClause = ' GROUP BY civicrm_contribution.id';
    }

    if (CRM_Utils_Array::value('contribution_type', $query->_returnProperties)) {
      $query->_select['contribution_type'] = "civicrm_contribution_type.name as contribution_type";
      $query->_element['contribution_type'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_type'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('contribution_type_id', $query->_returnProperties)) {
      $query->_select['contribution_type_id'] = "civicrm_contribution.contribution_type_id as contribution_type_id";
      $query->_element['contribution_type_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('contribution_note', $query->_returnProperties)) {
      $query->_select['contribution_note'] = "civicrm_note_contribution.note as contribution_note";
      $query->_element['contribution_note'] = 1;
      $query->_tables['contribution_note'] = 1;
    }

    // get contribution_status
    if (CRM_Utils_Array::value('contribution_status_id', $query->_returnProperties)) {
      $query->_select['contribution_status_id'] = "civicrm_contribution.contribution_status_id as contribution_status_id";
      $query->_element['contribution_status_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    // get contribution_status label
    if (CRM_Utils_Array::value('contribution_status', $query->_returnProperties)) {
      $query->_select['contribution_status'] = "contribution_status.label as contribution_status";
      $query->_element['contribution_status'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_status'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    // get payment instruments
    if (CRM_Utils_Array::value('payment_instrument', $query->_returnProperties)) {
      $query->_select['contribution_payment_instrument'] = "payment_instrument.name as contribution_payment_instrument";
      $query->_element['contribution_payment_instrument'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_payment_instrument'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('payment_instrument_id', $query->_returnProperties)) {
      $query->_select['payment_instrument_id'] = "civicrm_contribution.payment_instrument_id as payment_instrument_id";
      $query->_element['payment_instrument_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('check_number', $query->_returnProperties)) {
      $query->_select['contribution_check_number'] = "civicrm_contribution.check_number as contribution_check_number";
      $query->_element['contribution_check_number'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_whereTables['civicrm_contribution'] = 1;
    }

    // referrer and landing
    if (CRM_Utils_Array::value('contribution_referrer_type', $query->_returnProperties)) {
      $query->_select['contribution_referrer_type'] = "civicrm_track.referrer_type as contribution_referrer_type";
      $query->_element['contribution_referrer_type'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_referrer_network', $query->_returnProperties)) {
      $query->_select['contribution_referrer_network'] = "civicrm_track.referrer_network as contribution_referrer_network";
      $query->_element['contribution_referrer_network'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_referrer_url', $query->_returnProperties)) {
      $query->_select['contribution_referrer_url'] = "civicrm_track.referrer_url as contribution_referrer_url";
      $query->_element['contribution_referrer_url'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_landing', $query->_returnProperties)) {
      $query->_select['contribution_landing'] = "civicrm_track.landing as contribution_landing";
      $query->_element['contribution_landing'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    
    /* utm fields */
    if (CRM_Utils_Array::value('contribution_utm_source', $query->_returnProperties)) {
      $query->_select['contribution_utm_source'] = "civicrm_track.referrer_type as contribution_utm_source";
      $query->_element['contribution_utm_source'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_utm_medium', $query->_returnProperties)) {
      $query->_select['contribution_utm_medium'] = "civicrm_track.referrer_type as contribution_utm_medium";
      $query->_element['contribution_utm_medium'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_utm_campaign', $query->_returnProperties)) {
      $query->_select['contribution_utm_campaign'] = "civicrm_track.referrer_type as contribution_utm_campaign";
      $query->_element['contribution_utm_campaign'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_utm_term', $query->_returnProperties)) {
      $query->_select['contribution_utm_term'] = "civicrm_track.referrer_type as contribution_utm_term";
      $query->_element['contribution_utm_term'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
    if (CRM_Utils_Array::value('contribution_utm_content', $query->_returnProperties)) {
      $query->_select['contribution_utm_content'] = "civicrm_track.referrer_type as contribution_utm_content";
      $query->_element['contribution_utm_content'] = 1;
      $query->_tables['civicrm_track'] = 1;
    }
  }

  static function where(&$query) {
    $isTest = FALSE;
    $grouping = NULL;
    // Check contribution_test first, used in contribution_payment_processor_id.
    foreach (array_keys($query->_params) as $id) {
      if ($query->_params[$id][0] == 'contribution_test' && $query->_params[$id][2] == 1) {
        $isTest = TRUE;
        break;
      }
    }
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 13) == 'contribution_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        if ($query->_params[$id][0] == 'contribution_payment_processor_id' && $isTest) {
          $query->_params[$id][2] += 1;
        }
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }

    if ($grouping !== NULL &&
      !$isTest
    ) {
      $values = array('contribution_test', '=', 0, $grouping, 0);
      self::whereClauseSingle($values, $query);
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($op == 'LIKE' && $value === '!%' && !$wildcard) {
      $op = 'IS NULL';
      $value = '';
    }
    elseif ($op == 'LIKE' && $value === '%' && !$wildcard) {
      $op = 'IS NOT NULL';
      $value = '';
    }
    elseif ($op == 'LIKE' && !$wildcard) {
      // Refs #33503, '_' is used for match any single character.
      if (strstr($value, '\_')) {
        $value = '%' . trim($value, '%') . '%';
      }
      else {
        $value = '%' . trim(str_replace('_', '\_', $value), '%') . '%';
      }
    }

    $fields = array();
    $fields = self::getFields();
    if (!empty($value)) {
      // $quoteValue only used in qill.
      $quoteValue = "\"$value\"";
      $quoteValue = str_replace('\_', '_', $quoteValue);
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    switch ($name) {
      case 'contribution_created_date':
      case 'contribution_created_date_low':
      case 'contribution_created_date_low_time':
      case 'contribution_created_date_high':
      case 'contribution_created_date_high_time':
        // process to / from date
        $query->dateQueryBuilder($values, 'civicrm_contribution', 'contribution_created_date', 'created_date', 'Created Date');
        return;

      case 'contribution_date':
      case 'contribution_date_low':
      case 'contribution_date_low_time':
      case 'contribution_date_high':
      case 'contribution_date_high_time':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_contribution', 'contribution_date', 'receive_date', 'Contribution Date'
        );
        return;

      case 'contribution_receipt_date':
      case 'contribution_receipt_date_low':
      case 'contribution_receipt_date_low_time':
      case 'contribution_receipt_date_high':
      case 'contribution_receipt_date_high_time':
        // process to / from date
        $query->dateQueryBuilder($values, 'civicrm_contribution', 'contribution_receipt_date', 'receipt_date', 'Receipt Date');
        return;
      case 'contribution_month':
        $v = preg_replace('/[^0-9]/i', '', $value);
        $created_clause = "(EXTRACT(YEAR_MONTH FROM civicrm_contribution.created_date) = '$v' AND civicrm_contribution.receive_date IS NULL)";
        $receive_clause = "EXTRACT(YEAR_MONTH FROM civicrm_contribution.receive_date) = '$v'";

        $query->_where[$grouping][] = "( $created_clause OR $receive_clause )";
        $query->_qill[$grouping][] = ts('Filter by month') .' - '. $value;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_amount':
      case 'contribution_amount_low':
      case 'contribution_amount_high':
        // process min/max amount
        $query->numberRangeBuilder($values,
          'civicrm_contribution', 'contribution_amount', 'total_amount', 'Contribution Amount'
        );
        return;

      case 'contribution_total_amount':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.total_amount",
          $op, $value, "Money"
        );
        $query->_qill[$grouping][] = ts('Contribution Total Amount %1 %2', array(1 => $op, 2 => $value));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_thankyou_date_isnull':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.thankyou_date", "IS NULL");
        $query->_qill[$grouping][] = ts('Contribution Thank-you date is null');
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_receipt_date_isnull':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.receipt_date", "IS NULL");
        $query->_qill[$grouping][] = ts('Contribution Receipt date is null');
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_pdf_receipt_not_send':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_activity_send.id", "IS NULL");
        $query->_qill[$grouping][] = ts('Email Receipt') .' '. ts('IS NULL');
        $query->_tables['contribution_activity_email_pdf_receipt'] = $query->_whereTables['contribution_activity_email_pdf_receipt'] = 1;
        return;

      case 'contribution_pdf_receipt_not_print':
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_activity_print.id", "IS NULL");
          $query->_qill[$grouping][] = ts('Print Contribution Receipts') .' '. ts('IS NULL');
          $query->_tables['contribution_activity_print_pdf_receipt'] = $query->_whereTables['contribution_activity_print_pdf_receipt'] = 1;
          return;
  
      case 'contribution_type_id':
      case 'contribution_type':
        $types = CRM_Contribute_PseudoConstant::contributionType();
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$v] = $v;
            }
          }
          $contribution_type_id = CRM_Utils_Array::implode(',', $val);
          if (count($val) > 1) {
            $op = 'IN';
            $contribution_type_id = "({$contribution_type_id})";
          }
          $names = array_intersect_key($types, $val);
        }
        else {
          $op = '=';
          $contribution_type_id = $value;
          $names = array($types[$contribution_type_id]);
        }
        $query->_qill[$grouping][] = ts('Contribution Type - %1', array(1 => $op)) . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $names);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_type_id", $op, $contribution_type_id, "Integer");
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_page_id':
        require_once 'CRM/Contribute/PseudoConstant.php';
        $cPage = $value;
        $pages = CRM_Contribute_PseudoConstant::contributionPage();    
        if (is_array($cPage)) {
          foreach ($cPage as $k => $v) {
            if ($v) {
              $val[$v] = $v;
            }
          }
          $contribution_page_id = CRM_Utils_Array::implode(',', $val);
          if (count($val) > 1) {
            $op = 'IN';
            $contribution_page_id = "({$contribution_page_id})";
          }
          $names = array_intersect_key($pages, $val);
        }
        else {
          $op = '=';
          $contribution_page_id = $cPage;
          $names = array($pages[$contribution_page_id]);
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_page_id", $op, $contribution_page_id, "Integer");;
        $query->_qill[$grouping][] = ts('Contribution Page - %1', array(1 => $op)) . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $names);
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_pcp_made_through_id':
        require_once 'CRM/Contribute/PseudoConstant.php';
        $pcPage = $value;
        $pcpages = CRM_Contribute_PseudoConstant::pcPage();
        $query->_where[$grouping][] = "civicrm_contribution_soft.pcp_id = $pcPage";
        $query->_qill[$grouping][] = ts('Personal Campaign Page - %1', array(1 => $pcpages[$pcPage]));
        $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        return;

      case 'contribution_payment_instrument_id':
      case 'contribution_payment_instrument':
        require_once 'CRM/Contribute/PseudoConstant.php';
        $pis = CRM_Contribute_PseudoConstant::paymentInstrument();
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$v] = $v;
              $nameSelectedPi[] = $pis[$v];
            }
          }
          $instrument_ids = CRM_Utils_Array::implode(',', $val);
          $op = 'IN';
          $value = "({$instrument_ids})";
            $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.payment_instrument_id",
            $op, $value, "Integer"
          );
          $query->_qill[$grouping][] = ts('Paid By - %1', array(1 => CRM_Utils_Array::implode(', ', $nameSelectedPi)));
        }
        else {
          $pi = $value;
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.payment_instrument_id",
            $op, $value, "Integer"
          );
  
          $query->_qill[$grouping][] = ts('Paid By - %1', array(1 => $pis[$pi]));
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_payment_processor_id':
        $pps = CRM_Core_PseudoConstant::paymentProcessor();
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.payment_processor_id",
          $op, $value, "Integer"
        );
        // Test ppid is even, use name of odd id.
        if (!($value % 2)) {
          $value -= 1;
        }
        $query->_qill[$grouping][] = ts('Payment Processor').' - '.$pps[$value];
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_in_honor_of':
        $name = trim($value);
        $newName = str_replace(',', " ", $name);
        $pieces = explode(' ', $newName);
        foreach ($pieces as $piece) {
          $value = $strtolower(CRM_Core_DAO::escapeString(trim($piece)));
          $value = "'%$value%'";
          $sub[] = " ( contact_b.sort_name LIKE $value )";
        }

        $query->_where[$grouping][] = ' ( ' . CRM_Utils_Array::implode('  OR ', $sub) . ' ) ';
        $query->_qill[$grouping][] = ts('Honor name like - \'%1\'', array(1 => $name));
        $query->_tables['civicrm_contact_b'] = $query->_whereTables['civicrm_contact_b'] = 1;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_status_id':
      case 'contribution_status':
        require_once "CRM/Core/OptionGroup.php";
        $statusValues = CRM_Core_OptionGroup::values("contribution_status");
        if ($name == 'contribution_status') {
          $statusIndex = null;
          if (is_numeric($value) || ctype_digit($value)) {
            $value = CRM_Utils_Type::escape($value, 'Integer');
            if (isset($statusValues[$value])) {
              $statusIndex = $value;
            }
          }
          elseif (is_string($value)) {
            $value = CRM_Utils_Type::escape($value, 'String');
            $statusIndex = array_search($value, $statusValues);
          }

          // Check contribution status exit or not
          if ($statusIndex !== null && $statusIndex != false) {
            $value = $statusIndex;
          }
          else {
            $value = 0;
          }
        }

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

        $names = array();
        if (is_array($val)) {
          foreach ($val as $id => $dontCare) {
            $names[] = $statusValues[$id];
          }
        }
        else {
          $names[] = $statusValues[$value];
        }

        $query->_qill[$grouping][] = ts('Contribution Status %1', array(1 => $op)) . ' ' . CRM_Utils_Array::implode(' ' . ts('or') . ' ', $names);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_status_id",
          $op,
          $status,
          "Integer"
        );
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_source':
        $value = $strtolower(CRM_Core_DAO::escapeString($value));
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.source)" : "civicrm_contribution.source";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Source %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_source':
        /*
        $value = $strtolower(CRM_Core_DAO::escapeString($value));
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.source)" : "civicrm_contribution.source";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Source %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        */
        return;

      case 'contribution_trxn_id':
      case 'contribution_transaction_id':
        $wc = "civicrm_contribution.trxn_id";
        $value = $value.'%';
        if (!strstr(strtolower($op), 'null')) {
          $op = 'LIKE';
        }
        if ($wildcard) {
          $value = "%".trim($value, '%')."%";
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Transaction ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;
      case 'contribution_invoice_id':
        $wc = "civicrm_contribution.invoice_id";
        $value = $value.'%';
        if (!strstr(strtolower($op), 'null')) {
          $op = 'LIKE';
        }
        if ($wildcard) {
          $value = "%".trim($value, '%')."%";
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Invoice ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_receipt_id':
        $wc = "civicrm_contribution.receipt_id";
        $value = $value.'%';
        if (!strstr(strtolower($op), 'null')) {
          $op = 'LIKE';
        }
        if ($wildcard) {
          $value = "%".trim($value, '%')."%";
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Receipt ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_check_number':
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.check_number)" : "civicrm_contribution.check_number";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Check Number %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_is_test':
      case 'contribution_test':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.is_test", $op, $value, "Boolean");
        if ($value) {
          $query->_qill[$grouping][] = ts("Find Test Contributions");
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_is_pay_later':
      case 'contribution_pay_later':
        if ($value) {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.is_pay_later", $op, $value, "Boolean");
          $query->_qill[$grouping][] = ts("Find Pay Later Contributions");
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
        return;

      case 'contribution_recurring':
        if ($value == 1) {
          $query->_where[$grouping][] = "civicrm_contribution.contribution_recur_id IS NOT NULL";
          $query->_qill[$grouping][] = ts("Displaying Recurring Contributions");
        }
        elseif($value == 2){
          $query->_where[$grouping][] = "civicrm_contribution.contribution_recur_id IS NULL";
          $query->_qill[$grouping][] = ts("Displaying Non-Recurring Contributions");
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_recur_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_recur_id",
          $op, $value, "Integer"
        );
        $query->_qill[$grouping][] = ts('Recurring Contributions ID').' '.$op. ' '.$value;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.id", $op, $value, "Integer");
        $query->_qill[$grouping][] = ts('Contribution ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_note':
        $value = $strtolower(CRM_Core_DAO::escapeString($value));
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $wc = "civicrm_note_contribution.note";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Note %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = $query->_whereTables['contribution_note'] = 1;
        return;

      case 'contribution_membership_id':
        $query->_where[$grouping][] = " civicrm_membership.id $op $value";
        $query->_tables['contribution_membership'] = $query->_whereTables['contribution_membership'] = 1;

        return;

      case 'contribution_participant_id':
        $query->_where[$grouping][] = " civicrm_participant.id $op $value";
        $query->_tables['contribution_participant'] = $query->_whereTables['contribution_participant'] = 1;
        return;

      case 'contribution_pcp_display_in_roll':
        $query->_where[$grouping][] = " civicrm_contribution_soft.pcp_display_in_roll $op '$value'";
        if ($value) {
          $query->_qill[$grouping][] = ts("Display in Roll");
        }
        $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        return;

      //supporting search for currency type -- CRM-4711

      case 'contribution_currency_type':
        $currencySymbol = CRM_Core_PseudoConstant::currencySymbols('name');
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.currency",
          $op, $currencySymbol[$value], "String"
        );
        $query->_qill[$grouping][] = ts('Currency Type - %1', array(1 => $currencySymbol[$value]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_referrer_type':
        $qill = array();
        $trafficTypes = CRM_Core_PseudoConstant::referrerTypes();
        if (is_array($value)) {
          $search = "'".CRM_Utils_Array::implode("','", $value)."'";
          $query->_where[$grouping][] = " civicrm_track.referrer_type IN ($search)";
          foreach($value as $ttype) {
            $qill[] = $trafficTypes[$ttype];
          }
        }
        else {
          $query->_where[$grouping][] = " civicrm_track.referrer_type $op '$value'";
        }
        $query->_qill[$grouping][] = ts('Referrer Type') .'  -  '.CRM_Utils_Array::implode(",", $qill);
        $query->_tables['civicrm_track'] = $query->_whereTables['civicrm_track'] = 1;
        return;
      case 'contribution_referrer_network':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_track.referrer_network', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Referrer Network').' - '.$value;
        $query->_tables['civicrm_track'] = $query->_whereTables['civicrm_track'] = 1;
        return;
      case 'contribution_referrer_url':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_track.referrer_url', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Referrer URL').' - '.$value;
        $query->_tables['civicrm_track'] = $query->_whereTables['civicrm_track'] = 1;
        return;
      case 'contribution_landing':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_track.landing', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Landing Page').' - '.$value;
        $query->_tables['civicrm_track'] = $query->_whereTables['civicrm_track'] = 1;
        return;
      case 'contribution_utm_source':
      case 'contribution_utm_medium':
      case 'contribution_utm_campaign':
      case 'contribution_utm_term':
      case 'contribution_utm_content':
        $fieldName = str_replace('contribution_', '', $name);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_track.'.$fieldName, $op, $value, "String");
        $query->_qill[$grouping][] = $fieldName.' - '.$value;
        $query->_tables['civicrm_track'] = $query->_whereTables['civicrm_track'] = 1;
        return;

      case 'product_name':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_product.name', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Product Name') . ' - ' . $value;
        $query->_tables['civicrm_product'] = $query->_whereTables['civicrm_product'] = 1;
        return;

      case 'product_option':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_contribution_product.product_option', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Product Option') . ' - ' . $value;
        $query->_tables['civicrm_contribution_product'] = $query->_whereTables['civicrm_contribution_product'] = 1;
        return;

      // First contribution type:
      //   1: only single contribution or first time of recurring
      //   2: The second and above contribution of recurring
      case 'contribution_first_type':
        $sqlSingle = "SELECT id FROM civicrm_contribution WHERE contribution_recur_id IS NULL ";
        $sqlRecurFirst = "SELECT id FROM (SELECT * FROM civicrm_contribution WHERE contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) c";
        if ($value == 1) {
          $query->_where[$grouping][] = "(civicrm_contribution.id IN ($sqlSingle) OR civicrm_contribution.id IN ($sqlRecurFirst))";
          $query->_qill[$grouping][] = ts('Single and first contributions of recur');
        }
        if ($value == 2) {
          $query->_where[$grouping][] = "(civicrm_contribution.id NOT IN ($sqlSingle) AND civicrm_contribution.id NOT IN ($sqlRecurFirst))";
          $query->_qill[$grouping][] = ts('Not single and first contribution of recur');
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      default:
        //all other elements are handle in this case
        $fldName = substr($name, 13);
        $whereTable = $fields[$fldName];
        $value = trim($value);

        //contribution fields (decimal fields) which don't require a quote in where clause.
        $moneyFields = array('non_deductible_amount', 'fee_amount', 'net_amount');
        //date fields
        $dateFields = array('receive_date', 'cancel_date', 'receipt_date', 'thankyou_date', 'fulfilled_date');

        if (in_array($fldName, $dateFields)) {
          $dataType = "Date";
        }
        elseif (in_array($fldName, $moneyFields)) {
          $dataType = "Money";
        }
        else {
          $dataType = "String";
        }

        $wc = $whereTable['where'];
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, $dataType);
        $query->_qill[$grouping][] = "$whereTable[title] $op $quoteValue";
        list($tableName, $fieldName) = explode('.', $whereTable['where'], 2);
        $query->_tables[$tableName] = $query->_whereTables[$tableName] = 1;
        if ($tableName == 'civicrm_contribution_product') {
          $query->_tables['civicrm_product'] = $query->_whereTables['civicrm_product'] = 1;
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
        else {
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_contribution':
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
          $from = " INNER JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id ";
        }
        else {
          $from = " $side JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id ";
        }
        break;

      case 'civicrm_contribution_recur':
        $from = " $side JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id ";
        break;

      case 'civicrm_contribution_type':
        $from = " $side JOIN civicrm_contribution_type ON civicrm_contribution.contribution_type_id = civicrm_contribution_type.id ";
        break;

      case 'civicrm_contribution_page':
        $from = " $side JOIN civicrm_contribution_page ON civicrm_contribution.contribution_page ON civicrm_contribution.contribution_page.id";
        break;

      case 'civicrm_product':
        $from = " $side  JOIN civicrm_contribution_product ON civicrm_contribution_product.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_product ON civicrm_contribution_product.product_id =civicrm_product.id ";
        break;

      case 'contribution_payment_instrument':
        $from = " $side JOIN civicrm_option_group option_group_payment_instrument ON ( option_group_payment_instrument.name = 'payment_instrument')";
        $from .= " $side JOIN civicrm_option_value payment_instrument ON (civicrm_contribution.payment_instrument_id = payment_instrument.value
                               AND option_group_payment_instrument.id = payment_instrument.option_group_id ) ";
        break;

      case 'civicrm_contact_b':
        $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_contribution.honor_contact_id = contact_b.id )";
        break;

      case 'contribution_status':
        $from = " $side JOIN civicrm_option_group option_group_contribution_status ON (option_group_contribution_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value contribution_status ON (civicrm_contribution.contribution_status_id = contribution_status.value 
                               AND option_group_contribution_status.id = contribution_status.option_group_id ) ";
        break;

      case 'contribution_note':
        $from .= " $side JOIN civicrm_note civicrm_note_contribution ON ( civicrm_note_contribution.entity_table = 'civicrm_contribution' AND
                                                    civicrm_contribution.id = civicrm_note_contribution.entity_id )";
        break;

      case 'contribution_membership':
        $from = " $side  JOIN civicrm_membership_payment ON civicrm_membership_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_membership ON civicrm_membership_payment.membership_id = civicrm_membership.id ";
        break;

      case 'contribution_participant':
        $from = " $side  JOIN civicrm_participant_payment ON civicrm_participant_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_participant ON civicrm_participant_payment.participant_id = civicrm_participant.id ";
        break;

      case 'civicrm_contribution_soft':
        $from = " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id";
        break;

      case 'contribution_activity_email_pdf_receipt':
        $emailReceiptType = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
        if ($emailReceiptType && is_numeric($emailReceiptType)) {
          $from = " $side JOIN civicrm_activity civicrm_activity_send ON civicrm_contribution.id = civicrm_activity_send.source_record_id AND civicrm_activity_send.activity_type_id = ".$emailReceiptType;
        }
        break;

      case 'contribution_activity_print_pdf_receipt':
          $emailReceiptType = CRM_Core_OptionGroup::getValue('activity_type', 'Print Contribution Receipts', 'name');
          if ($emailReceiptType && is_numeric($emailReceiptType)) {
            $from = " $side JOIN civicrm_activity civicrm_activity_print ON civicrm_contribution.id = civicrm_activity_print.source_record_id AND civicrm_activity_print.activity_type_id = ".$emailReceiptType;
          }
          break;

      case 'civicrm_contribution_taiwanach':
          $from = " $side JOIN civicrm_contribution_taiwanach ON civicrm_contribution_taiwanach.contribution_recur_id = civicrm_contribution.contribution_recur_id";
          break;

      case 'civicrm_track':
        $from = " $side JOIN civicrm_track ON civicrm_track.entity_table = 'civicrm_contribution' AND civicrm_track.entity_id = civicrm_contribution.id";
        break;
    }
    return $from;
  }

  static function defaultReturnProperties($mode) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'contribution_type' => 1,
        'contribution_type_id' => 1,
        'contribution_source' => 1,
        'created_date' => 1,
        'receive_date' => 1,
        'thankyou_date' => 1,
        'cancel_date' => 1,
        'total_amount' => 1,
        'accounting_code' => 1,
        'payment_instrument' => 1,
        'payment_instrument_id' => 1,
        'check_number' => 1,
        'non_deductible_amount' => 1,
        'fee_amount' => 1,
        'net_amount' => 1,
        'trxn_id' => 1,
        'invoice_id' => 1,
        'currency' => 1,
        'cancel_date' => 1,
        'cancel_reason' => 1,
        'receipt_date' => 1,
        'thankyou_date' => 1,
        'product_name' => 1,
        'sku' => 1,
        'product_option' => 1,
        'fulfilled_date' => 1,
        'contribution_start_date' => 1,
        'contribution_end_date' => 1,
        'is_test' => 1,
        'is_pay_later' => 1,
        'contribution_status' => 1,
        'contribution_status_id' => 1,
        'contribution_recur_id' => 1,
        'amount_level' => 1,
        'contribution_note' => 1,
        'contribution_page_id' => 1,
        'receipt_id' => 1,
        'contribution_referrer_type' => 1,
      );

      // also get all the custom contribution properties
      require_once "CRM/Core/BAO/CustomField.php";
      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Contribution');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }
    return $properties;
  }

  /**
   * add all the elements shared between contribute search and advnaced search
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {
    require_once 'CRM/Utils/Money.php';

    //added contribution source
    $form->addNumber('contribution_id', ts('Contribution ID'));
    $form->addNumber('contribution_recur_id', ts('Recurring Contributions ID'));
    $form->addElement('text', 'contribution_source', ts('Source'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'source'));

    $form->addDate('contribution_created_date_low', ts('Created Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('contribution_created_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addDate('contribution_date_low', ts('Contribution Dates - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('contribution_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addDate('contribution_receipt_date_low', ts('Receipt Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('contribution_receipt_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addDate('contribution_month', ts('Filter by month'), FALSE, array('formatType' => 'custom', 'format' => 'yy-mm'));

    $form->add('text', 'contribution_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'contribution_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    //adding select option for curreny type -- CRM-4711
    $form->add('select', 'contribution_currency_type',
      ts('Currency'),
      array('' => ts('- select -')) +
      CRM_Core_PseudoConstant::currencySymbols('name')
    );

    $attrs = array('multiple' => 'multiple');
    $ctypes = CRM_Contribute_PseudoConstant::contributionType(NULL, NULL, TRUE);
    $form->addSelect(
      'contribution_type_id',
      ts('Contribution Type'),
      $ctypes,
      $attrs
    );

    $form->addSelect(
      'contribution_page_id',
      ts('Contribution Page'),
      CRM_Contribute_PseudoConstant::contributionPage(),
      $attrs
    );

    $form->addSelect(
      'contribution_payment_instrument_id',
      ts('Payment Instrument'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(), 
      $attrs
    );

    $form->addSelect(
      'contribution_pcp_made_through_id',
      ts('Personal Campaign Page'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::pcPage()
    );

    $form->addSelect(
      'contribution_payment_processor_id',
      ts('Payment Processor'),
      array('' => ts('- select -')) + CRM_Core_PseudoConstant::paymentProcessor()
    );

    $status = array();

    $statusValues = CRM_Core_OptionGroup::values("contribution_status");
    // Remove status values that are only used for recurring contributions or pledges (In Progress, Overdue).
    unset($statusValues['5']);
    unset($statusValues['6']);

    foreach ($statusValues as $key => $val) {
      $status[] = $form->createElement('advcheckbox', $key, NULL, $val);
    }

    $form->addGroup($status, 'contribution_status_id', ts('Contribution Status'));

    // add null checkboxes for thank you and receipt
    $form->addElement('checkbox', 'contribution_thankyou_date_isnull', ts('Thank-you date not set?'));
    $form->addElement('checkbox', 'contribution_receipt_date_isnull', ts('Receipt date not set?'));
    $emailReceiptType = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if ($emailReceiptType) {
      $form->addElement('checkbox', 'contribution_pdf_receipt_not_send', ts('Email receipt not sent?'));
    }
    $printReceiptType = CRM_Core_OptionGroup::getValue('activity_type', 'Print Contribution Receipts', 'name');
    if ($printReceiptType) {
      $form->addElement('checkbox', 'contribution_pdf_receipt_not_print', ts('Receipt is not print?'));
    }

    //add fields for honor search
    $form->addElement('text', 'contribution_in_honor_of', ts("In Honor Of"));

    $form->addElement('checkbox', 'contribution_test', ts('Find Test Contributions?'));
    $form->addElement('checkbox', 'contribution_pay_later', ts('Find Pay Later Contributions?'));

    //add field for transaction ID search
    $form->addElement('text', 'contribution_transaction_id', ts("Transaction ID"));
    $form->addElement('text', 'contribution_invoice_id', ts("Invoice ID"));
    $form->addElement('text', 'contribution_receipt_id', ts("Receipt ID"));

    $form->addSelect(
      'contribution_recurring',
      ts('Find Recurring Contributions?'),
      array(
        '' => ts('All'),
        1 => ts('Recurring Contribution'),
        2 => ts('Non-Recurring Contribution'),
      )
    );
    $form->addElement('text', 'contribution_check_number', ts('Check Number'));

    //add field for pcp display in roll search
    $form->addYesNo('contribution_pcp_display_in_roll', ts('Personal Campaign Page').' - '.ts('Display In Roll ?'));

    // add all the custom  searchable fields
    require_once 'CRM/Core/BAO/CustomGroup.php';
    $contribution = array('Contribution');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $contribution);
    if ($groupDetails) {
      require_once 'CRM/Core/BAO/CustomField.php';
      $form->assign('contributeGroupTree', $groupDetails);
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

    // tracking related
    $track = new CRM_Core_DAO_Track;
    $trackFields = $track->fields();
    $trafficTypes = CRM_Core_PseudoConstant::referrerTypes();
    unset($trafficTypes['unknown']);
    $form->addSelect(
      'contribution_referrer_type',
      ts('Referrer Type'),
      $trafficTypes,
      array('multiple' => 'multiple')
    );
    $form->addElement('text', 'contribution_referrer_network', ts("Referrer Network"));
    $form->addElement('text', 'contribution_referrer_url', ts("Referrer URL"));
    $form->addElement('text', 'contribution_landing', ts("Landing Page"));
    $form->addElement('text', 'contribution_utm_source', 'UTM Source');
    $form->addElement('text', 'contribution_utm_medium', 'UTM Medium');
    $form->addElement('text', 'contribution_utm_campaign', 'UTM Campaign');
    $form->addElement('text', 'contribution_utm_term', 'UTM Term');
    $form->addElement('text', 'contribution_utm_content', 'UTM Content');

    // premium filters
    require_once 'CRM/Contribute/DAO/Product.php';
    $product_dao = new CRM_Contribute_DAO_Product();
    $product_dao->is_active = 1;
    $product_dao->find();
    $product_name_select = $product_option_select = array();
    $product_name_select[""] = ts('- select -');
    $product_option_select[""] = ts('- select -');
    $product_option_data = array();

    while ($product_dao->fetch()) {
      $product_name_select[$product_dao->name] = $product_dao->sku ? $product_dao->name . " ( " . $product_dao->sku . " )" : $product_dao->name;
      $options = explode(',', $product_dao->options);
      $product_option_data[$product_dao->name] = array();

      foreach ($options as $v) {
        $trim_v = trim($v);
        $product_option_data[$product_dao->name][] = $trim_v;
      }
    }

    $form->addSelect(
      'product_name',
      ts('Product Name'),
      $product_name_select
    );

    // Use data-parent and data-parent-filter setting to associate the product_name with the product_option
    $form->addSelect(
      'product_option',
      ts('Product Option'),
      $product_option_select,
      array('data-parent' => 'product_name', 'data-parent-custom' => 0)
    );
    $product_option_select_elem = $form->getElement('product_option');

    foreach ($product_option_data as $product_name => $product_options) {
      $product_option_select_attr = array('data-parent-filter' => $product_name);

      foreach ($product_options as $product_option) {
        if (trim($product_option) !== '') {
          $product_option_select_elem->addOption($product_option, $product_option, $product_option_select_attr);
        }
      }
    }

    $form->assign('validCiviContribute', TRUE);
  }

  static function addShowHide(&$showHide) {
    $showHide->addHide('contributeForm');
    $showHide->addShow('contributeForm_show');
  }

  static function searchAction(&$row, $id) {}

  static function tableNames(&$tables) {
    //add contribution table
    if (CRM_Utils_Array::value('civicrm_product', $tables)) {
      $tables = array_merge(array('civicrm_contribution' => 1), $tables);
    }
  }
}

