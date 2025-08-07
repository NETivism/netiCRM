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
echo "This function is disabled for now";
http_response_code(404);
exit;

/* Cache the real UF, override it with the SOAP environment */
CRM_Core_Config::singleton();

if ( empty( $_GET ) ) {
    $rpInvoiceArray = [];
    $rpInvoiceArray = explode( '&' , $_POST['rp_invoice_id'] );
    foreach ( $rpInvoiceArray as $rpInvoiceValue ) {
        $rpValueArray = explode ( '=' , $rpInvoiceValue );
        if ( $rpValueArray[0] == 'm' ) {
            $value = $rpValueArray[1];
        }
    }
    $paypalIPN = new CRM_Core_Payment_PayPalProIPN( );
} else {
    $value = CRM_Utils_Array::value( 'module', $_GET );
    $paypalIPN = new CRM_Core_Payment_PayPalIPN( );
}

switch ( $value ) {
 case 'contribute':
     $paypalIPN->main( 'contribute' );
     break;
 case 'event':
     $paypalIPN->main( 'event' );
     break;
 default     :
     CRM_Core_Error::debug_log_message( "Could not get module name from request url" );
     echo "Could not get module name from request url<p>";
     break;
 }



