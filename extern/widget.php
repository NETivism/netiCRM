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
require_once __DIR__.'/extern.inc';

CRM_Core_Config::singleton( );
$cpageId  = CRM_Utils_Request::retrieve( 'cpageId', 'Positive', CRM_Core_DAO::$_nullObject);
if (empty($cpageId)) {
  http_response_code(400);
  exit;
}

$language = CRM_Utils_Request::retrieve( 'language', 'String', CRM_Core_DAO::$_nullObject);
if (!empty($language)) {
  global $tsLocale;
  $tsLocale = $language;
}


if (!CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Widget', $cpageId, 'is_active', 'contribution_page_id')) {
  CRM_Utils_System::civiExit();
}

$data = CRM_Contribute_BAO_Widget::getContributionPageData($cpageId);

$output = '
  var jsondata = '.json_encode( $data ) .';
';

header('Content-type: application/javascript');
echo $output;
CRM_Utils_System::civiExit( );
