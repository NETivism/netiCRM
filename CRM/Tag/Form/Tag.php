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
require_once 'CRM/Core/BAO/Tag.php';
require_once 'CRM/Core/BAO/EntityTag.php';

/**
 * This class generates form components for tags
 * 
 */
class CRM_Tag_Form_Tag extends CRM_Core_Form
{

    /**
     * The contact id, used when add/edit tag
     *
     * @var int
     */
    protected $_contactId;
    
    function preProcess( ) 
    {
        $this->_contactId   = $this->get('contactId');
    }

    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        // get categories for the contact id
        $entityTag =& CRM_Core_BAO_EntityTag::getTag($this->_contactId);
        $this->assign('tagged', $entityTag);
    
        // get the list of all the categories
        $allTag =& CRM_Core_PseudoConstant::tag();
        // need to append the array with the " checked " if contact is tagged with the tag
        foreach ($allTag as $tagID => $varValue) {
            if( in_array($tagID, $entityTag)) {
                $tagAttribute = array('onclick' => "return changeRowColor(\"rowidtag_$tagID\")", 'checked' => 'checked', 'id' => "tag_{$tagID}" );
            } else {
                $tagAttribute = array('onclick' => "return changeRowColor(\"rowidtag_$tagID\")", 'id' => "tag_{$tagID}" );
            }
            
            $tagChk[$tagID] = $this->createElement('checkbox', $tagID, '', '', $tagAttribute );
        }

        $this->addGroup($tagChk, 'tagList', null, null, true);
        
        $tags = new CRM_Core_BAO_Tag ();
        $tree =$tags->getTree();
        $this->assign       ( 'tree'  , $tags->getTree() );
      
        $this->assign('tag', $allTag);

        if ( $this->_action & CRM_Core_Action::BROWSE ) {
            $this->freeze();
        } else {
            $this->addButtons( array(
                                     array ( 'type'      => 'next',
                                             'name'      => ts('Update Tags'),
                                             'isDefault' => true   ),
                                     array ( 'type'       => 'cancel',
                                             'name'      => ts('Cancel') ),
                                     )
                               );
        }
    }

       
    /**
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        // array contains the posted values
        // exportvalues is not used because its give value 1 of the checkbox which were checked by default, 
        // even after unchecking them before submitting them
        $contactTag = $_POST['tagList'];
        
        CRM_Core_BAO_EntityTag::create($contactTag, $this->_contactId );
        
        CRM_Core_Session::setStatus( ts('Your update(s) have been saved.') );
        
    }//end of function

}


