<?php
/*
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ContributionPage
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a contribution_page
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'contribution_page'
 * @example ContributionPageCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields contribution_page_create}
 * @access public
 */
function civicrm_api3_contribution_page_create($params) {
  if(is_array($params['payment_processor']) && !empty($params['payment_processor'])){
    if(is_numeric(reset($params['payment_processor']))){
      $params['payment_processor'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $params['payment_processor']);
    }
    else{
      $params['payment_processor'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($params['payment_processor']));
    }
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
/*
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_page_create_spec(&$params) {
  $params['contribution_type_id']['api.required'] = 1;
}

/**
 * Returns array of contribution_pages  matching a set of one or more group properties
 *
 * @param array $params  (referance) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all contribution_pages will be returned
 *
 * @return array  (referance) Array of matching contribution_pages
 * {@getfields contribution_page_get}
 * @access public
 */
function civicrm_api3_contribution_page_get($params) {
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  if (!empty($result['values'])) {
    foreach($result['values'] as $idx => &$contributionPage) {
      _civicrm_api3_contribution_page_getachieved($contributionPage, $contributionPage['id']);
      _civicrm_api3_contribution_page_getamount($contributionPage, $contributionPage['id']);
    }
  }

  return $result;
}

/**
 * delete an existing contribution_page
 *
 * This method is used to delete any existing contribution_page. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params  (reference) array containing id of the group
 *                       to be deleted
 *
 * @return array  (referance) returns flag true if successfull, error
 *                message otherwise
 * {@getfields contribution_page_delete}
 * @access public
 */
function civicrm_api3_contribution_page_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


function _civicrm_api3_contribution_page_getamount(&$page, $pageId) {
  if ($pageId) {
    $fee = CRM_Contribute_BAO_ContributionPage::feeBlock($pageId);
    $feeBlock = [];
    foreach($fee['label'] as $idx => $label) {
      if (isset($fee['value'][$idx]) && $fee['value'][$idx] !== '') {
        $grouping = !empty($fee['grouping'][$idx]) ? $fee['grouping'][$idx] : "all";
        $feeBlock[$grouping][] = [
          'label' => $label,
          'value' => $fee['value'][$idx],
        ];
      }
    }
    $page['price_set_id'] = !empty($fee['price_set_id']) ? $fee['price_set_id'] : 0;
    $page['fee_block'] = $feeBlock;
  }
}

function _civicrm_api3_contribution_page_getachieved(&$page, $pageId) {
  $achieved = CRM_Contribute_BAO_ContributionPage::goalAchieved($pageId);
  if (!empty($achieved)) {
    $page['goal_type'] = $achieved['type'];
    $page['goal_label'] = $achieved['label'];
    $page['goal'] = $achieved['goal'];
    $page['goal_achieved'] = $achieved['achieved'] ? 1 : 0;
    $page['goal_achieved_current'] = $achieved['current'];
    $page['goal_achieved_percent'] = $achieved['percent'];
    unset($page['goal_recurring']);
    unset($page['goal_amount']);
  }
  else {
    $page['goal_type'] = '';
  }
}