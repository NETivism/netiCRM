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
require_once 'CRM/Core/BAO/EntityTag.php';

/**
 * This class provides the functionality to remove tags of contact(s). 
 */
class CRM_Contact_Form_Task_RemoveFromTag extends CRM_Contact_Form_Task {

    /**
     * name of the tag
     *
     * @var string
     */
    protected $_name;

    /**
     * all the tags in the system
     *
     * @var array
     */
    protected $_tags;

    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    function buildQuickForm( ) {
        // add select for tag
        $this->_tags =  CRM_Core_PseudoConstant::tag( );
        foreach ($this->_tags as $tagID => $tagName) {
            $this->_tagElement =& $this->addElement('checkbox', "tag[$tagID]", null, $tagName);
        }
    
        $this->addDefaultButtons( ts('Remove Tag Contacts') );
    }
    
    function addRules( )
    {
        $this->addFormRule( array( 'CRM_Contact_Form_Task_RemoveFromTag', 'formRule' ) );
    }
    
    static function formRule(&$form,&$rule) {
        $errors =array();
        if(empty($form['tag'])) {
            $errors['_qf_default'] = "Please Check atleast one checkbox";
        }
        return $errors;
    }
    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
       
        $tagId         = $this->controller->exportValue( 'RemoveFromTag', 'tag'  );
        $this->_name   = array(); 
        foreach($tagId as $key=>$dnc) {
            $this->_name[]   = $this->_tags[$key];
            
            list( $total, $removed, $notRemoved ) = CRM_Core_BAO_EntityTag::removeContactsFromTag( $this->_contactIds, $key );
            $status = array(
                            'Contact(s) tagged as: '       . implode(',', $this->_name),
                            'Total Selected Contact(s): '  . $total
                            );
        }
        if ( $removed ) {
            $status[] = 'Total Contact(s) to be  removed from tag: ' . $removed;
        }
        if ( $notRemoved ) {
            $status[] = 'Total Contact(s) already removed: ' . $notRemoved;
        }
        CRM_Core_Session::setStatus( $status );
    }//end of function

}


