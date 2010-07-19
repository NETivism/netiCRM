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
 * $Id: Display.php 26284 2010-02-17 17:58:00Z shot $
 *
 */

require_once 'CRM/Admin/Form/Preferences.php';

/**
 * This class generates form components for the display preferences
 * 
 */
class CRM_Admin_Form_Preferences_Display extends CRM_Admin_Form_Preferences
{
    function preProcess( ) {
        parent::preProcess( );
        CRM_Utils_System::setTitle(ts('Settings - Site Preferences'));
        // add all the checkboxes
        $this->_cbs = array(
                            'contact_view_options'    => ts( 'Viewing Contacts'  ),
                            'contact_edit_options'    => ts( 'Editing Contacts'  ),
                            'advanced_search_options' => ts( 'Contact Search'    ),
                            'user_dashboard_options'  => ts( 'Contact Dashboard' )
                            );
    }

    function setDefaultValues( ) {
        $defaults = array( );

        parent::cbsDefaultValues( $defaults );
        if ( $this->_config->editor_id ) {
            $defaults['wysiwyg_editor'] = $this->_config->editor_id ;
        }
        return $defaults;
    }

    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        $this->addElement( 'select', 'wysiwyg_editor', ts('WYSIWYG Editor'), 
                           array( '' => ts( 'Textarea' ) ) + CRM_Core_PseudoConstant::wysiwygEditor( ),null );
        parent::buildQuickForm( );
    }

       
    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        if ( $this->_action == CRM_Core_Action::VIEW ) {
            return;
        }

        $this->_params = $this->controller->exportValues( $this->_name );
        $this->_config->editor_id = $this->_params['wysiwyg_editor'];

        // set default editor to session if changed
        $session =& CRM_Core_Session::singleton();
        $session->set( 'defaultWysiwygEditor', $this->_params['wysiwyg_editor'] );
        
        parent::postProcess( );
    }//end of function

}


