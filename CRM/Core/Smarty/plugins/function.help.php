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
 * Generate help icon/text HTML to be inserted into the template.
 *
 * @param array $params (id, text, file, helpicon)
 * @param Smarty &$smarty reference to the Smarty object
 *
 * @return string|void the help HTML
 */
function smarty_function_help($params, &$smarty) {
  if (!isset($params['id']) || !isset($smarty->_tpl_vars['config'])) {
    return;
  }

  $help = '';
  if (isset($params['text'])) {
    $help = '<div class="crm-help">' . $params['text'] . '</div>';
  }

  if (isset($params['file'])) {
    $file = $params['file'];
  }
  elseif (isset($smarty->_tpl_vars['tplFile'])) {
    $file = $smarty->_tpl_vars['tplFile'];
  }
  else {
    return;
  }

  if (isset($params['helpicon'])) {
    $helpclass = $params['helpicon'];
    $helpselector = '.'.str_replace(' ', '.', $helpclass);
    ;
  }
  else {
    $helpclass = 'helpicon';
    $helpselector = '.helpicon';
  }

  $file = str_replace('.tpl', '.hlp', $file);
  $id = urlencode($params['id']);
  if ($id == 'accesskeys') {
    $file = 'CRM/common/accesskeys.hlp';
  }

  $config = CRM_Core_Config::singleton();
  $smarty->assign('id', $params['id']);
  if (!$help) {
    $help = $smarty->fetch($file);
  }
  return <<< EOT
  <script type="text/javascript"> cj( function() { cj("{$helpselector}").toolTip({ skipVerticalComparison: true }); });</script>
  <div class="{$helpclass}">&nbsp;<span id="{$id}_help" style="display:none">$help</span></div>&nbsp;&nbsp;&nbsp;
  EOT;
}
