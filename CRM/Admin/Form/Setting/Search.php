<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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



/**
 * This class generates form components for Search Parameters
 *
 */
class CRM_Admin_Form_Setting_Search extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Contacts Search'));

    $this->addYesNo('includeWildCardInName', ts('Automatic Wildcard'));
    $this->addYesNo('includeEmailInName', ts('Include Email'));
    $this->addYesNo('includeNickNameInName', ts('Include Nickname'));

    $this->addYesNo('includeAlphabeticalPager', ts('Include Alphabetical Pager'));
    $this->addYesNo('includeOrderByClause', ts('Include Order By Clause'));

    $this->addElement('text', 'smartGroupCacheTimeout', ts('Smart group cache timeout'),
      ['size' => 3, 'maxlength' => 5]
    );


    $types = ['Contact', 'Individual', 'Organization', 'Household'];
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    $this->add('select', 'defaultSearchProfileID', ts('Default Contact Search Profile'),
      ['' => ts('- select -')] + $profiles
    );

    $options = [ts('Contact Name') => 1] + array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, TRUE));
    $this->addCheckBox('autocompleteContactSearch', ts('Autocomplete Contact Search'), $options);
    $element = $this->getElement('autocompleteContactSearch');
    $element->_elements[0]->_flagFrozen = TRUE;
    parent::buildQuickForm();
  }
}

