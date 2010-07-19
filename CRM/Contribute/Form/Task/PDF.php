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
 *
 */

require_once 'CRM/Contribute/Form/Task.php';

/**
 * This class provides the functionality to email a group of
 * contacts. 
 */
class CRM_Contribute_Form_Task_PDF extends CRM_Contribute_Form_Task {

    /**
     * Are we operating in "single mode", i.e. updating the task of only
     * one specific contribution?
     *
     * @var boolean
     */
    public $_single = false;

    protected $_rows;

    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    
    function preProcess( ) {
        $id = CRM_Utils_Request::retrieve( 'id', 'Positive',
                                           $this, false );

        if ( $id ) {
            $this->_contributionIds    = array( $id );
            $this->_componentClause =
                " civicrm_contribution.id IN ( $id ) ";
            $this->_single             = true;
            $this->assign( 'totalSelectedContributions', 1 );
        } else {
            parent::preProcess( );
        }

        // check that all the contribution ids have pending status
        $query = "
SELECT count(*)
FROM   civicrm_contribution
WHERE  contribution_status_id != 1
AND    {$this->_componentClause}";
        $count = CRM_Core_DAO::singleValueQuery( $query,
                                                 CRM_Core_DAO::$_nullArray );
        if ( $count != 0 ) {
            CRM_Core_Error::statusBounce( "Please select only online contributions with Completed status." ); 
        }

        // we have all the contribution ids, so now we get the contact ids
        parent::setContactIDs( );
        $this->assign( 'single', $this->_single );
        
        $breadCrumb = array ( array('title' => ts('Search Results'),
                                    'url'   => CRM_Utils_System::url( 'civicrm/contribute/search'
                                                                      )) );
        CRM_Utils_System::appendBreadCrumb( $breadCrumb );
        CRM_Utils_System::setTitle( ts('Print Contribution Receipts') );
    }
    
    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    public function buildQuickForm()
    {
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Download Receipt(s)'),
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'back',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
        // get all the details needed to generate a receipt
        $contribIDs = implode( ',', $this->_contributionIds );

        require_once 'CRM/Contribute/Form/Task/Status.php';
        $details =& CRM_Contribute_Form_Task_Status::getDetails( $contribIDs );

        require_once 'CRM/Core/Payment/BaseIPN.php';
        $baseIPN = new CRM_Core_Payment_BaseIPN( );

        $message  =  array( );
        $template =& CRM_Core_Smarty::singleton( );

        foreach ( $details as $contribID => $detail ) {
            $input = $ids = $objects = array( );
            
            $input['component'] = $detail['component'];

            $ids['contact'     ]      = $detail['contact'];
            $ids['contribution']      = $contribID;
            $ids['contributionRecur'] = null;
            $ids['contributionPage']  = null;
            $ids['membership']        = $detail['membership'];
            $ids['participant']       = $detail['participant'];
            $ids['event']             = $detail['event'];

            if ( ! $baseIPN->validateData( $input, $ids, $objects, false ) ) {
                CRM_Core_Error::fatal( );
            }

            $contribution =& $objects['contribution'];
            // CRM_Core_Error::debug('o',$objects);


            // set some fake input values so we can reuse IPN code
            $input['amount']     = $contribution->total_amount;
            $input['is_test']    = $contribution->is_test;
            $input['fee_amount'] = $contribution->fee_amount;
            $input['net_amount'] = $contribution->net_amount;
            $input['trxn_id']    = $contribution->trxn_id;
            $input['trxn_date']  = isset( $contribution->trxn_date ) ? $contribution->trxn_date : null;

            // CRM_Core_Error::debug('input',$input);
            
            $values = array( );
            $mail = $baseIPN->sendMail( $input, $ids, $objects, $values, false, true );
            $mail = str_replace( "\n\n", "<p>", $mail );
            $mail = str_replace( "\n", "<br/>", $mail );

            $message[] = $mail;

            // reset template values before processing next transactions
            $template->clearTemplateVars( );
        }
        
        require_once 'CRM/Utils/PDF/Utils.php';
        CRM_Utils_PDF_Utils::domlib( $message, "civicrmContributionReceipt.pdf" );
        exit( );
    }

}


