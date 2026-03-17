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
 * Smarty resource callback to get template source.
 *
 * @param string $tpl_name template name
 * @param string &$tpl_source reference to store template source
 * @param Smarty &$smarty_obj the Smarty object
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_template($tpl_name, &$tpl_source, &$smarty_obj) {
  $tpl_source = $tpl_name;
  return TRUE;
}

/**
 * Smarty resource callback to get template timestamp.
 *
 * @param string $tpl_name template name
 * @param int &$tpl_timestamp reference to store timestamp
 * @param Smarty &$smarty_obj the Smarty object
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
  $tpl_timestamp = time();
  return TRUE;
}

/**
 * Smarty resource callback to check secure mode.
 *
 * @param string $tpl_name template name
 * @param Smarty &$smarty_obj the Smarty object
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_secure($tpl_name, &$smarty_obj) {
  return TRUE;
}

/**
 * Smarty resource callback to check trusted mode.
 *
 * @param string $tpl_name template name
 * @param Smarty &$smarty_obj the Smarty object
 *
 * @return void
 */
function civicrm_smarty_resource_string_get_trusted($tpl_name, &$smarty_obj) {
}

/**
 * Register the 'string' resource with Smarty.
 *
 * @param Smarty|null &$template optional Smarty object
 *
 * @return void
 */
function civicrm_smarty_register_string_resource(&$template = NULL) {
  if (empty($template)) {
    $template = &CRM_Core_Smarty::singleton();
  }
  $template->register_resource(
    'string',
    [
      'civicrm_smarty_resource_string_get_template',
      'civicrm_smarty_resource_string_get_timestamp',
      'civicrm_smarty_resource_string_get_secure',
      'civicrm_smarty_resource_string_get_trusted',
    ]
  );
}
