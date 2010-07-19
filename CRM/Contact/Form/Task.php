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

require_once 'CRM/Core/SelectValues.php';
require_once 'CRM/Core/Form.php';

/**
 * This class generates form components for relationship
 * 
 */
class CRM_Contact_Form_Task extends CRM_Core_Form
{
    /**
     * the task being performed
     *
     * @var int
     */
    protected $_task;

    /**
     * The array that holds all the contact ids
     *
     * @var array
     */
    public $_contactIds;

    /**
     * The array that holds all the contact types
     *
     * @var array
     */
    public $_contactTypes;

    /**
     * The additional clause that we restrict the search with
     *
     * @var string
     */
    protected $_componentClause = null;

    /**
     * The array that holds all the component ids
     *
     * @var array
     */
    protected $_componentIds;

    /**
     * build all the data structures needed to build the form
     *
     * @param
     * @return void
     * @access public
     */
    function preProcess( ) 
    {
        $this->_contactIds   = array( );
        $this->_contactTypes = array( );

        // get the submitted values of the search form
        // we'll need to get fv from either search or adv search in the future
        $fragment = 'search';
        if ( $this->_action == CRM_Core_Action::ADVANCED ) {
            $values = $this->controller->exportValues( 'Advanced' );
            $fragment .= '/advanced';
        } else if ( $this->_action == CRM_Core_Action::PROFILE ) {
            $values = $this->controller->exportValues( 'Builder' );
            $fragment .= '/builder';
        } else if ( $this->_action == CRM_Core_Action::COPY ) {
            $values = $this->controller->exportValues( 'Custom' );
            $fragment .= '/custom';
        } else {
            $values = $this->controller->exportValues( 'Basic' );
        }
        
        //set the user context for redirection of task actions
        $url = CRM_Utils_System::url( 'civicrm/contact/' . $fragment, 'force=1' );
        $session =& CRM_Core_Session::singleton( );
        $session->replaceUserContext( $url );
        
        require_once 'CRM/Contact/Task.php';
        $this->_task         = $values['task'];
        $crmContactTaskTasks = CRM_Contact_Task::taskTitles();
        $this->assign( 'taskName', $crmContactTaskTasks[$this->_task] );
       
        // all contacts or action = save a search
        if ( ( CRM_Utils_Array::value('radio_ts', $values ) == 'ts_all' ) ||
             ( $this->_task == CRM_Contact_Task::SAVE_SEARCH ) ) {
            // need to perform action on all contacts
            // fire the query again and get the contact id's + display name
            $sortID = null;
            if ( $this->get( CRM_Utils_Sort::SORT_ID  ) ) {
                $sortID = CRM_Utils_Sort::sortIDValue( $this->get( CRM_Utils_Sort::SORT_ID  ),
                                                       $this->get( CRM_Utils_Sort::SORT_DIRECTION ) );
            }

            $selectorName = $this->controller->selectorName( );
            require_once( str_replace('_', DIRECTORY_SEPARATOR, $selectorName ) . '.php' );

            $fv          = $this->get( 'formValues' );
            $customClass = $this->get( 'customSearchClass' );
            require_once "CRM/Core/BAO/Mapping.php";
            $returnProperties = CRM_Core_BAO_Mapping::returnProperties( $values);

            eval( '$selector   =& new ' .
                  $selectorName . 
                  '( $customClass, $fv, null, $returnProperties ); '
                  );

            $params    =  $this->get( 'queryParams' );

            // fix for CRM-5165
            $sortByCharacter = $this->get( 'sortByCharacter' );
            if ( $sortByCharacter &&
                 $sortByCharacter != 1 ) {
                $params[] = array( 'sortByCharacter', '=', $sortByCharacter, 0, 0 );
            }
            $dao       =& $selector->contactIDQuery( $params, $this->_action, $sortID );

            while ( $dao->fetch( ) ) {
                $this->_contactIds[] = $dao->contact_id;
            }
        } else if ( CRM_Utils_Array::value( 'radio_ts' , $values ) == 'ts_sel') {
            // selected contacts only
            // need to perform action on only selected contacts
            foreach ( $values as $name => $value ) {
                if ( substr( $name, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                    $this->_contactIds[] = substr( $name, CRM_Core_Form::CB_PREFIX_LEN );
                }
            }
        }
        
        //contact type for pick up profiles as per selected contact types with subtypes
        //CRM-5521
        if ( $selectedTypes = CRM_Utils_Array::value( 'contact_type' , $values ) ) {
            $selectedTypes  = explode( " ", $selectedTypes );
            foreach( $selectedTypes as $ct => $dontcare ) {
                if ( strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR) === false ) {
                    $this->_contactTypes[] = $ct;  
                } else {
                    $separator = strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR);
                    $this->_contactTypes[] = substr($ct, $separator+1);
                }
            }  
        }
        
        if ( ! empty( $this->_contactIds ) ) {
            $this->_componentClause =
                ' contact_a.id IN ( ' .
                implode( ',', $this->_contactIds ) . ' ) ';
            $this->assign( 'totalSelectedContacts', count( $this->_contactIds ) );             

            $this->_componentIds = $this->_contactIds;
        }
    }

    /**
     * This function sets the default values for the form. Relationship that in edit/view action
     * the default values are retrieved from the database
     * 
     * @access public
     * @return void
     */
    function setDefaultValues( ) 
    {
        $defaults = array( );
        return $defaults;
    }
    

    /**
     * This function is used to add the rules for form.
     *
     * @return void
     * @access public
     */
    function addRules( )
    {
    }


    /**
     * Function to actually build the form
     *
     * @return void
     * @access public
     */
    public function buildQuickForm( ) 
    {
        $this->addDefaultButtons(ts('Confirm Action'));        
    }

       
    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return void
     */
    public function postProcess() 
    {
    }//end of function

    /**
     * simple shell that derived classes can call to add buttons to
     * the form with a customized title for the main Submit
     *
     * @param string $title title of the main button
     * @param string $type  button type for the form after processing
     * @return void
     * @access public
     */
    function addDefaultButtons( $title, $nextType = 'next', $backType = 'back' ) {
        $this->addButtons( array(
                                 array ( 'type'      => $nextType,
                                         'name'      => $title,
                                         'isDefault' => true   ),
                                 array ( 'type'      => $backType,
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

}


