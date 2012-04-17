<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';

/**
 * This class generates form components generic to recurring contributions
 * 
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Core_Form
{
    /**
     * The recurring contribution id, used when editing the recurring contribution
     *
     * @var int
     */
    protected $_id;
    /**
     * the id of the contact associated with this recurring contribution
     *
     * @var int
     * @public
     */
    public $_contactID;

    function preProcess( ) {
        $this->_id          = $this->get( 'id'    );
        $this->_contactID   = $this->get( 'cid'   );
    }
    
    /**
     * This function sets the default values for the form. Note that in edit/view mode
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    function setDefaultValues( ) {
        $defaults = array( );

        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            if ( isset( $this->_id ) ) {
                $params['id'] = $this->_id;
                CRM_Core_DAO::commonRetrieve( 'CRM_Contribute_DAO_ContributionRecur', $params, $defaults );
            }
        }
        return $defaults;
    }

    /**
     * Function to actually build the components of the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) {
    
        // define the fields
        $this->add('text', 'amount' , ts('Amount') , array('size' => 20), true);
        $this->add('text', 'currency' , ts('Currency') , array('size' => 20), true);
        $this->add('text', 'frequency_interval' , ts('Frequency interval') , array('size' => 20), true);
        $this->add('text', 'frequency_unit' , ts('Frequency unit') , array('size' => 20), true);
        $this->add('text', 'cycle_day' , ts('Cycle day') , array('size' => 20), true);
        
        // define the buttons
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Save'),
                                         'isDefault' => true   ),
                                 array ( 'type'       => 'cancel',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
        
    }

    /**
     * This function is called after the user submits the form
     * 
     * @access public
     * @return None
     */
    public function postProcess( )
    {
        // store the submitted values in an array
        $params = $this->exportValues();

        // if this is an update of an existing recurring contribution, pass the ID
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            $params['id'] = $this->_id;
        }
        
        // save the changes
        $ids = array();
        require_once 'CRM/Contribute/BAO/ContributionRecur.php';
        CRM_Contribute_BAO_ContributionRecur::add( $params, $ids );
        CRM_Core_Session::setStatus( ts('Your recurring contribution has been saved.') );

    }//end of function
}

