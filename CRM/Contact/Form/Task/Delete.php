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

require_once 'CRM/Contact/Form/Task.php';
require_once 'CRM/Core/Menu.php';
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'CRM/Contact/BAO/Contact.php';
/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * deletion.
 */
class CRM_Contact_Form_Task_Delete extends CRM_Contact_Form_Task {

    /** 
     * Are we operating in "single mode", i.e. sending email to one 
     * specific contact? 
     * 
     * @var boolean 
     */ 
    protected $_single = false; 

    /** 
     * build all the data structures needed to build the form 
     * 
     * @return void 
     * @access public 
     */ 
    function preProcess( ) { 
        
        //check for delete
        if ( !CRM_Core_Permission::check( 'delete contacts' ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );  
        }
        
        $cid = CRM_Utils_Request::retrieve( 'cid', 'Positive',
                                            $this, false ); 
        
        if ( $cid ) { 
            require_once 'CRM/Contact/BAO/Contact/Permission.php';
            if ( !CRM_Contact_BAO_Contact_Permission::allow( $cid, CRM_Core_Permission::EDIT ) ) {
                CRM_Core_Error::fatal( ts( 'You do not have permission to delete this contact. Note: you can delete contacts if you can edit them.' ) );
            }

            $this->_contactIds = array( $cid ); 
            $this->_single     = true; 
            $this->assign( 'totalSelectedContacts', 1 );
        } else {
            parent::preProcess( );
        }
    }
    
    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    function buildQuickForm( ) {
        if ( $this->_single ) {
            // also fix the user context stack in case the user hits cancel
            $session =& CRM_Core_Session::singleton( );
            $session->replaceUserContext( CRM_Utils_System::url('civicrm/contact/view',
                                                                'reset=1&cid=' . $this->_contactIds[0] ) );
            $this->addDefaultButtons( ts('Delete Contacts'), 'done', 'cancel' );
        } else {
            $this->addDefaultButtons( ts('Delete Contacts'), 'done' );
        }
    }

    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
        $session =& CRM_Core_Session::singleton( );
        $currentUserId = $session->get( 'userID' );
        
        $selfDelete = false;
        $deletedContacts = 0;
        foreach ( $this->_contactIds as $contactId ) {
            if ($currentUserId == $contactId) {
                $selfDelete = true;
                continue;
            }

            if ( CRM_Contact_BAO_Contact::deleteContact( $contactId ) ) {
                $deletedContacts++;
            }
        }
        if ( ! $this->_single ) {
            $status = array( );
            $status = array(
                            ts( 'Deleted Contact(s): %1', array(1 => $deletedContacts)),
                            ts('Total Selected Contact(s): %1', array(1 => count($this->_contactIds))),
                            );
            
            if ( $selfDelete ) {
                $display_name = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact',
                                                             $currentUserId,
                                                             'display_name' );
                $status[] = ts('The contact record which is linked to the currently logged in user account - \'%1\' - cannot be deleted.', array(1 => $display_name));
            }
        } else {
            if ( $deletedContacts ) {
                
                $isAdvanced      = $session->get( 'isAdvanced' );
                $isSearchBuilder = $session->get( 'isSearchBuilder' );
                
                if ( $isAdvanced == 1 ) {
                    $session->replaceUserContext( CRM_Utils_System::url( 'civicrm/contact/search/advanced', 'force=1' ) );
                } else if ( ( $isAdvanced == 2 ) && ( $isSearchBuilder == 1 ) ) {
                    $session->replaceUserContext( CRM_Utils_System::url( 'civicrm/contact/search/builder', 'force=1' ) );
                } else {
                    $session->replaceUserContext( CRM_Utils_System::url( 'civicrm/contact/search/basic', 'force=1' ) );
                }
                
                $status = ts('Selected contact was deleted sucessfully.');
            } else {
                $status = array(
                                ts('Selected contact cannot be deleted.')
                                ); 
                if ( $selfDelete ) {
                    $display_name = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact',
                                                                 $currentUserId,
                                                                 'display_name' );
                    $status[] = ts('This contact record is linked to the currently logged in user account - \'%1\' - and cannot be deleted.', array(1 => $display_name));
                } else {
                    $status[] = ts( 'The contact might be the Membership Organization of a Membership Type. You will need to edit the Membership Type and change the Membership Organization before you can delete this contact.' );
                }
            }
        }

        CRM_Core_Session::setStatus( $status );
    }//end of function


}


