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

require_once 'CRM/Core/Page.php';
require_once 'CRM/Event/DAO/Event.php';


class CRM_Event_Page_ManageEventEdit extends CRM_Core_Page
{

    protected $_isTemplate = false;

    /**
     * Run the page.
     *
     * This method is called after the page is created. It checks for the  
     * type of action and executes that action.
     * Finally it calls the parent's run method.
     *
     * @return void
     * @access public
     *
     */
    function run()
    {
        // get the requested action
        $action = CRM_Utils_Request::retrieve('action', 'String',
                                              $this, false, 'browse'); // default to 'browse'
        
        $config =& CRM_Core_Config::singleton( );
        if ( in_array("CiviEvent", $config->enableComponents) ) {
            $this->assign('CiviEvent', true );
        }

        $this->_id  = CRM_Utils_Request::retrieve('id', 'Positive',
                                                  $this, false, 0);

        if ( $this->_id ) {
            $params = array( 'id' => $this->_id );
            require_once 'CRM/Event/BAO/Event.php';
            CRM_Event_BAO_Event::retrieve( $params, $eventInfo );

            // its an update mode, do a permission check
            require_once 'CRM/Event/BAO/Event.php';
            if ( ! CRM_Event_BAO_Event::checkPermission( $this->_id, CRM_Core_Permission::EDIT ) ) {
                CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
            } 
        }

        // figure out whether we’re handling an event or an event template
        if ($this->_id) {
            $this->_isTemplate = CRM_Utils_Array::value( 'is_template', $eventInfo );
        } elseif ($action & CRM_Core_Action::ADD) {
            $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
        }

        // assign vars to templates
        $this->assign( 'action', $action);
        $this->assign( 'id',     $this->_id );
        $this->assign( 'isTemplate', $this->_isTemplate);
        $this->assign( 'isOnlineRegistration', CRM_Utils_Array::value( 'is_online_registration', $eventInfo ));
        
        $subPage = CRM_Utils_Request::retrieve( 'subPage', 'String', $this );
        
        if ( !$subPage && ($action & CRM_Core_Action::ADD) ) {
            $subPage = 'EventInfo';
        }

        if ( $this->_id ) {
            if ( $this->_isTemplate ) {
                $title = CRM_Utils_Array::value( 'template_title', $eventInfo );
                CRM_Utils_System::setTitle(ts('Edit Event Template') . " - $title");
            } else {
                $title = CRM_Utils_Array::value( 'title', $eventInfo );
                CRM_Utils_System::setTitle(ts('Configure Event') . " - $title");
            }
            $this->assign( 'title', $title );
        } else if ( $action & CRM_Core_Action::ADD ) {
            if ( $this->_isTemplate ) {
                $title = ts('New Event Template');
                CRM_Utils_System::setTitle( $title );
            } else {
                $title = ts('New Event');
                CRM_Utils_System::setTitle( $title );
            }
            $this->assign( 'title', $title );
        }

        require_once 'CRM/Event/PseudoConstant.php';
        $statusTypes        = CRM_Event_PseudoConstant::participantStatus(null, 'is_counted = 1');
        $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(null, 'is_counted = 0');
        
        $findParticipants['statusCounted'] = implode( '/', array_values( $statusTypes ) );
        $findParticipants['statusNotCounted'] = implode( '/', array_values( $statusTypesPending ) );
        $findParticipants['urlCounted'] = CRM_Utils_System::url( 'civicrm/event/search',"reset=1&force=1&event=$this->_id&status=true" );
        $findParticipants['urlNotCounted'] = CRM_Utils_System::url( 'civicrm/event/search',"reset=1&force=1&event=$this->_id&status=false" );
        
        $this->assign('findParticipants', $findParticipants);
        
        if ($this->_id) {
            $participantListingID = CRM_Utils_Array::value( 'participant_listing_id', $eventInfo );

            if ( $participantListingID ) {
                $participantListingURL = CRM_Utils_System::url( 'civicrm/event/participant',
                                                                "reset=1&id={$this->_id}",
                                                                true, null, true, true );
                $this->assign( 'participantListingURL', $participantListingURL );
            }
        }

        $form = null;
        switch ( $subPage ) {
      
        case 'EventInfo':
            $form = 'CRM_Event_Form_ManageEvent_EventInfo';
            break;

        case 'Location':
            $form = 'CRM_Event_Form_ManageEvent_Location';
            break;

        case 'Fee':
            $form = 'CRM_Event_Form_ManageEvent_Fee';
            break;

        case 'Registration':
            $form = 'CRM_Event_Form_ManageEvent_Registration';
            break;

        case 'Friend':
            $form = 'CRM_Friend_Form_Event';
            break;
        }

        if ( $form ) {
            require_once 'CRM/Core/Controller/Simple.php'; 
            $controller =& new CRM_Core_Controller_Simple($form, $subPage, $action); 
            $controller->set('id', $this->_id); 
            $controller->set('single', true );
            $controller->process(); 
            return $controller->run(); 
        }

        if ( $this->_id ) {
            $session =& CRM_Core_Session::singleton(); 
            $session->pushUserContext( CRM_Utils_System::url( CRM_Utils_System::currentPath( ),
                                                              "action=update&reset=1&id={$this->_id}" ) );
        }
        return parent::run();
    }

}

