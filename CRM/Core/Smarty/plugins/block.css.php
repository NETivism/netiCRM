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
 * CiviCRM's Smarty css-block plugin
 *
 * Template elements tagged {css}...{/css} move stylesheet loading out of the
 * page body into the document head, mirroring the {js} block. This avoids the
 * style flicker (FOUC) caused by <link rel="stylesheet"> placed inside <body>.
 *
 * @author Poliphilo <poliphilo@netivism.com.tw>
 * @copyright CiviCRM LLC (c) 2004-2010
 */

/**
 * Smarty block function providing css loading support.
 *
 * @param array $params template call's parameters
 * @param string|null $text {css} block contents from the template
 * @param Smarty &$smarty the Smarty object
 *
 * @return string empty
 */
function smarty_block_css($params, $text, &$smarty) {
  $params['smarty_block_css'] = TRUE;
  return CRM_Utils_System::addCss($params, $text);
}
