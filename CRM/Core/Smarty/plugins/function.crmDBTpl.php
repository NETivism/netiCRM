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
 * @copyright TTTP
 *
 */

/**
 * Smarty function to load a context.
 *
 * If 'name' is provided, only that data is returned.
 * If 'name' is not provided, the whole context is returned.
 *
 * @param array $params 'context', 'name' (optional), 'var'
 * @param Smarty &$smarty the Smarty object
 *
 * @return void
 */
function smarty_function_crmDBTpl($params, &$smarty) {
  // $vars = array( 'context', 'name', 'assign' ); out of which name is optional

  $contextNameData = CRM_Core_BAO_Persistent::getContext(
    $params['context'],
    CRM_Utils_Array::value('name', $params)
  );
  $smarty->assign($params['var'], $contextNameData);
}
