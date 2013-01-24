<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */


/**
 *
 */

class CRM_Core_Payment_ProcessorForm 
{
    static function preProcess( &$form, 
                                $type = null, $mode = null ) {
        if ( $type ) {
            $form->_type = $type;
        } else {
            $form->_type = CRM_Utils_Request::retrieve( 'type', 'String', $form );
        }

        require_once 'CRM/Core/BAO/PaymentProcessor.php';
        if ($form->_type) {
            $form->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($form->_type, $form->_mode);
        }
    }
    
    static function buildQuickform( &$form ) {
        require_once 'CRM/Core/Payment/Form.php';  
        if ( $form->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT ) {
            CRM_Core_Payment_Form::buildDirectDebit( $form );
        } elseif (( $form->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD ))  {
            CRM_Core_Payment_Form::buildCreditCard( $form );
        }
    }
}

