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

require_once 'CRM/Admin/Form.php';

/**
 * This class generates form components for Tag
 * 
 */
class CRM_Admin_Form_Tag extends CRM_Admin_Form
{
    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        if ($this->_action == CRM_Core_Action::DELETE) {
            if ($this->_id && $tag = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_Tag', $this->_id, 'name', 'parent_id' ) ) {
                CRM_Core_Session::setStatus( ts("This tag cannot be deleted! You must Delete all its child tags ('%1', etc) prior to deleting this tag.", array(1 => $tag)) );
                $url = CRM_Utils_System::url( 'civicrm/admin/tag', "reset=1" );
                CRM_Utils_System::redirect($url);
                return true;
            } else {
                $this->addButtons( array(
                                         array ( 'type'      => 'next',
                                                 'name'      => ts('Delete'),
                                                 'isDefault' => true   ),
                                         array ( 'type'      => 'cancel',
                                                 'name'      => ts('Cancel') ),
                                         )
                                   );
            }
        } else {
            $this->applyFilter('__ALL__', 'trim');
            
            $this->add('text', 'name', ts('Name')       ,
                       CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_Tag', 'name' ),true );
            $this->addRule( 'name', ts('Name already exists in Database.'), 'objectExists', array( 'CRM_Core_DAO_Tag', $this->_id ) );

            $this->add('text', 'description', ts('Description'), 
                       CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_Tag', 'description' ) );

            //@lobo haven't a clue why the checkbox isn't displayed (it should be checked by default
            $this->add( 'checkbox', 'is_selectable', ts("If it's a tag or a category"));

            $allTag = array ('' => '- ' . ts('select') . ' -') + CRM_Core_PseudoConstant::tag();

            if ( $this->_id ) {
                unset( $allTag[$this->_id] );
            }

            $this->add( 'select', 'parent_id', ts('Parent Tag'), $allTag );

            parent::buildQuickForm( ); 
        }
    }

       
    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        $params = $ids = array();

        // store the submitted values in an array
        $params = $this->exportValues();
        $ids['tag'] = $this->_id;
        
        if ($this->_action == CRM_Core_Action::DELETE) {
            if ($this->_id  > 0 ) {
                CRM_Core_BAO_Tag::del( $this->_id );
            }
        } else {
            CRM_Core_BAO_Tag::add($params, $ids);
        }        
        
    }//end of function
}


