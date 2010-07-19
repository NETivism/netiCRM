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
require_once 'CRM/Contact/Form/Task/EmailCommon.php';
require_once 'CRM/Core/Menu.php';
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'CRM/Contact/BAO/Contact.php';
/**
 * This class provides the functionality to email a group of
 * contacts. 
 */
class CRM_Contact_Form_Task_Email extends CRM_Contact_Form_Task {

    /**
     * Are we operating in "single mode", i.e. sending email to one
     * specific contact?
     *
     * @var boolean
     */
    public $_single = false;

    /**
     * Are we operating in "single mode", i.e. sending email to one
     * specific contact?
     *
     * @var boolean
     */
    public $_noEmails = false;

    /**
     * all the existing templates in the system
     *
     * @var array
     */
    public $_templates = null;

    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    
    function preProcess( ) {
        // store case id if present
        $this->_caseId  = CRM_Utils_Request::retrieve( 'caseid', 'Positive', $this, false );
        $this->_context = CRM_Utils_Request::retrieve( 'context', 'String', $this );

        $cid = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, false );

        if ( $cid || $this->_context == 'standalone' ) {
            CRM_Contact_Form_Task_EmailCommon::preProcessSingle( $this, $cid );
        } else {
            parent::preProcess( );
        }
        $this->assign( 'single', $this->_single );
        require_once 'CRM/Core/Permission.php';
        if ( CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
            $this->assign( 'isAdmin', 1 );
        }
    }
    
    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    public function buildQuickForm()
    {
        //enable form element
        $this->assign( 'suppressForm', false );

        CRM_Contact_Form_Task_EmailCommon::buildQuickForm( $this );
    }

    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
        CRM_Contact_Form_Task_EmailCommon::postProcess( $this );
    }

}


