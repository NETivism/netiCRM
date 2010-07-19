<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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

require_once 'CRM/Contribute/DAO/FinancialTrxn.php';

class CRM_Contribute_BAO_FinancialTrxn extends CRM_Contribute_DAO_FinancialTrxn
{
    function __construct()
    {
        parent::__construct();
    }
    
    /**
     * takes an associative array and creates a financial transaction object
     *
     * @param array  $params (reference ) an assoc array of name/value pairs
     *
     * @return object CRM_Contribute_BAO_FinancialTrxn object
     * @access public
     * @static
     */
    static function create(&$params) {
        $trxn =& new CRM_Contribute_DAO_FinancialTrxn();
        $trxn->copyValues($params);

        require_once 'CRM/Utils/Rule.php';
        if (!CRM_Utils_Rule::currencyCode($trxn->currency)) {
            require_once 'CRM/Core/Config.php';
            $config =& CRM_Core_Config::singleton();
            $trxn->currency = $config->defaultCurrency;
        }

        // if a transaction already exists for a contribution id, lets get the id
        $id = CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_FinancialTrxn',
                                           $trxn->contribution_id,
                                           'id',
                                           'contribution_id' );
        if ( $id ) {
            $trxn->id = $id;
        }
                                           
        return $trxn->save();
    }

}
